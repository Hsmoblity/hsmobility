<?php
/**
 * Simple test for admin dashboard API fix
 */

echo "🧪 Testing Admin Dashboard API Fix...\n\n";

// Test the validate_permissions method directly
echo "🔍 Testing validate_permissions method...\n";

// Mock the necessary functions
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

// Mock HSM_Error_Handler for testing - using proper mock pattern
class Mock_HSM_Error_Handler {
    public function log_error($message) {
        echo "Error: " . $message . "\n";
    }
}

// Include the REST manager class
require_once __DIR__ . '/../includes/api/rest/class-rest-base.php';
require_once __DIR__ . '/../includes/api/rest/class-rest-manager.php';

try {
    $errorHandler = new Mock_HSM_Error_Handler();
    $restManager = new HSM_REST_Manager($errorHandler);
    
    // Test 1: Internal WordPress request (no headers)
    echo "Test 1: Internal WordPress request (no headers)...\n";
    $request1 = new WP_REST_Request();
    $result1 = $restManager->validate_permissions($request1);
    
    if ($result1 === true) {
        echo "✅ Internal WordPress request authentication successful\n";
    } else {
        echo "❌ Internal WordPress request authentication failed: " . (is_wp_error($result1) ? $result1->get_error_message() : 'Unknown') . "\n";
    }
    
    // Test 2: External request with nonce
    echo "\nTest 2: External request with nonce...\n";
    $request2 = new WP_REST_Request();
    $request2->set_header('X-WP-Nonce', 'valid_nonce');
    $request2->set_header('Origin', 'https://example.com');
    $result2 = $restManager->validate_permissions($request2);
    
    if ($result2 === true) {
        echo "✅ External request with nonce authentication successful\n";
    } else {
        echo "❌ External request with nonce authentication failed: " . (is_wp_error($result2) ? $result2->get_error_message() : 'Unknown') . "\n";
    }
    
    // Test 3: External request without nonce
    echo "\nTest 3: External request without nonce...\n";
    $request3 = new WP_REST_Request();
    $request3->set_header('Origin', 'https://example.com');
    $result3 = $restManager->validate_permissions($request3);
    
    if (is_wp_error($result3)) {
        echo "✅ External request without nonce properly rejected\n";
    } else {
        echo "❌ External request without nonce should be rejected\n";
    }
    
    echo "\n🎉 Admin dashboard API fix test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with exception: " . $e->getMessage() . "\n";
}

echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";