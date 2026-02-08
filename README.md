# VKFS Search Result Display Controller

VK Filter Search のURLクエリパラメータに基づいて、インナーコンテンツの表示/非表示を制御するWordPressブロックプラグインです。

## 概要

このプラグインは、VK Filter Search プラグインと連携して動作します。検索結果ページのURLパラメータを判定し、条件に一致した場合のみブロック内のコンテンツを表示します。

## 機能

- URLクエリパラメータに基づく条件付き表示
- 複数の条件パラメータをサポート:
  - `vkfs_post_type` - 投稿タイプ
  - `category_name` - カテゴリー名
  - `keyword` - 検索キーワード
  - `category_operator` - カテゴリー演算子（and/or）

## 使い方

1. ブロックエディタで「VKFS Search Result Controller」ブロックを追加
2. サイドバーの「Conditions」パネルで条件を設定
3. ブロック内に表示したいコンテンツを追加

設定した条件がURLパラメータと一致した場合のみ、ブロック内のコンテンツが表示されます。

## 要件

- WordPress 5.0以上
- VK Filter Search プラグイン

## デバッグ

`WP_DEBUG` が有効な場合、HTMLコメントとしてデバッグ情報が出力されます。

```php
// wp-config.php
define( 'WP_DEBUG', true );
```

## セキュリティ

- すべての `$_GET` パラメータは `sanitize_text_field()` でサニタイズされます
- 出力は `esc_html()` でエスケープされます
- デバッグ出力は `WP_DEBUG` モード時のみ有効です

## ライセンス

GPL v2 or later
