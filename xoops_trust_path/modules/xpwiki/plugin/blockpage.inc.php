<?php
class xpwiki_plugin_blockpage extends xpwiki_plugin {
	function plugin_blockpage_init () {

	}

	function plugin_blockpage_convert() {
		$options = array();
		$args = func_get_args();
		$this->fetch_options($options, $args, array('id', 'page'));
		if ( $options['id'] !== '' && strlen($options['id']) < 256 && $options['page'] !== '') {
			$page = $this->func->get_fullname($options['page'], $this->root->vars['page']);
			if (! $this->func->is_page($page)) {
				return '#blockpage(ID,PAGENAME): No such page: ' . $this->func->make_pagelink($page);
			} else {
				$GLOBALS['Xpwiki_'.$this->root->mydirname]['cache']['blockpage'][$options['id']] = $page;
			}
		} else {
			return '#blockpage(ID,PAGENAME): Invalid ID or PageName.';
		}
		return '';
	}
}

