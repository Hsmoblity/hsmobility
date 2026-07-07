<?php
/**
 * HSM Error Handler Class
 * 
 * Handles error responses and logging for the HSM plugin
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Error_Handler {
    
    /**
     * Create success response
     * 
     * @param mixed $data Response data
     * @return WP_REST_Response Success response
     */
    public function success_response($data) {
        return new WP_REST_Response([
            'success' => true,
            'data' => $data
        ], 200);
    }
    
    /**
     * Create validation error response
     * 
     * @param string $message Error message
     * @return WP_REST_Response Validation error response
     */
    public function validation_error($message) {
        return new WP_REST_Response([
            'success' => false,
            'error' => $message,
            'code' => 'VALIDATION_FAILED'
        ], 400);
    }
    
    /**
     * Create Stripe API error response
     * 
     * @param string $message Error message
     * @return WP_REST_Response Stripe error response
     */
    public function stripe_error($message) {
        return new WP_REST_Response([
            'success' => false,
            'error' => 'Payment processing error: ' . $message,
            'code' => 'STRIPE_API_ERROR'
        ], 422);
    }
    
    /**
     * Create server error response
     * 
     * @param string $message Error message
     * @return WP_REST_Response Server error response
     */
    public function server_error($message) {
        return new WP_REST_Response([
            'success' => false,
            'error' => 'Internal server error',
            'code' => 'SERVER_ERROR'
        ], 500);
    }
    
    /**
     * Log error message
     * 
     * @param string $message Error message
     * @param array $context Additional context
     * @return void
     */
    public function log_error($message, $context = []) {
        global $wpdb;
        
        $log_data = [
            'level' => 'error',
            'message' => $message,
            'context' => json_encode($context),
            'timestamp' => current_time('mysql')
        ];
        
        $wpdb->insert(
            $wpdb->prefix . 'hsm_stripe_logs',
            $log_data,
            ['%s', '%s', '%s', '%s']
        );
        
        // Also log to WordPress error log
        error_log("HSM Plugin Error: {$message} - Context: " . json_encode($context));
    }
    
    /**
     * Log success message
     * 
     * @param string $action Action performed
     * @param array $data Additional data
     * @return void
     */
    public function log_success($action, $data = []) {
        global $wpdb;
        
        $log_data = [
            'level' => 'success',
            'message' => "Action completed: {$action}",
            'context' => json_encode($data),
            'timestamp' => current_time('mysql')
        ];
        
        $wpdb->insert(
            $wpdb->prefix . 'hsm_stripe_logs',
            $log_data,
            ['%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Get recent logs
     * 
     * @param int $limit Number of logs to retrieve
     * @return array Recent logs
     */
    public function get_recent_logs($limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hsm_stripe_logs';
        
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} ORDER BY timestamp DESC LIMIT %d",
                $limit
            )
        );
        
        return $logs ?: [];
    }
}