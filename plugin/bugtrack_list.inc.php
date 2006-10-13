<?php
class xpwiki_plugin_bugtrack_list extends xpwiki_plugin {
	
	function plugin_bugtrack_list_init()
	{

	// $Id: bugtrack_list.inc.php,v 1.1 2006/10/13 13:17:49 nao-pon Exp $
	//
	// PukiWiki BugTrack-list plugin - A part of BugTrack plugin
	//
	// Copyright
	// 2002-2005 PukiWiki Developers Team
	// 2002 Y.MASUI GPL2 http://masui.net/pukiwiki/ masui@masui.net
	
		require_once($this->cont['PLUGIN_DIR'] . 'bugtrack.inc.php');

		$this->func->plugin_bugtrack_init();
	}
}
?>