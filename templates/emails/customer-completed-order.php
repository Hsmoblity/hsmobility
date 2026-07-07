<?php
/**
 * Custom WooCommerce Email Template - Customer Completed Order
 * 
 * Override for WooCommerce default customer completed order email
 * Displays clear order information and line items
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var WC_Order $order
 * @var WC_Email $email
 * @var bool $sent_to_admin
 * @var bool $plain_text
 */

// Get order data
$order_id = $order->get_id();
$order_number = $order->get_order_number();
$order_date = $order->get_date_created()->format('F j, Y \a\t g:i A');
$order_status = ucfirst($order->get_status());

// Customer information
$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
$customer_email = $order->get_billing_email();
$customer_phone = $order->get_billing_phone();

// Shipping information
$shipping_name = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
$shipping_address = $order->get_formatted_shipping_address();
$shipping_method = $order->get_shipping_method();

// Billing information
$billing_address = $order->get_formatted_billing_address();
$payment_method = $order->get_payment_method_title();

// Pricing information
$subtotal = $order->get_subtotal();
$tax = $order->get_total_tax();
$shipping_cost = $order->get_shipping_total();
$discount = $order->get_total_discount();
$total = $order->get_total();
$currency = $order->get_currency();

// Company information
$company_name = get_bloginfo('name');
$company_email = get_option('admin_email');
$company_phone = get_option('woocommerce_store_phone');
$company_website = home_url();

