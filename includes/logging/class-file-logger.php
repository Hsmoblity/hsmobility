<?php
/**
 * HSM File Logger Class
 * 
 * File-based logging for the HSM plugin
 * 
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_File_Logger extends HSM_Logger_Base {
    
    /**
     * Log file path
     *
     * @var string
     */
    private $log_file;
    
    /**
     * Constructor
     *
     * @param string $min_level Minimum log level
     * @param string $log_file Log file path
     */
    public function __construct($min_level = self::LEVEL_INFO, $log_file = null) {
        parent::__construct($min_level);
        
        if ($log_file) {
            $this->log_file = $log_file;
        } else {
            $upload_dir = wp_upload_dir();
            $this->log_file = $upload_dir['basedir'] . '/hsm-logs/hsm-plugin.log';
        }
        
        // Ensure log directory exists
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
    }
    
    /**
     * Log message to file
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function log($level, $message, $context = []) {
        if (!$this->should_log($level)) {
            return;
        }
        
        $formatted_message = $this->format_message($level, $message, $context) . "\n";
        
        // Write to file
        file_put_contents($this->log_file, $formatted_message, FILE_APPEND | LOCK_EX);
        
        // Rotate log file if it gets too large (10MB)
        $this->rotate_log_if_needed();
    }
    
    /**
     * Rotate log file if it gets too large
     *
     * @return void
     */
    private function rotate_log_if_needed() {
        if (file_exists($this->log_file) && filesize($this->log_file) > 10 * 1024 * 1024) {
            $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s');
            rename($this->log_file, $backup_file);
            
            // Keep only last 5 backup files
            $this->cleanup_old_logs();
        }
    }
    
    /**
     * Cleanup old log files
     *
     * @return void
     */
    private function cleanup_old_logs() {
        $log_dir = dirname($this->log_file);
        $log_files = glob($log_dir . '/hsm-plugin.log.*');
        
        if (count($log_files) > 5) {
            // Sort by modification time (oldest first)
            usort($log_files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files
            $files_to_remove = array_slice($log_files, 0, count($log_files) - 5);
            foreach ($files_to_remove as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get log file path
     *
     * @return string
     */
    public function get_log_file() {
        return $this->log_file;
    }
    
    /**
     * Clear log file
     *
     * @return bool
     */
    public function clear_log() {
        if (file_exists($this->log_file)) {
            return unlink($this->log_file);
        }
        return true;
    }
    
    /**
     * Get log file size
     *
     * @return int
     */
    public function get_log_size() {
        return file_exists($this->log_file) ? filesize($this->log_file) : 0;
    }
}