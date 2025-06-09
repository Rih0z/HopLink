<?php
/**
 * URL一括変換ツール管理画面
 *
 * @package HopLink
 * @since   1.0.0
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// アソシエイトタグの確認
$associate_tag = get_option('hoplink_amazon_associate_tag', '');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if (empty($associate_tag)): ?>
        <div class="notice notice-error">
            <p>
                <strong>エラー:</strong> 
                Amazonアソシエイトタグが設定されていません。
                <a href="<?php echo esc_url(admin_url('admin.php?page=hoplink-settings')); ?>">設定ページ</a>で設定してください。
            </p>
        </div>
    <?php else: ?>
        
        <div class="hoplink-converter-intro">
            <p>このツールを使用して、既存の記事内のAmazon URLを一括でアフィリエイトリンクに変換できます。</p>
        </div>

        <!-- 一括変換フォーム -->
        <div class="hoplink-converter-section">
            <h2>一括変換</h2>
            
            <form id="hoplink-bulk-converter" method="post" action="">
                <?php wp_nonce_field('hoplink_bulk_convert', 'hoplink_bulk_convert_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">投稿タイプ</th>
                        <td>
                            <label>
                                <input type="checkbox" name="post_types[]" value="post" checked>
                                投稿
                            </label><br>
                            <label>
                                <input type="checkbox" name="post_types[]" value="page">
                                固定ページ
                            </label>
                            <?php
                            // カスタム投稿タイプを追加
                            $custom_post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
                            foreach ($custom_post_types as $post_type):
                            ?>
                                <br>
                                <label>
                                    <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($post_type->name); ?>">
                                    <?php echo esc_html($post_type->label); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">変換オプション</th>
                        <td>
                            <label>
                                <input type="checkbox" name="preserve_existing" value="1" checked>
                                既存のアフィリエイトリンクを保持する
                            </label>
                            <p class="description">
                                チェックを外すと、既存のアフィリエイトタグも新しいタグで上書きされます。
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">処理モード</th>
                        <td>
                            <label>
                                <input type="radio" name="mode" value="preview" checked>
                                プレビュー（変更を確認するだけ）
                            </label><br>
                            <label>
                                <input type="radio" name="mode" value="execute">
                                実行（実際に変換を実行）
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="hoplink-start-conversion">
                        変換を開始
                    </button>
                </p>
            </form>
        </div>

        <!-- 進行状況表示エリア -->
        <div id="hoplink-conversion-progress" style="display: none;">
            <h2>変換進行状況</h2>
            <div class="hoplink-progress-bar">
                <div class="hoplink-progress-bar-fill" style="width: 0%;"></div>
            </div>
            <div class="hoplink-progress-info">
                <span class="hoplink-progress-current">0</span> / 
                <span class="hoplink-progress-total">0</span> 件処理済み
            </div>
        </div>

        <!-- 結果表示エリア -->
        <div id="hoplink-conversion-results" style="display: none;">
            <h2>変換結果</h2>
            <div class="hoplink-results-summary">
                <p>
                    <strong>処理完了:</strong> 
                    <span class="hoplink-total-processed">0</span>件の記事を処理し、
                    <span class="hoplink-total-converted">0</span>個のURLを変換しました。
                </p>
            </div>
            <div class="hoplink-results-details">
                <h3>詳細</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>記事タイトル</th>
                            <th>変換されたURL数</th>
                            <th>ステータス</th>
                            <th>アクション</th>
                        </tr>
                    </thead>
                    <tbody id="hoplink-results-tbody">
                        <!-- 結果がここに追加される -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 単一記事変換ツール -->
        <div class="hoplink-converter-section">
            <h2>個別記事の変換</h2>
            
            <form id="hoplink-single-converter" method="post" action="">
                <?php wp_nonce_field('hoplink_single_convert', 'hoplink_single_convert_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">記事ID または URL</th>
                        <td>
                            <input type="text" name="post_identifier" class="regular-text" 
                                   placeholder="例: 123 または https://example.com/post-name/">
                            <p class="description">
                                変換したい記事のIDまたはURLを入力してください。
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-secondary">
                        この記事を変換
                    </button>
                </p>
            </form>
        </div>

        <!-- URL検索ツール -->
        <div class="hoplink-converter-section">
            <h2>Amazon URL検索</h2>
            <p>サイト内のAmazon URLを検索して確認できます。</p>
            
            <form id="hoplink-url-search" method="post" action="">
                <?php wp_nonce_field('hoplink_url_search', 'hoplink_url_search_nonce'); ?>
                
                <p class="submit">
                    <button type="submit" class="button button-secondary">
                        Amazon URLを検索
                    </button>
                </p>
            </form>
            
            <div id="hoplink-search-results" style="display: none;">
                <!-- 検索結果がここに表示される -->
            </div>
        </div>

    <?php endif; ?>
</div>

<style>
.hoplink-converter-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.hoplink-progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0;
}

.hoplink-progress-bar-fill {
    height: 100%;
    background: #2271b1;
    transition: width 0.3s ease;
}

.hoplink-progress-info {
    text-align: center;
    margin: 10px 0;
}

.hoplink-results-summary {
    background: #f6f7f7;
    border-left: 4px solid #2271b1;
    padding: 12px;
    margin: 10px 0;
}

.hoplink-converter-intro {
    background: #e7f7ff;
    border-left: 4px solid #0073aa;
    padding: 12px;
    margin: 10px 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 一括変換フォームの処理
    $('#hoplink-bulk-converter').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var mode = $('input[name="mode"]:checked').val();
        
        // 確認ダイアログ
        if (mode === 'execute') {
            if (!confirm('本当に変換を実行しますか？この操作は取り消せません。')) {
                return;
            }
        }
        
        // 進行状況を表示
        $('#hoplink-conversion-progress').show();
        $('#hoplink-conversion-results').hide();
        
        // Ajax処理を開始
        startBulkConversion(formData);
    });
    
    // 単一記事変換フォームの処理
    $('#hoplink-single-converter').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData + '&action=hoplink_convert_single',
            success: function(response) {
                if (response.success) {
                    alert('変換が完了しました。' + response.data.converted_count + '個のURLを変換しました。');
                } else {
                    alert('エラー: ' + response.data.message);
                }
            }
        });
    });
    
    // URL検索フォームの処理
    $('#hoplink-url-search').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData + '&action=hoplink_search_urls',
            success: function(response) {
                if (response.success) {
                    displaySearchResults(response.data);
                }
            }
        });
    });
    
    function startBulkConversion(formData) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData + '&action=hoplink_start_bulk_conversion',
            success: function(response) {
                if (response.success) {
                    var totalPosts = response.data.total_posts;
                    $('.hoplink-progress-total').text(totalPosts);
                    
                    // バッチ処理を開始
                    processBatch(0, totalPosts, formData);
                }
            }
        });
    }
    
    function processBatch(offset, total, formData) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData + '&action=hoplink_process_batch&offset=' + offset,
            success: function(response) {
                if (response.success) {
                    var processed = offset + response.data.batch_size;
                    var progress = (processed / total) * 100;
                    
                    // 進行状況を更新
                    $('.hoplink-progress-bar-fill').css('width', progress + '%');
                    $('.hoplink-progress-current').text(processed);
                    
                    // 結果を追加
                    addResults(response.data.results);
                    
                    // 次のバッチを処理
                    if (processed < total) {
                        processBatch(processed, total, formData);
                    } else {
                        // 完了
                        showCompletionSummary();
                    }
                }
            }
        });
    }
    
    function addResults(results) {
        var tbody = $('#hoplink-results-tbody');
        
        results.forEach(function(result) {
            var row = $('<tr>');
            row.append('<td>' + result.title + '</td>');
            row.append('<td>' + result.converted_count + '</td>');
            row.append('<td>' + result.status + '</td>');
            row.append('<td><a href="' + result.edit_url + '" target="_blank">編集</a></td>');
            tbody.append(row);
        });
    }
    
    function showCompletionSummary() {
        $('#hoplink-conversion-progress').hide();
        $('#hoplink-conversion-results').show();
        
        // サマリーを更新
        var totalProcessed = $('#hoplink-results-tbody tr').length;
        var totalConverted = 0;
        
        $('#hoplink-results-tbody tr').each(function() {
            var count = parseInt($(this).find('td:eq(1)').text());
            totalConverted += count;
        });
        
        $('.hoplink-total-processed').text(totalProcessed);
        $('.hoplink-total-converted').text(totalConverted);
    }
    
    function displaySearchResults(data) {
        var html = '<h3>検索結果</h3>';
        html += '<p>' + data.total_urls + '個のAmazon URLが見つかりました。</p>';
        
        if (data.posts.length > 0) {
            html += '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr><th>記事</th><th>URL数</th><th>サンプルURL</th></tr></thead>';
            html += '<tbody>';
            
            data.posts.forEach(function(post) {
                html += '<tr>';
                html += '<td><a href="' + post.edit_url + '">' + post.title + '</a></td>';
                html += '<td>' + post.url_count + '</td>';
                html += '<td>' + post.sample_url + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
        }
        
        $('#hoplink-search-results').html(html).show();
    }
});
</script>