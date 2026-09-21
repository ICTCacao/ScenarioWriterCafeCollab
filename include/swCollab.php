<?php
// ------------------------------------------------------------------------------
//
//    共同執筆（コラボレーション）共通処理
//        swCollab.php（include）
//
//    Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//    シナリオごとに権限の段階を 3 つ持つ。
//      ・owner  (プロデューサー) … SW_SCENARIO.USER_ID。削除・複写・メンバー管理・役割の定義ができる
//      ・editor (編集できる)     … SW_SCENARIO_MEMBER.MEMBER_ROLE = editor。本文・場面・登場人物・シノプシス・情報を編集できる
//      ・reader (閲覧のみ)       … SW_SCENARIO_MEMBER.MEMBER_ROLE = reader。読む・ダウンロードだけ
//    表示する「役割名」はプロデューサーが作品ごとに自由に定義する（SW_SCENARIO_ROLE: 役割名 + 権限の段階。
//    初期値は ライター=editor、閲覧者=reader）。メンバー・招待には役割名を持たせる（MEMBER_TITLE / INVITE_TITLE）。
//    招待は SW_SCENARIO_INVITE（推測できない 32 桁の鍵）。swInvite.php?k=鍵 でログインまたは新規登録して参加する。
//    更新履歴は SW_ACTIVITY、在席は SW_PRESENCE。ajax/ajaxSwCollab.php から 20 秒おきに読む（WebSocket は使わない）。
//    表が無い既存 DB には初回に自動作成する（SQLite / MySQL 両対応）。
//
// ------------------------------------------------------------------------------

