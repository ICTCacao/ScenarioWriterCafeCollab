// ------------------------------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//		コラボ制作（コラボレーション版）
//		ajaxSwCollab.js
//
//		1) swCollab_start(opt)  … メニューバー（include/swMenuBar.php）から呼ばれる。
//		   20 秒おきに ajaxSwCollab.php SubMode=Status を呼び、在席と「他の人の更新」を表示する。
//		   他の人の更新は自動で再読み込みせず、通知バーに「再読み込み」リンクを出す
//		   （インライン編集中の入力を壊さないため）。
//		2) swCollab_members*()  … コラボ制作画面（swScenarioMember.php。プロデューサー限定）のメンバー・役割・招待・履歴の操作
//
// ------------------------------------------------------------------------------
var swCollab = { opt: null, sinceId: 0, timer: null, pending: [], hidden: false };

var swCollab_pageNames = {
	'swScenarioEdit.php': 'シナリオ編集', 'swScenarioEditInfo.php': 'シナリオ情報', 'swScenarioEditSynopsis.php': 'シノプシス',
	'swScenarioEditScene.php': '場面設定', 'swScenarioEditCharacter.php': '登場人物設定', 'swScenarioView.php': 'シナリオを読む',
	'swScenarioDownload.php': 'ダウンロード', 'swScenarioMember.php': 'コラボ制作', 'swUserOption.php': 'オプション設定'
};

function swCollab_esc(s){
	return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; });
}

function swCollab_post(subMode, extra, done){
	var d = { SubMode: subMode, fdtUserLoginId: swCollab.opt.loginId, fdtScenarioId: swCollab.opt.scenarioId };
	if (extra) { for (var k in extra) { d[k] = extra[k]; } }
	$.ajax({ type: 'POST', url: './ajax/ajaxSwCollab.php', data: d, dataType: 'json',
		success: function(r){ done(r); },
		error: function(xhr){
			var r = null;
			try { r = JSON.parse(xhr.responseText); } catch (e) {}
			done(r || { ok: false, message: '通信エラー' });
		}
	});
}

// ------------------------------------------------------------------------------
//	ポーリング
// ------------------------------------------------------------------------------
function swCollab_start(opt){
	swCollab.opt = opt;
	if (!opt || !opt.scenarioId || !opt.loginId) { return; }
	swCollab_poll();
	swCollab.timer = setInterval(swCollab_poll, 20000);
	//タブが隠れている間は止める（戻ったらすぐ 1 回）
	document.addEventListener('visibilitychange', function(){
		if (document.hidden) { swCollab.hidden = true; }
		else if (swCollab.hidden) { swCollab.hidden = false; swCollab_poll(); }
	});
}

function swCollab_poll(){
	if (document.hidden) { return; }
	swCollab_post('Status', { page: swCollab.opt.page, sinceId: swCollab.sinceId }, function(r){
		if (!r || !r.ok) { return; }
		//在席
		var p = $('#swCollabPresence');
		if (p.length) {
			if (r.presence && r.presence.length) {
				var names = $.map(r.presence, function(u){ return swCollab_esc(u.name) + '<small>(' + swCollab_esc(swCollab_pageNames[u.page] || u.page) + ')</small>'; });
				p.html('在席: ' + names.join(', '));
			} else {
				p.html('');
			}
		}
		//他の人の更新（初回は基準だけ覚える）
		if (swCollab.sinceId === 0) { swCollab.sinceId = r.maxId || 0; return; }
		if (r.activities && r.activities.length) {
			for (var i = r.activities.length - 1; i >= 0; i--) { swCollab.pending.push(r.activities[i]); }
			swCollab_showBar();
		}
		if (r.maxId > swCollab.sinceId) { swCollab.sinceId = r.maxId; }
		//共同執筆画面なら履歴も更新
		if (typeof swCollab_membersRefreshActivities === 'function' && $('#swCollabActivities').length && r.activities && r.activities.length) {
			swCollab_membersRefreshActivities();
		}
	});
}

function swCollab_showBar(){
	var bar = $('#swCollabBar');
	if (!bar.length || !swCollab.pending.length) { return; }
	var last = swCollab.pending[swCollab.pending.length - 1];
	var t = (last.date || '').substr(11, 5);
	var msg = swCollab_esc(last.user) + ' さんが「' + swCollab_esc(last.kind) + '」' + (last.note ? '（' + swCollab_esc(last.note) + '）' : '') + ' ' + swCollab_esc(t);
	if (swCollab.pending.length > 1) { msg += '　ほか ' + (swCollab.pending.length - 1) + ' 件'; }
	msg += '　この画面の表示は古くなっています。';
	msg += '<a onclick="swCollab_reload();">再読み込み</a>';
	msg += '<a class="sw-collab-close" onclick="swCollab_dismiss();" title="閉じる">×</a>';
	bar.html(msg).show();
}

