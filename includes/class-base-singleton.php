<?php
/**
 * HSM Base Singleton Class
 * 
 * Base class for implementing singleton pattern across HSM plugin classes.
 * Eliminates code duplication and provides consistent singleton implementation.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class HSM_Base_Singleton {
    
    /**
     * Singleton instances
     * 
     * @var array
     */
    private static $instances = array();
    
    /**
     * Get singleton instance
     * 
     * @return static Singleton instance
     */
    public static function get_instance() {
        $class = get_called_class();
        
        if (!isset(self::$instances[$class])) {
            if (method_exists($class, 'get_instance')) {
                self::$instances[$class] = call_user_func(array($class, 'get_instance'));
            } else {
                self::$instances[$class] = new $class();
            }
        }
        
        return self::$instances[$class];
    }
    
    /**
     * Constructor
     */
    protected function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the singleton (to be overridden by child classes)
     */
    protected function init() {
        // Override in child classes
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {
        // Prevent cloning
    }
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception('Cannot unserialize singleton');
    }
    
    /**
     * Reset singleton instance (for testing purposes)
     * 
     * @param string $class Class name
     */
    public static function reset_instance($class = null) {
        if ($class === null) {
            $class = get_called_class();
        }
        
        if (isset(self::$instances[$class])) {
            unset(self::$instances[$class]);
        }
    }
    
    /**
     * Reset all singleton instances (for testing purposes)
     */
    public static function reset_all_instances() {
        self::$instances = array();
    }
    
    /**
     * Check if instance exists
     * 
     * @param string $class Class name
     * @return bool Instance exists
     */
    public static function has_instance($class = null) {
        if ($class === null) {
            $class = get_called_class();
        }
        
        return isset(self::$instances[$class]);
    }
    
    /**
     * Get all singleton instances
     * 
     * @return array All instances
     */
    public static function get_all_instances() {
        return self::$instances;
    }
    
    /**
     * Get instance count
     * 
     * @return int Instance count
     */
    public static function get_instance_count() {
        return count(self::$instances);
    }
    
    /**
     * Log singleton creation
     * 
     * @param string $class Class name
     */
    protected function log_singleton_creation($class = null) {
        if ($class === null) {
            $class = get_called_class();
        }
        
        if (class_exists('HSM_Log_Manager')) {
            $log_manager = HSM_Log_Manager::get_instance();
            $log_manager->log("Singleton created: {$class}", 'debug');
        }
    }
    
    /**
     * Health check for singleton
     * 
     * @return array Health status
     */
    public function health_check() {
        // Use unified health manager if available, otherwise fallback to basic check
        if (class_exists('HSM_Health_Manager')) {
            $health_manager = HSM_Health_Manager::get_instance();
            $base_health = $health_manager->get_quick_health_status();
            
            // Add singleton-specific health data
            $base_health['singleton_info'] = array(
                'class' => get_called_class(),
                'instance_exists' => self::has_instance(),
                'instance_count' => self::get_instance_count()
            );
            
            return $base_health;
        }
        
        // Fallback to basic singleton health check
        return array(
            'status' => 'healthy',
            'class' => get_called_class(),
            'instance_exists' => self::has_instance(),
            'instance_count' => self::get_instance_count(),
            'note' => 'Using fallback health check - HSM_Health_Manager not available'
        );
    }
}