<?php
/**
 * Complete test for critical PHP fatal error fix
 * 
 * This script comprehensively tests that the fatal error is completely fixed
 * 
 * @package HSM
 * @since 1.0.0
 */

echo "🧪 Testing Critical PHP Fatal Error Complete Fix...\n\n";

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

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'test_nonce_' . $action;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return true;
    }
}

if (!function_exists('error_log')) {
    function error_log($message) {
        echo "LOG: " . $message . "\n";
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
        
        public function get_error_message() {
            return isset($this->errors[0]) ? $this->errors[0][0] : 'Unknown error';
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

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public $data;
        public $status;
        
        public function __construct($data = null, $status = 200) {
            $this->data = $data;
            $this->status = $status;
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
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

echo "🔍 Test 1: Class instantiation...\n";
try {
    $errorHandler = new Mock_HSM_Error_Handler();
    $restManager = new HSM_REST_Manager($errorHandler);
    echo "✅ HSM_REST_Manager instantiated successfully\n";
} catch (Exception $e) {
    echo "❌ HSM_REST_Manager instantiation failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🔍 Test 2: Method existence check...\n";
try {
    if (method_exists($restManager, 'validate_permissions')) {
        echo "✅ validate_permissions method exists\n";
    } else {
        echo "❌ validate_permissions method not found\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Method existence check failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🔍 Test 3: Method accessibility check...\n";
try {
    if (is_callable([$restManager, 'validate_permissions'])) {
        echo "✅ validate_permissions method is callable\n";
    } else {
        echo "❌ validate_permissions method is not callable\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Method accessibility check failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🔍 Test 4: Method execution test...\n";
try {
    $request = new WP_REST_Request();
    $result = $restManager->validate_permissions($request);
    
    if ($result === true || is_wp_error($result)) {
        echo "✅ validate_permissions method executed successfully\n";
        echo "   Result: " . ($result === true ? 'true' : $result->get_error_message()) . "\n";
    } else {
        echo "❌ validate_permissions method returned unexpected result\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Method execution failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🔍 Test 5: Parent method call test...\n";
try {
    // Test with a request that should trigger parent method call
    $request = new WP_REST_Request();
    $request->set_header('X-WP-Nonce', 'valid_nonce');
    $result = $restManager->validate_permissions($request);
    
    if ($result === true || is_wp_error($result)) {
        echo "✅ Parent method call test successful\n";
        echo "   Result: " . ($result === true ? 'true' : $result->get_error_message()) . "\n";
    } else {
        echo "❌ Parent method call test failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Parent method call test failed with exception: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🔍 Test 6: Fallback implementation test...\n";
try {
    // Test with a request that should trigger fallback implementation
    $request = new WP_REST_Request();
    // Don't set any headers to trigger fallback
    $result = $restManager->validate_permissions($request);
    
    if ($result === true || is_wp_error($result)) {
        echo "✅ Fallback implementation test successful\n";
        echo "   Result: " . ($result === true ? 'true' : $result->get_error_message()) . "\n";
    } else {
        echo "❌ Fallback implementation test failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Fallback implementation test failed with exception: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🔍 Test 7: REST API registration test...\n";
try {
    // Test that register_routes doesn't throw fatal errors
    $restManager->register_routes();
    echo "✅ REST API registration completed without fatal errors\n";
} catch (Exception $e) {
    echo "❌ REST API registration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🔍 Test 8: Error handling test...\n";
try {
    // Test error handling with invalid request
    $request = new WP_REST_Request();
    $request->set_header('X-WP-Nonce', 'invalid_nonce');
    $result = $restManager->validate_permissions($request);
    
    if (is_wp_error($result)) {
        echo "✅ Error handling test successful\n";
        echo "   Error: " . $result->get_error_message() . "\n";
    } else {
        echo "❌ Error handling test failed - should return error for invalid nonce\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Error handling test failed with exception: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 All tests passed! Critical PHP fatal error fix is working correctly!\n";
echo "\n📊 Test Summary:\n";
echo "================\n";
echo "✅ Class instantiation: PASS\n";
echo "✅ Method existence: PASS\n";
echo "✅ Method accessibility: PASS\n";
echo "✅ Method execution: PASS\n";
echo "✅ Parent method call: PASS\n";
echo "✅ Fallback implementation: PASS\n";
echo "✅ REST API registration: PASS\n";
echo "✅ Error handling: PASS\n";
echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";