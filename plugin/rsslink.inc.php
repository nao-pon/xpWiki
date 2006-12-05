<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: rsslink.inc.php,v 1.1 2006/12/05 00:03:29 nao-pon Exp $
//
class xpwiki_plugin_rsslink extends xpwiki_plugin {
	function plugin_rsslink_init () {

	}
	
	function plugin_rsslink_inline()
	{
		$list_count = 0;
		if (func_num_args() == 5)
		{
			list($page,$type,$with_content,$list_count,$body) = func_get_args();
		}
		elseif (func_num_args() == 4)
		{
			list($page,$type,$with_content,$body) = func_get_args();
		}
		elseif (func_num_args() == 3)
		{
			list($page,$type,$body) = func_get_args();
			$with_content="";
		}
		elseif (func_num_args() == 2)
		{
			list($page,$body) = func_get_args();
			$type = $with_content = "";
		}
		elseif (func_num_args() == 1)
			$page = $type = $with_content = "";
		else
			return FALSE;
		if ($type == "rss10" || $type == "10")
			$type = "rss&amp;ver=1.0";
		else
			$type = "rss";
	
		if ($this->func->is_page($page))
		{
			$s_page = "&amp;p=".rawurlencode($page);
			$page = " of ".htmlspecialchars($page);
		}
		else
		{
			$s_page = "";
			$page = " of ".htmlspecialchars($this->root->page_title);
		}
		if ($type == "rss10")
		{
			$with_content = strtolower($with_content);
			if ($with_content)
			{
				if ($with_content == "s")
					$s_content = "&amp;content=s";
				else
					$s_content = "&amp;content=l";
			}
			else
			{
				$s_content = "";
			}
		}
		$s_list_count = ($list_count)? "&amp;count=$list_count" : "";
		return "<a href=\"{$this->root->script}?cmd=$type$s_page$s_content$s_list_count\" title=\"RSS$page\"><img src=\"".$this->cont['IMAGE_DIR']."rss.png\" alt=\"RSS$page\" /></a>";
	}
}
?>