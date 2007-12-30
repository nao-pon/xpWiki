<?php
//
// $Id: renderattach.inc.php,v 1.1 2007/12/30 02:41:34 nao-pon Exp $
//
class xpwiki_plugin_renderattach extends xpwiki_plugin {
	function plugin_renderattach_init () {
	}
	
	function plugin_renderattach_convert()
	{
		$args = func_get_args();
		$page = @ $args[0];
		if ($this->func->is_page($this->root->render_attach . '/' . $page)) {
			$this->root->render_attach .= '/' . $page;
		}
		return '';
	}
}
?>