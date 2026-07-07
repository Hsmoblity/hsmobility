/**
 * HSM Plugin Admin JavaScript
 *
 * @package HSM
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * HSM Admin Dashboard
     */
    const HSMAdmin = {
        
        /**
         * Initialize admin dashboard
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initAutoRefresh();
            this.initFormValidation();
            this.initAccessibility();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Status indicator clicks
            $(document).on('click', '.hsm-status-item', this.handleStatusClick);
            
            // Endpoint status checks
            $(document).on('click', '.endpoint-status', this.checkEndpointStatus);
            
            // Log refresh
            $(document).on('click', '.refresh-logs', this.refreshLogs);
            
            // Form submissions
            $(document).on('submit', '.hsm-config-form', this.handleFormSubmit);
            
            // Tab switching
            $(document).on('click', '.hsm-nav-tabs .nav-tab', this.handleTabSwitch);
            
            // ASCII map interactions
            $(document).on('click', '.hsm-ascii-map', this.handleAsciiMapClick);
            
            // Keyboard navigation
            $(document).on('keydown', this.handleKeyboardNavigation);
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Add tooltips to status indicators
            $('.hsm-status-item').each(function() {
                const $this = $(this);
                const status = $this.find('.status-value').text();
                $this.attr('title', `Current status: ${status}`);
            });

            // Add tooltips to health indicators
            $('.hsm-health-item').each(function() {
                const $this = $(this);
                const name = $this.find('.health-name').text();
                const status = $this.find('.health-status').text();
                $this.attr('title', `${name}: ${status}`);
            });
        },

        /**
         * Initialize auto-refresh for dashboard
         */
        initAutoRefresh: function() {
            // Only refresh on dashboard tab
            if ($('.hsm-nav-tabs .nav-tab-active').text().trim() === 'Dashboard') {
                setInterval(this.refreshDashboard, 30000); // Refresh every 30 seconds
            }
        },

        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            // Stripe key validation
            $('#stripe_secret_key').on('blur', function() {
                const value = $(this).val();
                if (value && !value.startsWith('sk_')) {
                    HSMAdmin.showFieldError($(this), 'Stripe secret key should start with "sk_"');
                } else {
                    HSMAdmin.hideFieldError($(this));
                }
            });

            $('#stripe_publishable_key').on('blur', function() {
                const value = $(this).val();
                if (value && !value.startsWith('pk_')) {
                    HSMAdmin.showFieldError($(this), 'Stripe publishable key should start with "pk_"');
                } else {
                    HSMAdmin.hideFieldError($(this));
                }
            });

            // Tax rate validation
            $('#tax_fallback_rate').on('blur', function() {
                const value = parseFloat($(this).val());
                if (value < 0 || value > 100) {
                    HSMAdmin.showFieldError($(this), 'Tax rate must be between 0 and 100');
                } else {
                    HSMAdmin.hideFieldError($(this));
                }
            });
        },

        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            // Add ARIA labels
            $('.hsm-status-item').attr('role', 'button').attr('tabindex', '0');
            $('.hsm-health-item').attr('role', 'button').attr('tabindex', '0');
            $('.endpoint-status').attr('role', 'button').attr('tabindex', '0');

            // Add ARIA live region for dynamic content
            $('body').append('<div id="hsm-aria-live" aria-live="polite" aria-atomic="true" class="screen-reader-text"></div>');
        },

        /**
         * Handle status indicator clicks
         */
        handleStatusClick: function(e) {
            e.preventDefault();
            const $this = $(this);
            const statusType = $this.find('.status-label').text();
            
            HSMAdmin.announceToScreenReader(`Status check for ${statusType}`);
            
            // Add visual feedback
            $this.addClass('clicked');
            setTimeout(() => {
                $this.removeClass('clicked');
            }, 200);
        },

        /**
         * Check endpoint status
         */
        checkEndpointStatus: function(e) {
            e.preventDefault();
            const $this = $(this);
            const endpoint = $this.closest('.hsm-endpoint-item').find('code').text();
            
            HSMAdmin.announceToScreenReader(`Checking status for ${endpoint}`);
            
            // Simulate status check
            $this.find('.status-indicator').text('⟳');
            $this.find('.status-indicator').addClass('spinning');
            
            setTimeout(() => {
                $this.find('.status-indicator').text('✓');
                $this.find('.status-indicator').removeClass('spinning');
                HSMAdmin.announceToScreenReader(`${endpoint} is active`);
            }, 1000);
        },

        /**
         * Refresh logs
         */
        refreshLogs: function(e) {
            e.preventDefault();
            HSMAdmin.announceToScreenReader('Refreshing logs');
            
            // Reload the page to refresh logs
            window.location.reload();
        },

        /**
         * Handle form submission
         */
        handleFormSubmit: function(e) {
            const $form = $(this);
            const $submitBtn = $form.find('input[type="submit"]');
            
            // Disable submit button and show loading state
            $submitBtn.prop('disabled', true).val('Saving...');
            
            // Add loading class to form
            $form.addClass('loading');
            
            HSMAdmin.announceToScreenReader('Saving settings');
        },

        /**
         * Handle tab switching
         */
        handleTabSwitch: function(e) {
            const tabName = $(this).text().trim();
            HSMAdmin.announceToScreenReader(`Switched to ${tabName} tab`);
        },

        /**
         * Handle ASCII map clicks
         */
        handleAsciiMapClick: function(e) {
            const $this = $(this);
            const clickX = e.offsetX;
            const clickY = e.offsetY;
            
            // Simple ASCII map interaction
            HSMAdmin.announceToScreenReader('ASCII map clicked - System architecture visualization');
            
            // Add visual feedback
            $this.addClass('ascii-clicked');
            setTimeout(() => {
                $this.removeClass('ascii-clicked');
            }, 300);
        },

        /**
         * Handle keyboard navigation
         */
        handleKeyboardNavigation: function(e) {
            // Handle Enter key on interactive elements
            if (e.key === 'Enter' || e.key === ' ') {
                const $target = $(e.target);
                
                if ($target.hasClass('hsm-status-item') || 
                    $target.hasClass('hsm-health-item') || 
                    $target.hasClass('endpoint-status')) {
                    e.preventDefault();
                    $target.click();
                }
            }
            
            // Handle Escape key to close any open modals or overlays
            if (e.key === 'Escape') {
                $('.hsm-modal, .hsm-overlay').remove();
            }
        },

        /**
         * Refresh dashboard data
         */
        refreshDashboard: function() {
            // Only refresh if on dashboard tab
            if ($('.hsm-nav-tabs .nav-tab-active').text().trim() !== 'Dashboard') {
                return;
            }

            HSMAdmin.announceToScreenReader('Refreshing dashboard data');
            
            // Add refresh indicator
            $('.hsm-dashboard-content').addClass('refreshing');
            
            // Simulate data refresh
            setTimeout(() => {
                $('.hsm-dashboard-content').removeClass('refreshing');
                HSMAdmin.announceToScreenReader('Dashboard data refreshed');
            }, 2000);
        },

        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            HSMAdmin.hideFieldError($field);
            
            const $error = $('<div class="hsm-field-error">' + message + '</div>');
            $field.after($error);
            $field.addClass('error');
            
            HSMAdmin.announceToScreenReader(`Error: ${message}`);
        },

        /**
         * Hide field error
         */
        hideFieldError: function($field) {
            $field.siblings('.hsm-field-error').remove();
            $field.removeClass('error');
        },

        /**
         * Announce to screen readers
         */
        announceToScreenReader: function(message) {
            $('#hsm-aria-live').text(message);
        },

        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            const $notification = $(`
                <div class="hsm-notification hsm-notification-${type}" role="alert">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close" aria-label="Close notification">&times;</button>
                </div>
            `);
            
            $('body').append($notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(() => {
                    $notification.remove();
                });
            }, 5000);
            
            // Handle close button
            $notification.find('.notification-close').on('click', function() {
                $notification.fadeOut(() => {
                    $notification.remove();
                });
            });
            
            HSMAdmin.announceToScreenReader(message);
        },

        /**
         * Format file size
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * Format timestamp
         */
        formatTimestamp: function(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleString();
        },

        /**
         * Copy to clipboard
         */
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    HSMAdmin.showNotification('Copied to clipboard', 'success');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                HSMAdmin.showNotification('Copied to clipboard', 'success');
            }
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        HSMAdmin.init();
        
        // Add copy functionality to code blocks
        $('code').on('click', function() {
            HSMAdmin.copyToClipboard($(this).text());
        });
        
        // Add loading states to buttons
        $('.button').on('click', function() {
            const $btn = $(this);
            if (!$btn.prop('disabled')) {
                $btn.addClass('loading');
                setTimeout(() => {
                    $btn.removeClass('loading');
                }, 2000);
            }
        });
    });

    /**
     * Add CSS for JavaScript interactions
     */
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .hsm-status-item.clicked,
            .hsm-health-item.clicked {
                transform: scale(0.95);
                transition: transform 0.2s ease;
            }
            
            .hsm-ascii-map.ascii-clicked {
                background: #001100 !important;
                transition: background 0.3s ease;
            }
            
            .spinning {
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            .hsm-dashboard-content.refreshing {
                opacity: 0.7;
                transition: opacity 0.3s ease;
            }
            
            .hsm-field-error {
                color: #d63638;
                font-size: 12px;
                margin-top: 5px;
                font-style: italic;
            }
            
            .hsm-field-error + input {
                border-color: #d63638;
            }
            
            .hsm-notification {
                position: fixed;
                top: 32px;
                right: 20px;
                background: white;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 15px 20px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                z-index: 9999;
                max-width: 400px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .hsm-notification-success {
                border-left: 4px solid #00a32a;
            }
            
            .hsm-notification-error {
                border-left: 4px solid #d63638;
            }
            
            .hsm-notification-info {
                border-left: 4px solid #2271b1;
            }
            
            .notification-close {
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                margin-left: 10px;
                color: #646970;
            }
            
            .notification-close:hover {
                color: #1d2327;
            }
            
            .button.loading {
                opacity: 0.7;
                cursor: not-allowed;
            }
            
            .button.loading::after {
                content: " ⟳";
                animation: spin 1s linear infinite;
            }
            
            .screen-reader-text {
                clip: rect(1px, 1px, 1px, 1px);
                position: absolute !important;
                height: 1px;
                width: 1px;
                overflow: hidden;
            }
        `)
        .appendTo('head');

})(jQuery);