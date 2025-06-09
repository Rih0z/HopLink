<?php
/**
 * Amazon PA-API v5 デバッグクラス
 * 
 * PA-API v5の認証エラーやアクセス制限に関する詳細なデバッグ情報を提供します。
 * 
 * @since 1.0.0
 */
class HopLink_Amazon_Debug {
    
    /**
     * API認証情報の検証
     */
    public static function validate_credentials() {
        $access_key = get_option('hoplink_amazon_access_key');
        $secret_key = get_option('hoplink_amazon_secret_key');
        $partner_tag = get_option('hoplink_amazon_partner_tag');
        
        $issues = array();
        
        // アクセスキーの形式チェック（通常20文字）
        if (strlen($access_key) !== 20) {
            $issues[] = 'アクセスキーの長さが不正です（20文字である必要があります）';
        }
        
        // アクセスキーの形式チェック（AKで始まる）
        if (substr($access_key, 0, 2) !== 'AK') {
            $issues[] = 'アクセスキーの形式が不正です（AKで始まる必要があります）';
        }
        
        // シークレットキーの形式チェック（通常40文字）
        if (strlen($secret_key) !== 40) {
            $issues[] = 'シークレットキーの長さが不正です（40文字である必要があります）';
        }
        
        // パートナータグの形式チェック
        if (!preg_match('/^[a-zA-Z0-9\-]+$/', $partner_tag)) {
            $issues[] = 'パートナータグの形式が不正です';
        }
        
        return $issues;
    }
    
    /**
     * 基本診断（API呼び出しなし）
     */
    public static function basic_diagnosis() {
        $diagnosis = array();
        
        // サーバー情報
        $diagnosis['server_info'] = array(
            'php_version' => phpversion(),
            'curl_enabled' => extension_loaded('curl'),
            'openssl_enabled' => extension_loaded('openssl'),
            'json_enabled' => extension_loaded('json'),
            'wordpress_version' => get_bloginfo('version'),
            'timezone' => date_default_timezone_get(),
            'current_utc_time' => gmdate('Y-m-d H:i:s')
        );
        
        // API設定
        $diagnosis['api_config'] = array(
            'access_key_set' => !empty(get_option('hoplink_amazon_access_key')),
            'secret_key_set' => !empty(get_option('hoplink_amazon_secret_key')),
            'partner_tag_set' => !empty(get_option('hoplink_amazon_partner_tag')),
            'host' => 'webservices.amazon.co.jp',
            'region' => 'us-west-2'
        );
        
        // 認証情報チェック
        $access_key = get_option('hoplink_amazon_access_key');
        $secret_key = get_option('hoplink_amazon_secret_key');
        $partner_tag = get_option('hoplink_amazon_partner_tag');
        
        $diagnosis['credential_check'] = array(
            'access_key_set' => !empty($access_key),
            'access_key_length' => strlen($access_key),
            'access_key_format' => preg_match('/^[A-Z0-9]{20}$/', $access_key),
            'secret_key_set' => !empty($secret_key),
            'secret_key_length' => strlen($secret_key),
            'partner_tag_set' => !empty($partner_tag),
            'partner_tag_format' => preg_match('/^[a-zA-Z0-9\-]+$/', $partner_tag),
            'issues' => self::validate_credentials()
        );
        
        // 接続テストとAPIテストはスキップ
        $diagnosis['connectivity_test'] = array('skipped' => true, 'reason' => '頻度制限によりスキップ');
        $diagnosis['api_test'] = array('skipped' => true, 'reason' => '頻度制限によりスキップ');
        
        // 推奨事項
        $diagnosis['recommendations'] = array();
        
        if (!empty($diagnosis['credential_check']['issues'])) {
            $diagnosis['recommendations'][] = array(
                'category' => 'credentials',
                'priority' => 'critical',
                'message' => '認証情報を正しく設定してください'
            );
        }
        
        $diagnosis['recommendations'][] = array(
            'category' => 'rate_limiting',
            'priority' => 'info',
            'message' => 'デバッグテストの頻度制限が有効です。これによりTooManyRequestsエラーを防いでいます。'
        );
        
        return $diagnosis;
    }
    
