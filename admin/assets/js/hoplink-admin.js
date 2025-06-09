/**
 * HopLink Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Connection Test
        $('#test-connection').on('click', function() {
            var $button = $(this);
            var $results = $('#connection-results');
            
            $button.prop('disabled', true).text('Testing...');
            $results.removeClass('success error').html('<span class="loading">Testing connection</span>').show();
            
            $.ajax({
                url: hoplink_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hoplink_test_amazon_api',
                    nonce: hoplink_ajax.nonce,
                    test_type: 'connection'
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Test Connection');
                    
                    if (response.success) {
                        $results.addClass('success').html(
                            '<strong>Success!</strong><br>' +
                            'Message: ' + response.message + '<br>' +
                            'Execution Time: ' + response.execution_time + '<br>' +
                            (response.sample_item ? 'Sample Item: ' + response.sample_item : '')
                        );
                    } else {
                        $results.addClass('error').html(
                            '<strong>Error!</strong><br>' +
                            'Message: ' + response.message + '<br>' +
                            'Execution Time: ' + response.execution_time +
                            (response.error_data ? '<br>Details: <pre>' + JSON.stringify(response.error_data, null, 2) + '</pre>' : '')
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false).text('Test Connection');
                    $results.addClass('error').html(
                        '<strong>AJAX Error!</strong><br>' +
                        'Status: ' + status + '<br>' +
                        'Error: ' + error
                    );
                }
            });
        });
        
        // Product Search Test
        $('#test-search').on('click', function() {
            var $button = $(this);
            var $results = $('#search-results');
            var keyword = $('#search-keyword').val().trim();
            
            if (!keyword) {
                alert('Please enter a search keyword');
                return;
            }
            
            $button.prop('disabled', true).text('Searching...');
            $results.removeClass('success error').html('<span class="loading">Searching for products</span>').show();
            
            $.ajax({
                url: hoplink_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hoplink_test_amazon_api',
                    nonce: hoplink_ajax.nonce,
                    test_type: 'search',
                    keyword: keyword
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Search Products');
                    
                    if (response.success) {
                        var html = '<strong>Success!</strong><br>' +
                                 'Message: ' + response.message + '<br>' +
                                 'Execution Time: ' + response.execution_time + '<br><br>';
                        
                        if (response.results && response.results.length > 0) {
                            html += '<h4>Products Found:</h4>';
                            response.results.forEach(function(product) {
                                html += '<div class="product-item">';
                                if (product.image_url) {
                                    html += '<img src="' + product.image_url + '" alt="' + product.title + '">';
                                }
                                html += '<div class="product-info">';
                                html += '<div class="product-title">' + product.title + '</div>';
                                html += '<div class="product-price">' + product.price_formatted + '</div>';
                                html += '<div>ASIN: ' + product.product_id + '</div>';
                                html += '<div>Availability: ' + product.availability + '</div>';
                                if (product.is_prime) {
                                    html += '<div style="color: #00a8e1;">✓ Prime Eligible</div>';
                                }
                                if (product.features && product.features.length > 0) {
                                    html += '<div class="product-features">Features:<ul>';
                                    product.features.slice(0, 3).forEach(function(feature) {
                                        html += '<li>' + feature + '</li>';
                                    });
                                    html += '</ul></div>';
                                }
                                html += '<div><a href="' + product.url + '" target="_blank">View on Amazon</a></div>';
                                html += '</div>';
                                html += '</div>';
                            });
                        }
                        
                        $results.addClass('success').html(html);
                    } else {
                        $results.addClass('error').html(
                            '<strong>Error!</strong><br>' +
                            'Message: ' + response.message + '<br>' +
                            'Execution Time: ' + response.execution_time +
                            (response.error_data ? '<br>Details: <pre>' + JSON.stringify(response.error_data, null, 2) + '</pre>' : '')
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false).text('Search Products');
                    $results.addClass('error').html(
                        '<strong>AJAX Error!</strong><br>' +
                        'Status: ' + status + '<br>' +
                        'Error: ' + error
                    );
                }
            });
        });
        
        // Clear Cache
        $('#clear-cache').on('click', function() {
            var $button = $(this);
            var $results = $('#cache-results');
            
            if (!confirm('Are you sure you want to clear all cached products?')) {
                return;
            }
            
            $button.prop('disabled', true).text('Clearing...');
            $results.removeClass('success error').html('<span class="loading">Clearing cache</span>').show();
            
            $.ajax({
                url: hoplink_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hoplink_test_amazon_api',
                    nonce: hoplink_ajax.nonce,
                    test_type: 'cache_clear'
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Clear All Cache');
                    
                    if (response.success) {
                        $results.addClass('success').html(
                            '<strong>Success!</strong><br>' +
                            response.message
                        );
                    } else {
                        $results.addClass('error').html(
                            '<strong>Error!</strong><br>' +
                            response.message
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false).text('Clear All Cache');
                    $results.addClass('error').html(
                        '<strong>AJAX Error!</strong><br>' +
                        'Status: ' + status + '<br>' +
                        'Error: ' + error
                    );
                }
            });
        });
        
        // Enter key support for search
        $('#search-keyword').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#test-search').click();
            }
        });
        
        // Product Search Test (両プラットフォーム)
        $('#test-product-search').on('click', function() {
            var $button = $(this);
            var keyword = $('#product-search-keyword').val().trim();
            
            if (!keyword) {
                alert('検索キーワードを入力してください');
                return;
            }
            
            $button.prop('disabled', true).text('検索中...');
            $('#product-search-results').show();
            $('#search-stats-info').html('<span class="loading">商品を検索しています</span>');
            $('#amazon-search-results').html('<div class="loading">Amazon商品を検索中...</div>');
            $('#rakuten-search-results').html('<div class="loading">楽天商品を検索中...</div>');
            
            var startTime = Date.now();
            var amazonTime = 0;
            var rakutenTime = 0;
            var amazonCount = 0;
            var rakutenCount = 0;
            
            // Amazon検索
            $.ajax({
                url: hoplink_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hoplink_test_amazon_api',
                    nonce: hoplink_ajax.nonce,
                    test_type: 'search',
                    keyword: keyword
                },
                success: function(response) {
                    amazonTime = Date.now() - startTime;
                    
                    if (response.success && response.results) {
                        amazonCount = response.results.length;
                        var html = '';
                        response.results.slice(0, 5).forEach(function(product) {
                            html += createProductCard(product, 'amazon');
                        });
                        $('#amazon-search-results').html(html || '<p>商品が見つかりませんでした。</p>');
                    } else {
                        $('#amazon-search-results').html(
                            '<div class="error-message">' +
                            '<strong>エラー:</strong> ' + response.message +
                            '</div>'
                        );
                    }
                    updateSearchStats();
                },
                error: function() {
                    amazonTime = Date.now() - startTime;
                    $('#amazon-search-results').html('<div class="error-message">Amazon API エラーが発生しました。</div>');
                    updateSearchStats();
                }
            });
            
            // 楽天検索
            $.ajax({
                url: hoplink_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hoplink_test_rakuten_api',
                    nonce: hoplink_ajax.nonce,
                    test_type: 'search',
                    keyword: keyword
                },
                success: function(response) {
                    rakutenTime = Date.now() - startTime;
                    
                    if (response.success && response.results) {
                        rakutenCount = response.results.length;
                        var html = '';
                        response.results.slice(0, 5).forEach(function(product) {
                            html += createProductCard(product, 'rakuten');
                        });
                        $('#rakuten-search-results').html(html || '<p>商品が見つかりませんでした。</p>');
                    } else {
                        $('#rakuten-search-results').html(
                            '<div class="error-message">' +
                            '<strong>エラー:</strong> ' + (response.message || '楽天API接続エラー') +
                            '</div>'
                        );
                    }
                    updateSearchStats();
                },
                error: function() {
                    rakutenTime = Date.now() - startTime;
                    $('#rakuten-search-results').html('<div class="error-message">楽天API エラーが発生しました。</div>');
                    updateSearchStats();
                }
            });
            
            // キャッシュ情報取得
            $.ajax({
                url: hoplink_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hoplink_get_cache_info',
                    nonce: hoplink_ajax.nonce,
                    keyword: keyword
                },
                success: function(response) {
                    if (response.success) {
                        $('#cache-status').show();
                        var cacheHtml = '';
                        if (response.cache_info.amazon) {
                            cacheHtml += '<div class="cache-status-item"><strong>Amazon:</strong> ' + 
                                        (response.cache_info.amazon.cached ? 'キャッシュあり (有効期限: ' + response.cache_info.amazon.expires + ')' : 'キャッシュなし') +
                                        '</div>';
                        }
                        if (response.cache_info.rakuten) {
                            cacheHtml += '<div class="cache-status-item"><strong>楽天:</strong> ' + 
                                        (response.cache_info.rakuten.cached ? 'キャッシュあり (有効期限: ' + response.cache_info.rakuten.expires + ')' : 'キャッシュなし') +
                                        '</div>';
                        }
                        $('#cache-status-content').html(cacheHtml);
                    }
                }
            });
            
            function updateSearchStats() {
                if (amazonTime > 0 && rakutenTime > 0) {
                    $button.prop('disabled', false).text('商品を検索');
                    var statsHtml = 'キーワード: <strong>' + keyword + '</strong> | ' +
                                  'Amazon: ' + amazonCount + '件 <span class="api-response-time">' + amazonTime + 'ms</span> | ' +
                                  '楽天: ' + rakutenCount + '件 <span class="api-response-time">' + rakutenTime + 'ms</span>';
                    $('#search-stats-info').html(statsHtml);
                }
            }
        });
        
        // 楽天API接続テスト
        $('#test-rakuten-connection').on('click', function() {
            var $button = $(this);
            var $results = $('#rakuten-connection-results');
            
            $button.prop('disabled', true).text('テスト中...');
            $results.removeClass('success error').html('<span class="loading">接続テスト中</span>').show();
            
            $.ajax({
                url: hoplink_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hoplink_test_rakuten_api',
                    nonce: hoplink_ajax.nonce,
                    test_type: 'connection'
                },
                success: function(response) {
                    $button.prop('disabled', false).text('接続テスト');
                    
                    if (response.success) {
                        $results.addClass('success').html(
                            '<strong>成功!</strong><br>' +
                            'メッセージ: ' + response.message + '<br>' +
                            '実行時間: ' + response.execution_time + '<br>' +
                            (response.sample_item ? 'サンプル商品: ' + response.sample_item : '')
                        );
                    } else {
                        $results.addClass('error').html(
                            '<strong>エラー!</strong><br>' +
                            'メッセージ: ' + response.message + '<br>' +
                            '実行時間: ' + response.execution_time +
                            (response.error_data ? '<br>詳細: <pre>' + JSON.stringify(response.error_data, null, 2) + '</pre>' : '')
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false).text('接続テスト');
                    $results.addClass('error').html(
                        '<strong>AJAXエラー!</strong><br>' +
                        'ステータス: ' + status + '<br>' +
                        'エラー: ' + error
                    );
                }
            });
        });
        
        // 楽天商品検索テスト
        $('#test-rakuten-search').on('click', function() {
            var $button = $(this);
            var $results = $('#rakuten-search-results');
            var keyword = $('#rakuten-search-keyword').val().trim();
            
            if (!keyword) {
                alert('検索キーワードを入力してください');
                return;
            }
            
            $button.prop('disabled', true).text('検索中...');
            $results.removeClass('success error').html('<span class="loading">商品を検索中</span>').show();
            
            $.ajax({
                url: hoplink_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hoplink_test_rakuten_api',
                    nonce: hoplink_ajax.nonce,
                    test_type: 'search',
                    keyword: keyword
                },
                success: function(response) {
                    $button.prop('disabled', false).text('商品を検索');
                    
                    if (response.success) {
                        var html = '<strong>成功!</strong><br>' +
                                 'メッセージ: ' + response.message + '<br>' +
                                 '実行時間: ' + response.execution_time + '<br><br>';
                        
                        if (response.results && response.results.length > 0) {
                            html += '<h4>商品一覧:</h4>';
                            response.results.forEach(function(product) {
                                html += '<div class="product-item">';
                                if (product.image_url) {
                                    html += '<img src="' + product.image_url + '" alt="' + product.title + '">';
                                }
                                html += '<div class="product-info">';
                                html += '<div class="product-title">' + product.title + '</div>';
                                html += '<div class="product-price">' + product.price_formatted + '</div>';
                                html += '<div>商品コード: ' + product.product_id + '</div>';
                                html += '<div>ショップ: ' + product.shop_name + '</div>';
                                if (product.review_count > 0) {
                                    html += '<div>レビュー: ★' + product.review_average + ' (' + product.review_count + '件)</div>';
                                }
                                html += '<div>' + product.shipping_info + '</div>';
                                if (product.is_asuraku) {
                                    html += '<div style="color: #bf0000;">あす楽対応</div>';
                                }
                                html += '<div><a href="' + product.url + '" target="_blank">楽天で見る</a></div>';
                                html += '</div>';
                                html += '</div>';
                            });
                        }
                        
                        $results.addClass('success').html(html);
                    } else {
                        $results.addClass('error').html(
                            '<strong>エラー!</strong><br>' +
                            'メッセージ: ' + response.message + '<br>' +
                            '実行時間: ' + response.execution_time +
                            (response.error_data ? '<br>詳細: <pre>' + JSON.stringify(response.error_data, null, 2) + '</pre>' : '')
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false).text('商品を検索');
                    $results.addClass('error').html(
                        '<strong>AJAXエラー!</strong><br>' +
                        'ステータス: ' + status + '<br>' +
                        'エラー: ' + error
                    );
                }
            });
        });
        
        // Enter key support for rakuten search
        $('#rakuten-search-keyword').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#test-rakuten-search').click();
            }
        });
        
        // Enter key support for product search
        $('#product-search-keyword').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#test-product-search').click();
            }
        });
        
        // 商品カード作成関数
        function createProductCard(product, platform) {
            var card = '<div class="product-card">';
            
            // 商品画像
            if (product.image_url) {
                card += '<img src="' + product.image_url + '" alt="' + product.title + '">';
            }
            
            // プラットフォームバッジ
            card += '<span class="product-platform ' + platform + '">' + 
                    (platform === 'amazon' ? 'Amazon' : '楽天市場') + '</span>';
            
            // 商品タイトル
            card += '<div class="product-title">' + product.title + '</div>';
            
            // 価格
            card += '<div class="product-price">' + product.price_formatted + '</div>';
            
            // メタ情報
            card += '<div class="product-meta">';
            if (platform === 'amazon') {
                card += 'ASIN: ' + product.product_id + '<br>';
                if (product.is_prime) {
                    card += '<span style="color: #00a8e1;">✓ Prime対象</span><br>';
                }
                card += product.availability;
            } else {
                card += 'ショップ: ' + product.shop_name + '<br>';
                if (product.review_count > 0) {
                    card += '★' + product.review_average + ' (' + product.review_count + '件)<br>';
                }
                card += product.shipping_info;
                if (product.is_asuraku) {
                    card += ' <span style="color: #bf0000;">あす楽</span>';
                }
            }
            card += '</div>';
            
            // リンク
            card += '<a href="' + product.affiliate_url + '" target="_blank" class="product-link">' +
                    '詳細を見る</a>';
            
            card += '</div>';
            
            return card;
        }
    });

})(jQuery);