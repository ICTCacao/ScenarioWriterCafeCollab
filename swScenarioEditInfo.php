<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//     SW_SCENARIO I/O ｼｽﾃﾑ
//
//     swScenarioEditInfo.php
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
  $ThisPHP = 'swScenarioEditInfo.php';
  //ﾃﾞｰﾀ管理ｸﾗｽ
  include_once("./class/clsSwScenario.php");
  $clsSwScenario = new clsSwScenario();
  //共通関数をｲﾝｸﾙｰﾄﾞ
  include_once("./include/swFunc.php");

  // ﾍｯﾀﾞ表示
  $HtmlTitle = "シナリオ情報";
  include_once("./include/swHeaderWithFileinput.php");

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
  //共通global変数
  global  $SubmitMode;
  //ﾌｫｰﾑ name要素をglobal変数にする
  global  $fdtScenarioId;
  global  $fdtUserId;
  global  $fdtScenarioTitle;
  global  $fdtScenarioSubtitle;
  global  $fdtScenarioWriterName;
  global  $fdtScenarioMemo;
  global  $fdtScenarioDate;
  global  $fdtScenarioCategory;

  //POSTされた要素を取得
  $fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
  $fdtUserLoginDate = swFunc_GetPostData('fdtUserLoginDate');      //USERログイン日時

  $fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //シナリオID

  //処理ﾓｰﾄが空白ならreturnするﾞ
  if(!isset($_POST['SubmitMode'])){return;}
  //POSTされた要素を取得
  $fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //シナリオID
  $fdtUserId = swFunc_GetPostData('fdtUserId');                    //USER_ID
  $fdtScenarioTitle = swFunc_GetPostData('fdtScenarioTitle');      //タイトル
  $fdtScenarioSubtitle = swFunc_GetPostData('fdtScenarioSubtitle');//サブタイトル
  $fdtScenarioWriterName = swFunc_GetPostData('fdtScenarioWriterName');//作者名
  $fdtScenarioMemo = swFunc_GetPostData('fdtScenarioMemo');        //メモ
  $fdtScenarioDate = swFunc_GetPostData('fdtScenarioDate');        //執筆日
  $fdtScenarioCategory = swFunc_GetPostData('fdtScenarioCategory');        //分類
}//end function

// ------------------------------------------------------------------------------
//      MAIN PROCEDURE
//          fncMainProc($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainProc($mySqlConnObj){
  //class
  global  $clsSwScenario;
  //ﾌｫｰﾑの表示
  fncMainForm($mySqlConnObj);
}//end function

