<?php
// ------------------------------------------------------------------------------
//
//    コラボ制作への招待（参加ページ）
//        swInvite.php?k=鍵
//
//    Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//    プロデューサーがコラボ制作画面で発行した招待 URL を開くと来る。ログインは不要で開ける。
//      ・既にログイン中（セッションあり） → 「このアカウントで参加」
//      ・登録済み                          → メールアドレスとパスワードでログインして参加
//      ・未登録                            → 新しくアカウントを作って参加（新規登録はこの招待経由だけ）
//    参加後はシナリオ選択へ移る（ログイン情報を POST）。
//
// ------------------------------------------------------------------------------
	include_once("./sw_config/swConstant.php");
	include_once("./include/ConnectMySQL.php");
	include_once("./include/swFunc.php");
	include_once("./include/swCollab.php");

	header('X-Robots-Tag: noindex, nofollow');
	session_name('scenario-writer-cafe');
	session_start();

	$key = isset($_REQUEST['k']) ? (string)$_REQUEST['k'] : '';
	$inv = swCollab_FindInvite($mySqlConnObj, $key);
	$action = isset($_POST['action']) ? (string)$_POST['action'] : '';
	$err = '';
	$showLogin = false;

	//ログイン中のユーザー
	$sessUserId = 0;
	$sessName = '';
	if (isset($_SESSION['swLoginId'])) {
		$sessUserId = swCollab_UserIdFromLoginId($mySqlConnObj, (string)$_SESSION['swLoginId']);
		if ($sessUserId > 0) { $u = swCollab_User($mySqlConnObj, $sessUserId); $sessName = $u['name']; }
	}

	//参加処理 → シナリオ選択へ
	function swInvite_Go($db, $key, $userId){
		list($ok, $sid, $msg) = swCollab_AcceptInvite($db, $key, $userId);
		if (!$ok) { return $msg; }
		list($loginId, $loginDate) = swCollab_Login($db, $userId);
		$l = htmlspecialchars($loginId, ENT_QUOTES, 'UTF-8');
		$d = htmlspecialchars($loginDate, ENT_QUOTES, 'UTF-8');
		$m = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
		print <<<END_OF_HTML
<!DOCTYPE html>
<html lang="ja"><head><meta charset="utf-8"><title>ScenarioWriterCafe</title></head>
<body>
<form name="frmGo" method="POST" action="./swScenarioSelect.php">
	<input type="hidden" name="fdtUserLoginId" value="{$l}">
	<input type="hidden" name="fdtUserLoginDate" value="{$d}">
</form>
<script>alert("{$m}"); document.frmGo.submit();</script>
</body></html>
END_OF_HTML;
		exit();
	}

	if ($inv && $action !== '') {
		switch ($action) {
			case 'join':
				if ($sessUserId > 0) { $err = swInvite_Go($mySqlConnObj, $key, $sessUserId); }
				else { $err = 'ログインしていません。'; $showLogin = true; }
				break;
			case 'login':
				$mail = trim(isset($_POST['mail']) ? (string)$_POST['mail'] : '');
				$pass = isset($_POST['pass']) ? (string)$_POST['pass'] : '';
				$uid = swCollab_CheckPassword($mySqlConnObj, $mail, $pass);
				if ($uid <= 0) { $err = 'メールアドレスまたはパスワードが違います。'; $showLogin = true; }
				else { $err = swInvite_Go($mySqlConnObj, $key, $uid); }
				break;
			case 'register':
				$name = trim(isset($_POST['name']) ? (string)$_POST['name'] : '');
				$mail = trim(isset($_POST['mail']) ? (string)$_POST['mail'] : '');
				$pass = isset($_POST['pass']) ? (string)$_POST['pass'] : '';
				$pass2 = isset($_POST['pass2']) ? (string)$_POST['pass2'] : '';
				if ($name === '') { $err = '名前を入力してください。'; }
				elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) { $err = 'メールアドレスの形式が正しくありません。'; }
				elseif (!preg_match('/^[0-9A-Za-z_]{4,12}$/', $pass)) { $err = 'パスワードは英数字と _ で 4〜12 文字にしてください。'; }
				elseif ($pass !== $pass2) { $err = 'パスワード（確認）が一致しません。'; }
				elseif (swCollab_FindUserByMail($mySqlConnObj, $mail) > 0) { $err = 'このメールアドレスは登録済みです。「登録済みの方」からログインしてください。'; $showLogin = true; }
				else {
					$uid = swCollab_CreateUser($mySqlConnObj, $mail, $pass, $name);
					if ($uid <= 0) { $err = 'アカウントを作れませんでした。'; }
					else { $err = swInvite_Go($mySqlConnObj, $key, $uid); }
				}
				break;
		}
	}

	$title = $inv ? swFunc_SanitizeStrings($inv['title']) : '';
	$subtitle = $inv ? swFunc_SanitizeStrings($inv['subtitle']) : '';
	$roleLabel = $inv ? swFunc_SanitizeStrings($inv['label']) : '';
	$levelLabel = $inv ? swCollab_LevelLabel($inv['role']) : '';
	$ownerName = '';
	if ($inv) { $o = swCollab_User($mySqlConnObj, $inv['ownerId']); $ownerName = swFunc_SanitizeStrings($o['name']); }
	$expire = ($inv && $inv['expire'] !== '') ? swFunc_SanitizeStrings(substr($inv['expire'], 0, 16)) : '';
	$k = swFunc_SanitizeStrings($key);
	$errHtml = swFunc_SanitizeStrings($err);
	$pMail = swFunc_SanitizeStrings(isset($_POST['mail']) ? $_POST['mail'] : '');
	$pName = swFunc_SanitizeStrings(isset($_POST['name']) ? $_POST['name'] : '');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>コラボ制作への招待 - ScenarioWriterCafe</title>
