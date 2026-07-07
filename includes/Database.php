<?php
/**
 * Database management class
 *
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database management class
 */
class HSM_Database {
    
    /**
     * Plugin instance
     *
     * @var HSM_Database
     */
    private static $instance = null;
    
    /**
     * Database version
     *
     * @var string
     */
    private $db_version = '1.0.0';
    
    /**
     * Get plugin instance
     *
     * @return HSM_Database
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
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Stripe logs table
        $table_name = $wpdb->prefix . 'hsm_stripe_logs';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            action varchar(50) NOT NULL,
            payment_intent_id varchar(255),
            order_id bigint(20),
            status varchar(20),
            data longtext,
            error_message text,
            PRIMARY KEY (id),
            KEY payment_intent_id (payment_intent_id),
            KEY order_id (order_id),
            KEY status (status),
            KEY time (time)
        ) $charset_collate;";
        
        // Tax calculation cache table
        $tax_cache_table = $wpdb->prefix . 'hsm_tax_cache';
        $tax_sql = "CREATE TABLE $tax_cache_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            cache_key varchar(255) NOT NULL,
            country varchar(2) NOT NULL,
            state varchar(100),
            postal_code varchar(20),
            tax_rate decimal(5,4) NOT NULL,
            tax_amount decimal(10,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            expires_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY cache_key (cache_key),
            KEY country_state (country, state),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // Order metadata table
        $order_meta_table = $wpdb->prefix . 'hsm_order_metadata';
        $order_meta_sql = "CREATE TABLE $order_meta_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY meta_key (meta_key)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql);
        dbDelta($tax_sql);
        dbDelta($order_meta_sql);
        
        // Update database version
        update_option('hsm_db_version', self::$instance->db_version);
    }
    
    /**
     * Drop database tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'hsm_stripe_logs',
            $wpdb->prefix . 'hsm_tax_cache',
            $wpdb->prefix . 'hsm_order_metadata'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Remove database version
        delete_option('hsm_db_version');
    }
    
    /**
     * Check if database needs update
     */
    public static function needs_update() {
        $current_version = get_option('hsm_db_version', '0.0.0');
        return version_compare($current_version, self::$instance->db_version, '<');
    }
    
    /**
     * Update database
     */
    public static function update_database() {
        if (self::needs_update()) {
            self::create_tables();
        }
    }
    
    /**
     * Log Stripe activity
     */
    public static function log_stripe_activity($action, $data = array(), $payment_intent_id = null, $order_id = null, $status = null, $error_message = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hsm_stripe_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'action' => sanitize_text_field($action),
                'payment_intent_id' => sanitize_text_field($payment_intent_id),
                'order_id' => intval($order_id),
                'status' => sanitize_text_field($status),
                'data' => wp_json_encode($data),
                'error_message' => sanitize_text_field($error_message)
            ),
            array(
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s'
            )
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get Stripe logs
     */
    public static function get_stripe_logs($limit = 50, $offset = 0, $filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hsm_stripe_logs';
        $where_conditions = array('1=1');
        $where_values = array();
        
        if (!empty($filters['action'])) {
            $where_conditions[] = 'action = %s';
            $where_values[] = $filters['action'];
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'time >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'time <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE $where_clause ORDER BY time DESC LIMIT %d OFFSET %d",
            array_merge($where_values, array($limit, $offset))
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Cache tax calculation
     */
    public static function cache_tax_calculation($cache_key, $country, $state, $postal_code, $tax_rate, $tax_amount, $expires_in_hours = 24) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hsm_tax_cache';
        $expires_at = date('Y-m-d H:i:s', time() + ($expires_in_hours * HOUR_IN_SECONDS));
        
        $wpdb->replace(
            $table_name,
            array(
                'cache_key' => sanitize_text_field($cache_key),
                'country' => sanitize_text_field($country),
                'state' => sanitize_text_field($state),
                'postal_code' => sanitize_text_field($postal_code),
                'tax_rate' => floatval($tax_rate),
                'tax_amount' => floatval($tax_amount),
                'expires_at' => $expires_at
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
                '%f',
                '%f',
                '%s'
            )
        );
    }
    
    /**
     * Get cached tax calculation
     */
    public static function get_cached_tax_calculation($cache_key) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hsm_tax_cache';
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE cache_key = %s AND expires_at > %s",
                $cache_key,
                current_time('mysql')
            )
        );
        
        return $result;
    }
    
    /**
     * Clear expired tax cache
     */
    public static function clear_expired_tax_cache() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hsm_tax_cache';
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE expires_at < %s",
                current_time('mysql')
            )
        );
    }
    
    /**
     * Store order metadata
     */
    public static function store_order_metadata($order_id, $meta_key, $meta_value) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hsm_order_metadata';
        
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => intval($order_id),
                'meta_key' => sanitize_text_field($meta_key),
                'meta_value' => wp_json_encode($meta_value)
            ),
            array(
                '%d',
                '%s',
                '%s'
            )
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get order metadata
     */
    public static function get_order_metadata($order_id, $meta_key = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hsm_order_metadata';
        
        if ($meta_key) {
            $result = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT meta_value FROM $table_name WHERE order_id = %d AND meta_key = %s",
                    $order_id,
                    $meta_key
                )
            );
            
            return $result ? json_decode($result, true) : null;
        } else {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT meta_key, meta_value FROM $table_name WHERE order_id = %d",
                    $order_id
                )
            );
            
            $metadata = array();
            foreach ($results as $result) {
                $metadata[$result->meta_key] = json_decode($result->meta_value, true);
            }
            
            return $metadata;
        }
    }
}