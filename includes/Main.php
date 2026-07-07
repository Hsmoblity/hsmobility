<?php
/**
 * Main plugin class
 *
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main HSM plugin class
 */
class HSM_Main {
    
    /**
     * Plugin instance
     *
     * @var HSM_Main
     */
    private static $instance = null;
    
    /**
     * Plugin version
     *
     * @var string
     */
    public $version = HSM_VERSION;
    
    /**
     * Get plugin instance
     *
     * @return HSM_Main
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('rest_api_init', array($this, 'init_rest_api'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // WooCommerce hooks
        add_action('woocommerce_init', array($this, 'init_woocommerce'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . HSM_PLUGIN_BASENAME, array($this, 'add_action_links'));
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize error handler
        HSM_Error_Handler::get_instance();
        
        // Initialize logger
        HSM_Logger::get_instance();
        
        // Initialize cache
        HSM_Cache::get_instance();
        
        // Initialize security
        HSM_Security::get_instance();
        
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize Stripe if configured
        $this->init_stripe();
        
        // Initialize tax calculator
        HSM_Tax_Calculator::get_instance();
        
        // Initialize payment processor
        HSM_Payment_Processor::get_instance();
        
        // Initialize order manager
        HSM_Order_Manager::get_instance();
    }
    
    /**
     * Initialize WooCommerce integration
     */
    public function init_woocommerce() {
        // Initialize WooCommerce specific features
        HSM_WooCommerce_Integration::get_instance();
    }
    
    /**
     * Initialize REST API
     */
    public function init_rest_api() {
        // Register REST API routes
        HSM_REST_API::get_instance();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('HSM Settings', 'hsm'),
            __('HSM', 'hsm'),
            'manage_options',
            'hsm-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        // Redirect to the main HSM plugin admin page
        wp_redirect(admin_url('admin.php?page=hsm-plugin'));
        exit;
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings
        HSM_Admin_Settings::get_instance()->register_settings();
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        if ('settings_page_hsm-settings' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'hsm-admin',
            HSM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_enqueue_style(
            'hsm-admin',
            HSM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version
        );
        
        wp_localize_script('hsm-admin', 'hsmAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hsm_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this item?', 'hsm'),
                'saving' => __('Saving...', 'hsm'),
                'saved' => __('Settings saved!', 'hsm'),
                'error' => __('An error occurred. Please try again.', 'hsm')
            )
        ));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        // Only enqueue on payment page
        if (is_page('payment')) {
            wp_enqueue_script(
                'stripe-js',
                'https://js.stripe.com/v3/',
                array(),
                null,
                true
            );
            
            wp_enqueue_script(
                'hsm-frontend',
                HSM_PLUGIN_URL . 'assets/js/frontend.js',
                array('stripe-js'),
                $this->version,
                true
            );
            
            wp_localize_script('hsm-frontend', 'hsmFrontend', array(
                'apiUrl' => rest_url('hsm/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'strings' => array(
                    'loading' => __('Loading...', 'hsm'),
                    'error' => __('An error occurred. Please try again.', 'hsm')
                )
            ));
        }
    }
    
    /**
     * Initialize Stripe
     */
    private function init_stripe() {
        $stripe_secret_key = HSM_Options::get('stripe_secret_key');
        
        if ($stripe_secret_key && class_exists('\Stripe\Stripe')) {
            \Stripe\Stripe::setApiKey($stripe_secret_key);
        }
    }
    
    /**
     * Add plugin action links
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=hsm-settings') . '">' . __('Settings', 'hsm') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}