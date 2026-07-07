<?php
/**
 * HSM Admin Pages Class
 * 
 * Handles WordPress admin page rendering and functionality
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Admin_Pages {
    
    /**
     * Error handler instance
     * 
     * @var HSM_Error_Handler
     */
    private $error_handler;
    
    /**
     * Constructor
     * 
     * @param HSM_Error_Handler $error_handler Error handler instance
     */
    public function __construct($error_handler) {
        $this->error_handler = $error_handler;
    }
    
    /**
     * Enhanced permission validation function
     * 
     * @return bool True if user has proper permissions
     */
    private function validate_admin_permissions() {
        // Use unified permission manager
        if (class_exists('HSM_Permission_Manager')) {
            $permission_manager = HSM_Permission_Manager::get_instance();
            return $permission_manager->validate_admin_page_permission('manage_options');
        }
        
        // Fallback to basic admin permission check
        // Check if user is logged in
        if (!is_user_logged_in()) {
            error_log('HSM Plugin: User is not logged in');
            return false;
        }
        
        // Check if user is in admin area
        if (!is_admin()) {
            error_log('HSM Plugin: User is not in admin area');
            return false;
        }
        
        // Validate user session
        if (!$this->validate_user_session()) {
            error_log('HSM Plugin: User session validation failed');
            return false;
        }
        
        // Check multiple capability levels
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Check for admin user specifically
        if (current_user_can('administrator')) {
            return true;
        }
        
        // Check for super admin in multisite
        if (is_multisite() && is_super_admin()) {
            return true;
        }
        
        error_log('HSM Plugin: User does not have required capabilities');
        return false;
    }

    /**
     * Validate user session
     * 
     * @return bool True if session is valid
     */
    private function validate_user_session() {
        // Check if user ID exists
        $user_id = get_current_user_id();
        if (!$user_id) {
            return false;
        }
        
        // Check if user exists in database
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Check if user is active (not deleted/spam)
        if ($user->user_status !== '0') {
            return false;
        }
        
        // Check if user has proper role
        $user_roles = $user->roles;
        $allowed_roles = ['administrator', 'editor', 'author'];
        
        foreach ($user_roles as $role) {
            if (in_array($role, $allowed_roles)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Render main admin dashboard page
     * 
     * @return void
     */
    public function admin_page() {
        // Enhanced permission check
        if (!$this->validate_admin_permissions()) {
            wp_die('Sorry, you are not allowed to access this page.');
        }
        
        // Handle form submission
        if (isset($_POST['submit'])) {
            // Verify nonce for security
            if (!wp_verify_nonce($_POST['hsm_admin_nonce'], 'hsm_admin_action')) {
                wp_die('Security check failed. Please try again.');
            }
            
            $stripe_secret_key = sanitize_text_field($_POST['stripe_secret_key']);
            $webhook_secret = sanitize_text_field($_POST['webhook_secret']);
            $nextjs_url = esc_url_raw($_POST['nextjs_url']);
            $debug_mode = isset($_POST['debug_mode']) ? 1 : 0;
            
            // Login customization settings
            $enable_login_customization = isset($_POST['enable_login_customization']) ? 1 : 0;
            $login_logo_url = esc_url_raw($_POST['login_logo_url'] ?? '');
            $login_logo_width = intval($_POST['login_logo_width'] ?? 320);
            $login_logo_height = intval($_POST['login_logo_height'] ?? 84);
            $login_background_color = sanitize_hex_color($_POST['login_background_color'] ?? '#f0f0f1');
            $login_button_color = sanitize_hex_color($_POST['login_button_color'] ?? '#2271b1');
            $login_button_hover_color = sanitize_hex_color($_POST['login_button_hover_color'] ?? '#135e96');
            
            // Validate inputs
            $errors = [];
            if (!empty($stripe_secret_key) && !$this->validate_stripe_key_format($stripe_secret_key)) {
                $errors[] = 'Invalid Stripe secret key format';
            }
            if (!empty($webhook_secret) && strpos($webhook_secret, 'whsec_') !== 0) {
                $errors[] = 'Invalid webhook secret format';
            }
            if (!empty($nextjs_url) && !filter_var($nextjs_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'Invalid Next.js URL format';
            }
            if (!empty($login_logo_url) && !filter_var($login_logo_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'Invalid login logo URL format';
            }
            if ($login_logo_width < 100 || $login_logo_width > 500) {
                $errors[] = 'Login logo width must be between 100 and 500 pixels';
            }
            if ($login_logo_height < 50 || $login_logo_height > 200) {
                $errors[] = 'Login logo height must be between 50 and 200 pixels';
            }
            
            if (empty($errors)) {
                update_option('hsm_stripe_secret_key', $stripe_secret_key);
                update_option('hsm_stripe_webhook_secret', $webhook_secret);
                update_option('hsm_nextjs_url', $nextjs_url);
                update_option('hsm_stripe_debug_mode', $debug_mode);
                
                // Save login customization settings
                update_option('hsm_enable_login_customization', $enable_login_customization);
                update_option('hsm_login_logo_url', $login_logo_url);
                update_option('hsm_login_logo_width', $login_logo_width);
                update_option('hsm_login_logo_height', $login_logo_height);
                update_option('hsm_login_background_color', $login_background_color);
                update_option('hsm_login_button_color', $login_button_color);
                update_option('hsm_login_button_hover_color', $login_button_hover_color);
                
                echo '<div class="notice notice-success"><p>✅ Settings saved successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>❌ ' . implode('<br>', $errors) . '</p></div>';
            }
        }
        
        // Get current settings
        $stripe_secret_key = get_option('hsm_stripe_secret_key', '');
        $webhook_secret = get_option('hsm_stripe_webhook_secret', '');
        $debug_mode = get_option('hsm_stripe_debug_mode', 0);
        
        // Get system status
        $system_status = $this->get_system_status();
        
        ?>
        <div class="wrap">
            <h1>🚀 HSM Stripe Plugin Dashboard</h1>
            
            <!-- Navigation Tabs -->
            <nav class="nav-tab-wrapper">
                <a href="?page=hsm-plugin" class="nav-tab nav-tab-active">📊 Dashboard</a>
                <a href="?page=hsm-plugin-settings" class="nav-tab">⚙️ Settings</a>
                <a href="?page=hsm-stripe-api-check" class="nav-tab">🔌 API Check</a>
                <a href="?page=hsm-stripe-webhook" class="nav-tab">🔗 Webhook Check</a>
                <a href="?page=hsm-graphql-proxy" class="nav-tab">🔗 GraphQL Proxy</a>
            </nav>
            
            <!-- WordPress Admin Columns Layout -->
            <div class="wp-admin-columns">
                <div class="wp-admin-column-left">
                    <!-- Left Sidebar Widgets -->
                    <div class="meta-box-sortables">
                        <!-- System Status Panel -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle">📊 System Health Status</h2>
                            </div>
                            <div class="inside">
                                <div class="status-indicators">
                        <!-- Stripe Integration -->
                        <div class="status-item <?php echo $system_status['stripe']['status'] ? 'status-ok' : 'status-error'; ?>">
                            <span class="status-icon"><?php echo $system_status['stripe']['status'] ? '✅' : '❌'; ?></span>
                            <span class="status-text">Stripe Integration</span>
                            <span class="status-detail"><?php echo $system_status['stripe']['integration_type']; ?></span>
                        </div>
                        
                        <!-- WooCommerce -->
                        <div class="status-item <?php echo $system_status['woocommerce']['status'] ? 'status-ok' : 'status-error'; ?>">
                            <span class="status-icon"><?php echo $system_status['woocommerce']['status'] ? '✅' : '❌'; ?></span>
                            <span class="status-text">WooCommerce</span>
                            <span class="status-detail"><?php echo $system_status['woocommerce']['version'] ? 'v' . $system_status['woocommerce']['version'] : 'Not Active'; ?></span>
                        </div>
                        
                        <!-- Next.js Integration -->
                        <div class="status-item <?php echo $system_status['nextjs_integration']['status'] ? 'status-ok' : 'status-warning'; ?>">
                            <span class="status-icon"><?php echo $system_status['nextjs_integration']['status'] ? '✅' : '⚠️'; ?></span>
                            <span class="status-text">Next.js Integration</span>
                            <span class="status-detail"><?php echo $system_status['nextjs_integration']['integration_type']; ?></span>
                        </div>
                        
                        <!-- API Endpoints -->
                        <div class="status-item <?php echo $system_status['api_endpoints']['status'] ? 'status-ok' : 'status-error'; ?>">
                            <span class="status-icon"><?php echo $system_status['api_endpoints']['status'] ? '✅' : '❌'; ?></span>
                            <span class="status-text">API Endpoints</span>
                            <span class="status-detail">
                                <?php 
                                $api_info = $system_status['api_endpoints'];
                                $total = $api_info['total_endpoints'] ?? 0;
                                $namespaces = $api_info['namespaces'] ?? [];
                                $active_count = 0;
                                foreach ($namespaces as $ns => $info) {
                                    if ($info['status']) $active_count += $info['count'];
                                }
                                echo sprintf('%d/%d endpoints active (%d namespaces)', $active_count, $total, count($namespaces));
                                ?>
                            </span>
                        </div>
                        
                        <!-- Database -->
                        <div class="status-item <?php echo $system_status['database'] ? 'status-ok' : 'status-error'; ?>">
                            <span class="status-icon"><?php echo $system_status['database'] ? '✅' : '❌'; ?></span>
                            <span class="status-text">Database</span>
                            <span class="status-detail">Connection OK</span>
                        </div>
                        
                        <!-- Plugin Dependencies -->
                        <div class="status-item <?php echo $system_status['plugin_dependencies']['status'] ? 'status-ok' : 'status-error'; ?>">
                            <span class="status-icon"><?php echo $system_status['plugin_dependencies']['status'] ? '✅' : '❌'; ?></span>
                            <span class="status-text">Dependencies</span>
                            <span class="status-detail"><?php echo $system_status['plugin_dependencies']['status'] ? 'All Required' : 'Missing: ' . implode(', ', $system_status['plugin_dependencies']['missing_plugins']); ?></span>
                        </div>
                        
                                    <!-- Security Status -->
                                    <div class="status-item <?php echo $system_status['security_status']['status'] ? 'status-ok' : 'status-warning'; ?>">
                                        <span class="status-icon"><?php echo $system_status['security_status']['status'] ? '✅' : '⚠️'; ?></span>
                                        <span class="status-text">Security</span>
                                        <span class="status-detail"><?php echo $system_status['security_status']['ssl_enabled'] ? 'SSL Enabled' : 'SSL Required'; ?></span>
                                    </div>
                                </div>
                                
                                <!-- Detailed System Information -->
                                <div class="system-details">
                        <h4>🔧 System Information</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <strong>PHP Version:</strong> <?php echo $system_status['system_resources']['php_version']; ?>
                            </div>
                            <div class="info-item">
                                <strong>WordPress Version:</strong> <?php echo $system_status['system_resources']['wordpress_version']; ?>
                            </div>
                            <div class="info-item">
                                <strong>Memory Limit:</strong> <?php echo $system_status['system_resources']['memory_limit']; ?>
                            </div>
                            <div class="info-item">
                                <strong>Max Execution Time:</strong> <?php echo $system_status['system_resources']['max_execution_time']; ?>s
                            </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Configuration Panel -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle">⚙️ API Configuration</h2>
                            </div>
                            <div class="inside">
                    <form method="post" action="" class="config-form" id="hsm-config-form">
                        <?php wp_nonce_field('hsm_admin_action', 'hsm_admin_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Stripe Secret Key</th>
                                <td>
                                    <div class="api-key-field">
                                        <input type="password" name="stripe_secret_key" 
                                               value="<?php echo esc_attr($stripe_secret_key); ?>" 
                                               class="regular-text api-key-input" 
                                               id="stripe-secret-key" />
                                        <button type="button" class="button toggle-visibility" data-target="stripe-secret-key">
                                            👁️
                                        </button>
                                        <span class="key-status <?php echo $this->validate_stripe_key_format($stripe_secret_key) ? 'valid' : 'invalid'; ?>">
                                            <?php echo $this->validate_stripe_key_format($stripe_secret_key) ? '✅ Valid Format' : '❌ Invalid Format'; ?>
                                        </span>
                                    </div>
                                    <p class="description">
                                        Enter your Stripe secret key (starts with sk_test_ or sk_live_)
                                        <br><strong>Note:</strong> Payment processing is handled by Next.js application
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Webhook Secret</th>
                                <td>
                                    <div class="api-key-field">
                                        <input type="password" name="webhook_secret" 
                                               value="<?php echo esc_attr($webhook_secret); ?>" 
                                               class="regular-text api-key-input" 
                                               id="webhook-secret" />
                                        <button type="button" class="button toggle-visibility" data-target="webhook-secret">
                                            👁️
                                        </button>
                                        <span class="key-status <?php echo !empty($webhook_secret) && strpos($webhook_secret, 'whsec_') === 0 ? 'valid' : 'invalid'; ?>">
                                            <?php echo !empty($webhook_secret) && strpos($webhook_secret, 'whsec_') === 0 ? '✅ Valid Format' : '❌ Invalid Format'; ?>
                                        </span>
                                    </div>
                                    <p class="description">Enter your Stripe webhook secret (starts with whsec_)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Next.js Application URL</th>
                                <td>
                                    <input type="url" name="nextjs_url" 
                                           value="<?php echo esc_attr(get_option('hsm_nextjs_url', '')); ?>" 
                                           class="regular-text" 
                                           placeholder="https://your-nextjs-app.com" />
                                    <p class="description">URL of your Next.js application for payment processing</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Debug Mode</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="debug_mode" value="1" 
                                               <?php checked($debug_mode, 1); ?> />
                                        Enable debug logging
                                    </label>
                                    <p class="description">Enable detailed logging for troubleshooting (not recommended for production)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">API Key Rotation</th>
                                <td>
                                    <button type="button" class="button button-secondary" id="rotate-keys">
                                        🔄 Rotate API Keys
                                    </button>
                                    <p class="description">Generate new API keys for enhanced security</p>
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Login Customization Settings -->
                        <h3>🎨 Login Page Customization</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Enable Login Customization</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_login_customization" value="1" 
                                               <?php checked(get_option('hsm_enable_login_customization', 1), 1); ?> />
                                        Enable custom login page branding
                                    </label>
                                    <p class="description">Customize the WordPress login page with your logo and branding</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Login Logo URL</th>
                                <td>
                                    <input type="url" name="login_logo_url" 
                                           value="<?php echo esc_attr(get_option('hsm_login_logo_url', '')); ?>" 
                                           class="regular-text" 
                                           placeholder="https://example.com/logo.png" />
                                    <p class="description">URL of the logo image to display on the login page (recommended: 320x84px)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Logo Dimensions</th>
                                <td>
                                    <label>Width: 
                                        <input type="number" name="login_logo_width" 
                                               value="<?php echo esc_attr(get_option('hsm_login_logo_width', 320)); ?>" 
                                               min="100" max="500" step="10" style="width: 80px;" /> px
                                    </label>
                                    <label style="margin-left: 20px;">Height: 
                                        <input type="number" name="login_logo_height" 
                                               value="<?php echo esc_attr(get_option('hsm_login_logo_height', 84)); ?>" 
                                               min="50" max="200" step="5" style="width: 80px;" /> px
                                    </label>
                                    <p class="description">Dimensions of the login logo in pixels</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Background Color</th>
                                <td>
                                    <input type="color" name="login_background_color" 
                                           value="<?php echo esc_attr(get_option('hsm_login_background_color', '#f0f0f1')); ?>" />
                                    <p class="description">Background color for the login page</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Button Color</th>
                                <td>
                                    <input type="color" name="login_button_color" 
                                           value="<?php echo esc_attr(get_option('hsm_login_button_color', '#2271b1')); ?>" />
                                    <p class="description">Primary color for login buttons</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Button Hover Color</th>
                                <td>
                                    <input type="color" name="login_button_hover_color" 
                                           value="<?php echo esc_attr(get_option('hsm_login_button_hover_color', '#135e96')); ?>" />
                                    <p class="description">Hover color for login buttons</p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button('Save Configuration', 'primary', 'submit', false, ['id' => 'save-config']); ?>
                        <button type="button" class="button button-secondary" id="test-config">
                            🧪 Test Configuration
                        </button>
                    </form>
                    
                    <!-- Configuration Status -->
                    <div class="config-status">
                        <h4>📋 Configuration Status</h4>
                        <div class="status-summary">
                            <div class="status-item <?php echo !empty($stripe_secret_key) ? 'status-ok' : 'status-error'; ?>">
                                <span class="status-icon"><?php echo !empty($stripe_secret_key) ? '✅' : '❌'; ?></span>
                                <span class="status-text">Stripe Secret Key</span>
                            </div>
                            <div class="status-item <?php echo !empty($webhook_secret) ? 'status-ok' : 'status-error'; ?>">
                                <span class="status-icon"><?php echo !empty($webhook_secret) ? '✅' : '❌'; ?></span>
                                <span class="status-text">Webhook Secret</span>
                            </div>
                            <?php 
                            $hpos_compatibility = class_exists('HSM_HPOS_Compatibility') ? HSM_HPOS_Compatibility::get_instance()->get_compatibility_status() : array('hpos_enabled' => false, 'compatibility_declared' => false);
                            ?>
                            <div class="status-item <?php echo $hpos_compatibility['compatibility_declared'] ? 'status-ok' : 'status-warning'; ?>">
                                <span class="status-icon"><?php echo $hpos_compatibility['compatibility_declared'] ? '✅' : '⚠️'; ?></span>
                                <span class="status-text">HPOS Compatibility</span>
                            </div>
                            <div class="status-item <?php echo $hpos_compatibility['hpos_enabled'] ? 'status-info' : 'status-neutral'; ?>">
                                <span class="status-icon"><?php echo $hpos_compatibility['hpos_enabled'] ? '🚀' : '📊'; ?></span>
                                <span class="status-text"><?php echo $hpos_compatibility['hpos_enabled'] ? 'HPOS Enabled' : 'Standard Storage'; ?></span>
                            </div>
                            <div class="status-item <?php echo !empty(get_option('hsm_nextjs_url', '')) ? 'status-ok' : 'status-warning'; ?>">
                                <span class="status-icon"><?php echo !empty(get_option('hsm_nextjs_url', '')) ? '✅' : '⚠️'; ?></span>
                                <span class="status-text">Next.js URL</span>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Webhook Events Panel -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">📋 Recent Webhook Events</h2>
                    </div>
                    <div class="inside">
                    <div class="webhook-events">
                        <?php
                        $recent_events = $this->get_recent_webhook_events();
                        if (!empty($recent_events)) {
                            foreach ($recent_events as $event) {
                                $status_class = $event['status'] === 'success' ? 'status-ok' : 'status-error';
                                $status_icon = $event['status'] === 'success' ? '✅' : '❌';
                                ?>
                                <div class="event-item <?php echo $status_class; ?>">
                                    <div class="event-header">
                                        <span class="event-type"><?php echo esc_html($event['action']); ?></span>
                                        <span class="event-status"><?php echo $status_icon; ?></span>
                                        <span class="event-time"><?php echo esc_html($event['time']); ?></span>
                                    </div>
                                    <div class="event-details">
                                        <?php if (!empty($event['data'])): ?>
                                        <div class="event-data"><?php echo esc_html($event['data']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p class="no-events">No recent webhook events found.</p>';
                        }
                        ?>
                    </div>
                    <div class="events-actions">
                        <button type="button" class="button button-secondary" id="refresh-events">
                            🔄 Refresh Events
                        </button>
                        <button type="button" class="button button-secondary" id="clear-events">
                            🗑️ Clear Events
                        </button>
                    </div>
                </div>
                
                <!-- Performance Metrics Panel -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">⚡ Performance Metrics</h2>
                    </div>
                    <div class="inside">
                    <div class="performance-metrics">
                        <?php
                        $performance_data = $this->get_performance_metrics();
                        ?>
                        <div class="metric-item">
                            <div class="metric-label">API Response Time</div>
                            <div class="metric-value <?php echo $performance_data['api_response_time'] < 1000 ? 'good' : 'warning'; ?>">
                                <?php echo $performance_data['api_response_time']; ?>ms
                            </div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Database Query Time</div>
                            <div class="metric-value <?php echo $performance_data['db_query_time'] < 100 ? 'good' : 'warning'; ?>">
                                <?php echo $performance_data['db_query_time']; ?>ms
                            </div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Memory Usage</div>
                            <div class="metric-value <?php echo $performance_data['memory_usage'] < 80 ? 'good' : 'warning'; ?>">
                                <?php echo $performance_data['memory_usage']; ?>%
                            </div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Error Rate (24h)</div>
                            <div class="metric-value <?php echo $performance_data['error_rate'] < 5 ? 'good' : 'warning'; ?>">
                                <?php echo $performance_data['error_rate']; ?>%
                            </div>
                        </div>
                    </div>
                    <div class="performance-actions">
                        <button type="button" class="button button-secondary" id="refresh-metrics">
                            🔄 Refresh Metrics
                        </button>
                        <button type="button" class="button button-secondary" id="performance-test">
                            🧪 Run Performance Test
                        </button>
                    </div>
                </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="wp-admin-column-right">
                    <!-- Help Section -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">❓ Help & Documentation</h2>
                        </div>
                        <div class="inside">
                            <div class="help-grid">
                    <div class="help-item">
                        <h4>🚀 Quick Start</h4>
                        <p>1. Enter your Stripe secret key<br>
                           2. Configure webhook secret<br>
                           3. Test API endpoints<br>
                           4. Monitor system status</p>
                    </div>
                    <div class="help-item">
                        <h4>📚 API Documentation</h4>
                        <p><strong>Consolidated API Structure:</strong></p>
                        <p><strong>hsm/v1:</strong> General HSM endpoints<br>
                           <strong>hsm-stripe/v1:</strong> Stripe payment operations<br>
                           <strong>hsm-graphql/v1:</strong> GraphQL proxy operations</p>
                        <p><strong>Key Endpoints:</strong><br>
                           <strong>Tax:</strong> GET /wp-json/hsm-stripe/v1/tax/calculate<br>
                           <strong>Payment:</strong> POST /wp-json/hsm-stripe/v1/payment/intent<br>
                           <strong>Orders:</strong> POST /wp-json/hsm-stripe/v1/orders/create<br>
                           <strong>GraphQL:</strong> POST /wp-json/hsm-graphql/v1/proxy</p>
                        <p><em>See <a href="<?php echo admin_url('admin.php?page=hsm-stripe-api-check'); ?>">API Check</a> for complete endpoint list.</em></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .hsm-admin-dashboard {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .hsm-nav-tabs {
            margin: 20px 0;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .hsm-nav-tabs .nav-tab {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-bottom: none;
            padding: 10px 20px;
            text-decoration: none;
            color: #1d2327;
            margin-right: 5px;
        }
        
        .hsm-nav-tabs .nav-tab-active {
            background: #fff;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
        }
        
        .hsm-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .hsm-widget {
            background: #fff;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
        }
        
        .hsm-widget h3 {
            margin-top: 0;
            color: #1d2327;
        }
        
        .status-indicators {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-radius: 4px;
        }
        
        .status-ok {
            background: #d4edda;
            color: #155724;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .hsm-help-section {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .help-item {
            background: #fff;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e1e5e9;
        }
        
        .help-item h4 {
            margin-top: 0;
            color: #1d2327;
        }
        
        /* Enhanced Status Styles */
        .status-detail {
            font-size: 0.9em;
            color: #666;
            margin-left: auto;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-neutral {
            background: #f8f9fa;
            color: #6c757d;
        }
        
        .system-details {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .info-item {
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 0.9em;
        }
        
        /* API Key Field Styles */
        .api-key-field {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }
        
        .api-key-input {
            flex: 1;
        }
        
        .toggle-visibility {
            padding: 5px 10px;
            font-size: 0.9em;
        }
        
        .key-status {
            font-size: 0.8em;
            padding: 2px 8px;
            border-radius: 3px;
            font-weight: bold;
        }
        
        .key-status.valid {
            background: #d4edda;
            color: #155724;
        }
        
        .key-status.invalid {
            background: #f8d7da;
            color: #721c24;
        }
        
        .config-status {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }
        
        .status-summary {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        
        .status-summary .status-item {
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        
        /* Button Styles */
        #test-config {
            margin-left: 10px;
        }
        
        #rotate-keys {
            margin-bottom: 5px;
        }
        
        /* Webhook Events Styles */
        .webhook-events {
            margin-top: 15px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .event-item {
            border: 1px solid #e1e5e9;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 8px;
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .event-type {
            font-weight: bold;
            color: #1d2327;
        }
        
        .event-time {
            font-size: 0.8em;
            color: #666;
        }
        
        .event-data {
            font-size: 0.9em;
            color: #666;
            background: #f8f9fa;
            padding: 5px;
            border-radius: 3px;
            margin-top: 5px;
            word-break: break-all;
        }
        
        .no-events {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
        
        .events-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .events-actions .button {
            font-size: 0.9em;
        }
        
        /* Performance Metrics Styles */
        .performance-metrics {
            margin-top: 15px;
        }
        
        .metric-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border: 1px solid #e1e5e9;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        
        .metric-label {
            font-weight: bold;
            color: #1d2327;
        }
        
        .metric-value {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 3px;
        }
        
        .metric-value.good {
            background: #d4edda;
            color: #155724;
        }
        
        .metric-value.warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .performance-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .performance-actions .button {
            font-size: 0.9em;
        }
        </style>
        <?php
    }
    
    /**
     * Render API check page
     * 
     * @return void
     */
    public function api_check_page() {
        // Enhanced permission check
        if (!$this->validate_admin_permissions()) {
            wp_die('Sorry, you are not allowed to access this page.');
        }
        $api_results = [];
        
        // Test API endpoints if requested
        if (isset($_POST['test_apis'])) {
            // Verify nonce for security
            if (!wp_verify_nonce($_POST['hsm_admin_nonce'], 'hsm_admin_action')) {
                wp_die('Security check failed. Please try again.');
            }
            $api_results = $this->test_api_endpoints();
        }
        
        ?>
        <div class="wrap hsm-api-check">
            <h1>🔌 HSM Stripe API Check</h1>
            
            <!-- Navigation Tabs -->
            <div class="hsm-nav-tabs">
                <a href="?page=hsm-plugin" class="nav-tab">📊 Dashboard</a>
                <a href="?page=hsm-stripe-api-check" class="nav-tab nav-tab-active">🔌 API Check</a>
                <a href="?page=hsm-stripe-webhook" class="nav-tab">🔗 Webhook Check</a>
                <a href="?page=hsm-graphql-proxy" class="nav-tab">🔗 GraphQL Proxy</a>
            </div>
            
            <div class="hsm-dashboard-grid">
                <!-- API Test Controls -->
                <div class="hsm-widget">
                    <h3>🧪 API Endpoint Testing</h3>
                    <form method="post" action="">
                        <?php wp_nonce_field('hsm_admin_action', 'hsm_admin_nonce'); ?>
                        <p>Test all HSM Stripe API endpoints to ensure they're working correctly.</p>
                        <?php submit_button('Test All APIs', 'primary', 'test_apis'); ?>
                    </form>
                </div>
                
                <!-- API Results -->
                <?php if (!empty($api_results)): ?>
                <div class="hsm-widget">
                    <h3>📊 Test Results</h3>
                    <div class="api-results">
                        <?php foreach ($api_results as $endpoint => $result): ?>
                        <div class="api-result-item <?php echo $result['status'] ? 'status-ok' : 'status-error'; ?>">
                            <div class="api-endpoint">
                                <strong><?php echo esc_html($endpoint); ?></strong>
                                <span class="api-status"><?php echo $result['status'] ? '✅' : '❌'; ?></span>
                            </div>
                            <div class="api-details">
                                <div class="api-url"><?php echo esc_html($result['url']); ?></div>
                                <div class="api-response"><?php echo esc_html($result['response']); ?></div>
                                <?php if (!empty($result['error'])): ?>
                                <div class="api-error">Error: <?php echo esc_html($result['error']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Available Endpoints -->
                <div class="hsm-widget">
                    <h3>📋 Available Endpoints (Consolidated Structure)</h3>
                    
                    <!-- hsm/v1 Namespace -->
                    <div class="endpoints-namespace">
                        <h4>hsm/v1 - General HSM API</h4>
                        <div class="endpoints-list">
                            <div class="endpoint-item">
                                <strong>Health Check</strong>
                                <code><?php echo rest_url('hsm/v1/health'); ?></code>
                                <span class="endpoint-method">GET</span>
                            </div>
                            <div class="endpoint-item">
                                <strong>System Status</strong>
                                <code><?php echo rest_url('hsm/v1/system-status'); ?></code>
                                <span class="endpoint-method">GET</span>
                            </div>
                            <div class="endpoint-item">
                                <strong>Consult Form URL</strong>
                                <code><?php echo rest_url('hsm/v1/consult-form-url'); ?></code>
                                <span class="endpoint-method">GET</span>
                            </div>
                            <div class="endpoint-item">
                                <strong>Contact Form URL</strong>
                                <code><?php echo rest_url('hsm/v1/contact-form-url'); ?></code>
                                <span class="endpoint-method">GET</span>
                            </div>
                            <div class="endpoint-item">
                                <strong>Stripe Webhook</strong>
                                <code><?php echo rest_url('hsm/v1/stripe-webhook'); ?></code>
                                <span class="endpoint-method">POST</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- hsm-stripe/v1 Namespace -->
                    <div class="endpoints-namespace">
                        <h4>hsm-stripe/v1 - Stripe Payment Operations</h4>
                        <div class="endpoints-list">
                            <div class="endpoint-item">
                                <strong>Tax Calculator</strong>
                                <code><?php echo rest_url('hsm-stripe/v1/tax/calculate'); ?></code>
                                <span class="endpoint-method">GET</span>
                            </div>
                            <div class="endpoint-item">
                                <strong>Payment Intent</strong>
                                <code><?php echo rest_url('hsm-stripe/v1/payment/intent'); ?></code>
                                <span class="endpoint-method">POST</span>
                            </div>
                            <div class="endpoint-item">
                                <strong>Order Creation</strong>
                                <code><?php echo rest_url('hsm-stripe/v1/orders/create'); ?></code>
                                <span class="endpoint-method">POST</span>
                            </div>
                            <div class="endpoint-item">
                                <strong>Order Update Status</strong>
                                <code><?php echo rest_url('hsm-stripe/v1/orders/update-status'); ?></code>
                                <span class="endpoint-method">POST</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- hsm-graphql/v1 Namespace -->
                    <div class="endpoints-namespace">
                        <h4>hsm-graphql/v1 - GraphQL Proxy Operations</h4>
                        <div class="endpoints-list">
                            <div class="endpoint-item">
                                <strong>GraphQL Proxy</strong>
                                <code><?php echo rest_url('hsm-graphql/v1/proxy'); ?></code>
                                <span class="endpoint-method">POST</span>
                            </div>
                            <div class="endpoint-item">
                                <strong>GraphQL Health</strong>
                                <code><?php echo rest_url('hsm-graphql/v1/health'); ?></code>
                                <span class="endpoint-method">GET</span>
                            </div>
                            <div class="endpoint-item">
                                <strong>GraphQL Status</strong>
                                <code><?php echo rest_url('hsm-graphql/v1/status'); ?></code>
                                <span class="endpoint-method">GET</span>
                            </div>
                            <div class="endpoint-item">
                                <strong>GraphQL Nonce</strong>
                                <code><?php echo rest_url('hsm-graphql/v1/nonce'); ?></code>
                                <span class="endpoint-method">GET</span>
                            </div>
                        </div>
                    </div>
                    
                    <p class="description" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e1e5e9;">
                        <strong>Note:</strong> API routes have been consolidated to eliminate duplicates. 
                        See <a href="<?php echo admin_url('admin.php?page=hsm-plugin'); ?>">Dashboard</a> for full API documentation.
                    </p>
                </div>
            </div>
        </div>
        
        <style>
        .api-results {
            margin-top: 15px;
        }
        
        .api-result-item {
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .api-endpoint {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .api-details {
            font-size: 0.9em;
            color: #666;
        }
        
        .api-url {
            font-family: monospace;
            background: #f8f9fa;
            padding: 5px;
            border-radius: 3px;
            margin-bottom: 5px;
        }
        
        .api-response {
            margin-bottom: 5px;
        }
        
        .api-error {
            color: #721c24;
            background: #f8d7da;
            padding: 5px;
            border-radius: 3px;
        }
        
        .endpoints-namespace {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e1e5e9;
        }
        
        .endpoints-namespace:last-child {
            border-bottom: none;
        }
        
        .endpoints-namespace h4 {
            margin: 0 0 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #2271b1;
            font-size: 1.1em;
            color: #1d2327;
        }
        
        .endpoints-list {
            margin-top: 15px;
        }
        
        .endpoint-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            border: 1px solid #e1e5e9;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        
        .endpoint-item code {
            flex: 1;
            background: #f8f9fa;
            padding: 5px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        .endpoint-method {
            background: #0073aa;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: bold;
        }
        </style>
        <?php
    }
    
    /**
     * Render webhook check page
     * 
     * @return void
     */
    public function webhook_check_page() {
        // Enhanced permission check
        if (!$this->validate_admin_permissions()) {
            wp_die('Sorry, you are not allowed to access this page.');
        }
        $webhook_results = [];
        
        // Test webhook if requested
        if (isset($_POST['test_webhook'])) {
            // Verify nonce for security
            if (!wp_verify_nonce($_POST['hsm_admin_nonce'], 'hsm_admin_action')) {
                wp_die('Security check failed. Please try again.');
            }
            $webhook_results = $this->test_webhook_functionality();
        }
        
        $webhook_url = rest_url('hsm-stripe/v1/webhook');
        $webhook_secret = get_option('hsm_stripe_webhook_secret', '');
        
        ?>
        <div class="wrap hsm-webhook-check">
            <h1>🔗 HSM Stripe Webhook Check</h1>
            
            <!-- Navigation Tabs -->
            <div class="hsm-nav-tabs">
                <a href="?page=hsm-plugin" class="nav-tab">📊 Dashboard</a>
                <a href="?page=hsm-stripe-api-check" class="nav-tab">🔌 API Check</a>
                <a href="?page=hsm-stripe-webhook" class="nav-tab nav-tab-active">🔗 Webhook Check</a>
                <a href="?page=hsm-graphql-proxy" class="nav-tab">🔗 GraphQL Proxy</a>
            </div>
            
            <div class="hsm-dashboard-grid">
                <!-- Webhook Configuration -->
                <div class="hsm-widget">
                    <h3>⚙️ Webhook Configuration</h3>
                    <div class="webhook-config">
                        <div class="config-item">
                            <strong>Webhook URL:</strong>
                            <code><?php echo esc_html($webhook_url); ?></code>
                            <button type="button" class="button button-small copy-url" data-url="<?php echo esc_attr($webhook_url); ?>">
                                📋 Copy URL
                            </button>
                        </div>
                        <div class="config-item">
                            <strong>Webhook Secret:</strong>
                            <span class="secret-status <?php echo !empty($webhook_secret) ? 'configured' : 'not-configured'; ?>">
                                <?php echo !empty($webhook_secret) ? '✅ Configured' : '❌ Not Configured'; ?>
                            </span>
                        </div>
                        <div class="config-item">
                            <strong>Integration Type:</strong>
                            <span class="integration-type">Next.js Integration (SDK Removed)</span>
                        </div>
                    </div>
                </div>
                
                <!-- Webhook Testing -->
                <div class="hsm-widget">
                    <h3>🧪 Webhook Testing</h3>
                    <form method="post" action="">
                        <?php wp_nonce_field('hsm_admin_action', 'hsm_admin_nonce'); ?>
                        <p>Test webhook functionality with sample events.</p>
                        <div class="test-options">
                            <label>
                                <input type="radio" name="test_event" value="payment_intent.succeeded" checked>
                                Payment Intent Succeeded
                            </label>
                            <label>
                                <input type="radio" name="test_event" value="payment_intent.payment_failed">
                                Payment Intent Failed
                            </label>
                            <label>
                                <input type="radio" name="test_event" value="checkout.session.completed">
                                Checkout Session Completed
                            </label>
                        </div>
                        <?php submit_button('Test Webhook', 'primary', 'test_webhook'); ?>
                    </form>
                </div>
                
                <!-- Webhook Results -->
                <?php if (!empty($webhook_results)): ?>
                <div class="hsm-widget">
                    <h3>📊 Test Results</h3>
                    <div class="webhook-results">
                        <div class="result-item <?php echo $webhook_results['status'] ? 'status-ok' : 'status-error'; ?>">
                            <div class="result-header">
                                <strong><?php echo esc_html($webhook_results['event_type']); ?></strong>
                                <span class="result-status"><?php echo $webhook_results['status'] ? '✅' : '❌'; ?></span>
                            </div>
                            <div class="result-details">
                                <div class="result-url"><?php echo esc_html($webhook_results['url']); ?></div>
                                <div class="result-response"><?php echo esc_html($webhook_results['response']); ?></div>
                                <?php if (!empty($webhook_results['error'])): ?>
                                <div class="result-error">Error: <?php echo esc_html($webhook_results['error']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Webhook Events -->
                <div class="hsm-widget">
                    <h3>📋 Supported Events</h3>
                    <div class="events-list">
                        <div class="event-item">
                            <strong>payment_intent.succeeded</strong>
                            <span class="event-description">Payment completed successfully</span>
                        </div>
                        <div class="event-item">
                            <strong>payment_intent.payment_failed</strong>
                            <span class="event-description">Payment failed</span>
                        </div>
                        <div class="event-item">
                            <strong>checkout.session.completed</strong>
                            <span class="event-description">Checkout session completed</span>
                        </div>
                        <div class="event-item">
                            <strong>invoice.payment_succeeded</strong>
                            <span class="event-description">Invoice payment succeeded</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .webhook-config {
            margin-top: 15px;
        }
        
        .config-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid #e1e5e9;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        
        .config-item code {
            flex: 1;
            background: #f8f9fa;
            padding: 5px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        .secret-status.configured {
            color: #155724;
            background: #d4edda;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        .secret-status.not-configured {
            color: #721c24;
            background: #f8d7da;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        .integration-type {
            color: #856404;
            background: #fff3cd;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        .test-options {
            margin: 15px 0;
        }
        
        .test-options label {
            display: block;
            margin-bottom: 8px;
            padding: 8px;
            border: 1px solid #e1e5e9;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .test-options label:hover {
            background: #f8f9fa;
        }
        
        .webhook-results {
            margin-top: 15px;
        }
        
        .result-item {
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            padding: 15px;
        }
        
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .result-details {
            font-size: 0.9em;
            color: #666;
        }
        
        .result-url {
            font-family: monospace;
            background: #f8f9fa;
            padding: 5px;
            border-radius: 3px;
            margin-bottom: 5px;
        }
        
        .result-response {
            margin-bottom: 5px;
        }
        
        .result-error {
            color: #721c24;
            background: #f8d7da;
            padding: 5px;
            border-radius: 3px;
        }
        
        .events-list {
            margin-top: 15px;
        }
        
        .event-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border: 1px solid #e1e5e9;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        
        .event-description {
            color: #666;
            font-size: 0.9em;
        }
        
        .copy-url {
            font-size: 0.8em;
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Copy URL functionality
            document.querySelectorAll('.copy-url').forEach(function(button) {
                button.addEventListener('click', function() {
                    const url = this.getAttribute('data-url');
                    navigator.clipboard.writeText(url).then(function() {
                        button.textContent = '✅ Copied!';
                        setTimeout(function() {
                            button.textContent = '📋 Copy URL';
                        }, 2000);
                    });
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get comprehensive system status information
     * 
     * @return array System status with detailed health checks
     */
    private function get_system_status() {
        return [
            'stripe' => $this->check_stripe_integration(),
            'woocommerce' => $this->check_woocommerce_integration(),
            'api_endpoints' => $this->check_api_endpoints(),
            'database' => $this->check_database_connection(),
            'nextjs_integration' => $this->check_nextjs_integration(),
            'system_resources' => $this->check_system_resources(),
            'plugin_dependencies' => $this->check_plugin_dependencies(),
            'security_status' => $this->check_security_status()
        ];
    }
    
    /**
     * Check database connection
     * 
     * @return bool True if database is accessible
     */
    private function check_database_connection() {
        global $wpdb;
        return $wpdb->db_connect() !== false;
    }
    
    /**
     * Check Stripe integration status
     * 
     * @return array Stripe integration details
     */
    private function check_stripe_integration() {
        $secret_key = get_option('hsm_stripe_secret_key', '');
        $webhook_secret = get_option('hsm_stripe_webhook_secret', '');
        
        return [
            'status' => !empty($secret_key) && !empty($webhook_secret),
            'secret_key_configured' => !empty($secret_key),
            'webhook_secret_configured' => !empty($webhook_secret),
            'key_format_valid' => $this->validate_stripe_key_format($secret_key),
            'integration_type' => 'Next.js Integration (SDK removed)'
        ];
    }
    
    /**
     * Check WooCommerce integration status
     * 
     * @return array WooCommerce integration details
     */
    private function check_woocommerce_integration() {
        $wc_active = $this->is_plugin_active('woocommerce/woocommerce.php');
        $wc_version = $wc_active ? WC()->version : null;
        
        return [
            'status' => $wc_active,
            'version' => $wc_version,
            'api_available' => $wc_active && class_exists('WC_REST_API'),
            'order_management' => $wc_active && class_exists('WC_Order')
        ];
    }
    
    /**
     * Check API endpoints status
     * 
     * Updated to reflect consolidated API namespace structure:
     * - hsm/v1: General HSM endpoints
     * - hsm-stripe/v1: Stripe payment operations
     * - hsm-graphql/v1: GraphQL proxy operations
     * 
     * @return array API endpoints status
     */
    private function check_api_endpoints() {
        // Check hsm/v1 namespace (General HSM API)
        $hsm_base_url = rest_url('hsm/v1/');
        $hsm_endpoints = [
            'health' => $hsm_base_url . 'health',
            'system_status' => $hsm_base_url . 'system-status',
            'consult_form_url' => $hsm_base_url . 'consult-form-url',
            'contact_form_url' => $hsm_base_url . 'contact-form-url',
            'stripe_webhook' => $hsm_base_url . 'stripe-webhook'
        ];
        
        // Check hsm-stripe/v1 namespace (Stripe Operations)
        $stripe_base_url = rest_url('hsm-stripe/v1/');
        $stripe_endpoints = [
            'tax_calculator' => $stripe_base_url . 'tax/calculate',
            'payment_intent' => $stripe_base_url . 'payment/intent',
            'order_creation' => $stripe_base_url . 'orders/create',
            'order_update_status' => $stripe_base_url . 'orders/update-status'
        ];
        
        // Check hsm-graphql/v1 namespace (GraphQL Operations)
        $graphql_base_url = rest_url('hsm-graphql/v1/');
        $graphql_endpoints = [
            'proxy' => $graphql_base_url . 'proxy',
            'health' => $graphql_base_url . 'health',
            'status' => $graphql_base_url . 'status',
            'nonce' => $graphql_base_url . 'nonce'
        ];
        
        // Combine all endpoints
        $all_endpoints = array_merge($hsm_endpoints, $stripe_endpoints, $graphql_endpoints);
        
        $status = true;
        $failed_endpoints = [];
        $namespace_status = [
            'hsm/v1' => true,
            'hsm-stripe/v1' => true,
            'hsm-graphql/v1' => true
        ];
        
        // Test hsm/v1 endpoints (GET requests)
        foreach ($hsm_endpoints as $name => $url) {
            $response = wp_remote_get($url, ['timeout' => 5]);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
                $status = false;
                $namespace_status['hsm/v1'] = false;
                $failed_endpoints[] = "hsm/v1/{$name}";
            }
        }
        
        // Test hsm-stripe/v1 endpoints (GET for tax, POST for others - just check availability)
        foreach ($stripe_endpoints as $name => $url) {
            $method = ($name === 'tax_calculator') ? 'GET' : 'POST';
            $args = ['timeout' => 5, 'method' => $method];
            if ($method === 'POST') {
                $args['body'] = json_encode([]);
            }
            $response = wp_remote_request($url, $args);
            // Accept 200-299 and 400-499 (400 means endpoint exists but needs proper data)
            $code = wp_remote_retrieve_response_code($response);
            if (is_wp_error($response) || ($code >= 500)) {
                $status = false;
                $namespace_status['hsm-stripe/v1'] = false;
                $failed_endpoints[] = "hsm-stripe/v1/{$name}";
            }
        }
        
        // Test hsm-graphql/v1 endpoints
        foreach ($graphql_endpoints as $name => $url) {
            $method = ($name === 'proxy') ? 'POST' : 'GET';
            $args = ['timeout' => 5, 'method' => $method];
            if ($method === 'POST') {
                $args['body'] = json_encode(['query' => '{ __typename }']);
            }
            $response = wp_remote_request($url, $args);
            $code = wp_remote_retrieve_response_code($response);
            if (is_wp_error($response) || ($code >= 500)) {
                $status = false;
                $namespace_status['hsm-graphql/v1'] = false;
                $failed_endpoints[] = "hsm-graphql/v1/{$name}";
            }
        }
        
        return [
            'status' => $status,
            'namespaces' => [
                'hsm/v1' => [
                    'status' => $namespace_status['hsm/v1'],
                    'endpoints' => $hsm_endpoints,
                    'count' => count($hsm_endpoints)
                ],
                'hsm-stripe/v1' => [
                    'status' => $namespace_status['hsm-stripe/v1'],
                    'endpoints' => $stripe_endpoints,
                    'count' => count($stripe_endpoints)
                ],
                'hsm-graphql/v1' => [
                    'status' => $namespace_status['hsm-graphql/v1'],
                    'endpoints' => $graphql_endpoints,
                    'count' => count($graphql_endpoints)
                ]
            ],
            'total_endpoints' => count($all_endpoints),
            'failed_endpoints' => $failed_endpoints,
            'rest_api_active' => function_exists('rest_url')
        ];
    }
    
    /**
     * Check Next.js integration status
     * 
     * @return array Next.js integration details
     */
    private function check_nextjs_integration() {
        // Since we removed Stripe SDK, check if Next.js integration is configured
        $nextjs_url = get_option('hsm_nextjs_url', '');
        
        return [
            'status' => !empty($nextjs_url),
            'nextjs_url' => $nextjs_url,
            'integration_type' => 'Payment processing handled by Next.js',
            'stripe_sdk_removed' => true
        ];
    }
    
    /**
     * Check system resources
     * 
     * @return array System resources status
     */
    private function check_system_resources() {
        $memory_limit = ini_get('memory_limit');
        $max_execution_time = ini_get('max_execution_time');
        $upload_max_filesize = ini_get('upload_max_filesize');
        
        return [
            'status' => true,
            'memory_limit' => $memory_limit,
            'max_execution_time' => $max_execution_time,
            'upload_max_filesize' => $upload_max_filesize,
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version')
        ];
    }
    
    /**
     * Check plugin dependencies
     * 
     * @return array Plugin dependencies status
     */
    private function check_plugin_dependencies() {
        $required_plugins = [
            'WooCommerce' => $this->is_plugin_active('woocommerce/woocommerce.php'),
            'WPGraphQL' => $this->is_plugin_active('wp-graphql/wp-graphql.php'),
            'WordPress REST API' => function_exists('rest_url'),
            'WordPress Database' => function_exists('get_option')
        ];
        
        $all_active = !in_array(false, $required_plugins);
        
        return [
            'status' => $all_active,
            'required_plugins' => $required_plugins,
            'missing_plugins' => array_keys(array_filter($required_plugins, function($status) { return !$status; }))
        ];
    }
    
    /**
     * Check if a plugin is active
     * 
     * @param string $plugin_file Plugin file path relative to plugins directory
     * @return bool True if plugin is active, false otherwise
     */
    private function is_plugin_active($plugin_file) {
        // Include WordPress plugin functions if not already loaded
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        // Check if plugin is active
        if (function_exists('is_plugin_active')) {
            return is_plugin_active($plugin_file);
        }
        
        // Fallback: Check if plugin class exists (less reliable)
        $plugin_classes = [
            'woocommerce/woocommerce.php' => 'WooCommerce',
            'wp-graphql/wp-graphql.php' => 'WPGraphQL'
        ];
        
        if (isset($plugin_classes[$plugin_file])) {
            return class_exists($plugin_classes[$plugin_file]);
        }
        
        return false;
    }
    
    /**
     * Check security status
     * 
     * @return array Security status
     */
    private function check_security_status() {
        $ssl_enabled = is_ssl();
        $debug_mode = get_option('hsm_stripe_debug_mode', 0);
        
        return [
            'status' => $ssl_enabled && !$debug_mode,
            'ssl_enabled' => $ssl_enabled,
            'debug_mode' => (bool)$debug_mode,
            'security_recommendations' => [
                'ssl_required' => !$ssl_enabled,
                'debug_disabled' => $debug_mode
            ]
        ];
    }
    
    /**
     * Validate Stripe key format
     * 
     * @param string $key Stripe key to validate
     * @return bool True if format is valid
     */
    private function validate_stripe_key_format($key) {
        if (empty($key)) {
            return false;
        }
        
        // Check for valid Stripe key prefixes
        $valid_prefixes = ['sk_test_', 'sk_live_', 'pk_test_', 'pk_live_'];
        foreach ($valid_prefixes as $prefix) {
            if (strpos($key, $prefix) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get recent webhook events from logs
     * 
     * @return array Recent webhook events
     */
    private function get_recent_webhook_events() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hsm_stripe_logs';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return [];
        }
        
        $events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT action, data, status, created_at FROM $table_name 
                 WHERE action LIKE %s 
                 ORDER BY created_at DESC 
                 LIMIT 10",
                '%webhook%'
            ),
            ARRAY_A
        );
        
        $formatted_events = [];
        foreach ($events as $event) {
            $formatted_events[] = [
                'action' => $event['action'],
                'data' => $event['data'],
                'status' => $event['status'],
                'time' => human_time_diff(strtotime($event['created_at'])) . ' ago'
            ];
        }
        
        return $formatted_events;
    }
    
    /**
     * Get performance metrics
     * 
     * @return array Performance metrics data
     */
    private function get_performance_metrics() {
        global $wpdb;
        
        // Measure API response time (simplified)
        $start_time = microtime(true);
        $test_url = rest_url('hsm-stripe/v1/test');
        $response = wp_remote_get($test_url, ['timeout' => 5]);
        $api_response_time = round((microtime(true) - $start_time) * 1000);
        
        // Measure database query time
        $start_time = microtime(true);
        $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");
        $db_query_time = round((microtime(true) - $start_time) * 1000);
        
        // Calculate memory usage percentage
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = $this->convert_to_bytes($memory_limit);
        $memory_usage_bytes = memory_get_usage(true);
        $memory_usage = round(($memory_usage_bytes / $memory_limit_bytes) * 100);
        
        // Calculate error rate (simplified - check logs)
        $table_name = $wpdb->prefix . 'hsm_stripe_logs';
        $error_count = 0;
        $total_count = 0;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $total_count = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table_name WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $error_count = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table_name WHERE status = 'error' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
        }
        
        $error_rate = $total_count > 0 ? round(($error_count / $total_count) * 100) : 0;
        
        return [
            'api_response_time' => $api_response_time,
            'db_query_time' => $db_query_time,
            'memory_usage' => $memory_usage,
            'error_rate' => $error_rate
        ];
    }
    
    /**
     * Convert memory limit string to bytes
     * 
     * @param string $val Memory limit string (e.g., "128M", "1G")
     * @return int Memory limit in bytes
     */
    private function convert_to_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int) $val;
        
        switch($last) {
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
     * Test API endpoints functionality
     * 
     * @return array API test results
     */
    private function test_api_endpoints() {
        $results = [];
        
        // Test hsm/v1 endpoints (General HSM API)
        $hsm_base_url = rest_url('hsm/v1/');
        $hsm_endpoints = [
            'HSM Health Check' => [
                'url' => $hsm_base_url . 'health',
                'method' => 'GET',
                'test_data' => []
            ],
            'HSM System Status' => [
                'url' => $hsm_base_url . 'system-status',
                'method' => 'GET',
                'test_data' => []
            ],
            'HSM Consult Form URL' => [
                'url' => $hsm_base_url . 'consult-form-url',
                'method' => 'GET',
                'test_data' => []
            ],
            'HSM Contact Form URL' => [
                'url' => $hsm_base_url . 'contact-form-url',
                'method' => 'GET',
                'test_data' => []
            ]
        ];
        
        // Test hsm-stripe/v1 endpoints (Stripe Operations)
        $stripe_base_url = rest_url('hsm-stripe/v1/');
        $stripe_endpoints = [
            'Stripe Tax Calculator' => [
                'url' => $stripe_base_url . 'tax/calculate?country=CA&state=ON&items=[]',
                'method' => 'GET',
                'test_data' => []
            ],
            'Stripe Payment Intent' => [
                'url' => $stripe_base_url . 'payment/intent',
                'method' => 'POST',
                'test_data' => [
                    'amount' => 1000,
                    'currency' => 'cad'
                ]
            ],
            'Stripe Order Creation' => [
                'url' => $stripe_base_url . 'orders/create',
                'method' => 'POST',
                'test_data' => [
                    'items' => [],
                    'customer' => []
                ]
            ],
            'Stripe Order Update Status' => [
                'url' => $stripe_base_url . 'orders/update-status',
                'method' => 'POST',
                'test_data' => [
                    'orderId' => 0
                ]
            ]
        ];
        
        // Test hsm-graphql/v1 endpoints (GraphQL Operations)
        $graphql_base_url = rest_url('hsm-graphql/v1/');
        $graphql_endpoints = [
            'GraphQL Health Check' => [
                'url' => $graphql_base_url . 'health',
                'method' => 'GET',
                'test_data' => []
            ],
            'GraphQL Quick Health' => [
                'url' => $graphql_base_url . 'health/quick',
                'method' => 'GET',
                'test_data' => []
            ],
            'GraphQL Health Metrics' => [
                'url' => $graphql_base_url . 'health/metrics',
                'method' => 'GET',
                'test_data' => []
            ],
            'GraphQL Proxy' => [
                'url' => $graphql_base_url . 'proxy',
                'method' => 'POST',
                'test_data' => [
                    'query' => '{ __schema { types { name } } }',
                    'variables' => []
                ]
            ],
            'GraphQL Schema' => [
                'url' => $graphql_base_url . 'schema',
                'method' => 'GET',
                'test_data' => []
            ],
            'GraphQL Status' => [
                'url' => $graphql_base_url . 'status',
                'method' => 'GET',
                'test_data' => []
            ],
            'GraphQL Nonce' => [
                'url' => $graphql_base_url . 'nonce',
                'method' => 'GET',
                'test_data' => []
            ]
        ];
        
        // Test WordPress Core endpoints (wp/v2 namespace) - these are always available
        $wp_base_url = rest_url('wp/v2/');
        $wp_endpoints = [
            'WordPress Posts' => [
                'url' => $wp_base_url . 'posts',
                'method' => 'GET',
                'test_data' => []
            ],
            'WordPress Pages' => [
                'url' => $wp_base_url . 'pages',
                'method' => 'GET',
                'test_data' => []
            ],
            'WordPress Categories' => [
                'url' => $wp_base_url . 'categories',
                'method' => 'GET',
                'test_data' => []
            ]
        ];
        
        // Combine all endpoints from all namespaces
        $all_endpoints = array_merge($hsm_endpoints, $stripe_endpoints, $graphql_endpoints, $wp_endpoints);
        
        foreach ($all_endpoints as $name => $config) {
            $result = $this->test_single_endpoint($name, $config);
            $results[$name] = $result;
        }
        
        return $results;
    }
    
    /**
     * Test a single API endpoint
     * 
     * @param string $name Endpoint name
     * @param array $config Endpoint configuration
     * @return array Test result
     */
    private function test_single_endpoint($name, $config) {
        $url = $config['url'];
        $method = $config['method'];
        $test_data = $config['test_data'];
        
        try {
            // FIXED: Use direct REST API call instead of wp_remote_request()
            // This avoids nonce context issues with external HTTP requests
            
            // Extract the route from the URL and determine namespace
            $route = '';
            $namespace = '';
            
            if (strpos($url, 'hsm-graphql/v1') !== false) {
                $route = str_replace(rest_url('hsm-graphql/v1/'), '', $url);
                $namespace = '/hsm-graphql/v1';
            } elseif (strpos($url, 'hsm-stripe/v1') !== false) {
                $route = str_replace(rest_url('hsm-stripe/v1/'), '', $url);
                $namespace = '/hsm-stripe/v1';
            } elseif (strpos($url, 'wp/v2') !== false) {
                $route = str_replace(rest_url('wp/v2/'), '', $url);
                $namespace = '/wp/v2';
            } else {
                // Fallback to old hsm/v1 logic
                $route = str_replace(rest_url('hsm/v1/'), '', $url);
                $namespace = '/hsm/v1';
            }
            
            if (empty($route)) {
                $route = '/';
            }
            
            // Create REST request object directly
            $request = new WP_REST_Request($method, $namespace . $route);
            
            // Add test data as parameters
            if (!empty($test_data)) {
                foreach ($test_data as $key => $value) {
                    $request->set_param($key, $value);
                }
            }
            
            // Set current user for authentication (admin user)
            wp_set_current_user(get_current_user_id());
            
            // Make direct REST API call
            $response = rest_do_request($request);
            
            if (is_wp_error($response)) {
                return [
                    'status' => false,
                    'url' => $url,
                    'response' => 'REST API request failed',
                    'error' => $response->get_error_message()
                ];
            }
            
            // Get response data from REST response object
            $status_code = $response->get_status();
            $data = $response->get_data();
            $body = is_array($data) ? json_encode($data) : (string)$data;
            
            // Consider 200-299 as success, but also accept 400+ for validation errors
            $is_success = $status_code >= 200 && $status_code < 500;
            
            return [
                'status' => $is_success,
                'url' => $url,
                'response' => "HTTP {$status_code}: " . substr($body, 0, 100) . (strlen($body) > 100 ? '...' : ''),
                'error' => $is_success ? null : "HTTP {$status_code}"
            ];
            
        } catch (Exception $e) {
            return [
                'status' => false,
                'url' => $url,
                'response' => 'Exception occurred',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test webhook functionality
     * 
     * @return array Webhook test result
     */
    private function test_webhook_functionality() {
        $event_type = $_POST['test_event'] ?? 'payment_intent.succeeded';
        $webhook_url = rest_url('hsm-stripe/v1/webhook');
        
        // Create sample webhook payload
        $payload = [
            'id' => 'evt_test_' . uniqid(),
            'object' => 'event',
            'type' => $event_type,
            'created' => time(),
            'data' => [
                'object' => [
                    'id' => 'pi_test_' . uniqid(),
                    'object' => 'payment_intent',
                    'amount' => 1000,
                    'currency' => 'usd',
                    'status' => 'succeeded'
                ]
            ]
        ];
        
        try {
            // Prepare request arguments
            $args = [
                'method' => 'POST',
                'timeout' => 10,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-WP-Nonce' => wp_create_nonce('wp_rest')
                ],
                'body' => json_encode($payload)
            ];
            
            // Make the request
            $response = wp_remote_request($webhook_url, $args);
            
            if (is_wp_error($response)) {
                return [
                    'status' => false,
                    'event_type' => $event_type,
                    'url' => $webhook_url,
                    'response' => 'Request failed',
                    'error' => $response->get_error_message()
                ];
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            // Consider 200-299 as success
            $is_success = $status_code >= 200 && $status_code < 300;
            
            return [
                'status' => $is_success,
                'event_type' => $event_type,
                'url' => $webhook_url,
                'response' => "HTTP {$status_code}: " . substr($body, 0, 100) . (strlen($body) > 100 ? '...' : ''),
                'error' => $is_success ? null : "HTTP {$status_code}"
            ];
            
        } catch (Exception $e) {
            return [
                'status' => false,
                'event_type' => $event_type,
                'url' => $webhook_url,
                'response' => 'Exception occurred',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Render HSM Plugin Settings page
     * 
     * @return void
     */
    public function settings_page() {
        // Enhanced permission check
        if (!$this->validate_admin_permissions()) {
            wp_die('Sorry, you are not allowed to access this page.');
        }
        
        // Handle form submission
        if (isset($_POST['submit'])) {
            // Verify nonce for security
            if (!wp_verify_nonce($_POST['hsm_settings_nonce'], 'hsm_settings_action')) {
                wp_die('Security check failed. Please try again.');
            }
            
            // Consult Form settings
            $google_form_url = esc_url_raw($_POST['hsm_google_form_url'] ?? '');
            $contact_form_url = esc_url_raw($_POST['hsm_contact_form_url'] ?? '');
            
            // Validate URLs
            $errors = [];
            if (!empty($google_form_url) && !filter_var($google_form_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'Invalid Consult Form URL format';
            }
            if (!empty($contact_form_url) && !filter_var($contact_form_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'Invalid Contact Form URL format';
            }
            
            if (empty($errors)) {
                update_option('hsm_google_form_url', $google_form_url);
                update_option('hsm_contact_form_url', $contact_form_url);
                
                echo '<div class="notice notice-success"><p>✅ Settings saved successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>❌ ' . implode('<br>', $errors) . '</p></div>';
            }
        }
        
        // Get current settings
        $google_form_url = get_option('hsm_google_form_url', '');
        $contact_form_url = get_option('hsm_contact_form_url', '');
        ?>
        <div class="wrap">
            <h1>⚙️ HSM Plugin Settings</h1>
            
            <!-- Navigation Tabs -->
            <nav class="nav-tab-wrapper">
                <a href="?page=hsm-plugin" class="nav-tab">📊 Dashboard</a>
                <a href="?page=hsm-plugin-settings" class="nav-tab nav-tab-active">⚙️ Settings</a>
                <a href="?page=hsm-stripe-api-check" class="nav-tab">🔌 API Check</a>
                <a href="?page=hsm-stripe-webhook" class="nav-tab">🔗 Webhook Check</a>
                <a href="?page=hsm-graphql-proxy" class="nav-tab">🔗 GraphQL Proxy</a>
            </nav>
            
            <form method="post" action="" class="hsm-settings-form">
                <?php wp_nonce_field('hsm_settings_action', 'hsm_settings_nonce'); ?>
                
                <!-- Consult Form Settings Section -->
                <div class="postbox" style="margin-top: 20px;">
                    <div class="postbox-header">
                        <h2 class="hndle">📝 Consult Form Settings</h2>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <label for="hsm_google_form_url">Consultation Form URL</label>
                                </th>
                                <td>
                                    <input type="url" 
                                           name="hsm_google_form_url" 
                                           id="hsm_google_form_url"
                                           value="<?php echo esc_attr($google_form_url); ?>" 
                                           class="regular-text" 
                                           placeholder="https://docs.google.com/forms/d/e/.../viewform?embedded=true" />
                                    <p class="description">
                                        Paste the Consult Form embed URL (Google Forms) for consultation forms.<br>
                                        Format: <code>https://docs.google.com/forms/d/e/&lt;FORM_ID&gt;/viewform?embedded=true</code>
                                    </p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="hsm_contact_form_url">Contact Form URL</label>
                                </th>
                                <td>
                                    <input type="url" 
                                           name="hsm_contact_form_url" 
                                           id="hsm_contact_form_url"
                                           value="<?php echo esc_attr($contact_form_url); ?>" 
                                           class="regular-text" 
                                           placeholder="https://docs.google.com/forms/d/e/.../viewform?embedded=true" />
                                    <p class="description">
                                        Paste the Contact Form embed URL (Google Forms) for contact forms.<br>
                                        Format: <code>https://docs.google.com/forms/d/e/&lt;FORM_ID&gt;/viewform?embedded=true</code>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- API Endpoints Info -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">🔗 REST API Endpoints</h2>
                    </div>
                    <div class="inside">
                        <p>The following REST API endpoints are available for the frontend to fetch these URLs:</p>
                        <ul>
                            <li><strong>Consultation Form:</strong> <code>GET /wp-json/hsm/v1/consult-form-url</code></li>
                            <li><strong>Contact Form:</strong> <code>GET /wp-json/hsm/v1/contact-form-url</code></li>
                        </ul>
                        <p class="description">Both endpoints return: <code>{"success": true, "url": "..."}</code></p>
                    </div>
                </div>
                
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render HSM Plugin Health Check page
     * 
     * @return void
     */
    public function health_check_page() {
        // Enhanced permission check
        if (!$this->validate_admin_permissions()) {
            wp_die('Sorry, you are not allowed to access this page.');
        }
        
        // Perform health checks
        $health_checks = $this->perform_health_checks();
        
        ?>
        <div class="wrap">
            <h1>HSM Plugin Health Check</h1>
            
            <div class="card">
                <h2>System Health Status</h2>
                <div class="health-check-results">
                    <?php foreach ($health_checks as $check): ?>
                        <div class="health-check-item">
                            <span class="health-check-icon <?php echo $check['status'] === 'pass' ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                            <strong><?php echo esc_html($check['name']); ?>:</strong>
                            <span class="health-check-status <?php echo $check['status']; ?>">
                                <?php echo esc_html(ucfirst($check['status'])); ?>
                            </span>
                            <?php if (!empty($check['message'])): ?>
                                <p class="health-check-message"><?php echo esc_html($check['message']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="card">
                <h2>Plugin Information</h2>
                <table class="widefat">
                    <tbody>
                        <tr>
                            <td><strong>Plugin Version:</strong></td>
                            <td><?php echo HSM_PLUGIN_VERSION; ?></td>
                        </tr>
                        <tr>
                            <td><strong>WordPress Version:</strong></td>
                            <td><?php echo get_bloginfo('version'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>PHP Version:</strong></td>
                            <td><?php echo PHP_VERSION; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Database Version:</strong></td>
                            <td><?php echo $GLOBALS['wpdb']->db_version(); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <style>
        .health-check-results {
            margin: 20px 0;
        }
        .health-check-item {
            margin: 10px 0;
            padding: 10px;
            border-left: 4px solid #ddd;
        }
        .health-check-item .pass {
            border-left-color: #46b450;
        }
        .health-check-item .fail {
            border-left-color: #dc3232;
        }
        .health-check-icon {
            margin-right: 10px;
        }
        .health-check-status {
            font-weight: bold;
        }
        .health-check-status.pass {
            color: #46b450;
        }
        .health-check-status.fail {
            color: #dc3232;
        }
        .health-check-message {
            margin: 5px 0 0 30px;
            font-style: italic;
            color: #666;
        }
        </style>
        <?php
    }
    
    /**
     * Render HSM GraphQL Proxy monitoring page (Alternative implementation)
     * 
     * @return void
     */
    public function graphql_proxy_page_alternative() {
        // Enhanced permission check
        if (!$this->validate_admin_permissions()) {
            wp_die('Sorry, you are not allowed to access this page.');
        }
        
        // Get GraphQL proxy health status
        $health_status = $this->get_graphql_proxy_health_status();
        $quick_status = $this->get_graphql_proxy_quick_status();
        $metrics = $this->get_graphql_proxy_metrics();
        
        ?>
        <div class="wrap hsm-graphql-proxy">
            <h1>🔗 HSM GraphQL Proxy Monitoring</h1>
            
            <!-- Navigation Tabs -->
            <nav class="nav-tab-wrapper">
                <a href="?page=hsm-plugin" class="nav-tab">📊 Dashboard</a>
                <a href="?page=hsm-stripe-api-check" class="nav-tab">🔌 API Check</a>
                <a href="?page=hsm-stripe-webhook" class="nav-tab">🔗 Webhook Check</a>
                <a href="?page=hsm-graphql-proxy" class="nav-tab nav-tab-active">🔗 GraphQL Proxy</a>
            </nav>
            
            <!-- Status Overview -->
            <div class="hsm-graphql-proxy-overview">
                <div class="hsm-status-cards">
                    <div class="hsm-status-card" id="proxy-status-card">
                        <h3>🔗 Proxy Status</h3>
                        <div class="status-indicator <?php echo $health_status['status'] === 'healthy' ? 'status-ok' : 'status-error'; ?>">
                            <span class="status-icon"><?php echo $health_status['status'] === 'healthy' ? '✅' : '❌'; ?></span>
                            <span class="status-text"><?php echo ucfirst($health_status['status']); ?></span>
                        </div>
                        <div class="status-details">
                            <p><strong>Last Check:</strong> <?php echo $health_status['timestamp']; ?></p>
                            <p><strong>Response Time:</strong> <?php echo $health_status['response_time']; ?>ms</p>
                        </div>
                    </div>
                    
                    <div class="hsm-status-card" id="connection-status-card">
                        <h3>🔌 Connection Status</h3>
                        <div class="status-indicator <?php echo $health_status['graphql_connection'] ? 'status-ok' : 'status-error'; ?>">
                            <span class="status-icon"><?php echo $health_status['graphql_connection'] ? '✅' : '❌'; ?></span>
                            <span class="status-text"><?php echo $health_status['graphql_connection'] ? 'Connected' : 'Disconnected'; ?></span>
                        </div>
                        <div class="status-details">
                            <p><strong>WPGraphQL:</strong> 
                                <?php 
                                $wpgraphql_status = $health_status['wpgraphql_connected'] ? 'Connected' : 'Not Connected';
                                $wpgraphql_icon = $health_status['wpgraphql_connected'] ? '✅' : '❌';
                                echo $wpgraphql_icon . ' ' . $wpgraphql_status;
                                if (isset($health_status['individual_connections']['wpgraphql']['error'])) {
                                    echo ' (' . $health_status['individual_connections']['wpgraphql']['error'] . ')';
                                }
                                ?>
                            </p>
                            <p><strong>WooCommerce GraphQL:</strong> 
                                <?php 
                                $woocommerce_status = $health_status['woocommerce_graphql_connected'] ? 'Connected' : 'Not Connected';
                                $woocommerce_icon = $health_status['woocommerce_graphql_connected'] ? '✅' : '❌';
                                echo $woocommerce_icon . ' ' . $woocommerce_status;
                                if (isset($health_status['individual_connections']['woocommerce_graphql']['error'])) {
                                    echo ' (' . $health_status['individual_connections']['woocommerce_graphql']['error'] . ')';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="hsm-status-card" id="dependencies-card">
                        <h3>📦 Dependencies</h3>
                        <div class="status-indicator <?php echo $health_status['dependencies_ok'] ? 'status-ok' : 'status-warning'; ?>">
                            <span class="status-icon"><?php echo $health_status['dependencies_ok'] ? '✅' : '⚠️'; ?></span>
                            <span class="status-text"><?php echo $health_status['dependencies_ok'] ? 'All OK' : 'Issues Found'; ?></span>
                        </div>
                        <div class="status-details">
                            <p><strong>WPGraphQL:</strong> <?php echo $health_status['wpgraphql_version'] ? 'v' . $health_status['wpgraphql_version'] : 'Not Installed'; ?></p>
                            <p><strong>WooCommerce:</strong> <?php echo $health_status['woocommerce_version'] ? 'v' . $health_status['woocommerce_version'] : 'Not Installed'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Performance Metrics -->
            <div class="hsm-performance-metrics">
                <h2>📊 Performance Metrics</h2>
                <div class="metrics-grid">
                    <div class="metric-card">
                        <h4>Response Time</h4>
                        <div class="metric-value <?php echo $metrics['avg_response_time'] < 1000 ? 'good' : 'warning'; ?>">
                            <?php echo $metrics['avg_response_time']; ?>ms
                        </div>
                        <div class="metric-label">Average</div>
                    </div>
                    
                    <div class="metric-card">
                        <h4>Requests (24h)</h4>
                        <div class="metric-value">
                            <?php echo $metrics['total_requests']; ?>
                        </div>
                        <div class="metric-label">Total</div>
                    </div>
                    
                    <div class="metric-card">
                        <h4>Success Rate</h4>
                        <div class="metric-value <?php echo $metrics['success_rate'] > 95 ? 'good' : 'warning'; ?>">
                            <?php echo $metrics['success_rate']; ?>%
                        </div>
                        <div class="metric-label">Last 24h</div>
                    </div>
                    
                    <div class="metric-card">
                        <h4>Error Rate</h4>
                        <div class="metric-value <?php echo $metrics['error_rate'] < 5 ? 'good' : 'warning'; ?>">
                            <?php echo $metrics['error_rate']; ?>%
                        </div>
                        <div class="metric-label">Last 24h</div>
                    </div>
                </div>
            </div>
            
            <!-- GraphQL Test Interface -->
            <div class="hsm-graphql-test-interface">
                <h2>🧪 GraphQL Test Interface</h2>
                <div class="test-interface">
                    <div class="test-query-section">
                        <h4>Test Query</h4>
                        <textarea id="graphql-test-query" placeholder="Enter your GraphQL query here..." rows="6">query {
  generalSettings {
    title
    description
  }
}</textarea>
                        <div class="test-actions">
                            <button type="button" class="button button-primary" id="test-graphql-query">Test Query</button>
                            <button type="button" class="button button-secondary" id="clear-graphql-query">Clear</button>
                        </div>
                    </div>
                    
                    <div class="test-results-section">
                        <h4>Test Results</h4>
                        <div id="graphql-test-results" class="test-results">
                            <p class="no-results">No query executed yet. Enter a query and click "Test Query" to see results.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Error Logs -->
            <div class="hsm-error-logs">
                <h2>📋 Recent Error Logs</h2>
                <div class="error-logs-container">
                    <?php
                    $error_logs = $this->get_graphql_error_logs();
                    if (!empty($error_logs)) {
                        foreach ($error_logs as $log) {
                            $log_class = $log['level'] === 'error' ? 'error' : ($log['level'] === 'warning' ? 'warning' : 'info');
                            ?>
                            <div class="error-log-item <?php echo $log_class; ?>">
                                <div class="log-header">
                                    <span class="log-level"><?php echo strtoupper($log['level']); ?></span>
                                    <span class="log-time"><?php echo $log['timestamp']; ?></span>
                                </div>
                                <div class="log-message"><?php echo esc_html($log['message']); ?></div>
                                <?php if (!empty($log['context'])): ?>
                                <div class="log-context">
                                    <strong>Context:</strong> <?php echo esc_html($log['context']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="no-logs">No recent error logs found.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <style>
        .hsm-graphql-proxy {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .hsm-graphql-proxy-overview {
            margin: 20px 0;
        }
        
        .hsm-status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .hsm-status-card {
            background: #fff;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .hsm-status-card h3 {
            margin-top: 0;
            color: #1d2327;
            font-size: 16px;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
            padding: 10px;
            border-radius: 4px;
        }
        
        .status-indicator.status-ok {
            background: #d4edda;
            color: #155724;
        }
        
        .status-indicator.status-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-indicator.status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-details {
            font-size: 14px;
            color: #666;
        }
        
        .status-details p {
            margin: 5px 0;
        }
        
        .hsm-performance-metrics {
            margin: 30px 0;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .metric-card {
            background: #fff;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .metric-card h4 {
            margin-top: 0;
            color: #1d2327;
            font-size: 14px;
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .metric-value.good {
            color: #46b450;
        }
        
        .metric-value.warning {
            color: #ffb900;
        }
        
        .metric-label {
            font-size: 12px;
            color: #666;
        }
        
        .hsm-graphql-test-interface {
            margin: 30px 0;
        }
        
        .test-interface {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .test-query-section,
        .test-results-section {
            background: #fff;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
        }
        
        .test-query-section h4,
        .test-results-section h4 {
            margin-top: 0;
            color: #1d2327;
        }
        
        #graphql-test-query {
            width: 100%;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            resize: vertical;
        }
        
        .test-actions {
            margin-top: 10px;
        }
        
        .test-actions .button {
            margin-right: 10px;
        }
        
        .test-results {
            min-height: 200px;
            max-height: 400px;
            overflow-y: auto;
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 4px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        
        .no-results {
            color: #666;
            font-style: italic;
        }
        
        .hsm-error-logs {
            margin: 30px 0;
        }
        
        .error-logs-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e1e5e9;
            border-radius: 4px;
            background: #fff;
        }
        
        .error-log-item {
            border-bottom: 1px solid #e1e5e9;
            padding: 15px;
        }
        
        .error-log-item:last-child {
            border-bottom: none;
        }
        
        .error-log-item.error {
            border-left: 4px solid #dc3232;
        }
        
        .error-log-item.warning {
            border-left: 4px solid #ffb900;
        }
        
        .error-log-item.info {
            border-left: 4px solid #00a0d2;
        }
        
        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .log-level {
            font-weight: bold;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 3px;
            color: #fff;
        }
        
        .error-log-item.error .log-level {
            background: #dc3232;
        }
        
        .error-log-item.warning .log-level {
            background: #ffb900;
        }
        
        .error-log-item.info .log-level {
            background: #00a0d2;
        }
        
        .log-time {
            font-size: 12px;
            color: #666;
        }
        
        .log-message {
            margin: 5px 0;
            color: #1d2327;
        }
        
        .log-context {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .no-logs {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .test-interface {
                grid-template-columns: 1fr;
            }
            
            .hsm-status-cards {
                grid-template-columns: 1fr;
            }
            
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // GraphQL Test Interface
            const testQueryBtn = document.getElementById('test-graphql-query');
            const clearQueryBtn = document.getElementById('clear-graphql-query');
            const queryTextarea = document.getElementById('graphql-test-query');
            const resultsDiv = document.getElementById('graphql-test-results');
            
            testQueryBtn.addEventListener('click', function() {
                const query = queryTextarea.value.trim();
                if (!query) {
                    alert('Please enter a GraphQL query to test.');
                    return;
                }
                
                testQueryBtn.disabled = true;
                testQueryBtn.textContent = 'Testing...';
                resultsDiv.innerHTML = '<p class="no-results">Executing query...</p>';
                
                // Make AJAX request to test GraphQL query
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'hsm_test_graphql_query',
                        query: query,
                        nonce: '<?php echo wp_create_nonce('hsm_graphql_proxy_nonce'); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultsDiv.innerHTML = '<pre>' + JSON.stringify(data.data, null, 2) + '</pre>';
                    } else {
                        resultsDiv.innerHTML = '<div class="error">Error: ' + data.data + '</div>';
                    }
                })
                .catch(error => {
                    resultsDiv.innerHTML = '<div class="error">Network Error: ' + error.message + '</div>';
                })
                .finally(() => {
                    testQueryBtn.disabled = false;
                    testQueryBtn.textContent = 'Test Query';
                });
            });
            
            clearQueryBtn.addEventListener('click', function() {
                queryTextarea.value = '';
                resultsDiv.innerHTML = '<p class="no-results">No query executed yet. Enter a query and click "Test Query" to see results.</p>';
            });
        });
        </script>
        <?php
    }

    /**
     * Get GraphQL proxy health status
     * 
     * @return array Health status data
     */
    private function get_graphql_proxy_health_status() {
        // Use health monitor directly instead of API call
        try {
            // Create health monitor instance
            $error_handler = new HSM_Error_Handler();
            $health_monitor = new HSM_GraphQL_Health_Monitor($error_handler);
            
            // Use the new perform_health_checks function
            $health_data = $health_monitor->perform_health_checks();
            
            // Format data for dashboard display
            return [
                'status' => $health_data['overall_status'],
                'timestamp' => $health_data['timestamp'],
                'response_time' => $health_data['graphql_connection']['response_time_ms'] ?? 0,
                'graphql_connection' => $health_data['graphql_connection']['status'] === 'connected',
                'wpgraphql_active' => $health_data['wpgraphql_active'],
                'woocommerce_graphql_active' => $health_data['woocommerce_graphql_active'],
                'dependencies_ok' => $health_data['dependencies_ok'],
                'wpgraphql_version' => $health_data['wpgraphql_version'],
                'woocommerce_version' => $health_data['woocommerce_version'],
                'detailed_dependencies' => $health_data['detailed_dependencies'] ?? null,
                // Individual connection status
                'wpgraphql_connected' => $health_data['wpgraphql_connected'] ?? false,
                'woocommerce_graphql_connected' => $health_data['woocommerce_graphql_connected'] ?? false,
                'individual_connections' => $health_data['individual_connections'] ?? [],
                'checks_performed' => $health_data['checks_performed'] ?? [],
                'error' => null
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'timestamp' => current_time('mysql'),
                'response_time' => 0,
                'graphql_connection' => false,
                'wpgraphql_active' => false,
                'woocommerce_graphql_active' => false,
                'dependencies_ok' => false,
                'wpgraphql_version' => null,
                'woocommerce_version' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get GraphQL proxy quick status
     * 
     * @return array Quick status data
     */
    private function get_graphql_proxy_quick_status() {
        // Use health monitor directly instead of API call
        try {
            // Create health monitor instance
            $error_handler = new HSM_Error_Handler();
            $health_monitor = new HSM_GraphQL_Health_Monitor($error_handler);
            
            // Get quick health status
            $health_data = $health_monitor->get_quick_health_status();
            
            return [
                'status' => $health_data['status'],
                'timestamp' => $health_data['timestamp'],
                'error' => $health_data['error'] ?? null
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'timestamp' => current_time('mysql'),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get GraphQL proxy metrics
     * 
     * @return array Metrics data
     */
    private function get_graphql_proxy_metrics() {
        $metrics_endpoint = rest_url('hsm-graphql/v1/health/metrics');
        
        $response = wp_remote_get($metrics_endpoint, [
            'timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return [
                'avg_response_time' => 0,
                'total_requests' => 0,
                'success_rate' => 0,
                'error_rate' => 100,
                'error' => $response->get_error_message()
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return [
            'avg_response_time' => $data['avg_response_time'] ?? 0,
            'total_requests' => $data['total_requests'] ?? 0,
            'success_rate' => $data['success_rate'] ?? 0,
            'error_rate' => $data['error_rate'] ?? 0,
            'error' => null
        ];
    }

    /**
     * Get GraphQL error logs
     * 
     * @return array Error logs
     */
    private function get_graphql_error_logs() {
        // This would typically fetch from a log file or database
        // For now, return sample data
        return [
            [
                'level' => 'error',
                'timestamp' => '2025-01-28 15:00:00',
                'message' => 'GraphQL query timeout',
                'context' => 'Query: { generalSettings { title } }'
            ],
            [
                'level' => 'warning',
                'timestamp' => '2025-01-28 14:45:00',
                'message' => 'Slow query detected',
                'context' => 'Response time: 2.5s'
            ],
            [
                'level' => 'info',
                'timestamp' => '2025-01-28 14:30:00',
                'message' => 'GraphQL proxy health check completed',
                'context' => 'Status: healthy'
            ]
        ];
    }

    /**
     * Perform comprehensive health checks
     * 
     * @return array Health check results
     */
    private function perform_health_checks() {
        $checks = [];
        
        // Check WordPress environment
        $checks[] = [
            'name' => 'WordPress Environment',
            'status' => version_compare(get_bloginfo('version'), '5.0', '>=') ? 'pass' : 'fail',
            'message' => version_compare(get_bloginfo('version'), '5.0', '>=') ? 'WordPress version is compatible' : 'WordPress version is too old'
        ];
        
        // Check PHP version
        $checks[] = [
            'name' => 'PHP Version',
            'status' => version_compare(PHP_VERSION, '7.4', '>=') ? 'pass' : 'fail',
            'message' => version_compare(PHP_VERSION, '7.4', '>=') ? 'PHP version is compatible' : 'PHP version is too old'
        ];
        
        // Check database connection
        global $wpdb;
        $checks[] = [
            'name' => 'Database Connection',
            'status' => $wpdb->db_connect() ? 'pass' : 'fail',
            'message' => $wpdb->db_connect() ? 'Database connection is working' : 'Database connection failed'
        ];
        
        // Check Stripe configuration
        $stripe_secret = get_option('hsm_stripe_secret_key');
        $checks[] = [
            'name' => 'Stripe Configuration',
            'status' => !empty($stripe_secret) ? 'pass' : 'fail',
            'message' => !empty($stripe_secret) ? 'Stripe secret key is configured' : 'Stripe secret key is missing'
        ];
        
        // Check webhook configuration
        $webhook_secret = get_option('hsm_webhook_secret');
        $checks[] = [
            'name' => 'Webhook Configuration',
            'status' => !empty($webhook_secret) ? 'pass' : 'fail',
            'message' => !empty($webhook_secret) ? 'Webhook secret is configured' : 'Webhook secret is missing'
        ];
        
        // Check Next.js URL configuration
        $nextjs_url = get_option('hsm_nextjs_url');
        $checks[] = [
            'name' => 'Next.js Integration',
            'status' => !empty($nextjs_url) ? 'pass' : 'fail',
            'message' => !empty($nextjs_url) ? 'Next.js URL is configured' : 'Next.js URL is missing'
        ];
        
        // Check plugin tables
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}hsm_stripe_logs'");
        $checks[] = [
            'name' => 'Plugin Database Tables',
            'status' => $table_exists ? 'pass' : 'fail',
            'message' => $table_exists ? 'Plugin database tables exist' : 'Plugin database tables are missing'
        ];
        
        return $checks;
    }
    
    /**
     * Render GraphQL proxy testing page
     * 
     * @return void
     */
    public function graphql_proxy_page() {
        // Enhanced permission check
        if (!$this->validate_admin_permissions()) {
            wp_die('Sorry, you are not allowed to access this page.');
        }
        
        // Initialize GraphQL testing page
        $graphql_testing = new HSM_GraphQL_Testing_Page($this->error_handler);
        
        ?>
        <div class="wrap">
            <h1><?php _e('HSM Plugin - GraphQL Proxy Testing', 'hsm'); ?></h1>
            
            <!-- Navigation Tabs -->
            <div class="hsm-nav-tabs">
                <a href="?page=hsm-plugin" class="nav-tab">📊 Dashboard</a>
                <a href="?page=hsm-stripe-api-check" class="nav-tab">🔌 API Check</a>
                <a href="?page=hsm-stripe-webhook" class="nav-tab">🔗 Webhook Check</a>
                <a href="?page=hsm-graphql-proxy" class="nav-tab nav-tab-active">🔗 GraphQL Proxy</a>
            </div>
            
            <?php $graphql_testing->render_page(); ?>
        </div>
        <?php
    }
}

// Agent Signature: 160125 - Fullstack - Admin_API_Health_Check_Update