    /**
     * テストリクエストの送信
     */
    public static function test_request() {
        require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-amazon-api.php';
        
        $api = new HopLink_Amazon_API();
        
        // デバッグモードを有効化
        $api->set_debug_mode(true);
        
        // 認証情報の検証
        $credential_issues = $api->validate_credentials();
        
        // デバッグ情報のキャプチャを開始
        $debug_logs = array();
        
        // エラーログのキャプチャを設定
        add_action('http_api_debug', function($response, $context, $class, $args, $url) use (&$debug_logs) {
            $debug_logs['request_url'] = $url;
            $debug_logs['request_headers'] = $args['headers'] ?? array();
            $debug_logs['request_body'] = $args['body'] ?? '';
            
            if (is_wp_error($response)) {
                $debug_logs['wp_error'] = $response->get_error_message();
                $debug_logs['wp_error_code'] = $response->get_error_code();
            } else {
                $debug_logs['response_code'] = wp_remote_retrieve_response_code($response);
                $debug_logs['response_headers'] = wp_remote_retrieve_headers($response);
                $debug_logs['response_body'] = wp_remote_retrieve_body($response);
                
                // レスポンスのサイズ情報
                $response_body = wp_remote_retrieve_body($response);
                $debug_logs['response_size'] = strlen($response_body);
                
                // JSONの解析試行
                $json_data = json_decode($response_body, true);
                if ($json_data !== null) {
                    $debug_logs['json_parsed'] = true;
                    if (isset($json_data['Errors'])) {
                        $debug_logs['api_errors'] = $json_data['Errors'];
                    }
                    if (isset($json_data['SearchResult'])) {
                        $debug_logs['search_result_found'] = true;
                        $debug_logs['items_count'] = isset($json_data['SearchResult']['Items']) ? count($json_data['SearchResult']['Items']) : 0;
                    }
                } else {
                    $debug_logs['json_parsed'] = false;
                    $debug_logs['json_error'] = json_last_error_msg();
                }
            }
        }, 10, 5);
        
        // 実際のリクエスト実行
        $start_time = microtime(true);
        $results = $api->search_items('test', 1);
        $end_time = microtime(true);
        
        // レスポンス詳細の収集
        $debug_info = array(
            'timestamp' => date('Y-m-d H:i:s'),
            'execution_time_ms' => round(($end_time - $start_time) * 1000, 2),
            'credential_issues' => $credential_issues,
            'results_count' => count($results),
            'api_settings' => array(
                'host' => 'webservices.amazon.co.jp',
                'region' => 'us-west-2',
                'service' => 'ProductAdvertisingAPI',
                'access_key_prefix' => substr(get_option('hoplink_amazon_access_key'), 0, 4) . '...',
                'partner_tag' => get_option('hoplink_amazon_partner_tag')
            ),
            'debug_logs' => $debug_logs
        );
        
        // デバッグファイルに詳細ログを書き込み
        if (defined('WP_CONTENT_DIR')) {
            $log_file = WP_CONTENT_DIR . '/debug-hoplink-amazon-detailed.log';
            $log_content = "[" . date('Y-m-d H:i:s') . "] Detailed Debug Test Results:\n";
            $log_content .= json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
            file_put_contents($log_file, $log_content, FILE_APPEND | LOCK_EX);
        }
        
        return array(
            'success' => !empty($results),
            'product_count' => count($results),
            'first_product' => !empty($results) ? $results[0] : null,
            'execution_time' => round(($end_time - $start_time) * 1000, 2) . 'ms',
            'credential_issues' => $credential_issues,
            'debug_info' => $debug_info
        );
    }
    
    /**
     * 署名プロセスのテスト
     */
    public static function test_signature_process() {
        require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-amazon-api.php';
        
        $api = new HopLink_Amazon_API();
        $api->set_debug_mode(true);
        
        // テスト用のパラメータ
        $test_params = array(
            'operation' => 'SearchItems',
            'timestamp' => gmdate('Ymd\THis\Z'),
            'date' => gmdate('Ymd'),
            'payload' => array(
                'Keywords' => 'test',
                'SearchIndex' => 'All',
                'ItemCount' => 1,
                'PartnerTag' => get_option('hoplink_amazon_partner_tag'),
                'PartnerType' => 'Associates',
                'Marketplace' => 'www.amazon.co.jp',
                'Resources' => array('ItemInfo.Title')
            )
        );
        
        // デバッグ情報を収集
        ob_start();
        error_log('=== Amazon PA-API Signature Process Test ===');
        error_log('Test Parameters: ' . print_r($test_params, true));
        
        // 実際のリクエストを実行（デバッグモードで詳細ログが出力される）
        $results = $api->search_items('test', 1);
        
        $debug_output = ob_get_clean();
        
        return array(
            'test_parameters' => $test_params,
            'request_success' => !empty($results),
            'debug_output' => $debug_output
        );
    }
    
