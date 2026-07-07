# HSM Stripe Plugin Troubleshooting Guide

**Version:** 2.0.0  
**Last Updated:** 2025-01-28  
**Status:** Production Ready ✅

## Overview

This comprehensive troubleshooting guide helps diagnose and resolve common issues with the HSM Stripe Plugin. It covers installation problems, configuration issues, API errors, and integration problems.

## Table of Contents

- [Quick Diagnostics](#quick-diagnostics)
- [Installation Issues](#installation-issues)
- [Configuration Problems](#configuration-problems)
- [API Errors](#api-errors)
- [GraphQL Issues](#graphql-issues)
- [Payment Processing Problems](#payment-processing-problems)
- [Performance Issues](#performance-issues)
- [Integration Problems](#integration-problems)
- [Debug Tools](#debug-tools)
- [Log Analysis](#log-analysis)
- [Common Solutions](#common-solutions)

---

## Quick Diagnostics

### Health Check Commands

Run these commands to quickly diagnose common issues:

```bash
# 1. Check plugin health
curl https://your-site.com/wp-json/hsm-graphql/v1/health/quick

# 2. Check comprehensive health
curl https://your-site.com/wp-json/hsm-graphql/v1/health

# 3. Test GraphQL proxy
curl -X POST https://your-site.com/wp-json/hsm-graphql/v1/proxy \
  -H "Content-Type: application/json" \
  -d '{"query": "query { products { nodes { id name } } }"}'

# 4. Test tax calculation
curl "https://your-site.com/wp-json/hsm-stripe/v1/tax/calculate?amount=1000"

# 5. Test payment intent
curl -X POST https://your-site.com/wp-json/hsm-stripe/v1/payment/intent \
  -H "Content-Type: application/json" \
  -d '{"amount": 1000, "currency": "usd"}'
```

### WordPress Admin Checks

1. **Plugin Status**
   - Go to **Plugins** → **Installed Plugins**
   - Verify HSM Stripe Plugin is active
   - Check for any error messages

2. **Dependencies**
   - Go to **HSM Stripe** → **Dashboard**
   - Check dependency status
   - Verify all required plugins are active

3. **Settings**
   - Go to **HSM Stripe** → **Settings**
   - Verify Stripe keys are configured
   - Check API endpoint URLs

---

## Installation Issues

### 1. Plugin Activation Failed

**Symptoms:**
- Plugin fails to activate
- Error message appears during activation
- Plugin appears in "Must Use" plugins

**Diagnosis:**
```bash
# Check WordPress error logs
tail -f /path/to/wp-content/debug.log

# Check PHP error logs
tail -f /var/log/php_errors.log

# Check plugin file permissions
ls -la /wp-content/plugins/hsm-stripe/
```

**Common Causes:**
- PHP version too old (requires 8.0+)
- WordPress version too old (requires 5.0+)
- Plugin file corruption
- Insufficient file permissions
- Memory limit too low

**Solutions:**
```bash
# 1. Update PHP version
# Contact hosting provider to upgrade to PHP 8.0+

# 2. Update WordPress
# Go to Dashboard → Updates → Update WordPress

# 3. Reinstall plugin
# Delete plugin folder and re-upload

# 4. Fix permissions
chmod -R 755 /wp-content/plugins/hsm-stripe/
chown -R www-data:www-data /wp-content/plugins/hsm-stripe/

# 5. Increase memory limit
# Add to wp-config.php:
ini_set('memory_limit', '512M');
```

### 2. Missing Dependencies

**Symptoms:**
- Plugin activates but shows warnings
- Features not working
- Error messages about missing plugins

**Diagnosis:**
```bash
# Check installed plugins
wp plugin list --status=active

# Check specific plugins
wp plugin is-active woocommerce
wp plugin is-active wp-graphql
wp plugin is-active wp-graphql-woocommerce
```

**Solutions:**
```bash
# Install WooCommerce
wp plugin install woocommerce --activate

# Install WPGraphQL
wp plugin install wp-graphql --activate

# Install WooCommerce GraphQL
wp plugin install wp-graphql-woocommerce --activate

# Or install all at once
wp plugin install woocommerce wp-graphql wp-graphql-woocommerce --activate
```

### 3. Database Errors

**Symptoms:**
- Database table creation fails
- Plugin settings not saving
- Data not persisting

**Diagnosis:**
```bash
# Check database connection
wp db check

# Check table creation
wp db query "SHOW TABLES LIKE 'wp_hsm_%'"

# Check database permissions
wp db query "SHOW GRANTS FOR CURRENT_USER()"
```

**Solutions:**
```bash
# 1. Fix database permissions
GRANT ALL PRIVILEGES ON database_name.* TO 'username'@'localhost';
FLUSH PRIVILEGES;

# 2. Recreate tables
wp plugin deactivate hsm-stripe
wp plugin activate hsm-stripe

# 3. Manual table creation
wp db query "CREATE TABLE wp_hsm_stripe_logs (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    event_type varchar(50) NOT NULL,
    event_data longtext,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);"
```

---

## Configuration Problems

### 1. Stripe Keys Not Working

**Symptoms:**
- Payment intents fail to create
- Stripe API errors
- "Invalid API key" errors

**Diagnosis:**
```bash
# Test Stripe API key
curl -u sk_test_your_key: https://api.stripe.com/v1/charges

# Check plugin settings
wp option get hsm_stripe_secret_key
wp option get hsm_stripe_publishable_key
```

**Solutions:**
```bash
# 1. Verify API keys in Stripe Dashboard
# Go to https://dashboard.stripe.com/apikeys

# 2. Update keys in WordPress
wp option update hsm_stripe_secret_key 'sk_test_new_key'
wp option update hsm_stripe_publishable_key 'pk_test_new_key'

# 3. Clear plugin cache
wp cache flush
```

### 2. CORS Configuration Issues

**Symptoms:**
- Frontend can't connect to API
- CORS errors in browser console
- "Access-Control-Allow-Origin" errors

**Diagnosis:**
```bash
# Test CORS headers
curl -H "Origin: https://your-frontend.com" \
     -H "Access-Control-Request-Method: POST" \
     -H "Access-Control-Request-Headers: X-Requested-With" \
     -X OPTIONS \
     https://your-site.com/wp-json/hsm-stripe/v1/health
```

**Solutions:**
```php
// Add to functions.php or plugin file
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

### 3. SSL Certificate Issues

**Symptoms:**
- SSL verification errors
- "Certificate verify failed" errors
- API calls failing with SSL errors

**Diagnosis:**
```bash
# Check SSL certificate
openssl s_client -connect your-site.com:443 -servername your-site.com

# Test SSL from PHP
php -r "echo file_get_contents('https://your-site.com/wp-json/hsm-graphql/v1/health');"
```

**Solutions:**
```bash
# 1. Update SSL certificate
# Contact hosting provider or use Let's Encrypt

# 2. Configure SSL in plugin
# Add to wp-config.php:
define('HSM_STRIPE_SSL_VERIFY', false); // Only for testing

# 3. Update cURL settings
# Add to wp-config.php:
ini_set('curl.cainfo', '/path/to/cacert.pem');
```

---

## API Errors

### 1. 404 Not Found Errors

**Symptoms:**
- API endpoints return 404
- "Endpoint not found" errors
- Frontend can't connect to API

**Diagnosis:**
```bash
# Check if endpoints exist
curl -I https://your-site.com/wp-json/hsm-stripe/v1/health
curl -I https://your-site.com/wp-json/hsm-graphql/v1/health

# Check WordPress permalinks
wp option get permalink_structure
```

**Solutions:**
```bash
# 1. Flush rewrite rules
wp rewrite flush

# 2. Check permalink structure
wp option update permalink_structure '/%postname%/'

# 3. Verify plugin activation
wp plugin is-active hsm-stripe
```

### 2. 500 Internal Server Errors

**Symptoms:**
- API returns 500 errors
- Server error logs show PHP errors
- Plugin functionality not working

**Diagnosis:**
```bash
# Check error logs
tail -f /path/to/wp-content/debug.log
tail -f /var/log/apache2/error.log

# Enable WordPress debug
# Add to wp-config.php:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

**Solutions:**
```bash
# 1. Fix PHP errors
# Check error logs and fix code issues

# 2. Increase memory limit
ini_set('memory_limit', '512M');

# 3. Check file permissions
chmod -R 755 /wp-content/plugins/hsm-stripe/
```

### 3. Authentication Errors

**Symptoms:**
- "Unauthorized" errors
- "Invalid nonce" errors
- API calls rejected

**Diagnosis:**
```bash
# Test with nonce
curl -H "X-WP-Nonce: your_nonce" \
     https://your-site.com/wp-json/hsm-stripe/v1/health

# Check nonce generation
wp eval "echo wp_create_nonce('wp_rest');"
```

**Solutions:**
```bash
# 1. Generate new nonce
wp eval "echo wp_create_nonce('wp_rest');"

# 2. Check user permissions
wp user meta get 1 capabilities

# 3. Verify API authentication
# Check if user has proper capabilities
```

---

## GraphQL Issues

### 1. GraphQL Proxy Not Working

**Symptoms:**
- GraphQL queries fail
- "GraphQL endpoint not found" errors
- Frontend can't query data

**Diagnosis:**
```bash
# Test GraphQL proxy
curl -X POST https://your-site.com/wp-json/hsm-graphql/v1/proxy \
  -H "Content-Type: application/json" \
  -d '{"query": "query { products { nodes { id name } } }"}'

# Test direct GraphQL
curl -X POST https://your-site.com/graphql \
  -H "Content-Type: application/json" \
  -d '{"query": "query { products { nodes { id name } } }"}'
```

**Solutions:**
```bash
# 1. Check WPGraphQL plugin
wp plugin is-active wp-graphql

# 2. Check GraphQL schema
curl https://your-site.com/wp-json/hsm-graphql/v1/schema

# 3. Verify plugin configuration
wp option get hsm_graphql_proxy_enabled
```

### 2. GraphQL Schema Errors

**Symptoms:**
- Schema validation errors
- "Unknown field" errors
- GraphQL introspection fails

**Diagnosis:**
```bash
# Test schema introspection
curl -X POST https://your-site.com/wp-json/hsm-graphql/v1/proxy \
  -H "Content-Type: application/json" \
  -d '{"query": "query IntrospectionQuery { __schema { types { name } } }"}'
```

**Solutions:**
```bash
# 1. Update WPGraphQL plugins
wp plugin update wp-graphql wp-graphql-woocommerce

# 2. Clear GraphQL cache
wp cache flush

# 3. Regenerate schema
wp eval "do_action('graphql_register_schema');"
```

---

## Payment Processing Problems

### 1. Payment Intent Creation Fails

**Symptoms:**
- Payment intents not created
- Stripe API errors
- "Invalid request" errors

**Diagnosis:**
```bash
# Test payment intent creation
curl -X POST https://your-site.com/wp-json/hsm-stripe/v1/payment/intent \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: your_nonce" \
  -d '{"amount": 1000, "currency": "usd"}'
```

**Solutions:**
```bash
# 1. Check Stripe API key
wp option get hsm_stripe_secret_key

# 2. Verify Stripe account status
# Check Stripe Dashboard for account issues

# 3. Test with Stripe CLI
stripe listen --forward-to https://your-site.com/wp-json/hsm-stripe/v1/webhooks/stripe
```

### 2. Webhook Processing Issues

**Symptoms:**
- Webhooks not received
- Orders not updating
- Payment status not syncing

**Diagnosis:**
```bash
# Check webhook endpoint
curl -X POST https://your-site.com/wp-json/hsm-stripe/v1/webhooks/stripe \
  -H "Content-Type: application/json" \
  -d '{"test": "webhook"}'

# Check webhook logs
wp db query "SELECT * FROM wp_hsm_stripe_logs WHERE event_type = 'webhook' ORDER BY created_at DESC LIMIT 10"
```

**Solutions:**
```bash
# 1. Verify webhook URL in Stripe Dashboard
# https://dashboard.stripe.com/webhooks

# 2. Check webhook secret
wp option get hsm_stripe_webhook_secret

# 3. Test webhook with Stripe CLI
stripe trigger payment_intent.succeeded
```

---

## Performance Issues

### 1. Slow API Responses

**Symptoms:**
- API calls taking too long
- Timeout errors
- Poor user experience

**Diagnosis:**
```bash
# Test response times
time curl https://your-site.com/wp-json/hsm-graphql/v1/health

# Check server resources
top
htop
```

**Solutions:**
```bash
# 1. Enable caching
wp option update hsm_graphql_cache_enabled true

# 2. Optimize database
wp db optimize

# 3. Increase server resources
# Contact hosting provider
```

### 2. Memory Issues

**Symptoms:**
- "Memory exhausted" errors
- Plugin crashes
- Server instability

**Diagnosis:**
```bash
# Check memory usage
php -r "echo ini_get('memory_limit');"

# Check current usage
wp eval "echo memory_get_usage(true);"
```

**Solutions:**
```bash
# 1. Increase memory limit
ini_set('memory_limit', '512M');

# 2. Optimize queries
# Review and optimize database queries

# 3. Enable object caching
wp plugin install redis-cache --activate
```

---

## Integration Problems

### 1. Frontend Connection Issues

**Symptoms:**
- Frontend can't connect to API
- CORS errors
- Network errors

**Diagnosis:**
```bash
# Test from frontend domain
curl -H "Origin: https://your-frontend.com" \
     https://your-site.com/wp-json/hsm-graphql/v1/health

# Check CORS headers
curl -I -H "Origin: https://your-frontend.com" \
     https://your-site.com/wp-json/hsm-graphql/v1/health
```

**Solutions:**
```bash
# 1. Update CORS configuration
wp option update hsm_stripe_frontend_url 'https://your-frontend.com'

# 2. Check SSL certificates
# Ensure both sites have valid SSL

# 3. Verify network connectivity
ping your-frontend.com
```

### 2. WooCommerce Integration Issues

**Symptoms:**
- Orders not creating
- Product data not syncing
- Tax calculation errors

**Diagnosis:**
```bash
# Check WooCommerce status
wp option get woocommerce_status

# Test product queries
curl -X POST https://your-site.com/wp-json/hsm-graphql/v1/proxy \
  -H "Content-Type: application/json" \
  -d '{"query": "query { products { nodes { id name } } }"}'
```

**Solutions:**
```bash
# 1. Verify WooCommerce plugin
wp plugin is-active woocommerce

# 2. Check WooCommerce settings
wp option get woocommerce_currency
wp option get woocommerce_tax_status

# 3. Test order creation
wp eval "wc_create_order(['status' => 'pending']);"
```

---

## Debug Tools

### 1. Health Check Endpoints

```bash
# Quick health check
curl https://your-site.com/wp-json/hsm-graphql/v1/health/quick

# Comprehensive health check
curl https://your-site.com/wp-json/hsm-graphql/v1/health

# Performance metrics
curl https://your-site.com/wp-json/hsm-graphql/v1/health/metrics
```

### 2. WordPress CLI Commands

```bash
# Check plugin status
wp plugin list --status=active

# Check plugin options
wp option list --search=hsm_stripe

# Check database tables
wp db query "SHOW TABLES LIKE 'wp_hsm_%'"

# Clear caches
wp cache flush
wp rewrite flush
```

### 3. Log Analysis

```bash
# WordPress debug log
tail -f /path/to/wp-content/debug.log

# Plugin specific logs
wp db query "SELECT * FROM wp_hsm_stripe_logs ORDER BY created_at DESC LIMIT 20"

# Server error logs
tail -f /var/log/apache2/error.log
tail -f /var/log/nginx/error.log
```

---

## Log Analysis

### 1. WordPress Debug Log

Enable debug logging in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Common log entries:
```
[28-Jan-2025 10:30:00] HSM Stripe: Plugin activated
[28-Jan-2025 10:30:01] HSM Stripe: Dependencies checked
[28-Jan-2025 10:30:02] HSM Stripe: Database tables created
```

### 2. Plugin Logs

Check plugin-specific logs:
```sql
-- Recent plugin events
SELECT * FROM wp_hsm_stripe_logs 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at DESC;

-- Error events only
SELECT * FROM wp_hsm_stripe_logs 
WHERE event_type LIKE '%error%'
ORDER BY created_at DESC;
```

### 3. API Request Logs

Monitor API requests:
```bash
# Check recent API calls
wp db query "SELECT event_type, created_at FROM wp_hsm_stripe_logs WHERE event_type LIKE '%api%' ORDER BY created_at DESC LIMIT 10"

# Check error rates
wp db query "SELECT COUNT(*) as error_count FROM wp_hsm_stripe_logs WHERE event_type LIKE '%error%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
```

---

## Common Solutions

### 1. Complete Reset

If all else fails, perform a complete reset:

```bash
# 1. Deactivate plugin
wp plugin deactivate hsm-stripe

# 2. Clear all plugin data
wp option delete hsm_stripe_%
wp db query "DROP TABLE IF EXISTS wp_hsm_stripe_logs"
wp db query "DROP TABLE IF EXISTS wp_hsm_graphql_cache"

# 3. Clear all caches
wp cache flush
wp rewrite flush

# 4. Reactivate plugin
wp plugin activate hsm-stripe

# 5. Reconfigure settings
wp option update hsm_stripe_secret_key 'sk_test_new_key'
wp option update hsm_stripe_publishable_key 'pk_test_new_key'
```

### 2. Plugin Update

Update to latest version:

```bash
# 1. Backup current installation
cp -r /wp-content/plugins/hsm-stripe /backup/hsm-stripe-backup

# 2. Download latest version
wp plugin install hsm-stripe --force

# 3. Activate updated version
wp plugin activate hsm-stripe

# 4. Run database updates
wp eval "do_action('hsm_stripe_updated');"
```

### 3. Environment Fix

Fix environment issues:

```bash
# 1. Check PHP version
php --version

# 2. Check WordPress version
wp core version

# 3. Check plugin versions
wp plugin list --status=active

# 4. Update everything
wp core update
wp plugin update --all
```

---

## Support Resources

### Getting Help

1. **Check Documentation**
   - [API Reference](API_REFERENCE.md)
   - [Installation Guide](INSTALLATION_GUIDE.md)

2. **Health Check**
   - Use `/wp-json/hsm-graphql/v1/health` for system status

3. **Log Analysis**
   - Check WordPress debug logs
   - Review plugin-specific logs

4. **Community Support**
   - Create an issue in the project repository
   - Check existing issues and solutions

### Emergency Contacts

- **Critical Issues:** Create urgent issue in repository
- **Hosting Issues:** Contact hosting provider
- **Stripe Issues:** Contact Stripe support
- **WordPress Issues:** WordPress.org support forums

---

**Last Updated:** 2025-01-28  
**Version:** 2.0.0  
**Status:** Production Ready ✅