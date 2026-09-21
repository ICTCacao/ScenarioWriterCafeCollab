<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//     シナリオをXMLでダウンロード（横書き）
//
//     swScenarioDownloadXMLYoko.php
// -----------------------------------------------------------
// ------------------------------------------------------------------------------
	//定数読み込み
	include_once("./sw_config/swConstant.php");
	//DB接続ｸﾗｽの初期化
	include_once("./include/ConnectMySQL.php");
// ------------------------------------------------------------------------------
	//処理時間無制限
	$ret = set_time_limit(0);

	//共通関数をｲﾝｸﾙｰﾄﾞ
	include_once("./include/swFunc.php");
	include_once("./include/swFuncScenarioXML.php");

	//ﾌｫｰﾑのﾃﾞｰﾀを読み込む
	fncGetPostItems($mySqlConnObj);

	//ｾｯｼｮﾝ管理
	include_once("./include/swAccept.php");
	include_once("./include/swCheckAdmin.php");
	//共同執筆: 権限ガード（閲覧）
	$swCollabNeed = 'read';
	include_once("./include/swCollabGuard.php");

	//MainProcedure
	fncMainProc($mySqlConnObj);
	//ﾌｧｲﾙ出力
	fncScenarioDownload();
	exit();

// ------------------------------------------------------------------------------
//      POSTされた要素を取得
//          fncGetPostItems($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncGetPostItems($mySqlConnObj){
	//共通global変数
	global	$fdtUserLoginId,$fdtUserLoginDate;
	global	$fdtScenarioId,$fdtUserName,$fdtScenarioTitle;

	global	$fdtCharacterCode,$fdtLineFeedCode;

	//POSTされた要素を取得
	$fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
	$fdtUserLoginDate = swFunc_GetPostData('fdtUserLoginDate');      //USERログイン日時
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');      //シナリオID

	$fdtCharacterCode = swFunc_GetPostData('fdtCharacterCode');    //文字コード
	$fdtLineFeedCode = swFunc_GetPostData('fdtLineFeedCode');      //改行コード

}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	global	$ScenarioXML,$ScenarioTitle;
	//共通global変数
	global	$fdtUserLoginId,$fdtUserLoginDate;
	global	$fdtScenarioId;
	//USER STYLE
	global	$UserOptionStyleArray;
	//シナリオのキャラクタ配列
	global	$ScenarioCharacterArray;
	global	$CharaAreaSpStr;

	global	$ScenarioXML;

	global	$fdtCharacterLength,$fdtBodyLength;

	//ﾛｸﾞｲﾝ情報からﾕｰｻﾞ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ ﾛｸﾞｲﾝ情報
	include_once("./class/clsSwUserLoginInfo.php");
	$clsSwUserLoginInfo = new clsSwUserLoginInfo();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$fdtUserLoginId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$UserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId(); //USER_ID
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("./class/clsSwUserOption.php");
	$clsSwUserOption = new clsSwUserOption();
	//USER STYLEを配列化
	$UserOptionStyleArray = $clsSwUserOption->fncGetUserOptionStyle($mySqlConnObj,$UserId);
	//ﾃﾞｰﾀ管理ｸﾗｽ ﾕｰｻﾞ情報
	include_once("./class/clsSwUserOptionSetting.php");
	$clsSwUserOptionSetting = new clsSwUserOptionSetting();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$UserOptionSettingCnt = $clsSwUserOptionSetting->clsSwUserOptionSettingInit($mySqlConnObj,$UserId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	if($UserOptionSettingCnt == '0'){
		$fdtCharacterLength = '12';
		$fdtBodyLength = '28';
	}else{
		$fdtCharacterLength = $clsSwUserOptionSetting->clsSwUserOptionSettingGetCharacterLength();  //キャラクタ部の文字数
		$fdtBodyLength = $clsSwUserOptionSetting->clsSwUserOptionSettingGetBodyLength();            //シナリオ本文の文字数
	}

	//登場人物欄の空白データ
	$CharaAreaSpStr = '';
	for($i=0; $i<$fdtCharacterLength; $i++){
		$CharaAreaSpStr .= "　";
	}

	//シナリオIDからシナリオ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("./class/clsSwScenario.php");
	$clsSwScenario = new clsSwScenario();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwScenario->clsSwScenarioInit($mySqlConnObj,$fdtScenarioId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$ScenarioTitle = $clsSwScenario->clsSwScenarioGetScenarioTitle();      //タイトル
	$ScenarioSubtitle = $clsSwScenario->clsSwScenarioGetScenarioSubtitle();//サブタイトル
	$ScenarioWriterName = $clsSwScenario->clsSwScenarioGetScenarioWriterName();//作者名
	//ｻﾆﾀｲｽﾞ
	//$ScenarioTitle = swFunc_SanitizeStrings($ScenarioTitle);
	//$ScenarioSubtitle = swFunc_SanitizeStrings($ScenarioSubtitle);
	//$ScenarioWriterName = swFunc_SanitizeStrings($ScenarioWriterName);


	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("./class/clsSwCharacter.php");
	$clsSwCharacter = new clsSwCharacter();
	//キャラクタをIDで連想配列にする
	$ScenarioCharacterArray = $clsSwCharacter->fncGetScenarioCharacter($mySqlConnObj,$fdtScenarioId);

	//XMLを編集
	$ScenarioXML = swFuncXMLHeader();

	//表紙
	$ScenarioWriterName = "脚本： {$ScenarioWriterName}";
	$Date = swFunc_SWHenkan(swFunc_GetNowDate());
	$Version = "1.0.0.5";
	$WriterId = "";
	$Address = "";
	$PhoneNo = "";
	$Email = isset($GLOBALS['fdtUserMailad']) ? (string)$GLOBALS['fdtUserMailad'] : "";   //ログインユーザーのメールアドレス
	$ScenarioXML .= swFuncXMLCover($ScenarioTitle,
																	$ScenarioSubtitle,
																	$Date,
																	$Version,
																	$ScenarioWriterName,
																	$WriterId,
																	$Address,
																	$PhoneNo,
																	$Email);
	//登場人物表
	$ScenarioXML .= swFuncXMLCharacterList($ScenarioTitle);
	//登場人物を検索
	$strCharacterSQL = <<<END_OF_SQL

		SELECT * FROM SW_CHARACTER
			WHERE SCENARIO_ID = :ScenarioId
					ORDER BY CHARACTER_ORDER_NO;
END_OF_SQL;
	$chara_stmt = $mySqlConnObj->prepare($strCharacterSQL);
	$chara_stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$chara_stmt->bindParam(':ScenarioId', $fdtScenarioId, PDO::PARAM_INT);
	$chara_stmt->execute();
	while($myCharaRow = $chara_stmt -> fetch(PDO::FETCH_ASSOC)) {
		$CharacterName = $myCharaRow['CHARACTER_NAME'];
		//サニタイズ
		//$CharacterName = swFunc_SanitizeStrings($CharacterName);
		$CharacterChara = $myCharaRow['CHARACTER_CHARA'];
		//<br>を改行にする
		$CharacterChara = str_replace("<br>","<w:br />",(string)$CharacterChara);   //PHP8.1: DBのNULLをそのまま渡すと Deprecated
		//キャラクター詳細
		if($CharacterChara == ''){$CharacterChara = $CharacterName;}

		$ScenarioXML .= swFuncXMLCharacter($CharacterName,$CharacterChara);
	}



	//シノプシス
	include_once("./class/clsSwSynopsis.php");
	$clsSwSynopsis = new clsSwSynopsis();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwSynopsis->clsSwSynopsisInitScenarioId($mySqlConnObj,$fdtScenarioId);
	$fdtSynopsis = $clsSwSynopsis->clsSwSynopsisGetSynopsis();                //SYNOPSIS
	$fdtSynopsis = str_replace("<br>","<w:br />",$fdtSynopsis);
	//XML書き出し
	$ScenarioXML .= swFuncXMLSynopsis($ScenarioTitle,$fdtSynopsis);


	//場面を検索
	$strSceneSQL = <<<END_OF_SQL

		SELECT * FROM SW_SCENE
			WHERE SCENARIO_ID = :ScenarioId
					ORDER BY SCENE_ORDER_NO;
END_OF_SQL;
	$scene_stmt = $mySqlConnObj->prepare($strSceneSQL);
	$scene_stmt->setFetchMode(PDO::FETCH_ASSOC);
	//パラメータのセット
	$scene_stmt->bindParam(':ScenarioId', $fdtScenarioId, PDO::PARAM_INT);
	$scene_stmt->execute();
	while($mySceneRow = $scene_stmt -> fetch(PDO::FETCH_ASSOC)) {
		$SceneId = $mySceneRow['SCENE_ID'];
		$SceneName = $mySceneRow['SCENE_NAME'];
		$SceneDescription = $mySceneRow['SCENE_DESCRIPTION'];
		//<br>を改行にする
		$SceneDescription = str_replace("<br>","<w:br />",(string)$SceneDescription);   //PHP8.1: DBのNULLをそのまま渡すと Deprecated
		//柱
		$ScenarioXML .= swFuncXMLScene($SceneName,$SceneDescription);

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
				//改行を置き換える
				$ScenarioLines = str_replace("<br>","<w:br />",$ScenarioLines);
				//サニタイズ
				//$ScenarioLines = swFunc_SanitizeStrings($ScenarioLines);

				//USER STYLEを反映させる
				if(isset($UserOptionStyleArray[$ScenarioType])){
					$StyleArray = explode(',',$UserOptionStyleArray[$ScenarioType]);
					$UserOptionStyleFontSize = '14';
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
								$ScenarioLines = ''.$ScenarioLines.'';
							break;
						case '2'://登場人物名表示&台詞を「」で囲まない
							break;
						case '3'://登場人物名非表示&台詞を「」で囲む
								$ScenarioLines = ''.$ScenarioLines.'';
							break;
						default://登場人物名非表示&台詞を「」で囲まない
							break;
					}
				}else{
					//ScenarioTypeで処理を分岐
					switch($ScenarioType){
						case '1':		//台詞
							$ScenarioLines = ''.$ScenarioLines.'';
							$UserOptionStyleIndent = 0;
							$UserOptionStyleStr = '';
							break;
						case '2':		//ト書
							$UserOptionStyleIndent = 3;
							$UserOptionStyleStr = '';
							break;
						case '3':		//歌詞
							$UserOptionStyleIndent = 3;
							$UserOptionStyleStr = '歌';
							break;
						case '4':		//ナレーション
							$ScenarioLines = ''.$ScenarioLines.'';
							$UserOptionStyleIndent = 0;
							$UserOptionStyleStr = 'NA';
							break;
						case '5':		//モノローグ
							$ScenarioLines = ''.$ScenarioLines.'';
							$UserOptionStyleIndent = 0;
							$UserOptionStyleStr = 'M';
							break;
						case '6':		//テロップ
							$UserOptionStyleIndent = 3;
							$UserOptionStyleStr = 'T';
							break;
						case '7':		//演技指示
							$UserOptionStyleIndent = 3;
							$UserOptionStyleStr = '';
							break;
						case '8':		//音響指示
							$UserOptionStyleIndent = 3;
							$UserOptionStyleStr = 'SE';
							break;
						case '9':		//照明指示
							$UserOptionStyleIndent = 3;
							$UserOptionStyleStr = 'L';
							break;
						default:
							break;
					}//end of switch
				}//end if

				//登場人物名を配列から取得
				if(isset($ScenarioCharacterArray[$CharacterId])){
					$CharacterName = $ScenarioCharacterArray[$CharacterId];
					//サニタイズ
					//$CharacterName = swFunc_SanitizeStrings($CharacterName);
				}else{
					$CharacterName = '';
				}
				//キャラクター名 半角文字を全角文字にする
				//$CharacterName = mb_convert_kana($CharacterName, 'KVRN');
				//$UserOptionStyleStr = mb_convert_kana($UserOptionStyleStr, 'KVRN');
				//キャラクター名部分
				if($CharacterName == ''){
					if($UserOptionStyleStr == ''){
						//空白
						$CharacterName = '';
					}else{
						//スタイル略語
						$CharacterName = $UserOptionStyleStr;
					}
				}else{
					if($UserOptionStyleStr == ''){
						//キャラクター名
						$CharacterName = $CharacterName;
					}else{
						//キャラクター名+スタイル略語
						$CharacterName = $CharacterName.'　'.$UserOptionStyleStr;
					}
				}
				$ScenarioXML .= swFuncXMLScenarioLine($CharacterName,$ScenarioLines);
		}//end while  ScenarioLines
	}//end while  Scene

	//body end
	$ScenarioXML .= swFuncXMLBodyEnd($ScenarioTitle);

}//end function

