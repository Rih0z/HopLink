# HopLink - WordPress クラフトビール アフィリエイトプラグイン

> クラフトビール記事から自動的に最適なアフィリエイトリンクを生成するWordPressプラグイン

[![WordPress](https://img.shields.io/badge/WordPress-6.0+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0+-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0+-red.svg)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.0.0-green.svg)](CHANGELOG.md)

## 📋 目次

- [概要](#概要)
- [主な機能](#主な機能)
- [インストール](#インストール)
- [初期設定](#初期設定)
- [使い方](#使い方)
- [API リファレンス](#api-リファレンス)
- [カスタマイズ](#カスタマイズ)
- [トラブルシューティング](#トラブルシューティング)
- [開発者向け情報](#開発者向け情報)
- [ライセンス](#ライセンス)

## 🍺 概要

HopLinkは、クラフトビール専門ブログ向けに開発されたWordPressプラグインです。記事の内容を自動解析し、楽天市場やAmazonから最適な関連商品を見つけてアフィリエイトリンクを生成します。

### 新機能ハイライト
- **API最小限利用モード**: 手動データ管理とAPI自動取得のハイブリッド運用
- **完全無料の技術スタック**: WordPress標準機能とオープンソースのみで構築
- **法令完全準拠**: 景品表示法・ステマ規制に自動対応

### 対象ユーザー
- クラフトビールブログ運営者
- ビール系メディア編集者
- アフィリエイトマーケター
- WordPress開発者

### 開発背景
rihobeer.com での実際の運用経験をもとに、クラフトビール業界特有のニーズに対応したプラグインとして開発されました。

## ✨ 主な機能

### 🤖 自動記事解析
- **ビール名抽出**: 記事からビール商品名を自動識別
- **ブルワリー識別**: 醸造所名の自動抽出
- **ビアスタイル判定**: IPA、スタウト、ピルスナーなどを自動分類
- **関連キーワード抽出**: グラス、ホップ、醸造関連用語を検出

### 🛒 アフィリエイト連携
- **楽天市場API**: 国内ビール商品・関連グッズを検索
- **Amazon PA-API**: 海外ビール・書籍・ギフトセットを検索
- **ふるさと納税**: 地域ブルワリー商品の自動提案
- **スマートマッチング**: 記事内容と商品の関連度を AI で判定
- **ハイブリッドモード**: API利用を最小限に抑える手動＋自動のデータ管理
- **静的データキャッシュ**: API制限時も商品表示を継続
- **フォールバック機能**: API障害時は事前登録商品を自動表示

### 📊 統計・分析
- **クリック追跡**: リンククリック数のリアルタイム計測
- **コンバージョン測定**: 売上発生の自動追跡
- **収益レポート**: 月次・年次の詳細分析
- **A/Bテスト**: 商品表示の最適化実験
- **ヒートマップ分析**: クリック位置を可視化（プライバシー保護対応）
- **商品別LTV分析**: 長期的な収益性を測定
- **離脱率分析**: 商品カード表示による影響を測定
- **時系列分析**: 季節・時間帯別の最適化提案

### 🎨 カスタマイズ
- **レスポンシブデザイン**: モバイル完全対応
- **テーマ統合**: 既存デザインに自然に溶け込む
- **ショートコード**: 手動での商品挿入も可能
- **フック・フィルター**: 開発者向けカスタマイズAPI
- **WebP自動変換**: 画像を最適化して表示速度向上
- **遅延読み込み**: Core Web Vitals スコア向上
- **構造化データ**: SEO最適化されたProduct Schema自動生成
- **アクセシビリティ**: WCAG 2.1 AA準拠

## 🚀 インストール

### 必要環境
- **WordPress**: 6.0 以上
- **PHP**: 8.0 以上
- **MySQL**: 8.0 以上
- **メモリ**: 512MB 以上
- **ストレージ**: 100MB 以上

### 推奨環境（無料で性能向上）
- **キャッシュプラグイン**: W3 Total Cache / WP Super Cache
- **画像最適化**: Smush / EWWW Image Optimizer
- **データベース**: SQLiteでの運用も可能（軽量環境向け）

### インストール方法

#### 方法1: WordPress管理画面から
1. WordPress管理画面 → プラグイン → 新規追加
2. 「HopLink」で検索
3. 「今すぐインストール」→「有効化」

#### 方法2: 手動インストール
```bash
# プラグインディレクトリに移動
cd /wp-content/plugins/

# GitHubからクローン
git clone https://github.com/yourusername/hoplink.git

# WordPress管理画面でプラグインを有効化
```

#### 方法3: ZIPファイル
1. [GitHub Releases](https://github.com/yourusername/hoplink/releases) から最新版をダウンロード
2. WordPress管理画面 → プラグイン → 新規追加 → プラグインのアップロード
3. ZIPファイルを選択してインストール → 有効化

### インストール確認
インストール後、管理画面に「HopLink」メニューが表示されれば成功です。

## ⚙️ 初期設定

### 1. アフィリエイトアカウント設定

#### 楽天アフィリエイト
1. HopLink → 設定 → 楽天API設定
2. 以下の情報を入力：
   ```
   アプリケーションID: your_app_id
   アフィリエイトID: your_affiliate_id
   シークレット: your_secret_key
   ```

#### Amazon PA-API v5（日本）
1. HopLink → 設定 → Amazon API設定
2. 以下の情報を入力：
   ```
   アクセスキーID: your_access_key        # 20文字の英数字
   シークレットアクセスキー: your_secret_key # 40文字の英数字記号
   アソシエイトタグ: your_associate_tag     # 例：yourname-22
   ```
3. **重要**: 日本のPA-APIは以下の設定が自動適用されます：
   - エンドポイント: `webservices.amazon.co.jp`
   - リージョン: `ap-northeast-1`（東京）
   - マーケットプレイス: `www.amazon.co.jp`

### 2. 基本設定

#### API利用モード
```php
// 利用モードの選択
$api_mode = [
    'mode' => 'hybrid', // 'full_api' | 'hybrid' | 'manual'
    'api_limit_per_day' => 100, // ハイブリッドモードでのAPI利用上限
    'cache_duration' => 604800, // 7日間のキャッシュ
    'fallback_enabled' => true, // API障害時のフォールバック
    'manual_product_priority' => 0.8 // 手動登録商品の優先度
];
```

#### 商品選定ルール
```php
// 設定例
$settings = [
    'max_products_per_article' => 5,
    'commission_weight' => 0.3,
    'relevance_weight' => 0.7,
    'price_range' => [500, 10000],
    'exclude_brands' => ['ブランドA', 'ブランドB'],
    'seasonal_adjustment' => true, // 季節商品の自動調整
    'stock_check_enabled' => true, // 在庫チェック
    'price_tracking' => true // 価格変動追跡
];
```

#### コンプライアンス設定
```php
// 法令対応設定
$compliance = [
    'auto_pr_label' => true, // PR表記の自動挿入
    'affiliate_disclosure' => true, // アフィリエイト開示
    'sponsored_attribute' => true, // rel="sponsored"の自動付与
    'cookie_consent' => true, // Cookie同意バナー連携
    'gdpr_compliant' => true // GDPR対応
];
```

#### 表示設定
- **商品カード位置**: 記事上部/下部/自動
- **デザインテーマ**: ライト/ダーク/カスタム
- **表示形式**: カード/リスト/比較表

### 3. パフォーマンス設定
- **キャッシュ**: 有効（推奨）
- **API制限**: 1分間50リクエスト
- **自動更新**: 24時間毎
- **画像最適化**: WebP自動変換、遅延読み込み
- **JavaScript最適化**: 非同期読み込み、ミニファイ
- **データベース**: インデックス最適化、クエリキャッシュ

## 🔄 API最小限利用モード

### ハイブリッドモードの仕組み

#### 1. 手動商品データベース
```php
// 事前に人気商品を手動登録
$manual_products = [
    [
        'name' => 'IPA専用グラス',
        'price' => 2500,
        'url' => 'https://...',
        'keywords' => ['IPA', 'グラス'],
        'season' => 'all' // 通年商品
    ]
];
```

#### 2. API利用の最適化
- **バッチ処理**: 複数記事をまとめてAPIコール
- **キャッシュ優先**: 既存データがあればAPIを使わない
- **オフピーク更新**: 深夜にAPIでデータ更新

#### 3. メリット
- **APIコスト削減**: 月間API利用数を約80%削減
- **高速表示**: キャッシュからの表示で速度向上
- **安定性**: API障害時もサービス継続

## 📖 使い方

### 自動モード（推奨）

#### 新規記事作成時
1. 通常通りビール記事を作成
2. 「公開」ボタンクリック
3. HopLinkが自動で記事を解析
4. 関連商品を自動挿入

#### 既存記事への適用
1. 記事編集画面を開く
2. 「HopLink解析」ボタンをクリック
3. 提案された商品を確認
4. 「適用」で自動挿入

### 手動モード

#### 商品データベース管理
1. HopLink → 商品データベース
2. 「新規商品追加」ボタン
3. 商品情報を入力（CSVインポートも可能）
4. キーワードと関連付け

#### ショートコード使用
```php
// 特定商品の表示
[hoplink product="楽天商品ID" platform="rakuten"]

// 自動商品提案
[hoplink keyword="IPA" category="beer" max="3"]

// 比較表示
[hoplink compare="true" products="rakuten:123,amazon:456"]
```

#### Gutenbergブロック
1. 「+」ボタン → HopLink → 商品ブロック
2. キーワードまたは商品IDを入力
3. プレビューで確認後、挿入

### 高度な使い方

#### カスタムルール設定
```php
// functions.php に追加
add_filter('hoplink_product_selection', function($products, $keywords) {
    // IPA記事の場合、グラス商品を優先
    if (in_array('IPA', $keywords)) {
        $products = prioritize_category($products, 'glass');
    }
    return $products;
});
```

#### カスタムテンプレート
```php
// テンプレートオーバーライド
// your-theme/hoplink/product-card.php
<div class="custom-product-card">
    <h3><?php echo $product['name']; ?></h3>
    <p class="price"><?php echo $product['price']; ?></p>
    <a href="<?php echo $product['affiliate_url']; ?>" target="_blank">
        購入する
    </a>
</div>
```

## 🎯 SEO最適化機能

### 構造化データ自動生成
```html
<!-- 自動生成されるProduct Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "IPA専用グラス",
  "offers": {
    "@type": "Offer",
    "price": "2500",
    "priceCurrency": "JPY",
    "availability": "https://schema.org/InStock"
  }
}
</script>
```

### Core Web Vitals 対応
- **LCP最適化**: 商品画像の事前読み込み
- **FID改善**: JavaScriptの非同期実行
- **CLS防止**: 固定サイズの商品カード

### リンクSEO
```html
<!-- 自動付与される属性 -->
<a href="affiliate-url" 
   rel="sponsored noopener" 
   target="_blank"
   data-product-id="123">
   商品を見る
</a>
```

## 🔧 API リファレンス

### REST API エンドポイント

#### 記事解析
```http
POST /wp-json/hoplink/v1/analyze
Content-Type: application/json

{
    "post_id": 123,
    "force_regenerate": false
}
```

**レスポンス:**
```json
{
    "success": true,
    "data": {
        "keywords": ["IPA", "ホップ", "柑橘"],
        "beer_styles": ["India Pale Ale"],
        "breweries": ["Stone Brewing"],
        "products": [
            {
                "platform": "rakuten",
                "product_id": "123456",
                "name": "IPA専用グラス",
                "price": 2500,
                "affiliate_url": "https://...",
                "relevance_score": 0.85
            }
        ]
    }
}
```

#### 統計取得
```http
GET /wp-json/hoplink/v1/stats?period=30days
```

#### 商品検索
```http
GET /wp-json/hoplink/v1/search?keyword=IPA&platform=rakuten
```

### WordPress アクション・フィルター

#### アクション
```php
// 商品リンク生成後
do_action('hoplink_links_generated', $post_id, $products);

// API呼び出し前
do_action('hoplink_before_api_call', $api_name, $params);

// エラー発生時
do_action('hoplink_error', $error_message, $context);
```

#### フィルター
```php
// 商品選定ロジックのカスタマイズ
apply_filters('hoplink_product_selection', $products, $keywords);

// 商品カード HTML のカスタマイズ
apply_filters('hoplink_product_card_html', $html, $product);

// API レスポンスの加工
apply_filters('hoplink_api_response', $response, $api_name);
```

## ⚖️ コンプライアンス機能

### 景品表示法対応
```php
// 自動PR表記機能
add_filter('the_content', function($content) {
    if (has_hoplink_products()) {
        $disclosure = '<div class="hoplink-disclosure">';
        $disclosure .= '※本記事はアフィリエイト広告を含んでいます。';
        $disclosure .= '</div>';
        $content = $disclosure . $content;
    }
    return $content;
});
```

### ステマ規制対応
- **明確な広告表示**: 「広告」「PR」ラベルの自動表示
- **誤認防止**: 誇大表現チェック機能
- **適正表示**: 商品情報の正確性保証

### GDPR/Cookie対応
```javascript
// Cookie同意管理
window.hoplink_consent = {
    init: function() {
        if (!this.hasConsent()) {
            this.showBanner();
        }
    },
    hasConsent: function() {
        return localStorage.getItem('hoplink_cookie_consent') === 'true';
    }
};
```

## 🎨 カスタマイズ

### CSS カスタマイズ

#### 基本クラス
```css
/* 商品カード */
.hoplink-product-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 16px;
    margin: 16px 0;
}

/* 価格表示 */
.hoplink-price {
    font-size: 1.2em;
    font-weight: bold;
    color: #e47911;
}

/* ボタン */
.hoplink-button {
    background: #ff6600;
    color: white;
    padding: 10px 20px;
    border-radius: 4px;
    text-decoration: none;
}
```

#### レスポンシブ対応
```css
@media (max-width: 768px) {
    .hoplink-product-card {
        padding: 12px;
        margin: 12px 0;
    }
    
    .hoplink-product-grid {
        grid-template-columns: 1fr;
    }
}
```

### JavaScript カスタマイズ

#### クリック追跡のカスタマイズ
```javascript
// カスタム追跡関数
window.hoplink = window.hoplink || {};
window.hoplink.trackClick = function(productId, platform) {
    // Google Analytics 4
    gtag('event', 'click', {
        'event_category': 'hoplink',
        'event_label': platform + '_' + productId,
        'value': 1
    });
    
    // カスタム処理
    fetch('/wp-json/hoplink/v1/track-click', {
        method: 'POST',
        body: JSON.stringify({
            product_id: productId,
            platform: platform
        })
    });
};
```

### PHP カスタマイズ

#### カスタム商品フィルター
```php
// 特定ブルワリーの優先表示
add_filter('hoplink_product_selection', function($products, $keywords) {
    if (in_array('Stone Brewing', $keywords)) {
        usort($products, function($a, $b) {
            if (strpos($a['name'], 'Stone') !== false) return -1;
            if (strpos($b['name'], 'Stone') !== false) return 1;
            return 0;
        });
    }
    return $products;
}, 10, 2);
```

#### カスタム解析ルール
```php
// 日本酒記事への対応
add_filter('hoplink_analyze_content', function($analysis, $content) {
    if (strpos($content, '日本酒') !== false) {
        $analysis['keywords'][] = 'sake';
        $analysis['categories'][] = 'japanese_alcohol';
    }
    return $analysis;
}, 10, 2);
```

## 🔍 トラブルシューティング

### よくある問題

#### Q1: 商品が表示されない
**症状**: 記事を公開しても商品リンクが生成されない

**原因と解決策**:
1. **API設定確認**
   ```bash
   # WordPress CLI で確認
   wp hoplink check-api
   ```

2. **権限確認**
   - プラグインが有効化されているか
   - APIキーが正しく設定されているか

3. **ログ確認**
   ```php
   // デバッグログの確認
   tail -f /wp-content/debug.log | grep hoplink
   ```

#### Q2: 表示が崩れる
**症状**: 商品カードのデザインが崩れる

**解決策**:
1. **テーマ競合確認**
   ```css
   /* CSSの競合を解決 */
   .hoplink-product-card {
       box-sizing: border-box !important;
   }
   ```

2. **プラグイン競合確認**
   - 他のプラグインを一時無効化してテスト

#### Q3: パフォーマンスが遅い
**症状**: ページ読み込みが遅くなる

**解決策**:
1. **キャッシュ有効化**
   ```php
   // 設定でキャッシュを有効化
   update_option('hoplink_cache_enabled', true);
   ```

2. **API呼び出し最適化**
   ```php
   // バックグラウンド処理の有効化
   update_option('hoplink_background_processing', true);
   ```

### エラーコード一覧

| コード | 意味 | 解決策 |
|--------|------|--------|
| HL001 | API認証エラー | APIキーを確認 |
| HL002 | レート制限超過 | 時間をおいて再試行 |
| HL003 | 商品が見つからない | キーワードを変更 |
| HL004 | データベースエラー | DB接続を確認 |
| HL005 | 権限不足 | ユーザー権限を確認 |

### デバッグモード

#### 有効化方法
```php
// wp-config.php に追加
define('HOPLINK_DEBUG', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

#### ログ出力例
```php
// カスタムログ出力
hoplink_log('商品検索開始', [
    'keyword' => $keyword,
    'platform' => $platform
]);
```

## 💰 収益最大化機能

### スマート在庫管理
```php
// 在庫切れ商品の自動除外
function check_product_availability($product_id) {
    $stock_status = get_cached_stock_status($product_id);
    if ($stock_status === 'out_of_stock') {
        // 代替商品を自動提案
        return get_alternative_product($product_id);
    }
    return $product_id;
}
```

### 価格変動アラート
- **値下げ通知**: 管理者にメール通知
- **セール情報**: 記事にセールバッジ自動表示
- **期間限定表示**: タイムセール対応

### A/B/Cテスト機能
```php
// 3パターン同時テスト
$test_variants = [
    'A' => ['layout' => 'card', 'button' => 'orange'],
    'B' => ['layout' => 'list', 'button' => 'green'],
    'C' => ['layout' => 'table', 'button' => 'blue']
];
```

## 👨‍💻 開発者向け情報

### ファイル構造
```
hoplink/
├── hoplink.php                 # メインプラグインファイル
├── includes/
│   ├── class-hoplink.php      # コアクラス
│   ├── class-analyzer.php     # 記事解析クラス
│   ├── class-api-manager.php  # API管理クラス
│   └── class-product-manager.php # 商品管理クラス
├── admin/
│   ├── class-admin.php        # 管理画面クラス
│   ├── views/                 # 管理画面テンプレート
│   └── assets/                # CSS/JS
├── public/
│   ├── class-public.php       # フロントエンド表示
│   ├── templates/             # 商品表示テンプレート
│   └── assets/                # CSS/JS
├── languages/                 # 翻訳ファイル
└── tests/                     # テストファイル
```

### 開発環境セットアップ

#### 必要ツール
```bash
# Composer (依存関係管理)
curl -sS https://getcomposer.org/installer | php

# Node.js (ビルドツール)
npm install

# WordPress CLI
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
```

#### 開発用インストール
```bash
# リポジトリクローン
git clone https://github.com/yourusername/hoplink.git
cd hoplink

# 依存関係インストール
composer install
npm install

# ビルド
npm run build
```

#### テスト実行
```bash
# PHPUnit テスト
composer test

# JavaScript テスト
npm test

# コード品質チェック
composer run phpcs
npm run lint
```

### API設計思想

#### RESTful設計
- リソース指向のURL設計
- HTTPメソッドの適切な使用
- ステータスコードの正確な返却

#### エラーハンドリング
```php
// 統一されたエラーレスポンス
{
    "success": false,
    "error": {
        "code": "HL001",
        "message": "API認証に失敗しました",
        "details": "アクセスキーが無効です"
    }
}
```

#### バージョニング
- URLにバージョン番号を含める: `/wp-json/hoplink/v1/`
- 下位互換性を維持
- 変更時は適切な廃止予告

### 拡張開発ガイド

#### カスタムアナライザー
```php
class CustomBeerAnalyzer implements HopLink_Analyzer_Interface {
    public function analyze($content) {
        // カスタム解析ロジック
        return [
            'keywords' => $this->extract_keywords($content),
            'confidence' => $this->calculate_confidence($content)
        ];
    }
}

// 登録
add_filter('hoplink_analyzers', function($analyzers) {
    $analyzers['custom'] = new CustomBeerAnalyzer();
    return $analyzers;
});
```

#### カスタムAPI連携
```php
class CustomAffiliateAPI implements HopLink_API_Interface {
    public function search($keywords, $options = []) {
        // カスタムAPI呼び出し
        return $this->call_api($keywords, $options);
    }
}

// 登録
add_filter('hoplink_apis', function($apis) {
    $apis['custom'] = new CustomAffiliateAPI();
    return $apis;
});
```

## 🚦 ロードマップ

### バージョン 1.x (現在)
- [x] 基本的な記事解析機能
- [x] 楽天・Amazon API連携
- [x] 商品カード表示
- [x] 基本統計機能
- [x] API最小限利用モード
- [x] コンプライアンス機能
- [x] SEO最適化機能
- [x] 収益最大化機能

### バージョン 2.0 (予定)
- [ ] AI/ML による解析精度向上（無料のTensorFlow.js使用）
- [ ] 多言語対応強化
- [ ] 他アフィリエイトサービス対応
- [ ] 高度なA/B/Cテスト機能
- [ ] PWA対応（オフライン機能）
- [ ] GraphQL APIサポート
- [ ] WebSocketリアルタイム更新

### バージョン 3.0 (構想)
- [ ] 画像認識による商品識別
- [ ] 音声入力対応
- [ ] ブロックチェーン対応
- [ ] VR/AR商品プレビュー

## 📄 ライセンス

HopLinkはGPLv2以降のライセンスの下で配布されています。

```
HopLink - WordPress Craft Beer Affiliate Plugin
Copyright (C) 2025 Your Name

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 🤝 コントリビューション

### バグレポート
GitHubの[Issues](https://github.com/yourusername/hoplink/issues)でバグレポートをお願いします。

### 機能要望
[Discussions](https://github.com/yourusername/hoplink/discussions)で機能要望をお聞かせください。

### プルリクエスト
1. フォークしてフィーチャーブランチを作成
2. 変更をコミット
3. テストを実行
4. プルリクエストを作成

## 📞 サポート

### コミュニティサポート
- [GitHub Discussions](https://github.com/yourusername/hoplink/discussions)
- [WordPress.org フォーラム](https://wordpress.org/support/plugin/hoplink/)

### 有料サポート
- カスタマイズ依頼
- 導入コンサルティング
- 専用サポート契約

### 連絡先
- Email: support@hoplink.dev
- Twitter: [@hoplink_plugin](https://twitter.com/hoplink_plugin)
- Web: [https://hoplink.dev](https://hoplink.dev)

---

**HopLink** - Crafted with 🍺 for beer enthusiasts by beer enthusiasts.
