<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
// 
//     編集メニュー
//
//     swEditDropDownMenu.php
//
//     コラボレーション版: 役割（SW_COLLAB_ROLE。swCollabGuard.php が定義）で出し分ける。
//       閲覧者(reader) … 読む・ダウンロードだけ
//       それ以外        … 従来どおり。「コラボ制作」はプロデューサー(owner)だけ
// -----------------------------------------------------------
$swMenuRole = defined('SW_COLLAB_ROLE') ? SW_COLLAB_ROLE : '';
$swMenuCollab = ($swMenuRole === 'owner') ? "      <li><a class=\"dropdown-item\" href=\"#\" onclick=\"AjaxFunc_EditMenuJump($TargetForm,'./swScenarioMember.php')\">コラボ制作</a></li>\n" : '';
if($swMenuRole === 'reader'){
	print <<<END_OF_HTML

  <div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
    	<img src="./img/editmenu.png" height="20">
    </button>
    <ul class="dropdown-menu" role="menu">
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioSelect.php');">シナリオ選択</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioView.php');">シナリオを読む</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioDownload.php');">ダウンロード</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swUserOption.php')">オプション設定</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_SubmitNoMsg($TargetForm,'./swUserLogout.php');">ログアウト</a></li>
    </ul>
  </div>
END_OF_HTML;
}elseif(SW_GUEST){
	print <<<END_OF_HTML

  <div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
    	<img src="./img/editmenu.png" height="20">
    </button>
    <ul class="dropdown-menu" role="menu">
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioSelect.php');">シナリオ選択</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swNewScenario.php');">新規シナリオ</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioEdit.php')">シナリオ編集</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioEditInfo.php')">シナリオ情報</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioEditSynopsis.php')">シノプシス</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioEditScene.php')">場面設定</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioEditCharacter.php')">登場人物設定</a></li>
{$swMenuCollab}      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swUserOption.php')">オプション設定</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJumpBlank($TargetForm,'./swScenarioView.php');">シナリオを読む</a></li>
      <li><a class="dropdown-item" href="#"><p style="color: #a9a9a9;align: center;">ダウンロード</p></a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_SubmitNoMsg($TargetForm,'./swUserLogout.php');">ログアウト</a></li>
    </ul>
  </div>
END_OF_HTML;
}else{
	print <<<END_OF_HTML

  <div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
    	<img src="./img/editmenu.png" height="20">
    </button>
    <ul class="dropdown-menu" role="menu">
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioSelect.php');">シナリオ選択</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swNewScenario.php');">新規シナリオ</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioEdit.php')">シナリオ編集</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioEditInfo.php')">シナリオ情報</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioEditSynopsis.php')">シノプシス</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioEditScene.php')">場面設定</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioEditCharacter.php')">登場人物設定</a></li>
{$swMenuCollab}      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swUserOption.php')">オプション設定</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJumpBlank($TargetForm,'./swScenarioView.php');">シナリオを読む</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swScenarioDownload.php');">ダウンロード</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_SubmitNoMsg($TargetForm,'./swUserLogout.php');">ログアウト</a></li>
    </ul>
  </div>
END_OF_HTML;
}
// -----------------------------------------------------------
?>
