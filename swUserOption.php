<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
// 
//     SW_USER_OPTION I/O ｼｽﾃﾑ
//
//     swUserOption.php
// -----------------------------------------------------------
// ------------------------------------------------------------------------------
	//定数読み込み
	include_once("./sw_config/swConstant.php");
	//DB接続ｸﾗｽの初期化
	include_once("./include/ConnectMySQL.php");
// ------------------------------------------------------------------------------
	//ｾｯｼｮﾝ管理
	include_once("./include/swAccept.php");
	//管理者チェック
	include_once("./include/swCheckAdmin.php");
	//共同執筆: 権限ガード（閲覧）
	$swCollabNeed = 'read';
	include_once("./include/swCollabGuard.php");
// ------------------------------------------------------------------------------
	//ﾃﾞﾌｫﾙﾄｱｸｼｮﾝ
	$ThisPHP = 'swUserOption.php';
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("./class/clsSwUserOption.php");
	$clsSwUserOption = new clsSwUserOption();
	//共通関数をｲﾝｸﾙｰﾄﾞ
	include_once("./include/swFunc.php");
	// ﾍｯﾀﾞ表示
	$HtmlTitle = "オプション設定";
	include_once("./include/swHeaderWithColorpicker.php");

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
	global	$fdtUserLoginId;
	
	//POSTされた要素を取得
	$fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
	global	$fdtScenarioId;
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //シナリオID（シナリオ編集メニューから来たときだけ入る）

}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
	//ﾌｫｰﾑの表示
	fncMainForm($mySqlConnObj);

}//end function

// ------------------------------------------------------------------------------
//      MAIN FORM
//          fncMainForm($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainForm($mySqlConnObj){
	//共通global変数
	global	$fdtUserLoginId;
	global	$fdtCharacterLength,$fdtBodyLength,$fdtUseKagikakko;
	
	//ﾛｸﾞｲﾝ情報からﾕｰｻﾞ情報を取得
	//ﾃﾞｰﾀ管理ｸﾗｽ ﾛｸﾞｲﾝ情報
	include_once("./class/clsSwUserLoginInfo.php");
	$clsSwUserLoginInfo = new clsSwUserLoginInfo();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$fdtUserLoginId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtUserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId(); //USER_ID

	//ﾃﾞｰﾀ管理ｸﾗｽ ﾕｰｻﾞ情報
	include_once("./class/clsSwUser.php");
	$clsSwUser = new clsSwUser();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUser->clsSwUserInit($mySqlConnObj,$fdtUserId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtUserName = $clsSwUser->clsSwUserGetUserName();               //ユーザー名

	//ｻﾆﾀｲｽﾞ
	$fdtUserName = swFunc_SanitizeStrings($fdtUserName);

	//ﾃﾞｰﾀ管理ｸﾗｽ ﾕｰｻﾞ情報
	include_once("./class/clsSwUserOptionSetting.php");
	$clsSwUserOptionSetting = new clsSwUserOptionSetting();
	//ﾃﾞｰﾀ管理ｸﾗｽの初期化
	$clsSwUserOptionSetting->clsSwUserOptionSettingInit($mySqlConnObj,$fdtUserId);
	//ﾌﾟﾛﾊﾟﾃｨGet
	$fdtCharacterLength = $clsSwUserOptionSetting->clsSwUserOptionSettingGetCharacterLength();  //キャラクタ部の文字数
	$fdtBodyLength = $clsSwUserOptionSetting->clsSwUserOptionSettingGetBodyLength();            //シナリオ本文の文字数
	$fdtUseKagikakko = $clsSwUserOptionSetting->clsSwUserOptionSettingGetUseKagikakko();        //台詞を「」で囲む
	$KagikakkoChecked = ($fdtUseKagikakko == 0) ? '' : 'checked';

	//css powerd by Bootstrap ver3
  	print <<<END_OF_HTML

END_OF_HTML;
	global	$fdtScenarioId;
	//シナリオ編集から来たときは編集メニュー、シナリオ選択から来たときは選択画面のメニュー
	$mbMenu = ($fdtScenarioId != '') ? './include/swEditDropDownMenu.php' : './include/swDropDownMenu.php';
	include_once("./include/swMenuBar.php");
	swMenuBar_Print($mbMenu, 'frmSwUserOption', 'オプション設定', array(
		array('label'=>'USER STYLE追加', 'onclick'=>"fncNewUserOptionStyleInit(frmSwUserOption);"),
	), $fdtUserName);
	print <<<END_OF_HTML

		<!-- main -->
			<div class="scenarioedit">
				<div class="row">
					<div class="col-sm-12">
						<form class="" role="form" name="frmSwUserOption" id="frmSwUserOption" method="POST" action="">
							<input type="hidden" name="fdtUserLoginId" id="fdtUserLoginId" value="$fdtUserLoginId">
							<input type="hidden" name="fdtScenarioId" id="fdtScenarioId" value="$fdtScenarioId">
							<input type="hidden" name="SubmitMode" id="SubmitMode" value="">
							<input type="hidden" name="SelectUserOptionId" id="SelectUserOptionId" value="">
							<input type="hidden" name="listpos" id="listpos" value="">

							<h4>USER OPTION設定</h4>
							<div class="row row-0">
								<label for="fdtCharacterLength" class="col-form-label col-sm-auto text-nowrap">シナリオヘッダ部文字数</label>
								<div class="col-sm-1">
									<input type="text" 
										class="form-control form-control-sm text-end"
										id="fdtCharacterLength" name="fdtCharacterLength"
										value="$fdtCharacterLength"
										onchange="fncUserOptionSettingAutoSave(frmSwUserOption);"
										placeholder="12">
								</div>
								<label for="fdtBodyLength" class="col-form-label col-sm-auto text-nowrap ms-sm-3">シナリオボディ部文字数</label>
								<div class="col-sm-1">
									<input type="text" 
										class="form-control form-control-sm text-end"
										id="fdtBodyLength" name="fdtBodyLength"
										value="$fdtBodyLength"
										onchange="fncUserOptionSettingAutoSave(frmSwUserOption);"
										placeholder="32">
								</div>
								<div class="col-sm-auto ms-sm-4 pt-1">
									<div class="form-check">
										<input type="checkbox" class="form-check-input" id="fdtUseKagikakko" name="fdtUseKagikakko" value="1" $KagikakkoChecked
											onchange="fncUserOptionSettingAutoSave(frmSwUserOption);">
										<label for="fdtUseKagikakko" class="form-check-label text-nowrap">台詞を「」で囲む</label>
									</div>
								</div>
								<div class="col-sm-auto ms-sm-2 pt-1">
									<span id="optionSaveMsg" class="text-success" style="font-size: 12px;"></span>
								</div>
							</div>
								
								<!-- データ表示位置 -->
								<span id="UserOptionStyleList"></span>
			
						</form>
					</div><!-- col-sm-12 -->
				</div><!-- row end -->
			</div>
				
	<script type="text/javascript" src="./ajax/ajaxUserOption.js"></script>
	<script language="JavaScript">
		fncReadUserOptionStyleList(frmSwUserOption)
	</script>

	<!--footer表示位置--><span class="sw-footer"></span>
  </body>
</html>

END_OF_HTML;

}//end function
// -----------------------------------------------------------
?>