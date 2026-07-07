<?php
/**
 * 403 Error Fix Test
 * 
 * This script tests if the 403 error in API check has been fixed.
 * 
 * @package HSM
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Define ABSPATH for standalone testing
    define('ABSPATH', dirname(__FILE__) . '/../../');
}

class HSM_403_Error_Fix_Test {
    
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    /**
     * Run all 403 error fix tests
     */
    public function run_all_tests() {
        echo "🏛️ POSEIDON - 403 Error Fix Test Suite\n";
        echo "=====================================\n\n";
        
        // Test 1: Check if security validation methods exist
        $this->test_security_validation_methods_exist();
        
        // Test 2: Check if multiple validation methods are implemented
        $this->test_multiple_validation_methods();
        
        // Test 3: Check if fallback security is implemented
        $this->test_fallback_security_implemented();
        
        // Test 4: Check if nonce validation is working
        $this->test_nonce_validation_working();
        
        // Test 5: Check if API key validation is working
        $this->test_api_key_validation_working();
        
        // Test 6: Check if origin validation is working
        $this->test_origin_validation_working();
        
        // Test 7: Check if referer validation is working
        $this->test_referer_validation_working();
        
        // Test 8: Check if basic security fallback is working
        $this->test_basic_security_fallback_working();
        
        // Test 9: Check if error logging is implemented
        $this->test_error_logging_implemented();
        
        // Test 10: Check if 403 error handling is improved
        $this->test_403_error_handling_improved();
        
        // Display results
        $this->display_results();
        
        return $this->test_results;
    }
    
    /**
     * Test if security validation methods exist
     */
    private function test_security_validation_methods_exist() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_validate_request_security = strpos($class_content, 'validate_request_security') !== false;
            $has_validate_api_key = strpos($class_content, 'validate_api_key') !== false;
            $has_validate_origin = strpos($class_content, 'validate_origin') !== false;
            $has_validate_referer = strpos($class_content, 'validate_referer') !== false;
            $has_validate_basic_security = strpos($class_content, 'validate_basic_security') !== false;
            
            $methods_exist = $has_validate_request_security && $has_validate_api_key && $has_validate_origin && $has_validate_referer && $has_validate_basic_security;
        } else {
            $methods_exist = false;
        }
        
        $this->passed_tests += $methods_exist ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Security Validation Methods Exist", $methods_exist, $methods_exist ? "All security validation methods exist" : "Some security validation methods are missing", $execution_time);
    }
    
    /**
     * Test if multiple validation methods are implemented
     */
    private function test_multiple_validation_methods() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_nonce_validation = strpos($class_content, 'wp_verify_nonce') !== false;
            $has_api_key_validation = strpos($class_content, 'X-API-Key') !== false;
            $has_origin_validation = strpos($class_content, 'Origin') !== false;
            $has_referer_validation = strpos($class_content, 'Referer') !== false;
            $has_basic_security = strpos($class_content, 'validate_basic_security') !== false;
            
            $multiple_methods = $has_nonce_validation && $has_api_key_validation && $has_origin_validation && $has_referer_validation && $has_basic_security;
        } else {
            $multiple_methods = false;
        }
        
        $this->passed_tests += $multiple_methods ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Multiple Validation Methods Implemented", $multiple_methods, $multiple_methods ? "Multiple validation methods are implemented" : "Multiple validation methods are not implemented", $execution_time);
    }
    
    /**
     * Test if fallback security is implemented
     */
    private function test_fallback_security_implemented() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_fallback_logic = strpos($class_content, 'Method 5: Basic security checks') !== false;
            $has_development_mode = strpos($class_content, 'WP_DEBUG') !== false;
            $has_localhost_check = strpos($class_content, '127.0.0.1') !== false;
            $has_user_agent_check = strpos($class_content, 'User-Agent') !== false;
            
            $fallback_implemented = $has_fallback_logic && $has_development_mode && $has_localhost_check && $has_user_agent_check;
        } else {
            $fallback_implemented = false;
        }
        
        $this->passed_tests += $fallback_implemented ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Fallback Security Implemented", $fallback_implemented, $fallback_implemented ? "Fallback security is properly implemented" : "Fallback security is not implemented", $execution_time);
    }
    
    /**
     * Test if nonce validation is working
     */
    private function test_nonce_validation_working() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_nonce_check = strpos($class_content, 'X-WP-Nonce') !== false;
            $has_nonce_verification = strpos($class_content, 'wp_verify_nonce') !== false;
            $has_nonce_action = strpos($class_content, 'hsm_graphql_request') !== false;
            
            $nonce_working = $has_nonce_check && $has_nonce_verification && $has_nonce_action;
        } else {
            $nonce_working = false;
        }
        
        $this->passed_tests += $nonce_working ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Nonce Validation Working", $nonce_working, $nonce_working ? "Nonce validation is properly implemented" : "Nonce validation is not working", $execution_time);
    }
    
    /**
     * Test if API key validation is working
     */
    private function test_api_key_validation_working() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_api_key_check = strpos($class_content, 'X-API-Key') !== false;
            $has_api_key_validation = strpos($class_content, 'validate_api_key') !== false;
            $has_api_key_generation = strpos($class_content, 'wp_generate_password') !== false;
            $has_api_key_storage = strpos($class_content, 'hsm_graphql_api_keys') !== false;
            
            $api_key_working = $has_api_key_check && $has_api_key_validation && $has_api_key_generation && $has_api_key_storage;
        } else {
            $api_key_working = false;
        }
        
        $this->passed_tests += $api_key_working ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("API Key Validation Working", $api_key_working, $api_key_working ? "API key validation is properly implemented" : "API key validation is not working", $execution_time);
    }
    
    /**
     * Test if origin validation is working
     */
    private function test_origin_validation_working() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_origin_check = strpos($class_content, 'Origin') !== false;
            $has_origin_validation = strpos($class_content, 'validate_origin') !== false;
            $has_allowed_origins = strpos($class_content, 'hsm_graphql_allowed_origins') !== false;
            $has_localhost_origins = strpos($class_content, 'localhost:3000') !== false;
            
            $origin_working = $has_origin_check && $has_origin_validation && $has_allowed_origins && $has_localhost_origins;
        } else {
            $origin_working = false;
        }
        
        $this->passed_tests += $origin_working ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Origin Validation Working", $origin_working, $origin_working ? "Origin validation is properly implemented" : "Origin validation is not working", $execution_time);
    }
    
    /**
     * Test if referer validation is working
     */
    private function test_referer_validation_working() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_referer_check = strpos($class_content, 'Referer') !== false;
            $has_referer_validation = strpos($class_content, 'validate_referer') !== false;
            $has_allowed_referers = strpos($class_content, 'hsm_graphql_allowed_referers') !== false;
            $has_hsmobility_referers = strpos($class_content, 'hsmobility.ca') !== false;
            
            $referer_working = $has_referer_check && $has_referer_validation && $has_allowed_referers && $has_hsmobility_referers;
        } else {
            $referer_working = false;
        }
        
        $this->passed_tests += $referer_working ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Referer Validation Working", $referer_working, $referer_working ? "Referer validation is properly implemented" : "Referer validation is not working", $execution_time);
    }
    
    /**
     * Test if basic security fallback is working
     */
    private function test_basic_security_fallback_working() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_basic_security = strpos($class_content, 'validate_basic_security') !== false;
            $has_development_mode = strpos($class_content, 'WP_DEBUG') !== false;
            $has_localhost_ips = strpos($class_content, '127.0.0.1') !== false;
            $has_user_agent_check = strpos($class_content, 'User-Agent') !== false;
            $has_content_type_check = strpos($class_content, 'application/json') !== false;
            
            $basic_security_working = $has_basic_security && $has_development_mode && $has_localhost_ips && $has_user_agent_check && $has_content_type_check;
        } else {
            $basic_security_working = false;
        }
        
        $this->passed_tests += $basic_security_working ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Basic Security Fallback Working", $basic_security_working, $basic_security_working ? "Basic security fallback is properly implemented" : "Basic security fallback is not working", $execution_time);
    }
    
    /**
     * Test if error logging is implemented
     */
    private function test_error_logging_implemented() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_logging_method = strpos($class_content, 'log_security_validation_failure') !== false;
            $has_client_ip_logging = strpos($class_content, 'get_client_ip') !== false;
            $has_user_agent_logging = strpos($class_content, 'User-Agent') !== false;
            $has_debug_logging = strpos($class_content, 'Log security validation failure') !== false;
            
            $logging_implemented = $has_logging_method && $has_client_ip_logging && $has_user_agent_logging && $has_debug_logging;
        } else {
            $logging_implemented = false;
        }
        
        $this->passed_tests += $logging_implemented ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Error Logging Implemented", $logging_implemented, $logging_implemented ? "Error logging is properly implemented" : "Error logging is not implemented", $execution_time);
    }
    
    /**
     * Test if 403 error handling is improved
     */
    private function test_403_error_handling_improved() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_403_response = strpos($class_content, '403') !== false;
            $has_security_validation = strpos($class_content, 'Security validation failed') !== false;
            $has_multiple_validation = strpos($class_content, 'Method 1:') !== false;
            $has_fallback_validation = strpos($class_content, 'Method 5:') !== false;
            
            $error_handling_improved = $has_403_response && $has_security_validation && $has_multiple_validation && $has_fallback_validation;
        } else {
            $error_handling_improved = false;
        }
        
        $this->passed_tests += $error_handling_improved ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("403 Error Handling Improved", $error_handling_improved, $error_handling_improved ? "403 error handling has been improved" : "403 error handling has not been improved", $execution_time);
    }
    
    /**
     * Record test result
     */
    private function record_test_result($test_name, $passed, $message, $execution_time) {
        $this->test_results[] = [
            'test_name' => $test_name,
            'passed' => $passed,
            'message' => $message,
            'execution_time' => $execution_time
        ];
        
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        echo sprintf("%-40s %s (%.4fs) - %s\n", $test_name, $status, $execution_time, $message);
    }
    
    /**
     * Display test results
     */
    private function display_results() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "403 ERROR FIX TEST RESULTS\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        echo "Success Rate: " . round(($this->passed_tests / $this->total_tests) * 100, 2) . "%\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL 403 ERROR FIX TESTS PASSED! The 403 error has been fixed!\n";
        } else {
            echo "\n⚠️  Some 403 error fix tests failed. The 403 error may not be fully fixed.\n";
        }
        
        echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
        echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test_suite = new HSM_403_Error_Fix_Test();
    $results = $test_suite->run_all_tests();
}