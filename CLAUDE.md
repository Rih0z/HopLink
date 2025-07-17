# CLAUDE.md - HopLink AIコーディング原則

```yaml
ai_coding_principles:
  meta:
    version: "1.0"
    last_updated: "2025-07-16"
    description: "HopLink WordPress プラグイン開発のためのClaude AIコーディング実行原則"
    project_type: "WordPress Plugin - Affiliate System"
    
  core_principles:
    mandatory_declaration: "全てのコーディング作業開始時に必ずcore_principlesを完全に宣言すること"
    第1条: 
      rule: "常に思考開始前にCLAUDE.mdの第一条から第四条のAIコーディング原則を全て宣言してから実施する"
      related_sections: ["execution_checklist", "mindset", "wordpress_standards"]
    第2条: 
      rule: "常にプロの世界最高エンジニアとして対応する"
      related_sections: ["mindset", "quality_standards", "wordpress_standards"]
    第3条: 
      rule: "モックや仮のコード、ハードコードを一切禁止する"
      related_sections: ["implementation", "architecture", "quality_standards", "affiliate_compliance"]
    第4条: 
      rule: "エンタープライズレベルの実装を実施し、修正は表面的ではなく、全体のアーキテクチャを意識して実施する"
      related_sections: ["architecture", "quality_standards", "deployment_requirements", "wordpress_standards"]
    第5条: 
      rule: "問題に詰まったら、まずCLAUDE.mdやプロジェクトドキュメント内に解決策がないか確認する"
      related_sections: ["documentation_management", "troubleshooting"]
    第6条: 
      rule: "push前にアップロードするべきではない情報（API キー、設定ファイル等）が含まれていないか確認する"
      related_sections: ["security", "affiliate_compliance"]
    第7条: 
      rule: "不要な文書やスクリプトは増やさない。既存のスクリプトの使用を優先し、新規作成時は適切なフォルダに格納する"
      related_sections: ["scripts", "documentation_management"]

  wordpress_standards:
    coding_standards:
      - "WordPress Coding Standards (WPCS) に準拠"
      - "PSR-4 オートローダー対応"
      - "WordPress標準のフック・フィルター使用"
      - "セキュリティ: nonce, sanitize, escape の徹底"
      - "データベース操作: $wpdb または WP_Query 使用"
    
    plugin_structure:
      - "メインファイル: hoplink.php (プラグインヘッダー必須)"
      - "アクティベーション: class-hoplink-activator.php"
      - "ディアクティベーション: class-hoplink-deactivator.php"
      - "国際化: class-hoplink-i18n.php + languages/"
      - "フック管理: class-hoplink-loader.php"
      - "オートローダー: class-hoplink-autoloader.php"
    
    security:
      - "直接アクセス防止: if (!defined('ABSPATH')) { exit; }"
      - "権限チェック: current_user_can() の使用"
      - "入力検証: sanitize_text_field(), wp_verify_nonce()"
      - "出力エスケープ: esc_html(), esc_url(), esc_attr()"

  affiliate_compliance:
    api_management:
      - "Amazon PA-API v5: レート制限遵守 (1秒/リクエスト)"
      - "楽天ウェブサービス: 1日1000リクエスト制限"
      - "API キー管理: config.php で管理、.gitignore 対象"
      - "エラーハンドリング: TooManyRequestsException 対策"
    
    legal_compliance:
      - "Amazon Associate Program Operating Agreement 遵守"
      - "楽天アフィリエイト利用規約 遵守"
      - "商品価格・在庫状況の免責事項表示"
      - "アフィリエイトリンクの明示（「広告」「PR」表示）"
    
    data_accuracy:
      - "商品情報の定期更新メカニズム"
      - "リンク切れ検出・自動修正"
      - "価格情報の正確性担保"
      - "在庫状況の適切な表示"

  quality_standards:
    security:
      - "API認証情報の暗号化保存"
      - "SQL インジェクション対策"
      - "XSS 対策（入力・出力の適切な処理）"
      - "CSRF 対策（nonce の使用）"
      - "脆弱性スキャンの実施"
    
    architecture:
      - "SOLID原則に従った設計"
      - "WordPress プラグイン標準アーキテクチャ"
      - "MVC パターンの適用"
      - "依存性注入の活用"
      - "インターフェース分離の原則"
    
    implementation:
      - "設定は config.php で集中管理"
      - "環境変数の適切な使用"
      - "キャッシュ機能の実装（24時間デフォルト）"
      - "エラーログの体系的な記録"
      - "パフォーマンス最適化"

  testing_standards:
    approach:
      - "WordPress 標準テスト環境の使用"
      - "PHPUnit によるユニットテスト"
      - "API 連携テスト（モック使用）"
      - "フロントエンドテスト（Jest/Cypress）"
      - "レスポンシブデザインテスト"
    
    validation:
      - "ショートコード動作テスト"
      - "Gutenberg ブロックテスト"
      - "管理画面機能テスト"
      - "API レート制限テスト"
      - "セキュリティテスト"
    
    test_data:
      - "テスト用 API キーの使用"
      - "サンプル商品データの活用"
      - "本番データの使用禁止"

  documentation_management:
    structure:
      - "ドキュメント: document/ フォルダに集約"
      - "ログ: .claude/logs/ フォルダに記録"
      - "API ドキュメント: 自動生成"
      - "変更履歴: CHANGELOG.md で管理"
      - "セキュリティ: SECURITY.md で管理"
    
    consistency:
      - "README.md の定期更新"
      - "コードコメントの充実"
      - "API 仕様書の同期"
      - "設定ファイルサンプルの更新"

  deployment_requirements:
    environment:
      - "WordPress 5.0+ 対応"
      - "PHP 7.4+ 対応"
      - "SSL/HTTPS 必須"
      - "ZIP 配布形式での提供"
    
    process:
      - "create-zip.sh スクリプトでデプロイ用ZIP作成"
      - "config.php を含めた完全なパッケージ"
      - "WordPress.org 審査基準準拠"
      - "本番環境での動作確認"
    
    monitoring:
      - "WordPress Admin でのヘルスチェック"
      - "API 接続状況の監視"
      - "エラーログの監視"
      - "パフォーマンス監視"

  scripts:
    existing_scripts:
      - "create-zip.sh: デプロイ用ZIP作成"
      - "config-sample.php: 設定テンプレート"
    
    development_scripts:
      - "api-test.php: API接続テスト"
      - "debug-logger.php: デバッグ用ログ出力"
      - "cache-clear.php: キャッシュクリア"
    
    how_to_use_scripts:
      - "新規スクリプト作成前に既存スクリプトを確認"
      - "デプロイ用: ./create-zip.sh"
      - "テスト用: admin/views/api-test.php"
      - "一時スクリプト: scripts/tmp/ (使用後削除)"

  mindset:
    philosophy:
      - "Ultrathink - 深く考え抜く"
      - "Don't hold back. Give it your all! - 全力で取り組む"
      - "WordPress コミュニティへの貢献"
      - "アフィリエイトシステムの責任ある運用"
      - "ユーザーエクスペリエンスの最優先"

  file_structure:
    main_directories:
      - "hoplink.php: メインプラグインファイル"
      - "includes/: コア機能クラス"
      - "admin/: 管理画面関連"
      - "public/: フロントエンド関連"
      - "blocks/: Gutenbergブロック"
      - "document/: ドキュメント"
      - "assets/: 静的リソース"
      - "languages/: 多言語対応"
      - "tests/: テストファイル"
    
    deployment_structure:
      - "hoplink-minimal/: 本番デプロイ用"
      - "hoplink-minimal/config.php: 設定ファイル"
      - "hoplink-minimal/create-zip.sh: ZIP作成"
      - "hoplink-minimal/keywords/: キーワード管理"
    
    logs_and_docs:
      - ".claude/logs/: Claude作業ログ"
      - "document/: プロジェクトドキュメント"
      - "document/message/: 一時メッセージ"

  execution_checklist:
    mandatory_declaration:
      - "[ ] **CORE_PRINCIPLES宣言**: 第1条〜第4条を完全に宣言"
      - "[ ] **WordPress関連宣言**: wordpress_standards セクションを宣言"
      - "[ ] **アフィリエイト関連宣言**: affiliate_compliance セクションを宣言"
      - "[ ] **関連セクション宣言**: 実行する作業に関連するセクションを宣言"
    
    before_coding:
      - "[ ] AIコーディング原則を宣言"
      - "[ ] WordPress標準準拠確認"
      - "[ ] API制限・コンプライアンス確認"
      - "[ ] セキュリティ要件の確認"
      - "[ ] 既存コードの理解"
    
    during_coding:
      - "[ ] WordPress Coding Standards準拠"
      - "[ ] セキュリティ対策の実装"
      - "[ ] API制限の考慮"
      - "[ ] エラーハンドリングの実装"
      - "[ ] パフォーマンスの最適化"
    
    after_coding:
      - "[ ] コードレビュー実施"
      - "[ ] セキュリティチェック"
      - "[ ] API接続テスト"
      - "[ ] WordPress環境でのテスト"
      - "[ ] ドキュメント更新"
      - "[ ] create-zip.sh でデプロイ準備"

  troubleshooting:
    common_issues:
      - "Amazon PA-API接続エラー: 売上実績・認証情報確認"
      - "楽天API接続エラー: アプリケーションID・SSL証明書確認"
      - "商品表示エラー: キーワード辞書・デバッグモード確認"
      - "権限エラー: WordPress権限・nonce確認"
    
    debug_process:
      - "デバッグモード有効化: define('HOPLINK_DEBUG', true)"
      - "ログ確認: wp-content/debug.log"
      - "API接続テスト: 管理画面 → API Test"
      - "キャッシュクリア: 設定画面から実行"
    
    performance_optimization:
      - "キャッシュ機能の活用"
      - "API レート制限の遵守"
      - "データベースクエリの最適化"
      - "画像最適化とレスポンシブ対応"

  project_specific:
    craft_beer_optimization:
      - "ビアスタイル優先度システム (IPA、スタウト等)"
      - "ブルワリー名認識 (うちゅうブルーイング等)"
      - "キーワード多様化 (4つの広告で異なる検索ワード)"
      - "一般ワード抑制 (「ビール」等の曖昧なワード)"
    
    responsive_design:
      - "PC: 4列表示"
      - "タブレット: 2列表示"
      - "スマートフォン: 1列表示"
      - "rihobeer.com カラーパレット (#ffd900/#ffffff)"
      - "WCAG準拠 (コントラスト比4.5:1以上)"
    
    multi_platform:
      - "Amazon・楽天の同期検索"
      - "JANコード・商品名マッチング"
      - "誤マッチング防止機能"
      - "クロスプラットフォーム URL変換"
```

