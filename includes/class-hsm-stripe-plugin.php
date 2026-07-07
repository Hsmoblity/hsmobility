<?php
/**
 * HSM Stripe Plugin Main Class
 * 
 * Main plugin class that coordinates all functionality
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Stripe_Plugin {
    
    /**
     * Stripe secret key
     * 
     * @var string
     */
    private $stripe_secret_key;
    
    /**
     * Error handler instance
     * 
     * @var HSM_Error_Handler
     */
    private $error_handler;
    
    /**
     * API manager instance
     * 
     * @var HSM_API_Manager
     */
    private $api_manager;
    
    /**
     * Admin menu instance
     * 
     * @var HSM_Admin_Menu
     */
    private $admin_menu;
    
    /**
     * Admin pages instance
     * 
     * @var HSM_Admin_Pages
     */
    private $admin_pages;
    
    /**
     * Settings manager instance
     * 
     * @var HSM_Settings_Manager
     */
    private $settings_manager;
    
    /**
     * Logger instance
     * 
     * @var HSM_Logger
     */
    private $logger;
    
    /**
     * REST API is now handled by HSM_REST_API singleton in Main.php
     * HSM_REST_Manager has been removed as part of API consolidation
     */
    
    /**
     * Plugin version
     * 
     * @var string
     */
    private $version = '2.0.1';
    
    /**
     * Constructor with memory optimization
     */
    public function __construct() {
        try {
            // Initialize memory manager first
            $memory_manager = HSM_Memory_Manager::get_instance();
            
            // Check if required classes exist before proceeding
            if (!class_exists('HSM_Error_Handler')) {
                error_log('HSM Stripe Plugin: HSM_Error_Handler class not found');
                $this->show_admin_notice('HSM Stripe Plugin: Required class HSM_Error_Handler not found. Please check plugin installation.');
                return;
            }
            
            // Initialize error handler first
            $this->error_handler = new HSM_Error_Handler();
            
            // Initialize settings manager with lazy loading
            $this->settings_manager = $memory_manager->get_singleton('HSM_Settings_Manager');
            
            // Initialize logger with lazy loading
            $this->logger = $memory_manager->get_singleton('HSM_Logger');
            
            // REST API is now handled by HSM_REST_API singleton in Main.php
            // HSM_REST_Manager has been consolidated into HSM_REST_API for cleaner architecture
            // No need to instantiate HSM_REST_Manager
            
            // Run migration for existing installations
            $this->migrate_options();
            
        // Register WordPress hooks
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Initialize REST API early to ensure hsm/v1 namespace is registered
        add_action('rest_api_init', [$this, 'ensure_endpoints_registered'], 5);
        
        // Also initialize REST API on init as a fallback
        add_action('init', [$this, 'init_rest_api_fallback'], 20);
            
            // WooCommerce email template override hooks (priority 20 to avoid conflicts)
            add_action('woocommerce_email_order_details', [$this, 'override_customer_completed_order_template'], 20, 4);
            add_filter('woocommerce_locate_template', [$this, 'locate_custom_email_template'], 20, 3);
            
        } catch (Exception $e) {
            error_log('HSM Stripe Plugin: Constructor error: ' . $e->getMessage());
            $this->show_admin_notice('HSM Stripe Plugin: Initialization error: ' . $e->getMessage());
            // Don't return here, let the plugin continue with limited functionality
        }
    }
    
    /**
     * Initialize plugin components
     * 
     * @return void
     */
    public function init() {
        // Get Stripe secret key from settings manager
        $stripe_settings = $this->settings_manager->get_stripe_settings();
        $this->stripe_secret_key = $stripe_settings->get_secret_key();
        
        // Stripe SDK removed - payment processing handled by Next.js application
        if ($this->stripe_secret_key) {
            $this->logger->info('Stripe secret key configured for Next.js integration');
        }
        
        // Initialize API manager
        $this->api_manager = new HSM_API_Manager($this->error_handler, $this->stripe_secret_key);
        
        // Register API endpoints if we're in the right context
        if (did_action('rest_api_init') || doing_action('rest_api_init')) {
            $this->api_manager->register_api_endpoints();
        }
        
        // GraphQL components are already initialized in constructor
        // No need to initialize again here
        
        // Initialize admin components
        $this->admin_pages = new HSM_Admin_Pages($this->error_handler);
        $this->admin_menu = new HSM_Admin_Menu($this->admin_pages);
        
        // Initialize HPOS compatibility
        HSM_HPOS_Compatibility::get_instance();
        
        // Initialize login customization
        $general_settings = $this->settings_manager->get_general_settings();
        new HSM_Login_Customization($general_settings);
        
        $this->logger->info('HSM Login Customization initialized');
        
        $this->logger->info('HSM Stripe Plugin initialized successfully');
    }
    
    /**
     * Ensure REST API endpoints are registered
     * 
     * @return void
     */
    public function ensure_endpoints_registered() {
        // This method ensures that all REST API endpoints are properly registered
        // It's called on rest_api_init with high priority to ensure registration
        $this->logger->info('Ensuring REST API endpoints are registered');
        
        // Initialize HSM REST API to register hsm/v1 namespace
        if (class_exists('HSM_REST_API')) {
            try {
                HSM_REST_API::get_instance();
                $this->logger->info('HSM REST API initialized successfully');
            } catch (Exception $e) {
                $this->logger->error('Failed to initialize HSM REST API: ' . $e->getMessage());
            }
        } else {
            $this->logger->error('HSM_REST_API class not found');
        }
        
        // Also register HSM Stripe API endpoints if API manager is available
        if ($this->api_manager) {
            try {
                $this->api_manager->register_api_endpoints();
                $this->logger->info('HSM Stripe API endpoints registered successfully');
            } catch (Exception $e) {
                $this->logger->error('Failed to register HSM Stripe API endpoints: ' . $e->getMessage());
            }
        } else {
            // Initialize API manager if not already done
            $this->init_api_manager_if_needed();
        }
    }
    
    /**
     * Initialize REST API fallback
     * 
     * @return void
     */
    public function init_rest_api_fallback() {
        // Fallback initialization for REST API if it wasn't properly initialized on rest_api_init
        $this->ensure_endpoints_registered();
    }
    
    /**
     * Initialize API manager if needed
     * 
     * @return void
     */
    private function init_api_manager_if_needed() {
        try {
            // Get Stripe secret key from settings manager
            $stripe_settings = $this->settings_manager->get_stripe_settings();
            $stripe_secret_key = $stripe_settings->get_secret_key();
            
            // Initialize API manager
            $this->api_manager = new HSM_API_Manager($this->error_handler, $stripe_secret_key);
            $this->api_manager->register_api_endpoints();
            
            $this->logger->info('API manager initialized and endpoints registered successfully');
        } catch (Exception $e) {
            $this->logger->error('Failed to initialize API manager: ' . $e->getMessage());
        }
    }
    
    /**
     * Get settings manager
     * 
     * @return HSM_Settings_Manager
     */
    public function get_settings_manager() {
        return $this->settings_manager;
    }
    
    /**
     * Get logger
     * 
     * @return HSM_Logger
     */
    public function get_logger() {
        return $this->logger;
    }
    
    /**
     * Add admin menu
     * 
     * @return void
     */
    public function add_admin_menu() {
        if ($this->admin_menu) {
            $this->admin_menu->add_admin_menu();
        }
    }
    
    /**
     * Enqueue scripts
     * 
     * @return void
     */
    public function enqueue_scripts() {
        // Stripe JS removed - payment processing handled by Next.js application
        // No client-side Stripe integration needed in WordPress
    }
    
    /**
     * Initialize GraphQL components
     * 
     * @return void
     */
    private function initialize_graphql_components() {
        try {
            // Enhanced dependency validation
            $dependencies = $this->validate_graphql_dependencies();
            
            if (!$dependencies['wpgraphql']['active']) {
                $this->logger->warning('WPGraphQL plugin is not active - GraphQL proxy functionality disabled');
                $this->show_dependency_error('WPGraphQL', $dependencies['wpgraphql']);
                
                // Register fallback endpoints that provide helpful error messages
                $this->register_fallback_endpoints();
                return;
            }
            
            if (!$dependencies['woocommerce']['active']) {
                $this->logger->warning('WooCommerce plugin is not active - GraphQL proxy functionality disabled');
                $this->show_dependency_error('WooCommerce', $dependencies['woocommerce']);
                
                // Register fallback endpoints that provide helpful error messages
                $this->register_fallback_endpoints();
                return;
            }
            
            // Initialize GraphQL manager
            $graphql_manager = new HSM_GraphQL_Manager($this->error_handler);
            
            // Initialize GraphQL proxy API
            $graphql_proxy_api = new HSM_GraphQL_Proxy_API($this->error_handler);
            
            // Register GraphQL proxy endpoints - ensure this happens on rest_api_init
            add_action('rest_api_init', [$graphql_proxy_api, 'register_endpoints'], 10);
            
            $this->logger->info('GraphQL components initialized successfully');
            
        } catch (Exception $e) {
            $this->error_handler->log_error('GraphQL components initialization failed', $e);
            $this->logger->error('GraphQL components initialization failed: ' . $e->getMessage());
            
            // Register fallback endpoints even if initialization fails
            $this->register_fallback_endpoints();
        }
    }

    /**
     * Validate GraphQL dependencies
     * 
     * @return array Dependency status
     */
    private function validate_graphql_dependencies() {
        return [
            'wpgraphql' => [
                'active' => class_exists('WPGraphQL'),
                'version' => defined('WPGRAPHQL_VERSION') ? WPGRAPHQL_VERSION : 'Not installed',
                'required' => '1.0.0',
                'installed' => function_exists('graphql_init') || class_exists('WPGraphQL')
            ],
            'woocommerce' => [
                'active' => class_exists('WooCommerce'),
                'version' => defined('WC_VERSION') ? WC_VERSION : 'Not installed',
                'required' => '7.1.0',
                'installed' => function_exists('woocommerce_init') || class_exists('WooCommerce')
            ]
        ];
    }

    /**
     * Show dependency error notice
     * 
     * @param string $plugin_name Plugin name
     * @param array $plugin_info Plugin information
     * @return void
     */
    private function show_dependency_error($plugin_name, $plugin_info) {
        add_action('admin_notices', function() use ($plugin_name, $plugin_info) {
            $status = $plugin_info['installed'] ? 'inactive' : 'not installed';
            $version_info = $plugin_info['version'] !== 'Not installed' ? " (Version: {$plugin_info['version']})" : '';
            
            echo '<div class="notice notice-error"><p>';
            echo '<strong>HSM Plugin:</strong> ' . $plugin_name . ' is ' . $status . $version_info . '. ';
            echo 'Please install and activate ' . $plugin_name . ' version ' . $plugin_info['required'] . ' or higher to use GraphQL proxy functionality.';
            echo '</p></div>';
        });
    }

    /**
     * Register fallback endpoints that provide helpful error messages
     * 
     * @return void
     */
    private function register_fallback_endpoints() {
        add_action('rest_api_init', function() {
            // Main GraphQL proxy endpoint fallback
            register_rest_route('hsm-graphql/v1', '/proxy', [
                'methods' => 'POST',
                'callback' => [$this, 'handle_fallback_proxy'],
                'permission_callback' => '__return_true',
                'args' => [
                    'query' => [
                        'required' => true,
                        'type' => 'string',
                        'description' => 'GraphQL query string'
                    ],
                    'variables' => [
                        'required' => false,
                        'type' => 'object',
                        'description' => 'GraphQL variables'
                    ]
                ]
            ]);
            
            // Status endpoint fallback
            register_rest_route('hsm-graphql/v1', '/status', [
                'methods' => 'GET',
                'callback' => [$this, 'handle_fallback_status'],
                'permission_callback' => '__return_true'
            ]);
        });
    }

    /**
     * Handle fallback proxy request
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response REST response
     */
    public function handle_fallback_proxy($request) {
        $dependencies = $this->validate_graphql_dependencies();
        
        $error_message = 'GraphQL proxy is not available due to missing dependencies. ';
        $missing_deps = [];
        
        if (!$dependencies['wpgraphql']['active']) {
            $missing_deps[] = 'WPGraphQL plugin (required)';
        }
        if (!$dependencies['woocommerce']['active']) {
            $missing_deps[] = 'WooCommerce plugin (required)';
        }
        
        $error_message .= 'Missing: ' . implode(', ', $missing_deps);
        
        return new WP_REST_Response([
            'errors' => [
                [
                    'message' => $error_message,
                    'extensions' => [
                        'code' => 'DEPENDENCY_MISSING',
                        'dependencies' => $dependencies
                    ]
                ]
            ]
        ], 503);
    }

    /**
     * Handle fallback status request
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response REST response
     */
    public function handle_fallback_status($request) {
        $dependencies = $this->validate_graphql_dependencies();
        
        return new WP_REST_Response([
            'status' => 'unhealthy',
            'message' => 'GraphQL proxy is not available due to missing dependencies',
            'dependencies' => $dependencies,
            'timestamp' => current_time('mysql'),
            'plugin_version' => HSM_PLUGIN_VERSION
        ], 503);
    }
    
    /**
     * Migrate options from old naming convention to new naming convention
     * Rule R41: Error Management - Handle migration gracefully
     * 
     * @return void
     */
    private function migrate_options() {
        // Check if migration has already been completed
        if (get_option('hsm_stripe_migration_completed', false)) {
            return;
        }
        
        // Migrate webhook secret option
        $old_webhook_secret = get_option('hsm_webhook_secret');
        if ($old_webhook_secret && !get_option('hsm_stripe_webhook_secret')) {
            update_option('hsm_stripe_webhook_secret', $old_webhook_secret);
            delete_option('hsm_webhook_secret');
        }
        
        // Migrate debug mode option
        $old_debug_mode = get_option('hsm_debug_mode');
        if ($old_debug_mode !== false && get_option('hsm_stripe_debug_mode') === false) {
            update_option('hsm_stripe_debug_mode', $old_debug_mode);
            delete_option('hsm_debug_mode');
        }
        
        // Mark migration as completed
        update_option('hsm_stripe_migration_completed', true);
        
        // Log migration completion
        error_log('HSM Stripe Plugin: Options migration completed successfully');
    }
    
    /**
     * Override customer completed order template
     * 
     * @param WC_Order $order Order object
     * @param bool $sent_to_admin Whether email is sent to admin
     * @param bool $plain_text Whether email is plain text
     * @param WC_Email $email Email object
     * @return void
     */
    public function override_customer_completed_order_template($order, $sent_to_admin, $plain_text, $email) {
        // Check if this is a configurator order or if we want to use custom template
        if ($order->get_meta('_hsm_configurator_order') || true) { // Always use custom template for now
            $custom_template = plugin_dir_path(dirname(__FILE__)) . 'templates/emails/customer-completed-order.php';
            
            // Check if custom template exists
            if (file_exists($custom_template)) {
                // Load the custom template
                include $custom_template;
                return;
            }
        }
        
        // Fall back to default WooCommerce template
        wc_get_template('emails/customer-completed-order.php', array(
            'order' => $order,
            'sent_to_admin' => $sent_to_admin,
            'plain_text' => $plain_text,
            'email' => $email
        ));
    }
    
    /**
     * Locate custom email template
     *
     * @param string $template Template path
     * @param string $template_name Template name
     * @param string $template_path Template path
     * @return string Modified template path
     */
    public function locate_custom_email_template($template, $template_name, $template_path) {
        // Check if this is a customer completed order email
        if ($template_name === 'emails/customer-completed-order.php') {
            $custom_template = plugin_dir_path(dirname(__FILE__)) . 'templates/emails/customer-completed-order.php';
            
            // Check if custom template exists
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Show admin notice
     * 
     * @param string $message Notice message
     * @param string $type Notice type (error, warning, success, info)
     * @return void
     */
    private function show_admin_notice($message, $type = 'error') {
        add_action('admin_notices', function() use ($message, $type) {
            echo '<div class="notice notice-' . esc_attr($type) . '"><p>';
            echo esc_html($message);
            echo '</p></div>';
        });
    }
}