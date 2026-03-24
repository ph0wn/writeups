#!/usr/bin/env python3
"""
Test script for the add functionality of the Food Service.
Tests adding food items via POST requests to the /add endpoint.

Usage: python3 test_instance.py <host> [options]

Examples:
  python3 test_instance.py http://localhost:5000           # Interactive menu to choose tests
  python3 test_instance.py localhost:5000 --test-all       # Run all vulnerability tests without menu
"""

import sys
import requests
import argparse
import re
from urllib.parse import urljoin


# ANSI color codes
class Colors:
    GREEN = '\033[92m'
    RED = '\033[91m'
    YELLOW = '\033[93m'
    BLUE = '\033[94m'
    RESET = '\033[0m'
    BOLD = '\033[1m'


def extract_food_filenames(html_content):
    """Extract food filenames from the HTML page."""
    # Look for patterns like: name="filename" value="food123"
    pattern = r'name="filename"\s+value="(food\d+)"'
    filenames = sorted(re.findall(pattern, html_content))
    return set(filenames)


def add_items(endpoints, test_items):
    """
    Add test items to the service.
    
    Returns:
        list: List of (item, filename) tuples that were successfully added
        int: Number of failures
    """
    add_endpoint = endpoints['add']
    index_endpoint = endpoints['index']
    
    files_to_delete = []
    failed = 0
    
    print(f"\n{Colors.BLUE}{Colors.BOLD}Adding test items...{Colors.RESET}")
    print("-" * 60)
    
    for item in test_items:
        try:
            # Get filename list BEFORE adding
            response = requests.get(index_endpoint, timeout=5)
            before_filenames = extract_food_filenames(response.text) if response.status_code == 200 else set()
            
            # Send POST request to /add endpoint
            response = requests.post(
                add_endpoint,
                data={'name': item},
                timeout=5,
                allow_redirects=True
            )
            
            # Check if request was successful
            if response.status_code == 200:
                # Get filename list AFTER adding
                response = requests.get(index_endpoint, timeout=5)
                after_filenames = extract_food_filenames(response.text) if response.status_code == 200 else set()
                
                # Find the new filename(s)
                new_files = after_filenames - before_filenames
                
                if new_files:
                    new_file = list(new_files)[0]  # Get first new file
                    print(f"{Colors.GREEN}✓ Added '{item}': Status {response.status_code} (filename={new_file}){Colors.RESET}")
                    files_to_delete.append((item, new_file))
                else:
                    print(f"{Colors.RED}✗ Added '{item}' but couldn't determine filename{Colors.RESET}")
                    failed += 1
            else:
                print(f"{Colors.RED}✗ Failed to add '{item}': Status {response.status_code}{Colors.RESET}")
                failed += 1
                
        except requests.exceptions.RequestException as e:
            print(f"{Colors.RED}✗ Error adding '{item}': {e}{Colors.RESET}")
            failed += 1
    
    return files_to_delete, failed


def verify_items_on_page(endpoints, files_to_delete):
    """
    Verify that added items appear on the index page.
    
    Returns:
        int: Number of verified items
    """
    index_endpoint = endpoints['index']
    verified_items = 0
    
    print("-" * 60)
    print("Verifying items on index page...")
    
    try:
        response = requests.get(index_endpoint, timeout=5)
        if response.status_code == 200:
            content = response.text
            for item, _ in files_to_delete:
                if item in content:
                    print(f"{Colors.GREEN}✓ Found '{item}' on index page{Colors.RESET}")
                    verified_items += 1
                else:
                    print(f"{Colors.RED}✗ Could not find '{item}' on index page{Colors.RESET}")
        else:
            print(f"{Colors.RED}✗ Failed to fetch index page: Status {response.status_code}{Colors.RESET}")
    except requests.exceptions.RequestException as e:
        print(f"{Colors.RED}✗ Error fetching index page: {e}{Colors.RESET}")
    
    return verified_items


