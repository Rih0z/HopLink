<?php
/**
 * Rakuten URL Parser Class
 *
 * 楽天URLから商品IDやJANコードを抽出し、商品情報を取得するクラス
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
 * HopLink_Rakuten_URL_Parser クラス
 */
class HopLink_Rakuten_URL_Parser {

    /**
     * 楽天URLのパターン定義
     *
     * @var array
     */
    private static $rakuten_patterns = [
        // 標準的な商品URL
        '/https?:\/\/item\.rakuten\.co\.jp\/([^\/]+)\/([^\/\?]+)/i',
        // 楽天市場の商品ページ
        '/https?:\/\/www\.rakuten\.co\.jp\/([^\/]+)\/([^\/\?]+)/i',
        // 短縮URL
        '/https?:\/\/r10\.to\/([a-zA-Z0-9]+)/i',
        // 楽天ブックス
        '/https?:\/\/books\.rakuten\.co\.jp\/rb\/(\d+)/i',
        // 商品コードを含むURL
        '/https?:\/\/search\.rakuten\.co\.jp\/.*[\?&]item_id=([^&]+)/i',
    ];

    /**
     * URLから商品情報を抽出
     *
     * @param string $url 楽天URL
     * @return array|false 商品情報の配列、または抽出できない場合はfalse
     */
    public static function extract_product_info($url) {
        if (empty($url)) {
            return false;
        }

        // 短縮URLの場合は展開を試みる
        if (strpos($url, 'r10.to') !== false) {
            $expanded_url = self::expand_short_url($url);
            if ($expanded_url) {
                $url = $expanded_url;
            }
        }

        $product_info = [
            'shop_code' => '',
            'item_code' => '',
            'item_id' => '',
            'url' => $url,
        ];

        // 標準的な商品URLパターン
        if (preg_match('/https?:\/\/item\.rakuten\.co\.jp\/([^\/]+)\/([^\/\?]+)/i', $url, $matches)) {
            $product_info['shop_code'] = $matches[1];
            $product_info['item_code'] = $matches[2];
            $product_info['item_id'] = $matches[1] . ':' . $matches[2];
            return $product_info;
        }

        // 楽天ブックス
        if (preg_match('/https?:\/\/books\.rakuten\.co\.jp\/rb\/(\d+)/i', $url, $matches)) {
            $product_info['item_code'] = $matches[1];
            $product_info['item_id'] = $matches[1];
            $product_info['is_books'] = true;
            return $product_info;
        }

        // 商品IDパラメータから
        if (preg_match('/[\?&]item_id=([^&]+)/i', $url, $matches)) {
            $product_info['item_id'] = urldecode($matches[1]);
            if (strpos($product_info['item_id'], ':') !== false) {
                list($shop, $item) = explode(':', $product_info['item_id'], 2);
                $product_info['shop_code'] = $shop;
                $product_info['item_code'] = $item;
            }
            return $product_info;
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
            return $location;
        }

        return false;
    }

    /**
     * 楽天APIを使用して商品の詳細情報を取得
     *
     * @param array $product_info 商品情報
     * @return array|false 詳細な商品情報、または取得できない場合はfalse
     */
    public static function get_product_details($product_info) {
        if (!class_exists('HopLink_Rakuten_API')) {
            return false;
        }

        $api = new HopLink_Rakuten_API();
        
        // 商品コードで検索
        if (!empty($product_info['item_code'])) {
            $search_params = [
                'itemCode' => $product_info['shop_code'] . ':' . $product_info['item_code'],
            ];
            
            $result = $api->search_items($search_params);
            
            if ($result && isset($result['Items'][0])) {
                return self::parse_api_response($result['Items'][0]);
            }
        }

        return false;
    }

