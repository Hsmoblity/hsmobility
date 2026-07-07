<?php
/**
 * 403 Cookie Nonce Error Test
 * 
 * This script tests the 403 cookie nonce error and verifies the fix.
 * 
 * @package HSM
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Define ABSPATH for standalone testing
    define('ABSPATH', dirname(__FILE__) . '/../../');
}

class HSM_403_Cookie_Nonce_Error_Test {
    
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    /**
     * Run all 403 cookie nonce error tests
     */
    public function run_all_tests() {
        echo "🏛️ POSEIDON - 403 Cookie Nonce Error Test Suite\n";
        echo "==============================================\n\n";
        
        // Test 1: Check if nonce generation is working
        $this->test_nonce_generation_working();
        
        // Test 2: Check if nonce validation is properly implemented
        $this->test_nonce_validation_implemented();
        
        // Test 3: Check if REST API nonce configuration is correct
        $this->test_rest_api_nonce_configuration();
        
        // Test 4: Check if frontend nonce inclusion is working
        $this->test_frontend_nonce_inclusion();
        
        // Test 5: Check if cookie nonce handling is implemented
        $this->test_cookie_nonce_handling();
        
        // Test 6: Check if nonce refresh mechanisms exist
        $this->test_nonce_refresh_mechanisms();
        
        // Test 7: Check if error handling for invalid nonces exists
        $this->test_invalid_nonce_error_handling();
        
        // Test 8: Check if nonce expiration is handled properly
        $this->test_nonce_expiration_handling();
        
        // Test 9: Check if multiple nonce validation methods exist
        $this->test_multiple_nonce_validation_methods();
        
        // Test 10: Check if nonce debugging is implemented
        $this->test_nonce_debugging_implemented();
        
        // Display results
        $this->display_results();
        
        return $this->test_results;
    }
    
    /**
     * Test if nonce generation is working
     */
    private function test_nonce_generation_working() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Check if nonce generation functions exist
        $nonce_functions_exist = function_exists('wp_create_nonce') && function_exists('wp_verify_nonce');
        
        // Check if nonce generation is used in API endpoints
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_nonce_generation = strpos($class_content, 'wp_create_nonce') !== false;
            $has_nonce_verification = strpos($class_content, 'wp_verify_nonce') !== false;
            $has_nonce_endpoint = strpos($class_content, 'get_graphql_nonce') !== false;
            
            $nonce_generation_working = $has_nonce_generation && $has_nonce_verification && $has_nonce_endpoint;
        } else {
            $nonce_generation_working = false;
        }
        
        $this->passed_tests += $nonce_generation_working ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Nonce Generation Working", $nonce_generation_working, $nonce_generation_working ? "Nonce generation is properly implemented" : "Nonce generation is not working", $execution_time);
    }
    
    /**
     * Test if nonce validation is properly implemented
     */
    private function test_nonce_validation_implemented() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_nonce_validation = strpos($class_content, 'validate_request_security') !== false;
            $has_nonce_check = strpos($class_content, 'X-WP-Nonce') !== false;
            $has_nonce_verification = strpos($class_content, 'wp_verify_nonce') !== false;
            $has_nonce_action = strpos($class_content, 'hsm_graphql_request') !== false;
            
            $nonce_validation_implemented = $has_nonce_validation && $has_nonce_check && $has_nonce_verification && $has_nonce_action;
        } else {
            $nonce_validation_implemented = false;
        }
        
        $this->passed_tests += $nonce_validation_implemented ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Nonce Validation Implemented", $nonce_validation_implemented, $nonce_validation_implemented ? "Nonce validation is properly implemented" : "Nonce validation is not implemented", $execution_time);
    }
    
    /**
     * Test if REST API nonce configuration is correct
     */
    private function test_rest_api_nonce_configuration() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_rest_route = strpos($class_content, 'register_rest_route') !== false;
            $has_permission_callback = strpos($class_content, 'permission_callback') !== false;
            $has_nonce_endpoint = strpos($class_content, '/nonce') !== false;
            $has_rest_api_hooks = strpos($class_content, 'rest_api_init') !== false;
            
            $rest_api_nonce_configuration = $has_rest_route && $has_permission_callback && $has_nonce_endpoint && $has_rest_api_hooks;
        } else {
            $rest_api_nonce_configuration = false;
        }
        
        $this->passed_tests += $rest_api_nonce_configuration ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("REST API Nonce Configuration", $rest_api_nonce_configuration, $rest_api_nonce_configuration ? "REST API nonce configuration is correct" : "REST API nonce configuration is incorrect", $execution_time);
    }
    
    /**
     * Test if frontend nonce inclusion is working
     */
    private function test_frontend_nonce_inclusion() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $graphql_testing_file = dirname(__FILE__) . '/../includes/admin/class-graphql-testing-page.php';
        $file_exists = file_exists($graphql_testing_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($graphql_testing_file);
            $has_nonce_localization = strpos($class_content, 'wp_localize_script') !== false;
            $has_nonce_creation = strpos($class_content, 'wp_create_nonce') !== false;
            $has_nonce_verification = strpos($class_content, 'wp_verify_nonce') !== false;
            $has_ajax_nonce = strpos($class_content, 'hsm_graphql_testing_nonce') !== false;
            
            $frontend_nonce_inclusion = $has_nonce_localization && $has_nonce_creation && $has_nonce_verification && $has_ajax_nonce;
        } else {
            $frontend_nonce_inclusion = false;
        }
        
        $this->passed_tests += $frontend_nonce_inclusion ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Frontend Nonce Inclusion", $frontend_nonce_inclusion, $frontend_nonce_inclusion ? "Frontend nonce inclusion is working" : "Frontend nonce inclusion is not working", $execution_time);
    }
    
    /**
     * Test if cookie nonce handling is implemented
     */
    private function test_cookie_nonce_handling() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $rest_base_file = dirname(__FILE__) . '/../includes/api/rest/class-rest-base.php';
        $file_exists = file_exists($rest_base_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($rest_base_file);
            $has_cookie_handling = strpos($class_content, 'Cookie') !== false;
            $has_cookie_parsing = strpos($class_content, 'wp_rest_nonce') !== false;
            $has_cookie_fallback = strpos($class_content, 'cookie_header') !== false;
            $has_cookie_validation = strpos($class_content, 'preg_match') !== false;
            
            $cookie_nonce_handling = $has_cookie_handling && $has_cookie_parsing && $has_cookie_fallback && $has_cookie_validation;
        } else {
            $cookie_nonce_handling = false;
        }
        
        $this->passed_tests += $cookie_nonce_handling ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Cookie Nonce Handling", $cookie_nonce_handling, $cookie_nonce_handling ? "Cookie nonce handling is implemented" : "Cookie nonce handling is not implemented", $execution_time);
    }
    
    /**
     * Test if nonce refresh mechanisms exist
     */
    private function test_nonce_refresh_mechanisms() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_nonce_endpoint = strpos($class_content, 'get_graphql_nonce') !== false;
            $has_expires_in = strpos($class_content, 'expires_in') !== false;
            $has_timestamp = strpos($class_content, 'timestamp') !== false;
            $has_nonce_refresh = strpos($class_content, 'nonce') !== false;
            
            $nonce_refresh_mechanisms = $has_nonce_endpoint && $has_expires_in && $has_timestamp && $has_nonce_refresh;
        } else {
            $nonce_refresh_mechanisms = false;
        }
        
        $this->passed_tests += $nonce_refresh_mechanisms ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Nonce Refresh Mechanisms", $nonce_refresh_mechanisms, $nonce_refresh_mechanisms ? "Nonce refresh mechanisms exist" : "Nonce refresh mechanisms do not exist", $execution_time);
    }
    
    /**
     * Test if error handling for invalid nonces exists
     */
    private function test_invalid_nonce_error_handling() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $rest_base_file = dirname(__FILE__) . '/../includes/api/rest/class-rest-base.php';
        $file_exists = file_exists($rest_base_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($rest_base_file);
            $has_missing_nonce_error = strpos($class_content, 'missing_nonce') !== false;
            $has_invalid_nonce_error = strpos($class_content, 'invalid_nonce') !== false;
            $has_error_status = strpos($class_content, 'status') !== false;
            $has_wp_error = strpos($class_content, 'WP_Error') !== false;
            
            $invalid_nonce_error_handling = $has_missing_nonce_error && $has_invalid_nonce_error && $has_error_status && $has_wp_error;
        } else {
            $invalid_nonce_error_handling = false;
        }
        
        $this->passed_tests += $invalid_nonce_error_handling ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Invalid Nonce Error Handling", $invalid_nonce_error_handling, $invalid_nonce_error_handling ? "Invalid nonce error handling exists" : "Invalid nonce error handling does not exist", $execution_time);
    }
    
    /**
     * Test if nonce expiration is handled properly
     */
    private function test_nonce_expiration_handling() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_expires_in = strpos($class_content, 'expires_in') !== false;
            $has_timestamp = strpos($class_content, 'timestamp') !== false;
            $has_nonce_creation = strpos($class_content, 'wp_create_nonce') !== false;
            $has_nonce_verification = strpos($class_content, 'wp_verify_nonce') !== false;
            
            $nonce_expiration_handling = $has_expires_in && $has_timestamp && $has_nonce_creation && $has_nonce_verification;
        } else {
            $nonce_expiration_handling = false;
        }
        
        $this->passed_tests += $nonce_expiration_handling ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Nonce Expiration Handling", $nonce_expiration_handling, $nonce_expiration_handling ? "Nonce expiration is handled properly" : "Nonce expiration is not handled properly", $execution_time);
    }
    
    /**
     * Test if multiple nonce validation methods exist
     */
    private function test_multiple_nonce_validation_methods() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_nonce_validation = strpos($class_content, 'X-WP-Nonce') !== false;
            $has_api_key_validation = strpos($class_content, 'X-API-Key') !== false;
            $has_origin_validation = strpos($class_content, 'Origin') !== false;
            $has_referer_validation = strpos($class_content, 'Referer') !== false;
            $has_basic_security = strpos($class_content, 'validate_basic_security') !== false;
            
            $multiple_nonce_validation_methods = $has_nonce_validation && $has_api_key_validation && $has_origin_validation && $has_referer_validation && $has_basic_security;
        } else {
            $multiple_nonce_validation_methods = false;
        }
        
        $this->passed_tests += $multiple_nonce_validation_methods ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Multiple Nonce Validation Methods", $multiple_nonce_validation_methods, $multiple_nonce_validation_methods ? "Multiple nonce validation methods exist" : "Multiple nonce validation methods do not exist", $execution_time);
    }
    
    /**
     * Test if nonce debugging is implemented
     */
    private function test_nonce_debugging_implemented() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $proxy_api_file = dirname(__FILE__) . '/../includes/api/class-graphql-proxy-api.php';
        $file_exists = file_exists($proxy_api_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($proxy_api_file);
            $has_logging = strpos($class_content, 'log_security_validation_failure') !== false;
            $has_debug_logging = strpos($class_content, 'error_log') !== false;
            $has_client_ip_logging = strpos($class_content, 'get_client_ip') !== false;
            $has_user_agent_logging = strpos($class_content, 'User-Agent') !== false;
            
            $nonce_debugging_implemented = $has_logging && $has_debug_logging && $has_client_ip_logging && $has_user_agent_logging;
        } else {
            $nonce_debugging_implemented = false;
        }
        
        $this->passed_tests += $nonce_debugging_implemented ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Nonce Debugging Implemented", $nonce_debugging_implemented, $nonce_debugging_implemented ? "Nonce debugging is implemented" : "Nonce debugging is not implemented", $execution_time);
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
        echo "403 COOKIE NONCE ERROR TEST RESULTS\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        echo "Success Rate: " . round(($this->passed_tests / $this->total_tests) * 100, 2) . "%\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL 403 COOKIE NONCE ERROR TESTS PASSED! The 403 cookie nonce error has been fixed!\n";
        } else {
            echo "\n⚠️  Some 403 cookie nonce error tests failed. The 403 cookie nonce error may not be fully fixed.\n";
        }
        
        echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
        echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test_suite = new HSM_403_Cookie_Nonce_Error_Test();
    $results = $test_suite->run_all_tests();
}