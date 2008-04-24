<?php
class xpwiki_plugin_contents extends xpwiki_plugin {
	function plugin_contents_init () {



	}
	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: contents.inc.php,v 1.2 2008/04/24 00:22:00 nao-pon Exp $
	//
	
	function plugin_contents_convert()
	{
		$this->root->rtf['contents_converted'] = TRUE;
		// This character string is substituted later.
		return '<#_contents_>';
	}
}
?>