# HSM Stripe Plugin Installation Guide

**Version:** 2.0.0  
**Last Updated:** 2025-01-28  
**Status:** Production Ready ✅

## Overview

This guide provides step-by-step instructions for installing and configuring the HSM Stripe Plugin on your WordPress site. The plugin requires specific WordPress plugins and server configurations to function properly.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation Methods](#installation-methods)
- [Plugin Configuration](#plugin-configuration)
- [WordPress Integration](#wordpress-integration)
- [SSL Configuration](#ssl-configuration)
- [Testing & Validation](#testing--validation)
- [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Server Requirements
- **WordPress:** 5.0 or higher (6.4+ recommended)
- **PHP:** 8.0 or higher (8.1+ recommended)
- **MySQL:** 5.7 or higher / MariaDB 10.3 or higher
- **Memory:** 256MB minimum (512MB recommended)
- **SSL Certificate:** Required for production

### Required WordPress Plugins
Before installing the HSM Stripe Plugin, ensure these plugins are installed and activated:

1. **WooCommerce** (7.1.0 or higher)
   - Download from: [WordPress.org](https://wordpress.org/plugins/woocommerce/)
   - Required for product management and order processing

2. **WPGraphQL** (1.15.0 or higher)
   - Download from: [WordPress.org](https://wordpress.org/plugins/wp-graphql/)
   - Required for GraphQL functionality

3. **WooCommerce GraphQL Extension** (0.15.0 or higher)
   - Download from: [WordPress.org](https://wordpress.org/plugins/wp-graphql-woocommerce/)
   - Required for WooCommerce GraphQL queries

### Optional Plugins
- **WP GraphQL CORS** - For advanced CORS configuration
- **Query Monitor** - For debugging and performance monitoring

---

## Installation Methods

### Method 1: WordPress Admin (Recommended)

1. **Download the Plugin**
   ```bash
   # Download from repository
   git clone https://github.com/your-repo/hsm-stripe-plugin.git
   # Or download ZIP file
   ```

2. **Upload to WordPress**
   - Log in to your WordPress admin dashboard
   - Navigate to **Plugins** → **Add New**
   - Click **Upload Plugin**
   - Choose the `hsm-stripe` ZIP file
   - Click **Install Now**

3. **Activate the Plugin**
   - After installation, click **Activate Plugin**
   - The plugin will automatically check for required dependencies

### Method 2: FTP Upload

1. **Prepare Plugin Files**
   ```bash
   # Extract plugin files
   unzip hsm-stripe.zip
   # Rename folder to hsm-stripe
   mv hsm-stripe-plugin hsm-stripe
   ```

2. **Upload via FTP**
   - Connect to your server via FTP/SFTP
   - Navigate to `/wp-content/plugins/`
   - Upload the `hsm-stripe` folder
   - Set proper file permissions (755 for folders, 644 for files)

3. **Activate in WordPress**
   - Log in to WordPress admin
   - Go to **Plugins** → **Installed Plugins**
   - Find "HSM Stripe Plugin" and click **Activate**

### Method 3: WP-CLI (Advanced)

```bash
# Install via WP-CLI
wp plugin install /path/to/hsm-stripe.zip --activate

# Or install from directory
wp plugin install /path/to/hsm-stripe --activate
```

---

## Plugin Configuration

### 1. Initial Setup

After activation, the plugin will automatically:
- Create necessary database tables
- Set up default options
- Check for required dependencies
- Configure basic settings

### 2. Stripe Configuration

1. **Access Plugin Settings**
   - Go to **HSM Stripe** in the WordPress admin menu
   - Click on **Settings** tab

2. **Configure Stripe Keys**
   ```
   Stripe Secret Key: sk_test_... (or sk_live_... for production)
   Stripe Publishable Key: pk_test_... (or pk_live_... for production)
   Webhook Secret: whsec_... (from Stripe dashboard)
   ```

3. **Test Mode Configuration**
   - Enable **Test Mode** for development
   - Use test keys from Stripe dashboard
   - Disable for production with live keys

### 3. API Configuration

1. **GraphQL Settings**
   - **Proxy Enabled:** ✅ (Recommended)
   - **Fallback Enabled:** ✅ (Recommended)
   - **CORS Headers:** Configure for your frontend domain

2. **REST API Settings**
   - **Rate Limiting:** 100 requests/minute (default)
   - **Authentication:** WordPress nonce (default)
   - **CORS:** Configure allowed origins

### 4. WooCommerce Integration

1. **Tax Settings**
   - Configure tax rates in WooCommerce
   - Set fallback tax rate (default: 13%)
   - Enable tax calculation in plugin

2. **Order Settings**
   - Set default order status: `pending-payment`
   - Configure order metadata fields
   - Enable Stripe payment intent tracking

---

## WordPress Integration

### 1. Database Setup

The plugin creates the following database tables:

```sql
-- HSM Stripe logs table
CREATE TABLE wp_hsm_stripe_logs (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    event_type varchar(50) NOT NULL,
    event_data longtext,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- HSM GraphQL cache table
CREATE TABLE wp_hsm_graphql_cache (
    cache_key varchar(255) NOT NULL,
    cache_data longtext,
    expires_at datetime NOT NULL,
    PRIMARY KEY (cache_key)
);
```

### 2. User Capabilities

The plugin adds the following capabilities:
- `manage_hsm_stripe` - Manage plugin settings
- `view_hsm_stripe_logs` - View plugin logs
- `manage_hsm_stripe_orders` - Manage orders

### 3. Admin Menu Integration

The plugin adds a dedicated admin menu:
- **HSM Stripe** (Main menu)
  - **Dashboard** - Overview and health status
  - **Settings** - Plugin configuration
  - **Orders** - Order management
  - **Logs** - System logs and debugging
  - **GraphQL Proxy** - GraphQL monitoring

---

## SSL Configuration

### 1. SSL Certificate Requirements

- **Valid SSL Certificate** required for production
- **HTTPS** must be enabled for all API endpoints
- **Mixed Content** issues must be resolved

### 2. SSL Configuration in Plugin

1. **Enable SSL Mode**
   ```php
   // In plugin settings
   define('HSM_STRIPE_SSL_MODE', 'strict');
   ```

2. **Configure SSL Headers**
   ```apache
   # In .htaccess
   Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
   Header always set X-Content-Type-Options nosniff
   Header always set X-Frame-Options DENY
   ```

3. **Test SSL Configuration**
   ```bash
   # Test SSL certificate
   openssl s_client -connect your-site.com:443 -servername your-site.com
   
   # Test API endpoints
   curl -I https://your-site.com/wp-json/hsm-stripe/v1/health
   ```

### 3. CORS Configuration

Configure CORS headers for frontend integration:

```php
// In HSM plugin CORS settings
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        $frontend_url = get_option('hsm_stripe_frontend_url', 'http://localhost:3000');
        header('Access-Control-Allow-Origin: ' . $frontend_url);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
        return $value;
    });
});
```

---

## Testing & Validation

### 1. Health Check

Test the plugin installation:

```bash
# Quick health check
curl https://your-site.com/wp-json/hsm-graphql/v1/health/quick

# Comprehensive health check
curl https://your-site.com/wp-json/hsm-graphql/v1/health
```

Expected response:
```json
{
  "status": "healthy",
  "dependencies_ok": true,
  "graphql_operational": true,
  "stripe_configured": true
}
```

### 2. API Endpoint Testing

Test all API endpoints:

```bash
# Test tax calculation
curl "https://your-site.com/wp-json/hsm-stripe/v1/tax/calculate?amount=1000"

# Test payment intent creation
curl -X POST https://your-site.com/wp-json/hsm-stripe/v1/payment/intent \
  -H "Content-Type: application/json" \
  -d '{"amount": 1000, "currency": "usd"}'

# Test GraphQL proxy
curl -X POST https://your-site.com/wp-json/hsm-graphql/v1/proxy \
  -H "Content-Type: application/json" \
  -d '{"query": "query { products { nodes { id name } } }"}'
```

### 3. WordPress Integration Testing

1. **Check Plugin Status**
   - Go to **Plugins** → **Installed Plugins**
   - Verify HSM Stripe Plugin is active
   - Check for any error messages

2. **Test Admin Interface**
   - Navigate to **HSM Stripe** → **Dashboard**
   - Verify all sections load correctly
   - Check health status indicators

3. **Test WooCommerce Integration**
   - Create a test product in WooCommerce
   - Verify product appears in GraphQL queries
   - Test order creation process

### 4. Frontend Integration Testing

1. **Configure Frontend**
   ```bash
   # In frontend .env.local
   HSM_GRAPHQL_PROXY_URL=https://your-site.com/wp-json/hsm-graphql/v1/proxy
   HSM_STRIPE_API_URL=https://your-site.com/wp-json/hsm-stripe/v1
   ```

2. **Test GraphQL Connection**
   ```javascript
   // In frontend console
   fetch('/wp-json/hsm-graphql/v1/health')
     .then(r => r.json())
     .then(console.log);
   ```

3. **Test Payment Flow**
   - Create a test order
   - Process payment with test Stripe keys
   - Verify order creation in WooCommerce

---

## Troubleshooting

### Common Installation Issues

#### 1. Plugin Activation Failed
**Symptoms:** Plugin fails to activate with error message
**Solutions:**
- Check PHP version (requires 8.0+)
- Verify WordPress version (requires 5.0+)
- Check for plugin conflicts
- Review error logs

#### 2. Missing Dependencies
**Symptoms:** Plugin activates but shows dependency warnings
**Solutions:**
- Install required plugins (WooCommerce, WPGraphQL)
- Activate all required plugins
- Check plugin versions
- Clear WordPress cache

#### 3. Database Errors
**Symptoms:** Database table creation fails
**Solutions:**
- Check database permissions
- Verify MySQL version
- Check for table name conflicts
- Review database error logs

#### 4. SSL Certificate Issues
**Symptoms:** API calls fail with SSL errors
**Solutions:**
- Verify SSL certificate validity
- Check certificate chain
- Update certificate if expired
- Test with different browsers

### Debug Mode

Enable debug mode for troubleshooting:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('HSM_STRIPE_DEBUG', true);
```

### Log Files

Check these log files for errors:
- **WordPress Debug Log:** `/wp-content/debug.log`
- **HSM Plugin Logs:** Admin → HSM Stripe → Logs
- **Server Error Log:** Check with your hosting provider
- **Stripe Logs:** Stripe Dashboard → Logs

### Support Resources

- **Plugin Documentation:** [README.md](README.md)
- **API Reference:** [API_REFERENCE.md](API_REFERENCE.md)
- **Health Check:** `/wp-json/hsm-graphql/v1/health`
- **WordPress Support:** [WordPress.org Support](https://wordpress.org/support/)

---

## Next Steps

After successful installation:

1. **Configure Frontend Integration**
   - Set up frontend environment variables
   - Test GraphQL proxy connection
   - Configure payment processing

2. **Set Up Monitoring**
   - Enable health monitoring
   - Configure alert notifications
   - Set up performance tracking

3. **Production Deployment**
   - Switch to live Stripe keys
   - Configure production SSL
   - Set up backup procedures

4. **User Training**
   - Train admin users on plugin features
   - Document custom configurations
   - Set up support procedures

---

**Last Updated:** 2025-01-28  
**Version:** 2.0.0  
**Status:** Production Ready ✅