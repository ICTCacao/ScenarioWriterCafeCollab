<?php
// -----------------------------------------------------------
//
// Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//     作品ファイル（.scwd）を読み込む
//
//     swScenarioImport.php
//
//     ScenarioWriterSolo（Mac 版・Windows 版）が保存した作品ファイル .scwd（UTF-8 の JSON、版 1）を
//     アップロードし、ログイン中のユーザーのシナリオとして新しい ID で登録する。
//     形式は ScenarioWriterSolo/docs/scwd-format.md。対応は Mac 版の ScenarioStore.importFile と同じ:
//       scenario → SW_SCENARIO（更新日時はファイルの値を保つ）
//       synopsis → SW_SYNOPSIS
//       characters → SW_CHARACTER（ファイル内の id → 新しい CHARACTER_ID）
//       scenes → SW_SCENE（valid=false は SCENE_VALID_CD=9）、lines → SW_SCENARIO_LINES（人物なしは -1）
//       thumbnail → img/thumb/<新シナリオID>.png（幅 250 に縮小）
//       改行は "\n" → DB では "<br>"（Web 版の編集画面と同じ）
//     スタイル（styles）はユーザー単位の設定なので上書きしない。ユーザーに無い種別番号だけ追加する。
//     textStyles / setting（文字数・「」）も上書きしない。
//     β5 より前の SQLite 形式の .scwd は受け付けない（Solo で開いて保存し直すと JSON になる）。
//
//     1 ファイル 1 トランザクション。失敗したファイルだけ取り消し、結果表に理由を出す。
//     メニュー（シナリオ選択のドロップダウン）の「シナリオを読み込む（インポート）」から開く。
//     書き出しは swScenarioDownloadScwd.php（ダウンロード画面の「作品ファイル.scwd」）。
//     標準版・コラボ版で同じファイル。コラボ版では読み込んだ人がプロデューサーになり、更新履歴に残す。
//
// ------------------------------------------------------------------------------
  //定数読み込み
  include_once("./sw_config/swConstant.php");
  //DB接続
  include_once("./include/ConnectMySQL.php");
// ------------------------------------------------------------------------------
  //ｾｯｼｮﾝ管理
  include_once("./include/swAccept.php");
  //ログインユーザー取得（$fdtUserId, $fdtUserName）
  include_once("./include/swCheckAdmin.php");
// ------------------------------------------------------------------------------
  //共通関数
  include_once("./include/swFunc.php");
  include_once("./include/swMenuBar.php");
  //ﾃﾞｰﾀ管理ｸﾗｽ
  include_once("./class/clsSwScenario.php");
  include_once("./class/clsSwCharacter.php");
  include_once("./class/clsSwScene.php");
  include_once("./class/clsSwScenarioLines.php");
  include_once("./class/clsSwSynopsis.php");
  include_once("./class/clsSwUserOption.php");

  $ThisPHP = 'swScenarioImport.php';

  // ﾍｯﾀﾞ表示
  $HtmlTitle = "作品ファイルを読み込む";
  include_once("./include/swHeader.php");

  //処理時間無制限
  set_time_limit(0);

  $SubmitMode = swFunc_GetPostData('SubmitMode');

  swMenuBar_Print('./include/swDropDownMenu.php', 'frmSwImport', '作品ファイルを読み込む', array(), $fdtUserName);
  print '<div class="container" style="max-width: 960px; margin-top: 80px;">';
  print '<form name="frmSwImport" id="frmSwImport" method="post" action="' . swImport_H($ThisPHP) . '" enctype="multipart/form-data">';
  print '<input type="hidden" name="fdtUserLoginId" value="' . swImport_H($fdtUserLoginId) . '">';
  print '<input type="hidden" name="fdtUserLoginDate" value="' . swImport_H($fdtUserLoginDate) . '">';
  print '<input type="hidden" name="SubmitMode" id="SubmitMode" value="">';

  if ($SubmitMode === 'IMPORT') {
    $result = array();
    foreach (swImport_UploadedFiles('scwdFiles') as $up) {
      $r = array('file' => $up['name'], 'title' => '', 'newId' => '', 'characters' => 0, 'scenes' => 0, 'lines' => 0, 'styles' => 0, 'thumb' => false, 'notes' => array(), 'error' => '');
      if ($up['error'] !== '') { $r['error'] = $up['error']; $result[] = $r; continue; }
      $f = swImport_Parse(file_get_contents($up['tmp']), $err);
      if ($f === null) { $r['error'] = $err; $result[] = $r; continue; }
      $result[] = swImport_Import($mySqlConnObj, $f, $fdtUserId, $r);
    }
    swImport_PrintResult($result);
  } else {
    swImport_PrintForm($fdtUserName);
  }

  print '</form></div></body></html>';
  exit();

