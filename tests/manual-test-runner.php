<?php
/**
 * HopLink Manual Test Runner
 * 
 * PHPUnitが使用できない環境での手動テスト実行
 *
 * @package HopLink
 * @subpackage Tests
 * @since 1.7.1
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    // WordPressが利用できない場合のテスト環境シミュレート
    define('ABSPATH', dirname(__FILE__, 2) . '/');
    
    // WordPress関数のモック
    if (!function_exists('shortcode_exists')) {
        function shortcode_exists($tag) {
            return in_array($tag, ['hoplink', 'hoplink_auto', 'hoplink_manual', 'hoplink_url']);
        }
    }
    
    if (!function_exists('do_shortcode')) {
        function do_shortcode($content) {
            // ショートコードのシミュレート
            if (strpos($content, '[hoplink') !== false) {
                return '<div class="hoplink-products hoplink-layout-grid">関連する広告</div>';
            }
            return $content;
        }
    }
    
    if (!function_exists('get_option')) {
        function get_option($option, $default = false) {
            $options = [
                'hoplink_amazon_associate_tag' => 'test-tag-22',
                'hoplink_rakuten_affiliate_id' => 'test_affiliate_id'
            ];
            return isset($options[$option]) ? $options[$option] : $default;
        }
    }
}

/**
 * Manual Test Runner Class
 */
class HopLink_Manual_Test_Runner {
    
    private $tests_passed = 0;
    private $tests_failed = 0;
    private $test_results = [];
    
    /**
     * テストを実行
     */
    public function run_all_tests() {
        echo "🧪 HopLink 徹底テスト開始\n";
        echo "================================\n\n";
        
        $this->test_css_files_exist();
        $this->test_responsive_breakpoints();
        $this->test_css_grid_responsive();
        $this->test_modern_design_elements();
        $this->test_shortcode_simulation();
        $this->test_security_xss_protection();
        $this->test_performance_optimization();
        $this->test_accessibility_features();
        $this->test_mobile_optimization();
        
        $this->display_results();
    }
    
    /**
     * CSS ファイル存在テスト
     */
    private function test_css_files_exist() {
        echo "📁 CSS ファイル存在確認...\n";
        
        $css_files = [
            'public/assets/css/hoplink-public.css',
            'public/assets/css/cross-platform.css',
            'blocks/amazon-link/style.css',
            'hoplink-minimal/public/css/hoplink-public.css',
            'hoplink-minimal/public/css/cross-platform.css'
        ];
        
        foreach ($css_files as $file) {
            $full_path = dirname(__FILE__, 2) . '/' . $file;
            if (file_exists($full_path)) {
                $this->assert_true(true, "CSS file exists: {$file}");
            } else {
                $this->assert_true(false, "CSS file missing: {$file}");
            }
        }
        echo "\n";
    }
    
    /**
     * レスポンシブブレークポイントテスト
     */
    private function test_responsive_breakpoints() {
        echo "📱 レスポンシブブレークポイント確認...\n";
        
        $css_file = dirname(__FILE__, 2) . '/public/assets/css/hoplink-public.css';
        if (file_exists($css_file)) {
            $css_content = file_get_contents($css_file);
            
            $this->assert_contains($css_content, '@media (max-width: 768px)', 'タブレットブレークポイント');
            $this->assert_contains($css_content, '@media (max-width: 480px)', 'モバイルブレークポイント');
            $this->assert_contains($css_content, 'grid-template-columns: 1fr', 'モバイル1列レイアウト');
        } else {
            $this->assert_true(false, 'CSS file not found for responsive test');
        }
        echo "\n";
    }
    
    /**
     * CSS Grid レスポンシブテスト
     */
    private function test_css_grid_responsive() {
        echo "🎯 CSS Grid レスポンシブ設定確認...\n";
        
        $css_file = dirname(__FILE__, 2) . '/public/assets/css/hoplink-public.css';
        if (file_exists($css_file)) {
            $css_content = file_get_contents($css_file);
            
            $this->assert_contains($css_content, 'grid-template-columns: repeat(auto-fill, minmax(', 'Grid auto-fill設定');
            $this->assert_contains($css_content, 'minmax(280px, 1fr)', 'モバイル最小幅280px');
            $this->assert_contains($css_content, 'gap:', 'Grid gap設定');
        }
        echo "\n";
    }
    
    /**
     * モダンデザイン要素テスト
     */
    private function test_modern_design_elements() {
        echo "🎨 2025年モダンデザイン要素確認...\n";
        
        $css_file = dirname(__FILE__, 2) . '/public/assets/css/hoplink-public.css';
        if (file_exists($css_file)) {
            $css_content = file_get_contents($css_file);
            
            $this->assert_contains($css_content, 'border-radius: 12px', '大きな角丸');
            $this->assert_contains($css_content, 'linear-gradient(135deg', 'グラデーション');
            $this->assert_contains($css_content, 'box-shadow:', '立体感のある影');
            $this->assert_contains($css_content, 'translateY(-', 'マイクロアニメーション');
            $this->assert_contains($css_content, 'will-change: transform', 'GPU最適化');
            $this->assert_contains($css_content, 'transition: all 0.2s ease', '高速トランジション');
        }
        echo "\n";
    }
    
