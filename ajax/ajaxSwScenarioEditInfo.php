<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
// 
//     SW_SCENARIO I/O ｼｽﾃﾑ
//
//     ajaxSwScenarioEditInfo.php
// -----------------------------------------------------------
// ------------------------------------------------------------------------------
	//定数読み込み
	include_once("../sw_config/swConstant.php");
	//DB接続ｸﾗｽの初期化
	include_once("../include/ConnectMySQL.php");
	//共同執筆: 権限ガード。削除・複写は作者のみ、それ以外（情報更新・画像）は共同執筆者も可
	$swCollabNeed = (isset($_POST['SubmitMode']) && in_array($_POST['SubmitMode'], array('DELETE', 'COPY'), true)) ? 'owner' : 'edit';
	include_once("../include/swCollabGuard.php");
// ------------------------------------------------------------------------------
	//共通関数をｲﾝｸﾙｰﾄﾞ
	include_once("../include/swFunc.php");

	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("../class/clsSwScenario.php");
	$clsSwScenario = new clsSwScenario();

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
	//共通global変数
	global	$SubmitMode;
	//ﾌｫｰﾑ name要素をglobal変数にする
	global	$fdtScenarioId;
	global	$fdtUserId;
	global	$fdtScenarioTitle;
	global	$fdtScenarioSubtitle;
	global	$fdtScenarioWriterName;
	global	$fdtScenarioMemo;
	global	$fdtScenarioDate;
	global	$fdtScenarioCategory;

	global	$fdtUserLoginId,$fdtUserLoginDate;



	//POSTされたログイン要素を取得
	$fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
	$fdtUserLoginDate = swFunc_GetPostData('fdtUserLoginDate');      //USERログイン日時
	//ﾃﾞｰﾀ管理ｸﾗｽ ﾛｸﾞｲﾝ情報
	include_once("../class/clsSwUserLoginInfo.php");
	$clsSwUserLoginInfo = new clsSwUserLoginInfo();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$fdtUserLoginId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtUserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId();                    //USER_ID
	//共同執筆: 共同執筆者が情報更新しても作者(SW_SCENARIO.USER_ID)は変えない
	if (isset($_POST['fdtScenarioId']) && (string)$_POST['fdtScenarioId'] !== '') {
		$swOwner = swCollab_OwnerId($mySqlConnObj, (int)str_replace(',', '', (string)$_POST['fdtScenarioId']));
		if ($swOwner > 0) { $fdtUserId = $swOwner; }
	}

	//処理ﾓｰﾄが空白ならreturnするﾞ
	if(!isset($_POST['SubmitMode'])){return;}
	//処理ﾓｰﾄﾞ
	$SubmitMode = swFunc_GetPostData('SubmitMode');
	if($SubmitMode == ''){return;}
	//POSTされた要素を取得
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //シナリオID
	//$fdtUserId = swFunc_GetPostData('fdtUserId');                    //USER_ID
	$fdtScenarioTitle = swFunc_GetPostData('fdtScenarioTitle');      //タイトル
	$fdtScenarioSubtitle = swFunc_GetPostData('fdtScenarioSubtitle');//サブタイトル
	$fdtScenarioWriterName = swFunc_GetPostData('fdtScenarioWriterName');//作者名
	$fdtScenarioMemo = swFunc_GetPostData('fdtScenarioMemo');        //メモ
	$fdtScenarioDate = swFunc_GetPostData('fdtScenarioDate');        //執筆日
	$fdtScenarioCategory = swFunc_GetPostData('fdtScenarioCategory');//分類


	//画像サイズを変更してアップロードする
	if (isset($_FILES['upfile']['error']) && is_array($_FILES['upfile']['error'])) {
    // 各ファイルをチェック
    foreach ($_FILES['upfile']['error'] as $k => $error) {
        try {
            // 更に配列がネストしていれば不正とする
            if (!is_int($error)) {
                throw new RuntimeException("[{$k}] パラメータが不正です");
            }
            // $_FILES['upfile']['error'][$k] の値を確認
            switch ($error) {
                case UPLOAD_ERR_OK: // OK
                    break;
                case UPLOAD_ERR_NO_FILE:   // ファイル未選択
                    continue 2;
                case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
                case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過
                    //throw new RuntimeException("[{$k}] ファイルサイズが大きすぎます");
                    fncErrorExit("ファイルサイズが大きすぎます");
                    
                default:
                    //throw new RuntimeException("[{$k}] その他のエラーが発生しました");
                    fncErrorExit("その他のエラーが発生しました");
            }
            // $_FILES['upfile']['mime']の値はブラウザ側で偽装可能なので
            // MIMEタイプを自前でチェックする
            if (!$info = @getimagesize($_FILES['upfile']['tmp_name'][$k])) {
                //throw new RuntimeException("[{$k}] 有効な画像ファイルを指定してください");
                fncErrorExit("有効な画像ファイルを指定してください");
            }
            if (!in_array($info[2], [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
                //throw new RuntimeException("[{$k}] 未対応の画像形式です");
                fncErrorExit("未対応の画像形式です");
            }
						//アップロードされたファイルがあれば削除する
						$dir = '../img/thumb/';
						$handle = opendir($dir);
						while($fname = readdir($handle)){
							if(is_file($dir.$fname)){
								$arr = explode('.',$fname);
								
								if($arr[0] == $fdtScenarioId){
									unlink($dir.$fname);
								}
							}
						}
						
            // 画像処理に使う関数名を決定する
            $create = str_replace('/', 'createfrom', $info['mime']);
            $output = str_replace('/', '', $info['mime']);
            // 縦横比を維持したまま 250 * 250 以下に収まるサイズを求める
            //if ($info[0] >= $info[1]) {
                $dst_w = 250;
                $dst_h = ceil(250 * $info[1] / max($info[0], 1));
            //} else {
            //    $dst_w = ceil(250 * $info[0] / max($info[1], 1));
            //    $dst_h = 250;
            //}
            // 元画像リソースを生成する
            if (!$src = @$create($_FILES['upfile']['tmp_name'][$k])) {
                //throw new RuntimeException("[{$k}] 画像リソースの生成に失敗しました");
                fncErrorExit("画像リソースの生成に失敗しました");
            }
            // リサンプリング先画像リソースを生成する
            $dst = imagecreatetruecolor($dst_w, $dst_h);
            // getimagesize関数で得られた情報も利用してリサンプリングを行う
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $dst_w, $dst_h, $info[0], $info[1]);
            // ファイルデータからSHA-1ハッシュを取ってファイル名を決定し、保存する
            if (!$output(
                $dst,
                sprintf('../img/thumb/%s%s',
                    //sha1_file($_FILES['upfile']['tmp_name'][$k]),
                    $fdtScenarioId,
                    image_type_to_extension($info[2])
                )
            )) {
                //throw new RuntimeException("[{$k}] ファイル保存時にエラーが発生しました");
                fncErrorExit("ファイル保存時にエラーが発生しました");
            }
            $msgs[] = ['green', "[{$k}] リサイズして保存しました"];
        } catch (RuntimeException $e) {
            $msgs[] = ['red', $e->getMessage()];
        }
        // リソースを解放
        if (isset($msg) && is_resource($img)) {
            imagedestroy($img);
        }
        if (isset($dst) && is_resource($dst)) {
            imagedestroy($dst);
        }
    }

	}
}//end function


