<?php

$root = & $this->root;
$const = & $this->cont;

$const['S_VERSION'] = $root->module['version'];
$const['S_COPYRIGHT'] = 
	'<strong>xpWiki ' . $const['S_VERSION'] . '</strong>' .
	' Copyright ' .
	$root->module['credits'] .
	' License is GPL.<br />' .
	' Based on "PukiWiki" 1.4.8_alpha';

/////////////////////////////////////////////////
// Init server variables

foreach (array('SCRIPT_NAME', 'SERVER_ADMIN', 'SERVER_NAME',
	'SERVER_PORT', 'SERVER_SOFTWARE') as $key) {
	if (!defined($key)) {
		define($key, isset($_SERVER[$key]) ? $_SERVER[$key] : '');
	}
	//unset(${$key}, $_SERVER[$key], $HTTP_SERVER_VARS[$key]);
}

/////////////////////////////////////////////////
// Init grobal variables

$root->foot_explain = array();	// Footnotes
$root->related      = array();	// Related pages
$root->head_tags    = array();	// XHTML tags in <head></head>
$root->head_precsses= array();	// XHTML CSS tags in <head></head> before skin's CSS.

// UI_LANG - Content encoding for buttons, menus,  etc
//$const['UI_LANG'] = $const['LANG']; // 'en' for Internationalized wikisite
$const['UI_LANG'] = $this->get_accept_language();

/////////////////////////////////////////////////
// INI_FILE: LANG に基づくエンコーディング設定

switch ($const['LANG']){
case 'en':
	// Internal content encoding = Output content charset (for skin)
	$const['CONTENT_CHARSET'] = 'iso-8859-1'; // 'UTF-8', 'iso-8859-1', 'EUC-JP' or ...
	// mb_language (for mbstring extension)
	$const['MB_LANGUAGE'] = 'English';	// 'uni'(means UTF-8), 'English', or 'Japanese'
	// Internal content encoding (for mbstring extension)
	$const['SOURCE_ENCODING'] = 'ASCII';	// 'UTF-8', 'ASCII', or 'EUC-JP'
	break;
	
case 'ja': // EUC-JP
	$const['CONTENT_CHARSET'] = 'EUC-JP';
	$const['MB_LANGUAGE'] = 'Japanese';
	$const['SOURCE_ENCODING'] = 'EUC-JP';
	break;

default:
	$this->die_message('No such language "' . LANG . '"'.memory_get_usage());
}

mb_language($const['MB_LANGUAGE']);
mb_internal_encoding($const['SOURCE_ENCODING']);
ini_set('mbstring.http_input', 'pass');
mb_http_output('pass');
mb_detect_order('auto');

/////////////////////////////////////////////////
// INI_FILE: Require LANG_FILE

$const['LANG_FILE_HINT'] = $const['DATA_HOME'] . 'private/lang/' . $const['LANG'] . '.lng.php';	// For encoding hint
$const['LANG_FILE'] = $const['DATA_HOME'] . 'private/lang/' . $const['UI_LANG'] . '.lng.php';	// For UI resource
$die = '';

$langfiles = array($const['LANG_FILE_HINT'], $const['LANG_FILE']);
array_unique($langfiles);
foreach ($langfiles as $langfile) {
	if (! file_exists($langfile) || ! is_readable($langfile)) {
		$die .= 'File is not found or not readable. (' . $langfile . ')' . "\n";
	} else {
		require($langfile);
	}
}
if ($die) $this->die_message(nl2br("\n\n" . $die));

/////////////////////////////////////////////////
// LANG_FILE: Init encoding hint

$const['PKWK_ENCODING_HINT'] = isset($_LANG['encode_hint'][$const['LANG']]) ? $_LANG['encode_hint'][$const['LANG']] : '';
//unset($_LANG['encode_hint']);

/////////////////////////////////////////////////
// LANG_FILE: Init severn days of the week

$root->weeklabels = $root->_msg_week;

/////////////////////////////////////////////////
// INI_FILE: Init $script

$root->script = str_replace(XOOPS_ROOT_PATH, XOOPS_URL, $root->mydirpath)."/";

if (isset($root->script)) {
	$this->get_script_uri($root->script); // Init manually
} else {
	$root->script = $this->get_script_uri(); // Init automatically
}

/////////////////////////////////////////////////
// INI_FILE: $agents:  UserAgentの識別

