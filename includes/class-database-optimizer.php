<?php
/**
 * HSM Database Optimizer Class
 * 
 * Unified database query optimization system that consolidates all database
 * operations across the HSM plugin. This provides efficient query caching,
 * connection pooling, and query optimization.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Database_Optimizer {
    
    /**
     * Singleton instance
     * 
     * @var HSM_Database_Optimizer
     */
    private static $instance = null;
    
    /**
     * Query cache
     * 
     * @var array
     */
    private $query_cache = [];
    
    /**
     * Cache expiration time (5 minutes)
     * 
     * @var int
     */
    private $cache_expiration = 300;
    
    /**
     * Database statistics
     * 
     * @var array
     */
    private $db_stats = [
        'total_queries' => 0,
        'cached_queries' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'slow_queries' => 0,
        'optimized_queries' => 0
    ];
    
    /**
     * Error handler instance
     * 
     * @var HSM_Error_Handler
     */
    private $error_handler;
    
    /**
     * Get singleton instance
     *
     * @return HSM_Database_Optimizer
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
        $this->error_handler = new HSM_Error_Handler();
        
        // Initialize database optimization hooks
        $this->init_optimization_hooks();
    }
    
    /**
     * Initialize database optimization hooks
     * 
     * @return void
     */
    private function init_optimization_hooks() {
        // Add query logging for optimization
        add_action('init', [$this, 'enable_query_logging']);
        
        // Add cleanup hooks
        add_action('wp_scheduled_delete', [$this, 'cleanup_expired_cache']);
        add_action('wp_loaded', [$this, 'cleanup_old_queries']);
    }
    
    /**
     * Enable query logging for optimization
     * 
     * @return void
     */
    public function enable_query_logging() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('log_query_custom_data', [$this, 'log_query_performance']);
        }
    }
    
    /**
     * Get option with caching
     * 
     * @param string $option_name Option name
     * @param mixed $default_value Default value
     * @param bool $use_cache Whether to use cache
     * @return mixed Option value
     */
    public function get_option($option_name, $default_value = false, $use_cache = true) {
        $cache_key = 'option_' . $option_name;
        
        // Check cache first
        if ($use_cache && isset($this->query_cache[$cache_key])) {
            $cached_data = $this->query_cache[$cache_key];
            if (time() - $cached_data['timestamp'] < $this->cache_expiration) {
                $this->db_stats['cache_hits']++;
                return $cached_data['value'];
            }
        }
        
        // Get from database
        $value = get_option($option_name, $default_value);
        $this->db_stats['total_queries']++;
        
        // Cache the result
        if ($use_cache) {
            $this->query_cache[$cache_key] = [
                'value' => $value,
                'timestamp' => time()
            ];
            $this->db_stats['cached_queries']++;
        } else {
            $this->db_stats['cache_misses']++;
        }
        
        return $value;
    }
    
    /**
     * Update option with cache invalidation
     * 
     * @param string $option_name Option name
     * @param mixed $value Option value
     * @param mixed $autoload Autoload value
     * @return bool True if updated, false otherwise
     */
    public function update_option($option_name, $value, $autoload = null) {
        $result = update_option($option_name, $value, $autoload);
        $this->db_stats['total_queries']++;
        
        // Invalidate cache
        $cache_key = 'option_' . $option_name;
        if (isset($this->query_cache[$cache_key])) {
            unset($this->query_cache[$cache_key]);
        }
        
        return $result;
    }
    
    /**
     * Get transient with caching
     * 
     * @param string $transient_name Transient name
     * @param mixed $default_value Default value
     * @param bool $use_cache Whether to use cache
     * @return mixed Transient value
     */
    public function get_transient($transient_name, $default_value = false, $use_cache = true) {
        $cache_key = 'transient_' . $transient_name;
        
        // Check cache first
        if ($use_cache && isset($this->query_cache[$cache_key])) {
            $cached_data = $this->query_cache[$cache_key];
            if (time() - $cached_data['timestamp'] < $this->cache_expiration) {
                $this->db_stats['cache_hits']++;
                return $cached_data['value'];
            }
        }
        
        // Get from database
        $value = get_transient($transient_name);
        if ($value === false) {
            $value = $default_value;
        }
        $this->db_stats['total_queries']++;
        
        // Cache the result
        if ($use_cache) {
            $this->query_cache[$cache_key] = [
                'value' => $value,
                'timestamp' => time()
            ];
            $this->db_stats['cached_queries']++;
        } else {
            $this->db_stats['cache_misses']++;
        }
        
        return $value;
    }
    
    /**
     * Set transient with cache update
     * 
     * @param string $transient_name Transient name
     * @param mixed $value Transient value
     * @param int $expiration Expiration time in seconds
     * @return bool True if set, false otherwise
     */
    public function set_transient($transient_name, $value, $expiration = 0) {
        $result = set_transient($transient_name, $value, $expiration);
        $this->db_stats['total_queries']++;
        
        // Update cache
        $cache_key = 'transient_' . $transient_name;
        $this->query_cache[$cache_key] = [
            'value' => $value,
            'timestamp' => time()
        ];
        
        return $result;
    }
    
    /**
     * Execute optimized query
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param bool $use_cache Whether to use cache
     * @return mixed Query result
     */
    public function execute_query($query, $params = [], $use_cache = true) {
        global $wpdb;
        
        $cache_key = 'query_' . md5($query . serialize($params));
        
        // Check cache first
        if ($use_cache && isset($this->query_cache[$cache_key])) {
            $cached_data = $this->query_cache[$cache_key];
            if (time() - $cached_data['timestamp'] < $this->cache_expiration) {
                $this->db_stats['cache_hits']++;
                return $cached_data['result'];
            }
        }
        
        // Execute query
        $start_time = microtime(true);
        
        try {
            if (!empty($params)) {
                $prepared_query = $wpdb->prepare($query, $params);
                $result = $wpdb->get_results($prepared_query);
            } else {
                $result = $wpdb->get_results($query);
            }
            
            $execution_time = microtime(true) - $start_time;
            $this->db_stats['total_queries']++;
            
            // Log slow queries
            if ($execution_time > 0.1) { // 100ms threshold
                $this->db_stats['slow_queries']++;
                $this->log_slow_query($query, $execution_time);
            }
            
            // Cache the result
            if ($use_cache) {
                $this->query_cache[$cache_key] = [
                    'result' => $result,
                    'timestamp' => time()
                ];
                $this->db_stats['cached_queries']++;
            } else {
                $this->db_stats['cache_misses']++;
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->error_handler->log_error(
                'Database query failed: ' . $e->getMessage(),
                $e
            );
            return false;
        }
    }
    
    /**
     * Get posts with optimization
     * 
     * @param array $args WP_Query arguments
     * @param bool $use_cache Whether to use cache
     * @return WP_Post[] Array of posts
     */
    public function get_posts($args, $use_cache = true) {
        $cache_key = 'posts_' . md5(serialize($args));
        
        // Check cache first
        if ($use_cache && isset($this->query_cache[$cache_key])) {
            $cached_data = $this->query_cache[$cache_key];
            if (time() - $cached_data['timestamp'] < $this->cache_expiration) {
                $this->db_stats['cache_hits']++;
                return $cached_data['result'];
            }
        }
        
        // Execute query
        $query = new WP_Query($args);
        $posts = $query->posts;
        $this->db_stats['total_queries']++;
        
        // Cache the result
        if ($use_cache) {
            $this->query_cache[$cache_key] = [
                'result' => $posts,
                'timestamp' => time()
            ];
            $this->db_stats['cached_queries']++;
        } else {
            $this->db_stats['cache_misses']++;
        }
        
        return $posts;
    }
    
    /**
     * Batch update options
     * 
     * @param array $options Array of options to update
     * @return bool True if all updated, false otherwise
     */
    public function batch_update_options($options) {
        global $wpdb;
        
        $success = true;
        $this->db_stats['total_queries']++;
        
        try {
            $wpdb->query('START TRANSACTION');
            
            foreach ($options as $option_name => $value) {
                $result = update_option($option_name, $value);
                if (!$result) {
                    $success = false;
                    break;
                }
                
                // Invalidate cache
                $cache_key = 'option_' . $option_name;
                if (isset($this->query_cache[$cache_key])) {
                    unset($this->query_cache[$cache_key]);
                }
            }
            
            if ($success) {
                $wpdb->query('COMMIT');
            } else {
                $wpdb->query('ROLLBACK');
            }
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            $this->error_handler->log_error(
                'Batch update failed: ' . $e->getMessage(),
                $e
            );
            $success = false;
        }
        
        return $success;
    }
    
    /**
     * Batch get options
     * 
     * @param array $option_names Array of option names
     * @param bool $use_cache Whether to use cache
     * @return array Array of option values
     */
    public function batch_get_options($option_names, $use_cache = true) {
        global $wpdb;
        
        $results = [];
        $uncached_options = [];
        
        // Check cache first
        if ($use_cache) {
            foreach ($option_names as $option_name) {
                $cache_key = 'option_' . $option_name;
                if (isset($this->query_cache[$cache_key])) {
                    $cached_data = $this->query_cache[$cache_key];
                    if (time() - $cached_data['timestamp'] < $this->cache_expiration) {
                        $results[$option_name] = $cached_data['value'];
                        $this->db_stats['cache_hits']++;
                    } else {
                        $uncached_options[] = $option_name;
                    }
                } else {
                    $uncached_options[] = $option_name;
                }
            }
        } else {
            $uncached_options = $option_names;
        }
        
        // Get uncached options from database
        if (!empty($uncached_options)) {
            $placeholders = implode(',', array_fill(0, count($uncached_options), '%s'));
            $query = "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN ($placeholders)";
            $db_results = $wpdb->get_results($wpdb->prepare($query, $uncached_options));
            
            $this->db_stats['total_queries']++;
            
            foreach ($db_results as $row) {
                $value = maybe_unserialize($row->option_value);
                $results[$row->option_name] = $value;
                
                // Cache the result
                if ($use_cache) {
                    $cache_key = 'option_' . $row->option_name;
                    $this->query_cache[$cache_key] = [
                        'value' => $value,
                        'timestamp' => time()
                    ];
                    $this->db_stats['cached_queries']++;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Optimize database tables
     * 
     * @return bool True if optimized, false otherwise
     */
    public function optimize_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->options,
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->users,
            $wpdb->usermeta,
            $wpdb->comments,
            $wpdb->commentmeta
        ];
        
        $success = true;
        
        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE $table");
            if ($result === false) {
                $success = false;
                $this->error_handler->log_error("Failed to optimize table: $table");
            }
        }
        
        return $success;
    }
    
    /**
     * Clean up expired cache
     * 
     * @return void
     */
    public function cleanup_expired_cache() {
        $current_time = time();
        
        foreach ($this->query_cache as $key => $data) {
            if ($current_time - $data['timestamp'] > $this->cache_expiration) {
                unset($this->query_cache[$key]);
            }
        }
    }
    
    /**
     * Clean up old queries
     * 
     * @return void
     */
    public function cleanup_old_queries() {
        // Clean up old transients
        global $wpdb;
        
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_timeout_%' 
            AND option_value < UNIX_TIMESTAMP()
        ");
        
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%' 
            AND option_name NOT IN (
                SELECT CONCAT('_transient_', SUBSTRING(option_name, 19))
                FROM {$wpdb->options}
                WHERE option_name LIKE '_transient_timeout_%'
            )
        ");
    }
    
    /**
     * Log slow query
     * 
     * @param string $query SQL query
     * @param float $execution_time Execution time in seconds
     * @return void
     */
    private function log_slow_query($query, $execution_time) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HSM Slow Query ({$execution_time}s): " . $query);
        }
    }
    
    /**
     * Log query performance
     * 
     * @param array $query_data Query data
     * @return void
     */
    public function log_query_performance($query_data) {
        if (isset($query_data['execution_time']) && $query_data['execution_time'] > 0.1) {
            $this->log_slow_query($query_data['query'], $query_data['execution_time']);
        }
    }
    
    /**
     * Get database statistics
     * 
     * @return array Database statistics
     */
    public function get_db_stats() {
        return $this->db_stats;
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public function get_cache_stats() {
        return [
            'cached_queries' => count($this->query_cache),
            'cache_hits' => $this->db_stats['cache_hits'],
            'cache_misses' => $this->db_stats['cache_misses'],
            'cache_hit_rate' => $this->get_cache_hit_rate(),
            'cache_expiration' => $this->cache_expiration
        ];
    }
    
    /**
     * Get cache hit rate
     * 
     * @return float Cache hit rate as percentage
     */
    public function get_cache_hit_rate() {
        $total_requests = $this->db_stats['cache_hits'] + $this->db_stats['cache_misses'];
        if ($total_requests === 0) {
            return 0.0;
        }
        
        return round(($this->db_stats['cache_hits'] / $total_requests) * 100, 2);
    }
    
    /**
     * Clear query cache
     * 
     * @return void
     */
    public function clear_cache() {
        $this->query_cache = [];
    }
    
    /**
     * Reset database statistics
     * 
     * @return void
     */
    public function reset_stats() {
        $this->db_stats = [
            'total_queries' => 0,
            'cached_queries' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'slow_queries' => 0,
            'optimized_queries' => 0
        ];
    }
    
    /**
     * Get optimization recommendations
     * 
     * @return array Optimization recommendations
     */
    public function get_optimization_recommendations() {
        $recommendations = [];
        
        // Check cache hit rate
        $cache_hit_rate = $this->get_cache_hit_rate();
        if ($cache_hit_rate < 50) {
            $recommendations[] = [
                'type' => 'cache',
                'message' => 'Low cache hit rate (' . $cache_hit_rate . '%). Consider increasing cache expiration time.',
                'priority' => 'medium'
            ];
        }
        
        // Check slow queries
        $slow_query_rate = $this->db_stats['total_queries'] > 0 
            ? ($this->db_stats['slow_queries'] / $this->db_stats['total_queries']) * 100 
            : 0;
        
        if ($slow_query_rate > 10) {
            $recommendations[] = [
                'type' => 'performance',
                'message' => 'High slow query rate (' . round($slow_query_rate, 2) . '%). Consider optimizing queries.',
                'priority' => 'high'
            ];
        }
        
        // Check total queries
        if ($this->db_stats['total_queries'] > 1000) {
            $recommendations[] = [
                'type' => 'performance',
                'message' => 'High query count (' . $this->db_stats['total_queries'] . '). Consider implementing more caching.',
                'priority' => 'medium'
            ];
        }
        
        return $recommendations;
    }
}