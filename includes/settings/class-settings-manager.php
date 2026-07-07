<?php
/**
 * HSM Settings Manager Class
 * 
 * Manages all plugin settings
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Settings_Manager {
    
    /**
     * Singleton instance
     *
     * @var HSM_Settings_Manager
     */
    private static $instance = null;
    
    /**
     * Stripe settings instance
     *
     * @var HSM_Stripe_Settings
     */
    private $stripe_settings;
    
    /**
     * General settings instance
     *
     * @var HSM_General_Settings
     */
    private $general_settings;
    
    /**
     * Get singleton instance
     *
     * @return HSM_Settings_Manager
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
        $this->stripe_settings = new HSM_Stripe_Settings();
        $this->general_settings = new HSM_General_Settings();
    }
    
    /**
     * Get Stripe settings instance
     *
     * @return HSM_Stripe_Settings
     */
    public function get_stripe_settings() {
        return $this->stripe_settings;
    }
    
    /**
     * Get general settings instance
     *
     * @return HSM_General_Settings
     */
    public function get_general_settings() {
        return $this->general_settings;
    }
    
    /**
     * Get all settings
     *
     * @return array
     */
    public function get_all_settings() {
        return [
            'stripe' => $this->stripe_settings->get_all_settings(),
            'general' => $this->general_settings->get_all_settings()
        ];
    }
    
    /**
     * Update settings
     *
     * @param array $settings Settings array
     * @return bool
     */
    public function update_settings($settings) {
        $success = true;
        
        if (isset($settings['stripe'])) {
            if (!$this->stripe_settings->update_settings($settings['stripe'])) {
                $success = false;
            }
        }
        
        if (isset($settings['general'])) {
            if (!$this->general_settings->update_settings($settings['general'])) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Reset all settings to defaults
     *
     * @return bool
     */
    public function reset_all_settings() {
        $stripe_success = $this->stripe_settings->reset_to_defaults();
        $general_success = $this->general_settings->reset_to_defaults();
        
        return $stripe_success && $general_success;
    }
    
    /**
     * Export settings
     *
     * @return array
     */
    public function export_settings() {
        return [
            'version' => '1.0.0',
            'exported_at' => current_time('mysql'),
            'settings' => $this->get_all_settings()
        ];
    }
    
    /**
     * Import settings
     *
     * @param array $data Import data
     * @return bool|WP_Error
     */
    public function import_settings($data) {
        if (!isset($data['settings'])) {
            return new WP_Error('invalid_data', 'Invalid import data');
        }
        
        $settings = $data['settings'];
        
        if (!$this->update_settings($settings)) {
            return new WP_Error('import_failed', 'Failed to import settings');
        }
        
        return true;
    }
    
    /**
     * Validate settings
     *
     * @param array $settings Settings to validate
     * @return array Array of validation errors
     */
    public function validate_settings($settings) {
        $errors = [];
        
        if (isset($settings['stripe'])) {
            $stripe_errors = $this->validate_stripe_settings($settings['stripe']);
            if (!empty($stripe_errors)) {
                $errors['stripe'] = $stripe_errors;
            }
        }
        
        if (isset($settings['general'])) {
            $general_errors = $this->validate_general_settings($settings['general']);
            if (!empty($general_errors)) {
                $errors['general'] = $general_errors;
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate Stripe settings
     *
     * @param array $settings Stripe settings
     * @return array Validation errors
     */
    private function validate_stripe_settings($settings) {
        $errors = [];
        
        if (isset($settings['secret_key'])) {
            $validation = $this->stripe_settings->validate_secret_key($settings['secret_key']);
            if (is_wp_error($validation)) {
                $errors['secret_key'] = $validation->get_error_message();
            }
        }
        
        if (isset($settings['publishable_key'])) {
            $validation = $this->stripe_settings->validate_publishable_key($settings['publishable_key']);
            if (is_wp_error($validation)) {
                $errors['publishable_key'] = $validation->get_error_message();
            }
        }
        
        if (isset($settings['environment'])) {
            $validation = $this->stripe_settings->validate_environment($settings['environment']);
            if (is_wp_error($validation)) {
                $errors['environment'] = $validation->get_error_message();
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate general settings
     *
     * @param array $settings General settings
     * @return array Validation errors
     */
    private function validate_general_settings($settings) {
        $errors = [];
        
        if (isset($settings['tax_fallback_rate'])) {
            $validation = $this->general_settings->validate_tax_rate($settings['tax_fallback_rate']);
            if (is_wp_error($validation)) {
                $errors['tax_fallback_rate'] = $validation->get_error_message();
            }
        }
        
        if (isset($settings['currency'])) {
            $validation = $this->general_settings->validate_currency($settings['currency']);
            if (is_wp_error($validation)) {
                $errors['currency'] = $validation->get_error_message();
            }
        }
        
        return $errors;
    }
}