<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//     ダウンロード設定画面
//
//     SwScenarioDownload.php
// -----------------------------------------------------------
// ------------------------------------------------------------------------------
  //定数読み込み
  include_once("./sw_config/swConstant.php");
  //DB接続ｸﾗｽの初期化
  include_once("./include/ConnectMySQL.php");
// ------------------------------------------------------------------------------
  //ﾃﾞﾌｫﾙﾄｱｸｼｮﾝ
  $ThisPHP = 'SwScenarioDownload.php';

  //共通関数をｲﾝｸﾙｰﾄﾞ
  include_once("./include/swFunc.php");
  //管理者ｾｯｼｮﾝ管理
  include_once("./include/swAccept.php");
  //管理者チェック
  include_once("./include/swCheckAdmin.php");
  //共同執筆: 権限ガード（閲覧）
  $swCollabNeed = 'read';
  include_once("./include/swCollabGuard.php");

  // ﾍｯﾀﾞ表示
  $HtmlTitle = "ScenarioWriterCafe";
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
  //共通global変数
  global  $fdtUserLoginId,$fdtUserLoginDate;
  global  $fdtScenarioId;

  //POSTされたログイン要素を取得
  $fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
  $fdtUserLoginDate = swFunc_GetPostData('fdtUserLoginDate');      //USERログイン日時

  $fdtScenarioId = swFunc_GetPostData('fdtScenarioId');      //シナリオID
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
  global  $ThisPHP;
  //global 変数
  global  $fdtScenarioId;
  global  $fdtUserLoginId,$fdtUserLoginDate;

  //ログイン情報
  include_once("./class/clsSwUserLoginInfo.php");
  $clsSwUserLoginInfo = new clsSwUserLoginInfo();
  //ﾃﾞｰﾀ管理ｸﾗｽの初期化
  $clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$fdtUserLoginId);
  //ﾌﾟﾛﾊﾟﾃｨGet
  $fdtUserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId();                    //USER_ID
  $fdtUserLoginDate = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserLoginDate();      //USERログイン日時

  //ユーザ情報
  include_once("./class/clsSwUser.php");
  $clsSwUser = new clsSwUser();
  //ﾃﾞｰﾀ管理ｸﾗｽの初期化
  $clsSwUser->clsSwUserInit($mySqlConnObj,$fdtUserId);
  //ﾌﾟﾛﾊﾟﾃｨGet
  $fdtUserName = $clsSwUser->clsSwUserGetUserName();                //ユーザー名
  $fdtUserMaxScenario = $clsSwUser->clsSwUserGetUserMaxScenario();  //作成可能シナリオ数

  //ユーザOPTION
  include_once("./class/clsSwUserOptionSetting.php");
  $clsSwUserOptionSetting = new clsSwUserOptionSetting();
  //ﾃﾞｰﾀ管理ｸﾗｽの初期化
  $clsSwUserOptionSetting->clsSwUserOptionSettingInit($mySqlConnObj,$fdtUserId);
  //ﾌﾟﾛﾊﾟﾃｨGet
  $fdtCharacterLength = $clsSwUserOptionSetting->clsSwUserOptionSettingGetCharacterLength();  //キャラクタ部の文字数
  $fdtBodyLength = $clsSwUserOptionSetting->clsSwUserOptionSettingGetBodyLength();            //シナリオ本文の文字数

  if( $fdtCharacterLength == ''){$fdtCharacterLength = '12';}
  if( $fdtBodyLength == ''){$fdtBodyLength = '28';}


  //シナリオ情報
  //ﾃﾞｰﾀ管理ｸﾗｽ
  include_once("./class/clsSwScenario.php");
  $clsSwScenario = new clsSwScenario();
  //ﾃﾞｰﾀ管理ｸﾗｽの初期化
  $clsSwScenario->clsSwScenarioInit($mySqlConnObj,$fdtScenarioId);
  //ﾌﾟﾛﾊﾟﾃｨGet
  $fdtScenarioTitle = $clsSwScenario->clsSwScenarioGetScenarioTitle();      //タイトル
  $fdtScenarioSubtitle = $clsSwScenario->clsSwScenarioGetScenarioSubtitle();//サブタイトル

  $fdtWriterName = $clsSwScenario->clsSwScenarioGetScenarioWriterName();      //ScenarioWriterName

  //文字コード
  //------------------------------------------------------------
  //CSVﾃﾞｰﾀからselect box を作成する
  $ObjName = "fdtCharacterCode";    //select box の名称.ID
  //ここでfunctionからCSVﾃﾞｰﾀを取得
  $csvArray = swFunc_MakeSelectItemsCsvCharacterCode();  //CSVﾃﾞｰﾀ
  $default = "UTF-8";    //ﾃﾞﾌｫﾙﾄ値
  $onChange = '';//onChange で起動する javascript or jQuery
  $ViewCode = FALSE;        //ｺｰﾄﾞを表示する場合はTRUE
  //SelectBoxHtml出力
  $SelectBoxCharacterCode = swFunc_MakeSelectBox($ObjName,$csvArray,$default,$onChange,$ViewCode);
  //------------------------------------------------------------

  //改行コード
  //------------------------------------------------------------
  //CSVﾃﾞｰﾀからselect box を作成する
  $ObjName = "fdtLineFeedCode";    //select box の名称.ID
  //ここでfunctionからCSVﾃﾞｰﾀを取得
  $csvArray = swFunc_MakeSelectItemsCsvLineFeedCode();  //CSVﾃﾞｰﾀ
  $default = '\n';    //ﾃﾞﾌｫﾙﾄ値
  $onChange = '';//onChange で起動する javascript or jQuery
  $ViewCode = FALSE;        //ｺｰﾄﾞを表示する場合はTRUE
  //SelectBoxHtml出力
  $SelectBoxLineFeedCode = swFunc_MakeSelectBox($ObjName,$csvArray,$default,$onChange,$ViewCode);
  //------------------------------------------------------------


  //css powerd by Bootstrap ver3
    print <<<END_OF_HTML

  <div id="wrap">
