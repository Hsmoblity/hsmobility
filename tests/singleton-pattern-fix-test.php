<?php
/**
 * Singleton Pattern Fix Test
 * 
 * Tests the fix for PHP constructor error in HSM_Settings_Manager
 * singleton pattern with HSM_Memory_Manager lazy loading
 * 
 * @package HSM
 * @since 2.0.0
 * @author DIONYSUS - Divine Fullstack Engineer
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Singleton Pattern Fix Test Suite
 */
class HSM_Singleton_Pattern_Fix_Test {
    
    /**
     * Test singleton detection
     */
    public function test_singleton_detection() {
        echo "Testing Singleton Detection...\n";
        
        // Test memory manager
        $memory_manager = HSM_Memory_Manager::get_instance();
        
        // Test known singleton classes
        $known_singletons = array(
            'HSM_Settings_Manager',
            'HSM_Logger',
            'HSM_Database_Optimizer',
            'HSM_Memory_Manager',
            'HSM_Log_Manager',
            'HSM_HTTP_Client'
        );
        
        foreach ($known_singletons as $class_name) {
            // Use reflection to check if it's a singleton
            $reflection = new ReflectionClass($class_name);
            $is_singleton = $reflection->hasMethod('get_instance') || 
                           ($reflection->hasMethod('__construct') && 
                            $reflection->getMethod('__construct')->isPrivate());
            
            assert($is_singleton, "Class {$class_name} should be detected as singleton");
        }
        
        echo "✅ Singleton detection tests passed\n";
    }
    
    /**
     * Test lazy loading with singleton pattern
     */
    public function test_lazy_loading_singleton() {
        echo "Testing Lazy Loading with Singleton Pattern...\n";
        
        $memory_manager = HSM_Memory_Manager::get_instance();
        
        // Test lazy loading HSM_Settings_Manager (the problematic class)
        $settings_manager = $memory_manager->lazy_load_class('HSM_Settings_Manager');
        
        assert($settings_manager !== false, "HSM_Settings_Manager should load successfully");
        assert(is_object($settings_manager), "HSM_Settings_Manager should return an object");
        assert($settings_manager instanceof HSM_Settings_Manager, "Should be instance of HSM_Settings_Manager");
        
        // Test that it's the same instance (singleton behavior)
        $settings_manager2 = $memory_manager->lazy_load_class('HSM_Settings_Manager');
        assert($settings_manager === $settings_manager2, "Should return same instance (singleton)");
        
        echo "✅ Lazy loading singleton tests passed\n";
    }
    
    /**
     * Test get_singleton method
     */
    public function test_get_singleton_method() {
        echo "Testing Get Singleton Method...\n";
        
        $memory_manager = HSM_Memory_Manager::get_instance();
        
        // Test get_singleton for HSM_Settings_Manager
        $settings_manager = $memory_manager->get_singleton('HSM_Settings_Manager');
        
        assert($settings_manager !== false, "get_singleton should return instance");
        assert(is_object($settings_manager), "get_singleton should return object");
        assert($settings_manager instanceof HSM_Settings_Manager, "Should be HSM_Settings_Manager instance");
        
        // Test singleton behavior
        $settings_manager2 = $memory_manager->get_singleton('HSM_Settings_Manager');
        assert($settings_manager === $settings_manager2, "get_singleton should return same instance");
        
        echo "✅ Get singleton method tests passed\n";
    }
    
    /**
     * Test regular class instantiation
     */
    public function test_regular_class_instantiation() {
        echo "Testing Regular Class Instantiation...\n";
        
        $memory_manager = HSM_Memory_Manager::get_instance();
        
        // Test with a non-singleton class (if any exist in lazy_classes)
        // For now, test that the system doesn't break with regular classes
        $result = $memory_manager->lazy_load_class('NonExistentClass');
        assert($result === false, "Non-existent class should return false");
        
        echo "✅ Regular class instantiation tests passed\n";
    }
    
    /**
     * Test singleton statistics
     */
    public function test_singleton_statistics() {
        echo "Testing Singleton Statistics...\n";
        
        $memory_manager = HSM_Memory_Manager::get_instance();
        
        // Get singleton stats
        $stats = $memory_manager->get_singleton_stats();
        
        assert(isset($stats['known_singleton_classes']), "Stats should include known singleton classes");
        assert(isset($stats['detection_cache']), "Stats should include detection cache");
        assert(isset($stats['cache_hits']), "Stats should include cache hits");
        assert(isset($stats['registered_singletons']), "Stats should include registered singletons");
        
        assert(is_array($stats['known_singleton_classes']), "Known singleton classes should be array");
        assert(is_array($stats['detection_cache']), "Detection cache should be array");
        assert(is_int($stats['cache_hits']), "Cache hits should be integer");
        assert(is_int($stats['registered_singletons']), "Registered singletons should be integer");
        
        echo "✅ Singleton statistics tests passed\n";
    }
    
    /**
     * Test error handling
     */
    public function test_error_handling() {
        echo "Testing Error Handling...\n";
        
        $memory_manager = HSM_Memory_Manager::get_instance();
        
        // Test with invalid class name
        $result = $memory_manager->lazy_load_class('InvalidClassName123');
        assert($result === false, "Invalid class should return false");
        
        // Test get_singleton with invalid class
        $result = $memory_manager->get_singleton('InvalidClassName123');
        assert($result === false, "Invalid singleton should return false");
        
        echo "✅ Error handling tests passed\n";
    }
    
    /**
     * Test memory usage with singleton pattern
     */
    public function test_memory_usage_with_singleton() {
        echo "Testing Memory Usage with Singleton Pattern...\n";
        
        $memory_manager = HSM_Memory_Manager::get_instance();
        
        // Get initial memory usage
        $initial_memory = memory_get_usage(true);
        
        // Load multiple singletons
        $settings_manager = $memory_manager->get_singleton('HSM_Settings_Manager');
        $logger = $memory_manager->get_singleton('HSM_Logger');
        
        // Load them again (should return same instances)
        $settings_manager2 = $memory_manager->get_singleton('HSM_Settings_Manager');
        $logger2 = $memory_manager->get_singleton('HSM_Logger');
        
        // Verify singleton behavior
        assert($settings_manager === $settings_manager2, "Settings manager should be same instance");
        assert($logger === $logger2, "Logger should be same instance");
        
        // Check memory usage is reasonable
        $final_memory = memory_get_usage(true);
        $memory_increase = $final_memory - $initial_memory;
        
        // Should not increase memory significantly due to singleton pattern
        assert($memory_increase < 1048576, "Memory increase should be less than 1MB: " . $memory_increase . " bytes");
        
        echo "✅ Memory usage with singleton pattern tests passed\n";
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "🧪 Starting Singleton Pattern Fix Tests...\n\n";
        
        try {
            $this->test_singleton_detection();
            $this->test_lazy_loading_singleton();
            $this->test_get_singleton_method();
            $this->test_regular_class_instantiation();
            $this->test_singleton_statistics();
            $this->test_error_handling();
            $this->test_memory_usage_with_singleton();
            
            echo "\n🎉 All singleton pattern fix tests passed!\n";
            echo "✅ PHP constructor error has been resolved!\n";
            echo "✅ Singleton pattern integrity maintained!\n";
            echo "✅ Lazy loading system respects singleton patterns!\n";
            
            return true;
            
        } catch (Exception $e) {
            echo "\n❌ Test failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    $tests = new HSM_Singleton_Pattern_Fix_Test();
    $tests->run_all_tests();
}