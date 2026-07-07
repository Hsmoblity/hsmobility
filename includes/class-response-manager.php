<?php
/**
 * HSM Response Manager Class
 * 
 * Unified response management system that standardizes error handling and
 * response patterns across the HSM plugin. This provides consistent API
 * responses and error handling throughout the application.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Response_Manager {
    
    /**
     * Singleton instance
     * 
     * @var HSM_Response_Manager
     */
    private static $instance = null;
    
    /**
     * Error handler instance
     * 
     * @var HSM_Error_Handler
     */
    private $error_handler;
    
    /**
     * Response statistics
     * 
     * @var array
     */
    private $response_stats = [
        'total_responses' => 0,
        'success_responses' => 0,
        'error_responses' => 0,
        'responses_by_type' => []
    ];
    
    /**
     * Get singleton instance
     *
     * @return HSM_Response_Manager
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
     * Create a successful response
     * 
     * @param mixed $data Response data
     * @param int $status_code HTTP status code
     * @param array $headers Additional headers
     * @return WP_REST_Response
     */
    public function success($data = null, $status_code = 200, $headers = []) {
        $this->update_stats('success', $status_code);
        
        $response_data = [
            'status' => 'success',
            'timestamp' => current_time('Y-m-d H:i:s'),
            'data' => $data
        ];
        
        return new WP_REST_Response($response_data, $status_code, $headers);
    }
    
    /**
     * Create an error response
     * 
     * @param string $message Error message
     * @param int $status_code HTTP status code
     * @param string $error_code Error code
     * @param array $details Additional error details
     * @param array $headers Additional headers
     * @return WP_REST_Response
     */
    public function error($message, $status_code = 400, $error_code = 'error', $details = [], $headers = []) {
        $this->update_stats('error', $status_code);
        
        // Log the error
        $this->error_handler->log_error($message, new Exception($message));
        
        $response_data = [
            'status' => 'error',
            'timestamp' => current_time('Y-m-d H:i:s'),
            'error' => [
                'code' => $error_code,
                'message' => $message,
                'details' => $details
            ]
        ];
        
        return new WP_REST_Response($response_data, $status_code, $headers);
    }
    
    /**
     * Create a validation error response
     * 
     * @param array $validation_errors Validation errors
     * @param string $message Error message
     * @param array $headers Additional headers
     * @return WP_REST_Response
     */
    public function validation_error($validation_errors, $message = 'Validation failed', $headers = []) {
        return $this->error(
            $message,
            422,
            'validation_error',
            ['validation_errors' => $validation_errors],
            $headers
        );
    }
    
    /**
     * Create an authentication error response
     * 
     * @param string $message Error message
     * @param array $headers Additional headers
     * @return WP_REST_Response
     */
    public function authentication_error($message = 'Authentication required', $headers = []) {
        return $this->error(
            $message,
            401,
            'authentication_required',
            [],
            $headers
        );
    }
    
    /**
     * Create an authorization error response
     * 
     * @param string $message Error message
     * @param array $headers Additional headers
     * @return WP_REST_Response
     */
    public function authorization_error($message = 'Insufficient permissions', $headers = []) {
        return $this->error(
            $message,
            403,
            'insufficient_permissions',
            [],
            $headers
        );
    }
    
    /**
     * Create a not found error response
     * 
     * @param string $message Error message
     * @param array $headers Additional headers
     * @return WP_REST_Response
     */
    public function not_found_error($message = 'Resource not found', $headers = []) {
        return $this->error(
            $message,
            404,
            'not_found',
            [],
            $headers
        );
    }
    
    /**
     * Create a rate limit error response
     * 
     * @param string $message Error message
     * @param int $retry_after Seconds to wait before retrying
     * @param array $headers Additional headers
     * @return WP_REST_Response
     */
    public function rate_limit_error($message = 'Rate limit exceeded', $retry_after = 60, $headers = []) {
        $headers['Retry-After'] = $retry_after;
        
        return $this->error(
            $message,
            429,
            'rate_limit_exceeded',
            ['retry_after' => $retry_after],
            $headers
        );
    }
    
    /**
     * Create a server error response
     * 
     * @param string $message Error message
     * @param array $details Additional error details
     * @param array $headers Additional headers
     * @return WP_REST_Response
     */
    public function server_error($message = 'Internal server error', $details = [], $headers = []) {
        return $this->error(
            $message,
            500,
            'internal_server_error',
            $details,
            $headers
        );
    }
    
    /**
     * Create a maintenance mode response
     * 
     * @param string $message Error message
     * @param array $headers Additional headers
     * @return WP_REST_Response
     */
    public function maintenance_error($message = 'Service temporarily unavailable', $headers = []) {
        return $this->error(
            $message,
            503,
            'service_unavailable',
            [],
            $headers
        );
    }
    
    /**
     * Create a health check response
     * 
     * @param array $health_data Health data
     * @param int $status_code HTTP status code
     * @param array $headers Additional headers
     * @return WP_REST_Response
     */
    public function health_response($health_data, $status_code = 200, $headers = []) {
        $response_data = [
            'status' => 'healthy',
            'timestamp' => current_time('Y-m-d H:i:s'),
            'version' => '2.0.0',
            'health' => $health_data
        ];
        
        return new WP_REST_Response($response_data, $status_code, $headers);
    }
    
    /**
     * Create a paginated response
     * 
     * @param array $data Response data
     * @param int $page Current page
     * @param int $per_page Items per page
     * @param int $total Total items
     * @param int $status_code HTTP status code
     * @param array $headers Additional headers
     * @return WP_REST_Response
     */
    public function paginated_response($data, $page, $per_page, $total, $status_code = 200, $headers = []) {
        $total_pages = ceil($total / $per_page);
        
        $response_data = [
            'status' => 'success',
            'timestamp' => current_time('Y-m-d H:i:s'),
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => $total_pages,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ]
        ];
        
        return new WP_REST_Response($response_data, $status_code, $headers);
    }
    
    /**
     * Create a cached response
     * 
     * @param mixed $data Response data
     * @param int $cache_ttl Cache TTL in seconds
     * @param int $status_code HTTP status code
     * @param array $headers Additional headers
     * @return WP_REST_Response
     */
    public function cached_response($data, $cache_ttl = 300, $status_code = 200, $headers = []) {
        $headers['Cache-Control'] = "public, max-age={$cache_ttl}";
        $headers['X-Cache-TTL'] = $cache_ttl;
        
        $response_data = [
            'status' => 'success',
            'timestamp' => current_time('Y-m-d H:i:s'),
            'cached' => true,
            'cache_ttl' => $cache_ttl,
            'data' => $data
        ];
        
        return new WP_REST_Response($response_data, $status_code, $headers);
    }
    
    /**
     * Handle exceptions and return appropriate response
     * 
     * @param Exception $exception Exception to handle
     * @param string $context Context where exception occurred
     * @param array $headers Additional headers
     * @return WP_REST_Response
     */
    public function handle_exception($exception, $context = 'API', $headers = []) {
        // Log the exception
        $this->error_handler->log_error(
            "Exception in {$context}: " . $exception->getMessage(),
            $exception
        );
        
        // Determine appropriate response based on exception type
        if ($exception instanceof InvalidArgumentException) {
            return $this->validation_error(
                [$exception->getMessage()],
                'Invalid argument provided'
            );
        }
        
        if ($exception instanceof UnauthorizedException) {
            return $this->authentication_error($exception->getMessage());
        }
        
        if ($exception instanceof ForbiddenException) {
            return $this->authorization_error($exception->getMessage());
        }
        
        if ($exception instanceof NotFoundException) {
            return $this->not_found_error($exception->getMessage());
        }
        
        if ($exception instanceof RateLimitException) {
            return $this->rate_limit_error($exception->getMessage());
        }
        
        // Default to server error for unknown exceptions
        return $this->server_error(
            'An unexpected error occurred',
            ['context' => $context],
            $headers
        );
    }
    
    /**
     * Validate and sanitize response data
     * 
     * @param mixed $data Data to validate
     * @param string $type Expected data type
     * @return mixed Validated and sanitized data
     */
    public function validate_response_data($data, $type = 'mixed') {
        switch ($type) {
            case 'array':
                return is_array($data) ? $data : [];
            case 'string':
                return is_string($data) ? sanitize_text_field($data) : '';
            case 'integer':
                return is_numeric($data) ? intval($data) : 0;
            case 'float':
                return is_numeric($data) ? floatval($data) : 0.0;
            case 'boolean':
                return (bool) $data;
            case 'object':
                return is_object($data) ? $data : (object) [];
            default:
                return $data;
        }
    }
    
    /**
     * Add CORS headers to response
     * 
     * @param WP_REST_Response $response Response object
     * @param array $allowed_origins Allowed origins
     * @return WP_REST_Response
     */
    public function add_cors_headers($response, $allowed_origins = []) {
        if (empty($allowed_origins)) {
            $allowed_origins = get_option('hsm_allowed_origins', ['*']);
        }
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array('*', $allowed_origins) || in_array($origin, $allowed_origins)) {
            $response->header('Access-Control-Allow-Origin', $origin);
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-WP-Nonce');
            $response->header('Access-Control-Allow-Credentials', 'true');
        }
        
        return $response;
    }
    
    /**
     * Update response statistics
     * 
     * @param string $type Response type
     * @param int $status_code HTTP status code
     * @return void
     */
    private function update_stats($type, $status_code) {
        $this->response_stats['total_responses']++;
        
        if ($type === 'success') {
            $this->response_stats['success_responses']++;
        } else {
            $this->response_stats['error_responses']++;
        }
        
        if (!isset($this->response_stats['responses_by_type'][$status_code])) {
            $this->response_stats['responses_by_type'][$status_code] = 0;
        }
        $this->response_stats['responses_by_type'][$status_code]++;
    }
    
    /**
     * Get response statistics
     * 
     * @return array Response statistics
     */
    public function get_response_stats() {
        return $this->response_stats;
    }
    
    /**
     * Reset response statistics
     * 
     * @return void
     */
    public function reset_stats() {
        $this->response_stats = [
            'total_responses' => 0,
            'success_responses' => 0,
            'error_responses' => 0,
            'responses_by_type' => []
        ];
    }
    
    /**
     * Get success rate
     * 
     * @return float Success rate as percentage
     */
    public function get_success_rate() {
        if ($this->response_stats['total_responses'] === 0) {
            return 0.0;
        }
        
        return round(
            ($this->response_stats['success_responses'] / $this->response_stats['total_responses']) * 100,
            2
        );
    }
    
    /**
     * Get error rate
     * 
     * @return float Error rate as percentage
     */
    public function get_error_rate() {
        if ($this->response_stats['total_responses'] === 0) {
            return 0.0;
        }
        
        return round(
            ($this->response_stats['error_responses'] / $this->response_stats['total_responses']) * 100,
            2
        );
    }
}