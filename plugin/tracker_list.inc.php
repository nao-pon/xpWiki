<?php
class xpwiki_plugin_tracker_list extends xpwiki_plugin {
	
	function plugin_tracker_list_init()
	{

	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: tracker_list.inc.php,v 1.1 2006/10/13 13:17:49 nao-pon Exp $
	//
	// Issue tracker list plugin (a part of tracker plugin)
	
		require_once($this->cont['PLUGIN_DIR'] . 'tracker.inc.php');

		if (function_exists('plugin_tracker_init'))
			$this->func->plugin_tracker_init();
	}
}
?>