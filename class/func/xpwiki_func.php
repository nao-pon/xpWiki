<?php
//
// Created on 2006/10/02 by nao-pon http://hypweb.net/
// $Id: xpwiki_func.php,v 1.22 2006/11/15 01:13:46 nao-pon Exp $
//
class XpWikiFunc extends XpWikiXoopsWrapper {

	// xpWiki functions.
	// These are functions that need not be overwrited. 
	
	var $xpwiki;
	var $root;
	var $cont;
	var $pid;
	
	function XpWikiFunc (& $xpwiki) {
		$this->xpwiki = & $xpwiki;
		$this->root  = & $xpwiki->root;
		$this->cont = & $xpwiki->cont;
		$this->pid = $xpwiki->pid;
	}

	function load_ini() {
		$root = & $this->root;
		$const = & $this->cont;
		
		/////////////////////////////////////////////////
		// Time settings
		
		$const['LOCALZONE'] = date('Z');
		$const['UTIME'] = time() - $const['LOCALZONE'];
		$const['MUTIME'] = $this->getmicrotime();
		
		/////////////////////////////////////////////////
		// Require INI_FILE
		
		$const['INI_FILE'] = $const['DATA_HOME'] . 'private/ini/pukiwiki.ini.php';
		$die = '';
		if (! file_exists($const['INI_FILE']) || ! is_readable($const['INI_FILE'])) {
			$die .= 'File is not found. (INI_FILE)' . "\n";
		} else {
			require($const['INI_FILE']);
		}
		if ($die) $this->die_message(nl2br("\n\n" . $die));
	}

	function init() {

		include(dirname(dirname(__FILE__))."/include/init.php");
	}
	
	function & get_plugin_instance ($name) {
		static $instance = array();
		
		if (!isset($instance[$this->xpwiki->pid][$name])) {
			if ($class = $this->exist_plugin($name)) {
				$instance[$this->xpwiki->pid][$name] = new $class($this);
				$instance[$this->xpwiki->pid][$name]->name = $name;
				if ($this->plugin_init($name, $instance[$this->xpwiki->pid][$name]) === FALSE) {
					$this->die_message('Plugin init failed: ' . $name);
				}
			} else {
				$instance[$this->xpwiki->pid][$name] = false;
			}
		}
		
		return $instance[$this->xpwiki->pid][$name];
	}
	
	function get_plugin_filename ($name) {
		
		$files = array();
		if (file_exists($this->root->mydirpath . '/private/plugin/' . $name . '.inc.php')) {
			$files['user'] = $this->root->mydirpath . '/private/plugin/' . $name . '.inc.php';
		}
		if (file_exists($this->root->mytrustdirpath . '/plugin/' . $name . '.inc.php')) {
			$files['system'] = $this->root->mytrustdirpath . '/plugin/' . $name . '.inc.php';
		}
		return $files;
	}
	
	// Set global variables for plugins
	function set_plugin_messages($messages) {
		foreach ($messages as $name=>$val)
			if (! isset($this->root->$name))
				$this->root->$name = $val;
	}
	
	// Check plugin '$name' is here
	function exist_plugin($name) {
		//	global $vars;
		static $exist = array(), $count = array();
	
		$name = strtolower($name);
		if(isset($exist[$this->xpwiki->pid][$name])) {
			if (++$count[$this->xpwiki->pid][$name] > $this->cont['PKWK_PLUGIN_CALL_TIME_LIMIT'])
				die('Alert: plugin "' . htmlspecialchars($name) .
				'" was called over ' . $this->cont['PKWK_PLUGIN_CALL_TIME_LIMIT'] .
				' times. SPAM or someting?<br />' . "\n" .
				'<a href="' . $this->get_script_uri() . '?cmd=edit&amp;page='.
				rawurlencode($this->root->vars['page']) . '">Try to edit this page</a><br />' . "\n" .
				'<a href="' . $this->get_script_uri() . '">Return to frontpage</a>');
			return $exist[$this->xpwiki->pid][$name];
		}
	
		$plugin_files = $this->get_plugin_filename ($name);
		
		$exist[$this->xpwiki->pid][$name] = FALSE;
		$count[$this->xpwiki->pid][$name] = 1;
		$ret = FALSE;
		
		if (preg_match('/^\w{1,64}$/', $name) && $plugin_files ) {
			$ret =  FALSE;
			if (isset($plugin_files['system'])) {
				require_once($plugin_files['system']);
				$class_name = "xpwiki_plugin_{$name}";
				if (class_exists($class_name)) {
					$exist[$this->xpwiki->pid][$name] = TRUE;
					$count[$this->xpwiki->pid][$name] = 1;
					$ret = $class_name;
					if (isset($plugin_files['user'])) {
						require_once($plugin_files['user']);
						$class_name = "xpwiki_user_plugin_{$name}";
						if (class_exists($class_name)) {
							$ret = $class_name;
						}
					}
				}
			}
		}
		return $ret;
	}
	
