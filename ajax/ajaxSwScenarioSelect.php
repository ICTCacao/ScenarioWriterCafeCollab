<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
// 
//     SW_SCENARIO I/O ｼｽﾃﾑ
//
//     ajaxSwScenario.php
// -----------------------------------------------------------
// ------------------------------------------------------------------------------
	//定数読み込み
	include_once("../sw_config/swConstant.php");
	//DB接続ｸﾗｽの初期化
	include_once("../include/ConnectMySQL.php");
// ------------------------------------------------------------------------------
	//共通関数をｲﾝｸﾙｰﾄﾞ
	include_once("../include/swFunc.php");
	//共同執筆
	include_once("../include/swCollab.php");
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
	global	$fdtScenarioId;
	global	$fdtUserId;
	global	$fdtScenarioTitle;
	global	$fdtScenarioSubtitle;
	global	$fdtScenarioWriterName;
	global	$fdtScenarioMemo;
	global	$fdtScenarioDate;

	global	$fdtUserLoginId,$fdtUserLoginDate;
	//POSTされたログイン要素を取得
	$fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
	$fdtUserLoginDate = swFunc_GetPostData('fdtUserLoginDate');      //USERログイン日時

	//処理ﾓｰﾄが空白ならreturnするﾞ
	if(!isset($_POST['SubMode'])){return;}
	//処理ﾓｰﾄﾞ
	$SubMode = swFunc_GetPostData('SubMode');
	if($SubMode == ''){return;}
	//POSTされた要素を取得
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //シナリオID
	$fdtUserId = swFunc_GetPostData('fdtUserId');                    //USER_ID
	$fdtScenarioTitle = swFunc_GetPostData('fdtScenarioTitle');      //タイトル
	$fdtScenarioSubtitle = swFunc_GetPostData('fdtScenarioSubtitle');//サブタイトル
	$fdtScenarioWriterName = swFunc_GetPostData('fdtScenarioWriterName');//作者名
	$fdtScenarioMemo = swFunc_GetPostData('fdtScenarioMemo');        //メモ
	$fdtScenarioDate = swFunc_GetPostData('fdtScenarioDate');        //執筆日
}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	global	$SubMode;
	//global 変数
	global	$fdtScenarioId;
	global	$fdtUserId;
	global	$fdtScenarioTitle;
	global	$fdtScenarioSubtitle;
	global	$fdtScenarioWriterName;
	global	$fdtScenarioMemo;
	global	$fdtScenarioDate;
	
	// 出力をクリア
	$resultHtml = '';

	//SubModeで処理を制御
	if($SubMode == 'SwScenarioList'){
		$resultHtml = fncMakeSwScenarioList($mySqlConnObj);
	}
	// 出力charsetをUTF-8に指定
	mb_http_output ( 'UTF-8' );
	// 出力
	echo($resultHtml);
}//end function


//--------------------------------------------------------------------------------
// SW_SCENARIO ALL LIST
//--------------------------------------------------------------------------------
function fncMakeSwScenarioList($mySqlConnObj){
	global	$fdtUserLoginId,$fdtUserLoginDate;

	//ﾛｸﾞｲﾝ情報からﾕｰｻﾞ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ ﾛｸﾞｲﾝ情報
	include_once("../class/clsSwUserLoginInfo.php");
	$clsSwUserLoginInfo = new clsSwUserLoginInfo();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$fdtUserLoginId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtUserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId();                    //USER_ID
	
	//共同執筆: 自分のシナリオ＋共有されたシナリオ（MEMBER_ROLE 付き。更新日の新しい順）
	if ((int)$fdtUserId <= 0) { return ''; }
	$rows = swCollab_ScenarioList($mySqlConnObj, (int)$fdtUserId);
	//件数取得
	$myRowCnt = count($rows);
	if($myRowCnt == 0 ){
		$retHtml = <<<END_OF_HTML
		
		<div class="jumbotron">
			<p>&nbsp;</p>
			
			<p><h4>保存されたシナリオ・共有されたシナリオはありません。</h4></p>
			<p>&nbsp;<p>
			<p><h4>新規にシナリオを作成する場合は、メニューから新規シナリオをクリックしてください。</h4><p>
			
			<p>&nbsp;</p>
		</div>
END_OF_HTML;
	}else{
		$retHtml = <<<END_OF_HTML
		
			<!-- シナリオリスト（2026-09: Masonry をやめ、横4列のカード。左3:右7 で画像とタイトル） -->
			<div class="row row-cols-2 row-cols-md-4 g-3 sw-scenario-grid">
END_OF_HTML;

		//アップロードされた画像を配列化する
		$imgArray = array();
		$dir = '../img/thumb/';
		$imgSrcdir = './img/thumb/';
		$handle = opendir($dir);
		while($fname = readdir($handle)){
			if( $fname != '.' and $fname != '..'){
				if(is_file($dir.$fname)){
					$arr = explode('.',$fname);
					$imgName = $fname;
					$imgArray += array($arr[0]=>$imgName);
				}
			}
		}

		
		foreach($rows as $myRow) {
			$valScenarioId = $myRow['SCENARIO_ID'];
			$valScenarioTitle = swFunc_SanitizeStrings($myRow['SCENARIO_TITLE']);
			$valScenarioSubTitle = swFunc_SanitizeStrings($myRow['SCENARIO_SUBTITLE']);
			$valScenarioDate = $myRow['SCENARIO_DATE'];
			//役割（プロデューサー / 役割名）。閲覧のみのカードは「シナリオを読む」へ
			$valRole = (string)$myRow['MEMBER_ROLE'];
			if ($valRole !== 'owner' && $valRole !== 'editor') { $valRole = 'reader'; }
			$valRoleLabel = swFunc_SanitizeStrings(swCollab_MemberLabel($valRole, isset($myRow['MEMBER_TITLE']) ? $myRow['MEMBER_TITLE'] : ''));
			$valOnClick = ($valRole === 'reader') ? "fncSelectSwScenarioView(frmSwScenario,'$valScenarioId');" : "fncSelectSwScenario(frmSwScenario,'$valScenarioId');";

			if(isset($imgArray[$valScenarioId])){
				$imgFile = $dir.$imgArray[$valScenarioId];
				$imgSrcPath = $imgSrcdir.$imgArray[$valScenarioId];
			}else{
				//サムネイル未登録はロゴを表示（2026-09: dummy.jpg は存在せず 404 になっていた）
				$imgFile = '../img/logo.png';
				$imgSrcPath = './img/logo.png';
			}
			
			//ﾘｽﾄ（カード: 左 30% 画像・右 70% タイトル）
			$retHtml .= <<<END_OF_HTML
				<div class="col">
					<div class="item sw-card" onclick="$valOnClick">
						<span class="sw-card-role $valRole">$valRoleLabel</span>
						<div class="sw-card-img"><img src="$imgSrcPath" alt=""></div>
						<div class="sw-card-body">
							<p class="sw-card-title">$valScenarioTitle</p>
							<p class="sw-card-sub">$valScenarioSubTitle</p>
						</div>
					</div>
				</div>

END_OF_HTML;
		}//end while
		$retHtml .= <<<END_OF_HTML
		
			</div>
			<!-- シナリオ選択 END -->
END_OF_HTML;
	}//end if
	
	//HTMLを返す
	return $retHtml;
}
