# HSM Plugin Tests

This directory contains test files for the HSM Stripe Plugin.

## Test Files

### Plugin Activation Tests
- **`plugin-activation/test-plugin-activation.php`** - Tests the original plugin activation functionality
- **`plugin-activation/test-modular-plugin-activation.php`** - Tests the new modular plugin structure

## Running Tests

To run the plugin activation tests:

```bash
# Test original plugin structure
php tests/plugin-activation/test-plugin-activation.php

# Test modular plugin structure
php tests/plugin-activation/test-modular-plugin-activation.php
```

## Test Coverage

The tests verify:
- ✅ Required files exist
- ✅ Classes load successfully
- ✅ Plugin instantiates without errors
- ✅ Activation hook completes successfully
- ✅ WordPress standards compliance
- ✅ Modular structure functionality

## Test Results

Both test suites should pass with all green checkmarks, indicating the plugin is working correctly and follows WordPress best practices.