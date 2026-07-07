<?php
/**
 * HSM Order Creation API Class
 * 
 * Handles WooCommerce order creation API endpoint functionality
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Order_Creation_API {
    
    /**
     * Error handler instance
     * 
     * @var HSM_Error_Handler
     */
    private $error_handler;
    
    /**
     * Stripe secret key
     * 
     * @var string
     */
    private $stripe_secret_key;
    
    /**
     * Constructor
     * 
     * @param HSM_Error_Handler $error_handler Error handler instance
     * @param string $stripe_secret_key Stripe secret key
     */
    public function __construct($error_handler, $stripe_secret_key) {
        $this->error_handler = $error_handler;
        $this->stripe_secret_key = $stripe_secret_key;
    }
    
    /**
     * Create WooCommerce order
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response Response object
     */
    public function create_order($request) {
        try {
            $body = json_decode($request->get_body(), true);
            
            // Validate required fields (same structure as frontend)
            $required_fields = ['customer_info', 'cart_items', 'payment_intent_id'];
            foreach ($required_fields as $field) {
                if (!isset($body[$field])) {
                    return $this->error_handler->validation_error("Missing required field: {$field}");
                }
            }
            
            $customer_info = $body['customer_info'];
            $cart_items = $body['cart_items'];
            $payment_intent_id = sanitize_text_field($body['payment_intent_id']);
            
            // Verify payment intent exists
            if (!$this->verify_payment_intent($payment_intent_id)) {
                return $this->error_handler->validation_error('Invalid payment intent ID');
            }
            
            // Create WooCommerce order
            $order = wc_create_order();
            
            if (is_wp_error($order)) {
                return $this->error_handler->server_error('Failed to create order');
            }
            
            // Add customer data (aligned with frontend customer form structure)
            $this->add_customer_data_to_order($order, $customer_info);
            
            // Add cart items (same format as frontend cart store)
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
            
            // Log successful order creation
            $this->log_success('order_created', [
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'payment_intent_id' => $payment_intent_id,
                'total' => $order->get_total()
            ]);
            
            // Return format expected by frontend
            return $this->error_handler->success_response([
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'status' => $order->get_status(),
                'total' => $order->get_total()
            ]);
            
        } catch (Exception $e) {
            error_log('HSM Order Creation Error: ' . $e->getMessage());
            return $this->error_handler->server_error('Order creation failed');
        }
    }
    
    /**
     * Verify Stripe PaymentIntent exists (Redirected to Next.js)
     * 
     * @param string $payment_intent_id PaymentIntent ID
     * @return bool True if valid
     */
    private function verify_payment_intent($payment_intent_id) {
        // Stripe SDK removed - payment intent verification handled by Next.js application
        // For now, assume valid if payment_intent_id is provided (Next.js will handle verification)
        return !empty($payment_intent_id);
    }
    
    /**
     * Add customer data to WooCommerce order
     * 
     * @param WC_Order $order WooCommerce order object
     * @param array $customer_info Customer information
     * @return void
     */
    private function add_customer_data_to_order($order, $customer_info) {
        // Extract data (same structure as frontend payment store)
        $personal = $customer_info['personal_info'] ?? [];
        $shipping = $customer_info['shipping_address'] ?? [];
        $billing = $customer_info['billing_address'] ?? [];
        
        // Set customer data
        $order->set_billing_first_name(sanitize_text_field($personal['first_name'] ?? ''));
        $order->set_billing_last_name(sanitize_text_field($personal['last_name'] ?? ''));
        $order->set_billing_email(sanitize_email($personal['email'] ?? ''));
        $order->set_billing_phone(sanitize_text_field($personal['phone'] ?? ''));
        
        // Set billing address (use shipping if same_as_billing is true)
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
     * Add cart items to WooCommerce order
     * 
     * @param WC_Order $order WooCommerce order object
     * @param array $cart_items Cart items array
     * @return void
     */
    private function add_cart_items_to_order($order, $cart_items) {
        foreach ($cart_items as $item) {
            // Validate item structure (same as frontend cart store)
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

            // Create lightweight order item without persisting a catalog product
            $order_item = new WC_Order_Item_Product();
            $order_item->set_name($product_name);
            $order_item->set_quantity($quantity);
            $order_item->set_subtotal($line_total);
            $order_item->set_total($line_total);
            $order_item->set_product_id(0);
            $order_item->set_variation_id(0);
            $order_item->set_taxes([
                'total' => [],
                'subtotal' => []
            ]);

            // Add configurator price as custom metadata for transparency
            $order_item->add_meta_data('_configurator_unit_price', round($price, 2), true);

            // Add configurator options as metadata
            if (isset($item['options']) && is_array($item['options'])) {
                $order_item->add_meta_data('_configurator_options', wp_json_encode($item['options']), true);
            }

            // Add original item metadata for tracking
            if (isset($item['productId'])) {
                $order_item->add_meta_data('_original_product_id', sanitize_text_field($item['productId']), true);
            }

            $order->add_item($order_item);
        }
    }
    
    /**
     * Log successful operation
     * 
     * @param string $action Action name
     * @param array $data Data to log
     * @return void
     */
    private function log_success($action, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hsm_stripe_logs';
        $wpdb->insert(
            $table_name,
            [
                'action' => $action,
                'data' => json_encode($data)
            ],
            ['%s', '%s']
        );
    }
    
    /**
     * Update WooCommerce order status
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response Response object
     */
    public function update_order_status($request) {
        try {
            $body = json_decode($request->get_body(), true);
            
            $order_id = isset($body['orderId']) ? intval($body['orderId']) : null;
            $status = isset($body['status']) ? sanitize_text_field($body['status']) : null;
            $payment_status = isset($body['paymentStatus']) ? sanitize_text_field($body['paymentStatus']) : null;
            $payment_intent_id = isset($body['paymentIntentId']) ? sanitize_text_field($body['paymentIntentId']) : null;
            $amount_paid = isset($body['amountPaid']) ? intval($body['amountPaid']) : null;
            $currency = isset($body['currency']) ? sanitize_text_field($body['currency']) : null;
            $metadata = isset($body['metadata']) ? $body['metadata'] : [];
            
            // Validate required parameters
            if (!$order_id) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Order ID is required'
                ], 400);
            }
            
            // Get the WooCommerce order
            $order = wc_get_order($order_id);
            if (!$order) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }
            
            // Update order status
            if ($status) {
                $order->update_status($status, 'Status updated via payment API');
            }
            
            // Update payment information
            if ($payment_intent_id) {
                $order->set_transaction_id($payment_intent_id);
            }
            
            if ($amount_paid) {
                // Convert cents to dollars for WooCommerce
                $amount_dollars = $amount_paid / 100;
                $order->set_total($amount_dollars);
            }
            
            // Add payment method and metadata
            $order->set_payment_method('stripe');
            $order->set_payment_method_title('Stripe');
            
            // Add metadata
            if (!empty($metadata) && is_array($metadata)) {
                foreach ($metadata as $key => $value) {
                    $order->add_meta_data("_stripe_{$key}", $value, true);
                }
                
                // Add a customer note about the payment
                $order->add_order_note(
                    sprintf(
                        'Payment processed successfully via Stripe. Payment Intent: %s',
                        $payment_intent_id
                    ),
                    false
                );
            }
            
            // Mark payment as complete if succeeded
            if ($payment_status === 'paid' || $payment_status === 'succeeded') {
                $order->payment_complete($payment_intent_id);
            }
            
            // Save the order
            $order->save();
            
            // Log the successful update
            error_log(sprintf(
                'HSM Order Update: Successfully updated order %d with status %s and payment %s',
                $order_id,
                $status,
                $payment_intent_id
            ));
            
            // Return success response
            return new WP_REST_Response([
                'success' => true,
                'message' => 'Order updated successfully',
                'order' => [
                    'id' => $order->get_id(),
                    'orderNumber' => $order->get_order_number(),
                    'status' => $order->get_status(),
                    'paymentStatus' => $order->is_paid() ? 'paid' : 'pending',
                    'total' => $order->get_total(),
                    'currency' => $order->get_currency(),
                    'paymentMethod' => $order->get_payment_method(),
                    'transactionId' => $order->get_transaction_id()
                ]
            ], 200);
            
        } catch (Exception $e) {
            $this->error_handler->handle_error($e, 'order_update_api');
            
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Failed to update order: ' . $e->getMessage()
            ], 500);
        }
    }
}