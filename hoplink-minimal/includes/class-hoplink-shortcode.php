<?php
/**
 * ショートコード管理クラス
 */
class HopLink_Shortcode {
    
    private $api;
    
    public function __construct() {
        $this->api = new HopLink_API();
    }
    
    /**
     * ショートコード登録
     */
    public function register() {
        add_shortcode('hoplink', array($this, 'render_shortcode'));
        add_shortcode('hoplink_auto', array($this, 'render_auto_shortcode'));
    }
    
    /**
     * ショートコードレンダリング
     * 使用例: [hoplink keyword="クラフトビール" platform="all" limit="3"]
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'keyword' => '',
            'platform' => 'all', // all, rakuten, amazon
            'limit' => 3,
            'layout' => 'grid' // grid, list
        ), $atts);
        
        if (empty($atts['keyword'])) {
            return '<p>キーワードを指定してください。</p>';
        }
        
        // 商品検索
        $products = $this->api->search_all($atts['keyword'], $atts['platform']);
        
        if (empty($products)) {
            return '<p>商品が見つかりませんでした。</p>';
        }
        
        // 表示数制限
        $products = array_slice($products, 0, intval($atts['limit']));
        
        // HTML生成
        ob_start();
        ?>
        <div class="hoplink-container hoplink-<?php echo esc_attr($atts['layout']); ?> hoplink-ad-container">
            <div class="hoplink-products-grid">
            <?php foreach ($products as $product): ?>
                <div class="hoplink-product hoplink-ad-product">
                    <div class="hoplink-product-image">
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?php echo esc_url($product['image']); ?>" 
                                 alt="<?php echo esc_attr($product['title']); ?>"
                                 loading="lazy">
                        <?php endif; ?>
                    </div>
                    
                    <div class="hoplink-product-info">
                        <h3 class="hoplink-product-title">
                            <?php echo esc_html($this->truncate_title($product['title'], 50)); ?>
                        </h3>
                        
                        <div class="hoplink-product-meta">
                            <span class="hoplink-price">
                                ¥<?php echo number_format($product['price']); ?>
                            </span>
                            
                            <?php if ($product['review'] > 0): ?>
                                <span class="hoplink-review">
                                    ★<?php echo number_format($product['review'], 1); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="hoplink-product-shop">
                            <?php echo esc_html($product['shop']); ?>
                        </div>
                        
                        <a href="<?php echo esc_url($product['url']); ?>" 
                           class="hoplink-button"
                           target="_blank"
                           rel="sponsored noopener">
                            詳細を見る
                        </a>
                    </div>
                    
                    <div class="hoplink-platform-badge hoplink-<?php echo esc_attr($product['platform']); ?>">
                        <?php echo $product['platform'] === 'rakuten' ? '楽天' : 'Amazon'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        
        <div class="hoplink-disclosure">
            ※本記事はアフィリエイト広告を含んでいます。
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * タイトル省略
     */
    private function truncate_title($title, $length = 50) {
        if (mb_strlen($title) > $length) {
            return mb_substr($title, 0, $length) . '...';
        }
        return $title;
    }
    