	// Check if plugin API 'action' exists
	function exist_plugin_action($name) {
		$plugin = & $this->get_plugin_instance($name);
		return	is_object($plugin) ? method_exists($plugin, 'plugin_' . $name . '_action') : FALSE;
	}
	
	// Check if plugin API 'convert' exists
	function exist_plugin_convert($name) {
		$plugin = & $this->get_plugin_instance($name);
		return	is_object($plugin) ? method_exists($plugin, 'plugin_' . $name . '_convert') : FALSE;
	}
	
	// Check if plugin API 'inline' exists
	function exist_plugin_inline($name) {
		$plugin = & $this->get_plugin_instance($name);
		return	is_object($plugin) ? method_exists($plugin, 'plugin_' . $name . '_inline') : FALSE;
	}
	
	// Do init the plugin
	function plugin_init($name, & $plugin) {
		static $checked = array();
	
		if (isset($checked[$this->xpwiki->pid][$name])) return $checked[$this->xpwiki->pid][$name];
		
		$func = 'plugin_' . $name . '_init';
		if (method_exists($plugin, $func)) {
			// TRUE or FALSE or NULL (return nothing)
			$checked[$this->xpwiki->pid][$name] = call_user_func(array(& $plugin, $func));
		} else {
			$checked[$this->xpwiki->pid][$name] = NULL; // Not exist
		}
	
		return $checked[$this->xpwiki->pid][$name];
	}
	
	// Compatibility
	function do_plugin_init($name) {
		
		$plugin = & $this->get_plugin_instance($name);
	
		return plugin_init($name, $plugin);
	}
	
	// Call API 'action' of the plugin
	function do_plugin_action($name) {
		if (! $this->exist_plugin_action($name)) return array();

		//if($this->do_plugin_init($name) === FALSE)
		//		$this->die_message('Plugin init failed: ' . $name);
	
		$plugin = & $this->get_plugin_instance($name);
		$retvar = call_user_func(array(& $plugin, 'plugin_' . $name . '_action'));
		
		// Insert a hidden field, supports idenrtifying text enconding
		if ($this->cont['PKWK_ENCODING_HINT'] != '')
				$retvar =  preg_replace('/(<form[^>]*>)/', '$1' . "\n" .
					'<div><input type="hidden" name="encode_hint" value="' .
					$this->cont['PKWK_ENCODING_HINT'] . '" /></div>', $retvar);
	
		return $retvar;
	}
	
