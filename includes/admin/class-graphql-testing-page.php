<?php
/**
 * HSM GraphQL Testing Page Class
 * 
 * Handles GraphQL proxy dashboard testing functionality in admin dashboard
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_GraphQL_Testing_Page {
    
    /**
     * Error handler instance
     * 
     * @var HSM_Error_Handler
     */
    private $error_handler;
    
    /**
     * GraphQL manager instance
     * 
     * @var HSM_GraphQL_Manager
     */
    private $graphql_manager;
    
    /**
     * Constructor
     * 
     * @param HSM_Error_Handler $error_handler Error handler instance
     */
    public function __construct($error_handler) {
        $this->error_handler = $error_handler;
        $this->graphql_manager = new HSM_GraphQL_Manager($error_handler);
        
        // Hook into WordPress admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_hsm_test_graphql_connection', array($this, 'ajax_test_graphql_connection'));
        add_action('wp_ajax_hsm_execute_graphql_query', array($this, 'ajax_execute_graphql_query'));
        add_action('wp_ajax_hsm_get_query_templates', array($this, 'ajax_get_query_templates'));
    }
    
    /**
     * Add admin menu for GraphQL testing
     */
    public function add_admin_menu() {
        // This will be integrated into existing HSM admin dashboard
        // The menu item is handled by the main admin menu class
    }
    
    /**
     * Enqueue admin scripts and styles
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on HSM admin pages
        if (strpos($hook, 'hsm-') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'hsm-graphql-testing-css',
            HSM_PLUGIN_URL . 'assets/css/graphql-testing.css',
            array(),
            HSM_PLUGIN_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'hsm-graphql-testing-js',
            HSM_PLUGIN_URL . 'assets/js/graphql-testing.js',
            array('jquery'),
            HSM_PLUGIN_VERSION,
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('hsm-graphql-testing-js', 'hsmGraphQLTesting', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hsm_graphql_testing_nonce'),
            'strings' => array(
                'testing' => __('Testing...', 'hsm'),
                'success' => __('Success', 'hsm'),
                'error' => __('Error', 'hsm'),
                'connectionTest' => __('Connection Test', 'hsm'),
                'queryExecution' => __('Query Execution', 'hsm'),
                'responseTime' => __('Response Time', 'hsm'),
                'ms' => __('ms', 'hsm'),
            )
        ));
    }
    
    /**
     * Render GraphQL testing page content
     */
    public function render_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'hsm'));
        }
        
        ?>
        <div class="wrap hsm-graphql-testing">
            <h1><?php _e('GraphQL Proxy Testing', 'hsm'); ?></h1>
            <p class="description"><?php _e('Test GraphQL proxy connections and execute queries directly from the admin dashboard.', 'hsm'); ?></p>
            
            <!-- Connection Status Section -->
            <div class="hsm-testing-section">
                <h2><?php _e('Connection Status', 'hsm'); ?></h2>
                <div class="hsm-connection-status">
                    <div class="hsm-status-indicator" id="connection-status">
                        <span class="status-dot"></span>
                        <span class="status-text"><?php _e('Checking...', 'hsm'); ?></span>
                    </div>
                    <button type="button" class="button button-secondary" id="test-connection">
                        <?php _e('Test Connection', 'hsm'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Query Testing Section -->
            <div class="hsm-testing-section">
                <h2><?php _e('Query Testing', 'hsm'); ?></h2>
                
                <!-- Query Templates -->
                <div class="hsm-query-templates">
                    <label for="query-template"><?php _e('Query Template:', 'hsm'); ?></label>
                    <select id="query-template" class="regular-text">
                        <option value=""><?php _e('Select a template...', 'hsm'); ?></option>
                        <option value="products"><?php _e('Get Products', 'hsm'); ?></option>
                        <option value="product"><?php _e('Get Single Product', 'hsm'); ?></option>
                        <option value="cart"><?php _e('Get Cart', 'hsm'); ?></option>
                        <option value="categories"><?php _e('Get Categories', 'hsm'); ?></option>
                        <option value="custom"><?php _e('Custom Query', 'hsm'); ?></option>
                    </select>
                    <button type="button" class="button button-secondary" id="load-template">
                        <?php _e('Load Template', 'hsm'); ?>
                    </button>
                </div>
                
                <!-- Query Input -->
                <div class="hsm-query-input">
                    <label for="graphql-query"><?php _e('GraphQL Query:', 'hsm'); ?></label>
                    <textarea id="graphql-query" rows="10" class="large-text code" placeholder="<?php _e('Enter your GraphQL query here...', 'hsm'); ?>"></textarea>
                </div>
                
                <!-- Query Variables -->
                <div class="hsm-query-variables">
                    <label for="graphql-variables"><?php _e('Variables (JSON):', 'hsm'); ?></label>
                    <textarea id="graphql-variables" rows="3" class="large-text code" placeholder='{"key": "value"}'></textarea>
                </div>
                
                <!-- Execute Button -->
                <div class="hsm-query-actions">
                    <button type="button" class="button button-primary" id="execute-query">
                        <?php _e('Execute Query', 'hsm'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="clear-query">
                        <?php _e('Clear', 'hsm'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Results Section -->
            <div class="hsm-testing-section">
                <h2><?php _e('Results', 'hsm'); ?></h2>
                <div class="hsm-results-container">
                    <div class="hsm-results-header">
                        <span class="results-label"><?php _e('Response:', 'hsm'); ?></span>
                        <span class="response-time" id="response-time"></span>
                    </div>
                    <div class="hsm-results-content">
                        <pre id="query-results"><code><?php _e('Results will appear here...', 'hsm'); ?></code></pre>
                    </div>
                </div>
            </div>
            
            <!-- Error Log Section -->
            <div class="hsm-testing-section">
                <h2><?php _e('Error Log', 'hsm'); ?></h2>
                <div class="hsm-error-log">
                    <div class="error-log-header">
                        <button type="button" class="button button-secondary" id="refresh-error-log">
                            <?php _e('Refresh Log', 'hsm'); ?>
                        </button>
                        <button type="button" class="button button-secondary" id="clear-error-log">
                            <?php _e('Clear Log', 'hsm'); ?>
                        </button>
                    </div>
                    <div class="error-log-content">
                        <pre id="error-log-content"><?php _e('No errors...', 'hsm'); ?></pre>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for connection testing
     */
    public function ajax_test_graphql_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hsm_graphql_testing_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        try {
            // Test GraphQL proxy connection
            $test_query = 'query { __schema { types { name } } }';
            $start_time = microtime(true);
            
            $result = $this->graphql_manager->execute_query($test_query);
            
            $response_time = round((microtime(true) - $start_time) * 1000, 2);
            
            if ($result && !isset($result['errors'])) {
                wp_send_json_success(array(
                    'message' => 'Connection successful',
                    'response_time' => $response_time,
                    'status' => 'connected'
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Connection failed: ' . (isset($result['errors']) ? implode(', ', $result['errors']) : 'Unknown error'),
                    'status' => 'disconnected'
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Connection error: ' . $e->getMessage(),
                'status' => 'error'
            ));
        }
    }
    
    /**
     * AJAX handler for query execution
     */
    public function ajax_execute_graphql_query() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hsm_graphql_testing_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $query = sanitize_textarea_field($_POST['query']);
        $variables = isset($_POST['variables']) ? $_POST['variables'] : '{}';
        
        if (empty($query)) {
            wp_send_json_error(array('message' => 'Query cannot be empty'));
        }
        
        try {
            // Parse variables
            $variables_array = json_decode($variables, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(array('message' => 'Invalid JSON in variables'));
            }
            
            $start_time = microtime(true);
            $result = $this->graphql_manager->execute_query($query, $variables_array);
            $response_time = round((microtime(true) - $start_time) * 1000, 2);
            
            wp_send_json_success(array(
                'result' => $result,
                'response_time' => $response_time,
                'query' => $query,
                'variables' => $variables_array
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Query execution error: ' . $e->getMessage(),
                'query' => $query
            ));
        }
    }
    
    /**
     * AJAX handler for getting query templates
     */
    public function ajax_get_query_templates() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hsm_graphql_testing_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $templates = array(
            'products' => array(
                'query' => 'query GetProducts($first: Int, $after: String) {
                    products(first: $first, after: $after) {
                        edges {
                            node {
                                id
                                name
                                slug
                                price
                                description
                                image {
                                    sourceUrl
                                }
                            }
                        }
                        pageInfo {
                            hasNextPage
                            endCursor
                        }
                    }
                }',
                'variables' => '{"first": 10}'
            ),
            'product' => array(
                'query' => 'query GetProduct($id: ID!) {
                    product(id: $id) {
                        id
                        name
                        slug
                        price
                        description
                        shortDescription
                        image {
                            sourceUrl
                        }
                        galleryImages {
                            nodes {
                                sourceUrl
                            }
                        }
                    }
                }',
                'variables' => '{"id": "cHJvZHVjdDo1"}'
            ),
            'cart' => array(
                'query' => 'query GetCart {
                    cart {
                        contents {
                            nodes {
                                key
                                quantity
                                product {
                                    node {
                                        id
                                        name
                                        price
                                    }
                                }
                            }
                        }
                        total
                        subtotal
                    }
                }',
                'variables' => '{}'
            ),
            'categories' => array(
                'query' => 'query GetCategories {
                    productCategories {
                        nodes {
                            id
                            name
                            slug
                            description
                            count
                        }
                    }
                }',
                'variables' => '{}'
            )
        );
        
        wp_send_json_success($templates);
    }
}