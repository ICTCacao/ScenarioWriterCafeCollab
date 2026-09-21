<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
// 
//     SW_CHARACTER I/O ｼｽﾃﾑ
//
//     SwCharacter.php
// -----------------------------------------------------------
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
	//共同執筆: 権限ガード（編集）
	$swCollabNeed = 'edit';
	include_once("./include/swCollabGuard.php");
// ------------------------------------------------------------------------------
	//ﾃﾞﾌｫﾙﾄｱｸｼｮﾝ
	$ThisPHP = 'swScenarioEditCharacter.php';
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("./class/clsSwCharacter.php");
	$clsSwCharacter = new clsSwCharacter();
	//共通関数をｲﾝｸﾙｰﾄﾞ
	include_once("./include/swFunc.php");
	// ﾍｯﾀﾞ表示
	$HtmlTitle = "登場人物設定";
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
	global	$fdtUserLoginId;
	global	$fdtScenarioId;
	
	//POSTされた要素を取得
	$fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //SCENARIO_ID

}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	//ﾌｫｰﾑの表示
	fncMainForm($mySqlConnObj);

}//end function

// ------------------------------------------------------------------------------
//      MAIN FORM
//          fncMainForm($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainForm($mySqlConnObj){
	//共通global変数
	global	$fdtUserLoginId;
	global	$fdtScenarioId;
	
	//ﾛｸﾞｲﾝ情報からﾕｰｻﾞ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ ﾛｸﾞｲﾝ情報
	include_once("./class/clsSwUserLoginInfo.php");
	$clsSwUserLoginInfo = new clsSwUserLoginInfo();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$fdtUserLoginId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtUserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId(); //USER_ID

	//ﾃﾞｰﾀ管理ｸﾗｽ ﾕｰｻﾞ情報
	include_once("./class/clsSwUser.php");
	$clsSwUser = new clsSwUser();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUser->clsSwUserInit($mySqlConnObj,$fdtUserId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtUserName = $clsSwUser->clsSwUserGetUserName();               //ユーザー名

	//ﾃﾞｰﾀ管理ｸﾗｽからシナリオ情報取得
	include_once("./class/clsSwScenario.php");
	$clsSwScenario = new clsSwScenario();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScenario->clsSwScenarioInit($mySqlConnObj,$fdtScenarioId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtScenarioTitle = $clsSwScenario->clsSwScenarioGetScenarioTitle();      //タイトル

	//ｻﾆﾀｲｽﾞ
	$fdtUserName = swFunc_SanitizeStrings($fdtUserName);
	$fdtScenarioTitle = swFunc_SanitizeStrings($fdtScenarioTitle);

	//css powerd by Bootstrap ver3
  	print <<<END_OF_HTML

END_OF_HTML;
	include_once("./include/swMenuBar.php");
	swMenuBar_Print('./include/swEditDropDownMenu.php', 'frmSwScenario', swFunc_SanitizeStrings($fdtScenarioTitle), array(
		array('label'=>'登場人物追加', 'onclick'=>"fncNewCharacterInit(frmSwScenario);"),
	), $fdtUserName);
	print <<<END_OF_HTML

		<!-- main -->
			<div class="scenarioedit">
				<div class="row">
					<div class="col-sm-12">
						<form class="" role="form" name="frmSwScenario" id="frmSwScenario" method="POST" action="">
							<input type="hidden" name="fdtUserLoginId" id="fdtUserLoginId" value="$fdtUserLoginId">
							<input type="hidden" name="fdtScenarioId" id="fdtScenarioId" value="$fdtScenarioId">
							<input type="hidden" name="SubmitMode" id="SubmitMode" value="">
							<input type="hidden" name="SelectCharacterId" id="SelectCharacterId" value="">
							<input type="hidden" name="listpos" id="listpos" value="">
							
							<!-- データ表示位置 -->
							<div>
								<span id="CharacterList"></span>
							</div>
						</form>
					</div><!-- col-sm-12 -->
				</div><!-- row end -->
			</div>
				
	<script type="text/javascript" src="./ajax/ajaxSwScenarioEditCharacter.js"></script>
	<script language="JavaScript">
		fncReadSwCharacterList(frmSwScenario)
	</script>
	<!--footer表示位置--><span class="sw-footer"></span>
  </body>
</html>

END_OF_HTML;

}//end function
// -----------------------------------------------------------
?>