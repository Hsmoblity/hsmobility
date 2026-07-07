<?php
/**
 * Rate limiter class
 *
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rate limiter class
 */
class HSM_Rate_Limiter {
    
    /**
     * Plugin instance
     *
     * @var HSM_Rate_Limiter
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return HSM_Rate_Limiter
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
        // Constructor is private for singleton pattern
    }
    
    /**
     * Check rate limit
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public static function check_rate_limit($request) {
        $rate_limit = HSM_Options::get('api_rate_limit', 100); // requests per minute
        $ip_address = self::get_client_ip($request);
        $cache_key = 'hsm_rate_limit_' . md5($ip_address);
        
        // Get current count
        $current_count = get_transient($cache_key);
        
        if ($current_count === false) {
            // First request in this minute
            set_transient($cache_key, 1, MINUTE_IN_SECONDS);
            return true;
        }
        
        if ($current_count >= $rate_limit) {
            // Rate limit exceeded
            return false;
        }
        
        // Increment counter
        set_transient($cache_key, $current_count + 1, MINUTE_IN_SECONDS);
        return true;
    }
    
    /**
     * Get client IP address
     *
     * @param WP_REST_Request $request
     * @return string
     */
    private static function get_client_ip($request) {
        // Check for forwarded IP first
        $forwarded_ip = $request->get_header('X-Forwarded-For');
        if ($forwarded_ip) {
            $ips = explode(',', $forwarded_ip);
            return trim($ips[0]);
        }
        
        // Check for real IP
        $real_ip = $request->get_header('X-Real-IP');
        if ($real_ip) {
            return $real_ip;
        }
        
        // Fallback to server IP
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Clear rate limit for IP
     *
     * @param string $ip_address
     * @return bool
     */
    public static function clear_rate_limit($ip_address) {
        $cache_key = 'hsm_rate_limit_' . md5($ip_address);
        return delete_transient($cache_key);
    }
    
    /**
     * Get rate limit status for IP
     *
     * @param string $ip_address
     * @return array
     */
    public static function get_rate_limit_status($ip_address) {
        $rate_limit = HSM_Options::get('api_rate_limit', 100);
        $cache_key = 'hsm_rate_limit_' . md5($ip_address);
        $current_count = get_transient($cache_key);
        
        return array(
            'current_count' => $current_count ?: 0,
            'rate_limit' => $rate_limit,
            'remaining' => max(0, $rate_limit - ($current_count ?: 0)),
            'reset_time' => time() + MINUTE_IN_SECONDS
        );
    }
}