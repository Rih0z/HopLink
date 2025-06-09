<?php
/**
 * 記事解析クラス
 */
class HopLink_Analyzer {
    
    /**
     * ビール関連キーワード辞書を取得
     */
    private function get_beer_keywords() {
        // デフォルトキーワード
        $default_keywords = array(
        // ビアスタイル - IPA系
        'IPA', 'インディアペールエール', 'ヘイジーIPA', 'Hazy IPA', 'NEIPA', 
        'ニューイングランドIPA', 'ウエストコーストIPA', 'West Coast IPA', 
        'セッションIPA', 'Session IPA', 'ダブルIPA', 'DIPA', 'トリプルIPA',
        'ブラックIPA', 'Black IPA', 'ホワイトIPA', 'White IPA',
        
        // ビアスタイル - ペールエール系
        'ペールエール', 'Pale Ale', 'アメリカンペールエール', 'APA',
        'イングリッシュペールエール', 'ベルジャンペールエール',
        
        // ビアスタイル - ラガー系
        'ラガー', 'Lager', 'ピルスナー', 'Pilsner', 'ヘレス', 'Helles',
        'ドルトムンダー', 'メルツェン', 'ボック', 'Bock', 'ドッペルボック',
        'シュバルツ', 'Schwarzbier',
        
        // ビアスタイル - ダーク系
        'スタウト', 'Stout', 'ポーター', 'Porter', 'インペリアルスタウト',
        'Imperial Stout', 'バルチックポーター', 'Baltic Porter',
        'ミルクスタウト', 'オートミールスタウト',
        
        // ビアスタイル - 小麦系
        'ヴァイツェン', 'Weizen', 'ベルジャンホワイト', 'ヴィットビア',
        'ヘフェヴァイツェン', 'Hefeweizen', 'ヴァイスビア', 'Weissbier',
        
        // ビアスタイル - サワー系
        'サワーエール', 'Sour Ale', 'ゴーゼ', 'Gose', 'ベルリーナヴァイセ',
        'Berliner Weisse', 'ランビック', 'Lambic', 'フランダースレッドエール',
        
        // ビアスタイル - その他
        'バーレイワイン', 'Barleywine', 'セゾン', 'Saison', 'ファームハウスエール',
        'トリペル', 'Tripel', 'デュベル', 'Dubbel', 'ベルジャンストロング',
        
        // 日本のブルワリー
        'ヤッホーブルーイング', 'よなよなエール', '水曜日のネコ', 'インドの青鬼',
        'コエド', 'COEDO', 'コエドブルワリー', '常陸野ネスト', 'ベアードビール',
        'サンクトガーレン', 'スワンレイクビール', '富士桜高原麦酒', 'エチゴビール',
        'うちゅうブルーイング', 'OUTSIDER Brewing', 'アウトサイダーブルーイング',
        '新月ビア', '舞浜地ビール工房', 'Be Easy Brewing', 'ビーイージーブルーイング',
        'Y.Y.G.ブルワリー', 'ワイワイジーブルワリー', 'Far Yeast', 'ファーイースト',
        'VERTERE', 'ベルテレ', 'カケガワビール', '箕面ビール', '志賀高原ビール',
        
        // 海外ブルワリー
        'Cloudburst', 'クラウドバースト', 'Brujos', 'ブルホス', 'Fremont',
        'フレモント', 'Stone', 'ストーン', 'Brewdog', 'ブリュードッグ',
        
        // ビール関連用語
        'ホップ', 'Hop', 'モルト', 'Malt', '麦芽', '酵母', 'イースト', 'Yeast',
        '醸造', 'ブルワリー', 'Brewery', '醸造所', 'ブリューパブ', 'Brewpub',
        'タップルーム', 'Taproom', 'ビアバー', 'ビアパブ', 'ブルーパブ',
        'IBU', '国際苦味単位', 'ABV', 'アルコール度数', 'SRM', '色度',
        
        // 重要人物
        '丹羽智', 'Satoshi Niwa',
        
        // イベント・アワード
        'World Beer Cup', 'ワールドビアカップ', 'Beer 1', 'ビアワン',
        'Great American Beer Festival', 'GABF',
        
        // 一般的なビール用語
        'クラフトビール', 'Craft Beer', '地ビール', 'ビール', '発泡酒', 
        '第三のビール', '生ビール', '瓶ビール', '缶ビール', 'ドラフトビール',
        'タップリスト', 'フライト', 'テイスティング',
        
        // 関連商品
        'ビールグラス', 'ビアグラス', 'ジョッキ', 'タンブラー', 'グラウラー',
        'ビールサーバー', 'ホームタップ', 'ビールギフト', 'ビールセット',
        'IPAグラス', 'パイントグラス', 'ピルスナーグラス', 'ヴァイツェングラス'
    );
        
        // カスタムキーワードを追加
        $custom_keywords = get_option('hoplink_custom_keywords', '');
        if (!empty($custom_keywords)) {
            $custom_array = array_filter(array_map('trim', explode("\n", $custom_keywords)));
            $default_keywords = array_merge($default_keywords, $custom_array);
        }
        
        return $default_keywords;
    }
    
