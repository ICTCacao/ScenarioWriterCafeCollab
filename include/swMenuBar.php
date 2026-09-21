<?php
// ------------------------------------------------------------------------------
//
//    画面上部の共通メニューバー（2026-09 追加）
//        swMenuBar.php
//
//    左 40% : ドロップダウンメニュー | 作品名（タイトル）
//    中 50% : その画面の操作（テキストリンク。以前のボタンを置き換え）
//    右 10% : ログインユーザー名
//
//    使い方（画面側。heredoc をいったん閉じて呼ぶ）:
//      swMenuBar_Print('./include/swEditDropDownMenu.php', 'frmSwScenario', $title,
//          array(
//            array('label'=>'シナリオ情報更新', 'onclick'=>"fncSwScenarioSubmit(frmSwScenario,'UPDATE','TRUE','シナリオ情報更新');"),
//            array('label'=>'シナリオ削除',     'onclick'=>"fncSwScenarioSubmitDelete(frmSwScenario);", 'class'=>'danger'),
//            array('label'=>'シナリオ複写',     'disabled'=>true, 'note'=>'お試しログインでは使えません'),
//          ),
//          $fdtUserName, $note);
//
// ------------------------------------------------------------------------------
function swMenuBar_Print($menuFile, $TargetForm, $title, $actions, $userName = '', $note = ''){
	//ドロップダウンメニュー（各メニューファイルは $TargetForm を参照して print する）
	ob_start();
	include($menuFile);
	$dropdown = ob_get_clean();

	//ユーザー名が渡されないときはログイン情報から引く
	if($userName === '' || $userName === null){
		$userName = swMenuBar_UserName();
	}

	$actHtml = '';
	$first = true;
	foreach((array)$actions as $a){
		if(!$first){ $actHtml .= '<span class="sw-mb-sep">|</span>'; }
		$first = false;
		$cls = 'sw-mb-action' . (isset($a['class']) && $a['class'] != '' ? ' ' . $a['class'] : '');
		$label = htmlspecialchars((string)$a['label'], ENT_QUOTES, 'UTF-8');
		if(!empty($a['disabled'])){
			$actHtml .= '<span class="' . $cls . ' disabled">' . $label . '</span>';
		}else{
			$onclick = htmlspecialchars((string)$a['onclick'], ENT_QUOTES, 'UTF-8');
			$actHtml .= '<span class="' . $cls . '" onclick="' . $onclick . '">' . $label . '</span>';
		}
		if(!empty($a['note'])){
			$actHtml .= '<span class="sw-mb-note">' . htmlspecialchars((string)$a['note'], ENT_QUOTES, 'UTF-8') . '</span>';
		}
	}
	if($note !== ''){
		$actHtml .= '<span class="sw-mb-note">' . $note . '</span>';
	}
	$userHtml = htmlspecialchars((string)$userName, ENT_QUOTES, 'UTF-8');

	//共同執筆（コラボレーション版）: 役割バッジ・在席表示・他の人の更新通知バー。シナリオに紐付く画面だけ
	$collabRole = defined('SW_COLLAB_ROLE') ? SW_COLLAB_ROLE : '';
	$collabSid = defined('SW_COLLAB_SCENARIO_ID') ? (int)SW_COLLAB_SCENARIO_ID : 0;
	$roleHtml = '';
	$collabHtml = '';
	$collabScript = '';
	if($collabSid > 0 && $collabRole !== ''){
		include_once(__DIR__ . '/swCollab.php');
		$myTitle = '';
		if ($collabRole !== 'owner' && defined('SW_COLLAB_USER_ID')) {
			foreach (swCollab_Members($GLOBALS['mySqlConnObj'], $collabSid) as $m) { if ($m['id'] === (int)SW_COLLAB_USER_ID) { $myTitle = $m['title']; break; } }
		}
		$roleHtml = '<span class="sw-role-badge ' . $collabRole . '" title="このシナリオでのあなたの役割（' . swCollab_LevelLabel($collabRole) . '）">' . htmlspecialchars(swCollab_MemberLabel($collabRole, $myTitle), ENT_QUOTES, 'UTF-8') . '</span>';
		$collabHtml = '<span class="sw-mb-presence" id="swCollabPresence"></span>';
		$loginJs = json_encode(isset($GLOBALS['fdtUserLoginId']) ? (string)$GLOBALS['fdtUserLoginId'] : '');
		$pageJs = json_encode(basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : ''));
		$formJs = json_encode((string)$TargetForm);
		$collabScript = '<div class="sw-collab-bar" id="swCollabBar"></div>'
			. '<script type="text/javascript" src="./ajax/ajaxSwCollab.js?v=20260921c"></script>'
			. '<script>swCollab_start({scenarioId: ' . $collabSid . ', loginId: ' . $loginJs . ', page: ' . $pageJs . ', form: ' . $formJs . '});</script>';
	}

	print <<<END_OF_HTML

	<div class="sw-menubar">
		<div class="sw-mb-left">
			{$dropdown}
			<span class="sw-mb-title" title="{$title}">{$title}</span>
			{$roleHtml}
		</div>
		<div class="sw-mb-actions">{$actHtml}{$collabHtml}</div>
		<div class="sw-mb-user" title="{$userHtml}">{$userHtml}</div>
	</div>
	{$collabScript}

END_OF_HTML;
}

//ログインユーザー名（fdtUserLoginId → SW_USER_LOGIN_INFO → SW_USER）
function swMenuBar_UserName(){
	if(!isset($GLOBALS['mySqlConnObj']) || !isset($GLOBALS['fdtUserLoginId'])){ return ''; }
	$db = $GLOBALS['mySqlConnObj'];
	$stmt = $db->prepare("SELECT U.USER_NAME FROM SW_USER_LOGIN_INFO L INNER JOIN SW_USER U ON U.USER_ID = L.USER_ID WHERE L.USER_LOGIN_ID = :LoginId");
	$stmt->bindValue(':LoginId', $GLOBALS['fdtUserLoginId'], PDO::PARAM_STR);
	$stmt->execute();
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	return $row ? (string)$row['USER_NAME'] : '';
}
?>