def test_traversal_vulnerability(endpoints, payload, indicators, test_name, detection_desc):
    """
    Generic function to test for path traversal vulnerabilities.
    
    Args:
        endpoints: dict with 'add', 'delete', 'index' endpoints
        payload: the payload string to test
        indicators: list of strings to search for in page content
        test_name: name of the test (for display, e.g., "flag file exposure")
        detection_desc: description of what was detected (e.g., "flag file content")
    
    Returns:
        bool: True if vulnerability found, False otherwise
    """
    add_endpoint = endpoints['add']
    delete_endpoint = endpoints['delete']
    index_endpoint = endpoints['index']
    
    print(f"Testing payload: {payload}")
    try:
        # Get filename list BEFORE adding
        response = requests.get(index_endpoint, timeout=5)
        before_filenames = extract_food_filenames(response.text) if response.status_code == 200 else set()
        
        # Send POST request with traversal payload
        response = requests.post(
            add_endpoint,
            data={'name': payload},
            timeout=5,
            allow_redirects=True
        )
        
        # Get filename list AFTER adding
        response = requests.get(index_endpoint, timeout=5)
        after_filenames = extract_food_filenames(response.text) if response.status_code == 200 else set()
        
        # Find the new filename
        new_files = after_filenames - before_filenames
        
        if new_files:
            new_file = list(new_files)[0]
            page_content = response.text
            
            # Check if any indicators are displayed
            found_indicators = [ind for ind in indicators if ind in page_content]
            
            if found_indicators:
                print(f"{Colors.RED}{Colors.BOLD}✗ VULNERABILITY DETECTED!{Colors.RESET}")
                print(f"{Colors.RED}  Path traversal successful: payload '{payload}' exposed {detection_desc}{Colors.RESET}")
                print(f"{Colors.RED}  Found indicators: {', '.join(found_indicators)}{Colors.RESET}")
                
                # Clean up the traversal item
                print(f"{Colors.YELLOW}Removing traversal item: {new_file}...{Colors.RESET}")
                try:
                    delete_response = requests.post(
                        delete_endpoint,
                        data={'filename': new_file},
                        timeout=5,
                        allow_redirects=True
                    )
                    if delete_response.status_code == 200:
                        print(f"{Colors.GREEN}✓ Removed traversal item{Colors.RESET}")
                    else:
                        print(f"{Colors.RED}✗ Failed to remove traversal item{Colors.RESET}")
                except requests.exceptions.RequestException as e:
                    print(f"{Colors.RED}✗ Error removing traversal item: {e}{Colors.RESET}")
                
                return True
            else:
                print(f"{Colors.GREEN}✓ Payload '{payload}' added but NOT exposed{Colors.RESET}")
                # Clean up the test item
                try:
                    delete_response = requests.post(
                        delete_endpoint,
                        data={'filename': new_file},
                        timeout=5,
                        allow_redirects=True
                    )
                    if delete_response.status_code == 200:
                        print(f"{Colors.GREEN}✓ Test item removed{Colors.RESET}")
                except requests.exceptions.RequestException as e:
                    print(f"{Colors.RED}✗ Error removing test item: {e}{Colors.RESET}")
        else:
            print(f"{Colors.YELLOW}⚠ Couldn't determine filename for payload '{payload}'{Colors.RESET}")
            
    except requests.exceptions.RequestException as e:
        print(f"{Colors.RED}✗ Error testing payload '{payload}': {e}{Colors.RESET}")
    
    return False


def test_vulnerabilities(endpoints):
    """
    Test for path traversal vulnerabilities with flag file content.
    
    Returns:
        bool: True if vulnerability found, False otherwise
    """
    print("-" * 60)
    print(f"{Colors.BLUE}{Colors.BOLD}Testing for path traversal vulnerabilities...{Colors.RESET}")
    
    traversal_payloads = [
        '../../opt/flag.txt',
        '../../opt/flag',
        '../../home/app/flag.txt'
    ]
    
    for payload in traversal_payloads:
        print(f"\n{payload}:")
        if test_traversal_vulnerability(
            endpoints,
            payload,
            ['ph0wn'],
            'flag exposure',
            'file content containing ph0wn'
        ):
            return True
    
    return False


def test_passwd_traversal(endpoints):
    """
    Test for /etc/passwd exposure via path traversal.
    
    Returns:
        bool: True if vulnerability found, False otherwise
    """
    payload = '../../etc/passwd'
    
    print(f"\n{Colors.BLUE}{Colors.BOLD}Testing for /etc/passwd exposure...{Colors.RESET}")
    print(f"Testing payload: {payload}")
    
    passwd_indicators = ['root:', 'bin:', 'daemon:', 'adm:', 'sync:']
    
    return test_traversal_vulnerability(
        endpoints,
        payload,
        passwd_indicators,
        '/etc/passwd exposure',
        '/etc/passwd content'
    )


