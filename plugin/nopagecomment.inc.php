<?php
//
// Created on 2006/12/07 by nao-pon http://hypweb.net/
// $Id: nopagecomment.inc.php,v 1.1 2006/12/07 01:34:39 nao-pon Exp $
//
class xpwiki_plugin_nopagecomment extends xpwiki_plugin {
	function plugin_nopagecomment_convert()
	{
		$this->root->allow_pagecomment = FALSE;
		return '';
	}
}
?>