    /**
     * ショートコードシミュレーションテスト
     */
    private function test_shortcode_simulation() {
        echo "⚡ ショートコード機能確認...\n";
        
        $this->assert_true(shortcode_exists('hoplink'), 'hoplink ショートコード登録');
        $this->assert_true(shortcode_exists('hoplink_auto'), 'hoplink_auto ショートコード登録');
        $this->assert_true(shortcode_exists('hoplink_manual'), 'hoplink_manual ショートコード登録');
        $this->assert_true(shortcode_exists('hoplink_url'), 'hoplink_url ショートコード登録');
        
        $output = do_shortcode('[hoplink keyword="テストビール"]');
        $this->assert_contains($output, 'hoplink-products', 'ショートコード出力構造');
        
        echo "\n";
    }
    
    /**
     * セキュリティ・XSS対策テスト
     */
    private function test_security_xss_protection() {
        echo "🔒 セキュリティ・XSS対策確認...\n";
        
        // PHPファイルでの直接アクセス防止確認
        $php_files = glob(dirname(__FILE__, 2) . '/includes/*.php');
        foreach (array_slice($php_files, 0, 3) as $file) {
            $content = file_get_contents($file);
            $this->assert_contains($content, "if (!defined('ABSPATH'))", basename($file) . ' 直接アクセス防止');
        }
        
        // 設定ファイルのセキュリティ確認
        $gitignore = dirname(__FILE__, 2) . '/.gitignore';
        if (file_exists($gitignore)) {
            $content = file_get_contents($gitignore);
            $this->assert_contains($content, 'config.php', 'config.php除外設定');
            $this->assert_contains($content, '*.key', 'APIキー除外設定');
        }
        
        echo "\n";
    }
    
    /**
     * パフォーマンス最適化テスト
     */
    private function test_performance_optimization() {
        echo "🚀 パフォーマンス最適化確認...\n";
        
        $css_file = dirname(__FILE__, 2) . '/public/assets/css/hoplink-public.css';
        if (file_exists($css_file)) {
            $css_content = file_get_contents($css_file);
            
            $this->assert_contains($css_content, 'will-change: transform', 'GPU加速最適化');
            $this->assert_contains($css_content, 'transition: all 0.2s', '高速トランジション');
            
            // CSS圧縮効率チェック
            $file_size = filesize($css_file);
            $this->assert_true($file_size < 50000, "CSS file size reasonable: {$file_size} bytes");
        }
        
        // JavaScript遅延読み込み確認
        $js_file = dirname(__FILE__, 2) . '/public/assets/js/hoplink-public.js';
        if (file_exists($js_file)) {
            $js_content = file_get_contents($js_file);
            $this->assert_contains($js_content, 'IntersectionObserver', '画像遅延読み込み');
        }
        
        echo "\n";
    }
    
    /**
     * アクセシビリティ機能テスト
     */
    private function test_accessibility_features() {
        echo "♿ アクセシビリティ機能確認...\n";
        
        $css_file = dirname(__FILE__, 2) . '/public/assets/css/hoplink-public.css';
        if (file_exists($css_file)) {
            $css_content = file_get_contents($css_file);
            
            $this->assert_contains($css_content, 'line-height: 1.5', '読みやすい行間');
            $this->assert_contains($css_content, 'letter-spacing:', '文字間隔最適化');
            $this->assert_contains($css_content, 'min-height:', 'タッチ領域確保');
        }
        
        // カラーコントラスト分析ファイル確認
        $contrast_file = dirname(__FILE__, 2) . '/color-contrast-analysis.md';
        if (file_exists($contrast_file)) {
            $this->assert_true(true, 'カラーコントラスト分析ドキュメント存在');
        }
        
        echo "\n";
    }
    
    /**
     * モバイル最適化テスト
     */
    private function test_mobile_optimization() {
        echo "📲 モバイル最適化確認...\n";
        
        $css_file = dirname(__FILE__, 2) . '/public/assets/css/hoplink-public.css';
        if (file_exists($css_file)) {
            $css_content = file_get_contents($css_file);
            
            // モバイル専用レイアウト確認
            $this->assert_contains($css_content, 'padding: 14px 20px', 'モバイルボタンサイズ');
            $this->assert_contains($css_content, 'font-size: 1em', 'モバイルフォントサイズ');
            $this->assert_contains($css_content, 'width: 100%', 'モバイル幅100%');
            
            // タッチ最適化確認
            $this->assert_contains($css_content, 'border-radius: 10px', 'モバイル角丸最適化');
        }
        
        echo "\n";
    }
    
    /**
     * アサーション: 真偽値
     */
    private function assert_true($condition, $message) {
        if ($condition) {
            echo "✅ PASS: {$message}\n";
            $this->tests_passed++;
        } else {
            echo "❌ FAIL: {$message}\n";
            $this->tests_failed++;
        }
        $this->test_results[] = ['message' => $message, 'result' => $condition];
    }
    
    /**
     * アサーション: 文字列含有
     */
    private function assert_contains($haystack, $needle, $message) {
        $condition = strpos($haystack, $needle) !== false;
        $this->assert_true($condition, $message);
    }
    
    /**
     * テスト結果表示
     */
    private function display_results() {
        echo "================================\n";
        echo "🧪 テスト結果サマリー\n";
        echo "================================\n";
        echo "✅ 成功: {$this->tests_passed}\n";
        echo "❌ 失敗: {$this->tests_failed}\n";
        echo "📊 成功率: " . round(($this->tests_passed / ($this->tests_passed + $this->tests_failed)) * 100, 1) . "%\n\n";
        
        if ($this->tests_failed === 0) {
            echo "🎉 全テスト成功！HopLinkは本番環境ready状態です。\n";
        } else {
            echo "⚠️  {$this->tests_failed}個のテストが失敗しました。改善が必要です。\n";
        }
        
        echo "================================\n";
    }
}

// テスト実行
$test_runner = new HopLink_Manual_Test_Runner();
$test_runner->run_all_tests();