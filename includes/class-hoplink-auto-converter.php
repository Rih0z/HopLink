<?php
/**
 * Auto Converter Class
 *
 * 記事保存時にAmazon URLを自動でアフィリエイトリンクに変換する機能
 *
 * @package HopLink
 * @since   1.0.0
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HopLink_Auto_Converter クラス
 */
class HopLink_Auto_Converter {

    /**
     * コンストラクタ
     */
    public function __construct() {
        $this->init();
    }

    /**
     * 初期化
     */
    private function init() {
        // 自動変換が有効な場合のみフックを登録
        if ($this->is_auto_conversion_enabled()) {
            // 記事保存時のフック
            add_filter('content_save_pre', [$this, 'convert_urls_on_save'], 10, 1);
            add_filter('excerpt_save_pre', [$this, 'convert_urls_on_save'], 10, 1);
            
            // Gutenbergエディタ用のフック
            add_filter('wp_insert_post_data', [$this, 'convert_urls_in_post_data'], 10, 2);
            
            // メタボックスを追加（自動変換の制御用）
            add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
            add_action('save_post', [$this, 'save_meta_box_data']);
        }
    }

    /**
     * 自動変換が有効かどうかを確認
     *
     * @return bool
     */
    private function is_auto_conversion_enabled() {
        return get_option('hoplink_auto_convert_enabled', true);
    }

    /**
     * 記事保存時にURLを変換
     *
     * @param string $content 記事内容
     * @return string 変換後の記事内容
     */
    public function convert_urls_on_save($content) {
        // 空の場合は何もしない
        if (empty($content)) {
            return $content;
        }

        // 現在の投稿IDを取得
        $post_id = isset($_POST['post_ID']) ? intval($_POST['post_ID']) : 0;
        
        // 自動変換が無効化されている投稿の場合はスキップ
        if ($post_id && get_post_meta($post_id, '_hoplink_disable_auto_convert', true) === 'yes') {
            return $content;
        }

        // URLパーサーをロード
        if (!class_exists('HopLink_URL_Parser')) {
            require_once HOPLINK_PLUGIN_PATH . 'includes/class-hoplink-url-parser.php';
        }

        // アソシエイトタグを取得
        $associate_tag = get_option('hoplink_amazon_associate_tag', '');
        
        if (empty($associate_tag)) {
            return $content;
        }

        // URLを変換
        $converted_content = HopLink_URL_Parser::convert_amazon_urls(
            $content,
            $associate_tag,
            $this->should_preserve_existing_links()
        );

        // 変換履歴を記録
        if ($post_id && $content !== $converted_content) {
            $this->log_conversion($post_id, $content, $converted_content);
        }

        return $converted_content;
    }

    /**
     * Gutenbergエディタのデータを変換
     *
     * @param array $data 投稿データ
     * @param array $postarr 投稿配列
     * @return array 変換後の投稿データ
     */
    public function convert_urls_in_post_data($data, $postarr) {
        // 自動保存の場合はスキップ
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $data;
        }

        // 対象の投稿タイプかチェック
        $allowed_post_types = $this->get_allowed_post_types();
        if (!in_array($data['post_type'], $allowed_post_types, true)) {
            return $data;
        }

        // 記事内容を変換
        if (!empty($data['post_content'])) {
            $data['post_content'] = $this->convert_urls_on_save($data['post_content']);
        }

        // 抜粋を変換
        if (!empty($data['post_excerpt'])) {
            $data['post_excerpt'] = $this->convert_urls_on_save($data['post_excerpt']);
        }

