<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//     シナリオを作品ファイル（.scwd）でダウンロード
//
//     swScenarioDownloadScwd.php
//
//     ScenarioWriterSolo（Mac 版・Windows 版）で開ける作品ファイル .scwd（UTF-8 の JSON、版 1）を書き出す。
//     形式は ScenarioWriterSolo/docs/scwd-format.md。対応は Mac 版の ScenarioStore.exportFile と同じ:
//       SW_SCENARIO → scenario、SW_SYNOPSIS → synopsis、SW_CHARACTER → characters（id は CHARACTER_ID）
//       SW_SCENE → scenes（SCENE_VALID_CD=0 が valid）、SW_SCENARIO_LINES → scenes[].lines（人物なしは character を省略）
//       作者の SW_USER_OPTION → styles、SW_USER_OPTION_SETTING → setting、img/thumb/<ID>.* → thumbnail（PNG の base64）
//       DB の "<br>" → "\n"
//     Web 版に無い項目（styles の前後の余白・textStyles）は書かない（読む側が既定値にする）。
//     キーは ABC 順、2 段インデント（Mac 版と同じ見た目）。
//     読み込みは swScenarioImport.php。
//
//     権限: 標準版はシナリオの作者だけ。コラボ版（include/swCollabGuard.php がある）は閲覧できるメンバー。
// -----------------------------------------------------------
// ------------------------------------------------------------------------------
	//定数読み込み
	include_once("./sw_config/swConstant.php");
	//DB接続ｸﾗｽの初期化
	include_once("./include/ConnectMySQL.php");
// ------------------------------------------------------------------------------
	//共通関数をｲﾝｸﾙｰﾄﾞ
	include_once("./include/swFunc.php");

	$fdtScenarioId = (int)str_replace(',', '', (string)swFunc_GetPostData('fdtScenarioId'));

	//ｾｯｼｮﾝ管理
	include_once("./include/swAccept.php");
	include_once("./include/swCheckAdmin.php");

	//権限: コラボ版は権限ガード（閲覧）、標準版は作者だけ
	if (is_file('./include/swCollabGuard.php')) {
		$swCollabNeed = 'read';
		include_once("./include/swCollabGuard.php");
	} else {
		if ($fdtScenarioId <= 0 || swScwd_OwnerId($mySqlConnObj, $fdtScenarioId) !== (int)$fdtUserId) {
			http_response_code(403);
			header('Content-Type: text/plain; charset=UTF-8');
			echo 'このシナリオは書き出せません。';
			exit();
		}
	}

	$f = swScwd_Build($mySqlConnObj, $fdtScenarioId);
	if ($f === null) {
		http_response_code(404);
		header('Content-Type: text/plain; charset=UTF-8');
		echo 'シナリオがありません。';
		exit();
	}
	$json = swScwd_Encode($f);

	//ダウンロードファイル名（題名。ファイル名に使えない文字は全角に）
	$title = trim((string)$f['scenario']['title']);
	if ($title === '') { $title = 'scenario'; }
	$title = str_replace(array('/', '\\', ':', '*', '?', '"', '<', '>', '|'), array('／', '＼', '：', '＊', '？', '”', '＜', '＞', '｜'), $title);
	$DLfilename = $title . '.scwd';

	while (ob_get_level() > 0) { ob_end_clean(); }
	header('Content-Type: application/octet-stream');
	if (preg_match('/\bMSIE\b|\bSafari [12345]\b/', (string)getenv('HTTP_USER_AGENT'))) {
		header('Content-Disposition: attachment; filename="' . mb_convert_encoding($DLfilename, 'Shift_JIS', 'UTF-8') . '"');
	} else {
		header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($DLfilename));
	}
	header('Content-Length: ' . strlen($json));
	echo $json;
	exit();

// ------------------------------------------------------------------------------
// シナリオの作者 USER_ID。無ければ 0
function swScwd_OwnerId($db, $scenarioId){
	$st = $db->prepare('SELECT USER_ID FROM SW_SCENARIO WHERE SCENARIO_ID = :s');
	$st->bindValue(':s', (int)$scenarioId, PDO::PARAM_INT);
	$st->execute();
	$row = $st->fetch(PDO::FETCH_ASSOC);
	return $row ? (int)$row['USER_ID'] : 0;
}

// DB の "<br>" → "\n"
function swScwd_FromDb($s){ return str_replace('<br>', "\n", (string)$s); }

