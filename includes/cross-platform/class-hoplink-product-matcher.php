<?php
/**
 * Product Matcher Class
 *
 * 楽天とAmazonの商品をマッチングするためのロジックを提供するクラス
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
 * HopLink_Product_Matcher クラス
 */
class HopLink_Product_Matcher {

    /**
     * マッチング精度の定数
     */
    const MATCH_STRICT = 'strict';      // 厳密なマッチング
    const MATCH_NORMAL = 'normal';      // 通常のマッチング
    const MATCH_LOOSE = 'loose';        // ゆるいマッチング

    /**
     * 類似度のしきい値
     *
     * @var array
     */
    private $similarity_thresholds = [
        self::MATCH_STRICT => 0.9,
        self::MATCH_NORMAL => 0.7,
        self::MATCH_LOOSE => 0.5,
    ];

    /**
     * 楽天商品とAmazon商品をマッチング
     *
     * @param array $rakuten_product 楽天商品情報
     * @param array $amazon_candidates Amazon商品候補
     * @param string $match_mode マッチングモード
     * @return array|false マッチした商品情報、またはマッチしなかった場合はfalse
     */
    public function match_products($rakuten_product, $amazon_candidates, $match_mode = self::MATCH_NORMAL) {
        if (empty($rakuten_product) || empty($amazon_candidates)) {
            return false;
        }

        $best_match = null;
        $best_score = 0;
        $threshold = $this->similarity_thresholds[$match_mode] ?? 0.7;

        foreach ($amazon_candidates as $amazon_product) {
            $score = $this->calculate_similarity($rakuten_product, $amazon_product);
            
            if ($score >= $threshold && $score > $best_score) {
                $best_score = $score;
                $best_match = $amazon_product;
                $best_match['match_score'] = $score;
                $best_match['match_details'] = $this->get_match_details($rakuten_product, $amazon_product);
            }
        }

        return $best_match;
    }

    /**
     * 2つの商品の類似度を計算
     *
     * @param array $rakuten_product 楽天商品
     * @param array $amazon_product Amazon商品
     * @return float 類似度スコア（0-1）
     */
    private function calculate_similarity($rakuten_product, $amazon_product) {
        $scores = [];
        $weights = $this->get_weight_factors();

        // JANコードの一致
        if (!empty($rakuten_product['jan_code']) && !empty($amazon_product['jan_code'])) {
            $scores['jan'] = ($rakuten_product['jan_code'] === $amazon_product['jan_code']) ? 1.0 : 0.0;
        }

        // 商品名の類似度
        if (!empty($rakuten_product['name']) && !empty($amazon_product['title'])) {
            $scores['name'] = $this->calculate_string_similarity(
                $this->normalize_product_name($rakuten_product['name']),
                $this->normalize_product_name($amazon_product['title'])
            );
        }

        // 価格の類似度
        if (!empty($rakuten_product['price']) && !empty($amazon_product['price'])) {
            $scores['price'] = $this->calculate_price_similarity(
                $rakuten_product['price'],
                $amazon_product['price']
            );
        }

        // ブランド名の一致
        if (!empty($rakuten_product['brand']) && !empty($amazon_product['brand'])) {
            $scores['brand'] = $this->calculate_string_similarity(
                $rakuten_product['brand'],
                $amazon_product['brand']
            );
        }

        // 重み付き平均を計算
        $total_score = 0;
        $total_weight = 0;

        foreach ($scores as $factor => $score) {
            if (isset($weights[$factor])) {
                $total_score += $score * $weights[$factor];
                $total_weight += $weights[$factor];
            }
        }

        return $total_weight > 0 ? $total_score / $total_weight : 0;
    }

    /**
     * 重み係数を取得
     *
     * @return array 重み係数
     */
    private function get_weight_factors() {
        return [
            'jan' => 5.0,      // JANコードは最重要
            'name' => 3.0,     // 商品名も重要
            'price' => 1.0,    // 価格は補助的
            'brand' => 2.0,    // ブランドは中程度
        ];
    }

    /**
     * 文字列の類似度を計算
     *
     * @param string $str1 文字列1
     * @param string $str2 文字列2
     * @return float 類似度（0-1）
     */
    private function calculate_string_similarity($str1, $str2) {
        if (empty($str1) || empty($str2)) {
            return 0;
        }

        // 完全一致
        if ($str1 === $str2) {
            return 1.0;
        }

        // レーベンシュタイン距離を使用
        $distance = levenshtein($str1, $str2);
        $max_length = max(mb_strlen($str1), mb_strlen($str2));
        
        if ($max_length === 0) {
            return 0;
        }

        $similarity = 1 - ($distance / $max_length);
        
        // N-gram類似度も計算
        $ngram_similarity = $this->calculate_ngram_similarity($str1, $str2, 2);
        
        // 両方の類似度の平均を取る
        return ($similarity + $ngram_similarity) / 2;
    }

    /**
     * N-gram類似度を計算
     *
     * @param string $str1 文字列1
     * @param string $str2 文字列2
     * @param int $n N-gramのサイズ
     * @return float 類似度（0-1）
     */
    private function calculate_ngram_similarity($str1, $str2, $n = 2) {
        $ngrams1 = $this->get_ngrams($str1, $n);
        $ngrams2 = $this->get_ngrams($str2, $n);
        
        if (empty($ngrams1) || empty($ngrams2)) {
            return 0;
        }
        
        $intersection = array_intersect($ngrams1, $ngrams2);
        $union = array_unique(array_merge($ngrams1, $ngrams2));
        
        return count($intersection) / count($union);
    }

