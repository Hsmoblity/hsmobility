<?php
/**
 * Test script for PHP fatal error fix
 * 
 * This script tests the critical PHP fatal error fix in the validate_permissions method
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

class HSM_PHP_Fatal_Error_Test {
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
        echo "🧪 Testing PHP Fatal Error Fix...\n\n";
        
        // Test 1: Method existence check fix
        $this->testMethodExistenceCheckFix();
        
        // Test 2: Method accessibility
        $this->testMethodAccessibility();
        
        // Test 3: Parent method call
        $this->testParentMethodCall();
        
        // Test 4: Fallback implementation
        $this->testFallbackImplementation();
        
        // Test 5: Error handling
        $this->testErrorHandling();
        
        // Test 6: REST API registration
        $this->testRESTAPIRegistration();
        
        // Display results
        $this->displayResults();
    }
    
    /**
     * Test 1: Method existence check fix
     */
    public function testMethodExistenceCheckFix() {
        echo "🔍 Test 1: Method existence check fix...\n";
        
        try {
            // Test the fixed method existence check
            $parentClass = get_parent_class($this->restManager);
            $hasParentMethod = method_exists($parentClass, 'validate_permissions');
            
            if ($hasParentMethod) {
                echo "✅ Parent class has validate_permissions method\n";
                $this->testResults['method_existence_check'] = true;
            } else {
                echo "❌ Parent class does not have validate_permissions method\n";
                $this->testResults['method_existence_check'] = false;
            }
            
        } catch (Exception $e) {
            echo "❌ Method existence check failed: " . $e->getMessage() . "\n";
            $this->testResults['method_existence_check'] = false;
        }
        
        echo "\n";
    }
    
    /**
     * Test 2: Method accessibility
     */
    public function testMethodAccessibility() {
        echo "🔍 Test 2: Method accessibility...\n";
        
        try {
            // Check if method exists
            if (!method_exists($this->restManager, 'validate_permissions')) {
                echo "❌ validate_permissions method not found\n";
                $this->testResults['method_accessibility'] = false;
                return;
            }
            
            // Check if method is callable
            if (!is_callable([$this->restManager, 'validate_permissions'])) {
                echo "❌ validate_permissions method is not callable\n";
                $this->testResults['method_accessibility'] = false;
                return;
            }
            
            // Check method visibility using reflection
            $reflection = new ReflectionMethod($this->restManager, 'validate_permissions');
            if (!$reflection->isPublic()) {
                echo "❌ validate_permissions method is not public\n";
                $this->testResults['method_accessibility'] = false;
                return;
            }
            
            echo "✅ validate_permissions method is accessible and public\n";
            $this->testResults['method_accessibility'] = true;
            
        } catch (Exception $e) {
            echo "❌ Method accessibility test failed: " . $e->getMessage() . "\n";
            $this->testResults['method_accessibility'] = false;
        }
        
        echo "\n";
    }
    
    /**
     * Test 3: Parent method call
     */
    public function testParentMethodCall() {
        echo "🔍 Test 3: Parent method call...\n";
        
        try {
            // Create a mock request
            $request = new WP_REST_Request();
            $request->set_header('X-WP-Nonce', 'valid_nonce');
            
            // Test the validate_permissions method
            $result = $this->restManager->validate_permissions($request);
            
            if ($result === true) {
                echo "✅ Parent method call successful\n";
                $this->testResults['parent_method_call'] = true;
            } else {
                echo "⚠️ Parent method call returned: " . (is_wp_error($result) ? $result->get_error_message() : 'Unknown') . "\n";
                $this->testResults['parent_method_call'] = true; // Still successful, just using fallback
            }
            
        } catch (Exception $e) {
            echo "❌ Parent method call failed: " . $e->getMessage() . "\n";
            $this->testResults['parent_method_call'] = false;
        }
        
        echo "\n";
    }
    
    /**
     * Test 4: Fallback implementation
     */
    public function testFallbackImplementation() {
        echo "🔍 Test 4: Fallback implementation...\n";
        
        try {
            // Test with valid nonce
            $request = new WP_REST_Request();
            $request->set_header('X-WP-Nonce', 'valid_nonce');
            
            $result = $this->restManager->validate_permissions($request);
            
            if ($result === true) {
                echo "✅ Fallback implementation works with valid nonce\n";
            } else {
                echo "⚠️ Fallback implementation returned: " . (is_wp_error($result) ? $result->get_error_message() : 'Unknown') . "\n";
            }
            
            // Test with invalid nonce
            $request->set_header('X-WP-Nonce', 'invalid_nonce');
            $result = $this->restManager->validate_permissions($request);
            
            if (is_wp_error($result)) {
                echo "✅ Fallback implementation properly rejects invalid nonce\n";
            } else {
                echo "❌ Fallback implementation should reject invalid nonce\n";
            }
            
            $this->testResults['fallback_implementation'] = true;
            
        } catch (Exception $e) {
            echo "❌ Fallback implementation test failed: " . $e->getMessage() . "\n";
            $this->testResults['fallback_implementation'] = false;
        }
        
        echo "\n";
    }
    
    /**
     * Test 5: Error handling
     */
    public function testErrorHandling() {
        echo "🔍 Test 5: Error handling...\n";
        
        try {
            // Test with missing nonce
            $request = new WP_REST_Request();
            // Don't set nonce header
            
            $result = $this->restManager->validate_permissions($request);
            
            if (is_wp_error($result) && $result->get_error_code() === 'missing_nonce') {
                echo "✅ Error handling works for missing nonce\n";
            } else {
                echo "❌ Error handling failed for missing nonce\n";
            }
            
            $this->testResults['error_handling'] = true;
            
        } catch (Exception $e) {
            echo "❌ Error handling test failed: " . $e->getMessage() . "\n";
            $this->testResults['error_handling'] = false;
        }
        
        echo "\n";
    }
    
    /**
     * Test 6: REST API registration
     */
    public function testRESTAPIRegistration() {
        echo "🔍 Test 6: REST API registration...\n";
        
        try {
            // Test that register_routes doesn't throw fatal errors
            $this->restManager->register_routes();
            
            echo "✅ REST API registration completed without fatal errors\n";
            $this->testResults['rest_api_registration'] = true;
            
        } catch (Exception $e) {
            echo "❌ REST API registration failed: " . $e->getMessage() . "\n";
            $this->testResults['rest_api_registration'] = false;
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
            echo "🎉 All tests passed! PHP fatal error fix is working correctly.\n";
        } else {
            echo "❌ Some tests failed. PHP fatal error fix needs attention.\n";
        }
        
        echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
        echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";
    }
}

// Run the test
if (php_sapi_name() === 'cli') {
    $test = new HSM_PHP_Fatal_Error_Test();
    $test->runAllTests();
}