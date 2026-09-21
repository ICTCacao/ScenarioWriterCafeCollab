<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
// 
//     SW_SCENARIO_LINES 読み込み
//
//     ajaxSwScenarioView.php
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
	global	$id,$loginId,$Submode;

	//POSTされた要素を取得
	$id = swFunc_GetPostData('id');         	 		//SCENARIO_ID
	$loginId = swFunc_GetPostData('loginId');	//LOGIN_ID
	
	$Submode = swFunc_GetPostData('Submode');	//Submode

}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	global	$id,$loginId,$Submode;
	//台詞を「」で囲むかのｵﾌﾟｼｮﾝを読む
	swFunc_LoadKagikakkoOption($mySqlConnObj,$id);
	$resultHtml = '';
	
	if($Submode == 'scene_index'){
		$resultHtml = fncSceneIndex($mySqlConnObj,$id,$loginId);
	}
	if($Submode == 'scenario_line'){
		$resultHtml = fncScenarioRead($mySqlConnObj,$id,$loginId);
	}
	
	
	// 出力charsetをUTF-8に指定
	mb_http_output ( 'UTF-8' );
	// 出力
	echo($resultHtml);
}//end function
//--------------------------------------------------------------------------------
//	場面INDEXを表示する
//--------------------------------------------------------------------------------
function fncSceneIndex($mySqlConnObj,$ScenarioId,$LoginId){
	//シナリオIDからシナリオ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwScenario.php");
	$clsSwScenario = new clsSwScenario();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScenario->clsSwScenarioInit($mySqlConnObj,$ScenarioId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$ScenarioTitle = $clsSwScenario->clsSwScenarioGetScenarioTitle();      //タイトル
	$ScenarioSubtitle = $clsSwScenario->clsSwScenarioGetScenarioSubtitle();//サブタイトル
	$ScenarioWriterName = $clsSwScenario->clsSwScenarioGetScenarioWriterName();//作者名
	//ｻﾆﾀｲｽﾞ
	$ScenarioTitle = swFunc_SanitizeStrings($ScenarioTitle);
	$ScenarioSubtitle = swFunc_SanitizeStrings($ScenarioSubtitle);
	$ScenarioWriterName = swFunc_SanitizeStrings($ScenarioWriterName);
	//HTMLを編集
	$retHtml = <<<END_OF_HTML

				<h4>$ScenarioTitle</h4>
				<h4>$ScenarioSubtitle</h4>
				<h5>脚本: {$ScenarioWriterName}</h5>
				<div>
					<button type="button" class="btn btn-outline-secondary w-100" aria-label="Left Align"
						onClick="fncReadScenario('$ScenarioId','$LoginId');">
						<span class="glyphicon glyphicon glyphicon-refresh" aria-hidden="true"></span>
						Refresh
					</button>
				</div>
				<hr />
END_OF_HTML;

	//場面を検索
	$strSceneSQL = <<<END_OF_SQL
	
		SELECT * FROM SW_SCENE
			WHERE SCENARIO_ID = :ScenarioId
					ORDER BY SCENE_ORDER_NO;
END_OF_SQL;
	$scene_stmt = $mySqlConnObj->prepare($strSceneSQL);
	$scene_stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$scene_stmt->bindParam(':ScenarioId', $ScenarioId, PDO::PARAM_INT);
	$scene_stmt->execute();
	while($mySceneRow = $scene_stmt -> fetch(PDO::FETCH_ASSOC)) {
		$SceneId = $mySceneRow['SCENE_ID'];
		$SceneName = $mySceneRow['SCENE_NAME'];

		$retHtml .= <<<END_OF_HTML
		
		<p><a href="#{$SceneId}">$SceneName</a></p>
END_OF_HTML;
	}
	
	//HTMLを返す
	return $retHtml;
}
//--------------------------------------------------------------------------------
//	シナリオを表示する
//--------------------------------------------------------------------------------
function fncScenarioRead($mySqlConnObj,$ScenarioId,$LoginId){
	global	$UserOptionStyleArray;
	

	//シナリオIDからシナリオ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwScenario.php");
	$clsSwScenario = new clsSwScenario();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScenario->clsSwScenarioInit($mySqlConnObj,$ScenarioId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$ScenarioTitle = $clsSwScenario->clsSwScenarioGetScenarioTitle();      //タイトル
	$ScenarioSubtitle = $clsSwScenario->clsSwScenarioGetScenarioSubtitle();//サブタイトル
	$ScenarioWriterName = $clsSwScenario->clsSwScenarioGetScenarioWriterName();//作者名
	//ｻﾆﾀｲｽﾞ
	$ScenarioTitle = swFunc_SanitizeStrings($ScenarioTitle);
	$ScenarioSubtitle = swFunc_SanitizeStrings($ScenarioSubtitle);
	$ScenarioWriterName = swFunc_SanitizeStrings($ScenarioWriterName);

	//ﾛｸﾞｲﾝ情報からﾕｰｻﾞ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ ﾛｸﾞｲﾝ情報
	include_once("../class/clsSwUserLoginInfo.php");
	$clsSwUserLoginInfo = new clsSwUserLoginInfo();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$LoginId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$UserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId(); //USER_ID
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwUserOption.php");
	$clsSwUserOption = new clsSwUserOption();
	//USER STYLEを配列化
	$UserOptionStyleArray = $clsSwUserOption->fncGetUserOptionStyle($mySqlConnObj,$UserId);

	//HTMLを編集
	$retHtml = <<<END_OF_HTML
	
		<div id="Scenario" class="scroll_area100">
END_OF_HTML;

	//場面を検索
	$strSceneSQL = <<<END_OF_SQL
	
		SELECT * FROM SW_SCENE
			WHERE SCENARIO_ID = :ScenarioId
					ORDER BY SCENE_ORDER_NO;
END_OF_SQL;
	$scene_stmt = $mySqlConnObj->prepare($strSceneSQL);
	$scene_stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$scene_stmt->bindParam(':ScenarioId', $ScenarioId, PDO::PARAM_INT);
	$scene_stmt->execute();
	while($mySceneRow = $scene_stmt -> fetch(PDO::FETCH_ASSOC)) {
		$SceneId = $mySceneRow['SCENE_ID'];
		$SceneName = $mySceneRow['SCENE_NAME'];
		//柱を表示
		$retHtml .= <<<END_OF_HTML

		<div id="{$SceneId}" class="col-sm-12 hashira">
			<div>
				$SceneName
			</div>
		</div>
END_OF_HTML;
		//場面のシナリオを検索
		$strScenarioSQL = <<<END_OF_SQL
			
				SELECT * FROM SW_SCENARIO_LINES
					WHERE SCENE_ID = '$SceneId'
							ORDER BY SCENARIO_LINES_ORDER_NO;
END_OF_SQL;
		$scenario_stmt = $mySqlConnObj->prepare($strScenarioSQL);
		$scenario_stmt->setFetchMode(PDO::FETCH_ASSOC);
		$scenario_stmt->execute();
		while($myScenarioRow = $scenario_stmt -> fetch(PDO::FETCH_ASSOC)) {
			$ScenarioLinesId = $myScenarioRow['SCENARIO_LINES_ID'];
			$ScenarioLinesOrderNo = $myScenarioRow['SCENARIO_LINES_ORDER_NO'];
			$ScenarioType = $myScenarioRow['SCENARIO_TYPE'];
			$CharacterId = $myScenarioRow['CHARACTER_ID'];
			$ScenarioLines = $myScenarioRow['SCENARIO_LINES'];
			//表に追加
			$retHtml .=  fncArrangeScenarioLine($mySqlConnObj,$ScenarioLinesId,$ScenarioType,$CharacterId,$ScenarioLines,$ScenarioLinesOrderNo);
		}//end while
	}//end while
	
		$retHtml .= <<<END_OF_HTML
			</div><!-- scroll area end -->
		</div>
		
END_OF_HTML;
	
	//HTMLを返す
	return $retHtml;
}

//--------------------------------------------------------------------------------
//	シナリオを揃える
//--------------------------------------------------------------------------------
function fncArrangeScenarioLine($mySqlConnObj,$ScenarioLinesId,$ScenarioType,$CharacterId,$ScenarioLines,$ScenarioLinesOrderNo){
	global	$UserOptionStyleArray;
	
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwCharacter.php");
	$clsSwCharacter = new clsSwCharacter();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwCharacter->clsSwCharacterInit($mySqlConnObj,$CharacterId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtCharacterName = $clsSwCharacter->clsSwCharacterGetCharacterName();      //登場人物名
	//サニタイズ
	$fdtCharacterName = swFunc_SanitizeStrings($fdtCharacterName);
	//最後が読点なら削除
	$ScenarioLines = swFunc_RemoveLastStr($ScenarioLines,'。');
	
	$ScenarioLines = str_replace("<br>","\n",$ScenarioLines);
	$ScenarioLines = swFunc_SanitizeStrings($ScenarioLines);
	$ScenarioLines = str_replace("\n","<br>",$ScenarioLines);
	
	$retHtml = <<<END_OF_HTML
	
					<div class="col-sm-12 scenario-line">
						<ul class="list-inline">
END_OF_HTML;

	//USER STYLEを反映させる
	if(isset($UserOptionStyleArray[$ScenarioType])){
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
					$ScenarioLines = swFunc_Kagikakko($ScenarioLines);
					$align = 'left';
				break;
			case '2'://登場人物名表示&台詞を「」で囲まない
					$fdtCharacterName = $fdtCharacterName.'&nbsp;'.$UserOptionStyleStr;
					$align = 'left';
				break;
			case '3'://登場人物名非表示&台詞を「」で囲む
					$fdtCharacterName = $UserOptionStyleStr;
					$StrLine = swFunc_Kagikakko($StrLine);
					$align = 'right';
				break;
			default://登場人物名非表示&台詞を「」で囲まない
					$fdtCharacterName = $UserOptionStyleStr;
					$align = 'right';
				break;
		}
		if($fdtCharacterName == ''){
			$fdtCharacterName = '&nbsp;';
			$align = 'right;color: #c0c6c9';
		}
		//CSS編集
		$cssCharacterName = 'style="line-height: 1.8em;font-size: '.$UserOptionStyleFontSize.'pt;text-align: '.$align.';"';
		$cssScenarioLines = 'style="line-height: 1.8em;font-size: '.$UserOptionStyleFontSize.'pt;color: '.$UserOptionStyleColor.';padding-left: '.$UserOptionStyleIndent.'em;"';
				$retHtml .= <<<END_OF_HTML
							
							<li class="col-sm-1">&nbsp;</li>
							<li class="col-sm-2" $cssCharacterName>$fdtCharacterName</li>
							<li class="col-sm-9" $cssScenarioLines>$ScenarioLines</li>
							
END_OF_HTML;
	}else{
		//ScenarioTypeで処理を分岐
		switch($ScenarioType){
			case '1':		//台詞
				$ScenarioLines = swFunc_Kagikakko($ScenarioLines);
				
				$retHtml .= <<<END_OF_HTML
				
							<li class="col-sm-2">&nbsp;</li>
							<li class="col-sm-2 chara text-start">$fdtCharacterName</li>
							<li class="col-sm-7 lines text-start">$ScenarioLines</li>
							<li class="col-sm-1">&nbsp;</li>
END_OF_HTML;
				break;
			case '2':		//ト書
				$retHtml .= <<<END_OF_HTML
				
							<li class="col-sm-2">&nbsp;</li>
							<li class="col-sm-2 chara text-center" style="color: #c0c6c9">&nbsp;</li>
							<li class="col-sm-7 scenario_togaki text-start">$ScenarioLines</li>
							<li class="col-sm-1">&nbsp;</li>
END_OF_HTML;
				break;
			case '3':		//歌詞
				$retHtml .= <<<END_OF_HTML
				
							<li class="col-sm-2">&nbsp;</li>
							<li class="col-sm-2 chara text-end">Song&nbsp;</li>
							<li class="col-sm-7 scenario_kasi text-start">$ScenarioLines</li>
							<li class="col-sm-1">&nbsp;</li>
END_OF_HTML;
				break;
			case '4':		//ナレーション
				$ScenarioLines = swFunc_Kagikakko($ScenarioLines);
				$retHtml .= <<<END_OF_HTML
				
							<li class="col-sm-2">&nbsp;</li>
							<li class="col-sm-2 chara text-end">$fdtCharacterName&nbsp;NA</li>
							<li class="col-sm-7 lines text-start">$ScenarioLines</li>
							<li class="col-sm-1">&nbsp;</li>
END_OF_HTML;
				break;
			case '5':		//モノローグ
				$ScenarioLines = swFunc_Kagikakko($ScenarioLines);
				$retHtml .= <<<END_OF_HTML
				
							<li class="col-sm-2">&nbsp;</li>
							<li class="col-sm-2 chara text-end">$fdtCharacterName&nbsp;M</li>
							<li class="col-sm-7 lines text-start">$ScenarioLines</li>
							<li class="col-sm-1">&nbsp;</li>
END_OF_HTML;
				break;
			case '6':		//テロップ
				$retHtml .= <<<END_OF_HTML
				
							<li class="col-sm-2">&nbsp;</li>
							<li class="col-sm-2 chara text-end">T&nbsp;</li>
							<li class="col-sm-7 scenario_kasi text-start">$ScenarioLines</li>
							<li class="col-sm-1">&nbsp;</li>
END_OF_HTML;
				break;
			case '7':		//演技指示
				$retHtml .= <<<END_OF_HTML
				
							<li class="col-sm-2">&nbsp;</li>
							<li class="col-sm-2 chara text-start" style="color: #c0c6c9">&nbsp;</li>
							<li class="col-sm-7 scenario_siji text-start">$ScenarioLines</li>
							<li class="col-sm-1">&nbsp;</li>
END_OF_HTML;
				break;
			case '8':		//音響指示
				$retHtml .= <<<END_OF_HTML
				
							<li class="col-sm-2">&nbsp;</li>
							<li class="col-sm-2 chara text-end">SE&nbsp;</li>
							<li class="col-sm-7 scenario_siji text-start">$ScenarioLines</li>
							<li class="col-sm-1">&nbsp;</li>
END_OF_HTML;
				break;
			case '9':		//照明指示
				$retHtml .= <<<END_OF_HTML
				
							<li class="col-sm-2">&nbsp;</li>
							<li class="col-sm-2 chara text-end">L&nbsp</li>
							<li class="col-sm-7 scenario_siji text-start">$ScenarioLines</li>
							<li class="col-sm-1">&nbsp;</li>
END_OF_HTML;
				break;
			default:
				break;
		}//end of switch
	}//end if
	
	$retHtml .= <<<END_OF_HTML
		
						</ul>
					</div>
END_OF_HTML;

	return	$retHtml;
}

// -----------------------------------------------------------
?>