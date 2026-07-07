<?php
/**
 * HSM Health Manager Class
 * 
 * Unified health management system that consolidates all health check functionality
 * across the HSM plugin. This replaces 8 different health check implementations
 * with a single, consistent, and efficient system.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Health_Manager {
    
    /**
     * Singleton instance
     * 
     * @var HSM_Health_Manager
     */
    private static $instance = null;
    
    /**
     * Health check cache
     * 
     * @var array
     */
    private $health_cache = [];
    
    /**
     * Cache expiration time (5 minutes)
     * 
     * @var int
     */
    private $cache_expiration = 300;
    
    /**
     * Error handler instance
     * 
     * @var HSM_Error_Handler
     */
    private $error_handler;
    
    /**
     * Get singleton instance
     *
     * @return HSM_Health_Manager
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
    }
    
    /**
     * Get comprehensive health status
     * 
     * @param bool $use_cache Whether to use cached results
     * @return array Health status data
     */
    public function get_health_status($use_cache = true) {
        $cache_key = 'hsm_health_status';
        
        // Check cache first
        if ($use_cache && isset($this->health_cache[$cache_key])) {
            $cached_data = $this->health_cache[$cache_key];
            if (time() - $cached_data['timestamp'] < $this->cache_expiration) {
                return $cached_data['data'];
            }
        }
        
        // Perform health checks
        $health_data = [
            'status' => 'healthy',
            'timestamp' => current_time('Y-m-d H:i:s'),
            'version' => '2.0.0',
            'checks' => $this->perform_all_health_checks(),
            'summary' => $this->generate_health_summary()
        ];
        
        // Cache the results
        $this->health_cache[$cache_key] = [
            'data' => $health_data,
            'timestamp' => time()
        ];
        
        return $health_data;
    }
    
    /**
     * Get quick health status (lightweight)
     * 
     * @return array Quick health status
     */
    public function get_quick_health_status() {
        return [
            'status' => 'healthy',
            'timestamp' => current_time('Y-m-d H:i:s'),
            'dependencies_ok' => $this->check_core_dependencies(),
            'graphql_operational' => $this->check_graphql_availability(),
            'api_operational' => $this->check_api_availability()
        ];
    }
    
    /**
     * Perform all health checks
     * 
     * @return array Health check results
     */
    private function perform_all_health_checks() {
        $checks = [];
        
        // Core system checks
        $checks['wordpress'] = $this->check_wordpress_health();
        $checks['php'] = $this->check_php_health();
        $checks['database'] = $this->check_database_health();
        $checks['memory'] = $this->check_memory_health();
        
        // Plugin dependency checks
        $checks['dependencies'] = $this->check_plugin_dependencies();
        
        // API health checks
        $checks['rest_api'] = $this->check_rest_api_health();
        $checks['graphql'] = $this->check_graphql_health();
        
        // Integration checks
        $checks['stripe'] = $this->check_stripe_health();
        $checks['woocommerce'] = $this->check_woocommerce_health();
        
        // Security checks
        $checks['security'] = $this->check_security_health();
        
        return $checks;
    }
    
    /**
     * Check WordPress health
     * 
     * @return array WordPress health status
     */
    private function check_wordpress_health() {
        return [
            'status' => 'healthy',
            'version' => get_bloginfo('version'),
            'multisite' => is_multisite(),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ];
    }
    
    /**
     * Check PHP health
     * 
     * @return array PHP health status
     */
    private function check_php_health() {
        $php_version = PHP_VERSION;
        $required_version = '7.4';
        
        return [
            'status' => version_compare($php_version, $required_version, '>=') ? 'healthy' : 'warning',
            'version' => $php_version,
            'required_version' => $required_version,
            'extensions' => $this->check_php_extensions(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ];
    }
    
    /**
     * Check database health
     * 
     * @return array Database health status
     */
    private function check_database_health() {
        global $wpdb;
        
        try {
            $result = $wpdb->get_var("SELECT 1");
            $status = ($result === '1') ? 'healthy' : 'error';
        } catch (Exception $e) {
            $status = 'error';
        }
        
        return [
            'status' => $status,
            'connection' => $status === 'healthy',
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate,
            'last_error' => $wpdb->last_error
        ];
    }
    
    /**
     * Check memory health
     * 
     * @return array Memory health status
     */
    private function check_memory_health() {
        $memory_usage = memory_get_usage(true);
        $memory_limit = $this->convert_to_bytes(ini_get('memory_limit'));
        $usage_percentage = ($memory_limit > 0) ? ($memory_usage / $memory_limit) * 100 : 0;
        
        $status = 'healthy';
        if ($usage_percentage > 90) {
            $status = 'critical';
        } elseif ($usage_percentage > 75) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'usage' => $memory_usage,
            'limit' => $memory_limit,
            'percentage' => round($usage_percentage, 2),
            'peak_usage' => memory_get_peak_usage(true)
        ];
    }
    
    /**
     * Check plugin dependencies
     * 
     * @return array Plugin dependency status
     */
    private function check_plugin_dependencies() {
        $dependencies = [
            'woocommerce' => $this->is_plugin_active('woocommerce/woocommerce.php'),
            'wp_graphql' => $this->is_plugin_active('wp-graphql/wp-graphql.php'),
            'woocommerce_graphql' => $this->is_plugin_active('wp-graphql-woocommerce/wp-graphql-woocommerce.php')
        ];
        
        $all_active = !in_array(false, $dependencies, true);
        
        return [
            'status' => $all_active ? 'healthy' : 'warning',
            'dependencies' => $dependencies,
            'all_active' => $all_active
        ];
    }
    
    /**
     * Check REST API health
     * 
     * @return array REST API health status
     */
    private function check_rest_api_health() {
        $rest_url = rest_url('hsm/v1/health');
        $response = wp_remote_get($rest_url, ['timeout' => 5]);
        
        if (is_wp_error($response)) {
            return [
                'status' => 'error',
                'available' => false,
                'error' => $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        return [
            'status' => $status_code === 200 ? 'healthy' : 'error',
            'available' => $status_code === 200,
            'status_code' => $status_code,
            'response_time' => $this->get_response_time($response)
        ];
    }
    
    /**
     * Check GraphQL health
     * 
     * @return array GraphQL health status
     */
    private function check_graphql_health() {
        $graphql_url = rest_url('hsm-graphql/v1/status');
        $response = wp_remote_get($graphql_url, ['timeout' => 5]);
        
        if (is_wp_error($response)) {
            return [
                'status' => 'error',
                'available' => false,
                'error' => $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        return [
            'status' => $status_code === 200 ? 'healthy' : 'error',
            'available' => $status_code === 200,
            'status_code' => $status_code,
            'response_time' => $this->get_response_time($response)
        ];
    }
    
    /**
     * Check Stripe health
     * 
     * @return array Stripe health status
     */
    private function check_stripe_health() {
        $secret_key = get_option('hsm_stripe_secret_key', '');
        $webhook_secret = get_option('hsm_stripe_webhook_secret', '');
        
        return [
            'status' => !empty($secret_key) ? 'healthy' : 'warning',
            'secret_key_configured' => !empty($secret_key),
            'webhook_secret_configured' => !empty($webhook_secret),
            'test_mode' => strpos($secret_key, 'sk_test_') === 0
        ];
    }
    
    /**
     * Check WooCommerce health
     * 
     * @return array WooCommerce health status
     */
    private function check_woocommerce_health() {
        if (!class_exists('WooCommerce')) {
            return [
                'status' => 'error',
                'available' => false,
                'version' => null
            ];
        }
        
        return [
            'status' => 'healthy',
            'available' => true,
            'version' => WC()->version,
            'api_available' => class_exists('WC_REST_API'),
            'order_management' => class_exists('WC_Order')
        ];
    }
    
    /**
     * Check security health
     * 
     * @return array Security health status
     */
    private function check_security_health() {
        return [
            'status' => 'healthy',
            'ssl_enabled' => is_ssl(),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'file_permissions' => $this->check_file_permissions(),
            'security_headers' => $this->check_security_headers()
        ];
    }
    
    /**
     * Check core dependencies
     * 
     * @return bool Whether core dependencies are available
     */
    private function check_core_dependencies() {
        return class_exists('WooCommerce') && 
               function_exists('wp_remote_get') && 
               function_exists('rest_url');
    }
    
    /**
     * Check GraphQL availability
     * 
     * @return bool Whether GraphQL is available
     */
    private function check_graphql_availability() {
        return $this->is_plugin_active('wp-graphql/wp-graphql.php') &&
               $this->is_plugin_active('wp-graphql-woocommerce/wp-graphql-woocommerce.php');
    }
    
    /**
     * Check API availability
     * 
     * @return bool Whether APIs are available
     */
    private function check_api_availability() {
        $rest_url = rest_url('hsm/v1/health');
        $response = wp_remote_get($rest_url, ['timeout' => 3]);
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Check PHP extensions
     * 
     * @return array PHP extension status
     */
    private function check_php_extensions() {
        $required_extensions = ['curl', 'json', 'mbstring', 'openssl'];
        $extensions = [];
        
        foreach ($required_extensions as $ext) {
            $extensions[$ext] = extension_loaded($ext);
        }
        
        return $extensions;
    }
    
    /**
     * Check file permissions
     * 
     * @return array File permission status
     */
    private function check_file_permissions() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/hsm-logs';
        
        return [
            'upload_dir_writable' => wp_is_writable($upload_dir['basedir']),
            'log_dir_writable' => wp_is_writable($log_dir),
            'plugin_dir_writable' => wp_is_writable(plugin_dir_path(__FILE__))
        ];
    }
    
    /**
     * Check security headers
     * 
     * @return array Security header status
     */
    private function check_security_headers() {
        $headers = [];
        
        if (function_exists('headers_list')) {
            $header_list = headers_list();
            foreach ($header_list as $header) {
                if (stripos($header, 'X-') === 0) {
                    $headers[] = $header;
                }
            }
        }
        
        return [
            'headers_present' => count($headers) > 0,
            'headers' => $headers
        ];
    }
    
    /**
     * Generate health summary
     * 
     * @return array Health summary
     */
    private function generate_health_summary() {
        $checks = $this->perform_all_health_checks();
        $statuses = array_column($checks, 'status');
        
        $healthy_count = count(array_filter($statuses, function($status) {
            return $status === 'healthy';
        }));
        
        $warning_count = count(array_filter($statuses, function($status) {
            return $status === 'warning';
        }));
        
        $error_count = count(array_filter($statuses, function($status) {
            return $status === 'error';
        }));
        
        $total_checks = count($statuses);
        
        return [
            'total_checks' => $total_checks,
            'healthy' => $healthy_count,
            'warnings' => $warning_count,
            'errors' => $error_count,
            'overall_status' => $error_count > 0 ? 'error' : ($warning_count > 0 ? 'warning' : 'healthy'),
            'health_score' => round(($healthy_count / $total_checks) * 100, 2)
        ];
    }
    
    /**
     * Get response time from HTTP response
     * 
     * @param array $response HTTP response
     * @return float Response time in milliseconds
     */
    private function get_response_time($response) {
        $headers = wp_remote_retrieve_headers($response);
        if (isset($headers['x-response-time'])) {
            return floatval($headers['x-response-time']);
        }
        return 0;
    }
    
    /**
     * Convert memory limit string to bytes
     * 
     * @param string $val Memory limit string
     * @return int Memory limit in bytes
     */
    private function convert_to_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int) $val;
        
        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        
        return $val;
    }
    
    /**
     * Check if plugin is active
     * 
     * @param string $plugin_file Plugin file path
     * @return bool Whether plugin is active
     */
    private function is_plugin_active($plugin_file) {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        return function_exists('is_plugin_active') ? is_plugin_active($plugin_file) : false;
    }
    
    /**
     * Clear health cache
     * 
     * @return void
     */
    public function clear_cache() {
        $this->health_cache = [];
    }
    
    /**
     * Get cache status
     * 
     * @return array Cache status
     */
    public function get_cache_status() {
        return [
            'cached_entries' => count($this->health_cache),
            'cache_expiration' => $this->cache_expiration,
            'cache_keys' => array_keys($this->health_cache)
        ];
    }
}