<link rel="shortcut icon" href="./img/icon/favicon.ico" type="image/x-icon">
<link rel="stylesheet" type="text/css" href="./css/scwDefault.css?v=20260921c">
<link rel="stylesheet" type="text/css" href="./css/scwUserStyle.css?v=20260918">
<style>
.iv-wrap { max-width: 560px; margin: 40px auto; padding: 0 16px; }
.iv-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px 24px; margin-bottom: 16px; }
.iv-title { font-size: 1.3em; font-weight: bold; margin: 0 0 4px; }
.iv-sub { color: #666; margin: 0 0 8px; }
.iv-meta { font-size: 13px; color: #666; }
.iv-role { display: inline-block; background: #198754; color: #fff; border-radius: 8px; padding: 3px 8px; font-size: 12px; }
.iv-role.reader { background: #6c757d; }
.iv-err { color: #dc3545; margin: 8px 0; }
.iv-toggle { cursor: pointer; color: #0d6efd; text-decoration: underline; font-size: 13px; }
</style>
</head>
<body style="background:#f5f5f5;">
<nav id="tf-menu" class="navbar navbar-default">
	<div class="container"><a class="navbar-brand" href="./swUserLogin.html"><img src="./img/logo.png" height="30"></a></div>
</nav>
<div class="iv-wrap">
<?php if (!$inv): ?>
	<div class="iv-card">
		<p class="iv-title">この招待は無効です</p>
		<p class="iv-meta">URL が正しいか、期限が切れていないか、作者に確認してください。</p>
		<p><a href="./swUserLogin.html">ログイン画面へ</a></p>
	</div>
<?php else: ?>
	<div class="iv-card">
		<p class="iv-meta">コラボ制作への招待</p>
		<p class="iv-title"><?php echo $title; ?></p>
		<?php if ($subtitle !== ''): ?><p class="iv-sub"><?php echo $subtitle; ?></p><?php endif; ?>
		<p class="iv-meta">プロデューサー: <?php echo $ownerName; ?>　役割: <span class="iv-role <?php echo $inv['role']; ?>"><?php echo $roleLabel; ?></span>（<?php echo $levelLabel; ?>）
			<?php if ($expire !== ''): ?>　有効期限: <?php echo $expire; ?><?php endif; ?></p>
		<p class="iv-meta"><?php echo ($inv['role'] === 'reader') ? 'この役割は台本を読む・ダウンロードすることができます。' : 'この役割は台詞・場面・登場人物・シノプシス・シナリオ情報を編集できます。'; ?></p>
		<?php if ($errHtml !== ''): ?><p class="iv-err"><?php echo $errHtml; ?></p><?php endif; ?>
	</div>

	<?php if ($sessUserId > 0): ?>
	<div class="iv-card">
		<form method="POST" action="./swInvite.php">
			<input type="hidden" name="k" value="<?php echo $k; ?>">
			<input type="hidden" name="action" value="join">
			<p>ログイン中: <b><?php echo swFunc_SanitizeStrings($sessName); ?></b></p>
			<button type="submit" class="btn btn-success">このアカウントで参加する</button>
			<p class="mt-2"><span class="iv-toggle" onclick="document.getElementById('ivOther').style.display='block';">別のアカウントで参加する</span></p>
		</form>
	</div>
	<div id="ivOther" style="display:<?php echo $showLogin ? 'block' : 'none'; ?>;">
	<?php else: ?>
	<div id="ivOther">
	<?php endif; ?>
		<div class="iv-card">
			<h5>登録済みの方</h5>
			<form method="POST" action="./swInvite.php">
				<input type="hidden" name="k" value="<?php echo $k; ?>">
				<input type="hidden" name="action" value="login">
				<div class="mb-2"><input type="email" class="form-control form-control-sm" name="mail" placeholder="メールアドレス" required value="<?php echo $pMail; ?>"></div>
				<div class="mb-2"><input type="password" class="form-control form-control-sm" name="pass" placeholder="パスワード" required></div>
				<button type="submit" class="btn btn-success btn-sm">ログインして参加する</button>
			</form>
		</div>
		<div class="iv-card">
			<h5>はじめての方（アカウントを作る）</h5>
			<form method="POST" action="./swInvite.php">
				<input type="hidden" name="k" value="<?php echo $k; ?>">
				<input type="hidden" name="action" value="register">
				<div class="mb-2"><input type="text" class="form-control form-control-sm" name="name" placeholder="名前（仲間に表示されます）" required value="<?php echo $pName; ?>"></div>
				<div class="mb-2"><input type="email" class="form-control form-control-sm" name="mail" placeholder="メールアドレス" required></div>
				<div class="mb-2"><input type="password" class="form-control form-control-sm" name="pass" placeholder="パスワード（英数字と _ で 4〜12 文字）" required></div>
				<div class="mb-2"><input type="password" class="form-control form-control-sm" name="pass2" placeholder="パスワード（確認）" required></div>
				<button type="submit" class="btn btn-outline-success btn-sm">アカウントを作って参加する</button>
			</form>
		</div>
	</div>
<?php endif; ?>
</div>
</body>
</html>