function swScwd_Rows($db, $sql, $params){
	$st = $db->prepare($sql);
	foreach ($params as $k => $v) { $st->bindValue($k, (int)$v, PDO::PARAM_INT); }
	$st->execute();
	$rows = array();
	while ($r = $st->fetch(PDO::FETCH_ASSOC)) { $rows[] = $r; }
	return $rows;
}

// ------------------------------------------------------------------------------
//   作品ファイルの中身（連想配列）を組み立てる。シナリオが無ければ null
// ------------------------------------------------------------------------------
function swScwd_Build($db, $scenarioId){
	$sc = swScwd_Rows($db, 'SELECT * FROM SW_SCENARIO WHERE SCENARIO_ID = :s', array(':s' => $scenarioId));
	if (count($sc) === 0) { return null; }
	$sc = $sc[0];
	$ownerId = (int)$sc['USER_ID'];

	$date = (string)$sc['SCENARIO_DATE'];
	$t = strtotime($date);
	$date = ($t !== false) ? date('Y-m-d H:i:s', $t) : '';

	$f = array(
		'format' => 'scwd',
		'version' => 1,
		'app' => 'ScenarioWriterCafe',
		'scenario' => array(
			'title' => (string)$sc['SCENARIO_TITLE'],
			'subtitle' => (string)$sc['SCENARIO_SUBTITLE'],
			'writer' => (string)$sc['SCENARIO_WRITER_NAME'],
			'memo' => swScwd_FromDb($sc['SCENARIO_MEMO']),
			'date' => $date,
			'category' => (int)$sc['SCENARIO_CATEGORY'],
		),
	);

	//シノプシス（1 シナリオ 1 件。先頭）
	$syn = swScwd_Rows($db, 'SELECT SYNOPSIS FROM SW_SYNOPSIS WHERE SCENARIO_ID = :s ORDER BY SYNOPSIS_ID', array(':s' => $scenarioId));
	$f['synopsis'] = count($syn) ? swScwd_FromDb($syn[0]['SYNOPSIS']) : '';

	//登場人物
	$f['characters'] = array();
	$charIds = array();
	foreach (swScwd_Rows($db, 'SELECT * FROM SW_CHARACTER WHERE SCENARIO_ID = :s ORDER BY CHARACTER_ORDER_NO, CHARACTER_ID', array(':s' => $scenarioId)) as $c) {
		$id = (int)$c['CHARACTER_ID'];
		$charIds[$id] = true;
		$f['characters'][] = array('id' => $id, 'name' => (string)$c['CHARACTER_NAME'], 'chara' => swScwd_FromDb($c['CHARACTER_CHARA']));
	}

	//台詞（場面ごと）
	$bySceneLines = array();
	foreach (swScwd_Rows($db, 'SELECT * FROM SW_SCENARIO_LINES WHERE SCENARIO_ID = :s ORDER BY SCENE_ID, SCENARIO_LINES_ORDER_NO, SCENARIO_LINES_ID', array(':s' => $scenarioId)) as $l) {
		$line = array('type' => (int)$l['SCENARIO_TYPE'], 'text' => swScwd_FromDb($l['SCENARIO_LINES']));
		$cid = (int)$l['CHARACTER_ID'];
		if ($cid > 0 && isset($charIds[$cid])) { $line['character'] = $cid; }
		$bySceneLines[(int)$l['SCENE_ID']][] = $line;
	}

	//場面
	$f['scenes'] = array();
	foreach (swScwd_Rows($db, 'SELECT * FROM SW_SCENE WHERE SCENARIO_ID = :s ORDER BY SCENE_ORDER_NO, SCENE_ID', array(':s' => $scenarioId)) as $s) {
		$sid = (int)$s['SCENE_ID'];
		$f['scenes'][] = array(
			'name' => (string)$s['SCENE_NAME'],
			'description' => swScwd_FromDb($s['SCENE_DESCRIPTION']),
			'valid' => ((string)$s['SCENE_VALID_CD'] === '' || (int)$s['SCENE_VALID_CD'] === 0),
			'minutes' => (int)$s['SCENE_TIME_MIN'],
			'seconds' => (int)$s['SCENE_TIME_SEC'],
			'lines' => isset($bySceneLines[$sid]) ? $bySceneLines[$sid] : array(),
		);
	}

	//スタイル（作者の設定。無ければ既定の USER_ID=0）
	$styleRows = swScwd_Rows($db, 'SELECT * FROM SW_USER_OPTION WHERE USER_ID = :u ORDER BY USER_OPTION_STYLE_ORDER_NO, USER_OPTION_STYLE_ID', array(':u' => $ownerId));
	if (count($styleRows) === 0) {
		$styleRows = swScwd_Rows($db, 'SELECT * FROM SW_USER_OPTION WHERE USER_ID = :u ORDER BY USER_OPTION_STYLE_ORDER_NO, USER_OPTION_STYLE_ID', array(':u' => 0));
	}
	$f['styles'] = array();
	foreach ($styleRows as $r) {
		$color = (string)$r['USER_OPTION_STYLE_COLOR'];
		if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) { $color = '#000000'; }
		$f['styles'][] = array(
			'id' => (int)$r['USER_OPTION_STYLE_ID'],
			'order' => (int)$r['USER_OPTION_STYLE_ORDER_NO'],
			'name' => (string)$r['USER_OPTION_STYLE_NAME'],
			'size' => ((int)$r['USER_OPTION_STYLE_FONT_SIZE'] > 0) ? (int)$r['USER_OPTION_STYLE_FONT_SIZE'] : 12,
			'color' => strtolower($color),
			'indent' => (int)$r['USER_OPTION_STYLE_INDENT'],
			'abbr' => (string)$r['USER_OPTION_STYLE_STR'],
			'mode' => (int)$r['USER_OPTION_STYLE_WORD'],
			'marginBefore' => 0,
			'marginAfter' => 0,
		);
	}

	//書式設定（作者の設定）
	$set = swScwd_Rows($db, 'SELECT * FROM SW_USER_OPTION_SETTING WHERE USER_ID = :u', array(':u' => $ownerId));
	$set = count($set) ? $set[0] : array();
	$f['setting'] = array(
		'characterLength' => (isset($set['CHARACTER_LENGTH']) && (int)$set['CHARACTER_LENGTH'] > 0) ? (int)$set['CHARACTER_LENGTH'] : 8,
		'bodyLength' => (isset($set['BODY_LENGTH']) && (int)$set['BODY_LENGTH'] > 0) ? (int)$set['BODY_LENGTH'] : 32,
		'kagikakko' => isset($set['USE_KAGIKAKKO']) ? ((int)$set['USE_KAGIKAKKO'] !== 0) : true,
	);

	//作品画像
	$png = swScwd_ThumbPng($scenarioId);
	if ($png !== null) { $f['thumbnail'] = base64_encode($png); }

	return $f;
}