function swCollab_dismiss(){
	swCollab.pending = [];
	$('#swCollabBar').hide();
}

// 再読み込み。画面が swCollab_refresh を定義していればそれを使う（シナリオ編集は場面の表示だけ更新）。
// 無ければフォームを同じ画面に POST し直す（location.reload だと POST の再送確認が出るため）。
function swCollab_reload(){
	swCollab_dismiss();
	if (typeof window.swCollab_refresh === 'function') { window.swCollab_refresh(); return; }
	var f = swCollab.opt.form ? document.forms[swCollab.opt.form] : null;
	if (f) {
		if (f.elements['SubmitMode']) { f.elements['SubmitMode'].value = ''; }
		f.action = location.pathname;
		f.target = '_self';
		f.submit();
	} else {
		location.reload();
	}
}

// ------------------------------------------------------------------------------
//	コラボ制作画面（swScenarioMember.php）
// ------------------------------------------------------------------------------
function swCollab_membersInit(){
	swCollab_membersRefresh();
	swCollab_membersRefreshActivities();
}

function swCollab_membersMsg(r){
	$('#swCollabMsg').text((r && r.message) ? r.message : '').toggleClass('text-danger', !(r && r.ok)).toggleClass('text-success', !!(r && r.ok));
}

var swCollab_levelName = { owner: 'プロデューサー', editor: '編集できる', reader: '閲覧のみ' };
var swCollab_roles = [];

function swCollab_roleOptions(selectedName){
	var h = '';
	$.each(swCollab_roles, function(i, r){
		h += '<option value="' + r.id + '"' + (r.name === selectedName ? ' selected' : '') + '>' + swCollab_esc(r.name) + '（' + swCollab_esc(swCollab_levelName[r.level]) + '）</option>';
	});
	return h;
}

