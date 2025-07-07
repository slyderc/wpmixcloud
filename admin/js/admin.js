/**
 * WP Mixcloud Archives Admin JavaScript
 * 
 * Handles admin interface interactions and AJAX functionality.
 */

(function($) {
    'use strict';
    
    // AIDEV-NOTE: Admin interface controller object
    const WPMixcloudAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.setupFormValidation();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // AIDEV-NOTE: Clear cache button handler
            $('#wp-mixcloud-clear-cache').on('click', this.clearCache.bind(this));
            
            // AIDEV-NOTE: Account field validation on blur
            $('#mixcloud_account').on('blur', this.validateAccount.bind(this));
            
            // AIDEV-NOTE: Cache expiration change handler
            $('#cache_expiration').on('change', this.onCacheExpirationChange.bind(this));
        },
        
        /**
         * Setup form validation
         */
        setupFormValidation: function() {
            const $form = $('form[action="options.php"]');
            
            if ($form.length) {
                $form.on('submit', this.validateForm.bind(this));
            }
        },
        
        /**
         * Clear cache via AJAX
         */
        clearCache: function(e) {
            e.preventDefault();
            
            const $button = $('#wp-mixcloud-clear-cache');
            const $status = $('#wp-mixcloud-clear-cache-status');
            
            // AIDEV-NOTE: Prevent multiple clicks during processing
            if ($button.hasClass('loading')) {
                return;
            }
            
            // Update UI to show loading state
            $button.addClass('loading').prop('disabled', true);
            $status.removeClass('success error').text('');
            
            // AIDEV-NOTE: Perform AJAX request to clear cache
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_mixcloud_clear_cache',
                    nonce: wpMixcloudAdmin.clearCacheNonce
                },
                timeout: 30000, // 30 second timeout
                success: function(response) {
                    if (response.success) {
                        $status.addClass('success').text(response.data.message);
                        
                        // AIDEV-NOTE: Auto-hide success message after 3 seconds
                        setTimeout(function() {
                            $status.fadeOut('slow');
                        }, 3000);
                    } else {
                        $status.addClass('error').text(response.data.message || 'Unknown error occurred.');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Failed to clear cache.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    }
                    
                    $status.addClass('error').text(errorMessage);
                },
                complete: function() {
                    // Reset button state
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        },
        
        /**
         * Validate Mixcloud account field
         */
        validateAccount: function() {
            const $field = $('#mixcloud_account');
            const value = $field.val().trim();
            
            // AIDEV-NOTE: Remove @ symbol if present and validate format
            if (value.startsWith('@')) {
                $field.val(value.substring(1));
            }
            
            // Basic validation for allowed characters
            const validPattern = /^[a-zA-Z0-9_-]*$/;
            if (value && !validPattern.test(value)) {
                this.showFieldError($field, 'Only letters, numbers, underscores, and hyphens are allowed.');
                return false;
            }
            
            this.clearFieldError($field);
            return true;
        },
        
        /**
         * Handle cache expiration change
         */
        onCacheExpirationChange: function() {
            const $field = $('#cache_expiration');
            const value = parseInt($field.val());
            
            // AIDEV-NOTE: Provide user feedback for cache duration choices
            if (value <= 900) {
                this.showFieldInfo($field, 'Short cache duration - shows updates quickly but may impact performance.');
            } else if (value >= 14400) {
                this.showFieldInfo($field, 'Long cache duration - better performance but updates may be delayed.');
            } else {
                this.clearFieldInfo($field);
            }
        },
        
        /**
         * Validate entire form before submission
         */
        validateForm: function(e) {
            let isValid = true;
            
            // Validate account field
            if (!this.validateAccount()) {
                isValid = false;
            }
            
            // Validate days field
            const days = parseInt($('#default_days').val());
            if (days < 1 || days > 365) {
                this.showFieldError($('#default_days'), 'Days must be between 1 and 365.');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                this.scrollToFirstError();
            }
            
            return isValid;
        },
        
        /**
         * Show field error message
         */
        showFieldError: function($field, message) {
            this.clearFieldError($field);
            
            const $error = $('<div class="wp-mixcloud-field-error" style="color: #dc3232; font-size: 13px; margin-top: 5px;">' + 
                           message + '</div>');
            $field.after($error);
            $field.css('border-color', '#dc3232');
        },
        
        /**
         * Clear field error message
         */
        clearFieldError: function($field) {
            $field.siblings('.wp-mixcloud-field-error').remove();
            $field.css('border-color', '');
        },
        
        /**
         * Show field info message
         */
        showFieldInfo: function($field, message) {
            this.clearFieldInfo($field);
            
            const $info = $('<div class="wp-mixcloud-field-info" style="color: #666; font-size: 12px; margin-top: 3px; font-style: italic;">' + 
                          message + '</div>');
            $field.closest('td').find('.description').after($info);
        },
        
        /**
         * Clear field info message
         */
        clearFieldInfo: function($field) {
            $field.closest('td').find('.wp-mixcloud-field-info').remove();
        },
        
        /**
         * Scroll to first error on page
         */
        scrollToFirstError: function() {
            const $firstError = $('.wp-mixcloud-field-error').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 500);
            }
        }
    };
    
    // AIDEV-NOTE: Initialize when document is ready
    $(document).ready(function() {
        // Only initialize on plugin admin pages
        if ($('.wp-mixcloud-archives-admin').length || $('#wp-mixcloud-clear-cache').length) {
            WPMixcloudAdmin.init();
        }
    });
    
    // AIDEV-NOTE: Make admin object available globally for debugging
    window.WPMixcloudAdmin = WPMixcloudAdmin;
    
})(jQuery);