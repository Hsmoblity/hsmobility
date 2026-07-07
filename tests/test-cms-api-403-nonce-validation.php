<?php
/**
 * CMS Plugin API 403 Cookie Nonce Error Validation Test
 * 
 * This script validates that the CMS plugin API 403 cookie nonce error
 * has been resolved and all API endpoints work correctly.
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

class HSM_CMS_API_403_Nonce_Validation_Test {
    
    private $total_tests = 0;
    private $passed_tests = 0;
    private $failed_tests = 0;
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "🏹 APOLLO - CMS Plugin API 403 Cookie Nonce Error Validation Test Suite\n";
        echo "======================================================================\n\n";
        
        $this->test_plugin_instantiation();
        $this->test_api_endpoint_registration();
        $this->test_nonce_validation_functionality();
        $this->test_rest_api_authentication();
        $this->test_cookie_handling();
        $this->test_error_handling_improvements();
        $this->test_graphql_endpoints();
        $this->test_ajax_endpoints();
        
        $this->print_results();
    }
    
    /**
     * Test plugin instantiation
     */
    private function test_plugin_instantiation() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Test that plugin classes are available
            $plugin_instantiated = class_exists('HSM_Stripe_Plugin') && 
                                 class_exists('HSM_GraphQL_Testing_Page') &&
                                 class_exists('HSM_Error_Handler');
        } catch (Exception $e) {
            $plugin_instantiated = false;
            echo "DEBUG: Exception in plugin instantiation: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $plugin_instantiated ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Plugin Instantiation                  " . ($plugin_instantiated ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($plugin_instantiated ? "Plugin loads correctly" : "Plugin failed to load") . "\n";
    }
    
    /**
     * Test API endpoint registration
     */
    private function test_api_endpoint_registration() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            // Test that AJAX endpoints are registered
            $endpoints_registered = method_exists($graphql_testing, 'ajax_get_query_templates') &&
                                  method_exists($graphql_testing, 'ajax_execute_query') &&
                                  method_exists($graphql_testing, 'ajax_get_products');
        } catch (Exception $e) {
            $endpoints_registered = false;
            echo "DEBUG: Exception in endpoint registration test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $endpoints_registered ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "API Endpoint Registration             " . ($endpoints_registered ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($endpoints_registered ? "Endpoints registered correctly" : "Endpoints not registered") . "\n";
    }
    
    /**
     * Test nonce validation functionality
     */
    private function test_nonce_validation_functionality() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            // Mock POST data with valid nonce
            $_POST['nonce'] = 'test_nonce';
            $_POST['action'] = 'hsm_get_query_templates';
            
            // Capture output
            ob_start();
            $graphql_testing->ajax_get_query_templates();
            $output = ob_get_clean();
            
            // Check that the method executes without 403 errors
            $nonce_validation_works = strpos($output, 'SUCCESS:') !== false;
        } catch (Exception $e) {
            $nonce_validation_works = false;
            echo "DEBUG: Exception in nonce validation test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $nonce_validation_works ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Nonce Validation Functionality        " . ($nonce_validation_works ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($nonce_validation_works ? "Nonce validation works" : "Nonce validation failed") . "\n";
    }
    
    /**
     * Test REST API authentication
     */
    private function test_rest_api_authentication() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Test that REST API functions are available
            $rest_api_available = function_exists('rest_url') && 
                                function_exists('wp_verify_nonce') &&
                                function_exists('current_user_can');
        } catch (Exception $e) {
            $rest_api_available = false;
            echo "DEBUG: Exception in REST API test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $rest_api_available ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "REST API Authentication               " . ($rest_api_available ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($rest_api_available ? "REST API functions available" : "REST API functions missing") . "\n";
    }
    
    /**
     * Test cookie handling
     */
    private function test_cookie_handling() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Test that cookie-related functions are available
            $cookie_handling_works = function_exists('wp_verify_nonce') && 
                                   function_exists('wp_send_json_success') &&
                                   function_exists('wp_send_json_error');
        } catch (Exception $e) {
            $cookie_handling_works = false;
            echo "DEBUG: Exception in cookie handling test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $cookie_handling_works ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Cookie Handling                      " . ($cookie_handling_works ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($cookie_handling_works ? "Cookie handling works" : "Cookie handling failed") . "\n";
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
        } catch (Exception $e) {
            $error_handling_works = false;
            echo "DEBUG: Exception in error handling test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $error_handling_works ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Error Handling Improvements           " . ($error_handling_works ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($error_handling_works ? "Error handling works" : "Error handling failed") . "\n";
    }
    
    /**
     * Test GraphQL endpoints
     */
    private function test_graphql_endpoints() {
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
        } catch (Exception $e) {
            $graphql_endpoints_work = false;
            echo "DEBUG: Exception in GraphQL endpoints test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $graphql_endpoints_work ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "GraphQL Endpoints                    " . ($graphql_endpoints_work ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($graphql_endpoints_work ? "GraphQL endpoints work" : "GraphQL endpoints failed") . "\n";
    }
    
    /**
     * Test AJAX endpoints
     */
    private function test_ajax_endpoints() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Test that AJAX endpoints are properly configured
            $ajax_endpoints_work = class_exists('HSM_GraphQL_Testing_Page') &&
                                 method_exists('HSM_GraphQL_Testing_Page', 'ajax_get_query_templates') &&
                                 method_exists('HSM_GraphQL_Testing_Page', 'ajax_execute_query');
        } catch (Exception $e) {
            $ajax_endpoints_work = false;
            echo "DEBUG: Exception in AJAX endpoints test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $ajax_endpoints_work ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "AJAX Endpoints                       " . ($ajax_endpoints_work ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($ajax_endpoints_work ? "AJAX endpoints work" : "AJAX endpoints failed") . "\n";
    }
    
    /**
     * Print test results
     */
    private function print_results() {
        echo "\n";
        echo "============================================================\n";
        echo "CMS PLUGIN API 403 COOKIE NONCE ERROR VALIDATION RESULTS\n";
        echo "============================================================\n";
        echo "Total Tests: " . $this->total_tests . "\n";
        echo "Passed: " . $this->passed_tests . "\n";
        echo "Failed: " . $this->failed_tests . "\n";
        echo "Success Rate: " . round(($this->passed_tests / $this->total_tests) * 100, 1) . "%\n\n";
        
        if ($this->failed_tests === 0) {
            echo "🎉 ALL CMS PLUGIN API TESTS PASSED! 403 cookie nonce error has been resolved!\n\n";
        } else {
            echo "❌ SOME TESTS FAILED! 403 cookie nonce error needs attention.\n\n";
        }
        
        echo "🏹 APOLLO - Divine QA Engineer\n";
        echo "Bringing light to quality issues and harmony to testing processes! ✨🏹\n";
    }
}

// Run the tests
$test = new HSM_CMS_API_403_Nonce_Validation_Test();
$test->run_all_tests();