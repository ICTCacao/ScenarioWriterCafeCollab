<?php
// ------------------------------------------------------------------------------
//    コラボ制作 I/O（コラボレーション版）
//        ajaxSwCollab.php
//    POST: fdtUserLoginId, fdtScenarioId, SubMode
//      Status       … 在席を更新し、他の人の在席と sinceId より新しい更新履歴を返す（20 秒おきのポーリング。全員）
//      以下はプロデューサー（作者）だけ
//      Members      … メンバー一覧・役割の定義・有効な招待
//      Activities   … 更新履歴（新しい順、最大 50 件）
//      AddMember    … mail, roleId     登録済みユーザーをメールアドレスで追加
//      SetRole      … userId, roleId
//      RemoveMember … userId
//      CreateInvite … roleId, days     招待 URL を発行
//      RevokeInvite … key
//      AddRole      … name, level      役割を定義（level: editor=編集できる reader=閲覧のみ）
//      DeleteRole   … roleId
//    返却 JSON: {ok, message, ...}
// ------------------------------------------------------------------------------
	include_once("../sw_config/swConstant.php");
	include_once("../include/ConnectMySQL.php");
	include_once("../include/swFunc.php");
	include_once("../include/swCollab.php");

	$SubMode = swFunc_GetPostData('SubMode');
	//権限ガード（JSON で拒否）。在席以外はプロデューサー限定
	$swCollabNeed = ($SubMode === 'Status') ? 'read' : 'owner';
	$swCollabDenyJson = true;
	include_once("../include/swCollabGuard.php");

	header('Content-Type: application/json; charset=UTF-8');
	function out($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit(); }

	$db = $mySqlConnObj;
	$scenarioId = (int)SW_COLLAB_SCENARIO_ID;
	$userId = (int)SW_COLLAB_USER_ID;
	$role = SW_COLLAB_ROLE;
	if ($scenarioId <= 0) { out(array('ok' => false, 'message' => 'シナリオが指定されていません。')); }

	//roleId → 役割（無ければエラー）
	function roleOrFail($db, $scenarioId){
		$r = swCollab_RoleById($db, $scenarioId, (int)swFunc_GetPostData('roleId'));
		if (!$r) { out(array('ok' => false, 'message' => '役割を選んでください。')); }
		return $r;
	}

	switch ($SubMode) {
		case 'Status':
			$page = swFunc_GetPostData('page');
			$sinceId = (int)swFunc_GetPostData('sinceId');
			swCollab_Touch($db, $scenarioId, $userId, $page);
			$maxId = swCollab_MaxActivityId($db, $scenarioId);
			$acts = array();
			if ($sinceId > 0 && $maxId > $sinceId) {
				foreach (swCollab_Activities($db, $scenarioId, $sinceId, 20) as $a) {
					if ($a['userId'] === $userId) { continue; }   //自分の操作は通知しない
					$acts[] = $a;
				}
			}
			out(array('ok' => true, 'role' => $role, 'presence' => swCollab_Presence($db, $scenarioId, $userId, 60), 'activities' => $acts, 'maxId' => $maxId));

		case 'Members':
			out(array('ok' => true, 'role' => $role, 'me' => $userId,
				'members' => swCollab_Members($db, $scenarioId),
				'roles' => swCollab_ScenarioRoles($db, $scenarioId),
				'invites' => swCollab_Invites($db, $scenarioId)));

		case 'Activities':
			out(array('ok' => true, 'activities' => swCollab_Activities($db, $scenarioId, 0, 50)));

		case 'AddMember':
			$mail = trim(swFunc_GetPostData('mail'));
			$r = roleOrFail($db, $scenarioId);
			if ($mail === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) { out(array('ok' => false, 'message' => 'メールアドレスの形式が正しくありません。')); }
			$uid = swCollab_FindUserByMail($db, $mail);
			if ($uid <= 0) { out(array('ok' => false, 'message' => 'このメールアドレスのユーザーは登録されていません。招待 URL を発行して渡してください。')); }
			if ($uid === $userId) { out(array('ok' => false, 'message' => '自分自身は追加できません（プロデューサーです）。')); }
			swCollab_AddMember($db, $scenarioId, $uid, $r['level'], $r['name']);
			$u = swCollab_User($db, $uid);
			swCollab_Log($db, $scenarioId, $userId, 'メンバーを追加', $u['name'] . '（' . $r['name'] . '）');
			out(array('ok' => true, 'message' => $u['name'] . ' さんを' . $r['name'] . 'として追加しました。'));

		case 'SetRole':
			$uid = (int)swFunc_GetPostData('userId');
			$r = roleOrFail($db, $scenarioId);
			if ($uid <= 0 || $uid === $userId) { out(array('ok' => false, 'message' => 'プロデューサーの役割は変えられません。')); }
			swCollab_SetRole($db, $scenarioId, $uid, $r['level'], $r['name']);
			$u = swCollab_User($db, $uid);
			swCollab_Log($db, $scenarioId, $userId, '役割を変更', $u['name'] . ' → ' . $r['name']);
			out(array('ok' => true, 'message' => '役割を変更しました。'));

		case 'RemoveMember':
			$uid = (int)swFunc_GetPostData('userId');
			if ($uid <= 0 || $uid === $userId) { out(array('ok' => false, 'message' => 'プロデューサーは外せません。')); }
			$u = swCollab_User($db, $uid);
			swCollab_RemoveMember($db, $scenarioId, $uid);
			swCollab_Log($db, $scenarioId, $userId, 'メンバーを外した', $u['name']);
			out(array('ok' => true, 'message' => $u['name'] . ' さんを外しました。'));

		case 'CreateInvite':
			$r = roleOrFail($db, $scenarioId);
			$days = (int)swFunc_GetPostData('days');
			$key = swCollab_CreateInvite($db, $scenarioId, $r['level'], $days, $userId, $r['name']);
			swCollab_Log($db, $scenarioId, $userId, '招待URLを発行', $r['name']);
			out(array('ok' => true, 'message' => '招待 URL を発行しました。相手に伝えてください。', 'url' => swCollab_InviteUrl($key), 'key' => $key));

		case 'RevokeInvite':
			$key = swFunc_GetPostData('key');
			swCollab_RevokeInvite($db, $scenarioId, $key);
			out(array('ok' => true, 'message' => '招待 URL を無効にしました。'));

		case 'AddRole':
			$name = trim(swFunc_GetPostData('name'));
			$level = swFunc_GetPostData('level');
			if ($name === '') { out(array('ok' => false, 'message' => '役割名を入力してください。')); }
			foreach (swCollab_ScenarioRoles($db, $scenarioId) as $x) { if ($x['name'] === $name) { out(array('ok' => false, 'message' => '同じ役割名があります。')); } }
			swCollab_AddRole($db, $scenarioId, $name, $level);
			out(array('ok' => true, 'message' => '役割「' . $name . '」を追加しました。'));

		case 'DeleteRole':
			$rid = (int)swFunc_GetPostData('roleId');
			$r = swCollab_RoleById($db, $scenarioId, $rid);
			if (!$r) { out(array('ok' => false, 'message' => '役割がありません。')); }
			if (count(swCollab_ScenarioRoles($db, $scenarioId)) <= 1) { out(array('ok' => false, 'message' => '役割は 1 つ以上必要です。')); }
			swCollab_DeleteRole($db, $scenarioId, $rid);
			out(array('ok' => true, 'message' => '役割「' . $r['name'] . '」を削除しました。既にその役割の人はそのままです。'));

		default:
			out(array('ok' => false, 'message' => '不明な操作です。'));
	}
?>
