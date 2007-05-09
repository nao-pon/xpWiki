<?php
//
// Created on 2006/10/05 by nao-pon http://hypweb.net/
// $Id: plugin.php,v 1.3 2007/05/09 12:08:37 nao-pon Exp $
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
		$lang = $this->root->mytrustdirpath.'/lang/plugin/'.$this->name.'.'.$uilang.'.php';

		if (!file_exists($lang)) {
			$uilang = 'en';
			$lang = $this->root->mytrustdirpath.'/lang/plugin/'.$this->name.'.'.$uilang.'.php';
		}
		if (file_exists($lang)) {
			include ($lang);
			$this->msg = $msg;
		}
		// html側にファイルがあれば上書き
		$lang = $this->root->mydirpath.'/praivate/lang/plugin/'.$this->name.'.'.$uilang.'.php';
		if (file_exists($lang)) {
			include ($lang);
			$this->msg = array_merge($this->msg, $msg);
		}
	}
}

?>