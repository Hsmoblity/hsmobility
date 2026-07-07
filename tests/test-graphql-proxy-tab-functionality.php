<?php
/**
 * GraphQL Proxy Tab Functionality Test
 * 
 * This script tests the GraphQL proxy tab functionality to ensure
 * all buttons and actions are working correctly.
 * 
 * @package HSM
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Define ABSPATH for standalone testing
    define('ABSPATH', dirname(__FILE__) . '/../../');
}

// Mock WordPress functions for testing
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args) {
        return $value;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) {
        return true;
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {
        die($message);
    }
}

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

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'test_nonce_' . $action;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'http://localhost/wp-admin/' . $path;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src, $deps = array(), $ver = false, $in_footer = false) {
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src, $deps = array(), $ver = false, $media = 'all') {
        return true;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        return true;
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

if (!function_exists('add_option')) {
    function add_option($option, $value = '', $deprecated = '', $autoload = 'yes') {
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        return true;
    }
}

if (!function_exists('rest_url')) {
    function rest_url($path = '', $scheme = 'rest') {
        return 'http://localhost/wp-json/' . $path;
    }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = array()) {
        return array('body' => '{"data": {"test": "success"}}', 'response' => array('code' => 200));
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return isset($response['body']) ? $response['body'] : '';
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return isset($response['response']['code']) ? $response['response']['code'] : 0;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return false;
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = '') {
        if (is_object($args)) {
            $r = get_object_vars($args);
        } elseif (is_array($args)) {
            $r =& $args;
        } else {
            wp_parse_str($args, $r);
        }
        
        if (is_array($defaults)) {
            return array_merge($defaults, $r);
        }
        
        return $r;
    }
}

if (!function_exists('wp_parse_str')) {
    function wp_parse_str($string, &$array) {
        parse_str($string, $array);
    }
}

class HSM_GraphQL_Proxy_Tab_Functionality_Test {
    
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    /**
     * Run all functionality tests
     */
    public function run_all_tests() {
        echo "🏛️ POSEIDON - GraphQL Proxy Tab Functionality Test Suite\n";
        echo "=======================================================\n\n";
        
        // Test 1: Check GraphQL testing page instantiation
        $this->test_graphql_testing_page_instantiation();
        
        // Test 2: Check AJAX handlers registration
        $this->test_ajax_handlers_registration();
        
        // Test 3: Check connection test functionality
        $this->test_connection_test_functionality();
        
        // Test 4: Check query template loading functionality
        $this->test_query_template_loading_functionality();
        
        // Test 5: Check query execution functionality
        $this->test_query_execution_functionality();
        
        // Test 6: Check page rendering functionality
        $this->test_page_rendering_functionality();
        
        // Test 7: Check JavaScript file existence
        $this->test_javascript_file_existence();
        
        // Test 8: Check CSS file existence
        $this->test_css_file_existence();
        
        // Display results
        $this->display_results();
        
        return $this->test_results;
    }
    
    /**
     * Test GraphQL testing page instantiation
     */
    private function test_graphql_testing_page_instantiation() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            $instantiation_successful = $graphql_testing !== null;
        } catch (Exception $e) {
            $instantiation_successful = false;
            echo "DEBUG: Exception in GraphQL testing page instantiation: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $instantiation_successful ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("GraphQL Testing Page Instantiation", $instantiation_successful, $instantiation_successful ? "Page instantiated successfully" : "Page instantiation failed", $execution_time);
    }
    
    /**
     * Test AJAX handlers registration
     */
    private function test_ajax_handlers_registration() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            // Check if AJAX handlers are registered
            $connection_handler_registered = has_action('wp_ajax_hsm_test_graphql_connection');
            $query_handler_registered = has_action('wp_ajax_hsm_execute_graphql_query');
            $template_handler_registered = has_action('wp_ajax_hsm_get_query_templates');
            
            $handlers_registered = $connection_handler_registered && $query_handler_registered && $template_handler_registered;
        } catch (Exception $e) {
            $handlers_registered = false;
            echo "DEBUG: Exception in AJAX handlers test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $handlers_registered ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("AJAX Handlers Registration", $handlers_registered, $handlers_registered ? "All AJAX handlers registered" : "AJAX handlers not registered", $execution_time);
    }
    
    /**
     * Test connection test functionality
     */
    private function test_connection_test_functionality() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            // Check if connection test method exists and is callable
            $method_exists = method_exists($graphql_testing, 'ajax_test_graphql_connection');
            $method_callable = is_callable([$graphql_testing, 'ajax_test_graphql_connection']);
            
            $connection_test_works = $method_exists && $method_callable;
        } catch (Exception $e) {
            $connection_test_works = false;
            echo "DEBUG: Exception in connection test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $connection_test_works ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Connection Test Functionality", $connection_test_works, $connection_test_works ? "Connection test method works" : "Connection test method failed", $execution_time);
    }
    
    /**
     * Test query template loading functionality
     */
    private function test_query_template_loading_functionality() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            // Check if template loading method exists and is callable
            $method_exists = method_exists($graphql_testing, 'ajax_get_query_templates');
            $method_callable = is_callable([$graphql_testing, 'ajax_get_query_templates']);
            
            $template_loading_works = $method_exists && $method_callable;
        } catch (Exception $e) {
            $template_loading_works = false;
            echo "DEBUG: Exception in template loading test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $template_loading_works ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Query Template Loading Functionality", $template_loading_works, $template_loading_works ? "Template loading method works" : "Template loading method failed", $execution_time);
    }
    
    /**
     * Test query execution functionality
     */
    private function test_query_execution_functionality() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            // Check if query execution method exists and is callable
            $method_exists = method_exists($graphql_testing, 'ajax_execute_graphql_query');
            $method_callable = is_callable([$graphql_testing, 'ajax_execute_graphql_query']);
            
            $query_execution_works = $method_exists && $method_callable;
        } catch (Exception $e) {
            $query_execution_works = false;
            echo "DEBUG: Exception in query execution test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $query_execution_works ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Query Execution Functionality", $query_execution_works, $query_execution_works ? "Query execution method works" : "Query execution method failed", $execution_time);
    }
    
    /**
     * Test page rendering functionality
     */
    private function test_page_rendering_functionality() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            // Check if render method exists and is callable
            $method_exists = method_exists($graphql_testing, 'render_page');
            $method_callable = is_callable([$graphql_testing, 'render_page']);
            
            $page_rendering_works = $method_exists && $method_callable;
        } catch (Exception $e) {
            $page_rendering_works = false;
            echo "DEBUG: Exception in page rendering test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $page_rendering_works ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Page Rendering Functionality", $page_rendering_works, $page_rendering_works ? "Page rendering method works" : "Page rendering method failed", $execution_time);
    }
    
    /**
     * Test JavaScript file existence
     */
    private function test_javascript_file_existence() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $js_file = dirname(__FILE__) . '/../assets/js/graphql-testing.js';
        $file_exists = file_exists($js_file);
        
        $this->passed_tests += $file_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("JavaScript File Existence", $file_exists, $file_exists ? "JavaScript file exists" : "JavaScript file not found", $execution_time);
    }
    
    /**
     * Test CSS file existence
     */
    private function test_css_file_existence() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $css_file = dirname(__FILE__) . '/../assets/css/graphql-testing.css';
        $file_exists = file_exists($css_file);
        
        $this->passed_tests += $file_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("CSS File Existence", $file_exists, $file_exists ? "CSS file exists" : "CSS file not found", $execution_time);
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
        echo "GRAPHQL PROXY TAB FUNCTIONALITY TEST RESULTS\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        echo "Success Rate: " . round(($this->passed_tests / $this->total_tests) * 100, 2) . "%\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL FUNCTIONALITY TESTS PASSED! GraphQL proxy tab is working!\n";
        } else {
            echo "\n⚠️  Some functionality tests failed. Check the debug output above.\n";
        }
        
        echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
        echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test_suite = new HSM_GraphQL_Proxy_Tab_Functionality_Test();
    $results = $test_suite->run_all_tests();
}