<?php
// ------------------------------------------------------------------------------
//
//    劇団員プレビュー（閲覧ページ）
//        swPreview.php?k=鍵
//
//    Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//    ログイン不要。脚本家がシナリオ情報画面で公開したシナリオだけ、鍵付き URL で読める。
//    スマホで読む前提。縦書き / 横書き の切替と文字サイズ変更（ブラウザに記憶）。
//
// ------------------------------------------------------------------------------
	include_once("./sw_config/swConstant.php");
	include_once("./include/ConnectMySQL.php");
	include_once("./include/swPreview.php");

	header('X-Robots-Tag: noindex, nofollow');
	$key = isset($_GET['k']) ? (string)$_GET['k'] : '';
	$scenarioId = swPreview_FindByKey($mySqlConnObj, $key);
	if ($scenarioId === 0) {
		http_response_code(404);
		$pv = null;
	} else {
		$pv = swPreview_Render($mySqlConnObj, $scenarioId);
	}
	$pageTitle = $pv ? $pv['title'] : 'プレビュー';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo $pageTitle; ?> - ScenarioWriterCafe</title>
<link rel="shortcut icon" href="./img/icon/favicon.ico" type="image/x-icon">
<link rel="icon" type="image/png" sizes="192x192" href="./img/icon/icon-192.png">
<link rel="apple-touch-icon" sizes="180x180" href="./img/icon/apple-touch-icon.png">
<style>
:root { --pv-fs: 16px; --pv-bar: 44px; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #fbfaf7; color: #222; }
body { font-family: "Hiragino Mincho ProN", "Yu Mincho", "YuMincho", "Noto Serif JP", serif; font-size: var(--pv-fs); line-height: 1.9; -webkit-text-size-adjust: 100%; }
.pv-bar { position: fixed; top: 0; left: 0; right: 0; height: var(--pv-bar); background: #fff; border-bottom: 1px solid #e3e0d8; display: flex; align-items: center; gap: 6px; padding: 0 8px; z-index: 10; font-family: -apple-system, "Hiragino Sans", "Noto Sans JP", sans-serif; font-size: 13px; }
.pv-bar .pv-bar-title { flex: 1 1 auto; min-width: 0; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; font-weight: bold; }
.pv-bar button, .pv-bar select { font-size: 13px; height: 30px; padding: 0 8px; border: 1px solid #cfcac0; border-radius: 6px; background: #fff; color: #222; }
.pv-bar button.on { background: #f7931e; border-color: #f7931e; color: #fff; }
.pv-wrap { padding-top: var(--pv-bar); }
.pv-head { padding: 16px 16px 8px; border-bottom: 1px dashed #d8d3c8; }
.pv-title { font-size: 1.4em; font-weight: bold; margin: 0; line-height: 1.5; }
.pv-subtitle { margin: 2px 0 0; color: #555; }
.pv-writer { margin: 6px 0 0; color: #555; font-size: 0.9em; }
.pv-body { padding: 8px 16px 40px; }
.pv-body h2 { font-size: 1.05em; font-weight: bold; margin: 1.6em 0 0.6em; padding-inline-start: 0; }
.pv-hashira { border-inline-start: 4px solid #f7931e; padding-inline-start: 0.5em; }
.pv-scene-no { display: inline-block; min-width: 1.6em; color: #f7931e; }
.pv-scene-desc { color: #006400; margin: 0 0 0.8em; padding-inline-start: 1em; }
.pv-cast dl { margin: 0; } .pv-cast-row { display: flex; gap: 0.6em; margin: 0 0 0.2em; } .pv-cast dt { flex: 0 0 auto; font-weight: bold; } .pv-cast dd { margin: 0; color: #555; }
.pv-synopsis p { margin: 0; }
.pv-line { margin: 0 0 0.35em; }
.pv-name { font-weight: bold; margin-inline-end: 0.6em; }
.pv-empty { color: #999; }
.pv-notfound { padding: 40px 20px; text-align: center; color: #666; }
/* 縦書き: 本文ブロックを右から左へ流し、横スクロールで読む */
body.vertical .pv-wrap { height: 100vh; overflow: hidden; }
body.vertical .pv-scroll { height: calc(100vh - var(--pv-bar)); overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; }
body.vertical .pv-doc { writing-mode: vertical-rl; text-orientation: mixed; height: 100%; padding: 20px 20px 16px 20px; width: max-content; }
body.vertical .pv-head { border-bottom: 0; border-block-end: 1px dashed #d8d3c8; padding: 0 12px 0 20px; margin: 0; }
body.vertical .pv-hashira { border-inline-start: 0; border-block-start: 4px solid #f7931e; padding-inline-start: 0; padding-block-start: 0.3em; inline-size: fit-content; }
body.vertical .pv-body h2 { margin: 0 0 0 1.2em; }
body.vertical .pv-body h2 + * { margin-inline-start: 0; }
body.vertical .pv-body { padding: 0 8px 0 16px; }
body.vertical .pv-cast-row { flex-direction: column; gap: 0; }
body.vertical .pv-line { margin: 0 0.3em 0 0; }
body.vertical .pv-title { margin-inline-end: 0.4em; }
body.vertical .pv-scene-no { display: inline; min-width: 0; }
/* 場面一覧（ジャンプ） */
.pv-index { display: none; position: fixed; top: var(--pv-bar); right: 8px; max-height: 60vh; overflow: auto; background: #fff; border: 1px solid #cfcac0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,.15); z-index: 11; min-width: 160px; font-family: -apple-system, "Hiragino Sans", "Noto Sans JP", sans-serif; font-size: 13px; }
.pv-index.open { display: block; }
.pv-index a { display: block; padding: 8px 12px; color: #222; text-decoration: none; border-bottom: 1px solid #eee; }
.pv-index a:last-child { border-bottom: 0; }
</style>
</head>
<body>
<?php if (!$pv): ?>
	<div class="pv-notfound"><p>このプレビューは公開されていません。</p><p style="font-size:12px;">URL が正しいか、脚本家に公開中かを確認してください。</p></div>
<?php else: ?>
	<div class="pv-bar">
		<button type="button" id="pvIdxBtn" title="場面一覧">場面</button>
		<span class="pv-bar-title"><?php echo $pv['title']; ?></span>
		<button type="button" id="pvSmall" title="文字を小さく">A-</button>
		<button type="button" id="pvLarge" title="文字を大きく">A+</button>
		<button type="button" id="pvMode">縦書き</button>
	</div>
	<div class="pv-index" id="pvIndex">
		<?php foreach ($pv['index'] as $ix): ?><a href="#<?php echo $ix['id']; ?>"><?php echo $ix['name']; ?></a><?php endforeach; ?>
	</div>
	<div class="pv-wrap"><div class="pv-scroll"><div class="pv-doc">
		<div class="pv-head">
			<h1 class="pv-title"><?php echo $pv['title']; ?></h1>
			<?php if ($pv['subtitle'] !== ''): ?><p class="pv-subtitle"><?php echo $pv['subtitle']; ?></p><?php endif; ?>
			<?php if ($pv['writer'] !== ''): ?><p class="pv-writer">作：<?php echo $pv['writer']; ?></p><?php endif; ?>
		</div>
		<div class="pv-body"><?php echo $pv['html']; ?></div>
	</div></div></div>
	<script>
	(function(){
		var body = document.body, modeBtn = document.getElementById('pvMode');
		var fs = 16, vertical = false;
		try { fs = parseInt(localStorage.getItem('swpv_fs') || '16', 10) || 16; vertical = localStorage.getItem('swpv_mode') === 'v'; } catch(e) {}
		function apply(){
			document.documentElement.style.setProperty('--pv-fs', fs + 'px');
			body.classList.toggle('vertical', vertical);
			modeBtn.textContent = vertical ? '横書き' : '縦書き';
			modeBtn.classList.toggle('on', vertical);
			try { localStorage.setItem('swpv_fs', fs); localStorage.setItem('swpv_mode', vertical ? 'v' : 'h'); } catch(e) {}
			if (vertical) { var sc = document.querySelector('.pv-scroll'); sc.scrollLeft = sc.scrollWidth; }
		}
		modeBtn.addEventListener('click', function(){ vertical = !vertical; apply(); });
		document.getElementById('pvSmall').addEventListener('click', function(){ fs = Math.max(12, fs - 1); apply(); });
		document.getElementById('pvLarge').addEventListener('click', function(){ fs = Math.min(28, fs + 1); apply(); });
		var idx = document.getElementById('pvIndex');
		document.getElementById('pvIdxBtn').addEventListener('click', function(e){ e.stopPropagation(); idx.classList.toggle('open'); });
		document.addEventListener('click', function(){ idx.classList.remove('open'); });
		idx.addEventListener('click', function(e){
			var a = e.target.closest('a'); if (!a) { return; }
			e.preventDefault(); idx.classList.remove('open');
			var t = document.querySelector(a.getAttribute('href')); if (!t) { return; }
			if (vertical) { t.scrollIntoView({ inline: 'end', block: 'nearest' }); }
			else { window.scrollTo({ top: t.getBoundingClientRect().top + window.pageYOffset - 52 }); }
		});
		apply();
	})();
	</script>
<?php endif; ?>
</body>
</html>
