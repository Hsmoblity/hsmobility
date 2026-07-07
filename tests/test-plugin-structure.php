<?php
/**
 * Plugin Structure Test Script
 * 
 * This script tests the plugin structure to ensure it won't cause WordPress crashes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSM_Plugin_Structure_Test {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_test_menu']);
    }
    
    public function add_test_menu() {
        add_management_page(
            'HSM Plugin Test',
            'HSM Plugin Test',
            'manage_options',
            'hsm-plugin-test',
            [$this, 'test_page']
        );
    }
    
    public function test_page() {
        echo '<div class="wrap">';
        echo '<h1>HSM Plugin Structure Test</h1>';
        
        // Test 1: Check if main class exists
        echo '<h2>Test 1: Class Existence</h2>';
        if (class_exists('HSM_Stripe_Simple')) {
            echo '<p style="color: green;">✓ HSM_Stripe_Simple class exists</p>';
        } else {
            echo '<p style="color: red;">✗ HSM_Stripe_Simple class not found</p>';
        }
        
        if (class_exists('HSM_Error_Handler')) {
            echo '<p style="color: green;">✓ HSM_Error_Handler class exists</p>';
        } else {
            echo '<p style="color: red;">✗ HSM_Error_Handler class not found</p>';
        }
        
        // Test 2: Check WooCommerce dependency
        echo '<h2>Test 2: WooCommerce Dependency</h2>';
        if (class_exists('WooCommerce')) {
            echo '<p style="color: green;">✓ WooCommerce is active</p>';
        } else {
            echo '<p style="color: orange;">⚠ WooCommerce is not active (plugin will show admin notice)</p>';
        }
        
        // Test 3: Check Stripe SDK
        echo '<h2>Test 3: Stripe SDK</h2>';
        if (class_exists('\Stripe\Stripe')) {
            echo '<p style="color: green;">✓ Stripe SDK is available</p>';
        } else {
            echo '<p style="color: orange;">⚠ Stripe SDK not found (plugin will log error)</p>';
        }
        
        // Test 4: Check plugin options
        echo '<h2>Test 4: Plugin Options</h2>';
        $options = [
            'hsm_stripe_secret_key',
            'hsm_stripe_publishable_key',
            'hsm_stripe_webhook_secret',
            'hsm_stripe_environment'
        ];
        
        foreach ($options as $option) {
            $value = get_option($option);
            if ($value !== false) {
                echo '<p style="color: green;">✓ Option ' . $option . ' exists</p>';
            } else {
                echo '<p style="color: red;">✗ Option ' . $option . ' not found</p>';
            }
        }
        
        // Test 5: Check database table
        echo '<h2>Test 5: Database Table</h2>';
        global $wpdb;
        $table_name = $wpdb->prefix . 'hsm_stripe_logs';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
        
        if ($table_exists) {
            echo '<p style="color: green;">✓ Database table ' . $table_name . ' exists</p>';
        } else {
            echo '<p style="color: red;">✗ Database table ' . $table_name . ' not found</p>';
        }
        
        // Test 6: Check for fatal errors
        echo '<h2>Test 6: Error Check</h2>';
        $error_log = ini_get('error_log');
        if ($error_log && file_exists($error_log)) {
            $recent_errors = $this->get_recent_errors($error_log);
            if (empty($recent_errors)) {
                echo '<p style="color: green;">✓ No recent errors found</p>';
            } else {
                echo '<p style="color: orange;">⚠ Recent errors found:</p>';
                echo '<pre>' . htmlspecialchars($recent_errors) . '</pre>';
            }
        } else {
            echo '<p style="color: orange;">⚠ Error log not accessible</p>';
        }
        
        echo '</div>';
    }
    
    private function get_recent_errors($error_log) {
        $lines = file($error_log);
        $recent_lines = array_slice($lines, -20); // Last 20 lines
        $hsm_errors = array_filter($recent_lines, function($line) {
            return strpos($line, 'HSM Plugin') !== false;
        });
        return implode('', $hsm_errors);
    }
}

// Initialize test only in admin
if (is_admin()) {
    new HSM_Plugin_Structure_Test();
}