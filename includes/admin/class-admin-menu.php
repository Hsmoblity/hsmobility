<?php
/**
 * HSM Admin Menu Class
 * 
 * Handles WordPress admin menu creation and management
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Admin_Menu {
    
    /**
     * Admin pages instance
     * 
     * @var HSM_Admin_Pages
     */
    private $admin_pages;
    
    /**
     * Constructor
     * 
     * @param HSM_Admin_Pages $admin_pages Admin pages instance
     */
    public function __construct($admin_pages) {
        $this->admin_pages = $admin_pages;
    }
    
    /**
     * Permission callback function for admin menu
     * 
     * @return bool True if user has proper permissions
     */
    public function admin_permission_callback() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Check if user is in admin area
        if (!is_admin()) {
            return false;
        }
        
        // Check multiple capability levels
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Check for admin user specifically
        if (current_user_can('administrator')) {
            return true;
        }
        
        // Check for super admin in multisite
        if (is_multisite() && is_super_admin()) {
            return true;
        }
        
        return false;
    }

    /**
     * Add admin menu pages
     * 
     * @return void
     */
    public function add_admin_menu() {
        // Create main HSM Plugin menu
        add_menu_page(
            'HSM Plugin Dashboard',
            'HSM Plugin',
            'manage_options',
            'hsm-plugin',
            [$this->admin_pages, 'admin_page'],
            'dashicons-admin-tools',
            30
        );
        
        // Dashboard submenu (same as main page)
        add_submenu_page(
            'hsm-plugin',
            'HSM Plugin Dashboard',
            'Dashboard',
            'manage_options',
            'hsm-plugin',
            [$this->admin_pages, 'admin_page']
        );
        
        // Stripe Webhook submenu
        add_submenu_page(
            'hsm-plugin',
            'HSM Stripe Webhook',
            'Stripe Webhook',
            'manage_options',
            'hsm-stripe-webhook',
            [$this->admin_pages, 'webhook_check_page']
        );
        
        // API Check submenu
        add_submenu_page(
            'hsm-plugin',
            'HSM Stripe API Check',
            'API Check',
            'manage_options',
            'hsm-stripe-api-check',
            [$this->admin_pages, 'api_check_page']
        );
        
        // Settings submenu (for future configuration)
        add_submenu_page(
            'hsm-plugin',
            'HSM Plugin Settings',
            'Settings',
            'manage_options',
            'hsm-plugin-settings',
            [$this->admin_pages, 'settings_page']
        );
        
        // Health Check submenu
        add_submenu_page(
            'hsm-plugin',
            'HSM Plugin Health Check',
            'Health Check',
            'manage_options',
            'hsm-plugin-health-check',
            [$this->admin_pages, 'health_check_page']
        );
        
        // GraphQL Proxy submenu
        add_submenu_page(
            'hsm-plugin',
            'HSM GraphQL Proxy',
            'GraphQL Proxy',
            'manage_options',
            'hsm-graphql-proxy',
            [$this->admin_pages, 'graphql_proxy_page']
        );
    }
}