    /**
     * エラーレスポンスの詳細解析
     */
    public static function analyze_error_response($response_body) {
        $data = json_decode($response_body, true);
        
        if (!$data) {
            return array(
                'error' => 'Invalid JSON response',
                'raw_response' => $response_body
            );
        }
        
        $analysis = array(
            'has_errors' => isset($data['Errors']),
            'error_count' => isset($data['Errors']) ? count($data['Errors']) : 0,
            'errors' => array()
        );
        
        if (isset($data['Errors'])) {
            foreach ($data['Errors'] as $error) {
                $error_detail = array(
                    'code' => $error['Code'] ?? 'Unknown',
                    'message' => $error['Message'] ?? 'No message',
                    'type' => $error['Type'] ?? 'Unknown'
                );
                
                // エラーコードに基づく推奨対処法
                switch ($error['Code'] ?? '') {
                    case 'InvalidSignature':
                        $error_detail['suggestion'] = 'シークレットキーが正しいか確認してください。コピー時に余分なスペースや改行が含まれていないか確認してください。また、サーバーの時刻が正しいか確認してください。';
                        break;
                    case 'InvalidParameterValue':
                        $error_detail['suggestion'] = 'リクエストパラメータの形式を確認してください。';
                        break;
                    case 'InvalidPartnerTag':
                        $error_detail['suggestion'] = 'パートナータグ（ストアID）が正しいか確認してください。アソシエイト・セントラルの右上で確認できます。';
                        break;
                    case 'UnauthorizedAccess':
                        $error_detail['suggestion'] = 'アクセスキーとシークレットキーが正しいか確認してください。';
                        break;
                    case 'AccessDenied':
                        $error_detail['suggestion'] = '【重要】新規アソシエイトの場合：180日以内に3件の売上が必要です。既存アソシエイトの場合：過去30日間に売上があるか確認してください。PA-APIの利用制限については https://webservices.amazon.com/paapi5/documentation/troubleshooting.html#efficiency-guidelines を参照してください。';
                        $error_detail['priority'] = 'critical';
                        $error_detail['category'] = 'access_restriction';
                        break;
                    case 'TooManyRequests':
                    case 'RequestThrottled':
                        $error_detail['suggestion'] = 'リクエスト頻度が制限を超えています。売上実績により制限は緩和されます。キャッシュを有効にしてください。';
                        break;
                    case 'IncompleteSignature':
                        $error_detail['suggestion'] = '認証情報が不完全です。アクセスキー、シークレットキー、パートナータグすべてが入力されているか確認してください。';
                        break;
                    case 'InvalidRequest':
                        $error_detail['suggestion'] = 'リクエストの形式が不正です。PA-API v5の仕様に準拠しているか確認してください。';
                        break;
                    case 'MissingAuthenticationToken':
                        $error_detail['suggestion'] = '認証トークンがありません。Authorizationヘッダーが正しく設定されているか確認してください。';
                        break;
                    case 'SignatureDoesNotMatch':
                        $error_detail['suggestion'] = '署名が一致しません。署名計算プロセス、特にサービス名(paapi)、リージョン(us-west-2)、タイムスタンプの形式を確認してください。';
                        break;
                    default:
                        $error_detail['suggestion'] = 'Amazon PA-APIのドキュメントを参照してください。エラーコード: ' . $error['Code'];
                }
                
                $analysis['errors'][] = $error_detail;
            }
        }
        
        return $analysis;
    }
    
