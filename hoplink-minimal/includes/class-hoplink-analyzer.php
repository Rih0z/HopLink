<?php
/**
 * 記事解析クラス
 */
class HopLink_Analyzer {
    
    /**
     * キーワードマネージャーインスタンス
     */
    private $keyword_manager = null;
    
    /**
     * キーワードマネージャーを取得
     */
    private function get_keyword_manager() {
        if ($this->keyword_manager === null) {
            require_once HOPLINK_PLUGIN_DIR . 'includes/class-keyword-manager.php';
            $this->keyword_manager = new HopLink_Keyword_Manager();
        }
        return $this->keyword_manager;
    }
    
    /**
     * 記事から優先度付きキーワードを抽出
     */
    public function extract_keywords($content, $limit = 5) {
        $keyword_manager = $this->get_keyword_manager();
        
        // 多様化されたキーワードを抽出
        $diverse_keywords = $keyword_manager->extract_diverse_keywords($content, $limit);
        
        // シンプルな配列として返す（後方互換性のため）
        $simple_keywords = array();
        foreach ($diverse_keywords as $keyword_data) {
            $simple_keywords[] = $keyword_data['keyword'];
        }
        
        return $simple_keywords;
    }
    
    /**
     * 記事から詳細なキーワード情報を抽出
     */
    public function extract_keywords_detailed($content, $limit = 5) {
        $keyword_manager = $this->get_keyword_manager();
        return $keyword_manager->extract_diverse_keywords($content, $limit);
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
     * 記事に基づいて商品を自動選択（多様化検索対応）
     */
    public function get_products_for_post($post_id, $limit = 3) {
        $post = get_post($post_id);
        if (!$post) {
            return array();
        }
        
        // タイトルと本文を結合
        $content = $post->post_title . ' ' . $post->post_content;
        
        // 詳細なキーワード情報を抽出（4つの多様なキーワード）
        $detailed_keywords = $this->extract_keywords_detailed($content, 4);
        
        if (empty($detailed_keywords)) {
            return array();
        }
        
        // プラットフォーム別の最適化検索を実行
        $keyword_manager = $this->get_keyword_manager();
        $api = new HopLink_API();
        $all_products = array();
        
        // 楽天向けキーワードで検索
        $rakuten_keywords = $keyword_manager->get_platform_optimized_keywords($detailed_keywords, 'rakuten');
        foreach (array_slice($rakuten_keywords, 0, 2) as $keyword) {
            $results = $api->search_all($keyword, 'rakuten');
            foreach ($results as $result) {
                $result['search_keyword'] = $keyword;
                $result['search_platform'] = 'rakuten';
                $all_products[] = $result;
            }
        }
        
        // Amazon向けキーワードで検索
        $amazon_keywords = $keyword_manager->get_platform_optimized_keywords($detailed_keywords, 'amazon');
        foreach (array_slice($amazon_keywords, 0, 2) as $keyword) {
            $results = $api->search_all($keyword, 'amazon');
            foreach ($results as $result) {
                $result['search_keyword'] = $keyword;
                $result['search_platform'] = 'amazon';
                $all_products[] = $result;
            }
        }
        
        // 重複を除去（タイトルベース）
        $unique_products = array();
        $seen_titles = array();
        
        foreach ($all_products as $product) {
            $title_key = md5(strtolower(trim($product['title'])));
            if (!isset($seen_titles[$title_key])) {
                $unique_products[] = $product;
                $seen_titles[$title_key] = true;
            }
        }
        
        // 商品の関連度スコアを計算
        $scored_products = $this->score_product_relevance($unique_products, $detailed_keywords);
        
        // スコア順でソート
        usort($scored_products, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        // 指定数に制限
        return array_slice($scored_products, 0, $limit);
    }
    
    /**
     * 商品の関連度スコアを計算
     */
    private function score_product_relevance($products, $keywords) {
        $scored_products = array();
        
        foreach ($products as $product) {
            $score = 0;
            $product_text = strtolower($product['title'] . ' ' . $product['shop']);
            
            // キーワードマッチングスコア
            foreach ($keywords as $keyword_data) {
                $keyword = strtolower($keyword_data['keyword']);
                $priority = $keyword_data['priority'];
                
                if (strpos($product_text, $keyword) !== false) {
                    $score += $priority * 10; // 基本マッチングスコア
                }
            }
            
            // プラットフォーム多様性ボーナス
            if (isset($product['platform'])) {
                $score += 1; // プラットフォーム情報があるボーナス
            }
            
            // 価格帯によるスコア調整
            if (isset($product['price']) && $product['price'] > 0) {
                if ($product['price'] >= 1000 && $product['price'] <= 5000) {
                    $score += 2; // 適正価格帯ボーナス
                }
            }
            
            // レビュースコアボーナス
            if (isset($product['review']) && $product['review'] > 4.0) {
                $score += 1;
            }
            
            $product['relevance_score'] = $score;
            $scored_products[] = $product;
        }
        
        return $scored_products;
    }
    
    /**
     * 改善されたフォールバック検索キーワードを生成
     */
    public function get_fallback_keywords($original_keywords) {
        $keyword_manager = $this->get_keyword_manager();
        $fallback_keywords = array();
        
        // 元のキーワードのカテゴリ情報を取得
        $categorized_keywords = $keyword_manager->categorize_keywords($original_keywords);
        
        // カテゴリ別の改善されたフォールバックキーワード
        $category_fallbacks = array(
            'beer_styles' => array(
                'クラフトビール',
                'ビール 詰め合わせ',
                '地ビール セット',
                'エール ビール',
                'ラガー ビール'
            ),
            'breweries' => array(
                'クラフトビール',
                '日本 クラフトビール',
                '地ビール',
                'ブルワリー ビール',
                '限定 ビール'
            ),
            'related_products' => array(
                'ビールグラス',
                'ビアグラス セット',
                'IPAグラス',
                'ビール 雑貨',
                'ビール グッズ'
            ),
            'ingredients' => array(
                'ホップ ビール',
                'クラフトビール IPA',
                'モルト ビール',
                'フルーティ ビール'
            ),
            'locations' => array(
                '地ビール',
                'ご当地 ビール',
                '国産 クラフトビール',
                '日本 ビール'
            ),
            'flavor_descriptions' => array(
                'フルーティ ビール',
                'ホッピー ビール',
                'クリーミー ビール',
                '苦味 ビール'
            )
        );
        
        // カテゴリに基づいてフォールバックキーワードを選択
        foreach ($categorized_keywords as $category => $keywords) {
            if (isset($category_fallbacks[$category])) {
                $fallback_keywords = array_merge($fallback_keywords, $category_fallbacks[$category]);
            }
        }
        
        // ビアスタイルから一般的な商品への詳細マッピング
        $style_to_general = array(
            'IPA' => array('IPA ビール', 'インディアペールエール', 'ホップ ビール'),
            'ヘイジーIPA' => array('NEIPA', 'ヘイジー ビール', 'フルーティ IPA'),
            'スタウト' => array('スタウト ビール', '黒ビール', 'ダーク エール'),
            'ピルスナー' => array('ピルスナー ビール', 'ラガービール', 'すっきり ビール'),
            'ヴァイツェン' => array('ヴァイツェン ビール', '小麦ビール', 'ホワイトエール'),
            'セゾン' => array('セゾン ビール', 'ベルジャン エール', 'ファームハウス'),
            'ポーター' => array('ポーター ビール', '黒ビール', 'ダーク ビール')
        );
        
        foreach ($original_keywords as $keyword) {
            if (isset($style_to_general[$keyword])) {
                $fallback_keywords = array_merge($fallback_keywords, $style_to_general[$keyword]);
            }
        }
        
        // 優先度付きの一般的なキーワードを追加
        $general_fallbacks = array(
            // 高優先度
            'クラフトビール ギフト',
            'ビール 詰め合わせ',
            '地ビール セット',
            
            // 中優先度
            'ビール 贈り物',
            'クラフトビール セット',
            'ビールグラス',
            
            // 低優先度（最終手段）
            'ビール',
            'アルコール ギフト'
        );
        
        $fallback_keywords = array_merge($fallback_keywords, $general_fallbacks);
        
        // 重複を除去し、優先度順で並び替え
        $unique_fallbacks = array_unique($fallback_keywords);
        
        // 長いキーワード（より具体的）を優先
        usort($unique_fallbacks, function($a, $b) {
            $score_a = strlen($a) + (strpos($a, 'クラフトビール') !== false ? 5 : 0);
            $score_b = strlen($b) + (strpos($b, 'クラフトビール') !== false ? 5 : 0);
            return $score_b - $score_a;
        });
        
        return $unique_fallbacks;
    }
    
    /**
     * インテリジェントフォールバック検索
     * キーワード優先度とプラットフォーム特性を考慮した検索
     */
    public function intelligent_fallback_search($content, $platform = 'all', $limit = 4) {
        $keyword_manager = $this->get_keyword_manager();
        
        // 詳細キーワード分析
        $detailed_keywords = $keyword_manager->extract_diverse_keywords($content, 6);
        
        if (empty($detailed_keywords)) {
            // コンテンツからキーワードが抽出できない場合の緊急フォールバック
            return $this->emergency_fallback_search($platform, $limit);
        }
        
        $all_products = array();
        
        // 高優先度キーワードから順に検索
        $search_attempts = 0;
        $max_attempts = 3;
        
        foreach ($detailed_keywords as $keyword_data) {
            if ($search_attempts >= $max_attempts) break;
            
            $keyword = $keyword_data['keyword'];
            $category = $keyword_data['category'];
            $priority = $keyword_data['priority'];
            
            // 優先度が低い場合はスキップ
            if ($priority < 0.5) continue;
            
            // プラットフォーム別最適化
            if ($platform === 'rakuten' || $platform === 'all') {
                $rakuten_keyword = $keyword_manager->optimize_for_rakuten($keyword, $category);
                $api = new HopLink_API();
                $results = $api->search_all($rakuten_keyword, 'rakuten');
                $all_products = array_merge($all_products, $results);
            }
            
            if ($platform === 'amazon' || $platform === 'all') {
                $amazon_keyword = $keyword_manager->optimize_for_amazon($keyword, $category);
                $api = new HopLink_API();
                $results = $api->search_all($amazon_keyword, 'amazon');
                $all_products = array_merge($all_products, $results);
            }
            
            $search_attempts++;
            
            // 十分な商品が見つかったら終了
            if (count($all_products) >= $limit * 2) break;
        }
        
        return $all_products;
    }
    
    /**
     * 緊急フォールバック検索
     */
    private function emergency_fallback_search($platform = 'all', $limit = 4) {
        $emergency_keywords = array(
            'クラフトビール',
            'ビール ギフト',
            '地ビール',
            'ビールグラス'
        );
        
        $api = new HopLink_API();
        $all_products = array();
        
        foreach ($emergency_keywords as $keyword) {
            $results = $api->search_all($keyword, $platform);
            $all_products = array_merge($all_products, $results);
            
            if (count($all_products) >= $limit * 2) break;
        }
        
        return $all_products;
    }
}