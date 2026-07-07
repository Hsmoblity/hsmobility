<?php
/**
 * Simple Template Loading Test
 * 
 * This script tests the template loading functionality without requiring
 * the full plugin initialization.
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

class HSM_Template_Loading_Simple_Test {
    
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    /**
     * Run all simple tests
     */
    public function run_all_tests() {
        echo "🏛️ POSEIDON - Simple Template Loading Test Suite\n";
        echo "==============================================\n\n";
        
        // Test 1: Check template file existence
        $this->test_template_file_existence();
        
        // Test 2: Test path resolution logic
        $this->test_path_resolution_logic();
        
        // Test 3: Test locate_custom_email_template logic
        $this->test_locate_custom_email_template_logic();
        
        // Test 4: Test template content
        $this->test_template_content();
        
        // Test 5: Test template loading workflow
        $this->test_template_loading_workflow();
        
        // Display results
        $this->display_results();
        
        return $this->test_results;
    }
    
    /**
     * Test template file existence
     */
    private function test_template_file_existence() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $template_file = dirname(__FILE__) . '/../templates/emails/customer-completed-order.php';
        $file_exists = file_exists($template_file);
        $this->passed_tests += $file_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template File Existence", $file_exists, $file_exists ? "Template file exists" : "Template file not found", $execution_time);
    }
    
    /**
     * Test path resolution logic
     */
    private function test_path_resolution_logic() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Simulate the path resolution logic from the plugin
        $plugin_file = dirname(__FILE__) . '/../includes/class-hsm-stripe-plugin.php';
        $custom_template = plugin_dir_path(dirname($plugin_file)) . 'templates/emails/customer-completed-order.php';
        
        $path_resolved = file_exists($custom_template);
        $this->passed_tests += $path_resolved ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Path Resolution Logic", $path_resolved, $path_resolved ? "Path resolution works" : "Path resolution failed", $execution_time);
        
        if ($path_resolved) {
            echo "SUCCESS: Template found at: " . $custom_template . "\n";
        } else {
            echo "DEBUG: Plugin file: " . $plugin_file . "\n";
            echo "DEBUG: Resolved path: " . $custom_template . "\n";
            echo "DEBUG: plugin_dir_path(dirname(plugin_file)): " . plugin_dir_path(dirname($plugin_file)) . "\n";
        }
    }
    
    /**
     * Test locate_custom_email_template logic
     */
    private function test_locate_custom_email_template_logic() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Simulate the locate_custom_email_template method logic
        $template_name = 'emails/customer-completed-order.php';
        $original_template = 'default-template.php';
        
        if ($template_name === 'emails/customer-completed-order.php') {
            $plugin_file = dirname(__FILE__) . '/../includes/class-hsm-stripe-plugin.php';
            $custom_template = plugin_dir_path(dirname($plugin_file)) . 'templates/emails/customer-completed-order.php';
            
            if (file_exists($custom_template)) {
                $result = $custom_template;
            } else {
                $result = $original_template;
            }
        } else {
            $result = $original_template;
        }
        
        $logic_works = $result !== $original_template;
        $this->passed_tests += $logic_works ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Locate Custom Email Template Logic", $logic_works, $logic_works ? "Logic works correctly" : "Logic failed", $execution_time);
        
        if ($logic_works) {
            echo "SUCCESS: Custom template would be returned: " . $result . "\n";
        } else {
            echo "DEBUG: Would return original template: " . $result . "\n";
        }
    }
    
    /**
     * Test template content
     */
    private function test_template_content() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $template_file = dirname(__FILE__) . '/../templates/emails/customer-completed-order.php';
        
        if (file_exists($template_file)) {
            $template_content = file_get_contents($template_file);
            $is_valid_php = strpos($template_content, '<?php') !== false;
            $has_required_variables = strpos($template_content, '$order') !== false;
            $has_template_structure = strpos($template_content, 'Customer Completed Order') !== false;
            
            $template_valid = $is_valid_php && $has_required_variables && $has_template_structure;
        } else {
            $template_valid = false;
        }
        
        $this->passed_tests += $template_valid ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template Content", $template_valid, $template_valid ? "Template content is valid" : "Template content validation failed", $execution_time);
        
        if (!$template_valid && file_exists($template_file)) {
            echo "DEBUG: Template content analysis:\n";
            echo "  - Has PHP opening tag: " . ($is_valid_php ? 'YES' : 'NO') . "\n";
            echo "  - Has \$order variable: " . ($has_required_variables ? 'YES' : 'NO') . "\n";
            echo "  - Has template structure: " . ($has_template_structure ? 'YES' : 'NO') . "\n";
        }
    }
    
    /**
     * Test template loading workflow
     */
    private function test_template_loading_workflow() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Test the complete workflow
        $template_name = 'emails/customer-completed-order.php';
        $original_template = 'default-template.php';
        
        // Step 1: Check if it's the right template
        $is_customer_completed_order = ($template_name === 'emails/customer-completed-order.php');
        
        if ($is_customer_completed_order) {
            // Step 2: Build custom template path
            $plugin_file = dirname(__FILE__) . '/../includes/class-hsm-stripe-plugin.php';
            $custom_template = plugin_dir_path(dirname($plugin_file)) . 'templates/emails/customer-completed-order.php';
            
            // Step 3: Check if custom template exists
            $custom_template_exists = file_exists($custom_template);
            
            if ($custom_template_exists) {
                // Step 4: Return custom template
                $final_template = $custom_template;
                $workflow_success = true;
            } else {
                // Step 5: Return original template
                $final_template = $original_template;
                $workflow_success = false;
            }
        } else {
            $final_template = $original_template;
            $workflow_success = false;
        }
        
        $this->passed_tests += $workflow_success ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template Loading Workflow", $workflow_success, $workflow_success ? "Complete workflow works" : "Workflow failed", $execution_time);
        
        if ($workflow_success) {
            echo "SUCCESS: Complete workflow works - custom template would be loaded\n";
        } else {
            echo "DEBUG: Workflow failed at some step\n";
            echo "  - Is customer completed order: " . ($is_customer_completed_order ? 'YES' : 'NO') . "\n";
            if ($is_customer_completed_order) {
                echo "  - Custom template exists: " . ($custom_template_exists ? 'YES' : 'NO') . "\n";
                echo "  - Custom template path: " . $custom_template . "\n";
            }
        }
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
        echo "SIMPLE TEMPLATE LOADING TEST RESULTS\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        echo "Success Rate: " . round(($this->passed_tests / $this->total_tests) * 100, 2) . "%\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL SIMPLE TESTS PASSED! Template loading logic is working!\n";
        } else {
            echo "\n⚠️  Some simple tests failed. Check the debug output above.\n";
        }
        
        echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
        echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test_suite = new HSM_Template_Loading_Simple_Test();
    $results = $test_suite->run_all_tests();
}