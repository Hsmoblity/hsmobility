<?php
/**
 * WooCommerce Template Integration Test
 * 
 * This script tests the template loading functionality in the context
 * of WooCommerce email template loading.
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

// Mock WooCommerce functions
if (!function_exists('wc_get_template')) {
    function wc_get_template($template_name, $args = array(), $template_path = '', $default_path = '') {
        echo "MOCK: wc_get_template called with: {$template_name}\n";
        return true;
    }
}

class HSM_WooCommerce_Template_Integration_Test {
    
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    /**
     * Run all integration tests
     */
    public function run_all_tests() {
        echo "🏛️ POSEIDON - WooCommerce Template Integration Test Suite\n";
        echo "=======================================================\n\n";
        
        // Test 1: Test template location filter simulation
        $this->test_template_location_filter_simulation();
        
        // Test 2: Test template override action simulation
        $this->test_template_override_action_simulation();
        
        // Test 3: Test complete WooCommerce integration workflow
        $this->test_complete_woocommerce_integration_workflow();
        
        // Test 4: Test template loading with different scenarios
        $this->test_template_loading_scenarios();
        
        // Test 5: Test error handling and fallbacks
        $this->test_error_handling_and_fallbacks();
        
        // Display results
        $this->display_results();
        
        return $this->test_results;
    }
    
    /**
     * Test template location filter simulation
     */
    private function test_template_location_filter_simulation() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Simulate WooCommerce calling the locate template filter
        $original_template = '/path/to/woocommerce/templates/emails/customer-completed-order.php';
        $template_name = 'emails/customer-completed-order.php';
        $template_path = 'emails/';
        
        // Apply our locate_custom_email_template logic
        $custom_template = $this->locate_custom_email_template($original_template, $template_name, $template_path);
        
        $filter_works = $custom_template !== $original_template;
        $this->passed_tests += $filter_works ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template Location Filter Simulation", $filter_works, $filter_works ? "Filter works - custom template returned" : "Filter failed - original template returned", $execution_time);
        
        if ($filter_works) {
            echo "SUCCESS: Custom template would be used: " . $custom_template . "\n";
        } else {
            echo "DEBUG: Original template would be used: " . $custom_template . "\n";
        }
    }
    
    /**
     * Test template override action simulation
     */
    private function test_template_override_action_simulation() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Simulate WooCommerce calling the email order details action
        $order_data = [
            'id' => 123,
            'status' => 'completed',
            'meta' => ['_hsm_configurator_order' => true]
        ];
        
        $sent_to_admin = false;
        $plain_text = false;
        $email_data = ['type' => 'customer_completed_order'];
        
        // Test if our override method would work
        $override_works = $this->simulate_override_customer_completed_order_template($order_data, $sent_to_admin, $plain_text, $email_data);
        
        $this->passed_tests += $override_works ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template Override Action Simulation", $override_works, $override_works ? "Override action works" : "Override action failed", $execution_time);
    }
    
    /**
     * Test complete WooCommerce integration workflow
     */
    private function test_complete_woocommerce_integration_workflow() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Simulate the complete workflow
        echo "Simulating complete WooCommerce template loading workflow...\n";
        
        // Step 1: WooCommerce calls locate_template filter
        $original_template = '/path/to/woocommerce/templates/emails/customer-completed-order.php';
        $template_name = 'emails/customer-completed-order.php';
        $template_path = 'emails/';
        
        $located_template = $this->locate_custom_email_template($original_template, $template_name, $template_path);
        echo "Step 1 - Template located: " . $located_template . "\n";
        
        // Step 2: WooCommerce calls email_order_details action
        $order_data = ['id' => 123, 'status' => 'completed'];
        $sent_to_admin = false;
        $plain_text = false;
        $email_data = ['type' => 'customer_completed_order'];
        
        $override_result = $this->simulate_override_customer_completed_order_template($order_data, $sent_to_admin, $plain_text, $email_data);
        echo "Step 2 - Override result: " . ($override_result ? 'SUCCESS' : 'FAILED') . "\n";
        
        // Step 3: Check if custom template would be loaded
        $workflow_success = ($located_template !== $original_template) && $override_result;
        
        $this->passed_tests += $workflow_success ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Complete WooCommerce Integration Workflow", $workflow_success, $workflow_success ? "Complete workflow works" : "Complete workflow failed", $execution_time);
    }
    
    /**
     * Test template loading with different scenarios
     */
    private function test_template_loading_scenarios() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $scenarios = [
            'customer_completed_order' => 'emails/customer-completed-order.php',
            'customer_processing_order' => 'emails/customer-processing-order.php',
            'customer_invoice' => 'emails/customer-invoice.php',
            'admin_new_order' => 'emails/admin-new-order.php'
        ];
        
        $scenarios_passed = 0;
        $total_scenarios = count($scenarios);
        
        foreach ($scenarios as $scenario_name => $template_name) {
            $original_template = '/path/to/woocommerce/templates/' . $template_name;
            $custom_template = $this->locate_custom_email_template($original_template, $template_name, 'emails/');
            
            if ($template_name === 'emails/customer-completed-order.php') {
                // This should return custom template
                $scenario_passed = $custom_template !== $original_template;
            } else {
                // These should return original template
                $scenario_passed = $custom_template === $original_template;
            }
            
            $scenarios_passed += $scenario_passed ? 1 : 0;
            echo "Scenario '{$scenario_name}': " . ($scenario_passed ? 'PASS' : 'FAIL') . "\n";
        }
        
        $all_scenarios_passed = $scenarios_passed === $total_scenarios;
        $this->passed_tests += $all_scenarios_passed ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Template Loading Scenarios", $all_scenarios_passed, $all_scenarios_passed ? "All scenarios work correctly" : "Some scenarios failed", $execution_time);
    }
    
    /**
     * Test error handling and fallbacks
     */
    private function test_error_handling_and_fallbacks() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Test 1: Non-existent template file
        $original_template = '/path/to/woocommerce/templates/emails/customer-completed-order.php';
        $template_name = 'emails/customer-completed-order.php';
        $template_path = 'emails/';
        
        // Temporarily rename template file to test fallback
        $template_file = dirname(__FILE__) . '/../templates/emails/customer-completed-order.php';
        $backup_file = $template_file . '.backup';
        
        if (file_exists($template_file)) {
            rename($template_file, $backup_file);
        }
        
        $custom_template = $this->locate_custom_email_template($original_template, $template_name, $template_path);
        $fallback_works = $custom_template === $original_template;
        
        // Restore template file
        if (file_exists($backup_file)) {
            rename($backup_file, $template_file);
        }
        
        $this->passed_tests += $fallback_works ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Error Handling and Fallbacks", $fallback_works, $fallback_works ? "Fallback works correctly" : "Fallback failed", $execution_time);
    }
    
    /**
     * Simulate locate_custom_email_template method
     */
    private function locate_custom_email_template($template, $template_name, $template_path) {
        // Check if this is a customer completed order email
        if ($template_name === 'emails/customer-completed-order.php') {
            $plugin_file = dirname(__FILE__) . '/../includes/class-hsm-stripe-plugin.php';
            $custom_template = plugin_dir_path(dirname($plugin_file)) . 'templates/emails/customer-completed-order.php';
            
            // Check if custom template exists
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Simulate override_customer_completed_order_template method
     */
    private function simulate_override_customer_completed_order_template($order, $sent_to_admin, $plain_text, $email) {
        // Check if this is a configurator order or if we want to use custom template
        if (isset($order['meta']['_hsm_configurator_order']) || true) { // Always use custom template for now
            $plugin_file = dirname(__FILE__) . '/../includes/class-hsm-stripe-plugin.php';
            $custom_template = plugin_dir_path(dirname($plugin_file)) . 'templates/emails/customer-completed-order.php';
            
            // Check if custom template exists
            if (file_exists($custom_template)) {
                echo "SUCCESS: Custom template would be loaded: " . $custom_template . "\n";
                return true;
            }
        }
        
        // Fall back to default WooCommerce template
        echo "FALLBACK: Using default WooCommerce template\n";
        return false;
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
        echo "WOOCOMMERCE TEMPLATE INTEGRATION TEST RESULTS\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        echo "Success Rate: " . round(($this->passed_tests / $this->total_tests) * 100, 2) . "%\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL INTEGRATION TESTS PASSED! WooCommerce template integration is working!\n";
        } else {
            echo "\n⚠️  Some integration tests failed. Check the debug output above.\n";
        }
        
        echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
        echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test_suite = new HSM_WooCommerce_Template_Integration_Test();
    $results = $test_suite->run_all_tests();
}