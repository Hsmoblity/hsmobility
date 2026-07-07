<?php
/**
 * HSM Log Manager Class
 * 
 * Provides comprehensive logging with rotation, size limits, and performance optimization.
 * Implements enterprise-grade logging system with asynchronous processing and cleanup.
 * 
 * @package HSM
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSM_Log_Manager {
    
    /**
     * Singleton instance
     * 
     * @var HSM_Log_Manager
     */
    private static $instance = null;
    
    /**
     * Log directory
     * 
     * @var string
     */
    private $log_directory;
    
    /**
     * Maximum log file size (bytes)
     * 
     * @var int
     */
    private $max_file_size = 10485760; // 10MB
    
    /**
     * Maximum number of log files
     * 
     * @var int
     */
    private $max_files = 5;
    
    /**
     * Log levels
     * 
     * @var array
     */
    private $log_levels = array(
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4
    );
    
    /**
     * Current log level
     * 
     * @var int
     */
    private $current_log_level = 1; // info
    
    /**
     * Log queue for asynchronous processing
     * 
     * @var array
     */
    private $log_queue = array();
    
    /**
     * Log statistics
     * 
     * @var array
     */
    private $log_stats = array(
        'total_logs' => 0,
        'debug_logs' => 0,
        'info_logs' => 0,
        'warning_logs' => 0,
        'error_logs' => 0,
        'critical_logs' => 0,
        'rotated_files' => 0,
        'cleaned_files' => 0
    );
    
    /**
     * Get singleton instance
     *
     * @return HSM_Log_Manager
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
        $this->initialize_log_directory();
        $this->schedule_cleanup();
        $this->process_log_queue();
    }
    
    /**
     * Initialize log directory
     */
    private function initialize_log_directory() {
        $upload_dir = wp_upload_dir();
        $this->log_directory = $upload_dir['basedir'] . '/hsm-logs/';
        
        if (!file_exists($this->log_directory)) {
            wp_mkdir_p($this->log_directory);
        }
        
        // Create .htaccess to protect log files
        $htaccess_file = $this->log_directory . '.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Order deny,allow\nDeny from all\n");
        }
    }
    
    /**
     * Schedule log cleanup
     */
    private function schedule_cleanup() {
        if (!wp_next_scheduled('hsm_log_cleanup')) {
            wp_schedule_event(time(), 'daily', 'hsm_log_cleanup');
        }
        
        add_action('hsm_log_cleanup', array($this, 'cleanup_old_logs'));
    }
    
    /**
     * Process log queue
     */
    private function process_log_queue() {
        add_action('shutdown', array($this, 'flush_log_queue'));
    }
    
    /**
     * Log message
     * 
     * @param string $message Log message
     * @param string $level Log level
     * @param array $context Additional context
     */
    public function log($message, $level = 'info', $context = array()) {
        if ($this->log_levels[$level] < $this->current_log_level) {
            return;
        }
        
        $log_entry = $this->format_log_entry($message, $level, $context);
        
        // Add to queue for asynchronous processing
        $this->log_queue[] = $log_entry;
        
        // Update statistics
        $this->update_log_stats($level);
        
        // Process queue if it's getting large
        if (count($this->log_queue) >= 100) {
            $this->flush_log_queue();
        }
    }
    
    /**
     * Format log entry
     * 
     * @param string $message Log message
     * @param string $level Log level
     * @param array $context Additional context
     * @return string Formatted log entry
     */
    private function format_log_entry($message, $level, $context) {
        $timestamp = current_time('Y-m-d H:i:s');
        $memory_usage = memory_get_usage(true);
        $memory_peak = memory_get_peak_usage(true);
        
        $log_entry = "[{$timestamp}] [{$level}] [Memory: {$this->format_bytes($memory_usage)}] [Peak: {$this->format_bytes($memory_peak)}] {$message}";
        
        if (!empty($context)) {
            $log_entry .= " | Context: " . json_encode($context);
        }
        
        return $log_entry . "\n";
    }
    
    /**
     * Flush log queue
     */
    public function flush_log_queue() {
        if (empty($this->log_queue)) {
            return;
        }
        
        $log_file = $this->get_current_log_file();
        $log_content = implode('', $this->log_queue);
        
        // Check if file needs rotation
        if (file_exists($log_file) && filesize($log_file) + strlen($log_content) > $this->max_file_size) {
            $this->rotate_log_file();
        }
        
        // Write to file
        file_put_contents($log_file, $log_content, FILE_APPEND | LOCK_EX);
        
        // Clear queue
        $this->log_queue = array();
    }
    
    /**
     * Get current log file path
     * 
     * @return string Log file path
     */
    private function get_current_log_file() {
        return $this->log_directory . 'hsm-' . date('Y-m-d') . '.log';
    }
    
    /**
     * Rotate log file
     */
    private function rotate_log_file() {
        $current_file = $this->get_current_log_file();
        
        if (!file_exists($current_file)) {
            return;
        }
        
        // Rotate existing files
        for ($i = $this->max_files - 1; $i >= 1; $i--) {
            $old_file = $this->log_directory . "hsm-" . date('Y-m-d') . "-{$i}.log";
            $new_file = $this->log_directory . "hsm-" . date('Y-m-d') . "-" . ($i + 1) . ".log";
            
            if (file_exists($old_file)) {
                rename($old_file, $new_file);
            }
        }
        
        // Move current file to .1
        $rotated_file = $this->log_directory . "hsm-" . date('Y-m-d') . "-1.log";
        rename($current_file, $rotated_file);
        
        $this->log_stats['rotated_files']++;
        $this->log_info("Log file rotated: {$current_file} -> {$rotated_file}");
    }
    
    /**
     * Cleanup old log files
     */
    public function cleanup_old_logs() {
        $files = glob($this->log_directory . 'hsm-*.log');
        $files_to_keep = array();
        
        // Group files by date
        $files_by_date = array();
        foreach ($files as $file) {
            $basename = basename($file);
            if (preg_match('/hsm-(\d{4}-\d{2}-\d{2})/', $basename, $matches)) {
                $date = $matches[1];
                if (!isset($files_by_date[$date])) {
                    $files_by_date[$date] = array();
                }
                $files_by_date[$date][] = $file;
            }
        }
        
        // Keep only the most recent files
        krsort($files_by_date);
        $kept_dates = array_slice(array_keys($files_by_date), 0, $this->max_files);
        
        foreach ($files_by_date as $date => $date_files) {
            if (in_array($date, $kept_dates)) {
                // Sort files by modification time and keep the most recent ones
                usort($date_files, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                
                $files_to_keep = array_merge($files_to_keep, array_slice($date_files, 0, $this->max_files));
            }
        }
        
        // Delete files not in the keep list
        foreach ($files as $file) {
            if (!in_array($file, $files_to_keep)) {
                if (unlink($file)) {
                    $this->log_stats['cleaned_files']++;
                    $this->log_info("Deleted old log file: " . basename($file));
                }
            }
        }
        
        $this->log_info("Log cleanup completed. Kept " . count($files_to_keep) . " files, deleted " . $this->log_stats['cleaned_files'] . " files");
    }
    
    /**
     * Update log statistics
     * 
     * @param string $level Log level
     */
    private function update_log_stats($level) {
        $this->log_stats['total_logs']++;
        $this->log_stats[$level . '_logs']++;
    }
    
    /**
     * Get log statistics
     * 
     * @return array Log statistics
     */
    public function get_log_stats() {
        return $this->log_stats;
    }
    
    /**
     * Get log directory size
     * 
     * @return int Directory size in bytes
     */
    public function get_log_directory_size() {
        $size = 0;
        $files = glob($this->log_directory . 'hsm-*.log');
        
        foreach ($files as $file) {
            $size += filesize($file);
        }
        
        return $size;
    }
    
    /**
     * Get log files list
     * 
     * @return array Log files
     */
    public function get_log_files() {
        $files = glob($this->log_directory . 'hsm-*.log');
        $log_files = array();
        
        foreach ($files as $file) {
            $log_files[] = array(
                'name' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'size_formatted' => $this->format_bytes(filesize($file)),
                'modified' => filemtime($file),
                'modified_formatted' => date('Y-m-d H:i:s', filemtime($file))
            );
        }
        
        // Sort by modification time (newest first)
        usort($log_files, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        return $log_files;
    }
    
    /**
     * Set log level
     * 
     * @param string $level Log level
     */
    public function set_log_level($level) {
        if (isset($this->log_levels[$level])) {
            $this->current_log_level = $this->log_levels[$level];
            $this->log_info("Log level set to: {$level}");
        }
    }
    
    /**
     * Set maximum file size
     * 
     * @param int $size Size in bytes
     */
    public function set_max_file_size($size) {
        $this->max_file_size = max(1048576, min(104857600, $size)); // 1MB to 100MB
        $this->log_info("Maximum log file size set to: " . $this->format_bytes($this->max_file_size));
    }
    
    /**
     * Set maximum number of files
     * 
     * @param int $max_files Maximum number of files
     */
    public function set_max_files($max_files) {
        $this->max_files = max(1, min(20, $max_files));
        $this->log_info("Maximum log files set to: {$this->max_files}");
    }
    
    /**
     * Clear all logs
     */
    public function clear_all_logs() {
        $files = glob($this->log_directory . 'hsm-*.log');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        $this->log_queue = array();
        $this->log_info("All log files cleared");
    }
    
    /**
     * Get log content
     * 
     * @param string $filename Log filename
     * @param int $lines Number of lines to return
     * @return string Log content
     */
    public function get_log_content($filename, $lines = 100) {
        $file_path = $this->log_directory . $filename;
        
        if (!file_exists($file_path)) {
            return "Log file not found: {$filename}";
        }
        
        $content = file_get_contents($file_path);
        $content_lines = explode("\n", $content);
        
        if (count($content_lines) > $lines) {
            $content_lines = array_slice($content_lines, -$lines);
        }
        
        return implode("\n", $content_lines);
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes Bytes
     * @return string Formatted string
     */
    private function format_bytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Log info message
     * 
     * @param string $message Message
     */
    private function log_info($message) {
        // Use error_log to avoid infinite recursion
        error_log("HSM Log Manager: {$message}");
    }
    
    /**
     * Health check for log manager
     * 
     * @return array Health status
     */
    public function health_check() {
        // Use unified health manager if available, otherwise fallback to basic check
        if (class_exists('HSM_Health_Manager')) {
            $health_manager = HSM_Health_Manager::get_instance();
            $base_health = $health_manager->get_quick_health_status();
            
            // Add logging-specific health data
            $stats = $this->get_log_stats();
            $directory_size = $this->get_log_directory_size();
            $base_health['logging_info'] = array(
                'total_logs' => $stats['total_logs'],
                'directory_size' => $directory_size,
                'directory_size_formatted' => $this->format_bytes($directory_size),
                'log_files_count' => count($this->get_log_files()),
                'queue_size' => count($this->log_queue),
                'current_log_level' => array_search($this->current_log_level, $this->log_levels)
            );
            
            return $base_health;
        }
        
        // Fallback to basic logging health check
        $stats = $this->get_log_stats();
        $directory_size = $this->get_log_directory_size();
        return array(
            'status' => 'healthy',
            'total_logs' => $stats['total_logs'],
            'directory_size' => $directory_size,
            'directory_size_formatted' => $this->format_bytes($directory_size),
            'log_files_count' => count($this->get_log_files()),
            'queue_size' => count($this->log_queue),
            'current_log_level' => array_search($this->current_log_level, $this->log_levels),
            'note' => 'Using fallback health check - HSM_Health_Manager not available'
        );
    }
}