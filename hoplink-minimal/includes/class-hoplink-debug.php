<?php
/**
 * デバッグ・テストクラス
 */
class HopLink_Debug {
    
    /**
     * API接続テスト
     */
    public static function test_api_connection() {
        $results = array();
        
        // 楽天APIテスト
        $rakuten_app_id = get_option('hoplink_rakuten_app_id');
        if (!empty($rakuten_app_id)) {
            $api = new HopLink_API();
            $products = $api->search_rakuten('ビール', 1);
            $results['rakuten'] = array(
                'status' => !empty($products) ? 'success' : 'error',
                'message' => !empty($products) ? '接続成功' : 'APIキーを確認してください',
                'product_count' => count($products)
            );
        } else {
            $results['rakuten'] = array(
                'status' => 'not_configured',
                'message' => 'APIキーが設定されていません'
            );
        }
        
        // Amazon APIテスト
        $amazon_access_key = get_option('hoplink_amazon_access_key');
        if (!empty($amazon_access_key)) {
            require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-amazon-api.php';
            $amazon_api = new HopLink_Amazon_API();
            $products = $amazon_api->search_items('ビール', 1);
            $results['amazon'] = array(
                'status' => !empty($products) ? 'success' : 'error',
                'message' => !empty($products) ? '接続成功' : 'APIキーまたは署名を確認してください',
                'product_count' => count($products)
            );
        } else {
            $results['amazon'] = array(
                'status' => 'not_configured',
                'message' => 'APIキーが設定されていません'
            );
        }
        
        return $results;
    }
    
    /**
     * キャッシュクリア
     */
    public static function clear_cache() {
        global $wpdb;
        
        // hoplink_で始まるトランジェントをすべて削除
        $transients = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_hoplink_%' 
             OR option_name LIKE '_transient_timeout_hoplink_%'"
        );
        
        foreach ($transients as $transient) {
            delete_option($transient);
        }
        
        return count($transients) / 2; // timeoutとセットなので2で割る
    }
}