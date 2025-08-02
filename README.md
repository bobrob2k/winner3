# Enhanced SMTP Scanner

A powerful, multi-threaded SMTP server scanner that focuses on finding working authenticated SMTP servers for security testing purposes.

## Features

- **Authentication-Focused**: Only saves servers with valid authentication
- **Email Notifications**: Get real-time alerts when working SMTP servers are found
- **Rate-Limited Notifications**: Prevents spam with intelligent rate limiting
- **Downloadable Results**: Creates formatted text files for easy download
- **Multi-threaded**: Fast scanning with configurable thread count
- **Comprehensive Logging**: Detailed logs for debugging and analysis
- **Input Validation**: Proper error handling and connection management

## Files Generated

- `WORKING_SMTP_DOWNLOAD.txt` - Formatted, human-readable results
- `working_smtp_servers.txt` - Raw data in pipe-delimited format
- `smtp_scanner.log` - Detailed execution logs
- `notification_history.json` - Email notification tracking

## Configuration

Edit the SMTP configuration at the top of `smtp_scanner.py`:

```python
# --- SMTP CONFIGURATION (EDIT THESE) ---
SMTP_SERVER = "your-smtp-server.com"
SMTP_PORT = 587
SMTP_USER = "your-email@domain.com"
SMTP_PASS = "your-password"
NOTIFY_EMAIL = "alerts@yourdomain.com"
# ---------------------------------------
```

## Input Files

Create these three files with your targets:

### `ips.txt`
```
mail.target1.com
smtp.target2.org
192.168.1.100
mx.example.com
```

### `users.txt`
```
admin
mail
test
postmaster
```

### `pass.txt`
```
password
123456
admin
test123
```

## Usage

### Basic Usage
```bash
python smtp_scanner.py <threads>
```

### With Verbose Logging
```bash
python smtp_scanner.py <threads> verbose
```

### Examples
```bash
# Scan with 10 threads
python smtp_scanner.py 10

# Scan with 20 threads and verbose output
python smtp_scanner.py 20 verbose
```

## Security Features

- **Connection Timeouts**: Prevents hanging connections
- **Proper SMTP Protocol**: Uses standard SMTP commands
- **Clean Disconnection**: Properly closes all connections
- **Error Handling**: Robust error handling for network issues

## Output Format

### Working SMTP Servers File Format
```
host|username@domain|password|domain|timestamp
```

### Download File Format
```
============================================================
WORKING AUTHENTICATED SMTP SERVERS
============================================================
Generated: 2024-01-15 14:30:25
Total servers: 5
============================================================

Server #1
Host: mail.example.com
Username: admin@example.com
Password: password123
Domain: example.com
----------------------------------------
```

## Rate Limiting

- Maximum 10 notifications per hour
- 5-minute minimum interval between notifications for same host
- Configurable limits in the script

## Legal Notice

**WARNING**: This tool is for authorized security testing only. Only use on systems you own or have explicit permission to test. Unauthorized access to computer systems is illegal.

## Requirements

- Python 3.6+
- Standard library modules only (no external dependencies)

## Troubleshooting

### No Results Found
1. Check that target hosts are reachable
2. Verify usernames and passwords are correct
3. Check firewall settings
4. Review logs for connection errors

### Email Notifications Not Working
1. Verify SMTP configuration
2. Check credentials
3. Ensure SMTP server allows connections
4. Review notification rate limits

### Performance Issues
1. Reduce thread count
2. Check network latency
3. Increase timeout values
4. Monitor system resources

## License

This software is provided for educational and authorized security testing purposes only.