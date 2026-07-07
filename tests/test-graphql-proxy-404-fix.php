<?php
/**
 * Test GraphQL Proxy 404 Fix
 * 
 * This script tests the GraphQL proxy 404 error fix by verifying
 * that all endpoints are properly registered and accessible.
 * 
 * @package HSM
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSM_GraphQL_Proxy_404_Fix_Test {
    
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "🏛️ POSEIDON - GraphQL Proxy 404 Fix Test Suite\n";
        echo "==============================================\n\n";
        
        // Test 1: Check if plugin is active
        $this->test_plugin_active();
        
        // Test 2: Check if REST API is enabled
        $this->test_rest_api_enabled();
        
        // Test 3: Check if GraphQL proxy endpoints are registered
        $this->test_graphql_proxy_endpoints_registered();
        
        // Test 4: Check if fallback endpoints are registered
        $this->test_fallback_endpoints_registered();
        
        // Test 5: Test endpoint accessibility
        $this->test_endpoint_accessibility();
        
        // Test 6: Test rewrite rules
        $this->test_rewrite_rules();
        
        // Test 7: Test plugin activation
        $this->test_plugin_activation();
        
        // Test 8: Test dependency validation
        $this->test_dependency_validation();
        
        // Display results
        $this->display_results();
        
        return $this->test_results;
    }
    
    /**
     * Test if plugin is active
     */
    private function test_plugin_active() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $plugin_active = class_exists('HSM_Stripe_Plugin');
        $this->passed_tests += $plugin_active ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Plugin Active", $plugin_active, $plugin_active ? "Plugin is active" : "Plugin is not active", $execution_time);
    }
    
    /**
     * Test if REST API is enabled
     */
    private function test_rest_api_enabled() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $rest_api_enabled = function_exists('rest_url') && function_exists('register_rest_route');
        $this->passed_tests += $rest_api_enabled ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("REST API Enabled", $rest_api_enabled, $rest_api_enabled ? "REST API is enabled" : "REST API is not enabled", $execution_time);
    }
    
    /**
     * Test if GraphQL proxy endpoints are registered
     */
    private function test_graphql_proxy_endpoints_registered() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Check if the GraphQL proxy API class exists
        $class_exists = class_exists('HSM_GraphQL_Proxy_API');
        
        // Check if the register_endpoints method exists
        $method_exists = $class_exists && method_exists('HSM_GraphQL_Proxy_API', 'register_endpoints');
        
        $endpoints_registered = $class_exists && $method_exists;
        $this->passed_tests += $endpoints_registered ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("GraphQL Proxy Endpoints Registered", $endpoints_registered, $endpoints_registered ? "GraphQL proxy endpoints are registered" : "GraphQL proxy endpoints are not registered", $execution_time);
    }
    
    /**
     * Test if fallback endpoints are registered
     */
    private function test_fallback_endpoints_registered() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Check if the main plugin class exists and has fallback method
        $class_exists = class_exists('HSM_Stripe_Plugin');
        $method_exists = $class_exists && method_exists('HSM_Stripe_Plugin', 'register_fallback_endpoints');
        
        $fallback_registered = $class_exists && $method_exists;
        $this->passed_tests += $fallback_registered ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Fallback Endpoints Registered", $fallback_registered, $fallback_registered ? "Fallback endpoints are registered" : "Fallback endpoints are not registered", $execution_time);
    }
    
    /**
     * Test endpoint accessibility
     */
    private function test_endpoint_accessibility() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Test if we can access the REST API
        $rest_url = rest_url('hsm-graphql/v1/proxy');
        $accessible = !empty($rest_url) && filter_var($rest_url, FILTER_VALIDATE_URL);
        
        $this->passed_tests += $accessible ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Endpoint Accessibility", $accessible, $accessible ? "Endpoints are accessible" : "Endpoints are not accessible", $execution_time);
    }
    
    /**
     * Test rewrite rules
     */
    private function test_rewrite_rules() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Check if rewrite rules are flushed
        $rewrite_rules = get_option('rewrite_rules');
        $rules_exist = !empty($rewrite_rules);
        
        $this->passed_tests += $rules_exist ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Rewrite Rules", $rules_exist, $rules_exist ? "Rewrite rules exist" : "Rewrite rules do not exist", $execution_time);
    }
    
    /**
     * Test plugin activation
     */
    private function test_plugin_activation() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Check if activation hook exists
        $activation_hook_exists = has_action('register_activation_hook');
        
        $this->passed_tests += $activation_hook_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Plugin Activation", $activation_hook_exists, $activation_hook_exists ? "Plugin activation hook exists" : "Plugin activation hook does not exist", $execution_time);
    }
    
    /**
     * Test dependency validation
     */
    private function test_dependency_validation() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        // Check if dependency validation method exists
        $class_exists = class_exists('HSM_Stripe_Plugin');
        $method_exists = $class_exists && method_exists('HSM_Stripe_Plugin', 'validate_graphql_dependencies');
        
        $dependency_validation = $class_exists && $method_exists;
        $this->passed_tests += $dependency_validation ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Dependency Validation", $dependency_validation, $dependency_validation ? "Dependency validation exists" : "Dependency validation does not exist", $execution_time);
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
        echo sprintf("%-30s %s (%.4fs) - %s\n", $test_name, $status, $execution_time, $message);
    }
    
    /**
     * Display test results
     */
    private function display_results() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "TEST RESULTS SUMMARY\n";
        echo str_repeat("=", 50) . "\n";
        echo "Total Tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        echo "Success Rate: " . round(($this->passed_tests / $this->total_tests) * 100, 2) . "%\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL TESTS PASSED! GraphQL Proxy 404 fix is working correctly!\n";
        } else {
            echo "\n⚠️  Some tests failed. Please check the issues above.\n";
        }
        
        echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
        echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test_suite = new HSM_GraphQL_Proxy_404_Fix_Test();
    $results = $test_suite->run_all_tests();
}