<?php
/**
 * CORS management class
 *
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CORS management class
 */
class HSM_CORS {
    
    /**
     * Plugin instance
     *
     * @var HSM_CORS
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return HSM_CORS
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
     * Check if origin is allowed
     *
     * @param string $origin
     * @return bool
     */
    public static function is_allowed_origin($origin) {
        if (!HSM_Options::get('enable_cors')) {
            return false;
        }
        
        $allowed_origins = HSM_Options::get('cors_origins', array());
        
        if (empty($allowed_origins)) {
            // If no specific origins configured, allow all
            return true;
        }
        
        return in_array($origin, $allowed_origins);
    }
    
    /**
     * Get allowed origins
     *
     * @return array
     */
    public static function get_allowed_origins() {
        return HSM_Options::get('cors_origins', array());
    }
    
    /**
     * Add allowed origin
     *
     * @param string $origin
     * @return bool
     */
    public static function add_allowed_origin($origin) {
        $origins = self::get_allowed_origins();
        
        if (!in_array($origin, $origins)) {
            $origins[] = $origin;
            return HSM_Options::set('cors_origins', $origins);
        }
        
        return true;
    }
    
    /**
     * Remove allowed origin
     *
     * @param string $origin
     * @return bool
     */
    public static function remove_allowed_origin($origin) {
        $origins = self::get_allowed_origins();
        $origins = array_filter($origins, function($o) use ($origin) {
            return $o !== $origin;
        });
        
        return HSM_Options::set('cors_origins', array_values($origins));
    }
    
    /**
     * Validate origin URL
     *
     * @param string $origin
     * @return bool
     */
    public static function validate_origin($origin) {
        return filter_var($origin, FILTER_VALIDATE_URL) !== false;
    }
}