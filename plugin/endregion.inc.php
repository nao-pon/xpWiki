<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: endregion.inc.php,v 1.1 2007/01/12 00:43:55 nao-pon Exp $
//
class xpwiki_plugin_endregion extends xpwiki_plugin {
	function plugin_endregion_init () {

	}
	
	function plugin_endregion_convert()
	{
		return <<<EOD
</td></tr></table>
EOD;
	}
}
?>