// ------------------------------------------------------------------------------
//      エラーで処理を中断する
//          fncErrorExit($mes)
// ------------------------------------------------------------------------------
function fncErrorExit($mes){
	// 出力charsetをUTF-8に指定
	mb_http_output ( 'UTF-8' );
	echo("$mes");
	exit();
}
// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	global	$clsSwScenario;
	global	$SubmitMode;
	//global 変数
	global	$fdtScenarioId;
	global	$fdtUserId;
	global	$fdtScenarioTitle;
	global	$fdtScenarioSubtitle;
	global	$fdtScenarioWriterName;
	global	$fdtScenarioMemo;
	global	$fdtScenarioDate;
	global	$fdtScenarioCategory;

	// 出力をクリア
	$resultHtml = '';

	//SubmitModeで処理を分岐
	switch($SubmitMode ){
		case 'UPDATE':
			//更新処理
			$resultHtml = fncSwScenarioDBUpdate($mySqlConnObj,$clsSwScenario);
			//$resultHtml = '';
			break;
		case 'DELETE':
			//削除処理
			fncSwScenarioDBDelete($mySqlConnObj,$clsSwScenario);
			//共同執筆: メンバー・招待・履歴・在席も消す
			swCollab_PurgeScenario($mySqlConnObj, (int)str_replace(',', '', (string)$fdtScenarioId));
			$resultHtml = 'DELETE';
			break;
		case 'COPY':
			//削除処理
			$resultHtml =fncSwScenarioDBCopy($mySqlConnObj,$clsSwScenario);

			break;
		default:
			break;
	}

	// 出力charsetをUTF-8に指定
	mb_http_output ( 'UTF-8' );
	// 出力
	echo($resultHtml);
}//end function

