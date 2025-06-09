# Amazon PA-API 動作確認ガイド

## 現在の診断結果の分析

診断結果で以下の状況が確認されています：
- API呼び出しが実行されていない（実行時間: 0.08ms）
- debug_logsが空
- レート制限対策は正常動作
- 基本設定はすべて正常

## テスト手順

### 1. プラグインのインストールと有効化

```bash
# WordPressのプラグインディレクトリに移動
cd /path/to/wordpress/wp-content/plugins/

# HopLinkディレクトリをコピー
cp -r /Users/kokiriho/Documents/Projects/affiliate/shop/HopLink .

# WordPress管理画面でプラグインを有効化
```

### 2. API認証情報の設定

1. WordPress管理画面にログイン
2. **HopLink → Settings** にアクセス
3. Amazon PA-API設定を入力：
   - Access Key ID: 20文字の英数字
   - Secret Access Key: 40文字の英数字記号
   - Associate Tag: yourname-22 形式

### 3. デバッグモードの有効化

1. **HopLink → Settings → Performance** タブ
2. "Enable debug logging" にチェック
3. Save Settings

### 4. API接続テスト

1. **HopLink → API Test** にアクセス
2. **Connection Test** セクション：
   - "Test Connection" ボタンをクリック
   - 成功時：実行時間とサンプル商品が表示
   - 失敗時：エラーメッセージと詳細が表示

### 5. 商品検索テスト

1. **Product Search Test** セクション：
   - キーワード入力欄に "beer" と入力
   - "Search Products" ボタンをクリック
   - 結果を確認：
     - 商品画像
     - 商品タイトル
     - 価格（¥表示）
     - ASIN
     - 在庫状況
     - Prime対応状況

### 6. ショートコードテスト

1. 新規投稿または固定ページを作成
2. 以下のショートコードを挿入：
   ```
   [hoplink keyword="beer"]
   ```
3. プレビューまたは公開して表示確認

### 7. エラーログの確認

```bash
# WordPressのdebug.logを確認
tail -f /path/to/wordpress/wp-content/debug.log | grep HopLink
```

## 期待される動作

### 正常な API レスポンス例

```json
{
    "success": true,
    "message": "Found 5 products for \"beer\"",
    "execution_time": "523.45ms",
    "results": [
        {
            "platform": "amazon",
            "product_id": "B07XYZ123",
            "title": "サッポロ 黒ラベル 350ml×24本",
            "price": 4980,
            "price_formatted": "¥4,980",
            "availability": "在庫あり",
            "is_prime": true
        }
    ]
}
```

### エラーレスポンス例

```json
{
    "success": false,
    "message": "InvalidSignature: The request signature does not match",
    "execution_time": "125.32ms",
    "error_data": {
        "code": 401,
        "body": "..."
    }
}
```

## トラブルシューティング

### 1. API呼び出しが0.08msで終了する場合

**原因**: 認証情報が設定されていない
**対処**: Settings画面で認証情報を正しく入力

### 2. InvalidSignature エラー

**原因**: Secret Keyが正しくない
**対処**: 
- Secret Keyの前後の空白を確認
- 40文字ちょうどであることを確認

### 3. InvalidParameterValue エラー

**原因**: Associate Tagの形式が正しくない
**対処**: 
- 日本の場合は "-22" で終わる形式
- 例: mystore-22

### 4. RequestThrottled エラー

**原因**: APIレート制限
**対処**: 
- 数分待ってから再試行
- キャッシュ機能を有効化

## デバッグ情報の見方

### ログエントリの例

```
[2025-01-06 12:34:56] HopLink Amazon API - API Request: Array
(
    [operation] => SearchItems
    [payload] => Array
        (
            [Keywords] => beer
            [ItemCount] => 10
            [PartnerTag] => mystore-22
            [Marketplace] => www.amazon.co.jp
        )
    [headers] => Array
        (
            [content-type] => application/json; charset=utf-8
            [x-amz-target] => com.amazon.paapi5.v1.ProductAdvertisingAPIv1.SearchItems
        )
)

[2025-01-06 12:34:57] HopLink Amazon API - API Response: Array
(
    [code] => 200
    [body] => {"SearchResult":{"Items":[...]}}
)
```

## キャッシュの動作確認

1. 初回検索：APIが呼び出される（実行時間: 500ms以上）
2. 2回目の同じ検索：キャッシュから返される（実行時間: 50ms以下）
3. キャッシュクリア後：再度APIが呼び出される

## パフォーマンス指標

- **正常なAPI呼び出し**: 300-800ms
- **キャッシュヒット**: 10-50ms
- **エラーレスポンス**: 100-300ms

## 次のステップ

1. 実際のWordPress環境でプラグインをインストール
2. API認証情報を設定
3. 上記のテスト手順を実行
4. エラーログを確認
5. 必要に応じてデバッグ情報を収集