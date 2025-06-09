# HopLink Minimal - 最小限のアフィリエイトプラグイン

シンプルなWordPressアフィリエイトプラグインです。楽天とAmazonのAPIを使用して商品を検索・表示します。

## インストール方法

1. `hoplink-minimal`フォルダをWordPressの`wp-content/plugins/`にアップロード
2. WordPress管理画面でプラグインを有効化
3. 管理画面の「HopLink」からAPIキーを設定

## 設定

### 方法1: config.phpを使用（推奨）
1. `config-sample.php`を`config.php`にコピー
2. `config.php`にAPI認証情報を記入
3. プラグインを有効化（自動的に設定が反映されます）

### 方法2: 管理画面から設定
1. WordPress管理画面 → HopLink
2. 各APIの認証情報を入力

### 楽天API
- [楽天ウェブサービス](https://webservice.rakuten.co.jp/)でアプリ登録
- アプリケーションIDとアフィリエイトIDを設定

### Amazon PA-API v5（日本）
- [Amazon Product Advertising API](https://affiliate.amazon.co.jp/assoc_credentials/home)で認証情報取得
- アクセスキー（20文字）、シークレットキー（40文字）、パートナータグを設定
- **重要**: 日本のPA-APIは以下の設定を使用：
  - エンドポイント: `webservices.amazon.co.jp`
  - リージョン: `ap-northeast-1`（東京）
  - マーケットプレイス: `www.amazon.co.jp`

## 使い方

### 1. 手動でキーワード指定

記事内に以下のショートコードを挿入：

```
[hoplink keyword="クラフトビール" platform="all" limit="3"]
```

#### パラメータ
- `keyword` - 検索キーワード（必須）
- `platform` - all/rakuten/amazon（デフォルト: all）
- `limit` - 表示数（デフォルト: 3）
- `layout` - grid/list（デフォルト: grid）

### 2. 記事内容を自動解析（新機能！）

記事の内容を自動で解析して関連商品を表示：

```
[hoplink_auto]
```

または、オプション付き：

```
[hoplink_auto limit="5" platform="rakuten"]
```

#### 自動解析の仕組み
- 記事内のビール関連キーワードを自動抽出
- IPA、クラフトビール、ビアグラスなどを認識
- 最も関連性の高い商品を自動表示

#### 対応キーワード例
- ビアスタイル: IPA、ペールエール、スタウト、ピルスナーなど
- ブルワリー: ヤッホーブルーイング、コエドブルワリーなど
- 関連商品: ビールグラス、ビアサーバー、ギフトセットなど

## セキュリティ

- `config.php`は`.gitignore`に含まれており、Gitにコミットされません
- API認証情報は環境変数または`config.php`で管理してください
- 本番環境では必ず実際のAPI認証情報を使用してください

## 配布用ZIPファイルの作成

```bash
./create-zip.sh
```

このスクリプトは`config.php`を含めたZIPファイルを作成します。
⚠️ **注意**: 作成されたZIPファイルにはAPIキーが含まれているため、取り扱いに注意してください。

## Amazon PA-API v5 完全対応（日本）

このプラグインはAmazon Product Advertising API v5の日本仕様に完全対応しています：

- AWS署名バージョン4による認証（ap-northeast-1リージョン）
- 商品検索（SearchItems）API対応
- 商品情報（価格、画像、レビュー）の取得
- エラーハンドリング実装済み
- 日本のマーケットプレイス（www.amazon.co.jp）対応
- 正確な日本リージョン設定（東京: ap-northeast-1）

## API接続テスト機能

管理画面でAPI接続状態を確認できます：
- 楽天API接続テスト
- Amazon API接続テスト
- キャッシュクリア機能

## 注意事項

- APIレート制限に注意してください（楽天: 1秒1リクエスト、Amazon: 1秒1リクエスト）
- キャッシュ機能を有効にすることを推奨します（デフォルト24時間）
- Amazon APIはアソシエイトプログラムの売上実績が必要です

## ライセンス

GPL-2.0+