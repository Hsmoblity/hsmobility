<?php
/**
 * Security management class
 *
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security management class
 */
class HSM_Security {
    
    /**
     * Plugin instance
     *
     * @var HSM_Security
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return HSM_Security
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
     * Verify nonce
     *
     * @param string $nonce Nonce value
     * @param string $action Nonce action
     * @return bool
     */
    public function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }
    
    /**
     * Create nonce
     *
     * @param string $action Nonce action
     * @return string
     */
    public function create_nonce($action) {
        return wp_create_nonce($action);
    }
    
    /**
     * Check user capability
     *
     * @param string $capability Required capability
     * @return bool
     */
    public function check_capability($capability) {
        return current_user_can($capability);
    }
    
    /**
     * Sanitize input
     *
     * @param mixed $input Input to sanitize
     * @param string $type Sanitization type
     * @return mixed
     */
    public function sanitize_input($input, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($input);
            case 'url':
                return esc_url_raw($input);
            case 'int':
                return intval($input);
            case 'float':
                return floatval($input);
            case 'textarea':
                return sanitize_textarea_field($input);
            default:
                return sanitize_text_field($input);
        }
    }
}