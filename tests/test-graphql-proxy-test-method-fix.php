<?php
/**
 * Test GraphQL Proxy Test Method Fix
 * 
 * This script tests the GraphQL proxy test method fix by verifying
 * that the test methods are working correctly.
 * 
 * @package HSM
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSM_GraphQL_Proxy_Test_Method_Fix_Test {
    
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "🏛️ POSEIDON - GraphQL Proxy Test Method Fix Test Suite\n";
        echo "====================================================\n\n";
        
        // Test 1: Check if GraphQL Manager class exists
        $this->test_graphql_manager_class_exists();
        
        // Test 2: Check if GraphQL Manager can be instantiated
        $this->test_graphql_manager_instantiation();
        
        // Test 3: Check if execute_query method exists
        $this->test_execute_query_method_exists();
        
        // Test 4: Test GraphQL endpoint configuration
        $this->test_graphql_endpoint_configuration();
        
        // Test 5: Test GraphQL client initialization
        $this->test_graphql_client_initialization();
        
        // Test 6: Test simple GraphQL query execution
        $this->test_simple_graphql_query_execution();
        
        // Test 7: Test GraphQL testing page class
        $this->test_graphql_testing_page_class();
        
        // Test 8: Test AJAX test connection method
        $this->test_ajax_test_connection_method();
        
        // Display results
        $this->display_results();
        
        return $this->test_results;
    }
    
    /**
     * Test if GraphQL Manager class exists
     */
    private function test_graphql_manager_class_exists() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $class_exists = class_exists('HSM_GraphQL_Manager');
        $this->passed_tests += $class_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("GraphQL Manager Class Exists", $class_exists, $class_exists ? "HSM_GraphQL_Manager class exists" : "HSM_GraphQL_Manager class does not exist", $execution_time);
    }
    
    /**
     * Test if GraphQL Manager can be instantiated
     */
    private function test_graphql_manager_instantiation() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            $error_handler = new HSM_Error_Handler();
            $graphql_manager = new HSM_GraphQL_Manager($error_handler);
            $instantiated = true;
        } catch (Exception $e) {
            $instantiated = false;
        }
        
        $this->passed_tests += $instantiated ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("GraphQL Manager Instantiation", $instantiated, $instantiated ? "HSM_GraphQL_Manager can be instantiated" : "HSM_GraphQL_Manager instantiation failed", $execution_time);
    }
    
    /**
     * Test if execute_query method exists
     */
    private function test_execute_query_method_exists() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $method_exists = method_exists('HSM_GraphQL_Manager', 'execute_query');
        $this->passed_tests += $method_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Execute Query Method Exists", $method_exists, $method_exists ? "execute_query method exists" : "execute_query method does not exist", $execution_time);
    }
    
    /**
     * Test GraphQL endpoint configuration
     */
    private function test_graphql_endpoint_configuration() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            $error_handler = new HSM_Error_Handler();
            $graphql_manager = new HSM_GraphQL_Manager($error_handler);
            
            // Use reflection to check the endpoint
            $reflection = new ReflectionClass($graphql_manager);
            $property = $reflection->getProperty('graphql_endpoint');
            $property->setAccessible(true);
            $endpoint = $property->getValue($graphql_manager);
            
            $endpoint_correct = strpos($endpoint, 'hsm-graphql/v1/proxy') !== false;
        } catch (Exception $e) {
            $endpoint_correct = false;
        }
        
        $this->passed_tests += $endpoint_correct ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("GraphQL Endpoint Configuration", $endpoint_correct, $endpoint_correct ? "GraphQL endpoint configured correctly" : "GraphQL endpoint not configured correctly", $execution_time);
    }
    
    /**
     * Test GraphQL client initialization
     */
    private function test_graphql_client_initialization() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            $error_handler = new HSM_Error_Handler();
            $graphql_manager = new HSM_GraphQL_Manager($error_handler);
            
            // Use reflection to check the client
            $reflection = new ReflectionClass($graphql_manager);
            $property = $reflection->getProperty('graphql_client');
            $property->setAccessible(true);
            $client = $property->getValue($graphql_manager);
            
            $client_initialized = $client !== null;
        } catch (Exception $e) {
            $client_initialized = false;
        }
        
        $this->passed_tests += $client_initialized ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("GraphQL Client Initialization", $client_initialized, $client_initialized ? "GraphQL client initialized successfully" : "GraphQL client initialization failed", $execution_time);
    }
    
    /**
     * Test simple GraphQL query execution
     */
    private function test_simple_graphql_query_execution() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        try {
            $error_handler = new HSM_Error_Handler();
            $graphql_manager = new HSM_GraphQL_Manager($error_handler);
            
            // Test with a simple introspection query
            $test_query = 'query { __schema { types { name } } }';
            $result = $graphql_manager->execute_query($test_query);
            
            $query_executed = is_array($result);
        } catch (Exception $e) {
            $query_executed = false;
        }
        
        $this->passed_tests += $query_executed ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("Simple GraphQL Query Execution", $query_executed, $query_executed ? "GraphQL query executed successfully" : "GraphQL query execution failed", $execution_time);
    }
    
    /**
     * Test GraphQL testing page class
     */
    private function test_graphql_testing_page_class() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $class_exists = class_exists('HSM_GraphQL_Testing_Page');
        $this->passed_tests += $class_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("GraphQL Testing Page Class", $class_exists, $class_exists ? "HSM_GraphQL_Testing_Page class exists" : "HSM_GraphQL_Testing_Page class does not exist", $execution_time);
    }
    
    /**
     * Test AJAX test connection method
     */
    private function test_ajax_test_connection_method() {
        $this->total_tests++;
        $start_time = microtime(true);
        
        $method_exists = method_exists('HSM_GraphQL_Testing_Page', 'ajax_test_graphql_connection');
        $this->passed_tests += $method_exists ? 1 : 0;
        
        $execution_time = microtime(true) - $start_time;
        $this->record_test_result("AJAX Test Connection Method", $method_exists, $method_exists ? "ajax_test_graphql_connection method exists" : "ajax_test_graphql_connection method does not exist", $execution_time);
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
            echo "\n🎉 ALL TESTS PASSED! GraphQL Proxy Test Method fix is working correctly!\n";
        } else {
            echo "\n⚠️  Some tests failed. Please check the issues above.\n";
        }
        
        echo "\n🏛️ POSEIDON - Divine Fullstack Engineer\n";
        echo "Commanding the Seas of Code with Divine Precision! 🌊⚡\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test_suite = new HSM_GraphQL_Proxy_Test_Method_Fix_Test();
    $results = $test_suite->run_all_tests();
}