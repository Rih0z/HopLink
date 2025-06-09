/**
 * Cross Platform Admin JavaScript
 *
 * @package HopLink
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * HopLink Cross Platform Admin
     */
    var HopLinkCrossPlatformAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Settings form submission
            $('#hoplink-cross-platform-settings-form').on('submit', this.saveSettings);
            
            // Test matching button
            $('#hoplink-test-match').on('click', this.testMatching);
            
            // Clear cache button
            $('#hoplink-clear-cache').on('click', this.clearCache);
            
            // Enter key on test URL input
            $('#test_url').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#hoplink-test-match').click();
                }
            });
        },
        
        /**
         * Save settings
         */
        saveSettings: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            var originalText = $submit.text();
            
            // Show loading state
            $submit.prop('disabled', true).text(hoplink_cross_admin.i18n.saving);
            
            // Remove any existing notices
            $('.hoplink-notice').remove();
            
            // Send AJAX request
            $.post(hoplink_cross_admin.ajax_url, $form.serialize() + '&action=hoplink_save_match_settings', function(response) {
                if (response.success) {
                    // Show success message
                    var notice = $('<div class="hoplink-notice success">' + hoplink_cross_admin.i18n.saved + '</div>');
                    $form.before(notice);
                    
                    // Auto-hide after 3 seconds
                    setTimeout(function() {
                        notice.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 3000);
                } else {
                    // Show error message
                    var errorMsg = response.data && response.data.message ? response.data.message : hoplink_cross_admin.i18n.error;
                    var notice = $('<div class="hoplink-notice error">' + errorMsg + '</div>');
                    $form.before(notice);
                }
            }).fail(function() {
                var notice = $('<div class="hoplink-notice error">' + hoplink_cross_admin.i18n.error + '</div>');
                $form.before(notice);
            }).always(function() {
                $submit.prop('disabled', false).text(originalText);
            });
        },
        
        /**
         * Test matching
         */
        testMatching: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $input = $('#test_url');
            var $results = $('#hoplink-test-results');
            var url = $input.val().trim();
            
            if (!url) {
                $input.focus();
                return;
            }
            
            // Show loading state
            $button.prop('disabled', true).text(hoplink_cross_admin.i18n.testing);
            $results.html('<div class="hoplink-loading"></div>').addClass('show');
            
            // Send AJAX request
            $.post(hoplink_cross_admin.ajax_url, {
                action: 'hoplink_test_matching',
                url: url,
                nonce: hoplink_cross_admin.nonce
            }, function(response) {
                if (response.success && response.data && response.data.html) {
                    $results.html(response.data.html);
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : hoplink_cross_admin.i18n.error;
                    $results.html('<div class="hoplink-test-no-match"><p>' + errorMsg + '</p></div>');
                }
            }).fail(function() {
                $results.html('<div class="hoplink-test-no-match"><p>' + hoplink_cross_admin.i18n.error + '</p></div>');
            }).always(function() {
                $button.prop('disabled', false).text('テスト実行');
            });
        },
        
        /**
         * Clear cache
         */
        clearCache: function(e) {
            e.preventDefault();
            
            if (!confirm('キャッシュをクリアしてもよろしいですか？')) {
                return;
            }
            
            var $button = $(this);
            var originalText = $button.text();
            
            // Show loading state
            $button.prop('disabled', true).text(hoplink_cross_admin.i18n.clearing);
            
            // Send AJAX request
            $.post(hoplink_cross_admin.ajax_url, {
                action: 'hoplink_clear_match_cache',
                nonce: hoplink_cross_admin.nonce
            }, function(response) {
                if (response.success) {
                    // Show success message
                    var notice = $('<div class="hoplink-notice success">' + hoplink_cross_admin.i18n.cleared + '</div>');
                    $button.closest('p').after(notice);
                    
                    // Auto-hide after 3 seconds
                    setTimeout(function() {
                        notice.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 3000);
                }
            }).fail(function() {
                alert(hoplink_cross_admin.i18n.error);
            }).always(function() {
                $button.prop('disabled', false).text(originalText);
            });
        }
    };
    
    /**
     * Document ready
     */
    $(document).ready(function() {
        HopLinkCrossPlatformAdmin.init();
    });

})(jQuery);