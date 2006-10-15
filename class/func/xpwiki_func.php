<?php
//
// Created on 2006/10/02 by nao-pon http://hypweb.net/
// $Id: xpwiki_func.php,v 1.4 2006/10/15 10:47:05 nao-pon Exp $
//
class XpWikiFunc extends XpWikiXoopsWrapper {

	// xpWiki functions.
	// These are functions that need not be overwrited. 
	
	var $xpwiki;
	var $root;
	var $const;
	var $pid;
	
	function XpWikiFunc (& $xpwiki) {
		$this->xpwiki = & $xpwiki;
		$this->root  = & $xpwiki->root;
		$this->cont = & $xpwiki->cont;
		$this->pid = $xpwiki->pid;
	}

	function init() {

		include(dirname(dirname(__FILE__))."/include/init.php");
	}
	
	function & get_plugin_instance ($name) {
		static $instance = array();
		
		if (!isset($instance[$this->xpwiki->pid][$name])) {
			if ($class = $this->exist_plugin($name)) {
				$instance[$this->xpwiki->pid][$name] = new $class($this);
				$this->do_plugin_init($name);
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
	function set_plugin_messages($messages)
		{
			foreach ($messages as $name=>$val)
				if (! isset($this->root->$name))
					$this->root->$name = $val;
		}
	
	// Check plugin '$name' is here
	function exist_plugin($name)
		{
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
	function do_plugin_init($name)
		{
			static $checked = array();
	
		if (isset($checked[$name])) return $checked[$name];
		
		$plugin = & $this->get_plugin_instance($name);
		$func = 'plugin_' . $name . '_init';
			if (method_exists($plugin, $func)) {
			// TRUE or FALSE or NULL (return nothing)
			$checked[$name] = call_user_func(array(& $plugin, $func));
			} else {
				$checked[$name] = NULL; // Not exist
			}
	
		return $checked[$name];
		}
	
	// Call API 'action' of the plugin
	function do_plugin_action($name)
		{
			if (! $this->exist_plugin_action($name)) return array();
	
			if($this->do_plugin_init($name) === FALSE)
					$this->die_message('Plugin init failed: ' . $name);
		
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
	function do_plugin_convert($name, $args = '')
		{
			//	global $digest;
		
			if($this->do_plugin_init($name) === FALSE)
					return '[Plugin init failed: ' . $name . ']';
		
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
	function do_plugin_inline($name, $args, & $body)
		{
			//	global $digest;
		
			if($this->do_plugin_init($name) === FALSE)
					return '[Plugin init failed: ' . $name . ']';
		
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
		if (!empty($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
		{
			if (preg_match_all("/([\w]+)/i",$_SERVER["HTTP_ACCEPT_LANGUAGE"],$match,PREG_PATTERN_ORDER)) {
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
		if (empty($this->root->userinfo['uname'])) {
			$this->root->userinfo['uname'] = $this->root->cookie['name'];
		}
	}
	
	function save_cookie () {
		$data = $this->root->cookie['ucd'].
			"\t" . $this->root->cookie['name'];
		$url = parse_url ( $this->cont['ROOT_URL'] );
		setcookie($this->root->mydirname, $data, time()+86400*365, $url['path']); // 1年間
	}
	
	function set_user_code () {
		
		// cookieの読み込み
		$this->load_cookie();
		
		//user-codeの発行
		if(!$this->root->cookie['ucd']){
			$this->root->cookie['ucd'] = md5(getenv("REMOTE_ADDR"). __FILE__ .gmdate("Ymd", time()+9*60*60));
		}
		$this->ucd = substr(crypt($this->root->cookie['ucd'],($this->root->adminpass)? $this->root->adminpass : 'id'),-11);

		// cookieを更新
		$this->save_cookie();
	}
}
?>