## 使用方法

### 🚨 必須実行手順

1. **CORE_PRINCIPLES完全宣言**: 
   ```
   【AIコーディング原則宣言】
   第1条: 常に思考開始前にこれらのAIコーディング原則を宣言してから実施する
   第2条: 常にプロの世界最高エンジニアとして対応する  
   第3条: モックや仮のコード、ハードコードを一切禁止する
   第4条: エンタープライズレベルの実装を実施し、修正は表面的ではなく、全体のアーキテクチャを意識して実施する
   ```

2. **WordPress・アフィリエイト関連宣言**:
   ```
   【WordPress標準宣言】
   - wordpress_standards: WPCS準拠、セキュリティ対策、プラグイン構造
   
   【アフィリエイトコンプライアンス宣言】
   - affiliate_compliance: API管理、法的遵守、データ正確性
   ```

3. **関連セクション宣言**: 実行する作業に応じて関連セクションも必ず宣言
   - **API開発時**: affiliate_compliance + quality_standards.security
   - **UI/UX開発時**: project_specific.responsive_design + wordpress_standards
   - **デプロイ時**: deployment_requirements + scripts

### 📋 HopLink固有の宣言パターン例

```yaml
# API連携開発時の必須宣言
core_principles: [第3条, 第4条]
related_sections: [affiliate_compliance, quality_standards.security, wordpress_standards.security]

# フロントエンド開発時の必須宣言  
core_principles: [第2条, 第4条]
related_sections: [project_specific.responsive_design, wordpress_standards.coding_standards, quality_standards.implementation]

# デプロイメント時の必須宣言
core_principles: [第1条, 第6条, 第7条]
related_sections: [deployment_requirements, scripts, security]
```

