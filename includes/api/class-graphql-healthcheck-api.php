<?php
/**
 * HSM GraphQL Healthcheck API Class
 * 
 * Handles healthcheck requests for the HSM GraphQL proxy system.
 * Provides comprehensive health monitoring, status reporting, and diagnostic capabilities.
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_GraphQL_Healthcheck_API {
    
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
     * Health monitor instance
     * 
     * @var HSM_GraphQL_Health_Monitor
     */
    private $health_monitor;
    
    /**
     * Constructor
     * 
     * @param HSM_Error_Handler $error_handler Error handler instance
     */
    public function __construct($error_handler) {
        $this->error_handler = $error_handler;
        $this->graphql_manager = new HSM_GraphQL_Manager($error_handler);
        $this->health_monitor = new HSM_GraphQL_Health_Monitor($error_handler);
        
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }
    
    /**
     * Get comprehensive health status
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response REST response
     */
    public function get_health_status($request) {
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
            
            // Use unified health manager
            if (class_exists('HSM_Health_Manager')) {
                $health_manager = HSM_Health_Manager::get_instance();
                $health_status = $health_manager->get_health_status();
            } else {
                // Fallback to health monitor
                $health_status = $this->health_monitor->get_comprehensive_health_status();
            }
            
            // Add response metadata
            $response_data = array_merge($health_status, [
                'timestamp' => current_time('mysql'),
                'version' => HSM_PLUGIN_VERSION,
                'endpoint' => '/wp-json/hsm-graphql/v1/health',
                'response_time' => $this->measure_response_time()
            ]);
            
            return new WP_REST_Response([
                'status' => 'success',
                'data' => $response_data
            ], 200);
            
        } catch (Exception $e) {
            $this->error_handler->log_error('Healthcheck request failed', $e);
            
            return new WP_REST_Response([
                'status' => 'error',
                'error' => 'Healthcheck request failed: ' . $e->getMessage(),
                'timestamp' => current_time('mysql')
            ], 500);
        }
    }
    
    /**
     * Get quick health status (lightweight)
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response REST response
     */
    public function get_quick_health_status($request) {
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
            
            // Use unified health manager for quick status
            if (class_exists('HSM_Health_Manager')) {
                $health_manager = HSM_Health_Manager::get_instance();
                $health_status = $health_manager->get_quick_health_status();
            } else {
                // Fallback to health monitor
                $health_status = $this->health_monitor->get_quick_health_status();
            }
            
            return new WP_REST_Response([
                'status' => 'success',
                'data' => $health_status
            ], 200);
            
        } catch (Exception $e) {
            $this->error_handler->log_error('Quick healthcheck request failed', $e);
            
            return new WP_REST_Response([
                'status' => 'error',
                'error' => 'Quick healthcheck request failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get health metrics
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response REST response
     */
    public function get_health_metrics($request) {
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
            
            // Get health metrics
            $metrics = $this->health_monitor->get_health_metrics();
            
            return new WP_REST_Response([
                'status' => 'success',
                'data' => $metrics
            ], 200);
            
        } catch (Exception $e) {
            $this->error_handler->log_error('Health metrics request failed', $e);
            
            return new WP_REST_Response([
                'status' => 'error',
                'error' => 'Health metrics request failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check rate limiting
     * 
     * @return bool True if request is allowed
     */
    private function check_rate_limit() {
        $client_ip = $this->get_client_ip();
        $rate_limit_key = 'hsm_graphql_healthcheck_rate_limit_' . md5($client_ip);
        $rate_limit_window = 60; // 1 minute
        $max_requests = 60; // 60 requests per minute (more lenient for health checks)
        
        $current_count = get_transient($rate_limit_key);
        
        if ($current_count === false) {
            set_transient($rate_limit_key, 1, $rate_limit_window);
            return true;
        }
        
        if ($current_count >= $max_requests) {
            return false;
        }
        
        set_transient($rate_limit_key, $current_count + 1, $rate_limit_window);
        return true;
    }
    
    /**
     * Validate request security
     * 
     * @param WP_REST_Request $request REST request object
     * @return bool True if request is secure
     */
    private function validate_request_security($request) {
        // Check for WordPress nonce
        $nonce = $request->get_header('X-WP-Nonce');
        if (!$nonce || !wp_verify_nonce($nonce, 'hsm_graphql_healthcheck')) {
            return false;
        }
        
        // Check for required headers
        $required_headers = ['Content-Type', 'User-Agent'];
        foreach ($required_headers as $header) {
            if (!$request->get_header($header)) {
                return false;
            }
        }
        
        // Check Content-Type
        $content_type = $request->get_header('Content-Type');
        if ($content_type !== 'application/json') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Measure response time
     * 
     * @return float Response time in milliseconds
     */
    private function measure_response_time() {
        $start_time = microtime(true);
        $end_time = microtime(true);
        return round(($end_time - $start_time) * 1000, 2);
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
     * Validate REST API permissions with nonce support
     * 
     * @param WP_REST_Request $request REST request object
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    public function validate_rest_permissions($request) {
        // Use unified permission manager
        if (class_exists('HSM_Permission_Manager')) {
            $permission_manager = HSM_Permission_Manager::get_instance();
            return $permission_manager->validate_rest_permission($request, 'rest');
        }
        
        // Fallback to basic permission check
        return true;
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
            'timestamp' => current_time('mysql')
        ];
        
        error_log('HSM GraphQL Healthcheck Security Validation Failed: ' . json_encode($log_data));
    }
    
    /**
     * Register healthcheck endpoints
     * 
     * @return void
     */
    public function register_endpoints() {
        // Main healthcheck endpoint
        register_rest_route('hsm-graphql/v1', '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'get_health_status'],
            'permission_callback' => [$this, 'validate_rest_permissions'],
            'args' => [
                'format' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Response format (full, quick, metrics)',
                    'default' => 'full'
                ]
            ]
        ]);
        
        // Quick healthcheck endpoint
        register_rest_route('hsm-graphql/v1', '/health/quick', [
            'methods' => 'GET',
            'callback' => [$this, 'get_quick_health_status'],
            'permission_callback' => [$this, 'validate_rest_permissions']
        ]);
        
        // Health metrics endpoint
        register_rest_route('hsm-graphql/v1', '/health/metrics', [
            'methods' => 'GET',
            'callback' => [$this, 'get_health_metrics'],
            'permission_callback' => [$this, 'validate_rest_permissions']
        ]);
    }
}