	// Call API 'convert' of the plugin
	function do_plugin_convert($name, $args = '') {
		//	global $digest;
	
		//if($this->do_plugin_init($name) === FALSE)
		//		return '[Plugin init failed: ' . $name . ']';
	
		if (! $this->cont['PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK']) {
			// Multiline plugin?
			$pos  = strpos($args, "\r"); // "\r" is just a delimiter
				if ($pos !== FALSE) {
					$body = substr($args, $pos + 1);
					$args = substr($args, 0, $pos);
				}
			}
	
		if ($args === '') {
			$aryargs = array();                 // #plugin()
		} else {
			$aryargs = $this->csv_explode(',', $args); // #plugin(A,B,C,D)
		}
		if (! $this->cont['PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK']) {
			if (isset($body)) $aryargs[] = & $body;     // #plugin(){{body}}
		}

		$_digest = $this->root->digest;
		$plugin = & $this->get_plugin_instance($name);
		$retvar  = call_user_func_array(array(& $plugin, 'plugin_' . $name . '_convert'), $aryargs);
		$this->root->digest  = $_digest; // Revert
	
		if ($retvar === FALSE) {
			return htmlspecialchars('#' . $name .
				($args != '' ? '(' . $args . ')' : ''));
		} else if ($this->cont['PKWK_ENCODING_HINT'] != '') {
		// Insert a hidden field, supports idenrtifying text enconding
		return preg_replace('/(<form[^>]*>)/', '$1 ' . "\n" .
				'<div><input type="hidden" name="encode_hint" value="' .
				$this->cont['PKWK_ENCODING_HINT'] . '" /></div>', $retvar);
		} else {
			return $retvar;
		}
	}
	
	// Call API 'inline' of the plugin
	function do_plugin_inline($name, $args, & $body) {
		//	global $digest;
	
		//if($this->do_plugin_init($name) === FALSE)
		//		return '[Plugin init failed: ' . $name . ']';
	
		if ($args !== '') {
			$aryargs = $this->csv_explode(',', $args);
		} else {
			$aryargs = array();
		}

		// NOTE: A reference of $body is always the last argument
		$aryargs[] = & $body; // func_num_args() != 0
	
		$_digest = $this->root->digest;
		$plugin = & $this->get_plugin_instance($name);
		$retvar  = call_user_func_array(array(& $plugin, 'plugin_' . $name . '_inline'), $aryargs);

		$this->root->digest  = $_digest; // Revert
	
		if($retvar === FALSE) {
		// Do nothing
		return htmlspecialchars('&' . $name . ($args ? '(' . $args . ')' : '') . ';');
		} else {
			return $retvar;
		}
	}
	
	// Get HTTP_ACCEPT_LANGUAGE
	function get_accept_language () {
		$lang = "en";
		$accept = @ $_SERVER["HTTP_ACCEPT_LANGUAGE"];
		// cookie に指定があればそれを優先
		if (!empty($this->root->cookie['lang'])) {
			$accept = $this->root->cookie['lang'] . "," . $accept;
		}
		if (!empty($accept))
		{
			if (preg_match_all("/([\w]+)/i",$accept,$match,PREG_PATTERN_ORDER)) {
				foreach($match[1] as $lang) {
					if (file_exists($this->cont['DATA_HOME']."private/lang/{$lang}.lng.php")) { break; }
					else { $lang = "en"; }
				}
			}
		}
		return $lang;
	}
	
	function get_zone_by_time ($time) {
		$zones = array(
			-8=>"PST",
			-7=>"MST",
			-6=>"CST",
			-5=>"EST",
			-4=>"AST",
			0=>"GMT",
			1=>"CET",
			2=>"EET",
			9=>"JST",
		);
		if (isset($zones[$time])) { return $zones[$time]; }
		$time_string = ($time === 0)? "" : ($time > 0)? ("+".$time) : (string)$time;
		return "UTC".$time_string;
	}
	
	function load_cookie () {
		$cookies = array();
		if (isset($_COOKIE[$this->root->mydirname])) {
			$cookies = explode("\t", $_COOKIE[$this->root->mydirname]);
		}
		$this->root->cookie['ucd'] = (isset($cookies[0])) ? $cookies[0] : "";
		$this->root->cookie['name'] = (isset($cookies[1])) ? $cookies[1] : "";
		$this->root->cookie['skin'] = (isset($cookies[2])) ? $cookies[2] : "";
		$this->root->cookie['lang'] = (isset($cookies[3])) ? $cookies[3] : "";
		if (empty($this->root->userinfo['uname'])) {
			$this->root->userinfo['uname'] = $this->root->cookie['name'];
			$this->root->userinfo['uname_s'] = htmlspecialchars($this->root->userinfo['uname']);
		}
	}
	