    /**
     * 楽天API レスポンスをパース
     *
     * @param array $item API レスポンスのアイテム
     * @return array パースされた商品情報
     */
    private static function parse_api_response($item) {
        $product = [
            'name' => $item['itemName'] ?? '',
            'price' => $item['itemPrice'] ?? 0,
            'url' => $item['itemUrl'] ?? '',
            'image_url' => $item['mediumImageUrls'][0]['imageUrl'] ?? '',
            'shop_name' => $item['shopName'] ?? '',
            'shop_code' => $item['shopCode'] ?? '',
            'item_code' => $item['itemCode'] ?? '',
            'jan_code' => '',
            'description' => $item['itemCaption'] ?? '',
            'availability' => $item['availability'] ?? 1,
            'review_count' => $item['reviewCount'] ?? 0,
            'review_average' => $item['reviewAverage'] ?? 0,
        ];

        // JANコードの抽出（商品説明や商品名から）
        $jan_code = self::extract_jan_code($product['name'] . ' ' . $product['description']);
        if ($jan_code) {
            $product['jan_code'] = $jan_code;
        }

        return $product;
    }

    /**
     * テキストからJANコードを抽出
     *
     * @param string $text テキスト
     * @return string|false JANコード、または見つからない場合はfalse
     */
    public static function extract_jan_code($text) {
        // JAN-13（13桁）のパターン
        if (preg_match('/\b(49\d{11}|45\d{11})\b/', $text, $matches)) {
            return $matches[1];
        }
        
        // JAN-8（8桁）のパターン
        if (preg_match('/\b(49\d{6}|45\d{6})\b/', $text, $matches)) {
            return $matches[1];
        }

        // JANコード: という記載がある場合
        if (preg_match('/JAN[コード]*[:：]\s*(\d{8,13})/', $text, $matches)) {
            return $matches[1];
        }

        return false;
    }

    /**
     * URLが楽天URLかどうかを判定
     *
     * @param string $url URL
     * @return bool 楽天URLの場合true
     */
    public static function is_rakuten_url($url) {
        if (empty($url)) {
            return false;
        }

        $rakuten_domains = [
            'item.rakuten.co.jp',
            'www.rakuten.co.jp',
            'search.rakuten.co.jp',
            'books.rakuten.co.jp',
            'r10.to',
        ];

        $parsed_url = parse_url($url);
        
        if (!isset($parsed_url['host'])) {
            return false;
        }

        foreach ($rakuten_domains as $domain) {
            if (strpos($parsed_url['host'], $domain) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 楽天アフィリエイトリンクを生成
     *
     * @param string $url 元のURL
     * @param string $affiliate_id アフィリエイトID
     * @return string アフィリエイトリンク
     */
    public static function generate_affiliate_link($url, $affiliate_id = '') {
        if (empty($url)) {
            return '';
        }

        // アフィリエイトIDが指定されていない場合は設定から取得
        if (empty($affiliate_id)) {
            $affiliate_id = get_option('hoplink_rakuten_affiliate_id', '');
        }

        if (empty($affiliate_id)) {
            return $url;
        }

        // 既にアフィリエイトIDが含まれている場合は置換
        if (strpos($url, 'scid=') !== false) {
            $url = preg_replace('/scid=[^&]+/', 'scid=' . $affiliate_id, $url);
        } else {
            // URLにアフィリエイトIDを追加
            $separator = (strpos($url, '?') !== false) ? '&' : '?';
            $url .= $separator . 'scid=' . $affiliate_id;
        }

        return $url;
    }

    /**
     * 記事内の楽天URLを検出して配列で返す
     *
     * @param string $content 記事内容
     * @return array 楽天URLの配列
     */
    public static function find_rakuten_urls($content) {
        if (empty($content)) {
            return [];
        }

        $urls = [];
        
        // href属性内のURLを検出
        $pattern = '/href=["\']([^"\']+)["\']|(?:^|\s)(https?:\/\/[^\s<]+)/i';
        
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches as $match_group) {
                foreach ($match_group as $url) {
                    if (!empty($url) && self::is_rakuten_url($url)) {
                        $urls[] = $url;
                    }
                }
            }
        }

        // 重複を除去
        $urls = array_unique($urls);
        
        return array_values($urls);
    }
}