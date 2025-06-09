<?php
/**
 * キーワード管理クラス - 優先度付きキーワード解析
 *
 * @package HopLink
 * @since 1.0.0
 */

/**
 * Class HopLink_Keyword_Manager
 * 
 * ビール関連キーワードの管理と優先度付き記事解析を行うクラス
 */
class HopLink_Keyword_Manager {
    
    /**
     * キーワードデータ
     *
     * @var array
     */
    private $keywords_data = null;
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        $this->load_keywords();
    }
    
    /**
     * キーワードデータを読み込み
     */
    private function load_keywords() {
        $keywords_file = HOPLINK_PLUGIN_DIR . 'keywords/beer-keywords.json';
        
        if (file_exists($keywords_file)) {
            $json = file_get_contents($keywords_file);
            $this->keywords_data = json_decode($json, true);
        }
        
        if (!$this->keywords_data) {
            $this->keywords_data = $this->get_default_keywords();
        }
    }
    
    /**
     * デフォルトキーワードを取得
     */
    private function get_default_keywords() {
        return array(
            'priority_weights' => array(
                'brewery' => 1.0,
                'beer_style' => 0.9,
                'location' => 0.8,
                'ingredient' => 0.7,
                'brewing_term' => 0.6,
                'flavor' => 0.5,
                'product' => 0.4,
                'general' => 0.1
            ),
            'breweries' => array('priority' => 1.0, 'japanese' => array(), 'international' => array()),
            'beer_styles' => array('priority' => 0.9),
            'ingredients' => array('priority' => 0.7),
            'brewing_terms' => array('priority' => 0.6),
            'locations' => array('priority' => 0.8),
            'people' => array('priority' => 0.7),
            'events_competitions' => array('priority' => 0.6),
            'product_categories' => array('priority' => 0.4),
            'related_products' => array('priority' => 0.4),
            'flavor_descriptions' => array('priority' => 0.5),
            'brand_specific' => array('priority' => 0.6),
            'general_terms' => array('priority' => 0.1)
        );
    }
    
    /**
     * キーワードの重要度スコアを算出
     */
    public function calculate_keyword_importance($keywords, $content) {
        $keyword_scores = array();
        $text = strtolower(wp_strip_all_tags($content));
        
        foreach ($keywords as $keyword) {
            $score = 0;
            $frequency = substr_count($text, strtolower($keyword));
            
            if ($frequency > 0) {
                // 基本スコア（出現頻度）
                $score = $frequency;
                
                // カテゴリ別の重要度重み付け
                $category_priority = $this->get_keyword_priority($keyword);
                $score *= $category_priority;
                
                // キーワードの長さによる調整（長いキーワードほど重要）
                $length_bonus = strlen($keyword) / 10;
                $score += $length_bonus;
                
                // 位置による重み付け（タイトルに含まれるかどうか）
                if (is_array($content) && isset($content['title']) && stripos($content['title'], $keyword) !== false) {
                    $score *= 1.5; // タイトルに含まれる場合は1.5倍
                }
                
                $keyword_scores[$keyword] = $score;
            }
        }
        
        // スコア順でソート
        arsort($keyword_scores);
        
        return $keyword_scores;
    }
    
    /**
     * キーワードの優先度を取得
     */
    public function get_keyword_priority($keyword) {
        foreach ($this->keywords_data as $category => $data) {
            if ($category === 'priority_weights') continue;
            
            $found_priority = $this->search_keyword_in_category($keyword, $data);
            if ($found_priority !== null) {
                return $found_priority;
            }
        }
        
        // デフォルト優先度
        return 0.1;
    }
    
    /**
     * カテゴリ内でキーワードを検索し、優先度を取得
     */
    private function search_keyword_in_category($keyword, $category_data) {
        if (isset($category_data['priority'])) {
            $base_priority = $category_data['priority'];
            
            // ネストした構造を再帰的に検索
            foreach ($category_data as $key => $value) {
                if ($key === 'priority') continue;
                
                if (is_array($value)) {
                    // keywords配列がある場合
                    if (isset($value['keywords']) && in_array($keyword, $value['keywords'])) {
                        return isset($value['priority']) ? $value['priority'] : $base_priority;
                    }
                    // 直接配列の場合
                    elseif (in_array($keyword, $value)) {
                        return $base_priority;
                    }
                    // さらに深い構造の場合
                    else {
                        $result = $this->search_keyword_in_category($keyword, $value);
                        if ($result !== null) {
                            return $result;
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * 多様化されたキーワードを抽出（4つの異なるキーワード）
     */
    public function extract_diverse_keywords($content, $limit = 4) {
        $all_keywords = $this->get_all_keywords();
        $keyword_scores = $this->calculate_keyword_importance($all_keywords, $content);
        
        if (empty($keyword_scores)) {
            return array();
        }
        
        $diverse_keywords = array();
        $used_categories = array();
        
        // 異なるカテゴリから上位キーワードを選択
        foreach ($keyword_scores as $keyword => $score) {
            if (count($diverse_keywords) >= $limit) {
                break;
            }
            
            $category = $this->get_keyword_category($keyword);
            
            // 同じカテゴリから2つ以上選ばないようにする（ただし、ブルワリーとビアスタイルは例外）
            if (!in_array($category, $used_categories) || 
                ($category === 'breweries' || $category === 'beer_styles') && count(array_filter($used_categories, function($cat) use ($category) { return $cat === $category; })) < 2) {
                
                $diverse_keywords[] = array(
                    'keyword' => $keyword,
                    'score' => $score,
                    'category' => $category,
                    'priority' => $this->get_keyword_priority($keyword)
                );
                
                $used_categories[] = $category;
            }
        }
        
        // 不足分を補完
        if (count($diverse_keywords) < $limit) {
            foreach ($keyword_scores as $keyword => $score) {
                if (count($diverse_keywords) >= $limit) {
                    break;
                }
                
                // まだ選ばれていないキーワードを追加
                $already_selected = array_column($diverse_keywords, 'keyword');
                if (!in_array($keyword, $already_selected)) {
                    $diverse_keywords[] = array(
                        'keyword' => $keyword,
                        'score' => $score,
                        'category' => $this->get_keyword_category($keyword),
                        'priority' => $this->get_keyword_priority($keyword)
                    );
                }
            }
        }
        
        return $diverse_keywords;
    }
    
    /**
     * プラットフォーム別に最適化されたキーワードを生成
     */
    public function get_platform_optimized_keywords($base_keywords, $platform) {
        $optimized_keywords = array();
        
        foreach ($base_keywords as $keyword_data) {
            $keyword = $keyword_data['keyword'];
            $category = $keyword_data['category'];
            
            if ($platform === 'rakuten') {
                // 楽天向けの最適化
                $optimized_keywords[] = $this->optimize_for_rakuten($keyword, $category);
            } elseif ($platform === 'amazon') {
                // Amazon向けの最適化
                $optimized_keywords[] = $this->optimize_for_amazon($keyword, $category);
            } else {
                $optimized_keywords[] = $keyword;
            }
        }
        
        return array_unique($optimized_keywords);
    }
    
    /**
     * 楽天向けキーワード最適化
     */
    private function optimize_for_rakuten($keyword, $category) {
        $rakuten_mappings = array(
            'IPA' => 'IPA ビール',
            'スタウト' => 'スタウト 黒ビール',
            'ピルスナー' => 'ピルスナー ビール',
            'クラフトビール' => 'クラフトビール 地ビール',
            'ビールグラス' => 'ビアグラス'
        );
        
        if (isset($rakuten_mappings[$keyword])) {
            return $rakuten_mappings[$keyword];
        }
        
        // カテゴリ別の追加キーワード
        switch ($category) {
            case 'beer_styles':
                return $keyword . ' ビール';
            case 'breweries':
                return $keyword . ' クラフトビール';
            case 'related_products':
                return $keyword;
            default:
                return $keyword;
        }
    }
    
    /**
     * Amazon向けキーワード最適化
     */
    private function optimize_for_amazon($keyword, $category) {
        $amazon_mappings = array(
            'IPA' => 'IPA beer',
            'スタウト' => 'stout beer',
            'ピルスナー' => 'pilsner',
            'クラフトビール' => 'craft beer',
            'ビールグラス' => 'beer glass'
        );
        
        if (isset($amazon_mappings[$keyword])) {
            return $amazon_mappings[$keyword];
        }
        
        // カテゴリ別の追加キーワード
        switch ($category) {
            case 'beer_styles':
                return $keyword;
            case 'breweries':
                return $keyword . ' beer';
            case 'related_products':
                return $keyword;
            default:
                return $keyword;
        }
    }
    
    /**
     * 特定カテゴリのキーワードを取得
     */
    public function get_keywords_by_category($category) {
        if (!isset($this->keywords_data[$category])) {
            return array();
        }
        
        $category_data = $this->keywords_data[$category];
        return $this->extract_keywords_from_data($category_data);
    }
    
    /**
     * データ構造からキーワードを抽出
     */
    private function extract_keywords_from_data($data) {
        $keywords = array();
        
        foreach ($data as $key => $value) {
            if ($key === 'priority') continue;
            
            if (is_array($value)) {
                if (isset($value['keywords'])) {
                    $keywords = array_merge($keywords, $value['keywords']);
                } else {
                    $keywords = array_merge($keywords, $this->extract_keywords_from_data($value));
                }
            }
        }
        
        return $keywords;
    }
    
    /**
     * 全キーワードを取得
     */
    public function get_all_keywords() {
        $all_keywords = array();
        
        foreach ($this->keywords_data as $category => $data) {
            if ($category === 'priority_weights') continue;
            
            $category_keywords = $this->extract_keywords_from_data($data);
            $all_keywords = array_merge($all_keywords, $category_keywords);
        }
        
        return array_unique($all_keywords);
    }
    
    /**
     * キーワードのカテゴリを判定
     */
    public function get_keyword_category($keyword) {
        foreach ($this->keywords_data as $category => $data) {
            if ($category === 'priority_weights') continue;
            
            $category_keywords = $this->extract_keywords_from_data($data);
            if (in_array($keyword, $category_keywords)) {
                return $category;
            }
        }
        
        return 'unknown';
    }
    
    /**
     * キーワードを検索
     */
    public function search_keywords($query, $limit = 10) {
        $all_keywords = $this->get_all_keywords();
        $results = array();
        
        foreach ($all_keywords as $keyword) {
            if (stripos($keyword, $query) !== false) {
                $results[] = $keyword;
            }
        }
        
        return array_slice($results, 0, $limit);
    }
    
    /**
     * キーワードをカテゴリ別に分類
     */
    public function categorize_keywords($keywords) {
        $categorized = array();
        
        foreach ($keywords as $keyword) {
            $category = $this->get_keyword_category($keyword);
            if (!isset($categorized[$category])) {
                $categorized[$category] = array();
            }
            $categorized[$category][] = $keyword;
        }
        
        return $categorized;
    }
}