	function save_cookie () {
		$data =    $this->root->cookie['ucd'].
			"\t" . $this->root->cookie['name'].
			"\t" . $this->root->cookie['skin'].
			"\t" . $this->root->cookie['lang'];
		$url = parse_url ( $this->cont['ROOT_URL'] );
		setcookie($this->root->mydirname, $data, time()+86400*365, $url['path']); // 1年間
	}
	
	function load_usercookie () {
		
		static $sendcookie = false; 
		
		// cookieの読み込み
		$this->load_cookie();
		
		// user-codeの発行
		if(!$this->root->cookie['ucd']){
			$this->root->cookie['ucd'] = md5(getenv("REMOTE_ADDR"). __FILE__ .gmdate("Ymd", time()+9*60*60));
		}
		$this->root->userinfo['ucd'] = substr(crypt($this->root->cookie['ucd'],($this->root->adminpass)? $this->root->adminpass : 'id'),-11);
		
		// スキン指定をcookieにセット
		if (isset($_GET['setskin'])) {
			$this->root->cookie['skin'] = ($_GET['setskin'] === "none")? "" : preg_replace("/[^\w-]+/","",$_GET['setskin']);
			if (isset($_SERVER['QUERY_STRING'])) {
				$_SERVER['QUERY_STRING'] = preg_replace("/(^|&)setskin=.*?(?:&|$)/","$1",$_SERVER['QUERY_STRING']);
			}
			if (isset($_SERVER['argv'][0])) {
				$_SERVER['argv'][0] = preg_replace("/(^|&)setskin=.*?(?:&|$)/","$1",$_SERVER['argv'][0]);
			}
		}
		
		// 言語指定をcookieにセット
		if (isset($_GET[$this->cont['SETLANG']])) {
			$this->root->cookie['lang'] = ($_GET[$this->cont['SETLANG']] === "none")? "" : preg_replace("/[^\w-]+/","",$_GET[$this->cont['SETLANG']]);
			if (isset($_SERVER['QUERY_STRING'])) {
				$_SERVER['QUERY_STRING'] = preg_replace("/(^|&)".preg_quote($this->cont['SETLANG'],"/")."=.*?(?:&|$)/","$1",$_SERVER['QUERY_STRING']);
			}
			if (isset($_SERVER['argv'][0])) {
				$_SERVER['argv'][0] = preg_replace("/(^|&)".preg_quote($this->cont['SETLANG'],"/")."=.*?(?:&|$)/","$1",$_SERVER['argv'][0]);
			}
		}

		// cookieを更新
		if (!$sendcookie) {	$this->save_cookie(); }
		$sendcookie = TRUE;
	}
	
	function save_name2cookie ($name) {
		$this->root->cookie['name'] = $name;
		$this->save_cookie();
	}
	
	// フォント指定JavaScript
	function fontset_js_tag() {
		return <<<EOD
<script type="text/javascript">
<!--
	pukiwiki_show_fontset_img();
-->
</script>
EOD;
	}
	
	function get_page_cache ($page) {

		// キャッシュ判定
		$cache_file = $this->cont['CACHE_DIR']."page/".$this->encode($page).".".$this->cont['UI_LANG'];
		
		$use_cache_always = FALSE;
		if (isset($this->root->rtf['use_cache_always']) && file_exists($cache_file)) {
			$this->root->userinfo['admin'] = FALSE;
			$this->root->userinfo['uid'] = 0;
			$this->root->userinfo['uname'] = '';
			$this->root->userinfo['uname_s'] = '';
			$this->root->userinfo['gids'] = array();
			$use_cache_always = TRUE;
		}
		
		if (($this->root->userinfo['uid'] === 0 && $this->root->pagecache_min > 0 && file_exists($cache_file) && (filemtime($cache_file) + $this->root->pagecache_min * 60) > time())
			|| $use_cache_always) {
			$cache_dat = unserialize(join('',file($cache_file)));
		} else {
			$cache_dat = FALSE;
		}
		if (!$cache_dat) {
			$body  = $this->convert_html($this->get_source($page));
			
			// キャッシュ保存
			if ($use_cache_always || ($this->root->userinfo['uid'] === 0 && $this->root->pagecache_min > 0)) {
				$fp = fopen($cache_file, "wb");
				fwrite($fp, serialize(
					array(
						'body'          => $body,
						'root'          => array(
							'foot_explain'  => $this->root->foot_explain,
							'head_pre_tags' => $this->root->head_pre_tags,
							'head_tags'     => $this->root->head_tags,
							'related'       => $this->root->related,
							'runmode'       => $this->root->runmode
						),
						'cont'          => array(
							'SKIN_NAME'     => @$this->cont['SKIN_NAME']
						)
					)
				));
				fclose($fp);
			}
		} else {
			$body = array_shift($cache_dat);
			foreach ($cache_dat['root'] as $_key=>$_val) {
				$this->root->$_key = $_val;	
			}
			foreach ($cache_dat['cont'] as $_key=>$_val) {
				$this->cont[$_key] = $_val;	
			}
		}
		return $body;
	}
	
