<?php
/**
 * Cross Platform Admin Class
 *
 * クロスプラットフォーム機能の管理画面
 *
 * @package HopLink
 * @subpackage Admin
 * @since 1.0.0
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HopLink_Cross_Platform_Admin クラス
 */
class HopLink_Cross_Platform_Admin {

    /**
     * コンストラクタ
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_hoplink_test_matching', [$this, 'ajax_test_matching']);
        add_action('wp_ajax_hoplink_save_match_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_hoplink_clear_match_cache', [$this, 'ajax_clear_cache']);
    }

    /**
     * メニューページを追加
     */
    public function add_menu_pages() {
        add_submenu_page(
            'hoplink',
            __('クロスプラットフォーム設定', 'hoplink'),
            __('クロスプラットフォーム', 'hoplink'),
            'manage_options',
            'hoplink-cross-platform',
            [$this, 'render_settings_page']
        );
    }

    /**
     * 設定ページをレンダリング
     */
    public function render_settings_page() {
        // 現在の設定を取得
        $settings = get_option('hoplink_cross_platform_settings', $this->get_default_settings());
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="hoplink-cross-platform-admin">
                <!-- 設定フォーム -->
                <div class="hoplink-settings-section">
                    <h2><?php _e('マッチング設定', 'hoplink'); ?></h2>
                    
                    <form id="hoplink-cross-platform-settings-form">
                        <?php wp_nonce_field('hoplink_cross_platform_settings', 'hoplink_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="match_mode"><?php _e('マッチング精度', 'hoplink'); ?></label>
                                </th>
                                <td>
                                    <select name="match_mode" id="match_mode">
                                        <option value="strict" <?php selected($settings['match_mode'], 'strict'); ?>>
                                            <?php _e('厳密 - JANコード優先、高精度マッチング', 'hoplink'); ?>
                                        </option>
                                        <option value="normal" <?php selected($settings['match_mode'], 'normal'); ?>>
                                            <?php _e('通常 - バランス重視', 'hoplink'); ?>
                                        </option>
                                        <option value="loose" <?php selected($settings['match_mode'], 'loose'); ?>>
                                            <?php _e('ゆるい - 類似商品も含める', 'hoplink'); ?>
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php _e('商品マッチングの精度を設定します。厳密にするほど正確ですが、マッチする商品が減る可能性があります。', 'hoplink'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="enable_jan_search"><?php _e('JANコード検索', 'hoplink'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_jan_search" id="enable_jan_search" 
                                               value="1" <?php checked($settings['enable_jan_search'], true); ?>>
                                        <?php _e('JANコードでの検索を有効にする', 'hoplink'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('商品説明からJANコードを抽出して検索します。', 'hoplink'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="cache_duration"><?php _e('キャッシュ期間', 'hoplink'); ?></label>
                                </th>
                                <td>
                                    <select name="cache_duration" id="cache_duration">
                                        <option value="3600" <?php selected($settings['cache_duration'], 3600); ?>>
                                            <?php _e('1時間', 'hoplink'); ?>
                                        </option>
                                        <option value="86400" <?php selected($settings['cache_duration'], 86400); ?>>
                                            <?php _e('24時間', 'hoplink'); ?>
                                        </option>
                                        <option value="604800" <?php selected($settings['cache_duration'], 604800); ?>>
                                            <?php _e('7日間', 'hoplink'); ?>
                                        </option>
                                        <option value="2592000" <?php selected($settings['cache_duration'], 2592000); ?>>
                                            <?php _e('30日間', 'hoplink'); ?>
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php _e('マッチング結果をキャッシュする期間です。', 'hoplink'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="search_method"><?php _e('Amazon検索方法', 'hoplink'); ?></label>
                                </th>
                                <td>
                                    <select name="search_method" id="search_method">
                                        <option value="google" <?php selected($settings['search_method'], 'google'); ?>>
                                            <?php _e('Google検索API', 'hoplink'); ?>
                                        </option>
                                        <option value="duckduckgo" <?php selected($settings['search_method'], 'duckduckgo'); ?>>
                                            <?php _e('DuckDuckGo API', 'hoplink'); ?>
                                        </option>
                                        <option value="both" <?php selected($settings['search_method'], 'both'); ?>>
                                            <?php _e('両方（フォールバック）', 'hoplink'); ?>
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php _e('Amazon商品を検索する方法を選択します。', 'hoplink'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="auto_update"><?php _e('自動更新', 'hoplink'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="auto_update" id="auto_update" 
                                               value="1" <?php checked($settings['auto_update'], true); ?>>
                                        <?php _e('商品情報を定期的に自動更新する', 'hoplink'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('価格や在庫状況を定期的に更新します。', 'hoplink'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="fallback_behavior"><?php _e('フォールバック動作', 'hoplink'); ?></label>
                                </th>
                                <td>
                                    <select name="fallback_behavior" id="fallback_behavior">
                                        <option value="show_rakuten_only" <?php selected($settings['fallback_behavior'], 'show_rakuten_only'); ?>>
                                            <?php _e('楽天商品のみ表示', 'hoplink'); ?>
                                        </option>
                                        <option value="show_message" <?php selected($settings['fallback_behavior'], 'show_message'); ?>>
                                            <?php _e('メッセージを表示', 'hoplink'); ?>
                                        </option>
                                        <option value="hide" <?php selected($settings['fallback_behavior'], 'hide'); ?>>
                                            <?php _e('非表示', 'hoplink'); ?>
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php _e('Amazon商品が見つからない場合の動作を設定します。', 'hoplink'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button-primary">
                                <?php _e('設定を保存', 'hoplink'); ?>
                            </button>
                            <button type="button" class="button" id="hoplink-clear-cache">
                                <?php _e('キャッシュをクリア', 'hoplink'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- テストツール -->
                <div class="hoplink-test-section">
                    <h2><?php _e('マッチングテスト', 'hoplink'); ?></h2>
                    
                    <div class="hoplink-test-tool">
                        <p><?php _e('楽天URLを入力して、Amazon商品のマッチングをテストできます。', 'hoplink'); ?></p>
                        
                        <div class="hoplink-test-input">
                            <label for="test_url"><?php _e('楽天商品URL:', 'hoplink'); ?></label>
                            <input type="url" id="test_url" class="regular-text" 
                                   placeholder="https://item.rakuten.co.jp/...">
                            <button type="button" class="button" id="hoplink-test-match">
                                <?php _e('テスト実行', 'hoplink'); ?>
                            </button>
                        </div>
                        
                        <div id="hoplink-test-results" class="hoplink-test-results"></div>
                    </div>
                </div>
                
                <!-- 統計情報 -->
                <div class="hoplink-stats-section">
                    <h2><?php _e('マッチング統計', 'hoplink'); ?></h2>
                    
                    <?php
                    $stats = $this->get_matching_stats();
                    ?>
                    
                    <div class="hoplink-stats-grid">
                        <div class="hoplink-stat-item">
                            <div class="hoplink-stat-label"><?php _e('総マッチング数', 'hoplink'); ?></div>
                            <div class="hoplink-stat-value"><?php echo number_format($stats['total_matches']); ?></div>
                        </div>
                        
                        <div class="hoplink-stat-item">
                            <div class="hoplink-stat-label"><?php _e('成功率', 'hoplink'); ?></div>
                            <div class="hoplink-stat-value"><?php echo number_format($stats['success_rate'], 1); ?>%</div>
                        </div>
                        
                        <div class="hoplink-stat-item">
                            <div class="hoplink-stat-label"><?php _e('キャッシュヒット率', 'hoplink'); ?></div>
                            <div class="hoplink-stat-value"><?php echo number_format($stats['cache_hit_rate'], 1); ?>%</div>
                        </div>
                        
                        <div class="hoplink-stat-item">
                            <div class="hoplink-stat-label"><?php _e('平均マッチスコア', 'hoplink'); ?></div>
                            <div class="hoplink-stat-value"><?php echo number_format($stats['avg_match_score'], 1); ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * 管理画面用スクリプトをエンキュー
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'hoplink_page_hoplink-cross-platform') {
            return;
        }

        // CSS
        wp_enqueue_style(
            'hoplink-cross-platform-admin',
            HOPLINK_PLUGIN_URL . 'admin/assets/css/cross-platform-admin.css',
            [],
            HOPLINK_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'hoplink-cross-platform-admin',
            HOPLINK_PLUGIN_URL . 'admin/assets/js/cross-platform-admin.js',
            ['jquery'],
            HOPLINK_VERSION,
            true
        );

        // ローカライズ
        wp_localize_script('hoplink-cross-platform-admin', 'hoplink_cross_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hoplink_cross_admin_nonce'),
            'i18n' => [
                'saving' => __('保存中...', 'hoplink'),
                'saved' => __('設定を保存しました', 'hoplink'),
                'error' => __('エラーが発生しました', 'hoplink'),
                'testing' => __('テスト中...', 'hoplink'),
                'clearing' => __('クリア中...', 'hoplink'),
                'cleared' => __('キャッシュをクリアしました', 'hoplink'),
            ],
        ]);
    }

    /**
     * AJAX: マッチングテスト
     */
    public function ajax_test_matching() {
        check_ajax_referer('hoplink_cross_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $url = sanitize_url($_POST['url'] ?? '');
        
        if (empty($url)) {
            wp_send_json_error(['message' => __('URLを入力してください', 'hoplink')]);
        }

        // マッチング実行
        $shortcode = new HopLink_Cross_Shortcode();
        $result = $shortcode->search_by_rakuten_url($url, 'normal');

        if (empty($result)) {
            wp_send_json_error(['message' => __('商品が見つかりませんでした', 'hoplink')]);
        }

        // 結果をHTMLで返す
        ob_start();
        $this->render_test_results($result);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * テスト結果をレンダリング
     */
    private function render_test_results($result) {
        ?>
        <div class="hoplink-test-result-container">
            <h3><?php _e('マッチング結果', 'hoplink'); ?></h3>
            
            <!-- 楽天商品 -->
            <?php if (!empty($result['rakuten'])): ?>
                <div class="hoplink-test-product rakuten">
                    <h4><?php _e('楽天商品', 'hoplink'); ?></h4>
                    <div class="product-info">
                        <p><strong><?php _e('商品名:', 'hoplink'); ?></strong> <?php echo esc_html($result['rakuten']['name']); ?></p>
                        <p><strong><?php _e('価格:', 'hoplink'); ?></strong> ¥<?php echo number_format($result['rakuten']['price']); ?></p>
                        <?php if (!empty($result['rakuten']['jan_code'])): ?>
                            <p><strong><?php _e('JANコード:', 'hoplink'); ?></strong> <?php echo esc_html($result['rakuten']['jan_code']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Amazon商品 -->
            <?php if (!empty($result['amazon'])): ?>
                <div class="hoplink-test-product amazon">
                    <h4><?php _e('Amazon商品（マッチ）', 'hoplink'); ?></h4>
                    <div class="product-info">
                        <p><strong><?php _e('商品名:', 'hoplink'); ?></strong> <?php echo esc_html($result['amazon']['title']); ?></p>
                        <p><strong><?php _e('価格:', 'hoplink'); ?></strong> ¥<?php echo number_format($result['amazon']['price']); ?></p>
                        <p><strong><?php _e('ASIN:', 'hoplink'); ?></strong> <?php echo esc_html($result['amazon']['asin']); ?></p>
                        <?php if (!empty($result['amazon']['match_score'])): ?>
                            <p><strong><?php _e('マッチスコア:', 'hoplink'); ?></strong> 
                                <span class="match-score"><?php echo round($result['amazon']['match_score'] * 100); ?>%</span>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($result['amazon']['match_details'])): ?>
                            <p><strong><?php _e('マッチ要因:', 'hoplink'); ?></strong> 
                                <?php echo implode(', ', $result['amazon']['match_details']['match_factors']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="hoplink-test-no-match">
                    <p><?php _e('Amazon商品のマッチが見つかりませんでした。', 'hoplink'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX: 設定保存
     */
    public function ajax_save_settings() {
        check_ajax_referer('hoplink_cross_platform_settings', 'hoplink_nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $settings = [
            'match_mode' => sanitize_text_field($_POST['match_mode'] ?? 'normal'),
            'enable_jan_search' => !empty($_POST['enable_jan_search']),
            'cache_duration' => intval($_POST['cache_duration'] ?? 86400),
            'search_method' => sanitize_text_field($_POST['search_method'] ?? 'google'),
            'auto_update' => !empty($_POST['auto_update']),
            'fallback_behavior' => sanitize_text_field($_POST['fallback_behavior'] ?? 'show_rakuten_only'),
        ];

        update_option('hoplink_cross_platform_settings', $settings);
        
        wp_send_json_success(['message' => __('設定を保存しました', 'hoplink')]);
    }

    /**
     * AJAX: キャッシュクリア
     */
    public function ajax_clear_cache() {
        check_ajax_referer('hoplink_cross_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        // キャッシュクリア処理
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_hoplink_cross_%' 
             OR option_name LIKE '_transient_timeout_hoplink_cross_%'"
        );

        wp_send_json_success(['message' => __('キャッシュをクリアしました', 'hoplink')]);
    }

    /**
     * デフォルト設定を取得
     */
    private function get_default_settings() {
        return [
            'match_mode' => 'normal',
            'enable_jan_search' => true,
            'cache_duration' => 86400,
            'search_method' => 'google',
            'auto_update' => false,
            'fallback_behavior' => 'show_rakuten_only',
        ];
    }

    /**
     * マッチング統計を取得
     */
    private function get_matching_stats() {
        // 実装は実際の統計データに基づく
        return [
            'total_matches' => get_option('hoplink_total_matches', 0),
            'success_rate' => get_option('hoplink_match_success_rate', 0),
            'cache_hit_rate' => get_option('hoplink_cache_hit_rate', 0),
            'avg_match_score' => get_option('hoplink_avg_match_score', 0),
        ];
    }
}