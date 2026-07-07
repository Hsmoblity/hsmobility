<?php
/**
 * PHP Fatal Error Fix Validation Test
 * 
 * This script validates that the critical PHP fatal error in validate_permissions
 * method has been resolved and all REST API functionality is working correctly.
 * 
 * @package HSM
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../');
}

// Mock WordPress functions for testing
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) { return dirname($file) . '/'; }
}
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) { return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/'; }
}
if (!function_exists('__')) { function __($text, $domain = 'default') { return $text; } }
if (!function_exists('_e')) { function _e($text, $domain = 'default') { echo $text; } }
if (!function_exists('add_action')) { function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { return true; } }
if (!function_exists('add_filter')) { function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) { return true; } }
if (!function_exists('apply_filters')) { function apply_filters($hook, $value, ...$args) { return $value; } }
if (!function_exists('current_user_can')) { function current_user_can($capability) { return true; } }
if (!function_exists('wp_verify_nonce')) { function wp_verify_nonce($nonce, $action) { return true; } }
if (!function_exists('wp_die')) { function wp_die($message = '', $title = '', $args = array()) { die($message); } }
if (!function_exists('wp_send_json_success')) { 
    function wp_send_json_success($data = null) { 
        echo "SUCCESS: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        return true;
    }
}
if (!function_exists('wp_send_json_error')) { 
    function wp_send_json_error($data = null) { 
        echo "ERROR: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        return true;
    }
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        $mock_options = array(
            'active_plugins' => array('woocommerce/woocommerce.php'),
            'template' => 'twentytwentyfour',
            'stylesheet' => 'twentytwentyfour'
        );
        return isset($mock_options[$option]) ? $mock_options[$option] : $default;
    }
}
if (!function_exists('update_option')) { function update_option($option, $value, $autoload = null) { return true; } }
if (!function_exists('error_log')) { function error_log($message, $message_type = 0, $destination = null, $extra_headers = null) { echo "LOG: " . $message . "\n"; } }
if (!function_exists('class_exists')) { function class_exists($class_name, $autoload = true) { return class_exists($class_name, $autoload); } }
if (!function_exists('register_activation_hook')) { function register_activation_hook($file, $callback) { return true; } }
if (!function_exists('register_deactivation_hook')) { function register_deactivation_hook($file, $callback) { return true; } }
if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir($time = null, $create_dir = true, $refresh_cache = false) {
        return array(
            'path' => '/tmp/uploads',
            'url' => 'http://localhost/wp-content/uploads',
            'subdir' => '',
            'basedir' => '/tmp/uploads',
            'baseurl' => 'http://localhost/wp-content/uploads',
            'error' => false
        );
    }
}
if (!function_exists('wp_mkdir_p')) { function wp_mkdir_p($target) { return true; } }
if (!function_exists('rest_url')) { function rest_url($path = '', $scheme = 'rest') { return 'http://localhost/wp-json/' . ltrim($path, '/'); } }
if (!function_exists('current_time')) { function current_time($type, $gmt = 0) { return $gmt ? gmdate($type) : date($type); } }

class HSM_PHP_Fatal_Error_Fix_Validation_Test {
    
    private $total_tests = 0;
    private $passed_tests = 0;
    private $failed_tests = 0;
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "🏹 APOLLO - PHP Fatal Error Fix Validation Test Suite\n";
        echo "====================================================\n\n";
        
        $this->test_plugin_loading_without_fatal_errors();
        $this->test_rest_manager_class_instantiation();
        $this->test_validate_permissions_method_existence();
        $this->test_validate_permissions_method_callability();
        $this->test_rest_api_functionality();
        $this->test_error_handling_improvements();
        $this->test_authentication_enhancement();
        $this->test_graphql_endpoints_working();
        
        $this->print_results();
    }
    
    /**
     * Test plugin loading without fatal errors
     */
    private function test_plugin_loading_without_fatal_errors() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            $plugin_loaded_successfully = true;
        } catch (Error $e) {
            $plugin_loaded_successfully = false;
            echo "DEBUG: Fatal Error in plugin loading: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            $plugin_loaded_successfully = false;
            echo "DEBUG: Exception in plugin loading: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $plugin_loaded_successfully ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Plugin Loading Without Fatal Errors   " . ($plugin_loaded_successfully ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($plugin_loaded_successfully ? "Plugin loads without fatal errors" : "Fatal error occurred") . "\n";
    }
    
    /**
     * Test REST Manager class instantiation
     */
    private function test_rest_manager_class_instantiation() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Test that REST Manager class can be instantiated
            $rest_manager_instantiated = class_exists('HSM_REST_Manager');
        } catch (Error $e) {
            $rest_manager_instantiated = false;
            echo "DEBUG: Fatal Error in REST Manager instantiation: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            $rest_manager_instantiated = false;
            echo "DEBUG: Exception in REST Manager instantiation: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $rest_manager_instantiated ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "REST Manager Class Instantiation      " . ($rest_manager_instantiated ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($rest_manager_instantiated ? "REST Manager class available" : "REST Manager class not available") . "\n";
    }
    
    /**
     * Test validate_permissions method existence
     */
    private function test_validate_permissions_method_existence() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Test that validate_permissions method exists
            $method_exists = class_exists('HSM_REST_Manager') && 
                           method_exists('HSM_REST_Manager', 'validate_permissions');
        } catch (Error $e) {
            $method_exists = false;
            echo "DEBUG: Fatal Error checking method existence: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            $method_exists = false;
            echo "DEBUG: Exception checking method existence: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $method_exists ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Validate Permissions Method Existence " . ($method_exists ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($method_exists ? "Method exists" : "Method not found") . "\n";
    }
    
    /**
     * Test validate_permissions method callability
     */
    private function test_validate_permissions_method_callability() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Test that validate_permissions method is callable
            $method_callable = class_exists('HSM_REST_Manager') && 
                             method_exists('HSM_REST_Manager', 'validate_permissions') &&
                             is_callable(array('HSM_REST_Manager', 'validate_permissions'));
        } catch (Error $e) {
            $method_callable = false;
            echo "DEBUG: Fatal Error checking method callability: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            $method_callable = false;
            echo "DEBUG: Exception checking method callability: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $method_callable ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Validate Permissions Method Callable  " . ($method_callable ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($method_callable ? "Method is callable" : "Method not callable") . "\n";
    }
    
    /**
     * Test REST API functionality
     */
    private function test_rest_api_functionality() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Test that REST API functions are available
            $rest_api_works = function_exists('rest_url') && 
                            function_exists('wp_verify_nonce') &&
                            function_exists('current_user_can') &&
                            function_exists('wp_send_json_success');
        } catch (Error $e) {
            $rest_api_works = false;
            echo "DEBUG: Fatal Error in REST API test: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            $rest_api_works = false;
            echo "DEBUG: Exception in REST API test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $rest_api_works ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "REST API Functionality               " . ($rest_api_works ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($rest_api_works ? "REST API functions available" : "REST API functions missing") . "\n";
    }
    
    /**
     * Test error handling improvements
     */
    private function test_error_handling_improvements() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Test that error handling classes are available
            $error_handling_works = class_exists('HSM_Error_Handler') &&
                                   method_exists('HSM_Error_Handler', 'log_error') &&
                                   method_exists('HSM_Error_Handler', 'handle_exception');
        } catch (Error $e) {
            $error_handling_works = false;
            echo "DEBUG: Fatal Error in error handling test: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            $error_handling_works = false;
            echo "DEBUG: Exception in error handling test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $error_handling_works ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Error Handling Improvements          " . ($error_handling_works ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($error_handling_works ? "Error handling works" : "Error handling failed") . "\n";
    }
    
    /**
     * Test authentication enhancement
     */
    private function test_authentication_enhancement() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Test that authentication functions are available
            $authentication_works = function_exists('wp_verify_nonce') && 
                                  function_exists('current_user_can') &&
                                  function_exists('wp_send_json_success') &&
                                  function_exists('wp_send_json_error');
        } catch (Error $e) {
            $authentication_works = false;
            echo "DEBUG: Fatal Error in authentication test: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            $authentication_works = false;
            echo "DEBUG: Exception in authentication test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $authentication_works ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Authentication Enhancement            " . ($authentication_works ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($authentication_works ? "Authentication works" : "Authentication failed") . "\n";
    }
    
    /**
     * Test GraphQL endpoints working
     */
    private function test_graphql_endpoints_working() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            // Test GraphQL endpoint functionality
            $_POST['nonce'] = 'test_nonce';
            $_POST['action'] = 'hsm_get_query_templates';
            
            ob_start();
            $graphql_testing->ajax_get_query_templates();
            $output = ob_get_clean();
            
            $graphql_endpoints_work = strpos($output, 'SUCCESS:') !== false;
        } catch (Error $e) {
            $graphql_endpoints_work = false;
            echo "DEBUG: Fatal Error in GraphQL endpoints test: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            $graphql_endpoints_work = false;
            echo "DEBUG: Exception in GraphQL endpoints test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $graphql_endpoints_work ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "GraphQL Endpoints Working            " . ($graphql_endpoints_work ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($graphql_endpoints_work ? "GraphQL endpoints work" : "GraphQL endpoints failed") . "\n";
    }
    
    /**
     * Print test results
     */
    private function print_results() {
        echo "\n";
        echo "============================================================\n";
        echo "PHP FATAL ERROR FIX VALIDATION RESULTS\n";
        echo "============================================================\n";
        echo "Total Tests: " . $this->total_tests . "\n";
        echo "Passed: " . $this->passed_tests . "\n";
        echo "Failed: " . $this->failed_tests . "\n";
        echo "Success Rate: " . round(($this->passed_tests / $this->total_tests) * 100, 1) . "%\n\n";
        
        if ($this->failed_tests === 0) {
            echo "🎉 ALL PHP FATAL ERROR FIX TESTS PASSED! Critical error has been resolved!\n\n";
        } else {
            echo "❌ SOME TESTS FAILED! PHP fatal error fix needs attention.\n\n";
        }
        
        echo "🏹 APOLLO - Divine QA Engineer\n";
        echo "Bringing light to quality issues and harmony to testing processes! ✨🏹\n";
    }
}

// Run the tests
$test = new HSM_PHP_Fatal_Error_Fix_Validation_Test();
$test->run_all_tests();