/**
 * HSM GraphQL Testing Interface JavaScript
 * 
 * @package HSM
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // GraphQL Testing Interface
    const GraphQLTesting = {
        
        // Initialize the interface
        init: function() {
            this.bindEvents();
            this.testConnection();
            this.loadQueryTemplates();
        },
        
        // Bind event handlers
        bindEvents: function() {
            // Connection test button
            $('#test-connection').on('click', this.testConnection.bind(this));
            
            // Query template selection
            $('#query-template').on('change', this.loadQueryTemplate.bind(this));
            $('#load-template').on('click', this.loadQueryTemplate.bind(this));
            
            // Query execution
            $('#execute-query').on('click', this.executeQuery.bind(this));
            $('#clear-query').on('click', this.clearQuery.bind(this));
            
            // Error log actions
            $('#refresh-error-log').on('click', this.refreshErrorLog.bind(this));
            $('#clear-error-log').on('click', this.clearErrorLog.bind(this));
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Ctrl+Enter to execute query
                if (e.ctrlKey && e.keyCode === 13) {
                    e.preventDefault();
                    GraphQLTesting.executeQuery();
                }
                
                // Ctrl+Shift+C to clear query
                if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
                    e.preventDefault();
                    GraphQLTesting.clearQuery();
                }
            });
        },
        
        // Test GraphQL connection
        testConnection: function() {
            const $button = $('#test-connection');
            const $status = $('#connection-status');
            
            // Update UI
            $button.prop('disabled', true).text(hsmGraphQLTesting.strings.testing);
            $status.removeClass('connected disconnected error').addClass('checking');
            $status.find('.status-dot').removeClass('connected disconnected error').addClass('checking');
            $status.find('.status-text').text(hsmGraphQLTesting.strings.testing);
            
            // Make AJAX request
            $.ajax({
                url: hsmGraphQLTesting.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hsm_test_graphql_connection',
                    nonce: hsmGraphQLTesting.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.removeClass('checking').addClass('connected');
                        $status.find('.status-dot').removeClass('checking').addClass('connected');
                        $status.find('.status-text').text(hsmGraphQLTesting.strings.success + ' (' + response.data.response_time + 'ms)');
                    } else {
                        $status.removeClass('checking').addClass('disconnected');
                        $status.find('.status-dot').removeClass('checking').addClass('disconnected');
                        $status.find('.status-text').text(hsmGraphQLTesting.strings.error + ': ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    $status.removeClass('checking').addClass('error');
                    $status.find('.status-dot').removeClass('checking').addClass('error');
                    $status.find('.status-text').text(hsmGraphQLTesting.strings.error + ': ' + error);
                },
                complete: function() {
                    $button.prop('disabled', false).text(hsmGraphQLTesting.strings.connectionTest);
                }
            });
        },
        
        // Load query templates
        loadQueryTemplates: function() {
            $.ajax({
                url: hsmGraphQLTesting.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hsm_get_query_templates',
                    nonce: hsmGraphQLTesting.nonce
                },
                success: function(response) {
                    if (response.success) {
                        GraphQLTesting.templates = response.data;
                    }
                }
            });
        },
        
        // Load selected query template
        loadQueryTemplate: function() {
            const template = $('#query-template').val();
            
            if (!template || !this.templates || !this.templates[template]) {
                return;
            }
            
            const templateData = this.templates[template];
            
            // Load query
            $('#graphql-query').val(templateData.query);
            
            // Load variables
            $('#graphql-variables').val(templateData.variables);
            
            // Highlight syntax
            this.highlightSyntax();
        },
        
        // Execute GraphQL query
        executeQuery: function() {
            const query = $('#graphql-query').val().trim();
            const variables = $('#graphql-variables').val().trim();
            const $button = $('#execute-query');
            const $results = $('#query-results');
            const $responseTime = $('#response-time');
            
            if (!query) {
                alert('Please enter a GraphQL query.');
                return;
            }
            
            // Update UI
            $button.prop('disabled', true).text(hsmGraphQLTesting.strings.testing);
            $results.html('<code>' + hsmGraphQLTesting.strings.testing + '...</code>');
            $responseTime.text('');
            
            // Make AJAX request
            $.ajax({
                url: hsmGraphQLTesting.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hsm_execute_graphql_query',
                    nonce: hsmGraphQLTesting.nonce,
                    query: query,
                    variables: variables
                },
                success: function(response) {
                    if (response.success) {
                        const result = JSON.stringify(response.data.result, null, 2);
                        $results.html('<code>' + GraphQLTesting.escapeHtml(result) + '</code>');
                        $responseTime.text(response.data.response_time + 'ms');
                        $('.hsm-results-container').removeClass('error').addClass('success');
                        
                        // Highlight syntax
                        GraphQLTesting.highlightSyntax();
                    } else {
                        const error = response.data.message || 'Unknown error occurred';
                        $results.html('<code style="color: #dc3545;">Error: ' + GraphQLTesting.escapeHtml(error) + '</code>');
                        $('.hsm-results-container').removeClass('success').addClass('error');
                    }
                },
                error: function(xhr, status, error) {
                    $results.html('<code style="color: #dc3545;">AJAX Error: ' + GraphQLTesting.escapeHtml(error) + '</code>');
                    $('.hsm-results-container').removeClass('success').addClass('error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(hsmGraphQLTesting.strings.queryExecution);
                }
            });
        },
        
        // Clear query and variables
        clearQuery: function() {
            $('#graphql-query').val('');
            $('#graphql-variables').val('{}');
            $('#query-template').val('');
            $('#query-results').html('<code>' + hsmGraphQLTesting.strings.results + '...</code>');
            $('#response-time').text('');
            $('.hsm-results-container').removeClass('success error');
        },
        
        // Refresh error log
        refreshErrorLog: function() {
            // This would typically fetch recent error logs from the server
            // For now, we'll just show a placeholder
            $('#error-log-content').html('<code>Error log refreshed at ' + new Date().toLocaleTimeString() + '</code>');
        },
        
        // Clear error log
        clearErrorLog: function() {
            $('#error-log-content').html('<code>No errors...</code>');
        },
        
        // Highlight JSON syntax
        highlightSyntax: function() {
            $('.hsm-results-content code').each(function() {
                const $this = $(this);
                let content = $this.html();
                
                // Basic JSON syntax highlighting
                content = content.replace(/"([^"]+)":/g, '<span class="json-key">"$1":</span>');
                content = content.replace(/: "([^"]*)"/g, ': <span class="json-string">"$1"</span>');
                content = content.replace(/: (\d+)/g, ': <span class="json-number">$1</span>');
                content = content.replace(/: (true|false)/g, ': <span class="json-boolean">$1</span>');
                content = content.replace(/: null/g, ': <span class="json-null">null</span>');
                
                $this.html(content);
            });
        },
        
        // Escape HTML for safe display
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        GraphQLTesting.init();
    });
    
})(jQuery);