if ($plain_text) {
    // Plain text version
    echo "========================================\n";
    echo "ORDER CONFIRMATION\n";
    echo "========================================\n\n";
    
    echo "Thank you for your order!\n\n";
    
    echo "ORDER DETAILS:\n";
    echo "Order Number: {$order_number}\n";
    echo "Order Date: {$order_date}\n";
    echo "Status: {$order_status}\n\n";
    
    echo "CUSTOMER INFORMATION:\n";
    echo "Name: {$customer_name}\n";
    echo "Email: {$customer_email}\n";
    if ($customer_phone) {
        echo "Phone: {$customer_phone}\n";
    }
    echo "\n";
    
    echo "SHIPPING INFORMATION:\n";
    echo "Name: {$shipping_name}\n";
    echo "Address:\n{$shipping_address}\n";
    echo "Method: {$shipping_method}\n\n";
    
    echo "BILLING INFORMATION:\n";
    echo "Name: {$customer_name}\n";
    echo "Address:\n{$billing_address}\n";
    echo "Payment Method: {$payment_method}\n\n";
    
    echo "ORDER ITEMS:\n";
    echo "========================================\n";
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $item_name = $item->get_name();
        $quantity = $item->get_quantity();
        $item_total = $item->get_subtotal();
        
        echo "{$item_name}\n";
        echo "Quantity: {$quantity}\n";
        echo "Price: " . wc_price($item_total, array('currency' => $currency)) . "\n";
        
        // Show product meta data
        $meta_data = $item->get_formatted_meta_data('_', true);
        if (!empty($meta_data)) {
            foreach ($meta_data as $meta) {
                echo "{$meta['display_key']}: {$meta['display_value']}\n";
            }
        }
        echo "\n";
    }
    
    echo "ORDER SUMMARY:\n";
    echo "========================================\n";
    echo "Subtotal: " . wc_price($subtotal, array('currency' => $currency)) . "\n";
    if ($tax > 0) {
        echo "Tax: " . wc_price($tax, array('currency' => $currency)) . "\n";
    }
    if ($shipping_cost > 0) {
        echo "Shipping: " . wc_price($shipping_cost, array('currency' => $currency)) . "\n";
    }
    if ($discount > 0) {
        echo "Discount: -" . wc_price($discount, array('currency' => $currency)) . "\n";
    }
    echo "Total: " . wc_price($total, array('currency' => $currency)) . "\n\n";
    
    echo "PAYMENT INFORMATION:\n";
    echo "Method: {$payment_method}\n";
    if ($order->get_transaction_id()) {
        echo "Transaction ID: {$order->get_transaction_id()}\n";
    }
    echo "\n";
    
    echo "Thank you for choosing {$company_name}!\n";
    echo "If you have any questions, please contact us at:\n";
    echo "Email: {$company_email}\n";
    if ($company_phone) {
        echo "Phone: {$company_phone}\n";
    }
    echo "Website: {$company_website}\n";
    
} else {
    // HTML version
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Confirmation - <?php echo esc_html($order_number); ?></title>
        <style>
            /* Reset styles */
            body, table, td, p, a, li, blockquote {
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }
            table, td {
                mso-table-lspace: 0pt;
                mso-table-rspace: 0pt;
            }
            img {
                -ms-interpolation-mode: bicubic;
                border: 0;
                height: auto;
                line-height: 100%;
                outline: none;
                text-decoration: none;
            }
            
            /* Main styles */
            body {
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                background-color: #f4f4f4;
                font-family: Arial, sans-serif;
            }
            
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
            }
            
            .header {
                background-color: #1f2937;
                color: #ffffff;
                padding: 30px 20px;
                text-align: center;
            }
            
            .header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: bold;
            }
            
            .header p {
                margin: 10px 0 0 0;
                font-size: 16px;
                opacity: 0.9;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .order-info {
                background-color: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 30px;
            }
            
            .order-info h2 {
                margin: 0 0 15px 0;
                color: #1f2937;
                font-size: 20px;
            }
            
            .info-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #e2e8f0;
            }
            
            .info-row:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }
            
            .info-label {
                font-weight: bold;
                color: #374151;
            }
            
            .info-value {
                color: #6b7280;
            }
            
            .section {
                margin-bottom: 30px;
            }
            
            .section h3 {
                margin: 0 0 15px 0;
                color: #1f2937;
                font-size: 18px;
                border-bottom: 2px solid #3b82f6;
                padding-bottom: 5px;
            }
            
            .address-block {
                background-color: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                padding: 15px;
            }
            
            .address-block strong {
                display: block;
                margin-bottom: 5px;
                color: #1f2937;
            }
            
            .address-block p {
                margin: 0;
                color: #6b7280;
                line-height: 1.5;
            }
            
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            
            .items-table th {
                background-color: #f8fafc;
                color: #374151;
                font-weight: bold;
                padding: 12px;
                text-align: left;
                border: 1px solid #e2e8f0;
            }
            
            .items-table td {
                padding: 12px;
                border: 1px solid #e2e8f0;
                vertical-align: top;
            }
            
            .item-name {
                font-weight: bold;
                color: #1f2937;
                margin-bottom: 5px;
            }
            
            .item-details {
                color: #6b7280;
                font-size: 14px;
            }
            
            .quantity {
                text-align: center;
                font-weight: bold;
            }
            
            .price {
                text-align: right;
                font-weight: bold;
                color: #1f2937;
            }
            
            .pricing-summary {
                background-color: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                padding: 20px;
            }
            
            .pricing-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #e2e8f0;
            }
            
            .pricing-row:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
                font-weight: bold;
                font-size: 18px;
                color: #1f2937;
            }
            
            .pricing-label {
                color: #374151;
            }
            
            .pricing-value {
                color: #1f2937;
                font-weight: bold;
            }
            
            .footer {
                background-color: #1f2937;
                color: #ffffff;
                padding: 30px 20px;
                text-align: center;
            }
            
            .footer h3 {
                margin: 0 0 15px 0;
                font-size: 18px;
            }
            
            .footer p {
                margin: 0 0 10px 0;
                color: #d1d5db;
            }
            
            .footer a {
                color: #3b82f6;
                text-decoration: none;
            }
            
            .footer a:hover {
                text-decoration: underline;
            }
            
            .status-badge {
                display: inline-block;
                padding: 4px 12px;
                background-color: #10b981;
                color: #ffffff;
                border-radius: 20px;
                font-size: 12px;
                font-weight: bold;
                text-transform: uppercase;
            }
            
            /* Responsive styles */
            @media only screen and (max-width: 600px) {
                .email-container {
                    width: 100% !important;
                }
                
                .content {
                    padding: 20px 15px !important;
                }
                
                .header {
                    padding: 20px 15px !important;
                }
                
                .header h1 {
                    font-size: 24px !important;
                }
                
                .info-row {
                    flex-direction: column;
                }
                
                .info-label {
                    margin-bottom: 5px;
                }
                
                .items-table {
                    font-size: 14px;
                }
                
                .items-table th,
                .items-table td {
                    padding: 8px !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <!-- Header -->
            <div class="header">
                <h1>Order Confirmation</h1>
                <p>Thank you for your order!</p>
            </div>
            
            <!-- Content -->
            <div class="content">
                <!-- Order Information -->
                <div class="order-info">
                    <h2>Order Details</h2>
                    <div class="info-row">
                        <span class="info-label">Order Number:</span>
                        <span class="info-value"><?php echo esc_html($order_number); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Order Date:</span>
                        <span class="info-value"><?php echo esc_html($order_date); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="status-badge"><?php echo esc_html($order_status); ?></span>
                        </span>
                    </div>
                </div>
                
                <!-- Customer Information -->
                <div class="section">
                    <h3>Customer Information</h3>
                    <div class="address-block">
                        <strong><?php echo esc_html($customer_name); ?></strong>
                        <p><?php echo esc_html($customer_email); ?></p>
                        <?php if ($customer_phone): ?>
                            <p><?php echo esc_html($customer_phone); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Shipping Information -->
                <div class="section">
                    <h3>Shipping Information</h3>
                    <div class="address-block">
                        <strong><?php echo esc_html($shipping_name); ?></strong>
                        <p><?php echo wp_kses_post($shipping_address); ?></p>
                        <p><strong>Shipping Method:</strong> <?php echo esc_html($shipping_method); ?></p>
                    </div>
                </div>
                
                <!-- Billing Information -->
                <div class="section">
                    <h3>Billing Information</h3>
                    <div class="address-block">
                        <strong><?php echo esc_html($customer_name); ?></strong>
                        <p><?php echo wp_kses_post($billing_address); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo esc_html($payment_method); ?></p>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="section">
                    <h3>Order Items</h3>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="width: 80px;">Quantity</th>
                                <th style="width: 100px;">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order->get_items() as $item_id => $item): ?>
                            <tr>
                                <td>
                                    <div class="item-name"><?php echo esc_html($item->get_name()); ?></div>
                                    <?php 
                                    $meta_data = $item->get_formatted_meta_data('_', true);
                                    if (!empty($meta_data)): ?>
                                        <div class="item-details">
                                            <?php foreach ($meta_data as $meta): ?>
                                                <?php echo esc_html($meta['display_key'] . ': ' . $meta['display_value']); ?><br>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="quantity"><?php echo esc_html($item->get_quantity()); ?></td>
                                <td class="price"><?php echo wc_price($item->get_subtotal(), array('currency' => $currency)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pricing Summary -->
                <div class="section">
                    <h3>Order Summary</h3>
                    <div class="pricing-summary">
                        <div class="pricing-row">
                            <span class="pricing-label">Subtotal:</span>
                            <span class="pricing-value"><?php echo wc_price($subtotal, array('currency' => $currency)); ?></span>
                        </div>
                        <?php if ($tax > 0): ?>
                        <div class="pricing-row">
                            <span class="pricing-label">Tax:</span>
                            <span class="pricing-value"><?php echo wc_price($tax, array('currency' => $currency)); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($shipping_cost > 0): ?>
                        <div class="pricing-row">
                            <span class="pricing-label">Shipping:</span>
                            <span class="pricing-value"><?php echo wc_price($shipping_cost, array('currency' => $currency)); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($discount > 0): ?>
                        <div class="pricing-row">
                            <span class="pricing-label">Discount:</span>
                            <span class="pricing-value">-<?php echo wc_price($discount, array('currency' => $currency)); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="pricing-row">
                            <span class="pricing-label">Total:</span>
                            <span class="pricing-value"><?php echo wc_price($total, array('currency' => $currency)); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Information -->
                <div class="section">
                    <h3>Payment Information</h3>
                    <div class="address-block">
                        <p><strong>Payment Method:</strong> <?php echo esc_html($payment_method); ?></p>
                        <?php if ($order->get_transaction_id()): ?>
                            <p><strong>Transaction ID:</strong> <?php echo esc_html($order->get_transaction_id()); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <h3><?php echo esc_html($company_name); ?></h3>
                <p>Thank you for choosing us!</p>
                <p>
                    If you have any questions about your order, please contact us at<br>
                    <a href="mailto:<?php echo esc_attr($company_email); ?>"><?php echo esc_html($company_email); ?></a>
                    <?php if ($company_phone): ?>
                        or call <?php echo esc_html($company_phone); ?>
                    <?php endif; ?>
                </p>
                <p>
                    <a href="<?php echo esc_url($company_website); ?>"><?php echo esc_html($company_website); ?></a>
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>