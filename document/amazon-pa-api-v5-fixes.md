# Amazon PA-API v5 日本向け設定修正

## 修正概要
Web調査結果に基づいて、Amazon PA-API v5の日本向け設定を正確に修正しました。

## 修正内容

### 1. リージョン設定の修正
**変更前:** `ap-northeast-1` (東京リージョン)
**変更後:** `us-west-2`

```php
// 修正前
$this->region = 'ap-northeast-1'; // 日本リージョン（東京）

// 修正後
$this->region = 'us-west-2'; // 日本でもus-west-2を使用（PA-API v5の仕様）
```

### 2. サービス名の修正
**変更前:** `paapi`
**変更後:** `ProductAdvertisingAPI`

#### 署名文字列作成での修正
```php
// 修正前
$credential_scope = $date . '/' . $this->region . '/paapi/aws4_request';

// 修正後
$credential_scope = $date . '/' . $this->region . '/ProductAdvertisingAPI/aws4_request';
```

#### 署名計算での修正
```php
// 修正前
$k_service = hash_hmac('sha256', 'paapi', $k_region, true);

// 修正後
$k_service = hash_hmac('sha256', 'ProductAdvertisingAPI', $k_region, true);
```

#### Authorizationヘッダーでの修正
```php
// 修正前
$credential_scope = $date . '/' . $this->region . '/paapi/aws4_request';

// 修正後
$credential_scope = $date . '/' . $this->region . '/ProductAdvertisingAPI/aws4_request';
```

## 修正後の設定一覧

### 重要な設定項目
- **Host:** `webservices.amazon.co.jp` (変更なし)
- **Region:** `us-west-2` (修正済み)
- **Service:** `ProductAdvertisingAPI` (修正済み)
- **Marketplace:** `www.amazon.co.jp` (変更なし)
- **Path:** `/paapi5/searchitems` (変更なし)

### AWS署名プロセスでの使用
1. **Credential Scope:** `{date}/us-west-2/ProductAdvertisingAPI/aws4_request`
2. **Service Key:** `ProductAdvertisingAPI` を使用してHMAC-SHA256計算
3. **Authorization Header:** 正確なCredential Scopeを含む

## 影響
これらの修正により、Amazon PA-API v5の日本向けAPIが正確な設定で動作するようになり、認証エラーが解決される見込みです。

## 注意事項
- 既存のAPIキーはそのまま使用可能
- キャッシュが残っている場合は、WordPress transientをクリアすることを推奨
- デバッグモードを有効にして、修正後の動作を確認することを推奨