    /**
     * 記事からキーワードを抽出
     */
    public function extract_keywords($content, $limit = 5) {
        // HTMLタグを除去
        $text = wp_strip_all_tags($content);
        
        // キーワードを取得
        $beer_keywords = $this->get_beer_keywords();
        
        // キーワードの出現回数をカウント
        $keyword_counts = array();
        
        foreach ($beer_keywords as $keyword) {
            // 大文字小文字を区別せずに検索
            $count = substr_count(strtolower($text), strtolower($keyword));
            if ($count > 0) {
                $keyword_counts[$keyword] = $count;
            }
        }
        
        // 出現回数順にソート
        arsort($keyword_counts);
        
        // 上位のキーワードを返す
        return array_slice(array_keys($keyword_counts), 0, $limit);
    }
    
    /**
     * 記事に最適な商品カテゴリを判定
     */
    public function determine_category($keywords) {
        $categories = array(
            'beer' => array('IPA', 'ペールエール', 'スタウト', 'ピルスナー', 'エール', 'ラガー', 'クラフトビール', '地ビール'),
            'glass' => array('ビールグラス', 'ビアグラス', 'ジョッキ', 'タンブラー'),
            'gift' => array('ギフト', 'セット', 'プレゼント'),
            'equipment' => array('サーバー', 'ホームタップ', 'グラウラー')
        );
        
        foreach ($categories as $category => $cat_keywords) {
            foreach ($keywords as $keyword) {
                if (in_array($keyword, $cat_keywords)) {
                    return $category;
                }
            }
        }
        
        return 'beer'; // デフォルト
    }
    
    /**
     * 記事に基づいて商品を自動選択
     */
    public function get_products_for_post($post_id, $limit = 3) {
        $post = get_post($post_id);
        if (!$post) {
            return array();
        }
        
        // タイトルと本文を結合
        $content = $post->post_title . ' ' . $post->post_content;
        
        // キーワード抽出
        $keywords = $this->extract_keywords($content);
        
        if (empty($keywords)) {
            return array();
        }
        
        // メインキーワードで検索
        $api = new HopLink_API();
        $products = array();
        
        // 複数のキーワードで検索して結果をマージ
        foreach (array_slice($keywords, 0, 2) as $keyword) {
            $results = $api->search_all($keyword, 'all');
            $products = array_merge($products, $results);
        }
        
        // 重複を除去
        $unique_products = array();
        $seen_titles = array();
        
        foreach ($products as $product) {
            $title_key = md5($product['title']);
            if (!isset($seen_titles[$title_key])) {
                $unique_products[] = $product;
                $seen_titles[$title_key] = true;
            }
        }
        
        // 指定数に制限
        return array_slice($unique_products, 0, $limit);
    }
    
    /**
     * フォールバック検索キーワードを生成
     */
    public function get_fallback_keywords($original_keywords) {
        $fallback_keywords = array();
        
        // カテゴリ判定
        $category = $this->determine_category($original_keywords);
        
        // カテゴリ別のフォールバックキーワード
        $category_fallbacks = array(
            'beer' => array('クラフトビール', 'ビールセット', '地ビール'),
            'glass' => array('ビールグラス', 'ビアグラス', 'IPAグラス'),
            'gift' => array('ビールギフト', 'クラフトビール ギフト', 'ビール 詰め合わせ'),
            'equipment' => array('ビールサーバー', 'ホームタップ', 'ビール用品')
        );
        
        if (isset($category_fallbacks[$category])) {
            $fallback_keywords = array_merge($fallback_keywords, $category_fallbacks[$category]);
        }
        
        // ビアスタイルから一般的な商品へ
        $style_to_general = array(
            'IPA' => 'クラフトビール IPA',
            'ヘイジーIPA' => 'NEIPA',
            'スタウト' => '黒ビール',
            'ピルスナー' => 'ラガービール',
            'ヴァイツェン' => '小麦ビール'
        );
        
        foreach ($original_keywords as $keyword) {
            if (isset($style_to_general[$keyword])) {
                $fallback_keywords[] = $style_to_general[$keyword];
            }
        }
        
        // 一般的なキーワードを追加
        $fallback_keywords[] = 'クラフトビール ギフト';
        $fallback_keywords[] = 'ビール 贈り物';
        $fallback_keywords[] = 'クラフトビール セット';
        
        return array_unique($fallback_keywords);
    }
}