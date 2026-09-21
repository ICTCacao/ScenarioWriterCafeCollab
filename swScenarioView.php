<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
// 
//     シナリオを読む
//
//     swScenarioView.php
// ------------------------------------------------------------------------------
	//定数読み込み
	include_once("./sw_config/swConstant.php");
	//DB接続ｸﾗｽの初期化
	include_once("./include/ConnectMySQL.php");
// ------------------------------------------------------------------------------
	//ｾｯｼｮﾝ管理
	include_once("./include/swAccept.php");
	//管理者チェック
	include_once("./include/swCheckAdmin.php");
	//共同執筆: 権限ガード（閲覧）
	$swCollabNeed = 'read';
	include_once("./include/swCollabGuard.php");
// ------------------------------------------------------------------------------
	//ﾃﾞﾌｫﾙﾄｱｸｼｮﾝ
	$ThisPHP = 'swScenarioEdit.php';
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("./class/clsSwScenario.php");
	$clsSwScenario = new clsSwScenario();
	//共通関数をｲﾝｸﾙｰﾄﾞ
	include_once("./include/swFunc.php");
	

	//ﾌｫｰﾑのﾃﾞｰﾀを読み込む
	fncGetPostItems($mySqlConnObj);

	// ﾍｯﾀﾞ表示
	$HtmlTitle = $fdtScenarioTitle;
	include_once("./include/swHeader.php");


	//MainProcedure
	fncMainProc($mySqlConnObj);

	exit();

// ------------------------------------------------------------------------------
//      POSTされた要素を取得
//          fncGetPostItems($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncGetPostItems($mySqlConnObj){
	//共通global変数
	global	$fdtUserLoginId,$fdtUserLoginDate;
	global	$fdtScenarioId,$fdtUserName,$fdtScenarioTitle;

	//POSTされた要素を取得
	$fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
	$fdtUserLoginDate = swFunc_GetPostData('fdtUserLoginDate');      //USERログイン日時
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');      //シナリオID

	//ﾃﾞｰﾀ管理ｸﾗｽからｼﾅﾘｵ情報を取得
	include_once("./class/clsSwScenario.php");
	$clsSwScenario = new clsSwScenario();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScenario->clsSwScenarioInit($mySqlConnObj,$fdtScenarioId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtScenarioTitle = $clsSwScenario->clsSwScenarioGetScenarioTitle();      //タイトル
	//ｻﾆﾀｲｽﾞ
	$fdtScenarioTitle = swFunc_SanitizeStrings($fdtScenarioTitle);

}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	//共通global変数
	global	$fdtUserLoginId,$fdtUserLoginDate;
	global	$fdtScenarioId;

	//ﾌｫｰﾑの表示
	fncMainForm($mySqlConnObj);

}//end function

// ------------------------------------------------------------------------------
//      MAIN FORM
//          fncMainForm($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainForm($mySqlConnObj){
	//共通global変数
	global	$fdtUserLoginId,$fdtUserLoginDate;
	global	$fdtScenarioId,$fdtUserId;


  	//共同執筆: 閲覧者はこの画面が入口なので、メニューバー（閲覧者メニュー）を出す。メニュー遷移用の隠しフォームは全員分
	$swRole = defined('SW_COLLAB_ROLE') ? SW_COLLAB_ROLE : '';
	if($swRole === 'reader'){
		include_once("./include/swMenuBar.php");
		print '<div class="scenarioedit">';
		swMenuBar_Print('./include/swEditDropDownMenu.php', 'frmSwScenario', swFunc_SanitizeStrings($GLOBALS['fdtScenarioTitle']), array(), '', '閲覧のみ（編集はできません）');
		print '</div>';
	}
	$fdtScenarioIdHtml = swFunc_SanitizeStrings($fdtScenarioId);
	$fdtUserLoginIdHtml = swFunc_SanitizeStrings($fdtUserLoginId);
	$fdtUserLoginDateHtml = swFunc_SanitizeStrings($fdtUserLoginDate);

  	$retHtml =<<<END_OF_HTML

	<form name="frmSwScenario" id="frmSwScenario" method="POST" action="" style="display:none;">
		<input type="hidden" name="SubmitMode" id="SubmitMode" value="">
		<input type="hidden" name="fdtUserLoginId" id="fdtUserLoginId" value="{$fdtUserLoginIdHtml}">
		<input type="hidden" name="fdtUserLoginDate" id="fdtUserLoginDate" value="{$fdtUserLoginDateHtml}">
		<input type="hidden" name="fdtScenarioId" id="fdtScenarioId" value="{$fdtScenarioIdHtml}">
	</form>

	<div class="contener">
		<row class="row-1">
			<!-- 左側 -->
			<div class="col-sm-3">
				<!-- 場面表示フォーム -->
				<div id="scene-index-area"></div>
			</div>
			<!-- 右側 -->
			<div class="col-sm-9">
				<!-- シナリオ表示フォーム -->
				<div id="scenario-area"></div>
			</div>
		</row>
	</div>
	
	<script type="text/javascript" src="./ajax/ajaxSwScenarioView.js"></script>
	<script language="JavaScript">
		fncReadScenario('$fdtScenarioId','$fdtUserLoginId');
	</script>

	<!--footer表示位置--><span class="sw-footer"></span>
  </body>
</html>

END_OF_HTML;

	print $retHtml;

}//end function
// -----------------------------------------------------------
?>