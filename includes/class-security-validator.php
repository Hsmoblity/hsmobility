<?php
/**
 * HSM Security Validator Class
 * 
 * Unified security validation system that consolidates all security
 * validation patterns across the HSM plugin. This provides consistent
 * security checks and validation throughout the application.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Security_Validator {
    
    /**
     * Singleton instance
     * 
     * @var HSM_Security_Validator
     */
    private static $instance = null;
    
    /**
     * Security manager instance
     * 
     * @var HSM_Security_Manager
     */
    private $security_manager;
    
    /**
     * Error handler instance
     * 
     * @var HSM_Error_Handler
     */
    private $error_handler;
    
    /**
     * Rate limiting cache
     * 
     * @var array
     */
    private $rate_limit_cache = [];
    
    /**
     * Security validation statistics
     * 
     * @var array
     */
    private $validation_stats = [
        'total_validations' => 0,
        'successful_validations' => 0,
        'failed_validations' => 0,
        'rate_limit_hits' => 0,
        'nonce_failures' => 0,
        'api_key_failures' => 0,
        'origin_failures' => 0
    ];
    
    /**
     * Get singleton instance
     *
     * @return HSM_Security_Validator
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
        $this->security_manager = HSM_Security_Manager::get_instance();
        $this->error_handler = new HSM_Error_Handler();
    }
    
    /**
     * Validate request security comprehensively
     * 
     * @param WP_REST_Request $request REST request object
     * @param string $validation_type Type of validation to perform
     * @param array $options Validation options
     * @return bool|WP_Error True if valid, WP_Error otherwise
     */
    public function validate_request_security($request, $validation_type = 'standard', $options = []) {
        $this->validation_stats['total_validations']++;
        
        try {
            // Rate limiting check
            if (!$this->check_rate_limit($request, $options)) {
                $this->validation_stats['rate_limit_hits']++;
                return new WP_Error(
                    'rate_limit_exceeded',
                    'Rate limit exceeded. Please try again later.',
                    array('status' => 429)
                );
            }
            
            // Nonce validation
            if (!$this->validate_nonce($request, $options)) {
                $this->validation_stats['nonce_failures']++;
                return new WP_Error(
                    'invalid_nonce',
                    'Invalid or missing nonce.',
                    array('status' => 403)
                );
            }
            
            // API key validation (if required)
            if (isset($options['require_api_key']) && $options['require_api_key']) {
                if (!$this->validate_api_key($request, $options)) {
                    $this->validation_stats['api_key_failures']++;
                    return new WP_Error(
                        'invalid_api_key',
                        'Invalid or missing API key.',
                        array('status' => 403)
                    );
                }
            }
            
            // Origin validation (if required)
            if (isset($options['require_origin_validation']) && $options['require_origin_validation']) {
                if (!$this->validate_origin($request, $options)) {
                    $this->validation_stats['origin_failures']++;
                    return new WP_Error(
                        'invalid_origin',
                        'Invalid or missing origin.',
                        array('status' => 403)
                    );
                }
            }
            
            // Additional security checks based on validation type
            switch ($validation_type) {
                case 'strict':
                    if (!$this->validate_strict_security($request, $options)) {
                        return new WP_Error(
                            'strict_security_failed',
                            'Strict security validation failed.',
                            array('status' => 403)
                        );
                    }
                    break;
                    
                case 'development':
                    if (!$this->validate_development_security($request, $options)) {
                        return new WP_Error(
                            'development_security_failed',
                            'Development security validation failed.',
                            array('status' => 403)
                        );
                    }
                    break;
                    
                case 'public':
                    // Minimal validation for public endpoints
                    break;
                    
                default:
                    // Standard validation
                    if (!$this->validate_standard_security($request, $options)) {
                        return new WP_Error(
                            'standard_security_failed',
                            'Standard security validation failed.',
                            array('status' => 403)
                        );
                    }
                    break;
            }
            
            $this->validation_stats['successful_validations']++;
            return true;
            
        } catch (Exception $e) {
            $this->validation_stats['failed_validations']++;
            $this->error_handler->log_error(
                'Security validation failed: ' . $e->getMessage(),
                $e
            );
            
            return new WP_Error(
                'security_validation_error',
                'Security validation error occurred.',
                array('status' => 500)
            );
        }
    }
    
    /**
     * Check rate limiting
     * 
     * @param WP_REST_Request $request REST request object
     * @param array $options Validation options
     * @return bool True if within rate limit, false otherwise
     */
    private function check_rate_limit($request, $options = []) {
        $client_ip = $this->get_client_ip($request);
        $rate_limit_key = 'hsm_security_rate_limit_' . md5($client_ip);
        
        // Get rate limit settings
        $rate_limit_window = $options['rate_limit_window'] ?? 60; // 1 minute
        $max_requests = $options['max_requests'] ?? 100; // 100 requests per minute
        
        // Check current count
        $current_count = get_transient($rate_limit_key);
        if ($current_count === false) {
            $current_count = 0;
        }
        
        // Check if limit exceeded
        if ($current_count >= $max_requests) {
            return false;
        }
        
        // Increment counter
        set_transient($rate_limit_key, $current_count + 1, $rate_limit_window);
        
        return true;
    }
    
    /**
     * Validate nonce
     * 
     * @param WP_REST_Request $request REST request object
     * @param array $options Validation options
     * @return bool True if nonce is valid, false otherwise
     */
    private function validate_nonce($request, $options = []) {
        $nonce_action = $options['nonce_action'] ?? 'wp_rest';
        $nonce_header = $options['nonce_header'] ?? 'X-WP-Nonce';
        
        // Check for nonce in header
        $nonce = $request->get_header($nonce_header);
        if ($nonce && wp_verify_nonce($nonce, $nonce_action)) {
            return true;
        }
        
        // Check for nonce in cookie
        $cookie_header = $request->get_header('Cookie');
        if ($cookie_header) {
            preg_match('/wp_rest_nonce=([^;]+)/', $cookie_header, $matches);
            $cookie_nonce = $matches[1] ?? null;
            if ($cookie_nonce && wp_verify_nonce($cookie_nonce, $nonce_action)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate API key
     * 
     * @param WP_REST_Request $request REST request object
     * @param array $options Validation options
     * @return bool True if API key is valid, false otherwise
     */
    private function validate_api_key($request, $options = []) {
        $api_key_header = $options['api_key_header'] ?? 'X-API-Key';
        $api_key = $request->get_header($api_key_header);
        
        if (!$api_key) {
            return false;
        }
        
        // Get valid API keys
        $valid_api_keys = get_option('hsm_api_keys', []);
        if (empty($valid_api_keys)) {
            return false;
        }
        
        return in_array($api_key, $valid_api_keys, true);
    }
    
    /**
     * Validate origin
     * 
     * @param WP_REST_Request $request REST request object
     * @param array $options Validation options
     * @return bool True if origin is valid, false otherwise
     */
    private function validate_origin($request, $options = []) {
        $origin = $request->get_header('Origin');
        if (!$origin) {
            return false;
        }
        
        // Get allowed origins
        $allowed_origins = $options['allowed_origins'] ?? get_option('hsm_allowed_origins', []);
        if (empty($allowed_origins)) {
            return false;
        }
        
        // Check if origin is allowed
        return in_array($origin, $allowed_origins, true);
    }
    
    /**
     * Validate standard security
     * 
     * @param WP_REST_Request $request REST request object
     * @param array $options Validation options
     * @return bool True if security checks pass, false otherwise
     */
    private function validate_standard_security($request, $options = []) {
        // Check for required headers
        $required_headers = $options['required_headers'] ?? ['User-Agent'];
        foreach ($required_headers as $header) {
            if (!$request->get_header($header)) {
                return false;
            }
        }
        
        // Check for suspicious patterns
        $user_agent = $request->get_header('User-Agent');
        if ($user_agent && $this->is_suspicious_user_agent($user_agent)) {
            return false;
        }
        
        // Check for SQL injection patterns
        $params = $request->get_params();
        if ($this->contains_sql_injection($params)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate strict security
     * 
     * @param WP_REST_Request $request REST request object
     * @param array $options Validation options
     * @return bool True if security checks pass, false otherwise
     */
    private function validate_strict_security($request, $options = []) {
        // Standard security checks
        if (!$this->validate_standard_security($request, $options)) {
            return false;
        }
        
        // Additional strict checks
        $client_ip = $this->get_client_ip($request);
        
        // Check if IP is blocked
        if ($this->is_ip_blocked($client_ip)) {
            return false;
        }
        
        // Check for XSS patterns
        $params = $request->get_params();
        if ($this->contains_xss($params)) {
            return false;
        }
        
        // Check for CSRF patterns
        if (!$this->validate_csrf_protection($request, $options)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate development security
     * 
     * @param WP_REST_Request $request REST request object
     * @param array $options Validation options
     * @return bool True if security checks pass, false otherwise
     */
    private function validate_development_security($request, $options = []) {
        // Only allow in development mode
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return false;
        }
        
        $client_ip = $this->get_client_ip($request);
        $allowed_ips = $options['allowed_ips'] ?? ['127.0.0.1', '::1', 'localhost'];
        
        // Check if IP is in allowed list
        foreach ($allowed_ips as $allowed_ip) {
            if (strpos($client_ip, $allowed_ip) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user agent is suspicious
     * 
     * @param string $user_agent User agent string
     * @return bool True if suspicious, false otherwise
     */
    private function is_suspicious_user_agent($user_agent) {
        $suspicious_patterns = [
            'sqlmap',
            'nikto',
            'nmap',
            'masscan',
            'zap',
            'burp',
            'w3af',
            'acunetix',
            'nessus',
            'openvas'
        ];
        
        $user_agent_lower = strtolower($user_agent);
        foreach ($suspicious_patterns as $pattern) {
            if (strpos($user_agent_lower, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if parameters contain SQL injection patterns
     * 
     * @param array $params Request parameters
     * @return bool True if SQL injection detected, false otherwise
     */
    private function contains_sql_injection($params) {
        $sql_patterns = [
            'union.*select',
            'drop.*table',
            'delete.*from',
            'insert.*into',
            'update.*set',
            'alter.*table',
            'create.*table',
            'exec.*\(',
            'script.*>',
            'javascript:',
            'vbscript:',
            'onload=',
            'onerror='
        ];
        
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                foreach ($sql_patterns as $pattern) {
                    if (preg_match('/' . $pattern . '/i', $value)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if parameters contain XSS patterns
     * 
     * @param array $params Request parameters
     * @return bool True if XSS detected, false otherwise
     */
    private function contains_xss($params) {
        $xss_patterns = [
            '<script',
            'javascript:',
            'vbscript:',
            'onload=',
            'onerror=',
            'onclick=',
            'onmouseover=',
            'onfocus=',
            'onblur=',
            'onchange=',
            'onsubmit=',
            'onreset=',
            'onselect=',
            'onkeydown=',
            'onkeyup=',
            'onkeypress='
        ];
        
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                foreach ($xss_patterns as $pattern) {
                    if (stripos($value, $pattern) !== false) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is blocked
     * 
     * @param string $ip IP address
     * @return bool True if blocked, false otherwise
     */
    private function is_ip_blocked($ip) {
        $blocked_ips = get_option('hsm_blocked_ips', []);
        return in_array($ip, $blocked_ips, true);
    }
    
    /**
     * Validate CSRF protection
     * 
     * @param WP_REST_Request $request REST request object
     * @param array $options Validation options
     * @return bool True if CSRF protection is valid, false otherwise
     */
    private function validate_csrf_protection($request, $options = []) {
        // Check for CSRF token in header
        $csrf_token = $request->get_header('X-CSRF-Token');
        if ($csrf_token) {
            $expected_token = wp_create_nonce('hsm_csrf_token');
            return hash_equals($expected_token, $csrf_token);
        }
        
        // Check for referer validation
        $referer = $request->get_header('Referer');
        if ($referer) {
            $allowed_referers = $options['allowed_referers'] ?? [home_url()];
            foreach ($allowed_referers as $allowed_referer) {
                if (strpos($referer, $allowed_referer) === 0) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get client IP address
     * 
     * @param WP_REST_Request $request REST request object
     * @return string Client IP address
     */
    private function get_client_ip($request) {
        // Check for IP in various headers
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs (take the first one)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get validation statistics
     * 
     * @return array Validation statistics
     */
    public function get_validation_stats() {
        return $this->validation_stats;
    }
    
    /**
     * Reset validation statistics
     * 
     * @return void
     */
    public function reset_stats() {
        $this->validation_stats = [
            'total_validations' => 0,
            'successful_validations' => 0,
            'failed_validations' => 0,
            'rate_limit_hits' => 0,
            'nonce_failures' => 0,
            'api_key_failures' => 0,
            'origin_failures' => 0
        ];
    }
    
    /**
     * Get success rate
     * 
     * @return float Success rate as percentage
     */
    public function get_success_rate() {
        if ($this->validation_stats['total_validations'] === 0) {
            return 0.0;
        }
        
        return round(
            ($this->validation_stats['successful_validations'] / $this->validation_stats['total_validations']) * 100,
            2
        );
    }
    
    /**
     * Get failure rate
     * 
     * @return float Failure rate as percentage
     */
    public function get_failure_rate() {
        if ($this->validation_stats['total_validations'] === 0) {
            return 0.0;
        }
        
        return round(
            ($this->validation_stats['failed_validations'] / $this->validation_stats['total_validations']) * 100,
            2
        );
    }
}