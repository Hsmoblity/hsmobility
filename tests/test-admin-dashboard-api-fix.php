<?php
/**
 * Test script for admin dashboard API fix
 * 
 * This script tests the admin dashboard API authentication fix
 * 
 * @package HSM
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Mock WordPress functions for testing
    if (!function_exists('wp_verify_nonce')) {
        function wp_verify_nonce($nonce, $action) {
            return $nonce === 'valid_nonce';
        }
    }
    
    if (!function_exists('current_user_can')) {
        function current_user_can($capability) {
            return $capability === 'manage_options';
        }
    }
    
    if (!class_exists('WP_Error')) {
        class WP_Error {
            public $errors = [];
            public $error_data = [];
            
            public function __construct($code = '', $message = '', $data = '') {
                if (empty($code)) {
                    return;
                }
                $this->errors[$code][] = $message;
                if (!empty($data)) {
                    $this->error_data[$code] = $data;
                }
            }
        }
    }
    
    if (!class_exists('WP_REST_Request')) {
        class WP_REST_Request {
            private $headers = [];
            
            public function get_header($name) {
                return isset($this->headers[$name]) ? $this->headers[$name] : null;
            }
            
            public function set_header($name, $value) {
                $this->headers[$name] = $value;
            }
        }
    }
}

// Mock HSM_Error_Handler for testing - using proper mock pattern
class Mock_HSM_Error_Handler {
    public function log_error($message) {
        echo "Error: " . $message . "\n";
    }
}

// Include the REST manager class
require_once __DIR__ . '/../includes/api/rest/class-rest-base.php';
require_once __DIR__ . '/../includes/api/rest/class-rest-manager.php';

class HSM_Admin_Dashboard_API_Test {
    private $testResults = [];
    private $errorHandler;
    private $restManager;
    
    public function __construct() {
        $this->errorHandler = new Mock_HSM_Error_Handler();
        $this->restManager = new HSM_REST_Manager($this->errorHandler);
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "🧪 Testing Admin Dashboard API Fix...\n\n";
        
        // Test 1: Internal WordPress request (admin dashboard)
        $this->testInternalWordPressRequest();
        
        // Test 2: External request with nonce
        $this->testExternalRequestWithNonce();
        
        // Test 3: External request without nonce
        $this->testExternalRequestWithoutNonce();
        
        // Test 4: Admin user capability
        $this->testAdminUserCapability();
        
        // Display results
        $this->displayResults();
    }
    
    /**
     * Test 1: Internal WordPress request (admin dashboard)
     */
    public function testInternalWordPressRequest() {
        echo "🔍 Test 1: Internal WordPress request (admin dashboard)...\n";
        
        try {
            // Simulate internal WordPress request (no external headers)
            $request = new WP_REST_Request();
            // Don't set any headers - this simulates internal WordPress request
            
            $result = $this->restManager->validate_permissions($request);
            
            if ($result === true) {
                echo "✅ Internal WordPress request authentication successful\n";
                $this->testResults['internal_wordpress_request'] = true;
            } else {
                echo "❌ Internal WordPress request authentication failed: " . (is_wp_error($result) ? $result->get_error_message() : 'Unknown') . "\n";
                $this->testResults['internal_wordpress_request'] = false;
            }
            
        } catch (Exception $e) {
            echo "❌ Internal WordPress request test failed: " . $e->getMessage() . "\n";
            $this->testResults['internal_wordpress_request'] = false;
        }
        
        echo "\n";
    }
    
    /**
     * Test 2: External request with nonce
     */
    public function testExternalRequestWithNonce() {
        echo "🔍 Test 2: External request with nonce...\n";
        
        try {
            // Simulate external request with valid nonce
            $request = new WP_REST_Request();
            $request->set_header('X-WP-Nonce', 'valid_nonce');
            $request->set_header('Origin', 'https://example.com');
            $request->set_header('User-Agent', 'Mozilla/5.0');
            
            $result = $this->restManager->validate_permissions($request);
            
            if ($result === true) {
                echo "✅ External request with nonce authentication successful\n";
                $this->testResults['external_request_with_nonce'] = true;
            } else {
                echo "❌ External request with nonce authentication failed: " . (is_wp_error($result) ? $result->get_error_message() : 'Unknown') . "\n";
                $this->testResults['external_request_with_nonce'] = false;
            }
            
        } catch (Exception $e) {
            echo "❌ External request with nonce test failed: " . $e->getMessage() . "\n";
            $this->testResults['external_request_with_nonce'] = false;
        }
        
        echo "\n";
    }
    
    /**
     * Test 3: External request without nonce
     */
    public function testExternalRequestWithoutNonce() {
        echo "🔍 Test 3: External request without nonce...\n";
        
        try {
            // Simulate external request without nonce
            $request = new WP_REST_Request();
            $request->set_header('Origin', 'https://example.com');
            $request->set_header('User-Agent', 'Mozilla/5.0');
            // Don't set X-WP-Nonce header
            
            $result = $this->restManager->validate_permissions($request);
            
            if (is_wp_error($result)) {
                echo "✅ External request without nonce properly rejected\n";
                $this->testResults['external_request_without_nonce'] = true;
            } else {
                echo "❌ External request without nonce should be rejected\n";
                $this->testResults['external_request_without_nonce'] = false;
            }
            
        } catch (Exception $e) {
            echo "❌ External request without nonce test failed: " . $e->getMessage() . "\n";
            $this->testResults['external_request_without_nonce'] = false;
        }
        
        echo "\n";
    }
    
    /**
     * Test 4: Admin user capability
     */
    public function testAdminUserCapability() {
        echo "🔍 Test 4: Admin user capability...\n";
        
        try {
            // Test admin user capability fallback
            $request = new WP_REST_Request();
            // Don't set any headers - rely on admin user capability
            
            $result = $this->restManager->validate_permissions($request);
            
            if ($result === true) {
                echo "✅ Admin user capability authentication successful\n";
                $this->testResults['admin_user_capability'] = true;
            } else {
                echo "❌ Admin user capability authentication failed: " . (is_wp_error($result) ? $result->get_error_message() : 'Unknown') . "\n";
                $this->testResults['admin_user_capability'] = false;
            }
            
        } catch (Exception $e) {
            echo "❌ Admin user capability test failed: " . $e->getMessage() . "\n";
            $this->testResults['admin_user_capability'] = false;
        }
        
        echo "\n";
    }
    
    /**
     * Display test results
     */
    public function displayResults() {
        echo "📊 Test Results:\n";
        echo "================\n\n";
        
        $totalTests = count($this->testResults);
        $passedTests = array_sum($this->testResults);
        
        foreach ($this->testResults as $test => $result) {
            $status = $result ? '✅ PASS' : '❌ FAIL';
            echo "🔍 " . ucwords(str_replace('_', ' ', $test)) . ": $status\n";
        }
        
        echo "\n📈 Overall Results:\n";
        echo "Total Tests: $totalTests\n";
        echo "Passed: $passedTests\n";
        echo "Failed: " . ($totalTests - $passedTests) . "\n";
        echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";
        
        if ($passedTests === $totalTests) {
            echo "🎉 All tests passed! Admin dashboard API fix is working correctly.\n";
        } else {
            echo "❌ Some tests failed. Admin dashboard API fix needs attention.\n";
        }
        
        echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
        echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";
    }
}

// Run the test
if (php_sapi_name() === 'cli') {
    $test = new HSM_Admin_Dashboard_API_Test();
    $test->runAllTests();
}