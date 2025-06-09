<?php
/**
 * The public-facing functionality of the plugin
 */
class HopLink_Public {
    
    /**
     * Plugin version
     */
    private $version;
    
    /**
     * Constructor
     */
    public function __construct($version) {
        $this->version = $version;
    }
    
    /**
     * Register the stylesheets for the public-facing side
     */
    public function enqueue_styles() {
        wp_enqueue_style('hoplink-public', HOPLINK_PLUGIN_URL . 'public/assets/css/hoplink-public.css', [], $this->version, 'all');
    }
    
    /**
     * Register the JavaScript for the public-facing side
     */
    public function enqueue_scripts() {
        wp_enqueue_script('hoplink-public', HOPLINK_PLUGIN_URL . 'public/assets/js/hoplink-public.js', ['jquery'], $this->version, false);
        
        // Localize script for tracking
        wp_localize_script('hoplink-public', 'hoplink_public', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hoplink_public_nonce')
        ]);
    }
    
    /**
     * Shortcode handler
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function hoplink_shortcode($atts) {
        $atts = shortcode_atts([
            'keyword' => '',
            'category' => '',
            'max' => get_option('hoplink_max_products_per_article', 5),
            'platform' => 'amazon',
            'layout' => 'grid', // grid, list, carousel
            'product' => '', // specific product ID
        ], $atts, 'hoplink');
        
        // If specific product requested
        if (!empty($atts['product'])) {
            return $this->display_single_product($atts['product'], $atts['platform']);
        }
        
        // If keyword search
        if (!empty($atts['keyword'])) {
            return $this->display_keyword_products($atts['keyword'], $atts);
        }
        
        return '<p class="hoplink-error">Please specify a keyword or product ID.</p>';
    }
    
    /**
     * Manual shortcode handler
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function hoplink_manual_shortcode($atts) {
        $atts = shortcode_atts([
            'asin' => '',
            'asins' => '', // multiple ASINs comma-separated
            'keyword' => '',
            'category' => '',
            'max' => get_option('hoplink_max_products_per_article', 5),
            'layout' => 'grid', // grid, list, carousel
            'random' => false,
        ], $atts, 'hoplink_manual');
        
        // Initialize manual products manager
        $manual_products = new HopLink_Manual_Products();
        
        // If specific ASIN requested
        if (!empty($atts['asin'])) {
            $product = $manual_products->get_product_by_asin($atts['asin']);
            if ($product) {
                $formatted = $manual_products->format_product($product);
                return $this->render_products([$formatted], 'single');
            }
            return '<p class="hoplink-error">Product not found.</p>';
        }
        
        // If multiple ASINs requested
        if (!empty($atts['asins'])) {
            $asins = array_map('trim', explode(',', $atts['asins']));
            $products = [];
            
            foreach ($asins as $asin) {
                $product = $manual_products->get_product_by_asin($asin);
                if ($product) {
                    $products[] = $manual_products->format_product($product);
                }
            }
            
            if (!empty($products)) {
                return $this->render_products($products, $atts['layout']);
            }
            return '<p class="hoplink-error">No products found.</p>';
        }
        
        // If keyword search
        if (!empty($atts['keyword'])) {
            $products = $manual_products->search_products($atts['keyword'], intval($atts['max']));
            
            if (!empty($products)) {
                $formatted_products = array_map([$manual_products, 'format_product'], $products);
                
                // Random selection if requested
                if ($atts['random'] && count($formatted_products) > $atts['max']) {
                    shuffle($formatted_products);
                    $formatted_products = array_slice($formatted_products, 0, $atts['max']);
                }
                
                return $this->render_products($formatted_products, $atts['layout']);
            }
            return '<p class="hoplink-no-results">No products found for "' . esc_html($atts['keyword']) . '".</p>';
        }
        
        // If category search
        if (!empty($atts['category'])) {
            $products = $manual_products->get_products([
                'category' => $atts['category'],
                'limit' => intval($atts['max']),
                'status' => 'active'
            ]);
            
            if (!empty($products)) {
                $formatted_products = array_map([$manual_products, 'format_product'], $products);
                
                // Random selection if requested
                if ($atts['random'] && count($formatted_products) > $atts['max']) {
                    shuffle($formatted_products);
                    $formatted_products = array_slice($formatted_products, 0, $atts['max']);
                }
                
                return $this->render_products($formatted_products, $atts['layout']);
            }
            return '<p class="hoplink-no-results">No products found in category "' . esc_html($atts['category']) . '".</p>';
        }
        
        return '<p class="hoplink-error">Please specify an ASIN, keyword, or category.</p>';
    }
    
    /**
     * Display products by keyword
     * 
     * @param string $keyword Search keyword
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    private function display_keyword_products($keyword, $atts) {
        // Check cache first
        $cache_key = 'hoplink_' . md5($keyword . serialize($atts));
        $cached_html = get_transient($cache_key);
        
        if ($cached_html !== false && get_option('hoplink_cache_enabled', true)) {
            return $cached_html;
        }
        
        // Get products from API
        $products = $this->get_products_by_keyword($keyword, $atts);
        
        if (is_wp_error($products)) {
            return '<p class="hoplink-error">Error loading products: ' . $products->get_error_message() . '</p>';
        }
        
        if (empty($products)) {
            return '<p class="hoplink-no-results">No products found for "' . esc_html($keyword) . '".</p>';
        }
        
        // Generate HTML
        $html = $this->render_products($products, $atts['layout']);
        
        // Add compliance notices if enabled
        if (get_option('hoplink_affiliate_disclosure', true)) {
            $disclosure = '<div class="hoplink-disclosure">※ This content includes affiliate links.</div>';
            $html = $disclosure . $html;
        }
        
        // Cache the HTML
        if (get_option('hoplink_cache_enabled', true)) {
            $cache_duration = intval(get_option('hoplink_cache_duration', 604800));
            set_transient($cache_key, $html, $cache_duration);
        }
        
        return $html;
    }
    
    /**
     * Get products by keyword
     * 
     * @param string $keyword Search keyword
     * @param array $atts Attributes
     * @return array|WP_Error
     */
    private function get_products_by_keyword($keyword, $atts) {
        // Check Amazon mode setting
        $amazon_mode = get_option('hoplink_amazon_mode', 'api');
        
        if ($atts['platform'] === 'amazon') {
            // Manual mode only
            if ($amazon_mode === 'manual') {
                $manual_products = new HopLink_Manual_Products();
                $products = $manual_products->search_products($keyword, intval($atts['max']));
                
                if (!empty($products)) {
                    return array_map([$manual_products, 'format_product'], $products);
                }
                
                return [];
            }
            
            // Hybrid mode - check manual products first
            if ($amazon_mode === 'hybrid') {
                $manual_products = new HopLink_Manual_Products();
                $manual_results = $manual_products->search_products($keyword, intval($atts['max']));
                
                if (!empty($manual_results)) {
                    $formatted_manual = array_map([$manual_products, 'format_product'], $manual_results);
                    
                    // If we have enough manual products, return them
                    if (count($formatted_manual) >= intval($atts['max'])) {
                        return array_slice($formatted_manual, 0, intval($atts['max']));
                    }
                    
                    // Otherwise, try to get more from API
                    $needed = intval($atts['max']) - count($formatted_manual);
                    $api_products = $this->get_products_from_api($keyword, $atts['platform'], $needed);
                    
                    if (!is_wp_error($api_products)) {
                        return array_merge($formatted_manual, $api_products);
                    }
                    
                    return $formatted_manual;
                }
            }
            
            // API mode (default) or hybrid mode with no manual results
            return $this->get_products_from_api($keyword, $atts['platform'], intval($atts['max']));
        }
        
        // Other platforms (Rakuten, etc.)
        return $this->get_products_from_api($keyword, $atts['platform'], intval($atts['max']));
    }
    
    /**
     * Get products from API with caching
     * 
     * @param string $keyword Search keyword
     * @param string $platform Platform name
     * @param int $limit Number of products
     * @return array|WP_Error
     */
    private function get_products_from_api($keyword, $platform, $limit) {
        // Check database cache first
        global $wpdb;
        $table_name = $wpdb->prefix . 'hoplink_products_cache';
        
        $cache_key = md5($keyword . $platform);
        $cached = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE cache_key = %s AND platform = %s AND expires_at > NOW()",
            $cache_key,
            $platform
        ));
        
        if ($cached) {
            return json_decode($cached->product_data, true);
        }
        
        // Get from API
        if ($platform === 'amazon') {
            $api = new HopLink_Amazon_API();
            $products = $api->search_items($keyword, [
                'item_count' => $limit
            ]);
            
            if (!is_wp_error($products) && !empty($products)) {
                // Save to cache
                $wpdb->replace(
                    $table_name,
                    [
                        'cache_key' => $cache_key,
                        'platform' => $platform,
                        'product_data' => wp_json_encode($products),
                        'expires_at' => date('Y-m-d H:i:s', time() + intval(get_option('hoplink_cache_duration', 604800)))
                    ],
                    ['%s', '%s', '%s', '%s']
                );
            }
            
            return $products;
        }
        
        return new WP_Error('unsupported_platform', 'Platform not supported');
    }
    
    /**
     * Display single product
     * 
     * @param string $product_id Product ID
     * @param string $platform Platform name
     * @return string HTML output
     */
    private function display_single_product($product_id, $platform) {
        if ($platform === 'amazon') {
            $api = new HopLink_Amazon_API();
            $products = $api->get_items([$product_id]);
            
            if (is_wp_error($products)) {
                return '<p class="hoplink-error">Error loading product: ' . $products->get_error_message() . '</p>';
            }
            
            if (empty($products)) {
                return '<p class="hoplink-error">Product not found.</p>';
            }
            
            return $this->render_products($products, 'single');
        }
        
        return '<p class="hoplink-error">Platform not supported.</p>';
    }
    
    /**
     * Render products HTML
     * 
     * @param array $products Products array
     * @param string $layout Layout type
     * @return string HTML output
     */
    private function render_products($products, $layout = 'grid') {
        ob_start();
        
        echo '<div class="hoplink-products hoplink-layout-' . esc_attr($layout) . '">';
        
        foreach ($products as $product) {
            $this->render_product_card($product);
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Render single product card
     * 
     * @param array $product Product data
     */
    private function render_product_card($product) {
        ?>
        <div class="hoplink-product-card" data-product-id="<?php echo esc_attr($product['product_id']); ?>" data-platform="<?php echo esc_attr($product['platform']); ?>">
            <?php if ($product['image_url']): ?>
                <div class="hoplink-product-image">
                    <a href="<?php echo esc_url($product['affiliate_url']); ?>" target="_blank" rel="sponsored noopener" class="hoplink-product-link">
                        <img src="<?php echo esc_url($product['image_url']); ?>" alt="<?php echo esc_attr($product['title']); ?>" loading="lazy">
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="hoplink-product-content">
                <h3 class="hoplink-product-title">
                    <a href="<?php echo esc_url($product['affiliate_url']); ?>" target="_blank" rel="sponsored noopener" class="hoplink-product-link">
                        <?php echo esc_html($product['title']); ?>
                    </a>
                </h3>
                
                <div class="hoplink-product-price">
                    <?php echo esc_html($product['price_formatted']); ?>
                </div>
                
                <?php if ($product['is_prime']): ?>
                    <div class="hoplink-prime-badge">Prime</div>
                <?php endif; ?>
                
                <div class="hoplink-product-availability">
                    <?php echo esc_html($product['availability']); ?>
                </div>
                
                <?php if (!empty($product['features']) && is_array($product['features'])): ?>
                    <ul class="hoplink-product-features">
                        <?php foreach (array_slice($product['features'], 0, 3) as $feature): ?>
                            <li><?php echo esc_html($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <div class="hoplink-product-actions">
                    <a href="<?php echo esc_url($product['affiliate_url']); ?>" 
                       target="_blank" 
                       rel="sponsored noopener" 
                       class="hoplink-button hoplink-product-link">
                        View on Amazon
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}