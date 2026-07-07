<?php
/**
 * HSM HTTP Client Class
 * 
 * Provides robust HTTP client with timeout handling, retry logic, and circuit breaker pattern.
 * Implements enterprise-grade HTTP request management with comprehensive error handling.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_HTTP_Client {
    
    /**
     * Singleton instance
     * 
     * @var HSM_HTTP_Client
     */
    private static $instance = null;
    
    /**
     * Default timeout (seconds)
     * 
     * @var int
     */
    private $default_timeout = 10;
    
    /**
     * Maximum retries
     * 
     * @var int
     */
    private $max_retries = 3;
    
    /**
     * Retry delay base (seconds)
     * 
     * @var int
     */
    private $retry_delay_base = 1;
    
    /**
     * Circuit breaker threshold
     * 
     * @var int
     */
    private $circuit_breaker_threshold = 5;
    
    /**
     * Circuit breaker timeout (seconds)
     * 
     * @var int
     */
    private $circuit_breaker_timeout = 60;
    
    /**
     * Request cache TTL (seconds)
     * 
     * @var int
     */
    private $cache_ttl = 300; // 5 minutes
    
    /**
     * Request cache
     * 
     * @var array
     */
    private $request_cache = array();
    
    /**
     * Circuit breaker state
     * 
     * @var array
     */
    private $circuit_breaker_state = array();
    
    /**
     * Request statistics
     * 
     * @var array
     */
    private $request_stats = array(
        'total_requests' => 0,
        'successful_requests' => 0,
        'failed_requests' => 0,
        'cached_requests' => 0,
        'circuit_breaker_trips' => 0
    );
    
    /**
     * Get singleton instance
     *
     * @return HSM_HTTP_Client
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
        $this->initialize_circuit_breaker();
    }
    
    /**
     * Initialize circuit breaker state
     */
    private function initialize_circuit_breaker() {
        $this->circuit_breaker_state = array(
            'open' => false,
            'failure_count' => 0,
            'last_failure_time' => 0,
            'next_attempt_time' => 0
        );
    }
    
    /**
     * Make HTTP request with comprehensive error handling
     * 
     * @param string $url Request URL
     * @param array $args Request arguments
     * @return array|false Response data or false on failure
     */
    public function request($url, $args = array()) {
        $this->request_stats['total_requests']++;
        
        // Check circuit breaker
        if ($this->is_circuit_breaker_open($url)) {
            $this->log_error("Circuit breaker open for URL: {$url}");
            return false;
        }
        
        // Check cache first
        $cache_key = $this->generate_cache_key($url, $args);
        if (isset($this->request_cache[$cache_key])) {
            $cached_response = $this->request_cache[$cache_key];
            if (time() - $cached_response['timestamp'] < $this->cache_ttl) {
                $this->request_stats['cached_requests']++;
                return $cached_response['data'];
            }
        }
        
        // Prepare request arguments
        $request_args = $this->prepare_request_args($args);
        
        // Execute request with retry logic
        $response = $this->execute_request_with_retry($url, $request_args);
        
        if ($response !== false) {
            $this->request_stats['successful_requests']++;
            $this->reset_circuit_breaker($url);
            
            // Cache successful response
            $this->request_cache[$cache_key] = array(
                'data' => $response,
                'timestamp' => time()
            );
            
            return $response;
        } else {
            $this->request_stats['failed_requests']++;
            $this->handle_request_failure($url);
            return false;
        }
    }
    
    /**
     * Execute request with retry logic
     * 
     * @param string $url Request URL
     * @param array $args Request arguments
     * @return array|false Response or false on failure
     */
    private function execute_request_with_retry($url, $args) {
        $last_error = null;
        
        for ($attempt = 1; $attempt <= $this->max_retries; $attempt++) {
            $response = wp_remote_request($url, $args);
            
            if (!is_wp_error($response)) {
                $response_code = wp_remote_retrieve_response_code($response);
                
                if ($response_code >= 200 && $response_code < 300) {
                    return array(
                        'body' => wp_remote_retrieve_body($response),
                        'headers' => wp_remote_retrieve_headers($response),
                        'response_code' => $response_code,
                        'attempt' => $attempt
                    );
                } else {
                    $last_error = "HTTP {$response_code}: " . wp_remote_retrieve_response_message($response);
                }
            } else {
                $last_error = $response->get_error_message();
            }
            
            // If not the last attempt, wait before retrying
            if ($attempt < $this->max_retries) {
                $delay = $this->calculate_retry_delay($attempt);
                $this->log_info("Request failed (attempt {$attempt}), retrying in {$delay} seconds: {$last_error}");
                sleep($delay);
            }
        }
        
        $this->log_error("Request failed after {$this->max_retries} attempts: {$last_error}");
        return false;
    }
    
    /**
     * Prepare request arguments
     * 
     * @param array $args Original arguments
     * @return array Prepared arguments
     */
    private function prepare_request_args($args) {
        $default_args = array(
            'timeout' => $this->default_timeout,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent' => 'HSM-Plugin/2.0.0',
            'blocking' => true,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'cookies' => array(),
            'sslverify' => true,
            'stream' => false,
            'filename' => null,
            'decompress' => true
        );
        
        return wp_parse_args($args, $default_args);
    }
    
    /**
     * Calculate retry delay with exponential backoff and jitter
     * 
     * @param int $attempt Current attempt number
     * @return int Delay in seconds
     */
    private function calculate_retry_delay($attempt) {
        $delay = $this->retry_delay_base * pow(2, $attempt - 1);
        $jitter = mt_rand(0, 1000) / 1000; // 0-1 second jitter
        return $delay + $jitter;
    }
    
    /**
     * Check if circuit breaker is open for URL
     * 
     * @param string $url URL to check
     * @return bool Is circuit breaker open
     */
    private function is_circuit_breaker_open($url) {
        $host = parse_url($url, PHP_URL_HOST);
        
        if (!isset($this->circuit_breaker_state[$host])) {
            $this->circuit_breaker_state[$host] = array(
                'open' => false,
                'failure_count' => 0,
                'last_failure_time' => 0,
                'next_attempt_time' => 0
            );
        }
        
        $state = $this->circuit_breaker_state[$host];
        
        if ($state['open']) {
            if (time() < $state['next_attempt_time']) {
                return true;
            } else {
                // Try to close circuit breaker
                $this->circuit_breaker_state[$host]['open'] = false;
                $this->circuit_breaker_state[$host]['failure_count'] = 0;
            }
        }
        
        return false;
    }
    
    /**
     * Handle request failure
     * 
     * @param string $url Failed request URL
     */
    private function handle_request_failure($url) {
        $host = parse_url($url, PHP_URL_HOST);
        
        if (!isset($this->circuit_breaker_state[$host])) {
            $this->circuit_breaker_state[$host] = array(
                'open' => false,
                'failure_count' => 0,
                'last_failure_time' => 0,
                'next_attempt_time' => 0
            );
        }
        
        $this->circuit_breaker_state[$host]['failure_count']++;
        $this->circuit_breaker_state[$host]['last_failure_time'] = time();
        
        // Open circuit breaker if threshold exceeded
        if ($this->circuit_breaker_state[$host]['failure_count'] >= $this->circuit_breaker_threshold) {
            $this->circuit_breaker_state[$host]['open'] = true;
            $this->circuit_breaker_state[$host]['next_attempt_time'] = time() + $this->circuit_breaker_timeout;
            $this->request_stats['circuit_breaker_trips']++;
            $this->log_error("Circuit breaker opened for host: {$host}");
        }
    }
    
    /**
     * Reset circuit breaker for URL
     * 
     * @param string $url URL to reset
     */
    private function reset_circuit_breaker($url) {
        $host = parse_url($url, PHP_URL_HOST);
        
        if (isset($this->circuit_breaker_state[$host])) {
            $this->circuit_breaker_state[$host]['failure_count'] = 0;
            $this->circuit_breaker_state[$host]['open'] = false;
        }
    }
    
    /**
     * Generate cache key for request
     * 
     * @param string $url Request URL
     * @param array $args Request arguments
     * @return string Cache key
     */
    private function generate_cache_key($url, $args) {
        $cache_data = array(
            'url' => $url,
            'method' => isset($args['method']) ? $args['method'] : 'GET',
            'body' => isset($args['body']) ? $args['body'] : '',
            'headers' => isset($args['headers']) ? $args['headers'] : array()
        );
        
        return 'hsm_http_' . md5(serialize($cache_data));
    }
    
    /**
     * Get request statistics
     * 
     * @return array Request statistics
     */
    public function get_request_stats() {
        $stats = $this->request_stats;
        
        if ($stats['total_requests'] > 0) {
            $stats['success_rate'] = round(($stats['successful_requests'] / $stats['total_requests']) * 100, 2);
            $stats['cache_hit_rate'] = round(($stats['cached_requests'] / $stats['total_requests']) * 100, 2);
        } else {
            $stats['success_rate'] = 0;
            $stats['cache_hit_rate'] = 0;
        }
        
        return $stats;
    }
    
    /**
     * Get circuit breaker state
     * 
     * @return array Circuit breaker state
     */
    public function get_circuit_breaker_state() {
        return $this->circuit_breaker_state;
    }
    
    /**
     * Clear request cache
     */
    public function clear_cache() {
        $this->request_cache = array();
        $this->log_info("HTTP request cache cleared");
    }
    
    /**
     * Set timeout
     * 
     * @param int $timeout Timeout in seconds
     */
    public function set_timeout($timeout) {
        $this->default_timeout = max(1, min(300, $timeout));
        $this->log_info("HTTP timeout set to {$this->default_timeout} seconds");
    }
    
    /**
     * Set retry settings
     * 
     * @param int $max_retries Maximum retries
     * @param int $delay_base Base delay in seconds
     */
    public function set_retry_settings($max_retries, $delay_base) {
        $this->max_retries = max(0, min(10, $max_retries));
        $this->retry_delay_base = max(1, min(60, $delay_base));
        $this->log_info("Retry settings updated: max_retries={$this->max_retries}, delay_base={$this->retry_delay_base}");
    }
    
    /**
     * Set circuit breaker settings
     * 
     * @param int $threshold Failure threshold
     * @param int $timeout Circuit breaker timeout
     */
    public function set_circuit_breaker_settings($threshold, $timeout) {
        $this->circuit_breaker_threshold = max(1, min(20, $threshold));
        $this->circuit_breaker_timeout = max(10, min(600, $timeout));
        $this->log_info("Circuit breaker settings updated: threshold={$this->circuit_breaker_threshold}, timeout={$this->circuit_breaker_timeout}");
    }
    
    /**
     * Log error message
     * 
     * @param string $message Error message
     */
    private function log_error($message) {
        error_log("HSM HTTP Client Error: {$message}");
    }
    
    /**
     * Log info message
     * 
     * @param string $message Info message
     */
    private function log_info($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HSM HTTP Client Info: {$message}");
        }
    }
    
    /**
     * Health check for HTTP client
     * 
     * @return array Health status
     */
    public function health_check() {
        // Use unified health manager if available, otherwise fallback to basic check
        if (class_exists('HSM_Health_Manager')) {
            $health_manager = HSM_Health_Manager::get_instance();
            $base_health = $health_manager->get_quick_health_status();
            
            // Add HTTP client-specific health data
            $stats = $this->get_request_stats();
            $base_health['http_client_info'] = array(
                'total_requests' => $stats['total_requests'],
                'success_rate' => $stats['success_rate'],
                'cache_hit_rate' => $stats['cache_hit_rate'],
                'circuit_breaker_trips' => $stats['circuit_breaker_trips'],
                'open_circuits' => count(array_filter($this->circuit_breaker_state, function($state) {
                    return $state['open'];
                }))
            );
            
            return $base_health;
        }
        
        // Fallback to basic HTTP client health check
        $stats = $this->get_request_stats();
        return array(
            'status' => 'healthy',
            'total_requests' => $stats['total_requests'],
            'success_rate' => $stats['success_rate'],
            'cache_hit_rate' => $stats['cache_hit_rate'],
            'circuit_breaker_trips' => $stats['circuit_breaker_trips'],
            'open_circuits' => count(array_filter($this->circuit_breaker_state, function($state) {
                return $state['open'];
            })),
            'note' => 'Using fallback health check - HSM_Health_Manager not available'
        );
    }
}