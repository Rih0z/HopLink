<?php
/**
 * Cross Platform Default Template
 *
 * クロスプラットフォーム商品表示のデフォルトテンプレート
 *
 * @package HopLink
 * @subpackage Templates
 * @since 1.0.0
 *
 * @var array $rakuten_product 楽天商品情報
 * @var array $amazon_product Amazon商品情報
 * @var bool $show_both 両方表示するか
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="hoplink-cross-platform-container">
    <?php if ($show_both && $rakuten_product && $amazon_product): ?>
        <!-- 両方の商品を表示 -->
        <div class="hoplink-cross-products">
            <!-- 楽天商品 -->
            <div class="hoplink-product hoplink-rakuten-product">
                <div class="hoplink-product-badge">楽天市場</div>
                <?php if (!empty($rakuten_product['image_url'])): ?>
                    <div class="hoplink-product-image">
                        <img src="<?php echo esc_url($rakuten_product['image_url']); ?>" 
                             alt="<?php echo esc_attr($rakuten_product['name'] ?? ''); ?>"
                             loading="lazy">
                    </div>
                <?php endif; ?>
                
                <div class="hoplink-product-info">
                    <h3 class="hoplink-product-title">
                        <?php echo esc_html($rakuten_product['name'] ?? ''); ?>
                    </h3>
                    
                    <?php if (!empty($rakuten_product['price'])): ?>
                        <div class="hoplink-product-price">
                            <span class="hoplink-price-label">価格:</span>
                            <span class="hoplink-price-value">¥<?php echo number_format($rakuten_product['price']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($rakuten_product['shop_name'])): ?>
                        <div class="hoplink-product-shop">
                            <span class="hoplink-shop-label">ショップ:</span>
                            <span class="hoplink-shop-name"><?php echo esc_html($rakuten_product['shop_name']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($rakuten_product['review_average']) && $rakuten_product['review_average'] > 0): ?>
                        <div class="hoplink-product-rating">
                            <span class="hoplink-rating-stars" data-rating="<?php echo esc_attr($rakuten_product['review_average']); ?>">
                                <?php echo str_repeat('★', round($rakuten_product['review_average'])); ?>
                            </span>
                            <span class="hoplink-rating-count">(<?php echo number_format($rakuten_product['review_count'] ?? 0); ?>件)</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="hoplink-product-action">
                        <a href="<?php echo esc_url($rakuten_product['affiliate_url'] ?? $rakuten_product['url'] ?? '#'); ?>" 
                           class="hoplink-button hoplink-rakuten-button"
                           target="_blank"
                           rel="noopener noreferrer sponsored"
                           data-product-id="<?php echo esc_attr($rakuten_product['item_code'] ?? ''); ?>"
                           data-platform="rakuten">
                            楽天市場で見る
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Amazon商品 -->
            <div class="hoplink-product hoplink-amazon-product">
                <div class="hoplink-product-badge">Amazon</div>
                <?php if (!empty($amazon_product['image_url'])): ?>
                    <div class="hoplink-product-image">
                        <img src="<?php echo esc_url($amazon_product['image_url']); ?>" 
                             alt="<?php echo esc_attr($amazon_product['title'] ?? ''); ?>"
                             loading="lazy">
                    </div>
                <?php endif; ?>
                
                <div class="hoplink-product-info">
                    <h3 class="hoplink-product-title">
                        <?php echo esc_html($amazon_product['title'] ?? ''); ?>
                    </h3>
                    
                    <?php if (!empty($amazon_product['price'])): ?>
                        <div class="hoplink-product-price">
                            <span class="hoplink-price-label">価格:</span>
                            <span class="hoplink-price-value">¥<?php echo number_format($amazon_product['price']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($amazon_product['availability'])): ?>
                        <div class="hoplink-product-availability">
                            <span class="hoplink-availability-status"><?php echo esc_html($amazon_product['availability']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($amazon_product['rating']) && $amazon_product['rating'] > 0): ?>
                        <div class="hoplink-product-rating">
                            <span class="hoplink-rating-stars" data-rating="<?php echo esc_attr($amazon_product['rating']); ?>">
                                <?php echo str_repeat('★', round($amazon_product['rating'])); ?>
                            </span>
                            <span class="hoplink-rating-count">(<?php echo number_format($amazon_product['review_count'] ?? 0); ?>件)</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($amazon_product['match_score'])): ?>
                        <div class="hoplink-match-info">
                            <span class="hoplink-match-label">マッチ度:</span>
                            <span class="hoplink-match-score"><?php echo round($amazon_product['match_score'] * 100); ?>%</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="hoplink-product-action">
                        <a href="<?php echo esc_url($amazon_product['affiliate_url'] ?? $amazon_product['url'] ?? '#'); ?>" 
                           class="hoplink-button hoplink-amazon-button"
                           target="_blank"
                           rel="noopener noreferrer sponsored"
                           data-product-id="<?php echo esc_attr($amazon_product['asin'] ?? ''); ?>"
                           data-platform="amazon">
                            Amazonで見る
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($rakuten_product): ?>
        <!-- 楽天商品のみ表示 -->
        <div class="hoplink-single-product hoplink-rakuten-only">
            <div class="hoplink-product hoplink-rakuten-product">
                <div class="hoplink-product-badge">楽天市場</div>
                <?php if (!empty($rakuten_product['image_url'])): ?>
                    <div class="hoplink-product-image">
                        <img src="<?php echo esc_url($rakuten_product['image_url']); ?>" 
                             alt="<?php echo esc_attr($rakuten_product['name'] ?? ''); ?>"
                             loading="lazy">
                    </div>
                <?php endif; ?>
                
                <div class="hoplink-product-info">
                    <h3 class="hoplink-product-title">
                        <?php echo esc_html($rakuten_product['name'] ?? ''); ?>
                    </h3>
                    
                    <?php if (!empty($rakuten_product['price'])): ?>
                        <div class="hoplink-product-price">
                            <span class="hoplink-price-label">価格:</span>
                            <span class="hoplink-price-value">¥<?php echo number_format($rakuten_product['price']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="hoplink-product-action">
                        <a href="<?php echo esc_url($rakuten_product['affiliate_url'] ?? $rakuten_product['url'] ?? '#'); ?>" 
                           class="hoplink-button hoplink-rakuten-button"
                           target="_blank"
                           rel="noopener noreferrer sponsored">
                            楽天市場で見る
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="hoplink-no-match-notice">
                <p>※ この商品のAmazonでの取り扱いは見つかりませんでした。</p>
            </div>
        </div>
        
    <?php elseif ($amazon_product): ?>
        <!-- Amazon商品のみ表示 -->
        <div class="hoplink-single-product hoplink-amazon-only">
            <div class="hoplink-product hoplink-amazon-product">
                <div class="hoplink-product-badge">Amazon</div>
                <?php if (!empty($amazon_product['image_url'])): ?>
                    <div class="hoplink-product-image">
                        <img src="<?php echo esc_url($amazon_product['image_url']); ?>" 
                             alt="<?php echo esc_attr($amazon_product['title'] ?? ''); ?>"
                             loading="lazy">
                    </div>
                <?php endif; ?>
                
                <div class="hoplink-product-info">
                    <h3 class="hoplink-product-title">
                        <?php echo esc_html($amazon_product['title'] ?? ''); ?>
                    </h3>
                    
                    <?php if (!empty($amazon_product['price'])): ?>
                        <div class="hoplink-product-price">
                            <span class="hoplink-price-label">価格:</span>
                            <span class="hoplink-price-value">¥<?php echo number_format($amazon_product['price']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="hoplink-product-action">
                        <a href="<?php echo esc_url($amazon_product['affiliate_url'] ?? $amazon_product['url'] ?? '#'); ?>" 
                           class="hoplink-button hoplink-amazon-button"
                           target="_blank"
                           rel="noopener noreferrer sponsored">
                            Amazonで見る
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>