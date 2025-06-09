<?php
/**
 * 管理画面クラス
 */
class HopLink_Admin {
    
    /**
     * 初期化
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_cache_clear'));
        add_action('admin_init', array($this, 'handle_debug_actions'));
    }
    
    /**
     * 管理メニュー追加
     */
    public function add_admin_menu() {
        add_menu_page(
            'HopLink設定',
            'HopLink',
            'manage_options',
            'hoplink',
            array($this, 'settings_page'),
            'dashicons-cart',
            30
        );
    }
    
    /**
     * 設定登録
     */
    public function register_settings() {
        // 楽天API設定
        register_setting('hoplink_settings', 'hoplink_rakuten_app_id');
        register_setting('hoplink_settings', 'hoplink_rakuten_affiliate_id');
        
        // Amazon API設定
        register_setting('hoplink_settings', 'hoplink_amazon_access_key');
        register_setting('hoplink_settings', 'hoplink_amazon_secret_key');
        register_setting('hoplink_settings', 'hoplink_amazon_partner_tag');
        
        // キャッシュ設定
        register_setting('hoplink_settings', 'hoplink_cache_enabled');
        register_setting('hoplink_settings', 'hoplink_cache_duration');
        
        // 自動検索設定
        register_setting('hoplink_settings', 'hoplink_custom_keywords');
        register_setting('hoplink_settings', 'hoplink_fallback_keyword');
    }
    
    /**
     * キャッシュクリア処理
     */
    public function handle_cache_clear() {
        if (isset($_GET['page']) && $_GET['page'] === 'hoplink' && isset($_GET['cache_cleared'])) {
            require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-debug.php';
            HopLink_Debug::clear_cache();
        }
    }
    