// ------------------------------------------------------------------------------
//      シナリオデータ　ダウンロード
//          fncScenarioDownload()
// ------------------------------------------------------------------------------
function fncScenarioDownload(){
	global	$fdtScenarioId;
	global	$ScenarioXML,$ScenarioTitle;
	global	$fdtCharacterCode,$fdtLineFeedCode;

	//文字コード
	if($fdtCharacterCode != 'UTF-8'){
		$ScenarioXML = mb_convert_encoding($ScenarioXML, $fdtCharacterCode, 'UTF-8');
	}
	//改行コード
	if($fdtLineFeedCode != "LF"){
		switch($fdtLineFeedCode){
			case 'LF':
				$fdtLineFeedCode = "\n";
				break;
			case 'CR':
				$fdtLineFeedCode = "\r";
				break;
			case 'CRLF':
				$fdtLineFeedCode = "\r\n";
				break;
			default:
				break;
		}//end of switch
		$ScenarioXML = str_replace("\n",$fdtLineFeedCode,$ScenarioXML);
	}

	//ﾌｧｲﾙ名
	$filename = $ScenarioTitle . ".xml";
	$filename = mb_convert_encoding($filename, 'Shift_JIS', 'UTF-8');

	// Zipクラスロード
	$zip = new ZipArchive();
	//Zipファイル名
	$zipFileName = 'swc'.$fdtScenarioId.'.zip';
	// Zipファイル一時保存ディレクトリ
	$zipTmpDir = './tmp/';
	if(!is_dir($zipTmpDir)){ mkdir($zipTmpDir, 0777, true); }   //一時ﾌｫﾙﾀﾞが無ければ作る
	// Zipファイルオープン
	$result = $zip->open($zipTmpDir.$zipFileName, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
	if($result === true){
		//Zipファイルに追加
		$zip->addFromString($filename, $ScenarioXML);
		$zip->close();

		//ダウンロードファイル名
		$DLfilename = $ScenarioTitle . ".zip";

		//ストリームに出力
		header('Content-Type: application/octet-stream');

		//ファイル名の文字コード変換
		if (preg_match('/\bMSIE\b|\bSafari [12345]\b/', getenv('HTTP_USER_AGENT'))) {
			header('Content-Disposition: attachment; filename="' .
				mb_convert_encoding($DLfilename, 'Shift_JIS', 'UTF-8') . '"');
		}else {
			header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($DLfilename));
		}
		//ファイルサイズ
		header('Content-Length: '.filesize($zipTmpDir.$zipFileName));

		//出力用バッファをクリア
		ob_end_clean();
		//出力（ダウンロード）
		readfile($zipTmpDir.$zipFileName);
		//一時保存ファイルを削除
		unlink($zipTmpDir.$zipFileName);
	}else{
		echo 'Error Code: ' . $res;
	}

}

// -----------------------------------------------------------
?>