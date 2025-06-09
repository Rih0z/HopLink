<?php
/**
 * Blocks Registration Class
 *
 * Gutenbergブロックの登録と管理
 *
 * @package HopLink
 * @since   1.0.0
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HopLink_Blocks クラス
 */
class HopLink_Blocks {

    /**
     * コンストラクタ
     */
    public function __construct() {
        $this->init();
    }

    /**
     * 初期化
     */
    private function init() {
        // ブロックの登録
        add_action('init', [$this, 'register_blocks']);
        
        // REST APIエンドポイントの登録
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // ブロックカテゴリーの追加
        add_filter('block_categories_all', [$this, 'add_block_category'], 10, 2);
    }

    /**
     * ブロックを登録
     */
    public function register_blocks() {
        // Amazon Linkブロックを登録
        register_block_type(
            HOPLINK_PLUGIN_PATH . 'blocks/amazon-link',
            [
                'render_callback' => [$this, 'render_amazon_link_block'],
            ]
        );
    }

    /**
     * Amazon Linkブロックのレンダリングコールバック
     *
     * @param array $attributes ブロック属性
     * @param string $content ブロックコンテンツ
     * @return string レンダリングされたHTML
     */
    public function render_amazon_link_block($attributes, $content) {
        // ショートコードハンドラーをロード
        if (!class_exists('HopLink_URL_Shortcode')) {
            require_once HOPLINK_PLUGIN_PATH . 'includes/class-hoplink-url-shortcode.php';
        }

        // ショートコードを構築
        $shortcode_atts = [
            'url' => $attributes['url'] ?? '',
            'text' => $attributes['linkText'] ?? '',
            'button' => $attributes['buttonStyle'] ? 'true' : 'false',
            'show_price' => $attributes['showPrice'] ? 'true' : 'false',
            'show_image' => $attributes['showImage'] ? 'true' : 'false',
            'target' => $attributes['openInNewTab'] ? '_blank' : '_self',
        ];

        // ショートコードを実行
        $shortcode_handler = new HopLink_URL_Shortcode();
        $output = $shortcode_handler->render_shortcode($shortcode_atts);

        // ブロックラッパーで囲む
        $wrapper_attributes = get_block_wrapper_attributes([
            'class' => 'hoplink-amazon-block',
        ]);

        return sprintf(
            '<div %s>%s</div>',
            $wrapper_attributes,
            $output
        );
    }

    /**
     * REST APIルートを登録
     */
    public function register_rest_routes() {
        // URL検証エンドポイント
        register_rest_route('hoplink/v1', '/validate-url', [
            'methods' => 'POST',
            'callback' => [$this, 'validate_url_callback'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'url' => [
                    'required' => true,
                    'sanitize_callback' => 'esc_url_raw',
                ],
            ],
        ]);

        // 商品情報取得エンドポイント
        register_rest_route('hoplink/v1', '/product-info/(?P<asin>[A-Z0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_product_info_callback'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'asin' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return preg_match('/^[A-Z0-9]{10}$/i', $param);
                    },
                ],
            ],
        ]);
    }

    /**
     * URL検証コールバック
     *
     * @param WP_REST_Request $request リクエストオブジェクト
     * @return WP_REST_Response レスポンス
     */
    public function validate_url_callback($request) {
        $url = $request->get_param('url');

        // URLパーサーをロード
        if (!class_exists('HopLink_URL_Parser')) {
            require_once HOPLINK_PLUGIN_PATH . 'includes/class-hoplink-url-parser.php';
        }

        // Amazon URLかチェック
        if (!HopLink_URL_Parser::is_amazon_url($url)) {
            return new WP_REST_Response([
                'success' => false,
                'data' => [
                    'message' => '有効なAmazon URLではありません。',
                ],
            ], 400);
        }

        // ASINを抽出
        $asin = HopLink_URL_Parser::extract_asin($url);

        if (!$asin) {
            return new WP_REST_Response([
                'success' => false,
                'data' => [
                    'message' => 'ASINコードを抽出できませんでした。',
                ],
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'asin' => $asin,
                'url' => $url,
            ],
        ]);
    }

    /**
     * 商品情報取得コールバック
     *
     * @param WP_REST_Request $request リクエストオブジェクト
     * @return WP_REST_Response レスポンス
     */
    public function get_product_info_callback($request) {
        $asin = $request->get_param('asin');

        // 手動登録データベースから商品情報を取得
        $manual_products = get_option('hoplink_manual_products', []);
        
        foreach ($manual_products as $product) {
            if (isset($product['asin']) && $product['asin'] === $asin) {
                return new WP_REST_Response([
                    'success' => true,
                    'data' => $product,
                ]);
            }
        }

        // Amazon APIから取得する場合（オプション）
        if (class_exists('HopLink_Amazon_API')) {
            $api = new HopLink_Amazon_API();
            $product_info = $api->get_item($asin);
            
            if ($product_info) {
                return new WP_REST_Response([
                    'success' => true,
                    'data' => $product_info,
                ]);
            }
        }

        return new WP_REST_Response([
            'success' => false,
            'data' => [
                'message' => '商品情報が見つかりません。',
            ],
        ], 404);
    }

    /**
     * 権限チェック
     *
     * @return bool
     */
    public function check_permission() {
        return current_user_can('edit_posts');
    }

    /**
     * ブロックカテゴリーを追加
     *
     * @param array $categories 既存のカテゴリー
     * @param WP_Block_Editor_Context $context エディターコンテキスト
     * @return array 修正されたカテゴリー
     */
    public function add_block_category($categories, $context) {
        // HopLinkカテゴリーを追加
        return array_merge(
            [
                [
                    'slug' => 'hoplink',
                    'title' => __('HopLink', 'hoplink'),
                    'icon' => 'cart',
                ],
            ],
            $categories
        );
    }

    /**
     * ブロックエディター用のスクリプトをエンキュー
     */
    public static function enqueue_block_editor_assets() {
        // URLパーサーのローカライズ
        wp_localize_script(
            'hoplink-amazon-link-editor-script',
            'hoplinkBlockData',
            [
                'apiUrl' => home_url('/wp-json/hoplink/v1'),
                'nonce' => wp_create_nonce('wp_rest'),
                'associateTag' => get_option('hoplink_amazon_associate_tag', ''),
            ]
        );
    }
}