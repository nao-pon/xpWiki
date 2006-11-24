<?php
//
// Created on 2006/11/24 by nao-pon http://hypweb.net/
// $Id: noattach.inc.php,v 1.1 2006/11/24 13:47:07 nao-pon Exp $
//
class xpwiki_plugin_noattach extends xpwiki_plugin {
	function plugin_noattach_convert()
	{
		$attach =& $this->func->get_plugin_instance('attach');
		$attach->listed = TRUE;	
		return '';
	}
}
?>