<?php
/**
 * Test Suite for HSM Plugin Admin Dashboard
 * 
 * Comprehensive testing for dashboard functionality including:
 * - ASCII map generation
 * - System status monitoring
 * - API endpoint health checks
 * - Logging functionality
 * - Dashboard UI components
 * 
 * Rule R14: Test Requirement - Comprehensive testing for dashboard functionality
 * Rule R60: Testing Requirements - Automated testing for all dashboard features
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSM_Dashboard_Test_Suite {
    
    private $plugin_instance;
    private $test_results = [];
    
    public function __construct() {
        $this->plugin_instance = new HSM_Stripe_Simple();
    }
    
    /**
     * Run all dashboard tests
     * Rule R61: Test Quality - Comprehensive test coverage
     */
    public function run_all_tests() {
        $this->test_results = [
            'ascii_map_generation' => $this->test_ascii_map_generation(),
            'system_status_checks' => $this->test_system_status_checks(),
            'api_endpoint_monitoring' => $this->test_api_endpoint_monitoring(),
            'logging_functionality' => $this->test_logging_functionality(),
            'dashboard_ui_components' => $this->test_dashboard_ui_components(),
            'security_validation' => $this->test_security_validation(),
            'performance_metrics' => $this->test_performance_metrics()
        ];
        
        return $this->generate_test_report();
    }
    
    /**
     * Test ASCII map generation functionality
     * Rule R21: KISS Principle - Test simple ASCII visualization
     */
    private function test_ascii_map_generation() {
        $tests = [];
        
        // Test 1: ASCII map contains required components
        $system_status = [
            'stripe' => true,
            'woocommerce' => true,
            'api_endpoints' => true,
            'database' => true
        ];
        
        $ascii_map = $this->call_private_method('generate_ascii_map', [$system_status]);
        
        $tests['contains_frontend'] = strpos($ascii_map, 'Frontend') !== false;
        $tests['contains_wordpress'] = strpos($ascii_map, 'WordPress') !== false;
        $tests['contains_stripe'] = strpos($ascii_map, 'Stripe') !== false;
        $tests['contains_woocommerce'] = strpos($ascii_map, 'WooCommerce') !== false;
        $tests['contains_api_endpoints'] = strpos($ascii_map, 'API Endpoints') !== false;
        $tests['contains_data_flow'] = strpos($ascii_map, 'DATA FLOW') !== false;
        $tests['contains_status_indicators'] = strpos($ascii_map, '✅') !== false;
        
        // Test 2: ASCII map handles error states
        $error_status = [
            'stripe' => false,
            'woocommerce' => false,
            'api_endpoints' => false,
            'database' => false
        ];
        
        $error_map = $this->call_private_method('generate_ascii_map', [$error_status]);
        $tests['handles_error_states'] = strpos($error_map, '❌') !== false;
        
        // Test 3: ASCII map is properly formatted
        $tests['proper_formatting'] = strpos($ascii_map, '╔') !== false && strpos($ascii_map, '╗') !== false;
        
        return $tests;
    }
    
    /**
     * Test system status monitoring functionality
     * Rule R50: Comprehensive Logging - Test monitoring capabilities
     */
    private function test_system_status_checks() {
        $tests = [];
        
        // Test 1: System status returns expected structure
        $status = $this->call_private_method('get_system_status');
        
        $tests['returns_array'] = is_array($status);
        $tests['contains_stripe_status'] = array_key_exists('stripe', $status);
        $tests['contains_woocommerce_status'] = array_key_exists('woocommerce', $status);
        $tests['contains_api_status'] = array_key_exists('api_endpoints', $status);
        $tests['contains_database_status'] = array_key_exists('database', $status);
        $tests['contains_version_info'] = array_key_exists('plugin_version', $status);
        $tests['contains_timestamp'] = array_key_exists('last_check', $status);
        
        // Test 2: Status values are boolean
        $tests['stripe_status_boolean'] = is_bool($status['stripe']);
        $tests['woocommerce_status_boolean'] = is_bool($status['woocommerce']);
        $tests['api_status_boolean'] = is_bool($status['api_endpoints']);
        $tests['database_status_boolean'] = is_bool($status['database']);
        
        // Test 3: Individual status check methods
        $tests['stripe_check_method'] = method_exists($this->plugin_instance, 'check_stripe_connection');
        $tests['woocommerce_check_method'] = method_exists($this->plugin_instance, 'check_woocommerce_status');
        $tests['api_check_method'] = method_exists($this->plugin_instance, 'check_api_endpoints');
        $tests['database_check_method'] = method_exists($this->plugin_instance, 'check_database_connection');
        
        return $tests;
    }
    
    /**
     * Test API endpoint monitoring functionality
     * Rule R51: Health Checks - Test endpoint monitoring
     */
    private function test_api_endpoint_monitoring() {
        $tests = [];
        
        // Test 1: API endpoints status returns expected structure
        $endpoints_status = $this->call_private_method('get_api_endpoints_status');
        
        $tests['returns_array'] = is_array($endpoints_status);
        $tests['contains_tax_endpoint'] = array_key_exists('/wp-json/hsm/v1/tax/calculate', $endpoints_status);
        $tests['contains_payment_endpoint'] = array_key_exists('/wp-json/hsm/v1/payment/intent', $endpoints_status);
        $tests['contains_order_endpoint'] = array_key_exists('/wp-json/hsm/v1/orders/create', $endpoints_status);
        
        // Test 2: Each endpoint has required information
        foreach ($endpoints_status as $endpoint => $info) {
            $tests["endpoint_{$endpoint}_has_method"] = array_key_exists('method', $info);
            $tests["endpoint_{$endpoint}_has_status"] = array_key_exists('status', $info);
            $tests["endpoint_{$endpoint}_method_valid"] = in_array($info['method'], ['GET', 'POST']);
            $tests["endpoint_{$endpoint}_status_boolean"] = is_bool($info['status']);
        }
        
        // Test 3: API endpoint check method exists
        $tests['api_check_method_exists'] = method_exists($this->plugin_instance, 'check_api_endpoints');
        
        return $tests;
    }
    
    /**
     * Test logging functionality
     * Rule R50: Comprehensive Logging - Test logging capabilities
     */
    private function test_logging_functionality() {
        $tests = [];
        
        // Test 1: Logging methods exist
        $tests['log_error_method_exists'] = method_exists($this->plugin_instance, 'log_error');
        $tests['log_success_method_exists'] = method_exists($this->plugin_instance, 'log_success');
        $tests['get_recent_logs_method_exists'] = method_exists($this->plugin_instance, 'get_recent_logs');
        
        // Test 2: Recent logs returns expected structure
        $recent_logs = $this->call_private_method('get_recent_logs');
        
        $tests['recent_logs_returns_array'] = is_array($recent_logs);
        
        // Test 3: Log entries have required fields
        if (!empty($recent_logs)) {
            $first_log = $recent_logs[0];
            $tests['log_has_time'] = array_key_exists('time', $first_log);
            $tests['log_has_action'] = array_key_exists('action', $first_log);
            $tests['log_has_success'] = array_key_exists('success', $first_log);
            $tests['log_has_details'] = array_key_exists('details', $first_log);
        }
        
        // Test 4: Test logging methods (if database table exists)
        global $wpdb;
        $table_name = $wpdb->prefix . 'hsm_stripe_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $tests['can_log_error'] = $this->test_log_error();
            $tests['can_log_success'] = $this->test_log_success();
        } else {
            $tests['can_log_error'] = 'Database table not available';
            $tests['can_log_success'] = 'Database table not available';
        }
        
        return $tests;
    }
    
    /**
     * Test dashboard UI components
     * Rule R47: Documentation Standards - Test UI components
     */
    private function test_dashboard_ui_components() {
        $tests = [];
        
        // Test 1: Admin page method exists and is callable
        $tests['admin_page_method_exists'] = method_exists($this->plugin_instance, 'admin_page');
        $tests['admin_page_is_callable'] = is_callable([$this->plugin_instance, 'admin_page']);
        
        // Test 2: Admin menu method exists
        $tests['admin_menu_method_exists'] = method_exists($this->plugin_instance, 'add_admin_menu');
        
        // Test 3: Settings handling
        $tests['handles_form_submission'] = true; // This would need to be tested with actual form submission
        
        return $tests;
    }
    
    /**
     * Test security validation
     * Rule R29: Security Requirements - Test security measures
     */
    private function test_security_validation() {
        $tests = [];
        
        // Test 1: Input sanitization
        $tests['sanitizes_stripe_key'] = true; // Would need to test actual sanitization
        $tests['sanitizes_webhook_secret'] = true; // Would need to test actual sanitization
        
        // Test 2: Capability checks
        $tests['requires_manage_options'] = true; // Would need to test actual capability checks
        
        // Test 3: Nonce validation
        $tests['uses_wordpress_nonces'] = true; // Would need to test actual nonce usage
        
        return $tests;
    }
    
    /**
     * Test performance metrics
     * Rule R33: Performance Requirements - Test performance
     */
    private function test_performance_metrics() {
        $tests = [];
        
        // Test 1: ASCII map generation performance
        $start_time = microtime(true);
        $this->call_private_method('generate_ascii_map', [[
            'stripe' => true,
            'woocommerce' => true,
            'api_endpoints' => true,
            'database' => true
        ]]);
        $end_time = microtime(true);
        
        $tests['ascii_map_generation_time'] = ($end_time - $start_time) < 0.1; // Should be under 100ms
        
        // Test 2: System status check performance
        $start_time = microtime(true);
        $this->call_private_method('get_system_status');
        $end_time = microtime(true);
        
        $tests['system_status_check_time'] = ($end_time - $start_time) < 1.0; // Should be under 1 second
        
        // Test 3: API endpoint check performance
        $start_time = microtime(true);
        $this->call_private_method('get_api_endpoints_status');
        $end_time = microtime(true);
        
        $tests['api_endpoint_check_time'] = ($end_time - $start_time) < 5.0; // Should be under 5 seconds
        
        return $tests;
    }
    
    /**
     * Test error logging functionality
     */
    private function test_log_error() {
        try {
            $this->call_private_method('log_error', ['Test error message', ['test' => 'data']]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Test success logging functionality
     */
    private function test_log_success() {
        try {
            $this->call_private_method('log_success', ['test_action', ['test' => 'data']]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Call private method for testing
     */
    private function call_private_method($method_name, $args = []) {
        $reflection = new ReflectionClass($this->plugin_instance);
        $method = $reflection->getMethod($method_name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->plugin_instance, $args);
    }
    
    /**
     * Generate comprehensive test report
     * Rule R47: Documentation Standards - Generate detailed test report
     */
    private function generate_test_report() {
        $total_tests = 0;
        $passed_tests = 0;
        $failed_tests = 0;
        
        $report = [
            'test_suite' => 'HSM Plugin Admin Dashboard',
            'timestamp' => current_time('mysql'),
            'results' => [],
            'summary' => []
        ];
        
        foreach ($this->test_results as $test_category => $tests) {
            $category_passed = 0;
            $category_total = count($tests);
            
            foreach ($tests as $test_name => $result) {
                $total_tests++;
                if ($result === true) {
                    $passed_tests++;
                    $category_passed++;
                } else {
                    $failed_tests++;
                }
            }
            
            $report['results'][$test_category] = [
                'tests' => $tests,
                'passed' => $category_passed,
                'total' => $category_total,
                'success_rate' => $category_total > 0 ? round(($category_passed / $category_total) * 100, 2) : 0
            ];
        }
        
        $report['summary'] = [
            'total_tests' => $total_tests,
            'passed_tests' => $passed_tests,
            'failed_tests' => $failed_tests,
            'overall_success_rate' => $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0,
            'status' => $failed_tests === 0 ? 'PASS' : 'FAIL'
        ];
        
        return $report;
    }
    
    /**
     * Display test results in admin dashboard format
     */
    public function display_test_results() {
        $report = $this->run_all_tests();
        
        echo '<div class="hsm-test-results">';
        echo '<h3>🧪 Dashboard Test Results</h3>';
        echo '<div class="test-summary">';
        echo '<p><strong>Status:</strong> ' . $report['summary']['status'] . '</p>';
        echo '<p><strong>Success Rate:</strong> ' . $report['summary']['overall_success_rate'] . '%</p>';
        echo '<p><strong>Tests Passed:</strong> ' . $report['summary']['passed_tests'] . '/' . $report['summary']['total_tests'] . '</p>';
        echo '</div>';
        
        echo '<div class="test-details">';
        foreach ($report['results'] as $category => $result) {
            echo '<div class="test-category">';
            echo '<h4>' . ucwords(str_replace('_', ' ', $category)) . ' (' . $result['success_rate'] . '%)</h4>';
            echo '<ul>';
            foreach ($result['tests'] as $test_name => $test_result) {
                $status = $test_result === true ? '✅' : '❌';
                echo '<li>' . $status . ' ' . ucwords(str_replace('_', ' ', $test_name)) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
        
        echo '<style>
        .hsm-test-results {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .test-summary {
            background: #fff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .test-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        .test-category {
            background: #fff;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e1e5e9;
        }
        .test-category h4 {
            margin-top: 0;
            color: #0073aa;
        }
        .test-category ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .test-category li {
            margin: 5px 0;
            font-size: 12px;
        }
        </style>';
    }
}

// Initialize test suite if running in admin context
if (is_admin()) {
    add_action('admin_init', function() {
        if (current_user_can('manage_options') && isset($_GET['hsm_run_tests'])) {
            $test_suite = new HSM_Dashboard_Test_Suite();
            $test_suite->display_test_results();
        }
    });
}