// ------------------------------------------------------------------------------
//      ﾌﾟﾛﾊﾟﾃｨReset
//          fncSwScenarioResetProperty($clsSwScenario)
// ------------------------------------------------------------------------------
function fncSwScenarioResetProperty($clsSwScenario){
	global	$fdtScenarioId;
	global	$fdtUserId;
	global	$fdtScenarioTitle;
	global	$fdtScenarioSubtitle;
	global	$fdtScenarioWriterName;
	global	$fdtScenarioMemo;
	global	$fdtScenarioDate;
	global	$fdtScenarioCategory;

	//ﾌﾟﾛﾊﾟﾃｨReset
	$fdtScenarioId = "";                      //シナリオID
	$fdtUserId = "";                          //USER_ID
	$fdtScenarioTitle = "";                   //タイトル
	$fdtScenarioSubtitle = "";                //サブタイトル
	$fdtScenarioWriterName = "";              //作者名
	$fdtScenarioMemo = "";                    //メモ
	$fdtScenarioDate = "";                    //執筆日
	$fdtScenarioCategory = "";				//分類
}//end function

// ------------------------------------------------------------------------------
//      ﾌﾟﾛﾊﾟﾃｨGet
//          fncSwScenarioGetProperty($clsSwScenario)
// ------------------------------------------------------------------------------
function fncSwScenarioGetProperty($clsSwScenario){
	global	$fdtScenarioId;
	global	$fdtUserId;
	global	$fdtScenarioTitle;
	global	$fdtScenarioSubtitle;
	global	$fdtScenarioWriterName;
	global	$fdtScenarioMemo;
	global	$fdtScenarioDate;
	global	$fdtScenarioCategory;

	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtScenarioId = $clsSwScenario->clsSwScenarioGetScenarioId();            //シナリオID
	$fdtUserId = $clsSwScenario->clsSwScenarioGetUserId();                    //USER_ID
	$fdtScenarioTitle = $clsSwScenario->clsSwScenarioGetScenarioTitle();      //タイトル
	$fdtScenarioSubtitle = $clsSwScenario->clsSwScenarioGetScenarioSubtitle();//サブタイトル
	$fdtScenarioWriterName = $clsSwScenario->clsSwScenarioGetScenarioWriterName();//作者名

	//メモは<br>を\nに変換する
	$fdtScenarioMemo = $clsSwScenario->clsSwScenarioGetScenarioMemo();        //メモ
	$fdtScenarioMemo = str_replace("<br>","\n",$fdtScenarioMemo);

	$fdtScenarioDate = $clsSwScenario->clsSwScenarioGetScenarioDate();        //執筆日
	$fdtScenarioCategory = $clsSwScenario->clsSwScenarioGetScenarioCategory();//分類
}//end function

