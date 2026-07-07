<?php
/**
 * Tax calculator class
 *
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tax calculator class
 */
class HSM_Tax_Calculator {
    
    /**
     * Plugin instance
     *
     * @var HSM_Tax_Calculator
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return HSM_Tax_Calculator
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
     * Calculate tax
     *
     * @param string $country Country code
     * @param string $state State code
     * @param string $postal_code Postal code
     * @param array $items Cart items
     * @return array
     */
    public function calculate_tax($country, $state, $postal_code, $items) {
        // Calculate subtotal
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += floatval($item['price']) * intval($item['quantity']);
        }
        
        // Get tax rates
        $tax_rates = $this->get_tax_rates($country, $state, $postal_code);
        
        // Calculate tax
        $tax_total = 0;
        $tax_lines = array();
        
        if (!empty($tax_rates)) {
            foreach ($tax_rates as $rate) {
                $tax_amount = ($subtotal * floatval($rate->tax_rate)) / 100;
                $tax_total += $tax_amount;
                $tax_lines[] = array(
                    'label' => $rate->tax_rate_name ?: "Tax ({$rate->tax_rate}%)",
                    'amount' => round($tax_amount, 2)
                );
            }
        } else {
            // Fallback rate
            $fallback_rate = HSM_Options::get('tax_fallback_rate', 13.0);
            $tax_amount = ($subtotal * $fallback_rate) / 100;
            $tax_total = $tax_amount;
            $tax_lines[] = array(
                'label' => "Tax ({$fallback_rate}%)",
                'amount' => round($tax_amount, 2)
            );
        }
        
        return array(
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($tax_total, 2),
            'total_with_tax' => round($subtotal + $tax_total, 2),
            'tax_lines' => $tax_lines
        );
    }
    
    /**
     * Get tax rates from WooCommerce
     *
     * @param string $country Country code
     * @param string $state State code
     * @param string $postal_code Postal code
     * @return array
     */
    private function get_tax_rates($country, $state, $postal_code) {
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
}