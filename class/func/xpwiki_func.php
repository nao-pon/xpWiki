<?php
//
// Created on 2006/10/02 by nao-pon http://hypweb.net/
// $Id: xpwiki_func.php,v 1.60 2007/05/11 11:49:58 nao-pon Exp $
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
					$count[$this->xpwiki->pid][$name] = 1;
					$ret = $class_name;
					if (isset($plugin_files['user'])) {
						require_once($plugin_files['user']);
						$class_name = "xpwiki_user_plugin_{$name}";
						if (class_exists($class_name)) {
							$ret = $class_name;
						}
					}
					$ret = $class_name;
					$exist[$this->xpwiki->pid][$name] = $ret;
					$count[$this->xpwiki->pid][$name] = 1;
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
		
		$plugin = & $this->get_plugin_instance($name);
		
		// ブラウザとのコネクションが切れても実行し続ける
		$_iua = ignore_user_abort(TRUE);
		
		$retvar = call_user_func(array(& $plugin, 'plugin_' . $name . '_action'));
		
		// ignore_user_abort の設定値戻し
		ignore_user_abort($_iua);
		
		// Insert a hidden field, supports idenrtifying text enconding
		if ($this->cont['PKWK_ENCODING_HINT'] != '')
				$retvar =  preg_replace('/(<form[^>]*>)/', '$1' . "\n" .
					'<div><input type="hidden" name="encode_hint" value="' .
					$this->cont['PKWK_ENCODING_HINT'] . '" /></div>', $retvar);
	
		return $retvar;
	}
	
	// Call API 'convert' of the plugin
	function do_plugin_convert($name, $args = '') {
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
		$lang = $this->cont['LANG']; // 規定値
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
			if (empty($this->root->cookie['name'])) {
				$this->root->userinfo['uname'] = $this->root->siteinfo['anonymous'];
			} else {
				$this->root->userinfo['uname'] = $this->root->cookie['name'];
			}
			$this->root->userinfo['uname_s'] = htmlspecialchars($this->root->userinfo['uname']);
		}
		
		// 他の言語切り替えシステムをチェック
		if (!empty($this->cont['SETLANG_C']) && !empty($this->root->cookie[$this->cont['SETLANG_C']])) {
			if (preg_match($this->cont['ACCEPT_LANG_REGEX'], $this->root->cookie[$this->cont['SETLANG_C']], $match)) {
				$this->root->cookie['lang'] = $match[1];
			}
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
		if (isset($this->root->get['setskin'])) {
			$this->root->cookie['skin'] = ($this->root->get['setskin'] === "none")? "" : preg_replace("/[^\w-]+/","",$this->root->get['setskin']);
			if (isset($_SERVER['QUERY_STRING'])) {
				$_SERVER['QUERY_STRING'] = preg_replace("/(^|&)setskin=.*?(?:&|$)/","$1",$_SERVER['QUERY_STRING']);
			}
			if (isset($_SERVER['argv'][0])) {
				$_SERVER['argv'][0] = preg_replace("/(^|&)setskin=.*?(?:&|$)/","$1",$_SERVER['argv'][0]);
			}
		}
		
		// 言語指定をcookieにセット
		if (isset($this->root->get[$this->cont['SETLANG']])) {
			$this->root->cookie['lang'] = '';
			// 正規化
			if (preg_match($this->cont['ACCEPT_LANG_REGEX'], $this->root->get[$this->cont['SETLANG']], $match)) {
				$this->root->cookie['lang'] = $match[1];
			}
			//$this->root->cookie['lang'] = ($this->root->get[$this->cont['SETLANG']] === "none")? "" : preg_replace("/[^\w-]+/","",$this->root->get[$this->cont['SETLANG']]);
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
	
	function get_body ($page) {

		// キャッシュ判定
		$is_block = (isset($this->root->is_block))? 'b_' : '';
		$cache_file = $this->cont['CACHE_DIR']."page/".$is_block.$this->encode($page).".".$this->cont['UI_LANG'];
		$use_cache = ($this->root->userinfo['uid'] === 0 && $this->root->pagecache_min > 0 && file_exists($cache_file) && (filemtime($cache_file) + $this->root->pagecache_min * 60) > time());
		
		// 強制キャッシュ利用指定フラグ判定
		if (isset($this->root->rtf['use_cache_always'])) {
			// ゲスト扱いに固定
			$this->root->userinfo['admin'] = FALSE;
			$this->root->userinfo['uid'] = 0;
			$this->root->userinfo['uname'] = '';
			$this->root->userinfo['uname_s'] = '';
			$this->root->userinfo['gids'] = array();
			if (file_exists($cache_file)) {
				$use_cache = TRUE;
			}
		}
		
		if ($use_cache) {
			// キャッシュ利用
			$cache_dat = unserialize(join('',file($cache_file)));
			$body = array_shift($cache_dat);
			foreach ($cache_dat['root'] as $_key=>$_val) {
				$this->root->$_key = $_val;
			}
			foreach ($cache_dat['cont'] as $_key=>$_val) {
				$this->cont[$_key] = $_val;	
			}
		} else {
			// 通常のレンダリング
			$this->root->rtf['convert_nest'] = 0;
			$body  = $this->convert_html($this->get_source($page));
			$this->root->content_title = $this->get_heading($page);
			
			// キャッシュ保存
			if (isset($this->root->rtf['use_cache_always']) || ($this->root->userinfo['uid'] === 0 && $this->root->pagecache_min > 0)) {
				$fp = fopen($cache_file, "wb");
				fwrite($fp, serialize(
					array(
						'body'          => $body,
						'root'          => array(
							'foot_explain'  => $this->root->foot_explain,
							'head_pre_tags' => $this->root->head_pre_tags,
							'head_tags'     => $this->root->head_tags,
							'related'       => $this->root->related,
							'runmode'       => $this->root->runmode,
							'related_link'  => $this->root->related_link,
							'content_title' => $this->root->content_title,
							'allow_pagecomment'=> $this->root->allow_pagecomment
						),
						'cont'          => array(
							'SKIN_NAME'     => @$this->cont['SKIN_NAME']
						)
					)
				));
				fclose($fp);
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
						@unlink ($this->cont['CACHE_DIR']."page/b_".$this->encode($_page).".".$match[1]);
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
<script type="text/javascript" src="{$obj->cont['HOME_URL']}skin/loader.php?src=default.{$obj->cont['UI_LANG']}{$obj->cont['FILE_ENCORD_EXT']}.js"></script>
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
				$pginfo['uname'] = htmlspecialchars($this->root->userinfo['uname']);
			}
		}
		
		if ($pginfo['uid'] && !$pginfo['uname']) {
			$_uinfo = $this->get_userinfo_by_id($pginfo['uid']);
			$pginfo['uname'] = htmlspecialchars($_uinfo['uname']);
		}
		if ($pginfo['lastuid'] && !$pginfo['lastuname']) {
			$_uinfo = $this->get_userinfo_by_id($pginfo['lastuid']);
			$pginfo['lastuname'] = htmlspecialchars($_uinfo['uname']);
		}

		$pginfo['reading'] = '';
		if ($page) {
			$info[$this->xpwiki->pid][$page] = $pginfo;
		}
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
	
	// ページオーナー権限があるかどうか
	function is_owner ($page) {
		if ($this->root->userinfo['admin']) { return TRUE; }
		$pginfo = $this->get_pginfo($page);
		if ($this->is_page($page) && $pginfo['uid'] && ($pginfo['uid'] === $this->root->userinfo['uid'])) { return TRUE; }
		return FALSE;
	}
	
	// ページ毎閲覧権限チェック
	function check_readable_page ($page, $auth_flag = TRUE, $exit_flag = TRUE) {
		if ($this->is_owner($page)) return TRUE;
		
		$ret = FALSE;
		// #pginfo
		$pginfo = $this->get_pginfo($page);
		if ($pginfo['vgids'] === 'none' && $pginfo['vaids'] === 'none') {
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
				in_array($this->root->userinfo['uid'], $vaids, true)
			) {
				$ret = TRUE;
			}
		}
		if ($ret) return TRUE;
		if ($exit_flag) {
			$title = $this->root->_msg_not_readable;
			if (!$this->root->userinfo['uid'] && $auth_flag) {
				// needs login
				$this->redirect_header($this->root->siteinfo['loginurl'], 1, $title, true);
			} else if ($page === $this->root->defaultpage) {
				$this->redirect_header($this->root->siteinfo['rooturl'], 1, $title);
			} else {
				$this->redirect_header($this->root->script, 1, $title);
			}
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
		if ($pginfo['egids'] === 'none' && $pginfo['eaids'] === 'none') {
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
				in_array($this->root->userinfo['uid'], $eaids, true)
			) {
				$ret = TRUE;
			}
		}
		if ($ret) return TRUE;
		if ($exit_flag) {
			$title = $this->root->_msg_not_editable;
			if (!$this->root->userinfo['uid'] && $auth_flag) {
				// needs login
				$this->redirect_header($this->root->siteinfo['loginurl'], 1, $title, true);
			} else if ($this->is_page($page)) {
				$this->redirect_header($this->root->script.'?'.rawurlencode($page), 1, $title);
			} else {
				$this->redirect_header($this->root->script, 1, $title);
			}
			exit;
		}
		return FALSE;
	}
	
	// なぞなぞ認証をチェック
	function check_riddle () {
		$ret = FALSE;
		if ($this->root->userinfo['admin'] ||
			$this->root->riddle_auth === 0 ||
			($this->root->riddle_auth === 1 && $this->root->userinfo['uid'] !== 0)
		) return TRUE;
		foreach ($this->root->vars as $key => $val) {
			if (substr($key, 0, 6) === 'riddle') {
				$q_key = substr($key, 6);
				foreach ($this->root->riddles as $q => $a) {
					if ($q_key === md5($this->cont['HOME_URL'].$q)) {
						if ($a === rtrim($val)) {
							$ret = TRUE;
						}
						break;
					}
				}
				break;
			}
		}
		return $ret;	
	}
	function add_tag_head ($file,$pre=TRUE) {
		static $done = array();
		if (isset($done[$this->xpwiki->pid][$file])) { return; }
		$done[$this->xpwiki->pid][$file] = TRUE;
		
		if (preg_match("/^(.+)\.([^\.]+)$/",$file,$match)) {
			$target = $pre? 'head_pre_tags' : 'head_tags';
			$block = (isset($this->root->is_block))? 'b=1&amp;' : '';
			if ($match[2] === 'css') {
				$this->root->{$target}[] = '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->cont['HOME_URL'].'skin/loader.php?'.$block.'src='.$match[1].'.css" />';
			} else if ($match[2] === 'js') {
				$this->root->{$target}[] = '<script type="text/javascript" src="'.$this->cont['HOME_URL'].'skin/loader.php?src='.$match[1].'.js"></script>';
			}
		}	
	}

	// リファラチェック $blank = 1 で未設定も不許可(デフォルトで未設定は許可)
	function refcheck($blank = 0, $ref = NULL)
	{
		if (is_null($ref)) $ref = @$_SERVER['HTTP_REFERER'];
		if (!$blank && !$ref) return true;
		if (strpos($ref, $this->cont['ROOT_URL']) === 0 ) return TRUE;
		
		return FALSE;
	}
	
	// ページ作成者のIDを求める
	function get_pg_auther ($page) {
		$pginfo = $this->get_pginfo($page);
		return $pginfo['uid'];
	}


	//EXIFデータを得る
	function get_exif_data($file, $alltag = FALSE){
		$ret = array();
		if (function_exists('read_exif_data')) {
			$exif_data = @read_exif_data($file);
			if (!$exif_data) return $ret;
			
			$ret['title'] = "-- Shot Info --";
			//if (isset($exif_data['Make']))	$ret['Maker '] = $exif_data['Make'];
			if (isset($exif_data['Model']))
				$ret['Camera '] = $exif_data['Model'];
			
			if (isset($exif_data['DateTimeOriginal']))
				$ret['Date '] = $exif_data['DateTimeOriginal'];
			
			if (isset($exif_data['ExposureTime']))
				$ret['Shutter Speed '] = $this->get_exif_numbar($exif_data['ExposureTime']).' sec';
			
			if (isset($exif_data['FNumber']))
				$ret['F(Shot) '] = 'F '.$this->get_exif_numbar($exif_data['FNumber']);
			
			if (isset($exif_data['FocalLength']))
				$ret['Lens '] = $this->get_exif_numbar($exif_data['FocalLength']).' mm';
					
			if (isset($exif_data['MaxApertureValue']))
				@$ret['Lens '] .= '/F '.$this->get_exif_numbar($exif_data['MaxApertureValue']);
			
			if (isset($exif_data['Flash'])){
				if ($exif_data['Flash'] == 0) {$ret['Flash '] = "OFF";}
				else if ($exif_data['Flash'] == 1) {$ret['Flash '] = "ON";}
				else if ($exif_data['Flash'] == 5) {$ret['Flash '] = "Light(No Reflection)";}
				else if ($exif_data['Flash'] == 7) {$ret['Flash '] = "Light(Reflection)";}
				else if ($exif_data['Flash'] == 9) {$ret['Flash '] = "Always ON";}
				else if ($exif_data['Flash'] == 16) {$ret['Flash '] = "Always OFF";}
				else if ($exif_data['Flash'] == 24) {$ret['Flash '] = "Auto(None)";}
				else if ($exif_data['Flash'] == 25) {$ret['Flash '] = "Auto(Light)";}
				else {$ret['Flash'] = $exif_data['Flash '];}
			}
			
			if ($alltag) {
				$ret['-- :Orignal Exif'] = '--';
				foreach ($exif_data as $key=>$sect) {
					if (is_array($sect) == FALSE) {
						$ret[$key] = trim($sect);
					} else {
						foreach($sect as $name=>$val)	$ret[$key . $name] = trim($val);
					}
				}
				// 表示しないパラメーター
				unset($ret['FileName'], $ret['MakerNote']);
				
			}
		}
		return $ret;
	}
	function get_exif_numbar ($dat, $APEX=FALSE) {
		if (preg_match('#^([\d]+)/([1-9]+)$#',$dat,$match)) {
			$dat = $match[1] / $match[2];
		} else {
			$dat = (float)$dat;
		}
		if ($APEX) {
			$dat = pow(sqrt(2), $dat);
		}
		if ($dat < 1) {
			$dat = '1/' . (int)(1/$dat);
		}
		return $dat;
	}
	
	// php.ini の略式文字列からバイト数を得る
	function return_bytes($val) {
		$val = trim($val);
		$last = strtolower($val{strlen($val)-1});
		switch($last) {
			// 'G' は、PHP 5.1.0 より有効となる
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			 case 'k':
				$val *= 1024;
		}
		return $val;
	}

	//ページ名から最初の見出しを得る(ファイルから)
	function get_heading_init($page)
	{
		$_body = join('', $this->get_source($page));
		if (!$_body) return '';
		
		$ret = '';
		if (preg_match('/^\*+.+\s*$/m',$_body,$match)) {
			$ret = $match[0];
		} else if (preg_match('/^(?! |\s|#|\/\/).+\s*$/m',$_body,$match)) {
			$ret = $match[0];
		}
		
		if ($ret) {
			$ret = strip_tags($this->convert_html($ret));
			$ret = str_replace(array("\r","\n","\t", '&dagger;', '?', '&nbsp;'),' ',$ret);
			$ret = preg_replace('/\s+/',' ',$ret);
			$ret = trim($ret);
			$ret = $this->unhtmlspecialchars($ret, ENT_QUOTES);
		}
		return ($ret)? $ret : "- no title -";
	}
	
	function unhtmlspecialchars ($str, $quote_style = ENT_COMPAT) {
		$fr = array('&lt;', '&gt;');
		$tr = array('<',    '>');
		if ($quote_style !== ENT_NOQUOTES) {
			$fr[] = '&quot;';
			$tr[] = '"';
		}
		if ($quote_style === ENT_QUOTES) {
			$fr[] = '&#039;';
			$tr[] = '\'';
		}
		$fr[] = '&amp;';
		$tr[] = '&';
		return str_replace($fr, $tr, $str);
	}
	
	// ページ頭文字読みの配列を取得
	function get_readings() {
		$readings = array();
		$pages = $this->get_existpages(false, "", array('select' => array('reading', 'title')));
		foreach ($pages as $page=>$dat) {
			if (empty($dat['reading'])) {
				$dat['reading'] = $this->get_page_reading($page);
			}
			$readings[$page] = $dat['reading'];
			$titles[$page] = $dat['title'];
		}
		return array($readings, $titles);
	}

	// #pginfo を除去する
	function remove_pginfo ($str) {
		return preg_replace($this->cont['PKWK_PGINFO_REGEX'], '', $str);
	}

	// ページ内容追加履歴の書き出し
	function push_page_changes($page, $txt, $del=false) {
		
		if (!$this->is_page($page)) return;
		$id = $this->get_pgid_by_name($page);
		$add_file = $this->cont['DIFF_DIR'].$id.".add";
	
		if ($del) {
			@unlink($add_file);
			return;
		}	

		$txt = preg_replace('/^\+(.*\s*)$/m', '$1', $txt);
		
		// ゲスト扱いにする
		$_userinfo = $this->root->userinfo;
		$this->root->userinfo['admin'] = FALSE;
		$this->root->userinfo['uid'] = 0;
		$this->root->userinfo['uname'] = '';
		$this->root->userinfo['uname_s'] = '';
		$this->root->userinfo['gids'] = array();
		
		$txt = rtrim($this->convert_html($txt));
		
		$this->root->userinfo = $_userinfo;
		
		if (!$txt) {return;}
	
		$sep = "&#182;<!--ADD_TEXT_SEP-->\n";
		$limit = 5;
		
		$data = @join('',@file($add_file));
		if ($data) {
			$adds = preg_split("/".preg_quote($sep,"/")."/",$data);
			$adds = array_slice($adds,0,$limit-1);
		} else {
			$adds = array();
		}
		
		array_unshift($adds,$txt);
		
		if ($fp = @fopen($add_file,"wb")) {
			fputs($fp,join($sep,$adds));
			fclose($fp);
		}
	}

	// ページ内容追加履歴の取得
	function get_page_changes ($page) {
		if (!$this->is_page($page)) return;
		
		$id = $this->get_pgid_by_name($page);
		$add_file = $this->cont['DIFF_DIR'].$id.".add";
		if (file_exists($add_file)) {
			return join('', file($add_file));
		} else {
			return '';
		}
	}
	
	// ページ専用CSSタグを得る
	function get_page_css_tag ($page) {
		$ret = '';
		$_page = '';
		$block = (isset($this->root->is_block))? 'b=1&amp;' : '';
		
		// トップページ
		$pgid = $this->get_pgid_by_name($this->root->defaultpage);
		if (file_exists($this->cont['CACHE_DIR'].$pgid.'.css'))
		{
			$ret .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->cont['HOME_URL'].'skin/loader.php?'.$block.'src='.$pgid.'.page.css" />'."\n";
		}
		
		foreach(explode('/',$page) as $val)
		{
			$_page = ($_page)? $_page."/".$val : $val;
			if ($_page !== $this->root->defaultpage) {
				$pgid = $this->get_pgid_by_name($_page);
				if (file_exists($this->cont['CACHE_DIR'].$pgid.'.css'))
				{
					$ret .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->cont['HOME_URL'].'skin/loader.php?'.$block.'src='.$pgid.'.page.css" />'."\n";
				}
			}
		}
		return $ret;
	}
	
	// SKIN Function
	function skin_navigator ($obj, $key, $value = '', $javascript = '') {
		$lang = & $obj->root->_LANG['skin'];
		$link = & $obj->root->_LINK;
		if (! isset($lang[$key])) { echo $key.' LANG NOT FOUND'; return FALSE; }
		if (! isset($link[$key])) { echo $key.' LINK NOT FOUND'; return FALSE; }
		if (! $obj->cont['PKWK_ALLOW_JAVASCRIPT']) $javascript = '';
	
		echo '<a href="' . $link[$key] . '" ' . $javascript . '>' .
			(($value === '') ? $lang[$key] : $value) .
			'</a>';
	
		return TRUE;
	}

	// SKIN Function
	function skin_toolbar ($obj, $key, $x = 20, $y = 20) {
		$lang  = & $obj->root->_LANG['skin'];
		$link  = & $obj->root->_LINK;
		$image = & $obj->root->_IMAGE['skin'];
		if (! isset($lang[$key]) ) { echo $key.' LANG NOT FOUND';  return FALSE; }
		if (! isset($link[$key]) ) { echo $key.' LINK NOT FOUND';  return FALSE; }
		if (! isset($image[$key])) { echo $key.' IMAGE NOT FOUND'; return FALSE; }
	
		echo '<a href="' . $link[$key] . '">' .
			'<img src="' . $obj->cont['IMAGE_DIR'] . $image[$key] . '" width="' . $x . '" height="' . $y . '" ' .
				'alt="' . $lang[$key] . '" title="' . $lang[$key] . '" />' .
			'</a>';
	
		return TRUE;
	}

	// Breadcrumbs
	function get_breadcrumbs_array ($page, $name = 'name', $url = 'url') {
		$parts = explode('/', $page);
	
		$self = array_pop($parts); // Remove the page itself
		$ret = array();
		//$ret[] = array($name => $self, $url = '');
		while (! empty($parts)) {
			$_landing = join('/', $parts);
			$landing  = rawurlencode($_landing);
			$element = htmlspecialchars(array_pop($parts));
			
			if (! $this->is_page($_landing)) {
				// Page not exists
				$ret[] = array($name => $element, $url => '');
			} else {
				// Page exists
				$link = (@$this->root->static_url)? $this->get_pgid_by_name($_landing) . '.html' : '?' . $landing;
				$ret[] = array($name => $element, $url => $this->root->script . $link);
			}
		}
		$ret[] = array($name => $this->root->module['title'], $url => $this->root->script);
		
		return array_reverse($ret);
		
	}

/*----- DB Functions -----*/ 
	//ページ名からページIDを求める
	function get_pgid_by_name ($page)
	{
		//static $page_id = array();
		$page = addslashes($page);
		if (isset($this->root->pgids[$page])) return $this->root->pgids[$page];
		
		$db =& $this->xpwiki->db;
		$query = "SELECT `pgid` FROM ".$db->prefix($this->root->mydirname."_pginfo")." WHERE name='$page' LIMIT 1";
		$res = $db->query($query);
		if (!$res) return 0;
		list($ret) = mysql_fetch_row($res);
		$this->root->pgids[$page] = $ret;
		return $ret;
	}

	//ページIDからページ名を求める
	function get_name_by_pgid($id)
	{
		static $page_name = array();
		if (isset($page_name[$this->xpwiki->pid][$id])) return $page_name[$this->xpwiki->pid][$id];
		
		$db =& $this->xpwiki->db;
		$query = "SELECT `name` FROM ".$db->prefix($this->root->mydirname."_pginfo")." WHERE pgid='$id' LIMIT 1";
		$res = $db->query($query);
		if (!$res) return '';
		list($ret) = mysql_fetch_row($res);
		$page_name[$id] = $ret;
		return $ret;
	}

	//ページ名から最初の見出しを得る
	function get_heading($page, $init=false)
	{
		static $ret = array();
		$page = $this->strip_bracket($page);
		
		if (isset($ret[$this->xpwiki->pid][$page])) return $ret[$this->xpwiki->pid][$page];
		
		$page = addslashes($page);
		$db =& $this->xpwiki->db;
		$query = "SELECT `title` FROM ".$db->prefix($this->root->mydirname."_pginfo")." WHERE name='$page' LIMIT 1;";
		$res = $db->query($query);
		if (!$res) return "";
		$_ret = mysql_fetch_row($res);
		$_ret = htmlspecialchars($_ret[0], ENT_QUOTES);
		return $ret[$this->xpwiki->pid][$page] = ($_ret || $init)? $_ret : htmlspecialchars($page,ENT_NOQUOTES);
	}
	
	// 全ページ名を配列にDB版
	function get_existpages($nocheck = FALSE, $base = '', $options = array())
	{
		// File版を使用
		if (is_string($nocheck) && $nocheck !== $this->cont['DATA_DIR']) {
			return parent::get_existpages($nocheck,$base);
		}
		
		static $_aryret = array();
		if (isset($_aryret[$this->xpwiki->pid]) && $nocheck === FALSE && $base === '' && !$options) return $_aryret[$this->xpwiki->pid];
		
		$keys = array(
			'where'     => '',
			'limit'     => 0,
			'order'     => '',
			'nolisting' => FALSE,
			'nochiled'  => FALSE,
			'nodelete'  => TRUE,
			'withtime'  => FALSE,
			'select'    => array() );
		foreach ($keys as $key => $def) {
			$$key = (isset($options[$key]))? $options[$key] : $def ;
		}
		
		$aryret = array();
		
		if (!$nocheck) {
			if ($where)
				$where = " (" . $this->get_readable_where() . ") AND ($where)";
			else
				$where = $this->get_readable_where();
		}
		
		if ($base)
		{
			if (substr($base,-1) == '/')
			{
				$base = addslashes(substr($base,0,-1));
				if ($nochiled)
					//$base_where = "name = '$base' OR ( name LIKE '$base/%' AND name NOT LIKE '$base/%/%' )";
					$base_where = "name LIKE '$base/%' AND name NOT LIKE '$base/%/%'";
				else
					//$base_where = "name = '$base' OR name LIKE '$base/%'";
					$base_where = "name LIKE '$base/%'";
			}
			else
			{
				$base = addslashes($this->strip_bracket($base));
				if ($nochiled)
					$base_where = "name LIKE '$base%' AND name NOT LIKE '$base%/%'";
				else
					$base_where = "name LIKE '$base%'";
			}
			if ($where)
				$where = " ($base_where) AND ($where)";
			else
				$where = " $base_where";
				
		}
		else
		{
			if ($nochiled)
			{
				$base_where = "name NOT LIKE '%/%'";
	
				if ($where)
					$where = " ($base_where) AND ($where)";
				else
					$where = " $base_where";
			}
		}
		if ($nolisting)
		{
			if ($where)
				$where = " (name NOT LIKE ':%') AND ($where)";
			else
				$where = " (name NOT LIKE ':%')";
		}
		if ($nodelete)
		{
			if ($where)
				$where = " (editedtime !=0) AND ($where)";
			else
				$where = " (editedtime !=0)";
		}
		if ($where) $where = " WHERE".$where;
		$limit = ($limit)? " LIMIT $limit" : "";
		$_select = '';
		if ($select) {
			$keys = array_merge($select, array('name', 'pgid'));
			$keys = array_unique($keys);
			$_select = '`' . join('`,`', $keys) . '`';
			$query = 'SELECT '.$_select.' FROM '.$this->xpwiki->db->prefix($this->root->mydirname."_pginfo").$where.$order.$limit;
		} else {
			$query = 'SELECT `editedtime`, `name`, `pgid` FROM '.$this->xpwiki->db->prefix($this->root->mydirname."_pginfo").$where.$order.$limit;
		}
		$res = $this->xpwiki->db->query($query);
		if ($res)
		{
			if ($select) {
				while($data = mysql_fetch_assoc($res)) {
					$aryret[$data['name']] = $data;
					$this->root->pgids[$data['name']] = $data['pgid'];
				}
			} else {
				while($data = mysql_fetch_row($res)) {
					$aryret[$this->encode($data[1]).'.txt'] = ($withtime)? $data[0]."\t".$data[1] : $data[1];
					$this->root->pgids[$data[1]] = $data[2];
				}
			}
		}
		if ($nocheck === FALSE && $base === '' && !$options) $_aryret[$this->xpwiki->pid] = $aryret;
		return $aryret;
	}

	// pginfo DB を更新
	function pginfo_db_write($page, $action, $pginfo)
	{
		// pgid
		$id = $this->get_pgid_by_name($page);
		
		if ($action !== 'delete') {
			$file = $this->get_filename($page);
			$editedtime = filemtime($file) - $this->cont['LOCALZONE'];
			$s_name = addslashes($page);
			
			foreach (array('uid', 'ucd', 'uname', 'einherit', 'vinherit', 'lastuid', 'lastucd', 'lastuname', 'reading') as $key) {
				$$key = addslashes($pginfo[$key]);
			}
			foreach (array('eaids', 'egids', 'vaids', 'vgids') as $key) {
				if ($pginfo[$key] === 'all' || $pginfo[$key] === 'none') {
					$$key = $pginfo[$key];
				} else {
					$$key = '&'.$pginfo[$key].'&';
				}
			}
		
			// ページ名読み整形
			// 英数字は半角,カタカナは全角,ひらがなはカタカナに
			if (function_exists("mb_convert_kana"))
			{
				$reading = mb_convert_kana($reading,'aKVC');
			}
			
			//最初の見出し行取得
			$title = addslashes($this->get_heading_init($page));
		}
	
		// 新規作成
		if ($action == "insert")
		{
			$buildtime = $editedtime;
			
			if ($id)
			{
				// 以前に削除したページ
				$value = "`name`='$s_name' ," .
						"`title`='$title' ," .
						"`buildtime`='$buildtime' ," .
						"`editedtime`='$editedtime' ," .
						"`uid`='$uid' ," .
						"`ucd`='$ucd' ," .
						"`uname`='$uname' ," .
						"`freeze`='0' ," .
						"`einherit`='$einherit' ," .
						"`eaids`='$eaids' ," .
						"`egids`='$egids' ," .
						"`vinherit`='$vinherit' ," .
						"`vaids`='$vaids' ," .
						"`vgids`='$vgids' ," .
						"`lastuid`='$lastuid' ," .
						"`lastucd`='$lastucd' ," .
						"`lastuname`='$lastuname' ," .
						"`update`='0' ," .
						"`reading`='$reading'";
				$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." SET $value WHERE pgid = '$id' LIMIT 1";
			}
			else
			{
				$query = "INSERT INTO ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo").
						" (`name`,`title`,`buildtime`,`editedtime`,`uid`,`ucd`,`uname`,`freeze`,`einherit`,`eaids`,`egids`,`vinherit`,`vaids`,`vgids`,`lastuid`,`lastucd`,`lastuname`,`update`,`reading`)" .
						" values('$s_name','$title','$buildtime','$editedtime','$uid','$ucd','$uname','0','$einherit','$eaids','$egids','$vinherit','$vaids','$vgids','$lastuid','$lastucd','$lastuname','0','$reading')";
			}

			$result = $this->xpwiki->db->query($query);
			$this->need_update_plaindb($page,"insert");
			
			//投稿数カウントアップ
			//if ($uid && $countup_xoops)
			//{
			//	$user =new XoopsUser($uid);
			//	$user->incrementPost();
			//}
		}

		// ページ更新 
		elseif ($action == "update")
		{
			$value = "`title`='$title' ," .
					"`editedtime`='$editedtime' ," .
					"`lastuid`='$lastuid' ," .
					"`lastucd`='$lastucd' ," .
					"`lastuname`='$lastuname'";
			if ($reading) $value .= " ,`reading`='$reading'";
			$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." SET $value WHERE pgid = '$id' LIMIT 1";
			$result = $this->xpwiki->db->query($query);
			$this->need_update_plaindb($page,"update");
		}
		
		// ページ削除
		elseif ($action == "delete")
		{
	
			$value = "editedtime=0";
			$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." SET $value WHERE pgid = '$id' LIMIT 1";
			$result = $this->xpwiki->db->query($query);
			$this->plain_db_write($page,"delete");
		}

	}
	
	// freeze情報更新
	function pginfo_freeze_db_write ($page, $freeze) {

		// pgid
		$id = $this->get_pgid_by_name($page);
		
		if ($id) {
			$value = "`freeze`='$freeze'";	
			$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." SET $value WHERE pgid = '$id' LIMIT 1";
			$result = $this->xpwiki->db->queryF($query);
		}
	}
	
	// 権限情報更新
	function pginfo_perm_db_write ($page, $pginfo) {

		// pgid
		$id = $this->get_pgid_by_name($page);
		
		if ($id) {
			foreach (array('einherit', 'vinherit') as $key) {
				$$key = addslashes($pginfo[$key]);
			}
			foreach (array('eaids', 'egids', 'vaids', 'vgids') as $key) {
				if ($pginfo[$key] === 'all' || $pginfo[$key] === 'none') {
					$$key = $pginfo[$key];
				} else {
					$$key = '&'.$pginfo[$key].'&';
				}
			}
			$value = "`einherit`='$einherit' ," .
					"`eaids`='$eaids' ," .
					"`egids`='$egids' ," .
					"`vinherit`='$vinherit' ," .
					"`vaids`='$vaids' ," .
					"`vgids`='$vgids'";		
			$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." SET $value WHERE pgid = '$id' LIMIT 1";
			$result = $this->xpwiki->db->query($query);
		}
	}
	
	// ページ名のリネーム
	function pginfo_rename_db_write ($fromname, $toname) {
		// pgid
		$id = $this->get_pgid_by_name($fromname);
		if ($id) {
			// リンク情報更新準備
			$this->plain_db_write($fromname,"delete");

			$_toname = addslashes($toname);
			$value = "`name`='$_toname'";		
			$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." SET $value WHERE pgid = '$id' LIMIT 1";
			//exit($query);
			$result = $this->xpwiki->db->query($query);
			
			// リンク情報更新
			//$this->plain_db_write($toname,"insert");
			$this->need_update_plaindb($toname,"insert");
		}
	}
	
	// plane_text DB を更新
	function plain_db_write($page, $action, $init = FALSE)
	{
		if (!$pgid = $this->get_pgid_by_name($page)) return false;
		
		//ソースを取得
		$data = join('',$this->get_source($page));
		//delete_page_info($data);
		
		//処理しないプラグインを削除
		$no_plugins = explode(',',@$this->root->noplain_plugin);
		
		$rel_pages = array();
		// ページ読みのデータページはコンバート処理しない(過負荷対策)
		if ($page != $this->root->pagereading_config_page)
		{
			$spc = array
			(
				array
				(
					'&lt;',
					'&gt;',
					'&amp;',
					'&quot;',
					'&#039;',
					'&nbsp;',
				)
				,
				array
				(
					'<',
					'>',
					'&',
					'"',
					"'",
					" ",
				)
			);
			
			$pobj = new XpWiki($this->root->mydirname);
			$pobj->init($page);
			$pobj->root->userinfo['admin'] = TRUE;
			$pobj->root->userinfo['uname_s'] = '';
			$pobj->execute();
			$data = $pobj->body;

			// remove javascript
			$data = preg_replace("#<script.+?/script>#i","",$data);

			// リンク先ページ名
			//$rel_pages = array_merge(array_keys($pobj->related), array_keys($pobj->notyets));
			$rel_pages = array_keys($pobj->related);
			$rel_pages = array_unique($rel_pages);
			
			// 未作成ページ
			if ($page != $pobj->root->whatsdeleted && $page != $pobj->cont['PLUGIN_RENAME_LOGPAGE'])
			{	
				$yetlists = array();
				$notyets = array_keys($pobj->notyets);
				
				if (file_exists($this->cont['CACHE_DIR']."yetlist.dat"))
				{
					$yetlists = unserialize(join("",file($this->cont['CACHE_DIR']."yetlist.dat")));
				}
				
				// ページ新規作成されたらリストから除外
				if ($action === 'insert') {
					if (isset($yetlists[$page])) {unset($yetlists[$page]);}
				}
				
				// とりあえず参照元リストから除去
				foreach($yetlists as $_notyet => $_pages) {
					$yetlists[$_notyet] = array_diff($_pages, array($page));
					if (!$yetlists[$_notyet]) { unset($yetlists[$_notyet]); }
				}	
				
				// 削除時以外は参照元リストに追加
				if ($action !== 'delete' && $notyets) {
					foreach($notyets as $notyet) {
						$yetlists[$notyet][] = $page;
						$yetlists[$notyet] = array_unique($yetlists[$notyet]);
					}
				}

				if ($fp = fopen($this->cont['CACHE_DIR']."yetlist.dat","wb"))
				{
					fputs($fp, serialize($yetlists));
					fclose($fp);
				}
			}
			
			unset($pobj);
			
/*
			// 付箋
			if ($fusen_enable_allpage && empty($pwm_plugin_flg['fusen']['convert']))
			{
				require_once(PLUGIN_DIR."fusen.inc.php");
				$fusen_tag = do_plugin_convert("fusen");
				$fusen_tag = str_replace(array(WIKI_NAME_DEF,WIKI_UCD_DEF,'_XOOPS_WIKI_HOST_'),array("","",XOOPS_WIKI_HOST),$fusen_tag);
				$data .= $fusen_tag;
			}
*/
			$data = preg_replace("/".preg_quote("<a href=\"{$this->root->script}?cmd=edit&amp;page=","/")."[^\"]+".preg_quote("\">{$this->root->_symbol_noexists}</a>","/")."/","",$data);
			$data = str_replace($spc[0],$spc[1],strip_tags($data)).join(',',$rel_pages);
			
			// 英数字は半角,カタカナは全角,ひらがなはカタカナに
			if (function_exists("mb_convert_kana"))
			{
				$data = mb_convert_kana($data,'aKVC');
			}
		}
		$data = addslashes(preg_replace("/[\s]+/","",$data));
		//echo $data."<hr>";
		// 新規作成
		if ($action == "insert")
		{
			$query = "INSERT INTO ".$this->xpwiki->db->prefix($this->root->mydirname."_plain")." (pgid,plain) VALUES($pgid,'$data');";
			$result=$this->xpwiki->db->queryF($query);
			//if (!$result) echo $query."<hr>";
			
			//リンク先ページ
			foreach ($rel_pages as $rel_page)
			{
				$relid = $this->get_pgid_by_name($rel_page);
				if ($pgid == $relid || !$relid) {continue;}
				$query = "INSERT INTO ".$this->xpwiki->db->prefix($this->root->mydirname."_rel")." (pgid,relid) VALUES(".$pgid.",".$relid.");";
				$result=$this->xpwiki->db->queryF($query);
				//if (!$result) echo $query."<hr>";
			}
			
			//リンク元ページ
			//global $WikiName,$autolink,$nowikiname,$search_non_list,$wiki_common_dirs;
			// $pageがAutoLinkの対象となり得る場合
			if ($this->root->autolink
				and (preg_match('/^'.$this->root->WikiName.'$/',$page) ? $this->root->nowikiname : strlen($page) >= $this->root->autolink))
			{
				// $pageを参照していそうなページに一気に追加
				$this->root->search_non_list = 1;
				
				$lookup_page = $page;
				/*
				// 検索ページ名の共通リンクディレクトリを省略
				if (count($this->root->wiki_common_dirs))
				{
					foreach($this->root->wiki_common_dirs as $wiki_common_dir)
					{
						if (strpos($lookup_page,$wiki_common_dir) === 0)
						{
							$lookup_page = str_replace($wiki_common_dir,"",$lookup_page);
							if ($this->root->autolink > strlen($lookup_page)){$lookup_page = $page;}
							break;
						}
					}
				}
				*/
				// 検索実行
				$pages = $this->do_search($lookup_page,'AND',TRUE);
				
				foreach ($pages as $_page)
				{
					$refid = $this->get_pgid_by_name($_page);
					if ($pgid == $refid || !$refid) {continue;}
					$query = "INSERT INTO ".$this->xpwiki->db->prefix($this->root->mydirname."_rel")." (pgid,relid) VALUES(".$refid.",".$pgid.");";
					$result=$this->xpwiki->db->queryF($query);
					// PlainテキストDB 更新予約を設定
					//$this->need_update_plaindb($_page);
					// 相手先ページも更新
					$this->plain_db_write($_page, 'update');
					// ページHTMLキャッシュを削除
					$this->clear_page_cache($_page);
				}
			}
		}
		
		// ページ更新
		elseif ($action == "update")
		{
			$value = "plain='$data'";
			$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_plain")." SET $value WHERE pgid = $pgid;";
			$result=$this->xpwiki->db->queryF($query);
			//if (!$result) echo $query."<hr>";
			
			//リンク先ページ
			$query = "DELETE FROM ".$this->xpwiki->db->prefix($this->root->mydirname."_rel")." WHERE pgid = ".$pgid.";";
			$result=$this->xpwiki->db->queryF($query);
			//if (!$result) echo $query."<hr>";
			foreach ($rel_pages as $rel_page)
			{
				$relid = $this->get_pgid_by_name($rel_page);
				if ($pgid == $relid || !$relid) {continue;}
				$query = "INSERT INTO ".$this->xpwiki->db->prefix($this->root->mydirname."_rel")." (pgid,relid) VALUES(".$pgid.",".$relid.");";
				$result=$this->xpwiki->db->queryF($query);
				//if (!$result) echo $query."<hr>";
			}
		}
		
		// ページ削除
		elseif ($action == "delete")
		{
			$query = "DELETE FROM ".$this->xpwiki->db->prefix($this->root->mydirname."_plain")." WHERE pgid = $pgid;";
			$result=$this->xpwiki->db->queryF($query);
			//if (!$result) echo $query."<hr>";
			
			//リンクページ
			$query = "DELETE FROM ".$this->xpwiki->db->prefix($this->root->mydirname."_rel")." WHERE pgid = ".$pgid." OR relid = ".$pgid.";";
			$result=$this->xpwiki->db->queryF($query);
		}
		else
			return false;
		
		return true;
	}

	// attach DB を更新
	function attach_db_write($data,$action)
	{
		$ret = TRUE;
		
		//if (!$pgid = $data['pgid']) return false;
		
		$id = (int)@$data['id'];
		$pgid = (int)@$data['pgid'];
		$name = @$data['name'];
		$type = @$data['type'];
		$mtime = (int)@$data['mtime'];
		$size = (int)@$data['size'];
		// $mode normal=0, isbn=1, thumb=2
		$mode = (preg_match("/^ISBN.*\.(dat|jpg)/",$name))? 1 : ((preg_match("/^\d\d?%/",$name))? 2 : 0);
		$age = (int)@$data['status']['age'];
		$count = (int)@$data['status']['count'][$age];
		$pass = @$data['status']['pass'];
		$freeze = (int)@$data['status']['freeze'];
		$owner = @$data['status']['owner'];
		$copyright = (int)@$data['status']['copyright'];

		// 新規作成
		if ($action == "insert")
		{
			$query = "INSERT INTO ".$this->xpwiki->db->prefix($this->root->mydirname."_attach")." (pgid,name,type,mtime,size,mode,count,age,pass,freeze,copyright,owner) VALUES($pgid,'$name','$type',$mtime,$size,'$mode',$count,$age,'$pass',$freeze,$copyright,$owner);";
			$result=$this->xpwiki->db->queryF($query);
			//if (!$result) echo $query."<hr>";
		}
		
		// 更新
		elseif ($action == "update")
		{
			$value = "pgid=$pgid"
			.",name='$name'"
			.",type='$type'"
			.",mtime=$mtime"
			.",size=$size"
			.",mode=$mode"
			.",count=$count"
			.",age=$age"
			.",pass='$pass'"
			.",freeze=$freeze"
			.",copyright=$copyright"
			.",owner=$owner";
			$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_attach")." SET $value WHERE `id`='$id' LIMIT 1";
			$result=$this->xpwiki->db->queryF($query);
			//if (!$result) echo $query."<hr>";
		}
		
		// ファイル削除
		elseif ($action == "delete")
		{
			$q_name = ($name)? " AND name='{$name}' LIMIT 1" : "";
			
			$ret = array();
			$query = "SELECT name FROM ".$this->xpwiki->db->prefix($this->root->mydirname."_attach")." WHERE pgid = {$pgid}{$q_name};";
			if ($result=$this->xpwiki->db->query($query))
			{
				while($data = mysql_fetch_row($result))
				{
					$ret[] = $data[0];
				}
			}
			if (!$ret) $ret = TRUE;
			
			$query = "DELETE FROM ".$this->xpwiki->db->prefix($this->root->mydirname."_attach")." WHERE pgid = {$pgid}{$q_name};";
			
			$result=$this->xpwiki->db->queryF($query);
			//if (!$result) echo $query."<hr>";
		}
		else
			return false;
		
		return $ret;
	}

	function get_attachfile_id ($page, $name, $age) {
		$data = 0;
		$pgid = $this->get_pgid_by_name($page);
		$name = addslashes($name);
		$query = "SELECT `id` FROM ".$this->xpwiki->db->prefix($this->root->mydirname."_attach")." WHERE `pgid`='{$pgid}' AND `name`='{$name}' AND age='{$age}' LIMIT 1";
		if ($result=$this->xpwiki->db->query($query)) {
			$data = mysql_fetch_row($result);
			$data = $data[0];
		}
		return $data;
	}

	// プラグインからplane_text DB を更新を指示(コンバート時)
	function need_update_plaindb($page = null, $mode = 'update')
	{
		if (is_null($page)) $page = $this->root->vars['page'];
		
		if ($this->is_page($page))
		{
			// ランチャーファイル作成
			$filename = $this->cont['CACHE_DIR'].$this->encode($page).".udp";
			if ($fp = fopen($filename, 'wb')) {
				fwrite($fp, $mode);
				fclose($fp);
			}
		}
		return;
	}
	
	// データベースから関連ページを得る
	function links_get_related_db($page)
	{
		static $links = array();
		
		if (isset($links[$this->xpwiki->pid][$page])) {return $links[$this->xpwiki->pid][$page];}
		$links[$this->xpwiki->pid][$page] = array();
		
		$where = "`relid` = ".$this->get_pgid_by_name($page)." AND p.pgid = r.pgid";
		$r_where = $this->get_readable_where('p.');
		if ($r_where) {
			$where = "($where AND ($r_where))";
		}
		$where = " WHERE " . $where;
		
		$query = "SELECT p.name, p.editedtime FROM `".$this->xpwiki->db->prefix($this->root->mydirname."_rel")."` AS r, `".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")."` AS p ".$where;
		$result = $this->xpwiki->db->query($query);
		//echo $query;
		
		if ($result)
		{
			while(list($name,$time) = mysql_fetch_row($result))
			{
				$links[$this->xpwiki->pid][$page][$name] = $time;
			}
		}
		
		return $links[$this->xpwiki->pid][$page];
	}
	
	// 閲覧権限チェック用 WHERE句取得
	function get_readable_where ($table = '', $is_admin = NULL, $uid = NULL) {
		static $where = array();
		
		if (is_null($is_admin)) $is_admin = $this->root->userinfo['admin'];
		if (is_null($uid)) $uid = $this->root->userinfo['uid'];
		
		$key = ($is_admin)? ("-1".$table) : ("$uid".$table);
		
		if (!isset($where[$this->xpwiki->pid][$key]))
		{
			if ($is_admin)
				$where[$this->xpwiki->pid][$key] = '';
			else
			{
				$_where = "";
				if ($uid) $_where .= " ({$table}`uid` = '$uid') OR";
				$_where .= " ({$table}`vaids` = 'all')";
				if ($uid) $_where .= " OR ({$table}`vaids` LIKE '%&{$uid}&%')";
				foreach($this->get_mygroups($uid) as $gid)
				{
					$_where .= " OR ({$table}`vgids` LIKE '%&{$gid}&%')";
				}
				$where[$this->xpwiki->pid][$key] = $_where.' ';
			}
		}
		return $where[$this->xpwiki->pid][$key];
	}

	// plain Text を取得する
	function get_plain_text_db ($page) {
		
		$pgid = $this->get_pgid_by_name($page);
		
		$query = 'SELECT `plain` FROM `'.$this->xpwiki->db->prefix($this->root->mydirname."_plain").'` WHERE `pgid` = \''.$pgid.'\' LIMIT 1';		
		$result = $this->xpwiki->db->query($query);
		
		$text = '';
		if ($result)
		{
			list($text) = mysql_fetch_row($result);
		}
		return $text;
	}

	// ページの最終更新時間を更新
	function touch_db($page)
	{
		if ($id = $this->get_pgid_by_name($page))
		{
			clearstatcache();
			$editedtime = filemtime($this->cont['DATA_DIR'].$this->encode($page).".txt");
			$value = "`editedtime` = '$editedtime' ,".
					"`lastuid`='{$this->root->userinfo['uid']}' ," .
					"`lastucd`='{$this->root->userinfo['ucd']}' ," .
					"`lastuname`='{$this->root->cookie['name']}'";
			$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." SET $value WHERE `pgid`='$id'";
			$result = $this->xpwiki->db->queryF($query);
		}
	}
	
	// 'Search' main function (DB版)
	function do_search($word, $type = 'AND', $non_format = FALSE, $base = '', $db = FALSE, $limit = 0, $offset = 0 , $userid = 0)
	{
		if (!$db) return parent::do_search($word, $type, $non_format, $base);
	}

	// ページ読みを取得
	function get_page_reading ($page) {
		// 無効になっている
		if (! $this->root->pagereading_enable) return '';
		
		$reading = '';
		$pgid = $this->get_pgid_by_name($page);
		
		if ($pgid) {
			$query = 'SELECT `reading` FROM `'.$this->xpwiki->db->prefix($this->root->mydirname."_pginfo").'` WHERE `pgid` = \''.$pgid.'\' LIMIT 1';		
			$result = $this->xpwiki->db->query($query);
	
			if ($result)
			{
				list($reading) = mysql_fetch_row($result);
			}
		}
		
		if ($reading) return $reading;
				
		// Execute ChaSen/KAKASI, and get annotation
		switch(strtolower($this->root->pagereading_kanji2kana_converter)) {
		case 'chasen':
			if(! file_exists($this->root->pagereading_chasen_path))
				$this->die_message('ChaSen not found: ' . $this->root->pagereading_chasen_path);

			$tmpfname = tempnam(realpath($this->cont['CACHE_DIR']), 'PageReading');
			$fp = fopen($tmpfname, 'w') or
				$this->die_message('Cannot write temporary file "' . $tmpfname . '".' . "\n");
			fputs($fp, mb_convert_encoding($page . "\n",
					$this->root->pagereading_kanji2kana_encoding, $this->cont['SOURCE_ENCODING']));
			fclose($fp);

			$chasen = "{$this->root->pagereading_chasen_path} -F %y $tmpfname";
			$fp     = popen($chasen, 'r');
			if($fp === FALSE) {
				unlink($tmpfname);
				$this->die_message('ChaSen execution failed: ' . $chasen);
			}
			$line = fgets($fp);
			$line = mb_convert_encoding($line, $this->cont['SOURCE_ENCODING'],
				$this->root->pagereading_kanji2kana_encoding);
			$line = chop($line);
			$reading = $line;
			pclose($fp);

			unlink($tmpfname) or
				$this->die_message('Temporary file can not be removed: ' . $tmpfname);
			break;

		case 'kakasi':	/*FALLTHROUGH*/
		case 'kakashi':
			if(! file_exists($this->root->pagereading_kakasi_path))
				$this->die_message('KAKASI not found: ' . $this->root->pagereading_kakasi_path);

			$tmpfname = tempnam(realpath($this->cont['CACHE_DIR']), 'PageReading');
			$fp       = fopen($tmpfname, 'w') or
				$this->die_message('Cannot write temporary file "' . $tmpfname . '".' . "\n");
			fputs($fp, mb_convert_encoding($page . "\n",
				$this->root->pagereading_kanji2kana_encoding, $this->cont['SOURCE_ENCODING']));
			fclose($fp);

			$kakasi = "{$this->root->pagereading_kakasi_path} -kK -HK -JK < $tmpfname";
			$fp     = popen($kakasi, 'r');
			if($fp === FALSE) {
				unlink($tmpfname);
				$this->die_message('KAKASI execution failed: ' . $kakasi);
			}

			$line = fgets($fp);
			$line = mb_convert_encoding($line, $this->cont['SOURCE_ENCODING'],
				$this->root->pagereading_kanji2kana_encoding);
			$line = chop($line);
			$reading = $line;
			pclose($fp);

			unlink($tmpfname) or
				$this->die_message('Temporary file can not be removed: ' . $tmpfname);
			break;

		case 'none':
			$patterns = $replacements = $matches = array();
			foreach ($this->get_source($this->root->pagereading_config_dict) as $line) {
				$line = chop($line);
				if(preg_match('|^ /([^/]+)/,\s*(.+)$|', $line, $matches)) {
					$patterns[]     = $matches[1];
					$replacements[] = $matches[2];
				}
			}
			$reading = $page;
			foreach ($patterns as $no => $pattern)
				$reading = mb_convert_kana(mb_ereg_replace($pattern,
					$replacements[$no], $reading), 'aKCV');
			break;

		default:
			$this->die_message('Unknown kanji-kana converter: ' . $this->root->pagereading_kanji2kana_converter . '.');
			break;
		}

		$page_r = array();
		foreach(explode('/', $reading) as $_reading) {
			$page_r[] = mb_substr($_reading, 0, 1);
		}
		$reading = join('/', $page_r);
		
		// DBに保存
		if ($pgid) {
			$value = "`reading` = '".addslashes($reading)."'";
			$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." SET $value WHERE `pgid`='$pgid'";
			$result = $this->xpwiki->db->queryF($query);
		}
		
		return $reading;
	}

}
?>