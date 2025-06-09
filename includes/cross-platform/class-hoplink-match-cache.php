<?php
/**
 * Match Cache Class
 *
 * マッチング結果のキャッシュを管理するクラス
 *
 * @package HopLink
 * @subpackage Cross_Platform
 * @since 1.0.0
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HopLink_Match_Cache クラス
 */
class HopLink_Match_Cache {

    /**
     * キャッシュプレフィックス
     *
     * @var string
     */
    private $cache_prefix = 'hoplink_match_';

    /**
     * デフォルトのキャッシュ期間（秒）
     *
     * @var int
     */
    private $default_expiration = 86400; // 24時間

    /**
     * キャッシュを取得
     *
     * @param string $key キャッシュキー
     * @return mixed キャッシュデータ、またはfalse
     */
    public function get($key) {
        $cache_key = $this->get_cache_key($key);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            // キャッシュヒット統計を更新
            $this->update_cache_stats('hit');
            return $cached;
        }
        
        // キャッシュミス統計を更新
        $this->update_cache_stats('miss');
        return false;
    }

    /**
     * キャッシュを設定
     *
     * @param string $key キャッシュキー
     * @param mixed $data キャッシュするデータ
     * @param int $expiration 有効期限（秒）
     * @return bool 成功したかどうか
     */
    public function set($key, $data, $expiration = null) {
        $cache_key = $this->get_cache_key($key);
        
        if ($expiration === null) {
            $settings = get_option('hoplink_cross_platform_settings', []);
            $expiration = $settings['cache_duration'] ?? $this->default_expiration;
        }
        
        // メタデータを追加
        $cache_data = [
            'data' => $data,
            'created' => time(),
            'expiration' => $expiration,
        ];
        
        return set_transient($cache_key, $cache_data, $expiration);
    }

    /**
     * キャッシュを削除
     *
     * @param string $key キャッシュキー
     * @return bool 成功したかどうか
     */
    public function delete($key) {
        $cache_key = $this->get_cache_key($key);
        return delete_transient($cache_key);
    }

    /**
     * すべてのマッチングキャッシュをクリア
     *
     * @return int 削除されたキャッシュ数
     */
    public function clear_all() {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE %s 
             OR option_name LIKE %s",
            '_transient_' . $this->cache_prefix . '%',
            '_transient_timeout_' . $this->cache_prefix . '%'
        );
        
        $deleted = $wpdb->query($sql);
        
        // 統計をリセット
        $this->reset_cache_stats();
        
        return $deleted / 2; // タイムアウトとデータの両方を削除するため2で割る
    }

    /**
     * 楽天商品URLからキャッシュキーを生成
     *
     * @param string $url 楽天URL
     * @param string $match_mode マッチングモード
     * @return string キャッシュキー
     */
    public function get_url_cache_key($url, $match_mode = 'normal') {
        $normalized_url = $this->normalize_url($url);
        return md5($normalized_url . '_' . $match_mode);
    }

    /**
     * JANコードからキャッシュキーを生成
     *
     * @param string $jan JANコード
     * @return string キャッシュキー
     */
    public function get_jan_cache_key($jan) {
        $normalized_jan = preg_replace('/[^0-9]/', '', $jan);
        return 'jan_' . $normalized_jan;
    }

    /**
     * キーワードからキャッシュキーを生成
     *
     * @param string $keyword キーワード
     * @param array $options オプション
     * @return string キャッシュキー
     */
    public function get_keyword_cache_key($keyword, $options = []) {
        $normalized_keyword = mb_strtolower(trim($keyword));
        $options_string = serialize($options);
        return 'kw_' . md5($normalized_keyword . '_' . $options_string);
    }

    /**
     * キャッシュキーを生成
     *
     * @param string $key キー
     * @return string キャッシュキー
     */
    private function get_cache_key($key) {
        return $this->cache_prefix . $key;
    }

    /**
     * URLを正規化
     *
     * @param string $url URL
     * @return string 正規化されたURL
     */
    private function normalize_url($url) {
        // クエリパラメータを除去
        $url_parts = parse_url($url);
        $normalized = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
        
        // 末尾のスラッシュを除去
        return rtrim($normalized, '/');
    }

    /**
     * キャッシュ統計を更新
     *
     * @param string $type 'hit' または 'miss'
     */
    private function update_cache_stats($type) {
        $stats = get_option('hoplink_cache_stats', [
            'hits' => 0,
            'misses' => 0,
            'last_reset' => time(),
        ]);
        
        if ($type === 'hit') {
            $stats['hits']++;
        } else {
            $stats['misses']++;
        }
        
        update_option('hoplink_cache_stats', $stats);
    }

    /**
     * キャッシュ統計をリセット
     */
    private function reset_cache_stats() {
        update_option('hoplink_cache_stats', [
            'hits' => 0,
            'misses' => 0,
            'last_reset' => time(),
        ]);
    }

    /**
     * キャッシュ統計を取得
     *
     * @return array 統計データ
     */
    public function get_stats() {
        $stats = get_option('hoplink_cache_stats', [
            'hits' => 0,
            'misses' => 0,
            'last_reset' => time(),
        ]);
        
        $total = $stats['hits'] + $stats['misses'];
        $hit_rate = $total > 0 ? ($stats['hits'] / $total) * 100 : 0;
        
        return [
            'hits' => $stats['hits'],
            'misses' => $stats['misses'],
            'total' => $total,
            'hit_rate' => $hit_rate,
            'last_reset' => $stats['last_reset'],
        ];
    }

    /**
     * 期限切れのキャッシュをクリーンアップ
     *
     * @return int クリーンアップされた数
     */
    public function cleanup_expired() {
        global $wpdb;
        
        // WordPressの自動クリーンアップに依存するが、
        // 必要に応じて手動でクリーンアップも可能
        $current_time = time();
        
        $sql = $wpdb->prepare(
            "DELETE t1, t2 FROM {$wpdb->options} t1
             JOIN {$wpdb->options} t2 
             ON t2.option_name = CONCAT('_transient_timeout_', SUBSTRING(t1.option_name, 12))
             WHERE t1.option_name LIKE %s
             AND t2.option_value < %d",
            '_transient_' . $this->cache_prefix . '%',
            $current_time
        );
        
        return $wpdb->query($sql);
    }

    /**
     * キャッシュのウォームアップ
     *
     * @param array $urls 事前キャッシュするURLのリスト
     * @param string $match_mode マッチングモード
     */
    public function warmup($urls, $match_mode = 'normal') {
        $rakuten_parser = new HopLink_Rakuten_URL_Parser();
        $amazon_search = new HopLink_Amazon_Search();
        $matcher = new HopLink_Product_Matcher();
        
        foreach ($urls as $url) {
            // キャッシュチェック
            $cache_key = $this->get_url_cache_key($url, $match_mode);
            if ($this->get($cache_key) !== false) {
                continue; // 既にキャッシュされている
            }
            
            // 楽天商品情報を取得
            $product_info = $rakuten_parser->extract_product_info($url);
            if (!$product_info) {
                continue;
            }
            
            $rakuten_product = $rakuten_parser->get_product_details($product_info);
            if (!$rakuten_product) {
                continue;
            }
            
            // Amazon商品を検索
            $amazon_candidates = $amazon_search->find_similar_products($rakuten_product);
            
            // マッチング実行
            $amazon_match = $matcher->match_products($rakuten_product, $amazon_candidates, $match_mode);
            
            // 結果をキャッシュ
            $this->set($cache_key, [
                'rakuten' => $rakuten_product,
                'amazon' => $amazon_match,
            ]);
            
            // API制限を考慮して少し待つ
            usleep(500000); // 0.5秒
        }
    }

    /**
     * 商品データの更新チェック
     *
     * @param array $cached_data キャッシュされたデータ
     * @return bool 更新が必要かどうか
     */
    public function needs_update($cached_data) {
        if (!isset($cached_data['created'])) {
            return true;
        }
        
        $age = time() - $cached_data['created'];
        $settings = get_option('hoplink_cross_platform_settings', []);
        
        // 自動更新が有効で、一定期間経過している場合
        if (!empty($settings['auto_update']) && $age > 86400) { // 24時間
            return true;
        }
        
        return false;
    }
}