	function clear_page_cache ($page) {
		$base = $this->root->mytrustdirpath."/lang";
		if ($handle = opendir($base)) {
			$clr_pages = $this->root->always_clear_cache_pages;
			$clr_pages[] = $page;
			if ($this->root->clear_cache_parent)
			{
				// 上位層のページ
				while (strpos($page,"/") !== FALSE) {
					$page = dirname($page);
					$clr_pages[] = $page;
				}
			}
			while (false !== ($file = readdir($handle))) {
				if (preg_match("/^([\w-]+)\.lng\.php$/", $file, $match)) {
					foreach ($clr_pages as $_page) {
						@unlink ($this->cont['CACHE_DIR']."page/".$this->encode($_page).".".$match[1]);
					}
				}
			}
			closedir($handle);
		}
		return ;
	}
	
	function get_additional_headtags (& $obj) {
		// Pre Tags
		$head_pre_tag = ! empty($obj->root->head_pre_tags) ? join("\n", $obj->root->head_pre_tags) ."\n" : '';
		
		// Tags will be inserted into <head></head>
		$head_tag = ! empty($obj->root->head_tags) ? join("\n", $obj->root->head_tags) ."\n" : '';
		
		// WikiHelper JavaScript
		$head_tag .= <<<EOD
<script type="text/javascript">
<!--
var wikihelper_root_url = "{$obj->cont['HOME_URL']}";
//-->
</script>
<script type="text/javascript" src="{$obj->cont['HOME_URL']}skin/loader.php?type=js&amp;src=default.{$obj->cont['UI_LANG']}"></script>
EOD;
		// reformat
		$obj->root->head_tags = array();
		$obj->root->head_pre_tags = array();
		
		return array($head_pre_tag, $head_tag);
	}
	
	// ページ情報を得る
	function get_pginfo ($page, $src='') {
		static $info = array();
		
		if (isset($info[$this->xpwiki->pid][$page])) { return $info[$this->xpwiki->pid][$page]; }
		
		if ($src) {
			if (is_array($src)) {
				$src = join('', $src);
			}
		} else {
			$src = $this->get_source($page, TRUE, 1024);
		}
		
		// inherit = 0:継承指定なし, 1:規定値継承指定, 2:強制継承指定, 3:規定値継承した値, 4:強制継承した値
		if (preg_match("/^#pginfo\((.+)\)\s*/m", $src, $match)) {
			$_tmp = explode("\t",$match[1]);
			$pginfo['uid']       = (int)$_tmp[0];
			$pginfo['ucd']       = $_tmp[1];
			$pginfo['uname']     = $_tmp[2];
			$pginfo['einherit']  = (int)$_tmp[3];
			$pginfo['eaids']     = $_tmp[4];
			$pginfo['egids']     = $_tmp[5];
			$pginfo['vinherit']  = (int)$_tmp[6];
			$pginfo['vaids']     = $_tmp[7];
			$pginfo['vgids']     = $_tmp[8];
			$pginfo['lastuid']   = (int)$_tmp[9];
			$pginfo['lastucd']   = $_tmp[10];
			$pginfo['lastuname'] = $_tmp[11];
		} else {
			$pginfo = $this->pageinfo_inherit($page);
			if (!$this->is_page($page))
			{
				$pginfo['uid'] = $this->root->userinfo['uid'];
				$pginfo['ucd'] = $this->root->userinfo['ucd'];
				$pginfo['uname'] = $this->root->cookie['name'];
			}
		}
		$info[$this->xpwiki->pid][$page] = $pginfo;
		return $pginfo;
	}
	
