---
title: "MastodonからWordPressに記事をインポートしたい！(Google Apps Script)"
emoji: "📥"
type: "tech" # tech: 技術記事 / idea: アイデア
topics: ["Mastodon", "WordPress", "GAS"]
published: false
---

私は、自身が 2003 年から発信してきた記事の数々を、 鍵付きの WordPress に自分だけのアーカイブとして残しています。
昔、HTML で書いてきた日記や、「さるさる日記」に投稿していた内容、あるいは MovableType で書いていた内容なども、そのまま WordPress 記事として移行しています。
マメに発信しているわけでもなく、また全てを残しているわけでもないのですが、それでも 15 年分の記録がありますので、2500 記事あります。
Mastodon で過去に自分が投稿してきたトゥートに関しても、同様に WordPress にインポートしたいと思い、今回は Google Apps Script を使って実現しました。
トゥートを自身の WordPress 環境に取り込んでおけば、自分のデータとして自分で管理できますし、本文の検索も楽々できます。
もし同じようなことをしたいという方がいらっしゃいましたら、この記事が参考になれば幸いです。

## やりたいこと

- 環境構築せずに済むように、Google Apps Script を使う
- 自分の Mastodon アカウントで過去に投稿してきたトゥートを WordPress にインポートする
- トゥートの投稿日を WordPress 上の記事の投稿日とする
- トゥートの本文の頭 20 文字を、WordPress 上の記事のタイトルとする
- トゥートの本文が空の時は、WordPress 上の記事のタイトルは no title とする
- カテゴリを「Toots」にする
- トゥートに画像が添付されている場合、WordPress の本文に画像を添付する
- トゥートにハッシュタグを含む場合、WordPress のハッシュタグに変換する
- ブーストやリプライは除外する

## やらなかったこと

- 動画の添付
- その他の、自分が思いつかなかった、必要なかったこと

## 必要なもの

Mastodon からデータを取得するにあたり、アクセストークンが必要です。

### Mastodon のアクセストークンを取得する

- まずは、Mastodon のインスタンスにログインします。
- 「ユーザー設定」から「開発」を選択し、「新規アプリ」をクリックします。
- 任意のアプリケーション名を入力し、必要な権限（スコープ）を選択します。今回は「read」権限だけで OK です。
- 「送信」をクリックします。
- 作成したアプリケーションの詳細ページに移動し、「アクセストークン」をメモします

## Google Apps Script のコード

Google Apps Script にて、以下のコードの「main()」関数を実行します。
移行先の Mastodon インスタンスの URL、取得したアクセストークン、移行先の WordPress サイトの URL を設定してください。
実行時に、セキュリティの警告が出る場合は、許可してください。