// 表が無ければ作る
function swCollab_EnsureTables($db){
	static $done = false;
	if ($done) { return; }
	$done = true;
	$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
	if ($driver === 'sqlite') {
		$sqls = array(
			"CREATE TABLE IF NOT EXISTS SW_SCENARIO_MEMBER (
				SCENARIO_ID INTEGER NOT NULL,
				USER_ID INTEGER NOT NULL,
				MEMBER_ROLE VARCHAR(16) NOT NULL DEFAULT 'editor',
				MEMBER_TITLE VARCHAR(64) DEFAULT NULL,
				ADD_DATE TEXT NOT NULL DEFAULT (datetime('now','localtime')),
				PRIMARY KEY (SCENARIO_ID, USER_ID)
			)",
			"CREATE INDEX IF NOT EXISTS SW_SCENARIO_MEMBER_KEY_USER_ID ON SW_SCENARIO_MEMBER (USER_ID)",
			"CREATE TABLE IF NOT EXISTS SW_SCENARIO_INVITE (
				INVITE_KEY VARCHAR(64) NOT NULL PRIMARY KEY,
				SCENARIO_ID INTEGER NOT NULL,
				INVITE_ROLE VARCHAR(16) NOT NULL DEFAULT 'editor',
				INVITE_TITLE VARCHAR(64) DEFAULT NULL,
				INVITE_EXPIRE TEXT DEFAULT NULL,
				INVITE_VALID INTEGER NOT NULL DEFAULT 1,
				CREATE_USER_ID INTEGER DEFAULT NULL,
				CREATE_DATE TEXT NOT NULL DEFAULT (datetime('now','localtime'))
			)",
			"CREATE INDEX IF NOT EXISTS SW_SCENARIO_INVITE_KEY_SCENARIO_ID ON SW_SCENARIO_INVITE (SCENARIO_ID)",
			"CREATE TABLE IF NOT EXISTS SW_ACTIVITY (
				ACTIVITY_ID INTEGER PRIMARY KEY AUTOINCREMENT,
				SCENARIO_ID INTEGER NOT NULL,
				USER_ID INTEGER NOT NULL,
				ACTIVITY_KIND VARCHAR(64) NOT NULL,
				ACTIVITY_NOTE VARCHAR(256) DEFAULT NULL,
				ACTIVITY_DATE TEXT NOT NULL
			)",
			"CREATE INDEX IF NOT EXISTS SW_ACTIVITY_KEY_SCENARIO_ID ON SW_ACTIVITY (SCENARIO_ID, ACTIVITY_ID)",
			"CREATE TABLE IF NOT EXISTS SW_PRESENCE (
				SCENARIO_ID INTEGER NOT NULL,
				USER_ID INTEGER NOT NULL,
				PRESENCE_PAGE VARCHAR(64) DEFAULT NULL,
				PRESENCE_DATE TEXT NOT NULL,
				PRIMARY KEY (SCENARIO_ID, USER_ID)
			)",
			"CREATE TABLE IF NOT EXISTS SW_SCENARIO_ROLE (
				ROLE_ID INTEGER PRIMARY KEY AUTOINCREMENT,
				SCENARIO_ID INTEGER NOT NULL,
				ROLE_NAME VARCHAR(64) NOT NULL,
				ROLE_LEVEL VARCHAR(16) NOT NULL DEFAULT 'editor',
				ROLE_ORDER INTEGER NOT NULL DEFAULT 0
			)",
			"CREATE INDEX IF NOT EXISTS SW_SCENARIO_ROLE_KEY_SCENARIO_ID ON SW_SCENARIO_ROLE (SCENARIO_ID)",
		);
	} else {
		$sqls = array(
			"CREATE TABLE IF NOT EXISTS SW_SCENARIO_MEMBER (
				SCENARIO_ID int(11) NOT NULL COMMENT 'シナリオID',
				USER_ID int(11) NOT NULL COMMENT 'ユーザーID',
				MEMBER_ROLE varchar(16) NOT NULL DEFAULT 'editor' COMMENT '権限 editor=編集できる reader=閲覧のみ',
				MEMBER_TITLE varchar(64) DEFAULT NULL COMMENT '役割名(表示用)',
				ADD_DATE timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '追加日時',
				PRIMARY KEY (SCENARIO_ID, USER_ID),
				KEY SW_SCENARIO_MEMBER_KEY_USER_ID (USER_ID)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='共同執筆メンバー'",
			"CREATE TABLE IF NOT EXISTS SW_SCENARIO_INVITE (
				INVITE_KEY varchar(64) NOT NULL COMMENT '招待URLの鍵',
				SCENARIO_ID int(11) NOT NULL COMMENT 'シナリオID',
				INVITE_ROLE varchar(16) NOT NULL DEFAULT 'editor' COMMENT '参加後の権限',
				INVITE_TITLE varchar(64) DEFAULT NULL COMMENT '参加後の役割名',
				INVITE_EXPIRE datetime DEFAULT NULL COMMENT '有効期限(NULL=無期限)',
				INVITE_VALID int(11) NOT NULL DEFAULT 1 COMMENT '1=有効',
				CREATE_USER_ID int(11) DEFAULT NULL COMMENT '発行者',
				CREATE_DATE timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '発行日時',
				PRIMARY KEY (INVITE_KEY),
				KEY SW_SCENARIO_INVITE_KEY_SCENARIO_ID (SCENARIO_ID)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='共同執筆の招待'",
			"CREATE TABLE IF NOT EXISTS SW_ACTIVITY (
				ACTIVITY_ID int(11) NOT NULL AUTO_INCREMENT,
				SCENARIO_ID int(11) NOT NULL COMMENT 'シナリオID',
				USER_ID int(11) NOT NULL COMMENT '操作したユーザー',
				ACTIVITY_KIND varchar(64) NOT NULL COMMENT '操作の種類',
				ACTIVITY_NOTE varchar(256) DEFAULT NULL COMMENT '補足(場面名など)',
				ACTIVITY_DATE datetime NOT NULL COMMENT '日時',
				PRIMARY KEY (ACTIVITY_ID),
				KEY SW_ACTIVITY_KEY_SCENARIO_ID (SCENARIO_ID, ACTIVITY_ID)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='更新履歴'",
			"CREATE TABLE IF NOT EXISTS SW_PRESENCE (
				SCENARIO_ID int(11) NOT NULL COMMENT 'シナリオID',
				USER_ID int(11) NOT NULL COMMENT 'ユーザーID',
				PRESENCE_PAGE varchar(64) DEFAULT NULL COMMENT '開いている画面',
				PRESENCE_DATE datetime NOT NULL COMMENT '最終応答日時',
				PRIMARY KEY (SCENARIO_ID, USER_ID)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='在席'",
			"CREATE TABLE IF NOT EXISTS SW_SCENARIO_ROLE (
				ROLE_ID int(11) NOT NULL AUTO_INCREMENT,
				SCENARIO_ID int(11) NOT NULL COMMENT 'シナリオID',
				ROLE_NAME varchar(64) NOT NULL COMMENT '役割名',
				ROLE_LEVEL varchar(16) NOT NULL DEFAULT 'editor' COMMENT '権限 editor=編集できる reader=閲覧のみ',
				ROLE_ORDER int(11) NOT NULL DEFAULT 0 COMMENT '並び順',
				PRIMARY KEY (ROLE_ID),
				KEY SW_SCENARIO_ROLE_KEY_SCENARIO_ID (SCENARIO_ID)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='役割の定義'",
		);
	}
	foreach ($sqls as $sql) {
		try { $db->exec($sql); } catch (Exception $e) { error_log('swCollab_EnsureTables: ' . $e->getMessage()); }
	}
	//古い表（役割名の列が無い）に列を足す。既にあればエラーになるので黙って無視
	foreach (array("ALTER TABLE SW_SCENARIO_MEMBER ADD COLUMN MEMBER_TITLE VARCHAR(64) DEFAULT NULL", "ALTER TABLE SW_SCENARIO_INVITE ADD COLUMN INVITE_TITLE VARCHAR(64) DEFAULT NULL") as $sql) {
		try { @$db->exec($sql); } catch (Exception $e) {}
	}
}

function swCollab_Now(){ return date('Y-m-d H:i:s'); }

// ------------------------------------------------------------------------------
//   権限の段階と役割名
// ------------------------------------------------------------------------------
// 権限の段階（内部コード → 表示）
function swCollab_LevelLabel($role){
	switch ((string)$role) {
		case 'owner':  return 'プロデューサー';
		case 'editor': return '編集できる';
		case 'reader': return '閲覧のみ';
		default:       return '';
	}
}
// 権限の段階だけから決まる既定の役割名
function swCollab_RoleLabel($role){
	switch ((string)$role) {
		case 'owner':  return 'プロデューサー';
		case 'editor': return 'ライター';
		case 'reader': return '閲覧者';
		default:       return '';
	}
}
// メンバーの表示名（役割名があればそれ、無ければ段階の既定名）
function swCollab_MemberLabel($role, $title){
	if ((string)$role === 'owner') { return 'プロデューサー'; }
	$title = trim((string)$title);
	return ($title !== '') ? $title : swCollab_RoleLabel($role);
}
function swCollab_RoleLevel($role){
	switch ((string)$role) {
		case 'owner':  return 3;
		case 'editor': return 2;
		case 'reader': return 1;
		default:       return 0;
	}
}
// $need: 'owner' | 'edit' | 'read'
function swCollab_Satisfies($role, $need){
	$req = ($need === 'owner') ? 3 : (($need === 'edit') ? 2 : 1);
	return swCollab_RoleLevel($role) >= $req;
}

