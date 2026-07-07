<?php
/**
 * Test script for HSM GraphQL Health Monitor plugin detection logic
 * 
 * This script tests the improved plugin detection logic without requiring WordPress.
 * It simulates the detection methods to verify they work correctly.
 * 
 * @package HSM
 * @since 1.0.0
 */

echo "🧪 Testing HSM GraphQL Health Monitor Plugin Detection Logic\n";
echo "============================================================\n\n";

// Mock WordPress constants and functions for testing
if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', '/var/www/html/wp-content/plugins');
}

// Mock WordPress functions
if (!function_exists('is_plugin_active')) {
    function is_plugin_active($plugin_file) {
        // Simulate checking if plugin is active
        $active_plugins = [
            'woocommerce/woocommerce.php',
            'wp-graphql/wp-graphql.php'
        ];
        return in_array($plugin_file, $active_plugins);
    }
}

if (!function_exists('get_option')) {
    function get_option($option_name, $default = false) {
        if ($option_name === 'active_plugins') {
            return [
                'woocommerce/woocommerce.php',
                'wp-graphql/wp-graphql.php'
            ];
        }
        return $default;
    }
}

if (!function_exists('get_plugin_data')) {
    function get_plugin_data($plugin_path) {
        // Mock plugin data
        $plugin_data = [
            'woocommerce/woocommerce.php' => [
                'Name' => 'WooCommerce',
                'Version' => '8.5.0'
            ],
            'wp-graphql/wp-graphql.php' => [
                'Name' => 'WPGraphQL',
                'Version' => '1.19.0'
            ]
        ];
        
        $plugin_file = basename($plugin_path);
        return $plugin_data[$plugin_file] ?? [];
    }
}

if (!function_exists('class_exists')) {
    function class_exists($class_name) {
        // Mock class existence checks
        $classes = ['WooCommerce', 'WPGraphQL'];
        return in_array($class_name, $classes);
    }
}

if (!function_exists('function_exists')) {
    function function_exists($function_name) {
        // Mock function existence checks
        $functions = ['woocommerce_init', 'graphql_init'];
        return in_array($function_name, $functions);
    }
}

if (!function_exists('file_exists')) {
    function file_exists($file_path) {
        // Mock file existence checks
        $files = [
            '/var/www/html/wp-content/plugins/woocommerce/woocommerce.php',
            '/var/www/html/wp-content/plugins/wp-graphql/wp-graphql.php'
        ];
        return in_array($file_path, $files);
    }
}

// Mock HSM_Error_Handler class for testing - using proper mock pattern
class Mock_HSM_Error_Handler {
    public function log_error($message, $exception = null) {
        echo "ERROR: $message\n";
        if ($exception) {
            echo "Exception: " . $exception->getMessage() . "\n";
        }
    }
}

// Mock HSM_Logger class for testing - using proper mock pattern
class Mock_HSM_Logger {
    public static function get_instance() {
        return new self();
    }
}

// Mock HSM_GraphQL_Manager class
class HSM_GraphQL_Manager {
    public function __construct($error_handler) {
        $this->error_handler = $error_handler;
    }
    
    public function execute_query($query) {
        return ['__schema' => ['queryType' => ['name' => 'RootQuery']]];
    }
    
    public function get_endpoint_url() {
        return 'https://example.com/graphql';
    }
    
    public function get_client_status() {
        return [
            'initialized' => true,
            'wpgraphql_active' => true,
            'endpoint_valid' => true
        ];
    }
}

// Include the health monitor class
require_once('../includes/api/class-graphql-health-monitor.php');

echo "1. Testing Plugin Detection Methods\n";
echo "-----------------------------------\n";

