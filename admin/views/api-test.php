<?php
/**
 * API Test page view
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="hoplink-api-test">
        <div class="hoplink-test-section">
            <h2>Amazon PA-API Test</h2>
            
            <!-- Connection Test -->
            <div class="test-card">
                <h3>1. Connection Test</h3>
                <p>Test basic connection to Amazon PA-API with your credentials.</p>
                <button id="test-connection" class="button button-primary">Test Connection</button>
                <div id="connection-results" class="test-results"></div>
            </div>
            
            <!-- Search Test -->
            <div class="test-card">
                <h3>2. Product Search Test</h3>
                <p>Search for products using a keyword.</p>
                <div class="test-form">
                    <input type="text" id="search-keyword" placeholder="Enter keyword (e.g., beer, IPA)" value="beer" />
                    <button id="test-search" class="button button-primary">Search Products</button>
                </div>
                <div id="search-results" class="test-results"></div>
            </div>
            
            <!-- Cache Management -->
            <div class="test-card">
                <h3>3. Cache Management</h3>
                <p>View and manage product cache.</p>
                <button id="clear-cache" class="button">Clear All Cache</button>
                <div id="cache-results" class="test-results"></div>
            </div>
            
            <!-- Shortcode Test -->
            <div class="test-card">
                <h3>4. Shortcode Preview</h3>
                <p>Preview how the shortcode will display products.</p>
                <div class="shortcode-example">
                    <code>[hoplink keyword="beer"]</code>
                </div>
                <p>Copy this shortcode and paste it into any post or page.</p>
            </div>
        </div>
        
        <!-- Product Search Test Section -->
        <div class="hoplink-test-section">
            <h2>商品検索テスト</h2>
            
            <div class="test-card">
                <h3>実際の商品検索テスト</h3>
                <p>Amazon・楽天の両方でクラフトビール商品を検索し、結果を比較表示します。</p>
                
                <div class="test-form">
                    <label for="product-search-keyword">検索キーワード:</label>
                    <input type="text" id="product-search-keyword" placeholder="例: クラフトビール, IPA" value="クラフトビール" />
                    <button id="test-product-search" class="button button-primary">商品を検索</button>
                </div>
                
                <!-- Search Results Container -->
                <div id="product-search-results" style="display: none;">
                    <div class="search-results-header">
                        <h4>検索結果</h4>
                        <div class="search-stats">
                            <span id="search-stats-info"></span>
                        </div>
                    </div>
                    
                    <!-- Amazon Results -->
                    <div class="platform-results">
                        <h5>Amazon 検索結果</h5>
                        <div id="amazon-search-results" class="products-grid"></div>
                    </div>
                    
                    <!-- Rakuten Results -->
                    <div class="platform-results">
                        <h5>楽天市場 検索結果</h5>
                        <div id="rakuten-search-results" class="products-grid"></div>
                    </div>
                </div>
                
                <!-- Cache Status -->
                <div id="cache-status" class="cache-info" style="display: none;">
                    <h4>キャッシュ情報</h4>
                    <div id="cache-status-content"></div>
                </div>
            </div>
        </div>
        
        <!-- Rakuten API Test Section -->
        <div class="hoplink-test-section">
            <h2>楽天API テスト</h2>
            
            <!-- Connection Test -->
            <div class="test-card">
                <h3>1. 接続テスト</h3>
                <p>楽天APIへの基本的な接続をテストします。</p>
                <button id="test-rakuten-connection" class="button button-primary">接続テスト</button>
                <div id="rakuten-connection-results" class="test-results"></div>
            </div>
            
            <!-- Search Test -->
            <div class="test-card">
                <h3>2. 商品検索テスト</h3>
                <p>キーワードで商品を検索します。</p>
                <div class="test-form">
                    <input type="text" id="rakuten-search-keyword" placeholder="キーワードを入力 (例: ビール)" value="ビール" />
                    <button id="test-rakuten-search" class="button button-primary">商品を検索</button>
                </div>
                <div id="rakuten-search-results" class="test-results"></div>
            </div>
        </div>
        
        <!-- Debug Information -->
        <div class="hoplink-debug-info">
            <h3>Debug Information</h3>
            <div class="debug-content">
                <?php
                $amazon_api = new HopLink_Amazon_API();
                $rakuten_api = new HopLink_Rakuten_API();
                $debug_info = [
                    'Amazon Settings' => [
                        'Access Key Configured' => !empty(get_option('hoplink_amazon_access_key')) ? 'Yes' : 'No',
                        'Secret Key Configured' => !empty(get_option('hoplink_amazon_secret_key')) ? 'Yes' : 'No',
                        'Associate Tag' => get_option('hoplink_amazon_associate_tag') ?: 'Not set',
                        'Region' => 'ap-northeast-1 (Japan)',
                        'Endpoint' => 'webservices.amazon.co.jp',
                    ],
                    'Rakuten Settings' => [
                        'Application ID Configured' => !empty(get_option('hoplink_rakuten_application_id')) ? 'Yes' : 'No',
                        'Affiliate ID' => get_option('hoplink_rakuten_affiliate_id') ?: 'Not set',
                        'Endpoint' => 'app.rakuten.co.jp',
                    ],
                    'General Settings' => [
                        'API Mode' => get_option('hoplink_api_mode', 'hybrid'),
                        'Cache Enabled' => get_option('hoplink_cache_enabled', true) ? 'Yes' : 'No',
                        'Cache Duration' => get_option('hoplink_cache_duration', 604800) . ' seconds',
                        'Debug Mode' => get_option('hoplink_debug_mode', false) ? 'Yes' : 'No',
                    ]
                ];
                
                foreach ($debug_info as $section => $settings): ?>
                    <h4><?php echo esc_html($section); ?></h4>
                    <?php foreach ($settings as $label => $value): ?>
                        <div class="debug-row">
                            <strong><?php echo esc_html($label); ?>:</strong> 
                            <span><?php echo esc_html($value); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.hoplink-api-test {
    margin-top: 20px;
}

.test-card {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.test-card h3 {
    margin-top: 0;
}

.test-form {
    margin: 10px 0;
}

.test-form input[type="text"] {
    width: 300px;
    margin-right: 10px;
}

.test-results {
    margin-top: 15px;
    padding: 15px;
    background: #f5f5f5;
    border-left: 4px solid #0073aa;
    display: none;
}

.test-results.error {
    border-left-color: #dc3232;
}

.test-results.success {
    border-left-color: #46b450;
}

.shortcode-example {
    background: #f5f5f5;
    padding: 10px;
    margin: 10px 0;
    font-family: monospace;
}

.hoplink-debug-info {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.hoplink-debug-info h4 {
    margin: 15px 0 10px 0;
    color: #23282d;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}

.hoplink-debug-info h4:first-child {
    margin-top: 0;
}

.debug-row {
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}

.debug-row:last-child {
    border-bottom: none;
}

.product-item {
    border: 1px solid #ddd;
    padding: 15px;
    margin: 10px 0;
    background: #fff;
}

.product-item img {
    max-width: 100px;
    height: auto;
    float: left;
    margin-right: 15px;
}

.product-info {
    overflow: hidden;
}

.product-title {
    font-weight: bold;
    margin-bottom: 5px;
}

.product-price {
    color: #e47911;
    font-size: 1.2em;
    margin: 5px 0;
}

.product-features {
    font-size: 0.9em;
    color: #666;
}

.loading {
    display: inline-block;
    margin-left: 10px;
}

.loading:after {
    content: '...';
    animation: dots 1.5s steps(5, end) infinite;
}

@keyframes dots {
    0%, 20% {
        color: rgba(0,0,0,0);
        text-shadow:
            .25em 0 0 rgba(0,0,0,0),
            .5em 0 0 rgba(0,0,0,0);
    }
    40% {
        color: black;
        text-shadow:
            .25em 0 0 rgba(0,0,0,0),
            .5em 0 0 rgba(0,0,0,0);
    }
    60% {
        text-shadow:
            .25em 0 0 black,
            .5em 0 0 rgba(0,0,0,0);
    }
    80%, 100% {
        text-shadow:
            .25em 0 0 black,
            .5em 0 0 black;
    }
}

/* Product Search Test Styles */
.hoplink-test-section {
    margin-top: 30px;
}

