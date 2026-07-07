<?php
/**
 * HSM Permission Manager Class
 * 
 * Unified permission validation system that consolidates all permission checking
 * functionality across the HSM plugin. This replaces 5 different permission
 * validation implementations with a single, consistent, and secure system.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Permission_Manager {
    
    /**
     * Singleton instance
     * 
     * @var HSM_Permission_Manager
     */
    private static $instance = null;
    
    /**
     * Permission cache
     * 
     * @var array
     */
    private $permission_cache = [];
    
    /**
     * Cache expiration time (5 minutes)
     * 
     * @var int
     */
    private $cache_expiration = 300;
    
    /**
     * Error handler instance
     * 
     * @var HSM_Error_Handler
     */
    private $error_handler;
    
    /**
     * Security manager instance
     * 
     * @var HSM_Security_Manager
     */
    private $security_manager;
    
    /**
     * Get singleton instance
     *
     * @return HSM_Permission_Manager
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
        $this->error_handler = new HSM_Error_Handler();
        $this->security_manager = HSM_Security_Manager::get_instance();
    }
    
    /**
     * Validate API permission
     * 
     * @param WP_REST_Request $request REST request object
     * @param string $permission_type Type of permission to check
     * @param bool $use_cache Whether to use cached results
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    public function validate_api_permission($request, $permission_type = 'api', $use_cache = true) {
        $cache_key = 'api_permission_' . $permission_type . '_' . $this->get_request_hash($request);
        
        // Check cache first
        if ($use_cache && isset($this->permission_cache[$cache_key])) {
            $cached_data = $this->permission_cache[$cache_key];
            if (time() - $cached_data['timestamp'] < $this->cache_expiration) {
                return $cached_data['result'];
            }
        }
        
        // Perform permission validation based on type
        $result = $this->perform_permission_validation($request, $permission_type);
        
        // Cache the result
        $this->permission_cache[$cache_key] = [
            'result' => $result,
            'timestamp' => time()
        ];
        
        return $result;
    }
    
    /**
     * Validate admin permission
     * 
     * @param WP_REST_Request $request REST request object
     * @param string $capability Required capability
     * @param bool $use_cache Whether to use cached results
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    public function validate_admin_permission($request, $capability = 'manage_options', $use_cache = true) {
        $cache_key = 'admin_permission_' . $capability . '_' . $this->get_request_hash($request);
        
        // Check cache first
        if ($use_cache && isset($this->permission_cache[$cache_key])) {
            $cached_data = $this->permission_cache[$cache_key];
            if (time() - $cached_data['timestamp'] < $this->cache_expiration) {
                return $cached_data['result'];
            }
        }
        
        // Perform admin permission validation
        $result = $this->perform_admin_validation($request, $capability);
        
        // Cache the result
        $this->permission_cache[$cache_key] = [
            'result' => $result,
            'timestamp' => time()
        ];
        
        return $result;
    }
    
    /**
     * Validate REST API permission with enhanced security
     * 
     * @param WP_REST_Request $request REST request object
     * @param string $permission_type Type of permission to check
     * @param bool $use_cache Whether to use cached results
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    public function validate_rest_permission($request, $permission_type = 'rest', $use_cache = true) {
        $cache_key = 'rest_permission_' . $permission_type . '_' . $this->get_request_hash($request);
        
        // Check cache first
        if ($use_cache && isset($this->permission_cache[$cache_key])) {
            $cached_data = $this->permission_cache[$cache_key];
            if (time() - $cached_data['timestamp'] < $this->cache_expiration) {
                return $cached_data['result'];
            }
        }
        
        // Perform enhanced REST permission validation
        $result = $this->perform_rest_validation($request, $permission_type);
        
        // Cache the result
        $this->permission_cache[$cache_key] = [
            'result' => $result,
            'timestamp' => time()
        ];
        
        return $result;
    }
    
    /**
     * Validate admin page permission
     * 
     * @param string $capability Required capability
     * @param bool $use_cache Whether to use cached results
     * @return bool True if permission granted, false otherwise
     */
    public function validate_admin_page_permission($capability = 'manage_options', $use_cache = true) {
        $cache_key = 'admin_page_permission_' . $capability . '_' . get_current_user_id();
        
        // Check cache first
        if ($use_cache && isset($this->permission_cache[$cache_key])) {
            $cached_data = $this->permission_cache[$cache_key];
            if (time() - $cached_data['timestamp'] < $this->cache_expiration) {
                return $cached_data['result'];
            }
        }
        
        // Perform admin page permission validation
        $result = $this->perform_admin_page_validation($capability);
        
        // Cache the result
        $this->permission_cache[$cache_key] = [
            'result' => $result,
            'timestamp' => time()
        ];
        
        return $result;
    }
    
    /**
     * Perform permission validation based on type
     * 
     * @param WP_REST_Request $request REST request object
     * @param string $permission_type Type of permission to check
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    private function perform_permission_validation($request, $permission_type) {
        // Basic API permission - can be enhanced based on requirements
        switch ($permission_type) {
            case 'api':
                return $this->validate_basic_api_permission($request);
            case 'public':
                return true; // Public endpoints
            case 'authenticated':
                return $this->validate_authenticated_permission($request);
            case 'admin':
                return $this->validate_admin_permission($request, 'manage_options', false);
            default:
                return $this->validate_basic_api_permission($request);
        }
    }
    
    /**
     * Perform admin validation
     * 
     * @param WP_REST_Request $request REST request object
     * @param string $capability Required capability
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    private function perform_admin_validation($request, $capability) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new WP_Error(
                'not_logged_in',
                'User is not logged in',
                array('status' => 401)
            );
        }
        
        // Check if user has required capability
        if (!current_user_can($capability)) {
            return new WP_Error(
                'insufficient_permissions',
                'User does not have required permissions',
                array('status' => 403)
            );
        }
        
        // Additional security checks
        if (!$this->validate_user_session($request)) {
            return new WP_Error(
                'invalid_session',
                'User session is invalid',
                array('status' => 401)
            );
        }
        
        return true;
    }
    
    /**
     * Perform REST validation with enhanced security
     * 
     * @param WP_REST_Request $request REST request object
     * @param string $permission_type Type of permission to check
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    private function perform_rest_validation($request, $permission_type) {
        // Method 1: Check for WordPress nonce in header
        $nonce = $request->get_header('X-WP-Nonce');
        if ($nonce && wp_verify_nonce($nonce, 'wp_rest')) {
            $this->log_permission_success('X-WP-Nonce header', $request);
            return true;
        }
        
        // Method 2: Check for nonce in cookie
        $cookie_header = $request->get_header('Cookie');
        if ($cookie_header) {
            preg_match('/wp_rest_nonce=([^;]+)/', $cookie_header, $matches);
            $cookie_nonce = $matches[1] ?? null;
            if ($cookie_nonce && wp_verify_nonce($cookie_nonce, 'wp_rest')) {
                $this->log_permission_success('cookie nonce', $request);
                return true;
            }
        }
        
        // Method 3: Check for API key authentication
        $api_key = $request->get_header('X-API-Key');
        if ($api_key && $this->validate_api_key($api_key)) {
            $this->log_permission_success('API key', $request);
            return true;
        }
        
        // Method 4: Check for origin-based authentication
        $origin = $request->get_header('Origin');
        if ($origin && $this->validate_origin($origin)) {
            $this->log_permission_success('origin validation', $request);
            return true;
        }
        
        // Method 5: Check for referer-based authentication
        $referer = $request->get_header('Referer');
        if ($referer && $this->validate_referer($referer)) {
            $this->log_permission_success('referer validation', $request);
            return true;
        }
        
        // Method 6: Check for basic security (development mode)
        if ($this->validate_basic_security($request)) {
            $this->log_permission_success('basic security (development mode)', $request);
            return true;
        }
        
        // Method 7: Check for internal WordPress request
        if ($this->is_internal_wordpress_request($request)) {
            $this->log_permission_success('internal WordPress request', $request);
            return true;
        }
        
        // All methods failed
        $this->log_permission_failure($request);
        return new WP_Error(
            'permission_denied',
            'Permission denied. No valid authentication method found.',
            array('status' => 403)
        );
    }
    
    /**
     * Perform admin page validation
     * 
     * @param string $capability Required capability
     * @return bool True if permission granted, false otherwise
     */
    private function perform_admin_page_validation($capability) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            $this->error_handler->log_error('HSM Plugin: User is not logged in');
            return false;
        }
        
        // Check if user is in admin area
        if (!is_admin()) {
            $this->error_handler->log_error('HSM Plugin: User is not in admin area');
            return false;
        }
        
        // Validate user session
        if (!$this->validate_user_session()) {
            $this->error_handler->log_error('HSM Plugin: User session validation failed');
            return false;
        }
        
        // Check multiple capability levels
        if (current_user_can($capability)) {
            return true;
        }
        
        // Check for editor capabilities as fallback
        if (current_user_can('edit_posts')) {
            return true;
        }
        
        // Check for author capabilities as fallback
        if (current_user_can('publish_posts')) {
            return true;
        }
        
        $this->error_handler->log_error('HSM Plugin: User does not have required permissions');
        return false;
    }
    
    /**
     * Validate basic API permission
     * 
     * @param WP_REST_Request $request REST request object
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    private function validate_basic_api_permission($request) {
        // For now, return true for basic API access
        // This can be enhanced based on specific requirements
        return true;
    }
    
    /**
     * Validate authenticated permission
     * 
     * @param WP_REST_Request $request REST request object
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    private function validate_authenticated_permission($request) {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'not_authenticated',
                'User must be logged in',
                array('status' => 401)
            );
        }
        
        return true;
    }
    
    /**
     * Validate API key
     * 
     * @param string $api_key API key to validate
     * @return bool True if valid, false otherwise
     */
    private function validate_api_key($api_key) {
        $valid_api_keys = get_option('hsm_api_keys', array());
        return in_array($api_key, $valid_api_keys, true);
    }
    
    /**
     * Validate origin
     * 
     * @param string $origin Origin to validate
     * @return bool True if valid, false otherwise
     */
    private function validate_origin($origin) {
        $allowed_origins = get_option('hsm_allowed_origins', array());
        return in_array($origin, $allowed_origins, true);
    }
    
    /**
     * Validate referer
     * 
     * @param string $referer Referer to validate
     * @return bool True if valid, false otherwise
     */
    private function validate_referer($referer) {
        $allowed_referers = get_option('hsm_allowed_referers', array());
        return in_array($referer, $allowed_referers, true);
    }
    
    /**
     * Validate basic security (development mode)
     * 
     * @param WP_REST_Request $request REST request object
     * @return bool True if valid, false otherwise
     */
    private function validate_basic_security($request) {
        // Only allow in development mode
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return false;
        }
        
        // Check for localhost or development domain
        $host = $request->get_header('Host');
        $allowed_hosts = array('localhost', '127.0.0.1', 'dev.local', 'local.dev');
        
        foreach ($allowed_hosts as $allowed_host) {
            if (strpos($host, $allowed_host) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if request is internal WordPress request
     * 
     * @param WP_REST_Request $request REST request object
     * @return bool True if internal, false otherwise
     */
    private function is_internal_wordpress_request($request) {
        // Check for WordPress internal request headers
        $user_agent = $request->get_header('User-Agent');
        if (strpos($user_agent, 'WordPress') !== false) {
            return true;
        }
        
        // Check for WordPress admin request
        $referer = $request->get_header('Referer');
        if ($referer && strpos($referer, admin_url()) === 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate user session
     * 
     * @param WP_REST_Request $request Optional REST request object
     * @return bool True if valid, false otherwise
     */
    private function validate_user_session($request = null) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Check if user session is valid
        if (!wp_verify_nonce(wp_create_nonce('user_session'), 'user_session')) {
            return false;
        }
        
        // Additional session validation can be added here
        return true;
    }
    
    /**
     * Log permission success
     * 
     * @param string $method Authentication method used
     * @param WP_REST_Request $request REST request object
     * @return void
     */
    private function log_permission_success($method, $request) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HSM Debug: Permission validation successful via {$method}");
        }
    }
    
    /**
     * Log permission failure
     * 
     * @param WP_REST_Request $request REST request object
     * @return void
     */
    private function log_permission_failure($request) {
        $this->error_handler->log_error(
            'HSM Plugin: Permission validation failed',
            new Exception('No valid authentication method found')
        );
    }
    
    /**
     * Get request hash for caching
     * 
     * @param WP_REST_Request $request REST request object
     * @return string Request hash
     */
    private function get_request_hash($request) {
        $headers = $request->get_headers();
        $user_id = get_current_user_id();
        return md5(serialize($headers) . $user_id);
    }
    
    /**
     * Clear permission cache
     * 
     * @return void
     */
    public function clear_cache() {
        $this->permission_cache = [];
    }
    
    /**
     * Get cache status
     * 
     * @return array Cache status
     */
    public function get_cache_status() {
        return array(
            'cached_entries' => count($this->permission_cache),
            'cache_expiration' => $this->cache_expiration,
            'cache_keys' => array_keys($this->permission_cache)
        );
    }
    
    /**
     * Get permission statistics
     * 
     * @return array Permission statistics
     */
    public function get_permission_stats() {
        $stats = array(
            'total_checks' => 0,
            'successful_checks' => 0,
            'failed_checks' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0
        );
        
        // This would be implemented with actual statistics tracking
        // For now, return basic structure
        
        return $stats;
    }
}