// ------------------------------------------------------------------------------
function swImport_H($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// "\r\n" / "\r" / "\n" → DB の "<br>"（Web 版の編集画面・Mac 版 DBText.toDB と同じ）
function swImport_ToDb($s){
  return str_replace(array("\r\n", "\r", "\n"), array("<br>", "<br>", "<br>"), (string)$s);
}

// 無い・型違いの値は既定値にする（別のアプリが書いたファイルにも寛容に。Mac 版と同じ方針）
function swImport_Str($a, $k, $def = ''){ return (is_array($a) && isset($a[$k]) && (is_string($a[$k]) || is_numeric($a[$k]))) ? (string)$a[$k] : $def; }
function swImport_Int($a, $k, $def = 0){ return (is_array($a) && isset($a[$k]) && is_numeric($a[$k])) ? (int)$a[$k] : $def; }
function swImport_Bool($a, $k, $def){ return (is_array($a) && isset($a[$k]) && is_bool($a[$k])) ? $a[$k] : $def; }
function swImport_Arr($a, $k){ return (is_array($a) && isset($a[$k]) && is_array($a[$k])) ? $a[$k] : array(); }

// アップロードされたファイルを [name, tmp, error] の配列にする
function swImport_UploadedFiles($field){
  $out = array();
  if (!isset($_FILES[$field]) || !is_array($_FILES[$field]['name'])) { return $out; }
  foreach ($_FILES[$field]['name'] as $i => $name) {
    $code = $_FILES[$field]['error'][$i];
    if ($code === UPLOAD_ERR_NO_FILE) { continue; }
    $e = '';
    if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) { $e = 'ファイルが大きすぎます（サーバの upload_max_filesize / post_max_size を確認してください）'; }
    elseif ($code !== UPLOAD_ERR_OK) { $e = 'アップロードに失敗しました（コード ' . $code . '）'; }
    elseif (!is_uploaded_file($_FILES[$field]['tmp_name'][$i])) { $e = 'アップロードされたファイルではありません'; }
    $out[] = array('name' => $name, 'tmp' => $_FILES[$field]['tmp_name'][$i], 'error' => $e);
  }
  return $out;
}

// ------------------------------------------------------------------------------
//   読み取り: .scwd（JSON）を配列にする。作品ファイルでなければ null と理由
// ------------------------------------------------------------------------------
function swImport_Parse($data, &$err){
  $err = '';
  if ($data === false || $data === '') { $err = 'ファイルが空です'; return null; }
  if (strncmp($data, 'SQLite format 3', 15) === 0) {
    $err = '旧形式（SQLite）の作品ファイルです。ScenarioWriterSolo（β5 以降）で開いて保存し直すと読み込めます';
    return null;
  }
  if (strncmp($data, "\xEF\xBB\xBF", 3) === 0) { $data = substr($data, 3); }
  $f = json_decode($data, true);
  if (!is_array($f) || swImport_Str($f, 'format') !== 'scwd') { $err = 'ScenarioWriter の作品ファイル（.scwd）ではありません'; return null; }
  $ver = swImport_Int($f, 'version', 1);
  if ($ver > 1) { $err = 'この作品ファイルは新しい形式（版 ' . $ver . '）です。Web 版を更新してください'; return null; }
  return $f;
}

