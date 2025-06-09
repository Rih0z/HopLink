# HopLink Manual Mode Guide - PA-APIを使わないアフィリエイトリンク生成

## 概要

HopLinkの手動モードは、Amazon PA-APIを使用せずにアフィリエイトリンクを生成できる機能です。APIの利用制限や申請の問題を回避しながら、Amazon商品のアフィリエイトリンクを管理できます。

## 主な機能

### 1. 手動商品管理
- ASINコードと商品情報を手動で登録
- 商品名、価格、画像URL、説明文などを管理
- カテゴリとキーワードによる分類

### 2. 3つの動作モード
- **Manual Mode**: PA-APIを一切使用せず、手動登録商品のみ使用
- **API Mode**: 従来通りPA-APIで自動取得（デフォルト）
- **Hybrid Mode**: 手動登録商品を優先し、不足分をAPIで補完

### 3. 新しいショートコード
```
[hoplink_manual] - 手動登録商品専用
[hoplink]        - モード設定に応じて自動切り替え
```

## セットアップ手順

### 1. プラグインの有効化
WordPressの管理画面からHopLinkプラグインを有効化します。

### 2. 動作モードの設定
1. **HopLink → Settings** を開く
2. **Amazon PA-API** タブで **Amazon Mode** を選択：
   - **Manual (No API)**: PA-APIキー不要、手動商品のみ
   - **PA-API (Automatic)**: 従来のAPI自動取得
   - **Hybrid (API + Manual)**: 両方を併用

### 3. 商品の登録
1. **HopLink → Manual Products** を開く
2. **Add New** ボタンをクリック
3. 必要な情報を入力：
   - **ASIN Code**: Amazon商品のASINコード（必須）
   - **Product Name**: 商品名（必須）
   - **Price**: 価格（オプション）
   - **Image URL**: 商品画像のURL（オプション）
   - **Description**: 商品説明（オプション）
   - **Category**: カテゴリ（beer, glass, gift, book, accessory, other）
   - **Keywords**: 検索用キーワード（カンマ区切り）

## 使用方法

### 基本的な使い方

#### 特定のASINを表示
```
[hoplink_manual asin="B08L5VG843"]
```

#### 複数のASINを表示
```
[hoplink_manual asins="B08L5VG843,B07XLFXJ8P,B08BXCFQSG"]
```

#### キーワードで検索
```
[hoplink_manual keyword="IPA" max="3"]
```

#### カテゴリで表示
```
[hoplink_manual category="glass" max="5" layout="grid"]
```

#### ランダム表示
```
[hoplink_manual category="beer" max="3" random="true"]
```

### 既存ショートコードとの互換性

既存の `[hoplink]` ショートコードも、設定したモードに応じて動作します：

- **Manual Mode**: 手動登録商品から検索
- **API Mode**: PA-APIから検索
- **Hybrid Mode**: 手動登録商品を優先、不足分をAPIで補完

```
[hoplink keyword="クラフトビール" platform="amazon" max="5"]
```

## CSV一括インポート

### CSVフォーマット
```csv
ASIN,Product Name,Price,Image URL,Description,Category,Keywords
B08L5VG843,"YEBISU ビール ギフトセット YE3D",3300,https://example.com/image.jpg,"ギフトセット",beer,"ビール,ギフト"
```

### インポート手順
1. **HopLink → Manual Products** を開く
2. **Import CSV** ボタンをクリック
3. CSVファイルを選択してアップロード

## 高度な設定

### URLフォーマット
生成されるアフィリエイトURLのフォーマット：
```
https://www.amazon.co.jp/dp/{ASIN}?tag={associate_tag}
```

### キャッシュ設定
- 手動登録商品はデータベースに永続的に保存
- 表示用HTMLはトランジェントキャッシュで高速化
- キャッシュ期間は設定で調整可能

### パフォーマンス最適化
- 商品データは初回読み込み時にメモリにキャッシュ
- インデックスによる高速検索
- 画像の遅延読み込み対応

## トラブルシューティング

### Q: 商品が表示されない
A: 以下を確認してください：
- 商品のステータスが「Active」になっているか
- ASINコードが正しく入力されているか
- キーワードやカテゴリが一致しているか

### Q: 画像が表示されない
A: 画像URLが正しいか確認し、HTTPSのURLを使用してください。

### Q: インポートが失敗する
A: CSVファイルの文字コードがUTF-8であることを確認してください。

## ベストプラクティス

1. **定期的な価格更新**: 手動モードでは価格が自動更新されないため、定期的に確認
2. **画像の最適化**: WebP形式や適切なサイズの画像を使用
3. **キーワードの設定**: 複数のキーワードを設定して検索性を向上
4. **カテゴリの活用**: 商品を適切に分類して管理を効率化

## 楽天市場との併用

楽天市場は引き続きAPIモードで動作します：
```
[hoplink keyword="クラフトビール" platform="rakuten" max="3"]
```

## まとめ

HopLinkの手動モードにより、PA-APIの制限を気にすることなく、柔軟にAmazonアフィリエイトリンクを管理できます。商品データを自分でコントロールできるため、APIダウン時にも安定してサービスを提供できます。