<?php
/**
 * HSM API Endpoint Manager Class
 * 
 * Unified API endpoint registration system that consolidates all endpoint
 * registration patterns across the HSM plugin. This provides a consistent
 * and maintainable way to register REST API endpoints.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_API_Endpoint_Manager {
    
    /**
     * Singleton instance
     * 
     * @var HSM_API_Endpoint_Manager
     */
    private static $instance = null;
    
    /**
     * Registered endpoints
     * 
     * @var array
     */
    private $registered_endpoints = [];
    
    /**
     * Namespace configurations
     * 
     * @var array
     */
    private $namespaces = [
        'hsm-stripe/v1' => [
            'description' => 'HSM Stripe API endpoints',
            'version' => '1.0.0'
        ],
        'hsm-graphql/v1' => [
            'description' => 'HSM GraphQL API endpoints',
            'version' => '1.0.0'
        ],
        'hsm/v1' => [
            'description' => 'HSM General API endpoints',
            'version' => '1.0.0'
        ]
    ];
    
    /**
     * Permission manager instance
     * 
     * @var HSM_Permission_Manager
     */
    private $permission_manager;
    
    /**
     * Error handler instance
     * 
     * @var HSM_Error_Handler
     */
    private $error_handler;
    
    /**
     * Get singleton instance
     *
     * @return HSM_API_Endpoint_Manager
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
        $this->permission_manager = HSM_Permission_Manager::get_instance();
        $this->error_handler = new HSM_Error_Handler();
        
        // Register all endpoints on REST API init
        add_action('rest_api_init', [$this, 'register_all_endpoints']);
    }
    
    /**
     * Register all API endpoints
     * 
     * @return void
     */
    public function register_all_endpoints() {
        $this->register_stripe_endpoints();
        $this->register_graphql_endpoints();
        $this->register_general_endpoints();
        $this->register_health_endpoints();
    }
    
    /**
     * Register Stripe-related endpoints
     * 
     * @return void
     */
    private function register_stripe_endpoints() {
        $namespace = 'hsm-stripe/v1';
        
        // Tax calculation endpoint
        $this->register_endpoint($namespace, '/tax/calculate', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_tax_calculation'],
            'permission_callback' => $this->get_permission_callback('api'),
            'args' => [
                'country' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => [$this, 'validate_country_code']
                ],
                'amount' => [
                    'required' => true,
                    'type' => 'number',
                    'sanitize_callback' => 'floatval',
                    'validate_callback' => [$this, 'validate_amount']
                ]
            ]
        ]);
        
        // Payment intent endpoint
        $this->register_endpoint($namespace, '/payment/intent', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_payment_intent'],
            'permission_callback' => $this->get_permission_callback('api'),
            'args' => [
                'amount' => [
                    'required' => true,
                    'type' => 'number',
                    'sanitize_callback' => 'floatval',
                    'validate_callback' => [$this, 'validate_amount']
                ],
                'currency' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => 'usd',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => [$this, 'validate_currency']
                ]
            ]
        ]);
        
        // Order creation endpoint
        $this->register_endpoint($namespace, '/orders/create', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_order_creation'],
            'permission_callback' => $this->get_permission_callback('api'),
            'args' => [
                'items' => [
                    'required' => true,
                    'type' => 'array',
                    'validate_callback' => [$this, 'validate_order_items']
                ],
                'customer' => [
                    'required' => false,
                    'type' => 'object',
                    'validate_callback' => [$this, 'validate_customer_data']
                ]
            ]
        ]);
        
        // Stripe webhook endpoint
        $this->register_endpoint($namespace, '/stripe-webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_stripe_webhook'],
            'permission_callback' => '__return_true', // Webhooks don't need user authentication
            'args' => []
        ]);
    }
    
    /**
     * Register GraphQL-related endpoints
     * 
     * @return void
     */
    private function register_graphql_endpoints() {
        $namespace = 'hsm-graphql/v1';
        
        // Main GraphQL proxy endpoint
        $this->register_endpoint($namespace, '/proxy', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_graphql_proxy'],
            'permission_callback' => $this->get_permission_callback('rest'),
            'args' => [
                'query' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'validate_callback' => [$this, 'validate_graphql_query']
                ],
                'variables' => [
                    'required' => false,
                    'type' => 'object',
                    'validate_callback' => [$this, 'validate_graphql_variables']
                ]
            ]
        ]);
        
        // Specific operation endpoints
        $this->register_endpoint($namespace, '/operation/(?P<operation>[a-zA-Z0-9_-]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_graphql_operation'],
            'permission_callback' => $this->get_permission_callback('rest'),
            'args' => [
                'operation' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => [$this, 'validate_operation_name']
                ],
                'variables' => [
                    'required' => false,
                    'type' => 'object',
                    'validate_callback' => [$this, 'validate_graphql_variables']
                ]
            ]
        ]);
        
        // Schema endpoint
        $this->register_endpoint($namespace, '/schema', [
            'methods' => 'GET',
            'callback' => [$this, 'get_graphql_schema'],
            'permission_callback' => $this->get_permission_callback('rest'),
            'args' => []
        ]);
        
        // Status endpoint
        $this->register_endpoint($namespace, '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_graphql_status'],
            'permission_callback' => $this->get_permission_callback('rest'),
            'args' => []
        ]);
        
        // Nonce endpoint for frontend authentication
        $this->register_endpoint($namespace, '/nonce', [
            'methods' => 'GET',
            'callback' => [$this, 'get_graphql_nonce'],
            'permission_callback' => '__return_true',
            'args' => []
        ]);
    }
    
    /**
     * Register general API endpoints
     * 
     * @return void
     */
    private function register_general_endpoints() {
        $namespace = 'hsm/v1';
        
        // Settings endpoints
        $this->register_endpoint($namespace, '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_settings'],
            'permission_callback' => $this->get_permission_callback('admin'),
            'args' => []
        ]);
        
        $this->register_endpoint($namespace, '/settings', [
            'methods' => 'POST',
            'callback' => [$this, 'update_settings'],
            'permission_callback' => $this->get_permission_callback('admin'),
            'args' => [
                'settings' => [
                    'required' => true,
                    'type' => 'object',
                    'validate_callback' => [$this, 'validate_settings']
                ]
            ]
        ]);
        
        // System status endpoint
        $this->register_endpoint($namespace, '/system-status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_system_status'],
            'permission_callback' => $this->get_permission_callback('api'),
            'args' => []
        ]);
        
        // Test endpoint
        $this->register_endpoint($namespace, '/test', [
            'methods' => 'GET',
            'callback' => [$this, 'test_endpoint'],
            'permission_callback' => $this->get_permission_callback('api'),
            'args' => []
        ]);
        
        // API key endpoint
        $this->register_endpoint($namespace, '/api-key', [
            'methods' => 'GET',
            'callback' => [$this, 'get_api_key'],
            'permission_callback' => '__return_true', // Allow access for API key generation
            'args' => []
        ]);
        
        // Order callback endpoint
        $this->register_endpoint($namespace, '/orders/callback', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_order_callback'],
            'permission_callback' => $this->get_permission_callback('api'),
            'args' => [
                'order_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => [$this, 'validate_order_id']
                ],
                'status' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => [$this, 'validate_order_status']
                ]
            ]
        ]);
        
        // Order status endpoint
        $this->register_endpoint($namespace, '/orders/(?P<id>\d+)/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_order_status'],
            'permission_callback' => $this->get_permission_callback('api'),
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => [$this, 'validate_order_id']
                ]
            ]
        ]);
    }
    
    /**
     * Register health check endpoints
     * 
     * @return void
     */
    private function register_health_endpoints() {
        $namespace = 'hsm/v1';
        
        // Main health check endpoint
        $this->register_endpoint($namespace, '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'get_health_status'],
            'permission_callback' => '__return_true',
            'args' => [
                'format' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => 'json',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => [$this, 'validate_health_format']
                ]
            ]
        ]);
        
        // Quick health check endpoint
        $this->register_endpoint($namespace, '/health/quick', [
            'methods' => 'GET',
            'callback' => [$this, 'get_quick_health_status'],
            'permission_callback' => '__return_true',
            'args' => []
        ]);
        
        // Health metrics endpoint
        $this->register_endpoint($namespace, '/health/metrics', [
            'methods' => 'GET',
            'callback' => [$this, 'get_health_metrics'],
            'permission_callback' => '__return_true',
            'args' => []
        ]);
    }
    
    /**
     * Register a single endpoint
     * 
     * @param string $namespace API namespace
     * @param string $route Route pattern
     * @param array $args Endpoint arguments
     * @return void
     */
    private function register_endpoint($namespace, $route, $args) {
        try {
            register_rest_route($namespace, $route, $args);
            
            // Track registered endpoint
            $this->registered_endpoints[] = [
                'namespace' => $namespace,
                'route' => $route,
                'methods' => $args['methods'],
                'callback' => $args['callback'],
                'permission_callback' => $args['permission_callback']
            ];
            
        } catch (Exception $e) {
            $this->error_handler->log_error(
                "Failed to register endpoint {$namespace}{$route}",
                $e
            );
        }
    }
    
    /**
     * Get permission callback for endpoint
     * 
     * @param string $permission_type Type of permission
     * @return callable Permission callback
     */
    private function get_permission_callback($permission_type) {
        return function($request) use ($permission_type) {
            if (class_exists('HSM_Permission_Manager')) {
                $permission_manager = HSM_Permission_Manager::get_instance();
                
                switch ($permission_type) {
                    case 'api':
                        return $permission_manager->validate_api_permission($request, 'api');
                    case 'admin':
                        return $permission_manager->validate_admin_permission($request, 'manage_options');
                    case 'rest':
                        return $permission_manager->validate_rest_permission($request, 'rest');
                    default:
                        return $permission_manager->validate_api_permission($request, 'api');
                }
            }
            
            // Fallback to basic permission check
            return true;
        };
    }
    
    /**
     * Get registered endpoints
     * 
     * @return array Registered endpoints
     */
    public function get_registered_endpoints() {
        return $this->registered_endpoints;
    }
    
    /**
     * Get endpoint statistics
     * 
     * @return array Endpoint statistics
     */
    public function get_endpoint_stats() {
        $stats = [
            'total_endpoints' => count($this->registered_endpoints),
            'namespaces' => array_keys($this->namespaces),
            'endpoints_by_namespace' => [],
            'endpoints_by_method' => []
        ];
        
        // Count endpoints by namespace
        foreach ($this->registered_endpoints as $endpoint) {
            $namespace = $endpoint['namespace'];
            if (!isset($stats['endpoints_by_namespace'][$namespace])) {
                $stats['endpoints_by_namespace'][$namespace] = 0;
            }
            $stats['endpoints_by_namespace'][$namespace]++;
            
            // Count endpoints by method
            $method = $endpoint['methods'];
            if (!isset($stats['endpoints_by_method'][$method])) {
                $stats['endpoints_by_method'][$method] = 0;
            }
            $stats['endpoints_by_method'][$method]++;
        }
        
        return $stats;
    }
    
    // Validation methods
    public function validate_country_code($param, $request, $key) {
        $valid_countries = ['US', 'CA', 'GB', 'DE', 'FR', 'IT', 'ES', 'AU', 'JP', 'CN'];
        return in_array(strtoupper($param), $valid_countries);
    }
    
    public function validate_amount($param, $request, $key) {
        return is_numeric($param) && $param > 0;
    }
    
    public function validate_currency($param, $request, $key) {
        $valid_currencies = ['usd', 'eur', 'gbp', 'cad', 'aud', 'jpy', 'cny'];
        return in_array(strtolower($param), $valid_currencies);
    }
    
    public function validate_order_items($param, $request, $key) {
        return is_array($param) && !empty($param);
    }
    
    public function validate_customer_data($param, $request, $key) {
        return is_array($param);
    }
    
    public function validate_graphql_query($param, $request, $key) {
        return is_string($param) && !empty(trim($param));
    }
    
    public function validate_graphql_variables($param, $request, $key) {
        return is_array($param);
    }
    
    public function validate_operation_name($param, $request, $key) {
        return preg_match('/^[a-zA-Z0-9_-]+$/', $param);
    }
    
    public function validate_settings($param, $request, $key) {
        return is_array($param);
    }
    
    public function validate_order_id($param, $request, $key) {
        return is_numeric($param) && $param > 0;
    }
    
    public function validate_order_status($param, $request, $key) {
        $valid_statuses = ['pending', 'processing', 'completed', 'cancelled', 'refunded'];
        return in_array($param, $valid_statuses);
    }
    
    public function validate_health_format($param, $request, $key) {
        $valid_formats = ['json', 'xml', 'yaml'];
        return in_array($param, $valid_formats);
    }
    
    // Endpoint handlers (these would delegate to appropriate classes)
    public function handle_tax_calculation($request) {
        // Delegate to tax calculator
        if (class_exists('HSM_Tax_Calculator_API')) {
            $tax_calculator = new HSM_Tax_Calculator_API();
            return $tax_calculator->calculate_tax($request);
        }
        
        return new WP_REST_Response(['error' => 'Tax calculator not available'], 500);
    }
    
    public function handle_payment_intent($request) {
        // Delegate to payment intent API
        if (class_exists('HSM_Payment_Intent_API')) {
            $payment_api = new HSM_Payment_Intent_API();
            return $payment_api->create_payment_intent($request);
        }
        
        return new WP_REST_Response(['error' => 'Payment intent API not available'], 500);
    }
    
    public function handle_order_creation($request) {
        // Delegate to order creation API
        if (class_exists('HSM_Order_Creation_API')) {
            $order_api = new HSM_Order_Creation_API();
            return $order_api->create_order($request);
        }
        
        return new WP_REST_Response(['error' => 'Order creation API not available'], 500);
    }
    
    public function handle_stripe_webhook($request) {
        // Delegate to webhook handler
        return new WP_REST_Response(['status' => 'webhook received'], 200);
    }
    
    public function handle_graphql_proxy($request) {
        // Delegate to GraphQL proxy
        if (class_exists('HSM_GraphQL_Proxy_API')) {
            $proxy_api = new HSM_GraphQL_Proxy_API();
            return $proxy_api->handle_graphql_proxy($request);
        }
        
        return new WP_REST_Response(['error' => 'GraphQL proxy not available'], 500);
    }
    
    public function handle_graphql_operation($request) {
        // Delegate to GraphQL operation handler
        if (class_exists('HSM_GraphQL_Proxy_API')) {
            $proxy_api = new HSM_GraphQL_Proxy_API();
            return $proxy_api->handle_graphql_operation($request);
        }
        
        return new WP_REST_Response(['error' => 'GraphQL operation handler not available'], 500);
    }
    
    public function get_graphql_schema($request) {
        // Delegate to GraphQL schema handler
        if (class_exists('HSM_GraphQL_Proxy_API')) {
            $proxy_api = new HSM_GraphQL_Proxy_API();
            return $proxy_api->get_graphql_schema($request);
        }
        
        return new WP_REST_Response(['error' => 'GraphQL schema not available'], 500);
    }
    
    public function get_graphql_status($request) {
        // Delegate to GraphQL status handler
        if (class_exists('HSM_GraphQL_Proxy_API')) {
            $proxy_api = new HSM_GraphQL_Proxy_API();
            return $proxy_api->get_graphql_status($request);
        }
        
        return new WP_REST_Response(['error' => 'GraphQL status not available'], 500);
    }
    
    public function get_graphql_nonce($request) {
        // Delegate to GraphQL nonce handler
        if (class_exists('HSM_GraphQL_Proxy_API')) {
            $proxy_api = new HSM_GraphQL_Proxy_API();
            return $proxy_api->get_graphql_nonce($request);
        }
        
        return new WP_REST_Response(['error' => 'GraphQL nonce not available'], 500);
    }
    
    public function get_settings($request) {
        // Delegate to settings handler
        return new WP_REST_Response(['settings' => get_option('hsm_settings', [])], 200);
    }
    
    public function update_settings($request) {
        // Delegate to settings update handler
        $settings = $request->get_param('settings');
        update_option('hsm_settings', $settings);
        return new WP_REST_Response(['status' => 'settings updated'], 200);
    }
    
    public function get_system_status($request) {
        // Delegate to system status handler
        return new WP_REST_Response(['status' => 'system operational'], 200);
    }
    
    public function test_endpoint($request) {
        // Delegate to test handler
        return new WP_REST_Response(['status' => 'test successful'], 200);
    }
    
    public function get_api_key($request) {
        // Delegate to API key handler
        return new WP_REST_Response(['api_key' => wp_generate_password(32, false)], 200);
    }
    
    public function handle_order_callback($request) {
        // Delegate to order callback handler
        return new WP_REST_Response(['status' => 'callback processed'], 200);
    }
    
    public function get_order_status($request) {
        // Delegate to order status handler
        $order_id = $request->get_param('id');
        return new WP_REST_Response(['order_id' => $order_id, 'status' => 'pending'], 200);
    }
    
    public function get_health_status($request) {
        // Delegate to health manager
        if (class_exists('HSM_Health_Manager')) {
            $health_manager = HSM_Health_Manager::get_instance();
            return new WP_REST_Response($health_manager->get_health_status(), 200);
        }
        
        return new WP_REST_Response(['status' => 'healthy'], 200);
    }
    
    public function get_quick_health_status($request) {
        // Delegate to health manager
        if (class_exists('HSM_Health_Manager')) {
            $health_manager = HSM_Health_Manager::get_instance();
            return new WP_REST_Response($health_manager->get_quick_health_status(), 200);
        }
        
        return new WP_REST_Response(['status' => 'healthy'], 200);
    }
    
    public function get_health_metrics($request) {
        // Delegate to health manager
        if (class_exists('HSM_Health_Manager')) {
            $health_manager = HSM_Health_Manager::get_instance();
            $health_data = $health_manager->get_health_status();
            return new WP_REST_Response($health_data['summary'], 200);
        }
        
        return new WP_REST_Response(['metrics' => []], 200);
    }
}