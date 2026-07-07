<?php
/**
 * Email Template Test Script
 * 
 * This script tests the email template functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HSM_Email_Template_Test {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_test_menu']);
    }
    
    public function add_test_menu() {
        add_management_page(
            'HSM Email Template Test',
            'HSM Email Template Test',
            'manage_options',
            'hsm-email-template-test',
            [$this, 'test_page']
        );
    }
    
    public function test_page() {
        echo '<div class="wrap">';
        echo '<h1>HSM Email Template Test</h1>';
        
        // Test 1: Check if template file exists
        echo '<h2>Test 1: Email Template File</h2>';
        $template_file = plugin_dir_path(__FILE__) . 'templates/emails/customer-completed-order.php';
        if (file_exists($template_file)) {
            echo '<p style="color: green;">✓ Email template file exists</p>';
            echo '<p>Template path: ' . esc_html($template_file) . '</p>';
        } else {
            echo '<p style="color: red;">✗ Email template file not found</p>';
        }
        
        // Test 2: Check if hooks are registered
        echo '<h2>Test 2: WooCommerce Email Hooks</h2>';
        $hooks_registered = has_action('woocommerce_email_order_details', 'HSM_Stripe_Plugin::override_customer_completed_order_template');
        if ($hooks_registered) {
            echo '<p style="color: green;">✓ Email template override hooks are registered</p>';
            echo '<p>Hook priority: ' . $hooks_registered . '</p>';
        } else {
            echo '<p style="color: orange;">⚠ Email template override hooks may not be registered (plugin may not be active)</p>';
        }
        
        $locate_hook = has_filter('woocommerce_locate_template', 'HSM_Stripe_Plugin::locate_custom_email_template');
        if ($locate_hook) {
            echo '<p style="color: green;">✓ Template location hook is registered</p>';
            echo '<p>Hook priority: ' . $locate_hook . '</p>';
        } else {
            echo '<p style="color: orange;">⚠ Template location hook may not be registered</p>';
        }
        
        // Test 3: Check WooCommerce integration
        echo '<h2>Test 3: WooCommerce Integration</h2>';
        if (class_exists('WooCommerce')) {
            echo '<p style="color: green;">✓ WooCommerce is active</p>';
            
            // Check if there are any orders
            $orders = wc_get_orders(['limit' => 1]);
            if ($orders) {
                echo '<p style="color: green;">✓ WooCommerce orders exist</p>';
                echo '<p><a href="' . admin_url('edit.php?post_type=shop_order') . '" class="button">View Orders</a></p>';
            } else {
                echo '<p style="color: orange;">⚠ No WooCommerce orders found</p>';
            }
        } else {
            echo '<p style="color: red;">✗ WooCommerce is not active</p>';
        }
        
        // Test 4: Test template rendering
        echo '<h2>Test 4: Template Rendering Test</h2>';
        if (class_exists('WooCommerce') && !empty($orders)) {
            $test_order = $orders[0];
            echo '<p>Testing template rendering with order #' . $test_order->get_order_number() . '</p>';
            
            // Test template rendering
            ob_start();
            $order = $test_order;
            $sent_to_admin = false;
            $plain_text = false;
            $email = new WC_Email_Customer_Completed_Order();
            
            // Simulate the template rendering
            $template_path = plugin_dir_path(__FILE__) . 'templates/emails/customer-completed-order.php';
            if (file_exists($template_path)) {
                include $template_path;
                $rendered_content = ob_get_clean();
                
                if (!empty($rendered_content)) {
                    echo '<p style="color: green;">✓ Template renders successfully</p>';
                    echo '<p>Rendered content length: ' . strlen($rendered_content) . ' characters</p>';
                    
                    // Show preview
                    echo '<h3>Template Preview:</h3>';
                    echo '<div style="border: 1px solid #ccc; padding: 10px; max-height: 300px; overflow-y: auto;">';
                    echo '<pre>' . esc_html(substr($rendered_content, 0, 1000)) . '...</pre>';
                    echo '</div>';
                } else {
                    echo '<p style="color: red;">✗ Template rendered empty content</p>';
                }
            } else {
                ob_end_clean();
                echo '<p style="color: red;">✗ Template file not found for rendering test</p>';
            }
        } else {
            echo '<p style="color: orange;">⚠ Cannot test template rendering - WooCommerce not active or no orders</p>';
        }
        
        // Test 5: Check error logs
        echo '<h2>Test 5: Error Log Check</h2>';
        $error_log = ini_get('error_log');
        if ($error_log && file_exists($error_log)) {
            $recent_errors = $this->get_recent_email_errors($error_log);
            if (empty($recent_errors)) {
                echo '<p style="color: green;">✓ No recent email-related errors found</p>';
            } else {
                echo '<p style="color: orange;">⚠ Recent email-related errors found:</p>';
                echo '<pre>' . esc_html($recent_errors) . '</pre>';
            }
        } else {
            echo '<p style="color: orange;">⚠ Error log not accessible</p>';
        }
        
        // Test 6: Template file permissions
        echo '<h2>Test 6: Template File Permissions</h2>';
        if (file_exists($template_file)) {
            $permissions = fileperms($template_file);
            $readable = is_readable($template_file);
            echo '<p>File permissions: ' . decoct($permissions & 0777) . '</p>';
            echo '<p>Readable: ' . ($readable ? 'Yes' : 'No') . '</p>';
            
            if ($readable) {
                echo '<p style="color: green;">✓ Template file is readable</p>';
            } else {
                echo '<p style="color: red;">✗ Template file is not readable</p>';
            }
        }
        
        echo '</div>';
    }
    
    private function get_recent_email_errors($error_log) {
        $lines = file($error_log);
        $recent_lines = array_slice($lines, -50); // Last 50 lines
        $email_errors = array_filter($recent_lines, function($line) {
            return strpos($line, 'HSM Email') !== false;
        });
        return implode('', $email_errors);
    }
}

// Initialize test only in admin
if (is_admin()) {
    new HSM_Email_Template_Test();
}