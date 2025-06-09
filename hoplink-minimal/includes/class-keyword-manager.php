<?php
/**
 * キーワード管理クラス
 *
 * @package HopLink
 * @since 1.0.0
 */

namespace HopLink;

/**
 * Class Keyword_Manager
 * 
 * ビール関連キーワードの管理と記事解析を行うクラス
 */
class Keyword_Manager {
    
    /**
     * キーワードデータ
     *
     * @var array
     */
    private $keywords = [];
    
    /**
     * キーワードの重み付け設定
     *
     * @var array
     */
    private $keyword_weights = [
        'breweries' => [
            'japanese' => 1.0,
            'international' => 0.9
        ],
        'beer_styles' => [
            'ipa_variations' => 0.8,
            'lager_variations' => 0.8,
            'dark_beers' => 0.7,
            'belgian_styles' => 0.7,
            'other_styles' => 0.6
        ],
        'ingredients' => [
            'hops' => 0.6,
            'malt' => 0.5,
            'yeast' => 0.5
        ],
        'brewing_terms' => 0.5,
        'locations' => 0.4,
        'people' => 0.8,
        'events_competitions' => 0.6,
        'product_categories' => 0.7,
        'related_products' => 0.9,
        'flavor_descriptions' => 0.4,
        'brand_specific' => 1.0
    ];
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        $this->load_keywords();
    }
    
    /**
     * キーワードデータを読み込む
     *
     * @return void
     */
    private function load_keywords() {
        $keyword_file = plugin_dir_path(dirname(__FILE__)) . 'keywords/beer-keywords.json';
        
        if (file_exists($keyword_file)) {
            $json_data = file_get_contents($keyword_file);
            $this->keywords = json_decode($json_data, true);
            
            // キャッシュに保存
            set_transient('hoplink_keywords', $this->keywords, DAY_IN_SECONDS);
        }
    }
    
    /**
     * 記事内容からキーワードを抽出
     *
     * @param string $content 記事内容
     * @return array 抽出されたキーワードと重要度
     */
    public function extract_keywords($content) {
        $found_keywords = [];
        
        // HTMLタグを除去
        $clean_content = wp_strip_all_tags($content);
        
        // 大文字小文字を無視して検索
        $clean_content_lower = mb_strtolower($clean_content);
        
        foreach ($this->keywords as $category => $subcategories) {
            foreach ($subcategories as $subcategory => $keywords) {
                if (is_array($keywords)) {
                    foreach ($keywords as $keyword) {
                        if ($this->contains_keyword($clean_content_lower, $keyword)) {
                            $weight = $this->get_keyword_weight($category, $subcategory);
                            $count = $this->count_keyword_occurrences($clean_content_lower, $keyword);
                            
                            $found_keywords[] = [
                                'keyword' => $keyword,
                                'category' => $category,
                                'subcategory' => $subcategory,
                                'weight' => $weight,
                                'count' => $count,
                                'score' => $weight * log($count + 1) // 対数を使用して頻度の影響を緩和
                            ];
                        }
                    }
                }
            }
        }
        
        // スコアで降順ソート
        usort($found_keywords, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $found_keywords;
    }
    
    /**
     * キーワードが含まれているかチェック
     *
     * @param string $content コンテンツ
     * @param string $keyword キーワード
     * @return bool
     */
    private function contains_keyword($content, $keyword) {
        $keyword_lower = mb_strtolower($keyword);
        return mb_strpos($content, $keyword_lower) !== false;
    }
    
    /**
     * キーワードの出現回数をカウント
     *
     * @param string $content コンテンツ
     * @param string $keyword キーワード
     * @return int
     */
    private function count_keyword_occurrences($content, $keyword) {
        $keyword_lower = mb_strtolower($keyword);
        return mb_substr_count($content, $keyword_lower);
    }
    
    /**
     * キーワードの重み付けを取得
     *
     * @param string $category カテゴリー
     * @param string $subcategory サブカテゴリー
     * @return float
     */
    private function get_keyword_weight($category, $subcategory) {
        if (isset($this->keyword_weights[$category])) {
            if (is_array($this->keyword_weights[$category]) && isset($this->keyword_weights[$category][$subcategory])) {
                return $this->keyword_weights[$category][$subcategory];
            } elseif (is_numeric($this->keyword_weights[$category])) {
                return $this->keyword_weights[$category];
            }
        }
        
        return 0.5; // デフォルト値
    }
    
    /**
     * 商品検索用のクエリを生成
     *
     * @param array $extracted_keywords 抽出されたキーワード
     * @param int $max_keywords 使用する最大キーワード数
     * @return array
     */
    public function generate_search_queries($extracted_keywords, $max_keywords = 5) {
        $queries = [];
        $used_categories = [];
        
        // 上位のキーワードを選択
        $top_keywords = array_slice($extracted_keywords, 0, $max_keywords);
        
        // プライマリクエリ（ブルワリー名や商品名）
        $primary_queries = [];
        $secondary_queries = [];
        
        foreach ($top_keywords as $keyword_data) {
            $keyword = $keyword_data['keyword'];
            $category = $keyword_data['category'];
            
            // カテゴリーに応じて振り分け
            if (in_array($category, ['breweries', 'brand_specific', 'related_products'])) {
                $primary_queries[] = $keyword;
            } else {
                $secondary_queries[] = $keyword;
            }
            
            $used_categories[$category] = true;
        }
        
        // クエリの組み立て
        if (!empty($primary_queries)) {
            $queries['primary'] = implode(' OR ', array_slice($primary_queries, 0, 3));
        }
        
        if (!empty($secondary_queries)) {
            $queries['secondary'] = implode(' ', array_slice($secondary_queries, 0, 2));
        }
        
        // フォールバッククエリ（一般的なキーワード）
        if (empty($queries)) {
            $queries['fallback'] = 'クラフトビール';
        }
        
        return $queries;
    }
    
    /**
     * 関連キーワードを取得
     *
     * @param string $keyword キーワード
     * @return array
     */
    public function get_related_keywords($keyword) {
        $related = [];
        $keyword_lower = mb_strtolower($keyword);
        
        // 同じサブカテゴリー内のキーワードを探す
        foreach ($this->keywords as $category => $subcategories) {
            foreach ($subcategories as $subcategory => $keywords) {
                if (is_array($keywords) && in_array($keyword, $keywords)) {
                    // 同じサブカテゴリーの他のキーワードを追加
                    foreach ($keywords as $related_keyword) {
                        if ($related_keyword !== $keyword) {
                            $related[] = $related_keyword;
                        }
                    }
                    break 2;
                }
            }
        }
        
        return array_slice($related, 0, 5); // 最大5個まで
    }
    
    /**
     * キーワードのサジェスト機能
     *
     * @param string $input 入力文字列
     * @return array
     */
    public function suggest_keywords($input) {
        $suggestions = [];
        $input_lower = mb_strtolower($input);
        
        foreach ($this->keywords as $category => $subcategories) {
            foreach ($subcategories as $subcategory => $keywords) {
                if (is_array($keywords)) {
                    foreach ($keywords as $keyword) {
                        if (mb_strpos(mb_strtolower($keyword), $input_lower) === 0) {
                            $suggestions[] = [
                                'keyword' => $keyword,
                                'category' => $category,
                                'subcategory' => $subcategory
                            ];
                        }
                    }
                }
            }
        }
        
        return array_slice($suggestions, 0, 10); // 最大10個まで
    }
    
    /**
     * キーワードデータを更新
     *
     * @param array $new_keywords 新しいキーワードデータ
     * @return bool
     */
    public function update_keywords($new_keywords) {
        $keyword_file = plugin_dir_path(dirname(__FILE__)) . 'keywords/beer-keywords.json';
        
        // バックアップを作成
        if (file_exists($keyword_file)) {
            $backup_file = $keyword_file . '.backup.' . date('YmdHis');
            copy($keyword_file, $backup_file);
        }
        
        // 新しいデータを保存
        $json_data = json_encode($new_keywords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $result = file_put_contents($keyword_file, $json_data);
        
        if ($result !== false) {
            $this->keywords = $new_keywords;
            delete_transient('hoplink_keywords');
            return true;
        }
        
        return false;
    }
    
    /**
     * キーワード統計情報を取得
     *
     * @return array
     */
    public function get_keyword_stats() {
        $stats = [
            'total_keywords' => 0,
            'categories' => []
        ];
        
        foreach ($this->keywords as $category => $subcategories) {
            $category_count = 0;
            
            foreach ($subcategories as $subcategory => $keywords) {
                if (is_array($keywords)) {
                    $count = count($keywords);
                    $category_count += $count;
                    $stats['categories'][$category][$subcategory] = $count;
                }
            }
            
            $stats['categories'][$category]['total'] = $category_count;
            $stats['total_keywords'] += $category_count;
        }
        
        return $stats;
    }
}