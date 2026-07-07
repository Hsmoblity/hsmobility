<?php
/**
 * HSM General Settings Class
 * 
 * Manages general plugin settings
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_General_Settings extends HSM_Settings_Base {
    
    /**
     * Default general settings
     *
     * @var array
     */
    protected $defaults = [
        'tax_fallback_rate' => 13.0,
        'cache_tax_calculations' => true,
        'tax_cache_duration' => 24, // hours
        'enable_logging' => true,
        'log_retention_days' => 30,
        'api_rate_limit' => 100, // requests per minute
        'enable_cors' => true,
        'cors_origins' => [],
        'currency' => 'USD',
        'enable_webhooks' => true,
        'webhook_endpoint' => '',
        'order_status_mapping' => [
            'pending' => 'pending-payment',
            'processing' => 'processing',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded'
        ],
        // Login customization settings
        'enable_login_customization' => true, // Enable by default for testing
        'login_logo_url' => '',
        'login_logo_width' => 320,
        'login_logo_height' => 84,
        'login_background_color' => '#f0f0f1',
        'login_button_color' => '#2271b1',
        'login_button_hover_color' => '#135e96'
    ];
    
    /**
     * Get tax fallback rate
     *
     * @return float
     */
    public function get_tax_fallback_rate() {
        return (float) $this->get_setting('tax_fallback_rate', 13.0);
    }
    
    /**
     * Set tax fallback rate
     *
     * @param float $rate Tax rate
     * @return bool
     */
    public function set_tax_fallback_rate($rate) {
        return $this->set_setting('tax_fallback_rate', (float) $rate);
    }
    
    /**
     * Is tax caching enabled
     *
     * @return bool
     */
    public function is_tax_caching_enabled() {
        return (bool) $this->get_setting('cache_tax_calculations', true);
    }
    
    /**
     * Set tax caching enabled
     *
     * @param bool $enabled Tax caching enabled
     * @return bool
     */
    public function set_tax_caching_enabled($enabled) {
        return $this->set_setting('cache_tax_calculations', $enabled);
    }
    
    /**
     * Get tax cache duration
     *
     * @return int Hours
     */
    public function get_tax_cache_duration() {
        return (int) $this->get_setting('tax_cache_duration', 24);
    }
    
    /**
     * Set tax cache duration
     *
     * @param int $hours Hours
     * @return bool
     */
    public function set_tax_cache_duration($hours) {
        return $this->set_setting('tax_cache_duration', (int) $hours);
    }
    
    /**
     * Is logging enabled
     *
     * @return bool
     */
    public function is_logging_enabled() {
        return (bool) $this->get_setting('enable_logging', true);
    }
    
    /**
     * Set logging enabled
     *
     * @param bool $enabled Logging enabled
     * @return bool
     */
    public function set_logging_enabled($enabled) {
        return $this->set_setting('enable_logging', $enabled);
    }
    
    /**
     * Get log retention days
     *
     * @return int Days
     */
    public function get_log_retention_days() {
        return (int) $this->get_setting('log_retention_days', 30);
    }
    
    /**
     * Set log retention days
     *
     * @param int $days Days
     * @return bool
     */
    public function set_log_retention_days($days) {
        return $this->set_setting('log_retention_days', (int) $days);
    }
    
    /**
     * Get API rate limit
     *
     * @return int Requests per minute
     */
    public function get_api_rate_limit() {
        return (int) $this->get_setting('api_rate_limit', 100);
    }
    
    /**
     * Set API rate limit
     *
     * @param int $limit Requests per minute
     * @return bool
     */
    public function set_api_rate_limit($limit) {
        return $this->set_setting('api_rate_limit', (int) $limit);
    }
    
    /**
     * Is CORS enabled
     *
     * @return bool
     */
    public function is_cors_enabled() {
        return (bool) $this->get_setting('enable_cors', true);
    }
    
    /**
     * Set CORS enabled
     *
     * @param bool $enabled CORS enabled
     * @return bool
     */
    public function set_cors_enabled($enabled) {
        return $this->set_setting('enable_cors', $enabled);
    }
    
    /**
     * Get CORS origins
     *
     * @return array
     */
    public function get_cors_origins() {
        $origins = $this->get_setting('cors_origins', []);
        return is_array($origins) ? $origins : [];
    }
    
    /**
     * Set CORS origins
     *
     * @param array $origins Origins
     * @return bool
     */
    public function set_cors_origins($origins) {
        return $this->set_setting('cors_origins', (array) $origins);
    }
    
    /**
     * Get currency
     *
     * @return string
     */
    public function get_currency() {
        return $this->get_setting('currency', 'USD');
    }
    
    /**
     * Set currency
     *
     * @param string $currency Currency code
     * @return bool
     */
    public function set_currency($currency) {
        return $this->set_setting('currency', $currency);
    }
    
    /**
     * Is webhooks enabled
     *
     * @return bool
     */
    public function is_webhooks_enabled() {
        return (bool) $this->get_setting('enable_webhooks', true);
    }
    
    /**
     * Set webhooks enabled
     *
     * @param bool $enabled Webhooks enabled
     * @return bool
     */
    public function set_webhooks_enabled($enabled) {
        return $this->set_setting('enable_webhooks', $enabled);
    }
    
    /**
     * Get webhook endpoint
     *
     * @return string
     */
    public function get_webhook_endpoint() {
        return $this->get_setting('webhook_endpoint', '');
    }
    
    /**
     * Set webhook endpoint
     *
     * @param string $endpoint Endpoint URL
     * @return bool
     */
    public function set_webhook_endpoint($endpoint) {
        return $this->set_setting('webhook_endpoint', $endpoint);
    }
    
    /**
     * Get order status mapping
     *
     * @return array
     */
    public function get_order_status_mapping() {
        $mapping = $this->get_setting('order_status_mapping', []);
        return is_array($mapping) ? $mapping : [];
    }
    
    /**
     * Set order status mapping
     *
     * @param array $mapping Status mapping
     * @return bool
     */
    public function set_order_status_mapping($mapping) {
        return $this->set_setting('order_status_mapping', (array) $mapping);
    }
    
    /**
     * Validate tax rate
     *
     * @param float $rate Tax rate
     * @return bool|WP_Error
     */
    public function validate_tax_rate($rate) {
        if (!is_numeric($rate) || $rate < 0 || $rate > 100) {
            return new WP_Error('invalid_tax_rate', 'Tax rate must be between 0 and 100');
        }
        
        return true;
    }
    
    /**
     * Validate currency
     *
     * @param string $currency Currency code
     * @return bool|WP_Error
     */
    public function validate_currency($currency) {
        if (empty($currency) || strlen($currency) !== 3) {
            return new WP_Error('invalid_currency', 'Currency must be a 3-character code');
        }
        
        return true;
    }
    
    /**
     * Sanitize tax rate
     *
     * @param mixed $rate Tax rate
     * @return float
     */
    public function sanitize_tax_rate($rate) {
        return (float) $rate;
    }
    
    /**
     * Sanitize currency
     *
     * @param string $currency Currency code
     * @return string
     */
    public function sanitize_currency($currency) {
        return strtoupper(sanitize_text_field($currency));
    }
    
    /**
     * Is login customization enabled
     *
     * @return bool
     */
    public function is_login_customization_enabled() {
        return (bool) get_option('hsm_enable_login_customization', false);
    }
    
    /**
     * Set login customization enabled
     *
     * @param bool $enabled Login customization enabled
     * @return bool
     */
    public function set_login_customization_enabled($enabled) {
        return update_option('hsm_enable_login_customization', $enabled);
    }
    
    /**
     * Get login logo URL
     *
     * @return string
     */
    public function get_login_logo_url() {
        return get_option('hsm_login_logo_url', '');
    }
    
    /**
     * Set login logo URL
     *
     * @param string $url Logo URL
     * @return bool
     */
    public function set_login_logo_url($url) {
        return update_option('hsm_login_logo_url', esc_url_raw($url));
    }
    
    /**
     * Get login logo width
     *
     * @return int
     */
    public function get_login_logo_width() {
        return (int) get_option('hsm_login_logo_width', 320);
    }
    
    /**
     * Set login logo width
     *
     * @param int $width Logo width in pixels
     * @return bool
     */
    public function set_login_logo_width($width) {
        return update_option('hsm_login_logo_width', (int) $width);
    }
    
    /**
     * Get login logo height
     *
     * @return int
     */
    public function get_login_logo_height() {
        return (int) get_option('hsm_login_logo_height', 84);
    }
    
    /**
     * Set login logo height
     *
     * @param int $height Logo height in pixels
     * @return bool
     */
    public function set_login_logo_height($height) {
        return update_option('hsm_login_logo_height', (int) $height);
    }
    
    /**
     * Get login background color
     *
     * @return string
     */
    public function get_login_background_color() {
        return get_option('hsm_login_background_color', '#f0f0f1');
    }
    
    /**
     * Set login background color
     *
     * @param string $color Background color (hex)
     * @return bool
     */
    public function set_login_background_color($color) {
        return update_option('hsm_login_background_color', sanitize_hex_color($color));
    }
    
    /**
     * Get login button color
     *
     * @return string
     */
    public function get_login_button_color() {
        return get_option('hsm_login_button_color', '#2271b1');
    }
    
    /**
     * Set login button color
     *
     * @param string $color Button color (hex)
     * @return bool
     */
    public function set_login_button_color($color) {
        return update_option('hsm_login_button_color', sanitize_hex_color($color));
    }
    
    /**
     * Get login button hover color
     *
     * @return string
     */
    public function get_login_button_hover_color() {
        return get_option('hsm_login_button_hover_color', '#135e96');
    }
    
    /**
     * Set login button hover color
     *
     * @param string $color Button hover color (hex)
     * @return bool
     */
    public function set_login_button_hover_color($color) {
        return update_option('hsm_login_button_hover_color', sanitize_hex_color($color));
    }
}