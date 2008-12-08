<?php
/*
 * Created on 2008/12/06 by nao-pon http://hypweb.net/
 * License: GPL v2 or (at your option) any later version
 * $Id: sup.inc.php,v 1.1 2008/12/08 23:40:16 nao-pon Exp $
 */

class xpwiki_plugin_sup extends xpwiki_plugin {
	function plugin_sup_inline() {
		if (func_num_args() !== 1) {
			return FALSE;
		}
		
		list($body) = func_get_args();
		
		if ($body == ''){
			return FALSE;
		}
	
		return '<sup>' . $body . '</sup>';
	}	

}
