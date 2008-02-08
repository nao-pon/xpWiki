<?php
/*
 * Created on 2008/02/07 by nao-pon http://hypweb.net/
 * $Id: ajaxtree.inc.php,v 1.1 2008/02/08 03:04:24 nao-pon Exp $
 */

function xpwiki_onPageWriteAfter_ajaxtree (&$xpwiki_func, &$page, &$postdata, &$notimestamp, &$mode, &$diffdata) {

	// This block always execute.
	
	// Get plugin instance
	$plugin = & $xpwiki_func->get_plugin_instance('ajaxtree');
	
	$plugin->plugin_ajaxtree_write_after();

}
?>