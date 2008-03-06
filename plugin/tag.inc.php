<?php
class xpwiki_plugin_tag extends xpwiki_plugin {
	
	////////////////////////////////
	function plugin_tag_init()
	{
	
	////////////////////////////////
	/**
	 * Generate An HTML Tag Cloud
	 * @author astronote <http://astronote.jp/>
		 */
	//	global $plugin_tag;
		$this->root->plugin_tag = new XpWikiPluginTag($this->xpwiki);
	}
	
	function plugin_tag_inline()
	{
	//	global $plugin_tag;
		$args = func_get_args();
		return call_user_func_array(array($this->root->plugin_tag, 'inline'), $args);
	}
	
	function plugin_tag_convert()
	{
	//	global $plugin_tag;
		$args = func_get_args();
		return call_user_func_array(array($this->root->plugin_tag, 'convert'), $args);
	}

	function plugin_tag_action()
	{
		// 権限チェック
		if (!$this->root->userinfo['admin']) {
			return $this->action_msg_admin_only();
		}

		if ($this->root->module['platform'] == "xoops") {
			$this->root->runmode = "xoops_admin";
		}

		include_once $this->root->mytrustdirpath . '/events/onPageWriteAfter/tag.inc.php';
		$pages = $this->func->get_existpages();
		
		if ($dir_h = @opendir($this->cont['CACHE_DIR'])) {
			while($file = readdir($dir_h)) {
				if (substr($file, -4) === '.tag') @ unlink($this->cont['CACHE_DIR'] . $file);
			}
			closedir($dir_h);
		}

		foreach ($pages as $page) {
			$postdata = $this->func->get_source($page, TRUE, TRUE);
			$notimestamp = FALSE;
			$mode = 'insert';
			$diffdata = '';
			xpwiki_onPageWriteAfter_tag($this->func, $page, $postdata, $notimestamp, $mode, $diffdata);
		}
	
		return array(
			'msg'  => 'Tag plugin',
			'body' => 'Cache is updated.'
		);
	}

}
	// $Id: tag.inc.php,v 1.11 2008/03/06 23:49:15 nao-pon Exp $
	
class XpWikiPluginTag
{
	////// tag cloud ////////
	function XpWikiPluginTag(& $xpwiki) {
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;

	
	}
	
	function convert()
	{
		static $loaded = FALSE;
		
		if (!$loaded) {
			$loaded = TRUE;
			$this->func->add_tag_head('tagcloud.css');
		}
		
		// option
		if (func_num_args() == 0) {
			$limit = 20;
		} else {
			$args = func_get_args();
			$limit = array_shift($args);
		}

		$cloud_p = new XpWikiTagCloud($this->xpwiki);
		$cloud_d = $this->read_tagcloud();//$limit);
		foreach ($cloud_d as $key => $val) {
			list($tag, $count) = $val;
			$url = $this->func->get_script_uri() . '?' . 'cmd=lsx&amp;tag=' . rawurlencode($tag);
			$cloud_p->add(htmlspecialchars($tag), $url, $count);
		}
		return $cloud_p->html($limit);
	}
	
	function read_tagcloud($limit = NULL)
	{
		$cache = $this->cont['CACHE_DIR'] . 'tagcloud.tag';
		if (! file_exists($cache)) return array();
		
		//if (isset($limit))
		//$lines = file_head($cache, $limit); // pukiwiki API
		//else 
		$lines = file($cache);
		
		if ($lines === FALSE) return array();
		$lines = array_map('rtrim', $lines);
		
		$tagcloud = array();
		foreach ($lines as $line) {
			list($tag, $count) = explode("\t", $line);
			$key = $this->get_key($tag);
			$tagcloud[$key] = array($tag, $count);
		}
		return $tagcloud;
	}

	function write_tagcloud($tagcloud)
	{
		$cache = $this->cont['CACHE_DIR'] . 'tagcloud.tag';
		$contents = '';
		$tag_counts = array();
		ksort($tagcloud);
		foreach ($tagcloud as $key => $val) {
			list($tag, $count) = $val;
			if ($count == 0 || !$tag) continue;
			$contents .= $tag . "\t" . $count . "\n";
		}
		return $this->file_put_contents($cache, $contents);
	}

