# ScenarioWriterCafe コラボレーション版 — 設計メモと進捗

2026-09-21 に「ScenarioWriterCafe のコラボレーション版を別フォルダで構築する」で着手。
単独利用版（~/Sites/XSRV_cacao2/scenariowritercafe.com/public_html）はそのまま。ここ自体がアプリのルート。

## 状態（2026-09-21）

**コラボ機能を一通り実装し、php -S + SQLite でユーザー 3 人（作者・共同執筆者→閲覧者・部外者）の権限を curl で検証済み。**
未コミット（`git status` で新規・変更ファイルを確認）。リモート未設定。

## 実装したもの

| 種類 | ファイル | 内容 |
|---|---|---|
| 新規 | `include/swCollab.php` | 役割判定・メンバー・招待・更新履歴・在席・ユーザー作成/ログイン・共有一覧。表が無ければ自動 CREATE |
| 新規 | `include/swCollabGuard.php` | `$swCollabNeed = 'read'|'edit'|'owner'` を設定して include。POST の fdtScenarioId/id と fdtUserLoginId/loginId（無ければセッション）から役割を判定し、定数 `SW_COLLAB_ROLE` `SW_COLLAB_SCENARIO_ID` `SW_COLLAB_USER_ID` を定義。更新系リクエストは履歴に記録。拒否は画面→alert+シナリオ選択へ戻す / ajax→403（`$swCollabDenyJson=true` で JSON） |
| 新規 | `ajax/ajaxSwCollab.php` / `.js` | JSON API（Status/Members/Activities/AddMember/SetRole/RemoveMember/CreateInvite/RevokeInvite）と、20 秒ポーリング（在席・通知バー）＋共同執筆画面の UI |
| 新規 | `swScenarioMember.php` | 共同執筆画面（メンバー一覧・追加・役割変更・招待 URL・最近の更新） |
| 新規 | `swInvite.php` | 公開の参加ページ `?k=鍵`。ログイン中なら「このアカウントで参加」、登録済みはログイン、未登録は新規登録（新規登録はここだけ）。参加後はシナリオ選択へ POST |
| 変更 | 全画面・ajax | fdtScenarioId を受けるものにガードを挿入（画面: swCheckAdmin の直後、ajax: ConnectMySQL の直後）。編集系=edit、View/Download/UserOption=read、ajaxSwScenarioEditInfo は DELETE/COPY=owner、ajaxSwPreview は Status=read それ以外=owner。ajaxSwUser/ajaxSwUserLoginInfo も要ログインに |
| 変更 | `ajax/ajaxSwScenarioEditInfo.php` | 共同執筆者の情報更新で USER_ID（作者）を上書きしない。DELETE 時にコラボ行を purge |
| 変更 | `ajax/ajaxSwScenarioSelect.php` / `.js` | 自分の＋共有されたシナリオ（UNION）。役割バッジ。閲覧者のカードは swScenarioView へ |
| 変更 | `include/swEditDropDownMenu.php` | 「共同執筆」を追加。閲覧者にはメニューを絞る（選択・読む・ダウンロード・共同執筆・オプション・ログアウト） |
| 変更 | `include/swMenuBar.php` | 役割バッジ・在席表示・通知バー・ポーリング開始（SW_COLLAB_SCENARIO_ID があるときだけ） |
| 変更 | `swScenarioView.php` | 閲覧者には閲覧者メニューバー。メニュー遷移用の隠しフォーム |
| 変更 | `swScenarioEditInfo.php` | 複写・削除は作者だけ（他は無効表示） |
| 変更 | `swScenarioEdit.php` | `window.swCollab_refresh` で「再読み込み」は場面表示だけ読み直す |
| 変更 | `sw_config/scwSchema.sql` / `.sqlite.sql` | SW_SCENARIO_MEMBER / SW_SCENARIO_INVITE / SW_ACTIVITY / SW_PRESENCE |
| 変更 | `css/scwDefault.css` | バッジ・通知バー・メンバー表・履歴 |
| 変更 | `README.md` / `docs/manual.md` | 共同執筆の説明（manual は 11.5 章） |

