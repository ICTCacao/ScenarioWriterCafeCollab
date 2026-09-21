// ------------------------------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//		
//		ajaxSwScenarioSelect.js
//		
//    	charset=UTF-8
// ------------------------------------------------------------------------------
// ------------------------------------------------------------------------------
//	form 制御
// ------------------------------------------------------------------------------
jQuery(document).ready(function() {
	//selectbox onchange　ｲﾍﾞﾝﾄ


});

// ------------------------------------------------------------------------------
//		ﾃﾞｰﾀﾘｽﾄ	取得 jQuery ajax 
// ------------------------------------------------------------------------------
	function fncMakeSwScenarioSelectList(frm){
		jQuery(function($){
			var $form = $(frm);
			$phppath = "./ajax/ajaxSwScenarioSelect.php";
			$.ajax({
				async:false,
				type: "POST",
				url: $phppath,
				data: $form.serialize()
					+ "&SubMode=SwScenarioList",
				success: function(result){
					$("#ScenarioSelectList").html(result);
					//2026-09: Masonry は使わない（Bootstrap の row-cols で横4列に並べる）
				},
				error:function(result){
					alert(result);
				},
			});
		});
	}

// ------------------------------------------------------------------------------
//		Scenario 選択　ScenarioEdit起動
// ------------------------------------------------------------------------------
function fncSelectSwScenario(frm,valScenarioId){

	$('#fdtScenarioId').val(valScenarioId);
	$('#SubmitMode').val('SELECT');

    frm.action = "./swScenarioEdit.php";
    frm.target = "_self";
    frm.submit();

}
// ------------------------------------------------------------------------------
//		Scenario 選択（閲覧者）　ScenarioView起動（コラボレーション版で追加）
// ------------------------------------------------------------------------------
function fncSelectSwScenarioView(frm,valScenarioId){

	$('#fdtScenarioId').val(valScenarioId);
	$('#SubmitMode').val('SELECT');

    frm.action = "./swScenarioView.php";
    frm.target = "_self";
    frm.submit();

}
