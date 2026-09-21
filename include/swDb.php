<?php
// ------------------------------------------------------------------------------
//
//    DB 接続の共通層（マルチDB対応）
//        swDb.php
//
//    Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//    2026-09 追加。PDO を使い、$dbType で接続先を切り替える。
//      sqlite : SQLite 3（標準。ファイル1個。DBサーバ不要）
//      mysql  : MySQL / MariaDB（XSERVER 本番・MAMP）
//      pgsql  : PostgreSQL（将来用。DSN は作れるが scwSchema.pgsql.sql は未作成）
//
//    SQLite は MySQL と挙動が違う所を、この層で吸収する（アプリ側の SQL は変えない）。
//      ・SELECT 後の rowCount() が 0 を返す → 結果を先読みして件数を返す
//      ・CONCAT() が無い → PHP でユーザー関数として登録
//      ・PARAM_INT で bind した非整数文字列（uniqid 等）を整数に丸めてしまう → 型を補正
//
// ------------------------------------------------------------------------------

// DB 種別の一覧（設定画面の選択肢）
function swDb_Types(){
	return array(
		'sqlite' => 'SQLite（ファイル・標準）',
		'mysql'  => 'MySQL / MariaDB',
	);
}

// 設定配列の既定値を埋める
function swDb_NormalizeConfig($cfg){
	$def = array(
		'dbType' => '', 'dbHost' => 'localhost', 'dbPort' => '', 'dbSocket' => '',
		'dbName' => '', 'dbUser' => '', 'dbPass' => '', 'dbFile' => '',
		'dbCharset' => defined('_DB_CHARACTER_SET_') ? _DB_CHARACTER_SET_ : 'utf8mb4',
	);
	foreach ($def as $k => $v) {
		if (!isset($cfg[$k]) || $cfg[$k] === null) { $cfg[$k] = $v; }
		$cfg[$k] = is_string($cfg[$k]) ? trim($cfg[$k]) : $cfg[$k];
	}
	$cfg['dbType'] = strtolower($cfg['dbType']);
	//種別未指定: 旧設定（DB名あり）なら mysql、それ以外は標準の sqlite
	if ($cfg['dbType'] === '') { $cfg['dbType'] = ($cfg['dbName'] !== '') ? 'mysql' : 'sqlite'; }
	return $cfg;
}

// グローバル変数（swConstant.php が定義）から設定配列を作る
function swDb_ConfigFromGlobals(){
	$cfg = array();
	foreach (array('dbType','dbHost','dbPort','dbSocket','dbName','dbUser','dbPass','dbFile') as $k) {
		$cfg[$k] = isset($GLOBALS[$k]) ? $GLOBALS[$k] : null;
	}
	return swDb_NormalizeConfig($cfg);
}

