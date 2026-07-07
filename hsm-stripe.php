<?php
/**
 * Plugin Name: hsm-stripe
 * Description: Simplified Stripe integration for HSMobility payment flow (KISS + DRY)
 * Version: 2.0.1
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 7.1.0
 * WC tested up to: 8.5.0
 * 
 * Follows WordPress Plugin Development Handbook with modular structure
 * Aligned with frontend payment store patterns
 * 
 * WooCommerce HPOS compatibility: Yes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HSM_PLUGIN_VERSION', '2.0.1');
define('HSM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HSM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Declare HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Include core classes needed for login customization
require_once plugin_dir_path(__FILE__) . 'includes/class-base-singleton.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/class-options.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/class-settings-base.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/class-general-settings.php';

// Include login customization (works without WooCommerce)
require_once plugin_dir_path(__FILE__) . 'includes/class-login-customization.php';

// Check if WooCommerce is active
$woocommerce_active = in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));

if (!$woocommerce_active) {
    // Load minimal functionality for login customization even without WooCommerce
    add_action('plugins_loaded', function() {
        $general_settings = new HSM_General_Settings();
        new HSM_Login_Customization($general_settings);
    });
    return;
}

// Ensure memory manager class is available, then initialize for lazy loading
require_once plugin_dir_path(__FILE__) . 'includes/class-memory-manager.php';
$memory_manager = HSM_Memory_Manager::get_instance();

// Include required classes with lazy loading optimization
require_once plugin_dir_path(__FILE__) . 'includes/class-autoloader.php';
require_once plugin_dir_path(__FILE__) . 'includes/error/class-error-handler.php';

// Core API classes (loaded immediately for critical functionality)
require_once plugin_dir_path(__FILE__) . 'includes/api/class-tax-calculator-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/class-payment-intent-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/class-order-creation-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/class-api-manager.php';
// Submit consultation REST endpoint (accepts forwarded requests from Next.js proxy)
require_once plugin_dir_path(__FILE__) . 'includes/api/class-submit-consultation-api.php';

// Lazy load heavy classes through memory manager
// These will be loaded on-demand to reduce memory usage
require_once plugin_dir_path(__FILE__) . 'includes/api/class-graphql-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/class-graphql-proxy-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/class-graphql-healthcheck-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/class-graphql-health-monitor.php';

// REST API - Now using HSM_REST_API singleton (loaded in Main.php)
// HSM_REST_Manager and HSM_REST_Base have been consolidated into HSM_REST_API

// Logging classes (optimized)
require_once plugin_dir_path(__FILE__) . 'includes/logging/class-logger-base.php';
require_once plugin_dir_path(__FILE__) . 'includes/logging/class-file-logger.php';
require_once plugin_dir_path(__FILE__) . 'includes/logging/class-database-logger.php';
require_once plugin_dir_path(__FILE__) . 'includes/logging/class-logger.php';

// Settings classes
require_once plugin_dir_path(__FILE__) . 'includes/settings/class-settings-base.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/class-stripe-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/class-general-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/class-settings-manager.php';

// Admin classes (loaded for immediate use)
require_once plugin_dir_path(__FILE__) . 'includes/admin/class-admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/class-admin-pages.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/class-graphql-testing-page.php';

// WooCommerce compatibility
require_once plugin_dir_path(__FILE__) . 'includes/woocommerce/class-hpos-compatibility.php';

// Main plugin class
require_once plugin_dir_path(__FILE__) . 'includes/class-hsm-stripe-plugin.php';

// Register autoloader
HSM_Autoloader::register();

// Initialize plugin only after WordPress is fully loaded
add_action('plugins_loaded', function() {
    // Only initialize if WooCommerce is active
    if (class_exists('WooCommerce')) {
        new HSM_Stripe_Plugin();
    } else {
        // Show admin notice if WooCommerce is not active
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>HSM Stripe Plugin:</strong> WooCommerce is required but not active. ';
            echo 'Please install and activate WooCommerce to use this plugin.';
            echo '</p></div>';
        });
    }
});

// Activation hook with comprehensive error handling
register_activation_hook(__FILE__, function() {
    try {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('HSM Plugin requires WooCommerce to be installed and active.');
        }
        
        // Check if required files exist
        $required_files = [
            'includes/class-autoloader.php',
            'includes/error/class-error-handler.php',
            'includes/class-hsm-stripe-plugin.php'
        ];
        
        foreach ($required_files as $file) {
            $file_path = plugin_dir_path(__FILE__) . $file;
            if (!file_exists($file_path)) {
                error_log("HSM Plugin: Required file missing: {$file}");
                deactivate_plugins(plugin_basename(__FILE__));
                wp_die("HSM Plugin: Required file missing: {$file}");
            }
        }
        
        // Create necessary options with error handling
        $options = [
            'hsm_stripe_secret_key' => '',
            'hsm_stripe_publishable_key' => '',
            'hsm_stripe_webhook_secret' => '',
            'hsm_stripe_environment' => 'test',
            'hsm_stripe_debug_mode' => false,
            'hsm_stripe_migration_completed' => false
        ];
        
        foreach ($options as $option_name => $default_value) {
            if (!add_option($option_name, $default_value)) {
                error_log("HSM Plugin: Failed to create option {$option_name}");
            }
        }
        
        // Create simple log table for debugging with error handling
        global $wpdb;
        $table_name = $wpdb->prefix . 'hsm_stripe_logs';
        
        // Check if table already exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                action varchar(50) NOT NULL,
                data text,
                PRIMARY KEY (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            try {
                $result = dbDelta($sql);
                if (empty($result)) {
                    error_log('HSM Plugin: Failed to create logs table');
                } else {
                    error_log('HSM Plugin: Successfully created logs table');
                }
            } catch (Exception $e) {
                error_log('HSM Plugin: Database error during table creation: ' . $e->getMessage());
            }
        }
        
        // Flush rewrite rules to ensure REST API endpoints are registered
        flush_rewrite_rules();
        
        // Log successful activation
        error_log('HSM Plugin: Successfully activated');
        
    } catch (Exception $e) {
        error_log('HSM Plugin: Activation failed: ' . $e->getMessage());
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('HSM Plugin activation failed: ' . $e->getMessage());
    }
});

// Deactivation hook with optimized database operations
register_deactivation_hook(__FILE__, function() {
    try {
        // Get database optimizer instance
        $db_optimizer = HSM_Database_Optimizer::get_instance();
        $log_manager = HSM_Log_Manager::get_instance();
        
        // Log deactivation start
        $log_manager->log('Plugin deactivation started', 'info');
        
        // Clear cached data using optimized operations
        $patterns = array(
            '_transient_hsm_tax_rates_%',
            '_transient_timeout_hsm_tax_rates_%',
            '_transient_hsm_%',
            '_transient_timeout_hsm_%'
        );
        
        $total_cleared = 0;
        foreach ($patterns as $pattern) {
            $cleared = $db_optimizer->optimize_transient_cleanup($pattern);
            if ($cleared !== false) {
                $total_cleared += $cleared;
            }
        }
        
        // Clear memory and caches using memory manager
        if (class_exists('HSM_Memory_Manager')) {
            $memory_manager = HSM_Memory_Manager::get_instance();
            $memory_manager->clear_all_instances();
            $memory_manager->force_garbage_collection();
        }
        
        // Log successful deactivation
        $log_manager->log("Plugin deactivated successfully. Cleared {$total_cleared} transients", 'info');
        
    } catch (Exception $e) {
        // Use log manager if available, fallback to error_log
        if (class_exists('HSM_Log_Manager')) {
            $log_manager = HSM_Log_Manager::get_instance();
            $log_manager->log('Deactivation error: ' . $e->getMessage(), 'error');
        } else {
            error_log('HSM Plugin: Deactivation error: ' . $e->getMessage());
        }
    }
});

// Stripe SDK removed - using Next.js integration instead
// Payment processing is now handled by the Next.js application