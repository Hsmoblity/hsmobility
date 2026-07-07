<?php
/**
 * Payment processor class
 *
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payment processor class
 */
class HSM_Payment_Processor {
    
    /**
     * Plugin instance
     *
     * @var HSM_Payment_Processor
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return HSM_Payment_Processor
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
     * Create payment intent
     *
     * @param float $amount Amount
     * @param array $customer_info Customer information
     * @param string $currency Currency code
     * @return array
     */
    public function create_payment_intent($amount, $customer_info, $currency = 'USD') {
        $stripe_secret_key = HSM_Options::get('stripe_secret_key');
        
        if (!$stripe_secret_key) {
            throw new Exception('Stripe not configured');
        }
        
        // Convert to cents
        $amount_cents = intval($amount * 100);
        
        // Extract customer data
        $personal_info = $customer_info['personal_info'] ?? array();
        $shipping_address = $customer_info['shipping_address'] ?? array();
        
        // Create PaymentIntent
        $payment_intent = \Stripe\PaymentIntent::create(array(
            'amount' => $amount_cents,
            'currency' => strtolower($currency),
            'metadata' => array(
                'customer_email' => sanitize_email($personal_info['email'] ?? ''),
                'customer_name' => sanitize_text_field(
                    ($personal_info['first_name'] ?? '') . ' ' . ($personal_info['last_name'] ?? '')
                ),
                'source' => 'hsm_configurator',
                'country' => sanitize_text_field($shipping_address['country'] ?? ''),
                'state' => sanitize_text_field($shipping_address['state'] ?? '')
            ),
            'automatic_payment_methods' => array(
                'enabled' => true,
            ),
        ));
        
        // Log activity
        HSM_Logger::get_instance()->log_stripe_activity(
            'payment_intent_created',
            array('amount' => $amount, 'currency' => $currency),
            $payment_intent->id,
            null,
            'success'
        );
        
        return array(
            'client_secret' => $payment_intent->client_secret,
            'payment_intent_id' => $payment_intent->id
        );
    }
}