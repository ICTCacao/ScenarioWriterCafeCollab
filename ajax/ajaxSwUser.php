<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
// 
//     SW_USER I/O ｼｽﾃﾑ
//
//     ajaxSwUser.php
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
	global	$fdtUserId;
	global	$fdtUserMailad;
	global	$fdtUserPasswd;
	global	$fdtUserName;
	global	$fdtUserMaxScenario;

	//処理ﾓｰﾄが空白ならreturnするﾞ
	if(!isset($_POST['SubMode'])){return;}
	//処理ﾓｰﾄﾞ
	$SubMode = swFunc_GetPostData('SubMode');
	if($SubMode == ''){return;}
	//POSTされた要素を取得
	$fdtUserId = swFunc_GetPostData('fdtUserId');                    //ユーザーID
	$fdtUserMailad = swFunc_GetPostData('fdtUserMailad');            //メールアドレス
	$fdtUserPasswd = swFunc_GetPostData('fdtUserPasswd');            //パスワード
	$fdtUserName = swFunc_GetPostData('fdtUserName');                //ユーザー名


	$fdtUserMaxScenario = swFunc_GetPostData('fdtUserMaxScenario');  //作成可能シナリオ数
}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	global	$SubMode;
	//global 変数
	global	$fdtUserId;
	global	$fdtUserMailad;
	global	$fdtUserPasswd;
	global	$fdtUserName;
	global	$fdtUserMaxScenario;

	// 出力をクリア
	$resultHtml = '';

	//SubModeで処理を制御
	if($SubMode == 'SwUserList'){
		$resultHtml = fncMakeSwUserList($mySqlConnObj);
	}

	// 出力charsetをUTF-8に指定
	mb_http_output ( 'UTF-8' );
	// 出力
	echo($resultHtml);
}//end function

//--------------------------------------------------------------------------------
// SW_USER ALL LIST
//--------------------------------------------------------------------------------
function fncMakeSwUserList($mySqlConnObj){
	//ﾘｽﾄのﾍｯﾀﾞｰ
	$retHtml = <<<END_OF_HTML
	
		<div class="scroll_div">
		<table class="table" _fixedhead="rows:1;div-full-mode: no;">
			<tr>
				<th>メールアドレス</th>
				<th>ユーザー名</th>
				<th>作成可能シナリオ数</th>
			</tr>
END_OF_HTML;

	//DB から ﾃﾞｰﾀを取得しﾌﾟﾛﾊﾟﾃｨにｾｯﾄする
	$strSQL = <<<END_OF_SQL
		SELECT *,USER_ID as KEY_ITEM
			FROM SW_USER
					ORDER BY USER_ID;
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$stmt->execute();
	while($myRow = $stmt -> fetch(PDO::FETCH_ASSOC)) {
		$valUserMailad = $myRow['USER_MAILAD'];
		$valUserName = $myRow['USER_NAME'];
		$valUserMaxScenario = $myRow['USER_MAX_SCENARIO'];
		$valKeyItem = $myRow['KEY_ITEM'];
		//ﾘｽﾄ
		$retHtml .= <<<END_OF_HTML
		
			<tr onclick="fncSelectSwUser('$valKeyItem');">
				<td>$valUserMailad</td>
				<td>$valUserName</td>
				<td>$valUserMaxScenario</td>
			</tr>
END_OF_HTML;
		}//end while

		$retHtml .= <<<END_OF_HTML
		
		</table>
		</div>
END_OF_HTML;
	//HTMLを返す
	return $retHtml;
}
