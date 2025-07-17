<?php
/**
 * HopLink Responsive Design Test
 * 
 * レスポンシブデザインとモバイル最適化をテストします
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
 * HopLink Responsive Test Class
 */
class HopLink_Responsive_Test extends WP_UnitTestCase {

    /**
     * CSS ファイルの存在確認
     */
    public function test_css_files_exist() {
        $css_files = [
            'public/assets/css/hoplink-public.css',
            'public/assets/css/cross-platform.css',
            'blocks/amazon-link/style.css'
        ];
        
        foreach ($css_files as $file) {
            $full_path = plugin_dir_path(__FILE__) . '../' . $file;
            $this->assertFileExists($full_path, "CSS file not found: {$file}");
        }
    }

    /**
     * レスポンシブブレークポイントテスト
     */
    public function test_responsive_breakpoints() {
        $css_content = file_get_contents(plugin_dir_path(__FILE__) . '../public/assets/css/hoplink-public.css');
        
        // 必要なブレークポイントが定義されているかチェック
        $this->assertStringContainsString('@media (max-width: 768px)', $css_content);
        $this->assertStringContainsString('@media (max-width: 480px)', $css_content);
    }

    /**
     * CSS Grid レスポンシブ設定テスト
     */
    public function test_css_grid_responsive() {
        $css_content = file_get_contents(plugin_dir_path(__FILE__) . '../public/assets/css/hoplink-public.css');
        
        // Grid設定の確認
        $this->assertStringContainsString('grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))', $css_content);
        $this->assertStringContainsString('grid-template-columns: 1fr', $css_content);
    }

    /**
     * モバイル最適化CSS確認
     */
    public function test_mobile_optimization_css() {
        $css_content = file_get_contents(plugin_dir_path(__FILE__) . '../public/assets/css/hoplink-public.css');
        
        // モバイル最適化の要素が含まれているかチェック
        $this->assertStringContainsString('will-change: transform', $css_content);
        $this->assertStringContainsString('transition:', $css_content);
        $this->assertStringContainsString('border-radius: 12px', $css_content);
    }

    /**
     * フォントサイズのレスポンシブ対応テスト
     */
    public function test_responsive_font_sizes() {
        $css_content = file_get_contents(plugin_dir_path(__FILE__) . '../public/assets/css/hoplink-public.css');
        
        // レスポンシブフォントサイズの確認
        $this->assertStringContainsString('font-size: 1.05em', $css_content);
        $this->assertStringContainsString('font-size: 1em', $css_content);
    }

    /**
     * ボタンのモバイル対応テスト
     */
    public function test_mobile_button_optimization() {
        $css_content = file_get_contents(plugin_dir_path(__FILE__) . '../public/assets/css/hoplink-public.css');
        
        // モバイルでのボタン最適化確認
        $this->assertStringContainsString('padding: 14px 20px', $css_content);
        $this->assertStringContainsString('width: 100%', $css_content);
    }

    /**
     * 画像レスポンシブ設定テスト
     */
    public function test_responsive_images() {
        $css_content = file_get_contents(plugin_dir_path(__FILE__) . '../public/assets/css/hoplink-public.css');
        
        // 画像のレスポンシブ設定確認
        $this->assertStringContainsString('max-width: 100%', $css_content);
        $this->assertStringContainsString('height: auto', $css_content);
        $this->assertStringContainsString('object-fit: contain', $css_content);
    }

    /**
     * アクセシビリティ対応テスト
     */
    public function test_accessibility_features() {
        $css_content = file_get_contents(plugin_dir_path(__FILE__) . '../public/assets/css/hoplink-public.css');
        
        // アクセシビリティ要素の確認
        $this->assertStringContainsString('line-height: 1.5', $css_content);
        $this->assertStringContainsString('letter-spacing:', $css_content);
    }

    /**
     * パフォーマンス最適化テスト
     */
    public function test_performance_optimizations() {
        $css_content = file_get_contents(plugin_dir_path(__FILE__) . '../public/assets/css/hoplink-public.css');
        
        // パフォーマンス最適化の確認
        $this->assertStringContainsString('will-change: transform', $css_content);
        $this->assertStringContainsString('transition: all 0.2s ease', $css_content);
    }

    /**
     * クロスプラットフォームCSS テスト
     */
    public function test_cross_platform_css() {
        $css_file = plugin_dir_path(__FILE__) . '../public/assets/css/cross-platform.css';
        $this->assertFileExists($css_file);
        
        $css_content = file_get_contents($css_file);
        
        // クロスプラットフォーム要素の確認
        $this->assertStringContainsString('hoplink-cross-products', $css_content);
        $this->assertStringContainsString('hoplink-rakuten-button', $css_content);
        $this->assertStringContainsString('hoplink-amazon-button', $css_content);
    }

    /**
     * モダンデザイン要素テスト
     */
    public function test_modern_design_elements() {
        $css_content = file_get_contents(plugin_dir_path(__FILE__) . '../public/assets/css/hoplink-public.css');
        
        // 2025年モダンデザイン要素の確認
        $this->assertStringContainsString('border-radius: 12px', $css_content);
        $this->assertStringContainsString('linear-gradient(135deg', $css_content);
        $this->assertStringContainsString('box-shadow:', $css_content);
        $this->assertStringContainsString('translateY(-', $css_content);
    }
}