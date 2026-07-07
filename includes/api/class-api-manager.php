<?php
/**
 * HSM API Manager Class
 * 
 * Manages all Stripe-related API endpoints in the hsm-stripe/v1 namespace.
 * This is the PRIMARY namespace for all Stripe payment operations.
 * 
 * CONSOLIDATED API STRUCTURE:
 * - hsm/v1: General HSM endpoints (health, settings, system-status, etc.)
 * - hsm-stripe/v1: ALL Stripe payment operations (tax, payment intent, orders)
 * - hsm-graphql/v1: ALL GraphQL proxy operations
 * 
 * NOTE: Duplicate routes have been removed from hsm/v1 namespace.
 * All Stripe operations should use hsm-stripe/v1 namespace.
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_API_Manager {
    
    /**
     * Tax calculator API instance
     * 
     * @var HSM_Tax_Calculator_API
     */
    private $tax_calculator_api;
    
    /**
     * Payment intent API instance
     * 
     * @var HSM_Payment_Intent_API
     */
    private $payment_intent_api;
    
    /**
     * Order creation API instance
     * 
     * @var HSM_Order_Creation_API
     */
    private $order_creation_api;
    
    /**
     * GraphQL healthcheck API instance
     * 
     * @var HSM_GraphQL_Healthcheck_API
     */
    private $healthcheck_api;
    
    /**
     * Constructor
     * 
     * @param HSM_Error_Handler $error_handler Error handler instance
     * @param string $stripe_secret_key Stripe secret key
     */
    public function __construct($error_handler, $stripe_secret_key) {
        $this->tax_calculator_api = new HSM_Tax_Calculator_API($error_handler);
        $this->payment_intent_api = new HSM_Payment_Intent_API($error_handler, $stripe_secret_key);
        $this->order_creation_api = new HSM_Order_Creation_API($error_handler, $stripe_secret_key);
        $this->healthcheck_api = new HSM_GraphQL_Healthcheck_API($error_handler);
    }
    
    /**
     * Register all API endpoints
     * 
     * Registers all Stripe-related endpoints in the hsm-stripe/v1 namespace.
     * These are the PRIMARY endpoints for Stripe operations.
     * 
     * @return void
     */
    public function register_api_endpoints() {
        // Tax calculation endpoint - PRIMARY endpoint for tax calculations
        // NOTE: Using GET method for compatibility with frontend payment store
        // Base URL: /wp-json/hsm-stripe/v1/tax/calculate
        register_rest_route('hsm-stripe/v1', '/tax/calculate', [
            'methods' => 'GET',
            'callback' => [$this->tax_calculator_api, 'calculate_tax'],
            'permission_callback' => '__return_true', // Public endpoint for frontend use
            'args' => [
                'country' => ['required' => true, 'type' => 'string'],
                'state' => ['required' => true, 'type' => 'string'],
                'items' => ['required' => true, 'type' => 'string']
            ]
        ]);
        
        // Payment intent endpoint - PRIMARY endpoint for creating Stripe payment intents
        // Base URL: /wp-json/hsm-stripe/v1/payment/intent
        register_rest_route('hsm-stripe/v1', '/payment/intent', [
            'methods' => 'POST',
            'callback' => [$this->payment_intent_api, 'create_payment_intent'],
            'permission_callback' => '__return_true' // Public endpoint for frontend use
        ]);
        
        // Order creation endpoint - PRIMARY endpoint for creating WooCommerce orders
        // Base URL: /wp-json/hsm-stripe/v1/orders/create
        register_rest_route('hsm-stripe/v1', '/orders/create', [
            'methods' => 'POST',
            'callback' => [$this->order_creation_api, 'create_order'],
            'permission_callback' => '__return_true' // Public endpoint for frontend use
        ]);
        
        // Order status update endpoint - PRIMARY endpoint for updating order status
        // Base URL: /wp-json/hsm-stripe/v1/orders/update-status
        register_rest_route('hsm-stripe/v1', '/orders/update-status', [
            'methods' => 'POST',
            'callback' => [$this->order_creation_api, 'update_order_status'],
            'permission_callback' => '__return_true', // Public endpoint for frontend use
            'args' => [
                'orderId' => ['required' => true, 'type' => 'integer'],
                'status' => ['required' => false, 'type' => 'string'],
                'paymentStatus' => ['required' => false, 'type' => 'string'],
                'paymentIntentId' => ['required' => false, 'type' => 'string'],
                'amountPaid' => ['required' => false, 'type' => 'integer'],
                'currency' => ['required' => false, 'type' => 'string'],
                'metadata' => ['required' => false, 'type' => 'object']
            ]
        ]);
        
        // Register GraphQL healthcheck endpoints (in hsm-graphql/v1 namespace)
        $this->healthcheck_api->register_endpoints();
    }
}

// Agent Signature: 160125 - Fullstack - API_Namespace_Consolidation