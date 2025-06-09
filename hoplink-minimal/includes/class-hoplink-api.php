<?php
/**
 * API管理クラス
 */
class HopLink_API {
    
    /**
     * 楽天API検索
     */
    public function search_rakuten($keyword, $limit = 5) {
        $app_id = get_option('hoplink_rakuten_app_id');
        $affiliate_id = get_option('hoplink_rakuten_affiliate_id');
        
        if (empty($app_id)) {
            return array();
        }
        
        // キャッシュチェック
        $cache_key = 'hoplink_rakuten_' . md5($keyword);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        // API URL構築
        $api_url = 'https://app.rakuten.co.jp/services/api/IchibaItem/Search/20170706';
        $params = array(
            'applicationId' => $app_id,
            'affiliateId' => $affiliate_id,
            'keyword' => $keyword,
            'hits' => $limit,
            'sort' => '-reviewAverage', // レビュー評価順
            'genreId' => '510915' // ビール・発泡酒カテゴリ
        );
        
        $url = $api_url . '?' . http_build_query($params);
        
        // API呼び出し
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['Items'])) {
            return array();
        }
        
        // 商品データ整形
        $products = array();
        foreach ($data['Items'] as $item) {
            $products[] = array(
                'platform' => 'rakuten',
                'title' => $item['Item']['itemName'],
                'price' => $item['Item']['itemPrice'],
                'image' => $item['Item']['mediumImageUrls'][0]['imageUrl'] ?? '',
                'url' => $item['Item']['affiliateUrl'] ?? $item['Item']['itemUrl'],
                'shop' => $item['Item']['shopName'],
                'review' => $item['Item']['reviewAverage'] ?? 0
            );
        }
        
        // キャッシュ保存（24時間）
        set_transient($cache_key, $products, 86400);
        
        return $products;
    }
    
    /**
     * Amazon API検索
     */
    public function search_amazon($keyword, $limit = 5) {
        // Amazon API専用クラスを使用
        require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-amazon-api.php';
        $amazon_api = new HopLink_Amazon_API();
        return $amazon_api->search_items($keyword, $limit);
    }
    
    /**
     * 統合検索
     */
    public function search_all($keyword, $platform = 'all') {
        $results = array();
        
        if ($platform === 'all' || $platform === 'rakuten') {
            $rakuten_products = $this->search_rakuten($keyword);
            $results = array_merge($results, $rakuten_products);
        }
        
        if ($platform === 'all' || $platform === 'amazon') {
            $amazon_products = $this->search_amazon($keyword);
            $results = array_merge($results, $amazon_products);
        }
        
        // 価格順にソート
        usort($results, function($a, $b) {
            return $a['price'] - $b['price'];
        });
        
        return $results;
    }
}