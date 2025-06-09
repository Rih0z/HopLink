<?php
/**
 * Amazon Search Class (PA-API不要版)
 *
 * Web検索APIやスクレイピングを使用してAmazon商品を検索するクラス
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
 * HopLink_Amazon_Search クラス
 */
class HopLink_Amazon_Search {

    /**
     * 検索エンジンのエンドポイント
     *
     * @var string
     */
    private $search_endpoint = 'https://www.google.com/search';

    /**
     * キャッシュの有効期限（秒）
     *
     * @var int
     */
    private $cache_expiration = 86400; // 24時間

    /**
     * 商品名でAmazonを検索
     *
     * @param string $product_name 商品名
     * @param array $options 検索オプション
     * @return array 検索結果の配列
     */
    public function search_by_name($product_name, $options = []) {
        if (empty($product_name)) {
            return [];
        }

        // キャッシュチェック
        $cache_key = 'hoplink_amazon_search_' . md5($product_name . serialize($options));
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result;
        }

        // 検索クエリの構築
        $search_query = $this->build_search_query($product_name, $options);
        
        // Google検索を実行
        $search_results = $this->perform_google_search($search_query);
        
        // Amazon URLを抽出
        $amazon_products = $this->extract_amazon_products($search_results);
        
        // 結果をキャッシュ
        if (!empty($amazon_products)) {
            set_transient($cache_key, $amazon_products, $this->cache_expiration);
        }
        