	//////// tagging /////////
	function inline()
	{
		if (func_num_args() == 0) {
			return 'tag(): no argument(s). ';
		}
		
		$page = $this->root->vars['page'];
		$args = func_get_args(); array_pop($args); $tags = $args;
		// $tags = array_map('strtolower', $tags); // does not work for UTF-8
		$tags = array_unique($tags);
		
		if (@ $this->root->rtf['is_init'] && 
			$this->is_page_new($page) && 
			$this->check_tagnames($tags) &&
			$this->root->rtf['convert_nest'] < 2
		) {
			if ($ret = $this->renew_tagcache($page, $tags)) {
				return $this->frontend($tags);
			} else {
				return $ret;
			}			
		}
		
		return $this->frontend($tags);
		
		/*
		if (! $this->is_page_new($page))
			return $this->frontend($tags);

		if (! $this->check_tagnames($tags))
			return 'tag(): tag names are illegal. Do not use ^ and -. ';
		
		if ($this->root->rtf['convert_nest'] > 1 || !empty($this->root->rtf['preview']))
			return $this->frontend($tags);
		
		if ($ret = $this->renew_tagcache($page, $tags)) {
			return $this->frontend($tags);
		} else {
			return $ret;
		}
		*/
	}

	// for another listing plugin
	function get_taggedpages($tagtok = '')
	{
		$tags = array();
		$ops  = array();
		while (true) {
			$intersectpos = strpos($tagtok, '^');
			$diffpos	  = strpos($tagtok, '-');
			if ($intersectpos === FALSE && $diffpos === FALSE) {
				break;
			} elseif ($diffpos === FALSE || $intersectpos < $diffpos) {
				$pos = $intersectpos;
				array_push($ops, 'intersect');
			} else {
				$pos = $diffpos;
				array_push($ops, 'diff');
			}
			array_push($tags, substr($tagtok, 0, $pos));
			$tagtok = substr($tagtok, $pos + 1);
		}
		array_push($tags, $tagtok);

		$tag = array_shift($tags);
		$storage = $this->get_tagstorage($tag);
		if (! file_exists($storage)) return FALSE;
		$pages = array_map('rtrim', file($storage));
		foreach ($tags as $i => $tag) {
			$storage = $this->get_tagstorage($tag);
			if (! file_exists($storage)) return FALSE;
			$intersect	= array_intersect($pages, array_map('rtrim', file($storage)));
			switch ($ops[$i]) {
			case 'intersect':
				$pages = $intersect;
				break;
			case 'diff':
				$pages = array_diff($pages, $intersect);
				break;
			}
		}
		return $pages;
	}
	
	function check_tagnames($tags)
	{
		// '^' and '-' are reserved keys. 
		foreach ($tags as $tag) {
			if (strpos($tag, '^') !== FALSE) return FALSE;
			elseif (strpos($tag, '-') !== FALSE) return FALSE;
		}
		return TRUE;
	}

	function is_page_new($page)
	{
		$pagestamp	= $this->func->is_page($page) ? filemtime($this->func->get_filename($page)) : 0;
		$cache		= $this->get_pagestorage($page);
		$cachestamp = file_exists($cache) ? filemtime($cache) : 0;
		return $pagestamp > $cachestamp;
	}

	function frontend($tags)
	{
		$ret = '<span class="tag">';
		$ret .= 'Tag: ';
		foreach ($tags as $tag) {
			$ret .= '<a href="' . $this->func->get_script_uri() . '?cmd=lsx&amp;tag=' . rawurlencode($tag) . '">' . htmlspecialchars($tag) . '</a> ';
		}
		$ret .= '</span>';
		return $ret;
	}

	function del_page($tag, $page)
	{
		$storage = $this->get_tagstorage($tag);
		if (! file_exists($storage)) return FALSE;
		$pages = file($storage);
		$pages = array_diff($pages, array($page . "\n"));
		if (empty($pages)) {
			if (unlink($storage) === FALSE) return FALSE;
		} else {
			if ($this->file_put_contents($storage, implode("", $pages)) === FALSE) return FALSE;
		}
		return sizeof($pages);
	}

	function add_page($tag, $page)
	{
		$storage = $this->get_tagstorage($tag);
		$pages = array();
		if (file_exists($storage)) {
			$pages = file($storage);
		}
		array_push($pages, $page . "\n");
		//$pages = array_unique($pages);  // should be assured
		if ($this->file_put_contents($storage, implode("", $pages)) === FALSE) return FALSE;
		return sizeof($pages);
	}

	function get_tag_diff($page, $newtags)
	{
		$storage = $this->get_pagestorage($page);
		if (! file_exists($storage)) {
			$oldtags = array();
		} else {
			$oldtags = array_map('rtrim', file($storage));
		}
		return $this->my_array_diff($oldtags, $newtags);
	}

