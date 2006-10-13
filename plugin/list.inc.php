<?php
class xpwiki_plugin_list extends xpwiki_plugin {
	function plugin_list_init () {



	}
	// PukiWiki - Yet another WikiWikiWeb clone.
	// $Id: list.inc.php,v 1.1 2006/10/13 13:17:49 nao-pon Exp $
	//
	// IndexPages plugin: Show a list of page names
	
	function plugin_list_action()
	{
	//	global $vars, $_title_list, $_title_filelist, $whatsnew;
	
		// Redirected from filelist plugin?
		$filelist = (isset($this->root->vars['cmd']) && $this->root->vars['cmd'] == 'filelist');
	
		return array(
			'msg'=>$filelist ? $this->root->_title_filelist : $this->root->_title_list,
		'body'=>$this->plugin_list_getlist($filelist));
	}
	
	// Get a list
	function plugin_list_getlist($withfilename = FALSE)
	{
	//	global $non_list, $whatsnew;
	
		$pages = array_diff($this->func->get_existpages(), array($this->root->whatsnew));
		if (! $withfilename)
			$pages = array_diff($pages, preg_grep('/' . $this->root->non_list . '/S', $pages));
		if (empty($pages)) return '';
	
		return $this->func->page_list($pages, 'read', $withfilename);
	}
}
?>