END_OF_HTML;
  //------------------------------------------------------------
  //日付（執筆日）を年月日 SELECT で作成
  $ObjName = "fdtDate";    //ObjName名 **YY,**MM,**DD で作成する
  $default = "";    //ﾃﾞﾌｫﾙﾄ値
  $OnChange = '';              //onChange で起動する javascript or jQuery
  $DateCalendatHtml = swFunc_MakeCalendarBox($mySqlConnObj,'frmSwScenario',$ObjName,$default,TRUE,$OnChange);
  //------------------------------------------------------------
	include_once("./include/swMenuBar.php");
  swMenuBar_Print('./include/swEditDropDownMenu.php', 'frmSwScenario', swFunc_SanitizeStrings($fdtScenarioTitle), array(), $fdtUserName);
  print <<<END_OF_HTML
    <!-- main -->
    <div class="scroll_area">
      <div class="container">
          <!-- ﾌｫｰﾑ と ﾘｽﾄ -->
          <div class="row row-0">
            <div class="col-sm-12 row-0">
                <form class="" role="form" name="frmSwScenario" id="frmSwScenario" method="POST" action="">
                  <input type="hidden" name="fdtUserLoginId" id="fdtUserLoginId" value="$fdtUserLoginId">
                  <input type="hidden" name="fdtScenarioId" id="fdtScenarioId" value="$fdtScenarioId">
                  <input type="hidden" name="fdtTemplateId" id="fdtTemplateId" value="">

                  <div class="row row-0">
                      <div class="col-sm-12 scenario-title">
                        Scenario download: {$fdtScenarioTitle}　{$fdtScenarioSubtitle}
                      </div>
                  </div>
                  <div class="row row-0">
                    <label for="fdtCharacterCode" class="col-form-label col-sm-2">文字コード</label>
                    <div class="col-sm-2">
                      $SelectBoxCharacterCode
                    </div>
                    <label for="fdtLineFeedCode" class="col-form-label col-sm-2">改行コード</label>
                    <div class="col-sm-2">
                      $SelectBoxLineFeedCode
                    </div>
                  </div>
                  <div class="row row-0">
                      <div class="col-sm-3">
                          <button type="button" class="btn btn-outline-secondary btn-lg" aria-label="Left Align"
                            onClick="AjaxFunc_DounloadMenuJump(frmSwScenario,'./swScenarioDownloadZip.php','');">
                            <span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>
                            テキスト.txt
                          </button>
                      </div>
                      <div class="col-sm-8">
                          <p class="caption">
                            　txtは、文字情報のみで構成されたシンプルなファイルです。
                            文字数はオプション設定した文字数で、字下げは全角空白で文字埋します。
                            ダウンロードしたテキストをワープロソフトやレイアウトソフト等で編集してください。
                          </p>
                      </div>
                  </div>
                  <div class="row row-0">
                      <div class="col-sm-3">
                          <button type="button" class="btn btn-outline-secondary btn-lg" aria-label="Left Align"
                            onClick="AjaxFunc_DounloadMenuJump(frmSwScenario,'./swScenarioDownloadDocx.php','A4TP');">
                            <span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>A4縦 縦書き.docx
                          </button>
                      </div>
                      <div class="col-sm-8">
                          <p class="caption">
                            　docxは、Microsoftのワープロソフト「Word 2007」（Microsoft Office Word 2007）から採用されているXMLベースの文書です。利用するにはMicrosoft Office Word 2007以降の製品が必要です。 <br />
                          </p>
                      </div>
                  </div>
                  <div class="row row-0">
                    <label for="fdtWriterName" class="col-form-label col-2">作者名</label>
                    <div class="col-sm-4">
                        <input type="text"
                          class="form-control form-control-sm"
                          id="fdtWriterName" name="fdtWriterName"
                          value="$fdtWriterName"
                          placeholder="作者名">
                    </div>
                    <label for="fdtWriterName" class="col-form-label col-2">脚本協会登録番号等</label>
                    <div class="col-sm-3">
                        <input type="text"
                          class="form-control form-control-sm"
                          id="fdtWriterId" name="fdtWriterId"
                          value=""
                          placeholder="脚本協会登録番号等">
                    </div>
                  </div>
                  <div class="row row-0">
                    <label for="fdtDateYY" class="col-form-label col-2">日付</label>
                    <div class="d-flex flex-wrap align-items-center col-sm-5">
                        $DateCalendatHtml
                    </div>
                    <label for="fdtVersion" class="col-form-label col-2">草稿バージョン等</label>
                    <div class="col-sm-2">
                        <input type="text"
                          class="form-control form-control-sm"
                          id="fdtVersion" name="fdtVersion"
                          value=""
                          placeholder="">
                    </div>
                  </div>
                  <div class="row row-0">
                    <label for="fdtWriterName" class="col-form-label col-2">住所</label>
                    <div class="col-sm-5">
                        <textarea
                          class="form-control form-control-sm"
                          rows="2"
                          id="fdtWriterAddress" name="fdtWriterAddress"
                          placeholder="住所"></textarea>
                    </div>
                    <label for="fdtWriterPhoneNo" class="col-form-label col-2">電話番号</label>
                    <div class="col-sm-2">
                        <input type="text"
                          class="form-control form-control-sm"
                          id="fdtWriterPhoneNo" name="fdtWriterPhoneNo"
                          value=""
                          placeholder="電話番号">
                    </div>
                  </div>
                  <div class="row row-0">
                    <label for="fdtWriterEMail" class="col-form-label col-2">Eメール</label>
                    <div class="col-sm-5">
                        <input type="email"
                          class="form-control form-control-sm"
                          id="fdtWriterEMail" name="fdtWriterEMail"
                          value=""
                          placeholder="メールアドレス">
                    </div>
                  </div>
                  <div class="row row-0">
                      <div class="col-sm-3">
                          <button type="button" class="btn btn-outline-secondary btn-lg" aria-label="Left Align"
                            onClick="AjaxFunc_DounloadMenuJump(frmSwScenario,'./swScenarioDownloadDocx.php','A4YP');">
                            <span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>A4縦 横書き.docx
                          </button>
                          <button type="button" class="btn btn-outline-secondary btn-lg" aria-label="Left Align"
                            onClick="AjaxFunc_DounloadMenuJump(frmSwScenario,'./swScenarioDownloadDocx.php','A4TL');">
                            <span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>A4横 縦書き.docx
                          </button>
                      </div>
                      <div class="col-sm-8">
                          <p class="caption">
                            　A4縦 横書.docxとA4横 縦書.docxは、<a href="http://deerstudio.jp/inc/downloads/film-and-video/script-microsoft-word-template.html" target="_blank">株式会社deerstudio</a>からダウンロードできる脚本用Microsoft Wordのテンプレートスタイルを使ってシナリオを作成します。
                            <br />
                            <a href="http://deerstudio.jp/inc/downloads/film-and-video/script-microsoft-word-template.html" target="_blank">テンプレートのダウンロード</a>
                          </p>
                      </div>
                  </div>
                  <span id="retMsg"></span>
                </form>
            </div><!--col-sm-11 row-0 -->
          </div><!--row row-0 -->

          <div class="float-start fnav">
              <p>copyright © 2026. ictcacao.com all rights reserved.</p>
          </div>

      </div><!--container -->
    </div>


  </body>
</html>

END_OF_HTML;

}//end function



// -----------------------------------------------------------
?>