// img/thumb/<ID>.* を PNG のバイト列にする（PNG 以外は GD で変換。できなければ null）
function swScwd_ThumbPng($scenarioId){
	foreach (glob('./img/thumb/' . (int)$scenarioId . '.*') as $path) {
		if (!is_file($path)) { continue; }
		$bin = @file_get_contents($path);
		if ($bin === false || $bin === '') { continue; }
		$info = @getimagesizefromstring($bin);
		if ($info === false) { continue; }
		if ($info[2] === IMAGETYPE_PNG) { return $bin; }
		if (function_exists('imagecreatefromstring') && ($img = @imagecreatefromstring($bin)) !== false) {
			ob_start();
			imagepng($img);
			return ob_get_clean();
		}
	}
	return null;
}

// ------------------------------------------------------------------------------
//   JSON にする（キーは ABC 順、2 段インデント、日本語と / はそのまま。Mac 版と同じ見た目）
// ------------------------------------------------------------------------------
function swScwd_SortKeys($v){
	if (!is_array($v)) { return $v; }
	$isList = ($v === array()) || (array_keys($v) === range(0, count($v) - 1));
	if (!$isList) { ksort($v, SORT_STRING); }
	foreach ($v as $k => $x) { $v[$k] = swScwd_SortKeys($x); }
	return $v;
}

function swScwd_Encode($f){
	$json = json_encode(swScwd_SortKeys($f), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
	//PHP は 4 段インデント。行頭の空白を半分にする（文字列中の改行は \n にエスケープ済みなので行頭は必ずインデント）
	return preg_replace_callback('/^( +)/m', function($m){ return str_repeat(' ', intdiv(strlen($m[1]), 2)); }, $json) . "\n";
}
// -----------------------------------------------------------
?>