    /**
     * 文字列からN-gramを生成
     *
     * @param string $str 文字列
     * @param int $n N-gramのサイズ
     * @return array N-gramの配列
     */
    private function get_ngrams($str, $n) {
        $ngrams = [];
        $length = mb_strlen($str);
        
        for ($i = 0; $i <= $length - $n; $i++) {
            $ngrams[] = mb_substr($str, $i, $n);
        }
        
        return $ngrams;
    }

    /**
     * 価格の類似度を計算
     *
     * @param int $price1 価格1
     * @param int $price2 価格2
     * @return float 類似度（0-1）
     */
    private function calculate_price_similarity($price1, $price2) {
        if ($price1 <= 0 || $price2 <= 0) {
            return 0;
        }
        
        // 価格差の割合を計算
        $diff_ratio = abs($price1 - $price2) / max($price1, $price2);
        
        // 10%以内の差なら高スコア
        if ($diff_ratio <= 0.1) {
            return 1.0;
        }
        // 20%以内なら中スコア
        elseif ($diff_ratio <= 0.2) {
            return 0.8;
        }
        // 30%以内なら低スコア
        elseif ($diff_ratio <= 0.3) {
            return 0.5;
        }
        // それ以上は非常に低いスコア
        else {
            return max(0, 1 - $diff_ratio);
        }
    }

    /**
     * 商品名を正規化
     *
     * @param string $name 商品名
     * @return string 正規化された商品名
     */
    private function normalize_product_name($name) {
        // 小文字に変換
        $name = mb_strtolower($name);
        
        // 全角英数字を半角に変換
        $name = mb_convert_kana($name, 'a');
        
        // 不要な記号やスペースを除去
        $name = preg_replace('/[【】\[\]()（）「」『』【】［］｛｝〈〉《》〔〕]/u', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        
        // 一般的な修飾語を除去
        $remove_words = [
            '送料無料', 'ポイント', '倍', 'セール', '在庫あり', '即納',
            '新品', '正規品', '国内正規', '並行輸入', '限定', '特価',
        ];
        
        foreach ($remove_words as $word) {
            $name = str_replace($word, '', $name);
        }
        
        return trim($name);
    }

    /**
     * マッチングの詳細情報を取得
     *
     * @param array $rakuten_product 楽天商品
     * @param array $amazon_product Amazon商品
     * @return array マッチング詳細
     */
    private function get_match_details($rakuten_product, $amazon_product) {
        $details = [
            'match_factors' => [],
            'confidence' => 'low',
        ];
        
        // JANコードの一致
        if (!empty($rakuten_product['jan_code']) && 
            !empty($amazon_product['jan_code']) && 
            $rakuten_product['jan_code'] === $amazon_product['jan_code']) {
            $details['match_factors'][] = 'jan_code';
            $details['confidence'] = 'high';
        }
        
        // 商品名の類似度
        if (!empty($rakuten_product['name']) && !empty($amazon_product['title'])) {
            $name_similarity = $this->calculate_string_similarity(
                $this->normalize_product_name($rakuten_product['name']),
                $this->normalize_product_name($amazon_product['title'])
            );
            
            if ($name_similarity >= 0.8) {
                $details['match_factors'][] = 'product_name';
                if ($details['confidence'] === 'low') {
                    $details['confidence'] = 'medium';
                }
            }
        }
        
        // 価格の近似
        if (!empty($rakuten_product['price']) && !empty($amazon_product['price'])) {
            $price_similarity = $this->calculate_price_similarity(
                $rakuten_product['price'],
                $amazon_product['price']
            );
            
            if ($price_similarity >= 0.8) {
                $details['match_factors'][] = 'price';
            }
        }
        
        return $details;
    }

    /**
     * バッチマッチング（複数商品を一度にマッチング）
     *
     * @param array $rakuten_products 楽天商品の配列
     * @param string $match_mode マッチングモード
     * @return array マッチング結果の配列
     */
    public function batch_match($rakuten_products, $match_mode = self::MATCH_NORMAL) {
        $results = [];
        $amazon_search = new HopLink_Amazon_Search();
        
        foreach ($rakuten_products as $rakuten_product) {
            // Amazon商品を検索
            $amazon_candidates = $amazon_search->find_similar_products($rakuten_product);
            
            // マッチング実行
            $match = $this->match_products($rakuten_product, $amazon_candidates, $match_mode);
            
            $results[] = [
                'rakuten' => $rakuten_product,
                'amazon' => $match,
                'matched' => !empty($match),
            ];
        }
        
        return $results;
    }

    /**
     * マッチング結果の検証
     *
     * @param array $match_result マッチング結果
     * @return bool 妥当性のチェック結果
     */
    public function validate_match($match_result) {
        if (empty($match_result) || empty($match_result['match_score'])) {
            return false;
        }
        
        // スコアが極端に低い場合は無効
        if ($match_result['match_score'] < 0.3) {
            return false;
        }
        
        // JANコードが異なる場合は慎重に判断
        if (!empty($match_result['rakuten']['jan_code']) && 
            !empty($match_result['amazon']['jan_code']) && 
            $match_result['rakuten']['jan_code'] !== $match_result['amazon']['jan_code']) {
            // スコアが非常に高い場合のみ許可
            return $match_result['match_score'] >= 0.9;
        }
        
        return true;
    }
}