// ------------------------------------------------------------------------------
//   取り込み: 1 ファイルを 1 トランザクションで登録する
// ------------------------------------------------------------------------------
function swImport_Import($db, $f, $userId, $r){
  $sc = swImport_Arr($f, 'scenario');
  $r['title'] = swImport_Str($sc, 'title');
  $prevMode = $db->getAttribute(PDO::ATTR_ERRMODE);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  try {
    $db->beginTransaction();

    //スタイル: ユーザーに無い種別番号だけ追加（既存の見た目は変えない）
    $r['styles'] = swImport_AddMissingStyles($db, $userId, swImport_Arr($f, 'styles'));

    //作品情報
    $o = new clsSwScenario();
    $o->clsSwScenarioSetUserId($userId);
    $o->clsSwScenarioSetScenarioTitle($r['title'] !== '' ? $r['title'] : '名称未設定');
    $o->clsSwScenarioSetScenarioSubtitle(swImport_Str($sc, 'subtitle'));
    $o->clsSwScenarioSetScenarioWriterName(swImport_Str($sc, 'writer'));
    $o->clsSwScenarioSetScenarioMemo(swImport_ToDb(swImport_Str($sc, 'memo')));
    $cat = swImport_Int($sc, 'category', 0);
    $o->clsSwScenarioSetScenarioCategory(($cat >= 0 && $cat <= 10) ? $cat : 0);
    $newId = $o->clsSwScenarioDbInsert($db);

    //シノプシス（無くても 1 件作る。Web 版の新規シナリオと同じ）
    $oy = new clsSwSynopsis();
    $oy->clsSwSynopsisSetScenarioId($newId);
    $oy->clsSwSynopsisSetSynopsis(swImport_ToDb(swImport_Str($f, 'synopsis')));
    $oy->clsSwSynopsisDbInsert($db);

    //登場人物（ファイルの id → 新しい CHARACTER_ID）
    $charMap = array();
    $order = 0;
    foreach (swImport_Arr($f, 'characters') as $c) {
      if (!is_array($c)) { continue; }
      $order += 100;
      $oc = new clsSwCharacter();
      $oc->clsSwCharacterSetScenarioId($newId);
      $oc->clsSwCharacterSetCharacterOrderNo($order);
      $oc->clsSwCharacterSetCharacterName(swImport_Str($c, 'name'));
      $oc->clsSwCharacterSetCharacterChara(swImport_ToDb(swImport_Str($c, 'chara')));
      $charMap[swImport_Str($c, 'id')] = $oc->clsSwCharacterDbInsert($db);
      $r['characters']++;
    }

    //場面と台詞
    $order = 0;
    $unknownChar = 0;
    foreach (swImport_Arr($f, 'scenes') as $s) {
      if (!is_array($s)) { continue; }
      $order += 100;
      $os = new clsSwScene();
      $os->clsSwSceneSetScenarioId($newId);
      $os->clsSwSceneSetSceneOrderNo($order);
      $os->clsSwSceneSetSceneValidCd(swImport_Bool($s, 'valid', true) ? 0 : 9);
      $os->clsSwSceneSetSceneName(swImport_Str($s, 'name'));
      $os->clsSwSceneSetSceneDescription(swImport_ToDb(swImport_Str($s, 'description')));
      $os->clsSwSceneSetSceneTimeMin(swImport_Int($s, 'minutes', 0));
      $os->clsSwSceneSetSceneTimeSec(swImport_Int($s, 'seconds', 0));
      $sceneId = $os->clsSwSceneDbInsert($db);
      $r['scenes']++;

      $lineOrder = 0;
      foreach (swImport_Arr($s, 'lines') as $l) {
        if (!is_array($l)) { continue; }
        $lineOrder += 100;
        $charId = -1;
        $ck = swImport_Str($l, 'character');
        if ($ck !== '') {
          if (isset($charMap[$ck])) { $charId = $charMap[$ck]; } else { $unknownChar++; }
        }
        $ol = new clsSwScenarioLines();
        $ol->clsSwScenarioLinesSetScenarioId($newId);
        $ol->clsSwScenarioLinesSetSceneId($sceneId);
        $ol->clsSwScenarioLinesSetScenarioLinesOrderNo($lineOrder);
        $ol->clsSwScenarioLinesSetScenarioType(swImport_Int($l, 'type', 1));
        $ol->clsSwScenarioLinesSetCharacterId($charId);
        $ol->clsSwScenarioLinesSetScenarioLines(swImport_ToDb(swImport_Str($l, 'text')));
        $ol->clsSwScenarioLinesDbInsert($db);
        $r['lines']++;
      }
    }
    if ($unknownChar > 0) { $r['notes'][] = '登場人物が見つからない台詞 ' . $unknownChar . ' 行（人物なしで登録）'; }

    //更新日時はファイルの値を保つ（Mac 版と同じ）。形式が違えば取り込んだ時刻のまま
    $date = swImport_Str($sc, 'date');
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date)) {
      $st = $db->prepare('UPDATE SW_SCENARIO SET SCENARIO_DATE = :d WHERE SCENARIO_ID = :id');
      $st->bindValue(':d', $date, PDO::PARAM_STR);
      $st->bindValue(':id', (int)$newId, PDO::PARAM_INT);
      $st->execute();
    }

    $db->commit();
    $r['newId'] = $newId;
  } catch (Exception $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    $r['error'] = $e->getMessage();
    $db->setAttribute(PDO::ATTR_ERRMODE, $prevMode);
    return $r;
  }
  $db->setAttribute(PDO::ATTR_ERRMODE, $prevMode);

  //コラボ版（include/swCollab.php がある）は更新履歴に残す。読み込んだ人が作者＝プロデューサー
  if (is_file('./include/swCollab.php')) {
    include_once('./include/swCollab.php');
    swCollab_Log($db, $newId, $userId, '作品ファイルから読み込み', $r['file']);
  }

  //作品画像（DB の外なので登録後に置く。失敗しても作品は残す）
  $b64 = swImport_Str($f, 'thumbnail');
  if ($b64 !== '') {
    if (swImport_SaveThumb($newId, $b64)) { $r['thumb'] = true; } else { $r['notes'][] = '作品画像を保存できませんでした'; }
  }
  return $r;
}