def test_python_file_traversal(endpoints):
    """
    Test for Python file exposure via path traversal (e.g., ../../home/app/main.py).
    
    Returns:
        bool: True if vulnerability found, False otherwise
    """
    payload = '../../home/app/main.py'
    
    print(f"\n{Colors.BLUE}{Colors.BOLD}Testing for Python file exposure...{Colors.RESET}")
    print(f"Testing payload: {payload}")
    
    # Python file indicators
    python_indicators = [
        'import ',
        'def ',
        'class ',
        'from ',
        'if __name__',
        '@app.route',
        'Flask',
    ]
    
    return test_traversal_vulnerability(
        endpoints,
        payload,
        python_indicators,
        'Python file exposure',
        'Python file content'
    )


def test_tricky_traversal(endpoints):
    """
    Test for path traversal vulnerabilities using obfuscated/encoded payloads.
    Tests various encoding and obfuscation techniques to bypass simple detection.
    
    Returns:
        bool: True if vulnerability found, False otherwise
    """
    print(f"\n{Colors.BLUE}{Colors.BOLD}Testing for tricky/obfuscated path traversals...{Colors.RESET}")
    
    # Various ways to express the same path traversal
    tricky_payloads = [
        '..%2f..%2fopt%2fflag.txt',         # URL encoded: ../app/flag.txt
        '..%252f..%252fopt%252fflag.txt',          # Double URL encoded
        '..\\..\\opt\\flag.txt',                # Backslash instead of forward slash
        '..%5c..%5copt%5cflag.txt',              # URL encoded backslash
        '../../opt/flag.txt%00',               # Null byte termination
        '....//....//opt/flag.txt',         # Double slash variation
        '..;/opt/flag.txt',                 # Semicolon injection
    ]
    
    for payload in tricky_payloads:
        print(f"\nTesting payload: {payload}")
        if test_traversal_vulnerability(
            endpoints,
            payload,
            ['ph0wn'],
            'tricky traversal',
            'flag content via obfuscated payload'
        ):
            return True
    
    return False
def cleanup_items(endpoints, files_to_delete):
    """
    Delete all tracked items.
    
    Returns:
        int: Number of failures during cleanup
    """
    delete_endpoint = endpoints['delete']
    failed = 0
    
    print("-" * 60)
    print(f"{Colors.YELLOW}Cleaning up - removing normal test items...{Colors.RESET}")
    
    for item, filename in files_to_delete:
        try:
            delete_response = requests.post(
                delete_endpoint,
                data={'filename': filename},
                timeout=5,
                allow_redirects=True
            )
            
            if delete_response.status_code == 200:
                print(f"{Colors.GREEN}✓ Removed '{item}' (filename={filename}){Colors.RESET}")
            else:
                print(f"{Colors.RED}✗ Failed to remove '{item}' (filename={filename}): Status {delete_response.status_code}{Colors.RESET}")
                failed += 1
        except requests.exceptions.RequestException as e:
            print(f"{Colors.RED}✗ Error removing '{item}' (filename={filename}): {e}{Colors.RESET}")
            failed += 1
    
    return failed


