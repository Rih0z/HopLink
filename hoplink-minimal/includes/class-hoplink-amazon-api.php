<?php
/**
 * Amazon PA-API v5 実装クラス
 * 
 * このクラスはAmazon Product Advertising API v5を使用しています。
 * 認証キーは必ず https://affiliate.amazon.co.jp/assoc_credentials/home から取得してください。
 * 
 * @since 1.0.0
 */
class HopLink_Amazon_API {
    
    private $access_key;
    private $secret_key;
    private $partner_tag;
    private $host;
    private $region;
    private $debug_mode = false;
    private $last_request_time = 0;
    private $min_request_interval = 1000; // 1秒（ミリ秒）
    private $cache_duration = 86400; // 24時間（秒）
    private $max_retries = 3;
    private $base_retry_delay = 1000; // 1秒（ミリ秒）
    
    public function __construct() {
        $this->access_key = get_option('hoplink_amazon_access_key');
        $this->secret_key = get_option('hoplink_amazon_secret_key');
        $this->partner_tag = get_option('hoplink_amazon_partner_tag');
        $this->host = 'webservices.amazon.co.jp';
        $this->region = 'us-west-2'; // PA-API v5では全地域でus-west-2を使用
        
        // デバッグモードの設定
        $this->debug_mode = defined('HOPLINK_DEBUG') && HOPLINK_DEBUG;
        
        // レート制限設定の初期化
        $this->init_rate_limit_settings();
    }
    
    /**
     * レート制限設定の初期化
     */
    private function init_rate_limit_settings() {
        // ユーザー設定で上書き可能
        $user_min_interval = get_option('hoplink_api_min_interval', 1000);
        $user_cache_duration = get_option('hoplink_api_cache_duration', 86400);
        $user_max_retries = get_option('hoplink_api_max_retries', 3);
        
        $this->min_request_interval = max(1000, $user_min_interval); // 最低1秒は保証
        $this->cache_duration = max(3600, $user_cache_duration); // 最低1時間は保証
        $this->max_retries = max(1, min(5, $user_max_retries)); // 1-5回の範囲
        
        $this->debug_log('Rate limit settings initialized', array(
            'min_request_interval' => $this->min_request_interval,
            'cache_duration' => $this->cache_duration,
            'max_retries' => $this->max_retries
        ));
    }
    
    /**
     * デバッグモードの有効化/無効化
     */
    public function set_debug_mode($enabled) {
        $this->debug_mode = $enabled;
    }
    
