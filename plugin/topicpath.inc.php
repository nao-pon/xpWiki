<?php
class xpwiki_plugin_topicpath extends xpwiki_plugin {
	function plugin_topicpath_init () {


	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: topicpath.inc.php,v 1.1 2006/10/13 13:17:49 nao-pon Exp $
	//
	// 'topicpath' plugin for PukiWiki, available under GPL
	
	// Show a link to $defaultpage or not
		$this->cont['PLUGIN_TOPICPATH_TOP_DISPLAY'] =  1;
	
	// Label for $defaultpage
		$this->cont['PLUGIN_TOPICPATH_TOP_LABEL'] =  'Top';
	
	// Separetor / of / topic / path
		$this->cont['PLUGIN_TOPICPATH_TOP_SEPARATOR'] =  ' / ';
	
	// Show the page itself or not
		$this->cont['PLUGIN_TOPICPATH_THIS_PAGE_DISPLAY'] =  1;
	
	// If PLUGIN_TOPICPATH_THIS_PAGE_DISPLAY, add a link to itself
		$this->cont['PLUGIN_TOPICPATH_THIS_PAGE_LINK'] =  0;

	}
	
	function plugin_topicpath_convert()
	{
		return '<div>' . $this->plugin_topicpath_inline() . '</div>';
	}
	
	function plugin_topicpath_inline()
	{
	//	global $script, $vars, $defaultpage;
	
		$page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
		if ($page == '' || $page == $this->root->defaultpage) return '';
	
		$parts = explode('/', $page);
	
		$b_link = TRUE;
		if ($this->cont['PLUGIN_TOPICPATH_THIS_PAGE_DISPLAY']) {
			$b_link = $this->cont['PLUGIN_TOPICPATH_THIS_PAGE_LINK'];
		} else {
			array_pop($parts); // Remove the page itself
		}
	
		$topic_path = array();
		while (! empty($parts)) {
			$_landing = join('/', $parts);
			$landing  = rawurlencode($_landing);
			$element = htmlspecialchars(array_pop($parts));
			if (! $b_link)  {
				// This page ($_landing == $page)
				$b_link = TRUE;
				$topic_path[] = $element;
			} else if ($this->cont['PKWK_READONLY'] && ! $this->func->is_page($_landing)) {
				// Page not exists
				$topic_path[] = $element;
			} else {
				// Page exists or not exists
				$topic_path[] = '<a href="' . $this->root->script . '?' . $landing . '">' .
				$element . '</a>';
			}
		}
	
		if ($this->cont['PLUGIN_TOPICPATH_TOP_DISPLAY'])
			$topic_path[] = $this->func->make_pagelink($this->root->defaultpage, $this->cont['PLUGIN_TOPICPATH_TOP_LABEL']);
	
		return join($this->cont['PLUGIN_TOPICPATH_TOP_SEPARATOR'], array_reverse($topic_path));
	}
}
?>