```js
// メイン関数：Mastodonの投稿をWordPress形式に変換し、XMLファイルとして保存する
function main() {
  const instanceUrl = "https://mastodon.example.com"; // 移行元のMastodonインスタンスのURLを設定
  const accessToken = "your_token_here"; // 取得したアクセストークンを設定
  const siteUrl = "https://wordpress.example.com"; // 移行先のWordPressサイトのURLを設定

  // Mastodonの投稿を取得
  const posts = fetchMastodonPosts(instanceUrl, accessToken);
  // 取得した投稿をWordPress形式のXMLに変換
  const wxrContent = createWxrFile(posts, siteUrl);

  // XMLファイルを作成し、Google Driveに保存
  const blob = Utilities.newBlob(
    wxrContent,
    "application/xml",
    "mastodon_posts.xml"
  );
  const file = DriveApp.createFile(blob);
  Logger.log("File created: " + file.getUrl());
}

// Mastodonの投稿を取得する関数
function fetchMastodonPosts(instanceUrl, accessToken) {
  const headers = { Authorization: "Bearer " + accessToken };
  const options = { method: "get", headers: headers };
  // ユーザーIDを取得
  const userId = getUserId(instanceUrl, options);
  // 全ての投稿を取得
  return fetchAllPosts(instanceUrl, userId, options);
}

// ユーザーIDを取得する関数
function getUserId(instanceUrl, options) {
  const userResponse = UrlFetchApp.fetch(
    `${instanceUrl}/api/v1/accounts/verify_credentials`,
    options
  );
  return JSON.parse(userResponse.getContentText()).id;
}

// 全ての投稿を取得する関数
function fetchAllPosts(instanceUrl, userId, options) {
  let posts = [];
  let url = `${instanceUrl}/api/v1/accounts/${userId}/statuses`;
  while (url) {
    const response = UrlFetchApp.fetch(url, options);
    const data = JSON.parse(response.getContentText());
    posts = posts.concat(data);
    // 次のページのURLを取得
    url = getNextPageUrl(response);
  }
  return posts;
}

// レスポンスヘッダーから次のページのURLを取得する関数
function getNextPageUrl(response) {
  const links = response.getHeaders()["Link"];
  if (links && links.includes('rel="next"')) {
    return links.match(/<(.*)>; rel="next"/)[1];
  }
  return null;
}

// HTMLタグを除去する関数
function stripHtmlTags(str) {
  if (!str) return "";
  return str.toString().replace(/<[^>]*>/g, "");
}

// HTMLコンテンツをWordPressのブロックに変換する関数
function convertToWordPressBlocks(htmlContent) {
  return htmlContent
    .replace(
      /<p>(.*?)<\/p>/g,
      (match, content) =>
        `<!-- wp:paragraph -->\n<p>${content}</p>\n<!-- /wp:paragraph -->\n`
    )
    .replace(
      /<img src="(.*?)" alt="(.*?)" \/>/g,
      (match, src, alt) =>
        `<!-- wp:image -->\n<figure class="wp-block-image"><img src="${src}" alt="${alt}" /></figure>\n<!-- /wp:image -->\n`
    );
}

// 日付をRSS用のフォーマットに変換する関数
function formatPubDate(date) {
  const myDate = new Date(date);
  const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
  const months = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
  ];
  return `${days[myDate.getUTCDay()]}, ${myDate
    .getUTCDate()
    .toString()
    .padStart(2, "0")} ${
    months[myDate.getUTCMonth()]
  } ${myDate.getUTCFullYear()} ${myDate
    .getUTCHours()
    .toString()
    .padStart(2, "0")}:${myDate
    .getUTCMinutes()
    .toString()
    .padStart(2, "0")}:${myDate
    .getUTCSeconds()
    .toString()
    .padStart(2, "0")} +0000`;
}

// 日付をWordPress用のフォーマットに変換する関数
function formatDateToWordPress(date) {
  const myDate = new Date(date);
  return `${myDate.getFullYear()}-${String(myDate.getMonth() + 1).padStart(
    2,
    "0"
  )}-${String(myDate.getDate()).padStart(2, "0")} ${String(
    myDate.getHours()
  ).padStart(2, "0")}:${String(myDate.getMinutes()).padStart(2, "0")}:${String(
    myDate.getSeconds()
  ).padStart(2, "0")}`;
}

// WordPress eXtended RSS (WXR) ファイルを作成する関数
function createWxrFile(posts, siteUrl) {
  let xml = '<?xml version="1.0" encoding="UTF-8" ?>\n';
  xml +=
    '<rss version="2.0" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/">\n';
  xml += "<channel>\n";
  xml += "<wp:wxr_version>1.2</wp:wxr_version>\n";

  posts.forEach((post, index) => {
    xml += createWxrItem(post, index, siteUrl);
  });

  xml += "</channel>\n";
  xml += "</rss>\n";
  return xml;
}

// 個々の投稿をWXRアイテムに変換する関数
function createWxrItem(post, index, siteUrl) {
  // リプライやリブログの場合はスキップ
  if (post.in_reply_to_id !== null || post.reblog !== null) {
    return "";
  }

  let content = post.content;
  const strippedContent = stripHtmlTags(post.content);
  // タイトルを設定（警告テキストがある場合は優先）
  const title = post.spoiler_text
    ? `⚠️${post.spoiler_text}`
    : post.content
    ? strippedContent.substring(0, 20)
    : "no title";
  const postDate = formatDateToWordPress(post.created_at);
  const postPubDate = formatPubDate(post.created_at);

  // ハッシュタグを抽出
  const hashtags = extractHashtags(content);

  // コンテンツ内のハッシュタグをリンクに変換
  content = convertHashtagsToLinks(content, siteUrl);

  // 警告テキストがある場合、コンテンツの先頭に追加
  if (post.spoiler_text) {
    content = `<p>${post.spoiler_text}</p>${content}`;
  }

  // メディア添付がある場合、コンテンツに追加
  if (post.media_attachments.length > 0) {
    post.media_attachments.forEach((media) => {
      const alt = media.description ? media.description : "";
      if (media.type === "image") {
        content += `\n\n<img src="${media.url}" alt="${alt}" />`;
      }
    });
  }

  // WXRアイテムを構築
  let xmlItem = `
    <item>
      <title><![CDATA[${title}]]></title>
      <content:encoded><![CDATA[${convertToWordPressBlocks(
        content
      )}]]></content:encoded>
      <excerpt:encoded><![CDATA[]]></excerpt:encoded>
      <pubDate><![CDATA[${postPubDate}]]></pubDate>
      <dc:creator><![CDATA[dummy]]></dc:creator>
      <wp:post_id>${index + 1}</wp:post_id>
      <wp:post_date><![CDATA[${postDate}]]></wp:post_date>
      <wp:post_date_gmt><![CDATA[${postDate}]]></wp:post_date_gmt>
      <wp:post_modified><![CDATA[${postDate}]]></wp:post_modified>
      <wp:post_modified_gmt><![CDATA[${postDate}]]></wp:post_modified_gmt>
      <wp:post_type>post</wp:post_type>
      <wp:status><![CDATA[publish]]></wp:status>
      <category domain="category" nicename="toots"><![CDATA[Toots]]></category>
  `;

  // ハッシュタグをWordPressのタグとして追加
  hashtags.forEach((tag) => {
    xmlItem += `    <category domain="post_tag" nicename="${tag}"><![CDATA[${tag}]]></category>\n`;
  });

  xmlItem += "  </item>\n";

  return xmlItem;
}

// コンテンツからハッシュタグを抽出する関数
function extractHashtags(content) {
  const regex =
    /<a href="[^"]*" class="mention hashtag" rel="tag">#<span>([^<]+)<\/span><\/a>/g;
  const hashtags = [];
  let match;
  while ((match = regex.exec(content)) !== null) {
    hashtags.push(match[1]);
  }
  return hashtags;
}

// ハッシュタグをWordPress用のリンクに変換する関数
function convertHashtagsToLinks(content, siteUrl) {
  return content.replace(
    /<a href="[^"]*" class="mention hashtag" rel="tag">#<span>([^<]+)<\/span><\/a>/g,
    function (match, tag) {
      const tagUrl = `${siteUrl}/tag/${encodeURIComponent(tag)}/`;
      return `<a href="${tagUrl}" class="hashtag">#${tag}</a>`;
    }
  );
}
```

### WXR ファイル をダウンロードする

スクリプトを実行すると、Google Drive に WXR ファイルが作成されますので、ダウンロードします。

![WXR ファイルをダウンロードする](/images/mastodon-to-wordpress/logger_log.png)

## WordPress にインポートする

WordPress 管理画面の「ツール」→「インポート」から「WordPress」を選択し、WXR ファイルをインポートします。

:::message alert
本番環境にインポートする前に、必ず、テスト環境にてうまくインポートできるか動作を確認してください。
:::

上記の手順で、投稿としてインポートされます。

## 外部画像を WordPress に取り込む

そのままの状態では、画像は全て Masodon インスタンス上の画像に直接リンクされた状態になっています。
WordPress 上に画像をインポートするために、私は [Auto Upload Images プラグイン](https://wordpress.org/plugins/auto-upload-images/)を利用しました。
更新が止まっているようですが、他に同じことができるプラグインが見当たらなかったため、利用しています。

Auto Upload Images プラグインを有効化したら、「ツール」→「外部画像置き換え」を選択します。
投稿の一覧にて各投稿を一括選択し、「置き換え」をクリックすると、画像のアップロードが行われます。

## アイキャッチを設定する

上記にて画像がインポートできたら、必要に応じてアイキャッチを設定します。
[XO Featured Image Tools](https://ja.wordpress.org/plugins/xo-featured-image-tools/)は、投稿の画像からアイキャッチ画像を自動生成できるプラグインです。
プラグインをインストール＆有効化し、「ツール」→「アイキャッチ画像」を選択します。
投稿の一覧が表示されますので、「画像からアイキャッチを作成」をクリックすると、アイキャッチ画像が自動生成されます。

以上で全ての作業が完了しました。

## 感想

投稿の移行を思い立った際、それができるツールを探したのですが、なかなか条件に合うツールが見つかりませんでした。ありそうでないものですね。
開発が止まっていてうまく動かなかったり、トゥートを WordPress に表示できるものの投稿としてはインポートできなかったりしました。
なければ自分で作るしかありませんので、Perplexity に助っ人を頼みつつ、スクリプトを書いて、投稿を移行することにしました。

スクリプトの作成にあたり、一番苦労したのは、記事を移行できる最低限の WordPress eXtended RSS (WXR) ファイルを生成することでした。
どこかにあるのかもしれませんが、WXR の仕様を見つけられなかったので、WordPress から一度記事をエクスポートして、その内容を見よう見まねでスクリプトを記述していくことになりました。
とりあえず現時点では、そして自分の環境では動くものになりましたが、果たして他の環境でも動くかや、今後も動くかについてはわかりません。