    /**
     * 自動解析ショートコード
     * 使用例: [hoplink_auto] または [hoplink_auto limit="4" platform="all"]
     */
    public function render_auto_shortcode($atts) {
        global $post;
        
        if (!$post) {
            return '<p>記事内容を解析できませんでした。</p>';
        }
        
        $atts = shortcode_atts(array(
            'limit' => 4, // デフォルトを4に変更
            'platform' => 'all',
            'layout' => 'grid'
        ), $atts);
        
        // アナライザークラスを読み込み
        require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-analyzer.php';
        $analyzer = new HopLink_Analyzer();
        
        // 記事から商品を自動取得（多様化検索対応）
        $products = $analyzer->get_products_for_post($post->ID, intval($atts['limit']) * 2); // 余分に取得
        
        if (empty($products)) {
            // フォールバック: 高優先度キーワードによる検索
            $detailed_keywords = $analyzer->extract_keywords_detailed($post->post_title . ' ' . $post->post_content, 4);
            
            if (!empty($detailed_keywords)) {
                // キーワードマネージャーを使用してプラットフォーム別検索
                require_once HOPLINK_PLUGIN_DIR . 'includes/class-keyword-manager.php';
                $keyword_manager = new HopLink_Keyword_Manager();
                
                $all_fallback_products = array();
                
                // 楽天向け検索
                $rakuten_keywords = $keyword_manager->get_platform_optimized_keywords($detailed_keywords, 'rakuten');
                foreach (array_slice($rakuten_keywords, 0, 2) as $keyword) {
                    $results = $this->api->search_all($keyword, 'rakuten');
                    $all_fallback_products = array_merge($all_fallback_products, $results);
                }
                
                // Amazon向け検索
                $amazon_keywords = $keyword_manager->get_platform_optimized_keywords($detailed_keywords, 'amazon');
                foreach (array_slice($amazon_keywords, 0, 2) as $keyword) {
                    $results = $this->api->search_all($keyword, 'amazon');
                    $all_fallback_products = array_merge($all_fallback_products, $results);
                }
                
                $products = $all_fallback_products;
            }
        }
        
        // それでも見つからない場合は、従来のフォールバック検索
        if (empty($products)) {
            $extracted_keywords = $analyzer->extract_keywords($post->post_title . ' ' . $post->post_content, 5);
            $fallback_keywords = $analyzer->get_fallback_keywords($extracted_keywords);
            
            foreach ($fallback_keywords as $fallback_keyword) {
                $products = $this->api->search_all($fallback_keyword, $atts['platform']);
                if (!empty($products)) {
                    break;
                }
            }
        }
        
        // 最終的なフォールバック：設定されたキーワードまたはデフォルト
        if (empty($products)) {
            $fallback_keyword = get_option('hoplink_fallback_keyword', 'クラフトビール 贈り物');
            $products = $this->api->search_all($fallback_keyword, $atts['platform']);
        }
        
        if (empty($products)) {
            return '<p>関連する広告が見つかりませんでした。</p>';
        }
        
        // 楽天とAmazonの商品を分離
        $rakuten_products = array_filter($products, function($p) {
            return isset($p['platform']) && $p['platform'] === 'rakuten';
        });
        $amazon_products = array_filter($products, function($p) {
            return isset($p['platform']) && $p['platform'] === 'amazon';
        });
        
        // プラットフォーム多様化：各プラットフォームから均等に選択
        $selected_products = array();
        $target_limit = intval($atts['limit']);
        $per_platform = ceil($target_limit / 2);
        
        $rakuten_selected = array_slice($rakuten_products, 0, $per_platform);
        $amazon_selected = array_slice($amazon_products, 0, $per_platform);
        
        // 交互に選択して多様性を確保
        $max_length = max(count($rakuten_selected), count($amazon_selected));
        for ($i = 0; $i < $max_length && count($selected_products) < $target_limit; $i++) {
            if ($i < count($rakuten_selected)) {
                $selected_products[] = $rakuten_selected[$i];
            }
            if ($i < count($amazon_selected) && count($selected_products) < $target_limit) {
                $selected_products[] = $amazon_selected[$i];
            }
        }
        
        // 商品が不足している場合は調整
        if (count($selected_products) < $target_limit) {
            $remaining_products = array_diff_key($products, array_flip(array_keys($selected_products)));
            $additional_needed = $target_limit - count($selected_products);
            $selected_products = array_merge($selected_products, array_slice($remaining_products, 0, $additional_needed));
        }
        
        // 最終的な並び順を軽くシャッフル（完全ランダムではなく、関連度を維持）
        if (count($selected_products) > 2) {
            $first_half = array_slice($selected_products, 0, ceil(count($selected_products) / 2));
            $second_half = array_slice($selected_products, ceil(count($selected_products) / 2));
            shuffle($first_half);
            shuffle($second_half);
            $selected_products = array_merge($first_half, $second_half);
        }
        
        // HTML生成
        ob_start();
        ?>
        <div class="hoplink-container hoplink-<?php echo esc_attr($atts['layout']); ?> hoplink-auto hoplink-ad-container">
            <h3 class="hoplink-auto-title">関連する広告</h3>
            <div class="hoplink-products-grid">
                <?php foreach ($selected_products as $product): ?>
                    <div class="hoplink-product hoplink-ad-product">
                        <div class="hoplink-product-image">
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?php echo esc_url($product['image']); ?>" 
                                     alt="<?php echo esc_attr($product['title']); ?>"
                                     loading="lazy">
                            <?php endif; ?>
                        </div>
                        
                        <div class="hoplink-product-info">
                            <h3 class="hoplink-product-title">
                                <?php echo esc_html($this->truncate_title($product['title'], 50)); ?>
                            </h3>
                            
                            <div class="hoplink-product-meta">
                                <span class="hoplink-price">
                                    ¥<?php echo number_format($product['price']); ?>
                                </span>
                                
                                <?php if ($product['review'] > 0): ?>
                                    <span class="hoplink-review">
                                        ★<?php echo number_format($product['review'], 1); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="hoplink-product-shop">
                                <?php echo esc_html($product['shop']); ?>
                            </div>
                            
                            <a href="<?php echo esc_url($product['url']); ?>" 
                               class="hoplink-button"
                               target="_blank"
                               rel="sponsored noopener">
                                詳細を見る
                            </a>
                        </div>
                        
                        <div class="hoplink-platform-badge hoplink-<?php echo esc_attr($product['platform']); ?>">
                            <?php echo $product['platform'] === 'rakuten' ? '楽天' : 'Amazon'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="hoplink-disclosure">
            ※本記事はアフィリエイト広告を含んでいます。
        </div>
        <?php
        return ob_get_clean();
    }
}