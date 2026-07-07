<?php
/**
 * HSM GraphQL Manager Class
 * 
 * Handles all GraphQL operations with inheritance from existing API structure.
 * Routes frontend GraphQL requests through WordPress instead of direct connection.
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_GraphQL_Manager {
    
    /**
     * Error handler instance
     * 
     * @var HSM_Error_Handler
     */
    private $error_handler;
    
    /**
     * Logger instance
     * 
     * @var HSM_Logger
     */
    private $logger;
    
    /**
     * WordPress GraphQL endpoint URL
     * 
     * @var string
     */
    private $graphql_endpoint;
    
    /**
     * GraphQL client instance
     * 
     * @var mixed
     */
    private $graphql_client;
    
    /**
     * Constructor
     * 
     * @param HSM_Error_Handler $error_handler Error handler instance
     */
    public function __construct($error_handler) {
        $this->error_handler = $error_handler;
        $this->logger = HSM_Logger::get_instance();
        
        // Set GraphQL endpoint to use HSM proxy
        $this->graphql_endpoint = rest_url('hsm-graphql/v1/proxy');
        
        // Initialize GraphQL client
        $this->initialize_graphql_client();
        
        $this->logger->info('HSM GraphQL Manager initialized');
    }
    
    /**
     * Initialize GraphQL client
     * 
     * @return void
     */
    private function initialize_graphql_client() {
        try {
            // Initialize GraphQL client with HSM proxy
            $this->graphql_client = $this->create_graphql_client();
            
            $this->logger->info('GraphQL client initialized successfully with HSM proxy');
            
        } catch (Exception $e) {
            $this->error_handler->log_error('GraphQL client initialization failed', $e);
            $this->graphql_client = null;
        }
    }
    
    /**
     * Create GraphQL client with WordPress authentication
     * 
     * @return mixed GraphQL client instance
     */
    private function create_graphql_client() {
        // Use WordPress HTTP API for GraphQL requests
        return new class($this->graphql_endpoint, $this->error_handler) {
            private $endpoint;
            private $error_handler;
            
            public function __construct($endpoint, $error_handler) {
                $this->endpoint = $endpoint;
                $this->error_handler = $error_handler;
            }
            
            /**
             * Execute GraphQL query
             * 
             * @param string $query GraphQL query string
             * @param array $variables Query variables
             * @return array Query result
             */
            public function request($query, $variables = []) {
                $response = wp_remote_post($this->endpoint, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode([
                        'query' => $query,
                        'variables' => $variables
                    ]),
                    'timeout' => 30
                ]);
                
                if (is_wp_error($response)) {
                    throw new Exception('GraphQL request failed: ' . $response->get_error_message());
                }
                
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if (isset($data['errors'])) {
                    throw new Exception('GraphQL errors: ' . json_encode($data['errors']));
                }
                
                return $data['data'] ?? [];
            }
            
            /**
             * Get authentication token
             * 
             * @return string Auth token
             */
            private function get_auth_token() {
                // Use WordPress nonce for authentication
                return wp_create_nonce('hsm_graphql_request');
            }
        };
    }
    
    /**
     * Execute GraphQL query
     * 
     * @param string $query GraphQL query string
     * @param array $variables Query variables
     * @return array Query result
     */
    public function execute_query($query, $variables = []) {
        try {
            if (!$this->graphql_client) {
                throw new Exception('GraphQL client not initialized');
            }
            
            $this->logger->info('Executing GraphQL query', [
                'query' => substr($query, 0, 100) . '...',
                'variables' => $variables
            ]);
            
            $result = $this->graphql_client->request($query, $variables);
            
            $this->logger->info('GraphQL query executed successfully');
            
            return $result;
            
        } catch (Exception $e) {
            $this->error_handler->log_error('GraphQL query execution failed', $e);
            throw $e;
        }
    }
    
    /**
     * Execute GraphQL mutation
     * 
     * @param string $mutation GraphQL mutation string
     * @param array $variables Mutation variables
     * @return array Mutation result
     */
    public function execute_mutation($mutation, $variables = []) {
        return $this->execute_query($mutation, $variables);
    }
    
    /**
     * Get all products with basic information
     * 
     * @return array Products data
     */
    public function get_all_products() {
        $query = '
            query GetProducts {
                products(where: { typeIn: [SIMPLE] }, first: 50) {
                    nodes {
                        id
                        databaseId
                        name
                        slug
                        description
                        shortDescription
                        productSpecifications
                        relatedOptions
                        image {
                            sourceUrl
                        }
                        galleryImages(first: 10) {
                            nodes {
                                sourceUrl
                            }
                        }
                        ... on SimpleProduct {
                            price
                            regularPrice
                            salePrice
                        }
                        ... on ProductWithPricing {
                            price
                            regularPrice
                            salePrice
                        }
                    }
                }
            }
        ';
        
        return $this->execute_query($query);
    }
    
    /**
     * Get single product by slug
     * 
     * @param string $slug Product slug
     * @return array Product data
     */
    public function get_product_by_slug($slug) {
        $query = '
            query GetProductBySlug($slug: ID!) {
                product(id: $slug, idType: SLUG) {
                    id
                    databaseId
                    name
                    slug
                    description
                    shortDescription
                    productSpecifications
                    relatedOptions
                    image {
                        sourceUrl
                    }
                    galleryImages(first: 10) {
                        nodes {
                            sourceUrl
                        }
                    }
                    ... on SimpleProduct {
                        price
                        regularPrice
                        salePrice
                    }
                    ... on ProductWithVariations {
                        attributes {
                            nodes {
                                name
                            }
                        }
                        variations(first: 50) {
                            nodes {
                                id
                                databaseId
                                sku
                                price
                                regularPrice
                                salePrice
                                image { sourceUrl }
                                attributes { nodes { id name value } }
                            }
                        }
                    }
                }
            }
        ';
        
        return $this->execute_query($query, ['slug' => $slug]);
    }
    
    /**
     * Get products by database IDs
     * 
     * @param array $ids Array of product database IDs
     * @return array Products data
     */
    public function get_products_by_ids($ids) {
        $query = '
            query GetProductsByIds($ids: [Int]) {
                products(where: { include: $ids, typeIn: [VARIABLE] }) {
                    nodes {
                        id
                        databaseId
                        name
                        slug
                        __typename
                        description
                        shortDescription
                        productSpecifications
                        globalAttributes { 
                            nodes { 
                                label 
                                terms { 
                                    nodes { 
                                        name 
                                    } 
                                } 
                            } 
                        }
                        type
                        relatedOptions
                        variableType
                        image { sourceUrl }
                        galleryImages(first: 10) { 
                            nodes { 
                                sourceUrl 
                            } 
                        }
                        ... on SimpleProduct { 
                            price 
                            regularPrice 
                            salePrice 
                        }
                        ... on ProductWithVariations {
                            attributes{
                                nodes{
                                    name
                                }
                            }
                            variations(first: 50) {
                                nodes {
                                    id
                                    databaseId
                                    sku
                                    price
                                    regularPrice
                                    salePrice
                                    image { sourceUrl }
                                    attributes { nodes { id name value } }
                                }
                            }
                        }
                    }
                }
            }
        ';
        
        return $this->execute_query($query, ['ids' => $ids]);
    }
    
    /**
     * Get option product by ID
     * 
     * @param int $id Product database ID
     * @return array Product data
     */
    public function get_option_product_by_id($id) {
        $query = '
            query GetOptionProductById($id: Int!) {
                product(id: $id, idType: DATABASE_ID) {
                    id
                    databaseId
                    name
                    slug
                    description
                    shortDescription
                    productSpecifications
                    relatedOptions
                    image {
                        sourceUrl
                    }
                    galleryImages(first: 10) {
                        nodes {
                            sourceUrl
                        }
                    }
                    ... on SimpleProduct {
                        price
                        regularPrice
                        salePrice
                    }
                    ... on ProductWithVariations {
                        attributes {
                            nodes {
                                name
                            }
                        }
                        variations(first: 50) {
                            nodes {
                                id
                                databaseId
                                sku
                                price
                                regularPrice
                                salePrice
                                image { sourceUrl }
                                attributes { nodes { id name value } }
                            }
                        }
                    }
                }
            }
        ';
        
        return $this->execute_query($query, ['id' => $id]);
    }
    
    /**
     * Get cart data
     * 
     * @return array Cart data
     */
    public function get_cart() {
        $query = '
            query GetCart {
                cart {
                    contents {
                        nodes {
                            key
                            quantity
                            product {
                                node {
                                    id
                                    databaseId
                                    name
                                    slug
                                    price
                                    image {
                                        sourceUrl
                                    }
                                }
                            }
                            variation {
                                node {
                                    id
                                    databaseId
                                    sku
                                    price
                                    image {
                                        sourceUrl
                                    }
                                    attributes {
                                        nodes {
                                            id
                                            name
                                            value
                                        }
                                    }
                                }
                            }
                        }
                    }
                    subtotal
                    total
                    totalTax
                    shippingTotal
                    feeTotal
                    discountTotal
                }
            }
        ';
        
        return $this->execute_query($query);
    }
    
    /**
     * Get configuration categories
     * 
     * @return array Configuration categories
     */
    public function get_configuration_categories() {
        $query = '
            query GetConfigurationCategories {
                productCategories(where: { parent: 0 }) {
                    nodes {
                        id
                        databaseId
                        name
                        slug
                        description
                        image {
                            sourceUrl
                        }
                        children {
                            nodes {
                                id
                                databaseId
                                name
                                slug
                                description
                                image {
                                    sourceUrl
                                }
                            }
                        }
                    }
                }
            }
        ';
        
        return $this->execute_query($query);
    }
    
    /**
     * Check product compatibility
     * 
     * @param array $product_ids Array of product IDs to check compatibility
     * @return array Compatibility result
     */
    public function check_compatibility($product_ids) {
        $query = '
            query CheckCompatibility($productIds: [Int]) {
                products(where: { include: $productIds }) {
                    nodes {
                        id
                        databaseId
                        name
                        slug
                        productSpecifications
                        globalAttributes {
                            nodes {
                                label
                                terms {
                                    nodes {
                                        name
                                    }
                                }
                            }
                        }
                    }
                }
            }
        ';
        
        return $this->execute_query($query, ['productIds' => $product_ids]);
    }
    
    /**
     * Calculate financing options
     * 
     * @param float $amount Amount to finance
     * @param int $term Financing term in months
     * @return array Financing options
     */
    public function calculate_financing($amount, $term) {
        // This would typically call an external financing API
        // For now, return mock data
        return [
            'amount' => $amount,
            'term' => $term,
            'monthly_payment' => $amount / $term,
            'interest_rate' => 5.99,
            'total_cost' => $amount * 1.0599
        ];
    }
    
    /**
     * Estimate insurance cost
     * 
     * @param array $products Array of products to insure
     * @param string $coverage_type Type of coverage
     * @return array Insurance estimate
     */
    public function estimate_insurance($products, $coverage_type = 'comprehensive') {
        // This would typically call an external insurance API
        // For now, return mock data
        $total_value = array_sum(array_column($products, 'price'));
        
        return [
            'products' => $products,
            'coverage_type' => $coverage_type,
            'total_value' => $total_value,
            'monthly_premium' => $total_value * 0.01,
            'annual_premium' => $total_value * 0.12
        ];
    }
    
    /**
     * Load configuration
     * 
     * @param string $configuration_id Configuration ID
     * @return array Configuration data
     */
    public function load_configuration($configuration_id) {
        // This would load saved configuration from database
        // For now, return mock data
        return [
            'id' => $configuration_id,
            'products' => [],
            'options' => [],
            'total_price' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
    }
    
    /**
     * Add configuration to cart
     * 
     * @param array $configuration Configuration data
     * @return array Cart update result
     */
    public function add_configuration_to_cart($configuration) {
        // This would add configuration items to WooCommerce cart
        // For now, return mock data
        return [
            'success' => true,
            'cart_key' => 'config_' . time(),
            'message' => 'Configuration added to cart successfully'
        ];
    }
    
    /**
     * Update cart item configuration
     * 
     * @param string $cart_key Cart item key
     * @param array $configuration New configuration data
     * @return array Update result
     */
    public function update_cart_item_configuration($cart_key, $configuration) {
        // This would update cart item configuration
        // For now, return mock data
        return [
            'success' => true,
            'cart_key' => $cart_key,
            'message' => 'Cart item configuration updated successfully'
        ];
    }
    
    /**
     * Save configuration
     * 
     * @param array $configuration Configuration data
     * @return array Save result
     */
    public function save_configuration($configuration) {
        // This would save configuration to database
        // For now, return mock data
        $configuration_id = 'config_' . time();
        
        return [
            'success' => true,
            'configuration_id' => $configuration_id,
            'message' => 'Configuration saved successfully'
        ];
    }
    
    /**
     * Get featured products
     * 
     * @param int $limit Number of products to return
     * @return array Featured products
     */
    public function get_featured_products($limit = 10) {
        $query = '
            query GetFeaturedProducts($limit: Int) {
                products(where: { featured: true }, first: $limit) {
                    nodes {
                        id
                        databaseId
                        name
                        slug
                        description
                        shortDescription
                        productSpecifications
                        image {
                            sourceUrl
                        }
                        galleryImages(first: 5) {
                            nodes {
                                sourceUrl
                            }
                        }
                        ... on SimpleProduct {
                            price
                            regularPrice
                            salePrice
                        }
                    }
                }
            }
        ';
        
        return $this->execute_query($query, ['limit' => $limit]);
    }
    
    /**
     * Create headless Stripe session
     * 
     * @param array $session_data Session data
     * @return array Session result
     */
    public function create_headless_stripe_session($session_data) {
        // This would create Stripe session for headless checkout
        // For now, return mock data
        return [
            'success' => true,
            'session_id' => 'cs_' . time(),
            'url' => 'https://checkout.stripe.com/pay/cs_' . time(),
            'message' => 'Stripe session created successfully'
        ];
    }
    
    /**
     * Create headless order
     * 
     * @param array $order_data Order data
     * @return array Order result
     */
    public function create_headless_order($order_data) {
        // This would create WooCommerce order
        // For now, return mock data
        $order_id = wp_insert_post([
            'post_type' => 'shop_order',
            'post_status' => 'wc-pending',
            'post_title' => 'Order ' . time()
        ]);
        
        return [
            'success' => true,
            'order_id' => $order_id,
            'order_number' => '#' . $order_id,
            'message' => 'Order created successfully'
        ];
    }
    
    /**
     * Validate GraphQL endpoint
     * 
     * @return bool True if endpoint is valid
     */
    public function validate_endpoint() {
        try {
            $test_query = '{ __schema { types { name } } }';
            $this->execute_query($test_query);
            return true;
        } catch (Exception $e) {
            $this->error_handler->log_error('GraphQL endpoint validation failed', $e);
            return false;
        }
    }
    
    /**
     * Get GraphQL endpoint URL
     * 
     * @return string GraphQL endpoint URL
     */
    public function get_endpoint_url() {
        return $this->graphql_endpoint;
    }
    
    /**
     * Proxy GraphQL request from frontend
     * 
     * @param string $query GraphQL query string
     * @param array $variables Query variables
     * @param array $headers Request headers
     * @return array Query result
     */
    public function proxy_graphql_request($query, $variables = [], $headers = []) {
        try {
            // Validate query for security
            $this->validate_query($query);
            
            // Log the proxy request
            $this->logger->info('Proxying GraphQL request', [
                'query' => substr($query, 0, 100) . '...',
                'variables_count' => count($variables),
                'headers' => array_keys($headers)
            ]);
            
            // Execute the query
            $result = $this->execute_query($query, $variables);
            
            // Log successful proxy
            $this->logger->info('GraphQL proxy request completed successfully');
            
            return [
                'success' => true,
                'data' => $result,
                'timestamp' => current_time('mysql')
            ];
            
        } catch (Exception $e) {
            $this->error_handler->log_error('GraphQL proxy request failed', $e);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => current_time('mysql')
            ];
        }
    }
    
    /**
     * Validate GraphQL query for security
     * 
     * @param string $query GraphQL query string
     * @throws Exception If query is invalid or contains forbidden operations
     */
    private function validate_query($query) {
        // Remove comments and normalize whitespace
        $clean_query = preg_replace('/#.*$/m', '', $query);
        $clean_query = preg_replace('/\s+/', ' ', trim($clean_query));
        
        // Check for forbidden operations
        $forbidden_patterns = [
            '/__schema/i',
            '/__type/i',
            '/__typename/i',
            '/introspection/i',
            '/mutation\s+{/i',
            '/subscription\s+{/i'
        ];
        
        foreach ($forbidden_patterns as $pattern) {
            if (preg_match($pattern, $clean_query)) {
                throw new Exception('Forbidden GraphQL operation detected: ' . $pattern);
            }
        }
        
        // Check for allowed operations only
        $allowed_operations = [
            'query',
            'products',
            'product',
            'cart',
            'productCategories',
            'productCategory'
        ];
        
        $has_allowed_operation = false;
        foreach ($allowed_operations as $operation) {
            if (stripos($clean_query, $operation) !== false) {
                $has_allowed_operation = true;
                break;
            }
        }
        
        if (!$has_allowed_operation) {
            throw new Exception('No allowed GraphQL operations found in query');
        }
    }
    
    /**
     * Get GraphQL client status
     * 
     * @return array Client status information
     */
    public function get_client_status() {
        return [
            'initialized' => $this->graphql_client !== null,
            'endpoint' => $this->graphql_endpoint,
            'wpgraphql_active' => $this->is_plugin_active('wp-graphql/wp-graphql.php'),
            'endpoint_valid' => $this->validate_endpoint(),
            'proxy_method_available' => method_exists($this, 'proxy_graphql_request')
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
}