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
	//共同執筆: 権限ガード（編集）
	$swCollabNeed = 'edit';
	include_once("./include/swCollabGuard.php");
// ------------------------------------------------------------------------------
	//ﾃﾞﾌｫﾙﾄｱｸｼｮﾝ
	$ThisPHP = 'swScenarioEdit.php';
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("./class/clsSwScenario.php");
	$clsSwScenario = new clsSwScenario();
	//共通関数をｲﾝｸﾙｰﾄﾞ
	include_once("./include/swFunc.php");
	
	// ﾍｯﾀﾞ表示
	$HtmlTitle = "シナリオ編集";
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
	global	$fdtUserLoginId,$fdtUserLoginDate;
	global	$fdtScenarioId;

	//POSTされた要素を取得
	$fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
	$fdtUserLoginDate = swFunc_GetPostData('fdtUserLoginDate');      //USERログイン日時
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');      //シナリオID

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
	global	$fdtScenarioTitle,$fdtScenarioSubtitle;
	global	$fdtScenarioWriterName,$fdtScenarioMemo,$fdtScenarioDate;

	global	$fdtScenarioLinesId;
	global	$fdtSceneId;
	global	$fdtScenarioLinesOrderNo;
	global	$fdtScenarioType;
	global	$fdtCharacterId;
	global	$fdtScenarioLines;

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


	//ﾃﾞｰﾀ管理ｸﾗｽからｼﾅﾘｵ情報を取得
	include_once("./class/clsSwScenario.php");
	$clsSwScenario = new clsSwScenario();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScenario->clsSwScenarioInit($mySqlConnObj,$fdtScenarioId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	fncSwScenarioGetProperty($clsSwScenario);

	//ｻﾆﾀｲｽﾞ
	$fdtUserName = swFunc_SanitizeStrings($fdtUserName);
	$fdtScenarioTitle = swFunc_SanitizeStrings($fdtScenarioTitle);

	//css powerd by Bootstrap ver3
  	print <<<END_OF_HTML

	<div id="wrap">
END_OF_HTML;
	include_once("./include/swMenuBar.php");
	swMenuBar_Print('./include/swEditDropDownMenu.php', 'frmSwScenario', swFunc_SanitizeStrings($fdtScenarioTitle), array(), $fdtUserName, '登場人物名をクリックで編集、本文をクリックでその場編集');
	print <<<END_OF_HTML
END_OF_HTML;

	//------------------------------------------------------------
	//CSVﾃﾞｰﾀからselect box を作成する
	$ObjName = "fdtSceneId";		//select box の名称.ID
	//ここでfunctionからCSVﾃﾞｰﾀを取得
	$csvArray = swFunc_MakeSelectItemsCsvSceneId($mySqlConnObj,$fdtScenarioId);	//CSVﾃﾞｰﾀ
	$default = "$fdtSceneId";		//ﾃﾞﾌｫﾙﾄ値
	$onChange = '';					//onChange で起動する javascript or jQuery
	$ViewCode = FALSE;				//ｺｰﾄﾞを表示する場合はTRUE
	//SelectBoxHtml出力
	$SelectBoxSceneId = swFunc_MakeSelectBox($ObjName,$csvArray,$default,$onChange,$ViewCode);
	//------------------------------------------------------------

	//ｼﾅﾘｵBody ---------------------------------------------------------------------------------------------------
	print <<<END_OF_HTML
	
			<!-- main -->
			<div class="scenarioedit">
				<div class="row">
					<div class="col-sm-12">
						<form class="" role="form" name="frmSwScenario" id="frmSwScenario" method="POST" action="">
							<input type="hidden" name="fdtUserLoginId" id="fdtUserLoginId" value="$fdtUserLoginId">
							<input type="hidden" name="fdtUserLoginDate" id="fdtUserLoginDate" value="$fdtUserLoginDate">
							<input type="hidden" name="fdtScenarioId" id="fdtScenarioId" value="$fdtScenarioId">
							<input type="hidden" name="SubmitMode" id="SubmitMode" value="">
							<input type="hidden" name="SelectScenarioLinesId" id="SelectScenarioLinesId" value="">
							<input type="hidden" name="listpos" id="listpos" value="">
							<input type="hidden" name="SearchMode" id="SearchMode" value="">
							
							<div class="row mb-3">
								<div class="col-sm-5">
									$SelectBoxSceneId
								</div>
								<div class="col-sm-4">
									<div class="input-group">
										<input type="text" class="form-control  form-control-sm" style="font-size: 11pt;padding: 5.2px;"
											id="fdtSearchWord" name="fdtSearchWord" value="" placeholder="シナリオ全体から検索します">
											<button type="button" class="btn btn-outline-secondary" aria-label="Left Align"
												onClick="fncSearchScenario(frmSwScenario);">
												<span class="glyphicon glyphicon glyphicon-search" aria-hidden="true"></span>
												検索
											</button>
									</div>
								</div>
								<div class="col-sm-3">&nbsp;</div>
							</div>
							<!-- ｼﾅﾘｵ表示位置 -->
							<div class="row mb-3">
								<span id="ScenarioLinesOfScene"></span>
							</div>
						</form>
					</div><!-- col-sm-12 -->
				</div><!-- row end -->
			</div>
		</div>

	<script type="text/javascript" src="./ajax/ajaxSwScenarioEdit.js"></script>
	<script language="JavaScript">
		fncReadScenarioLinesOfScene(frmSwScenario)
	</script>
	<script>
		//共同執筆: 他の人の更新通知の「再読み込み」は、画面全体ではなく今の場面の表示だけ読み直す
		window.swCollab_refresh = function(){ fncReadScenarioLinesOfScene(frmSwScenario); };
	</script>
	
	<!--footer表示位置--><span class="sw-footer"></span>
  </body>
</html>

END_OF_HTML;

}//end function

// ------------------------------------------------------------------------------
//      ﾌﾟﾛﾊﾟﾃｨGet
//          fncSwScenarioGetProperty($clsSwScenario)
// ------------------------------------------------------------------------------
function fncSwScenarioGetProperty($clsSwScenario){
	global	$fdtScenarioId,$fdtUserId;
	global	$fdtScenarioTitle,$fdtScenarioSubtitle;
	global	$fdtScenarioWriterName,$fdtScenarioMemo,$fdtScenarioDate;

	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtScenarioId = $clsSwScenario->clsSwScenarioGetScenarioId();            //シナリオID
	$fdtUserId = $clsSwScenario->clsSwScenarioGetUserId();                    //USER_ID
	$fdtScenarioTitle = $clsSwScenario->clsSwScenarioGetScenarioTitle();      //タイトル
	$fdtScenarioSubtitle = $clsSwScenario->clsSwScenarioGetScenarioSubtitle();//サブタイトル
	$fdtScenarioWriterName = $clsSwScenario->clsSwScenarioGetScenarioWriterName();//作者名

	//メモは<br>を\nに変換する
	$fdtScenarioMemo = $clsSwScenario->clsSwScenarioGetScenarioMemo();        //メモ
	$fdtScenarioMemo = str_replace("<br>","\n",(string)$fdtScenarioMemo);   //PHP8.1: DBのNULLを渡すと Deprecated

	$fdtScenarioDate = $clsSwScenario->clsSwScenarioGetScenarioDate();        //執筆日
}//end function

// -----------------------------------------------------------
?>