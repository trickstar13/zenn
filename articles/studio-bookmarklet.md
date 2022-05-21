---
title: "いま開いているページをSTUDIOで即編集するブックマークレットを作った"
emoji: "🔖"
type: "tech" # tech: 技術記事 / idea: アイデア
topics: [STUDIO,ブックマークレット]
published: true
---
Chromeで開いているSTUDIO製のページから、ワンクリックで編集画面に行けるブックマークレットを作りました。 デザインエディタで作られているページなら、すぐに編集画面に飛べます。階層が深いサイトを制作している場合に特に便利です。
（CMSの動的ページには非対応です）

## 何ができるのか
https://youtu.be/nPRuLPfmmQw
※無音動画

通常であれば、ページを編集したければ「STUDIOにログイン→プロジェクトを選ぶ→ページを選んで開く」の３ステップが必要ですが、このブックマークレットを使えば、**いきなり「ページを開く」までショートカット**できます！
※事前にSTUDIOにログインしていなかった場合はログイン画面が開きます。


## 制約
1ブックマーク＝1プロジェクトに対応しています。
複数のプロジェクト（サイト）を制作している場合は複数ブックマークを作る必要があります。

## 使い方

### プロジェクトIDを確認する
STUDIOを開き、編集したいプロジェクトのIDを確認します。
`https://app.studio.design/projects/プロジェクトID/dashboard/home`

![スクリーンショット](/images/studio-bookmarklet/studio-bookmarklet-01.png)

### スクリプトにIDをコピペする
下記のスクリプトをテキストエディタにコピペします。
コードの中の **「プロジェクトID」を先ほど確認したプロジェクトIDに書き換え**ます。
```javascript
javascript:!function(){var o=location.pathname,e=encodeURIComponent(o.slice(1));open("https://app.studio.design/projects/' + 'プロジェクトID' + '/editor/"+e,"_blank")}();void(0);
```

### ブックマークを追加する
Chromeのブックマークバーを右クリックして「Add Page（ページを追加）」をクリックします。
![スクリーンショット](/images/studio-bookmarklet/studio-bookmarklet-02.png =400x)

名前に「STUDIOで編集」、URLに先ほど書き換えたコードをコピペして「Save（保存）」します。
![スクリーンショット](/images/studio-bookmarklet/studio-bookmarklet-03.png =400x)

以上で完成です！🙌

## スクリプトの中身について
全く大したことはしていません。
現在開いているページのURLを取得し、STUDIOでデザインエディタを使用して編集している際に表示されるURLを模して、新しいウィンドウで開いています。

```javascript
(function() {
  // プロジェクトIDを設定
  var projectId = '●プロジェクトID●';
  var pathname = location.pathname;
  var targetPath = encodeURIComponent(pathname.slice(1));
  var target = 'https://app.studio.design/projects/' + projectId + '/editor/' + targetPath;
  open(target, '_blank');
})();
```

