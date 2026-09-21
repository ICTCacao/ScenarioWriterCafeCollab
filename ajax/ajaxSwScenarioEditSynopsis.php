<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
// 
//     SW_SYNOPSIS I/O ｼｽﾃﾑ
//
//     ajaxSwSynopsis.php
// -----------------------------------------------------------
// ------------------------------------------------------------------------------
	//定数読み込み
	include_once("../sw_config/swConstant.php");
	//DB接続ｸﾗｽの初期化
	include_once("../include/ConnectMySQL.php");
	//共同執筆: 権限ガード（編集）
	$swCollabNeed = 'edit';
	include_once("../include/swCollabGuard.php");
// ------------------------------------------------------------------------------
	//共通関数をｲﾝｸﾙｰﾄﾞ
	include_once("../include/swFunc.php");
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
	global	$SubMode;
	//ﾌｫｰﾑ name要素をglobal変数にする
	global	$fdtSynopsisId;
	global	$fdtScenarioId;
	global	$fdtSynopsis;
	global	$fdtUpdateDate;

	//処理ﾓｰﾄが空白ならreturnするﾞ
	if(!isset($_POST['SubMode'])){return;}
	//処理ﾓｰﾄﾞ
	$SubMode = swFunc_GetPostData('SubMode');
	if($SubMode == ''){return;}
	//POSTされた要素を取得
	$fdtSynopsisId = swFunc_GetPostData('fdtSynopsisId');            //SYNOPSIS_ID
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //SCENARIO_ID
	$fdtSynopsis = swFunc_GetPostData('fdtSynopsis');                //SYNOPSIS
	$fdtUpdateDate = swFunc_GetPostData('fdtUpdateDate');            //更新日時
}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	global	$SubMode;
	//global 変数
	global	$fdtSynopsisId;
	global	$fdtScenarioId;
	global	$fdtSynopsis;
	global	$fdtUpdateDate;

	// 出力をクリア
	$resultHtml = '';

	//SubModeで処理を制御
	if($SubMode == 'SwSynopsisList'){
		$resultHtml = fncMakeSwSynopsisList($mySqlConnObj);
	}

	// 出力charsetをUTF-8に指定
	mb_http_output ( 'UTF-8' );
	// 出力
	echo($resultHtml);
}//end function

//--------------------------------------------------------------------------------
// SW_SYNOPSIS ALL LIST
//--------------------------------------------------------------------------------
function fncMakeSwSynopsisList($mySqlConnObj){
	//ﾘｽﾄのﾍｯﾀﾞｰ
	$retHtml = <<<END_OF_HTML
	
		<div class="scroll_div">
		<table class="table" _fixedhead="rows:1;div-full-mode: no;">
			<tr>
			</tr>
END_OF_HTML;

	//DB から ﾃﾞｰﾀを取得しﾌﾟﾛﾊﾟﾃｨにｾｯﾄする
	$strSQL = <<<END_OF_SQL
		SELECT *,SYNOPSIS_ID as KEY_ITEM
			FROM SW_SYNOPSIS
					ORDER BY SYNOPSIS_ID;
END_OF_SQL;
	//SQLを実行
	$myResult = $mySqlConnObj->query($strSQL);
	while($myRow = $myResult->fetch(PDO::FETCH_ASSOC)){
		$valKeyItem = $myRow['KEY_ITEM'];
		//ﾘｽﾄ
		$retHtml .= <<<END_OF_HTML
		
			<tr onclick="fncSelectSwSynopsis('$valKeyItem');">
			</tr>
END_OF_HTML;
		}//end while

		$retHtml .= <<<END_OF_HTML
		
		</table>
		</div>
END_OF_HTML;
	//結果セットを開放
	$myResult->closeCursor();   //PHP8: mysqli の free() は PDO に無い
	//HTMLを返す
	return $retHtml;
}
