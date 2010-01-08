<?php
class xpwiki_plugin_rss10 extends xpwiki_plugin {
	function plugin_rss10_init () {



	}
	// RSS 1.0 plugin - had been merged into rss plugin
	// $Id: rss10.inc.php,v 1.2 2010/01/08 13:34:33 nao-pon Exp $

	function plugin_rss10_action()
	{
		while( ob_get_level() ) {
			ob_end_clean() ;
		}
		$this->func->pkwk_headers_sent();
		header('Status: 301 Moved Permanently');
		header('Location: ' . $this->func->get_script_uri() . '?cmd=rss&ver=1.0'); // HTTP
		exit;
	}
}
?>