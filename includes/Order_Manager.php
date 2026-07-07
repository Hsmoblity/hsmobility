<?php
/**
 * Order manager class
 *
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Order manager class
 */
class HSM_Order_Manager {
    
    /**
     * Plugin instance
     *
     * @var HSM_Order_Manager
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return HSM_Order_Manager
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
     * Create order
     *
     * @param array $customer_info Customer information
     * @param array $cart_items Cart items
     * @param string $payment_intent_id Payment intent ID
     * @return array
     */
    public function create_order($customer_info, $cart_items, $payment_intent_id) {
        // Verify payment intent
        if (!$this->verify_payment_intent($payment_intent_id)) {
            throw new Exception('Invalid payment intent ID');
        }
        
        // Create WooCommerce order
        $order = wc_create_order();
        
        if (is_wp_error($order)) {
            throw new Exception('Failed to create order');
        }
        
        // Add customer data
        $this->add_customer_data_to_order($order, $customer_info);
        
        // Add cart items
        $this->add_cart_items_to_order($order, $cart_items);
        
        // Add payment metadata
        $order->update_meta_data('_stripe_payment_intent_id', $payment_intent_id);
        $order->update_meta_data('_payment_method', 'stripe');
        $order->update_meta_data('_payment_method_title', 'Credit Card (Stripe)');
        $order->update_meta_data('_hsm_configurator_order', true);
        
        // Set order status
        $order->set_status('pending-payment');
        
        // Calculate totals and save
        $order->calculate_totals();
        $order->save();
        
        // Log activity
        HSM_Logger::get_instance()->log_stripe_activity(
            'order_created',
            array('items_count' => count($cart_items)),
            $payment_intent_id,
            $order->get_id(),
            'success'
        );
        
        return array(
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'total' => $order->get_total()
        );
    }
    
    /**
     * Verify payment intent
     *
     * @param string $payment_intent_id Payment intent ID
     * @return bool
     */
    private function verify_payment_intent($payment_intent_id) {
        $stripe_secret_key = HSM_Options::get('stripe_secret_key');
        
        if (!$stripe_secret_key) {
            return false;
        }
        
        try {
            $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            return $payment_intent && $payment_intent->id === $payment_intent_id;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Add customer data to order
     *
     * @param WC_Order $order Order object
     * @param array $customer_info Customer information
     */
    private function add_customer_data_to_order($order, $customer_info) {
        $personal = $customer_info['personal_info'] ?? array();
        $shipping = $customer_info['shipping_address'] ?? array();
        $billing = $customer_info['billing_address'] ?? array();
        
        // Set customer data
        $order->set_billing_first_name(sanitize_text_field($personal['first_name'] ?? ''));
        $order->set_billing_last_name(sanitize_text_field($personal['last_name'] ?? ''));
        $order->set_billing_email(sanitize_email($personal['email'] ?? ''));
        $order->set_billing_phone(sanitize_text_field($personal['phone'] ?? ''));
        
        // Set billing address
        if (isset($billing['same_as_billing']) && $billing['same_as_billing']) {
            $billing_address = $shipping;
        } else {
            $billing_address = $billing;
        }
        
        $order->set_billing_address_1(sanitize_text_field($billing_address['address_line_1'] ?? ''));
        $order->set_billing_address_2(sanitize_text_field($billing_address['address_line_2'] ?? ''));
        $order->set_billing_city(sanitize_text_field($billing_address['city'] ?? ''));
        $order->set_billing_state(sanitize_text_field($billing_address['state'] ?? ''));
        $order->set_billing_postcode(sanitize_text_field($billing_address['postal_code'] ?? ''));
        $order->set_billing_country(sanitize_text_field($billing_address['country'] ?? ''));
        
        // Set shipping address
        $order->set_shipping_first_name(sanitize_text_field($personal['first_name'] ?? ''));
        $order->set_shipping_last_name(sanitize_text_field($personal['last_name'] ?? ''));
        $order->set_shipping_address_1(sanitize_text_field($shipping['address_line_1'] ?? ''));
        $order->set_shipping_address_2(sanitize_text_field($shipping['address_line_2'] ?? ''));
        $order->set_shipping_city(sanitize_text_field($shipping['city'] ?? ''));
        $order->set_shipping_state(sanitize_text_field($shipping['state'] ?? ''));
        $order->set_shipping_postcode(sanitize_text_field($shipping['postal_code'] ?? ''));
        $order->set_shipping_country(sanitize_text_field($shipping['country'] ?? ''));
    }
    
    /**
     * Add cart items to order
     *
     * @param WC_Order $order Order object
     * @param array $cart_items Cart items
     */
    private function add_cart_items_to_order($order, $cart_items) {
        foreach ($cart_items as $item) {
            if (!isset($item['price']) || !isset($item['quantity'])) {
                continue;
            }

            $product_name = sanitize_text_field($item['name'] ?? 'Configured Product');
            $price = floatval($item['price']);
            $quantity = intval($item['quantity']);

            if ($quantity <= 0 || $price < 0) {
                continue;
            }

            $line_total = round($price * $quantity, 2);

            // Create order item
            $order_item = new WC_Order_Item_Product();
            $order_item->set_name($product_name);
            $order_item->set_quantity($quantity);
            $order_item->set_subtotal($line_total);
            $order_item->set_total($line_total);
            $order_item->set_product_id(0);
            $order_item->set_variation_id(0);
            $order_item->set_taxes(array(
                'total' => array(),
                'subtotal' => array()
            ));

            // Add metadata
            $order_item->add_meta_data('_configurator_unit_price', round($price, 2), true);

            if (isset($item['options']) && is_array($item['options'])) {
                $order_item->add_meta_data('_configurator_options', wp_json_encode($item['options']), true);
            }

            if (isset($item['productId'])) {
                $order_item->add_meta_data('_original_product_id', sanitize_text_field($item['productId']), true);
            }

            $order->add_item($order_item);
        }
    }
}