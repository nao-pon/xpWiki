<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: rsslink.inc.php,v 1.3 2006/12/13 04:45:31 nao-pon Exp $
//
class xpwiki_plugin_rsslink extends xpwiki_plugin {
	function plugin_rsslink_init () {

	}
	
	function plugin_rsslink_inline()
	{
		$list_count = 0;
		
		if (func_num_args() == 4)
		{
			list($page,$type,$list_count,$body) = func_get_args();
		}
		elseif (func_num_args() == 3)
		{
			list($page,$type,$body) = func_get_args();
		}
		elseif (func_num_args() == 2)
		{
			list($page,$body) = func_get_args();
			$type = '';
		}
		elseif (func_num_args() == 1)
			$page = $type = '';
		else
			return FALSE;
		
		$type = strtolower($type);
		switch ($type) {
			case 'rss10':
			case '1.0';
			case '10':
				$ver  = ' 1.0';
				$type = 'rss&amp;ver=1.0';
				$icon = 'feed-rss1.png';
				break;
			case 'rss20':
			case '2.0';
			case '20':
				$ver  = ' 2.0';
				$type = 'rss&amp;ver=2.0';
				$icon = 'feed-rss2.png';
				break;
			case 'atom':
				$ver  = ' Atom';
				$type = 'rss&amp;ver=atom';
				$icon = 'feed-atom.png';
				break;
			default:
				$ver = '';
				$type = 'rss';
				$icon = 'feed-rss.png';
		}

	
		if ($this->func->is_page($page))
		{
			$s_page = '&amp;p='.rawurlencode($page);
			$page = ' of '.htmlspecialchars($page);
		}
		else
		{
			$s_page = '';
			$page = ' of '.htmlspecialchars($this->root->module['name']);
		}
		
		$title = 'RSS' . $ver . $page;
		$s_list_count = ($list_count)? '&amp;count=' . $list_count : '';
		return '<a href="' . $this->root->script. '?cmd=' . $type . $s_page . $s_list_count . '" title="' . $title. '"><img src="'.$this->cont['IMAGE_DIR'].$icon.'" alt="' . $title . '" /></a>';
	}
}
?>