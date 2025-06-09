#!/bin/bash
# HopLink Minimal プラグインのZIPファイル作成スクリプト
# config.phpを含めてZIPを作成します

# スクリプトのディレクトリに移動
cd "$(dirname "$0")"

# バージョン番号（必要に応じて変更）
VERSION="0.1.0"

# ZIPファイル名
ZIP_NAME="hoplink-minimal-v${VERSION}.zip"

# 一時ディレクトリ作成
TMP_DIR="tmp_hoplink_minimal"
rm -rf $TMP_DIR
mkdir -p $TMP_DIR/hoplink-minimal

# ファイルをコピー（config.phpを含む）
cp -r * $TMP_DIR/hoplink-minimal/ 2>/dev/null || true

# 不要なファイルを削除
cd $TMP_DIR/hoplink-minimal
rm -rf .git .gitignore .gitignore-zip create-zip.sh tmp_hoplink_minimal
rm -f *.zip

# 元のディレクトリに戻る
cd ../..

# ZIP作成
cd $TMP_DIR
zip -r ../$ZIP_NAME hoplink-minimal

# 一時ディレクトリ削除
cd ..
rm -rf $TMP_DIR

echo "✅ ZIPファイルを作成しました: $ZIP_NAME"
echo "⚠️  注意: このZIPファイルにはAPIキーが含まれています。取り扱いに注意してください。"