// ログインID（SW_USER_LOGIN_INFO の鍵）→ USER_ID。無効なら 0
function swCollab_UserIdFromLoginId($db, $loginId){
	$loginId = (string)$loginId;
	if ($loginId === '') { return 0; }
	$st = $db->prepare("SELECT USER_ID FROM SW_USER_LOGIN_INFO WHERE USER_LOGIN_ID = :l");
	if (!$st) { return 0; }   //表が無い（インストール前）ときは未ログイン扱い
	$st->bindValue(':l', $loginId, PDO::PARAM_STR);
	$st->execute();
	$row = $st->fetch(PDO::FETCH_ASSOC);
	return $row ? (int)$row['USER_ID'] : 0;
}

// シナリオの作者 USER_ID。無ければ 0
function swCollab_OwnerId($db, $scenarioId){
	$st = $db->prepare("SELECT USER_ID FROM SW_SCENARIO WHERE SCENARIO_ID = :s");
	if (!$st) { return 0; }
	$st->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
	$st->execute();
	$row = $st->fetch(PDO::FETCH_ASSOC);
	return $row ? (int)$row['USER_ID'] : 0;
}

// このユーザーのこのシナリオでの役割 'owner' | 'editor' | 'reader' | ''
function swCollab_Role($db, $scenarioId, $userId){
	$scenarioId = (int)$scenarioId; $userId = (int)$userId;
	if ($scenarioId <= 0 || $userId <= 0) { return ''; }
	if (swCollab_OwnerId($db, $scenarioId) === $userId) { return 'owner'; }
	swCollab_EnsureTables($db);
	$st = $db->prepare("SELECT MEMBER_ROLE FROM SW_SCENARIO_MEMBER WHERE SCENARIO_ID = :s AND USER_ID = :u");
	$st->bindValue(':s', $scenarioId, PDO::PARAM_INT);
	$st->bindValue(':u', $userId, PDO::PARAM_INT);
	$st->execute();
	$row = $st->fetch(PDO::FETCH_ASSOC);
	if (!$row) { return ''; }
	return ($row['MEMBER_ROLE'] === 'reader') ? 'reader' : 'editor';
}

// ------------------------------------------------------------------------------
//   ユーザー
// ------------------------------------------------------------------------------
function swCollab_User($db, $userId){
	$st = $db->prepare("SELECT USER_ID, USER_NAME, USER_MAILAD FROM SW_USER WHERE USER_ID = :u");
	$st->bindValue(':u', (int)$userId, PDO::PARAM_INT);
	$st->execute();
	$row = $st->fetch(PDO::FETCH_ASSOC);
	if (!$row) { return array('id' => 0, 'name' => '', 'mail' => ''); }
	return array('id' => (int)$row['USER_ID'], 'name' => (string)$row['USER_NAME'], 'mail' => (string)$row['USER_MAILAD']);
}
function swCollab_FindUserByMail($db, $mail){
	$mail = trim((string)$mail);
	if ($mail === '') { return 0; }
	$st = $db->prepare("SELECT USER_ID FROM SW_USER WHERE USER_MAILAD = :m");
	$st->bindValue(':m', $mail, PDO::PARAM_STR);
	$st->execute();
	$row = $st->fetch(PDO::FETCH_ASSOC);
	return $row ? (int)$row['USER_ID'] : 0;
}

// 新しいユーザーを作る（招待経由の登録。インストーラの最初のユーザー作成と同じ手順）。USER_ID を返す
function swCollab_CreateUser($db, $mail, $pass, $name){
	include_once(__DIR__ . '/swFunc.php');
	include_once(__DIR__ . '/../class/clsSwUserOption.php');
	$hash = swFunc_Hash($mail, $pass);
	$ins = $db->prepare("INSERT INTO SW_USER (USER_MAILAD, USER_PASSWD, USER_NAME, USER_MAX_SCENARIO) VALUES (:m, :p, :n, 9999)");
	$ins->bindValue(':m', $mail, PDO::PARAM_STR);
	$ins->bindValue(':p', $hash, PDO::PARAM_STR);
	$ins->bindValue(':n', $name, PDO::PARAM_STR);
	$ins->execute();
	$userId = (int)$db->lastInsertId();
	if ($userId <= 0) { return 0; }
	//オプション設定の既定行
	$chk = $db->prepare("SELECT USER_ID FROM SW_USER_OPTION_SETTING WHERE USER_ID = :u");
	$chk->bindValue(':u', $userId, PDO::PARAM_INT); $chk->execute();
	if (!$chk->fetch(PDO::FETCH_ASSOC)) {
		$s = $db->prepare("INSERT INTO SW_USER_OPTION_SETTING (USER_ID, CHARACTER_LENGTH, BODY_LENGTH, USE_KAGIKAKKO) VALUES (:u, 8, 32, 1)");
		$s->bindValue(':u', $userId, PDO::PARAM_INT); $s->execute();
	}
	//既定スタイル(USER_ID=0)を複写
	$opt = new clsSwUserOption();
	$opt->clsSwUserOptionSetDefault($db, $userId);
	return $userId;
}

