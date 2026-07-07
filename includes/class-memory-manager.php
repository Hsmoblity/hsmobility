<?php
/**
 * HSM Memory Manager Class
 * 
 * Provides comprehensive memory management, lazy loading, and performance monitoring.
 * Implements enterprise-grade memory optimization with usage tracking and cleanup.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Memory_Manager {
    
    /**
     * Singleton instance
     * 
     * @var HSM_Memory_Manager
     */
    private static $instance = null;
    
    /**
     * Memory usage threshold (percentage)
     * 
     * @var int
     */
    private $memory_threshold = 80;
    
    /**
     * Maximum memory usage (bytes)
     * 
     * @var int
     */
    private $max_memory_usage = 0;
    
    /**
     * Current memory usage (bytes)
     * 
     * @var int
     */
    private $current_memory_usage = 0;
    
    /**
     * Memory usage history
     * 
     * @var array
     */
    private $memory_history = array();
    
    /**
     * Lazy loaded classes registry
     * 
     * @var array
     */
    private $lazy_classes = array();
    
    /**
     * Singleton classes registry
     * 
     * @var array
     */
    private $singleton_classes = array();
    
    /**
     * Singleton detection cache
     * 
     * @var array
     */
    private $singleton_detection_cache = array();
    
    /**
     * Loaded class instances
     * 
     * @var array
     */
    private $instances = array();
    
    /**
     * Memory monitoring enabled
     * 
     * @var bool
     */
    private $monitoring_enabled = true;
    
    /**
     * Get singleton instance
     *
     * @return HSM_Memory_Manager
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
        $this->initialize_memory_limits();
        $this->register_lazy_classes();
        $this->register_singleton_classes();
        $this->start_memory_monitoring();
    }
    
    /**
     * Initialize memory limits
     */
    private function initialize_memory_limits() {
        $memory_limit = ini_get('memory_limit');
        
        if ($memory_limit === '-1') {
            $this->max_memory_usage = 128 * 1024 * 1024; // 128MB default
        } else {
            $this->max_memory_usage = $this->convert_to_bytes($memory_limit);
        }
        
        $this->current_memory_usage = memory_get_usage(true);
    }
    
    /**
     * Register lazy loaded classes
     */
    private function register_lazy_classes() {
        $this->lazy_classes = array(
            'HSM_GraphQL_Manager' => 'includes/api/class-graphql-manager.php',
            'HSM_GraphQL_Proxy_API' => 'includes/api/class-graphql-proxy-api.php',
            'HSM_GraphQL_Healthcheck_API' => 'includes/api/class-graphql-healthcheck-api.php',
            'HSM_GraphQL_Health_Monitor' => 'includes/api/class-graphql-health-monitor.php',
            'HSM_Admin_Menu' => 'includes/admin/class-admin-menu.php',
            'HSM_Admin_Pages' => 'includes/admin/class-admin-pages.php',
            'HSM_GraphQL_Testing_Page' => 'includes/admin/class-graphql-testing-page.php',
            'HSM_Logger' => 'includes/logging/class-logger.php',
            'HSM_Settings_Manager' => 'includes/settings/class-settings-manager.php',
            'HSM_Order_Manager' => 'includes/Order_Manager.php',
            'HSM_Payment_Processor' => 'includes/Payment_Processor.php',
            'HSM_Tax_Calculator' => 'includes/Tax_Calculator.php'
        );
        
        // Register known singleton classes for performance
        $this->singleton_classes = array(
            'HSM_Settings_Manager',
            'HSM_Logger',
            'HSM_Database_Optimizer',
            'HSM_Memory_Manager',
            'HSM_Log_Manager',
            'HSM_HTTP_Client'
        );
    }
    
    /**
     * Register singleton classes
     */
    private function register_singleton_classes() {
        // Singleton classes are already registered in register_lazy_classes
        // This method can be used for additional singleton registration
    }
    
    /**
     * Start memory monitoring
     */
    private function start_memory_monitoring() {
        if ($this->monitoring_enabled) {
            add_action('wp_loaded', array($this, 'monitor_memory_usage'));
            add_action('shutdown', array($this, 'cleanup_memory'));
        }
    }
    
    /**
     * Monitor memory usage
     */
    public function monitor_memory_usage() {
        $this->current_memory_usage = memory_get_usage(true);
        $memory_percentage = ($this->current_memory_usage / $this->max_memory_usage) * 100;
        
        // Record memory usage
        $this->memory_history[] = array(
            'timestamp' => time(),
            'usage' => $this->current_memory_usage,
            'percentage' => $memory_percentage,
            'peak' => memory_get_peak_usage(true)
        );
        
        // Keep only last 100 records
        if (count($this->memory_history) > 100) {
            array_shift($this->memory_history);
        }
        
        // Check if memory usage exceeds threshold
        if ($memory_percentage > $this->memory_threshold) {
            $this->handle_memory_warning($memory_percentage);
        }
        
        $this->log_memory_usage("Memory usage: " . $this->format_bytes($this->current_memory_usage) . 
            " ({$memory_percentage}%)", 'info');
    }
    
    /**
     * Handle memory warning
     * 
     * @param float $percentage Memory usage percentage
     */
    private function handle_memory_warning($percentage) {
        $this->log_memory_usage("Memory usage warning: {$percentage}% exceeds threshold of {$this->memory_threshold}%", 'warning');
        
        // Attempt to free up memory
        $this->cleanup_memory();
        
        // If still high, trigger garbage collection
        if (($this->current_memory_usage / $this->max_memory_usage) * 100 > $this->memory_threshold) {
            gc_collect_cycles();
            $this->log_memory_usage("Garbage collection triggered due to high memory usage", 'info');
        }
    }
    
    /**
     * Cleanup memory
     */
    public function cleanup_memory() {
        // Clear unused instances
        $this->cleanup_unused_instances();
        
        // Clear singleton detection cache
        $this->singleton_detection_cache = array();
        
        // Trigger garbage collection
        gc_collect_cycles();
        
        $this->log_memory_usage("Memory cleanup completed", 'info');
    }
    
    /**
     * Cleanup unused instances
     */
    private function cleanup_unused_instances() {
        foreach ($this->instances as $class_name => $instance) {
            // Check if instance is still needed
            if (!$this->is_instance_needed($class_name)) {
                unset($this->instances[$class_name]);
                $this->log_memory_usage("Cleaned up unused instance: {$class_name}", 'info');
            }
        }
    }
    
    /**
     * Check if instance is still needed
     * 
     * @param string $class_name Class name
     * @return bool Is needed
     */
    private function is_instance_needed($class_name) {
        // Simple heuristic - can be enhanced based on specific needs
        return true; // For now, keep all instances
    }
    
    /**
     * Lazy load class with singleton pattern detection
     * 
     * @param string $class_name Class name to load
     * @return object|false Class instance or false on failure
     */
    public function lazy_load_class($class_name) {
        if (isset($this->lazy_classes[$class_name])) {
            $file_path = plugin_dir_path(__FILE__) . '../' . $this->lazy_classes[$class_name];
            
            if (file_exists($file_path)) {
                require_once $file_path;
                
                if (class_exists($class_name)) {
                    $this->log_memory_usage("Lazy loaded: {$class_name}", 'info');
                    
                    // Check if it's a singleton class
                    if ($this->is_singleton_class($class_name)) {
                        return $this->get_singleton($class_name);
                    } else {
                        // Regular class instantiation with validation
                        if ($this->validate_constructor($class_name)) {
                            return new $class_name();
                        } else {
                            return false;
                        }
                    }
                }
            }
        }
        
        $this->log_memory_usage("Failed to lazy load: {$class_name}", 'warning');
        return false;
    }
    
    /**
     * Check if class is a singleton
     * 
     * @param string $class_name Class name to check
     * @return bool Is singleton class
     */
    private function is_singleton_class($class_name) {
        // Check cache first
        if (isset($this->singleton_detection_cache[$class_name])) {
            return $this->singleton_detection_cache[$class_name];
        }
        
        // Check known singleton classes first (performance optimization)
        if (in_array($class_name, $this->singleton_classes)) {
            $this->singleton_detection_cache[$class_name] = true;
            return true;
        }
        
        // Use reflection to detect singleton pattern
        if (class_exists($class_name)) {
            try {
                $reflection = new ReflectionClass($class_name);
                
                // Check for get_instance method
                if ($reflection->hasMethod('get_instance')) {
                    $get_instance_method = $reflection->getMethod('get_instance');
                    
                    // Check if get_instance is static and public
                    if ($get_instance_method->isStatic() && $get_instance_method->isPublic()) {
                        $this->singleton_detection_cache[$class_name] = true;
                        return true;
                    }
                }
                
                // Check for private constructor (common singleton pattern)
                if ($reflection->hasMethod('__construct')) {
                    $constructor = $reflection->getMethod('__construct');
                    if ($constructor->isPrivate()) {
                        $this->singleton_detection_cache[$class_name] = true;
                        return true;
                    }
                }
                
            } catch (Exception $e) {
                $this->log_memory_usage("Error detecting singleton pattern for {$class_name}: " . $e->getMessage(), 'warning');
            }
        }
        
        $this->singleton_detection_cache[$class_name] = false;
        return false;
    }
    
    /**
     * Validate constructor accessibility
     * 
     * @param string $class_name Class name
     * @return bool True if constructor is accessible, false otherwise
     */
    private function validate_constructor($class_name) {
        if (!class_exists($class_name)) {
            return false;
        }
        
        try {
            $reflection = new ReflectionClass($class_name);
            $constructor = $reflection->getConstructor();
            
            if ($constructor && $constructor->isPrivate()) {
                // Private constructor - should use singleton pattern
                $this->log_memory_usage("Private constructor detected for {$class_name}, using singleton", 'warning');
                return $this->get_singleton($class_name);
            }
            
            return true;
        } catch (Exception $e) {
            $this->log_memory_usage("Constructor validation failed for {$class_name}: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get singleton instance
     * 
     * @param string $class_name Class name
     * @return object|false Singleton instance or false on failure
     */
    public function get_singleton($class_name) {
        if (!isset($this->instances[$class_name])) {
            if (method_exists($class_name, 'get_instance')) {
                $this->instances[$class_name] = call_user_func(array($class_name, 'get_instance'));
            } else {
                // Fallback to lazy loading
                $this->instances[$class_name] = $this->lazy_load_class($class_name);
            }
        }
        
        return $this->instances[$class_name];
    }
    
    /**
     * Get class instance with lazy loading
     * 
     * @param string $class_name Class name
     * @return object|false Class instance or false on failure
     */
    public function get_class_instance($class_name) {
        if (isset($this->instances[$class_name])) {
            return $this->instances[$class_name];
        }
        
        // Check if it's a singleton class
        if ($this->is_singleton_class($class_name)) {
            return $this->get_singleton($class_name);
        } else {
            // Use lazy loading to load the class first
            $this->instances[$class_name] = $this->lazy_load_class($class_name);
        }
        
        return $this->instances[$class_name];
    }
    
    /**
     * Get memory usage statistics
     * 
     * @return array Memory statistics
     */
    public function get_memory_stats() {
        return array(
            'current_usage' => $this->current_memory_usage,
            'current_usage_formatted' => $this->format_bytes($this->current_memory_usage),
            'max_usage' => $this->max_memory_usage,
            'max_usage_formatted' => $this->format_bytes($this->max_memory_usage),
            'usage_percentage' => round(($this->current_memory_usage / $this->max_memory_usage) * 100, 2),
            'peak_usage' => memory_get_peak_usage(true),
            'peak_usage_formatted' => $this->format_bytes(memory_get_peak_usage(true)),
            'loaded_classes' => count($this->instances),
            'lazy_classes_count' => count($this->lazy_classes),
            'singleton_classes_count' => count($this->singleton_classes)
        );
    }
    
    /**
     * Get memory history
     * 
     * @return array Memory history
     */
    public function get_memory_history() {
        return $this->memory_history;
    }
    
    /**
     * Set memory threshold
     * 
     * @param int $threshold Threshold percentage
     */
    public function set_memory_threshold($threshold) {
        $this->memory_threshold = max(50, min(95, $threshold));
        $this->log_memory_usage("Memory threshold set to {$this->memory_threshold}%", 'info');
    }
    
    /**
     * Enable/disable memory monitoring
     * 
     * @param bool $enabled Monitoring enabled
     */
    public function set_monitoring_enabled($enabled) {
        $this->monitoring_enabled = $enabled;
        $this->log_memory_usage("Memory monitoring " . ($enabled ? 'enabled' : 'disabled'), 'info');
    }
    
    /**
     * Convert memory limit to bytes
     * 
     * @param string $memory_limit Memory limit string
     * @return int Bytes
     */
    private function convert_to_bytes($memory_limit) {
        $memory_limit = trim($memory_limit);
        $last = strtolower($memory_limit[strlen($memory_limit) - 1]);
        $memory_limit = (int) $memory_limit;
        
        switch ($last) {
            case 'g':
                $memory_limit *= 1024;
            case 'm':
                $memory_limit *= 1024;
            case 'k':
                $memory_limit *= 1024;
        }
        
        return $memory_limit;
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes Bytes
     * @return string Formatted string
     */
    private function format_bytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Log memory usage message
     * 
     * @param string $message Message
     * @param string $level Log level
     */
    private function log_memory_usage($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HSM Memory Manager [{$level}]: {$message}");
        }
    }
    
    /**
     * Force garbage collection
     */
    public function force_garbage_collection() {
        $before = memory_get_usage(true);
        gc_collect_cycles();
        $after = memory_get_usage(true);
        $freed = $before - $after;
        
        $this->log_memory_usage("Garbage collection freed " . $this->format_bytes($freed) . " of memory", 'info');
    }
    
    /**
     * Get loaded classes list
     * 
     * @return array Loaded classes
     */
    public function get_loaded_classes() {
        return array_keys($this->instances);
    }
    
    /**
     * Clear all instances (use with caution)
     */
    public function clear_all_instances() {
        $this->instances = array();
        $this->singleton_detection_cache = array();
        $this->log_memory_usage("All instances cleared", 'warning');
    }
}