    /**
     * 包括的なデバッグ診断
     */
    public static function comprehensive_debug() {
        $diagnosis = array(
            'timestamp' => date('Y-m-d H:i:s'),
            'server_info' => array(),
            'api_config' => array(),
            'credential_check' => array(),
            'connectivity_test' => array(),
            'signature_test' => array(),
            'api_test' => array(),
            'recommendations' => array()
        );
        
        // サーバー情報
        $diagnosis['server_info'] = array(
            'php_version' => phpversion(),
            'curl_enabled' => function_exists('curl_init'),
            'openssl_enabled' => function_exists('openssl_encrypt'),
            'json_enabled' => function_exists('json_encode'),
            'timezone' => date_default_timezone_get(),
            'current_utc_time' => gmdate('Y-m-d H:i:s') . 'Z',
            'wordpress_version' => get_bloginfo('version')
        );
        
        // API設定
        $diagnosis['api_config'] = array(
            'host' => 'webservices.amazon.co.jp',
            'region' => 'us-west-2',
            'service' => 'ProductAdvertisingAPI',
            'endpoint_url' => 'https://webservices.amazon.co.jp/paapi5/searchitems'
        );
        
        // 認証情報チェック
        $access_key = get_option('hoplink_amazon_access_key');
        $secret_key = get_option('hoplink_amazon_secret_key');
        $partner_tag = get_option('hoplink_amazon_partner_tag');
        
        $diagnosis['credential_check'] = array(
            'access_key_set' => !empty($access_key),
            'access_key_length' => strlen($access_key),
            'access_key_format' => preg_match('/^[A-Z0-9]{20}$/', $access_key),
            'secret_key_set' => !empty($secret_key),
            'secret_key_length' => strlen($secret_key),
            'partner_tag_set' => !empty($partner_tag),
            'partner_tag_format' => preg_match('/^[a-zA-Z0-9\-]+$/', $partner_tag),
            'issues' => self::validate_credentials()
        );
        
        // 接続テスト
        $connectivity_test_url = 'https://webservices.amazon.co.jp';
        $connectivity_response = wp_remote_get($connectivity_test_url, array('timeout' => 10));
        
        if (is_wp_error($connectivity_response)) {
            $diagnosis['connectivity_test'] = array(
                'success' => false,
                'error' => $connectivity_response->get_error_message(),
                'error_code' => $connectivity_response->get_error_code()
            );
        } else {
            $diagnosis['connectivity_test'] = array(
                'success' => true,
                'response_code' => wp_remote_retrieve_response_code($connectivity_response),
                'response_time' => 'Connected successfully'
            );
        }
        
        // 署名テスト
        if (!empty($diagnosis['credential_check']['issues'])) {
            $diagnosis['signature_test'] = array(
                'skipped' => true,
                'reason' => 'Invalid credentials'
            );
        } else {
            $signature_test = self::test_signature_process();
            $diagnosis['signature_test'] = $signature_test;
        }
        
        // 実際のAPIテスト
        if (empty($diagnosis['credential_check']['issues'])) {
            $api_test = self::test_request();
            $diagnosis['api_test'] = $api_test;
        } else {
            $diagnosis['api_test'] = array(
                'skipped' => true,
                'reason' => 'Invalid credentials'
            );
        }
        
        // 推奨事項
        $recommendations = array();
        
        if (!empty($diagnosis['credential_check']['issues'])) {
            $recommendations[] = array(
                'priority' => 'high',
                'category' => 'credentials',
                'message' => '認証情報を正しく設定してください。アソシエイト・セントラル（https://affiliate.amazon.co.jp/assoc_credentials/home）から取得できます。'
            );
        }
        
        if (!$diagnosis['connectivity_test']['success']) {
            $recommendations[] = array(
                'priority' => 'high', 
                'category' => 'connectivity',
                'message' => 'Amazon APIエンドポイントへの接続ができません。ファイアウォールやプロキシ設定を確認してください。'
            );
        }
        
        if (isset($diagnosis['api_test']['debug_info']['debug_logs']['api_errors'])) {
            foreach ($diagnosis['api_test']['debug_info']['debug_logs']['api_errors'] as $error) {
                if ($error['Code'] === 'AccessDenied') {
                    $recommendations[] = array(
                        'priority' => 'critical',
                        'category' => 'access_denied',
                        'message' => 'PA-APIのアクセスが拒否されています。新規アソシエイトの場合は180日以内に3件の売上が必要です。'
                    );
                }
            }
        }
        
        if (empty($recommendations)) {
            $recommendations[] = array(
                'priority' => 'info',
                'category' => 'status',
                'message' => 'すべての基本チェックに合格しています。'
            );
        }
        
        $diagnosis['recommendations'] = $recommendations;
        
        // 診断結果をファイルに保存
        if (defined('WP_CONTENT_DIR')) {
            $log_file = WP_CONTENT_DIR . '/debug-hoplink-amazon-diagnosis.log';
            $log_content = "[" . date('Y-m-d H:i:s') . "] Comprehensive Diagnosis:\n";
            $log_content .= json_encode($diagnosis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
            file_put_contents($log_file, $log_content, FILE_APPEND | LOCK_EX);
        }
        
        return $diagnosis;
    }
    
    /**
     * ログファイルの場所を取得
     */
    public static function get_log_file_paths() {
        if (!defined('WP_CONTENT_DIR')) {
            return array('error' => 'WP_CONTENT_DIR not defined');
        }
        
        $log_files = array(
            'general_debug' => WP_CONTENT_DIR . '/debug.log',
            'hoplink_amazon' => WP_CONTENT_DIR . '/debug-hoplink-amazon.log',
            'hoplink_detailed' => WP_CONTENT_DIR . '/debug-hoplink-amazon-detailed.log',
            'hoplink_diagnosis' => WP_CONTENT_DIR . '/debug-hoplink-amazon-diagnosis.log'
        );
        
        $file_info = array();
        foreach ($log_files as $key => $path) {
            $file_info[$key] = array(
                'path' => $path,
                'exists' => file_exists($path),
                'size' => file_exists($path) ? filesize($path) : 0,
                'modified' => file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : null,
                'readable' => file_exists($path) ? is_readable($path) : false
            );
        }
        
        return $file_info;
    }
}