	// ページ情報の継承を受ける(指定があれば)
	function pageinfo_inherit ($page) {
		// サイト規定値読み込み
		$pginfo = $this->root->pginfo;
		
		$done['edit'] = $done['view'] = 0;
		while ($done['edit'] < 2 && $done['view'] < 2) {
			if (strpos($page, '/') !== FALSE) {
				//上位ページを見る
				$uppage = dirname($page);
				$_pginfo = $this->get_pginfo($uppage);
				// 編集権限
				if ($done['edit'] < 2) {
					if ($_pginfo['einherit'] === 2 || $_pginfo['einherit'] === 4) {
						$pginfo['einherit'] = 4;
						$pginfo['eaids'] = $_pginfo['eaids'];
						$pginfo['egids'] = $_pginfo['egids'];
						$done['edit'] = 2;
					}
					if (!$done['edit'] && ($_pginfo['einherit'] === 1 || $_pginfo['einherit'] === 3)) {
						$pginfo['einherit'] = 3;
						$pginfo['eaids'] = $_pginfo['eaids'];
						$pginfo['egids'] = $_pginfo['egids'];
						$done['edit'] = 1;
					}
				}
				// 閲覧権限
				if ($done['view'] < 2) {
					if ($_pginfo['vinherit'] === 2 || $_pginfo['vinherit'] === 4) {
						$pginfo['vinherit'] = 4;
						$pginfo['vaids'] = $_pginfo['vaids'];
						$pginfo['vgids'] = $_pginfo['vgids'];
						$done['view'] = 2;
					}
					if (!$done['view'] && ($_pginfo['vinherit'] === 1 || $_pginfo['vinherit'] === 3)) {
						$pginfo['vinherit'] = 3;
						$pginfo['vaids'] = $_pginfo['vaids'];
						$pginfo['vgids'] = $_pginfo['vgids'];
						$done['view'] = 1;
					}
				}
				// さらに上階層
				$page = $uppage;
			} else {
				// 上階層なし
				$done['edit'] = 2;
				$done['view'] = 2;
			}
		}
		return $pginfo;
	}
	
	// グループ選択フォーム作成
	function make_grouplist_form ($tagname, $ids = array(), $disabled='') {
		$groups = $this->get_group_list();
		$mygroups = $this->get_mygroups();
		
		//$disabled = ($disabled)? ' disabled="disabled"' : '';
		
		$size = min(10, count($groups));
		$ret = '<select size="'.$size.'" name="'.$tagname.'[]" id="'.$tagname.'[]" multiple="multiple"'.$disabled.'>'."\n";
		$all = FALSE;
		if ($ids === 'all' || $ids === 'none') {
			$ids = array();
		}
		foreach ($groups as $gid => $gname){
			if ($this->root->userinfo['admin'] || in_array($gid,$mygroups)){
				$sel = (in_array($gid,$ids))? ' selected="selected"' : '';
				$ret .= '<option value="'.$gid.'"'.$sel.'>'.$gname.'</option>';
			}
		}
		$ret .= '</select>';
		return $ret;
	}
	
	// ユーザー選択フォーム作成
	function make_userlist_form ($tagname, $ids = array(), $disabled='') {
		$allusers = $this->get_allusers();
		
		//$disabled = ($disabled)? ' disabled="disabled"' : '';
		
		$ret = '<select size="10" name="'.$tagname.'[]" id="'.$tagname.'[]" multiple="multiple"'.$disabled.'>';
		$all = FALSE;
		if ($ids === 'all' || $ids === 'none') {
			$ids = array();
		}
		foreach ($allusers as $uid => $uname){
				$sel = (in_array($uid,$ids))? ' selected="selected"' : '';
				if ($uid !== $this->root->userinfo['uid']) $ret .= '<option value="'.$uid.'"'.$sel.'>'.$uname.'</option>';
		}
		$ret .= '</select>';
		return $ret;		
	}
	
