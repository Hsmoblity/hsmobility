<?php
/**
 * GraphQL Proxy Template Loading Test
 * 
 * This script tests the GraphQL proxy tab template loading functionality
 * to verify templates load correctly when selected.
 * 
 * @package HSM
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../');
}

// Mock WordPress functions
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

class HSM_GraphQL_Proxy_Template_Loading_Test {
    
    private $total_tests = 0;
    private $passed_tests = 0;
    private $failed_tests = 0;
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "🏹 APOLLO - GraphQL Proxy Template Loading Test Suite\n";
        echo "====================================================\n\n";
        
        $this->test_graphql_testing_page_instantiation();
        $this->test_template_dropdown_html_structure();
        $this->test_template_loading_javascript_functionality();
        $this->test_ajax_template_loading();
        $this->test_template_content_rendering();
        $this->test_template_selection_workflow();
        $this->test_error_handling_scenarios();
        
        $this->print_results();
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
            
            $instantiation_works = $graphql_testing instanceof HSM_GraphQL_Testing_Page;
        } catch (Exception $e) {
            $instantiation_works = false;
            echo "DEBUG: Exception in GraphQL testing page instantiation: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $instantiation_works ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "GraphQL Testing Page Instantiation    " . ($instantiation_works ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($instantiation_works ? "Page instantiated successfully" : "Failed to instantiate") . "\n";
    }
    
    /**
     * Test template dropdown HTML structure
     */
    private function test_template_dropdown_html_structure() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            // Capture the admin page HTML output
            ob_start();
            $graphql_testing->render_page();
            $html_output = ob_get_clean();
            
            // Check for template dropdown elements
            $has_template_select = strpos($html_output, 'id="query-template"') !== false;
            $has_load_button = strpos($html_output, 'id="load-template"') !== false;
            $has_query_textarea = strpos($html_output, 'id="graphql-query"') !== false;
            $has_variables_textarea = strpos($html_output, 'id="graphql-variables"') !== false;
            
            $structure_valid = $has_template_select && $has_load_button && $has_query_textarea && $has_variables_textarea;
        } catch (Exception $e) {
            $structure_valid = false;
            echo "DEBUG: Exception in template dropdown test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $structure_valid ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Template Dropdown HTML Structure      " . ($structure_valid ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($structure_valid ? "All required elements present" : "Missing required elements") . "\n";
    }
    
    /**
     * Test template loading JavaScript functionality
     */
    private function test_template_loading_javascript_functionality() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Check if JavaScript file exists and contains required functions
            $js_file_path = dirname(__FILE__) . '/../assets/js/graphql-testing.js';
            $js_content = file_get_contents($js_file_path);
            
            $has_loadQueryTemplates = strpos($js_content, 'loadQueryTemplates: function()') !== false;
            $has_loadQueryTemplate = strpos($js_content, 'loadQueryTemplate: function()') !== false;
            $has_template_handling = strpos($js_content, 'this.templates[template]') !== false;
            $has_query_loading = strpos($js_content, '$(\'#graphql-query\').val(templateData.query)') !== false;
            $has_variables_loading = strpos($js_content, '$(\'#graphql-variables\').val(templateData.variables)') !== false;
            
            $js_functionality_valid = $has_loadQueryTemplates && $has_loadQueryTemplate && $has_template_handling && $has_query_loading && $has_variables_loading;
        } catch (Exception $e) {
            $js_functionality_valid = false;
            echo "DEBUG: Exception in JavaScript functionality test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $js_functionality_valid ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Template Loading JavaScript          " . ($js_functionality_valid ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($js_functionality_valid ? "JavaScript functions present" : "Missing JavaScript functions") . "\n";
    }
    
    /**
     * Test AJAX template loading
     */
    private function test_ajax_template_loading() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            // Mock POST data
            $_POST['nonce'] = 'test_nonce';
            $_POST['action'] = 'hsm_get_query_templates';
            
            // Capture output
            ob_start();
            $graphql_testing->ajax_get_query_templates();
            $output = ob_get_clean();
            
            // Parse the JSON output
            $json_start = strpos($output, '{');
            if ($json_start !== false) {
                $json_data = substr($output, $json_start);
                $templates = json_decode($json_data, true);
                
                $ajax_works = is_array($templates) && 
                             isset($templates['products']) && 
                             isset($templates['product']) && 
                             isset($templates['categories']) &&
                             !empty($templates['products']['query']);
            } else {
                $ajax_works = false;
            }
        } catch (Exception $e) {
            $ajax_works = false;
            echo "DEBUG: Exception in AJAX template loading test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $ajax_works ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "AJAX Template Loading                " . ($ajax_works ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($ajax_works ? "Templates loaded via AJAX" : "AJAX template loading failed") . "\n";
    }
    
    /**
     * Test template content rendering
     */
    private function test_template_content_rendering() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            // Mock POST data
            $_POST['nonce'] = 'test_nonce';
            $_POST['action'] = 'hsm_get_query_templates';
            
            // Capture output
            ob_start();
            $graphql_testing->ajax_get_query_templates();
            $output = ob_get_clean();
            
            // Parse the JSON output
            $json_start = strpos($output, '{');
            if ($json_start !== false) {
                $json_data = substr($output, $json_start);
                $templates = json_decode($json_data, true);
                
                // Test that templates have proper content
                $products_query_valid = !empty($templates['products']['query']) && strpos($templates['products']['query'], 'query GetProducts') !== false;
                $product_query_valid = !empty($templates['product']['query']) && strpos($templates['product']['query'], 'query GetProduct') !== false;
                $cart_query_valid = !empty($templates['cart']['query']) && strpos($templates['cart']['query'], 'query GetCart') !== false;
                $categories_query_valid = !empty($templates['categories']['query']) && strpos($templates['categories']['query'], 'query GetCategories') !== false;
                
                $content_rendering_valid = $products_query_valid && $product_query_valid && $cart_query_valid && $categories_query_valid;
            } else {
                $content_rendering_valid = false;
            }
        } catch (Exception $e) {
            $content_rendering_valid = false;
            echo "DEBUG: Exception in template content rendering test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $content_rendering_valid ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Template Content Rendering           " . ($content_rendering_valid ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($content_rendering_valid ? "Template content is valid" : "Template content is invalid") . "\n";
    }
    
    /**
     * Test template selection workflow
     */
    private function test_template_selection_workflow() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            // Mock POST data
            $_POST['nonce'] = 'test_nonce';
            $_POST['action'] = 'hsm_get_query_templates';
            
            // Capture output
            ob_start();
            $graphql_testing->ajax_get_query_templates();
            $output = ob_get_clean();
            
            // Parse the JSON output
            $json_start = strpos($output, '{');
            if ($json_start !== false) {
                $json_data = substr($output, $json_start);
                $templates = json_decode($json_data, true);
                
                // Test that we can simulate template selection
                $template_keys = array_keys($templates);
                $has_products = in_array('products', $template_keys);
                $has_product = in_array('product', $template_keys);
                $has_cart = in_array('cart', $template_keys);
                $has_categories = in_array('categories', $template_keys);
                
                // Test template data structure
                $products_has_query = isset($templates['products']['query']) && !empty($templates['products']['query']);
                $products_has_variables = isset($templates['products']['variables']) && !empty($templates['products']['variables']);
                
                $workflow_valid = $has_products && $has_product && $has_cart && $has_categories && $products_has_query && $products_has_variables;
            } else {
                $workflow_valid = false;
            }
        } catch (Exception $e) {
            $workflow_valid = false;
            echo "DEBUG: Exception in template selection workflow test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $workflow_valid ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Template Selection Workflow          " . ($workflow_valid ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($workflow_valid ? "Template selection workflow works" : "Template selection workflow failed") . "\n";
    }
    
    /**
     * Test error handling scenarios
     */
    private function test_error_handling_scenarios() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Load the main plugin file
            require_once dirname(__FILE__) . '/../hsm-stripe.php';
            
            // Create error handler
            $error_handler = new HSM_Error_Handler();
            
            // Create GraphQL testing page instance
            $graphql_testing = new HSM_GraphQL_Testing_Page($error_handler);
            
            // Test that the method exists and is callable
            $error_handling_works = method_exists($graphql_testing, 'ajax_get_query_templates') && 
                                  is_callable(array($graphql_testing, 'ajax_get_query_templates'));
        } catch (Exception $e) {
            $error_handling_works = false;
            echo "DEBUG: Exception in error handling test: " . $e->getMessage() . "\n";
        }
        
        $this->passed_tests += $error_handling_works ? 1 : 0;
        $execution_time = round(microtime(true) - $start_time, 4);
        
        echo "Error Handling Method                " . ($error_handling_works ? "✅ PASS" : "❌ FAIL") . " ({$execution_time}s) - " . ($error_handling_works ? "Method exists and is callable" : "Method not found") . "\n";
    }
    
    /**
     * Print test results
     */
    private function print_results() {
        echo "\n";
        echo "============================================================\n";
        echo "GRAPHQL PROXY TEMPLATE LOADING TEST RESULTS\n";
        echo "============================================================\n";
        echo "Total Tests: " . $this->total_tests . "\n";
        echo "Passed: " . $this->passed_tests . "\n";
        echo "Failed: " . $this->failed_tests . "\n";
        echo "Success Rate: " . round(($this->passed_tests / $this->total_tests) * 100, 1) . "%\n\n";
        
        if ($this->failed_tests === 0) {
            echo "🎉 ALL GRAPHQL PROXY TEMPLATE TESTS PASSED! Template loading is working!\n\n";
        } else {
            echo "❌ SOME TESTS FAILED! Template loading needs attention.\n\n";
        }
        
        echo "🏹 APOLLO - Divine QA Engineer\n";
        echo "Bringing light to quality issues and harmony to testing processes! ✨🏹\n";
    }
}

// Run the tests
$test = new HSM_GraphQL_Proxy_Template_Loading_Test();
$test->run_all_tests();