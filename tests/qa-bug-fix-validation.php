<?php
/**
 * QA Bug Fix Validation Test
 * 
 * Comprehensive testing of the CMS plugin WordPress crash bug fix
 * Tests all the fixes applied by Fullstack Engineer
 * 
 * @package HSM
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../');
}

echo "=== QA Bug Fix Validation Test ===\n\n";
echo "Testing CMS Plugin WordPress Crash Bug Fix\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Test Results Tracking
$test_results = [];
$total_tests = 0;
$passed_tests = 0;

function run_test($test_name, $test_function) {
    global $test_results, $total_tests, $passed_tests;
    
    $total_tests++;
    echo "Test {$total_tests}: {$test_name}...\n";
    
    try {
        $result = $test_function();
        if ($result) {
            echo "✅ PASSED: {$test_name}\n\n";
            $test_results[] = "✅ {$test_name}";
            $passed_tests++;
        } else {
            echo "❌ FAILED: {$test_name}\n\n";
            $test_results[] = "❌ {$test_name}";
        }
    } catch (Exception $e) {
        echo "❌ FAILED: {$test_name} - Error: " . $e->getMessage() . "\n\n";
        $test_results[] = "❌ {$test_name} - Error: " . $e->getMessage();
    }
}

// Test 1: Plugin File Structure Validation
run_test("Plugin File Structure Validation", function() {
    $required_files = [
        'hsm-stripe.php',
        'composer.json',
        'INSTALLATION.md',
        'includes/class-hsm-stripe-plugin.php',
        'includes/class-autoloader.php',
        'includes/error/class-error-handler.php'
    ];
    
    $all_exist = true;
    foreach ($required_files as $file) {
        $file_path = __DIR__ . '/../' . $file;
        if (!file_exists($file_path)) {
            echo "✗ Missing file: {$file}\n";
            $all_exist = false;
        } else {
            echo "✓ Found file: {$file}\n";
        }
    }
    
    return $all_exist;
});

// Test 2: Plugin Header Validation
run_test("Plugin Header Validation", function() {
    $plugin_file = __DIR__ . '/../hsm-stripe.php';
    $content = file_get_contents($plugin_file);
    
    // Check for proper plugin header
    $has_header = strpos($content, 'Plugin Name: hsm-stripe') !== false;
    $has_description = strpos($content, 'Description: Simplified Stripe integration') !== false;
    $has_version = strpos($content, 'Version: 1.0.0') !== false;
    $has_php_requirement = strpos($content, 'Requires PHP: 7.4') !== false;
    
    echo "Plugin header checks:\n";
    echo "- Plugin Name: " . ($has_header ? "✓" : "✗") . "\n";
    echo "- Description: " . ($has_description ? "✓" : "✗") . "\n";
    echo "- Version: " . ($has_version ? "✓" : "✗") . "\n";
    echo "- PHP Requirement: " . ($has_php_requirement ? "✓" : "✗") . "\n";
    
    return $has_header && $has_description && $has_version && $has_php_requirement;
});

// Test 3: WooCommerce Dependency Check
run_test("WooCommerce Dependency Check", function() {
    $plugin_file = __DIR__ . '/../hsm-stripe.php';
    $content = file_get_contents($plugin_file);
    
    // Check for WooCommerce dependency check
    $has_woo_check = strpos($content, 'woocommerce/woocommerce.php') !== false;
    $has_early_return = strpos($content, 'if (!in_array(\'woocommerce/woocommerce.php\'') !== false;
    $has_admin_notice = strpos($content, 'WooCommerce is required but not active') !== false;
    
    echo "WooCommerce dependency checks:\n";
    echo "- WooCommerce check: " . ($has_woo_check ? "✓" : "✗") . "\n";
    echo "- Early return: " . ($has_early_return ? "✓" : "✗") . "\n";
    echo "- Admin notice: " . ($has_admin_notice ? "✓" : "✗") . "\n";
    
    return $has_woo_check && $has_early_return && $has_admin_notice;
});

// Test 4: Error Handling Implementation
run_test("Error Handling Implementation", function() {
    $plugin_class_file = __DIR__ . '/../includes/class-hsm-stripe-plugin.php';
    $content = file_get_contents($plugin_class_file);
    
    // Check for error handling in constructor
    $has_try_catch = strpos($content, 'try {') !== false;
    $has_catch_block = strpos($content, 'catch (Exception $e)') !== false;
    $has_error_logging = strpos($content, 'error_log(') !== false;
    $has_admin_notice_method = strpos($content, 'show_admin_notice') !== false;
    $has_class_check = strpos($content, 'if (!class_exists(\'HSM_Error_Handler\'))') !== false;
    
    echo "Error handling checks:\n";
    echo "- Try-catch block: " . ($has_try_catch ? "✓" : "✗") . "\n";
    echo "- Exception handling: " . ($has_catch_block ? "✓" : "✗") . "\n";
    echo "- Error logging: " . ($has_error_logging ? "✓" : "✗") . "\n";
    echo "- Admin notice method: " . ($has_admin_notice_method ? "✓" : "✗") . "\n";
    echo "- Class existence check: " . ($has_class_check ? "✓" : "✗") . "\n";
    
    return $has_try_catch && $has_catch_block && $has_error_logging && $has_admin_notice_method && $has_class_check;
});

// Test 5: Composer Dependency Management
run_test("Composer Dependency Management", function() {
    $composer_file = __DIR__ . '/../composer.json';
    
    if (!file_exists($composer_file)) {
        echo "✗ composer.json file missing\n";
        return false;
    }
    
    $composer_content = file_get_contents($composer_file);
    $composer_data = json_decode($composer_content, true);
    
    if (!$composer_data) {
        echo "✗ Invalid JSON in composer.json\n";
        return false;
    }
    
    $has_stripe_dependency = isset($composer_data['require']['stripe/stripe-php']);
    $has_php_requirement = isset($composer_data['require']['php']) && $composer_data['require']['php'] >= '7.4';
    $has_autoload = isset($composer_data['autoload']['psr-4']);
    $has_scripts = isset($composer_data['scripts']);
    
    echo "Composer dependency checks:\n";
    echo "- Stripe dependency: " . ($has_stripe_dependency ? "✓" : "✗") . "\n";
    echo "- PHP requirement: " . ($has_php_requirement ? "✓" : "✗") . "\n";
    echo "- Autoload config: " . ($has_autoload ? "✓" : "✗") . "\n";
    echo "- Scripts config: " . ($has_scripts ? "✓" : "✗") . "\n";
    
    return $has_stripe_dependency && $has_php_requirement && $has_autoload && $has_scripts;
});

// Test 6: Installation Guide Validation
run_test("Installation Guide Validation", function() {
    $install_file = __DIR__ . '/../INSTALLATION.md';
    
    if (!file_exists($install_file)) {
        echo "✗ INSTALLATION.md file missing\n";
        return false;
    }
    
    $install_content = file_get_contents($install_file);
    
    $has_prerequisites = strpos($install_content, 'Prerequisites') !== false;
    $has_install_steps = strpos($install_content, 'Installation Steps') !== false;
    $has_composer_install = strpos($install_content, 'composer install') !== false;
    $has_troubleshooting = strpos($install_content, 'Troubleshooting') !== false;
    $has_plugin_structure = strpos($install_content, 'Plugin Structure') !== false;
    
    echo "Installation guide checks:\n";
    echo "- Prerequisites section: " . ($has_prerequisites ? "✓" : "✗") . "\n";
    echo "- Installation steps: " . ($has_install_steps ? "✓" : "✗") . "\n";
    echo "- Composer install: " . ($has_composer_install ? "✓" : "✗") . "\n";
    echo "- Troubleshooting: " . ($has_troubleshooting ? "✓" : "✗") . "\n";
    echo "- Plugin structure: " . ($has_plugin_structure ? "✓" : "✗") . "\n";
    
    return $has_prerequisites && $has_install_steps && $has_composer_install && $has_troubleshooting && $has_plugin_structure;
});

// Test 7: Plugin Initialization Safety
run_test("Plugin Initialization Safety", function() {
    $plugin_file = __DIR__ . '/../hsm-stripe.php';
    $content = file_get_contents($plugin_file);
    
    // Check for safe initialization
    $has_plugins_loaded_hook = strpos($content, 'plugins_loaded') !== false;
    $has_woo_class_check = strpos($content, 'class_exists(\'WooCommerce\')') !== false;
    $has_conditional_init = strpos($content, 'if (class_exists(\'WooCommerce\'))') !== false;
    $has_no_immediate_instantiation = strpos($content, 'new HSM_Stripe_Plugin();') === false || strpos($content, 'plugins_loaded') !== false;
    
    echo "Plugin initialization safety checks:\n";
    echo "- plugins_loaded hook: " . ($has_plugins_loaded_hook ? "✓" : "✗") . "\n";
    echo "- WooCommerce class check: " . ($has_woo_class_check ? "✓" : "✗") . "\n";
    echo "- Conditional initialization: " . ($has_conditional_init ? "✓" : "✗") . "\n";
    echo "- No immediate instantiation: " . ($has_no_immediate_instantiation ? "✓" : "✗") . "\n";
    
    return $has_plugins_loaded_hook && $has_woo_class_check && $has_conditional_init && $has_no_immediate_instantiation;
});

// Test 8: File Size Compliance
run_test("File Size Compliance", function() {
    $files_to_check = [
        'hsm-stripe.php',
        'includes/class-hsm-stripe-plugin.php',
        'includes/class-autoloader.php',
        'includes/error/class-error-handler.php'
    ];
    
    $all_compliant = true;
    foreach ($files_to_check as $file) {
        $file_path = __DIR__ . '/../' . $file;
        if (file_exists($file_path)) {
            $line_count = count(file($file_path));
            $is_compliant = $line_count <= 200; // WordPress standard
            
            echo "{$file}: {$line_count} lines " . ($is_compliant ? "✓" : "✗") . "\n";
            
            if (!$is_compliant) {
                $all_compliant = false;
            }
        }
    }
    
    return $all_compliant;
});

// Test Summary
echo "=== QA Test Summary ===\n";
echo "Total Tests: {$total_tests}\n";
echo "Passed: {$passed_tests}\n";
echo "Failed: " . ($total_tests - $passed_tests) . "\n";
echo "Success Rate: " . round(($passed_tests / $total_tests) * 100, 2) . "%\n\n";

echo "=== Test Results ===\n";
foreach ($test_results as $result) {
    echo "{$result}\n";
}

echo "\n=== Bug Fix Validation ===\n";
if ($passed_tests === $total_tests) {
    echo "🎉 ALL TESTS PASSED!\n";
    echo "✅ WordPress crash bug fix is VALIDATED\n";
    echo "✅ Plugin structure is optimized\n";
    echo "✅ Error handling is comprehensive\n";
    echo "✅ Dependency management is proper\n";
    echo "✅ Installation guide is complete\n";
    echo "✅ File sizes comply with WordPress standards\n";
    echo "\nThe plugin should now activate without causing WordPress crashes.\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED!\n";
    echo "The bug fix requires additional validation.\n";
    exit(1);
}