	//　ページオーナー権限があるかどうか
	function is_owner ($page) {
		if ($this->root->userinfo['admin']) { return TRUE; }
		$pginfo = $this->get_pginfo($page);
		if ($pginfo['uid'] && ($pginfo['uid'] === $this->root->userinfo['uid'])) { return TRUE; }
		return FALSE;
	}
	
	// ページ毎閲覧権限チェック
	function check_readable_page ($page, $auth_flag = TRUE, $exit_flag = TRUE) {
		if ($this->is_owner($page)) return TRUE;
		
		$ret = FALSE;
		// #pginfo
		$pginfo = $this->get_pginfo($page);
		if ($pginfo['vgids'] === 'none' || $pginfo['vgids'] === 'none') {
			$ret = FALSE;
		} else {
			$vgids = explode('&', $pginfo['vgids']);
			$vaids = explode('&', $pginfo['vaids']);
			$_vg = array_merge($vgids, $this->root->userinfo['gids']);
			$vgauth = (count($_vg) === count(array_unique($_vg)))? FALSE : TRUE;
			
			if (
				$pginfo['vgids'] === 'all' || 
				$pginfo['vaids'] === 'all' ||
				$vgauth || 
				in_array($pginfo['uid'], $vaids)	
			) {
				$ret = TRUE;
			}
		}
		if ($ret) return TRUE;
		if ($exit_flag) {
			$title = $this->root->_msg_not_readable;
			$this->redirect_header($this->root->script, 1, $title);
			exit;
		}
		return FALSE;
	}

	// ページ毎編集権限チェック
	function check_editable_page ($page, $auth_flag = TRUE, $exit_flag = TRUE) {
		if ($this->is_owner($page)) return TRUE;
		
		if (!$this->check_readable_page ($page, $auth_flag, $exit_flag)) {
			return FALSE;
		}
		
		$ret = FALSE;
		// #pginfo
		$pginfo = $this->get_pginfo($page);
		if ($pginfo['egids'] === 'none' || $pginfo['egids'] === 'none') {
			$ret = FALSE;
		} else {
			$egids = explode('&', $pginfo['egids']);
			$eaids = explode('&', $pginfo['eaids']);
			$_eg = array_merge($egids, $this->root->userinfo['gids']);
			$eauth = (count($_eg) === count(array_unique($_eg)))? FALSE : TRUE;
			
			if (
				$pginfo['egids'] === 'all' || 
				$pginfo['eaids'] === 'all' ||
				$eauth || 
				in_array($pginfo['uid'], $eaids)	
			) {
				$ret = TRUE;
			}
		}
		if ($ret) return TRUE;
		if ($exit_flag) {
			$title = $this->root->_msg_not_editable;
			$this->redirect_header($this->root->script.'?'.rawurlencode($page), 1, $title);
			exit;
		}
		return FALSE;
	}
	
	function add_tag_head ($file,$pre=TRUE) {
		static $done = array();
		if (isset($done[$this->xpwiki->pid][$file])) { return; }
		$done[$this->xpwiki->pid][$file] = TRUE;
		
		if (preg_match("/^(.+)\.([^\.]+)$/",$file,$match)) {
			$target = $pre? 'head_pre_tags' : 'head_tags';
			$block = (isset($this->root->is_block))? '&amp;b=1' : '';
			if ($match[2] === 'css') {
				$this->root->{$target}[] = '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->cont['HOME_URL'].'skin/loader.php?type=css&amp;src='.$match[1].$block.'" />';
			} else if ($match[2] === 'js') {
				$this->root->{$target}[] = '<script type="text/javascript" src="'.$this->cont['HOME_URL'].'skin/loader.php?type=js&amp;src='.$match[1].'"></script>';
			}
		}	
	}
}
?>