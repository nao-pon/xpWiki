<?php
/*
 * Created on 2008/12/06 by nao-pon http://hypweb.net/
 * License: GPL v2 or (at your option) any later version
 * $Id: sub.inc.php,v 1.1 2008/12/08 23:39:54 nao-pon Exp $
 */

class xpwiki_plugin_sub extends xpwiki_plugin {
	function plugin_sub_inline() {
		if (func_num_args() !== 1) {
			return FALSE;
		}
		
		list($body) = func_get_args();
		
		if ($body == ''){
			return FALSE;
		}
	
		return '<sub>' . $body . '</sub>';
	}	
}