// ------------------------------------------------------------------------------
//      MAIN FORM
//          fncMainForm($mySqlConnObj)
// ------------------------------------------------------------------------------
function fncMainForm($mySqlConnObj){
  //共通global変数
  global  $fdtUserLoginId,$fdtUserLoginDate;

  global  $fdtScenarioId,$fdtUserId;
  global  $fdtScenarioTitle,$fdtScenarioSubtitle;
  global  $fdtScenarioWriterName,$fdtScenarioMemo,$fdtScenarioDate;
  global  $fdtScenarioCategory;

  //ﾛｸﾞｲﾝ情報からﾕｰｻﾞ情報を取得
  //ﾃﾞｰﾀ管理ｸﾗｽ ﾛｸﾞｲﾝ情報
  include_once("./class/clsSwUserLoginInfo.php");
  $clsSwUserLoginInfo = new clsSwUserLoginInfo();
  //ﾃﾞｰﾀ管理ｸﾗｽの初期化
  $clsSwUserLoginInfo->clsSwUserLoginInfoInit($mySqlConnObj,$fdtUserLoginId);
  //ﾌﾟﾛﾊﾟﾃｨGet
  $fdtUserId = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserId();                    //USER_ID
  $fdtUserLoginDate = $clsSwUserLoginInfo->clsSwUserLoginInfoGetUserLoginDate();      //USERログイン日時

  //ﾃﾞｰﾀ管理ｸﾗｽ ﾕｰｻﾞ情報
  include_once("./class/clsSwUser.php");
  $clsSwUser = new clsSwUser();
  //ﾃﾞｰﾀ管理ｸﾗｽの初期化
  $clsSwUser->clsSwUserInit($mySqlConnObj,$fdtUserId);
  //ﾌﾟﾛﾊﾟﾃｨGet
  $fdtUserName = $clsSwUser->clsSwUserGetUserName();                //ユーザー名
  $fdtUserMaxScenario = $clsSwUser->clsSwUserGetUserMaxScenario();  //作成可能シナリオ数


  //ﾃﾞｰﾀ管理ｸﾗｽからｼﾅﾘｵ情報を取得
  include_once("./class/clsSwScenario.php");
  $clsSwScenario = new clsSwScenario();
  //ﾃﾞｰﾀ管理ｸﾗｽの初期化
  $clsSwScenario->clsSwScenarioInit($mySqlConnObj,$fdtScenarioId);
  //ﾌﾟﾛﾊﾟﾃｨGet
  fncSwScenarioGetProperty($clsSwScenario);


  //css powerd by Bootstrap ver3
    print <<<END_OF_HTML

END_OF_HTML;
  //ｻﾆﾀｲｽﾞ
  $fdtScenarioTitle = swFunc_SanitizeStrings($fdtScenarioTitle);
  $mbActions = array(
    array('label'=>'シナリオ情報更新', 'onclick'=>"fncSwScenarioSubmit(frmSwScenario,'UPDATE','TRUE','シナリオ情報更新');"),
  );
  //コラボ制作: 複写・削除はプロデューサーだけ
  $swIsOwner = (defined('SW_COLLAB_ROLE') && SW_COLLAB_ROLE === 'owner');
  if(SW_GUEST){
    $mbActions[] = array('label'=>'シナリオ複写', 'disabled'=>true, 'note'=>'お試しログインでは複写機能は使用できません');
  }elseif(!$swIsOwner){
    $mbActions[] = array('label'=>'シナリオ複写', 'disabled'=>true);
    $mbActions[] = array('label'=>'シナリオ削除', 'disabled'=>true, 'note'=>'複写・削除はプロデューサーだけができます');
  }else{
    $mbActions[] = array('label'=>'シナリオ複写', 'onclick'=>"fncSwScenarioSubmitCopy(frmSwScenario);");
    $mbActions[] = array('label'=>'シナリオ削除', 'onclick'=>"fncSwScenarioSubmitDelete(frmSwScenario);", 'class'=>'danger');
  }
	include_once("./include/swMenuBar.php");
  swMenuBar_Print('./include/swEditDropDownMenu.php', 'frmSwScenario', $fdtScenarioTitle, $mbActions, $fdtUserName);
  print <<<END_OF_HTML
      <!-- ﾒﾆｭｰ 制御ﾎﾞﾀﾝ 表示ROW ここまで-->
END_OF_HTML;


  //分類SELECT BOX
  //CSVﾃﾞｰﾀからselect box を作成する
  $csvArray = swFunc_MakeSelectItemsCsvScenarioCategory();
  $ObjName = "fdtScenarioCategory";    //select box の名称.ID
  $default = $fdtScenarioCategory;    //ﾃﾞﾌｫﾙﾄ値
  $onChange = '';          //onChange で起動する javascript or jQuery
  $ViewCode = FALSE;        //ｺｰﾄﾞを表示する場合はTRUE
  //SelectBoxHtml出力
  $SelectBoxScenarioCategoryHtml = swFunc_MakeSelectBox($ObjName,$csvArray,$default,$onChange,$ViewCode);

  //アップロードされた画像を配列化する
  $imgArray = array();
  $dir = './img/thumb/';
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
  $imgName = isset($imgArray[$fdtScenarioId]) ? $imgArray[$fdtScenarioId] : '';   //PHP8: 画像未登録なら Warning(Undefined array key) になるので isset で判定
  $imgSrcPath = $imgSrcdir.$imgName;
  //作品画像のﾌﾟﾚﾋﾞｭｰ（ﾄﾞﾗｯｸﾞ&ﾄﾞﾛｯﾌﾟ枠の右に 50:50 で並べる）
  $previewHtml = is_file($imgSrcPath) ? '<img src="'.$imgSrcPath.'" alt="作品画像" style="max-width: 100%; height: auto;">' : '';
  //ｼﾅﾘｵBody ---------------------------------------------------------------------------------------------------

  print <<<END_OF_HTML

    <!-- main -->
    <div class="scenarioedit">
          <!-- ﾌｫｰﾑ と ﾘｽﾄ -->
          <div class="row row-0">
            <div class="col-sm-11 row-0">
              <div class="edit-jumbotron">
                <form class="" role="form"
                  name="frmSwScenario" id="frmSwScenario"
                  method="POST" action=""
                  enctype="multipart/form-data">

                  <input type="hidden" name="SubmitMode" id="SubmitMode" value="">
                  <input type="hidden" name="fdtUserLoginId" id="fdtUserLoginId" value="$fdtUserLoginId">
                  <input type="hidden" name="fdtUserLoginDate" id="fdtUserLoginDate" value="$fdtUserLoginDate">
                  <input type="hidden" name="fdtScenarioId" id="fdtScenarioId" value="$fdtScenarioId">

                    <div class="row row-0">
                      <label for="fdtScenarioTitle" class="col-form-label col-sm-2">タイトル</label>
                      <div class="col-sm-9">
                        <input type="text"
                          class="form-control form-control-sm"
                          id="fdtScenarioTitle" name="fdtScenarioTitle"
                          value="$fdtScenarioTitle"
                          placeholder="タイトル">
                      </div>
                    </div>
                    <div class="row row-0">
                      <label for="fdtScenarioSubtitle" class="col-form-label col-sm-2">サブタイトル</label>
                      <div class="col-sm-9">
                        <input type="text"
                          class="form-control form-control-sm"
                          id="fdtScenarioSubtitle" name="fdtScenarioSubtitle"
                          value="$fdtScenarioSubtitle"
                          placeholder="サブタイトル">
                      </div>
                    </div>
                    <div class="row row-0">
                      <label for="fdtScenarioWriterName" class="col-form-label col-sm-2">作者名</label>
                      <div class="col-sm-9">
                        <input type="text"
                          class="form-control form-control-sm"
                          id="fdtScenarioWriterName" name="fdtScenarioWriterName"
                          value="$fdtScenarioWriterName"
                          placeholder="作者名">
                      </div>
                    </div>
                    <div class="row row-0">
                      <label for="fdtScenarioWriterName" class="col-form-label col-sm-2">分類</label>
                      <div class="col-sm-4">
                        $SelectBoxScenarioCategoryHtml
                      </div>
                    </div>
                    <div class="row row-0">
                      <label for="fdtScenarioMemo" class="col-form-label col-sm-2">メモ</label>
                      <div class="col-sm-9">
                        <textarea placeholder="メモ"
                          class="form-control form-control-sm"
                          id="fdtScenarioMemo" name="fdtScenarioMemo"
                          rows="3"
                          id="InputTextarea">$fdtScenarioMemo</textarea>
                      </div>
                    </div>
                    <div class="row row-0">
                      <input type="hidden" name="fdtScenarioDate" id="fdtScenarioDate" value="$fdtScenarioDate">
                    </div>

                    <div class="row row-0 kv-main">
                      <label for="fdtDigiFile" class="col-form-label col-sm-2">作品画像</label>
                      <div class="col-sm-5">
                        <input name="upfile[]"
                          class="file"
                          type="file" multiple data-theme="bs5" data-language="ja" data-show-upload="false" data-show-caption="true">
                      </div>
                      <div class="col-sm-5 ps-3">
                        $previewHtml
                      </div>
                    </div>
                    <!-- 更新処理結果 -->
                    <div class="col-sm-12">
                      <span name="retResult" id="retResult"></span>
                    </div>

                    <!-- 劇団員プレビュー（2026-09 追加）: 鍵付き URL でログインなしに読める -->
                    <hr>
                    <div class="row row-0" id="preview">
                      <label class="col-form-label col-sm-2">劇団員プレビュー</label>
                      <div class="col-sm-9">
                        <div class="mb-2"><span id="pvStatus"></span>
                          <button type="button" class="btn btn-success btn-sm ms-2" id="pvBtnEnable" onclick="swPreview_call('Enable');">プレビューを公開する</button>
                          <button type="button" class="btn btn-outline-danger btn-sm ms-2" id="pvBtnDisable" onclick="swPreview_disable();" style="display:none;">公開を停止する</button>
                          <button type="button" class="btn btn-outline-secondary btn-sm ms-1" id="pvBtnRegen" onclick="swPreview_regen();" style="display:none;">URLを作り直す</button>
                        </div>
                        <div class="input-group input-group-sm mb-1" id="pvOnRow" style="display:none;">
                          <input type="text" class="form-control" id="pvUrl" readonly onclick="this.select();">
                          <button type="button" class="btn btn-outline-secondary" onclick="swPreview_copy();">コピー</button>
                          <a class="btn btn-outline-primary" id="pvOpen" href="#" target="_blank" rel="noopener">開く</a>
                        </div>
                        <div class="form-text" id="pvMsg"></div>
                        <div class="form-text">この URL を知っている人だけがシナリオを読めます（ログイン不要・閲覧のみ）。スマホ向けで、縦書き / 横書きを切り替えられます。編集内容はすぐ反映されます。</div>
                      </div>
                    </div>
END_OF_HTML;


  print <<<END_OF_HTML

                </form>
              </div><!--jumbotron -->
            </div><!--col-sm-11 row-0 -->
          </div><!--row row-0 -->
    </div><!--scenarioedit -->

    <script type="text/javascript" src="./ajax/ajaxSwScenarioEditInfo.js"></script>
    <script type="text/javascript" src="./ajax/ajaxSwPreview.js?v=20260919"></script>
  <script>
    autosize(document.querySelectorAll('textarea'));
  </script>

  </body>
</html>

END_OF_HTML;

}//end function

