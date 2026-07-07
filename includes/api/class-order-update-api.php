<?php
/**
 * HSM Order Update API Class
 * 
 * Handles order status updates from payment processing
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Order_Update_API {
    
    /**
     * Error handler instance
     * 
     * @var HSM_Error_Handler
     */
    private $error_handler;
    
    /**
     * Constructor
     * 
     * @param HSM_Error_Handler $error_handler Error handler instance
     */
    public function __construct($error_handler) {
        $this->error_handler = $error_handler;
    }
    
    /**
     * Update order status
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public function update_order_status($request) {
        try {
            $order_id = $request->get_param('orderId');
            $status = $request->get_param('status');
            $payment_status = $request->get_param('paymentStatus');
            $payment_intent_id = $request->get_param('paymentIntentId');
            $amount_paid = $request->get_param('amountPaid');
            $currency = $request->get_param('currency');
            $metadata = $request->get_param('metadata');
            
            // Validate required parameters
            if (empty($order_id)) {
                return new WP_Error('missing_order_id', 'Order ID is required', ['status' => 400]);
            }
            
            // Get the WooCommerce order
            $order = wc_get_order($order_id);
            if (!$order) {
                return new WP_Error('order_not_found', 'Order not found', ['status' => 404]);
            }
            
            // Update order status
            if (!empty($status)) {
                $order->update_status($status, 'Status updated via payment API');
            }
            
            // Update payment information
            if (!empty($payment_intent_id)) {
                $order->set_transaction_id($payment_intent_id);
            }
            
            if (!empty($amount_paid)) {
                // Convert cents to dollars for WooCommerce
                $amount_dollars = $amount_paid / 100;
                $order->set_total($amount_dollars);
            }
            
            // Add payment method and metadata
            $order->set_payment_method('stripe');
            $order->set_payment_method_title('Stripe');
            
            // Add metadata as order notes
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
                    false // Not a customer note
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
            
            return new WP_Error(
                'order_update_failed',
                'Failed to update order: ' . $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Validate order update permissions
     * 
     * @param WP_REST_Request $request Request object
     * @return bool True if request is valid
     */
    public function validate_permissions($request) {
        // For now, allow all requests
        // In production, you might want to add API key validation
        return true;
    }
}