function swCollab_membersRefresh(){
	swCollab_post('Members', {}, function(r){
		if (!r || !r.ok) { swCollab_membersMsg(r); return; }
		swCollab_roles = r.roles || [];
		//役割の定義
		var rh = '';
		$.each(swCollab_roles, function(i, x){
			rh += '<span class="badge rounded-pill text-bg-light border me-2 mb-1" style="font-weight:normal;">' + swCollab_esc(x.name)
				+ ' <small class="text-muted">' + swCollab_esc(swCollab_levelName[x.level]) + '</small>'
				+ ' <a href="#" class="text-danger text-decoration-none" title="この役割を削除" onclick="swCollab_deleteRole(' + x.id + ', \'' + swCollab_esc(x.name).replace(/'/g, '') + '\'); return false;">×</a></span>';
		});
		$('#swCollabRoles').html(rh);
		$('#swCollabAddRoleSel').html(swCollab_roleOptions(''));
		$('#swCollabInvRole').html(swCollab_roleOptions(''));
		//メンバー
		var h = '';
		$.each(r.members, function(i, m){
			var badge = '<span class="sw-role-badge ' + m.role + '">' + swCollab_esc(m.label) + '</span>'
				+ (m.role !== 'owner' ? ' <small class="text-muted">' + swCollab_esc(swCollab_levelName[m.role]) + '</small>' : '');
			var ctl = '';
			if (m.role !== 'owner') {
				ctl = '<select class="form-select form-select-sm d-inline-block w-auto" onchange="swCollab_setRole(' + m.id + ', this.value);">'
					+ '<option value="">役割を変更…</option>' + swCollab_roleOptions(m.title) + '</select> '
					+ '<button type="button" class="btn btn-outline-danger btn-sm" onclick="swCollab_remove(' + m.id + ', \'' + swCollab_esc(m.name).replace(/'/g, '') + '\');">外す</button>';
			}
			h += '<tr><td>' + swCollab_esc(m.name) + (m.id === r.me ? ' <small class="text-muted">(自分)</small>' : '') + '</td><td>' + swCollab_esc(m.mail) + '</td><td>' + badge + '</td><td>' + ctl + '</td></tr>';
		});
		$('#swCollabMemberRows').html(h);
		//招待
		var ih = '';
		if (!r.invites || !r.invites.length) {
			ih = '<div class="form-text">有効な招待 URL はありません。</div>';
		} else {
			$.each(r.invites, function(i, v){
				ih += '<div class="input-group input-group-sm mb-1">'
					+ '<span class="input-group-text">' + swCollab_esc(v.label) + (v.expire ? ' 〜' + swCollab_esc(v.expire.substr(0, 10)) : ' 無期限') + '</span>'
					+ '<input type="text" class="form-control" readonly value="' + swCollab_esc(v.url) + '" onclick="this.select();">'
					+ '<button type="button" class="btn btn-outline-secondary" onclick="swCollab_copy(this);">コピー</button>'
					+ '<button type="button" class="btn btn-outline-danger" onclick="swCollab_revoke(\'' + swCollab_esc(v.key) + '\');">無効にする</button>'
					+ '</div>';
			});
		}
		$('#swCollabInvites').html(ih);
	});
}

function swCollab_membersRefreshActivities(){
	swCollab_post('Activities', {}, function(r){
		if (!r || !r.ok) { return; }
		var h = '';
		if (!r.activities.length) { h = '<li class="text-muted">まだ更新履歴はありません。</li>'; }
		$.each(r.activities, function(i, a){
			h += '<li><span class="sw-act-date">' + swCollab_esc(a.date.substr(5, 11)) + '</span><span class="sw-act-user">' + swCollab_esc(a.user) + '</span>' + swCollab_esc(a.kind)
				+ (a.note ? '<span class="sw-act-note">' + swCollab_esc(a.note) + '</span>' : '') + '</li>';
		});
		$('#swCollabActivities').html(h);
	});
}

function swCollab_addMember(){
	var mail = $.trim($('#swCollabAddMail').val());
	if (!mail) { bootbox.alert('メールアドレスを入力してください。'); return; }
	swCollab_post('AddMember', { mail: mail, roleId: $('#swCollabAddRoleSel').val() }, function(r){
		swCollab_membersMsg(r);
		if (r && r.ok) { $('#swCollabAddMail').val(''); swCollab_membersRefresh(); swCollab_membersRefreshActivities(); }
	});
}

function swCollab_setRole(userId, roleId){
	if (!roleId) { return; }
	swCollab_post('SetRole', { userId: userId, roleId: roleId }, function(r){
		swCollab_membersMsg(r);
		swCollab_membersRefresh(); swCollab_membersRefreshActivities();
	});
}

function swCollab_remove(userId, name){
	bootbox.confirm(name + ' さんをこのシナリオから外します。よろしいですか？', function(ok){
		if (!ok) { return; }
		swCollab_post('RemoveMember', { userId: userId }, function(r){
			swCollab_membersMsg(r);
			swCollab_membersRefresh(); swCollab_membersRefreshActivities();
		});
	});
}

function swCollab_addRole(){
	var name = $.trim($('#swCollabNewRoleName').val());
	if (!name) { bootbox.alert('役割名を入力してください。'); return; }
	swCollab_post('AddRole', { name: name, level: $('#swCollabNewRoleLevel').val() }, function(r){
		swCollab_membersMsg(r);
		if (r && r.ok) { $('#swCollabNewRoleName').val(''); swCollab_membersRefresh(); }
	});
}

function swCollab_deleteRole(roleId, name){
	bootbox.confirm('役割「' + name + '」を削除します。既にその役割の人はそのまま残ります。よろしいですか？', function(ok){
		if (!ok) { return; }
		swCollab_post('DeleteRole', { roleId: roleId }, function(r){
			swCollab_membersMsg(r);
			swCollab_membersRefresh();
		});
	});
}

function swCollab_createInvite(){
	swCollab_post('CreateInvite', { roleId: $('#swCollabInvRole').val(), days: $('#swCollabInvDays').val() }, function(r){
		swCollab_membersMsg(r);
		if (r && r.ok) { swCollab_membersRefresh(); swCollab_membersRefreshActivities(); }
	});
}

function swCollab_revoke(key){
	bootbox.confirm('この招待 URL を無効にします。よろしいですか？', function(ok){
		if (!ok) { return; }
		swCollab_post('RevokeInvite', { key: key }, function(r){
			swCollab_membersMsg(r);
			swCollab_membersRefresh();
		});
	});
}

function swCollab_copy(btn){
	var inp = $(btn).closest('.input-group').find('input')[0];
	if (!inp) { return; }
	var done = function(){ $('#swCollabMsg').removeClass('text-danger').addClass('text-success').text('URL をコピーしました。'); };
	if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(inp.value).then(done, function(){ inp.select(); document.execCommand('copy'); done(); }); }
	else { inp.select(); document.execCommand('copy'); done(); }
}