        return $data;
    }

    /**
     * メタボックスを追加
     */
    public function add_meta_boxes() {
        $post_types = $this->get_allowed_post_types();
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'hoplink_auto_convert',
                'HopLink 自動変換設定',
                [$this, 'render_meta_box'],
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * メタボックスをレンダリング
     *
     * @param WP_Post $post 投稿オブジェクト
     */
    public function render_meta_box($post) {
        // nonce フィールドを追加
        wp_nonce_field('hoplink_auto_convert_meta_box', 'hoplink_auto_convert_nonce');

        // 現在の設定を取得
        $disable_auto_convert = get_post_meta($post->ID, '_hoplink_disable_auto_convert', true);
        $conversion_log = get_post_meta($post->ID, '_hoplink_conversion_log', true);
        ?>
        <div class="hoplink-meta-box">
            <p>
                <label>
                    <input type="checkbox" name="hoplink_disable_auto_convert" value="yes" 
                           <?php checked($disable_auto_convert, 'yes'); ?>>
                    この記事では自動変換を無効にする
                </label>
            </p>
            
            <?php if ($conversion_log): ?>
                <div class="hoplink-conversion-info">
                    <p><strong>変換履歴:</strong></p>
                    <ul>
                        <?php foreach ($conversion_log as $log): ?>
                            <li>
                                <?php echo esc_html($log['date']); ?>: 
                                <?php echo esc_html($log['count']); ?>個のURLを変換
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <p class="description">
                Amazon URLは保存時に自動的にアフィリエイトリンクに変換されます。
            </p>
        </div>
        <?php
    }

    /**
     * メタボックスのデータを保存
     *
     * @param int $post_id 投稿ID
     */
    public function save_meta_box_data($post_id) {
        // nonce チェック
        if (!isset($_POST['hoplink_auto_convert_nonce']) ||
            !wp_verify_nonce($_POST['hoplink_auto_convert_nonce'], 'hoplink_auto_convert_meta_box')) {
            return;
        }

        // 自動保存の場合はスキップ
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // 権限チェック
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // 自動変換の無効化設定を保存
        if (isset($_POST['hoplink_disable_auto_convert']) && $_POST['hoplink_disable_auto_convert'] === 'yes') {
            update_post_meta($post_id, '_hoplink_disable_auto_convert', 'yes');
        } else {
            delete_post_meta($post_id, '_hoplink_disable_auto_convert');
        }
    }

    /**
     * 既存のアフィリエイトリンクを保持するかどうか
     *
     * @return bool
     */
    private function should_preserve_existing_links() {
        return get_option('hoplink_preserve_existing_links', true);
    }

    /**
     * 許可された投稿タイプを取得
     *
     * @return array
     */
    private function get_allowed_post_types() {
        $default_types = ['post', 'page'];
        return apply_filters('hoplink_auto_convert_post_types', $default_types);
    }

    /**
     * 変換履歴を記録
     *
     * @param int $post_id 投稿ID
     * @param string $original_content 元の内容
     * @param string $converted_content 変換後の内容
     */
    private function log_conversion($post_id, $original_content, $converted_content) {
        // URLパーサーをロード
        if (!class_exists('HopLink_URL_Parser')) {
            require_once HOPLINK_PLUGIN_PATH . 'includes/class-hoplink-url-parser.php';
        }

        // 変換されたURLの数を計算
        $original_urls = HopLink_URL_Parser::find_amazon_urls($original_content);
        $converted_urls = HopLink_URL_Parser::find_amazon_urls($converted_content);
        
        $count = 0;
        foreach ($original_urls as $url) {
            if (strpos($url, 'tag=') === false) {
                $count++;
            }
        }

        if ($count > 0) {
            // 履歴を取得
            $log = get_post_meta($post_id, '_hoplink_conversion_log', true);
            if (!is_array($log)) {
                $log = [];
            }

            // 新しい履歴を追加
            $log[] = [
                'date' => current_time('Y-m-d H:i:s'),
                'count' => $count,
            ];

            // 最新の10件のみ保持
            $log = array_slice($log, -10);

            // 保存
            update_post_meta($post_id, '_hoplink_conversion_log', $log);
        }
    }

    /**
     * 手動でURLを変換
     *
     * @param int $post_id 投稿ID
     * @return bool|array 成功時は変換結果の配列、失敗時はfalse
     */
    public static function convert_post_urls($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return false;
        }

        // URLパーサーをロード
        if (!class_exists('HopLink_URL_Parser')) {
            require_once HOPLINK_PLUGIN_PATH . 'includes/class-hoplink-url-parser.php';
        }

        // アソシエイトタグを取得
        $associate_tag = get_option('hoplink_amazon_associate_tag', '');
        
        if (empty($associate_tag)) {
            return false;
        }

        $result = [
            'converted_count' => 0,
            'original_content' => $post->post_content,
            'converted_content' => '',
        ];

        // URLを検出
        $urls = HopLink_URL_Parser::find_amazon_urls($post->post_content);
        
        // 変換
        $converted_content = HopLink_URL_Parser::convert_amazon_urls(
            $post->post_content,
            $associate_tag,
            get_option('hoplink_preserve_existing_links', true)
        );

        $result['converted_content'] = $converted_content;
        
        // 変換されたURLの数を計算
        foreach ($urls as $url) {
            if (strpos($url, 'tag=') === false) {
                $result['converted_count']++;
            }
        }

        // 更新
        if ($result['converted_count'] > 0) {
            wp_update_post([
                'ID' => $post_id,
                'post_content' => $converted_content,
            ]);
        }

        return $result;
    }
}