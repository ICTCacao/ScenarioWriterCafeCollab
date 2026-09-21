<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//     コラボ制作（メンバー・役割・招待・更新履歴）プロデューサー限定
//
//     swScenarioMember.php（コラボレーション版で追加）
//
//     プロデューサー（作者）だけが開ける。役割の定義（役割名 + 権限の段階）、メンバーの追加（登録済みユーザーを
//     メールアドレスで）・役割変更・削除、招待 URL の発行と無効化、在席と最近の更新履歴。
//     一覧や操作は ajax/ajaxSwCollab.php（JSON）＋ ajax/ajaxSwCollab.js
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
	//コラボ制作: プロデューサー限定
	$swCollabNeed = 'owner';
	include_once("./include/swCollabGuard.php");
// ------------------------------------------------------------------------------
	//ﾃﾞﾌｫﾙﾄｱｸｼｮﾝ
	$ThisPHP = 'swScenarioMember.php';
	//ﾃﾞｰﾀ管理ｸﾗｽ
	include_once("./class/clsSwScenario.php");
	//共通関数をｲﾝｸﾙｰﾄﾞ
	include_once("./include/swFunc.php");

	// ﾍｯﾀﾞ表示
	$HtmlTitle = "コラボ制作";
	include_once("./include/swHeader.php");

	//ﾌｫｰﾑのﾃﾞｰﾀを読み込む
	fncGetPostItems();

	//MainProcedure
	fncMainForm($mySqlConnObj);

	exit();

// ------------------------------------------------------------------------------
//      POSTされた要素を取得
// ------------------------------------------------------------------------------
function fncGetPostItems(){
	global	$fdtUserLoginId,$fdtUserLoginDate,$fdtScenarioId;
	$fdtUserLoginId = swFunc_GetPostData('fdtUserLoginId');          //USERログインID
	$fdtUserLoginDate = swFunc_GetPostData('fdtUserLoginDate');      //USERログイン日時
	$fdtScenarioId = swFunc_GetPostData('fdtScenarioId');            //シナリオID
}//end function

// ------------------------------------------------------------------------------
//      MAIN FORM
// ------------------------------------------------------------------------------
function fncMainForm($mySqlConnObj){
	global	$fdtUserLoginId,$fdtUserLoginDate,$fdtScenarioId;

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

	$fdtScenarioIdHtml = swFunc_SanitizeStrings($fdtScenarioId);
	$fdtUserLoginIdHtml = swFunc_SanitizeStrings($fdtUserLoginId);
	$fdtUserLoginDateHtml = swFunc_SanitizeStrings($fdtUserLoginDate);
	$meHtml = (int)$fdtUserId;

	include_once("./include/swMenuBar.php");
	swMenuBar_Print('./include/swEditDropDownMenu.php', 'frmSwScenario', $fdtScenarioTitle, array(), $fdtUserName, 'プロデューサー限定: 役割の定義・メンバー・招待 URL');

	print <<<END_OF_HTML

		<!-- main -->
		<div class="scenarioedit">
			<div class="row row-0">
				<div class="col-sm-11 row-0">
					<div class="edit-jumbotron">
						<form class="" role="form" name="frmSwScenario" id="frmSwScenario" method="POST" action="">
							<input type="hidden" name="SubmitMode" id="SubmitMode" value="">
							<input type="hidden" name="fdtUserLoginId" id="fdtUserLoginId" value="{$fdtUserLoginIdHtml}">
							<input type="hidden" name="fdtUserLoginDate" id="fdtUserLoginDate" value="{$fdtUserLoginDateHtml}">
							<input type="hidden" name="fdtScenarioId" id="fdtScenarioId" value="{$fdtScenarioIdHtml}">
							<input type="hidden" id="swCollabMe" value="{$meHtml}">

							<h5>役割の定義</h5>
							<div class="mb-1" id="swCollabRoles"></div>
							<div class="row row-0 mb-2">
								<div class="col-sm-4">
									<input type="text" class="form-control form-control-sm" id="swCollabNewRoleName" placeholder="役割名（例: 演出、音響、キャスト）">
								</div>
								<div class="col-sm-3">
									<select class="form-select form-select-sm" id="swCollabNewRoleLevel">
										<option value="editor">編集できる</option>
										<option value="reader">閲覧のみ</option>
									</select>
								</div>
								<div class="col-sm-3">
									<button type="button" class="btn btn-outline-success btn-sm" onclick="swCollab_addRole();">役割を追加</button>
								</div>
							</div>
							<div class="form-text mb-3">
								役割名は自由です。権限は 2 段階で、<b>編集できる</b>: 台詞・場面・登場人物・シノプシス・シナリオ情報の編集、
								<b>閲覧のみ</b>: 読む・ダウンロードだけ。削除・複写・メンバー管理はプロデューサーだけができます。
							</div>

							<h5>メンバー</h5>
							<table class="table table-sm sw-member-table">
								<thead><tr><th>名前</th><th>メールアドレス</th><th>役割</th><th></th></tr></thead>
								<tbody id="swCollabMemberRows"><tr><td colspan="4" class="text-muted">読み込み中…</td></tr></tbody>
							</table>

							<h5>メンバーを追加（登録済みのユーザー）</h5>
							<div class="row row-0 mb-3">
								<div class="col-sm-5">
									<input type="email" class="form-control form-control-sm" id="swCollabAddMail" placeholder="相手のメールアドレス">
								</div>
								<div class="col-sm-3">
									<select class="form-select form-select-sm" id="swCollabAddRoleSel"></select>
								</div>
								<div class="col-sm-3">
									<button type="button" class="btn btn-success btn-sm" onclick="swCollab_addMember();">追加する</button>
								</div>
							</div>

							<h5>招待 URL（まだ登録していない人を招く）</h5>
							<div class="row row-0 mb-2">
								<div class="col-sm-3">
									<select class="form-select form-select-sm" id="swCollabInvRole"></select>
								</div>
								<div class="col-sm-3">
									<select class="form-select form-select-sm" id="swCollabInvDays">
										<option value="7">7 日間有効</option>
										<option value="30">30 日間有効</option>
										<option value="0">無期限</option>
									</select>
								</div>
								<div class="col-sm-3">
									<button type="button" class="btn btn-success btn-sm" onclick="swCollab_createInvite();">招待 URL を発行</button>
								</div>
							</div>
							<div id="swCollabInvites" class="mb-1"></div>
							<div class="form-text mb-3">URL を受け取った人は、ログインするか新しくアカウントを作ってこのシナリオに参加します。同じ URL を複数の人に渡せます。不要になったら無効にしてください。</div>

							<div class="form-text" id="swCollabMsg"></div>

							<hr>
							<h5>最近の更新</h5>
							<ul class="list-unstyled sw-activity" id="swCollabActivities"><li class="text-muted">読み込み中…</li></ul>
						</form>
					</div>
				</div>
			</div>
		</div>

	<script type="text/javascript">
		jQuery(document).ready(function(){ swCollab_membersInit(); });
	</script>

	</body>
</html>

END_OF_HTML;

}//end function
// -----------------------------------------------------------
?>
