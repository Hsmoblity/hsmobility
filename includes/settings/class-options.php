<?php
/**
 * HSM Options Class
 * 
 * Provides centralized options management for the HSM plugin.
 * Handles plugin settings, configuration, and option persistence.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Options {
    
    /**
     * Singleton instance
     * 
     * @var HSM_Options
     */
    private static $instance = null;
    
    /**
     * Options cache
     * 
     * @var array
     */
    private $options_cache = array();
    
    /**
     * Option prefix
     * 
     * @var string
     */
    private $option_prefix = 'hsm_';
    
    /**
     * Get singleton instance
     *
     * @return HSM_Options
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
        $this->load_default_options();
    }
    
    /**
     * Load default options
     */
    private function load_default_options() {
        $this->options_cache = array(
            'stripe_secret_key' => '',
            'stripe_publishable_key' => '',
            'stripe_webhook_secret' => '',
            'debug_mode' => false,
            'log_level' => 'info',
            'cache_ttl' => 300,
            'memory_threshold' => 80,
            'database_timeout' => 30,
            'http_timeout' => 10,
            'max_retries' => 3,
            'circuit_breaker_threshold' => 5,
            'log_rotation_size' => 10485760, // 10MB
            'log_max_files' => 5,
            'enable_caching' => true,
            'enable_memory_monitoring' => true,
            'enable_database_optimization' => true,
            'enable_http_optimization' => true,
            'enable_log_rotation' => true
        );
    }
    
    /**
     * Get option value
     * 
     * @param string $key Option key
     * @param mixed $default Default value
     * @return mixed Option value
     */
    public function get($key, $default = null) {
        $full_key = $this->option_prefix . $key;
        
        // Check cache first
        if (isset($this->options_cache[$key])) {
            return $this->options_cache[$key];
        }
        
        // Get from database
        $value = get_option($full_key, $default);
        
        // Cache the value
        $this->options_cache[$key] = $value;
        
        return $value;
    }
    
    /**
     * Set option value
     * 
     * @param string $key Option key
     * @param mixed $value Option value
     * @return bool Success status
     */
    public function set($key, $value) {
        $full_key = $this->option_prefix . $key;
        
        // Update database
        $success = update_option($full_key, $value);
        
        if ($success) {
            // Update cache
            $this->options_cache[$key] = $value;
        }
        
        return $success;
    }
    
    /**
     * Delete option
     * 
     * @param string $key Option key
     * @return bool Success status
     */
    public function delete($key) {
        $full_key = $this->option_prefix . $key;
        
        // Delete from database
        $success = delete_option($full_key);
        
        if ($success) {
            // Remove from cache
            unset($this->options_cache[$key]);
        }
        
        return $success;
    }
    
    /**
     * Get all options
     * 
     * @return array All options
     */
    public function get_all() {
        return $this->options_cache;
    }
    
    /**
     * Set multiple options
     * 
     * @param array $options Options array
     * @return bool Success status
     */
    public function set_multiple($options) {
        $success = true;
        
        foreach ($options as $key => $value) {
            if (!$this->set($key, $value)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Clear options cache
     */
    public function clear_cache() {
        $this->options_cache = array();
        $this->load_default_options();
    }
    
    /**
     * Get Stripe settings
     * 
     * @return array Stripe settings
     */
    public function get_stripe_settings() {
        return array(
            'secret_key' => $this->get('stripe_secret_key'),
            'publishable_key' => $this->get('stripe_publishable_key'),
            'webhook_secret' => $this->get('stripe_webhook_secret')
        );
    }
    
    /**
     * Set Stripe settings
     * 
     * @param array $settings Stripe settings
     * @return bool Success status
     */
    public function set_stripe_settings($settings) {
        return $this->set_multiple(array(
            'stripe_secret_key' => $settings['secret_key'] ?? '',
            'stripe_publishable_key' => $settings['publishable_key'] ?? '',
            'stripe_webhook_secret' => $settings['webhook_secret'] ?? ''
        ));
    }
    
    /**
     * Get debug settings
     * 
     * @return array Debug settings
     */
    public function get_debug_settings() {
        return array(
            'debug_mode' => $this->get('debug_mode', false),
            'log_level' => $this->get('log_level', 'info')
        );
    }
    
    /**
     * Set debug settings
     * 
     * @param array $settings Debug settings
     * @return bool Success status
     */
    public function set_debug_settings($settings) {
        return $this->set_multiple(array(
            'debug_mode' => $settings['debug_mode'] ?? false,
            'log_level' => $settings['log_level'] ?? 'info'
        ));
    }
    
    /**
     * Get performance settings
     * 
     * @return array Performance settings
     */
    public function get_performance_settings() {
        return array(
            'cache_ttl' => $this->get('cache_ttl', 300),
            'memory_threshold' => $this->get('memory_threshold', 80),
            'database_timeout' => $this->get('database_timeout', 30),
            'http_timeout' => $this->get('http_timeout', 10),
            'max_retries' => $this->get('max_retries', 3),
            'circuit_breaker_threshold' => $this->get('circuit_breaker_threshold', 5)
        );
    }
    
    /**
     * Set performance settings
     * 
     * @param array $settings Performance settings
     * @return bool Success status
     */
    public function set_performance_settings($settings) {
        return $this->set_multiple(array(
            'cache_ttl' => $settings['cache_ttl'] ?? 300,
            'memory_threshold' => $settings['memory_threshold'] ?? 80,
            'database_timeout' => $settings['database_timeout'] ?? 30,
            'http_timeout' => $settings['http_timeout'] ?? 10,
            'max_retries' => $settings['max_retries'] ?? 3,
            'circuit_breaker_threshold' => $settings['circuit_breaker_threshold'] ?? 5
        ));
    }
    
    /**
     * Get logging settings
     * 
     * @return array Logging settings
     */
    public function get_logging_settings() {
        return array(
            'log_rotation_size' => $this->get('log_rotation_size', 10485760),
            'log_max_files' => $this->get('log_max_files', 5),
            'enable_log_rotation' => $this->get('enable_log_rotation', true)
        );
    }
    
    /**
     * Set logging settings
     * 
     * @param array $settings Logging settings
     * @return bool Success status
     */
    public function set_logging_settings($settings) {
        return $this->set_multiple(array(
            'log_rotation_size' => $settings['log_rotation_size'] ?? 10485760,
            'log_max_files' => $settings['log_max_files'] ?? 5,
            'enable_log_rotation' => $settings['enable_log_rotation'] ?? true
        ));
    }
    
    /**
     * Get feature flags
     * 
     * @return array Feature flags
     */
    public function get_feature_flags() {
        return array(
            'enable_caching' => $this->get('enable_caching', true),
            'enable_memory_monitoring' => $this->get('enable_memory_monitoring', true),
            'enable_database_optimization' => $this->get('enable_database_optimization', true),
            'enable_http_optimization' => $this->get('enable_http_optimization', true)
        );
    }
    
    /**
     * Set feature flags
     * 
     * @param array $flags Feature flags
     * @return bool Success status
     */
    public function set_feature_flags($flags) {
        return $this->set_multiple(array(
            'enable_caching' => $flags['enable_caching'] ?? true,
            'enable_memory_monitoring' => $flags['enable_memory_monitoring'] ?? true,
            'enable_database_optimization' => $flags['enable_database_optimization'] ?? true,
            'enable_http_optimization' => $flags['enable_http_optimization'] ?? true
        ));
    }
    
    /**
     * Reset all options to defaults
     * 
     * @return bool Success status
     */
    public function reset_to_defaults() {
        $success = true;
        
        foreach ($this->options_cache as $key => $default_value) {
            if (!$this->set($key, $default_value)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Export options
     * 
     * @return array Exported options
     */
    public function export() {
        return $this->options_cache;
    }
    
    /**
     * Import options
     * 
     * @param array $options Options to import
     * @return bool Success status
     */
    public function import($options) {
        if (!is_array($options)) {
            return false;
        }
        
        return $this->set_multiple($options);
    }
}