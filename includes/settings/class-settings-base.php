<?php
/**
 * HSM Settings Base Class
 * 
 * Base class for settings management
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class HSM_Settings_Base {
    
    /**
     * Option prefix
     *
     * @var string
     */
    protected $prefix = 'hsm_stripe_';
    
    /**
     * Default settings
     *
     * @var array
     */
    protected $defaults = [];
    
    /**
     * Get setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function get_setting($key, $default = null) {
        $option_name = $this->prefix . $key;
        $value = get_option($option_name, $default);
        
        // Return default if value is empty and default is not null
        if (empty($value) && $default !== null) {
            return $default;
        }
        
        return $value;
    }
    
    /**
     * Set setting value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool
     */
    protected function set_setting($key, $value) {
        $option_name = $this->prefix . $key;
        return update_option($option_name, $value);
    }
    
    /**
     * Delete setting
     *
     * @param string $key Setting key
     * @return bool
     */
    protected function delete_setting($key) {
        $option_name = $this->prefix . $key;
        return delete_option($option_name);
    }
    
    /**
     * Get all settings
     *
     * @return array
     */
    public function get_all_settings() {
        $settings = [];
        
        foreach ($this->defaults as $key => $default) {
            $settings[$key] = $this->get_setting($key, $default);
        }
        
        return $settings;
    }
    
    /**
     * Update multiple settings
     *
     * @param array $settings Settings array
     * @return bool
     */
    public function update_settings($settings) {
        $success = true;
        
        foreach ($settings as $key => $value) {
            if (array_key_exists($key, $this->defaults)) {
                if (!$this->set_setting($key, $value)) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Reset settings to defaults
     *
     * @return bool
     */
    public function reset_to_defaults() {
        $success = true;
        
        foreach ($this->defaults as $key => $default) {
            if (!$this->set_setting($key, $default)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Validate setting value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool|WP_Error
     */
    protected function validate_setting($key, $value) {
        // Override in child classes for specific validation
        return true;
    }
    
    /**
     * Sanitize setting value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return mixed
     */
    protected function sanitize_setting($key, $value) {
        // Override in child classes for specific sanitization
        return $value;
    }
}