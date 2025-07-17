<?php
/**
 * HopLink Shortcode Test
 * 
 * WordPressプラグインのショートコード機能をテストします
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
 * HopLink Shortcode Test Class
 */
class HopLink_Shortcode_Test extends WP_UnitTestCase {

    private $shortcode_handler;

    /**
     * テスト前の準備
     */
    public function setUp(): void {
        parent::setUp();
        
        // ショートコードハンドラーを初期化
        if (class_exists('HopLink_URL_Shortcode')) {
            $this->shortcode_handler = new HopLink_URL_Shortcode();
        }
        
        // テスト用の設定
        update_option('hoplink_amazon_associate_tag', 'test-tag-22');
        update_option('hoplink_rakuten_affiliate_id', 'test_affiliate_id');
    }

    /**
     * テスト後のクリーンアップ
     */
    public function tearDown(): void {
        // テスト用設定を削除
        delete_option('hoplink_amazon_associate_tag');
        delete_option('hoplink_rakuten_affiliate_id');
        
        parent::tearDown();
    }

    /**
     * ショートコード登録のテスト
     */
    public function test_shortcode_registration() {
        // ショートコードが登録されているかテスト
        $this->assertTrue(shortcode_exists('hoplink'));
        $this->assertTrue(shortcode_exists('hoplink_auto'));
        $this->assertTrue(shortcode_exists('hoplink_manual'));
        $this->assertTrue(shortcode_exists('hoplink_url'));
    }

    /**
     * 基本的なショートコード出力テスト
     */
    public function test_basic_shortcode_output() {
        // キーワード検索ショートコード
        $output = do_shortcode('[hoplink keyword="テストビール" limit="2"]');
        
        // HTML構造の基本要素が含まれているかチェック
        $this->assertStringContainsString('hoplink-products', $output);
        $this->assertStringContainsString('関連する広告', $output);
    }

    /**
     * Amazon URLショートコードテスト
     */
    public function test_amazon_url_shortcode() {
        $test_url = 'https://www.amazon.co.jp/dp/B08XYZ1234';
        $output = do_shortcode('[hoplink_url url="' . $test_url . '"]');
        
        // アフィリエイトタグが追加されているかチェック
        $this->assertStringContainsString('test-tag-22', $output);
        $this->assertStringContainsString('hoplink-button', $output);
    }

    /**
     * レスポンシブデザインクラステスト
     */
    public function test_responsive_css_classes() {
        $output = do_shortcode('[hoplink keyword="クラフトビール"]');
        
        // レスポンシブ対応のCSSクラスが含まれているかチェック
        $this->assertStringContainsString('hoplink-layout-grid', $output);
        $this->assertStringContainsString('hoplink-product-card', $output);
    }

    /**
     * セキュリティテスト - XSS対策
     */
    public function test_xss_protection() {
        $malicious_keyword = '<script>alert("xss")</script>';
        $output = do_shortcode('[hoplink keyword="' . $malicious_keyword . '"]');
        
        // スクリプトタグがエスケープされているかチェック
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('alert(', $output);
    }

    /**
     * APIキー設定チェックテスト
     */
    public function test_api_credentials_check() {
        // APIキーが未設定の場合のテスト
        delete_option('hoplink_amazon_associate_tag');
        
        $output = do_shortcode('[hoplink keyword="テスト"]');
        
        // エラーメッセージまたは適切な代替表示が含まれているかチェック
        $this->assertTrue(
            strpos($output, 'hoplink-error') !== false || 
            strpos($output, 'hoplink-no-results') !== false
        );
    }

    /**
     * モバイル最適化テスト
     */
    public function test_mobile_optimization() {
        // モバイル環境をシミュレート
        global $wp_query;
        $wp_query->is_mobile = true;
        
        $output = do_shortcode('[hoplink keyword="ビール"]');
        
        // モバイル対応のクラスが含まれているかチェック
        $this->assertStringContainsString('hoplink-product-card', $output);
        
        // レスポンシブ画像設定の確認
        $this->assertStringContainsString('object-fit: contain', $output);
    }

    /**
     * パフォーマンステスト
     */
    public function test_performance_optimization() {
        $start_time = microtime(true);
        
        // 複数のショートコードを実行
        for ($i = 0; $i < 5; $i++) {
            do_shortcode('[hoplink keyword="テスト' . $i . '" limit="1"]');
        }
        
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        // 実行時間が妥当な範囲内かチェック（5秒以内）
        $this->assertLessThan(5.0, $execution_time, 'ショートコード実行時間が長すぎます');
    }

    /**
     * キャッシュ機能テスト
     */
    public function test_cache_functionality() {
        $keyword = 'キャッシュテスト';
        
        // 初回実行
        $start_time = microtime(true);
        $output1 = do_shortcode('[hoplink keyword="' . $keyword . '"]');
        $first_execution_time = microtime(true) - $start_time;
        
        // 2回目実行（キャッシュされているはず）
        $start_time = microtime(true);
        $output2 = do_shortcode('[hoplink keyword="' . $keyword . '"]');
        $second_execution_time = microtime(true) - $start_time;
        
        // 2回目の方が早いかチェック（キャッシュが効いている）
        $this->assertLessThan($first_execution_time, $second_execution_time);
    }
}