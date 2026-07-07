<?php
/**
 * WooCommerce HPOS Compatibility Class
 *
 * Handles High-Performance Order Storage compatibility for HSM plugin
 *
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * HPOS Compatibility class
 */
class HSM_HPOS_Compatibility {
    
    /**
     * Plugin instance
     *
     * @var HSM_HPOS_Compatibility
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return HSM_HPOS_Compatibility
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
        $this->init();
    }
    
    /**
     * Initialize HPOS compatibility
     */
    private function init() {
        // Declare HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
        
        // Add HPOS compatibility checks
        add_action('plugins_loaded', array($this, 'check_hpos_compatibility'));
        
        // Add order handling hooks for HPOS
        add_action('woocommerce_order_status_changed', array($this, 'handle_hpos_order_status_change'), 10, 3);
    }
    
    /**
     * Declare HPOS compatibility
     */
    public function declare_hpos_compatibility() {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', HSM_PLUGIN_PATH . 'hsm-stripe.php', true);
        }
    }
    
    /**
     * Check HPOS compatibility
     */
    public function check_hpos_compatibility() {
        // Check if HPOS is enabled
        if (class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class)) {
            $hpos_enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
            
            if ($hpos_enabled) {
                // Log HPOS compatibility status
                HSM_Logger::get_instance()->log_stripe_activity(
                    'hpos_compatibility_check',
                    array('hpos_enabled' => true),
                    null,
                    null,
                    'info'
                );
            }
        }
    }
    
    /**
     * Handle HPOS order status change
     *
     * @param int $order_id Order ID
     * @param string $old_status Old status
     * @param string $new_status New status
     */
    public function handle_hpos_order_status_change($order_id, $old_status, $new_status) {
        // Use HPOS-compatible order retrieval
        $order = wc_get_order($order_id);
        
        if (!$order || !$order->get_meta('_hsm_configurator_order')) {
            return;
        }
        
        // Log HPOS order status change
        HSM_Logger::get_instance()->log_stripe_activity(
            'hpos_order_status_changed',
            array(
                'order_id' => $order_id,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'hpos_enabled' => true
            ),
            $order->get_meta('_stripe_payment_intent_id'),
            $order_id,
            'success'
        );
    }
    
    /**
     * Check if HPOS is enabled
     *
     * @return bool
     */
    public function is_hpos_enabled() {
        if (class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class)) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }
    
    /**
     * Get order using HPOS-compatible method
     *
     * @param int $order_id Order ID
     * @return WC_Order|false
     */
    public function get_order($order_id) {
        return wc_get_order($order_id);
    }
    
    /**
     * Create order using HPOS-compatible method
     *
     * @param array $args Order arguments
     * @return WC_Order|WP_Error
     */
    public function create_order($args = array()) {
        return wc_create_order($args);
    }
    
    /**
     * Update order meta using HPOS-compatible method
     *
     * @param WC_Order $order Order object
     * @param string $key Meta key
     * @param mixed $value Meta value
     */
    public function update_order_meta($order, $key, $value) {
        $order->update_meta_data($key, $value);
        $order->save();
    }
    
    /**
     * Get order meta using HPOS-compatible method
     *
     * @param WC_Order $order Order object
     * @param string $key Meta key
     * @param bool $single Whether to return single value
     * @return mixed
     */
    public function get_order_meta($order, $key, $single = true) {
        return $order->get_meta($key, $single);
    }
    
    /**
     * Get orders using HPOS-compatible method
     *
     * @param array $args Query arguments
     * @return WC_Order[]
     */
    public function get_orders($args = array()) {
        return wc_get_orders($args);
    }
    
    /**
     * Get HPOS compatibility status
     *
     * @return array
     */
    public function get_compatibility_status() {
        $status = array(
            'hpos_enabled' => $this->is_hpos_enabled(),
            'compatibility_declared' => true,
            'plugin_version' => HSM_PLUGIN_VERSION,
            'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : 'Unknown'
        );
        
        return $status;
    }
}