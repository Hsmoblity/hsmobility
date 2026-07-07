<?php
/**
 * HSM Configuration Manager Class
 * 
 * Manages plugin configuration and settings
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Config_Manager {
    
    /**
     * Plugin version
     * 
     * @var string
     */
    private $version = '1.0.0';
    
    /**
     * Configuration options
     * 
     * @var array
     */
    private $options = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_options();
    }
    
    /**
     * Load configuration options
     * 
     * @return void
     */
    private function load_options() {
        $this->options = [
            'hsm_stripe_secret_key' => get_option('hsm_stripe_secret_key', ''),
            'hsm_stripe_publishable_key' => get_option('hsm_stripe_publishable_key', ''),
            'hsm_stripe_webhook_secret' => get_option('hsm_stripe_webhook_secret', ''),
            'hsm_stripe_environment' => get_option('hsm_stripe_environment', 'test'),
            'hsm_email_from' => get_option('hsm_email_from', get_option('admin_email')),
            'hsm_email_from_name' => get_option('hsm_email_from_name', get_bloginfo('name')),
            'hsm_email_reply_to' => get_option('hsm_email_reply_to', get_option('admin_email'))
        ];
    }
    
    /**
     * Get option value
     * 
     * @param string $key Option key
     * @param mixed $default Default value
     * @return mixed Option value
     */
    public function get_option($key, $default = null) {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }
    
    /**
     * Set option value
     * 
     * @param string $key Option key
     * @param mixed $value Option value
     * @return bool True if option was updated
     */
    public function set_option($key, $value) {
        $this->options[$key] = $value;
        return update_option($key, $value);
    }
    
    /**
     * Get Stripe secret key
     * 
     * @return string Stripe secret key
     */
    public function get_stripe_secret_key() {
        return $this->get_option('hsm_stripe_secret_key', '');
    }
    
    /**
     * Get Stripe publishable key
     * 
     * @return string Stripe publishable key
     */
    public function get_stripe_publishable_key() {
        return $this->get_option('hsm_stripe_publishable_key', '');
    }
    
    /**
     * Get Stripe webhook secret
     * 
     * @return string Stripe webhook secret
     */
    public function get_stripe_webhook_secret() {
        return $this->get_option('hsm_stripe_webhook_secret', '');
    }
    
    /**
     * Get Stripe environment
     * 
     * @return string Stripe environment (test/live)
     */
    public function get_stripe_environment() {
        return $this->get_option('hsm_stripe_environment', 'test');
    }
    
    /**
     * Get email from address
     * 
     * @return string Email from address
     */
    public function get_email_from() {
        return $this->get_option('hsm_email_from', get_option('admin_email'));
    }
    
    /**
     * Get email from name
     * 
     * @return string Email from name
     */
    public function get_email_from_name() {
        return $this->get_option('hsm_email_from_name', get_bloginfo('name'));
    }
    
    /**
     * Get email reply-to address
     * 
     * @return string Email reply-to address
     */
    public function get_email_reply_to() {
        return $this->get_option('hsm_email_reply_to', get_option('admin_email'));
    }
    
    /**
     * Migrate options from old naming convention
     * 
     * @return void
     */
    public function migrate_options() {
        $migrations = [
            'hsm_stripe_secret_key' => 'hsm_stripe_secret_key',
            'hsm_stripe_publishable_key' => 'hsm_stripe_publishable_key',
            'hsm_stripe_webhook_secret' => 'hsm_stripe_webhook_secret',
            'hsm_stripe_environment' => 'hsm_stripe_environment'
        ];
        
        foreach ($migrations as $new_key => $old_key) {
            $old_value = get_option($old_key);
            if ($old_value !== false && !get_option($new_key)) {
                update_option($new_key, $old_value);
                delete_option($old_key);
            }
        }
    }
    
    /**
     * Get plugin version
     * 
     * @return string Plugin version
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Check if Stripe is configured
     * 
     * @return bool True if Stripe is configured
     */
    public function is_stripe_configured() {
        return !empty($this->get_stripe_secret_key()) && !empty($this->get_stripe_publishable_key());
    }
    
    /**
     * Get all configuration options
     * 
     * @return array All configuration options
     */
    public function get_all_options() {
        return $this->options;
    }
}