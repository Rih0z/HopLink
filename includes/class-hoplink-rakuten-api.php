<?php
/**
 * Rakuten API integration class
 */
class HopLink_Rakuten_API {
    
    /**
     * API credentials
     */
    private $application_id;
    private $affiliate_id;
    private $api_endpoint = 'https://app.rakuten.co.jp/services/api/IchibaItem/Search/20170706';
    
    /**
     * Debug mode
     */
    private $debug_mode = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->application_id = get_option('hoplink_rakuten_application_id');
        $this->affiliate_id = get_option('hoplink_rakuten_affiliate_id');
        $this->debug_mode = get_option('hoplink_debug_mode', false);
    }
    
    /**
     * Search for products
     * 
     * @param string $keywords Search keywords
     * @param array $options Additional options
     * @return array|WP_Error
     */
    public function search_items($keywords, $options = []) {
        if (empty($this->application_id)) {
            return new WP_Error('missing_credentials', '楽天APIのアプリケーションIDが設定されていません');
        }
        
        // Default options
        $defaults = [
            'hits' => 10,
            'page' => 1,
            'sort' => 'standard',
            'minPrice' => null,
            'maxPrice' => null,
            'genreId' => null,
            'tagId' => null
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        // Build request parameters
        $params = [
            'applicationId' => $this->application_id,
            'keyword' => $keywords,
            'hits' => $options['hits'],
            'page' => $options['page'],
            'sort' => $options['sort'],
            'formatVersion' => 2
        ];
        
        // Add affiliate ID if available
        if (!empty($this->affiliate_id)) {
            $params['affiliateId'] = $this->affiliate_id;
        }
        
        // Add price filters if specified
        if ($options['minPrice'] !== null) {
            $params['minPrice'] = intval($options['minPrice']);
        }
        if ($options['maxPrice'] !== null) {
            $params['maxPrice'] = intval($options['maxPrice']);
        }
        
        // Add genre filter
        if ($options['genreId'] !== null) {
            $params['genreId'] = $options['genreId'];
        }
        
        // Add tag filter
        if ($options['tagId'] !== null) {
            $params['tagId'] = $options['tagId'];
        }
        
        // Make API request
        $response = $this->make_request($params);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Parse and format response
        return $this->format_search_response($response);
    }
    
    /**
     * Make API request
     * 
     * @param array $params Request parameters
     * @return array|WP_Error
     */
    private function make_request($params) {
        $url = add_query_arg($params, $this->api_endpoint);
        
        // Log debug info if enabled
        if ($this->debug_mode) {
            $this->log_debug('API Request', [
                'url' => $url,
                'params' => $params
            ]);
        }
        
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'sslverify' => true
        ]);
        
        if (is_wp_error($response)) {
            if ($this->debug_mode) {
                $this->log_debug('API Error', ['error' => $response->get_error_message()]);
            }
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Log response if debug mode
        if ($this->debug_mode) {
            $this->log_debug('API Response', [
                'code' => $response_code,
                'body' => $response_body
            ]);
        }
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error_description']) 
                ? $error_data['error_description'] 
                : '楽天APIエラー';
            
            return new WP_Error('api_error', $error_message, ['code' => $response_code, 'body' => $response_body]);
        }
        
        return json_decode($response_body, true);
    }
    
    /**
     * Format search response
     * 
     * @param array $response API response
     * @return array Formatted products
     */
    private function format_search_response($response) {
        $products = [];
        
        if (!isset($response['Items']) || empty($response['Items'])) {
            return $products;
        }
        
        foreach ($response['Items'] as $item) {
            $product = $this->format_item($item);
            if ($product) {
                $products[] = $product;
            }
        }
        
        return $products;
    }
    
    /**
     * Format individual item
     * 
     * @param array $item Raw item data
     * @return array|null Formatted product
     */
    private function format_item($item) {
        // 楽天APIのレスポンス構造に基づいてデータを抽出
        $item_code = $item['itemCode'] ?? '';
        $item_name = $item['itemName'] ?? '';
        $item_url = $item['itemUrl'] ?? '';
        $affiliate_url = $item['affiliateUrl'] ?? $item_url;
        
        if (empty($item_code) || empty($item_name)) {
            return null;
        }
        
        // 価格情報
        $price = $item['itemPrice'] ?? null;
        
        // 画像情報
        $image_urls = $item['mediumImageUrls'] ?? [];
        $main_image = '';
        if (!empty($image_urls) && isset($image_urls[0]['imageUrl'])) {
            $main_image = $image_urls[0]['imageUrl'];
        }
        
        // ショップ情報
        $shop_name = $item['shopName'] ?? '';
        $shop_url = $item['shopUrl'] ?? '';
        $shop_affiliate_url = $item['shopAffiliateUrl'] ?? $shop_url;
        
        // レビュー情報
        $review_count = $item['reviewCount'] ?? 0;
        $review_average = $item['reviewAverage'] ?? 0;
        
        // 配送情報
        $postage_flag = $item['postageFlag'] ?? 1; // 0:送料込み, 1:送料別
        $asuraku_flag = $item['asurakuFlag'] ?? 0; // あす楽対応
        
        return [
            'platform' => 'rakuten',
            'product_id' => $item_code,
            'title' => $item_name,
            'price' => $price,
            'price_currency' => 'JPY',
            'price_formatted' => $price ? '¥' . number_format($price) : '価格情報なし',
            'url' => $item_url,
            'affiliate_url' => $affiliate_url,
            'image_url' => $main_image,
            'images' => $image_urls,
            'shop_name' => $shop_name,
            'shop_url' => $shop_url,
            'shop_affiliate_url' => $shop_affiliate_url,
            'review_count' => $review_count,
            'review_average' => $review_average,
            'postage_flag' => $postage_flag,
            'shipping_info' => $postage_flag === 0 ? '送料込み' : '送料別',
            'asuraku_flag' => $asuraku_flag,
            'is_asuraku' => $asuraku_flag === 1,
            'raw_data' => $item // デバッグ用
        ];
    }
    
    /**
     * Log debug information
     * 
     * @param string $context Debug context
     * @param mixed $data Debug data
     */
    private function log_debug($context, $data) {
        if (!$this->debug_mode) {
            return;
        }
        
        $log_entry = sprintf(
            "[%s] HopLink Rakuten API - %s: %s\n",
            current_time('mysql'),
            $context,
            print_r($data, true)
        );
        
        error_log($log_entry, 3, WP_CONTENT_DIR . '/debug.log');
    }
    
    /**
     * Test API connection
     * 
     * @return array
     */
    public function test_connection() {
        $start_time = microtime(true);
        
        // Try to search for a simple keyword
        $result = $this->search_items('ビール', ['hits' => 1]);
        
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000, 2);
        
        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
                'execution_time' => $execution_time . 'ms',
                'error_data' => $result->get_error_data()
            ];
        }
        
        return [
            'success' => true,
            'message' => '楽天API接続成功',
            'execution_time' => $execution_time . 'ms',
            'items_found' => count($result),
            'sample_item' => isset($result[0]) ? $result[0]['title'] : null
        ];
    }
    
    /**
     * Get beer related genre IDs
     * 
     * @return array
     */
    public function get_beer_genre_ids() {
        return [
            '510901' => 'ビール',
            '510915' => '発泡酒',
            '510916' => '新ジャンル・第3のビール',
            '100316' => '地ビール',
            '510902' => '輸入ビール',
            '510903' => 'ノンアルコールビール'
        ];
    }
}