// ------------------------------------------------------------------------------
//      ﾌﾟﾛﾊﾟﾃｨGet
//          fncSwScenarioGetProperty($clsSwScenario)
// ------------------------------------------------------------------------------
function fncSwScenarioGetProperty($clsSwScenario){
  global  $fdtScenarioId,$fdtUserId;
  global  $fdtScenarioTitle,$fdtScenarioSubtitle;
  global  $fdtScenarioWriterName,$fdtScenarioMemo,$fdtScenarioDate;
  global  $fdtScenarioCategory;

  //ﾌﾟﾛﾊﾟﾃｨGet
  $fdtScenarioId = $clsSwScenario->clsSwScenarioGetScenarioId();            //シナリオID
  //$fdtUserId = $clsSwScenario->clsSwScenarioGetUserId();                    //USER_ID
  $fdtScenarioTitle = $clsSwScenario->clsSwScenarioGetScenarioTitle();      //タイトル
  $fdtScenarioSubtitle = $clsSwScenario->clsSwScenarioGetScenarioSubtitle();//サブタイトル
  $fdtScenarioWriterName = $clsSwScenario->clsSwScenarioGetScenarioWriterName();//作者名

  //メモは<br>を\nに変換する
  $fdtScenarioMemo = $clsSwScenario->clsSwScenarioGetScenarioMemo();        //メモ
  $fdtScenarioMemo = str_replace("<br>","\n",(string)$fdtScenarioMemo);   //PHP8.1: DBのNULLを渡すと Deprecated

  $fdtScenarioDate = $clsSwScenario->clsSwScenarioGetScenarioDate();        //執筆日
  $fdtScenarioCategory = $clsSwScenario->clsSwScenarioGetScenarioCategory();        //執筆日
}//end function

?>
