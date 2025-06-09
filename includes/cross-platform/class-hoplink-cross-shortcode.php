<?php
/**
 * Cross Platform Shortcode Class
 *
 * クロスプラットフォーム用ショートコード [hoplink_cross] の実装
 *
 * @package HopLink
 * @subpackage Cross_Platform
 * @since 1.0.0
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HopLink_Cross_Shortcode クラス
 */
class HopLink_Cross_Shortcode {

    /**
     * コンストラクタ
     */
    public function __construct() {
        add_shortcode('hoplink_cross', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * ショートコードをレンダリング
     *
     * @param array $atts ショートコード属性
     * @param string $content コンテンツ
     * @return string HTMLコンテンツ
     */
    public function render_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'url' => '',              // 楽天URL
            'keyword' => '',          // 検索キーワード
            'jan' => '',              // JANコード
            'template' => 'default',  // テンプレート
            'match_mode' => 'normal', // マッチングモード
            'show_both' => 'true',    // 両方表示するか
            'cache' => 'true',        // キャッシュを使用するか
        ], $atts);

        // 商品データを取得
        $products = $this->get_products($atts);
        
        if (empty($products)) {
            return $this->render_no_products_message();
        }

        // テンプレートをレンダリング
        return $this->render_template($products, $atts);
    }

    /**
     * 商品データを取得
     *
     * @param array $atts ショートコード属性
     * @return array 商品データ
     */
    private function get_products($atts) {
        $products = [
            'rakuten' => null,
            'amazon' => null,
        ];

        // キャッシュキーを生成
        $cache_key = 'hoplink_cross_' . md5(serialize($atts));
        
        // キャッシュチェック
        if ($atts['cache'] === 'true') {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        // 楽天URLから検索
        if (!empty($atts['url'])) {
            $products = $this->search_by_rakuten_url($atts['url'], $atts['match_mode']);
        }
        // JANコードから検索
        elseif (!empty($atts['jan'])) {
            $products = $this->search_by_jan($atts['jan']);
        }
        // キーワードから検索
        elseif (!empty($atts['keyword'])) {
            $products = $this->search_by_keyword($atts['keyword']);
        }

        // キャッシュに保存
        if (!empty($products) && $atts['cache'] === 'true') {
            set_transient($cache_key, $products, 3600); // 1時間
        }

        return $products;
    }

    /**
     * 楽天URLから商品を検索
     *
     * @param string $url 楽天URL
     * @param string $match_mode マッチングモード
     * @return array 商品データ
     */
    private function search_by_rakuten_url($url, $match_mode) {
        // 楽天URLパーサーを使用
        $rakuten_parser = new HopLink_Rakuten_URL_Parser();
        $product_info = $rakuten_parser->extract_product_info($url);
        
        if (!$product_info) {
            return [];
        }

        // 楽天商品の詳細を取得
        $rakuten_product = $rakuten_parser->get_product_details($product_info);
        
        if (!$rakuten_product) {
            return [];
        }

        // Amazon商品を検索
        $amazon_search = new HopLink_Amazon_Search();
        $amazon_candidates = $amazon_search->find_similar_products($rakuten_product);

        // マッチング実行
        $matcher = new HopLink_Product_Matcher();
        $amazon_match = $matcher->match_products($rakuten_product, $amazon_candidates, $match_mode);

        return [
            'rakuten' => $rakuten_product,
            'amazon' => $amazon_match,
        ];
    }

    /**
     * JANコードから商品を検索
     *
     * @param string $jan JANコード
     * @return array 商品データ
     */
    private function search_by_jan($jan) {
        $products = [
            'rakuten' => null,
            'amazon' => null,
        ];

        // 楽天で検索
        if (class_exists('HopLink_Rakuten_API')) {
            $rakuten_api = new HopLink_Rakuten_API();
            $rakuten_results = $rakuten_api->search_by_jan($jan);
            if (!empty($rakuten_results['Items'][0])) {
                $products['rakuten'] = HopLink_Rakuten_URL_Parser::parse_api_response($rakuten_results['Items'][0]);
            }
        }

        // Amazonで検索
        $amazon_search = new HopLink_Amazon_Search();
        $amazon_results = $amazon_search->search_by_jan($jan);
        if (!empty($amazon_results[0])) {
            $products['amazon'] = $amazon_search->enrich_product_info($amazon_results[0]);
        }

        return $products;
    }

    /**
     * キーワードから商品を検索
     *
     * @param string $keyword キーワード
     * @return array 商品データ
     */
    private function search_by_keyword($keyword) {
        $products = [
            'rakuten' => null,
            'amazon' => null,
        ];

        // 楽天で検索
        if (class_exists('HopLink_Rakuten_API')) {
            $rakuten_api = new HopLink_Rakuten_API();
            $rakuten_results = $rakuten_api->search_items(['keyword' => $keyword]);
            if (!empty($rakuten_results['Items'][0])) {
                $products['rakuten'] = HopLink_Rakuten_URL_Parser::parse_api_response($rakuten_results['Items'][0]);
            }
        }

        // Amazonで検索
        $amazon_search = new HopLink_Amazon_Search();
        $amazon_results = $amazon_search->search_by_name($keyword);
        if (!empty($amazon_results[0])) {
            $products['amazon'] = $amazon_search->enrich_product_info($amazon_results[0]);
        }

        return $products;
    }

    /**
     * テンプレートをレンダリング
     *
     * @param array $products 商品データ
     * @param array $atts 属性
     * @return string HTML
     */
    private function render_template($products, $atts) {
        ob_start();
        
        // カスタムテンプレートをチェック
        $template_file = locate_template('hoplink/cross-platform-' . $atts['template'] . '.php');
        
        if (!$template_file) {
            // デフォルトテンプレートを使用
            $template_file = HOPLINK_PLUGIN_PATH . 'public/templates/cross-platform-default.php';
        }

        // テンプレート変数を設定
        $rakuten_product = $products['rakuten'] ?? null;
        $amazon_product = $products['amazon'] ?? null;
        $show_both = filter_var($atts['show_both'], FILTER_VALIDATE_BOOLEAN);

        // アフィリエイトリンクを生成
        if ($rakuten_product) {
            $rakuten_product['affiliate_url'] = HopLink_Rakuten_URL_Parser::generate_affiliate_link(
                $rakuten_product['url'] ?? ''
            );
        }
        
        if ($amazon_product) {
            $amazon_product['affiliate_url'] = HopLink_URL_Parser::generate_affiliate_link(
                $amazon_product['asin'] ?? ''
            );
        }

        // テンプレートを読み込み
        include $template_file;
        
        return ob_get_clean();
    }

    /**
     * 商品が見つからない場合のメッセージをレンダリング
     *
     * @return string HTML
     */
    private function render_no_products_message() {
        return '<div class="hoplink-cross-no-products">' . 
               __('商品が見つかりませんでした。', 'hoplink') . 
               '</div>';
    }

    /**
     * スクリプトとスタイルをエンキュー
     */
    public function enqueue_scripts() {
        if (!is_admin()) {
            // CSS
            wp_enqueue_style(
                'hoplink-cross-platform',
                HOPLINK_PLUGIN_URL . 'public/assets/css/cross-platform.css',
                [],
                HOPLINK_VERSION
            );

            // JavaScript
            wp_enqueue_script(
                'hoplink-cross-platform',
                HOPLINK_PLUGIN_URL . 'public/assets/js/cross-platform.js',
                ['jquery'],
                HOPLINK_VERSION,
                true
            );

            // ローカライズ
            wp_localize_script('hoplink-cross-platform', 'hoplink_cross', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hoplink_cross_nonce'),
            ]);
        }
    }

    /**
     * AJAX: 商品情報を更新
     */
    public function ajax_update_product_info() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hoplink_cross_nonce')) {
            wp_die('Security check failed');
        }

        $product_id = sanitize_text_field($_POST['product_id'] ?? '');
        $platform = sanitize_text_field($_POST['platform'] ?? '');

        // 商品情報を更新
        $updated_info = $this->update_single_product($product_id, $platform);

        wp_send_json_success($updated_info);
    }

    /**
     * 単一商品の情報を更新
     *
     * @param string $product_id 商品ID
     * @param string $platform プラットフォーム
     * @return array 更新された商品情報
     */
    private function update_single_product($product_id, $platform) {
        // 実装は各プラットフォームのAPIに依存
        return [];
    }
}