# HopLink - WordPress アフィリエイトリンク自動生成プラグイン

HopLinkは、Amazon・楽天市場のアフィリエイトリンクを自動生成するWordPressプラグインです。

## 主な機能

- 🔍 **自動商品検索**: 記事内容を解析して関連商品を自動表示
- 🛒 **マルチプラットフォーム対応**: Amazon・楽天市場の両方に対応
- 📱 **レスポンシブデザイン**: PC/タブレット/スマホに最適化
- 🚀 **PA-API不要モード**: 売上条件を満たしていなくても利用可能
- 🔄 **クロスプラットフォーム検索**: 楽天商品をAmazonでも検索

## インストール

1. 最新版をダウンロード
2. WordPressの管理画面から「プラグイン」→「新規追加」→「プラグインのアップロード」
3. ZIPファイルを選択してインストール
4. プラグインを有効化

## 初期設定

### 楽天市場
1. [楽天ウェブサービス](https://webservice.rakuten.co.jp/)でアプリケーションIDを取得
2. HopLink設定画面でアプリケーションIDとアフィリエイトIDを入力

### Amazon
1. [Amazonアソシエイト](https://affiliate.amazon.co.jp/)に登録
2. パートナータグを取得
3. HopLink設定画面でパートナータグを入力

※PA-APIの利用には売上条件があります。条件を満たしていない場合はManual Modeをご利用ください。

## 使い方

### 基本のショートコード

```
[hoplink keyword="クラフトビール"]
```

### 自動解析（記事内容から商品を自動検索）

```
[hoplink_auto]
```

### 手動モード（PA-API不要）

```
[hoplink_manual asin="B08XYZ1234"]
```

### URLから変換

```
[hoplink_url url="https://www.amazon.co.jp/dp/B08XYZ1234"]
```

### クロスプラットフォーム検索

```
[hoplink_cross url="楽天商品URL"]
```

## パラメータ

- `keyword` - 検索キーワード
- `platform` - 検索対象（all/rakuten/amazon）
- `limit` - 表示数（デフォルト: 4）
- `layout` - レイアウト（grid/list）

## 動作モード

1. **API Mode**: PA-APIを使用して商品情報を自動取得
2. **Manual Mode**: 手動で商品情報を登録（PA-API不要）
3. **Hybrid Mode**: 手動登録を優先し、不足分をAPIで補完

## 必要環境

- WordPress 5.0以上
- PHP 7.4以上
- SSL対応サーバー

## ライセンス

GPL-2.0+

## 作者

[Your Name]

## 更新履歴

### v1.4.0 (2025-01-09)
- レスポンシブ広告レイアウト対応
- 楽天→Amazon商品マッチング機能
- 表示を「関連する広告」に変更

### v1.3.0 (2025-01-09)
- Amazon URLからASIN自動抽出
- 記事保存時の自動変換機能
- Gutenbergブロック対応

### v1.2.0 (2025-01-09)
- Manual Mode追加（PA-API不要）
- ASIN商品管理機能
- CSV一括インポート

### v1.1.0 (2025-01-09)
- 商品検索テスト機能
- Amazon PA-API v5レート制限対策
- 包括的診断ツール

### v1.0.0 (2025-01-08)
- 初回リリース