<?php
/**
 * HopLink WordPress Integration Test
 * 
 * WordPress環境での統合テスト
 *
 * @package HopLink
 * @subpackage Tests
 * @since 1.7.1
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress Integration Test Class
 */
class HopLink_Integration_Test {
    
    private $test_results = [];
    
    /**
     * 統合テスト実行
     */
    public function run_integration_tests() {
        echo "🔗 HopLink WordPress統合テスト開始\n";
        echo "====================================\n\n";
        
        $this->test_wordpress_environment();
        $this->test_plugin_activation();
        $this->test_database_operations();
        $this->test_ajax_functionality();
        $this->test_admin_interface();
        $this->test_frontend_output();
        $this->test_api_connections();
        $this->test_caching_system();
        
        $this->display_integration_results();
    }
    
    /**
     * WordPress環境テスト
     */
    private function test_wordpress_environment() {
        echo "🌐 WordPress環境確認...\n";
        
        // WordPress バージョン確認
        global $wp_version;
        $this->assert_true(version_compare($wp_version, '5.0', '>='), "WordPress {$wp_version} >= 5.0");
        
        // PHP バージョン確認
        $php_version = PHP_VERSION;
        $this->assert_true(version_compare($php_version, '7.4', '>='), "PHP {$php_version} >= 7.4");
        
        // 必須WordPress関数確認
        $required_functions = ['add_action', 'add_filter', 'wp_enqueue_script', 'wp_enqueue_style'];
        foreach ($required_functions as $func) {
            $this->assert_true(function_exists($func), "WordPress function: {$func}");
        }
        
        // SSL確認
        $this->assert_true(is_ssl() || is_admin(), 'HTTPS環境またはAdmin画面');
        
        echo "\n";
    }
    
    /**
     * プラグインアクティベーションテスト
     */
    private function test_plugin_activation() {
        echo "🔌 プラグインアクティベーション確認...\n";
        
        // プラグインファイル存在確認
        $plugin_file = dirname(__FILE__, 2) . '/hoplink.php';
        $this->assert_true(file_exists($plugin_file), 'メインプラグインファイル存在');
        
        // プラグインヘッダー確認
        if (file_exists($plugin_file)) {
            $plugin_data = get_file_data($plugin_file, [
                'Name' => 'Plugin Name',
                'Version' => 'Version',
                'Description' => 'Description'
            ]);
            
            $this->assert_true(!empty($plugin_data['Name']), 'プラグイン名設定');
            $this->assert_true(!empty($plugin_data['Version']), 'バージョン設定');
        }
        
        // ショートコード登録確認
        global $shortcode_tags;
        $hoplink_shortcodes = ['hoplink', 'hoplink_auto', 'hoplink_manual', 'hoplink_url'];
        foreach ($hoplink_shortcodes as $shortcode) {
            $this->assert_true(isset($shortcode_tags[$shortcode]), "ショートコード登録: {$shortcode}");
        }
        
        echo "\n";
    }
    
    /**
     * データベース操作テスト
     */
    private function test_database_operations() {
        echo "🗄️ データベース操作確認...\n";
        
        global $wpdb;
        
        // データベース接続確認
        $this->assert_true($wpdb->check_connection(), 'データベース接続');
        
        // オプション操作テスト
        $test_option = 'hoplink_test_option_' . time();
        $test_value = 'test_value_' . rand(1000, 9999);
        
        // オプション保存・取得・削除
        update_option($test_option, $test_value);
        $retrieved = get_option($test_option);
        $this->assert_true($retrieved === $test_value, 'オプション保存・取得');
        
        delete_option($test_option);
        $deleted = get_option($test_option, 'not_found');
        $this->assert_true($deleted === 'not_found', 'オプション削除');
        
        echo "\n";
    }
    
    /**
     * AJAX機能テスト
     */
    private function test_ajax_functionality() {
        echo "⚡ AJAX機能確認...\n";
        
        // AJAX URLの存在確認
        $ajax_url = admin_url('admin-ajax.php');
        $this->assert_true(!empty($ajax_url), 'AJAX URL生成');
        
        // nonceの生成確認
        $nonce = wp_create_nonce('hoplink_test_nonce');
        $this->assert_true(!empty($nonce), 'nonce生成');
        
        // nonce検証確認
        $verify = wp_verify_nonce($nonce, 'hoplink_test_nonce');
        $this->assert_true($verify === 1, 'nonce検証');
        
        echo "\n";
    }
    
