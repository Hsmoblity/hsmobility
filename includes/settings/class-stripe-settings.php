<?php
/**
 * HSM Stripe Settings Class
 * 
 * Manages Stripe-related settings
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Stripe_Settings extends HSM_Settings_Base {
    
    /**
     * Default Stripe settings
     *
     * @var array
     */
    protected $defaults = [
        'secret_key' => '',
        'publishable_key' => '',
        'webhook_secret' => '',
        'environment' => 'test',
        'debug_mode' => false,
        'migration_completed' => false
    ];
    
    /**
     * Get Stripe secret key
     *
     * @return string
     */
    public function get_secret_key() {
        return $this->get_setting('secret_key');
    }
    
    /**
     * Set Stripe secret key
     *
     * @param string $key Secret key
     * @return bool
     */
    public function set_secret_key($key) {
        return $this->set_setting('secret_key', $key);
    }
    
    /**
     * Get Stripe publishable key
     *
     * @return string
     */
    public function get_publishable_key() {
        return $this->get_setting('publishable_key');
    }
    
    /**
     * Set Stripe publishable key
     *
     * @param string $key Publishable key
     * @return bool
     */
    public function set_publishable_key($key) {
        return $this->set_setting('publishable_key', $key);
    }
    
    /**
     * Get webhook secret
     *
     * @return string
     */
    public function get_webhook_secret() {
        return $this->get_setting('webhook_secret');
    }
    
    /**
     * Set webhook secret
     *
     * @param string $secret Webhook secret
     * @return bool
     */
    public function set_webhook_secret($secret) {
        return $this->set_setting('webhook_secret', $secret);
    }
    
    /**
     * Get environment
     *
     * @return string
     */
    public function get_environment() {
        return $this->get_setting('environment', 'test');
    }
    
    /**
     * Set environment
     *
     * @param string $environment Environment (test or live)
     * @return bool
     */
    public function set_environment($environment) {
        return $this->set_setting('environment', $environment);
    }
    
    /**
     * Is debug mode enabled
     *
     * @return bool
     */
    public function is_debug_mode() {
        return (bool) $this->get_setting('debug_mode', false);
    }
    
    /**
     * Set debug mode
     *
     * @param bool $enabled Debug mode enabled
     * @return bool
     */
    public function set_debug_mode($enabled) {
        return $this->set_setting('debug_mode', $enabled);
    }
    
    /**
     * Is migration completed
     *
     * @return bool
     */
    public function is_migration_completed() {
        return (bool) $this->get_setting('migration_completed', false);
    }
    
    /**
     * Set migration completed
     *
     * @param bool $completed Migration completed
     * @return bool
     */
    public function set_migration_completed($completed) {
        return $this->set_setting('migration_completed', $completed);
    }
    
    /**
     * Validate Stripe secret key
     *
     * @param string $key Secret key
     * @return bool|WP_Error
     */
    public function validate_secret_key($key) {
        if (empty($key)) {
            return new WP_Error('empty_key', 'Secret key cannot be empty');
        }
        
        if (!preg_match('/^sk_(test_|live_)[a-zA-Z0-9]{24,}$/', $key)) {
            return new WP_Error('invalid_key', 'Invalid Stripe secret key format');
        }
        
        return true;
    }
    
    /**
     * Validate publishable key
     *
     * @param string $key Publishable key
     * @return bool|WP_Error
     */
    public function validate_publishable_key($key) {
        if (empty($key)) {
            return new WP_Error('empty_key', 'Publishable key cannot be empty');
        }
        
        if (!preg_match('/^pk_(test_|live_)[a-zA-Z0-9]{24,}$/', $key)) {
            return new WP_Error('invalid_key', 'Invalid Stripe publishable key format');
        }
        
        return true;
    }
    
    /**
     * Validate environment
     *
     * @param string $environment Environment
     * @return bool|WP_Error
     */
    public function validate_environment($environment) {
        if (!in_array($environment, ['test', 'live'])) {
            return new WP_Error('invalid_environment', 'Environment must be either "test" or "live"');
        }
        
        return true;
    }
    
    /**
     * Sanitize secret key
     *
     * @param string $key Secret key
     * @return string
     */
    public function sanitize_secret_key($key) {
        return sanitize_text_field($key);
    }
    
    /**
     * Sanitize publishable key
     *
     * @param string $key Publishable key
     * @return string
     */
    public function sanitize_publishable_key($key) {
        return sanitize_text_field($key);
    }
    
    /**
     * Sanitize webhook secret
     *
     * @param string $secret Webhook secret
     * @return string
     */
    public function sanitize_webhook_secret($secret) {
        return sanitize_text_field($secret);
    }
    
    /**
     * Sanitize environment
     *
     * @param string $environment Environment
     * @return string
     */
    public function sanitize_environment($environment) {
        return sanitize_text_field($environment);
    }
}