// パスワード確認。合えば USER_ID、違えば 0
function swCollab_CheckPassword($db, $mail, $pass){
	include_once(__DIR__ . '/swFunc.php');
	$userId = swCollab_FindUserByMail($db, $mail);
	if ($userId <= 0) { return 0; }
	$st = $db->prepare("SELECT USER_PASSWD FROM SW_USER WHERE USER_ID = :u");
	$st->bindValue(':u', $userId, PDO::PARAM_INT);
	$st->execute();
	$row = $st->fetch(PDO::FETCH_ASSOC);
	if (!$row) { return 0; }
	return (swFunc_Hash($mail, $pass) === (string)$row['USER_PASSWD']) ? $userId : 0;
}

// ログイン情報を作ってセッションに入れる（ajax/ajaxUserLogin.php と同じ手順）。array(loginId, loginDate)
function swCollab_Login($db, $userId){
	include_once(__DIR__ . '/../class/clsSwUserLoginInfo.php');
	$li = new clsSwUserLoginInfo();
	$loginId = uniqid("", true);
	$li->clsSwUserLoginInfoSetUserLoginId($loginId);
	$li->clsSwUserLoginInfoSetUserId($userId);
	$li->clsSwUserLoginInfoSetUserLoginDate('');
	$li->clsSwUserLoginInfoDbDeleteFromUserId($db);
	$li->clsSwUserLoginInfoDbInsert($db);
	$li->clsSwUserLoginInfoInit($db, $loginId);
	$loginDate = (string)$li->clsSwUserLoginInfoGetUserLoginDate();
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_name('scenario-writer-cafe');
		session_start();
	}
	$_SESSION['swLoginId'] = $loginId;
	$_SESSION['swLoginDate'] = $loginDate;
	return array($loginId, $loginDate);
}

// ------------------------------------------------------------------------------
//   役割の定義（プロデューサーが作品ごとに自由に決める。役割名 + 権限の段階）
// ------------------------------------------------------------------------------
// 一覧。無ければ既定（ライター=editor、閲覧者=reader）を作る
function swCollab_ScenarioRoles($db, $scenarioId){
	swCollab_EnsureTables($db);
	$scenarioId = (int)$scenarioId;
	$st = $db->prepare("SELECT ROLE_ID, ROLE_NAME, ROLE_LEVEL FROM SW_SCENARIO_ROLE WHERE SCENARIO_ID = :s ORDER BY ROLE_ORDER, ROLE_ID");
	$st->bindValue(':s', $scenarioId, PDO::PARAM_INT);
	$st->execute();
	$rows = $st->fetchAll(PDO::FETCH_ASSOC);
	if (count($rows) === 0 && $scenarioId > 0) {
		swCollab_AddRole($db, $scenarioId, 'ライター', 'editor');
		swCollab_AddRole($db, $scenarioId, '閲覧者', 'reader');
		$st->execute();
		$rows = $st->fetchAll(PDO::FETCH_ASSOC);
	}
	$out = array();
	foreach ($rows as $r) {
		$out[] = array('id' => (int)$r['ROLE_ID'], 'name' => (string)$r['ROLE_NAME'], 'level' => ((string)$r['ROLE_LEVEL'] === 'reader') ? 'reader' : 'editor');
	}
	return $out;
}
function swCollab_AddRole($db, $scenarioId, $name, $level){
	swCollab_EnsureTables($db);
	$name = mb_substr(trim((string)$name), 0, 64);
	if ($name === '') { return 0; }
	$level = ($level === 'reader') ? 'reader' : 'editor';
	$o = $db->prepare("SELECT MAX(ROLE_ORDER) AS M FROM SW_SCENARIO_ROLE WHERE SCENARIO_ID = :s");
	$o->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT); $o->execute();
	$r = $o->fetch(PDO::FETCH_ASSOC);
	$order = ($r && $r['M'] !== null) ? (int)$r['M'] + 10 : 10;
	$st = $db->prepare("INSERT INTO SW_SCENARIO_ROLE (SCENARIO_ID, ROLE_NAME, ROLE_LEVEL, ROLE_ORDER) VALUES (:s, :n, :l, :o)");
	$st->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
	$st->bindValue(':n', $name, PDO::PARAM_STR);
	$st->bindValue(':l', $level, PDO::PARAM_STR);
	$st->bindValue(':o', $order, PDO::PARAM_INT);
	$st->execute();
	return (int)$db->lastInsertId();
}
function swCollab_DeleteRole($db, $scenarioId, $roleId){
	swCollab_EnsureTables($db);
	$st = $db->prepare("DELETE FROM SW_SCENARIO_ROLE WHERE SCENARIO_ID = :s AND ROLE_ID = :r");
	$st->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
	$st->bindValue(':r', (int)$roleId, PDO::PARAM_INT);
	return (bool)$st->execute();
}
// ID から役割 array(id,name,level)。無ければ null
function swCollab_RoleById($db, $scenarioId, $roleId){
	foreach (swCollab_ScenarioRoles($db, $scenarioId) as $r) {
		if ($r['id'] === (int)$roleId) { return $r; }
	}
	return null;
}

