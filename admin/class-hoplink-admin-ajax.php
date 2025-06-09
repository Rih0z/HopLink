<?php
/**
 * Admin Ajax Handler Class
 *
 * 管理画面のAjax処理を担当するクラス
 *
 * @package HopLink
 * @since   1.0.0
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HopLink_Admin_Ajax クラス
 */
class HopLink_Admin_Ajax {

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
        // Ajaxアクションを登録
        add_action('wp_ajax_hoplink_start_bulk_conversion', [$this, 'handle_start_bulk_conversion']);
        add_action('wp_ajax_hoplink_process_batch', [$this, 'handle_process_batch']);
        add_action('wp_ajax_hoplink_convert_single', [$this, 'handle_convert_single']);
        add_action('wp_ajax_hoplink_search_urls', [$this, 'handle_search_urls']);
    }

    /**
     * 一括変換の開始処理
     */
    public function handle_start_bulk_conversion() {
        // nonce検証
        if (!isset($_POST['hoplink_bulk_convert_nonce']) || 
            !wp_verify_nonce($_POST['hoplink_bulk_convert_nonce'], 'hoplink_bulk_convert')) {
            wp_send_json_error(['message' => '不正なリクエストです。']);
        }

        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => '権限がありません。']);
        }

        // パラメータを取得
        $post_types = isset($_POST['post_types']) ? (array)$_POST['post_types'] : ['post'];
        $post_types = array_map('sanitize_text_field', $post_types);

        // 対象記事の総数を取得
        $args = [
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        $query = new WP_Query($args);
        $total_posts = $query->found_posts;

        wp_send_json_success([
            'total_posts' => $total_posts,
            'batch_size' => 10, // 一度に処理する記事数
        ]);
    }

    /**
     * バッチ処理
     */
    public function handle_process_batch() {
        // nonce検証
        if (!isset($_POST['hoplink_bulk_convert_nonce']) || 
            !wp_verify_nonce($_POST['hoplink_bulk_convert_nonce'], 'hoplink_bulk_convert')) {
            wp_send_json_error(['message' => '不正なリクエストです。']);
        }

        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => '権限がありません。']);
        }

        // パラメータを取得
        $post_types = isset($_POST['post_types']) ? (array)$_POST['post_types'] : ['post'];
        $post_types = array_map('sanitize_text_field', $post_types);
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'preview';
        $preserve_existing = isset($_POST['preserve_existing']) ? true : false;

        // URLパーサーとコンバーターをロード
        if (!class_exists('HopLink_URL_Parser')) {
            require_once HOPLINK_PLUGIN_PATH . 'includes/class-hoplink-url-parser.php';
        }
        if (!class_exists('HopLink_Auto_Converter')) {
            require_once HOPLINK_PLUGIN_PATH . 'includes/class-hoplink-auto-converter.php';
        }

        // バッチサイズ
        $batch_size = 10;

        // 記事を取得
        $args = [
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'ID',
            'order' => 'ASC',
        ];

        $query = new WP_Query($args);
        $results = [];

        foreach ($query->posts as $post) {
            $result = $this->process_single_post($post, $mode, $preserve_existing);
            $results[] = $result;
        }

        wp_send_json_success([
            'results' => $results,
            'batch_size' => count($results),
        ]);
    }

    /**
     * 単一記事の変換処理
     */
    public function handle_convert_single() {
        // nonce検証
        if (!isset($_POST['hoplink_single_convert_nonce']) || 
            !wp_verify_nonce($_POST['hoplink_single_convert_nonce'], 'hoplink_single_convert')) {
            wp_send_json_error(['message' => '不正なリクエストです。']);
        }

        // 権限チェック
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => '権限がありません。']);
        }

        // 記事を特定
        $post_identifier = isset($_POST['post_identifier']) ? sanitize_text_field($_POST['post_identifier']) : '';
        
        if (empty($post_identifier)) {
            wp_send_json_error(['message' => '記事IDまたはURLを入力してください。']);
        }

        // 記事IDまたはURLから投稿を取得
        $post = null;
        if (is_numeric($post_identifier)) {
            $post = get_post(intval($post_identifier));
        } else {
            $post_id = url_to_postid($post_identifier);
            if ($post_id) {
                $post = get_post($post_id);
            }
        }

        if (!$post) {
            wp_send_json_error(['message' => '記事が見つかりません。']);
        }

        // 変換を実行
        $result = HopLink_Auto_Converter::convert_post_urls($post->ID);

        if ($result === false) {
            wp_send_json_error(['message' => '変換に失敗しました。アソシエイトタグを確認してください。']);
        }

        wp_send_json_success([
            'post_id' => $post->ID,
            'title' => $post->post_title,
            'converted_count' => $result['converted_count'],
        ]);
    }

    /**
     * Amazon URLの検索
     */
    public function handle_search_urls() {
        // nonce検証
        if (!isset($_POST['hoplink_url_search_nonce']) || 
            !wp_verify_nonce($_POST['hoplink_url_search_nonce'], 'hoplink_url_search')) {
            wp_send_json_error(['message' => '不正なリクエストです。']);
        }

        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => '権限がありません。']);
        }

        // URLパーサーをロード
        if (!class_exists('HopLink_URL_Parser')) {
            require_once HOPLINK_PLUGIN_PATH . 'includes/class-hoplink-url-parser.php';
        }

        // 全記事を検索
        $args = [
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ];

        $query = new WP_Query($args);
        $total_urls = 0;
        $posts_with_urls = [];

        foreach ($query->posts as $post) {
            $urls = HopLink_URL_Parser::find_amazon_urls($post->post_content);
            
            if (!empty($urls)) {
                $total_urls += count($urls);
                $posts_with_urls[] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url_count' => count($urls),
                    'sample_url' => $urls[0],
                    'edit_url' => get_edit_post_link($post->ID),
                ];
            }
        }

        wp_send_json_success([
            'total_urls' => $total_urls,
            'posts' => $posts_with_urls,
        ]);
    }

    /**
     * 単一の投稿を処理
     *
     * @param WP_Post $post 投稿オブジェクト
     * @param string $mode 処理モード（preview または execute）
     * @param bool $preserve_existing 既存のリンクを保持するか
     * @return array 処理結果
     */
    private function process_single_post($post, $mode, $preserve_existing) {
        $result = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'converted_count' => 0,
            'status' => 'processed',
            'edit_url' => get_edit_post_link($post->ID),
        ];

        // Amazon URLを検出
        $urls = HopLink_URL_Parser::find_amazon_urls($post->post_content);
        
        if (empty($urls)) {
            $result['status'] = 'no_urls';
            return $result;
        }

        // アソシエイトタグを取得
        $associate_tag = get_option('hoplink_amazon_associate_tag', '');

        // 変換可能なURLをカウント
        foreach ($urls as $url) {
            if (!$preserve_existing || strpos($url, 'tag=') === false) {
                $result['converted_count']++;
            }
        }

        // 実行モードの場合は実際に変換
        if ($mode === 'execute' && $result['converted_count'] > 0) {
            $converted_content = HopLink_URL_Parser::convert_amazon_urls(
                $post->post_content,
                $associate_tag,
                $preserve_existing
            );

            wp_update_post([
                'ID' => $post->ID,
                'post_content' => $converted_content,
            ]);

            $result['status'] = 'converted';
        } elseif ($mode === 'preview') {
            $result['status'] = 'preview';
        }

        return $result;
    }
}