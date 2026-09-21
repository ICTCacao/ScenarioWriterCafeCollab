<?php
// ------------------------------------------------------------------------------
//    初期設定 / DB接続設定（2026-09）
//        swSetup.php
//    DB接続情報を入力・接続テスト・保存する。保存先は sw_config/swDbSetting.php。
//    起動時(index.php)にDB接続できないと、この画面へ誘導される。
//    ※公開運用では、設定完了後にこの画面へのアクセスを制限すること（下部の注記）。
// ------------------------------------------------------------------------------
	include_once("./sw_config/swConstant.php");
	include_once("./include/swDbCheck.php");

	//現在の設定値（swLocalConfig/swDbSetting/既定 の反映後）
	$cur = array(
		'dbType'   => isset($dbType)   ? $dbType   : 'sqlite',
		'dbFile'   => isset($dbFile)   ? $dbFile   : '',
		'dbHost'   => isset($dbHost)   ? $dbHost   : '',
		'dbPort'   => isset($dbPort)   ? $dbPort   : '',
		'dbSocket' => isset($dbSocket) ? $dbSocket : '',
		'dbName'   => isset($dbName)   ? $dbName   : '',
		'dbUser'   => isset($dbUser)   ? $dbUser   : '',
		'dbPass'   => isset($dbPass)   ? $dbPass   : '',
	);
	$connected = swDbCheck_Current();
	function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title>ScenarioWriterCafe 初期設定</title>
	<link rel="shortcut icon" href="./img/icon/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" type="text/css" href="./css/scwDefault.css?v=20260921c">
	<link rel="stylesheet" type="text/css" href="./css/scwUserStyle.css?v=20260918">
</head>
<body>
	<nav id="tf-menu" class="navbar navbar-default fixed-top">
		<div class="container">
			<a class="navbar-brand" href="./index.php"><img src="./img/logo.png" height="30"></a>
		</div>
	</nav>

	<div class="container" style="max-width: 720px; margin-top: 90px;">
		<h3>ScenarioWriterCafe 初期設定（DB接続）</h3>
		<?php if ($connected): ?>
			<div class="alert alert-success">現在の設定でデータベースに接続できています。</div>
		<?php else: ?>
			<div class="alert alert-warning">データベースが未設定です。標準の SQLite ならこのまま、メールアドレスとパスワードを入れて「インストール」を押してください（DB サーバは不要です）。</div>
		<?php endif; ?>

		<form id="frmSetup" onsubmit="return false;">
			<div class="row mb-3">
				<label class="col-sm-4 col-form-label text-end" for="dbType">データベース種別</label>
				<div class="col-sm-8">
					<select class="form-select" id="dbType" name="dbType" onchange="swSetupToggleType();">
						<?php foreach (swDb_Types() as $k => $label): ?>
						<option value="<?php echo h($k); ?>"<?php echo ($cur['dbType'] === $k) ? ' selected' : ''; ?>><?php echo h($label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div id="sqliteFields">
			<div class="row mb-3">
				<label class="col-sm-4 col-form-label text-end" for="dbFile">SQLiteファイル</label>
				<div class="col-sm-8">
					<input type="text" class="form-control" id="dbFile" name="dbFile" value="<?php echo h($cur['dbFile']); ?>" placeholder="空欄 = sw_config/swdata.sqlite">
					<div class="form-text">DBサーバ不要。ファイル1個に保存します。空欄なら sw_config/swdata.sqlite（Web からは読めない場所）。相対パスは sw_config/ からの相対。</div>
				</div>
			</div>
			</div>
			<div id="mysqlFields">
			<div class="row mb-3">
				<label class="col-sm-4 col-form-label text-end" for="dbHost">MySQLホスト名</label>
				<div class="col-sm-8"><input type="text" class="form-control" id="dbHost" name="dbHost" value="<?php echo h($cur['dbHost']); ?>" placeholder="localhost / mysqlXXXX.xserver.jp"></div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-4 col-form-label text-end" for="dbPort">ポート（任意）</label>
				<div class="col-sm-8"><input type="text" class="form-control" id="dbPort" name="dbPort" value="<?php echo h($cur['dbPort']); ?>" placeholder="3306（既定は空欄）"></div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-4 col-form-label text-end" for="dbSocket">UNIXソケット（任意）</label>
				<div class="col-sm-8"><input type="text" class="form-control" id="dbSocket" name="dbSocket" value="<?php echo h($cur['dbSocket']); ?>" placeholder="/Applications/MAMP/tmp/mysql/mysql.sock（MAMP等）"></div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-4 col-form-label text-end" for="dbName">データベース名</label>
				<div class="col-sm-8"><input type="text" class="form-control" id="dbName" name="dbName" value="<?php echo h($cur['dbName']); ?>"></div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-4 col-form-label text-end" for="dbUser">ユーザー名</label>
				<div class="col-sm-8"><input type="text" class="form-control" id="dbUser" name="dbUser" value="<?php echo h($cur['dbUser']); ?>"></div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-4 col-form-label text-end" for="dbPass">パスワード</label>
				<div class="col-sm-8"><input type="password" class="form-control" id="dbPass" name="dbPass" value="<?php echo h($cur['dbPass']); ?>"></div>
			</div>
			</div>
			<hr>
			<p class="text-muted" style="font-size:13px;">↓ ログインユーザー（ひとりで使う前提）。インストール時に登録します。</p>
			<div class="row mb-3">
				<label class="col-sm-4 col-form-label text-end" for="adminMail">メールアドレス</label>
				<div class="col-sm-8"><input type="email" class="form-control" id="adminMail" name="adminMail" value="" placeholder="you@example.com"></div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-4 col-form-label text-end" for="adminPass">パスワード</label>
				<div class="col-sm-8"><input type="password" class="form-control" id="adminPass" name="adminPass" value="" placeholder="ログイン用パスワード"></div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-4 col-form-label text-end" for="adminPass2">パスワード（確認）</label>
				<div class="col-sm-8"><input type="password" class="form-control" id="adminPass2" name="adminPass2" value=""></div>
			</div>
			<div class="row mb-3">
				<div class="col-sm-8 offset-sm-4">
					<button type="button" class="btn btn-outline-secondary" onclick="swSetupTest();">接続テスト</button>
					<button type="button" class="btn btn-success" onclick="swSetupInstall();">インストール（テーブル作成＋ユーザー登録）</button>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-8 offset-sm-4"><div id="setupMsg"></div></div>
			</div>
		</form>

		<hr>
		<p class="text-muted" style="font-size: 12px;">
			インストール内容: <code>sw_config/scwSchema.sql</code>（MySQL）または <code>scwSchema.sqlite.sql</code>（SQLite）を実行してテーブルを作成し、入力したユーザーを登録、<br>接続情報を <code>sw_config/swDbSetting.php</code> に保存します（<code>swConstant.php</code> が読み込む）。<br>
			セキュリティ: 設定完了後は、この画面（swSetup.php）を .htaccess 等でアクセス制限するか削除してください。
		</p>
	</div>

	<script src="./js/scw.js?v=20260918"></script>
	<script src="./ajax/ajaxSetup.js?v=20260918"></script>
	<script>swSetupToggleType();</script>
</body>
</html>
