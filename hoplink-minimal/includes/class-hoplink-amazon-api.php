<?php
/**
 * Amazon PA-API v5 integration class
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

class HopLink_Amazon_API {
    
    /**
     * API credentials
     */
    private $access_key;
    private $secret_key;
    private $associate_tag;
    private $region;
    private $marketplace;
    private $endpoint;
    
    /**
     * API configuration
     */
    private $service = 'ProductAdvertisingAPI';
    private $api_version = '2022-08-01';
    private $algorithm = 'AWS4-HMAC-SHA256';
    
    /**
     * Debug mode
     */
    private $debug_mode = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->access_key = get_option('hoplink_amazon_access_key');
        $this->secret_key = get_option('hoplink_amazon_secret_key');
        $this->associate_tag = get_option('hoplink_amazon_associate_tag');
        $this->region = get_option('hoplink_amazon_region', 'ap-northeast-1');
        $this->marketplace = get_option('hoplink_amazon_marketplace', 'www.amazon.co.jp');
        $this->endpoint = 'webservices.amazon.co.jp'; // 日本のエンドポイント
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
        if (empty($this->access_key) || empty($this->secret_key) || empty($this->associate_tag)) {
            return new WP_Error('missing_credentials', 'Amazon API credentials are not configured');
        }
        
        // Default options
        $defaults = [
            'item_count' => 10,
            'min_price' => null,
            'max_price' => null,
            'merchant' => 'All',
            'sort_by' => 'Relevance'
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        // Build request payload
        $payload = [
            'Keywords' => $keywords,
            'ItemCount' => $options['item_count'],
            'PartnerTag' => $this->associate_tag,
            'PartnerType' => 'Associates',
            'Marketplace' => $this->marketplace,
            'Resources' => [
                'Images.Primary.Large',
                'Images.Primary.Medium',
                'Images.Primary.Small',
                'ItemInfo.Title',
                'ItemInfo.Features',
                'ItemInfo.ProductInfo',
                'ItemInfo.TechnicalInfo',
                'Offers.Listings.Price',
                'Offers.Listings.Availability.Message',
                'Offers.Listings.Condition',
                'Offers.Listings.DeliveryInfo.IsPrimeEligible'
            ]
        ];
        
        // Add price filters if specified
        if ($options['min_price'] !== null) {
            $payload['MinPrice'] = intval($options['min_price']);
        }
        if ($options['max_price'] !== null) {
            $payload['MaxPrice'] = intval($options['max_price']);
        }
        
        // Add merchant filter
        if ($options['merchant'] !== 'All') {
            $payload['Merchant'] = $options['merchant'];
        }
        
        // Add sort option
        if ($options['sort_by'] !== 'Relevance') {
            $payload['SortBy'] = $options['sort_by'];
        }
        
        // Make API request
        $response = $this->make_request('SearchItems', $payload);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Parse and format response
        return $this->format_search_response($response);
    }
    
    /**
     * Get item details by ASIN
     * 
     * @param array $asins Array of ASINs
     * @return array|WP_Error
     */
    public function get_items($asins) {
        if (empty($this->access_key) || empty($this->secret_key) || empty($this->associate_tag)) {
            return new WP_Error('missing_credentials', 'Amazon API credentials are not configured');
        }
        
        if (!is_array($asins)) {
            $asins = [$asins];
        }
        
        // Limit to 10 items per request
        $asins = array_slice($asins, 0, 10);
        
        $payload = [
            'ItemIds' => $asins,
            'PartnerTag' => $this->associate_tag,
            'PartnerType' => 'Associates',
            'Marketplace' => $this->marketplace,
            'Resources' => [
                'Images.Primary.Large',
                'Images.Primary.Medium',
                'Images.Primary.Small',
                'ItemInfo.Title',
                'ItemInfo.Features',
                'ItemInfo.ProductInfo',
                'ItemInfo.TechnicalInfo',
                'Offers.Listings.Price',
                'Offers.Listings.Availability.Message',
                'Offers.Listings.Condition',
                'Offers.Listings.DeliveryInfo.IsPrimeEligible'
            ]
        ];
        
        // Make API request
        $response = $this->make_request('GetItems', $payload);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Parse and format response
        return $this->format_items_response($response);
    }
    
    /**
     * Make API request
     * 
     * @param string $operation API operation
     * @param array $payload Request payload
     * @return array|WP_Error
     */
    private function make_request($operation, $payload) {
        $host = $this->endpoint;
        $uri_path = '/paapi5/' . strtolower($operation);
        $target = 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.' . $operation;
        
        // Convert payload to JSON
        $payload_json = wp_json_encode($payload);
        
        // Create canonical request headers
        $headers = [
            'content-type' => 'application/json; charset=utf-8',
            'content-encoding' => 'amz-1.0',
            'host' => $host,
            'x-amz-target' => $target,
            'x-amz-date' => gmdate('Ymd\THis\Z')
        ];
        
        // Sign the request
        $signed_headers = $this->sign_request($headers, $uri_path, $payload_json);
        
        // Make HTTP request
        $response = wp_remote_post('https://' . $host . $uri_path, [
            'headers' => $signed_headers,
            'body' => $payload_json,
            'timeout' => 30,
            'sslverify' => true,
            'data_format' => 'body'
        ]);
        
        // Log debug info if enabled
        if ($this->debug_mode) {
            $this->log_debug('API Request', [
                'operation' => $operation,
                'payload' => $payload,
                'headers' => $signed_headers,
                'endpoint' => 'https://' . $host . $uri_path
            ]);
        }
        
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
            $error_message = isset($error_data['Errors'][0]['Message']) 
                ? $error_data['Errors'][0]['Message'] 
                : 'Unknown API error';
            
            return new WP_Error('api_error', $error_message, ['code' => $response_code, 'body' => $response_body]);
        }
        
        return json_decode($response_body, true);
    }
    
    /**
     * Sign API request using AWS Signature Version 4
     * 
     * @param array $headers Request headers
     * @param string $uri_path URI path
     * @param string $payload Request payload
     * @return array Signed headers
     */
    private function sign_request($headers, $uri_path, $payload) {
        $method = 'POST';
        $query_string = '';
        
        // Create canonical headers
        ksort($headers);
        $canonical_headers = '';
        $signed_headers_list = [];
        
        foreach ($headers as $key => $value) {
            $canonical_headers .= $key . ':' . trim($value) . "\n";
            $signed_headers_list[] = $key;
        }
        
        $signed_headers_string = implode(';', $signed_headers_list);
        
        // Create canonical request
        $canonical_request = $method . "\n" .
            $uri_path . "\n" .
            $query_string . "\n" .
            $canonical_headers . "\n" .
            $signed_headers_string . "\n" .
            hash('sha256', $payload);
        
        // Create string to sign
        $date_stamp = substr($headers['x-amz-date'], 0, 8);
        $credential_scope = $date_stamp . '/' . $this->region . '/' . $this->service . '/aws4_request';
        
        $string_to_sign = $this->algorithm . "\n" .
            $headers['x-amz-date'] . "\n" .
            $credential_scope . "\n" .
            hash('sha256', $canonical_request);
        
        // Calculate signature
        $signing_key = $this->get_signature_key($date_stamp);
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
        
        // Add authorization header
        $headers['authorization'] = $this->algorithm . ' ' .
            'Credential=' . $this->access_key . '/' . $credential_scope . ', ' .
            'SignedHeaders=' . $signed_headers_string . ', ' .
            'Signature=' . $signature;
        
        return $headers;
    }
    
    /**
     * Get signature key
     * 
     * @param string $date_stamp Date stamp
     * @return string
     */
    private function get_signature_key($date_stamp) {
        $k_date = hash_hmac('sha256', $date_stamp, 'AWS4' . $this->secret_key, true);
        $k_region = hash_hmac('sha256', $this->region, $k_date, true);
        $k_service = hash_hmac('sha256', $this->service, $k_region, true);
        $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
        
        return $k_signing;
    }
    
    /**
     * Format search response
     * 
     * @param array $response API response
     * @return array Formatted products
     */
    private function format_search_response($response) {
        $products = [];
        
        if (!isset($response['SearchResult']['Items'])) {
            return $products;
        }
        
        foreach ($response['SearchResult']['Items'] as $item) {
            $product = $this->format_item($item);
            if ($product) {
                $products[] = $product;
            }
        }
        
        return $products;
    }
    
    /**
     * Format items response
     * 
     * @param array $response API response
     * @return array Formatted products
     */
    private function format_items_response($response) {
        $products = [];
        
        if (!isset($response['ItemsResult']['Items'])) {
            return $products;
        }
        
        foreach ($response['ItemsResult']['Items'] as $item) {
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
        // Extract basic information
        $asin = $item['ASIN'] ?? '';
        $title = $item['ItemInfo']['Title']['DisplayValue'] ?? '';
        $detail_page_url = $item['DetailPageURL'] ?? '';
        
        if (empty($asin) || empty($title)) {
            return null;
        }
        
        // Extract price
        $price = null;
        $price_currency = 'JPY';
        if (isset($item['Offers']['Listings'][0]['Price']['Amount'])) {
            $price = $item['Offers']['Listings'][0]['Price']['Amount'];
            $price_currency = $item['Offers']['Listings'][0]['Price']['Currency'] ?? 'JPY';
        }
        
        // Extract availability
        $availability = $item['Offers']['Listings'][0]['Availability']['Message'] ?? '在庫状況不明';
        $is_prime = $item['Offers']['Listings'][0]['DeliveryInfo']['IsPrimeEligible'] ?? false;
        
        // Extract images
        $images = [
            'large' => $item['Images']['Primary']['Large']['URL'] ?? '',
            'medium' => $item['Images']['Primary']['Medium']['URL'] ?? '',
            'small' => $item['Images']['Primary']['Small']['URL'] ?? ''
        ];
        
        // Extract features
        $features = [];
        if (isset($item['ItemInfo']['Features']['DisplayValues'])) {
            $features = $item['ItemInfo']['Features']['DisplayValues'];
        }
        
        return [
            'platform' => 'amazon',
            'product_id' => $asin,
            'title' => $title,
            'price' => $price,
            'price_currency' => $price_currency,
            'price_formatted' => $price ? '¥' . number_format($price) : '価格情報なし',
            'url' => $detail_page_url,
            'affiliate_url' => $detail_page_url, // PA-APIはすでにアフィリエイトタグ付きURLを返す
            'image_url' => $images['large'] ?: $images['medium'] ?: $images['small'],
            'images' => $images,
            'availability' => $availability,
            'is_prime' => $is_prime,
            'features' => $features,
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
            "[%s] HopLink Amazon API - %s: %s\n",
            current_time('mysql'),
            $context,
            print_r($data, true)
        );
        
        error_log($log_entry, 3, WP_CONTENT_DIR . '/debug.log');
    }
    
    /**
     * Test API connection
     * 
     * @return array|WP_Error
     */
    public function test_connection() {
        $start_time = microtime(true);
        
        // Try to search for a simple keyword
        $result = $this->search_items('beer', ['item_count' => 1]);
        
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
            'message' => 'Amazon PA-API connection successful',
            'execution_time' => $execution_time . 'ms',
            'items_found' => count($result),
            'sample_item' => isset($result[0]) ? $result[0]['title'] : null
        ];
    }
}