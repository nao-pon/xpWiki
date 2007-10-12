<?php
/*
 * Created on 2007/10/05 by nao-pon http://hypweb.net/
 * $Id: pagepopup.inc.php,v 1.1 2007/10/12 08:07:08 nao-pon Exp $
 */

class xpwiki_plugin_pagepopup extends xpwiki_plugin {
	function plugin_pagepopup_init () {
		$this->positions = array(
			'top'    => '',
			'bottom' => '',
			'left'   => '',
			'right'  => '',
			'width'  => '',
			'height' => ''
		);
	}
	
	function plugin_pagepopup_inline()
	{
		$op = func_get_args();
		
		$page = $op[0];
		
		if ($this->func->is_page($page) || isset($this->root->page_aliases[$page])) {
			$options['popup']['use'] = 1;
			$options['popup']['position'] = '';
			foreach(array('top', 'left', 'bottom', 'right', 'width', 'height') as $_prm) {
				if (isset($this->positions[$_prm])) {
					if (preg_match('/^(\d+)(%|px)?/', $this->positions[$_prm], $_match)) {
					 	if (empty($_match[2])) $_match[2] = 'px';
					 	$options['popup']['position'] .= ',' . $_prm . ':\'' . $_match[1] . $_match[2] . '\'';
					}
				}
			}
		} else {
			$options = array();
		}
		
		return $this->func->make_pagelink($page, '', '', '', 'pagelink', $options);
	}
}
?>