## ⚠️ HopLink 重要な注意事項

### 🔴 絶対遵守ルール（WordPress プラグイン固有）
- **WordPress標準準拠**: WPCS、セキュリティ、プラグイン構造
- **API制限遵守**: Amazon PA-API (1秒/リクエスト)、楽天API (1000回/日)
- **アフィリエイト法的遵守**: 利用規約、免責事項、広告表示
- **設定ファイル管理**: config.php の適切な管理、.gitignore 対象

### 🚫 禁止事項（HopLink固有）
- **API キーのハードコード**: 必ず config.php で管理
- **テスト用本番API使用**: 開発時はモック・サンプルデータ使用
- **レート制限違反**: API制限を必ず遵守
- **アフィリエイト規約違反**: 各プラットフォームの利用規約を遵守

### ✅ 品質保証（HopLink固有）
- **API接続テスト**: 管理画面の API Test 機能で確認
- **レスポンシブテスト**: PC/タブレット/スマホでの表示確認
- **セキュリティテスト**: WordPress標準のセキュリティチェック
- **パフォーマンステスト**: API レート制限下での動作確認

### 📚 参考リソース
- **WordPress**: [Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- **Amazon PA-API**: [Product Advertising API 5.0](https://webservices.amazon.com/paapi5/documentation/)
- **楽天API**: [楽天ウェブサービス](https://webservice.rakuten.co.jp/)
- **プロジェクトドキュメント**: `document/` フォルダ内各種ガイド

---

*HopLink v1.7.0 - rihobeer.com 最適化済み*
*「ビアスタイル優先度システム」「キーワード多様化」対応*