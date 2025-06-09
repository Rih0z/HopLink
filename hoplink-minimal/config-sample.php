<?php
/**
 * API設定サンプルファイル
 * 
 * このファイルをconfig.phpにコピーして、実際のAPI認証情報を入力してください。
 * config.phpは.gitignoreに含まれているため、Gitにはコミットされません。
 */

// 楽天API設定
define('HOPLINK_RAKUTEN_APP_ID', 'your_rakuten_app_id_here');
define('HOPLINK_RAKUTEN_AFFILIATE_ID', 'your_rakuten_affiliate_id_here');

// Amazon PA-API v5設定（日本）
// 認証情報は https://affiliate.amazon.co.jp/assoc_credentials/home から取得
// 重要：日本のPA-APIはap-northeast-1リージョンとwebservices.amazon.co.jpエンドポイントを使用
define('HOPLINK_AMAZON_ACCESS_KEY', 'your_amazon_access_key_here');     // 20文字の英数字
define('HOPLINK_AMAZON_SECRET_KEY', 'your_amazon_secret_key_here');     // 40文字の英数字記号
define('HOPLINK_AMAZON_PARTNER_TAG', 'your_amazon_partner_tag_here');   // アソシエイトID（例：yourname-22）

// デバッグ設定（オプション）
// Amazon APIのデバッグモードを有効にする場合はtrueに設定
// define('HOPLINK_DEBUG', true);