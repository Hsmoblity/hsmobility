<?php
/**
 * Simple GraphQL Proxy Tab Test
 * 
 * This script tests the GraphQL proxy tab functionality without
 * loading the entire WordPress environment.
 * 
 * @package HSM
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Define ABSPATH for standalone testing
    define('ABSPATH', dirname(__FILE__) . '/../../');
}

class HSM_GraphQL_Proxy_Tab_Simple_Test {
    
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    /**
     * Run all simple tests
     */
    public function run_all_tests() {
        echo "🏛️ POSEIDON - Simple GraphQL Proxy Tab Test Suite\n";
        echo "===============================================\n\n";
        
        // Test 1: Check if GraphQL testing page class exists
        $this->test_graphql_testing_page_class_exists();
        
        // Test 2: Check if JavaScript file exists and is valid
        $this->test_javascript_file_validity();
        
        // Test 3: Check if CSS file exists and is valid
        $this->test_css_file_validity();
        
        // Test 4: Check if AJAX handlers are properly defined
        $this->test_ajax_handlers_defined();
        
        // Test 5: Check if page rendering method exists
        $this->test_page_rendering_method_exists();
        
        // Display results
        $this->display_results();
        
        return $this->test_results;
    }
    
    /**
     * Test if GraphQL testing page class exists
     */
    private function test_graphql_testing_page_class_exists() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $class_file = dirname(__FILE__) . '/../includes/admin/class-graphql-testing-page.php';
        $file_exists = file_exists($class_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($class_file);
            $class_defined = strpos($class_content, 'class HSM_GraphQL_Testing_Page') !== false;
        } else {
            $class_defined = false;
        }
        
        $class_exists = $file_exists && $class_defined;
        $this->passed_tests += $class_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("GraphQL Testing Page Class Exists", $class_exists, $class_exists ? "Class file exists and is defined" : "Class file missing or not defined", $execution_time);
    }
    
    /**
     * Test if JavaScript file exists and is valid
     */
    private function test_javascript_file_validity() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $js_file = dirname(__FILE__) . '/../assets/js/graphql-testing.js';
        $file_exists = file_exists($js_file);
        
        if ($file_exists) {
            $js_content = file_get_contents($js_file);
            $has_jquery_wrapper = strpos($js_content, '(function($) {') !== false;
            $has_init_function = strpos($js_content, 'GraphQLTesting.init()') !== false;
            $has_event_bindings = strpos($js_content, 'bindEvents') !== false;
            $has_ajax_calls = strpos($js_content, '$.ajax') !== false;
            $properly_closed = strpos($js_content, '})(jQuery);') !== false;
            
            $js_valid = $has_jquery_wrapper && $has_init_function && $has_event_bindings && $has_ajax_calls && $properly_closed;
        } else {
            $js_valid = false;
        }
        
        $this->passed_tests += $js_valid ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("JavaScript File Validity", $js_valid, $js_valid ? "JavaScript file is valid" : "JavaScript file is invalid or missing", $execution_time);
        
        if (!$js_valid && $file_exists) {
            echo "DEBUG: JavaScript file analysis:\n";
            echo "  - Has jQuery wrapper: " . ($has_jquery_wrapper ? 'YES' : 'NO') . "\n";
            echo "  - Has init function: " . ($has_init_function ? 'YES' : 'NO') . "\n";
            echo "  - Has event bindings: " . ($has_event_bindings ? 'YES' : 'NO') . "\n";
            echo "  - Has AJAX calls: " . ($has_ajax_calls ? 'YES' : 'NO') . "\n";
            echo "  - Properly closed: " . ($properly_closed ? 'YES' : 'NO') . "\n";
        }
    }
    
    /**
     * Test if CSS file exists and is valid
     */
    private function test_css_file_validity() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $css_file = dirname(__FILE__) . '/../assets/css/graphql-testing.css';
        $file_exists = file_exists($css_file);
        
        if ($file_exists) {
            $css_content = file_get_contents($css_file);
            $has_main_class = strpos($css_content, '.hsm-graphql-testing') !== false;
            $has_button_styles = strpos($css_content, 'button') !== false;
            $has_status_styles = strpos($css_content, 'status') !== false;
            $has_section_styles = strpos($css_content, 'hsm-testing-section') !== false;
            
            $css_valid = $has_main_class && $has_button_styles && $has_status_styles && $has_section_styles;
        } else {
            $css_valid = false;
        }
        
        $this->passed_tests += $css_valid ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("CSS File Validity", $css_valid, $css_valid ? "CSS file is valid" : "CSS file is invalid or missing", $execution_time);
    }
    
    /**
     * Test if AJAX handlers are properly defined
     */
    private function test_ajax_handlers_defined() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $class_file = dirname(__FILE__) . '/../includes/admin/class-graphql-testing-page.php';
        $file_exists = file_exists($class_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($class_file);
            $has_connection_handler = strpos($class_content, 'ajax_test_graphql_connection') !== false;
            $has_query_handler = strpos($class_content, 'ajax_execute_graphql_query') !== false;
            $has_template_handler = strpos($class_content, 'ajax_get_query_templates') !== false;
            $has_wp_ajax_actions = strpos($class_content, 'wp_ajax_') !== false;
            
            $ajax_handlers_defined = $has_connection_handler && $has_query_handler && $has_template_handler && $has_wp_ajax_actions;
        } else {
            $ajax_handlers_defined = false;
        }
        
        $this->passed_tests += $ajax_handlers_defined ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("AJAX Handlers Defined", $ajax_handlers_defined, $ajax_handlers_defined ? "AJAX handlers are properly defined" : "AJAX handlers are missing", $execution_time);
    }
    
    /**
     * Test if page rendering method exists
     */
    private function test_page_rendering_method_exists() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $class_file = dirname(__FILE__) . '/../includes/admin/class-graphql-testing-page.php';
        $file_exists = file_exists($class_file);
        
        if ($file_exists) {
            $class_content = file_get_contents($class_file);
            $has_render_method = strpos($class_content, 'public function render_page()') !== false;
            $has_html_output = strpos($class_content, '<div class="wrap hsm-graphql-testing">') !== false;
            $has_buttons = strpos($class_content, 'id="test-connection"') !== false;
            $has_form_elements = strpos($class_content, 'id="query-template"') !== false;
            
            $rendering_method_exists = $has_render_method && $has_html_output && $has_buttons && $has_form_elements;
        } else {
            $rendering_method_exists = false;
        }
        
        $this->passed_tests += $rendering_method_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Page Rendering Method Exists", $rendering_method_exists, $rendering_method_exists ? "Page rendering method exists with proper HTML" : "Page rendering method missing or incomplete", $execution_time);
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
        echo "SIMPLE GRAPHQL PROXY TAB TEST RESULTS\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        echo "Success Rate: " . round(($this->passed_tests / $this->total_tests) * 100, 2) . "%\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL SIMPLE TESTS PASSED! GraphQL proxy tab components are working!\n";
        } else {
            echo "\n⚠️  Some simple tests failed. Check the debug output above.\n";
        }
        
        echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
        echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test_suite = new HSM_GraphQL_Proxy_Tab_Simple_Test();
    $results = $test_suite->run_all_tests();
}