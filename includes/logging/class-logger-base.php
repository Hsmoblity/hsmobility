<?php
/**
 * HSM Logger Base Class
 * 
 * Base logging functionality for the HSM plugin
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class HSM_Logger_Base {
    
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    /**
     * Log level priorities
     */
    const PRIORITIES = [
        self::LEVEL_DEBUG => 0,
        self::LEVEL_INFO => 1,
        self::LEVEL_WARNING => 2,
        self::LEVEL_ERROR => 3,
        self::LEVEL_CRITICAL => 4
    ];
    
    /**
     * Minimum log level
     *
     * @var string
     */
    protected $min_level;
    
    /**
     * Constructor
     *
     * @param string $min_level Minimum log level
     */
    public function __construct($min_level = self::LEVEL_INFO) {
        $this->min_level = $min_level;
    }
    
    /**
     * Check if log level should be logged
     *
     * @param string $level Log level
     * @return bool
     */
    protected function should_log($level) {
        return self::PRIORITIES[$level] >= self::PRIORITIES[$this->min_level];
    }
    
    /**
     * Format log message
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return string
     */
    protected function format_message($level, $message, $context = []) {
        $timestamp = current_time('Y-m-d H:i:s');
        $context_str = !empty($context) ? ' ' . json_encode($context) : '';
        
        return "[{$timestamp}] [{$level}] {$message}{$context_str}";
    }
    
    /**
     * Log message
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    abstract public function log($level, $message, $context = []);
    
    /**
     * Log debug message
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function debug($message, $context = []) {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function info($message, $context = []) {
        $this->log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log warning message
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function warning($message, $context = []) {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log error message
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function error($message, $context = []) {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Log critical message
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function critical($message, $context = []) {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }
}