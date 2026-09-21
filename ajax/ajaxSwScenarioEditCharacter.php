<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
// 
//     SW_CHARACTER I/O ｼｽﾃﾑ
//
//     ajaxSwScenarioEditCharacter.php
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
	include_once("../class/clsSwCharacter.php");
	$clsSwCharacter = new clsSwCharacter();

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
	global	$fdtScenarioId;

	//共通global変数
	global	$SubMode,$SubmitMode;
	//ﾌｫｰﾑ name要素をglobal変数にする
	global	$fdtCharacterId;
	global	$fdtScenarioId;
	global	$fdtCharacterOrderNo;
	global	$fdtCharacterName;
	global	$fdtCharacterChara;
	//編集中のID
	global	$SelectCharacterId;
	global	$tgtId;
	
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
	$fdtCharacterId = swFunc_GetPostData('fdtCharacterId');          //CHARACTER_ID
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //SCENARIO_ID
	$fdtCharacterOrderNo = swFunc_GetPostData('fdtCharacterOrderNo');//並び順
	$fdtCharacterName = swFunc_GetPostData('fdtCharacterName');      //登場人物名
	$fdtCharacterChara = swFunc_GetPostData('fdtCharacterChara');    //登場人物説明

	//編集中のID
	$SelectCharacterId = swFunc_GetPostData('SelectCharacterId');     //編集中のID
	$tgtId = swFunc_GetPostData('tgtId');     //移動対象のシナリオID
}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	global	$fdtUserLoginId;
	global	$fdtScenarioId;

	//共通global変数
	global	$SubMode,$SubmitMode;
	//ﾌｫｰﾑ name要素をglobal変数にする
	global	$fdtCharacterId;
	global	$fdtScenarioId;
	global	$fdtCharacterOrderNo;
	global	$fdtCharacterName;
	global	$fdtCharacterChara;

	// 出力をクリア
	$resultHtml = '';

	switch($SubMode){
		case 'ReadSwCharacterList':
			$resultHtml = fncReadSwCharacterList($mySqlConnObj,$fdtScenarioId);
			break;
		case 'MoveCharacterBefore':
			$resultHtml = fncMoveCharacterBefore($mySqlConnObj);
			break;
		case 'MoveCharacterAfter':
			$resultHtml = fncMoveCharacterAfter($mySqlConnObj);
			break;
		case 'NewCharacterInit':
			$resultHtml = fncNewCharacterInit($mySqlConnObj);
			break;
		case 'CloseNewCharacterEdit':
			$resultHtml = fncCloseNewCharacterEdit($mySqlConnObj);
			break;
		case 'CharacterEditSubmit':
			$resultHtml = fncCharacterEditSubmit($mySqlConnObj);
			break;
		case 'SetCharacterEditForm':
			$resultHtml = fncSetCharacterEditForm($mySqlConnObj);
			break;
		case 'CloseCharacterEditForm':
			$resultHtml = fncCloseCharacterEditForm($mySqlConnObj);
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
//		fncCharacterEditSubmit($mySqlConnObj)
//			登場人物更新
//--------------------------------------------------------------------------------
function fncCharacterEditSubmit($mySqlConnObj){
	//共通global変数
	global	$clsSwCharacter;
	global	$SubmitMode;
	//ﾌｫｰﾑ name要素をglobal変数にする
	global	$fdtCharacterId;
	global	$fdtScenarioId;
	global	$fdtCharacterOrderNo;
	global	$fdtCharacterName;
	global	$fdtCharacterChara;

	//SubmitModeで処理を分岐
	switch($SubmitMode){
		case 'INSERT':
			//登録処理
			fncSwCharacterDBInsert($mySqlConnObj,$clsSwCharacter)	;
			break;
		case 'UPDATE':
			//更新処理
			fncSwCharacterDBUpdate($mySqlConnObj,$clsSwCharacter);
			break;
		case 'DELETE':
			//削除処理
			fncSwCharacterDBDelete($mySqlConnObj,$clsSwCharacter);
			break;
		default:
			break;
	}
	return;
}
//--------------------------------------------------------------------------------
//	fncNewCharacterInit($mySqlConnObj)
//		新キャラ登録エリア
//--------------------------------------------------------------------------------
function fncNewCharacterInit($mySqlConnObj){
	global	$clsSwCharacter;
	global	$tgtId;
	global	$fdtCharacterId;
	global	$fdtScenarioId;
	global	$fdtCharacterOrderNo;
	global	$fdtCharacterName;
	global	$fdtCharacterChara;

	//HTMLを編集
	$retHtml = <<<END_OF_HTML

										<div class="row row-0">
											<label for="fdtCharacterOrderNo" class="col-form-label col-sm-2">並び順</label>
											<div class="col-sm-4">
END_OF_HTML;

	//------------------------------------------------------------
	//CSVﾃﾞｰﾀからselect box を作成する
	$ObjName = "fdtCharacterOrderNo";		//select box の名称.ID
	//ここでfunctionからCSVﾃﾞｰﾀを取得
	$csvArray = swFunc_MakeSelectItemsCsvCharacterOrderNo($mySqlConnObj,$fdtScenarioId);	//CSVﾃﾞｰﾀ
	$default = "$fdtCharacterOrderNo";		//ﾃﾞﾌｫﾙﾄ値
	$onChange = '';					//onChange で起動する javascript or jQuery
	$ViewCode = FALSE;				//ｺｰﾄﾞを表示する場合はTRUE
	//SelectBoxHtml出力
	$CharacterOrderNoSelectBox = swFunc_MakeSelectBox($ObjName,$csvArray,$default,$onChange,$ViewCode);
//------------------------------------------------------------

	$retHtml .= <<<END_OF_HTML

												$CharacterOrderNoSelectBox
											</div>
										</div>
										<div class="row row-0">
											<label for="fdtCharacterName" class="col-form-label col-sm-2">登場人物名</label>
											<div class="col-sm-4">
												<input type="text" 
													class="form-control form-control-sm"
													id="fdtCharacterName" name="fdtCharacterName"
													value="$fdtCharacterName"
													placeholder="登場人物名">
											</div>
										</div>
										<div class="row row-0">
											<label for="fdtCharacterChara" class="col-form-label col-sm-2">登場人物説明</label>
											<div class="col-sm-4">
												<textarea placeholder="登場人物説明" 
														class="form-control form-control-sm"
														id="fdtCharacterChara" name="fdtCharacterChara"
														rows="4"
														id="InputTextarea">$fdtCharacterChara</textarea>
											</div>
										</div>
										<legend class="d-flex flex-wrap align-items-center editmenu">
										<ul class="list-inline">
											<li>
												<input type="button" value="CLOSE"
													id="btnDelete"
													onclick="fncNewCharacterEditClose(frmSwScenario);"
													class="btn btn-success btn-sm">
											</li>
											<li>
												<input type="button" value="保　存"
													id="btnInsert"
													onclick="fncCharacterEditSubmit(frmSwScenario,'INSERT');"
													class="btn btn-success btn-sm">
											</li>
										</ul>
										</legend>

END_OF_HTML;
	
	return	$retHtml;
}
//--------------------------------------------------------------------------------
//		fncCloseCharacterEdit($mySqlConnObj)
//		新キャラ登録エリアCLOSE
//--------------------------------------------------------------------------------
function fncCloseNewCharacterEdit($mySqlConnObj){
	$retHtml = <<<END_OF_HTML
						<h4>登場人物の設定
						</h4>
END_OF_HTML;
	return	$retHtml;
}

//--------------------------------------------------------------------------------
//	fncSetCharacterEditForm($mySqlConnObj)
//		登場人物編集エリア
//--------------------------------------------------------------------------------
function fncSetCharacterEditForm($mySqlConnObj){
	global	$clsSwCharacter;
	global	$SelectCharacterId;
	
	global	$fdtCharacterId;
	global	$fdtScenarioId;
	global	$fdtCharacterOrderNo;
	global	$fdtCharacterName;
	global	$fdtCharacterChara;
	
	//並び順を整理する
	fncAdjustCharacterOrderNo($mySqlConnObj,$fdtScenarioId);
	
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwCharacter->clsSwCharacterInit($mySqlConnObj,$SelectCharacterId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	fncSwCharacterGetProperty($clsSwCharacter);

	$retHtml = <<<END_OF_HTML
									<div id="edit_character" class="col-sm-12">
										<div class="row row-0">
											<input type="hidden" name="fdtCharacterId" id="fdtCharacterId" value="$fdtCharacterId">
											<input type="hidden" name="fdtCharacterOrderNo" id="fdtCharacterOrderNo" value="$fdtCharacterOrderNo">
											<label for="fdtCharacterName" class="col-form-label col-sm-2">登場人物名</label>
											<div class="col-sm-4">
												<input type="text" 
													class="form-control form-control-sm"
													id="fdtCharacterName" name="fdtCharacterName"
													value="$fdtCharacterName"
													placeholder="登場人物名">
											</div>
										</div>
										<div class="row row-0">
											<label for="fdtCharacterChara" class="col-form-label col-sm-2">登場人物説明</label>
											<div class="col-sm-4">
												<textarea placeholder="登場人物説明" 
														class="form-control form-control-sm"
														id="fdtCharacterChara" name="fdtCharacterChara"
														rows="4"
														id="InputTextarea">$fdtCharacterChara</textarea>
											</div>
										</div>
										<legend class="d-flex flex-wrap align-items-center editmenu">
										<ul class="list-inline">
											<li>
												<input type="button" value="CLOSE"
													id="btnDelete"
													onclick="fncCharacterEditClose(frmSwScenario,'$SelectCharacterId');"
													class="btn btn-success btn-sm">
											</li>
											<li>
												<input type="button" value="保　存"
													id="btnInsert"
													onclick="fncCharacterEditSubmit(frmSwScenario,'UPDATE');"
													class="btn btn-warning btn-sm">
											</li>
											<li>
												<input type="button" value="削　除"
													id="btnInsert"
													onclick="fncCharacterEditSubmit(frmSwScenario,'DELETE');"
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
//	fncCloseCharacterEditForm($mySqlConnObj)
//		登場人物編集エリアCLOSE
//--------------------------------------------------------------------------------
function fncCloseCharacterEditForm($mySqlConnObj){
	global	$clsSwCharacter;
	global	$SelectCharacterId;
	
	global	$fdtCharacterId;
	global	$fdtScenarioId;
	global	$fdtCharacterOrderNo;
	global	$fdtCharacterName;
	global	$fdtCharacterChara;

	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwCharacter->clsSwCharacterInit($mySqlConnObj,$SelectCharacterId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	fncSwCharacterGetProperty($clsSwCharacter);

	$retHtml = <<<END_OF_HTML
							<span class="col-sm-2 text-start">
									<img src="./css/img/up.png" width="20" style="cursor: pointer;" onclick="fncMoveCharacterBefore(frmSwScenario,'$fdtCharacterId');">
									<img src="./css/img/down.png" width="20" style="cursor: pointer;" onclick="fncMoveCharacterAfter(frmSwScenario,'$fdtCharacterId');">
							</span>
							<span class="col-sm-2 text-start" style="cursor: pointer;"
									onclick="fncSelectCharacter(frmSwScenario,'$fdtCharacterId');">
									$fdtCharacterName
							</span>
							<span class="col-sm-8 text-start">
									$fdtCharacterChara
							</span>
END_OF_HTML;
	//HTMLを返す
	return $retHtml;

}

//--------------------------------------------------------------------------------
//	fncAdjustCharacterOrderNo($mySqlConnObj)
//		並び順を整理する
//--------------------------------------------------------------------------------
function fncAdjustCharacterOrderNo($mySqlConnObj,$fdtScenarioId){
	global	$clsSwCharacter;
	
	$clsSwCharacter->fncAdjustCharacterOrderNo($mySqlConnObj,$fdtScenarioId);
	
}

//--------------------------------------------------------------------------------
//	fncMoveCharacterBefore
//
//			一つ前に移動させる
//				fncMoveCharacterBefore($mySqlConnObj)
//--------------------------------------------------------------------------------
function fncMoveCharacterBefore($mySqlConnObj){
	global	$clsSwCharacter;
	global	$tgtId;
	global	$fdtCharacterId;
	global	$fdtScenarioId;
	global	$fdtCharacterOrderNo;
	global	$fdtCharacterName;
	global	$fdtCharacterChara;

	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwCharacter->clsSwCharacterInit($mySqlConnObj,$tgtId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtCharacterId = $clsSwCharacter->clsSwCharacterGetCharacterId();          //CHARACTER_ID
	$fdtScenarioId = $clsSwCharacter->clsSwCharacterGetScenarioId();            //SCENARIO_ID
	$fdtCharacterOrderNo = $clsSwCharacter->clsSwCharacterGetCharacterOrderNo();//並び順
	$fdtCharacterName = $clsSwCharacter->clsSwCharacterGetCharacterName();      //登場人物名
	//登場人物説明は<br>を\nに変換する
	$fdtCharacterChara = $clsSwCharacter->clsSwCharacterGetCharacterChara();    //登場人物説明
	$fdtCharacterChara = str_replace("<br>","\n",$fdtCharacterChara);
	
	//並び順が小さくて最大の2件をSELECT
	$strSQL = <<<END_OF_SQL
	
	SELECT * FROM SW_CHARACTER
		WHERE SCENARIO_ID = :ScenarioId
				AND  CHARACTER_ORDER_NO < :OrderNo
		ORDER BY CHARACTER_ORDER_NO DESC LIMIT 2;
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$stmt->bindParam(':ScenarioId', $fdtScenarioId, PDO::PARAM_INT);
	$stmt->bindParam(':OrderNo', $fdtCharacterOrderNo, PDO::PARAM_INT);
	$stmt->execute();
	//件数取得
	$myRowCnt = $stmt->rowCount();
	
	//件数で処理を分岐
	switch($myRowCnt){
		case '2':
			//最大LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax1_OrderNo = $myRow['CHARACTER_ORDER_NO'];
			//2番目LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax2_OrderNo = $myRow['CHARACTER_ORDER_NO'];
			break;
		case '1':
			//最大LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax1_OrderNo = $myRow['CHARACTER_ORDER_NO'];
			$BeforeMax2_OrderNo = 0;
			break;
		default:
			$BeforeMax1_OrderNo = 100;
			$BeforeMax2_OrderNo = 0;
			break;
	}
	
	//新しい並び順
	$move_num = round(($BeforeMax1_OrderNo - $BeforeMax2_OrderNo) / 2);
	$fdtCharacterOrderNo = $BeforeMax1_OrderNo - $move_num;
	
	//更新処理
	fncSwCharacterDBUpdate($mySqlConnObj,$clsSwCharacter);
	return;
}
//--------------------------------------------------------------------------------
//	fncMoveCharacterAfter
//
//			一つ前に移動させる
//				fncMoveCharacterAfter($mySqlConnObj)
//--------------------------------------------------------------------------------
function fncMoveCharacterAfter($mySqlConnObj){
	global	$clsSwCharacter;
	global	$tgtId;
	global	$fdtCharacterId;
	global	$fdtScenarioId;
	global	$fdtCharacterOrderNo;
	global	$fdtCharacterName;
	global	$fdtCharacterChara;

	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwCharacter->clsSwCharacterInit($mySqlConnObj,$tgtId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtCharacterId = $clsSwCharacter->clsSwCharacterGetCharacterId();          //CHARACTER_ID
	$fdtScenarioId = $clsSwCharacter->clsSwCharacterGetScenarioId();            //SCENARIO_ID
	$fdtCharacterOrderNo = $clsSwCharacter->clsSwCharacterGetCharacterOrderNo();//並び順
	$fdtCharacterName = $clsSwCharacter->clsSwCharacterGetCharacterName();      //登場人物名
	//登場人物説明は<br>を\nに変換する
	$fdtCharacterChara = $clsSwCharacter->clsSwCharacterGetCharacterChara();    //登場人物説明
	$fdtCharacterChara = str_replace("<br>","\n",$fdtCharacterChara);
	
	//並び順が小さくて最大の2件をSELECT
	$strSQL = <<<END_OF_SQL
	
	SELECT * FROM SW_CHARACTER
		WHERE SCENARIO_ID = :ScenarioId
				AND  CHARACTER_ORDER_NO > :OrderNo
		ORDER BY CHARACTER_ORDER_NO LIMIT 2;
END_OF_SQL;
	$stmt = $mySqlConnObj->prepare($strSQL);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$stmt->bindParam(':ScenarioId', $fdtScenarioId, PDO::PARAM_INT);
	$stmt->bindParam(':OrderNo', $fdtCharacterOrderNo, PDO::PARAM_INT);
	$stmt->execute();
	//件数取得
	$myRowCnt = $stmt->rowCount();
	
	//ScenarioTypeで処理を分岐
	switch($myRowCnt){
		case '2':
			//最小LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax1_OrderNo = $myRow['CHARACTER_ORDER_NO'];
			//2番目LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax2_OrderNo = $myRow['CHARACTER_ORDER_NO'];
			//新しい並び順
			$move_num = round(($BeforeMax2_OrderNo - $BeforeMax1_OrderNo) / 2);
			$fdtCharacterOrderNo = $BeforeMax1_OrderNo + $move_num;
			break;
		case '1':
			//最小LINE
			$myRow = $stmt -> fetch(PDO::FETCH_ASSOC);
			$BeforeMax1_OrderNo = $myRow['CHARACTER_ORDER_NO'];
			//新しい並び順
			$fdtScenarioLinesOrderNo = $BeforeMax1ScenarioLinesOrderNo + 100;
			break;
		default:
			return;
			break;
	}
	
	//更新処理
	fncSwCharacterDBUpdate($mySqlConnObj,$clsSwCharacter);
	return;
}

//--------------------------------------------------------------------------------
// SW_CHARACTER ALL LIST
//--------------------------------------------------------------------------------
function fncReadSwCharacterList($mySqlConnObj,$fdtScenarioId){
	global	$clsSwCharacter;
	global	$tgtId;
	global	$fdtCharacterId;
	global	$fdtScenarioId;
	global	$fdtCharacterOrderNo;
	global	$fdtCharacterName;
	global	$fdtCharacterChara;
	
	//並び順を整理する
	fncAdjustCharacterOrderNo($mySqlConnObj,$fdtScenarioId);
	
	//HTMLを編集
	$retHtml = <<<END_OF_HTML
	
		<div  id="Characters">
			<div class="row mb-3">
				<div class="col-sm-12">
					<!-- 新キャラ設定エリア -->
					<span id="edit_new">
						<h4>登場人物の設定
						</h4>
					</span>
				</div>
			</div>
			
			<div class="scroll_area">
			
			<div class="col-sm-12 eidt-chara-head">
				<span class="col-sm-2 text-start">
					並び順
				</span>
				<span class="col-sm-2 text-start">
						登場人物名
				</span>
				<span class="col-sm-8 text-start">
						人物設定
				</span>
			</div>
			
END_OF_HTML;
	//DB から ﾃﾞｰﾀを取得しﾌﾟﾛﾊﾟﾃｨにｾｯﾄする
	$strSQL = <<<END_OF_SQL

		SELECT * FROM SW_CHARACTER
			WHERE SCENARIO_ID = :ScenarioId
					ORDER BY CHARACTER_ORDER_NO;
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
			$CharacterId = $myRow['CHARACTER_ID'];
			$ScenarioId = $myRow['SCENARIO_ID'];
			$CharacterOrderNo = $myRow['CHARACTER_ORDER_NO'];
			$CharacterName = $myRow['CHARACTER_NAME'];
			$CharacterChara = $myRow['CHARACTER_CHARA'];
			
			//ｻﾆﾀｲｽﾞ
			$CharacterName = swFunc_SanitizeStrings($CharacterName);
			$CharacterChara = swFunc_SanitizeStrings($CharacterChara);
			//表に追加
			$retHtml .= <<<END_OF_HTML
			
						<div id="edit_{$CharacterId}" class="col-sm-12 eidt-chara">
							<span class="col-sm-2 text-start">
									<img src="./css/img/up.png" width="20" style="cursor: pointer;" onclick="fncMoveCharacterBefore(frmSwScenario,'$CharacterId');">
									<img src="./css/img/down.png" width="20" style="cursor: pointer;" onclick="fncMoveCharacterAfter(frmSwScenario,'$CharacterId');">
							</span>
							<span class="col-sm-2 text-start" style="cursor: pointer;"
									onclick="fncSelectCharacter(frmSwScenario,'$CharacterId');">
									$CharacterName
							</span>
							<span class="col-sm-8 text-start">
									$CharacterChara
							</span>
						</div>
END_OF_HTML;
	}//end while
	
	$retHtml .= <<<END_OF_HTML
		
		</div><!-- scroll_area end -->
		</div>
		
		<script type="text/javascript">
			$(document).ready(function() {
				$("#Characters").scroll(function(){
					var st = $("#Characters").scrollTop();
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
//          fncSwCharacterResetProperty($clsSwCharacter)
// ------------------------------------------------------------------------------
function fncSwCharacterResetProperty($clsSwCharacter){
	global	$fdtCharacterId;
	global	$fdtScenarioId;
	global	$fdtCharacterOrderNo;
	global	$fdtCharacterName;
	global	$fdtCharacterChara;

	//ﾌﾟﾛﾊﾟﾃｨReset
	$fdtCharacterId = "";                     //CHARACTER_ID
	//$fdtScenarioId = "";                      //SCENARIO_ID
	$fdtCharacterOrderNo = "";                //並び順
	$fdtCharacterName = "";                   //登場人物名
	$fdtCharacterChara = "";                  //登場人物説明
}//end function

// ------------------------------------------------------------------------------
//      ﾌﾟﾛﾊﾟﾃｨGet
//          fncSwCharacterGetProperty($clsSwCharacter)
// ------------------------------------------------------------------------------
function fncSwCharacterGetProperty($clsSwCharacter){
	global	$fdtCharacterId;
	global	$fdtScenarioId;
	global	$fdtCharacterOrderNo;
	global	$fdtCharacterName;
	global	$fdtCharacterChara;

	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtCharacterId = $clsSwCharacter->clsSwCharacterGetCharacterId();          //CHARACTER_ID
	//$fdtScenarioId = $clsSwCharacter->clsSwCharacterGetScenarioId();            //SCENARIO_ID
	$fdtCharacterOrderNo = $clsSwCharacter->clsSwCharacterGetCharacterOrderNo();//並び順
	$fdtCharacterName = $clsSwCharacter->clsSwCharacterGetCharacterName();      //登場人物名

	//登場人物説明は<br>を\nに変換する
	$fdtCharacterChara = $clsSwCharacter->clsSwCharacterGetCharacterChara();    //登場人物説明
	$fdtCharacterChara = str_replace("<br>","\n",$fdtCharacterChara);

}//end function

// ------------------------------------------------------------------------------
//      ﾌﾟﾛﾊﾟﾃｨSet
//          fncSwCharacterSetProperty($clsSwCharacter)
// ------------------------------------------------------------------------------
function fncSwCharacterSetProperty($clsSwCharacter){
	global	$fdtCharacterId;
	global	$fdtScenarioId;
	global	$fdtCharacterOrderNo;
	global	$fdtCharacterName;
	global	$fdtCharacterChara;

	//ﾌﾟﾛﾊﾟﾃｨSet
	$clsSwCharacter->clsSwCharacterSetCharacterId($fdtCharacterId);          //CHARACTER_ID
	$clsSwCharacter->clsSwCharacterSetScenarioId($fdtScenarioId);            //SCENARIO_ID
	$clsSwCharacter->clsSwCharacterSetCharacterOrderNo($fdtCharacterOrderNo);//並び順
	$clsSwCharacter->clsSwCharacterSetCharacterName($fdtCharacterName);      //登場人物名
	//登場人物説明は改行を<br>にしてからSetする
	$fdtCharacterChara = str_replace("\r\n","<br>",$fdtCharacterChara);
	$fdtCharacterChara = str_replace("\r","<br>",$fdtCharacterChara);
	$fdtCharacterChara = str_replace("\n","<br>",$fdtCharacterChara);
	$clsSwCharacter->clsSwCharacterSetCharacterChara($fdtCharacterChara);    //登場人物説明

}//end function

// ------------------------------------------------------------------------------
//      DB INSERT
//          fncSwCharacterDBInsert($mySqlConnObj,$clsSwCharacter)
// ------------------------------------------------------------------------------
function fncSwCharacterDBInsert($mySqlConnObj,$clsSwCharacter){
	global	$fdtCharacterId;

	//ﾌﾟﾛﾊﾟﾃｨｾｯﾄ
	fncSwCharacterSetProperty($clsSwCharacter);
	//ﾃﾞｰﾀ管理ｸﾗｽ DbInsert
	$fdtCharacterId = $clsSwCharacter->clsSwCharacterDbInsert($mySqlConnObj);
}//end function

// ------------------------------------------------------------------------------
//      DB UPDATE
//          fncSwCharacterDBUpdate($mySqlConnObj,$clsSwCharacter)
// ------------------------------------------------------------------------------
function fncSwCharacterDBUpdate($mySqlConnObj,$clsSwCharacter){
	//ﾌﾟﾛﾊﾟﾃｨｾｯﾄ
	fncSwCharacterSetProperty($clsSwCharacter);
	//ﾃﾞｰﾀ管理ｸﾗｽ DbUpdate
	$clsSwCharacter->clsSwCharacterDbUpdate($mySqlConnObj);
}//end function

// ------------------------------------------------------------------------------
//      DB DELETE
//          fncSwCharacterDBDelete($mySqlConnObj,$clsSwCharacter)
// ------------------------------------------------------------------------------
function fncSwCharacterDBDelete($mySqlConnObj,$clsSwCharacter){
	//ﾌﾟﾛﾊﾟﾃｨｾｯﾄ
	fncSwCharacterSetProperty($clsSwCharacter);
	//ﾃﾞｰﾀ管理ｸﾗｽ DbDelete
	$clsSwCharacter->clsSwCharacterDbDelete($mySqlConnObj);
}//end function
// -----------------------------------------------------------
?>