// ユーザーの SW_USER_OPTION に無い種別番号のスタイルを追加する。追加した数を返す
function swImport_AddMissingStyles($db, $userId, $styles){
  $have = array();
  $st = $db->prepare('SELECT USER_OPTION_STYLE_ID FROM SW_USER_OPTION WHERE USER_ID = :u');
  $st->bindValue(':u', (int)$userId, PDO::PARAM_INT);
  $st->execute();
  while ($row = $st->fetch(PDO::FETCH_ASSOC)) { $have[(int)$row['USER_OPTION_STYLE_ID']] = true; }
  $added = 0;
  foreach ($styles as $s) {
    if (!is_array($s)) { continue; }
    $id = swImport_Int($s, 'id', 0);
    if ($id <= 0 || isset($have[$id])) { continue; }
    $color = swImport_Str($s, 'color', '#000000');
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) { $color = '#000000'; }
    $mode = swImport_Int($s, 'mode', 0);
    $o = new clsSwUserOption();
    $o->clsSwUserOptionSetUserId($userId);
    $o->clsSwUserOptionSetUserOptionStyleId($id);
    $o->clsSwUserOptionSetUserOptionStyleOrderNo(swImport_Int($s, 'order', $id * 100));
    $o->clsSwUserOptionSetUserOptionStyleName(swImport_Str($s, 'name', '種別' . $id));
    $o->clsSwUserOptionSetUserOptionStyleFontSize(swImport_Int($s, 'size', 12));
    $o->clsSwUserOptionSetUserOptionStyleColor($color);
    $o->clsSwUserOptionSetUserOptionStyleIndent(swImport_Int($s, 'indent', 0));
    $o->clsSwUserOptionSetUserOptionStyleStr(swImport_Str($s, 'abbr'));
    $o->clsSwUserOptionSetUserOptionStyleWord(($mode >= 0 && $mode <= 3) ? $mode : 0);
    $o->clsSwUserOptionDbInsert($db);
    $have[$id] = true;
    $added++;
  }
  return $added;
}

// 作品画像（PNG の base64）を img/thumb/<ID>.<拡張子> に置く。Web 版のアップロードと同じく幅 250 に縮小
function swImport_SaveThumb($scenarioId, $b64){
  $bin = base64_decode($b64, true);
  if ($bin === false || $bin === '') { return false; }
  $info = @getimagesizefromstring($bin);
  if ($info === false || !in_array($info[2], array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF), true)) { return false; }
  $dir = './img/thumb/';
  if (!is_dir($dir) || !is_writable($dir)) { return false; }
  //同じ ID の古い画像があれば消す（シナリオ選択は ID をファイル名の先頭で引く）
  foreach (glob($dir . $scenarioId . '.*') as $old) { if (is_file($old)) { @unlink($old); } }
  $path = $dir . $scenarioId . image_type_to_extension($info[2]);
  if (function_exists('imagecreatefromstring') && $info[0] > 0) {
    $src = @imagecreatefromstring($bin);
    if ($src !== false) {
      $w = 250;
      $h = (int)ceil(250 * $info[1] / max($info[0], 1));
      $dst = imagecreatetruecolor($w, $h);
      imagealphablending($dst, false);
      imagesavealpha($dst, true);
      imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $info[0], $info[1]);
      $path = $dir . $scenarioId . '.png';
      return imagepng($dst, $path);
    }
  }
  return file_put_contents($path, $bin) !== false;
}