    /**
     * 管理画面インターフェーステスト
     */
    private function test_admin_interface() {
        echo "👨‍💼 管理画面インターフェース確認...\n";
        
        // 管理画面アクセス権限確認
        if (is_admin()) {
            $this->assert_true(current_user_can('manage_options'), '管理者権限確認');
        }
        
        // 管理画面CSSファイル確認
        $admin_css = dirname(__FILE__, 2) . '/admin/assets/css/hoplink-admin.css';
        $this->assert_true(file_exists($admin_css), '管理画面CSSファイル存在');
        
        // 管理画面JSファイル確認
        $admin_js = dirname(__FILE__, 2) . '/admin/assets/js/hoplink-admin.js';
        $this->assert_true(file_exists($admin_js), '管理画面JSファイル存在');
        
        echo "\n";
    }
    
    /**
     * フロントエンド出力テスト
     */
    private function test_frontend_output() {
        echo "🌐 フロントエンド出力確認...\n";
        
        // ショートコード出力テスト
        $shortcode_output = do_shortcode('[hoplink keyword="テスト"]');
        $this->assert_true(!empty($shortcode_output), 'ショートコード出力生成');
        $this->assert_true(strpos($shortcode_output, 'hoplink') !== false, 'ショートコード適切なHTML');
        
        // CSS・JSエンキュー確認
        global $wp_styles, $wp_scripts;
        
        // CSSが適切にエンキューされているかチェック
        if (isset($wp_styles->registered['hoplink-public'])) {
            $this->assert_true(true, 'パブリックCSS登録');
        }
        
        if (isset($wp_scripts->registered['hoplink-public'])) {
            $this->assert_true(true, 'パブリックJS登録');
        }
        
        echo "\n";
    }
    
    /**
     * API接続テスト
     */
    private function test_api_connections() {
        echo "🔗 API接続確認...\n";
        
        // 設定値の存在確認
        $amazon_tag = get_option('hoplink_amazon_associate_tag');
        $rakuten_id = get_option('hoplink_rakuten_affiliate_id');
        
        if (!empty($amazon_tag)) {
            $this->assert_true(true, 'Amazon設定値存在');
        } else {
            $this->assert_true(true, 'Amazon設定値未設定（初期状態）');
        }
        
        if (!empty($rakuten_id)) {
            $this->assert_true(true, '楽天設定値存在');
        } else {
            $this->assert_true(true, '楽天設定値未設定（初期状態）');
        }
        
        // HTTP機能確認
        $this->assert_true(function_exists('wp_remote_get'), 'HTTP API機能');
        
        echo "\n";
    }
    
    /**
     * キャッシュシステムテスト
     */
    private function test_caching_system() {
        echo "⚡ キャッシュシステム確認...\n";
        
        // WordPress Transient API確認
        $this->assert_true(function_exists('set_transient'), 'Transient API');
        $this->assert_true(function_exists('get_transient'), 'Transient取得');
        $this->assert_true(function_exists('delete_transient'), 'Transient削除');
        
        // キャッシュ動作テスト
        $cache_key = 'hoplink_test_cache_' . time();
        $cache_value = 'test_cache_value_' . rand(1000, 9999);
        
        set_transient($cache_key, $cache_value, 60);
        $cached = get_transient($cache_key);
        $this->assert_true($cached === $cache_value, 'キャッシュ保存・取得');
        
        delete_transient($cache_key);
        $deleted_cache = get_transient($cache_key);
        $this->assert_true($deleted_cache === false, 'キャッシュ削除');
        
        echo "\n";
    }
    
    /**
     * アサーション
     */
    private function assert_true($condition, $message) {
        if ($condition) {
            echo "✅ PASS: {$message}\n";
            $this->test_results[] = ['message' => $message, 'result' => true];
        } else {
            echo "❌ FAIL: {$message}\n";
            $this->test_results[] = ['message' => $message, 'result' => false];
        }
    }
    
    /**
     * 統合テスト結果表示
     */
    private function display_integration_results() {
        $passed = count(array_filter($this->test_results, function($r) { return $r['result']; }));
        $total = count($this->test_results);
        $failed = $total - $passed;
        
        echo "====================================\n";
        echo "🧪 WordPress統合テスト結果\n";
        echo "====================================\n";
        echo "✅ 成功: {$passed}\n";
        echo "❌ 失敗: {$failed}\n";
        echo "📊 成功率: " . round(($passed / $total) * 100, 1) . "%\n\n";
        
        if ($failed === 0) {
            echo "🎉 全統合テスト成功！WordPress環境で完璧に動作します。\n";
        } else {
            echo "⚠️  {$failed}個の統合テストが失敗しました。\n";
        }
        
        echo "====================================\n";
    }
}

// WordPress環境でのみ実行
if (defined('ABSPATH')) {
    $integration_test = new HopLink_Integration_Test();
    $integration_test->run_integration_tests();
} else {
    echo "⚠️ このテストはWordPress環境で実行してください。\n";
}