def test_add_functionality(host, test_flag=True, test_passwd=True, test_python=True, test_tricky=True):
    """
    Test the add functionality of the food service.
    
    Args:
        host: The host URL to test
        test_flag: Whether to test for flag file exposure vulnerabilities
        test_passwd: Whether to test for /etc/passwd exposure vulnerabilities
        test_python: Whether to test for Python file exposure vulnerabilities
        test_tricky: Whether to test for obfuscated/tricky traversal vulnerabilities
    """
    
    # Ensure host has proper format
    if not host.startswith(('http://', 'https://')):
        host = f'http://{host}'
    
    # Setup endpoints
    endpoints = {
        'add': urljoin(host, '/add'),
        'delete': urljoin(host, '/delete'),
        'index': urljoin(host, '/')
    }
    
    test_items = [
        'Pizza',
        'Sushi',
        'Burger with cheese',
        'Pasta Carbonara'
    ]
    
    print(f"\n{Colors.BLUE}{Colors.BOLD}Testing add functionality on {host}{Colors.RESET}")
    
    # Phase 1: Add normal items
    files_to_delete, add_failures = add_items(endpoints, test_items)
    
    # Phase 2: Verify items on page
    verified_items = verify_items_on_page(endpoints, files_to_delete)
    
    # Phase 3: Print summary
    print("-" * 60)
    print(f"\n{Colors.BOLD}Test Results:{Colors.RESET}")
    print(f"  Items added: {len(files_to_delete)}/{len(test_items)}")
    print(f"  Items verified on page: {verified_items}/{len(files_to_delete)}")
    print(f"  Add failures: {add_failures}")
    print(f"  Vulnerability tests: ", end="")
    tests = []
    if test_flag:
        tests.append("flag")
    if test_passwd:
        tests.append("passwd")
    if test_python:
        tests.append("python")
    if test_tricky:
        tests.append("tricky")
    print(", ".join(tests) if tests else "none")
    
    # Phase 4: Test for flag vulnerabilities (if requested)
    vulnerability_found = False
    if test_flag:
        vulnerability_found = test_vulnerabilities(endpoints)
    
    # Phase 5: Test for /etc/passwd exposure (if requested)
    if test_passwd and not vulnerability_found:
        passwd_exposed = test_passwd_traversal(endpoints)
        vulnerability_found = vulnerability_found or passwd_exposed
    
    # Phase 6: Test for Python file exposure (if requested)
    if test_python and not vulnerability_found:
        python_exposed = test_python_file_traversal(endpoints)
        vulnerability_found = vulnerability_found or python_exposed
    
    # Phase 7: Test for tricky/obfuscated traversals (if requested)
    if test_tricky and not vulnerability_found:
        tricky_exposed = test_tricky_traversal(endpoints)
        vulnerability_found = vulnerability_found or tricky_exposed
    
    # Phase 8: Always cleanup before returning
    cleanup_failures = cleanup_items(endpoints, files_to_delete)
    
    print("-" * 60)
    if vulnerability_found:
        print(f"\n{Colors.RED}{Colors.BOLD}✗ VULNERABILITY FOUND - TEST FAILED{Colors.RESET}")
        return 1
    
    if add_failures == 0 and cleanup_failures == 0:
        print(f"\n{Colors.GREEN}{Colors.BOLD}✓ All tests passed!{Colors.RESET}")
        return 0
    else:
        print(f"\n{Colors.RED}{Colors.BOLD}✗ Some tests failed{Colors.RESET}")
        return 1


def show_test_menu():
    """
    Display an interactive menu to choose which tests to run.
    
    Returns:
        tuple: (test_flag, test_passwd, test_python, test_tricky)
    """
    print(f"\n{Colors.BLUE}{Colors.BOLD}Select vulnerability tests to run:{Colors.RESET}")
    print("-" * 60)
    print("1. Flag file exposure (../../opt/flag.txt)")
    print("2. /etc/passwd exposure (../../etc/passwd)")
    print("3. Python file exposure (../app/main.py)")
    print("4. Tricky traversal (encoded/obfuscated payloads)")
    print("5. All vulnerability tests")
    print()
    
    while True:
        choice = input(f"{Colors.BOLD}Enter your choice (1-5): {Colors.RESET}").strip()
        
        if choice == '1':
            return True, False, False, False
        elif choice == '2':
            return False, True, False, False
        elif choice == '3':
            return False, False, True, False
        elif choice == '4':
            return False, False, False, True
        elif choice == '5':
            return True, True, True, True
        else:
            print(f"{Colors.RED}Invalid choice. Please enter 1, 2, 3, 4, or 5.{Colors.RESET}")


def main():
    parser = argparse.ArgumentParser(
        description='Test script for the Food Service add functionality'
    )
    parser.add_argument(
        'host',
        help='Host URL to test (e.g., http://localhost:5000 or localhost:5000)'
    )
    parser.add_argument(
        '--test-all',
        action='store_true',
        help='Test all vulnerabilities without showing the menu'
    )
    
    args = parser.parse_args()
    
    # Determine which tests to run
    if args.test_all:
        test_flag = True
        test_passwd = True
        test_python = True
        test_tricky = True
    else:
        # Show interactive menu
        test_flag, test_passwd, test_python, test_tricky = show_test_menu()
    
    return test_add_functionality(args.host, test_flag, test_passwd, test_python, test_tricky)


if __name__ == '__main__':
    sys.exit(main())
