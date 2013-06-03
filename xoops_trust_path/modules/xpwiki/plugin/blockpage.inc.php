<?php
class xpwiki_plugin_blockpage extends xpwiki_plugin {
	function plugin_blockpage_init () {

	}
	
	function plugin_blockpage_convert() {
		$args = func_get_args();
		if (! empty($args[0]) && strlen($args[0]) > 255 && isset($args[1])) {
			$page = $this->func->get_fullname($args[1], $this->root->vars['page']);
			if (! $this->func->is_page($page)) {
				return '#blockpage(ID,PAGENAME): No such page: ' . $this->func->make_pagelink($page);
			} else {
				$GLOBALS['Xpwiki_'.$this->root->mydirname]['cache']['blockpage'][$args[0]] = $page;
			}
		} else {
			return '#blockpage(ID,PAGENAME): Invalid ID or PageName.'
		}
		return '';
	}
}
