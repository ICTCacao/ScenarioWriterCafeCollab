<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
// 
//     サブメニュー
//
//     swDropDownMenu.php
// -----------------------------------------------------------
if(SW_GUEST){
	print <<<END_OF_HTML

  <div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
    	<img src="./img/editmenu.png" height="30">
    </button>
    <ul class="dropdown-menu" role="menu">
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_SubmitNoMsg($TargetForm,'./swScenarioSelect.php');">シナリオ選択</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_SubmitNoMsg($TargetForm,'./swNewScenario.php');">新規シナリオ</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_SubmitNoMsg($TargetForm,'./swScenarioImport.php');">シナリオを読み込む（インポート）</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swUserOption.php')">オプション設定</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#"><p style="color: #a9a9a9;align: center;">パスワード変更</p></a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_SubmitNoMsg($TargetForm,'./swUserLogout.php');">ログアウト</a></li>
    </ul>
  </div>
END_OF_HTML;

}else{
	print <<<END_OF_HTML

  <div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
    	<img src="./img/editmenu.png" height="30">
    </button>
    <ul class="dropdown-menu" role="menu">
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_SubmitNoMsg($TargetForm,'./swScenarioSelect.php');">シナリオ選択</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_SubmitNoMsg($TargetForm,'./swNewScenario.php');">新規シナリオ</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_SubmitNoMsg($TargetForm,'./swScenarioImport.php');">シナリオを読み込む（インポート）</a></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_EditMenuJump($TargetForm,'./swUserOption.php')">オプション設定</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_SubmitNoMsg($TargetForm,'./swUserChangePassword.php');">パスワード変更</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#" onclick="AjaxFunc_SubmitNoMsg($TargetForm,'./swUserLogout.php');">ログアウト</a></li>
    </ul>
  </div>
END_OF_HTML;
}
// -----------------------------------------------------------
?>
