<?php
class xpwiki_plugin_norelated extends xpwiki_plugin {
	function plugin_norelated_init () {



	}
	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: norelated.inc.php,v 1.1 2006/10/13 13:17:49 nao-pon Exp $
	//
	// norelated plugin
	// - Stop showing related link automatically if $related_link = 1
	
	function plugin_norelated_convert()
	{
	//	global $related_link;
		$this->root->related_link = 0;
		return '';
	}
}
?>