    /**
     * 認証情報の検証
     */
    public function validate_credentials() {
        $issues = array();
        
        // アクセスキーの検証
        if (empty($this->access_key)) {
            $issues[] = 'Access key is empty';
        } elseif (strlen($this->access_key) !== 20) {
            $issues[] = 'Access key has invalid length (should be 20 characters)';
        } elseif (!preg_match('/^[A-Z0-9]{20}$/', $this->access_key)) {
            $issues[] = 'Access key has invalid format';
        }
        
        // シークレットキーの検証
        if (empty($this->secret_key)) {
            $issues[] = 'Secret key is empty';
        } elseif (strlen($this->secret_key) !== 40) {
            $issues[] = 'Secret key has invalid length (should be 40 characters)';
        }
        
        // パートナータグの検証
        if (empty($this->partner_tag)) {
            $issues[] = 'Partner tag is empty';
        } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $this->partner_tag)) {
            $issues[] = 'Partner tag has invalid format';
        }
        
        return $issues;
    }
    
    /**
     * デバッグログ出力
     */
    private function debug_log($message, $data = null) {
        if (!$this->debug_mode) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] [HopLink Amazon API] $message";
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $log_message .= ' | Data: ' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                $log_message .= ' | Data: ' . $data;
            }
        }
        error_log($log_message);
        
        // ファイルにも記録
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $log_file = WP_CONTENT_DIR . '/debug-hoplink-amazon.log';
            file_put_contents($log_file, $log_message . "\n", FILE_APPEND | LOCK_EX);
        } elseif (defined('WP_CONTENT_DIR')) {
            // デバッグログが無効でも専用ファイルに記録
            $log_file = WP_CONTENT_DIR . '/debug-hoplink-amazon.log';
            file_put_contents($log_file, $log_message . "\n", FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * 商品検索
     */
    public function search_items($keyword, $limit = 5) {
        $this->debug_log('search_items called', array('keyword' => $keyword, 'limit' => $limit));
        
        if (empty($this->access_key) || empty($this->secret_key) || empty($this->partner_tag)) {
            $this->debug_log('Missing API credentials', array(
                'has_access_key' => !empty($this->access_key),
                'has_secret_key' => !empty($this->secret_key),
                'has_partner_tag' => !empty($this->partner_tag)
            ));
            return array();
        }
        
        // キャッシュチェック（強化されたキャッシュ戦略）
        $cache_key = 'hoplink_amazon_' . md5($keyword . '_' . $limit);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            $this->debug_log('Cache hit for keyword', array('keyword' => $keyword, 'cache_key' => $cache_key));
            return $cached;
        }
        
        $this->debug_log('Cache miss - API request required', array('keyword' => $keyword, 'cache_key' => $cache_key));
        
        // リクエストパラメータ
        $payload = array(
            'Keywords' => $keyword,
            'SearchIndex' => 'All',
            'ItemCount' => min($limit, 10),
            'PartnerTag' => $this->partner_tag,
            'PartnerType' => 'Associates',
            'Marketplace' => 'www.amazon.co.jp',
            'Resources' => array(
                'ItemInfo.Title',
                'Offers.Listings.Price',
                'Images.Primary.Large',
                'CustomerReviews.StarRating',
                'CustomerReviews.Count'
            )
        );
        
        // API呼び出し（リトライ機能付き）
        $response = $this->call_api_with_retry('SearchItems', $payload);
        
        if (is_wp_error($response)) {
            // エラーの場合でも短時間キャッシュして連続リクエストを防ぐ
            set_transient($cache_key . '_error', true, 300); // 5分間
            return array();
        }
        
        $products = $this->parse_response($response);
        
        // キャッシュ保存（成功・失敗に関わらず）
        if (!empty($products)) {
            set_transient($cache_key, $products, $this->cache_duration);
            $this->debug_log('Products cached successfully', array(
                'count' => count($products),
                'cache_duration' => $this->cache_duration
            ));
        } else {
            // 空の結果もキャッシュして無駄なリクエストを防ぐ
            set_transient($cache_key, array(), 3600); // 1時間
            $this->debug_log('Empty result cached', array('cache_duration' => 3600));
        }
        
        return $products;
    }
    
    /**
     * レート制限制御
     */
    private function enforce_rate_limit() {
        $current_time = microtime(true) * 1000; // ミリ秒
        $time_since_last_request = $current_time - $this->last_request_time;
        
        if ($time_since_last_request < $this->min_request_interval) {
            $sleep_time = $this->min_request_interval - $time_since_last_request;
            $this->debug_log('Rate limit enforced', array(
                'sleep_time_ms' => $sleep_time,
                'time_since_last_request_ms' => $time_since_last_request,
                'min_interval_ms' => $this->min_request_interval
            ));
            usleep($sleep_time * 1000); // マイクロ秒に変換
        }
        
        $this->last_request_time = microtime(true) * 1000;
    }
    
    /**
     * リトライ機能付きAPI呼び出し
     */
    private function call_api_with_retry($operation, $payload) {
        $retry_count = 0;
        
        while ($retry_count <= $this->max_retries) {
            // レート制限制御
            $this->enforce_rate_limit();
            
            $response = $this->call_api($operation, $payload);
            
            if (!is_wp_error($response)) {
                $response_code = wp_remote_retrieve_response_code($response);
                
                // 成功またはリトライ不要なエラー
                if ($response_code === 200 || !$this->should_retry($response_code)) {
                    return $response;
                }
                
                // リトライ対象のエラー
                if ($this->should_retry($response_code) && $retry_count < $this->max_retries) {
                    $delay = $this->calculate_retry_delay($retry_count);
                    $this->debug_log('API request failed, retrying', array(
                        'response_code' => $response_code,
                        'retry_count' => $retry_count + 1,
                        'max_retries' => $this->max_retries,
                        'delay_ms' => $delay
                    ));
                    usleep($delay * 1000); // マイクロ秒に変換
                }
            }
            
            $retry_count++;
        }
        
        $this->debug_log('API request failed after all retries', array(
            'max_retries' => $this->max_retries,
            'operation' => $operation
        ));
        
        return $response;
    }
    
    /**
     * リトライすべきかの判定
     */
    private function should_retry($response_code) {
        // リトライ対象のHTTPステータスコード
        $retryable_codes = array(429, 500, 502, 503, 504);
        return in_array($response_code, $retryable_codes);
    }
    
    /**
     * リトライ遅延時間計算（指数バックオフ）
     */
    private function calculate_retry_delay($retry_count) {
        // 指数バックオフ: base_delay * (2 ^ retry_count) + ランダムジッター
        $exponential_delay = $this->base_retry_delay * pow(2, $retry_count);
        $jitter = rand(0, 1000); // 0-1秒のランダムジッター
        return $exponential_delay + $jitter;
    }
    
    /**
     * API呼び出し
     */
    private function call_api($operation, $payload) {
        $this->debug_log('call_api started', array('operation' => $operation));
        
        $path = '/paapi5/' . strtolower($operation);
        // PA-API v5の仕様に従い、タイムスタンプは20231215T123456Z形式
        // 重要: PA-API v5では、日本を含む全地域でus-west-2リージョンとProductAdvertisingAPIサービス名を使用
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        
        $this->debug_log('Request details', array(
            'path' => $path,
            'timestamp' => $timestamp,
            'timestamp_format_check' => preg_match('/^\d{8}T\d{6}Z$/', $timestamp) ? 'valid' : 'invalid',
            'date' => $date,
            'host' => $this->host,
            'region' => $this->region
        ));
        
        // リクエストヘッダー (PA-API v5仕様に準拠)
        // 署名計算では小文字、実際のHTTPリクエストでは大文字小文字を区別
        $headers = array(
            'content-encoding' => 'amz-1.0',
            'content-type' => 'application/json; charset=utf-8',
            'host' => $this->host,
            'x-amz-date' => $timestamp,
            'x-amz-target' => 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.' . $operation
        );
        
        // ヘッダーの正確性を検証
        $this->debug_log('Request headers validation', array(
            'x-amz-target_format' => 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.' . $operation,
            'content-type_correct' => $headers['content-type'] === 'application/json; charset=utf-8',
            'host_matches' => $headers['host'] === $this->host
        ));
        
        // ペイロードのJSON化
        $payload_json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->debug_log('Request payload', $payload);
        $this->debug_log('Request payload (JSON)', $payload_json);
        $this->debug_log('Payload length', strlen($payload_json));
        
        // 正規リクエスト作成
        $canonical_request = $this->create_canonical_request('POST', $path, '', $headers, $payload_json);
        $this->debug_log('Canonical request created', $canonical_request);
        
        // 署名作成
        $string_to_sign = $this->create_string_to_sign($timestamp, $date, $canonical_request);
        $this->debug_log('String to sign', $string_to_sign);
        
        $signature = $this->calculate_signature($date, $string_to_sign);
        $this->debug_log('Signature calculated', $signature);
        
        // Authorization ヘッダー
        $headers['Authorization'] = $this->create_authorization_header($date, $headers, $signature);
        $this->debug_log('Authorization header', $headers['Authorization']);
        
        // HTTPリクエスト
        $url = 'https://' . $this->host . $path;
        $this->debug_log('Request URL', $url);
        $this->debug_log('Request headers (for signature)', $headers);
        $this->debug_log('Authorization header details', array(
            'signature_calculated' => $signature,
            'access_key_used' => substr($this->access_key, 0, 4) . '...',
            'region' => $this->region,
            'service' => 'ProductAdvertisingAPI',
            'timestamp_used' => $timestamp
        ));
        
        // WordPressのHTTPヘッダーは大文字小文字を区別するため、適切な形式に変換
        $wp_headers = array(
            'Content-Encoding' => $headers['content-encoding'],
            'Content-Type' => $headers['content-type'],
            'Host' => $headers['host'],
            'X-Amz-Date' => $headers['x-amz-date'],
            'X-Amz-Target' => $headers['x-amz-target'],
            'Authorization' => $headers['Authorization']
        );
        
        $this->debug_log('Final HTTP headers', $wp_headers);
        
        $response = wp_remote_post($url, array(
            'headers' => $wp_headers,
            'body' => $payload_json,
            'timeout' => 30,
            'httpversion' => '1.1',
            'sslverify' => true
        ));
        
        // レスポンスのデバッグ
        if (is_wp_error($response)) {
            $this->debug_log('Request failed (WP_Error)', $response->get_error_message());
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $response_headers = wp_remote_retrieve_headers($response);
            
            $this->debug_log('Response code', $response_code);
            $this->debug_log('Response headers', $response_headers);
            $this->debug_log('Response body', $response_body);
            
            // エラーレスポンスの詳細解析
            if ($response_code !== 200) {
                $error_data = json_decode($response_body, true);
                if (isset($error_data['Errors'])) {
                    $this->debug_log('API Errors detected', $error_data['Errors']);
                    foreach ($error_data['Errors'] as $error) {
                        $this->debug_log('Error detail', array(
                            'Code' => $error['Code'] ?? 'Unknown',
                            'Message' => $error['Message'] ?? 'No message',
                            'Type' => $error['Type'] ?? 'Unknown'
                        ));
                    }
                } else if (isset($error_data['__type'])) {
                    $this->debug_log('AWS Error Type', $error_data['__type']);
                    $this->debug_log('AWS Error Message', $error_data['message'] ?? 'No message');
                }
            }
        }
        
        return $response;
    }
    
    /**
     * 正規リクエスト作成
     */
    private function create_canonical_request($method, $path, $query, $headers, $payload) {
        $this->debug_log('Creating canonical request', array(
            'method' => $method,
            'path' => $path,
            'query' => $query,
            'headers_count' => count($headers)
        ));
        
        $canonical_headers = '';
        $signed_headers_array = array();
        
        // ヘッダーを小文字にしてソート
        $sorted_headers = array();
        foreach ($headers as $key => $value) {
            $sorted_headers[strtolower($key)] = trim($value);
        }
        ksort($sorted_headers);
        $this->debug_log('Sorted headers', $sorted_headers);
        
        foreach ($sorted_headers as $key => $value) {
            $canonical_headers .= $key . ':' . $value . "\n";
            $signed_headers_array[] = $key;
        }
        
        $signed_headers = implode(';', $signed_headers_array);
        
        $payload_hash = hash('sha256', $payload);
        $this->debug_log('Payload for hash', array(
            'payload_length' => strlen($payload),
            'payload_first_50_chars' => substr($payload, 0, 50) . '...',
            'payload_hash' => $payload_hash
        ));
        
        $canonical_request = implode("\n", array(
            $method,
            $path,
            $query,
            $canonical_headers,
            $signed_headers,
            $payload_hash
        ));
        
        $this->debug_log('Canonical request components', array(
            'method' => $method,
            'path' => $path,
            'query' => $query,
            'canonical_headers' => $canonical_headers,
            'signed_headers' => $signed_headers,
            'payload_hash' => $payload_hash
        ));
        
        return $canonical_request;
    }
    
    /**
     * 署名文字列作成
     */
    private function create_string_to_sign($timestamp, $date, $canonical_request) {
        // PA-API v5では、サービス名は'ProductAdvertisingAPI'を使用（PAPIではない）
        $credential_scope = $date . '/' . $this->region . '/ProductAdvertisingAPI/aws4_request';
        $canonical_request_hash = hash('sha256', $canonical_request);
        
        $string_to_sign = implode("\n", array(
            'AWS4-HMAC-SHA256',
            $timestamp,
            $credential_scope,
            $canonical_request_hash
        ));
        
        $this->debug_log('String to sign components', array(
            'algorithm' => 'AWS4-HMAC-SHA256',
            'timestamp' => $timestamp,
            'credential_scope' => $credential_scope,
            'canonical_request_hash' => $canonical_request_hash
        ));
        
        return $string_to_sign;
    }
    
    /**
     * 署名計算
     */
    private function calculate_signature($date, $string_to_sign) {
        $this->debug_log('Calculating signature', array('date' => $date));
        
        $k_date = hash_hmac('sha256', $date, 'AWS4' . $this->secret_key, true);
        $this->debug_log('k_date', bin2hex($k_date));
        
        $k_region = hash_hmac('sha256', $this->region, $k_date, true);
        $this->debug_log('k_region', bin2hex($k_region));
        
        // PA-API v5では、サービス名は'ProductAdvertisingAPI'を使用（PAAPIではない）
        $k_service = hash_hmac('sha256', 'ProductAdvertisingAPI', $k_region, true);
        $this->debug_log('k_service', bin2hex($k_service));
        
        $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
        $this->debug_log('k_signing', bin2hex($k_signing));
        
        $signature = hash_hmac('sha256', $string_to_sign, $k_signing);
        $this->debug_log('Final signature', $signature);
        
        return $signature;
    }
    
    /**
     * Authorizationヘッダー作成
     */
    private function create_authorization_header($date, $headers, $signature) {
        // ヘッダーキーを小文字にしてソート
        $signed_headers_array = array();
        foreach ($headers as $key => $value) {
            $signed_headers_array[] = strtolower($key);
        }
        sort($signed_headers_array);
        $signed_headers = implode(';', $signed_headers_array);
        
        // PA-API v5では、サービス名は'ProductAdvertisingAPI'を使用（PAPIではない）
        $credential_scope = $date . '/' . $this->region . '/ProductAdvertisingAPI/aws4_request';
        
        return sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $this->access_key,
            $credential_scope,
            $signed_headers,
            $signature
        );
    }
    
    /**
     * レスポンス解析
     */
    private function parse_response($response) {
        $this->debug_log('Parsing response');
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->debug_log('WP_Error in response', $error_message);
            error_log('HopLink Amazon API Error: ' . $error_message);
            return array();
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        $this->debug_log('Response parsing', array(
            'response_code' => $response_code,
            'body_length' => strlen($body),
            'json_decode_success' => $data !== null
        ));
        
        // エラーレスポンスの詳細チェック
        if ($response_code !== 200) {
            if (isset($data['Errors'])) {
                foreach ($data['Errors'] as $error) {
                    $error_details = array(
                        'Code' => $error['Code'] ?? 'Unknown',
                        'Message' => $error['Message'] ?? 'No message',
                        'Type' => $error['Type'] ?? 'Unknown'
                    );
                    $this->debug_log('API Error Details', $error_details);
                    
                    // AccessDenied エラーの特別処理
                    if ($error['Code'] === 'AccessDenied') {
                        error_log('HopLink Amazon API Error: アクセスが拒否されました。新規アソシエイトの場合は3件の売上が必要です。既存アソシエイトの場合は過去30日間の売上を確認してください。');
                    } else {
                        error_log('HopLink Amazon API Error: ' . $error['Code'] . ' - ' . $error['Message']);
                    }
                }
            } else {
                $this->debug_log('Non-200 response without error details', array(
                    'response_code' => $response_code,
                    'body' => $body
                ));
            }
            return array();
        }
        
        if (empty($data['SearchResult']['Items'])) {
            return array();
        }
        
        $products = array();
        foreach ($data['SearchResult']['Items'] as $item) {
            $product = array(
                'platform' => 'amazon',
                'asin' => $item['ASIN'] ?? '',
                'title' => $item['ItemInfo']['Title']['DisplayValue'] ?? 'タイトルなし',
                'price' => 0,
                'image' => '',
                'url' => '',
                'shop' => 'Amazon.co.jp',
                'review' => 0,
                'review_count' => 0
            );
            
            // 価格取得
            if (isset($item['Offers']['Listings'][0]['Price']['Amount'])) {
                $product['price'] = intval($item['Offers']['Listings'][0]['Price']['Amount']);
            }
            
            // 画像取得
            if (isset($item['Images']['Primary']['Large']['URL'])) {
                $product['image'] = $item['Images']['Primary']['Large']['URL'];
            }
            
            // URL生成
            $product['url'] = 'https://www.amazon.co.jp/dp/' . $product['asin'] . '?tag=' . $this->partner_tag;
            
            // レビュー情報
            if (isset($item['CustomerReviews']['StarRating']['Value'])) {
                $product['review'] = floatval($item['CustomerReviews']['StarRating']['Value']);
            }
            if (isset($item['CustomerReviews']['Count'])) {
                $product['review_count'] = intval($item['CustomerReviews']['Count']);
            }
            
            $products[] = $product;
        }
        
        return $products;
    }
}