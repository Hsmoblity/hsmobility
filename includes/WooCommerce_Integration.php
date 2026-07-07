<?php
/**
 * WooCommerce integration class
 *
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce integration class
 */
class HSM_WooCommerce_Integration {
    
    /**
     * Plugin instance
     *
     * @var HSM_WooCommerce_Integration
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return HSM_WooCommerce_Integration
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
     * Initialize WooCommerce integration
     */
    public function init() {
        // Add hooks for WooCommerce integration
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 3);
        add_filter('woocommerce_payment_gateways', array($this, 'add_payment_gateway'));
    }
    
    /**
     * Handle order status change
     *
     * @param int $order_id Order ID
     * @param string $old_status Old status
     * @param string $new_status New status
     */
    public function handle_order_status_change($order_id, $old_status, $new_status) {
        $order = wc_get_order($order_id);
        
        if (!$order || !$order->get_meta('_hsm_configurator_order')) {
            return;
        }
        
        // Log order status change
        HSM_Logger::get_instance()->log_stripe_activity(
            'order_status_changed',
            array(
                'order_id' => $order_id,
                'old_status' => $old_status,
                'new_status' => $new_status
            ),
            $order->get_meta('_stripe_payment_intent_id'),
            $order_id,
            'success'
        );
    }
    
    /**
     * Add payment gateway
     *
     * @param array $gateways Payment gateways
     * @return array
     */
    public function add_payment_gateway($gateways) {
        // Add HSM Stripe gateway if needed
        return $gateways;
    }
}