// ------------------------------------------------------------------------------
//   メンバー
// ------------------------------------------------------------------------------
// 作者を先頭に、全メンバー array(array(id,name,mail,role,date), ...)
function swCollab_Members($db, $scenarioId){
	swCollab_EnsureTables($db);
	$out = array();
	$ownerId = swCollab_OwnerId($db, $scenarioId);
	if ($ownerId > 0) {
		$u = swCollab_User($db, $ownerId);
		$out[] = array('id' => $ownerId, 'name' => $u['name'], 'mail' => $u['mail'], 'role' => 'owner', 'title' => 'プロデューサー', 'label' => 'プロデューサー', 'date' => '');
	}
	$st = $db->prepare("SELECT M.USER_ID, M.MEMBER_ROLE, M.MEMBER_TITLE, M.ADD_DATE, U.USER_NAME, U.USER_MAILAD
		FROM SW_SCENARIO_MEMBER M LEFT JOIN SW_USER U ON U.USER_ID = M.USER_ID
		WHERE M.SCENARIO_ID = :s ORDER BY M.ADD_DATE, M.USER_ID");
	$st->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
	$st->execute();
	while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
		if ((int)$r['USER_ID'] === $ownerId) { continue; }
		$lv = ($r['MEMBER_ROLE'] === 'reader') ? 'reader' : 'editor';
		$out[] = array('id' => (int)$r['USER_ID'], 'name' => (string)$r['USER_NAME'], 'mail' => (string)$r['USER_MAILAD'],
			'role' => $lv, 'title' => (string)$r['MEMBER_TITLE'], 'label' => swCollab_MemberLabel($lv, $r['MEMBER_TITLE']), 'date' => (string)$r['ADD_DATE']);
	}
	return $out;
}

// メンバーを追加（既にいれば役割を更新）。作者は追加できない。true/false
function swCollab_AddMember($db, $scenarioId, $userId, $role, $title = ''){
	swCollab_EnsureTables($db);
	$scenarioId = (int)$scenarioId; $userId = (int)$userId;
	$role = ($role === 'reader') ? 'reader' : 'editor';
	$title = mb_substr(trim((string)$title), 0, 64);
	if ($scenarioId <= 0 || $userId <= 0) { return false; }
	if (swCollab_OwnerId($db, $scenarioId) === $userId) { return false; }
	$st = $db->prepare("SELECT MEMBER_ROLE FROM SW_SCENARIO_MEMBER WHERE SCENARIO_ID = :s AND USER_ID = :u");
	$st->bindValue(':s', $scenarioId, PDO::PARAM_INT); $st->bindValue(':u', $userId, PDO::PARAM_INT); $st->execute();
	if ($st->fetch(PDO::FETCH_ASSOC)) {
		$up = $db->prepare("UPDATE SW_SCENARIO_MEMBER SET MEMBER_ROLE = :r, MEMBER_TITLE = :t WHERE SCENARIO_ID = :s AND USER_ID = :u");
	} else {
		$up = $db->prepare("INSERT INTO SW_SCENARIO_MEMBER (SCENARIO_ID, USER_ID, MEMBER_ROLE, MEMBER_TITLE, ADD_DATE) VALUES (:s, :u, :r, :t, :d)");
		$up->bindValue(':d', swCollab_Now(), PDO::PARAM_STR);
	}
	$up->bindValue(':s', $scenarioId, PDO::PARAM_INT);
	$up->bindValue(':u', $userId, PDO::PARAM_INT);
	$up->bindValue(':r', $role, PDO::PARAM_STR);
	$up->bindValue(':t', $title, PDO::PARAM_STR);
	return (bool)$up->execute();
}
function swCollab_SetRole($db, $scenarioId, $userId, $role, $title = ''){
	return swCollab_AddMember($db, $scenarioId, $userId, $role, $title);
}
function swCollab_RemoveMember($db, $scenarioId, $userId){
	swCollab_EnsureTables($db);
	$st = $db->prepare("DELETE FROM SW_SCENARIO_MEMBER WHERE SCENARIO_ID = :s AND USER_ID = :u");
	$st->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
	$st->bindValue(':u', (int)$userId, PDO::PARAM_INT);
	$st->execute();
	$p = $db->prepare("DELETE FROM SW_PRESENCE WHERE SCENARIO_ID = :s AND USER_ID = :u");
	$p->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
	$p->bindValue(':u', (int)$userId, PDO::PARAM_INT);
	$p->execute();
	return true;
}