    /**
     * 設定ページ
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>HopLink設定</h1>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>設定を保存しました。</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php settings_fields('hoplink_settings'); ?>
                
                <h2>楽天API設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hoplink_rakuten_app_id">アプリケーションID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="hoplink_rakuten_app_id" 
                                   name="hoplink_rakuten_app_id" 
                                   value="<?php echo esc_attr(get_option('hoplink_rakuten_app_id')); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <a href="https://webservice.rakuten.co.jp/app/create" target="_blank">
                                    楽天ウェブサービス
                                </a>
                                でアプリケーションIDを取得してください。
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hoplink_rakuten_affiliate_id">アフィリエイトID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="hoplink_rakuten_affiliate_id" 
                                   name="hoplink_rakuten_affiliate_id" 
                                   value="<?php echo esc_attr(get_option('hoplink_rakuten_affiliate_id')); ?>" 
                                   class="regular-text" />
                            <p class="description">楽天アフィリエイトIDを入力してください（任意）。</p>
                        </td>
                    </tr>
                </table>
                
                <h2>Amazon API設定</h2>
                
                <?php // PA-API アクセス要件の警告を表示 ?>
                <div class="notice notice-warning" style="position: relative;">
                    <h3 style="margin-top: 10px;">⚠️ Amazon PA-API v5 重要な利用条件</h3>
                    <ol>
                        <li><strong>新規アソシエイトの方：</strong>登録から180日以内に3件以上の適格な売上が必要です。売上がない場合、APIアクセスは制限されます。</li>
                        <li><strong>既存アソシエイトの方：</strong>過去30日間に売上がない場合、APIアクセスが一時的に制限される可能性があります。</li>
                        <li><strong>認証キーの取得場所：</strong>必ず <a href="https://affiliate.amazon.co.jp/assoc_credentials/home" target="_blank">アソシエイト・セントラル → ツール → Product Advertising API</a> から取得してください。</li>
                    </ol>
                    <p style="margin-bottom: 10px;"><small>※ これらの制限はAmazonの仕様によるものです。詳細は<a href="https://affiliate.amazon.co.jp/help/node/topic/GFSZ6Y63YJXJ4NFJ" target="_blank">公式ドキュメント</a>をご確認ください。</small></p>
                </div>
                
                <?php // レート制限対策の情報を表示 ?>
                <div class="notice notice-info" style="position: relative;">
                    <h3 style="margin-top: 10px;">🚦 レート制限対策が有効です</h3>
                    <p>TooManyRequestsエラーを防ぐため、以下の対策が自動的に適用されています：</p>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li><strong>リクエスト間隔制御：</strong>API呼び出し間に最低1秒の間隔を設けています</li>
                        <li><strong>強化されたキャッシュ：</strong>デフォルトで24時間の長期キャッシュを使用</li>
                        <li><strong>自動リトライ機能：</strong>一時的なエラー時に指数バックオフでリトライ</li>
                        <li><strong>頻度制御：</strong>デバッグテストの連続実行を制限</li>
                    </ul>
                    <p style="margin-bottom: 10px;"><small>※ これらの機能により、API制限に達するリスクを大幅に軽減しています。</small></p>
                </div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hoplink_amazon_access_key">アクセスキー</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="hoplink_amazon_access_key" 
                                   name="hoplink_amazon_access_key" 
                                   value="<?php echo esc_attr(get_option('hoplink_amazon_access_key')); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <strong>取得方法：</strong><br>
                                1. <a href="https://affiliate.amazon.co.jp/" target="_blank">アソシエイト・セントラル</a>にログイン<br>
                                2. 上部メニューの「ツール」→「Product Advertising API」を選択<br>
                                3. <a href="https://affiliate.amazon.co.jp/assoc_credentials/home" target="_blank">認証情報の管理</a>ページでアクセスキーを取得<br>
                                ※ 20文字の英数字で構成されています（例：AKIAJ1234567890ABCD）
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hoplink_amazon_secret_key">シークレットキー</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="hoplink_amazon_secret_key" 
                                   name="hoplink_amazon_secret_key" 
                                   value="<?php echo esc_attr(get_option('hoplink_amazon_secret_key')); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                アクセスキーと同時に生成される40文字のシークレットキーです。<br>
                                <strong>重要：</strong>初回生成時にのみ表示されます。必ずコピーして安全に保管してください。
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hoplink_amazon_partner_tag">パートナータグ</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="hoplink_amazon_partner_tag" 
                                   name="hoplink_amazon_partner_tag" 
                                   value="<?php echo esc_attr(get_option('hoplink_amazon_partner_tag')); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                Amazonアソシエイトのトラッキングタグ（ストアID）を入力してください。<br>
                                例：yourname-22<br>
                                ※ アソシエイト・セントラルの右上に表示されています
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h2>キャッシュ設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">キャッシュ</th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="hoplink_cache_enabled" 
                                       value="1" 
                                       <?php checked(1, get_option('hoplink_cache_enabled', 1)); ?> />
                                キャッシュを有効にする
                            </label>
                            <p class="description">API呼び出し回数を削減するため、キャッシュの使用を推奨します。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hoplink_cache_duration">キャッシュ期間</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="hoplink_cache_duration" 
                                   name="hoplink_cache_duration" 
                                   value="<?php echo esc_attr(get_option('hoplink_cache_duration', 86400)); ?>" 
                                   class="small-text" /> 秒
                            <p class="description">デフォルト: 86400秒（24時間）</p>
                        </td>
                    </tr>
                </table>
                
                <h2>自動検索設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hoplink_custom_keywords">追加キーワード</label>
                        </th>
                        <td>
                            <textarea id="hoplink_custom_keywords" 
                                      name="hoplink_custom_keywords" 
                                      rows="5" 
                                      cols="50" 
                                      class="large-text"><?php echo esc_textarea(get_option('hoplink_custom_keywords', '')); ?></textarea>
                            <p class="description">
                                自動検索で認識させたいキーワードを1行に1つずつ入力してください。<br>
                                例：<br>
                                箕面ビール<br>
                                京都醸造<br>
                                よなよなの里
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hoplink_fallback_keyword">フォールバックキーワード</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="hoplink_fallback_keyword" 
                                   name="hoplink_fallback_keyword" 
                                   value="<?php echo esc_attr(get_option('hoplink_fallback_keyword', 'クラフトビール 贈り物')); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                商品が見つからない場合の最終検索キーワード（デフォルト：クラフトビール 贈り物）
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <h2>使い方</h2>
            <div class="hoplink-usage">
                <h3>1. 手動キーワード指定</h3>
                <p>特定のキーワードで商品を検索する場合：</p>
                <code>[hoplink keyword="クラフトビール" platform="all" limit="3"]</code>
                
                <h4>パラメータ</h4>
                <ul>
                    <li><strong>keyword</strong> - 検索キーワード（必須）</li>
                    <li><strong>platform</strong> - 検索対象（all/rakuten/amazon）デフォルト: all</li>
                    <li><strong>limit</strong> - 表示数（デフォルト: 3）</li>
                    <li><strong>layout</strong> - レイアウト（grid/list）デフォルト: grid</li>
                </ul>
                
                <h3>2. 記事内容の自動解析</h3>
                <p>記事の内容を自動で解析して関連商品を表示する場合：</p>
                <code>[hoplink_auto]</code>
                
                <p>またはオプション付き：</p>
                <code>[hoplink_auto limit="5" platform="rakuten" layout="list"]</code>
                
                <h4>自動解析の仕組み</h4>
                <ul>
                    <li>記事内のビール関連キーワードを自動抽出</li>
                    <li>ビアスタイル、ブルワリー名、関連商品を認識</li>
                    <li>見つからない場合は設定したフォールバックキーワードで検索</li>
                    <li>追加キーワードで独自のブランドや商品名も認識可能</li>
                </ul>
                
                <h4>使用例</h4>
                <pre style="background: #f5f5f5; padding: 10px;">
今日は箕面ビールのペールエールを飲みました。
ホップの香りが素晴らしく、IPAファンにもおすすめです。

[hoplink_auto limit="3"]
                </pre>
            </div>
            
            <h2>API接続テスト</h2>
            <div class="hoplink-test">
                <?php
                require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-api.php';
                require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-debug.php';
                require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-amazon-debug.php';
                
                // Amazon認証情報の検証
                $amazon_issues = HopLink_Amazon_Debug::validate_credentials();
                if (!empty($amazon_issues)) {
                    echo '<div class="notice notice-warning"><p><strong>Amazon API設定の問題:</strong><br>';
                    foreach ($amazon_issues as $issue) {
                        echo '- ' . esc_html($issue) . '<br>';
                    }
                    echo '</p></div>';
                }
                
                $test_results = HopLink_Debug::test_api_connection();
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>API</th>
                            <th>ステータス</th>
                            <th>メッセージ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>楽天市場API</td>
                            <td>
                                <?php if ($test_results['rakuten']['status'] === 'success'): ?>
                                    <span style="color: green;">✓ 接続成功</span>
                                <?php elseif ($test_results['rakuten']['status'] === 'error'): ?>
                                    <span style="color: red;">✗ エラー</span>
                                <?php else: ?>
                                    <span style="color: gray;">- 未設定</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($test_results['rakuten']['message']); ?></td>
                        </tr>
                        <tr>
                            <td>Amazon PA-API</td>
                            <td>
                                <?php if ($test_results['amazon']['status'] === 'success'): ?>
                                    <span style="color: green;">✓ 接続成功</span>
                                <?php elseif ($test_results['amazon']['status'] === 'error'): ?>
                                    <span style="color: red;">✗ エラー</span>
                                <?php else: ?>
                                    <span style="color: gray;">- 未設定</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($test_results['amazon']['message']); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <?php if (isset($_GET['cache_cleared'])): ?>
                    <div class="notice notice-success is-dismissible">
                        <p>キャッシュをクリアしました。</p>
                    </div>
                <?php endif; ?>
                
                <p style="margin-top: 20px;">
                    <a href="<?php echo admin_url('admin.php?page=hoplink&cache_cleared=1'); ?>" 
                       class="button">キャッシュクリア</a>
                </p>
            </div>
            
            <h2>商品検索テスト</h2>
            <div class="hoplink-search-test">
                <p>実際に商品を検索してAPIの動作を確認します。</p>
                <form method="post" action="">
                    <?php wp_nonce_field('hoplink_search_test', 'hoplink_search_test_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="test_keyword">検索キーワード</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="test_keyword" 
                                       name="test_keyword" 
                                       value="クラフトビール" 
                                       class="regular-text" />
                                <p class="description">検索したいキーワードを入力してください。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">プラットフォーム</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="test_platform[]" value="rakuten" checked> 楽天市場
                                </label><br>
                                <label>
                                    <input type="checkbox" name="test_platform[]" value="amazon" checked> Amazon
                                </label>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <input type="submit" name="hoplink_search_test" class="button button-primary" value="商品検索テスト実行" />
                    </p>
                </form>
                
                <?php
                // 検索テストの処理
                if (isset($_POST['hoplink_search_test']) && 
                    isset($_POST['hoplink_search_test_nonce']) && 
                    wp_verify_nonce($_POST['hoplink_search_test_nonce'], 'hoplink_search_test')) {
                    
                    $keyword = sanitize_text_field($_POST['test_keyword']);
                    $platforms = isset($_POST['test_platform']) ? $_POST['test_platform'] : array();
                    
                    if (!empty($keyword) && !empty($platforms)) {
                        echo '<h3>検索結果: ' . esc_html($keyword) . '</h3>';
                        
                        require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-api.php';
                        $api = new HopLink_API();
                        
                        foreach ($platforms as $platform) {
                            $start_time = microtime(true);
                            
                            if ($platform === 'rakuten') {
                                $products = $api->search_rakuten($keyword, 3);
                                $platform_name = '楽天市場';
                            } else {
                                $products = $api->search_amazon($keyword, 3);
                                $platform_name = 'Amazon';
                            }
                            
                            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
                            
                            echo '<h4>' . esc_html($platform_name) . ' (実行時間: ' . $execution_time . 'ms)</h4>';
                            
                            if (!empty($products)) {
                                echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">';
                                foreach ($products as $product) {
                                    ?>
                                    <div style="border: 1px solid #ddd; padding: 15px; background: #fff;">
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="<?php echo esc_url($product['image']); ?>" 
                                                 alt="<?php echo esc_attr($product['title']); ?>" 
                                                 style="max-width: 100%; height: 200px; object-fit: contain;">
                                        <?php endif; ?>
                                        <h5 style="margin: 10px 0; font-size: 14px;">
                                            <?php echo esc_html($product['title']); ?>
                                        </h5>
                                        <p style="color: #b12704; font-size: 18px; margin: 5px 0;">
                                            ¥<?php echo number_format($product['price']); ?>
                                        </p>
                                        <?php if ($product['review'] > 0): ?>
                                            <p style="margin: 5px 0;">
                                                ★<?php echo number_format($product['review'], 1); ?> 
                                                (<?php echo number_format($product['review_count']); ?>件)
                                            </p>
                                        <?php endif; ?>
                                        <a href="<?php echo esc_url($product['url']); ?>" 
                                           target="_blank" 
                                           class="button button-small" 
                                           style="margin-top: 10px;">
                                            商品を見る
                                        </a>
                                    </div>
                                    <?php
                                }
                                echo '</div>';
                            } else {
                                echo '<p style="color: red;">商品が見つかりませんでした。APIの設定を確認してください。</p>';
                            }
                        }
                    }
                }
                ?>
            </div>
            
            <?php // Amazon API詳細デバッグセクション ?>
            <?php if ($test_results['amazon']['status'] === 'error'): ?>
            <h2>Amazon API デバッグ情報</h2>
            <div class="hoplink-debug" style="background: #f5f5f5; padding: 15px; border-radius: 5px;">
                <?php
                // デバッグモードでテストリクエストを実行
                if (isset($_GET['amazon_debug'])) {
                    echo '<h3>デバッグテスト結果</h3>';
                    $debug_result = HopLink_Amazon_Debug::test_request();
                    
                    if (!$debug_result['success'] && isset($debug_result['credential_issues'])) {
                        echo '<div class="notice notice-error"><p><strong>認証情報の問題:</strong><br>';
                        foreach ($debug_result['credential_issues'] as $issue) {
                            echo '- ' . esc_html($issue) . '<br>';
                        }
                        echo '</p></div>';
                    }
                    
                    echo '<pre style="background: white; padding: 10px; overflow: auto;">';
                    echo esc_html(print_r($debug_result, true));
                    echo '</pre>';
                    
                    // エラーログの最新内容を表示
                    echo '<h3>エラーログ（最新100行）</h3>';
                    $log_file = WP_CONTENT_DIR . '/debug.log';
                    if (file_exists($log_file)) {
                        $lines = file($log_file);
                        $amazon_logs = array_filter($lines, function($line) {
                            return strpos($line, '[HopLink Amazon API]') !== false || 
                                   strpos($line, 'Amazon API') !== false;
                        });
                        $recent_logs = array_slice($amazon_logs, -100);
                        
                        echo '<pre style="background: black; color: #0f0; padding: 10px; overflow: auto; max-height: 400px;">';
                        foreach ($recent_logs as $log) {
                            echo esc_html($log);
                        }
                        echo '</pre>';
                    } else {
                        echo '<p>デバッグログファイルが見つかりません。wp-config.phpでWP_DEBUGとWP_DEBUG_LOGを有効にしてください。</p>';
                    }
                }
                ?>
                
                <p>
                    <a href="<?php echo admin_url('admin.php?page=hoplink&amazon_debug=1'); ?>" 
                       class="button button-primary">Amazon APIデバッグテストを実行</a>
                    <a href="<?php echo admin_url('admin.php?page=hoplink&amazon_comprehensive_debug=1'); ?>" 
                       class="button">包括的診断を実行</a>
                    <a href="<?php echo admin_url('admin.php?page=hoplink&view_log_files=1'); ?>" 
                       class="button">ログファイル確認</a>
                </p>
                
                <h4>トラブルシューティングのヒント：</h4>
                <ul>
                    <li><strong>認証エラーの主な原因：</strong></li>
                    <ul style="margin-left: 20px;">
                        <li>アソシエイトアカウントに3件の売上実績がない</li>
                        <li>過去30日間に売上がない</li>
                        <li>認証キーが正しくない（スペースや改行が含まれている）</li>
                        <li>PA-API利用申請が承認されていない</li>
                    </ul>
                    <li><strong>認証キーの確認ポイント：</strong></li>
                    <ul style="margin-left: 20px;">
                        <li>アクセスキー：20文字の英数字（通常AKIAで始まる）</li>
                        <li>シークレットキー：40文字の英数字と記号</li>
                        <li>パートナータグ：ハイフンを含む（例: yourname-22）</li>
                    </ul>
                    <li><strong>PA-API v5の仕様：</strong></li>
                    <ul style="margin-left: 20px;">
                        <li>リージョンは日本でも us-west-2 を使用</li>
                        <li>エンドポイント：webservices.amazon.co.jp</li>
                        <li>リクエスト制限：1秒あたり1リクエスト（売上により増加）</li>
                    </ul>
                </ul>
                
                <h4>よくあるエラーと対処法：</h4>
                <table class="widefat" style="margin-top: 10px;">
                    <tr>
                        <th>エラーコード</th>
                        <th>意味</th>
                        <th>対処法</th>
                    </tr>
                    <tr>
                        <td>InvalidSignature</td>
                        <td>署名が正しくない</td>
                        <td>シークレットキーを確認し、余分なスペースがないか確認</td>
                    </tr>
                    <tr>
                        <td>InvalidPartnerTag</td>
                        <td>パートナータグが無効</td>
                        <td>アソシエイト・セントラルで正しいタグを確認</td>
                    </tr>
                    <tr>
                        <td>TooManyRequests</td>
                        <td>リクエスト制限超過</td>
                        <td>キャッシュを有効にし、リクエスト頻度を下げる</td>
                    </tr>
                    <tr>
                        <td>AccessDenied</td>
                        <td>アクセス権限なし</td>
                        <td>3件の売上実績を達成するか、売上を発生させる</td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * デバッグアクション処理
     */
    public function handle_debug_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // 包括的診断
        if (isset($_GET['amazon_comprehensive_debug'])) {
            add_action('admin_notices', function() {
                require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-amazon-debug.php';
                $diagnosis = HopLink_Amazon_Debug::comprehensive_debug();
                
                echo '<div class="notice notice-info"><h3>包括的診断結果</h3>';
                echo '<pre style="background: white; padding: 10px; overflow: auto; max-height: 400px;">';
                echo esc_html(json_encode($diagnosis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                echo '</pre></div>';
            });
        }
        
        // ログファイル確認
        if (isset($_GET['view_log_files'])) {
            add_action('admin_notices', function() {
                require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-amazon-debug.php';
                $log_files = HopLink_Amazon_Debug::get_log_file_paths();
                
                echo '<div class="notice notice-info"><h3>ログファイル情報</h3>';
                echo '<table class="widefat">';
                echo '<tr><th>ファイル名</th><th>パス</th><th>存在</th><th>サイズ</th><th>最終更新</th></tr>';
                
                foreach ($log_files as $name => $info) {
                    echo '<tr>';
                    echo '<td>' . esc_html($name) . '</td>';
                    echo '<td><code>' . esc_html($info['path']) . '</code></td>';
                    echo '<td>' . ($info['exists'] ? '✓' : '✗') . '</td>';
                    echo '<td>' . ($info['exists'] ? number_format($info['size']) . ' bytes' : '-') . '</td>';
                    echo '<td>' . ($info['modified'] ?: '-') . '</td>';
                    echo '</tr>';
                }
                
                echo '</table></div>';
                
                // 最新のデバッグログを表示
                $debug_log = WP_CONTENT_DIR . '/debug-hoplink-amazon.log';
                if (file_exists($debug_log)) {
                    $lines = file($debug_log);
                    $recent_lines = array_slice($lines, -50);
                    
                    echo '<div class="notice notice-info"><h3>最新のデバッグログ（50行）</h3>';
                    echo '<pre style="background: black; color: #0f0; padding: 10px; overflow: auto; max-height: 300px;">';
                    foreach ($recent_lines as $line) {
                        echo esc_html($line);
                    }
                    echo '</pre></div>';
                }
            });
        }
    }
}