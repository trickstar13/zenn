---
title: "「Snow Monkey Archive Content」の使用時に「公開」ボタンを無効にするスニペット"
emoji: "🙈"
type: "tech" # tech: 技術記事 / idea: アイデア
topics: [WordPress,SnowMonkey]
published: true
---

WordPress テーマ Snow Monkey の別売アドオン [Snow Monkey Archive Content](https://snow-monkey.2inc.org/product/snow-monkey-archive-content/)は、 **任意のアーカイブページの上下に、指定した固定ページの内容を表示できる便利なプラグイン** です。

設定も更新もかんたんでページをリッチにできる、制作に重宝するアドオンですが、動作の都合上、アーカイブページに割り当てる固定ページを常に **「下書き」で保存** しなければいけません。うっかり **「公開」してしまうと、アーカイブページの割り当てから外れて、表示されなくなってしまいます。**

しかしながら、ページの内容を変更したら「公開」したくなってしまうのがヒトの心理というもので、内容を更新するたびにカテゴリーページでのコンテンツ表示が無効になってしまうという運用上の使いにくさがありました😞

## 気をつけなくても良いように、カスタマイズしてみよう
「公開ボタンを押さないでください」と納品先にアナウンスするよりも、Snow Monkey Archive Contentで割り当てられている固定ページに関しては、 **公開ボタンを押せなくする方が親切** だと思いました🙈

この記事では下記の環境でカスタマイズしています。
- WordPress 5.9.1
- Snow Monkey 16.1.3
- Snow Monkey Archive Content 1.0.5
- Code Snippets 2.14.3

## カスタマイズ用のコード
:::message
このコードを有効にすると、作動時に **強制的に「公開前チェックリストの追加」オプションが無効** になります。不都合がある場合は利用しないでください。
:::
私は[Code Snippets](https://ja.wordpress.org/plugins/code-snippets/)を利用してカスタマイズしていますが、もちろん公式に推奨されている [My Snow Monkeyプラグイン](https://snow-monkey.2inc.org/2019/02/04/my-snow-monkey-plugin/)への追記でも良いと思います。

コードの作成にあたり [How to Conditionally Disable the Publish Button in the WordPress Block Editor](https://www.ibenic.com/how-to-conditionally-disable-the-publish-button-in-the-wordpress-block-editor/) と、Snow Monkey Archive Contentプラグインのコードを参考にしました。

![スクリーンショット：CodeSnippetsの設定画面](/images/wp-disable-publish-button/001.png)

```php:カテゴリーに指定されているページの公開ボタンを無効化
<?php

// Adminフッターでトリガー
add_action('admin_footer', 'disable_publish_button_if_category_archive_content');

function disable_publish_button_if_category_archive_content()
{
  global $post;

  // 投稿がなければスキップ
  if (!$post) {
    return;
  }

  // ページでなければスキップ
  if ('page' !== get_post_type($post)) {
    return;
  }

  // テーマへの変更を取得する
  $theme_mods = get_theme_mods();

  // IDのための配列を作る
  $archiveContentIdList = array();
  foreach ($theme_mods as $key => $value) {
    // Snow Monkey Archive Contentの設定を取得して、ページIDを配列に追加する
    if (preg_match('|^snow-monkey-archive-content/term/(.+?)/(\d+?)/page-id(-\d)?$|', $key)) {
      array_push($archiveContentIdList, $value);
    }
  }

  // 表示しているページのIDが配列内になければスキップ
  if (!in_array($post->ID, $archiveContentIdList)) {
    return;
  }
  ?>
  <script defer>
    wp.domReady(() => {
      // 「公開前チェックリストの追加」オプションを無効にする
      wp.data.dispatch('core/editor').disablePublishSidebar();

      // 公開ボタンを無効にする
      wp.data.dispatch('core/editor').lockPostSaving('isArchiveContent');

      // お知らせを表示する
      wp.data.dispatch('core/notices').createNotice(
              'warning',
              'このページはカテゴリーコンテンツのため、常に「下書き保存」してください',
              {
                isDismissible: true,
              }
      );
    });
  </script>
  <?php
}
```

### 完成形
コードを有効にすると、「公開」ボタンが無効化されて、「このページはカテゴリーコンテンツのため、常に「下書き保存」してください」のメッセージが表示されるようになります。
![スクリーンショット：WordPressの投稿画面](/images/wp-disable-publish-button/002.png)

## 説明
Snow Monkey Archive Contentの設定に含まれているページのIDと、表示しているページのIDを照らし合わせて、公開ボタンの表示をコントロールしています。

また、[disablePublishSidebar()](https://developer.wordpress.org/block-editor/reference-guides/data/data-core-editor/#disablepublishsidebar)オプションを無効にしないと、[lockPostSaving()](https://developer.wordpress.org/block-editor/reference-guides/data/data-core-editor/#lockpostsaving)が働きませんでしたので、無効にしています。
[createNotice()]()https://developer.wordpress.org/block-editor/how-to-guides/notices/#notices-in-the-block-editorを使ってのお知らせは表示しなくても良いとは思いますが、「なぜ公開ボタンを押せないの？」という混乱を防ぐために表示しています。ヒトは忘れる生き物だから。

## ひとりごと
ヘッドレスではないWordPressは、Snow Monkey がないとかなりキツイです（個人の感想です）。
いつもアップデートいただき、本当にありがとうございます。
