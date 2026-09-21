<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//     SW_SCENARIO_LINES I/O ｼｽﾃﾑ
//
//     ajaxSwScenarioEdit.php
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

	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwScenarioLines.php");
	$clsSwScenarioLines = new clsSwScenarioLines();

	//ﾌｫｰﾑのﾃﾞｰﾀを読み込む
	fncGetPostItems($mySqlConnObj);

	//MainProcedure
	fncMainProc($mySqlConnObj);

	exit();

// ------------------------------------------------------------------------------
//      POSTされた要素を取得
//          fncGetPostItems()
// ------------------------------------------------------------------------------
function fncGetPostItems($mySqlConnObj){
	global	$clsSwScenarioLines;
	//共通global変数
	global	$fdtUserLoginId,$fdtUserLoginDate;
	//共通global変数
	global	$SubMode,$SubmitMode;
	//ﾌｫｰﾑ name要素をglobal変数にする
	global	$fdtScenarioLinesId;
	global	$fdtScenarioId;
	global	$fdtSceneId;
	global	$fdtScenarioLinesOrderNo;
	global	$fdtScenarioType;
	global	$fdtCharacterId;
	global	$fdtScenarioLines;

	//編集中のID
	global	$fdtEditLinesId;
	global	$tgtId;

	//検索文字
	global	$fdtSearchWord,$SearchedWord,$fdtReplaceWord;
	global	$SearchMode;

	//POSTされた要素を取得
	$fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
	$fdtUserLoginDate = swFunc_GetPostData('fdtUserLoginDate');      //USERログイン日時
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //SCENARIO_ID

	//処理ﾓｰﾄが空白ならreturnするﾞ
	if(!isset($_POST['SubMode'])){return;}
	//処理ﾓｰﾄﾞ
	$SubMode = swFunc_GetPostData('SubMode');
	if($SubMode == ''){return;}
	//POSTされた要素を取得
	//$fdtScenarioLinesId = swFunc_GetPostData('fdtScenarioLinesId');  //SCENARIO_LINES_ID
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //SCENARIO_ID
	$fdtSceneId = swFunc_GetPostData('fdtSceneId');                  //SCENE_ID
	$fdtScenarioLinesOrderNo = swFunc_GetPostData('fdtScenarioLinesOrderNo');//並び順
	$fdtScenarioType = swFunc_GetPostData('fdtScenarioType');        //シナリオ種別
	$fdtCharacterId = swFunc_GetPostData('fdtCharacterId');          //登場人物
	$fdtScenarioLines = swFunc_GetPostData('fdtScenarioLines');      //台詞
	$SubmitMode = swFunc_GetPostData('SubmitMode');      //SubmitMode
	$SelectScenarioLinesId = swFunc_GetPostData('SelectScenarioLinesId');//SCENARIO_LINES_ID
	$fdtScenarioLinesId = $SelectScenarioLinesId;

	//編集中のID
	$fdtEditLinesId = swFunc_GetPostData('fdtEditLinesId');     //編集中のID
	$tgtId = swFunc_GetPostData('tgtId');     //移動対象のシナリオID
	//検索文字
	$fdtSearchWord = swFunc_GetPostData('fdtSearchWord'); //検索文字
	$SearchedWord = swFunc_GetPostData('SearchedWord'); //検索文字
	$fdtReplaceWord = swFunc_GetPostData('fdtReplaceWord'); //置換文字

	$SearchMode = swFunc_GetPostData('SearchMode'); //検索状態
	//検索中の場面IDは個別に $fdtEditLinesId から取得する
	if($SearchMode == 'ON'){
		if($fdtEditLinesId != ''){
			//ﾃﾞｰﾀ管理ｸﾗｽの初期化
			$clsSwScenarioLines->clsSwScenarioLinesInit($mySqlConnObj,$fdtEditLinesId);
			$fdtSceneId = $clsSwScenarioLines->clsSwScenarioLinesGetSceneId();//SCENE_ID
		}
	}
	//echo "fdtEditLinesId = $fdtEditLinesId<br />";
	//echo "fdtSceneId = $fdtSceneId<br />";
}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	global	$SubMode,$SubmitMode;
	//global 変数
	global	$fdtScenarioLinesId;
	global	$fdtScenarioId;
	global	$fdtSceneId;
	global	$fdtScenarioLinesOrderNo;
	global	$fdtScenarioType;
	global	$fdtCharacterId;
	global	$fdtScenarioLines;
	//台詞を「」で囲むかのｵﾌﾟｼｮﾝを読む
	swFunc_LoadKagikakkoOption($mySqlConnObj,$fdtScenarioId);

	// 出力をクリア
	$resultHtml = '';

	switch($SubMode){
		case 'ReadScenarioLinesOfScene':
			//並び順を整理する
			fncUpdateScenarioLinesOfScene($mySqlConnObj);
			//シーンのシナリオを表示
			$resultHtml = fncMakeScenarioLinesOfScene($mySqlConnObj);
			break;
		case 'ScenarioLinesOfScene':
			$resultHtml = fncMakeScenarioLinesOfScene($mySqlConnObj);
			break;
		case 'SwitchEditForm':
			$resultHtml = fncMakeSwitchEditForm($mySqlConnObj);
			break;
		case 'SetLinesById':
			$resultHtml = fncSetLinesById($mySqlConnObj);
			break;
		case 'GetNextOrderNo':
			$resultHtml = fncGetNextOrderNo($mySqlConnObj);
			break;
		case 'MoveScenarioLinesBefore':
			$resultHtml = fncMoveScenarioLinesBefore($mySqlConnObj);
			break;
		case 'MoveScenarioLinesAfter':
			$resultHtml = fncMoveScenarioLinesAfter($mySqlConnObj);
			break;
		case 'Search':
			$resultHtml = fncScenarioSearch($mySqlConnObj);
			break;
		case 'Replace':
			$resultHtml = fncScenarioReplace($mySqlConnObj);
			break;
		case 'ReorderLines':
			//ﾄﾞﾗｯｸﾞ&ﾄﾞﾛｯﾌﾟの並び替え（2026-09 追加）
			$resultHtml = fncReorderLines($mySqlConnObj);
			break;
		case 'GetLinesText':
			//本文ｲﾝﾗｲﾝ編集: 生のﾃｷｽﾄを返す（2026-09 追加）
			$resultHtml = fncGetLinesText($mySqlConnObj);
			break;
		case 'InlineUpdate':
			//本文ｲﾝﾗｲﾝ編集: 本文だけ更新（2026-09 追加）
			$resultHtml = fncInlineUpdateLines($mySqlConnObj);
			break;
		default:
			break;
	}

	// 出力charsetをUTF-8に指定
	mb_http_output ( 'UTF-8' );
	// 出力
	echo($resultHtml);
}//end function


