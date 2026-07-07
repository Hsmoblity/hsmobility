<?php
/**
 * HSM Database Logger Class
 * 
 * Database-based logging for the HSM plugin
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Database_Logger extends HSM_Logger_Base {
    
    /**
     * Database table name
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Constructor
     *
     * @param string $min_level Minimum log level
     */
    public function __construct($min_level = self::LEVEL_INFO) {
        parent::__construct($min_level);
        
        global $wpdb;
        $this->table_name = ($wpdb && isset($wpdb->prefix)) ? $wpdb->prefix . 'hsm_stripe_logs' : 'hsm_stripe_logs';
    }
    
    /**
     * Log message to database
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function log($level, $message, $context = []) {
        if (!$this->should_log($level)) {
            return;
        }
        
        global $wpdb;
        
        $formatted_message = $this->format_message($level, $message, $context);
        
        $wpdb->insert(
            $this->table_name,
            [
                'time' => current_time('mysql'),
                'action' => $level,
                'data' => $formatted_message
            ],
            [
                '%s',
                '%s',
                '%s'
            ]
        );
    }
    
    /**
     * Get recent logs
     *
     * @param int $limit Number of logs to retrieve
     * @param string $level Filter by log level
     * @return array
     */
    public function get_recent_logs($limit = 100, $level = null) {
        global $wpdb;
        
        $where_clause = '';
        $where_values = [];
        
        if ($level) {
            $where_clause = 'WHERE action = %s';
            $where_values[] = $level;
        }
        
        $query = "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY time DESC LIMIT %d";
        $where_values[] = $limit;
        
        return $wpdb->get_results($wpdb->prepare($query, $where_values));
    }
    
    /**
     * Clear old logs
     *
     * @param int $days Number of days to keep
     * @return int Number of deleted logs
     */
    public function clear_old_logs($days = 30) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE time < %s",
            $cutoff_date
        ));
    }
    
    /**
     * Get log statistics
     *
     * @return array
     */
    public function get_log_stats() {
        global $wpdb;
        
        $stats = $wpdb->get_results(
            "SELECT action, COUNT(*) as count FROM {$this->table_name} GROUP BY action"
        );
        
        $result = [];
        foreach ($stats as $stat) {
            $result[$stat->action] = (int) $stat->count;
        }
        
        return $result;
    }
}