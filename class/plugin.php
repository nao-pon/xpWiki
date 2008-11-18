<?php
//
// Created on 2006/10/05 by nao-pon http://hypweb.net/
// $Id: plugin.php,v 1.16 2008/11/18 07:46:08 nao-pon Exp $
//


class xpwiki_plugin {
	var $xpwiki;
	var $root;
	var $const;
	var $func;
	
	var $name;
	var $msg;
	
	function xpwiki_plugin (&$func) {

		$this->xpwiki = & $func->xpwiki;
		$this->root   = & $func->root;
		$this->cont   = & $func->cont;
		$this->func   = & $func;
		
		$this->language_loaded = FALSE;
	}
	
	// 言語ファイルの読み込み
	function load_language ($setlang = '') {
		if (! $this->language_loaded) {
			$this->language_loaded = TRUE;
			$this->msg = array();
			
			$uilang = ($setlang? $setlang : $this->cont['UI_LANG']) . $this->cont['FILE_ENCORD_EXT'];
			
			$isOfficial = in_array($uilang, $this->cont['OFFICIAL_LANGS']);
			if (! $isOfficial) {
				// Load base language file.
				include ($this->root->mytrustdirpath.'/language/xpwiki/en/plugin/'.$this->name.'.lng.php');
				$this->msg = $msg;
			}
			
			$lang = $this->root->mytrustdirpath.'/language/xpwiki/' . $uilang . '/plugin/'.$this->name.'.lng.php';
			if (file_exists($lang)) {
				include ($lang);
				$this->msg = array_merge($this->msg, $msg);
			} else {
				if ($isOfficial && $uilang !== 'en') {
					$uilang = 'en';
					$lang = $this->root->mytrustdirpath.'/language/xpwiki/en/plugin/'.$this->name.'.lng.php';
					include ($lang);
					$this->msg = array_merge($this->msg, $msg);
				}
			}
	
			// html側にファイルがあれば上書き
			$lang = $this->root->mydirpath.'/language/xpwiki/' . $uilang . '/plugin/'.$this->name.'.lng.php';
			if (file_exists($lang)) {
				include ($lang);
				$this->msg = array_merge($this->msg, $msg);
			}
		}
	}
	
	// プラグインオプションの解析
	function fetch_options (& $options, $args, $keys = array(), $other_key = '_args', $sep = '(?:=|:)') {
		if ($keys) {
			$args = array_pad($args, count($keys), null);
			foreach($keys as $key) {
				$options[$key] = array_shift($args);
			}
		}
		if ($args) {
			$done = FALSE;
			$done_check = isset($options['_done']);
			foreach($args as $arg) {
				if ($done) {
					if ($arg) $options[$other_key][] = $arg;
				} else {
					list($key, $val) = array_pad(preg_split('/' . $sep . '/', $arg, 2), 2, NULL);
					if (! is_null($val) && isset($options[$key])) {
						$options[$key] = ($val === '')? NULL : $val;
						continue;
					}
					if (! isset($options[$arg])) {
						if ($done_check) {
							$done = $options['_done'] = TRUE;
						}
						if ($arg) $options[$other_key][] = $arg;
					} else {
						$options[$arg] = TRUE;
					}
				}
			}
		}
	}
	
	function action_msg_admin_only () {
		return array(
			'msg'  => 'Admin\'s area',
			'body' => 'Here is an area only for the administer.'
		);	
	}

	function action_msg_owner_only () {
		return array(
			'msg'  => 'Owner\'s area',
			'body' => 'Here is an area only for this page owner.'
		);	
	}
	
	function get_domid ($name, $withDirname = false) {
		static $count = array();
		$pgid = $this->func->get_pgid_by_name($this->root->vars['page']);
		$plugin = substr(get_class($this), 14);
		if (! isset($count[$this->root->mydirname][$pgid][$plugin][$name])) {
			$count[$this->root->mydirname][$pgid][$plugin][$name] = 0;
		}
		$count[$this->root->mydirname][$pgid][$plugin][$name]++;
		$dirname = $withDirname? $this->root->mydirname . ':' : '';
		return $dirname . $this->root->mydirname .'_' . $plugin . '_' . $name . '_' . $pgid . '_' . $count[$this->root->mydirname][$pgid][$plugin][$name];
	}
}

?>