<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//     SW_SYNOPSIS 編集
//
//     swScenarioEditSynopsis.php
//
//     2026-09 作り直し。元のファイルは 2017 年に中身が壊れて（ls の出力に置き換わって）いた。
//     シナリオ 1 本につきシノプシス 1 件（SW_SYNOPSIS.SCENARIO_ID）。無ければ INSERT、あれば UPDATE。
//     改行は他の項目と同じく <br> で保存し、表示時に改行へ戻す。
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
  //共同執筆: 権限ガード（編集）
  $swCollabNeed = 'edit';
  include_once("./include/swCollabGuard.php");
// ------------------------------------------------------------------------------
  //ﾃﾞﾌｫﾙﾄｱｸｼｮﾝ
  $ThisPHP = 'swScenarioEditSynopsis.php';
  //ﾃﾞｰﾀ管理ｸﾗｽ
  include_once("./class/clsSwScenario.php");
  include_once("./class/clsSwSynopsis.php");
  //共通関数をｲﾝｸﾙｰﾄﾞ
  include_once("./include/swFunc.php");

  // ﾍｯﾀﾞ表示
  $HtmlTitle = "シノプシス";
  include_once("./include/swHeader.php");

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
  global  $fdtUserLoginId,$fdtUserLoginDate;
  global  $SubmitMode;
  global  $fdtScenarioId,$fdtSynopsis;

  $fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
  $fdtUserLoginDate = swFunc_GetPostData('fdtUserLoginDate');      //USERログイン日時
  $fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //シナリオID
  $SubmitMode = swFunc_GetPostData('SubmitMode');                  //処理ﾓｰﾄﾞ
  $fdtSynopsis = swFunc_GetPostData('fdtSynopsis');                //シノプシス
}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
  global  $SubmitMode,$fdtScenarioId,$fdtSynopsis,$retMessage;

  $retMessage = '';
  //保存
  if($SubmitMode == 'UPDATE' && $fdtScenarioId != ''){
    //改行は <br> で保存する（他の項目・ダウンロード処理と同じ）
    $saveText = str_replace(array("\r\n","\r","\n"), "<br>", (string)$fdtSynopsis);
    $clsSwSynopsis = new clsSwSynopsis();
    if($clsSwSynopsis->clsSwSynopsisInitScenarioId($mySqlConnObj,$fdtScenarioId)){
      //既存 → UPDATE
      $clsSwSynopsis->clsSwSynopsisSetSynopsis($saveText);
      $clsSwSynopsis->clsSwSynopsisDbUpdate($mySqlConnObj);
    }else{
      //新規 → INSERT
      $clsSwSynopsis->clsSwSynopsisSetScenarioId($fdtScenarioId);
      $clsSwSynopsis->clsSwSynopsisSetSynopsis($saveText);
      $clsSwSynopsis->clsSwSynopsisDbInsert($mySqlConnObj);
    }
    $retMessage = 'シノプシスを保存しました。';
  }
  //ﾌｫｰﾑの表示
  fncMainForm($mySqlConnObj);
}//end function

// ------------------------------------------------------------------------------
//      MAIN FORM
//          fncMainForm($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainForm($mySqlConnObj){
  global  $fdtUserLoginId,$fdtUserLoginDate;
  global  $ThisPHP;
  global  $fdtScenarioId,$retMessage;

  //ﾛｸﾞｲﾝ情報からﾕｰｻﾞ情報を取得
  include_once("./class/clsSwUserLoginInfo.php");
  $clsSwUserLoginInfo = new clsSwUserLoginInfo();
  $clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$fdtUserLoginId);
  $fdtUserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId();
  $fdtUserLoginDate = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserLoginDate();

  include_once("./class/clsSwUser.php");
  $clsSwUser = new clsSwUser();
  $clsSwUser->clsSwUserInit($mySqlConnObj,$fdtUserId);
  $fdtUserName = swFunc_SanitizeStrings($clsSwUser->clsSwUserGetUserName());

  //ｼﾅﾘｵ情報
  $clsSwScenario = new clsSwScenario();
  $clsSwScenario->clsSwScenarioInit($mySqlConnObj,$fdtScenarioId);
  $fdtScenarioTitle = swFunc_SanitizeStrings($clsSwScenario->clsSwScenarioGetScenarioTitle());

  //ｼﾉﾌﾟｼｽ（<br> を改行に戻してから ｻﾆﾀｲｽﾞ）
  $clsSwSynopsis = new clsSwSynopsis();
  $clsSwSynopsis->clsSwSynopsisInitScenarioId($mySqlConnObj,$fdtScenarioId);
  $fdtSynopsis = str_replace("<br>", "\n", (string)$clsSwSynopsis->clsSwSynopsisGetSynopsis());
  $fdtSynopsis = swFunc_SanitizeStrings($fdtSynopsis);
  $fdtUpdateDate = swFunc_SanitizeStrings((string)$clsSwSynopsis->clsSwSynopsisGetUpdateDate());
  $updateInfo = ($fdtUpdateDate != '') ? "最終更新: $fdtUpdateDate" : '未登録';
  $retMessage = swFunc_SanitizeStrings((string)$retMessage);

  print <<<END_OF_HTML

END_OF_HTML;
	include_once("./include/swMenuBar.php");
  swMenuBar_Print('./include/swEditDropDownMenu.php', 'frmSwScenario', $fdtScenarioTitle, array(
    array('label'=>'シノプシス保存', 'onclick'=>"fncSwSynopsisSubmit(frmSwScenario,'UPDATE','FALSE','シノプシス保存');"),
  ), $fdtUserName, ($retMessage != '' ? '<span class="text-success">'.$retMessage.'</span>' : ''));
  print <<<END_OF_HTML

    <div class="scenarioedit">
      <div class="row row-0">
        <div class="col-sm-11 row-0">
          <div class="edit-jumbotron">
            <form class="" role="form" name="frmSwScenario" id="frmSwScenario" method="POST" action="$ThisPHP">
              <input type="hidden" name="SubmitMode" id="SubmitMode" value="">
              <input type="hidden" name="fdtUserLoginId" id="fdtUserLoginId" value="$fdtUserLoginId">
              <input type="hidden" name="fdtUserLoginDate" id="fdtUserLoginDate" value="$fdtUserLoginDate">
              <input type="hidden" name="fdtScenarioId" id="fdtScenarioId" value="$fdtScenarioId">

              <div class="row row-0">
                <label for="fdtSynopsis" class="col-form-label col-sm-2">シノプシス</label>
                <div class="col-sm-10">
                  <textarea class="form-control form-control-sm" rows="15"
                    id="fdtSynopsis" name="fdtSynopsis"
                    placeholder="あらすじ・梗概を入力します。ダウンロード（テキスト / docx）の冒頭に入ります。">$fdtSynopsis</textarea>
                  <p class="text-muted mt-1 mb-0">$updateInfo</p>
                </div>
              </div>
            </form>
          </div><!--jumbotron -->
        </div><!--col-sm-11 row-0 -->
      </div><!--row row-0 -->
    </div><!--scenarioedit -->

    <script type="text/javascript" src="./ajax/ajaxSwScenarioEditSynopsis.js?v=20260917"></script>
  <script>
    autosize(document.querySelectorAll('textarea'));
  </script>

  </body>
</html>

END_OF_HTML;

}//end function
// ------------------------------------------------------------------------------
?>
