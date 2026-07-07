<?php
/**
 * HSM Security Manager Class
 * 
 * Provides comprehensive security management for the HSM plugin.
 * Handles authentication, authorization, input validation, and security monitoring.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Security_Manager {
    
    /**
     * Singleton instance
     * 
     * @var HSM_Security_Manager
     */
    private static $instance = null;
    
    /**
     * Security events log
     * 
     * @var array
     */
    private $security_events = array();
    
    /**
     * Rate limiting data
     * 
     * @var array
     */
    private $rate_limits = array();
    
    /**
     * Blocked IPs
     * 
     * @var array
     */
    private $blocked_ips = array();
    
    /**
     * Security settings
     * 
     * @var array
     */
    private $security_settings = array(
        'max_login_attempts' => 5,
        'lockout_duration' => 300, // 5 minutes
        'rate_limit_requests' => 100,
        'rate_limit_window' => 3600, // 1 hour
        'enable_csrf_protection' => true,
        'enable_xss_protection' => true,
        'enable_sql_injection_protection' => true,
        'enable_rate_limiting' => true,
        'enable_ip_blocking' => true,
        'log_security_events' => true
    );
    
    /**
     * Get singleton instance
     *
     * @return HSM_Security_Manager
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
        $this->load_security_settings();
        $this->initialize_security_hooks();
    }
    
    /**
     * Load security settings
     */
    private function load_security_settings() {
        if (class_exists('HSM_Options')) {
            $options = HSM_Options::get_instance();
            $this->security_settings = array_merge($this->security_settings, $options->get('security_settings', array()));
        }
    }
    
    /**
     * Initialize security hooks
     */
    private function initialize_security_hooks() {
        if ($this->security_settings['enable_csrf_protection']) {
            add_action('init', array($this, 'init_csrf_protection'));
        }
        
        if ($this->security_settings['enable_xss_protection']) {
            add_action('init', array($this, 'init_xss_protection'));
        }
        
        if ($this->security_settings['enable_rate_limiting']) {
            add_action('init', array($this, 'check_rate_limits'));
        }
    }
    
    /**
     * Initialize CSRF protection
     */
    public function init_csrf_protection() {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['hsm_csrf_token'])) {
            $_SESSION['hsm_csrf_token'] = $this->generate_csrf_token();
        }
    }
    
    /**
     * Initialize XSS protection
     */
    public function init_xss_protection() {
        // Add security headers
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }
    
    /**
     * Check rate limits
     */
    public function check_rate_limits() {
        $client_ip = $this->get_client_ip();
        $current_time = time();
        
        if (!isset($this->rate_limits[$client_ip])) {
            $this->rate_limits[$client_ip] = array(
                'requests' => 0,
                'window_start' => $current_time
            );
        }
        
        $rate_data = &$this->rate_limits[$client_ip];
        
        // Reset window if expired
        if ($current_time - $rate_data['window_start'] > $this->security_settings['rate_limit_window']) {
            $rate_data['requests'] = 0;
            $rate_data['window_start'] = $current_time;
        }
        
        // Check if rate limit exceeded
        if ($rate_data['requests'] >= $this->security_settings['rate_limit_requests']) {
            $this->log_security_event('rate_limit_exceeded', array(
                'ip' => $client_ip,
                'requests' => $rate_data['requests'],
                'window_start' => $rate_data['window_start']
            ));
            
            if ($this->security_settings['enable_ip_blocking']) {
                $this->block_ip($client_ip, 'Rate limit exceeded');
            }
            
            wp_die('Rate limit exceeded. Please try again later.', 'Rate Limit Exceeded', array('response' => 429));
        }
        
        $rate_data['requests']++;
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token CSRF token
     * @return bool Is valid
     */
    public function validate_csrf_token($token) {
        if (!session_id()) {
            session_start();
        }
        
        return isset($_SESSION['hsm_csrf_token']) && hash_equals($_SESSION['hsm_csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public function generate_csrf_token() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Get CSRF token
     * 
     * @return string CSRF token
     */
    public function get_csrf_token() {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['hsm_csrf_token'])) {
            $_SESSION['hsm_csrf_token'] = $this->generate_csrf_token();
        }
        
        return $_SESSION['hsm_csrf_token'];
    }
    
    /**
     * Sanitize input data
     * 
     * @param mixed $data Input data
     * @param string $type Data type
     * @return mixed Sanitized data
     */
    public function sanitize_input($data, $type = 'text') {
        if (is_array($data)) {
            return array_map(function($item) use ($type) {
                return $this->sanitize_input($item, $type);
            }, $data);
        }
        
        switch ($type) {
            case 'email':
                return sanitize_email($data);
            case 'url':
                return esc_url_raw($data);
            case 'text':
                return sanitize_text_field($data);
            case 'textarea':
                return sanitize_textarea_field($data);
            case 'int':
                return intval($data);
            case 'float':
                return floatval($data);
            case 'bool':
                return (bool) $data;
            case 'html':
                return wp_kses_post($data);
            default:
                return sanitize_text_field($data);
        }
    }
    
    /**
     * Validate input data
     * 
     * @param mixed $data Input data
     * @param array $rules Validation rules
     * @return array Validation result
     */
    public function validate_input($data, $rules) {
        $errors = array();
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "Field '{$field}' is required";
                continue;
            }
            
            if (empty($value) && !isset($rule['required'])) {
                continue;
            }
            
            if (isset($rule['type'])) {
                $sanitized = $this->sanitize_input($value, $rule['type']);
                if ($sanitized !== $value) {
                    $errors[$field] = "Field '{$field}' contains invalid data";
                    continue;
                }
            }
            
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = "Field '{$field}' must be at least {$rule['min_length']} characters";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = "Field '{$field}' must be no more than {$rule['max_length']} characters";
            }
            
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field] = "Field '{$field}' format is invalid";
            }
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * Check if IP is blocked
     * 
     * @param string $ip IP address
     * @return bool Is blocked
     */
    public function is_ip_blocked($ip) {
        return isset($this->blocked_ips[$ip]) && $this->blocked_ips[$ip]['expires'] > time();
    }
    
    /**
     * Block IP address
     * 
     * @param string $ip IP address
     * @param string $reason Block reason
     * @param int $duration Block duration in seconds
     */
    public function block_ip($ip, $reason = 'Security violation', $duration = 3600) {
        $this->blocked_ips[$ip] = array(
            'reason' => $reason,
            'blocked_at' => time(),
            'expires' => time() + $duration
        );
        
        $this->log_security_event('ip_blocked', array(
            'ip' => $ip,
            'reason' => $reason,
            'duration' => $duration
        ));
    }
    
    /**
     * Unblock IP address
     * 
     * @param string $ip IP address
     */
    public function unblock_ip($ip) {
        unset($this->blocked_ips[$ip]);
        
        $this->log_security_event('ip_unblocked', array(
            'ip' => $ip
        ));
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP
     */
    public function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Log security event
     * 
     * @param string $event Event type
     * @param array $data Event data
     */
    public function log_security_event($event, $data = array()) {
        if (!$this->security_settings['log_security_events']) {
            return;
        }
        
        $event_data = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'data' => $data
        );
        
        $this->security_events[] = $event_data;
        
        // Keep only last 1000 events
        if (count($this->security_events) > 1000) {
            array_shift($this->security_events);
        }
        
        // Log to file if log manager available
        if (class_exists('HSM_Log_Manager')) {
            $log_manager = HSM_Log_Manager::get_instance();
            $log_manager->log("Security Event: {$event}", 'warning', $event_data);
        }
    }
    
    /**
     * Get security events
     * 
     * @param int $limit Number of events to return
     * @return array Security events
     */
    public function get_security_events($limit = 100) {
        return array_slice($this->security_events, -$limit);
    }
    
    /**
     * Get blocked IPs
     * 
     * @return array Blocked IPs
     */
    public function get_blocked_ips() {
        $current_time = time();
        $active_blocks = array();
        
        foreach ($this->blocked_ips as $ip => $block_data) {
            if ($block_data['expires'] > $current_time) {
                $active_blocks[$ip] = $block_data;
            }
        }
        
        return $active_blocks;
    }
    
    /**
     * Get security statistics
     * 
     * @return array Security statistics
     */
    public function get_security_stats() {
        $events_by_type = array();
        foreach ($this->security_events as $event) {
            $events_by_type[$event['event']] = ($events_by_type[$event['event']] ?? 0) + 1;
        }
        
        return array(
            'total_events' => count($this->security_events),
            'events_by_type' => $events_by_type,
            'blocked_ips_count' => count($this->get_blocked_ips()),
            'rate_limits_count' => count($this->rate_limits)
        );
    }
    
    /**
     * Check user capabilities
     * 
     * @param string $capability Required capability
     * @return bool Has capability
     */
    public function check_capability($capability) {
        return current_user_can($capability);
    }
    
    /**
     * Verify nonce
     * 
     * @param string $nonce Nonce value
     * @param string $action Nonce action
     * @return bool Is valid
     */
    public function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }
    
    /**
     * Create nonce
     * 
     * @param string $action Nonce action
     * @return string Nonce value
     */
    public function create_nonce($action) {
        return wp_create_nonce($action);
    }
    
    /**
     * Health check for security manager
     * 
     * @return array Health status
     */
    public function health_check() {
        // Use unified health manager if available, otherwise fallback to basic check
        if (class_exists('HSM_Health_Manager')) {
            $health_manager = HSM_Health_Manager::get_instance();
            $base_health = $health_manager->get_quick_health_status();
            
            // Add security-specific health data
            $stats = $this->get_security_stats();
            $base_health['security_info'] = array(
                'total_events' => $stats['total_events'],
                'blocked_ips' => $stats['blocked_ips_count'],
                'rate_limits' => $stats['rate_limits_count'],
                'settings' => $this->security_settings
            );
            
            return $base_health;
        }
        
        // Fallback to basic security health check
        $stats = $this->get_security_stats();
        return array(
            'status' => 'healthy',
            'total_events' => $stats['total_events'],
            'blocked_ips' => $stats['blocked_ips_count'],
            'rate_limits' => $stats['rate_limits_count'],
            'settings' => $this->security_settings,
            'note' => 'Using fallback health check - HSM_Health_Manager not available'
        );
    }
}