// ------------------------------------------------------------------------------
//   招待
// ------------------------------------------------------------------------------
function swCollab_NewKey(){
	if (function_exists('random_bytes')) { return bin2hex(random_bytes(16)); }
	return bin2hex(openssl_random_pseudo_bytes(16));
}
function swCollab_InviteUrl($key){
	include_once(__DIR__ . '/swFunc.php');
	return swFunc_BaseUrl() . 'swInvite.php?k=' . $key;
}
// 招待を発行して鍵を返す。$days: 0 = 無期限
function swCollab_CreateInvite($db, $scenarioId, $role, $days, $byUserId, $title = ''){
	swCollab_EnsureTables($db);
	$key = swCollab_NewKey();
	$role = ($role === 'reader') ? 'reader' : 'editor';
	$title = mb_substr(trim((string)$title), 0, 64);
	$days = (int)$days;
	$expire = ($days > 0) ? date('Y-m-d H:i:s', time() + $days * 86400) : null;
	$st = $db->prepare("INSERT INTO SW_SCENARIO_INVITE (INVITE_KEY, SCENARIO_ID, INVITE_ROLE, INVITE_TITLE, INVITE_EXPIRE, INVITE_VALID, CREATE_USER_ID, CREATE_DATE)
		VALUES (:k, :s, :r, :t, :e, 1, :u, :d)");
	$st->bindValue(':t', $title, PDO::PARAM_STR);
	$st->bindValue(':k', $key, PDO::PARAM_STR);
	$st->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
	$st->bindValue(':r', $role, PDO::PARAM_STR);
	if ($expire === null) { $st->bindValue(':e', null, PDO::PARAM_NULL); } else { $st->bindValue(':e', $expire, PDO::PARAM_STR); }
	$st->bindValue(':u', (int)$byUserId, PDO::PARAM_INT);
	$st->bindValue(':d', swCollab_Now(), PDO::PARAM_STR);
	$st->execute();
	return $key;
}
// 有効な招待の一覧
function swCollab_Invites($db, $scenarioId){
	swCollab_EnsureTables($db);
	$st = $db->prepare("SELECT INVITE_KEY, INVITE_ROLE, INVITE_TITLE, INVITE_EXPIRE, CREATE_DATE FROM SW_SCENARIO_INVITE
		WHERE SCENARIO_ID = :s AND INVITE_VALID = 1 ORDER BY CREATE_DATE DESC");
	$st->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
	$st->execute();
	$out = array();
	$now = swCollab_Now();
	while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
		$exp = ($r['INVITE_EXPIRE'] === null) ? '' : (string)$r['INVITE_EXPIRE'];
		if ($exp !== '' && $exp < $now) { continue; }
		$lv = ((string)$r['INVITE_ROLE'] === 'reader') ? 'reader' : 'editor';
		$out[] = array('key' => (string)$r['INVITE_KEY'], 'role' => $lv, 'title' => (string)$r['INVITE_TITLE'], 'label' => swCollab_MemberLabel($lv, $r['INVITE_TITLE']),
			'expire' => $exp, 'date' => (string)$r['CREATE_DATE'], 'url' => swCollab_InviteUrl($r['INVITE_KEY']));
	}
	return $out;
}
function swCollab_RevokeInvite($db, $scenarioId, $key){
	swCollab_EnsureTables($db);
	$st = $db->prepare("UPDATE SW_SCENARIO_INVITE SET INVITE_VALID = 0 WHERE SCENARIO_ID = :s AND INVITE_KEY = :k");
	$st->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
	$st->bindValue(':k', (string)$key, PDO::PARAM_STR);
	return (bool)$st->execute();
}
// 鍵から有効な招待を返す。無効なら null
function swCollab_FindInvite($db, $key){
	if (!preg_match('/^[0-9a-f]{32}$/', (string)$key)) { return null; }
	swCollab_EnsureTables($db);
	$st = $db->prepare("SELECT I.*, S.SCENARIO_TITLE, S.SCENARIO_SUBTITLE, S.USER_ID AS OWNER_ID
		FROM SW_SCENARIO_INVITE I INNER JOIN SW_SCENARIO S ON S.SCENARIO_ID = I.SCENARIO_ID
		WHERE I.INVITE_KEY = :k AND I.INVITE_VALID = 1");
	$st->bindValue(':k', $key, PDO::PARAM_STR);
	$st->execute();
	$r = $st->fetch(PDO::FETCH_ASSOC);
	if (!$r) { return null; }
	if ($r['INVITE_EXPIRE'] !== null && (string)$r['INVITE_EXPIRE'] !== '' && (string)$r['INVITE_EXPIRE'] < swCollab_Now()) { return null; }
	return array(
		'key' => (string)$r['INVITE_KEY'], 'scenarioId' => (int)$r['SCENARIO_ID'],
		'role' => ((string)$r['INVITE_ROLE'] === 'reader') ? 'reader' : 'editor',
		'roleTitle' => isset($r['INVITE_TITLE']) ? (string)$r['INVITE_TITLE'] : '',   //役割名（'title' は作品名）
		'label' => swCollab_MemberLabel(((string)$r['INVITE_ROLE'] === 'reader') ? 'reader' : 'editor', isset($r['INVITE_TITLE']) ? $r['INVITE_TITLE'] : ''),
		'expire' => ($r['INVITE_EXPIRE'] === null) ? '' : (string)$r['INVITE_EXPIRE'],
		'title' => (string)$r['SCENARIO_TITLE'], 'subtitle' => (string)$r['SCENARIO_SUBTITLE'],
		'ownerId' => (int)$r['OWNER_ID'], 'byUserId' => (int)$r['CREATE_USER_ID'],
	);
}
// 招待を受けて参加する。array(ok, scenarioId, message)
//   既に上位の役割なら何もしない（作者が自分の招待を開いても作者のまま）。招待は使い回せる（複数人に同じ URL を配れる）。
function swCollab_AcceptInvite($db, $key, $userId){
	$inv = swCollab_FindInvite($db, $key);
	if (!$inv) { return array(false, 0, 'この招待は無効か、期限が切れています。'); }
	$cur = swCollab_Role($db, $inv['scenarioId'], $userId);
	if (swCollab_RoleLevel($cur) >= swCollab_RoleLevel($inv['role'])) {
		return array(true, $inv['scenarioId'], '既に参加しています（' . swCollab_LevelLabel($cur) . '）。');
	}
	swCollab_AddMember($db, $inv['scenarioId'], $userId, $inv['role'], $inv['roleTitle']);
	swCollab_Log($db, $inv['scenarioId'], $userId, '参加', $inv['label'] . 'として参加');
	return array(true, $inv['scenarioId'], $inv['label'] . 'として参加しました。');
}

// ------------------------------------------------------------------------------
//   更新履歴
// ------------------------------------------------------------------------------
function swCollab_Log($db, $scenarioId, $userId, $kind, $note = ''){
	swCollab_EnsureTables($db);
	$scenarioId = (int)$scenarioId; $userId = (int)$userId;
	if ($scenarioId <= 0 || $userId <= 0 || (string)$kind === '') { return; }
	$st = $db->prepare("INSERT INTO SW_ACTIVITY (SCENARIO_ID, USER_ID, ACTIVITY_KIND, ACTIVITY_NOTE, ACTIVITY_DATE) VALUES (:s, :u, :k, :n, :d)");
	$st->bindValue(':s', $scenarioId, PDO::PARAM_INT);
	$st->bindValue(':u', $userId, PDO::PARAM_INT);
	$st->bindValue(':k', mb_substr((string)$kind, 0, 64), PDO::PARAM_STR);
	$st->bindValue(':n', mb_substr((string)$note, 0, 256), PDO::PARAM_STR);
	$st->bindValue(':d', swCollab_Now(), PDO::PARAM_STR);
	$st->execute();
	//古い履歴は捨てる（30 日）
	if (mt_rand(1, 20) === 1) {
		$del = $db->prepare("DELETE FROM SW_ACTIVITY WHERE SCENARIO_ID = :s AND ACTIVITY_DATE < :d");
		$del->bindValue(':s', $scenarioId, PDO::PARAM_INT);
		$del->bindValue(':d', date('Y-m-d H:i:s', time() - 30 * 86400), PDO::PARAM_STR);
		$del->execute();
	}
}

// 今のリクエスト（SubMode / SubmitMode / 画面名）が更新操作なら履歴に残す。更新でなければ何もしない
function swCollab_LogRequest($db, $scenarioId, $userId){
	$script = basename(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '');
	$sub = isset($_POST['SubMode']) ? (string)$_POST['SubMode'] : '';
	$submit = isset($_POST['SubmitMode']) ? (string)$_POST['SubmitMode'] : '';
	$kind = '';
	$crud = array('INSERT' => '追加', 'MAKE_INSERT' => 'まとめて追加', 'UPDATE' => '更新', 'DELETE' => '削除');
	switch ($script) {
		case 'ajaxSwScenarioEdit.php':
			switch ($sub) {
				case 'InlineUpdate': $kind = '本文を編集'; break;
				case 'ReorderLines': $kind = '行を並べ替え'; break;
				case 'MoveScenarioLinesBefore': case 'MoveScenarioLinesAfter': $kind = '行を移動'; break;
				case 'Replace': $kind = '文字列を置換'; break;
				case 'SwitchEditForm': if (isset($crud[$submit])) { $kind = '行を' . $crud[$submit]; } break;
			}
			break;
		case 'ajaxSwScenarioEditScene.php':
			if ($sub === 'SceneEditSubmit' && isset($crud[$submit])) { $kind = '場面を' . $crud[$submit]; }
			if ($sub === 'MoveSceneBefore' || $sub === 'MoveSceneAfter') { $kind = '場面を移動'; }
			break;
		case 'ajaxSwScenarioEditCharacter.php':
			if ($sub === 'CharacterEditSubmit' && isset($crud[$submit])) { $kind = '登場人物を' . $crud[$submit]; }
			if ($sub === 'MoveCharacterBefore' || $sub === 'MoveCharacterAfter') { $kind = '登場人物を移動'; }
			break;
		case 'ajaxSwScenarioEditInfo.php':
			if ($submit === 'UPDATE') { $kind = 'シナリオ情報を更新'; }
			if ($submit === 'COPY') { $kind = 'シナリオを複写'; }
			break;
		case 'swScenarioEditSynopsis.php':
			if ($submit === 'UPDATE') { $kind = 'シノプシスを保存'; }
			break;
		case 'ajaxSwPreview.php':
			if ($sub === 'Enable') { $kind = 'プレビューを公開'; }
			if ($sub === 'Disable') { $kind = 'プレビューを停止'; }
			if ($sub === 'Regenerate') { $kind = 'プレビューURLを作り直し'; }
			break;
	}
	if ($kind === '') { return; }
	//場面名を補足に
	$note = '';
	$sceneId = isset($_POST['fdtSceneId']) ? (int)$_POST['fdtSceneId'] : 0;
	if ($sceneId > 0 && $script === 'ajaxSwScenarioEdit.php') {
		$st = $db->prepare("SELECT SCENE_NAME FROM SW_SCENE WHERE SCENE_ID = :i");
		$st->bindValue(':i', $sceneId, PDO::PARAM_INT); $st->execute();
		$r = $st->fetch(PDO::FETCH_ASSOC);
		if ($r) { $note = '場面: ' . (string)$r['SCENE_NAME']; }
	}
	if ($script === 'ajaxSwScenarioEditScene.php' && isset($_POST['fdtSceneName']) && (string)$_POST['fdtSceneName'] !== '') {
		$note = '場面: ' . (string)$_POST['fdtSceneName'];
	}
	if ($script === 'ajaxSwScenarioEditCharacter.php' && isset($_POST['fdtCharacterName']) && (string)$_POST['fdtCharacterName'] !== '') {
		$note = (string)$_POST['fdtCharacterName'];
	}
	swCollab_Log($db, $scenarioId, $userId, $kind, $note);
}

// 履歴（新しい順）。$sinceId > 0 ならそれより新しいものだけ
function swCollab_Activities($db, $scenarioId, $sinceId = 0, $limit = 30){
	swCollab_EnsureTables($db);
	$sql = "SELECT A.ACTIVITY_ID, A.USER_ID, A.ACTIVITY_KIND, A.ACTIVITY_NOTE, A.ACTIVITY_DATE, U.USER_NAME
		FROM SW_ACTIVITY A LEFT JOIN SW_USER U ON U.USER_ID = A.USER_ID
		WHERE A.SCENARIO_ID = :s" . ((int)$sinceId > 0 ? " AND A.ACTIVITY_ID > :i" : "") . "
		ORDER BY A.ACTIVITY_ID DESC LIMIT " . (int)$limit;
	$st = $db->prepare($sql);
	$st->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
	if ((int)$sinceId > 0) { $st->bindValue(':i', (int)$sinceId, PDO::PARAM_INT); }
	$st->execute();
	$out = array();
	while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
		$out[] = array('id' => (int)$r['ACTIVITY_ID'], 'userId' => (int)$r['USER_ID'], 'user' => (string)$r['USER_NAME'],
			'kind' => (string)$r['ACTIVITY_KIND'], 'note' => (string)$r['ACTIVITY_NOTE'], 'date' => (string)$r['ACTIVITY_DATE']);
	}
	return $out;
}
function swCollab_MaxActivityId($db, $scenarioId){
	swCollab_EnsureTables($db);
	$st = $db->prepare("SELECT MAX(ACTIVITY_ID) AS M FROM SW_ACTIVITY WHERE SCENARIO_ID = :s");
	$st->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
	$st->execute();
	$r = $st->fetch(PDO::FETCH_ASSOC);
	return ($r && $r['M'] !== null) ? (int)$r['M'] : 0;
}

// ------------------------------------------------------------------------------
//   在席
// ------------------------------------------------------------------------------
function swCollab_Touch($db, $scenarioId, $userId, $page){
	swCollab_EnsureTables($db);
	$scenarioId = (int)$scenarioId; $userId = (int)$userId;
	if ($scenarioId <= 0 || $userId <= 0) { return; }
	$page = mb_substr((string)$page, 0, 64);
	$st = $db->prepare("SELECT USER_ID FROM SW_PRESENCE WHERE SCENARIO_ID = :s AND USER_ID = :u");
	$st->bindValue(':s', $scenarioId, PDO::PARAM_INT); $st->bindValue(':u', $userId, PDO::PARAM_INT); $st->execute();
	if ($st->fetch(PDO::FETCH_ASSOC)) {
		$up = $db->prepare("UPDATE SW_PRESENCE SET PRESENCE_PAGE = :p, PRESENCE_DATE = :d WHERE SCENARIO_ID = :s AND USER_ID = :u");
	} else {
		$up = $db->prepare("INSERT INTO SW_PRESENCE (SCENARIO_ID, USER_ID, PRESENCE_PAGE, PRESENCE_DATE) VALUES (:s, :u, :p, :d)");
	}
	$up->bindValue(':s', $scenarioId, PDO::PARAM_INT);
	$up->bindValue(':u', $userId, PDO::PARAM_INT);
	$up->bindValue(':p', $page, PDO::PARAM_STR);
	$up->bindValue(':d', swCollab_Now(), PDO::PARAM_STR);
	$up->execute();
}
// 直近 $withinSec 秒以内に応答があった人（自分を除く）
function swCollab_Presence($db, $scenarioId, $excludeUserId = 0, $withinSec = 60){
	swCollab_EnsureTables($db);
	$st = $db->prepare("SELECT P.USER_ID, P.PRESENCE_PAGE, P.PRESENCE_DATE, U.USER_NAME
		FROM SW_PRESENCE P LEFT JOIN SW_USER U ON U.USER_ID = P.USER_ID
		WHERE P.SCENARIO_ID = :s AND P.PRESENCE_DATE >= :d ORDER BY U.USER_NAME");
	$st->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
	$st->bindValue(':d', date('Y-m-d H:i:s', time() - (int)$withinSec), PDO::PARAM_STR);
	$st->execute();
	$out = array();
	while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
		if ((int)$r['USER_ID'] === (int)$excludeUserId) { continue; }
		$out[] = array('userId' => (int)$r['USER_ID'], 'name' => (string)$r['USER_NAME'], 'page' => (string)$r['PRESENCE_PAGE'], 'date' => (string)$r['PRESENCE_DATE']);
	}
	return $out;
}

// シナリオ削除時にコラボ関連の行を消す
function swCollab_PurgeScenario($db, $scenarioId){
	swCollab_EnsureTables($db);
	foreach (array('SW_SCENARIO_MEMBER', 'SW_SCENARIO_INVITE', 'SW_ACTIVITY', 'SW_PRESENCE', 'SW_SCENARIO_ROLE') as $t) {
		$st = $db->prepare("DELETE FROM $t WHERE SCENARIO_ID = :s");
		$st->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
		$st->execute();
	}
}

// 自分のシナリオ＋共有されたシナリオ。各行に MEMBER_ROLE（owner/editor/reader）を付けて更新日の新しい順
function swCollab_ScenarioList($db, $userId){
	swCollab_EnsureTables($db);
	$sql = "SELECT S.*, 'owner' AS MEMBER_ROLE, '' AS MEMBER_TITLE FROM SW_SCENARIO S WHERE S.USER_ID = :u1
		UNION ALL
		SELECT S.*, M.MEMBER_ROLE AS MEMBER_ROLE, M.MEMBER_TITLE AS MEMBER_TITLE FROM SW_SCENARIO S
			INNER JOIN SW_SCENARIO_MEMBER M ON M.SCENARIO_ID = S.SCENARIO_ID
			WHERE M.USER_ID = :u2 AND S.USER_ID <> :u3
		ORDER BY SCENARIO_DATE DESC";
	$st = $db->prepare($sql);
	$st->bindValue(':u1', (int)$userId, PDO::PARAM_INT);
	$st->bindValue(':u2', (int)$userId, PDO::PARAM_INT);
	$st->bindValue(':u3', (int)$userId, PDO::PARAM_INT);
	$st->execute();
	return $st->fetchAll(PDO::FETCH_ASSOC);
}
?>
