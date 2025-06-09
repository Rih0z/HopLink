# HopLink - WordPress アフィリエイトリンク自動生成プラグイン

[![Version](https://img.shields.io/badge/version-1.4.0-blue.svg)](https://github.com/yourusername/hoplink)
[![WordPress](https://img.shields.io/badge/WordPress-5.0+-green.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--2.0+-red.svg)](LICENSE)

HopLinkは、Amazon・楽天市場のアフィリエイトリンクを自動生成するWordPressプラグインです。記事内容を解析して関連商品を自動表示し、売上条件を満たしていない場合でも利用できるManual Mode機能を搭載しています。

## 🌟 主な機能

### 🔍 自動商品検索
- 記事内容を解析してビール関連キーワードを自動抽出
- ビアスタイル、ブルワリー名、関連商品を自動認識
- クラフトビール専門の豊富なキーワード辞書

### 🛒 マルチプラットフォーム対応
- **Amazon**: PA-API v5対応（条件付き）
- **楽天市場**: 楽天ウェブサービスAPI対応
- 両プラットフォームの商品を同時検索・表示

### 📱 完全レスポンシブデザイン
- **PC**: 4列グリッド表示
- **タブレット**: 2列グリッド表示  
- **スマホ**: 1列表示
- スムーズなアニメーション効果

### 🚀 PA-API不要モード
- **Manual Mode**: 手動で商品情報を登録
- **URL Mode**: Amazon URLからASINを自動抽出
- **Hybrid Mode**: 手動登録＋API自動補完

### 🔄 クロスプラットフォーム検索
- 楽天商品をAmazonでも自動検索
- JANコード・商品名による高精度マッチング
- 誤マッチング防止機能

## 📥 インストール

### 要件
- WordPress 5.0以上
- PHP 7.4以上
- SSL対応サーバー（HTTPS必須）

### インストール手順

1. **GitHubから最新版をダウンロード**
   ```bash
   git clone https://github.com/yourusername/hoplink.git
   cd hoplink/hoplink-minimal
   ```

2. **設定ファイルの準備**
   ```bash
   cp config-sample.php config.php
   ```

3. **API認証情報の設定**
   ```php
   // config.php を編集
   define('HOPLINK_RAKUTEN_APP_ID', 'your_app_id');
   define('HOPLINK_RAKUTEN_AFFILIATE_ID', 'your_affiliate_id');
   define('HOPLINK_AMAZON_PARTNER_TAG', 'your_partner_tag');
   ```

4. **ZIPファイルの作成**
   ```bash
   ./create-zip.sh
   ```

5. **WordPressにアップロード**
   - 管理画面 → プラグイン → 新規追加 → プラグインのアップロード
   - 作成されたZIPファイルを選択してインストール

## ⚙️ 初期設定

### 楽天市場API
1. [楽天ウェブサービス](https://webservice.rakuten.co.jp/)でアプリ登録
2. アプリケーションIDを取得
3. 楽天アフィリエイトに登録してアフィリエイトIDを取得

### Amazon アソシエイト
1. [Amazonアソシエイト](https://affiliate.amazon.co.jp/)に登録
2. パートナータグ（トラッキングID）を取得
3. **PA-API利用条件**:
   - 新規: 180日以内に3件の売上が必要
   - 既存: 過去30日間の売上実績が必要

## 🚀 使い方

### 基本のショートコード

```php
// キーワード検索
[hoplink keyword="クラフトビール"]

// 自動解析（記事内容から関連商品を検索）
[hoplink_auto]

// プラットフォーム指定
[hoplink keyword="IPA" platform="amazon" limit="2"]
```

### Manual Mode（PA-API不要）

```php
// ASIN指定
[hoplink_manual asin="B08XYZ1234"]

// キーワード検索（手動登録商品から）
[hoplink_manual keyword="IPA" limit="3"]
```

### URL変換モード

```php
// Amazon URLを自動でアフィリエイトリンクに変換
[hoplink_url url="https://www.amazon.co.jp/dp/B08XYZ1234"]

// カスタムテキスト指定
[hoplink_url url="https://amzn.to/3abc123" text="この商品をチェック" button="true"]
```

### クロスプラットフォーム検索

```php
// 楽天商品をAmazonでも検索
[hoplink_cross url="https://item.rakuten.co.jp/shop/item123"]

// JANコードで両プラットフォーム検索
[hoplink_cross jan="4901234567890"]
```

## 📊 パラメータ一覧

| パラメータ | 説明 | デフォルト値 | 例 |
|----------|------|------------|-----|
| `keyword` | 検索キーワード | - | `"クラフトビール"` |
| `platform` | 検索対象 | `"all"` | `"amazon"`, `"rakuten"`, `"all"` |
| `limit` | 表示商品数 | `4` | `2`, `6`, `8` |
| `layout` | レイアウト | `"grid"` | `"grid"`, `"list"` |
| `asin` | Amazon ASIN | - | `"B08XYZ1234"` |
| `url` | 商品URL | - | Amazon・楽天の商品URL |
| `text` | リンクテキスト | `"詳細を見る"` | `"購入する"` |
| `button` | ボタン表示 | `false` | `true`, `false` |

## 🎨 カスタマイズ

### CSS カスタマイズ

```css
/* 商品カードのカスタマイズ */
.hoplink-container {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

/* ボタンのカスタマイズ */
.hoplink-button {
    background: linear-gradient(45deg, #007cba, #00a0d2);
    color: white;
    border-radius: 5px;
}

/* レスポンシブカスタマイズ */
@media (max-width: 768px) {
    .hoplink-products-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}
```

### フック・フィルター

```php
// 商品検索結果をフィルタ
add_filter('hoplink_search_results', function($products, $keyword) {
    // カスタム処理
    return $products;
}, 10, 2);

// 表示テンプレートをカスタマイズ
add_filter('hoplink_product_template', function($template, $product) {
    // カスタムテンプレート
    return $template;
}, 10, 2);
```

## 🔧 動作モード

### 1. API Mode
PA-APIを使用して商品情報を自動取得
- 最新の商品情報
- 豊富な商品データ
- 売上条件が必要

### 2. Manual Mode  
手動で商品情報を登録・管理
- PA-API不要
- 永続的なデータ保存
- CSV一括インポート対応

### 3. Hybrid Mode
手動登録を優先し、不足分をAPIで補完
- 最適なパフォーマンス
- フレキシブルな運用
- 段階的な移行が可能

## 🐛 トラブルシューティング

### よくある問題

**❌ Amazon API接続エラー**
```
解決方法:
1. アソシエイト・セントラルで売上実績を確認
2. パートナータグの形式確認（例：yourname-22）
3. Manual Modeの利用を検討
```

**❌ 楽天API接続エラー**
```
解決方法:
1. アプリケーションIDの確認
2. APIリクエスト制限の確認
3. SSL証明書の確認
```

**❌ 商品が表示されない**
```
解決方法:
1. キーワード辞書の確認
2. 管理画面でAPI接続テスト実行
3. デバッグモードを有効化
```

### デバッグモード

```php
// config.php に追加
define('HOPLINK_DEBUG', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📈 パフォーマンス最適化

### キャッシュ設定
- デフォルト: 24時間キャッシュ
- 設定画面から期間変更可能
- 手動でキャッシュクリア可能

### レート制限対策
- Amazon PA-API: 最低1秒間隔
- 自動リトライ機能（指数バックオフ）
- TooManyRequestsエラー防止

## 📝 ライセンス

GPL-2.0+ - [LICENSE](LICENSE) ファイルを参照

## 🤝 コントリビューション

1. このリポジトリをフォーク
2. フィーチャーブランチを作成 (`git checkout -b feature/amazing-feature`)
3. 変更をコミット (`git commit -m 'Add amazing feature'`)
4. ブランチにプッシュ (`git push origin feature/amazing-feature`)
5. プルリクエストを作成

## 📞 サポート

- **Issues**: [GitHub Issues](https://github.com/yourusername/hoplink/issues)
- **ドキュメント**: [Wiki](https://github.com/yourusername/hoplink/wiki)
- **メール**: your.email@example.com

## 🎯 ロードマップ

### v1.5.0 (予定)
- [ ] WooCommerce連携
- [ ] 商品比較機能
- [ ] 詳細な統計機能

### v1.6.0 (予定)  
- [ ] Gutenbergブロック強化
- [ ] 多言語対応
- [ ] 他ECサイト対応

## 📊 更新履歴

### v1.4.0 (2025-01-09)
- ✨ レスポンシブ広告レイアウト対応
- ✨ 楽天→Amazon商品マッチング機能
- 🎨 表示を「関連する広告」に変更
- 📱 PC4列/タブレット2列/スマホ1列対応

### v1.3.0 (2025-01-09)
- ✨ Amazon URLからASIN自動抽出
- ✨ 記事保存時の自動変換機能
- ✨ Gutenbergブロック対応

### v1.2.0 (2025-01-09)
- ✨ Manual Mode追加（PA-API不要）
- ✨ ASIN商品管理機能
- ✨ CSV一括インポート

### v1.1.0 (2025-01-09)
- ✨ 商品検索テスト機能
- 🔧 Amazon PA-API v5レート制限対策
- 🔍 包括的診断ツール

### v1.0.0 (2025-01-08)
- 🎉 初回リリース

---

<div align="center">

**HopLink** で効率的なアフィリエイト運営を始めましょう！

[⬇️ ダウンロード](https://github.com/yourusername/hoplink/releases) | [📖 ドキュメント](https://github.com/yourusername/hoplink/wiki) | [🐛 Issues](https://github.com/yourusername/hoplink/issues)

</div>