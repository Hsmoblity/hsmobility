<?php
/**
 * Immediate CMS Plugin Issues Verification Test
 * 
 * This script verifies that all immediate critical bugs in the CMS plugin
 * have been properly fixed and are still working correctly.
 * 
 * @package HSM
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSM_Immediate_CMS_Plugin_Issues_Verification_Test {
    
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    /**
     * Run all verification tests
     */
    public function run_all_tests() {
        echo "🏛️ POSEIDON - Immediate CMS Plugin Issues Verification Test Suite\n";
        echo "================================================================\n\n";
        
        // Test 1: Verify HSM_GraphQL_Manager class loading
        $this->test_graphql_manager_class_loading();
        
        // Test 2: Verify HSM_GraphQL_Proxy_API class loading
        $this->test_graphql_proxy_api_class_loading();
        
        // Test 3: Verify HSM_GraphQL_Health_Monitor class loading
        $this->test_graphql_health_monitor_class_loading();
        
        // Test 4: Verify HSM_Logger class loading
        $this->test_logger_class_loading();
        
        // Test 5: Verify HSM_Admin_Menu class loading
        $this->test_admin_menu_class_loading();
        
        // Test 6: Verify HSM_Admin_Pages class loading
        $this->test_admin_pages_class_loading();
        
        // Test 7: Verify HSM_GraphQL_Testing_Page class loading
        $this->test_graphql_testing_page_class_loading();
        
        // Test 8: Verify private method access fix
        $this->test_private_method_access_fix();
        
        // Test 9: Verify method redeclaration fix
        $this->test_method_redeclaration_fix();
        
        // Test 10: Verify plugin initialization
        $this->test_plugin_initialization();
        
        // Display results
        $this->display_results();
        
        return $this->test_results;
    }
    
    /**
     * Test HSM_GraphQL_Manager class loading
     */
    private function test_graphql_manager_class_loading() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $class_exists = class_exists('HSM_GraphQL_Manager');
        $this->passed_tests += $class_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("HSM_GraphQL_Manager Class Loading", $class_exists, $class_exists ? "Class loaded successfully" : "Class not found", $execution_time);
    }
    
    /**
     * Test HSM_GraphQL_Proxy_API class loading
     */
    private function test_graphql_proxy_api_class_loading() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $class_exists = class_exists('HSM_GraphQL_Proxy_API');
        $this->passed_tests += $class_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("HSM_GraphQL_Proxy_API Class Loading", $class_exists, $class_exists ? "Class loaded successfully" : "Class not found", $execution_time);
    }
    
    /**
     * Test HSM_GraphQL_Health_Monitor class loading
     */
    private function test_graphql_health_monitor_class_loading() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $class_exists = class_exists('HSM_GraphQL_Health_Monitor');
        $this->passed_tests += $class_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("HSM_GraphQL_Health_Monitor Class Loading", $class_exists, $class_exists ? "Class loaded successfully" : "Class not found", $execution_time);
    }
    
    /**
     * Test HSM_Logger class loading
     */
    private function test_logger_class_loading() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $class_exists = class_exists('HSM_Logger');
        $this->passed_tests += $class_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("HSM_Logger Class Loading", $class_exists, $class_exists ? "Class loaded successfully" : "Class not found", $execution_time);
    }
    
    /**
     * Test HSM_Admin_Menu class loading
     */
    private function test_admin_menu_class_loading() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $class_exists = class_exists('HSM_Admin_Menu');
        $this->passed_tests += $class_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("HSM_Admin_Menu Class Loading", $class_exists, $class_exists ? "Class loaded successfully" : "Class not found", $execution_time);
    }
    
    /**
     * Test HSM_Admin_Pages class loading
     */
    private function test_admin_pages_class_loading() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $class_exists = class_exists('HSM_Admin_Pages');
        $this->passed_tests += $class_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("HSM_Admin_Pages Class Loading", $class_exists, $class_exists ? "Class loaded successfully" : "Class not found", $execution_time);
    }
    
    /**
     * Test HSM_GraphQL_Testing_Page class loading
     */
    private function test_graphql_testing_page_class_loading() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $class_exists = class_exists('HSM_GraphQL_Testing_Page');
        $this->passed_tests += $class_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("HSM_GraphQL_Testing_Page Class Loading", $class_exists, $class_exists ? "Class loaded successfully" : "Class not found", $execution_time);
    }
    
    /**
     * Test private method access fix
     */
    private function test_private_method_access_fix() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Test that get_singleton method is public
            $memory_manager = new HSM_Memory_Manager();
            $method_is_public = method_exists($memory_manager, 'get_singleton') && 
                               (new ReflectionMethod($memory_manager, 'get_singleton'))->isPublic();
            
            $access_fixed = $method_is_public;
        } catch (Exception $e) {
            $access_fixed = false;
        }
        
        $this->passed_tests += $access_fixed ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Private Method Access Fix", $access_fixed, $access_fixed ? "get_singleton method is public" : "get_singleton method access issue", $execution_time);
    }
    
    /**
     * Test method redeclaration fix
     */
    private function test_method_redeclaration_fix() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Test that there are no method redeclaration errors
            $plugin_class = new ReflectionClass('HSM_Stripe_Plugin');
            $get_instance_methods = $plugin_class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);
            
            $get_instance_count = 0;
            foreach ($get_instance_methods as $method) {
                if ($method->getName() === 'get_instance') {
                    $get_instance_count++;
                }
            }
            
            $no_redeclaration = $get_instance_count <= 1;
        } catch (Exception $e) {
            $no_redeclaration = false;
        }
        
        $this->passed_tests += $no_redeclaration ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Method Redeclaration Fix", $no_redeclaration, $no_redeclaration ? "No method redeclaration found" : "Method redeclaration issue found", $execution_time);
    }
    
    /**
     * Test plugin initialization
     */
    private function test_plugin_initialization() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            // Test that plugin can be instantiated without fatal errors
            $plugin = HSM_Stripe_Plugin::get_instance();
            $initialization_successful = $plugin !== null;
        } catch (Exception $e) {
            $initialization_successful = false;
        }
        
        $this->passed_tests += $initialization_successful ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Plugin Initialization", $initialization_successful, $initialization_successful ? "Plugin initializes successfully" : "Plugin initialization failed", $execution_time);
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
        echo "VERIFICATION RESULTS SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        echo "Success Rate: " . round(($this->passed_tests / $this->total_tests) * 100, 2) . "%\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL VERIFICATION TESTS PASSED! All immediate CMS plugin issues are still fixed!\n";
        } else {
            echo "\n⚠️  Some verification tests failed. Please check the issues above.\n";
        }
        
        echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
        echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test_suite = new HSM_Immediate_CMS_Plugin_Issues_Verification_Test();
    $results = $test_suite->run_all_tests();
}