// ------------------------------------------------------------------------------
//   表示
// ------------------------------------------------------------------------------
function swImport_PrintForm($userName){
  print '<h4>作品ファイル（.scwd）を読み込む</h4>';
  print '<div class="alert alert-info">ScenarioWriterSolo（Mac 版・Windows 版）で保存した作品ファイル <b>.scwd</b> を選んで「読み込む」を押すと、ログイン中のユーザー <b>' . swImport_H($userName) . '</b> のシナリオとして新しく登録します。複数のファイルをまとめて選べます。同じファイルを二度読み込むと別の作品として二重に登録されます。</div>';
  print '<div class="mb-3"><input class="form-control" type="file" name="scwdFiles[]" id="scwdFiles" accept=".scwd" multiple></div>';
  print '<ul class="text-muted" style="font-size:13px;">';
  print '<li>作品情報・シノプシス・登場人物・場面・台詞・作品画像を取り込みます。</li>';
  print '<li>行のスタイル（色・字下げなど）と書式設定（文字数・「」）は、いまの設定を使います。ファイルにあってこちらに無い種別だけ追加します。</li>';
  print '<li>β5 より前の古い形式（SQLite）の .scwd は、ScenarioWriterSolo で開いて保存し直してから読み込んでください。</li>';
  print '</ul>';
  print '<button type="button" class="btn btn-success" onclick="if(!document.getElementById(\'scwdFiles\').files.length){alert(\'作品ファイルを選んでください\');return;}document.getElementById(\'SubmitMode\').value=\'IMPORT\';document.frmSwImport.submit();">読み込む</button> ';
  print '<button type="button" class="btn btn-outline-secondary" onclick="AjaxFunc_SubmitNoMsg(document.frmSwImport,\'./swScenarioSelect.php\');">シナリオ選択へ戻る</button>';
}

function swImport_PrintResult($result){
  print '<h4>読み込み結果</h4>';
  if (count($result) === 0) {
    print '<div class="alert alert-warning">ファイルが届きませんでした。選び直してください。</div>';
  } else {
    $ng = 0;
    print '<table class="table table-sm"><thead><tr><th>ファイル</th><th>タイトル</th><th>新ID</th><th class="text-end">登場人物</th><th class="text-end">場面</th><th class="text-end">台詞</th><th>画像</th><th>結果</th></tr></thead><tbody>';
    foreach ($result as $r) {
      if ($r['error'] !== '') { $ng++; }
      $notes = $r['notes'];
      if ($r['styles'] > 0) { $notes[] = 'スタイル ' . $r['styles'] . ' 種を追加'; }
      $res = ($r['error'] === '') ? '<span class="text-success">OK</span>' : '<span class="text-danger">失敗: ' . swImport_H($r['error']) . '</span>';
      if (count($notes)) { $res .= '<div style="font-size:12px;" class="text-muted">' . swImport_H(implode(' / ', $notes)) . '</div>'; }
      print '<tr><td>' . swImport_H($r['file']) . '</td><td>' . swImport_H($r['title']) . '</td><td>' . swImport_H($r['newId']) . '</td><td class="text-end">' . $r['characters'] . '</td><td class="text-end">' . $r['scenes'] . '</td><td class="text-end">' . $r['lines'] . '</td><td>' . ($r['thumb'] ? 'あり' : '') . '</td><td>' . $res . '</td></tr>';
    }
    print '</tbody></table>';
    print '<div class="alert ' . ($ng ? 'alert-danger' : 'alert-success') . '">' . ($ng ? $ng . ' 件が失敗しました（失敗したファイルは何も登録していません）。' : '読み込みが完了しました。') . ' 読み込んだシナリオはシナリオ選択に表示されます。</div>';
  }
  print '<button type="button" class="btn btn-primary" onclick="AjaxFunc_SubmitNoMsg(document.frmSwImport,\'./swScenarioSelect.php\');">シナリオ選択へ</button> ';
  print '<button type="button" class="btn btn-outline-secondary" onclick="AjaxFunc_SubmitNoMsg(document.frmSwImport,\'./swScenarioImport.php\');">続けて読み込む</button>';
}
// -----------------------------------------------------------
?>