$root->ua = 'HTTP_USER_AGENT';
$root->user_agent = $matches = array();

$root->user_agent['agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
//unset(${$ua}, $_SERVER[$ua], $HTTP_SERVER_VARS[$ua], $ua);	// safety

foreach ($root->agents as $agent) {
	if (preg_match($agent['pattern'], $root->user_agent['agent'], $matches)) {
		$root->user_agent['profile'] = isset($agent['profile']) ? $agent['profile'] : '';
		$root->user_agent['name']    = isset($matches[1]) ? $matches[1] : '';	// device or browser name
		$root->user_agent['vers']    = isset($matches[2]) ? $matches[2] : ''; // 's version
		break;
	}
}
unset($root->agents);

// Profile-related init and setting
$const['UA_PROFILE'] = isset($root->user_agent['profile']) ? $root->user_agent['profile'] : '';

$const['UA_INI_FILE'] = $const['DATA_HOME'] .'private/ini/'. $const['UA_PROFILE'] . '.ini.php';
if (! file_exists($const['UA_INI_FILE']) || ! is_readable($const['UA_INI_FILE'])) {
	$this->die_message('UA_INI_FILE for "' . $const['UA_PROFILE'] . '" not found.');
} else {
	require($const['UA_INI_FILE']); // Also manually
}

$const['UA_NAME'] = isset($user_agent['name']) ? $user_agent['name'] : '';
$const['UA_VERS'] = isset($user_agent['vers']) ? $user_agent['vers'] : '';
unset($user_agent);	// Unset after reading UA_INI_FILE

/////////////////////////////////////////////////
// ディレクトリのチェック

$die = '';
foreach(array($const['DATA_DIR'], $const['DIFF_DIR'], $const['BACKUP_DIR'], $const['CACHE_DIR']) as $dir){
	if (! is_writable($dir))
		$die .= 'Directory is not found or not writable (' . $dir . ')' . "\n";
}

// 設定ファイルの変数チェック
$temp = '';
foreach(array('rss_max', 'page_title', 'note_hr', 'related_link', 'show_passage',
	'rule_related_str', 'load_template_func') as $var){
	if (! isset($root->{$var})) $temp .= '$' . $var . "\n";
}
if ($temp) {
	if ($die) $die .= "\n";	// A breath
	$die .= 'Variable(s) not found: (Maybe the old *.ini.php?)' . "\n" . $temp;
}

$temp = '';
foreach(array($const['LANG'], $const['PLUGIN_DIR']) as $def){
	if (! isset($def)) $temp .= $def . "\n";
}
if ($temp) {
	if ($die) $die .= "\n";	// A breath
	$die .= 'Define(s) not found: (Maybe the old *.ini.php?)' . "\n" . $temp;
}

if($die) $this->die_message(nl2br("\n\n" . $die));
unset($die, $temp);

// 指定ページ表示モード
if (!empty($const['page_show'])) {
	
	$get['cmd']  = $post['cmd']  = $vars['cmd']  = 'read';
	$get['page'] = $post['page'] = $vars['page'] = $const['page_show'];

} else {

	/////////////////////////////////////////////////
	// 必須のページが存在しなければ、空のファイルを作成する
	
	foreach(array($root->defaultpage, $root->whatsnew, $root->interwiki) as $page){
		if (! $this->is_page($page)) touch($this->get_filename($page));
	}
	
	/////////////////////////////////////////////////
	// 外部からくる変数のチェック
	
	// Prohibit $_GET attack
	foreach (array('msg', 'pass') as $key) {
		if (isset($_GET[$key])) die_message('Sorry, already reserved: ' . $key . '=');
	}
	
	// Expire risk
	unset($HTTP_GET_VARS, $HTTP_POST_VARS);	//, 'SERVER', 'ENV', 'SESSION', ...
	unset($_REQUEST);	// Considered harmful
	
	// Remove null character etc.
	$_GET    = $this->input_filter($_GET);
	$_POST   = $this->input_filter($_POST);
	$_COOKIE = $this->input_filter($_COOKIE);
	
	// 文字コード変換 ($_POST)
	// <form> で送信された文字 (ブラウザがエンコードしたデータ) のコードを変換
	// POST method は常に form 経由なので、必ず変換する
	//
	if (isset($_POST['encode_hint']) && $_POST['encode_hint'] != '') {
		// do_plugin_xxx() の中で、<form> に encode_hint を仕込んでいるので、
		// encode_hint を用いてコード検出する。
		// 全体を見てコード検出すると、機種依存文字や、妙なバイナリ
		// コードが混入した場合に、コード検出に失敗する恐れがある。
		$encode = mb_detect_encoding($_POST['encode_hint']);
		mb_convert_variables($const['SOURCE_ENCODING'], $encode, $_POST);
	
	} else if (isset($_POST['charset']) && $_POST['charset'] != '') {
		// TrackBack Ping で指定されていることがある
		// うまくいかない場合は自動検出に切り替え
		if (mb_convert_variables($const['SOURCE_ENCODING'],
		    $_POST['charset'], $_POST) !== $_POST['charset']) {
			mb_convert_variables($const['SOURCE_ENCODING'], 'auto', $_POST);
		}
	
	} else if (! empty($_POST)) {
		// 全部まとめて、自動検出／変換
		mb_convert_variables($const['SOURCE_ENCODING'], 'auto', $_POST);
	}
	
	// 文字コード変換 ($_GET)
	// GET method は form からの場合と、<a href="http://script/?key=value> の場合がある
	// <a href...> の場合は、サーバーが rawurlencode しているので、コード変換は不要
	if (isset($_GET['encode_hint']) && $_GET['encode_hint'] != '')
	{
		// form 経由の場合は、ブラウザがエンコードしているので、コード検出・変換が必要。
		// encode_hint が含まれているはずなので、それを見て、コード検出した後、変換する。
		// 理由は、post と同様
		$encode = mb_detect_encoding($_GET['encode_hint']);
		mb_convert_variables($const['SOURCE_ENCODING'], $encode, $_GET);
	}
	
	
	/////////////////////////////////////////////////
	// QUERY_STRINGを取得
	
	// cmdもpluginも指定されていない場合は、QUERY_STRINGを
	// ページ名かInterWikiNameであるとみなす
	$arg = '';
	if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) {
		$arg = $_SERVER['QUERY_STRING'];
	} else if (isset($_SERVER['argv']) && ! empty($_SERVER['argv'])) {
		$arg = $_SERVER['argv'][0];
	}
	if ($const['PKWK_QUERY_STRING_MAX'] && strlen($arg) > $const['PKWK_QUERY_STRING_MAX']) {
		// Something nasty attack?
		$this->pkwk_common_headers();
		sleep(1);	// Fake processing, and/or process other threads
		echo('Query string too long');
		exit;
	}
	$arg = $this->input_filter($arg); // \0 除去
	
	// unset QUERY_STRINGs
	// Now use plugin or xoops. 
	//foreach (array('QUERY_STRING', 'argv', 'argc') as $key) {
	////	unset(${$key}, $_SERVER[$key], $HTTP_SERVER_VARS[$key]);
	//	unset(${$key}, $_SERVER[$key]);
	//}
	// $_SERVER['REQUEST_URI'] is used at func.php NOW
	//unset($REQUEST_URI, $HTTP_SERVER_VARS['REQUEST_URI']);
	
	// mb_convert_variablesのバグ(?)対策: 配列で渡さないと落ちる
	$arg = array($arg);
	mb_convert_variables($const['SOURCE_ENCODING'], 'auto', $arg);
	$arg = $arg[0];
	
	/////////////////////////////////////////////////
	// QUERY_STRINGを分解してコード変換し、$_GET に上書き
	
	// URI を urlencode せずに入力した場合に対処する
	$matches = array();
	foreach (explode('&', $arg) as $key_and_value) {
		if (preg_match('/^([^=]+)=(.+)/', $key_and_value, $matches) &&
		    mb_detect_encoding($matches[2]) != 'ASCII') {
			$_GET[$matches[1]] = $matches[2];
		}
	}
	unset($matches);
	
	/////////////////////////////////////////////////
	// GET, POST, COOKIE
	
	$get    = & $_GET;
	$post   = & $_POST;
	$cookie = & $_COOKIE;
	
	// GET + POST = $vars
	if (empty($_POST)) {
		$vars = & $_GET;  // Major pattern: Read-only access via GET
	} else if (empty($_GET)) {
		$vars = & $_POST; // Minor pattern: Write access via POST etc.
	} else {
		$vars = array_merge($_GET, $_POST); // Considered reliable than $_REQUEST
	}
	
	// 入力チェック: cmd, plugin の文字列は英数字以外ありえない
	foreach(array('cmd', 'plugin') as $var) {
		if (isset($vars[$var]) && ! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $vars[$var]))
			unset($get[$var], $post[$var], $vars[$var]);
	}
	
	// 整形: page, strip_bracket()
	if (isset($vars['page'])) {
		$get['page'] = $post['page'] = $vars['page']  = $this->strip_bracket($vars['page']);
	} else {
		$get['page'] = $post['page'] = $vars['page'] = '';
	}
	
	// 整形: msg, 改行を取り除く
	if (isset($vars['msg'])) {
		$get['msg'] = $post['msg'] = $vars['msg'] = str_replace("\r", '', $vars['msg']);
	}
	
	// 後方互換性 (?md5=...)
	if (isset($vars['md5']) && $vars['md5'] != '') {
		$get['cmd'] = $post['cmd'] = $vars['cmd'] = 'md5';
	}
	
	// TrackBack Ping
	if (isset($vars['tb_id']) && $vars['tb_id'] != '') {
		$get['cmd'] = $post['cmd'] = $vars['cmd'] = 'tb';
	}
	
	// cmdもpluginも指定されていない場合は、QUERY_STRINGをページ名かInterWikiNameであるとみなす
	if (! isset($vars['cmd']) && ! isset($vars['plugin'])) {
	
		$get['cmd']  = $post['cmd']  = $vars['cmd']  = 'read';
	
		if ($arg == '') $arg = $root->defaultpage;
		$arg = rawurldecode($arg);
		// XOOPS の redirect_header で付加されることがある &以降を削除
		$arg = preg_replace("/&.*$/", "", $arg);
		$arg = $this->strip_bracket($arg);
		$arg = $this->input_filter($arg);
		$get['page'] = $post['page'] = $vars['page'] = $arg;
	}
	
	// 入力チェック: 'cmd=' prohibits nasty 'plugin='
	if (isset($vars['cmd']) && isset($vars['plugin']))
		unset($get['plugin'], $post['plugin'], $vars['plugin']);
		
	//exit ($vars['cmd']);
}
$root->get =& $get;
$root->post =& $post;
$root->vars =& $vars;