//--------------------------------------------------------------------------------
//	シナリオをIDでSETする
//--------------------------------------------------------------------------------
function fncSetLinesById($mySqlConnObj){
	global	$fdtUserLoginId,$fdtScenarioId;
	global	$UserOptionStyleArray;
	//シナリオのキャラクタ配列
	global	$ScenarioCharacterArray;
	//編集中のID
	global	$fdtEditLinesId;
	global	$SearchMode;


	//ﾛｸﾞｲﾝ情報からﾕｰｻﾞ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ ﾛｸﾞｲﾝ情報
	include_once("../class/clsSwUserLoginInfo.php");
	$clsSwUserLoginInfo = new clsSwUserLoginInfo();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$fdtUserLoginId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$UserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId(); //USER_ID

	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwUserOption.php");
	$clsSwUserOption = new clsSwUserOption();
	//USER STYLEを配列化
	$UserOptionStyleArray = $clsSwUserOption->fncGetUserOptionStyle($mySqlConnObj,$UserId);

	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwCharacter.php");
	$clsSwCharacter = new clsSwCharacter();
	//キャラクタをIDで連想配列にする
	$ScenarioCharacterArray = $clsSwCharacter->fncGetScenarioCharacter($mySqlConnObj,$fdtScenarioId);

	//DB から ﾃﾞｰﾀを取得しﾌﾟﾛﾊﾟﾃｨにｾｯﾄする
	$strSQL = <<<END_OF_SQL

		SELECT * FROM SW_SCENARIO_LINES
			WHERE SCENARIO_LINES_ID = :EditLinesId;
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$stmt->bindParam(':EditLinesId', $fdtEditLinesId, PDO::PARAM_INT);
	$stmt->execute();
	$retHtml = '';
	while($myRow = $stmt -> fetch(PDO::FETCH_ASSOC)) {
		$ScenarioLinesId = $myRow['SCENARIO_LINES_ID'];
		$ScenarioId = $myRow['SCENARIO_ID'];
		$SceneId = $myRow['SCENE_ID'];
		$ScenarioLinesOrderNo = $myRow['SCENARIO_LINES_ORDER_NO'];
		$ScenarioType = $myRow['SCENARIO_TYPE'];
		$CharacterId = $myRow['CHARACTER_ID'];
		$ScenarioLines = $myRow['SCENARIO_LINES'];

		//表に追加
		$retHtml =  fncArrangeScenarioLine($mySqlConnObj,$ScenarioLinesId,$ScenarioType,$CharacterId,$ScenarioLines,$ScenarioLinesOrderNo,FALSE);
	}

	return	$retHtml;
}
//--------------------------------------------------------------------------------
//	シーンのシナリオを表示する
//--------------------------------------------------------------------------------
function fncMakeScenarioLinesOfScene($mySqlConnObj){
	global	$fdtUserLoginId;
	global	$UserOptionStyleArray;
	//シナリオのキャラクタ配列
	global	$ScenarioCharacterArray;
	//global 変数
	global	$fdtScenarioLinesId;
	global	$fdtScenarioId;
	global	$fdtSceneId;
	global	$fdtScenarioLinesOrderNo;
	global	$fdtScenarioType;
	global	$fdtCharacterId;
	global	$fdtScenarioLines;

	//ﾛｸﾞｲﾝ情報からﾕｰｻﾞ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ ﾛｸﾞｲﾝ情報
	include_once("../class/clsSwUserLoginInfo.php");
	$clsSwUserLoginInfo = new clsSwUserLoginInfo();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$fdtUserLoginId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$UserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId(); //USER_ID

	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwUserOption.php");
	$clsSwUserOption = new clsSwUserOption();
	//USER STYLEを配列化
	$UserOptionStyleArray = $clsSwUserOption->fncGetUserOptionStyle($mySqlConnObj,$UserId);

	//シナリオIDからシナリオ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwScenario.php");
	$clsSwScenario = new clsSwScenario();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScenario->clsSwScenarioInit($mySqlConnObj,$fdtScenarioId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtScenarioTitle = swFunc_SanitizeStrings($clsSwScenario->clsSwScenarioGetScenarioTitle());      //タイトル
	$fdtScenarioSubtitle = swFunc_SanitizeStrings($clsSwScenario->clsSwScenarioGetScenarioSubtitle());//サブタイトル
	$fdtScenarioWriterName = swFunc_SanitizeStrings($clsSwScenario->clsSwScenarioGetScenarioWriterName());//作者名
	//シーンIDからシーン情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwScene.php");
	$clsSwScene = new clsSwScene();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScene->clsSwSceneInit($mySqlConnObj,$fdtSceneId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtSceneName = swFunc_SanitizeStrings($clsSwScene->clsSwSceneGetSceneName());              //場面名称

	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwCharacter.php");
	$clsSwCharacter = new clsSwCharacter();
	//キャラクタをIDで連想配列にする
	$ScenarioCharacterArray = $clsSwCharacter->fncGetScenarioCharacter($mySqlConnObj,$fdtScenarioId);


	//HTMLを編集
	$retHtml = <<<END_OF_HTML

		<div class="scroll_area" id="ScenelioLines">
END_OF_HTML;
	//DB から ﾃﾞｰﾀを取得しﾌﾟﾛﾊﾟﾃｨにｾｯﾄする
	$strSQL = <<<END_OF_SQL

		SELECT * FROM SW_SCENARIO_LINES
			WHERE SCENE_ID = :SceneId
					ORDER BY SCENARIO_LINES_ORDER_NO;
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$stmt->bindParam(':SceneId', $fdtSceneId, PDO::PARAM_INT);
	$stmt->execute();
	//件数取得
	$myRowCnt = $stmt->rowCount();
	//書き始めで何も登録されていない場合は編集ﾌｫｰﾑ表示
	if($myRowCnt == 0 ){
		$SubmitMode = 'NEXT';
		$retHtml = fncMakeSwitchEditForm($mySqlConnObj);
	}else{
		while($myRow = $stmt -> fetch(PDO::FETCH_ASSOC)) {
			$ScenarioLinesId = $myRow['SCENARIO_LINES_ID'];
			$ScenarioId = $myRow['SCENARIO_ID'];
			$SceneId = $myRow['SCENE_ID'];
			$ScenarioLinesOrderNo = $myRow['SCENARIO_LINES_ORDER_NO'];
			$ScenarioType = $myRow['SCENARIO_TYPE'];
			$CharacterId = $myRow['CHARACTER_ID'];
			$ScenarioLines = $myRow['SCENARIO_LINES'];
			//$ScenarioLines = str_replace("\n","<br>",$ScenarioLines);
			//表に追加
			$retHtml .=  fncArrangeScenarioLine($mySqlConnObj,$ScenarioLinesId,$ScenarioType,$CharacterId,$ScenarioLines,$ScenarioLinesOrderNo,TRUE);
		}//end while
	}

		$retHtml .= <<<END_OF_HTML

		</div>

		<script type="text/javascript">
			$(document).ready(function() {
				$("#ScenelioLines").scroll(function(){
					var st = $("#ScenelioLines").scrollTop();
					$("#listpos").text(st);
				});
			});
		</script>

END_OF_HTML;

	//HTMLを返す
	return $retHtml;
}

//--------------------------------------------------------------------------------
//	シナリオを揃える
//--------------------------------------------------------------------------------
function fncArrangeScenarioLine($mySqlConnObj,$ScenarioLinesId,$ScenarioType,$CharacterId,$ScenarioLines,$ScenarioLinesOrderNo,$mode){
	global	$UserOptionStyleArray;
	//シナリオのキャラクタ配列
	global	$ScenarioCharacterArray;
	global	$SearchMode;//検索状態
	global	$SubmitMode;//処理モード


	//登場人物名を配列から取得
	if(isset($ScenarioCharacterArray[$CharacterId])){
		$fdtCharacterName = $ScenarioCharacterArray[$CharacterId];
	}else{
		$fdtCharacterName = '';
	}
	//サニタイズ
	$fdtCharacterName = swFunc_SanitizeStrings($fdtCharacterName);
	//最後が読点なら削除
	//$ScenarioLines = swFunc_RemoveLastStr($ScenarioLines,'。');
	$ScenarioLines = str_replace("<br>","\n",$ScenarioLines);
	//サニタイズ
	$ScenarioLines = swFunc_SanitizeStrings($ScenarioLines);
	$ScenarioLines = str_replace("\n","<br>",$ScenarioLines);

	switch($mode){
		case TRUE:
			$orderId = "order_{$ScenarioLinesOrderNo}";
			$retHtml = <<<END_OF_HTML

				<div id="$orderId"></div>
				<div id="edit_{$ScenarioLinesId}" class="col-sm-12 eidt-lines">
					<div>
						<span class="col-sm-1 chara text-start">
							<span class="sw-drag-handle" title="ドラッグで並べ替え">&#8942;&#8942;</span>
							<img src="./css/img/up.png" width="18" style="cursor: pointer;" onclick="fncMoveScenarioLinesBefore(frmSwScenario,'$ScenarioLinesId');">
							<img src="./css/img/down.png" width="18" style="cursor: pointer;" onclick="fncMoveScenarioLinesAfter(frmSwScenario,'$ScenarioLinesId');">
						</span>
END_OF_HTML;
			break;
		case FALSE:
			$orderId = "order_{$ScenarioLinesOrderNo}";
			$retHtml = <<<END_OF_HTML

					<div>
						<span class="col-sm-1 chara text-start">
							<span class="sw-drag-handle" title="ドラッグで並べ替え">&#8942;&#8942;</span>
							<img src="./css/img/up.png" width="18" style="cursor: pointer;" onclick="fncMoveScenarioLinesBefore(frmSwScenario,'$ScenarioLinesId');">
							<img src="./css/img/down.png" width="18" style="cursor: pointer;" onclick="fncMoveScenarioLinesAfter(frmSwScenario,'$ScenarioLinesId');">
						</span>
END_OF_HTML;
			break;
		default:
			break;
	}

	//検索中は移動不可
	if($SearchMode == 'ON'){
			$mode = 'NONE';
			$orderId = "order_{$ScenarioLinesOrderNo}";

			if($SubmitMode == ''){
				$retHtml = <<<END_OF_HTML

				<div id="$orderId"></div>
				<div id="edit_{$ScenarioLinesId}" class="col-sm-12 eidt-lines">
					<div>
						<span class="col-sm-1 chara text-start">&nbsp;</span>
END_OF_HTML;
			}else{
				$retHtml = <<<END_OF_HTML

					<div>
						<span class="col-sm-1 chara text-start">&nbsp;</span>
END_OF_HTML;
			}
	}


	//USER STYLEを反映させる
	$StyleArray = explode(',',$UserOptionStyleArray[$ScenarioType]);
	$UserOptionStyleFontSize = '12';
	$UserOptionStyleColor = '#000';
	$UserOptionStyleIndent = '0';
	$UserOptionStyleStr = '';
	$UserOptionStyleWord = '0';
	if(isset($StyleArray[0])){$UserOptionStyleFontSize = $StyleArray[0];}
	if(isset($StyleArray[1])){$UserOptionStyleColor = $StyleArray[1];}
	if(isset($StyleArray[2])){$UserOptionStyleIndent = $StyleArray[2];}
	if(isset($StyleArray[3])){$UserOptionStyleStr = $StyleArray[3];}
	if(isset($StyleArray[4])){$UserOptionStyleWord = $StyleArray[4];}

	switch($UserOptionStyleWord){
		case '1'://登場人物名表示&台詞を「」で囲む
				$fdtCharacterName = $fdtCharacterName.'&nbsp;'.$UserOptionStyleStr;
				//最後が読点なら削除して「」で囲む（ｵﾌﾟｼｮﾝで囲まない設定なら何もしない）
				$ScenarioLines = swFunc_KagikakkoMaru($ScenarioLines);
				$align = 'left';
			break;
		case '2'://登場人物名表示&台詞を「」で囲まない
				$fdtCharacterName = $fdtCharacterName.'&nbsp;'.$UserOptionStyleStr;
				$align = 'left';
			break;
		case '3'://登場人物名非表示&台詞を「」で囲む
				$fdtCharacterName = $UserOptionStyleStr;
				//最後が読点なら削除して「」で囲む（ｵﾌﾟｼｮﾝで囲まない設定なら何もしない）
				$ScenarioLines = swFunc_KagikakkoMaru($ScenarioLines);
				$align = 'right';
			break;
		default://登場人物名非表示&台詞を「」で囲まない
				$fdtCharacterName = $UserOptionStyleStr;
				$align = 'right';
			break;
	}
	if($fdtCharacterName == ''){
		$fdtCharacterName = '編集';
		$align = 'right;color: #c0c6c9';
	}
	//CSS編集
	$cssCharacterName = 'style="cursor: pointer;font-size: '.$UserOptionStyleFontSize.'pt;text-align: '.$align.';"';
	$cssScenarioLines = 'style="font-size: '.$UserOptionStyleFontSize.'pt;color: '.$UserOptionStyleColor.';padding-left: '.$UserOptionStyleIndent.'em;"';
	$retOnclick = <<<END_OF_HTML
		onclick="fncSelectScenarioLines(frmSwScenario,'$ScenarioLinesId','$ScenarioLinesOrderNo');"
END_OF_HTML;

	$retHtml .= <<<END_OF_HTML

						<span class="col-sm-2" $cssCharacterName
							$retOnclick>$fdtCharacterName</span>
						<span class="col-sm-8 sw-inline-editable" $cssScenarioLines
							onclick="fncInlineEditLines(this,'$ScenarioLinesId');">$ScenarioLines</span>
						<span class="col-sm-1">&nbsp;</span>
END_OF_HTML;


	switch($mode){
		case TRUE:
		$retHtml .= <<<END_OF_HTML

					</div>
					<div class="sw-insert-bar"
						onclick="fncInsertAfterLine('$ScenarioLinesId','$ScenarioLinesOrderNo');"><span>＋ この下に追加</span></div>
				</div>
END_OF_HTML;
			break;
		case FALSE:
		$retHtml .= <<<END_OF_HTML

					</div>
					<div class="sw-insert-bar"
						onclick="fncInsertAfterLine('$ScenarioLinesId','$ScenarioLinesOrderNo');"><span>＋ この下に追加</span></div>
END_OF_HTML;
			break;
		case 'NONE':
		$retHtml .= <<<END_OF_HTML

					</div>
					<div class="sw-insert-bar"
						onclick="fncInsertAfterLine('$ScenarioLinesId','$ScenarioLinesOrderNo');"><span>＋ この下に追加</span></div>
				</div>
END_OF_HTML;
			break;
		default:
			break;
	}

	return	$retHtml;
}


//--------------------------------------------------------------------------------
//	シナリオの編集Formを編集する
//--------------------------------------------------------------------------------
function fncMakeSwitchEditForm($mySqlConnObj){
	global	$clsSwScenarioLines;

	global	$SubmitMode;
	global	$fdtScenarioLinesId;
	global	$fdtScenarioLinesOrderNo;

	//SubmitModeで処理を分岐
	switch($SubmitMode){
		case 'NEXT':
			//ﾌﾟﾛﾊﾟﾃｨReset
			fncSwScenarioLinesResetProperty($clsSwScenarioLines);
			//ﾌｫｰﾑの表示
			$retHtml = fncMakeEditForm($mySqlConnObj);
			break;
		case 'SELECT':
			//ﾃﾞｰﾀ管理ｸﾗｽの初期化
			$clsSwScenarioLines->clsSwScenarioLinesInit($mySqlConnObj,$fdtScenarioLinesId);
			//ﾌﾟﾛﾊﾟﾃｨGet
			fncSwScenarioLinesGetProperty($clsSwScenarioLines);
			//ﾌｫｰﾑの表示
			$retHtml = fncMakeEditForm($mySqlConnObj);
			break;
		case 'INSERT':
			//登録処理
			fncSwScenarioLinesDBInsert($mySqlConnObj,$clsSwScenarioLines);
			//選択状態にする
			$SubmitMode = 'SELECT';
			//ﾃﾞｰﾀ管理ｸﾗｽの初期化
			$clsSwScenarioLines->clsSwScenarioLinesInit($mySqlConnObj,$fdtScenarioLinesId);
			//ﾌﾟﾛﾊﾟﾃｨGet
			fncSwScenarioLinesGetProperty($clsSwScenarioLines);

			$retHtml =	$fdtScenarioLinesId;
			break;
		case 'UPDATE':
			//更新処理
			fncSwScenarioLinesDBUpdate($mySqlConnObj,$clsSwScenarioLines);
			//選択状態にする
			$SubmitMode = 'SELECT';
			//ﾃﾞｰﾀ管理ｸﾗｽの初期化
			$clsSwScenarioLines->clsSwScenarioLinesInit($mySqlConnObj,$fdtScenarioLinesId);
			//ﾌﾟﾛﾊﾟﾃｨGet
			fncSwScenarioLinesGetProperty($clsSwScenarioLines);
			break;
		case 'DELETE':
			//削除処理
			fncSwScenarioLinesDBDelete($mySqlConnObj,$clsSwScenarioLines);
			$SubmitMode = '';
			$fdtScenarioLinesId = '';
			break;
		default:
			//ﾌｫｰﾑの表示
			$retHtml = fncMakeEditForm($mySqlConnObj);
			break;
	}

	return	$retHtml;

}

// ------------------------------------------------------------------------------
//      EDIT FORM
//          fncMakeEditForm($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMakeEditForm($mySqlConnObj){
	global	$clsSwScenarioLines;
	global	$fdtUserLoginId;

	global	$SubmitMode;
	global	$fdtScenarioLinesId;
	global	$fdtScenarioId;
	global	$fdtSceneId;
	global	$fdtScenarioLinesOrderNo;
	global	$fdtScenarioType;
	global	$fdtCharacterId;
	global	$fdtScenarioLines;

	global	$SearchMode;

	//ﾛｸﾞｲﾝ情報からﾕｰｻﾞ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ ﾛｸﾞｲﾝ情報
	include_once("../class/clsSwUserLoginInfo.php");
	$clsSwUserLoginInfo = new clsSwUserLoginInfo();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$fdtUserLoginId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$UserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId(); //USER_ID

	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwUserOption.php");
	$clsSwUserOption = new clsSwUserOption();
	//USER STYLEを配列化
	$UserOptionStyleArray = $clsSwUserOption->fncGetUserOptionStyle($mySqlConnObj,$UserId);
	//default
	$UserOptionStyleFontSize = '12';
	if(isset($UserOptionStyleArray[1])){$UserOptionStyleFontSize = $UserOptionStyleArray[1];}

	//場面情報
	//シナリオﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwScenarioLines.php");
	$clsSwScenarioLines = new clsSwScenarioLines();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScenarioLines->clsSwScenarioLinesInit($mySqlConnObj,$fdtScenarioLinesId);
	$EditSceneId = $clsSwScenarioLines->clsSwScenarioLinesGetSceneId();                  //SCENE_ID
	//場面ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwScene.php");
	$clsSwScene = new clsSwScene();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScene->clsSwSceneInit($mySqlConnObj,$EditSceneId);
	$EditSceneName = $clsSwScene->clsSwSceneGetSceneName();              //場面名称

	//CSVﾃﾞｰﾀからselect box を作成する
	$ObjName = "fdtScenarioType";		//select box の名称.ID
	//ここでfunctionからCSVﾃﾞｰﾀを取得
	$csvArray = swFunc_MakeSelectItemsCsvScenarioType($mySqlConnObj,$UserId);	//CSVﾃﾞｰﾀ
	$default = "$fdtScenarioType";		//ﾃﾞﾌｫﾙﾄ値
	$onChange = '';					//onChange で起動する javascript or jQuery
	$ViewCode = FALSE;				//ｺｰﾄﾞを表示する場合はTRUE
	//SelectBoxHtml出力
	$SelectBoxScenarioType = swFunc_MakeSelectBox($ObjName,$csvArray,$default,$onChange,$ViewCode);
	//------------------------------------------------------------
	//CSVﾃﾞｰﾀからselect box を作成する
	$ObjName = "fdtCharacterId";		//select box の名称.ID
	//ここでfunctionからCSVﾃﾞｰﾀを取得
	$csvArray = swFunc_MakeSelectItemsCsvCharacterId($mySqlConnObj,$fdtScenarioId);	//CSVﾃﾞｰﾀ
	$default = "$fdtCharacterId";		//ﾃﾞﾌｫﾙﾄ値
	$onChange = '';//onChange で起動する javascript or jQuery
	$ViewCode = FALSE;				//ｺｰﾄﾞを表示する場合はTRUE
	//SelectBoxHtml出力
	$SelectBoxfdtCharacterId = swFunc_MakeSelectBox($ObjName,$csvArray,$default,$onChange,$ViewCode);
	//------------------------------------------------------------

	//後に挿入のとき選択した台詞を編集領域に表示する
	if ($SubmitMode == 'NEXT'){
		$retHtml = fncSetLinesById($mySqlConnObj);
	}else{
		$retHtml = '';
	}
	if(!is_numeric($UserOptionStyleFontSize)){
		$UserOptionStyleFontSize = 12;
	}
	//inputのfontsizeを設定
	$EditFontSize = $UserOptionStyleFontSize - 1;
	$retHtml .= <<<END_OF_HTML

				<div id="edit_lines" class="col-sm-12" style="background-color: #e7e7eb;">
					<br />
					<input type="hidden" name="fdtEditLinesId" id="fdtEditLinesId" value="$fdtScenarioLinesId">
					<input type="hidden" name="fdtScenarioLinesId" id="fdtScenarioLinesId" value="$fdtScenarioLinesId">
					<input type="hidden" name="fdtScenarioLinesOrderNo" id="fdtScenarioLinesOrderNo" value="$fdtScenarioLinesOrderNo">
						<div class="row mb-3">
							<div class="col-sm-5">
								<span class="sw-close-x" title="閉じる" onclick="fncScenarioEditClose(frmSwScenario);">&#x2715;</span>
								$EditSceneName
							</div>
						</div>
						<div class="row mb-3">
							<div class="col-sm-3">
								$SelectBoxScenarioType
							</div>
							<div class="col-sm-4">
								$SelectBoxfdtCharacterId
							</div>
						</div>
						<div class="row mb-3">
							<div class="col-sm-9 offset-sm-3">
								<textarea placeholder="台詞やト書"
									class="form-control form-control-sm"
									id="fdtScenarioLines" name="fdtScenarioLines"
									rows="5"
									style="font-size: {$EditFontSize}pt;">$fdtScenarioLines</textarea>
							</div>
						</div>
						<div class="row mb-3">
							<div class="col-sm-12">
END_OF_HTML;

    //SubmitModeで表示するボタンを制御する
	switch($SubmitMode){
		case 'SELECT':
			$retHtml .= <<<END_OF_HTML
				<legend class="d-flex flex-wrap align-items-center editmenu">
				<ul class="list-inline">
END_OF_HTML;

			//「後に挿入」ﾎﾞﾀﾝは 2026-09 に廃止。各行の下の追加ﾊﾞｰ（fncInsertAfterLine）から挿入する

			$retHtml .= <<<END_OF_HTML
					<li>
						<input type="button" value="保　存"
							id="btnUpdate"
							onclick="fncScenarioLinesSubmit(frmSwScenario,'UPDATE');"
							class="btn btn-warning btn-sm">
					</li>
					<li style="width: 200px;text-align: right;">
						<input type="button" value="削　除"
							id="btnDelete"
							onclick="fncScenarioLinesSubmit(frmSwScenario,'DELETE');"
							class="btn btn-danger btn-sm">
					</li>
				</ul>
				</legend>
END_OF_HTML;
			break;
		case 'NEXT':
			$retHtml .= <<<END_OF_HTML
				<legend class="d-flex flex-wrap align-items-center editmenu">
				<ul class="list-inline">
					<li>
						<input type="button" value="保　存"
							id="btnInsert"
							onclick="fncScenarioLinesSubmit(frmSwScenario,'INSERT');"
							class="btn btn-success btn-sm">
					</li>
				</ul>
				</legend>
END_OF_HTML;
			break;
        default:
			$retHtml .= <<<END_OF_HTML
				<legend class="d-flex flex-wrap align-items-center editmenu">
				<ul class="list-inline">
					<li style="width: 200px;text-align: right;">
						<input type="button" value="保　存"
						id="btnInsert"
						onclick="fncScenarioLinesSubmit(frmSwScenario,'INSERT');"
						class="btn btn-success btn-sm">
					</li>
				</ul>
				</legend>
END_OF_HTML;
            break;
    }

	$retHtml .= <<<END_OF_HTML

					</div>
				</div>
			</div><!-- edit_form -->
			<script type="text/javascript">
				$(document).ready(function() {
					//selectbox onchange　ｲﾍﾞﾝﾄ
					$('#fdtScenarioType').change(function() {
						if($('#fdtScenarioType').val()!='1'){
							$('#fdtCharacterId').val(-1);
						}
					});
				});
			</script>
			<script>
				autosize(document.querySelectorAll('textarea'));
			</script>

END_OF_HTML;



	return	$retHtml;

}//end function

// ------------------------------------------------------------------------------
//      ﾌﾟﾛﾊﾟﾃｨReset
//          fncSwScenarioLinesResetProperty($clsSwScenarioLines)
// ------------------------------------------------------------------------------
function fncSwScenarioLinesResetProperty($clsSwScenarioLines){
	global	$fdtScenarioLinesId;
	global	$fdtScenarioId;
	global	$fdtSceneId;
	global	$fdtScenarioLinesOrderNo;
	global	$fdtScenarioType;
	global	$fdtCharacterId;
	global	$fdtScenarioLines;

	//ﾌﾟﾛﾊﾟﾃｨReset
	//$fdtScenarioLinesId = "";                 //SCENARIO_LINES_ID
	//$fdtScenarioId = "";                      //SCENARIO_ID
	//$fdtSceneId = "";                         //SCENE_ID
	//$fdtScenarioLinesOrderNo = "";            //並び順
	$fdtScenarioType = "";                    //シナリオ種別
	$fdtCharacterId = "";                     //登場人物
	$fdtScenarioLines = "";                   //台詞
}//end function

// ------------------------------------------------------------------------------
//      ﾌﾟﾛﾊﾟﾃｨGet
//          fncSwScenarioLinesGetProperty($clsSwScenarioLines)
// ------------------------------------------------------------------------------
function fncSwScenarioLinesGetProperty($clsSwScenarioLines){
	global	$fdtScenarioLinesId;
	global	$fdtScenarioId;
	global	$fdtSceneId;
	global	$fdtScenarioLinesOrderNo;
	global	$fdtScenarioType;
	global	$fdtCharacterId;
	global	$fdtScenarioLines;

	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtScenarioLinesId = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioLinesId();  //SCENARIO_LINES_ID
	//$fdtScenarioId = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioId();            //SCENARIO_ID
	//$fdtSceneId = $clsSwScenarioLines->clsSwScenarioLinesGetSceneId();                  //SCENE_ID
	$fdtScenarioLinesOrderNo = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioLinesOrderNo();//並び順
	$fdtScenarioType = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioType();        //シナリオ種別
	$fdtCharacterId = $clsSwScenarioLines->clsSwScenarioLinesGetCharacterId();          //登場人物

	//台詞は<br>を\nに変換する
	$fdtScenarioLines = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioLines();      //台詞
	$fdtScenarioLines = str_replace("<br>","\n",$fdtScenarioLines);

}//end function

// ------------------------------------------------------------------------------
//      ﾌﾟﾛﾊﾟﾃｨSet
//          fncSwScenarioLinesSetProperty($clsSwScenarioLines)
// ------------------------------------------------------------------------------
function fncSwScenarioLinesSetProperty($clsSwScenarioLines){
	global	$fdtScenarioLinesId;
	global	$fdtScenarioId;
	global	$fdtSceneId;
	global	$fdtScenarioLinesOrderNo;
	global	$fdtScenarioType;
	global	$fdtCharacterId;
	global	$fdtScenarioLines;

	//ﾌﾟﾛﾊﾟﾃｨSet
	$clsSwScenarioLines->clsSwScenarioLinesSetScenarioLinesId($fdtScenarioLinesId);  //SCENARIO_LINES_ID
	$clsSwScenarioLines->clsSwScenarioLinesSetScenarioId($fdtScenarioId);            //SCENARIO_ID
	$clsSwScenarioLines->clsSwScenarioLinesSetSceneId($fdtSceneId);                  //SCENE_ID
	$clsSwScenarioLines->clsSwScenarioLinesSetScenarioLinesOrderNo($fdtScenarioLinesOrderNo);//並び順
	$clsSwScenarioLines->clsSwScenarioLinesSetScenarioType($fdtScenarioType);        //シナリオ種別
	$clsSwScenarioLines->clsSwScenarioLinesSetCharacterId($fdtCharacterId);          //登場人物
	//台詞は改行を<br>にしてからSetする
	$fdtScenarioLines = str_replace("\r\n","<br>",$fdtScenarioLines);
	$fdtScenarioLines = str_replace("\r","<br>",$fdtScenarioLines);
	$fdtScenarioLines = str_replace("\n","<br>",$fdtScenarioLines);
	$clsSwScenarioLines->clsSwScenarioLinesSetScenarioLines($fdtScenarioLines);      //台詞

}//end function

// ------------------------------------------------------------------------------
//      DB INSERT
//          fncSwScenarioLinesDBInsert($mySqlConnObj,$clsSwScenarioLines)
// ------------------------------------------------------------------------------
function fncSwScenarioLinesDBInsert($mySqlConnObj,$clsSwScenarioLines){
	global	$fdtScenarioLinesId;

	//ﾌﾟﾛﾊﾟﾃｨｾｯﾄ
	fncSwScenarioLinesSetProperty($clsSwScenarioLines);
	//ﾃﾞｰﾀ管理ｸﾗｽ DbInsert
	$fdtScenarioLinesId = $clsSwScenarioLines->clsSwScenarioLinesDbInsert($mySqlConnObj);
}//end function

// ------------------------------------------------------------------------------
//      DB UPDATE
//          fncSwScenarioLinesDBUpdate($mySqlConnObj,$clsSwScenarioLines)
// ------------------------------------------------------------------------------
function fncSwScenarioLinesDBUpdate($mySqlConnObj,$clsSwScenarioLines){
	//ﾌﾟﾛﾊﾟﾃｨｾｯﾄ
	fncSwScenarioLinesSetProperty($clsSwScenarioLines);
	//ﾃﾞｰﾀ管理ｸﾗｽ DbUpdate
	$clsSwScenarioLines->clsSwScenarioLinesDbUpdate($mySqlConnObj);
}//end function

// ------------------------------------------------------------------------------
//      DB DELETE
//          fncSwScenarioLinesDBDelete($mySqlConnObj,$clsSwScenarioLines)
// ------------------------------------------------------------------------------
function fncSwScenarioLinesDBDelete($mySqlConnObj,$clsSwScenarioLines){
	//ﾌﾟﾛﾊﾟﾃｨｾｯﾄ
	fncSwScenarioLinesSetProperty($clsSwScenarioLines);
	//ﾃﾞｰﾀ管理ｸﾗｽ DbDelete
	$clsSwScenarioLines->clsSwScenarioLinesDbDelete($mySqlConnObj);
}//end function

//--------------------------------------------------------------------------------
//	fncUpdateScenarioLinesOfScene($mySqlConnObj)
//		並び順を整理する
//--------------------------------------------------------------------------------
function fncUpdateScenarioLinesOfScene($mySqlConnObj){
	global	$clsSwScenarioLines;
	global	$fdtScenarioId;
	global	$fdtSceneId;

	$clsSwScenarioLines->fncAdjustScenarioLinesOrderNo($mySqlConnObj,$fdtScenarioId,$fdtSceneId);

}

//--------------------------------------------------------------------------------
//	fncMoveScenarioLinesBefore
//
//			一つ前に移動させる
//				fncMoveScenarioLinesBefore($mySqlConnObj)
//--------------------------------------------------------------------------------
function fncMoveScenarioLinesBefore($mySqlConnObj){
	global	$clsSwScenarioLines;
	//global 変数
	global	$fdtScenarioLinesId;
	global	$fdtScenarioId;
	global	$fdtSceneId;
	global	$fdtScenarioLinesOrderNo;
	global	$fdtScenarioType;
	global	$fdtCharacterId;
	global	$fdtScenarioLines;

	global	$tgtId;

	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScenarioLines->clsSwScenarioLinesInit($mySqlConnObj,$tgtId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtScenarioLinesId = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioLinesId();  //SCENARIO_LINES_ID
	$fdtScenarioId = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioId();            //SCENARIO_ID
	$fdtSceneId = $clsSwScenarioLines->clsSwScenarioLinesGetSceneId();                  //SCENE_ID
	$fdtScenarioLinesOrderNo = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioLinesOrderNo();//並び順
	$fdtScenarioType = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioType();        //シナリオ種別
	$fdtCharacterId = $clsSwScenarioLines->clsSwScenarioLinesGetCharacterId();          //登場人物
	//台詞は<br>を\nに変換する
	$fdtScenarioLines = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioLines();      //台詞
	$fdtScenarioLines = str_replace("<br>","\n",$fdtScenarioLines);

	//並び順が小さくて最大の2件をSELECT
	$strSQL = <<<END_OF_SQL

	SELECT * FROM SW_SCENARIO_LINES
		WHERE SCENE_ID = :SceneId
				AND  SCENARIO_LINES_ORDER_NO < :OrderNo
		ORDER BY SCENARIO_LINES_ORDER_NO DESC LIMIT 2;
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$stmt->bindParam(':SceneId', $fdtSceneId, PDO::PARAM_INT);
	$stmt->bindParam(':OrderNo', $fdtScenarioLinesOrderNo, PDO::PARAM_INT);
	$stmt->execute();
	//件数取得
	$myRowCnt = $stmt->rowCount();

	//ScenarioTypeで処理を分岐
	switch($myRowCnt){
		case '2':
			//最大LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax1ScenarioLinesOrderNo = $myRow['SCENARIO_LINES_ORDER_NO'];
			//2番目LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax2ScenarioLinesOrderNo = $myRow['SCENARIO_LINES_ORDER_NO'];
			//新しい並び順
			$move_num = round(($BeforeMax1ScenarioLinesOrderNo - $BeforeMax2ScenarioLinesOrderNo) / 2);
			$fdtScenarioLinesOrderNo = $BeforeMax1ScenarioLinesOrderNo - $move_num;
			break;
		case '1':
			//最大LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax1ScenarioLinesOrderNo = $myRow['SCENARIO_LINES_ORDER_NO'];
			$BeforeMax2ScenarioLinesOrderNo = 0;
			//新しい並び順
			$move_num = round(($BeforeMax1ScenarioLinesOrderNo - $BeforeMax2ScenarioLinesOrderNo) / 2);
			$fdtScenarioLinesOrderNo = $BeforeMax1ScenarioLinesOrderNo - $move_num;
			break;
		default:
			$BeforeMax1ScenarioLinesOrderNo = 100;
			$BeforeMax2ScenarioLinesOrderNo = 0;
			//新しい並び順
			$move_num = round(($BeforeMax1ScenarioLinesOrderNo - $BeforeMax2ScenarioLinesOrderNo) / 2);
			$fdtScenarioLinesOrderNo = $BeforeMax1ScenarioLinesOrderNo - $move_num;
			break;
	}

	//更新処理
	fncSwScenarioLinesDBUpdate($mySqlConnObj,$clsSwScenarioLines);

	return;
}
//--------------------------------------------------------------------------------
//	fncMoveScenarioLinesAfter
//
//			一つ前に移動させる
//				fncMoveScenarioLinesAfter($mySqlConnObj)
//--------------------------------------------------------------------------------
function fncMoveScenarioLinesAfter($mySqlConnObj){
	global	$clsSwScenarioLines;
	//global 変数
	global	$fdtScenarioLinesId;
	global	$fdtScenarioId;
	global	$fdtSceneId;
	global	$fdtScenarioLinesOrderNo;
	global	$fdtScenarioType;
	global	$fdtCharacterId;
	global	$fdtScenarioLines;

	global	$tgtId;

	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScenarioLines->clsSwScenarioLinesInit($mySqlConnObj,$tgtId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtScenarioLinesId = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioLinesId();  //SCENARIO_LINES_ID
	$fdtScenarioId = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioId();            //SCENARIO_ID
	$fdtSceneId = $clsSwScenarioLines->clsSwScenarioLinesGetSceneId();                  //SCENE_ID
	$fdtScenarioLinesOrderNo = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioLinesOrderNo();//並び順
	$fdtScenarioType = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioType();        //シナリオ種別
	$fdtCharacterId = $clsSwScenarioLines->clsSwScenarioLinesGetCharacterId();          //登場人物
	//台詞は<br>を\nに変換する
	$fdtScenarioLines = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioLines();      //台詞
	$fdtScenarioLines = str_replace("<br>","\n",$fdtScenarioLines);

	//並び順が小さくて最大の2件をSELECT
	$strSQL = <<<END_OF_SQL

	SELECT * FROM SW_SCENARIO_LINES
		WHERE SCENE_ID = :SceneId
				AND  SCENARIO_LINES_ORDER_NO > :OrderNo
		ORDER BY SCENARIO_LINES_ORDER_NO LIMIT 2;
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$stmt->bindParam(':SceneId', $fdtSceneId, PDO::PARAM_INT);
	$stmt->bindParam(':OrderNo', $fdtScenarioLinesOrderNo, PDO::PARAM_INT);
	$stmt->execute();
	//件数取得
	$myRowCnt = $stmt->rowCount();

	//ScenarioTypeで処理を分岐
	switch($myRowCnt){
		case '2':
			//最小LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax1ScenarioLinesOrderNo = $myRow['SCENARIO_LINES_ORDER_NO'];
			//2番目LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax2ScenarioLinesOrderNo = $myRow['SCENARIO_LINES_ORDER_NO'];
			//新しい並び順
			$move_num = round(($BeforeMax2ScenarioLinesOrderNo - $BeforeMax1ScenarioLinesOrderNo) / 2);
			$fdtScenarioLinesOrderNo = $BeforeMax1ScenarioLinesOrderNo + $move_num;
			break;
		case '1':
			//最小LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax1ScenarioLinesOrderNo = $myRow['SCENARIO_LINES_ORDER_NO'];
			//新しい並び順
			$fdtScenarioLinesOrderNo = $BeforeMax1ScenarioLinesOrderNo + 100;
			break;
		default:
			return;
			break;
	}

	//更新処理
	fncSwScenarioLinesDBUpdate($mySqlConnObj,$clsSwScenarioLines);

	return;
}


//--------------------------------------------------------------------------------
//	fncGetNextOrderNo
//--------------------------------------------------------------------------------
function fncGetNextOrderNo($mySqlConnObj){
	//global 変数
	global	$fdtScenarioLinesId;
	global	$fdtScenarioId;
	global	$fdtSceneId;
	global	$fdtScenarioLinesOrderNo;
	global	$fdtScenarioType;
	global	$fdtCharacterId;
	global	$fdtScenarioLines;

	//初期化
	$NewOrderNo = 0;
	$ScenarioLinesOrderNo = $fdtScenarioLinesOrderNo;
	$AfterMinScenarioLinesOrderNo = $fdtScenarioLinesOrderNo + 100;
	//DB から ﾃﾞｰﾀを取得しﾌﾟﾛﾊﾟﾃｨにｾｯﾄする
	$strSQL = <<<END_OF_SQL

		SELECT
			  MIN(B.SCENARIO_LINES_ORDER_NO) 		AS	AFTER_MIN_SCENARIO_LINES_ORDER_NO
			, A.SCENARIO_LINES_ORDER_NO					AS	SCENARIO_LINES_ORDER_NO
		FROM SW_SCENARIO_LINES A
			INNER JOIN SW_SCENARIO_LINES B
				ON A.SCENE_ID = B.SCENE_ID
						AND A.SCENARIO_LINES_ORDER_NO < B.SCENARIO_LINES_ORDER_NO
		WHERE A.SCENARIO_LINES_ID = :ScenarioLinesId;
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$stmt->bindParam(':ScenarioLinesId', $fdtScenarioLinesId, PDO::PARAM_INT);
	$stmt->execute();
	while($myRow = $stmt -> fetch(PDO::FETCH_ASSOC)) {
		$AfterMinScenarioLinesOrderNo = $myRow['AFTER_MIN_SCENARIO_LINES_ORDER_NO'];
		$ScenarioLinesOrderNo = $myRow['SCENARIO_LINES_ORDER_NO'];
	}
	//新しい並び順
	if($AfterMinScenarioLinesOrderNo == ''){
		$NewOrderNo = round($fdtScenarioLinesOrderNo + 100,-2);
	}else{
		$OrderNo = round(($AfterMinScenarioLinesOrderNo - $ScenarioLinesOrderNo) / 2,4);
		$NewOrderNo = $ScenarioLinesOrderNo + $OrderNo;
	}

	return	$NewOrderNo;
}

//--------------------------------------------------------------------------------
//	シナリオ検索
//--------------------------------------------------------------------------------
function fncScenarioSearch($mySqlConnObj){
	//共通global変数
	global	$fdtSearchWord,$fdtScenarioId;
	global	$fdtUserLoginId;

	global	$fdtUserLoginId;
	global	$UserOptionStyleArray;
	//シナリオのキャラクタ配列
	global	$ScenarioCharacterArray;

	//ﾛｸﾞｲﾝ情報からﾕｰｻﾞ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ ﾛｸﾞｲﾝ情報
	include_once("../class/clsSwUserLoginInfo.php");
	$clsSwUserLoginInfo = new clsSwUserLoginInfo();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$fdtUserLoginId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$UserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId(); //USER_ID

	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwUserOption.php");
	$clsSwUserOption = new clsSwUserOption();
	//USER STYLEを配列化
	$UserOptionStyleArray = $clsSwUserOption->fncGetUserOptionStyle($mySqlConnObj,$UserId);

	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwCharacter.php");
	$clsSwCharacter = new clsSwCharacter();
	//キャラクタをIDで連想配列にする
	$ScenarioCharacterArray = $clsSwCharacter->fncGetScenarioCharacter($mySqlConnObj,$fdtScenarioId);



	//HTMLを編集
	$retHtml = <<<END_OF_HTML

		<div class="scroll_area" id="ScenelioLines">
			<div class="col-sm-1">&nbsp;</div>
			<div class="col-sm-3">
				<button type="button" class="btn btn-outline-secondary w-100" aria-label="Left Align"
					onClick="fncReadScenarioLinesOfScene(frmSwScenario);">
					<span class="glyphicon glyphicon glyphicon-refresh" aria-hidden="true"></span>
					再読み込み
				</button>
			</div>
			<input type="hidden" name="SearchedWord" id="SearchedWord" value="$fdtSearchWord">
			<div class="col-sm-1">&nbsp;</div>
			<div class="col-sm-4">
				<div class="input-group">
					<input type="text" class="form-control form-control-sm" style="font-size: 11pt;padding: 5.2px;"
						id="fdtReplaceWord" name="fdtReplaceWord" value="$fdtSearchWord" placeholder="置換文字">
					<span class="input-group-btn">
						<button type="button" class="btn btn-outline-secondary" aria-label="Left Align"
							onClick="fncReplaceScenario(frmSwScenario);">
							<span class="glyphicon glyphicon glyphicon-repeat" aria-hidden="true"></span>
							全て置換
						</button>
					</span>
				</div>
			</div>
END_OF_HTML;


	//空白なら何もしない
	if(str_replace(array(" ", "　"), "", $fdtSearchWord)==''){
		$retHtml .= <<<END_OF_HTML

		<row>
			<div class="col-sm-12">
				<p>※検索文字が空白です。</p>
			</div>
		</row>
END_OF_HTML;
		return $retHtml;
	}

	//検索文字の半角、全角空白を区切り文字に正規化
	$fdtSearchWord = str_replace(array(" ", "　"), "", $fdtSearchWord);

	//シナリオを検索
	$strSQL = <<<END_OF_SQL

			SELECT * FROM SW_SCENARIO_LINES
				LEFT JOIN SW_SCENE ON SW_SCENE.SCENARIO_ID = SW_SCENARIO_LINES.SCENARIO_ID
														AND SW_SCENE.SCENE_ID = SW_SCENARIO_LINES.SCENE_ID
			WHERE SW_SCENARIO_LINES.SCENARIO_ID = :ScenarioId
				AND SW_SCENARIO_LINES.SCENARIO_LINES LIKE '%{$fdtSearchWord}%'
						ORDER BY SW_SCENE.SCENE_ORDER_NO
							,SW_SCENARIO_LINES.SCENARIO_LINES_ORDER_NO;
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$stmt->bindParam(':ScenarioId', $fdtScenarioId, PDO::PARAM_INT);
	$stmt->execute();
	while($myRow = $stmt -> fetch(PDO::FETCH_ASSOC)) {
		$ScenarioLinesId = $myRow['SCENARIO_LINES_ID'];
		$ScenarioId = $myRow['SCENARIO_ID'];
		$ScenarioLinesOrderNo = $myRow['SCENARIO_LINES_ORDER_NO'];
		$ScenarioType = $myRow['SCENARIO_TYPE'];
		$CharacterId = $myRow['CHARACTER_ID'];
		$ScenarioLines = $myRow['SCENARIO_LINES'];

		//シナリオ表示
		$retHtml .=  fncArrangeScenarioLine($mySqlConnObj,$ScenarioLinesId,$ScenarioType,$CharacterId,$ScenarioLines,$ScenarioLinesOrderNo,FALSE);
	}

	$retHtml .= <<<END_OF_HTML

		</div>
END_OF_HTML;

	//HTMLを返す
	return $retHtml;
}
//--------------------------------------------------------------------------------
//	シナリオ置換検索
//--------------------------------------------------------------------------------
function fncScenarioReplace($mySqlConnObj){
	//共通global変数
	global	$fdtSearchWord,$fdtScenarioId;
	global	$fdtUserLoginId;
	global	$UserOptionStyleArray;
	//シナリオのキャラクタ配列
	global	$ScenarioCharacterArray;

	global	$fdtReplaceWord,$SearchedWord;

	//HTMLを編集
	$retHtml = <<<END_OF_HTML

		<div class="scroll_area" id="ScenelioLines">
			<div class="col-sm-5">
				<button type="button" class="btn btn-outline-secondary w-100" aria-label="Left Align"
					onClick="fncReadScenarioLinesOfScene(frmSwScenario);">
					<span class="glyphicon glyphicon glyphicon-refresh" aria-hidden="true"></span>
					再読み込み
				</button>
			</div>

END_OF_HTML;

	//シナリオを検索
	$strSQL = <<<END_OF_SQL

			SELECT * FROM SW_SCENARIO_LINES
			WHERE SCENARIO_ID = :ScenarioId
				AND SCENARIO_LINES LIKE '%{$SearchedWord}%';
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$stmt->bindParam(':ScenarioId', $fdtScenarioId, PDO::PARAM_INT);
	$stmt->execute();
	while($myRow = $stmt -> fetch(PDO::FETCH_ASSOC)) {
		$ScenarioLinesId = $myRow['SCENARIO_LINES_ID'];
		$ScenarioLines = $myRow['SCENARIO_LINES'];
		//置換
		fncReplaceScenarioLines($mySqlConnObj,$ScenarioLinesId);

	}

	$retHtml .= <<<END_OF_HTML

		</div>
END_OF_HTML;

	//HTMLを返す
	return $retHtml;
}

//--------------------------------------------------------------------------------
//	シナリオ置換検索
//--------------------------------------------------------------------------------
function fncReplaceScenarioLines($mySqlConnObj,$ScenarioLinesId){
	global	$fdtReplaceWord,$SearchedWord;

	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwScenarioLines.php");
	$clsSwScenarioLines = new clsSwScenarioLines();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScenarioLines->clsSwScenarioLinesInit($mySqlConnObj,$ScenarioLinesId);

	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtScenarioLinesId = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioLinesId();  //SCENARIO_LINES_ID
	$fdtScenarioId = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioId();            //SCENARIO_ID
	$fdtSceneId = $clsSwScenarioLines->clsSwScenarioLinesGetSceneId();                  //SCENE_ID
	$fdtScenarioLinesOrderNo = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioLinesOrderNo();//並び順
	$fdtScenarioType = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioType();        //シナリオ種別
	$fdtCharacterId = $clsSwScenarioLines->clsSwScenarioLinesGetCharacterId();          //登場人物
	$fdtScenarioLines = $clsSwScenarioLines->clsSwScenarioLinesGetScenarioLines();      //台詞

	//replace
	$fdtScenarioLines = str_replace($SearchedWord,$fdtReplaceWord,$fdtScenarioLines);

	//ﾌﾟﾛﾊﾟﾃｨSet
	$clsSwScenarioLines->clsSwScenarioLinesSetScenarioLinesId($fdtScenarioLinesId);  //SCENARIO_LINES_ID
	$clsSwScenarioLines->clsSwScenarioLinesSetScenarioId($fdtScenarioId);            //SCENARIO_ID
	$clsSwScenarioLines->clsSwScenarioLinesSetSceneId($fdtSceneId);                  //SCENE_ID
	$clsSwScenarioLines->clsSwScenarioLinesSetScenarioLinesOrderNo($fdtScenarioLinesOrderNo);//並び順
	$clsSwScenarioLines->clsSwScenarioLinesSetScenarioType($fdtScenarioType);        //シナリオ種別
	$clsSwScenarioLines->clsSwScenarioLinesSetCharacterId($fdtCharacterId);          //登場人物
	$clsSwScenarioLines->clsSwScenarioLinesSetScenarioLines($fdtScenarioLines);      //台詞

	//ﾃﾞｰﾀ管理ｸﾗｽ DbUpdate
	$clsSwScenarioLines->clsSwScenarioLinesDbUpdate($mySqlConnObj);

	return;
}
// -----------------------------------------------------------
?><?php
// ------------------------------------------------------------------------------
//      本文ｲﾝﾗｲﾝ編集（2026-09 追加）
//        一覧の本文をｸﾘｯｸしたとき、編集ﾌｫｰﾑを開かずにその場の textarea で本文だけを直す。
//        GetLinesText   : fdtEditLinesId の本文を生ﾃｷｽﾄ（<br> は改行）で返す
//        InlineUpdate   : fdtEditLinesId の本文を fdtInlineText で更新する（種別・登場人物・場面・並び順はそのまま）
// ------------------------------------------------------------------------------
function fncGetLinesText($mySqlConnObj){
	global	$fdtEditLinesId;
	if($fdtEditLinesId == ''){ return ''; }
	$cls = new clsSwScenarioLines();
	$cls->clsSwScenarioLinesInit($mySqlConnObj,$fdtEditLinesId);
	$text = (string)$cls->clsSwScenarioLinesGetScenarioLines();
	return str_replace("<br>","\n",$text);
}
function fncInlineUpdateLines($mySqlConnObj){
	global	$fdtEditLinesId;
	if($fdtEditLinesId == ''){ return 'NG'; }
	$text = swFunc_GetPostData('fdtInlineText');
	$text = str_replace(array("\r\n","\r","\n"), "<br>", (string)$text);
	$cls = new clsSwScenarioLines();
	$cls->clsSwScenarioLinesInit($mySqlConnObj,$fdtEditLinesId);
	if($cls->clsSwScenarioLinesGetScenarioLinesId() == ''){ return 'NG'; }
	$cls->clsSwScenarioLinesSetScenarioLines($text);
	$cls->clsSwScenarioLinesDbUpdate($mySqlConnObj);
	return 'OK';
}
?>
<?php
// ------------------------------------------------------------------------------
//      ﾄﾞﾗｯｸﾞ&ﾄﾞﾛｯﾌﾟの並び替え（2026-09 追加）
//        lineIds = 画面に並んだ順の SCENARIO_LINES_ID（ｶﾝﾏ区切り）。
//        場面(fdtSceneId)・ｼﾅﾘｵ(fdtScenarioId)に属する行だけを対象に、並んだ順に 100,200,... を振り直す。
// ------------------------------------------------------------------------------
function fncReorderLines($mySqlConnObj){
	global	$fdtScenarioId,$fdtSceneId;
	$ids = array_filter(array_map('trim', explode(',', (string)swFunc_GetPostData('lineIds'))), 'strlen');
	if(count($ids) == 0 || $fdtScenarioId == '' || $fdtSceneId == ''){ return 'NG'; }
	//対象行の確認（このｼﾅﾘｵ・この場面の行だけ）
	$chk = $mySqlConnObj->prepare("SELECT SCENARIO_LINES_ID FROM SW_SCENARIO_LINES WHERE SCENARIO_ID = :ScenarioId AND SCENE_ID = :SceneId");
	$chk->bindParam(':ScenarioId', $fdtScenarioId, PDO::PARAM_INT);
	$chk->bindParam(':SceneId', $fdtSceneId, PDO::PARAM_INT);
	$chk->execute();
	$valid = array();
	while($r = $chk->fetch(PDO::FETCH_ASSOC)){ $valid[(string)$r['SCENARIO_LINES_ID']] = true; }
	$upd = $mySqlConnObj->prepare("UPDATE SW_SCENARIO_LINES SET SCENARIO_LINES_ORDER_NO = :OrderNo WHERE SCENARIO_LINES_ID = :LinesId");
	$orderNo = 0;
	foreach($ids as $id){
		if(!isset($valid[(string)$id])){ continue; }
		$orderNo += 100;
		$upd->bindValue(':OrderNo', $orderNo, PDO::PARAM_INT);
		$upd->bindValue(':LinesId', (int)$id, PDO::PARAM_INT);
		$upd->execute();
	}
	return 'OK';
}
?>