// SQLite のファイルパス。空なら sw_config/swdata.sqlite、相対なら sw_config/ からの相対。
function swDb_SqlitePath($file){
	$file = trim((string)$file);
	$base = dirname(__DIR__) . '/sw_config';
	if ($file === '') { return $base . '/swdata.sqlite'; }
	if ($file[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\/]/', $file)) { return $file; }
	return $base . '/' . $file;
}

// DSN を組み立てる。array($dsn, $user, $pass) を返す。
function swDb_Dsn($cfg){
	$cfg = swDb_NormalizeConfig($cfg);
	switch ($cfg['dbType']) {
		case 'sqlite':
			return array('sqlite:' . swDb_SqlitePath($cfg['dbFile']), null, null);
		case 'pgsql':
			$dsn = "pgsql:dbname=" . $cfg['dbName'];
			if ($cfg['dbSocket'] !== '') { $dsn .= ";host=" . $cfg['dbSocket']; }   // pgsql は socket もディレクトリを host に書く
			else { $dsn .= ";host=" . $cfg['dbHost']; if ($cfg['dbPort'] !== '') { $dsn .= ";port=" . $cfg['dbPort']; } }
			return array($dsn, $cfg['dbUser'], $cfg['dbPass']);
		case 'mysql':
		default:
			$dsn = "mysql:dbname=" . $cfg['dbName'] . ";charset=" . $cfg['dbCharset'];
			if ($cfg['dbSocket'] !== '') { $dsn .= ";unix_socket=" . $cfg['dbSocket']; }
			else { $dsn .= ";host=" . $cfg['dbHost']; if ($cfg['dbPort'] !== '') { $dsn .= ";port=" . $cfg['dbPort']; } }
			return array($dsn, $cfg['dbUser'], $cfg['dbPass']);
	}
}

// 接続して PDO を返す（失敗は PDOException）。$options は PDO の属性（呼び出し側で上書き可）。
function swDb_Connect($cfg, $options = array()){
	$cfg = swDb_NormalizeConfig($cfg);
	list($dsn, $user, $pass) = swDb_Dsn($cfg);
	$opt = array(
		PDO::ATTR_EMULATE_PREPARES => false,
		PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
	);
	if ($cfg['dbType'] === 'sqlite') {
		//ロック待ち（秒）。同時書込みで "database is locked" になるのを防ぐ
		$opt[PDO::ATTR_TIMEOUT] = 5;
		//SELECT の rowCount() 等を MySQL と同じ挙動にする文クラス
		$opt[PDO::ATTR_STATEMENT_CLASS] = array('swPdoStatementSqlite', array());
	}
	foreach ($options as $k => $v) { $opt[$k] = $v; }

	//PHP 8.4+ は PDO::connect() でドライバ別サブクラス（Pdo\Sqlite 等）が返る。
	//  SQLite のユーザー関数登録が PHP 8.5 で sqliteCreateFunction → createFunction に変わったため。
	if (method_exists('PDO', 'connect')) {
		$pdo = PDO::connect($dsn, $user, $pass, $opt);
	} else {
		$pdo = new PDO($dsn, $user, $pass, $opt);
	}

	if ($cfg['dbType'] === 'sqlite') {
		swDb_SqliteInit($pdo);
	}
	return $pdo;
}

// SQLite 接続直後の初期化（ユーザー関数の登録・PRAGMA）
function swDb_SqliteInit($pdo){
	$reg = method_exists($pdo, 'createFunction') ? 'createFunction' : 'sqliteCreateFunction';
	//MySQL の CONCAT()。引数に NULL があれば NULL（MySQL と同じ）。
	//  float は PHP の文字列化（100.0→"100"）で MySQL の double→文字列と同じ見え方になる。
	$pdo->$reg('CONCAT', function(){
		$s = '';
		foreach (func_get_args() as $a) {
			if ($a === null) { return null; }
			$s .= (string)$a;
		}
		return $s;
	});
	//MySQL の NOW()（ローカル時刻）。今の SQL には無いが、追加時に使えるように。
	$pdo->$reg('NOW', function(){ return date('Y-m-d H:i:s'); }, 0);
	//WAL で読み書きの同時実行に強くする（失敗しても続行）
	try { $pdo->exec("PRAGMA journal_mode=WAL"); } catch (Exception $e) {}
}

// この DB 種別で使うスキーマファイル
function swDb_SchemaFile($type){
	$dir = dirname(__DIR__) . '/sw_config';
	switch (strtolower((string)$type)) {
		case 'sqlite': return $dir . '/scwSchema.sqlite.sql';
		case 'pgsql':  return $dir . '/scwSchema.pgsql.sql';
		default:       return $dir . '/scwSchema.sql';
	}
}

// 現在日時を DB に入れる時の文字列（MySQL の current_timestamp と同じ書式）
function swDb_Now(){
	return date('Y-m-d H:i:s');
}

// ------------------------------------------------------------------------------
//    SQLite 用 PDOStatement
//      ・SELECT 後の rowCount(): SQLite は常に 0 を返すので、結果を先読みして件数を返し、
//        その後の fetch()/fetchAll()/fetchColumn() は先読みした配列から返す。
//      ・PARAM_INT で非整数値（uniqid の "5f...\.123" や "150.5"、''）を bind した時は
//        PARAM_STR に切り替える（SQLite は PARAM_INT だと整数に丸めてしまうため）。
//        bindParam は参照なので、値の判定は execute() 時に行う。
//      ※ PHP 7.4〜8.x 両対応（#[...] は 7.4 ではコメント扱い）
// ------------------------------------------------------------------------------
class swPdoStatementSqlite extends PDOStatement {
	private $swBuf = null;
	private $swPos = 0;
	private $swMode = PDO::FETCH_BOTH;
	private $swParams = array();

	protected function __construct(){}

	private static function swFixType($value, $type){
		if ($type === PDO::PARAM_INT && !is_int($value) && !is_null($value) && !is_bool($value)) {
			if (!is_string($value) || !preg_match('/^\s*-?\d+\s*$/', $value)) { return PDO::PARAM_STR; }
		}
		return $type;
	}

	public function bindParam($param, &$var, $type = PDO::PARAM_STR, $maxLength = 0, $driverOptions = null): bool {
		$this->swParams[$param] = array('ref' => &$var, 'type' => $type);
		return true;
	}

	public function bindValue($param, $value, $type = PDO::PARAM_STR): bool {
		unset($this->swParams[$param]);
		return parent::bindValue($param, $value, self::swFixType($value, $type));
	}

	public function execute($params = null): bool {
		$this->swBuf = null;
		$this->swPos = 0;
		foreach ($this->swParams as $p => $info) {
			$v = $info['ref'];
			parent::bindValue($p, $v, self::swFixType($v, $info['type']));
		}
		//複数人が同時に書き込むと busy_timeout 内でも "database is locked"(SQLITE_BUSY=5) で返ることがある
		//（コラボレーション版: 在席更新と編集が重なる）。少し待って最大 5 回やり直す。
		for ($try = 0; $try < 5; $try++) {
			$ok = @parent::execute($params);
			if ($ok) { return true; }
			$err = $this->errorInfo();
			$busy = (isset($err[1]) && ((int)$err[1] === 5 || (int)$err[1] === 6)) || (isset($err[2]) && stripos((string)$err[2], 'locked') !== false);
			if (!$busy) { break; }
			//失敗した文は sqlite3_reset しないと、次の execute で PDO がパラメータを bind し直す時点で
			// SQLITE_MISUSE(21) になる。closeCursor が reset に当たる。
			parent::closeCursor();
			usleep(mt_rand(20000, 80000) * ($try + 1));
		}
		//やり直しても駄目なら従来どおり（ERRMODE に従って警告/例外）
		parent::closeCursor();
		return parent::execute($params);
	}

	#[\ReturnTypeWillChange]
	public function setFetchMode($mode, $params = null, ...$args){
		//PHP7.4 は (mode, params) 固定、PHP8 は (mode, ...args) 可変。渡された引数だけを親に渡す
		//（FETCH_ASSOC 等で余分な null を渡すとエラーになるため）
		$this->swMode = $mode;
		return parent::setFetchMode(...func_get_args());
	}

	public function rowCount(): int {
		if ($this->columnCount() > 0) {
			$this->swFill();
			return count($this->swBuf);
		}
		return parent::rowCount();
	}

	private function swFill(){
		if ($this->swBuf === null) {
			$this->swBuf = parent::fetchAll(PDO::FETCH_BOTH);
			$this->swPos = 0;
		}
	}

	private function swConv($row, $mode){
		if ($mode === 0) { $mode = $this->swMode; }   // 0 = PDO::FETCH_DEFAULT（文の既定）
		switch ($mode) {
			case PDO::FETCH_ASSOC:
				$r = array(); foreach ($row as $k => $v) { if (is_string($k)) { $r[$k] = $v; } } return $r;
			case PDO::FETCH_NUM:
				$r = array(); foreach ($row as $k => $v) { if (is_int($k)) { $r[$k] = $v; } } return $r;
			case PDO::FETCH_OBJ:
				$r = array(); foreach ($row as $k => $v) { if (is_string($k)) { $r[$k] = $v; } } return (object)$r;
			default:
				return $row;
		}
	}

	#[\ReturnTypeWillChange]
	public function fetch($mode = 0, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0){
		//SELECT は最初の fetch で結果を全部読み切る（コラボレーション版で変更）。
		//  1 行だけ fetch して途中で止めると読み取りトランザクションが開いたままになり、WAL では同じ接続の
		//  次の UPDATE/INSERT が「database is locked」で即失敗する（busy_timeout も効かない）。
		if ($this->swBuf === null && $this->columnCount() > 0) { $this->swFill(); }
		if ($this->swBuf === null) { return parent::fetch($mode, $cursorOrientation, $cursorOffset); }
		if ($this->swPos >= count($this->swBuf)) { return false; }
		return $this->swConv($this->swBuf[$this->swPos++], $mode);
	}

	public function fetchAll($mode = 0, ...$args): array {
		if ($this->swBuf === null && $this->columnCount() > 0 && count($args) === 0 && ($mode === 0 || $mode === PDO::FETCH_ASSOC || $mode === PDO::FETCH_NUM || $mode === PDO::FETCH_BOTH || $mode === PDO::FETCH_OBJ)) { $this->swFill(); }
		if ($this->swBuf === null) { return parent::fetchAll($mode, ...$args); }
		$rest = array_slice($this->swBuf, $this->swPos);
		$this->swPos = count($this->swBuf);
		$out = array();
		foreach ($rest as $row) { $out[] = $this->swConv($row, $mode); }
		return $out;
	}

	#[\ReturnTypeWillChange]
	public function fetchColumn($column = 0){
		if ($this->swBuf === null && $this->columnCount() > 0) { $this->swFill(); }
		if ($this->swBuf === null) { return parent::fetchColumn($column); }
		$row = $this->fetch(PDO::FETCH_NUM);
		if ($row === false) { return false; }
		return isset($row[$column]) ? $row[$column] : null;
	}

	public function closeCursor(): bool {
		$this->swBuf = null;
		$this->swPos = 0;
		return parent::closeCursor();
	}
}
?>
