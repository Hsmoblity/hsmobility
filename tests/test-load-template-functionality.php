<?php
/**
 * Test Load Template Functionality
 * 
 * This script tests the load template to test functionality by verifying
 * that template loading methods are working correctly.
 * 
 * @package HSM
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSM_Load_Template_Functionality_Test {
    
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "🏛️ POSEIDON - Load Template Functionality Test Suite\n";
        echo "================================================\n\n";
        
        // Test 1: Check if template file exists
        $this->test_template_file_exists();
        
        // Test 2: Check if template loading methods exist
        $this->test_template_loading_methods_exist();
        
        // Test 3: Test template file path resolution
        $this->test_template_file_path_resolution();
        
        // Test 4: Test template loading hook registration
        $this->test_template_loading_hook_registration();
        
        // Test 5: Test template override functionality
        $this->test_template_override_functionality();
        
        // Test 6: Test template validation
        $this->test_template_validation();
        
        // Test 7: Test template loading error handling
        $this->test_template_loading_error_handling();
        
        // Test 8: Test template loading performance
        $this->test_template_loading_performance();
        
        // Display results
        $this->display_results();
        
        return $this->test_results;
    }
    
    /**
     * Test if template file exists
     */
    private function test_template_file_exists() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $template_file = plugin_dir_path(__FILE__) . '../templates/emails/customer-completed-order.php';
        $file_exists = file_exists($template_file);
        $this->passed_tests += $file_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template File Exists", $file_exists, $file_exists ? "Template file exists at: {$template_file}" : "Template file not found", $execution_time);
    }
    
    /**
     * Test if template loading methods exist
     */
    private function test_template_loading_methods_exist() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $locate_method_exists = method_exists('HSM_Stripe_Plugin', 'locate_custom_email_template');
        $override_method_exists = method_exists('HSM_Stripe_Plugin', 'override_customer_completed_order_template');
        
        $methods_exist = $locate_method_exists && $override_method_exists;
        $this->passed_tests += $methods_exist ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template Loading Methods Exist", $methods_exist, $methods_exist ? "Both template loading methods exist" : "Template loading methods missing", $execution_time);
    }
    
    /**
     * Test template file path resolution
     */
    private function test_template_file_path_resolution() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Create a mock plugin instance
            $plugin = new HSM_Stripe_Plugin();
            
            // Test the locate_custom_email_template method
            $template_path = $plugin->locate_custom_email_template(
                'default-template.php',
                'emails/customer-completed-order.php',
                'emails/'
            );
            
            $path_resolved = $template_path !== 'default-template.php';
        } catch (Exception $e) {
            $path_resolved = false;
        }
        
        $this->passed_tests += $path_resolved ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template File Path Resolution", $path_resolved, $path_resolved ? "Template path resolved correctly" : "Template path resolution failed", $execution_time);
    }
    
    /**
     * Test template loading hook registration
     */
    private function test_template_loading_hook_registration() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $locate_hook_registered = has_filter('woocommerce_locate_template', 'HSM_Stripe_Plugin::locate_custom_email_template');
        $override_hook_registered = has_action('woocommerce_email_order_details', 'HSM_Stripe_Plugin::override_customer_completed_order_template');
        
        $hooks_registered = $locate_hook_registered && $override_hook_registered;
        $this->passed_tests += $hooks_registered ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template Loading Hook Registration", $hooks_registered, $hooks_registered ? "Template loading hooks are registered" : "Template loading hooks not registered", $execution_time);
    }
    
    /**
     * Test template override functionality
     */
    private function test_template_override_functionality() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Create a mock plugin instance
            $plugin = new HSM_Stripe_Plugin();
            
            // Test that the override method can be called without errors
            $method_callable = is_callable([$plugin, 'override_customer_completed_order_template']);
            
            $override_functional = $method_callable;
        } catch (Exception $e) {
            $override_functional = false;
        }
        
        $this->passed_tests += $override_functional ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template Override Functionality", $override_functional, $override_functional ? "Template override method is callable" : "Template override method not callable", $execution_time);
    }
    
    /**
     * Test template validation
     */
    private function test_template_validation() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $template_file = plugin_dir_path(__FILE__) . '../templates/emails/customer-completed-order.php';
        
        if (file_exists($template_file)) {
            $template_content = file_get_contents($template_file);
            $is_valid_php = strpos($template_content, '<?php') !== false;
            $has_required_variables = strpos($template_content, '$order') !== false;
            
            $template_valid = $is_valid_php && $has_required_variables;
        } else {
            $template_valid = false;
        }
        
        $this->passed_tests += $template_valid ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template Validation", $template_valid, $template_valid ? "Template is valid PHP with required variables" : "Template validation failed", $execution_time);
    }
    
    /**
     * Test template loading error handling
     */
    private function test_template_loading_error_handling() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Create a mock plugin instance
            $plugin = new HSM_Stripe_Plugin();
            
            // Test with non-existent template
            $template_path = $plugin->locate_custom_email_template(
                'default-template.php',
                'emails/non-existent-template.php',
                'emails/'
            );
            
            // Should return original template for non-existent custom template
            $error_handled = $template_path === 'default-template.php';
        } catch (Exception $e) {
            $error_handled = false;
        }
        
        $this->passed_tests += $error_handled ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template Loading Error Handling", $error_handled, $error_handled ? "Error handling works correctly" : "Error handling failed", $execution_time);
    }
    
    /**
     * Test template loading performance
     */
    private function test_template_loading_performance() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Create a mock plugin instance
            $plugin = new HSM_Stripe_Plugin();
            
            // Test multiple template path resolutions
            $start_perf = microtime(true);
            for ($i = 0; $i < 100; $i++) {
                $plugin->locate_custom_email_template(
                    'default-template.php',
                    'emails/customer-completed-order.php',
                    'emails/'
                );
            }
            $end_perf = microtime(true);
            
            $execution_time_perf = $end_perf - $start_perf;
            $performance_acceptable = $execution_time_perf < 1.0; // Should complete in less than 1 second
        } catch (Exception $e) {
            $performance_acceptable = false;
        }
        
        $this->passed_tests += $performance_acceptable ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template Loading Performance", $performance_acceptable, $performance_acceptable ? "Template loading performance is acceptable" : "Template loading performance is too slow", $execution_time);
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
        echo "TEST RESULTS SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        echo "Success Rate: " . round(($this->passed_tests / $this->total_tests) * 100, 2) . "%\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL TESTS PASSED! Load Template functionality is working correctly!\n";
        } else {
            echo "\n⚠️  Some tests failed. Please check the issues above.\n";
        }
        
        echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
        echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test_suite = new HSM_Load_Template_Functionality_Test();
    $results = $test_suite->run_all_tests();
}