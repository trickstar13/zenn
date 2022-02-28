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
      // Publish Sidebarを無効にする
      wp.data.dispatch('core/editor').disablePublishSidebar();

      // 「公開前チェックリストの追加」設定を無効にする
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