// ------------------------------------------------------------------------------
//      ﾌﾟﾛﾊﾟﾃｨSet
//          fncSwScenarioSetProperty($clsSwScenario)
// ------------------------------------------------------------------------------
function fncSwScenarioSetProperty($clsSwScenario){
	global	$fdtScenarioId;
	global	$fdtUserId;
	global	$fdtScenarioTitle;
	global	$fdtScenarioSubtitle;
	global	$fdtScenarioWriterName;
	global	$fdtScenarioMemo;
	global	$fdtScenarioDate;
	global	$fdtScenarioCategory;

	//ﾌﾟﾛﾊﾟﾃｨSet

	//シナリオIDはｶﾝﾏを取り除いてからSetする
	$fdtScenarioId = str_replace(",","",$fdtScenarioId);
	$clsSwScenario->clsSwScenarioSetScenarioId($fdtScenarioId);            //シナリオID


	//USER_IDはｶﾝﾏを取り除いてからSetする
	$fdtUserId = str_replace(",","",$fdtUserId);
	$clsSwScenario->clsSwScenarioSetUserId($fdtUserId);                    //USER_ID

	$clsSwScenario->clsSwScenarioSetScenarioTitle($fdtScenarioTitle);      //タイトル
	$clsSwScenario->clsSwScenarioSetScenarioSubtitle($fdtScenarioSubtitle);//サブタイトル
	$clsSwScenario->clsSwScenarioSetScenarioWriterName($fdtScenarioWriterName);//作者名

	//メモは改行を<br>にしてからSetする
	$fdtScenarioMemo = str_replace("\r\n","<br>",$fdtScenarioMemo);
	$fdtScenarioMemo = str_replace("\r","<br>",$fdtScenarioMemo);
	$fdtScenarioMemo = str_replace("\n","<br>",$fdtScenarioMemo);
	$clsSwScenario->clsSwScenarioSetScenarioMemo($fdtScenarioMemo);        //メモ

	$clsSwScenario->clsSwScenarioSetScenarioDate($fdtScenarioDate);        //執筆日
	$clsSwScenario->clsSwScenarioSetScenarioCategory($fdtScenarioCategory);        //分類
}//end function

// ------------------------------------------------------------------------------
//      DB INSERT
//          fncSwScenarioDBInsert($mySqlConnObj,$clsSwScenario)
// ------------------------------------------------------------------------------
function fncSwScenarioDBInsert($mySqlConnObj,$clsSwScenario){
	global	$fdtScenarioId;

	//ﾌﾟﾛﾊﾟﾃｨｾｯﾄ
	fncSwScenarioSetProperty($clsSwScenario);
	//ﾃﾞｰﾀ管理ｸﾗｽ DbInsert
	$fdtScenarioId = $clsSwScenario->clsSwScenarioDbInsert($mySqlConnObj);
}//end function

// ------------------------------------------------------------------------------
//      DB UPDATE
//          fncSwScenarioDBUpdate($mySqlConnObj,$clsSwScenario)
// ------------------------------------------------------------------------------
function fncSwScenarioDBUpdate($mySqlConnObj,$clsSwScenario){
	//ﾌﾟﾛﾊﾟﾃｨｾｯﾄ
	fncSwScenarioSetProperty($clsSwScenario);
	//ﾃﾞｰﾀ管理ｸﾗｽ DbUpdate
	$clsSwScenario->clsSwScenarioDbUpdate($mySqlConnObj);
}//end function

// ------------------------------------------------------------------------------
//      DB DELETE
//          fncSwScenarioDBDelete($mySqlConnObj,$clsSwScenario)
// ------------------------------------------------------------------------------
function fncSwScenarioDBDelete($mySqlConnObj,$clsSwScenario){
	//ﾌﾟﾛﾊﾟﾃｨｾｯﾄ
	fncSwScenarioSetProperty($clsSwScenario);
	//ﾃﾞｰﾀ管理ｸﾗｽ DbDelete
	$clsSwScenario->clsSwScenarioDbDelete($mySqlConnObj);
}//end function
// ------------------------------------------------------------------------------
//      DB COPY
//          fncSwScenarioDBCopy($mySqlConnObj,$clsSwScenario)
// ------------------------------------------------------------------------------
function fncSwScenarioDBCopy($mySqlConnObj,$clsSwScenario){
	//ﾌﾟﾛﾊﾟﾃｨｾｯﾄ
	fncSwScenarioSetProperty($clsSwScenario);
	//ﾃﾞｰﾀ管理ｸﾗｽ DbDelete
	$retHtml = $clsSwScenario->clsSwScenarioDbCopy($mySqlConnObj);
	return	$retHtml;
}//end function
