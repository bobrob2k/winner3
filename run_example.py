#!/usr/bin/env python3
"""
Example usage script for the Enhanced SMTP Scanner
This demonstrates how to run the scanner with different configurations
"""

import subprocess
import sys
import os

def run_scanner_example():
    """Run the SMTP scanner with example configuration"""
    
    print("Enhanced SMTP Scanner - Example Usage")
    print("=" * 50)
    
    # Check if required files exist
    required_files = ['smtp_scanner.py', 'ips.txt', 'users.txt', 'pass.txt']
    missing_files = [f for f in required_files if not os.path.exists(f)]
    
    if missing_files:
        print(f"Missing required files: {', '.join(missing_files)}")
        print("Please ensure all files are present before running the scanner.")
        return
    
    print("Configuration Check:")
    print("✓ smtp_scanner.py found")
    print("✓ ips.txt found")
    print("✓ users.txt found") 
    print("✓ pass.txt found")
    print()
    
    # Show file contents
    print("Current Configuration:")
    print("-" * 30)
    
    try:
        with open('ips.txt', 'r') as f:
            hosts = [line.strip() for line in f if line.strip() and not line.startswith('#')]
        print(f"Hosts to scan: {len(hosts)}")
        for host in hosts[:3]:  # Show first 3
            print(f"  - {host}")
        if len(hosts) > 3:
            print(f"  ... and {len(hosts) - 3} more")
    except:
        print("Could not read ips.txt")
    
    try:
        with open('users.txt', 'r') as f:
            users = [line.strip() for line in f if line.strip() and not line.startswith('#')]
        print(f"Users to test: {len(users)}")
    except:
        print("Could not read users.txt")
    
    try:
        with open('pass.txt', 'r') as f:
            passwords = [line.strip() for line in f if line.strip() and not line.startswith('#')]
        print(f"Passwords to test: {len(passwords)}")
    except:
        print("Could not read pass.txt")
    
    print()
    print("Example Commands:")
    print("-" * 30)
    print("1. Basic scan with 5 threads:")
    print("   python smtp_scanner.py 5")
    print()
    print("2. Verbose scan with 10 threads:")
    print("   python smtp_scanner.py 10 verbose")
    print()
    print("3. Conservative scan with 2 threads:")
    print("   python smtp_scanner.py 2")
    print()
    
    # Ask user if they want to run
    response = input("Would you like to run a test scan now? (y/n): ").lower().strip()
    
    if response in ['y', 'yes']:
        threads = input("Enter number of threads (default 5): ").strip()
        if not threads:
            threads = "5"
        
        verbose = input("Enable verbose logging? (y/n): ").lower().strip()
        
        cmd = ['python', 'smtp_scanner.py', threads]
        if verbose in ['y', 'yes']:
            cmd.append('verbose')
        
        print(f"\nRunning: {' '.join(cmd)}")
        print("=" * 50)
        
        try:
            subprocess.run(cmd)
        except KeyboardInterrupt:
            print("\nScan interrupted by user")
        except Exception as e:
            print(f"Error running scanner: {e}")
    
    print("\nRemember to:")
    print("- Only scan systems you own or have permission to test")
    print("- Check the generated files for results:")
    print("  * WORKING_SMTP_DOWNLOAD.txt (formatted results)")
    print("  * working_smtp_servers.txt (raw data)")
    print("  * smtp_scanner.log (execution logs)")

if __name__ == "__main__":
    run_scanner_example()