## 設計方針（着手前に決めたもの。実装は上記のとおり）

- 権限: SW_SCENARIO.USER_ID = 作者(owner)。SW_SCENARIO_MEMBER(SCENARIO_ID, USER_ID, MEMBER_ROLE editor|reader)。owner 行は持たない。
- 招待: SW_SCENARIO_INVITE(鍵32桁hex, SCENARIO_ID, ROLE, 期限, 有効) → `swInvite.php?k=鍵`。
- 同時編集: WebSocket は使わず SW_PRESENCE + SW_ACTIVITY を 20 秒ポーリング。他人の更新は自動再読込せず通知＋再読込リンク。
- 検証: homebrew php 8.5 の `php -S 127.0.0.1:8099` + SQLite。テスト用 DB は `sw_config/swdata.sqlite`（gitignore 対象）。user01@example.com / pass1234（作者）、user02@example.com / pass02。

## SQLite での同時書き込み（2026-09-21 検証）

- php -S を `PHP_CLI_SERVER_WORKERS=8` で起動し、2 ユーザー分の在席更新・場面追加・本文更新・読取を 120〜240 リクエスト同時に投げて **警告ゼロ・欠落ゼロ** を確認（`include/swDb.php` 修正後）。
- 修正 1: `swPdoStatementSqlite::execute` に "database is locked" の再試行（最大 5 回、reset してから再実行。reset しないと PDO の再 bind で SQLITE_MISUSE(21) になる）。
- 修正 2: `fetch()` `fetchColumn()` `fetchAll()` は最初の呼び出しで結果を全部読み切る。1 行だけ fetch して止めると読み取りトランザクションが開いたままになり、WAL では同じ接続の次の書き込みが busy_timeout を待たずに即 BUSY になる（在席更新の UPDATE がこれで失敗していた）。
- 注意: WAL は NFS など共有ファイルシステム上では使えない。レンタルサーバのローカルディスクなら問題なし。

## 用語と役割の変更（2026-09-21 本人指示）

- 「共同執筆」→「コラボ制作」。メニューと画面（swScenarioMember.php）は **プロデューサー限定**（ガード owner、メニューは owner のときだけ表示）。
- 「作者」→「プロデューサー」、「共同執筆者」→「ライター」。内部コード（owner/editor/reader）は変えていない。
- 役割はプロデューサーが作品ごとに自由定義: 新表 `SW_SCENARIO_ROLE`（役割名 + 権限 editor/reader）。初期値 ライター=editor、閲覧者=reader。
  メンバー・招待は役割名を持つ（`MEMBER_TITLE` / `INVITE_TITLE`。既存表には自動 ALTER）。表示は役割名、権限は「編集できる / 閲覧のみ」。
- API: Members が roles を返す。AddMember/SetRole/CreateInvite は roleId。AddRole/DeleteRole を追加。RemoveMember の自己退出は廃止。

## 残課題・気づき

- MAMP の `http://scenariowritercafecollab/` でも動作確認する（本人）。
- 「シナリオ」を「プロジェクト」と呼び替えるかを検証する（本人の判断待ち。画面の文言・メニュー・マニュアルに広く影響）。
- 役割名の並び替え・名前の変更は未実装（追加と削除だけ）。

- マニュアルのスライド版（docs/manual-slides.md）と PDF は未更新。
- 同一台詞の同時編集は後勝ち（通知で気づいてもらう運用）。行ロックは未実装。
- 閲覧者向けの `swScenarioView.php` はメニューバーを足しただけで、レイアウト（scroll_area100 の高さ）はブラウザで要確認。
- ブラウザ（実機）での画面確認はまだ。curl での機能確認のみ。
