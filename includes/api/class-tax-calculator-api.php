<?php
/**
 * HSM Tax Calculator API Class
 * 
 * Handles tax calculation API endpoint functionality
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Tax_Calculator_API {
    
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
     * Calculate tax for given amount and location (simplified version for REST API)
     * 
     * @param float $amount Amount to calculate tax for
     * @param string $country Country code
     * @param string $state State/province code
     * @return array Tax calculation result
     */
    public function calculate_tax($amount, $country = '', $state = '') {
        try {
            if ($amount <= 0) {
                throw new Exception('Invalid amount: must be greater than 0');
            }
            
            // Get tax rates from WooCommerce (cached for performance)
            $cache_key = "hsm_tax_rates_{$country}_{$state}";
            $tax_rates = get_transient($cache_key);
            
            if (false === $tax_rates) {
                $tax_rates = $this->get_woocommerce_tax_rates($country, $state, '');
                set_transient($cache_key, $tax_rates, HOUR_IN_SECONDS);
            }
            
            // Calculate tax using WooCommerce logic
            $tax_total = 0;
            $tax_lines = [];
            
            if (!empty($tax_rates)) {
                foreach ($tax_rates as $rate) {
                    $tax_amount = ($amount * floatval($rate->tax_rate)) / 100;
                    $tax_total += $tax_amount;
                    $tax_lines[] = [
                        'label' => $rate->tax_rate_name ?: "Tax ({$rate->tax_rate}%)",
                        'amount' => round($tax_amount, 2)
                    ];
                }
            } else {
                // Fallback to hardcoded rate if no WooCommerce rates found
                $fallback_rate = 13; // 13% as per frontend current implementation
                $tax_amount = ($amount * $fallback_rate) / 100;
                $tax_total = $tax_amount;
                $tax_lines[] = [
                    'label' => "Tax ({$fallback_rate}%)",
                    'amount' => round($tax_amount, 2)
                ];
            }
            
            return [
                'subtotal' => round($amount, 2),
                'tax_total' => round($tax_total, 2),
                'total' => round($amount + $tax_total, 2),
                'tax_lines' => $tax_lines,
                'country' => $country,
                'state' => $state,
                'fallback_used' => empty($tax_rates)
            ];
            
        } catch (Exception $e) {
            throw new Exception('Tax calculation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate tax for given items and location (original method)
     * 
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response Response object
     */
    public function calculate_tax_from_request($request) {
        try {
            // Validate and sanitize input
            $country = sanitize_text_field($request['country']);
            $state = sanitize_text_field($request['state']);
            $postal_code = sanitize_text_field($request['postal_code'] ?? '');
            $items = json_decode($request['items'], true);
            
            if (!$country || !$state || !$items) {
                return $this->error_handler->validation_error('Missing required fields: country, state, or items');
            }
            
            // Calculate subtotal from cart items (same format as frontend)
            $subtotal = 0;
            foreach ($items as $item) {
                if (!isset($item['price']) || !isset($item['quantity'])) {
                    return $this->error_handler->validation_error('Invalid item format: missing price or quantity');
                }
                $subtotal += floatval($item['price']) * intval($item['quantity']);
            }
            
            // Get tax rates from WooCommerce (cached for performance)
            $cache_key = "hsm_tax_rates_{$country}_{$state}_{$postal_code}";
            $tax_rates = get_transient($cache_key);
            
            if (false === $tax_rates) {
                $tax_rates = $this->get_woocommerce_tax_rates($country, $state, $postal_code);
                set_transient($cache_key, $tax_rates, HOUR_IN_SECONDS);
            }
            
            // Calculate tax using WooCommerce logic
            $tax_total = 0;
            $tax_lines = [];
            
            if (!empty($tax_rates)) {
                foreach ($tax_rates as $rate) {
                    $tax_amount = ($subtotal * floatval($rate->tax_rate)) / 100;
                    $tax_total += $tax_amount;
                    $tax_lines[] = [
                        'label' => $rate->tax_rate_name ?: "Tax ({$rate->tax_rate}%)",
                        'amount' => round($tax_amount, 2)
                    ];
                }
            } else {
                // Fallback to hardcoded rate if no WooCommerce rates found
                $fallback_rate = 13; // 13% as per frontend current implementation
                $tax_amount = ($subtotal * $fallback_rate) / 100;
                $tax_total = $tax_amount;
                $tax_lines[] = [
                    'label' => "Tax ({$fallback_rate}%)",
                    'amount' => round($tax_amount, 2)
                ];
            }
            
            // Log successful tax calculation
            $this->log_success('tax_calculation', [
                'country' => $country,
                'state' => $state,
                'subtotal' => $subtotal,
                'tax_total' => $tax_total
            ]);
            
            // Return exact format expected by frontend payment store
            return $this->error_handler->success_response([
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($tax_total, 2),
                'total_with_tax' => round($subtotal + $tax_total, 2),
                'tax_lines' => $tax_lines
            ]);
            
        } catch (Exception $e) {
            error_log('HSM Tax Calculation Error: ' . $e->getMessage());
            return $this->error_handler->server_error('Tax calculation failed');
        }
    }
    
    /**
     * Get WooCommerce tax rates for given location
     * 
     * @param string $country Country code
     * @param string $state State code
     * @param string $postal_code Postal code
     * @return array Tax rates
     */
    private function get_woocommerce_tax_rates($country, $state, $postal_code) {
        global $wpdb;
        
        $query = "
            SELECT tr.*, trl.location_code
            FROM {$wpdb->prefix}woocommerce_tax_rates tr
            LEFT JOIN {$wpdb->prefix}woocommerce_tax_rate_locations trl ON tr.tax_rate_id = trl.tax_rate_id
            WHERE tr.tax_rate_country = %s 
            AND (tr.tax_rate_state = %s OR tr.tax_rate_state = '') 
            AND (trl.location_code = %s OR trl.location_code IS NULL OR trl.location_code = '')
            ORDER BY tr.tax_rate_priority ASC
        ";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $country, $state, $postal_code)
        );
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