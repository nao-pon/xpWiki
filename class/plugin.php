<?php
//
// Created on 2006/10/05 by nao-pon http://hypweb.net/
// $Id: plugin.php,v 1.7 2008/03/06 23:49:15 nao-pon Exp $
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
	}
	
	// 言語ファイルの読み込み
	function load_language () {
		$uilang = $this->cont['UI_LANG'] . $this->cont['FILE_ENCORD_EXT'];
		$lang = $this->root->mytrustdirpath.'/language/xpwiki/' . $uilang . '/plugin/'.$this->name.'.lng.php';

		if (!file_exists($lang)) {
			$uilang = 'en';
			$lang = $this->root->mytrustdirpath.'/language/xpwiki/en/plugin/'.$this->name.'.lng.php';
		}
		if (file_exists($lang)) {
			include ($lang);
			$this->msg = $msg;
		}
		// html側にファイルがあれば上書き
		$lang = $this->root->mydirpath.'/language/xpwiki/' . $uilang . '/plugin/'.$this->name.'.lng.php';
		if (file_exists($lang)) {
			include ($lang);
			$this->msg = array_merge($this->msg, $msg);
		}
	}
	
	// プラグインオプションの解析
	function fetch_options (&$options, $args, $keys=array(), $other_key='_args', $sep='(?:=|:)') {
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
				$arg = trim($arg);
				if ($done) {
					$options[$arg] = $arg;
				} else {
					if (preg_match('/(.+)' . $sep . '(.*)/s', $arg, $match)) {
						$match[1] = trim(@ $match[1]);
						$match[2] = trim(@ $match[2]);
						if (isset($options[$match[1]])) {
							$options[$match[1]] = ($match[2])? $match[2] : null;
							continue;
						}
					}
					if (!isset($options[$arg])) {
						if ($done_check) {
							$done = $options['_done'] = TRUE;
						}
						$options[$other_key][] = $arg;
					}
					$options[$arg] = $arg;
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
}

?>