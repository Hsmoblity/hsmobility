# HSM Stripe Plugin API Reference

**Version:** 2.0.0  
**Last Updated:** 2025-01-28  
**Status:** Production Ready ✅

## Overview

The HSM Stripe Plugin provides a comprehensive API for e-commerce functionality including payment processing, GraphQL proxy services, and WooCommerce integration. This document covers all available endpoints, request/response formats, and integration examples.

## Table of Contents

- [REST API Endpoints](#rest-api-endpoints)
- [GraphQL Proxy API](#graphql-proxy-api)
- [Health Monitoring API](#health-monitoring-api)
- [Webhook API](#webhook-api)
- [Authentication](#authentication)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)
- [Examples](#examples)

---

## REST API Endpoints

### Base URL
```
https://your-wordpress-site.com/wp-json/hsm-stripe/v1
```

### 1. Tax Calculation

**Endpoint:** `GET /tax/calculate`

**Description:** Calculates tax totals using WooCommerce tax rates with fallback to 13% rate.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `amount` | number | Yes | Base amount for tax calculation |
| `country` | string | No | Country code (default: US) |
| `state` | string | No | State/province code |
| `postal_code` | string | No | Postal/ZIP code |

**Example Request:**
```bash
GET /wp-json/hsm-stripe/v1/tax/calculate?amount=1000&country=US&state=CA&postal_code=90210
```

**Response:**
```json
{
  "success": true,
  "data": {
    "base_amount": 1000,
    "tax_amount": 130,
    "total_amount": 1130,
    "tax_rate": 0.13,
    "tax_source": "woocommerce",
    "breakdown": {
      "federal_tax": 100,
      "state_tax": 30
    }
  }
}
```

### 2. Payment Intent Creation

**Endpoint:** `POST /payment/intent`

**Description:** Creates a Stripe PaymentIntent with mapped customer metadata.

**Request Body:**
```json
{
  "amount": 1130,
  "currency": "usd",
  "customer_data": {
    "email": "customer@example.com",
    "name": "John Doe",
    "phone": "+1234567890"
  },
  "metadata": {
    "order_id": "order_123",
    "product_ids": [1, 2, 3],
    "configuration_id": "config_456"
  },
  "description": "HSMobility Product Configuration"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "client_secret": "pi_1234567890_secret_abcdef",
    "payment_intent_id": "pi_1234567890",
    "amount": 1130,
    "currency": "usd",
    "status": "requires_payment_method"
  }
}
```

### 3. Order Creation

**Endpoint:** `POST /orders/create`

**Description:** Creates a WooCommerce order from configurator cart data.

**Request Body:**
```json
{
  "customer_data": {
    "email": "customer@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1234567890"
  },
  "billing_address": {
    "address_1": "123 Main St",
    "city": "Los Angeles",
    "state": "CA",
    "postcode": "90210",
    "country": "US"
  },
  "shipping_address": {
    "address_1": "123 Main St",
    "city": "Los Angeles",
    "state": "CA",
    "postcode": "90210",
    "country": "US"
  },
  "line_items": [
    {
      "product_id": 123,
      "quantity": 1,
      "price": 1000,
      "options": [
        {
          "name": "Color",
          "value": "Red"
        },
        {
          "name": "Size",
          "value": "Large"
        }
      ]
    }
  ],
  "payment_intent_id": "pi_1234567890",
  "total": 1130,
  "tax_total": 130
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "order_id": 456,
    "order_number": "HSM-456",
    "status": "pending-payment",
    "total": "1130.00",
    "payment_url": "https://your-site.com/checkout/order-pay/456/",
    "stripe_payment_intent": "pi_1234567890"
  }
}
```

---

## GraphQL Proxy API

### Base URL
```
https://your-wordpress-site.com/wp-json/hsm-graphql/v1
```

### 1. GraphQL Proxy

**Endpoint:** `POST /proxy`

**Description:** Routes all frontend GraphQL requests through HSM plugin for enhanced security and logging.

**Request Body:**
```json
{
  "query": "query GetProducts { products { nodes { id name price } } }",
  "variables": {},
  "operationName": "GetProducts"
}
```

**Response:**
```json
{
  "data": {
    "products": {
      "nodes": [
        {
          "id": "1",
          "name": "Product Name",
          "price": "99.99"
        }
      ]
    }
  },
  "extensions": {
    "hsm_metadata": {
      "request_id": "req_123456",
      "processing_time": 45,
      "cache_hit": false
    }
  }
}
```

### 2. GraphQL Operations

**Endpoint:** `POST /operation/{operation_name}`

**Description:** Execute specific GraphQL operations with optimized handling.

**Available Operations:**
- `get_all_products` - Retrieve all products
- `get_product_by_slug` - Get product by slug
- `get_cart` - Get current cart
- `add_to_cart` - Add item to cart
- `create_headless_order` - Create order without checkout

**Example Request:**
```bash
POST /wp-json/hsm-graphql/v1/operation/get_all_products
```

**Response:**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "name": "Product Name",
        "slug": "product-name",
        "price": "99.99",
        "description": "Product description"
      }
    ]
  }
}
```

---

## Health Monitoring API

### 1. Comprehensive Health Check

**Endpoint:** `GET /health`

**Description:** Complete system health status with detailed diagnostics.

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2025-01-28T10:30:00Z",
  "version": "2.0.0",
  "dependencies": {
    "wordpress": {
      "version": "6.4",
      "status": "active"
    },
    "woocommerce": {
      "version": "8.5.0",
      "status": "active"
    },
    "wpgraphql": {
      "version": "1.15.0",
      "status": "active"
    },
    "woocommerce_graphql": {
      "version": "0.15.0",
      "status": "active"
    }
  },
  "graphql": {
    "proxy_status": "operational",
    "endpoint_accessible": true,
    "response_time": 45
  },
  "stripe": {
    "api_accessible": true,
    "webhook_configured": true,
    "test_mode": true
  },
  "performance": {
    "memory_usage": "45MB",
    "response_time": 120,
    "cache_status": "enabled"
  }
}
```

### 2. Quick Health Check

**Endpoint:** `GET /health/quick`

**Description:** Lightweight health check for basic status.

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2025-01-28T10:30:00Z",
  "dependencies_ok": true,
  "graphql_operational": true
}
```

### 3. Performance Metrics

**Endpoint:** `GET /health/metrics`

**Description:** Detailed performance metrics and monitoring data.

**Response:**
```json
{
  "timestamp": "2025-01-28T10:30:00Z",
  "metrics": {
    "response_times": {
      "average": 120,
      "p95": 200,
      "p99": 300
    },
    "error_rates": {
      "last_hour": 0.01,
      "last_24h": 0.005
    },
    "throughput": {
      "requests_per_minute": 45,
      "peak_requests_per_minute": 120
    },
    "memory": {
      "current_usage": "45MB",
      "peak_usage": "60MB",
      "limit": "256MB"
    }
  }
}
```

---

## Webhook API

### Stripe Webhooks

**Endpoint:** `POST /webhooks/stripe`

**Description:** Handles Stripe webhook events for payment processing.

**Supported Events:**
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `payment_intent.canceled`
- `checkout.session.completed`

**Example Webhook Payload:**
```json
{
  "id": "evt_1234567890",
  "object": "event",
  "type": "payment_intent.succeeded",
  "data": {
    "object": {
      "id": "pi_1234567890",
      "amount": 1130,
      "currency": "usd",
      "status": "succeeded"
    }
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Webhook processed successfully",
  "order_updated": true,
  "order_id": 456
}
```

---

## Authentication

### API Key Authentication

All API requests require authentication via WordPress nonce or API key.

**Headers:**
```http
X-WP-Nonce: your_nonce_here
Content-Type: application/json
```

**For external applications:**
```http
Authorization: Bearer your_api_key_here
Content-Type: application/json
```

---

## Error Handling

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "INVALID_REQUEST",
    "message": "Invalid request parameters",
    "details": {
      "field": "amount",
      "issue": "Must be a positive number"
    }
  },
  "request_id": "req_123456"
}
```

### Common Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `INVALID_REQUEST` | 400 | Invalid request parameters |
| `UNAUTHORIZED` | 401 | Authentication required |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `NOT_FOUND` | 404 | Resource not found |
| `RATE_LIMITED` | 429 | Rate limit exceeded |
| `SERVER_ERROR` | 500 | Internal server error |
| `STRIPE_ERROR` | 502 | Stripe API error |
| `WOOCOMMERCE_ERROR` | 502 | WooCommerce error |

---

## Rate Limiting

### Limits
- **REST API:** 100 requests per minute per IP
- **GraphQL Proxy:** 200 requests per minute per IP
- **Health Checks:** 60 requests per minute per IP

### Headers
```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640995200
```

---

## Examples

### Complete Payment Flow

```javascript
// 1. Calculate tax
const taxResponse = await fetch('/wp-json/hsm-stripe/v1/tax/calculate?amount=1000');
const taxData = await taxResponse.json();

// 2. Create payment intent
const paymentResponse = await fetch('/wp-json/hsm-stripe/v1/payment/intent', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': nonce
  },
  body: JSON.stringify({
    amount: taxData.data.total_amount,
    currency: 'usd',
    customer_data: {
      email: 'customer@example.com',
      name: 'John Doe'
    }
  })
});
const paymentData = await paymentResponse.json();

// 3. Process payment with Stripe.js
const stripe = Stripe('pk_test_...');
const result = await stripe.confirmCardPayment(paymentData.data.client_secret, {
  payment_method: {
    card: cardElement
  }
});

// 4. Create order
if (result.paymentIntent.status === 'succeeded') {
  const orderResponse = await fetch('/wp-json/hsm-stripe/v1/orders/create', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce
    },
    body: JSON.stringify({
      customer_data: customerData,
      line_items: cartItems,
      payment_intent_id: result.paymentIntent.id,
      total: taxData.data.total_amount
    })
  });
}
```

### GraphQL Integration

```javascript
// Using HSM GraphQL Client
import { HSMGraphQLClient } from './lib/graphql/hsm-graphql-client';

const client = new HSMGraphQLClient();

// Get all products
const products = await client.getAllProducts();

// Get product by slug
const product = await client.getProductBySlug('product-slug');

// Add to cart
const cartResult = await client.addConfigurationToCart({
  productId: 123,
  options: selectedOptions,
  quantity: 1
});
```

---

## Support

For API support and questions:
- **Documentation:** [Plugin Documentation](README.md)
- **Issues:** Create an issue in the project repository
- **Health Check:** Use `/wp-json/hsm-graphql/v1/health` for system status

---

**Last Updated:** 2025-01-28  
**Version:** 2.0.0  
**Status:** Production Ready ✅