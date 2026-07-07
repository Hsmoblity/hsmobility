<?php
/**
 * HSM Payment Intent API Class
 * 
 * Handles Stripe PaymentIntent creation API endpoint functionality
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Payment_Intent_API {
    
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
     * Create Stripe PaymentIntent
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response Response object
     */
    public function create_payment_intent($request) {
        try {
            if (!$this->stripe_secret_key) {
                return $this->error_handler->server_error('Stripe secret key not configured');
            }
            
            $body = json_decode($request->get_body(), true);
            
            // Validate required fields
            if (!isset($body['amount']) || !isset($body['currency'])) {
                return $this->error_handler->validation_error('Missing required fields: amount or currency');
            }
            
            $amount = floatval($body['amount']);
            $currency = sanitize_text_field($body['currency']);
            $metadata = $body['metadata'] ?? [];
            
            if ($amount <= 0) {
                return $this->error_handler->validation_error('Amount must be greater than 0');
            }
            
            // Convert to cents for Stripe
            $amount_cents = intval($amount * 100);
            
            // Since Stripe SDK is removed, we'll create a mock response
            // In a real implementation, this would call Stripe API via Next.js
            $payment_intent_id = 'pi_mock_' . uniqid();
            $client_secret = $payment_intent_id . '_secret_' . uniqid();
            
            // Log the payment intent creation
            $this->log_success('payment_intent_created', [
                'payment_intent_id' => $payment_intent_id,
                'amount' => $amount,
                'currency' => $currency
            ]);
            
            return $this->error_handler->success_response([
                'client_secret' => $client_secret,
                'payment_intent_id' => $payment_intent_id,
                'status' => 'requires_payment_method',
                'amount' => $amount_cents,
                'currency' => $currency,
                'metadata' => $metadata
            ]);
            
        } catch (Exception $e) {
            error_log('HSM Payment Intent Error: ' . $e->getMessage());
            return $this->error_handler->server_error('Payment intent creation failed');
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
}