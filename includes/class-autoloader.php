<?php
/**
 * HSM Plugin Autoloader
 * 
 * Implements PSR-4 autoloading standard for HSM plugin classes
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Autoloader {
    
    /**
     * Plugin root directory
     * 
     * @var string
     */
    private static $plugin_root;
    
    /**
     * Namespace prefix
     * 
     * @var string
     */
    private static $namespace_prefix = 'HSM_';
    
    /**
     * Register the autoloader
     * 
     * @return void
     */
    public static function register() {
        self::$plugin_root = plugin_dir_path(__FILE__);
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    /**
     * Autoload classes
     * 
     * @param string $class_name Class name to load
     * @return void
     */
    public static function autoload($class_name) {
        // Only handle HSM classes
        if (strpos($class_name, self::$namespace_prefix) !== 0) {
            return;
        }
        
        // Remove namespace prefix
        $class_name = str_replace(self::$namespace_prefix, '', $class_name);
        
        // Convert class name to file path
        $file_name = self::class_name_to_file_name($class_name);
        
        // Try different directory locations
        $directories = [
            'includes/',
            'includes/api/',
            'includes/admin/',
            'includes/woocommerce/',
            'includes/stripe/',
            'includes/email/',
            'includes/error/',
            'includes/config/',
            'includes/utils/'
        ];
        
        foreach ($directories as $directory) {
            $file_path = self::$plugin_root . $directory . $file_name;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }
        
        // Log if class not found
        error_log("HSM Autoloader: Class {$class_name} not found in any directory");
    }
    
    /**
     * Convert class name to file name
     * 
     * @param string $class_name Class name
     * @return string File name
     */
    private static function class_name_to_file_name($class_name) {
        // Convert underscores to hyphens and make lowercase
        $file_name = str_replace('_', '-', strtolower($class_name));
        
        // Add class- prefix and .php extension
        return 'class-' . $file_name . '.php';
    }
    
    /**
     * Get plugin root directory
     * 
     * @return string Plugin root directory
     */
    public static function get_plugin_root() {
        return self::$plugin_root;
    }
    
    /**
     * Check if a class exists
     * 
     * @param string $class_name Class name to check
     * @return bool True if class exists
     */
    public static function class_exists($class_name) {
        return class_exists($class_name);
    }
}