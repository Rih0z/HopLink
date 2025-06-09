/**
 * Cross Platform Frontend JavaScript
 *
 * @package HopLink
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * HopLink Cross Platform
     */
    var HopLinkCrossPlatform = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.trackImpressions();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // アフィリエイトリンククリックの追跡
            $(document).on('click', '.hoplink-button', this.trackClick);
            
            // 画像の遅延読み込み
            this.lazyLoadImages();
            
            // ツールチップ
            this.initTooltips();
        },
        
        /**
         * Track link clicks
         */
        trackClick: function(e) {
            var $link = $(this);
            var productId = $link.data('product-id');
            var platform = $link.data('platform');
            
            if (!productId || !platform) {
                return;
            }
            
            // Track the click
            $.post(hoplink_cross.ajax_url, {
                action: 'hoplink_track_click',
                product_id: productId,
                platform: platform,
                nonce: hoplink_cross.nonce
            });
            
            // Google Analytics tracking (if available)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'click', {
                    'event_category': 'hoplink_affiliate',
                    'event_label': platform + '_' + productId
                });
            }
        },
        
        /**
         * Track product impressions
         */
        trackImpressions: function() {
            var products = [];
            
            $('.hoplink-product').each(function() {
                var $product = $(this);
                var productId = $product.find('.hoplink-button').data('product-id');
                var platform = $product.find('.hoplink-button').data('platform');
                
                if (productId && platform) {
                    products.push({
                        id: productId,
                        platform: platform
                    });
                }
            });
            
            if (products.length > 0) {
                // Send impression data
                $.post(hoplink_cross.ajax_url, {
                    action: 'hoplink_track_impressions',
                    products: products,
                    nonce: hoplink_cross.nonce
                });
            }
        },
        
        /**
         * Lazy load images
         */
        lazyLoadImages: function() {
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var image = entry.target;
                            image.src = image.dataset.src || image.src;
                            image.classList.remove('lazy');
                            imageObserver.unobserve(image);
                        }
                    });
                });
                
                var images = document.querySelectorAll('.hoplink-product-image img[loading="lazy"]');
                images.forEach(function(img) {
                    imageObserver.observe(img);
                });
            }
        },
        
        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('.hoplink-match-score').attr('title', 'この数値は楽天とAmazon商品の類似度を表します');
            
            // Simple tooltip implementation
            $('.hoplink-match-score').hover(
                function() {
                    var $this = $(this);
                    var tooltip = $('<div class="hoplink-tooltip">' + $this.attr('title') + '</div>');
                    $('body').append(tooltip);
                    
                    var pos = $this.offset();
                    tooltip.css({
                        top: pos.top - tooltip.outerHeight() - 5,
                        left: pos.left + ($this.outerWidth() / 2) - (tooltip.outerWidth() / 2)
                    }).fadeIn(200);
                },
                function() {
                    $('.hoplink-tooltip').remove();
                }
            );
        },
        
        /**
         * Update product info via AJAX
         */
        updateProductInfo: function(productId, platform) {
            var $product = $('.hoplink-' + platform + '-product').filter(function() {
                return $(this).find('.hoplink-button').data('product-id') === productId;
            });
            
            if ($product.length === 0) {
                return;
            }
            
            // Show loading state
            $product.addClass('hoplink-loading');
            
            $.post(hoplink_cross.ajax_url, {
                action: 'hoplink_update_product',
                product_id: productId,
                platform: platform,
                nonce: hoplink_cross.nonce
            }, function(response) {
                if (response.success && response.data) {
                    // Update price
                    if (response.data.price) {
                        $product.find('.hoplink-price-value').text('¥' + response.data.price.toLocaleString());
                    }
                    
                    // Update availability
                    if (response.data.availability) {
                        $product.find('.hoplink-availability-status').text(response.data.availability);
                    }
                }
            }).always(function() {
                $product.removeClass('hoplink-loading');
            });
        }
    };
    
    /**
     * Document ready
     */
    $(document).ready(function() {
        HopLinkCrossPlatform.init();
    });
    
    /**
     * Expose to global scope
     */
    window.HopLinkCrossPlatform = HopLinkCrossPlatform;

})(jQuery);