<?php
/**
 * HSM Stripe Simple Class
 * 
 * Simplified Stripe integration for basic payment processing.
 * Provides a lightweight interface for common Stripe operations.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Stripe_Simple {
    
    /**
     * Singleton instance
     * 
     * @var HSM_Stripe_Simple
     */
    private static $instance = null;
    
    /**
     * Stripe secret key
     * 
     * @var string
     */
    private $secret_key;
    
    /**
     * Stripe publishable key
     * 
     * @var string
     */
    private $publishable_key;
    
    /**
     * Get singleton instance
     *
     * @return HSM_Stripe_Simple
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
        $this->load_stripe_keys();
    }
    
    /**
     * Load Stripe keys from options
     */
    private function load_stripe_keys() {
        if (class_exists('HSM_Options')) {
            $options = HSM_Options::get_instance();
            $stripe_settings = $options->get_stripe_settings();
            $this->secret_key = $stripe_settings['secret_key'] ?? '';
            $this->publishable_key = $stripe_settings['publishable_key'] ?? '';
        } else {
            $this->secret_key = get_option('hsm_stripe_secret_key', '');
            $this->publishable_key = get_option('hsm_stripe_publishable_key', '');
        }
    }
    
    /**
     * Get publishable key
     * 
     * @return string Publishable key
     */
    public function get_publishable_key() {
        return $this->publishable_key;
    }
    
    /**
     * Get secret key
     * 
     * @return string Secret key
     */
    public function get_secret_key() {
        return $this->secret_key;
    }
    
    /**
     * Check if Stripe is configured
     * 
     * @return bool Is configured
     */
    public function is_configured() {
        return !empty($this->secret_key) && !empty($this->publishable_key);
    }
    
    /**
     * Create payment intent
     * 
     * @param array $params Payment intent parameters
     * @return array|false Payment intent data or false on failure
     */
    public function create_payment_intent($params) {
        if (!$this->is_configured()) {
            return false;
        }
        
        // Basic payment intent creation
        $default_params = array(
            'amount' => 0,
            'currency' => 'usd',
            'metadata' => array()
        );
        
        $params = wp_parse_args($params, $default_params);
        
        // In a real implementation, this would call Stripe API
        // For now, return a mock response
        return array(
            'id' => 'pi_mock_' . uniqid(),
            'client_secret' => 'pi_mock_' . uniqid() . '_secret_' . uniqid(),
            'amount' => $params['amount'],
            'currency' => $params['currency'],
            'status' => 'requires_payment_method'
        );
    }
    
    /**
     * Get payment intent
     * 
     * @param string $payment_intent_id Payment intent ID
     * @return array|false Payment intent data or false on failure
     */
    public function get_payment_intent($payment_intent_id) {
        if (!$this->is_configured()) {
            return false;
        }
        
        // Mock implementation
        return array(
            'id' => $payment_intent_id,
            'status' => 'succeeded',
            'amount' => 1000,
            'currency' => 'usd'
        );
    }
    
    /**
     * Update payment intent
     * 
     * @param string $payment_intent_id Payment intent ID
     * @param array $params Update parameters
     * @return array|false Updated payment intent or false on failure
     */
    public function update_payment_intent($payment_intent_id, $params) {
        if (!$this->is_configured()) {
            return false;
        }
        
        // Mock implementation
        return array(
            'id' => $payment_intent_id,
            'status' => 'requires_payment_method',
            'amount' => $params['amount'] ?? 1000,
            'currency' => $params['currency'] ?? 'usd'
        );
    }
    
    /**
     * Confirm payment intent
     * 
     * @param string $payment_intent_id Payment intent ID
     * @return array|false Confirmed payment intent or false on failure
     */
    public function confirm_payment_intent($payment_intent_id) {
        if (!$this->is_configured()) {
            return false;
        }
        
        // Mock implementation
        return array(
            'id' => $payment_intent_id,
            'status' => 'succeeded',
            'amount' => 1000,
            'currency' => 'usd'
        );
    }
    
    /**
     * Get error message
     * 
     * @param string $error_code Error code
     * @return string Error message
     */
    public function get_error_message($error_code) {
        $error_messages = array(
            'card_declined' => 'Your card was declined.',
            'expired_card' => 'Your card has expired.',
            'incorrect_cvc' => 'Your card\'s security code is incorrect.',
            'processing_error' => 'An error occurred while processing your card.',
            'authentication_required' => 'Your card requires authentication.'
        );
        
        return $error_messages[$error_code] ?? 'An error occurred with your payment.';
    }
    
    /**
     * Log error
     * 
     * @param string $message Error message
     * @param array $context Additional context
     */
    private function log_error($message, $context = array()) {
        if (class_exists('HSM_Log_Manager')) {
            $log_manager = HSM_Log_Manager::get_instance();
            $log_manager->log("HSM Stripe Simple Error: {$message}", 'error', $context);
        } else {
            error_log("HSM Stripe Simple Error: {$message}");
        }
    }
    
    /**
     * Health check
     * 
     * @return array Health status
     */
    public function health_check() {
        // Use unified health manager if available, otherwise fallback to basic check
        if (class_exists('HSM_Health_Manager')) {
            $health_manager = HSM_Health_Manager::get_instance();
            $base_health = $health_manager->get_quick_health_status();
            
            // Add Stripe-specific health data
            $base_health['stripe_info'] = array(
                'configured' => $this->is_configured(),
                'has_secret_key' => !empty($this->secret_key),
                'has_publishable_key' => !empty($this->publishable_key)
            );
            
            return $base_health;
        }
        
        // Fallback to basic Stripe health check
        return array(
            'status' => 'healthy',
            'configured' => $this->is_configured(),
            'has_secret_key' => !empty($this->secret_key),
            'has_publishable_key' => !empty($this->publishable_key),
            'note' => 'Using fallback health check - HSM_Health_Manager not available'
        );
    }
}