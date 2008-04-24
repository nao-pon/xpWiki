<?php
/*
 * Created on 2008/04/24 by nao-pon http://hypweb.net/
 * $Id: nocontents.inc.php,v 1.1 2008/04/24 00:22:00 nao-pon Exp $
 */
class xpwiki_plugin_nocontents extends xpwiki_plugin {
	function plugin_nocontents_convert() {
		$this->root->rtf['contents_converted'] = TRUE;
		return '';
	}
}
?>