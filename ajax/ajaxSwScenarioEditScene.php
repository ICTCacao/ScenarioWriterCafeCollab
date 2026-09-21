<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//     SW_SCENE I/O ｼｽﾃﾑ
//
//     ajaxSwScenarioEditScene.php
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
	include_once("../class/clsSwScene.php");
	$clsSwScene = new clsSwScene();

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
	global	$fdtUserLoginId;
	//共通global変数
	global	$SubMode,$SubmitMode;
	//ﾌｫｰﾑ name要素をglobal変数にする
	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;
	global	$fdtSceneTimeMin;
	global	$fdtSceneTimeSec;
	//編集中のID
	global	$SelectSceneId;
	global	$tgtId;

	global $fdtMakuBeforeName;
	global $fdtMaku;
	global $fdtMakuAfterName;
	global $fdtBaBeforeName;
	global $fdtBaSt;
	global $fdtBaEd;
	global $fdtBaAfterName;


	//POSTされた要素を取得
	$fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //SCENARIO_ID

	//処理ﾓｰﾄが空白ならreturnするﾞ
	if(!isset($_POST['SubMode'])){return;}
	//処理ﾓｰﾄﾞ
	$SubMode = swFunc_GetPostData('SubMode');
	if($SubMode == ''){return;}
	$SubmitMode = swFunc_GetPostData('SubmitMode');
	//POSTされた要素を取得
	$fdtSceneId = swFunc_GetPostData('fdtSceneId');                  //SCENE_ID
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //SCENARIO_ID
	$fdtSceneOrderNo = swFunc_GetPostData('fdtSceneOrderNo');        //場面順番
	$fdtSceneValidCd = swFunc_GetPostData('fdtSceneValidCd');        //有効
	$fdtSceneName = swFunc_GetPostData('fdtSceneName');              //場面名称
	$fdtSceneDescription = swFunc_GetPostData('fdtSceneDescription');//場面説明
	$fdtSceneTimeMin = swFunc_GetPostData('fdtSceneTimeMin');					//場面時間（分）
	$fdtSceneTimeSec = swFunc_GetPostData('fdtSceneTimeSec');					//場面時間（秒）

	$fdtMakuBeforeName = swFunc_GetPostData('fdtMakuBeforeName');
	$fdtMaku = swFunc_GetPostData('fdtMaku');
	$fdtMakuAfterName = swFunc_GetPostData('fdtMakuAfterName');
	$fdtBaBeforeName = swFunc_GetPostData('fdtBaBeforeName');
	$fdtBaSt = swFunc_GetPostData('fdtBaSt');
	$fdtBaEd = swFunc_GetPostData('fdtBaEd');
	$fdtBaAfterName = swFunc_GetPostData('fdtBaAfterName');

	//編集中のID
	$SelectSceneId = swFunc_GetPostData('SelectSceneId');     //編集中のID
	$tgtId = swFunc_GetPostData('tgtId');     //移動対象のシナリオID
}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	global	$fdtUserLoginId;

	//共通global変数
	global	$SubMode,$SubmitMode;
	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;
	global	$fdtSceneTimeMin;
	global	$fdtSceneTimeSec;
	//編集中のID
	global	$SelectSceneId;

	// 出力をクリア
	$resultHtml = '';

	//SubModeで処理を制御
	switch($SubMode){
		case 'ReadSwSceneList':
			$resultHtml = fncReadSwSceneList($mySqlConnObj,$fdtScenarioId);
			break;
		case 'MoveSceneBefore':
			$resultHtml = fncMoveSceneBefore($mySqlConnObj);
			break;
		case 'MoveSceneAfter':
			$resultHtml = fncMoveSceneAfter($mySqlConnObj);
			break;
		case 'NewSceneInit':
			$resultHtml = fncNewSceneInit($mySqlConnObj);
			break;
		case 'CloseNewSceneEdit':
			$resultHtml = fncCloseNewSceneEdit($mySqlConnObj);
			break;
		case 'SceneEditSubmit':
			$resultHtml = fncSceneEditSubmit($mySqlConnObj);
			break;
		case 'SetSceneEditForm':
			$resultHtml = fncSetSceneEditForm($mySqlConnObj);
			break;
		case 'CloseSceneEditForm':
			$resultHtml = fncCloseSceneEditForm($mySqlConnObj);
			break;
		case 'GetSceneTime':
			$resultHtml = fncGetSceneTime($mySqlConnObj);
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
//		fncGetSceneTime($mySqlConnObj)
//			合計時間を取得
//--------------------------------------------------------------------------------
function fncGetSceneTime($mySqlConnObj){
	//ﾌｫｰﾑ name要素をglobal変数にする
	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;
	global	$fdtSceneTimeMin;
	global	$fdtSceneTimeSec;

	//DB から ﾃﾞｰﾀを取得しﾌﾟﾛﾊﾟﾃｨにｾｯﾄする
	$strSQL = <<<END_OF_SQL

		SELECT SUM(SCENE_TIME_MIN)		AS	SCENE_TIME_MIN
					,SUM(SCENE_TIME_SEC)		AS	SCENE_TIME_SEC
			FROM SW_SCENE
			WHERE SCENARIO_ID = :ScenarioId;
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$stmt->bindParam(':ScenarioId', $fdtScenarioId, PDO::PARAM_INT);
	$stmt->execute();
	//件数取得
	$myRowCnt = $stmt->rowCount();
	//リスト表示
	while($myRow = $stmt -> fetch(PDO::FETCH_ASSOC)) {
		$SceneTimeMin = $myRow['SCENE_TIME_MIN'];
		$SceneTimeSec = $myRow['SCENE_TIME_SEC'];
	}

	$total_sec = $SceneTimeMin * 60 + $SceneTimeSec;
	//時
	$hour = floor($total_sec / 3600);
	$min = floor(($total_sec / 60) % 60);
	$sec = $total_sec % 60;

	$hms = sprintf("%02d:%02d:%02d",$hour,$min,$sec);

	return	$hms;
}
//--------------------------------------------------------------------------------
//		fncSceneEditSubmit($mySqlConnObj)
//			場面更新
//--------------------------------------------------------------------------------
function fncSceneEditSubmit($mySqlConnObj){
	//共通global変数
	global	$clsSwScene;
	global	$SubmitMode;
	//ﾌｫｰﾑ name要素をglobal変数にする
	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;
	global	$fdtSceneTimeMin;
	global	$fdtSceneTimeSec;
	//編集中のID
	global	$SelectSceneId;

	//SubmitModeで処理を分岐
	switch($SubmitMode){
		case 'INSERT':
			//登録処理
			fncSwSceneDBInsert($mySqlConnObj,$clsSwScene);
			break;
		case 'MAKE_INSERT':
			//一括登録処理
			fncSwSceneMakeInsert($mySqlConnObj,$clsSwScene);
			break;
		case 'UPDATE':
			//更新処理
			fncSwSceneDBUpdate($mySqlConnObj,$clsSwScene);
			break;
		case 'DELETE':
			//削除処理
			fncSwSceneDBDelete($mySqlConnObj,$clsSwScene);
			break;
		default:
			break;
	}
	return;
}
//--------------------------------------------------------------------------------
//	fncNewSceneInit($mySqlConnObj)
//		新規場面登録エリア
//--------------------------------------------------------------------------------
function fncNewSceneInit($mySqlConnObj){
	global	$clsSwScene;
	global	$tgtId;
	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;
	global	$fdtSceneTimeMin;
	global	$fdtSceneTimeSec;
	//編集中のID
	global	$SelectSceneId;

	//HTMLを編集
	$retHtml = <<<END_OF_HTML

										<div class="row row-0">
											<label for="fdtSceneOrderNo" class="col-form-label col-sm-2">並び順</label>
											<div class="col-sm-4">
END_OF_HTML;

	//------------------------------------------------------------
	//CSVﾃﾞｰﾀからselect box を作成する
	$ObjName = "fdtSceneOrderNo";		//select box の名称.ID
	//ここでfunctionからCSVﾃﾞｰﾀを取得
	$csvArray = swFunc_MakeSelectItemsCsvSceneOrderNo($mySqlConnObj,$fdtScenarioId);	//CSVﾃﾞｰﾀ
	$default = "$fdtSceneOrderNo";		//ﾃﾞﾌｫﾙﾄ値
	$onChange = '';					//onChange で起動する javascript or jQuery
	$ViewCode = FALSE;				//ｺｰﾄﾞを表示する場合はTRUE
	//SelectBoxHtml出力
	$SceneOrderNoSelectBox = swFunc_MakeSelectBox($ObjName,$csvArray,$default,$onChange,$ViewCode);
	//------------------------------------------------------------
	//------------------------------------------------------------
	//CSVﾃﾞｰﾀからselect box を作成する
	$ObjName = "fdtSceneValidCd";		//select box の名称.ID
	//ここでfunctionからCSVﾃﾞｰﾀを取得
	$csvArray = swFunc_MakeSelectItemsCsvSceneValidCd();	//CSVﾃﾞｰﾀ
	$default = "$fdtSceneValidCd";		//ﾃﾞﾌｫﾙﾄ値
	$onChange = '';					//onChange で起動する javascript or jQuery
	$ViewCode = FALSE;				//ｺｰﾄﾞを表示する場合はTRUE
	//SelectBoxHtml出力
	$SceneValidCdSelectBox = swFunc_MakeSelectBox($ObjName,$csvArray,$default,$onChange,$ViewCode);
	//------------------------------------------------------------

	$retHtml .= <<<END_OF_HTML

												$SceneOrderNoSelectBox
											</div>
										</div>
										<div class="row row-0">
											<label for="fdtSceneName" class="col-form-label col-sm-2">場面名</label>
											<div class="col-sm-4">
												<input type="text"
													class="form-control form-control-sm"
													id="fdtSceneName" name="fdtSceneName"
													value="$fdtSceneName"
													placeholder="場面名">
											</div>
											<div class="col-sm-2">
												$SceneValidCdSelectBox
											</div>
										</div>
										<div class="row row-0">
											<label for="fdtSceneDescription" class="col-form-label col-sm-2">場面説明</label>
											<div class="col-sm-6">
												<textarea placeholder="場面説明"
														class="form-control form-control-sm"
														id="fdtSceneDescription" name="fdtSceneDescription"
														rows="4"
														id="InputTextarea">$fdtSceneDescription</textarea>
											</div>
										</div>

										<div class="row row-0>
											<div class="d-flex flex-wrap align-items-center">
												<label for="fdtSceneTimeMin" class="col-form-label col-sm-2">時　間</label>
												<div class="col-sm-6 d-flex flex-wrap align-items-center">
													<input type="text"
														class="form-control text-end"
														id="fdtSceneTimeMin" name="fdtSceneTimeMin"
														value="$fdtSceneTimeMin"
														placeholder="分" style="width: 70px;">
													<span style="margin: 0 5px;">分</span>
													<input type="text"
														class="form-control text-end"
														id="fdtSceneTimeSec" name="fdtSceneTimeSec"
														value="$fdtSceneTimeSec"
														placeholder="秒" style="width: 70px;">
														<span style="margin: 0 5px;">秒</span>
												</div>
											</div>
										</div>
END_OF_HTML;

	//ここでfunctionからCSVﾃﾞｰﾀを取得
	$csvArray = array();
	array_push($csvArray,"0:--");
	for( $i = 1 ; $i <= 50 ;$i++ ){
		$dd_value = $i;
		array_push($csvArray,$dd_value.":".$i);
	}
	$default = "1";		//ﾃﾞﾌｫﾙﾄ値
	$onChange = '';					//onChange で起動する javascript or jQuery
	$ViewCode = FALSE;				//ｺｰﾄﾞを表示する場合はTRUE
	//CSVﾃﾞｰﾀからselect box を作成する
	$ObjName = "fdtMaku";		//select box の名称.ID
	$MakuSelectBoxHtml = swFunc_MakeSelectBox($ObjName,$csvArray,$default,$onChange,$ViewCode);
	//CSVﾃﾞｰﾀからselect box を作成する
	$ObjName = "fdtBaSt";		//select box の名称.ID
	$BaStSelectBoxHtml = swFunc_MakeSelectBox($ObjName,$csvArray,$default,$onChange,$ViewCode);
	//CSVﾃﾞｰﾀからselect box を作成する
	$ObjName = "fdtBaEd";		//select box の名称.ID
	$default = "1";		//ﾃﾞﾌｫﾙﾄ値
	$BaEdSelectBoxHtml = swFunc_MakeSelectBox($ObjName,$csvArray,$default,$onChange,$ViewCode);

	$retHtml .= <<<END_OF_HTML

										<div class="row row-0">
											<label for="fdtSceneTimeMin" class="col-form-label col-sm-2">一括登録</label>
											<div class="col-sm-1">
												<input type="text"
													class="form-control form-control-sm text-end"
													id="fdtMakuBeforeName" name="fdtMakuBeforeName"
													value="">
											</div>
											<div class="col-sm-1 text-end">
												$MakuSelectBoxHtml
											</div>
											<div class="col-sm-1">
												<input type="text"
													class="form-control form-control-sm"
													id="fdtMakuAfterName" name="fdtMakuAfterName"
													value="幕">
											</div>
										</div>
										<div class="row row-0">
											<div class="col-sm-2">&nbsp;</div>
											<div class="col-sm-1">
												<input type="text"
													class="form-control form-control-sm text-end"
													id="fdtBaBeforeName" name="fdtBaBeforeName"
													value="">
											</div>
											<div class="col-sm-1 text-end">
												$BaStSelectBoxHtml
											</div>
											<div class="col-sm-1 text-center">
												～
											</div>
											<div class="col-sm-1 text-start">
												$BaEdSelectBoxHtml
											</div>
											<div class="col-sm-1">
												<input type="text"
													class="form-control form-control-sm"
													id="fdtBaAfterName" name="fdtBaAfterName"
													value="場">
											</div>
										</div>


										<legend class="d-flex flex-wrap align-items-center editmenu">
										<ul class="list-inline">
											<li>
												<input type="button" value="CLOSE"
													id="btnDelete"
													onclick="fncNewSceneEditClose(frmSwScenario);"
													class="btn btn-success btn-sm">
											</li>
											<li>
												<input type="button" value="追　　加"
													id="btnInsert"
													onclick="fncSceneEditSubmit(frmSwScenario,'INSERT');"
													class="btn btn-success btn-sm">
											</li>
											<li>
													<input type="button" value="一括追加"
														id="btnInsert"
														onclick="fncSceneEditSubmit(frmSwScenario,'MAKE_INSERT');"
														class="btn btn-warning btn-sm">
											</li>
										</ul>
										</legend>
END_OF_HTML;


	return	$retHtml;
}
//--------------------------------------------------------------------------------
//		fncCloseSceneEdit($mySqlConnObj)
//		新規場面登録エリアCLOSE
//--------------------------------------------------------------------------------
function fncCloseNewSceneEdit($mySqlConnObj){
	$retHtml = <<<END_OF_HTML
						<h4>場面の設定
						</h4>
END_OF_HTML;
	return	$retHtml;
}

//--------------------------------------------------------------------------------
//	fncSetSceneEditForm($mySqlConnObj)
//		場面編集エリア
//--------------------------------------------------------------------------------
function fncSetSceneEditForm($mySqlConnObj){
	global	$clsSwScene;
	global	$SelectSceneId;

	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;
	global	$fdtSceneTimeMin;
	global	$fdtSceneTimeSec;

	//並び順を整理する
	fncAdjustSceneOrderNo($mySqlConnObj,$fdtScenarioId);

	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScene->clsSwSceneInit($mySqlConnObj,$SelectSceneId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	fncSwSceneGetProperty($clsSwScene);
	//------------------------------------------------------------
	//CSVﾃﾞｰﾀからselect box を作成する
	$ObjName = "fdtSceneValidCd";		//select box の名称.ID
	//ここでfunctionからCSVﾃﾞｰﾀを取得
	$csvArray = swFunc_MakeSelectItemsCsvSceneValidCd();	//CSVﾃﾞｰﾀ
	$default = "$fdtSceneValidCd";		//ﾃﾞﾌｫﾙﾄ値
	$onChange = '';					//onChange で起動する javascript or jQuery
	$ViewCode = FALSE;				//ｺｰﾄﾞを表示する場合はTRUE
	//SelectBoxHtml出力
	$SceneValidCdSelectBox = swFunc_MakeSelectBox($ObjName,$csvArray,$default,$onChange,$ViewCode);
//------------------------------------------------------------

	$retHtml = <<<END_OF_HTML
									<div id="edit_scene" class="col-sm-12">
										<div class="row row-0">
											<input type="hidden" name="fdtSceneId" id="fdtSceneId" value="$fdtSceneId">
											<input type="hidden" name="fdtSceneOrderNo" id="fdtSceneOrderNo" value="$fdtSceneOrderNo">
											<label for="fdtSceneName" class="col-form-label col-sm-2">場面名</label>
											<div class="col-sm-4">
												<input type="text"
													class="form-control form-control-sm"
													id="fdtSceneName" name="fdtSceneName"
													value="$fdtSceneName"
													placeholder="場面名">
											</div>
											<div class="col-sm-2">
												$SceneValidCdSelectBox
											</div>
										</div>
										<div class="row row-0">
											<label for="fdtSceneDescription" class="col-form-label col-sm-2">場面説明</label>
											<div class="col-sm-6">
												<textarea placeholder="場面説明"
														class="form-control form-control-sm"
														id="fdtSceneDescription" name="fdtSceneDescription"
														rows="4"
														id="InputTextarea">$fdtSceneDescription</textarea>
											</div>
										</div>

										<div class="row row-0>
											<div class="d-flex flex-wrap align-items-center">
												<label for="fdtSceneTimeMin" class="col-form-label col-sm-2">時　間</label>
												<div class="col-sm-6 d-flex flex-wrap align-items-center">
													<input type="text"
														class="form-control text-end"
														id="fdtSceneTimeMin" name="fdtSceneTimeMin"
														value="$fdtSceneTimeMin"
														placeholder="分" style="width: 70px;">
													<span style="margin: 0 5px;">分</span>
													<input type="text"
														class="form-control text-end"
														id="fdtSceneTimeSec" name="fdtSceneTimeSec"
														value="$fdtSceneTimeSec"
														placeholder="秒" style="width: 70px;">
														<span style="margin: 0 5px;">秒</span>
												</div>
											</div>
										</div>

										<legend class="d-flex flex-wrap align-items-center editmenu">
										<ul class="list-inline">
											<li>
												<input type="button" value="CLOSE"
													id="btnDelete"
													onclick="fncSceneEditClose(frmSwScenario,'$SelectSceneId');"
													class="btn btn-success btn-sm">
											</li>
											<li>
												<input type="button" value="保　存"
													id="btnInsert"
													onclick="fncSceneEditSubmit(frmSwScenario,'UPDATE');"
													class="btn btn-warning btn-sm">
											</li>
											<li>
												<input type="button" value="削　除"
													id="btnInsert"
													onclick="fncSceneEditSubmit(frmSwScenario,'DELETE');"
													class="btn btn-danger btn-sm">
											</li>
										</ul>
										</legend>
								</div>
END_OF_HTML;

	//HTMLを返す
	return $retHtml;

}
//--------------------------------------------------------------------------------
//	fncCloseSceneEditForm($mySqlConnObj)
//		場面編集エリアCLOSE
//--------------------------------------------------------------------------------
function fncCloseSceneEditForm($mySqlConnObj){
	global	$clsSwScene;
	global	$SelectSceneId;

	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;
	global	$fdtSceneTimeMin;
	global	$fdtSceneTimeSec;

	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScene->clsSwSceneInit($mySqlConnObj,$SelectSceneId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	fncSwSceneGetProperty($clsSwScene);

	$retHtml = <<<END_OF_HTML
							<span class="col-sm-1 text-start">
									<img src="./css/img/up.png" width="20" style="cursor: pointer;" onclick="fncMoveSceneBefore(frmSwScenario,'$fdtSceneId');">
									<img src="./css/img/down.png" width="20" style="cursor: pointer;" onclick="fncMoveSceneAfter(frmSwScenario,'$fdtSceneId');">
							</span>
							<span class="col-sm-2 text-start" style="cursor: pointer;"
									onclick="fncSelectScene(frmSwScenario,'$fdtSceneId');">
									$fdtSceneName
							</span>
							<span class="col-sm-7 text-start">
									$fdtSceneDescription
							</span>
							<span class="col-sm-2 text-start">
									{$fdtSceneTimeMin}分{$fdtSceneTimeSec}秒
							</span>
END_OF_HTML;
	//HTMLを返す
	return $retHtml;

}

//--------------------------------------------------------------------------------
//	fncAdjustSceneOrderNo($mySqlConnObj)
//		並び順を整理する
//--------------------------------------------------------------------------------
function fncAdjustSceneOrderNo($mySqlConnObj,$fdtScenarioId){
	global	$clsSwScene;

	$clsSwScene->fncAdjustSceneOrderNo($mySqlConnObj,$fdtScenarioId);

}

//--------------------------------------------------------------------------------
//	fncMoveSceneBefore
//
//			一つ前に移動させる
//				fncMoveSceneBefore($mySqlConnObj)
//--------------------------------------------------------------------------------
function fncMoveSceneBefore($mySqlConnObj){
	global	$clsSwScene;
	global	$tgtId;
	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;

	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScene->clsSwSceneInit($mySqlConnObj,$tgtId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	fncSwSceneGetProperty($clsSwScene);

	//並び順が小さくて最大の2件をSELECT
	$strSQL = <<<END_OF_SQL

	SELECT * FROM SW_SCENE
		WHERE SCENARIO_ID = :ScenarioId
				AND  SCENE_ORDER_NO < :OrderNo
		ORDER BY SCENE_ORDER_NO DESC LIMIT 2;
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$stmt->bindParam(':ScenarioId', $fdtScenarioId, PDO::PARAM_INT);
	$stmt->bindParam(':OrderNo', $fdtSceneOrderNo, PDO::PARAM_INT);
	$stmt->execute();
	//件数取得
	$myRowCnt = $stmt->rowCount();

	//件数で処理を分岐
	switch($myRowCnt){
		case '2':
			//最大LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax1_OrderNo = $myRow['SCENE_ORDER_NO'];
			//2番目LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax2_OrderNo = $myRow['SCENE_ORDER_NO'];
			break;
		case '1':
			//最大LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax1_OrderNo = $myRow['SCENE_ORDER_NO'];
			$BeforeMax2_OrderNo = 0;
			break;
		default:
			$BeforeMax1_OrderNo = 100;
			$BeforeMax2_OrderNo = 0;
			break;
	}

	//新しい並び順
	$move_num = round(($BeforeMax1_OrderNo - $BeforeMax2_OrderNo) / 2);
	$fdtSceneOrderNo = $BeforeMax1_OrderNo - $move_num;

	//更新処理
	fncSwSceneDBUpdate($mySqlConnObj,$clsSwScene);
	return;
}
//--------------------------------------------------------------------------------
//	fncMoveSceneAfter
//
//			一つ前に移動させる
//				fncMoveSceneAfter($mySqlConnObj)
//--------------------------------------------------------------------------------
function fncMoveSceneAfter($mySqlConnObj){
	global	$clsSwScene;
	global	$tgtId;
	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;

	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScene->clsSwSceneInit($mySqlConnObj,$tgtId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	fncSwSceneGetProperty($clsSwScene);

	//並び順が小さくて最大の2件をSELECT
	$strSQL = <<<END_OF_SQL

	SELECT * FROM SW_SCENE
		WHERE SCENARIO_ID = :ScenarioId
				AND  SCENE_ORDER_NO > :OrderNo
		ORDER BY SCENE_ORDER_NO LIMIT 2;
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$stmt->bindParam(':ScenarioId', $fdtScenarioId, PDO::PARAM_INT);
	$stmt->bindParam(':OrderNo', $fdtSceneOrderNo, PDO::PARAM_INT);
	$stmt->execute();
	//件数取得
	$myRowCnt = $stmt->rowCount();

	//ScenarioTypeで処理を分岐
	switch($myRowCnt){
		case '2':
			//最小LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax1_OrderNo = $myRow['SCENE_ORDER_NO'];
			//2番目LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax2_OrderNo = $myRow['SCENE_ORDER_NO'];
			//新しい並び順
			$move_num = round(($BeforeMax2_OrderNo - $BeforeMax1_OrderNo) / 2);
			$fdtSceneOrderNo = $BeforeMax1_OrderNo + $move_num;
			break;
		case '1':
			//最小LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax1_OrderNo = $myRow['SCENE_ORDER_NO'];
			//新しい並び順
			$fdtScenarioLinesOrderNo = $BeforeMax1ScenarioLinesOrderNo + 100;
			break;
		default:
			return;
			break;
	}

	//更新処理
	fncSwSceneDBUpdate($mySqlConnObj,$clsSwScene);
	return;
}

//--------------------------------------------------------------------------------
// SW_SCENE ALL LIST
//--------------------------------------------------------------------------------
function fncReadSwSceneList($mySqlConnObj,$fdtScenarioId){
	global	$clsSwScene;
	global	$tgtId;
	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;
	global	$fdtSceneTimeMin;
	global	$fdtSceneTimeSec;

	//並び順を整理する
	fncAdjustSceneOrderNo($mySqlConnObj,$fdtScenarioId);

	//HTMLを編集
	$retHtml = <<<END_OF_HTML

		<div  id="Scenes">
			<div class="row mb-3">
				<div class="col-sm-12">
					<!-- 新規場面設定エリア -->
					<span id="edit_new">
						<h4>場面の設定
						</h4>
					</span>
				</div>
			</div>


		<div class="scroll_area">
			<div class="col-sm-12 eidt-chara-head">
				<span class="col-sm-1 text-start">
					並順
				</span>
				<span class="col-sm-3 text-start">
						場面名
				</span>
				<span class="col-sm-5 text-start">
						場面説明
				</span>
				<span class="col-sm-3 text-start">
						時間&nbsp;<span id="total_time"></span>
				</span>
			</div>


END_OF_HTML;
	//DB から ﾃﾞｰﾀを取得しﾌﾟﾛﾊﾟﾃｨにｾｯﾄする
	$strSQL = <<<END_OF_SQL

		SELECT * FROM SW_SCENE
			WHERE SCENARIO_ID = :ScenarioId
					ORDER BY SCENE_ORDER_NO;
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$stmt->bindParam(':ScenarioId', $fdtScenarioId, PDO::PARAM_INT);
	$stmt->execute();
	//件数取得
	$myRowCnt = $stmt->rowCount();
	//リスト表示
	while($myRow = $stmt -> fetch(PDO::FETCH_ASSOC)) {
		$SceneId = $myRow['SCENE_ID'];
		$ScenarioId = $myRow['SCENARIO_ID'];
		$SceneOrderNo = $myRow['SCENE_ORDER_NO'];
		$SceneValidCd = $myRow['SCENE_VALID_CD'];
		$SceneName = $myRow['SCENE_NAME'];
		$SceneDescription = $myRow['SCENE_DESCRIPTION'];
		$SceneDescription = str_replace("<br>","\n",$SceneDescription);
		$SceneTimeMin = $myRow['SCENE_TIME_MIN'];
		$SceneTimeSec = $myRow['SCENE_TIME_SEC'];
		//ｻﾆﾀｲｽﾞ
		$SceneName = swFunc_SanitizeStrings($SceneName);
		$SceneDescription = swFunc_SanitizeStrings($SceneDescription);

		//表に追加
		if($SceneValidCd){
			$css = 'eidt-scene-glay';
		}else{
			$css = 'eidt-scene';
		}
		//時間表示
		if($SceneTimeMin == '' and $SceneTimeSec == ''){
			$timeHtml = '';
		}else{
			if($SceneTimeMin == '0' and $SceneTimeSec == '0'){
			$timeHtml = '';
			}else{
				$timeHtml = $SceneTimeMin."分".$SceneTimeSec."秒";
			}
		}
		$retHtml .= <<<END_OF_HTML

						<div id="edit_{$SceneId}" class="col-sm-12 $css">
							<span class="col-sm-1 text-start">
									<img src="./css/img/up.png" width="20" style="cursor: pointer;" onclick="fncMoveSceneBefore(frmSwScenario,'$SceneId');">
									<img src="./css/img/down.png" width="20" style="cursor: pointer;" onclick="fncMoveSceneAfter(frmSwScenario,'$SceneId');">
							</span>
							<span class="col-sm-3 text-start" style="cursor: pointer;"
									onclick="fncSelectScene(frmSwScenario,'$SceneId');">
									$SceneName
							</span>
							<span class="col-sm-5 text-start">
									$SceneDescription
							</span>
							<span class="col-sm-3 text-start">
									$timeHtml
							</span>
						</div>
END_OF_HTML;
	}//end while

	$retHtml .= <<<END_OF_HTML

		</div><!-- scroll_area end -->
		</div>
		<script type="text/javascript">
			$(document).ready(function() {
				$("#Scenes").scroll(function(){
					var st = $("#Scenes").scrollTop();
					$("#listpos").text(st);
				});
			});
		</script>

END_OF_HTML;

	//HTMLを返す
	return $retHtml;
}

// ------------------------------------------------------------------------------
//      ﾌﾟﾛﾊﾟﾃｨReset
//          fncSwSceneResetProperty($clsSwScene)
// ------------------------------------------------------------------------------
function fncSwSceneResetProperty($clsSwScene){
	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;
	global	$fdtSceneTimeMin;
	global	$fdtSceneTimeSec;

	//ﾌﾟﾛﾊﾟﾃｨReset
	$fdtSceneId = "";                         //SCENE_ID
	//$fdtScenarioId = "";                      //SCENARIO_ID
	$fdtSceneOrderNo = "";                    //場面順番
	$fdtSceneValidCd = "";                    //有効
	$fdtSceneName = "";                       //場面名称
	$fdtSceneDescription = "";                //場面説明
	$fdtSceneTimeMin = "";                //時間（分）
	$fdtSceneTimeSec = "";                //時間（秒）
}//end function

// ------------------------------------------------------------------------------
//      ﾌﾟﾛﾊﾟﾃｨGet
//          fncSwSceneGetProperty($clsSwScene)
// ------------------------------------------------------------------------------
function fncSwSceneGetProperty($clsSwScene){
	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;
	global	$fdtSceneTimeMin;
	global	$fdtSceneTimeSec;

	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtSceneId = $clsSwScene->clsSwSceneGetSceneId();                  //SCENE_ID
	//$fdtScenarioId = $clsSwScene->clsSwSceneGetScenarioId();            //SCENARIO_ID
	$fdtSceneOrderNo = $clsSwScene->clsSwSceneGetSceneOrderNo();        //場面順番
	$fdtSceneValidCd = $clsSwScene->clsSwSceneGetSceneValidCd();        //有効
	$fdtSceneName = $clsSwScene->clsSwSceneGetSceneName();              //場面名称

	//場面説明は<br>を\nに変換する
	$fdtSceneDescription = $clsSwScene->clsSwSceneGetSceneDescription();//場面説明
	$fdtSceneDescription = str_replace("<br>","\n",$fdtSceneDescription);

	$fdtSceneTimeMin = $clsSwScene->clsSwSceneGetSceneTimeMin();              //時間（分）
	if($fdtSceneTimeMin == '0'){$fdtSceneTimeMin = '';}
	$fdtSceneTimeSec = $clsSwScene->clsSwSceneGetSceneTimeSec();              //時間（秒）
	if($fdtSceneTimeSec == '0'){$fdtSceneTimeSec = '';}

}//end function

// ------------------------------------------------------------------------------
//      ﾌﾟﾛﾊﾟﾃｨSet
//          fncSwSceneSetProperty($clsSwScene)
// ------------------------------------------------------------------------------
function fncSwSceneSetProperty($clsSwScene){
	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;
	global	$fdtSceneTimeMin;
	global	$fdtSceneTimeSec;

	//ﾌﾟﾛﾊﾟﾃｨSet
	$clsSwScene->clsSwSceneSetSceneId($fdtSceneId);                  //SCENE_ID
	$clsSwScene->clsSwSceneSetScenarioId($fdtScenarioId);            //SCENARIO_ID
	$clsSwScene->clsSwSceneSetSceneOrderNo($fdtSceneOrderNo);        //場面順番
	$clsSwScene->clsSwSceneSetSceneValidCd($fdtSceneValidCd);        //有効
	$clsSwScene->clsSwSceneSetSceneName($fdtSceneName);              //場面名称

	//場面説明は改行を<br>にしてからSetする
	$fdtSceneDescription = str_replace("\r\n","<br>",$fdtSceneDescription);
	$fdtSceneDescription = str_replace("\r","<br>",$fdtSceneDescription);
	$fdtSceneDescription = str_replace("\n","<br>",$fdtSceneDescription);
	$clsSwScene->clsSwSceneSetSceneDescription($fdtSceneDescription);//場面説明

	$clsSwScene->clsSwSceneSetSceneTimeMin($fdtSceneTimeMin);              //時間（分）
	$clsSwScene->clsSwSceneSetSceneTimeSec($fdtSceneTimeSec);              //時間（秒）

}//end function

// ------------------------------------------------------------------------------
//      DB INSERT
//          fncSwSceneDBInsert($mySqlConnObj,$clsSwScene)
// ------------------------------------------------------------------------------
function fncSwSceneDBInsert($mySqlConnObj,$clsSwScene){
	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;
	global	$fdtSceneTimeMin;
	global	$fdtSceneTimeSec;

	//ﾌﾟﾛﾊﾟﾃｨｾｯﾄ
	fncSwSceneSetProperty($clsSwScene);
	//ﾃﾞｰﾀ管理ｸﾗｽ DbInsert
	$fdtSceneId = $clsSwScene->clsSwSceneDbInsert($mySqlConnObj);

	//場面のシナリオを1件追加する
	global	$fdtScenarioLinesId;
	global	$fdtScenarioId;
	global	$fdtSceneId;
	global	$fdtScenarioLinesOrderNo;
	global	$fdtScenarioType;
	global	$fdtCharacterId;
	global	$fdtScenarioLines;

	global	$clsSwScenarioLines;

	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwScenarioLines.php");
	$clsSwScenarioLines = new clsSwScenarioLines();

	$fdtScenarioLines = $fdtSceneName;
	$fdtScenarioLinesOrderNo = 1;
	$fdtScenarioType = 4;
	$fdtCharacterId = -1;
	//登録処理
	fncSwScenarioLinesDBInsert($mySqlConnObj,$clsSwScenarioLines);



}//end function

// ------------------------------------------------------------------------------
//      DB UPDATE
//          fncSwSceneDBUpdate($mySqlConnObj,$clsSwScene)
// ------------------------------------------------------------------------------
function fncSwSceneDBUpdate($mySqlConnObj,$clsSwScene){
	//ﾌﾟﾛﾊﾟﾃｨｾｯﾄ
	fncSwSceneSetProperty($clsSwScene);
	//ﾃﾞｰﾀ管理ｸﾗｽ DbUpdate
	$clsSwScene->clsSwSceneDbUpdate($mySqlConnObj);
}//end function

// ------------------------------------------------------------------------------
//      DB DELETE
//          fncSwSceneDBDelete($mySqlConnObj,$clsSwScene)
// ------------------------------------------------------------------------------
function fncSwSceneDBDelete($mySqlConnObj,$clsSwScene){
	//ﾌﾟﾛﾊﾟﾃｨｾｯﾄ
	fncSwSceneSetProperty($clsSwScene);
	//ﾃﾞｰﾀ管理ｸﾗｽ DbDelete
	$clsSwScene->clsSwSceneDbDelete($mySqlConnObj);
}//end function

// ------------------------------------------------------------------------------
//      MAKE INSERT
//          fncSwSceneMakeInsert($mySqlConnObj,$clsSwScene)
// ------------------------------------------------------------------------------
function fncSwSceneMakeInsert($mySqlConnObj,$clsSwScene){
	//ﾌｫｰﾑ name要素をglobal変数にする
	global	$fdtSceneId;
	global	$fdtScenarioId;
	global	$fdtSceneOrderNo;
	global	$fdtSceneValidCd;
	global	$fdtSceneName;
	global	$fdtSceneDescription;
	global	$fdtSceneTimeMin;
	global	$fdtSceneTimeSec;

	global $fdtMakuBeforeName;
	global $fdtMaku;
	global $fdtMakuAfterName;
	global $fdtBaBeforeName;
	global $fdtBaSt;
	global $fdtBaEd;
	global $fdtBaAfterName;

	$orderNo = $fdtSceneOrderNo;
	for( $ba = $fdtBaSt ; $ba <= $fdtBaEd ;$ba++ ){
		//プロパティSet
		$clsSwScene->clsSwSceneSetScenarioId($fdtScenarioId);            //SCENARIO_ID
		$orderNo = $orderNo + 1;
		$fdtSceneOrderNo = $orderNo;//場面順番
		$clsSwScene->clsSwSceneSetSceneOrderNo($fdtSceneOrderNo);        //場面順番
		$fdtSceneValidCd = '0';//有効
		$clsSwScene->clsSwSceneSetSceneValidCd($fdtSceneValidCd);        //有効
		//場面名称組み立て
		$fdtSceneName = $fdtMakuBeforeName.$fdtMaku.$fdtMakuAfterName.$fdtBaBeforeName.$ba.$fdtBaAfterName;
		$clsSwScene->clsSwSceneSetSceneName($fdtSceneName);              //場面名称

		//ﾃﾞｰﾀ管理ｸﾗｽ DbInsert
		$fdtSceneId = $clsSwScene->clsSwSceneDbInsert($mySqlConnObj);
	}

	//ﾃﾞｰﾀ管理ｸﾗｽ 並び順更新
	$clsSwScene->fncAdjustSceneOrderNo($mySqlConnObj,$fdtScenarioId);
}


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

// -----------------------------------------------------------
?>