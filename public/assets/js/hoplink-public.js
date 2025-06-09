/**
 * HopLink Public JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Track product clicks
        $('.hoplink-product-link').on('click', function(e) {
            var $card = $(this).closest('.hoplink-product-card');
            var productId = $card.data('product-id');
            var platform = $card.data('platform');
            
            if (productId && platform) {
                // Send tracking data
                $.ajax({
                    url: hoplink_public.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'hoplink_track_click',
                        nonce: hoplink_public.nonce,
                        product_id: productId,
                        platform: platform,
                        post_id: hoplink_get_post_id()
                    }
                });
                
                // Google Analytics tracking if available
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'click', {
                        'event_category': 'hoplink',
                        'event_label': platform + '_' + productId
                    });
                }
            }
        });
        
        // Lazy load images
        if ('IntersectionObserver' in window) {
            var imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var image = entry.target;
                        image.src = image.dataset.src;
                        image.classList.remove('lazy');
                        imageObserver.unobserve(image);
                    }
                });
            });
            
            var lazyImages = document.querySelectorAll('.hoplink-product-image img.lazy');
            lazyImages.forEach(function(image) {
                imageObserver.observe(image);
            });
        }
    });
    
    // Helper function to get current post ID
    function hoplink_get_post_id() {
        // Try to get post ID from body class
        var bodyClass = $('body').attr('class');
        if (bodyClass) {
            var match = bodyClass.match(/postid-(\d+)/);
            if (match) {
                return match[1];
            }
        }
        
        // Try to get from article tag
        var $article = $('article[id^="post-"]');
        if ($article.length) {
            var id = $article.attr('id');
            return id.replace('post-', '');
        }
        
        return 0;
    }

})(jQuery);