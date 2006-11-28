<?php
//
// Created on 2006/11/28 by nao-pon http://hypweb.net/
// $Id: xpwikiver.inc.php,v 1.1 2006/11/28 08:19:25 nao-pon Exp $
//
class xpwiki_plugin_xpwikiver extends xpwiki_plugin {
	function plugin_xpwikiver_inline()
	{
		return $this->cont['S_VERSION'];
	}
}
?>