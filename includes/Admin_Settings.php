<?php
/**
 * Admin settings class
 *
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin settings management class
 */
class HSM_Admin_Settings {
    
    /**
     * Plugin instance
     *
     * @var HSM_Admin_Settings
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return HSM_Admin_Settings
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
     * Register settings
     */
    public function register_settings() {
        // Register settings group
        register_setting('hsm_settings_group', 'hsm_stripe_secret_key', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_stripe_secret_key'),
            'default' => ''
        ));
        
        register_setting('hsm_settings_group', 'hsm_stripe_publishable_key', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_stripe_publishable_key'),
            'default' => ''
        ));
        
        register_setting('hsm_settings_group', 'hsm_stripe_webhook_secret', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('hsm_settings_group', 'hsm_tax_fallback_rate', array(
            'type' => 'number',
            'sanitize_callback' => array($this, 'sanitize_tax_fallback_rate'),
            'default' => 13.0
        ));
        
        register_setting('hsm_settings_group', 'hsm_currency', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_currency'),
            'default' => 'USD'
        ));
        
        register_setting('hsm_settings_group', 'hsm_enable_logging', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ));
        
        register_setting('hsm_settings_group', 'hsm_enable_debug_mode', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
        
        // Login customization settings
        register_setting('hsm_settings_group', 'hsm_enable_login_customization', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ));
        
        register_setting('hsm_settings_group', 'hsm_login_logo_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        
        register_setting('hsm_settings_group', 'hsm_login_logo_width', array(
            'type' => 'integer',
            'sanitize_callback' => 'intval',
            'default' => 320
        ));
        
        register_setting('hsm_settings_group', 'hsm_login_logo_height', array(
            'type' => 'integer',
            'sanitize_callback' => 'intval',
            'default' => 84
        ));
        
        register_setting('hsm_settings_group', 'hsm_login_background_color', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#f0f0f1'
        ));
        
        register_setting('hsm_settings_group', 'hsm_login_button_color', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#2271b1'
        ));
        
        register_setting('hsm_settings_group', 'hsm_login_button_hover_color', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#135e96'
        ));
        
        // Google Form settings - consolidated from standalone settings page
        register_setting('hsm_settings_group', 'hsm_google_form_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        
        register_setting('hsm_settings_group', 'hsm_contact_form_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
    }
    
    /**
     * Sanitize Stripe secret key
     *
     * @param string $value
     * @return string
     */
    public function sanitize_stripe_secret_key($value) {
        $value = sanitize_text_field($value);
        
        if (!empty($value) && !preg_match('/^sk_(test_|live_)?[a-zA-Z0-9]{24,}$/', $value)) {
            add_settings_error(
                'hsm_stripe_secret_key',
                'invalid_stripe_secret_key',
                __('Invalid Stripe secret key format. Key should start with "sk_"', 'hsm')
            );
            return '';
        }
        
        return $value;
    }
    
    /**
     * Sanitize Stripe publishable key
     *
     * @param string $value
     * @return string
     */
    public function sanitize_stripe_publishable_key($value) {
        $value = sanitize_text_field($value);
        
        if (!empty($value) && !preg_match('/^pk_(test_|live_)?[a-zA-Z0-9]{24,}$/', $value)) {
            add_settings_error(
                'hsm_stripe_publishable_key',
                'invalid_stripe_publishable_key',
                __('Invalid Stripe publishable key format. Key should start with "pk_"', 'hsm')
            );
            return '';
        }
        
        return $value;
    }
    
    /**
     * Sanitize tax fallback rate
     *
     * @param mixed $value
     * @return float
     */
    public function sanitize_tax_fallback_rate($value) {
        $value = floatval($value);
        
        if ($value < 0 || $value > 100) {
            add_settings_error(
                'hsm_tax_fallback_rate',
                'invalid_tax_rate',
                __('Tax fallback rate must be between 0 and 100', 'hsm')
            );
            return 13.0;
        }
        
        return $value;
    }
    
    /**
     * Sanitize currency
     *
     * @param string $value
     * @return string
     */
    public function sanitize_currency($value) {
        $value = strtoupper(sanitize_text_field($value));
        
        $allowed_currencies = array('USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY');
        
        if (!in_array($value, $allowed_currencies)) {
            add_settings_error(
                'hsm_currency',
                'invalid_currency',
                __('Invalid currency code', 'hsm')
            );
            return 'USD';
        }
        
        return $value;
    }
    
    /**
     * Get settings errors
     *
     * @return array
     */
    public function get_settings_errors() {
        return get_settings_errors('hsm_settings_group');
    }
    
    /**
     * Clear settings errors
     */
    public function clear_settings_errors() {
        settings_errors('hsm_settings_group');
    }
}