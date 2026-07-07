<?php
/**
 * Test script for HSM GraphQL Health Monitor dependency detection fix
 * 
 * This script tests the improved plugin detection logic to ensure
 * that WooCommerce and WPGraphQL plugins are correctly detected.
 * 
 * @package HSM
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress if not already loaded
    if (!function_exists('wp_load_plugins')) {
        require_once('../../../wp-load.php');
    }
}

// Include the health monitor class
require_once('../includes/api/class-graphql-health-monitor.php');

// Mock error handler for testing
class Mock_Error_Handler {
    public function log_error($message, $exception = null) {
        echo "ERROR: $message\n";
        if ($exception) {
            echo "Exception: " . $exception->getMessage() . "\n";
        }
    }
}

echo "🧪 Testing HSM GraphQL Health Monitor Dependency Detection Fix\n";
echo "================================================================\n\n";

// Create health monitor instance
$error_handler = new Mock_Error_Handler();
$health_monitor = new HSM_GraphQL_Health_Monitor($error_handler);

echo "1. Testing Basic Dependency Detection\n";
echo "-------------------------------------\n";

// Test basic dependency detection
$dependencies = $health_monitor->check_dependencies();
echo "WooCommerce Active: " . ($dependencies['woocommerce'] ? '✅ YES' : '❌ NO') . "\n";
echo "WPGraphQL Active: " . ($dependencies['wpgraphql'] ? '✅ YES' : '❌ NO') . "\n";
echo "WordPress REST API: " . ($dependencies['wordpress_rest_api'] ? '✅ YES' : '❌ NO') . "\n";
echo "cURL Available: " . ($dependencies['curl'] ? '✅ YES' : '❌ NO') . "\n";
echo "JSON Available: " . ($dependencies['json'] ? '✅ YES' : '❌ NO') . "\n";
echo "mbstring Available: " . ($dependencies['mbstring'] ? '✅ YES' : '❌ NO') . "\n";
echo "OpenSSL Available: " . ($dependencies['openssl'] ? '✅ YES' : '❌ NO') . "\n\n";

echo "2. Testing Detailed Dependency Detection\n";
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

echo "3. Testing Quick Health Status\n";
echo "------------------------------\n";

// Test quick health status
$quick_status = $health_monitor->get_quick_health_status();
echo "Overall Status: " . $quick_status['status'] . "\n";
echo "WooCommerce Active: " . ($quick_status['woocommerce_graphql_active'] ? '✅ YES' : '❌ NO') . "\n";
echo "WPGraphQL Active: " . ($quick_status['wpgraphql_active'] ? '✅ YES' : '❌ NO') . "\n";
echo "Dependencies OK: " . ($quick_status['dependencies_ok'] ? '✅ YES' : '❌ NO') . "\n";
echo "WooCommerce Version: " . ($quick_status['woocommerce_version'] ? $quick_status['woocommerce_version'] : 'Unknown') . "\n";
echo "WPGraphQL Version: " . ($quick_status['wpgraphql_version'] ? $quick_status['wpgraphql_version'] : 'Unknown') . "\n\n";

echo "4. Testing Comprehensive Health Status\n";
echo "--------------------------------------\n";

// Test comprehensive health status
$comprehensive_status = $health_monitor->get_comprehensive_health_status();
echo "Overall Status: " . $comprehensive_status['overall_status'] . "\n";
echo "WooCommerce Active: " . ($comprehensive_status['woocommerce_graphql_active'] ? '✅ YES' : '❌ NO') . "\n";
echo "WPGraphQL Active: " . ($comprehensive_status['wpgraphql_active'] ? '✅ YES' : '❌ NO') . "\n";
echo "Dependencies OK: " . ($comprehensive_status['dependencies_ok'] ? '✅ YES' : '❌ NO') . "\n";
echo "WooCommerce Version: " . ($comprehensive_status['woocommerce_version'] ? $comprehensive_status['woocommerce_version'] : 'Unknown') . "\n";
echo "WPGraphQL Version: " . ($comprehensive_status['wpgraphql_version'] ? $comprehensive_status['wpgraphql_version'] : 'Unknown') . "\n\n";

echo "5. Testing Plugin Detection Methods\n";
echo "-----------------------------------\n";

// Test individual plugin detection methods
echo "Testing WooCommerce Detection:\n";
echo "  is_plugin_active(): " . (is_plugin_active('woocommerce/woocommerce.php') ? '✅ YES' : '❌ NO') . "\n";
echo "  class_exists('WooCommerce'): " . (class_exists('WooCommerce') ? '✅ YES' : '❌ NO') . "\n";
echo "  function_exists('woocommerce_init'): " . (function_exists('woocommerce_init') ? '✅ YES' : '❌ NO') . "\n";
echo "  file_exists(): " . (file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php') ? '✅ YES' : '❌ NO') . "\n\n";

echo "Testing WPGraphQL Detection:\n";
echo "  is_plugin_active(): " . (is_plugin_active('wp-graphql/wp-graphql.php') ? '✅ YES' : '❌ NO') . "\n";
echo "  class_exists('WPGraphQL'): " . (class_exists('WPGraphQL') ? '✅ YES' : '❌ NO') . "\n";
echo "  function_exists('graphql_init'): " . (function_exists('graphql_init') ? '✅ YES' : '❌ NO') . "\n";
echo "  file_exists(): " . (file_exists(WP_PLUGIN_DIR . '/wp-graphql/wp-graphql.php') ? '✅ YES' : '❌ NO') . "\n\n";

echo "6. Testing Active Plugins List\n";
echo "------------------------------\n";

// Test active plugins list
$active_plugins = get_option('active_plugins', array());
echo "Total Active Plugins: " . count($active_plugins) . "\n";
echo "WooCommerce in active_plugins: " . (in_array('woocommerce/woocommerce.php', $active_plugins) ? '✅ YES' : '❌ NO') . "\n";
echo "WPGraphQL in active_plugins: " . (in_array('wp-graphql/wp-graphql.php', $active_plugins) ? '✅ YES' : '❌ NO') . "\n\n";

echo "7. Summary\n";
echo "==========\n";

$woo_detected = $dependencies['woocommerce'];
$wpgraphql_detected = $dependencies['wpgraphql'];

echo "WooCommerce Detection: " . ($woo_detected ? '✅ WORKING' : '❌ FAILED') . "\n";
echo "WPGraphQL Detection: " . ($wpgraphql_detected ? '✅ WORKING' : '❌ FAILED') . "\n";
echo "Overall Fix Status: " . (($woo_detected && $wpgraphql_detected) ? '✅ SUCCESS' : '❌ NEEDS MORE WORK') . "\n\n";

if ($woo_detected && $wpgraphql_detected) {
    echo "🎉 SUCCESS: Dependency detection fix is working correctly!\n";
    echo "The HSM GraphQL proxy dashboard should now show correct dependency status.\n";
} else {
    echo "⚠️  WARNING: Some dependencies are still not being detected correctly.\n";
    echo "Please check the plugin installation and activation status.\n";
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
?>