<?php
/*
 * Created on 2008/10/09 by nao-pon http://hypweb.net/
 * License: GPL v2 or (at your option) any later version
 * $Id: build_js.inc.php,v 1.4 2008/11/24 02:07:40 nao-pon Exp $
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
			if ($this->root->vars['refer'] !== $this->root->vars['base']) {
				$args[1] = $this->root->vars['refer'] . '/' . $args[1];
			}
			$obj = (empty($_GET['winop']))? 'parent' : 'opener.window';
			if (! empty($_GET['mode']) && $_GET['mode'] === 'fck') {
				$jsfunc = $obj . '.XpWiki.FCKrefInsert';
			} else {
				$jsfunc = $obj . '.XpWiki.refInsert';
			}
			return '<a href="#" onclick="return ' . $jsfunc . '(\''.htmlspecialchars($args[1], ENT_QUOTES).'\',\''.$args[2].'\')">'.$this->root->_attach_messages['msg_insert'].'</a>';
			break;
		default :
			return false;
		}
	}
}
?>