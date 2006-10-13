<?php
//
// Created on 2006/10/05 by nao-pon http://hypweb.net/
// $Id: plugin.php,v 1.1 2006/10/13 13:17:49 nao-pon Exp $
//


class xpwiki_plugin {
	var $xpwiki;
	var $root;
	var $const;
	var $func;
	
	function xpwiki_plugin (&$func) {

		$this->xpwiki = & $func->xpwiki;
		$this->root   = & $func->root;
		$this->cont  = & $func->cont;
		$this->func   = & $func;
	}
}

?>