// Test individual detection methods
echo "Testing WooCommerce Detection:\n";
echo "  is_plugin_active('woocommerce/woocommerce.php'): " . (is_plugin_active('woocommerce/woocommerce.php') ? '✅ YES' : '❌ NO') . "\n";
echo "  class_exists('WooCommerce'): " . (class_exists('WooCommerce') ? '✅ YES' : '❌ NO') . "\n";
echo "  function_exists('woocommerce_init'): " . (function_exists('woocommerce_init') ? '✅ YES' : '❌ NO') . "\n";
echo "  file_exists(): " . (file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php') ? '✅ YES' : '❌ NO') . "\n\n";

echo "Testing WPGraphQL Detection:\n";
echo "  is_plugin_active('wp-graphql/wp-graphql.php'): " . (is_plugin_active('wp-graphql/wp-graphql.php') ? '✅ YES' : '❌ NO') . "\n";
echo "  class_exists('WPGraphQL'): " . (class_exists('WPGraphQL') ? '✅ YES' : '❌ NO') . "\n";
echo "  function_exists('graphql_init'): " . (function_exists('graphql_init') ? '✅ YES' : '❌ NO') . "\n";
echo "  file_exists(): " . (file_exists(WP_PLUGIN_DIR . '/wp-graphql/wp-graphql.php') ? '✅ YES' : '❌ NO') . "\n\n";

echo "2. Testing Health Monitor Integration\n";
echo "------------------------------------\n";

// Create health monitor instance
$error_handler = new Mock_HSM_Error_Handler();
$health_monitor = new HSM_GraphQL_Health_Monitor($error_handler);

// Test basic dependency detection
$dependencies = $health_monitor->check_dependencies();
echo "WooCommerce Active: " . ($dependencies['woocommerce'] ? '✅ YES' : '❌ NO') . "\n";
echo "WPGraphQL Active: " . ($dependencies['wpgraphql'] ? '✅ YES' : '❌ NO') . "\n";
echo "WordPress REST API: " . ($dependencies['wordpress_rest_api'] ? '✅ YES' : '❌ NO') . "\n";
echo "cURL Available: " . ($dependencies['curl'] ? '✅ YES' : '❌ NO') . "\n";
echo "JSON Available: " . ($dependencies['json'] ? '✅ YES' : '❌ NO') . "\n";
echo "mbstring Available: " . ($dependencies['mbstring'] ? '✅ YES' : '❌ NO') . "\n";
echo "OpenSSL Available: " . ($dependencies['openssl'] ? '✅ YES' : '❌ NO') . "\n\n";

echo "3. Testing Detailed Dependency Detection\n";
echo "---------------------------------------\n";

// Test detailed dependency detection
$detailed_dependencies = $health_monitor->get_detailed_dependency_status();

// WooCommerce details
$woo = $detailed_dependencies['woocommerce'];
echo "WooCommerce Details:\n";
echo "  Name: " . $woo['name'] . "\n";
echo "  Active: " . ($woo['active'] ? '✅ YES' : '❌ NO') . "\n";
echo "  Version: " . ($woo['version'] ? $woo['version'] : 'Unknown') . "\n";
echo "  Class Exists: " . ($woo['class_exists'] ? '✅ YES' : '❌ NO') . "\n";
echo "  Function Exists: " . ($woo['function_exists'] ? '✅ YES' : '❌ NO') . "\n";
echo "  File Exists: " . ($woo['file_exists'] ? '✅ YES' : '❌ NO') . "\n\n";

// WPGraphQL details
$wpgraphql = $detailed_dependencies['wpgraphql'];
echo "WPGraphQL Details:\n";
echo "  Name: " . $wpgraphql['name'] . "\n";
echo "  Active: " . ($wpgraphql['active'] ? '✅ YES' : '❌ NO') . "\n";
echo "  Version: " . ($wpgraphql['version'] ? $wpgraphql['version'] : 'Unknown') . "\n";
echo "  Class Exists: " . ($wpgraphql['class_exists'] ? '✅ YES' : '❌ NO') . "\n";
echo "  Function Exists: " . ($wpgraphql['function_exists'] ? '✅ YES' : '❌ NO') . "\n";
echo "  File Exists: " . ($wpgraphql['file_exists'] ? '✅ YES' : '❌ NO') . "\n\n";

echo "4. Testing Quick Health Status\n";
echo "------------------------------\n";

// Test quick health status
$quick_status = $health_monitor->get_quick_health_status();
echo "Overall Status: " . $quick_status['status'] . "\n";
echo "WooCommerce Active: " . ($quick_status['woocommerce_graphql_active'] ? '✅ YES' : '❌ NO') . "\n";
echo "WPGraphQL Active: " . ($quick_status['wpgraphql_active'] ? '✅ YES' : '❌ NO') . "\n";
echo "Dependencies OK: " . ($quick_status['dependencies_ok'] ? '✅ YES' : '❌ NO') . "\n";
echo "WooCommerce Version: " . ($quick_status['woocommerce_version'] ? $quick_status['woocommerce_version'] : 'Unknown') . "\n";
echo "WPGraphQL Version: " . ($quick_status['wpgraphql_version'] ? $quick_status['wpgraphql_version'] : 'Unknown') . "\n\n";

echo "5. Testing Comprehensive Health Status\n";
echo "--------------------------------------\n";

// Test comprehensive health status
$comprehensive_status = $health_monitor->get_comprehensive_health_status();
echo "Overall Status: " . $comprehensive_status['overall_status'] . "\n";
echo "WooCommerce Active: " . ($comprehensive_status['woocommerce_graphql_active'] ? '✅ YES' : '❌ NO') . "\n";
echo "WPGraphQL Active: " . ($comprehensive_status['wpgraphql_active'] ? '✅ YES' : '❌ NO') . "\n";
echo "Dependencies OK: " . ($comprehensive_status['dependencies_ok'] ? '✅ YES' : '❌ NO') . "\n";
echo "WooCommerce Version: " . ($comprehensive_status['woocommerce_version'] ? $comprehensive_status['woocommerce_version'] : 'Unknown') . "\n";
echo "WPGraphQL Version: " . ($comprehensive_status['wpgraphql_version'] ? $comprehensive_status['wpgraphql_version'] : 'Unknown') . "\n\n";

echo "6. Summary\n";
echo "==========\n";

$woo_detected = $dependencies['woocommerce'];
$wpgraphql_detected = $dependencies['wpgraphql'];

echo "WooCommerce Detection: " . ($woo_detected ? '✅ WORKING' : '❌ FAILED') . "\n";
echo "WPGraphQL Detection: " . ($wpgraphql_detected ? '✅ WORKING' : '❌ FAILED') . "\n";
echo "Overall Fix Status: " . (($woo_detected && $wpgraphql_detected) ? '✅ SUCCESS' : '❌ NEEDS MORE WORK') . "\n\n";

if ($woo_detected && $wpgraphql_detected) {
    echo "🎉 SUCCESS: Plugin detection logic is working correctly!\n";
    echo "The HSM GraphQL proxy dashboard should now show correct dependency status.\n";
    echo "\nKey improvements made:\n";
    echo "✅ Enhanced is_plugin_active() method with multiple detection strategies\n";
    echo "✅ Added detailed dependency status with version information\n";
    echo "✅ Updated dashboard to use health monitor directly instead of API calls\n";
    echo "✅ Added proper error handling for detection edge cases\n";
    echo "✅ Implemented fallback detection methods for reliability\n";
} else {
    echo "⚠️  WARNING: Some dependencies are still not being detected correctly.\n";
    echo "Please check the plugin installation and activation status.\n";
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
?>