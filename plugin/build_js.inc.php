<?php
/*
 * Created on 2008/10/09 by nao-pon http://hypweb.net/
 * License: GPL v2 or (at your option) any later version
 * $Id: build_js.inc.php,v 1.1 2008/10/09 08:19:20 nao-pon Exp $
 */

class xpwiki_plugin_build_js extends xpwiki_plugin {
	function plugin_build_js_init() {
	
	}
	
	function plugin_build_js_inline() {
		$args = func_get_args();
		$action = $args[0];
		switch ($action) {
		case 'refInsert':
			if (empty($args[1])) {
				return false;
			}
			if (empty($args[2])) {
				$args[2] = '';
			} else {
				list($args[2]) = explode('/', $args[2]);
			}
			return '<a href="#" onclick="return parent.XpWiki.refInsert(\''.htmlspecialchars($args[1], ENT_QUOTES).'\',\''.$args[2].'\')">'.$this->root->_attach_messages['msg_insert'].'</a>';
			break;
		default :
			return false;
		}
	}
}
?>