	function renew_tagcache($page, $tags)
	{
		list($dels, $adds) = $this->get_tag_diff($page, $tags);

		$storage = $this->get_pagestorage($page);
		if (!$tags) {
			@unlink($storage);
		} else {
			if ($this->file_put_contents($storage, implode("\n", $tags) . "\n") === FALSE)
				return "tag(): failed to write page tagdata. ";
		}
		
		$tagcloud = $this->read_tagcloud();
		if ($dels) {
			foreach ($dels as $tag) {
				$count = $this->del_page($tag, $page);
				if ($count === FALSE)
					return "tag(): failed to delete $page from tag cache for $tag. ";
				$tagcloud[$this->get_key($tag)] = array($tag, $count);
			}
		}
		if ($adds) {
			foreach ($adds as $tag) {
				$count = $this->add_page($tag, $page);
				if ($count === FALSE)
					return "tag(): failed to add $page to tag cache for $tag. ";
				$tagcloud[$this->get_key($tag)] = array($tag, $count);
			}
		}
		if ($this->write_tagcloud($tagcloud) === FALSE)
			return "tag(): failed to write tag cloud cache. ";
		
		return TRUE;
	}

	// PHP4 does not allow static function
	function get_key($tag)
	{
		return mb_strtolower($tag, $this->cont['SOURCE_ENCODING']);
	}

	function get_tagstorage($tag)
	{
		$key = $this->get_key($tag);
		return	$this->cont['CACHE_DIR'] . $this->func->encode($key) . '_tag.tag';
	}

	function get_pagestorage($page)
	{
		return $this->cont['CACHE_DIR'] . $this->func->encode($page) . '_page.tag';
	}

	// PHP extension
	function my_array_diff(&$oldarray, &$newarray)
	{
		$common = array_intersect($oldarray, $newarray);
		$minus	= array_diff($oldarray, $common);
		$plus	= array_diff($newarray, $common);
		return array($minus, $plus);
	}

	// PHP5.0 has file_put_contents($file, $contents),	though
	function file_put_contents($file, $contents)
	{
		if (($fp = fopen($file, "w")) === FALSE) {
			return FALSE;
		}
		if (fwrite($fp, $contents) === FALSE) {
			fclose($fp);
			return FALSE;
		} 
		return fclose($fp);
	}
}
class XpWikiTagCloud
{
	var $counts;
	var $urls;
	
	function XpWikiTagCloud(& $xpwiki)
	{
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;

		$this->counts = array();
		$this->urls = array();
	}

	function add($tag, $url, $count)
	{
		$this->counts[$tag] = $count;
		$this->urls[$tag] = $url;
	}

	function css()
	{
		$css = '#htmltagcloud { text-align: center; line-height: 16px; }';
		for ($level = 0; $level <= 24; $level++) {
			$font = 12 + $level;
			$css .= "span.tagcloud$level { font-size: ${font}px;}\n";
			$css .= "span.tagcloud$level a {text-decoration: none;}\n";
		}
		return $css;
	}

	function html($limit = NULL)
	{
		$a = $this->counts;
		arsort($a);
		$tags = array_keys($a);
		if (!empty($limit)) {
			$tags = array_slice($tags, 0, $limit);
		}
		$n = count($tags);
		if ($n == 0) {
			return '';
		} elseif ($n == 1) {
			$tag = $tags[0];
			$url = $this->urls[$tag];
			return "<div class=\"htmltagcloud\"><span class=\"tagcloud1\"><a href=\"$url\">$tag</a></span></div>\n"; 
		}
		
		$min = sqrt($this->counts[$tags[$n - 1]]);
		$max = sqrt($this->counts[$tags[0]]);
		$factor = 0;
		
		// specal case all tags having the same count
		if (($max - $min) == 0) {
			$min -= 24;
			$factor = 1;
		} else {
			$factor = 24 / ($max - $min);
		}
		$html = '';
		//sort($tags);
		natcasesort($tags);
		foreach($tags as $tag) {
			$count = $this->counts[$tag];
			$url   = $this->urls[$tag];
			$level = (int)((sqrt($count) - $min) * $factor);
			$html .=  "<span class=\"tagcloud$level\"><a href=\"$url\">$tag</a></span>\n"; 
		}
		$html = "<div class=\"htmltagcloud\">$html</div>";
		return $html;
	}

	function htmlAndCSS($limit = NULL)
	{
		$html = "<style type=\"text/css\">\n" . $this->css() . "</style>" . $this->html($limit);
		return $html;
	}
}
?>