/////////////////////////////////////////////////
// 初期設定($WikiName,$BracketNameなど)
// $WikiName = '[A-Z][a-z]+(?:[A-Z][a-z]+)+';
// $WikiName = '\b[A-Z][a-z]+(?:[A-Z][a-z]+)+\b';
// $WikiName = '(?<![[:alnum:]])(?:[[:upper:]][[:lower:]]+){2,}(?![[:alnum:]])';
// $WikiName = '(?<!\w)(?:[A-Z][a-z]+){2,}(?!\w)';

// BugTrack/304暫定対処
$root->WikiName = '(?:[A-Z][a-z]+){2,}(?!\w)';

// $BracketName = ':?[^\s\]#&<>":]+:?';
$root->BracketName = '(?!\s):?[^\r\n\t\f\[\]<>#&":]+:?(?<!\s)';

// InterWiki
$root->InterWikiName = '(\[\[)?((?:(?!\s|:|\]\]).)+):(.+)(?(1)\]\])';

// 注釈
$root->NotePattern = '/\(\(((?:(?>(?:(?!\(\()(?!\)\)(?:[^\)]|$)).)+)|(?R))*)\)\)/ex';

/////////////////////////////////////////////////
// 初期設定(ユーザ定義ルール読み込み)
require($const['DATA_HOME'] . 'private/ini/rules.ini.php');

/////////////////////////////////////////////////
// 初期設定(その他のグローバル変数)

// 現在時刻
$root->now = $this->format_date($const['UTIME']);

// フェイスマークを$line_rulesに加える
if ($root->usefacemark) $root->line_rules += $root->facemark_rules;
//unset($facemark_rules);

// 実体参照パターンおよびシステムで使用するパターンを$line_rulesに加える
//$entity_pattern = '[a-zA-Z0-9]{2,8}';
$root->entity_pattern = trim(join('', file($const['CACHE_DIR'] . 'entities.dat')));

$root->line_rules = array_merge(array(
	'&amp;(#[0-9]+|#x[0-9a-f]+|' . $root->entity_pattern . ');' => '&$1;',
	"\r"          => '<br />' . "\n",	/* 行末にチルダは改行 */
	'#related$'   => '<del>#related</del>',
	'^#contents$' => '<del>#contents</del>'
), $root->line_rules);

$root->digest = "";
?>