<?php
/**
 * HSM REST API Class
 * 
 * Provides comprehensive REST API management for the HSM plugin.
 * Handles API endpoints, authentication, rate limiting, and response formatting.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_REST_API {
    
    /**
     * Singleton instance
     * 
     * @var HSM_REST_API
     */
    private static $instance = null;
    
    /**
     * API namespace
     * 
     * @var string
     */
    private $namespace = 'hsm/v1';
    
    /**
     * Registered endpoints
     * 
     * @var array
     */
    private $endpoints = array();
    
    /**
     * API statistics
     * 
     * @var array
     */
    private $api_stats = array(
        'total_requests' => 0,
        'successful_requests' => 0,
        'failed_requests' => 0,
        'endpoints_called' => array()
    );
    
    /**
     * Get singleton instance
     *
     * @return HSM_REST_API
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
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('rest_api_init', array($this, 'add_cors_support'));
        add_filter('rest_pre_serve_request', array($this, 'log_api_request'), 10, 4);
    }
    
    /**
     * Register API routes
     */
    public function register_routes() {
        // Health check endpoint
        register_rest_route($this->namespace, '/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'health_check'),
            'permission_callback' => '__return_true'
        ));
        
        // NOTE: Stripe-related routes (tax/calculate, payment/intent, orders/create) have been
        // consolidated to hsm-stripe/v1 namespace. Use /wp-json/hsm-stripe/v1/ instead.
        // See: includes/api/class-api-manager.php
        
        // NOTE: GraphQL proxy route has been consolidated to hsm-graphql/v1 namespace.
        // Use /wp-json/hsm-graphql/v1/proxy instead of /wp-json/hsm/v1/graphql
        // See: includes/api/class-graphql-proxy-api.php
        
        // Settings endpoint
        register_rest_route($this->namespace, '/settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_settings'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        register_rest_route($this->namespace, '/settings', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_settings'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'settings' => array(
                    'required' => true,
                    'type' => 'object'
                )
            )
        ));
        
        // Additional routes from HSM_REST_Manager (consolidated)
        register_rest_route($this->namespace, '/system-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_system_status'),
            'permission_callback' => array($this, 'check_api_permission')
        ));
        
        register_rest_route($this->namespace, '/test', array(
            'methods' => 'GET',
            'callback' => array($this, 'test_endpoint'),
            'permission_callback' => array($this, 'check_api_permission')
        ));
        
        register_rest_route($this->namespace, '/api-key', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_api_key'),
            'permission_callback' => '__return_true' // Allow access for API key generation
        ));
        
        register_rest_route($this->namespace, '/stripe-webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_stripe_webhook'),
            'permission_callback' => '__return_true' // Webhooks don't need user authentication
        ));
        
        register_rest_route($this->namespace, '/orders/callback', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_order_callback'),
            'permission_callback' => array($this, 'check_api_permission')
        ));
        
        register_rest_route($this->namespace, '/orders/(?P<id>\d+)/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_order_status'),
            'permission_callback' => array($this, 'check_api_permission')
        ));
    }
    
    /**
     * Add CORS support
     */
    public function add_cors_support() {
        remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
        add_filter('rest_pre_serve_request', array($this, 'cors_headers'));
    }
    
    /**
     * CORS headers
     * 
     * @param mixed $value
     * @return mixed
     */
    public function cors_headers($value) {
        // Respect plugin/general settings if available
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        $allowed_origin = '*';

        if (class_exists('HSM_General_Settings')) {
            try {
                $settings = new HSM_General_Settings();
                if (!$settings->is_cors_enabled()) {
                    return $value; // CORS disabled by settings
                }

                $origins = $settings->get_cors_origins();
                if (empty($origins) || in_array('*', $origins)) {
                    $allowed_origin = '*';
                } elseif (in_array($origin, $origins)) {
                    $allowed_origin = $origin;
                } else {
                    // origin not allowed; do not set CORS headers
                    return $value;
                }
            } catch (Exception $e) {
                // Fall back to permissive behaviour on error
                $allowed_origin = '*';
            }
        }

        header('Access-Control-Allow-Origin: ' . $allowed_origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
        header('Access-Control-Allow-Credentials: true');

        // Handle preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            // short-circuit preflight
            status_header(200);
            // WordPress will end the request after this filter if we exit; keep existing behavior
            exit();
        }
        
        return $value;
    }
    
    /**
     * Health check endpoint
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function health_check($request) {
        // Use unified health manager
        if (class_exists('HSM_Health_Manager')) {
            $health_manager = HSM_Health_Manager::get_instance();
            $health_data = $health_manager->get_health_status();
        } else {
            // Fallback to basic health check
            $health_data = array(
                'status' => 'healthy',
                'timestamp' => current_time('Y-m-d H:i:s'),
                'version' => '2.0.0',
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'note' => 'Using fallback health check - HSM_Health_Manager not available'
            );
        }
        
        return new WP_REST_Response($health_data, 200);
    }
    
    /**
     * DEPRECATED: Tax calculation endpoint
     * 
     * This method has been moved to hsm-stripe/v1 namespace.
     * Use /wp-json/hsm-stripe/v1/tax/calculate instead.
     * 
     * @deprecated Use HSM_Tax_Calculator_API via hsm-stripe/v1 namespace
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function calculate_tax($request) {
        return new WP_REST_Response(array(
            'error' => 'This endpoint has been moved',
            'message' => 'Use /wp-json/hsm-stripe/v1/tax/calculate instead',
            'new_endpoint' => rest_url('hsm-stripe/v1/tax/calculate')
        ), 301);
    }
    
    /**
     * DEPRECATED: Create payment intent endpoint
     * 
     * This method has been moved to hsm-stripe/v1 namespace.
     * Use /wp-json/hsm-stripe/v1/payment/intent instead.
     * 
     * @deprecated Use HSM_Payment_Intent_API via hsm-stripe/v1 namespace
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function create_payment_intent($request) {
        return new WP_REST_Response(array(
            'error' => 'This endpoint has been moved',
            'message' => 'Use /wp-json/hsm-stripe/v1/payment/intent instead',
            'new_endpoint' => rest_url('hsm-stripe/v1/payment/intent')
        ), 301);
    }
    
    /**
     * DEPRECATED: Create order endpoint
     * 
     * This method has been moved to hsm-stripe/v1 namespace.
     * Use /wp-json/hsm-stripe/v1/orders/create instead.
     * 
     * @deprecated Use HSM_Order_Creation_API via hsm-stripe/v1 namespace
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function create_order($request) {
        return new WP_REST_Response(array(
            'error' => 'This endpoint has been moved',
            'message' => 'Use /wp-json/hsm-stripe/v1/orders/create instead',
            'new_endpoint' => rest_url('hsm-stripe/v1/orders/create')
        ), 301);
    }
    
    /**
     * DEPRECATED: GraphQL proxy endpoint
     * 
     * This method has been moved to hsm-graphql/v1 namespace.
     * Use /wp-json/hsm-graphql/v1/proxy instead.
     * 
     * @deprecated Use HSM_GraphQL_Proxy_API via hsm-graphql/v1 namespace
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function graphql_proxy($request) {
        return new WP_REST_Response(array(
            'error' => 'This endpoint has been moved',
            'message' => 'Use /wp-json/hsm-graphql/v1/proxy instead',
            'new_endpoint' => rest_url('hsm-graphql/v1/proxy')
        ), 301);
    }
    
    /**
     * Get settings endpoint
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_settings($request) {
        try {
            if (class_exists('HSM_Options')) {
                $options = HSM_Options::get_instance();
                $settings = $options->get_all();
            } else {
                $settings = array();
            }
            
            return new WP_REST_Response($settings, 200);
            
        } catch (Exception $e) {
            // Use unified response manager
            if (class_exists('HSM_Response_Manager')) {
                $response_manager = HSM_Response_Manager::get_instance();
                return $response_manager->handle_exception($e, 'Get Settings');
            }
            
            // Fallback to basic error response
            return new WP_REST_Response(array(
                'error' => 'Failed to get settings',
                'message' => $e->getMessage()
            ), 500);
        }
    }
    
    /**
     * Update settings endpoint
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_settings($request) {
        try {
            $settings = $request->get_param('settings');
            
            if (class_exists('HSM_Options')) {
                $options = HSM_Options::get_instance();
                $success = $options->set_multiple($settings);
                
                if ($success) {
                    return new WP_REST_Response(array('success' => true), 200);
                } else {
                    return new WP_REST_Response(array(
                        'error' => 'Failed to update settings'
                    ), 500);
                }
            } else {
                return new WP_REST_Response(array(
                    'error' => 'Options manager not available'
                ), 503);
            }
            
        } catch (Exception $e) {
            // Use unified response manager
            if (class_exists('HSM_Response_Manager')) {
                $response_manager = HSM_Response_Manager::get_instance();
                return $response_manager->handle_exception($e, 'Update Settings');
            }
            
            // Fallback to basic error response
            return new WP_REST_Response(array(
                'error' => 'Settings update failed',
                'message' => $e->getMessage()
            ), 500);
        }
    }
    
    /**
     * Check API permission
     * 
     * @param WP_REST_Request $request
     * @return bool Has permission
     */
    public function check_api_permission($request) {
        // Use unified permission manager
        if (class_exists('HSM_Permission_Manager')) {
            $permission_manager = HSM_Permission_Manager::get_instance();
            return $permission_manager->validate_api_permission($request, 'api');
        }
        
        // Fallback to basic permission check
        return true;
    }
    
    /**
     * Check admin permission
     * 
     * @param WP_REST_Request $request
     * @return bool Has permission
     */
    public function check_admin_permission($request) {
        // Use unified permission manager
        if (class_exists('HSM_Permission_Manager')) {
            $permission_manager = HSM_Permission_Manager::get_instance();
            return $permission_manager->validate_admin_permission($request, 'manage_options');
        }
        
        // Fallback to basic admin permission check
        return current_user_can('manage_options');
    }
    
    /**
     * Log API request
     * 
     * @param mixed $served
     * @param WP_REST_Response $result
     * @param WP_REST_Request $request
     * @param WP_REST_Server $server
     * @return mixed
     */
    public function log_api_request($served, $result, $request, $server) {
        $this->api_stats['total_requests']++;
        
        if ($result->get_status() >= 200 && $result->get_status() < 300) {
            $this->api_stats['successful_requests']++;
        } else {
            $this->api_stats['failed_requests']++;
        }
        
        $route = $request->get_route();
        $this->api_stats['endpoints_called'][$route] = ($this->api_stats['endpoints_called'][$route] ?? 0) + 1;
        
        return $served;
    }
    
    /**
     * Get API statistics
     * 
     * @return array API statistics
     */
    public function get_api_stats() {
        $stats = $this->api_stats;
        
        if ($stats['total_requests'] > 0) {
            $stats['success_rate'] = round(($stats['successful_requests'] / $stats['total_requests']) * 100, 2);
        } else {
            $stats['success_rate'] = 0;
        }
        
        return $stats;
    }
    
    /**
     * Get registered endpoints
     * 
     * @return array Registered endpoints
     */
    public function get_endpoints() {
        return $this->endpoints;
    }
    
    /**
     * Get internal health status for REST API
     * 
     * @return array Health status
     */
    public function get_internal_health_status() {
        $stats = $this->get_api_stats();
        
        return array(
            'status' => 'healthy',
            'namespace' => $this->namespace,
            'total_requests' => $stats['total_requests'],
            'success_rate' => $stats['success_rate'],
            'endpoints_count' => count($this->endpoints)
        );
    }
    
    /**
     * Get system status endpoint
     * Consolidated from HSM_REST_Manager for single API system
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_system_status($request) {
        try {
            $status = array(
                'plugin_version' => '2.0.0',
                'wordpress_version' => get_bloginfo('version'),
                'woocommerce_active' => class_exists('WooCommerce'),
                'php_version' => PHP_VERSION,
                'server_time' => current_time('mysql'),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'api_status' => 'healthy',
                'rest_api_namespace' => $this->namespace,
                'total_api_requests' => $this->api_stats['total_requests'],
                'successful_api_requests' => $this->api_stats['successful_requests']
            );
            
            return new WP_REST_Response($status, 200);
            
        } catch (Exception $e) {
            // Use unified response manager
            if (class_exists('HSM_Response_Manager')) {
                $response_manager = HSM_Response_Manager::get_instance();
                return $response_manager->handle_exception($e, 'System Status Check');
            }
            
            // Fallback to basic error response
            return new WP_REST_Response(array(
                'error' => 'System status check failed',
                'message' => $e->getMessage()
            ), 500);
        }
    }
    
    /**
     * Test endpoint
     * Consolidated from HSM_REST_Manager for single API system
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function test_endpoint($request) {
        try {
            $user = wp_get_current_user();
            
            $response = array(
                'message' => 'HSM API is working correctly',
                'timestamp' => current_time('mysql'),
                'nonce' => wp_create_nonce('wp_rest'),
                'user_id' => $user->ID,
                'user_login' => $user->user_login,
                'authentication' => 'success',
                'api_version' => '2.0.0',
                'namespace' => $this->namespace
            );
            
            return new WP_REST_Response($response, 200);
            
        } catch (Exception $e) {
            // Use unified response manager
            if (class_exists('HSM_Response_Manager')) {
                $response_manager = HSM_Response_Manager::get_instance();
                return $response_manager->handle_exception($e, 'Test Endpoint');
            }
            
            // Fallback to basic error response
            return new WP_REST_Response(array(
                'error' => 'Test endpoint failed',
                'message' => $e->getMessage()
            ), 500);
        }
    }
    
    /**
     * Get API key for frontend authentication
     * Consolidated from HSM_REST_Manager for single API system
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_api_key($request) {
        try {
            // Generate a new API key
            $api_key = wp_generate_password(32, false);
            
            // Store the API key in options using unified database optimizer
            if (class_exists('HSM_Database_Optimizer')) {
                $db_optimizer = HSM_Database_Optimizer::get_instance();
                $api_keys = $db_optimizer->get_option('hsm_api_keys', array());
                $api_keys[] = $api_key;
                $db_optimizer->update_option('hsm_api_keys', $api_keys);
            } else {
                $api_keys = get_option('hsm_api_keys', array());
                $api_keys[] = $api_key;
                update_option('hsm_api_keys', $api_keys);
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'api_key' => $api_key,
                'message' => 'API key generated successfully',
                'usage' => 'Include this API key in X-API-Key header for authentication'
            ), 200);
            
        } catch (Exception $e) {
            // Use unified response manager
            if (class_exists('HSM_Response_Manager')) {
                $response_manager = HSM_Response_Manager::get_instance();
                return $response_manager->handle_exception($e, 'API Key Generation');
            }
            
            // Fallback to basic error response
            return new WP_REST_Response(array(
                'error' => 'API key generation failed',
                'message' => $e->getMessage()
            ), 500);
        }
    }
    
    /**
     * Handle Stripe webhook
     * Consolidated from HSM_REST_Manager for single API system
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function handle_stripe_webhook($request) {
        try {
            $payload = $request->get_body();
            $sig_header = $request->get_header('stripe-signature');
            
            if (!$sig_header) {
                return new WP_REST_Response(array(
                    'error' => 'Missing Stripe signature'
                ), 400);
            }
            
            // Use unified database optimizer
            if (class_exists('HSM_Database_Optimizer')) {
                $db_optimizer = HSM_Database_Optimizer::get_instance();
                $webhook_secret = $db_optimizer->get_option('hsm_stripe_webhook_secret', '');
            } else {
                $webhook_secret = get_option('hsm_stripe_webhook_secret', '');
            }
            if (!$webhook_secret) {
                return new WP_REST_Response(array(
                    'error' => 'Webhook secret not configured'
                ), 400);
            }
            
            // Stripe SDK removed - webhook verification handled by Next.js application
            // This endpoint is kept for compatibility
            $event = json_decode($payload, true);
            
            // Process webhook event
            $this->process_webhook_event($event);
            
            return new WP_REST_Response(array(
                'message' => 'Webhook processed successfully'
            ), 200);
            
        } catch (Exception $e) {
            // Use unified response manager
            if (class_exists('HSM_Response_Manager')) {
                $response_manager = HSM_Response_Manager::get_instance();
                return $response_manager->handle_exception($e, 'Webhook Processing');
            }
            
            // Fallback to basic error response
            return new WP_REST_Response(array(
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage()
            ), 500);
        }
    }
    
    /**
     * Process webhook event
     * Private helper for webhook processing
     *
     * @param array $event Stripe event
     * @return void
     */
    private function process_webhook_event($event) {
        if (!is_array($event) || !isset($event['type'])) {
            return;
        }
        
        switch ($event['type']) {
            case 'payment_intent.succeeded':
                $this->handle_payment_success($event['data']['object'] ?? array());
                break;
            case 'payment_intent.payment_failed':
                $this->handle_payment_failure($event['data']['object'] ?? array());
                break;
            default:
                error_log("HSM: Unhandled webhook event type: {$event['type']}");
        }
    }
    
    /**
     * Handle payment success
     * Private helper for successful payments
     *
     * @param array $payment_intent Payment intent object
     * @return void
     */
    private function handle_payment_success($payment_intent) {
        if (isset($payment_intent['id'])) {
            error_log("HSM: Payment succeeded for intent: {$payment_intent['id']}");
        }
    }
    
    /**
     * Handle payment failure
     * Private helper for failed payments
     *
     * @param array $payment_intent Payment intent object
     * @return void
     */
    private function handle_payment_failure($payment_intent) {
        if (isset($payment_intent['id'])) {
            error_log("HSM: Payment failed for intent: {$payment_intent['id']}");
        }
    }
    
    /**
     * Handle order callback for frontend updates
     * Consolidated from HSM_REST_Manager for single API system
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function handle_order_callback($request) {
        try {
            $body = json_decode($request->get_body(), true);
            
            // Validate required fields
            if (!isset($body['order_id']) || !isset($body['status'])) {
                return new WP_REST_Response(array(
                    'error' => 'Missing required fields: order_id or status'
                ), 400);
            }
            
            $order_id = intval($body['order_id']);
            $status = sanitize_text_field($body['status']);
            $metadata = $body['metadata'] ?? array();
            
            // Get WooCommerce order
            if (!function_exists('wc_get_order')) {
                return new WP_REST_Response(array(
                    'error' => 'WooCommerce not available'
                ), 503);
            }
            
            $order = wc_get_order($order_id);
            if (!$order) {
                return new WP_REST_Response(array(
                    'error' => 'Order not found'
                ), 404);
            }
            
            // Update order status
            $order->set_status($status);
            
            // Add metadata if provided
            foreach ($metadata as $key => $value) {
                $order->update_meta_data($key, $value);
            }
            
            $order->save();
            
            return new WP_REST_Response(array(
                'message' => 'Order callback processed successfully',
                'order_id' => $order_id,
                'status' => $status
            ), 200);
            
        } catch (Exception $e) {
            // Use unified response manager
            if (class_exists('HSM_Response_Manager')) {
                $response_manager = HSM_Response_Manager::get_instance();
                return $response_manager->handle_exception($e, 'Order Callback');
            }
            
            // Fallback to basic error response
            return new WP_REST_Response(array(
                'error' => 'Order callback failed',
                'message' => $e->getMessage()
            ), 500);
        }
    }
    
    /**
     * Get order status
     * Consolidated from HSM_REST_Manager for single API system
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_order_status($request) {
        try {
            $order_id = intval($request->get_param('id'));
            
            // Get WooCommerce order
            if (!function_exists('wc_get_order')) {
                return new WP_REST_Response(array(
                    'error' => 'WooCommerce not available'
                ), 503);
            }
            
            $order = wc_get_order($order_id);
            if (!$order) {
                return new WP_REST_Response(array(
                    'error' => 'Order not found'
                ), 404);
            }
            
            $order_data = array(
                'id' => $order->get_id(),
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'currency' => $order->get_currency(),
                'payment_method' => $order->get_payment_method(),
                'payment_method_title' => $order->get_payment_method_title(),
                'stripe_payment_intent_id' => $order->get_meta('_stripe_payment_intent_id'),
                'created_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'modified_date' => $order->get_date_modified()->format('Y-m-d H:i:s')
            );
            
            return new WP_REST_Response($order_data, 200);
            
        } catch (Exception $e) {
            // Use unified response manager
            if (class_exists('HSM_Response_Manager')) {
                $response_manager = HSM_Response_Manager::get_instance();
                return $response_manager->handle_exception($e, 'Get Order Status');
            }
            
            // Fallback to basic error response
            return new WP_REST_Response(array(
                'error' => 'Failed to get order status',
                'message' => $e->getMessage()
            ), 500);
        }
    }
}

// Agent Signature: 160125 - Fullstack - API_Namespace_Consolidation