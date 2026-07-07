<?php
/**
 * HSM GraphQL Proxy API Class
 * 
 * Handles GraphQL proxy requests from frontend to WordPress GraphQL endpoint.
 * Routes all GraphQL operations through HSM plugin instead of direct connection.
 * 
 * CONSOLIDATED API STRUCTURE:
 * - hsm/v1: General HSM endpoints (health, settings, system-status, etc.)
 * - hsm-stripe/v1: ALL Stripe payment operations (tax, payment intent, orders)
 * - hsm-graphql/v1: ALL GraphQL proxy operations (PRIMARY namespace for GraphQL)
 * 
 * NOTE: The /wp-json/hsm/v1/graphql endpoint has been deprecated.
 * All GraphQL operations should use /wp-json/hsm-graphql/v1/proxy instead.
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_GraphQL_Proxy_API {
    
    /**
     * Error handler instance
     * 
     * @var HSM_Error_Handler
     */
    private $error_handler;
    
    /**
     * GraphQL manager instance
     * 
     * @var HSM_GraphQL_Manager
     */
    private $graphql_manager;
    
    /**
     * Constructor
     * 
     * @param HSM_Error_Handler $error_handler Error handler instance
     */
    public function __construct($error_handler) {
        $this->error_handler = $error_handler;
        $this->graphql_manager = new HSM_GraphQL_Manager($error_handler);
        
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }
    
    /**
     * Handle GraphQL proxy request
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response REST response
     */
    public function handle_graphql_proxy($request) {
        try {
            // Rate limiting check
            if (!$this->check_rate_limit()) {
                return new WP_REST_Response([
                    'error' => 'Rate limit exceeded. Please try again later.'
                ], 429);
            }
            
            // Security validation
            if (!$this->validate_request_security($request)) {
                return new WP_REST_Response([
                    'error' => 'Security validation failed'
                ], 403);
            }
            
            // Get request body
            $body = $request->get_body();
            $data = json_decode($body, true);
            
            if (!$data) {
                return new WP_REST_Response([
                    'error' => 'Invalid JSON in request body'
                ], 400);
            }
            
            // Validate required fields
            if (!isset($data['query'])) {
                return new WP_REST_Response([
                    'error' => 'Missing required field: query'
                ], 400);
            }
            
            $query = $data['query'];
            $variables = $data['variables'] ?? [];
            
            // Enhanced query validation
            if (!$this->validate_query_enhanced($query)) {
                return new WP_REST_Response([
                    'error' => 'Query validation failed'
                ], 400);
            }
            
            // Execute GraphQL query through HSM manager
            $result = $this->graphql_manager->proxy_graphql_request($query, $variables);
            
            return new WP_REST_Response([
                'data' => $result['data'] ?? null,
                'success' => $result['success'] ?? false,
                'timestamp' => $result['timestamp'] ?? current_time('mysql')
            ], 200);
            
        } catch (Exception $e) {
            $this->error_handler->log_error('GraphQL proxy request failed', $e);
            
            return new WP_REST_Response([
                'error' => 'GraphQL request failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Handle specific GraphQL operations
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response REST response
     */
    public function handle_graphql_operation($request) {
        try {
            $operation = $request->get_param('operation');
            $params = $request->get_json_params();
            
            switch ($operation) {
                case 'get_all_products':
                    $result = $this->graphql_manager->get_all_products();
                    break;
                    
                case 'get_product_by_slug':
                    if (!isset($params['slug'])) {
                        return new WP_REST_Response(['error' => 'Missing slug parameter'], 400);
                    }
                    $result = $this->graphql_manager->get_product_by_slug($params['slug']);
                    break;
                    
                case 'get_products_by_ids':
                    if (!isset($params['ids'])) {
                        return new WP_REST_Response(['error' => 'Missing ids parameter'], 400);
                    }
                    $result = $this->graphql_manager->get_products_by_ids($params['ids']);
                    break;
                    
                case 'get_option_product_by_id':
                    if (!isset($params['id'])) {
                        return new WP_REST_Response(['error' => 'Missing id parameter'], 400);
                    }
                    $result = $this->graphql_manager->get_option_product_by_id($params['id']);
                    break;
                    
                case 'get_cart':
                    $result = $this->graphql_manager->get_cart();
                    break;
                    
                case 'get_configuration_categories':
                    $result = $this->graphql_manager->get_configuration_categories();
                    break;
                    
                case 'check_compatibility':
                    if (!isset($params['product_ids'])) {
                        return new WP_REST_Response(['error' => 'Missing product_ids parameter'], 400);
                    }
                    $result = $this->graphql_manager->check_compatibility($params['product_ids']);
                    break;
                    
                case 'calculate_financing':
                    if (!isset($params['amount']) || !isset($params['term'])) {
                        return new WP_REST_Response(['error' => 'Missing amount or term parameter'], 400);
                    }
                    $result = $this->graphql_manager->calculate_financing($params['amount'], $params['term']);
                    break;
                    
                case 'estimate_insurance':
                    if (!isset($params['products'])) {
                        return new WP_REST_Response(['error' => 'Missing products parameter'], 400);
                    }
                    $coverage_type = $params['coverage_type'] ?? 'comprehensive';
                    $result = $this->graphql_manager->estimate_insurance($params['products'], $coverage_type);
                    break;
                    
                case 'load_configuration':
                    if (!isset($params['configuration_id'])) {
                        return new WP_REST_Response(['error' => 'Missing configuration_id parameter'], 400);
                    }
                    $result = $this->graphql_manager->load_configuration($params['configuration_id']);
                    break;
                    
                case 'add_configuration_to_cart':
                    if (!isset($params['configuration'])) {
                        return new WP_REST_Response(['error' => 'Missing configuration parameter'], 400);
                    }
                    $result = $this->graphql_manager->add_configuration_to_cart($params['configuration']);
                    break;
                    
                case 'update_cart_item_configuration':
                    if (!isset($params['cart_key']) || !isset($params['configuration'])) {
                        return new WP_REST_Response(['error' => 'Missing cart_key or configuration parameter'], 400);
                    }
                    $result = $this->graphql_manager->update_cart_item_configuration($params['cart_key'], $params['configuration']);
                    break;
                    
                case 'save_configuration':
                    if (!isset($params['configuration'])) {
                        return new WP_REST_Response(['error' => 'Missing configuration parameter'], 400);
                    }
                    $result = $this->graphql_manager->save_configuration($params['configuration']);
                    break;
                    
                case 'get_featured_products':
                    $limit = $params['limit'] ?? 10;
                    $result = $this->graphql_manager->get_featured_products($limit);
                    break;
                    
                case 'create_headless_stripe_session':
                    if (!isset($params['session_data'])) {
                        return new WP_REST_Response(['error' => 'Missing session_data parameter'], 400);
                    }
                    $result = $this->graphql_manager->create_headless_stripe_session($params['session_data']);
                    break;
                    
                case 'create_headless_order':
                    if (!isset($params['order_data'])) {
                        return new WP_REST_Response(['error' => 'Missing order_data parameter'], 400);
                    }
                    $result = $this->graphql_manager->create_headless_order($params['order_data']);
                    break;
                    
                default:
                    return new WP_REST_Response(['error' => 'Unknown operation: ' . $operation], 400);
            }
            
            return new WP_REST_Response([
                'data' => $result
            ], 200);
            
        } catch (Exception $e) {
            $this->error_handler->log_error('GraphQL operation failed', $e);
            
            return new WP_REST_Response([
                'error' => 'Operation failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get GraphQL schema information
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response REST response
     */
    public function get_graphql_schema($request) {
        try {
            $query = '
                query IntrospectionQuery {
                    __schema {
                        queryType { name }
                        mutationType { name }
                        subscriptionType { name }
                        types {
                            ...FullType
                        }
                        directives {
                            name
                            description
                            locations
                            args {
                                ...InputValue
                            }
                        }
                    }
                }
                
                fragment FullType on __Type {
                    kind
                    name
                    description
                    fields(includeDeprecated: true) {
                        name
                        description
                        args {
                            ...InputValue
                        }
                        type {
                            ...TypeRef
                        }
                        isDeprecated
                        deprecationReason
                    }
                    inputFields {
                        ...InputValue
                    }
                    interfaces {
                        ...TypeRef
                    }
                    enumValues(includeDeprecated: true) {
                        name
                        description
                        isDeprecated
                        deprecationReason
                    }
                    possibleTypes {
                        ...TypeRef
                    }
                }
                
                fragment InputValue on __InputValue {
                    name
                    description
                    type { ...TypeRef }
                    defaultValue
                }
                
                fragment TypeRef on __Type {
                    kind
                    name
                    ofType {
                        kind
                        name
                        ofType {
                            kind
                            name
                            ofType {
                                kind
                                name
                                ofType {
                                    kind
                                    name
                                    ofType {
                                        kind
                                        name
                                        ofType {
                                            kind
                                            name
                                            ofType {
                                                kind
                                                name
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            ';
            
            $result = $this->graphql_manager->execute_query($query);
            
            return new WP_REST_Response([
                'data' => $result
            ], 200);
            
        } catch (Exception $e) {
            $this->error_handler->log_error('GraphQL schema request failed', $e);
            
            return new WP_REST_Response([
                'error' => 'Schema request failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get GraphQL endpoint status
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response REST response
     */
    public function get_graphql_status($request) {
        try {
            $status = $this->graphql_manager->get_client_status();
            
            return new WP_REST_Response([
                'data' => $status
            ], 200);
            
        } catch (Exception $e) {
            $this->error_handler->log_error('GraphQL status request failed', $e);
            
            return new WP_REST_Response([
                'error' => 'Status request failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get GraphQL nonce for frontend authentication
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response REST response
     */
    public function get_graphql_nonce($request) {
        try {
            $nonce = wp_create_nonce('hsm_graphql_request');
            // Use unified database optimizer
            if (class_exists('HSM_Database_Optimizer')) {
                $db_optimizer = HSM_Database_Optimizer::get_instance();
                $api_keys = $db_optimizer->get_option('hsm_graphql_api_keys', []);
            } else {
                $api_keys = get_option('hsm_graphql_api_keys', []);
            }
            
            return new WP_REST_Response([
                'nonce' => $nonce,
                'api_keys' => $api_keys,
                'timestamp' => current_time('mysql'),
                'expires_in' => 3600 // 1 hour
            ], 200);
            
        } catch (Exception $e) {
            $this->error_handler->log_error('GraphQL nonce request failed', $e);
            
            return new WP_REST_Response([
                'error' => 'Nonce request failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validate GraphQL query for security
     * 
     * @param string $query GraphQL query string
     * @return bool True if query is valid
     */
    private function validate_query($query) {
        // Basic security validation
        $forbidden_patterns = [
            '/__schema/i',
            '/__type/i',
            '/__typename/i',
            '/system/i',
            '/admin/i',
            '/user/i',
            '/password/i',
            '/token/i',
            '/secret/i',
            '/key/i'
        ];
        
        foreach ($forbidden_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return false;
            }
        }
        
        // Check for allowed operations only
        $allowed_operations = [
            'products',
            'product',
            'cart',
            'productCategories',
            'variations',
            'attributes',
            'galleryImages',
            'image'
        ];
        
        $has_allowed_operation = false;
        foreach ($allowed_operations as $operation) {
            if (strpos($query, $operation) !== false) {
                $has_allowed_operation = true;
                break;
            }
        }
        
        return $has_allowed_operation;
    }
    
    /**
     * Register GraphQL proxy endpoints
     * 
     * Registers all GraphQL-related endpoints in the hsm-graphql/v1 namespace.
     * These are the PRIMARY endpoints for all GraphQL operations.
     * 
     * @return void
     */
    public function register_endpoints() {
        // Main GraphQL proxy endpoint - PRIMARY endpoint for GraphQL queries
        // Base URL: /wp-json/hsm-graphql/v1/proxy
        register_rest_route('hsm-graphql/v1', '/proxy', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_graphql_proxy'],
            'permission_callback' => [$this, 'validate_rest_permissions'],
            'args' => [
                'query' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'GraphQL query string'
                ],
                'variables' => [
                    'required' => false,
                    'type' => 'object',
                    'description' => 'GraphQL query variables'
                ]
            ]
        ]);
        
        // Specific operation endpoints
        register_rest_route('hsm-graphql/v1', '/operation/(?P<operation>[a-zA-Z0-9_-]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_graphql_operation'],
            'permission_callback' => [$this, 'validate_rest_permissions'],
            'args' => [
                'operation' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'GraphQL operation name'
                ]
            ]
        ]);
        
        // Schema endpoint
        register_rest_route('hsm-graphql/v1', '/schema', [
            'methods' => 'GET',
            'callback' => [$this, 'get_graphql_schema'],
            'permission_callback' => [$this, 'validate_rest_permissions']
        ]);
        
        // Status endpoint
        register_rest_route('hsm-graphql/v1', '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_graphql_status'],
            'permission_callback' => [$this, 'validate_rest_permissions']
        ]);
        
        // Nonce endpoint for frontend authentication
        register_rest_route('hsm-graphql/v1', '/nonce', [
            'methods' => 'GET',
            'callback' => [$this, 'get_graphql_nonce'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * Check rate limiting
     * 
     * @return bool True if request is allowed
     */
    private function check_rate_limit() {
        // Use unified security validator for rate limiting
        if (class_exists('HSM_Security_Validator')) {
            $security_validator = HSM_Security_Validator::get_instance();
            $request = new WP_REST_Request();
            $result = $security_validator->validate_request_security($request, 'standard', [
                'rate_limit_window' => 60,
                'max_requests' => 100
            ]);
            
            return is_wp_error($result) ? false : true;
        }
        
        // Fallback to basic rate limiting
        $client_ip = $this->get_client_ip();
        $rate_limit_key = 'hsm_graphql_rate_limit_' . md5($client_ip);
        $rate_limit_window = 60; // 1 minute
        $max_requests = 100; // 100 requests per minute
        
        // Use unified database optimizer
        if (class_exists('HSM_Database_Optimizer')) {
            $db_optimizer = HSM_Database_Optimizer::get_instance();
            $current_count = $db_optimizer->get_transient($rate_limit_key, false);
        } else {
            $current_count = get_transient($rate_limit_key);
        }
        
        if ($current_count === false) {
            // Use unified database optimizer
            if (class_exists('HSM_Database_Optimizer')) {
                $db_optimizer = HSM_Database_Optimizer::get_instance();
                $db_optimizer->set_transient($rate_limit_key, 1, $rate_limit_window);
            } else {
                set_transient($rate_limit_key, 1, $rate_limit_window);
            }
            return true;
        }
        
        if ($current_count >= $max_requests) {
            return false;
        }
        
        // Use unified database optimizer
        if (class_exists('HSM_Database_Optimizer')) {
            $db_optimizer = HSM_Database_Optimizer::get_instance();
            $db_optimizer->set_transient($rate_limit_key, $current_count + 1, $rate_limit_window);
        } else {
            set_transient($rate_limit_key, $current_count + 1, $rate_limit_window);
        }
        return true;
    }
    
    /**
     * Validate request security
     * 
     * @param WP_REST_Request $request REST request object
     * @return bool True if request is secure
     */
    private function validate_request_security($request) {
        // Use unified security validator
        if (class_exists('HSM_Security_Validator')) {
            $security_validator = HSM_Security_Validator::get_instance();
            $result = $security_validator->validate_request_security($request, 'standard', [
                'require_api_key' => false,
                'require_origin_validation' => false,
                'nonce_action' => 'hsm_graphql_request'
            ]);
            
            return is_wp_error($result) ? false : true;
        }
        
        // Fallback to basic security validation
        return $this->validate_basic_security($request);
    }
    
    /**
     * Enhanced query validation
     * 
     * @param string $query GraphQL query string
     * @return bool True if query is valid
     */
    private function validate_query_enhanced($query) {
        // Basic validation first
        if (!$this->validate_query($query)) {
            return false;
        }
        
        // Check query length
        if (strlen($query) > 10000) {
            return false;
        }
        
        // Check for nested queries (prevent deep nesting attacks)
        $nesting_level = 0;
        $max_nesting = 10;
        
        for ($i = 0; $i < strlen($query); $i++) {
            if ($query[$i] === '{') {
                $nesting_level++;
                if ($nesting_level > $max_nesting) {
                    return false;
                }
            } elseif ($query[$i] === '}') {
                $nesting_level--;
            }
        }
        
        // Check for suspicious patterns
        $suspicious_patterns = [
            '/union/i',
            '/fragment\s+\w+\s+on\s+\w+/i',
            '/directive\s*@/i',
            '/extend\s+(type|interface|union|enum|input|scalar)/i'
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Validate API key
     * 
     * @param string $api_key API key to validate
     * @return bool True if API key is valid
     */
    private function validate_api_key($api_key) {
        $valid_api_keys = get_option('hsm_graphql_api_keys', []);
        
        if (empty($valid_api_keys)) {
            // Generate default API key if none exists
            $default_key = wp_generate_password(32, false);
            $valid_api_keys = [$default_key];
            update_option('hsm_graphql_api_keys', $valid_api_keys);
        }
        
        return in_array($api_key, $valid_api_keys);
    }
    
    /**
     * Validate origin header
     * 
     * @param string $origin Origin header value
     * @return bool True if origin is allowed
     */
    private function validate_origin($origin) {
        $allowed_origins = get_option('hsm_graphql_allowed_origins', [
            'http://localhost:3000',
            'https://localhost:3000',
            'http://127.0.0.1:3000',
            'https://127.0.0.1:3000',
            'https://hsmobility.ca',
            'https://www.hsmobility.ca',
            'https://cms.hsmobility.ca',
            'https://nx.hsmobility.ca'
        ]);
        
        foreach ($allowed_origins as $allowed_origin) {
            if (strpos($origin, $allowed_origin) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate referer header
     * 
     * @param string $referer Referer header value
     * @return bool True if referer is allowed
     */
    private function validate_referer($referer) {
        $allowed_referers = get_option('hsm_graphql_allowed_referers', [
            'http://localhost:3000',
            'https://localhost:3000',
            'http://127.0.0.1:3000',
            'https://127.0.0.1:3000',
            'https://hsmobility.ca',
            'https://www.hsmobility.ca',
            'https://cms.hsmobility.ca',
            'https://nx.hsmobility.ca'
        ]);
        
        foreach ($allowed_referers as $allowed_referer) {
            if (strpos($referer, $allowed_referer) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate basic security (fallback for development)
     * 
     * @param WP_REST_Request $request REST request object
     * @return bool True if basic security checks pass
     */
    private function validate_basic_security($request) {
        // Check if we're in development mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // In development, allow requests from localhost
            $client_ip = $this->get_client_ip();
            $localhost_ips = ['127.0.0.1', '::1', 'localhost'];
            
            if (in_array($client_ip, $localhost_ips)) {
                return true;
            }
        }
        
        // Check for valid User-Agent (basic bot protection)
        $user_agent = $request->get_header('User-Agent');
        if (empty($user_agent) || strpos($user_agent, 'bot') !== false) {
            return false;
        }
        
        // Check Content-Type for JSON requests
        $content_type = $request->get_header('Content-Type');
        if ($content_type && strpos($content_type, 'application/json') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate REST API permissions with nonce support
     * 
     * @param WP_REST_Request $request REST request object
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    public function validate_rest_permissions($request) {
        // Enhanced authentication with multiple fallback methods
        
        // Method 1: Check for WordPress nonce in header
        $nonce = $request->get_header('X-WP-Nonce');
        if ($nonce && wp_verify_nonce($nonce, 'wp_rest')) {
            error_log('HSM Debug: Authentication successful via X-WP-Nonce header');
            return true;
        }
        
        // Method 2: Check for nonce in cookie
        $cookie_header = $request->get_header('Cookie');
        if ($cookie_header) {
            preg_match('/wp_rest_nonce=([^;]+)/', $cookie_header, $matches);
            $cookie_nonce = $matches[1] ?? null;
            if ($cookie_nonce && wp_verify_nonce($cookie_nonce, 'wp_rest')) {
                error_log('HSM Debug: Authentication successful via cookie nonce');
                return true;
            }
        }
        
        // Method 3: Check for API key authentication
        $api_key = $request->get_header('X-API-Key');
        if ($api_key && $this->validate_api_key($api_key)) {
            error_log('HSM Debug: Authentication successful via API key');
            return true;
        }
        
        // Method 4: Check for origin-based authentication
        $origin = $request->get_header('Origin');
        if ($origin && $this->validate_origin($origin)) {
            error_log('HSM Debug: Authentication successful via origin validation');
            return true;
        }
        
        // Method 5: Check for referer-based authentication
        $referer = $request->get_header('Referer');
        if ($referer && $this->validate_referer($referer)) {
            error_log('HSM Debug: Authentication successful via referer validation');
            return true;
        }
        
        // Method 6: Check for basic security (development mode)
        if ($this->validate_basic_security($request)) {
            error_log('HSM Debug: Authentication successful via basic security (development mode)');
            return true;
        }
        
        // Method 7: Allow localhost/development access (for testing)
        $origin = $request->get_header('Origin');
        $referer = $request->get_header('Referer');
        $user_agent = $request->get_header('User-Agent');
        
        if (($origin && (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false)) ||
            ($referer && (strpos($referer, 'localhost') !== false || strpos($referer, '127.0.0.1') !== false)) ||
            ($user_agent && strpos($user_agent, 'Postman') !== false)) {
            error_log('HSM Debug: Authentication successful via localhost/development access');
            return true;
        }
        
        // Method 8: Allow requests from Next.js development server
        if ($origin && (strpos($origin, '3000') !== false || strpos($origin, '3001') !== false || strpos($origin, '3002') !== false)) {
            error_log('HSM Debug: Authentication successful via Next.js development server');
            return true;
        }
        
        // Log the failed authentication attempt with details
        $this->log_security_validation_failure($request);
        
        // Return a more informative error
        return new WP_Error(
            'authentication_failed',
            'Authentication failed - no valid nonce, API key, or development access found',
            array('status' => 403)
        );
    }
    
    /**
     * Log security validation failure for debugging
     * 
     * @param WP_REST_Request $request REST request object
     * @return void
     */
    private function log_security_validation_failure($request) {
        $client_ip = $this->get_client_ip();
        $user_agent = $request->get_header('User-Agent');
        $origin = $request->get_header('Origin');
        $referer = $request->get_header('Referer');
        
        $log_data = [
            'client_ip' => $client_ip,
            'user_agent' => $user_agent,
            'origin' => $origin,
            'referer' => $referer,
            'headers' => $request->get_headers(),
            'timestamp' => current_time('mysql')
        ];
        
        error_log('HSM GraphQL Security Validation Failed: ' . json_encode($log_data));
        
        // Also log to plugin logger if available
        if ($this->error_handler) {
            $this->error_handler->log_error('GraphQL security validation failed', new Exception('Security validation failed for request from ' . $client_ip));
        }
    }
}

// Agent Signature: 160125 - Fullstack - API_Namespace_Consolidation