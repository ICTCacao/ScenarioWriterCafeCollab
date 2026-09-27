<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
// 
//     SW_SCENARIO I/O ｼｽﾃﾑ
//
//     swScenarioSelect.php
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
// ------------------------------------------------------------------------------

	//ﾃﾞﾌｫﾙﾄｱｸｼｮﾝ
	$ThisPHP = 'swScenarioSelect.php';
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("./class/clsSwScenario.php");
	$clsSwScenario = new clsSwScenario();
	//共通関数をｲﾝｸﾙｰﾄﾞ
	include_once("./include/swFunc.php");
	
	// ﾍｯﾀﾞ表示
	$HtmlTitle = "シナリオ選択";
	include_once("./include/swHeader.php");

	//ﾌｫｰﾑのﾃﾞｰﾀを読み込む
	fncGetPostItems();


	//MainProcedure
	fncMainProc($mySqlConnObj);

	exit();

// ------------------------------------------------------------------------------
//      POSTされた要素を取得
//          fncGetPostItems()
// ------------------------------------------------------------------------------
function fncGetPostItems(){
	//共通global変数
	global	$SubmitMode,$fdtUserLoginId,$fdtUserLoginDate;

	//POSTされた要素を取得
	$fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
	$fdtUserLoginDate = swFunc_GetPostData('fdtUserLoginDate');      //USERログイン日時

	//処理ﾓｰﾄが空白ならreturnするﾞ
	if(!isset($_POST['SubmitMode'])){return;}
	//処理ﾓｰﾄﾞ
	$SubmitMode = swFunc_GetPostData('SubmitMode');
	if($SubmitMode == ''){return;}

}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	//共通global変数
	global	$SubmitMode,$fdtUserLoginId,$fdtUserLoginDate;

	//選択ﾌｫｰﾑの表示
	fncMainForm($mySqlConnObj);

}//end function

// ------------------------------------------------------------------------------
//      MAIN FORM
//          fncMainForm($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainForm($mySqlConnObj){
	//共通global変数
	global	$ThisPHP,$SubmitMode,$fdtUserLoginId,$fdtUserLoginDate;
	global	$PastTime;

	//ﾛｸﾞｲﾝ情報からﾕｰｻﾞ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ ﾛｸﾞｲﾝ情報
	include_once("./class/clsSwUserLoginInfo.php");
	$clsSwUserLoginInfo = new clsSwUserLoginInfo();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$fdtUserLoginId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtUserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId();                    //USER_ID
	$fdtUserLoginDate = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserLoginDate();      //USERログイン日時

	//ﾃﾞｰﾀ管理ｸﾗｽ ﾕｰｻﾞ情報
	include_once("./class/clsSwUser.php");
	$clsSwUser = new clsSwUser();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUser->clsSwUserInit($mySqlConnObj,$fdtUserId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtUserName = $clsSwUser->clsSwUserGetUserName();                //ユーザー名
	$fdtUserMaxScenario = $clsSwUser->clsSwUserGetUserMaxScenario();  //作成可能シナリオ数


	print <<<END_OF_HTML

END_OF_HTML;
	include_once("./include/swMenuBar.php");
	swMenuBar_Print('./include/swDropDownMenu.php', 'frmSwScenario', 'シナリオ選択', array(
		array('label'=>'シナリオを読み込む（.scwd）', 'onclick'=>"AjaxFunc_SubmitNoMsg(document.frmSwScenario,'./swScenarioImport.php');"),
	), $fdtUserName);
	print <<<END_OF_HTML
		
		<!-- main -->
		
		<div class="scenarioedit">
			<div class="row">
				<div class="col-sm-12">
					<form class="" role="form" name="frmSwScenario" id="frmSwScenario" method="POST" action="$ThisPHP">
						<input type="hidden" name="SubmitMode" id="SubmitMode" value="$SubmitMode">
						<input type="hidden" name="fdtUserLoginId" id="fdtUserLoginId" value="$fdtUserLoginId">
						<input type="hidden" name="fdtUserLoginDate" id="fdtUserLoginDate" value="$fdtUserLoginDate">
						<input type="hidden" name="fdtScenarioId" id="fdtScenarioId" value="">
						
						<!-- リストの表示位置 -->
						<div id="ScenarioSelectList"></div>
					</form>

				</div>
			</div>
		</div>
		
    
	<script type="text/javascript" src="./ajax/ajaxSwScenarioSelect.js"></script>
    <script type="text/javascript">
        fncMakeSwScenarioSelectList(frmSwScenario);
    </script>
	
  </body>
</html>

END_OF_HTML;

}//end function


// -----------------------------------------------------------
?>