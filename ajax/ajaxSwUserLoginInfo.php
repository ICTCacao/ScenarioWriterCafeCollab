<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
// 
//     SW_USER_LOGIN_INFO I/O ｼｽﾃﾑ
//
//     ajaxSwUserLoginInfo.php
// -----------------------------------------------------------
// ------------------------------------------------------------------------------
	//定数読み込み
	include_once("../sw_config/swConstant.php");
	//DB接続ｸﾗｽの初期化
	include_once("../include/ConnectMySQL.php");
	//共同執筆: 権限ガード（閲覧・要ログイン）
	$swCollabNeed = 'read';
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
	global	$fdtUserLoginId;
	global	$fdtUserId;
	global	$fdtUserLoginDate;

	//処理ﾓｰﾄが空白ならreturnするﾞ
	if(!isset($_POST['SubMode'])){return;}
	//処理ﾓｰﾄﾞ
	$SubMode = swFunc_GetPostData('SubMode');
	if($SubMode == ''){return;}
	//POSTされた要素を取得
	$fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
	$fdtUserId = swFunc_GetPostData('fdtUserId');                    //USER_ID
	$fdtUserLoginDate = swFunc_GetPostData('fdtUserLoginDate');      //USERログイン日時
}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	global	$SubMode;
	//global 変数
	global	$fdtUserLoginId;
	global	$fdtUserId;
	global	$fdtUserLoginDate;

	// 出力をクリア
	$resultHtml = '';

	//SubModeで処理を制御
	if($SubMode == 'SwUserLoginInfoList'){
		$resultHtml = fncMakeSwUserLoginInfoList($mySqlConnObj);
	}

	// 出力charsetをUTF-8に指定
	mb_http_output ( 'UTF-8' );
	// 出力
	echo($resultHtml);
}//end function

//--------------------------------------------------------------------------------
// SW_USER_LOGIN_INFO ALL LIST
//--------------------------------------------------------------------------------
function fncMakeSwUserLoginInfoList($mySqlConnObj){
	//ﾘｽﾄのﾍｯﾀﾞｰ
	$retHtml = <<<END_OF_HTML
	
		<div class="scroll_div">
		<table class="table" _fixedhead="rows:1;div-full-mode: no;">
			<tr>
				<th>USERログインID</th>
				<th>USER_ID</th>
				<th>USERログイン日時</th>
			</tr>
END_OF_HTML;

	//DB から ﾃﾞｰﾀを取得しﾌﾟﾛﾊﾟﾃｨにｾｯﾄする
	$strSQL = <<<END_OF_SQL
		SELECT *,USER_LOGIN_ID as KEY_ITEM
			FROM SW_USER_LOGIN_INFO
					ORDER BY USER_LOGIN_ID;
END_OF_SQL;
	//SQLを実行
	$myResult = $mySqlConnObj->query($strSQL);
	while($myRow = $myResult->fetch(PDO::FETCH_ASSOC)){
		$valUserLoginId = $myRow['USER_LOGIN_ID'];
		$valUserId = $myRow['USER_ID'];
		$valUserLoginDate = $myRow['USER_LOGIN_DATE'];
		$valKeyItem = $myRow['KEY_ITEM'];
		//ﾘｽﾄ
		$retHtml .= <<<END_OF_HTML
		
			<tr onclick="fncSelectSwUserLoginInfo('$valKeyItem');">
				<td>$valUserLoginId</td>
				<td>$valUserId</td>
				<td>$valUserLoginDate</td>
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
