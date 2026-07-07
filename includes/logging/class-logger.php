<?php
/**
 * HSM Logger Class
 * 
 * Main logging class that combines file and database logging
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Logger {
    
    /**
     * Singleton instance
     *
     * @var HSM_Logger
     */
    private static $instance = null;
    
    /**
     * File logger instance
     *
     * @var HSM_File_Logger
     */
    private $file_logger;
    
    /**
     * Database logger instance
     *
     * @var HSM_Database_Logger
     */
    private $database_logger;
    
    /**
     * Minimum log level
     *
     * @var string
     */
    private $min_level;
    
    /**
     * Get singleton instance
     *
     * @return HSM_Logger
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->min_level = get_option('hsm_stripe_debug_mode', false) 
            ? HSM_Logger_Base::LEVEL_DEBUG 
            : HSM_Logger_Base::LEVEL_INFO;
        
        $this->file_logger = new HSM_File_Logger($this->min_level);
        $this->database_logger = new HSM_Database_Logger($this->min_level);
    }
    
    /**
     * Log message
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function log($level, $message, $context = []) {
        // Log to file
        $this->file_logger->log($level, $message, $context);
        
        // Log to database for error and critical levels
        if (in_array($level, [HSM_Logger_Base::LEVEL_ERROR, HSM_Logger_Base::LEVEL_CRITICAL])) {
            $this->database_logger->log($level, $message, $context);
        }
    }
    
    /**
     * Log debug message
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function debug($message, $context = []) {
        $this->log(HSM_Logger_Base::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function info($message, $context = []) {
        $this->log(HSM_Logger_Base::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log warning message
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function warning($message, $context = []) {
        $this->log(HSM_Logger_Base::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log error message
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function error($message, $context = []) {
        $this->log(HSM_Logger_Base::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Log critical message
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function critical($message, $context = []) {
        $this->log(HSM_Logger_Base::LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Get recent logs from database
     *
     * @param int $limit Number of logs to retrieve
     * @param string $level Filter by log level
     * @return array
     */
    public function get_recent_logs($limit = 100, $level = null) {
        return $this->database_logger->get_recent_logs($limit, $level);
    }
    
    /**
     * Clear old logs
     *
     * @param int $days Number of days to keep
     * @return int Number of deleted logs
     */
    public function clear_old_logs($days = 30) {
        return $this->database_logger->clear_old_logs($days);
    }
    
    /**
     * Get log statistics
     *
     * @return array
     */
    public function get_log_stats() {
        return $this->database_logger->get_log_stats();
    }
    
    /**
     * Get log file path
     *
     * @return string
     */
    public function get_log_file() {
        return $this->file_logger->get_log_file();
    }
    
    /**
     * Clear log file
     *
     * @return bool
     */
    public function clear_log_file() {
        return $this->file_logger->clear_log();
    }
    
    /**
     * Get log file size
     *
     * @return int
     */
    public function get_log_file_size() {
        return $this->file_logger->get_log_size();
    }
}