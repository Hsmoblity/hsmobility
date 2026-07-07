<?php
/**
 * Cache management class
 *
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache management class
 */
class HSM_Cache {
    
    /**
     * Plugin instance
     *
     * @var HSM_Cache
     */
    private static $instance = null;
    
    /**
     * Cache prefix
     *
     * @var string
     */
    private $prefix = 'hsm_';
    
    /**
     * Get plugin instance
     *
     * @return HSM_Cache
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
     * Get cached data
     *
     * @param string $key Cache key
     * @return mixed
     */
    public function get($key) {
        return get_transient($this->prefix . $key);
    }
    
    /**
     * Set cached data
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $expiration Expiration time in seconds
     * @return bool
     */
    public function set($key, $data, $expiration = HOUR_IN_SECONDS) {
        return set_transient($this->prefix . $key, $data, $expiration);
    }
    
    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @return bool
     */
    public function delete($key) {
        return delete_transient($this->prefix . $key);
    }
    
    /**
     * Clear all cache
     */
    public function clear_all() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $this->prefix . '%',
                '_transient_timeout_' . $this->prefix . '%'
            )
        );
    }
}