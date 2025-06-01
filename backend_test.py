#!/usr/bin/env python3
import requests
import sys
import json
import time
from datetime import datetime

class PriceBotNGTester:
    def __init__(self):
        self.base_url = "http://localhost"  # Default to localhost for testing
        self.tests_run = 0
        self.tests_passed = 0
        self.test_results = []

    def run_test(self, name, method, endpoint, expected_status, data=None, params=None, headers=None):
        """Run a single API test"""
        url = f"{self.base_url}/{endpoint}"
        
        if headers is None:
            headers = {'Content-Type': 'application/json'}
        
        self.tests_run += 1
        print(f"\n🔍 Testing {name}...")
        
        try:
            if method == 'GET':
                response = requests.get(url, params=params, headers=headers)
            elif method == 'POST':
                response = requests.post(url, json=data, headers=headers)
            
            success = response.status_code == expected_status
            
            if success:
                self.tests_passed += 1
                print(f"✅ Passed - Status: {response.status_code}")
                try:
                    response_data = response.json() if response.text else {}
                    print(f"Response: {json.dumps(response_data, indent=2)}")
                except:
                    print(f"Response: {response.text[:200]}")
            else:
                print(f"❌ Failed - Expected {expected_status}, got {response.status_code}")
                print(f"Response: {response.text[:200]}")
            
            self.test_results.append({
                "name": name,
                "success": success,
                "status_code": response.status_code,
                "expected_status": expected_status,
                "response": response.text[:500]
            })
            
            return success, response
            
        except Exception as e:
            print(f"❌ Failed - Error: {str(e)}")
            self.test_results.append({
                "name": name,
                "success": False,
                "error": str(e)
            })
            return False, None

    def test_webhook_verification(self):
        """Test the webhook verification endpoint"""
        return self.run_test(
            "Webhook Verification",
            "GET",
            "webhook.php",
            200,
            params={
                "hub_mode": "subscribe",
                "hub_verify_token": "pricebotng_verify_token_2025",
                "hub_challenge": "test123"
            }
        )

    def test_health_check(self):
        """Test the health check endpoint"""
        return self.run_test(
            "Health Check",
            "GET",
            "webhook.php",
            200,
            params={"health": "check"}
        )

    def test_admin_login(self, email="pricebotng@hsl.com.ng", password="Admin123"):
        """Test admin login functionality"""
        headers = {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
        
        return self.run_test(
            "Admin Login",
            "POST",
            "admin/login.php",
            200,  # Login page returns 200 even on success (with redirect)
            data={"email": email, "password": password},
            headers=headers
        )

    def test_price_scraper(self, query="iPhone 15"):
        """Test price scraper with a sample product"""
        # This is a mock test since we can't directly call the scraper
        # We'll simulate a WhatsApp message to the webhook
        
        message_data = {
            "entry": [{
                "changes": [{
                    "value": {
                        "messages": [{
                            "from": "2349012345678",
                            "id": "test_message_id",
                            "timestamp": int(time.time()),
                            "text": {
                                "body": query
                            }
                        }],
                        "contacts": [{
                            "wa_id": "2349012345678",
                            "profile": {
                                "name": "Test User"
                            }
                        }]
                    }
                }]
            }]
        }
        
        return self.run_test(
            f"Price Scraper Test ({query})",
            "POST",
            "webhook.php",
            200,
            data=message_data
        )

    def run_all_tests(self):
        """Run all tests and print results"""
        print("🚀 Starting PriceBotNG Tests")
        print("=" * 50)
        
        # Test webhook verification
        self.test_webhook_verification()
        
        # Test health check
        self.test_health_check()
        
        # Test admin login
        self.test_admin_login()
        
        # Test price scraper with sample products
        self.test_price_scraper("iPhone 15")
        self.test_price_scraper("Samsung Galaxy")
        
        # Print test summary
        print("\n" + "=" * 50)
        print(f"📊 Tests Summary: {self.tests_passed}/{self.tests_run} passed")
        
        # Return overall success status
        return self.tests_passed == self.tests_run

if __name__ == "__main__":
    tester = PriceBotNGTester()
    success = tester.run_all_tests()
    sys.exit(0 if success else 1)