.hoplink-test-section h2 {
    background: #f5f5f5;
    padding: 10px 15px;
    margin: 0 0 20px 0;
    border-left: 4px solid #0073aa;
}

#product-search-results {
    margin-top: 20px;
}

.search-results-header {
    background: #f9f9f9;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
}

.search-stats {
    margin-top: 10px;
    font-size: 14px;
    color: #666;
}

.platform-results {
    margin-bottom: 30px;
}

.platform-results h5 {
    background: #fff;
    padding: 10px;
    margin: 0 0 15px 0;
    border: 1px solid #ddd;
    border-left: 4px solid #0073aa;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.product-card {
    background: #fff;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

.product-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.product-card img {
    max-width: 100%;
    height: 200px;
    object-fit: contain;
    margin-bottom: 10px;
}

.product-card .product-title {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 10px;
    line-height: 1.4;
    max-height: 2.8em;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-card .product-price {
    color: #e47911;
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 10px;
}

.product-card .product-platform {
    display: inline-block;
    padding: 3px 8px;
    font-size: 12px;
    border-radius: 3px;
    margin-bottom: 10px;
}

.product-platform.amazon {
    background: #ff9900;
    color: #fff;
}

.product-platform.rakuten {
    background: #bf0000;
    color: #fff;
}

.product-card .product-meta {
    font-size: 12px;
    color: #666;
    margin-bottom: 10px;
}

.product-card .product-link {
    display: inline-block;
    padding: 8px 15px;
    background: #0073aa;
    color: #fff;
    text-decoration: none;
    border-radius: 3px;
    font-size: 14px;
    transition: background 0.3s ease;
}

.product-card .product-link:hover {
    background: #005a87;
}

.cache-info {
    background: #f5f5f5;
    padding: 15px;
    margin-top: 20px;
    border-left: 4px solid #46b450;
}

.cache-info h4 {
    margin-top: 0;
}

.cache-status-item {
    padding: 5px 0;
    border-bottom: 1px solid #ddd;
}

.cache-status-item:last-child {
    border-bottom: none;
}

.error-message {
    background: #fef2f2;
    border-left: 4px solid #dc3232;
    padding: 15px;
    margin: 10px 0;
}

.success-message {
    background: #f0f8ff;
    border-left: 4px solid #46b450;
    padding: 15px;
    margin: 10px 0;
}

.api-response-time {
    display: inline-block;
    padding: 2px 8px;
    background: #e5e5e5;
    border-radius: 3px;
    font-size: 12px;
    margin-left: 10px;
}
</style>