        return $amazon_products;
    }

    /**
     * JANコードでAmazonを検索
     *
     * @param string $jan_code JANコード
     * @return array 検索結果の配列
     */
    public function search_by_jan($jan_code) {
        if (empty($jan_code)) {
            return [];
        }

        // JANコードを正規化
        $jan_code = preg_replace('/[^0-9]/', '', $jan_code);
        
        // Amazon内でJANコード検索
        $search_query = 'site:amazon.co.jp "' . $jan_code . '"';
        
        return $this->search_by_name($search_query, ['exact_match' => true]);
    }

    /**
     * 検索クエリを構築
     *
     * @param string $product_name 商品名
     * @param array $options オプション
     * @return string 検索クエリ
     */
    private function build_search_query($product_name, $options = []) {
        $query_parts = [];
        
        // サイト指定
        $query_parts[] = 'site:amazon.co.jp';
        
        // 商品名
        if (!empty($options['exact_match'])) {
            $query_parts[] = '"' . $product_name . '"';
        } else {
            $query_parts[] = $product_name;
        }
        
        // カテゴリ指定
        if (!empty($options['category'])) {
            $query_parts[] = $this->get_category_keyword($options['category']);
        }
        
        // 除外キーワード
        if (!empty($options['exclude'])) {
            foreach ((array)$options['exclude'] as $exclude) {
                $query_parts[] = '-' . $exclude;
            }
        }
        
        return implode(' ', $query_parts);
    }

    /**
     * カテゴリに対応するキーワードを取得
     *
     * @param string $category カテゴリ
     * @return string キーワード
     */
    private function get_category_keyword($category) {
        $category_map = [
            'beer' => 'ビール',
            'craft_beer' => 'クラフトビール',
            'glass' => 'ビアグラス',
            'book' => '本',
            'gift' => 'ギフトセット',
        ];
        
        return $category_map[$category] ?? '';
    }

    /**
     * Google検索を実行
     *
     * @param string $query 検索クエリ
     * @return string 検索結果のHTML
     */
    private function perform_google_search($query) {
        $args = [
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ];
        
        $search_url = add_query_arg([
            'q' => $query,
            'hl' => 'ja',
            'num' => 10,
        ], $this->search_endpoint);
        
        $response = wp_remote_get($search_url, $args);
        
        if (is_wp_error($response)) {
            return '';
        }
        
        return wp_remote_retrieve_body($response);
    }

    /**
     * 検索結果からAmazon商品を抽出
     *
     * @param string $html 検索結果のHTML
     * @return array Amazon商品の配列
     */
    private function extract_amazon_products($html) {
        if (empty($html)) {
            return [];
        }
        
        $products = [];
        
        // Amazon URLのパターン
        $pattern = '/<a[^>]+href=["\']([^"\']*amazon\.co\.jp[^"\']+)["\'][^>]*>([^<]+)<\/a>/i';
        
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $url = html_entity_decode($match[1]);
                $title = strip_tags(html_entity_decode($match[2]));
                
                // URLからASINを抽出
                if (class_exists('HopLink_URL_Parser')) {
                    $asin = HopLink_URL_Parser::extract_asin($url);
                    
                    if ($asin) {
                        $products[] = [
                            'asin' => $asin,
                            'title' => $title,
                            'url' => $url,
                            'source' => 'google_search',
                        ];
                    }
                }
                
                // 最大5件まで
                if (count($products) >= 5) {
                    break;
                }
            }
        }
        
        return $products;
    }

    /**
     * DuckDuckGo APIを使用した代替検索
     *
     * @param string $query 検索クエリ
     * @return array 検索結果
     */
    public function search_with_duckduckgo($query) {
        $api_url = 'https://api.duckduckgo.com/';
        
        $args = [
            'timeout' => 10,
        ];
        
        $api_params = [
            'q' => $query . ' site:amazon.co.jp',
            'format' => 'json',
            'no_html' => 1,
            'skip_disambig' => 1,
        ];
        
        $response = wp_remote_get(add_query_arg($api_params, $api_url), $args);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$data || empty($data['RelatedTopics'])) {
            return [];
        }
        
        $products = [];
        
        foreach ($data['RelatedTopics'] as $topic) {
            if (isset($topic['FirstURL']) && strpos($topic['FirstURL'], 'amazon.co.jp') !== false) {
                $products[] = [
                    'url' => $topic['FirstURL'],
                    'title' => $topic['Text'] ?? '',
                    'source' => 'duckduckgo',
                ];
            }
        }
        
        return $products;
    }

    /**
     * 商品情報を詳細化（スクレイピング）
     *
     * @param array $product 基本商品情報
     * @return array 詳細な商品情報
     */
    public function enrich_product_info($product) {
        if (empty($product['url'])) {
            return $product;
        }
        
        // キャッシュチェック
        $cache_key = 'hoplink_amazon_product_' . md5($product['url']);
        $cached_info = get_transient($cache_key);
        
        if ($cached_info !== false) {
            return array_merge($product, $cached_info);
        }
        
        $args = [
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ];
        
        $response = wp_remote_get($product['url'], $args);
        
        if (is_wp_error($response)) {
            return $product;
        }
        
        $html = wp_remote_retrieve_body($response);
        
        // 商品情報を抽出
        $enriched_info = $this->parse_amazon_page($html);
        
        if (!empty($enriched_info)) {
            set_transient($cache_key, $enriched_info, $this->cache_expiration);
            return array_merge($product, $enriched_info);
        }
        
        return $product;
    }

    /**
     * AmazonページのHTMLをパース
     *
     * @param string $html HTML
     * @return array 抽出された商品情報
     */
    private function parse_amazon_page($html) {
        $info = [];
        
        // 商品タイトル
        if (preg_match('/<span[^>]+id="productTitle"[^>]*>([^<]+)<\/span>/i', $html, $matches)) {
            $info['title'] = trim(strip_tags($matches[1]));
        }
        
        // 価格
        if (preg_match('/<span[^>]+class="[^"]*a-price-whole[^"]*"[^>]*>([^<]+)<\/span>/i', $html, $matches)) {
            $info['price'] = preg_replace('/[^0-9]/', '', $matches[1]);
        }
        
        // 商品画像
        if (preg_match('/<img[^>]+id="landingImage"[^>]+src="([^"]+)"/i', $html, $matches)) {
            $info['image_url'] = $matches[1];
        }
        
        // 在庫状況
        if (preg_match('/<span[^>]+class="[^"]*availability[^"]*"[^>]*>([^<]+)<\/span>/i', $html, $matches)) {
            $info['availability'] = trim(strip_tags($matches[1]));
        }
        
        // レビュー数
        if (preg_match('/<span[^>]+id="acrCustomerReviewText"[^>]*>([0-9,]+)[^<]*<\/span>/i', $html, $matches)) {
            $info['review_count'] = preg_replace('/[^0-9]/', '', $matches[1]);
        }
        
        // 評価
        if (preg_match('/<span[^>]+class="[^"]*a-icon-star[^"]*"[^>]*>([0-9.]+)[^<]*<\/span>/i', $html, $matches)) {
            $info['rating'] = floatval($matches[1]);
        }
        
        return $info;
    }

    /**
     * 楽天商品情報から類似のAmazon商品を検索
     *
     * @param array $rakuten_product 楽天商品情報
     * @return array Amazon商品の候補
     */
    public function find_similar_products($rakuten_product) {
        $candidates = [];
        
        // JANコードで検索
        if (!empty($rakuten_product['jan_code'])) {
            $jan_results = $this->search_by_jan($rakuten_product['jan_code']);
            $candidates = array_merge($candidates, $jan_results);
        }
        
        // 商品名で検索（JANコードがない場合または追加検索）
        if (empty($candidates) && !empty($rakuten_product['name'])) {
            // 商品名をクリーンアップ
            $clean_name = $this->clean_product_name($rakuten_product['name']);
            $name_results = $this->search_by_name($clean_name);
            $candidates = array_merge($candidates, $name_results);
        }
        
        // 重複を除去
        $unique_candidates = [];
        $seen_asins = [];
        
        foreach ($candidates as $candidate) {
            if (!empty($candidate['asin']) && !in_array($candidate['asin'], $seen_asins)) {
                $unique_candidates[] = $candidate;
                $seen_asins[] = $candidate['asin'];
            }
        }
        
        return $unique_candidates;
    }

    /**
     * 商品名をクリーンアップ
     *
     * @param string $name 商品名
     * @return string クリーンアップされた商品名
     */
    private function clean_product_name($name) {
        // 不要な記号や情報を除去
        $patterns = [
            '/【[^】]+】/',  // 【】内の情報
            '/\[[^\]]+\]/',  // []内の情報
            '/［[^］]+］/',  // ［］内の情報
            '/\([^)]+\)/',   // ()内の情報（ただし重要な情報は残す）
            '/送料無料/',
            '/ポイント\d+倍/',
            '/\d+%OFF/',
            '/セール/',
            '/在庫あり/',
        ];
        
        $clean_name = $name;
        foreach ($patterns as $pattern) {
            $clean_name = preg_replace($pattern, ' ', $clean_name);
        }
        
        // 連続するスペースを単一スペースに
        $clean_name = preg_replace('/\s+/', ' ', $clean_name);
        
        return trim($clean_name);
    }
}