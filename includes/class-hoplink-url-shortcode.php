<?php
/**
 * URL Shortcode Class
 *
 * [hoplink_url] ショートコードの実装
 *
 * @package HopLink
 * @since   1.0.0
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HopLink_URL_Shortcode クラス
 */
class HopLink_URL_Shortcode {

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
        // ショートコードを登録
        add_shortcode('hoplink_url', [$this, 'render_shortcode']);
    }

    /**
     * ショートコードをレンダリング
     *
     * @param array $atts ショートコード属性
     * @param string $content ショートコードの内容
     * @return string レンダリングされたHTML
     */
    public function render_shortcode($atts, $content = null) {
        // デフォルト属性
        $defaults = [
            'url' => '',
            'text' => '',
            'class' => 'hoplink-affiliate-link',
            'target' => '_blank',
            'rel' => 'nofollow sponsored noopener',
            'show_price' => 'false',
            'show_image' => 'false',
            'button' => 'false',
            'button_text' => 'Amazonで見る',
        ];

        // 属性をパース
        $atts = shortcode_atts($defaults, $atts, 'hoplink_url');

        // URLが指定されていない場合はエラー
        if (empty($atts['url'])) {
            return $this->render_error('URLが指定されていません。');
        }

        // URLパーサーをロード
        if (!class_exists('HopLink_URL_Parser')) {
            require_once HOPLINK_PLUGIN_PATH . 'includes/class-hoplink-url-parser.php';
        }

        // Amazon URLかどうかチェック
        if (!HopLink_URL_Parser::is_amazon_url($atts['url'])) {
            return $this->render_error('指定されたURLはAmazon URLではありません。');
        }

        // ASINを抽出
        $asin = HopLink_URL_Parser::extract_asin($atts['url']);
        
        if (!$asin) {
            return $this->render_error('ASINコードを抽出できませんでした。');
        }

        // アフィリエイトリンクを生成
        $affiliate_url = HopLink_URL_Parser::generate_affiliate_link($asin);
        
        if (empty($affiliate_url)) {
            return $this->render_error('アフィリエイトリンクの生成に失敗しました。アソシエイトタグを設定してください。');
        }

        // リンクテキストを決定
        $link_text = !empty($atts['text']) ? $atts['text'] : (!empty($content) ? $content : $atts['button_text']);

        // 商品情報を取得（オプション）
        $product_info = null;
        if ($atts['show_price'] === 'true' || $atts['show_image'] === 'true') {
            $product_info = HopLink_URL_Parser::get_product_info($atts['url']);
        }

        // HTMLを生成
        if ($atts['button'] === 'true') {
            return $this->render_button($affiliate_url, $link_text, $atts, $product_info);
        } else {
            return $this->render_link($affiliate_url, $link_text, $atts, $product_info);
        }
    }

    /**
     * 通常のリンクをレンダリング
     *
     * @param string $url アフィリエイトURL
     * @param string $text リンクテキスト
     * @param array $atts 属性
     * @param array|null $product_info 商品情報
     * @return string HTML
     */
    private function render_link($url, $text, $atts, $product_info = null) {
        $html = '<span class="hoplink-url-wrapper">';

        // 商品画像を表示
        if ($atts['show_image'] === 'true' && $product_info && isset($product_info['image_url'])) {
            $html .= sprintf(
                '<img src="%s" alt="%s" class="hoplink-product-image" loading="lazy">',
                esc_url($product_info['image_url']),
                esc_attr($product_info['name'] ?? $text)
            );
        }

        // リンクを生成
        $html .= sprintf(
            '<a href="%s" class="%s" target="%s" rel="%s" data-hoplink="true">%s</a>',
            esc_url($url),
            esc_attr($atts['class']),
            esc_attr($atts['target']),
            esc_attr($atts['rel']),
            esc_html($text)
        );

        // 価格を表示
        if ($atts['show_price'] === 'true' && $product_info && isset($product_info['price'])) {
            $html .= sprintf(
                '<span class="hoplink-price">%s</span>',
                esc_html($this->format_price($product_info['price']))
            );
        }

        $html .= '</span>';

        return $html;
    }

    /**
     * ボタンスタイルのリンクをレンダリング
     *
     * @param string $url アフィリエイトURL
     * @param string $text ボタンテキスト
     * @param array $atts 属性
     * @param array|null $product_info 商品情報
     * @return string HTML
     */
    private function render_button($url, $text, $atts, $product_info = null) {
        $html = '<div class="hoplink-button-wrapper">';

        // 商品情報を表示
        if ($product_info) {
            $html .= '<div class="hoplink-product-info">';
            
            // 商品画像
            if ($atts['show_image'] === 'true' && isset($product_info['image_url'])) {
                $html .= sprintf(
                    '<img src="%s" alt="%s" class="hoplink-product-image" loading="lazy">',
                    esc_url($product_info['image_url']),
                    esc_attr($product_info['name'] ?? $text)
                );
            }

            // 商品名
            if (isset($product_info['name'])) {
                $html .= sprintf(
                    '<h4 class="hoplink-product-name">%s</h4>',
                    esc_html($product_info['name'])
                );
            }

            // 価格
            if ($atts['show_price'] === 'true' && isset($product_info['price'])) {
                $html .= sprintf(
                    '<p class="hoplink-price">%s</p>',
                    esc_html($this->format_price($product_info['price']))
                );
            }

            $html .= '</div>';
        }

        // ボタンを生成
        $html .= sprintf(
            '<a href="%s" class="%s hoplink-button" target="%s" rel="%s" data-hoplink="true">%s</a>',
            esc_url($url),
            esc_attr($atts['class']),
            esc_attr($atts['target']),
            esc_attr($atts['rel']),
            esc_html($text)
        );

        $html .= '</div>';

        return $html;
    }

    /**
     * エラーメッセージをレンダリング
     *
     * @param string $message エラーメッセージ
     * @return string HTML
     */
    private function render_error($message) {
        // 管理者のみエラーを表示
        if (!current_user_can('manage_options')) {
            return '';
        }

        return sprintf(
            '<div class="hoplink-error">HopLink エラー: %s</div>',
            esc_html($message)
        );
    }

    /**
     * 価格をフォーマット
     *
     * @param int|float|string $price 価格
     * @return string フォーマットされた価格
     */
    private function format_price($price) {
        if (is_numeric($price)) {
            return '¥' . number_format((float)$price);
        }
        return $price;
    }

    /**
     * ショートコードの使用例を取得
     *
     * @return array 使用例の配列
     */
    public static function get_examples() {
        return [
            [
                'title' => '基本的な使用方法',
                'code' => '[hoplink_url url="https://www.amazon.co.jp/dp/B08XYZ1234"]',
                'description' => '指定したAmazon URLをアフィリエイトリンクに変換します。',
            ],
            [
                'title' => 'カスタムテキスト',
                'code' => '[hoplink_url url="https://www.amazon.co.jp/dp/B08XYZ1234" text="この商品を見る"]',
                'description' => 'リンクテキストをカスタマイズできます。',
            ],
            [
                'title' => 'ボタンスタイル',
                'code' => '[hoplink_url url="https://www.amazon.co.jp/dp/B08XYZ1234" button="true" button_text="Amazonで購入"]',
                'description' => 'ボタンスタイルで表示します。',
            ],
            [
                'title' => '商品情報付き',
                'code' => '[hoplink_url url="https://www.amazon.co.jp/dp/B08XYZ1234" show_price="true" show_image="true"]',
                'description' => '商品画像と価格を表示します（手動登録データから取得）。',
            ],
            [
                'title' => '短縮URL対応',
                'code' => '[hoplink_url url="https://amzn.to/3abc123"]',
                'description' => 'Amazon短縮URLも自動で展開して処理します。',
            ],
        ];
    }
}