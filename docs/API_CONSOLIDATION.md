# CMS Plugin API Consolidation Documentation

## Overview

This document describes the consolidated API namespace structure for the HSM CMS Plugin. The API routes have been reorganized to eliminate duplicates and provide clear separation of concerns.

**Date:** January 16, 2025  
**Agent:** Fullstack  
**Task:** API Namespace Consolidation

---

## Consolidated API Structure

### 1. `hsm/v1` - General HSM API

**Purpose:** General HSM plugin endpoints (non-Stripe, non-GraphQL)

**Base URL:** `/wp-json/hsm/v1/`

**Routes:**
- `GET /health` - Health check endpoint (public)
- `GET /settings` - Get plugin settings (admin)
- `POST /settings` - Update plugin settings (admin)
- `GET /system-status` - System status information (protected)
- `GET /test` - Test endpoint (protected)
- `GET /api-key` - Generate API key (public)
- `POST /stripe-webhook` - Stripe webhook handler (public)
- `POST /orders/callback` - Order callback handler (protected)
- `GET /orders/{id}/status` - Get order status (protected)
- `POST /submit-consultation` - Submit consultation form (public)
- `GET /consult-form-url` - Get consultation form URL (public)
- `GET /contact-form-url` - Get contact form URL (public)

**Registration File:** `includes/api/class-rest-api.php`

---

### 2. `hsm-stripe/v1` - Stripe Payment Operations

**Purpose:** ALL Stripe payment-related operations

**Base URL:** `/wp-json/hsm-stripe/v1/`

**Routes:**
- `GET /tax/calculate` - Calculate tax (public)
- `POST /payment/intent` - Create payment intent (public)
- `POST /orders/create` - Create WooCommerce order (public)
- `POST /orders/update-status` - Update order status (public)

**Registration File:** `includes/api/class-api-manager.php`

**Note:** These are the PRIMARY endpoints for Stripe operations. The duplicate routes in `hsm/v1` have been deprecated.

---

### 3. `hsm-graphql/v1` - GraphQL Proxy Operations

**Purpose:** ALL GraphQL proxy operations

**Base URL:** `/wp-json/hsm-graphql/v1/`

**Routes:**
- `POST /proxy` - Main GraphQL proxy endpoint (protected)
- `POST /operation/{operation}` - Specific GraphQL operation (protected)
- `GET /schema` - Get GraphQL schema (protected)
- `GET /status` - GraphQL status (protected)
- `GET /nonce` - Get nonce for authentication (public)
- `GET /health` - Health check (protected)
- `GET /health/quick` - Quick health check (protected)
- `GET /health/metrics` - Health metrics (protected)

**Registration Files:**
- `includes/api/class-graphql-proxy-api.php` (main routes)
- `includes/api/class-graphql-healthcheck-api.php` (health routes)

**Note:** These are the PRIMARY endpoints for GraphQL operations. The `/wp-json/hsm/v1/graphql` endpoint has been deprecated.

---

## Deprecated Endpoints

The following endpoints in `hsm/v1` namespace have been deprecated and return HTTP 301 redirects:

1. **POST /wp-json/hsm/v1/tax/calculate**
   - **New Endpoint:** GET /wp-json/hsm-stripe/v1/tax/calculate
   - **Reason:** Consolidated to Stripe namespace

2. **POST /wp-json/hsm/v1/payment/intent**
   - **New Endpoint:** POST /wp-json/hsm-stripe/v1/payment/intent
   - **Reason:** Consolidated to Stripe namespace

3. **POST /wp-json/hsm/v1/orders/create**
   - **New Endpoint:** POST /wp-json/hsm-stripe/v1/orders/create
   - **Reason:** Consolidated to Stripe namespace

4. **POST /wp-json/hsm/v1/graphql**
   - **New Endpoint:** POST /wp-json/hsm-graphql/v1/proxy
   - **Reason:** Consolidated to GraphQL namespace

---

## Migration Guide

### For Frontend Developers

#### Stripe Operations

**Before:**
```javascript
// Old endpoint (deprecated)
POST /wp-json/hsm/v1/tax/calculate
POST /wp-json/hsm/v1/payment/intent
POST /wp-json/hsm/v1/orders/create
```

**After:**
```javascript
// New endpoints (use these)
GET /wp-json/hsm-stripe/v1/tax/calculate
POST /wp-json/hsm-stripe/v1/payment/intent
POST /wp-json/hsm-stripe/v1/orders/create
```

#### GraphQL Operations

**Before:**
```javascript
// Old endpoint (deprecated)
POST /wp-json/hsm/v1/graphql
```

**After:**
```javascript
// New endpoint (use this)
POST /wp-json/hsm-graphql/v1/proxy
```

---

## Benefits of Consolidation

1. **Clear Separation of Concerns**
   - Stripe operations → `hsm-stripe/v1`
   - GraphQL operations → `hsm-graphql/v1`
   - General operations → `hsm/v1`

2. **Eliminated Duplicates**
   - No more duplicate routes across namespaces
   - Single source of truth for each operation

3. **Consistent Permissions**
   - Each namespace has consistent permission handling
   - Clear documentation of public vs protected endpoints

4. **Better Maintainability**
   - Routes organized by functionality
   - Easier to find and update endpoints

5. **Backward Compatibility**
   - Deprecated endpoints return 301 redirects with new endpoint URLs
   - Frontend can gradually migrate

---

## API Route Registration Files

| Namespace | Registration File | Purpose |
|-----------|------------------|---------|
| `hsm/v1` | `includes/api/class-rest-api.php` | General HSM endpoints |
| `hsm-stripe/v1` | `includes/api/class-api-manager.php` | Stripe payment operations |
| `hsm-graphql/v1` | `includes/api/class-graphql-proxy-api.php` | GraphQL proxy operations |
| `hsm-graphql/v1` | `includes/api/class-graphql-healthcheck-api.php` | GraphQL health checks |
| `hsm/v1` | `includes/api/class-submit-consultation-api.php` | Consultation form endpoints |

---

## Summary

- **Total Namespaces:** 3
- **Total Routes:** 28
- **Deprecated Routes:** 4 (with 301 redirects)
- **Consolidation Status:** ✅ Complete

All duplicate routes have been removed, and the API structure is now consistent and well-organized.

