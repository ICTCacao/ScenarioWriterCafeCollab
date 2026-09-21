<?php
// ------------------------------------------------------------------------------
//
//    共同執筆の権限ガード
//        swCollabGuard.php（include）
//
//    Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//    fdtScenarioId を受け取る画面・ajax の先頭（DB 接続の後）で include する。
//      使い方:  $swCollabNeed = 'edit';   // 'read' | 'edit' | 'owner'（省略時 read）
//               include_once("./include/swCollabGuard.php");
//    入力は $_POST から直接読む（fdtScenarioId または id、fdtUserLoginId または loginId、無ければセッション）。
//      ・ログインが無効           → 拒否
//      ・シナリオID あり & 権限不足 → 拒否
//      ・シナリオID なし           → 役割 '' のまま通す（swUserOption.php 等、シナリオに紐付かない画面）
//    通ったら定数 SW_COLLAB_ROLE / SW_COLLAB_SCENARIO_ID / SW_COLLAB_USER_ID を定義し、
//    更新系のリクエストなら更新履歴（SW_ACTIVITY）に残す。
//    拒否の出力: 画面 → ダイアログを出してシナリオ選択へ戻す。ajax → 403 のテキスト（$swCollabDenyJson=true なら JSON）。
//
// ------------------------------------------------------------------------------
	include_once(__DIR__ . '/swCollab.php');

	if (!isset($swCollabNeed) || !in_array($swCollabNeed, array('read', 'edit', 'owner'), true)) { $swCollabNeed = 'read'; }
	if (!isset($swCollabDenyJson)) { $swCollabDenyJson = false; }
	$swCollabIsAjax = (basename(dirname(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '')) === 'ajax');

	//シナリオID
	$swCollabScenarioId = 0;
	if (isset($_POST['fdtScenarioId']) && (string)$_POST['fdtScenarioId'] !== '') { $swCollabScenarioId = (int)str_replace(',', '', (string)$_POST['fdtScenarioId']); }
	elseif (isset($_POST['id']) && (string)$_POST['id'] !== '') { $swCollabScenarioId = (int)$_POST['id']; }

	//ログインID → USER_ID
	$swCollabLoginId = '';
	if (isset($_POST['fdtUserLoginId']) && (string)$_POST['fdtUserLoginId'] !== '') { $swCollabLoginId = (string)$_POST['fdtUserLoginId']; }
	elseif (isset($_POST['loginId']) && (string)$_POST['loginId'] !== '') { $swCollabLoginId = (string)$_POST['loginId']; }
	elseif (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['swLoginId'])) { $swCollabLoginId = (string)$_SESSION['swLoginId']; }
	elseif (isset($fdtUserLoginId) && (string)$fdtUserLoginId !== '') { $swCollabLoginId = (string)$fdtUserLoginId; }
	$swCollabUserId = swCollab_UserIdFromLoginId($mySqlConnObj, $swCollabLoginId);

	$swCollabRole = '';
	$swCollabDenied = '';
	if ($swCollabUserId <= 0) {
		$swCollabDenied = 'ログインしてください。';
	} elseif ($swCollabScenarioId > 0) {
		$swCollabRole = swCollab_Role($mySqlConnObj, $swCollabScenarioId, $swCollabUserId);
		if ($swCollabRole === '') {
			$swCollabDenied = 'このシナリオは共有されていません。';
		} elseif (!swCollab_Satisfies($swCollabRole, $swCollabNeed)) {
			$swCollabDenied = ($swCollabNeed === 'owner') ? 'この操作はプロデューサーだけができます。' : '閲覧者は編集できません。';
		}
	}

	if ($swCollabDenied !== '') {
		swCollabGuard_Deny($swCollabDenied, $swCollabIsAjax, $swCollabDenyJson, $swCollabLoginId);
		exit();
	}

	if (!defined('SW_COLLAB_ROLE')) { define('SW_COLLAB_ROLE', $swCollabRole); }
	if (!defined('SW_COLLAB_SCENARIO_ID')) { define('SW_COLLAB_SCENARIO_ID', $swCollabScenarioId); }
	if (!defined('SW_COLLAB_USER_ID')) { define('SW_COLLAB_USER_ID', $swCollabUserId); }

	//更新系のリクエストは履歴に残す
	if ($swCollabScenarioId > 0 && $swCollabNeed !== 'read') {
		swCollab_LogRequest($mySqlConnObj, $swCollabScenarioId, $swCollabUserId);
	}

// 拒否の出力
function swCollabGuard_Deny($msg, $isAjax, $json, $loginId){
	if ($isAjax) {
		http_response_code(403);
		if ($json) {
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('ok' => false, 'valid' => 0, 'url' => '', 'message' => $msg), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		} else {
			header('Content-Type: text/plain; charset=UTF-8');
			echo $msg;
		}
		return;
	}
	$m = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
	$l = htmlspecialchars($loginId, ENT_QUOTES, 'UTF-8');
	$d = date('Y-m-d H:i:s');
	print <<<END_OF_HTML
<!DOCTYPE html>
<html lang="ja"><head><meta charset="utf-8"><title>ScenarioWriterCafe</title></head>
<body>
<form name="frmBack" method="POST" action="./swScenarioSelect.php">
	<input type="hidden" name="fdtUserLoginId" value="{$l}">
	<input type="hidden" name="fdtUserLoginDate" value="{$d}">
</form>
<script>
	alert("{$m}");
	if ("{$l}" === "") { document.location.replace("./swUserLogin.html"); } else { document.frmBack.submit(); }
</script>
</body></html>
END_OF_HTML;
}
?>
