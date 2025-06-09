<?php
/**
 * URL Parser Class
 *
 * Amazon URLからASINコードを抽出し、アフィリエイトリンクを生成するクラス
 *
 * @package HopLink
 * @since   1.0.0
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HopLink_URL_Parser クラス
 */
class HopLink_URL_Parser {

    /**
     * Amazon URLのパターン定義
     *
     * @var array
     */
    private static $amazon_patterns = [
        // 標準的な商品URL
        '/https?:\/\/(?:www\.)?amazon\.co\.jp\/dp\/([A-Z0-9]{10})/i',
        '/https?:\/\/(?:www\.)?amazon\.co\.jp\/gp\/product\/([A-Z0-9]{10})/i',
        // 商品名を含むURL
        '/https?:\/\/(?:www\.)?amazon\.co\.jp\/[^\/]+\/dp\/([A-Z0-9]{10})/i',
        // exec/obidos形式
        '/https?:\/\/(?:www\.)?amazon\.co\.jp\/exec\/obidos\/ASIN\/([A-Z0-9]{10})/i',
        // gp/aw/d形式（モバイル）
        '/https?:\/\/(?:www\.)?amazon\.co\.jp\/gp\/aw\/d\/([A-Z0-9]{10})/i',
        // 短縮URL
        '/https?:\/\/amzn\.to\/([a-zA-Z0-9]+)/i',
        '/https?:\/\/amzn\.asia\/d\/([A-Z0-9]{10})/i',
    ];

    /**
     * URLからASINを抽出
     *
     * @param string $url Amazon URL
     * @return string|false ASINコード、または抽出できない場合はfalse
     */
    public static function extract_asin($url) {
        if (empty($url)) {
            return false;
        }

        // 短縮URLの場合は展開を試みる
        if (strpos($url, 'amzn.to') !== false) {
            $expanded_url = self::expand_short_url($url);
            if ($expanded_url) {
                $url = $expanded_url;
            }
        }

        // 各パターンでマッチングを試みる
        foreach (self::$amazon_patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                // ASINの妥当性をチェック（10文字の英数字）
                if (isset($matches[1]) && preg_match('/^[A-Z0-9]{10}$/i', $matches[1])) {
                    return strtoupper($matches[1]);
                }
            }
        }

        return false;
    }

    /**
     * 短縮URLを展開
     *
     * @param string $short_url 短縮URL
     * @return string|false 展開されたURL、または展開できない場合はfalse
     */
    private static function expand_short_url($short_url) {
        $args = [
            'method' => 'HEAD',
            'timeout' => 5,
            'redirection' => 0,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; HopLink/1.0)',
            ],
        ];

        $response = wp_remote_request($short_url, $args);

        if (is_wp_error($response)) {
            return false;
        }

        $location = wp_remote_retrieve_header($response, 'location');
        
        if ($location) {
            // リダイレクト先URLをクリーンアップ
            $location = strtok($location, '?'); // クエリパラメータを除去
            return $location;
        }

        return false;
    }

    /**
     * ASINからアフィリエイトリンクを生成
     *
     * @param string $asin ASINコード
     * @param string $associate_tag アソシエイトタグ
     * @param array $params 追加パラメータ
     * @return string アフィリエイトリンクURL
     */
    public static function generate_affiliate_link($asin, $associate_tag = '', $params = []) {
        if (empty($asin)) {
            return '';
        }

        // アソシエイトタグが指定されていない場合は設定から取得
        if (empty($associate_tag)) {
            $associate_tag = get_option('hoplink_amazon_associate_tag', '');
        }

        if (empty($associate_tag)) {
            return '';
        }

        // 基本URLを構築
        $base_url = 'https://www.amazon.co.jp/dp/' . $asin;
        
        // パラメータを構築
        $query_params = [
            'tag' => $associate_tag,
        ];

        // 追加パラメータがある場合はマージ
        if (!empty($params)) {
            $query_params = array_merge($query_params, $params);
        }

        // URLを生成
        $affiliate_url = add_query_arg($query_params, $base_url);

        return $affiliate_url;
    }

    /**
     * URLがAmazon URLかどうかを判定
     *
     * @param string $url URL
     * @return bool Amazon URLの場合true
     */
    public static function is_amazon_url($url) {
        if (empty($url)) {
            return false;
        }

        $amazon_domains = [
            'amazon.co.jp',
            'www.amazon.co.jp',
            'amzn.to',
            'amzn.asia',
        ];

        $parsed_url = parse_url($url);
        
        if (!isset($parsed_url['host'])) {
            return false;
        }

        return in_array($parsed_url['host'], $amazon_domains, true);
    }

    /**
     * 記事内のAmazon URLを検出して配列で返す
     *
     * @param string $content 記事内容
     * @return array Amazon URLの配列
     */
    public static function find_amazon_urls($content) {
        if (empty($content)) {
            return [];
        }

        $urls = [];
        
        // href属性内のURLを検出
        $pattern = '/href=["\']([^"\']+)["\']|(?:^|\s)(https?:\/\/[^\s<]+)/i';
        
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches as $match_group) {
                foreach ($match_group as $url) {
                    if (!empty($url) && self::is_amazon_url($url)) {
                        $urls[] = $url;
                    }
                }
            }
        }

        // 重複を除去
        $urls = array_unique($urls);
        
        return array_values($urls);
    }

    /**
     * 記事内のAmazon URLをアフィリエイトリンクに変換
     *
     * @param string $content 記事内容
     * @param string $associate_tag アソシエイトタグ
     * @param bool $preserve_existing 既存のアフィリエイトリンクを保持するか
     * @return string 変換後の記事内容
     */
    public static function convert_amazon_urls($content, $associate_tag = '', $preserve_existing = true) {
        if (empty($content)) {
            return $content;
        }

        // Amazon URLを検出
        $amazon_urls = self::find_amazon_urls($content);

        foreach ($amazon_urls as $original_url) {
            // ASINを抽出
            $asin = self::extract_asin($original_url);
            
            if (!$asin) {
                continue;
            }

            // 既存のアフィリエイトリンクの場合
            if ($preserve_existing && strpos($original_url, 'tag=') !== false) {
                continue;
            }

            // アフィリエイトリンクを生成
            $affiliate_url = self::generate_affiliate_link($asin, $associate_tag);

            if (!empty($affiliate_url)) {
                // URLを置換（完全一致のみ）
                $content = str_replace(
                    ['"' . $original_url . '"', "'" . $original_url . "'"],
                    ['"' . $affiliate_url . '"', "'" . $affiliate_url . "'"],
                    $content
                );
            }
        }

        return $content;
    }

    /**
     * URLから商品情報を取得（オプション機能）
     *
     * @param string $url Amazon URL
     * @return array|false 商品情報の配列、または取得できない場合はfalse
     */
    public static function get_product_info($url) {
        $asin = self::extract_asin($url);
        
        if (!$asin) {
            return false;
        }

        // 手動登録データベースから商品情報を取得
        $manual_products = get_option('hoplink_manual_products', []);
        
        foreach ($manual_products as $product) {
            if (isset($product['asin']) && $product['asin'] === $asin) {
                return $product;
            }
        }

        // Amazon APIから取得する場合（オプション）
        if (class_exists('HopLink_Amazon_API')) {
            $api = new HopLink_Amazon_API();
            return $api->get_item($asin);
        }

        return false;
    }
}