<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: showrss.inc.php,v 1.3 2007/06/03 22:58:40 nao-pon Exp $
//  Id:showrss.inc.php,v 1.40 2003/03/18 11:52:58 hiro Exp
// Copyright (C):
//     2002-2006 PukiWiki Developers Team
//     2002      PANDA <panda@arino.jp>
//     (Original)hiro_do3ob@yahoo.co.jp
// License: GPL, same as PukiWiki
//
// Show RSS (of remote site) plugin
// NOTE:
//    * This plugin needs 'PHP xml extension'
//    * Cache data will be stored as CACHE_DIR/*.tmp

class xpwiki_plugin_showrss extends xpwiki_plugin {
	function plugin_showrss_init () {

		$this->cont['PLUGIN_SHOWRSS_USAGE'] =  '#showrss(URI-to-RSS[,default|menubar|recent[,Cache-lifetime[,Show-timestamp]]])';

	}
	
	// Show related extensions are found or not
	function plugin_showrss_action()
	{
		if ($this->cont['PKWK_SAFE_MODE']) $this->func->die_message('PKWK_SAFE_MODE prohibit this');
	
		$body = '';
		foreach(array('xml', 'mbstring') as $extension){
			$$extension = extension_loaded($extension) ?
				'&color(green){Found};' :
				'&color(red){Not found};';
			$body .= '| ' . $extension . ' extension | ' . $$extension . ' |' . "\n";
		}
		return array('msg' => 'showrss_info', 'body' => $this->func->convert_html($body));
	}
	
	function plugin_showrss_convert()
	{
		static $_xml;
		if (! isset ($_xml)) $_xml = extension_loaded('xml');
		if (! $_xml) return '#showrss: xml extension is not found<br />' . "\n";
	
		$num = func_num_args();
		if ($num == 0) return $this->cont['PLUGIN_SHOWRSS_USAGE'] . '<br />' . "\n";
	
		$argv = func_get_args();
		$timestamp = FALSE;
		$cachehour = 0;
		$template = $uri = '';
		switch ($num) {
		case 4: $timestamp = (trim($argv[3]) == '1');	/*FALLTHROUGH*/
		case 3: $cachehour = trim($argv[2]);		/*FALLTHROUGH*/
		case 2: $template  = strtolower(trim($argv[1]));/*FALLTHROUGH*/
		case 1: $uri       = trim($argv[0]);
		}
	
		$class = ($template == '' || $template == 'default') ? 'XpWikiShowRSS_html' : 'XpWikiShowRSS_html_' . $template;
		if (! is_numeric($cachehour))
			return '#showrss: Cache-lifetime seems not numeric: ' . htmlspecialchars($cachehour) . '<br />' . "\n";
		if (! class_exists($class))
			return '#showrss: Template not found: ' . htmlspecialchars($template) . '<br />' . "\n";
		if (! $this->func->is_url($uri))
			return '#showrss: Seems not URI: ' . htmlspecialchars($uri) . '<br />' . "\n";
	
		list($rss, $time) = $this->plugin_showrss_get_rss($uri, $cachehour);
		if ($rss === FALSE) return '#showrss: Failed fetching RSS from the server<br />' . "\n";
	
		$time_str = '';
		if ($timestamp > 0) {
			$time_str = '<p style="font-size:10px; font-weight:bold">Last-Modified:' .
			$this->func->get_date('Y/m/d H:i:s', $time) .  '</p>';
		}
	
		$obj = new $class($this->xpwiki, $rss);
		return $obj->toString($time_str);
	}
	
	// Get and save RSS
	function plugin_showrss_get_rss($target, $cachehour)
	{
		$buf  = '';
		$time = NULL;
		if ($cachehour) {
			// Remove expired cache
			$this->plugin_showrss_cache_expire($cachehour);
	
			// Get the cache not expired
			$filename = $this->cont['CACHE_DIR'] . $this->func->encode($target) . '.tmp';
			if (is_readable($filename)) {
				$buf  = join('', file($filename));
				$time = filemtime($filename) - $this->cont['LOCALZONE'];
			}
		}
	
		if ($time === NULL) {
			// Newly get RSS
			$data = $this->func->http_request($target);
			if ($data['rc'] !== 200)
				return array(FALSE, 0);
	
			$buf = $data['data'];
			$time = $this->cont['UTIME'];
	
			// Save RSS into cache
			if ($cachehour) {
				$fp = fopen($filename, 'w');
				fwrite($fp, $buf);
				fclose($fp);
			}
		}
	
		// Parse
		$obj = new XpWikiShowRSS_XML($this->xpwiki);
		return array($obj->parse($buf),$time);
	}
	
	// Remove cache if expired limit exeed
	function plugin_showrss_cache_expire($cachehour)
	{
		$expire = $cachehour * 60 * 60; // Hour
		$dh = dir($this->cont['CACHE_DIR']);
		while (($file = $dh->read()) !== FALSE) {
			if (substr($file, -4) != '.tmp') continue;
			$file = $this->cont['CACHE_DIR'] . $file;
			$last = time() - filemtime($file);
			if ($last > $expire) unlink($file);
		}
		$dh->close();
	}
	
	function plugin_showrss_get_timestamp($str)
	{
		$str = trim($str);
		if ($str == '') return $this->cont['UTIME'];
	
		$matches = array();
		if (preg_match('/(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})(([+-])(\d{2}):(\d{2}))?/', $str, $matches)) {
			$time = strtotime($matches[1] . ' ' . $matches[2]);
			if ($time == -1) {
				$time = $this->cont['UTIME'];
			} else if ($matches[3]) {
				$diff = ($matches[5] * 60 + $matches[6]) * 60;
				$time += ($matches[4] == '-' ? $diff : -$diff);
			}
			return $time;
		} else {
			$time = strtotime($str);
			return ($time == -1) ? $this->cont['UTIME'] : $time - $this->cont['LOCALZONE'];
		}
	}
}
	
// Create HTML from RSS array()
class XpWikiShowRSS_html
{
	var $items = array();
	var $class = '';

	function XpWikiShowRSS_html(& $xpwiki, $rss)
	{
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;
		if ($rss && is_array($rss)) {
			foreach ($rss as $date=>$items) {
				foreach ($items as $item) {
					$link  = $item['LINK'];
					$title = $item['TITLE'];
					$passage = $this->func->get_passage($item['_TIMESTAMP']);
					$link = '<a href="' . $link . '" title="' .  $title . ' ' .
						$passage . '" rel="nofollow">' . $title . '</a>';
					$this->items[$date][] = $this->format_link($link);
				}
			}
		}
	}

	function format_link($link)
	{
		return $link . '<br />' . "\n";
	}

	function format_list($date, $str)
	{
		return $str;
	}

	function format_body($str)
	{
		return $str;
	}

	function toString($timestamp)
	{
		$retval = '';
		foreach ($this->items as $date=>$items)
			$retval .= $this->format_list($date, join('', $items));
		$retval = $this->format_body($retval);
		return <<<EOD
<div{$this->class}>
$retval$timestamp
</div>
EOD;
	}
}
	
class XpWikiShowRSS_html_menubar extends XpWikiShowRSS_html
{
	var $class = ' class="small"';
	
	//function XpWikiShowRSS_html_menubar(& $xpwiki) {
	//	parent::XpWikiShowRSS_html($xpwiki);
	//}
	
	function format_link($link) {
		return '<li>' . $link . '</li>' . "\n";
	}

	function format_body($str) {
		return '<ul class="recent_list">' . "\n" . $str . '</ul>' . "\n";
	}
}
	
class XpWikiShowRSS_html_recent extends XpWikiShowRSS_html
{
	var $class = ' class="small"';

	//function XpWikiShowRSS_html_recent (& $xpwiki) {
	//	parent::XpWikiShowRSS_html($xpwiki);
	//}
	
	function format_link($link) {
		return '<li>' . $link . '</li>' . "\n";
	}

	function format_list($date, $str) {
		return '<strong>' . $date . '</strong>' . "\n" .
			'<ul class="recent_list">' . "\n" . $str . '</ul>' . "\n";
	}
}
	
	// Get RSS and array() them
class XpWikiShowRSS_XML
{
	var $items;
	var $item;
	var $is_item;
	var $tag;
	var $encoding;
	
	function XpWikiShowRSS_XML(& $xpwiki)
	{
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;
	}

	function parse($buf)
	{
		$this->items   = array();
		$this->item    = array();
		$this->is_item = FALSE;
		$this->tag     = '';

		// Detect encoding
		$matches = array();
		if(preg_match('/<\?xml [^>]*\bencoding="([a-z0-9-_]+)"/i', $buf, $matches)) {
			$this->encoding = $matches[1];
		} else {
			$this->encoding = mb_detect_encoding($buf);
		}

		// Normalize to UTF-8 / ASCII
		if (! in_array(strtolower($this->encoding), array('us-ascii', 'iso-8859-1', 'utf-8'))) {
			$buf = mb_convert_encoding($buf, 'utf-8', $this->encoding);
			$this->encoding = 'utf-8';
		}

		// Parsing
		$xml_parser = xml_parser_create($this->encoding);
		xml_set_element_handler($xml_parser, array(& $this, 'start_element'), array(& $this, 'end_element'));
		xml_set_character_data_handler($xml_parser, array(& $this, 'character_data'));
		if (! xml_parse($xml_parser, $buf, 1)) {
			return(sprintf('XML error: %s at line %d in %s',
				xml_error_string(xml_get_error_code($xml_parser)),
				xml_get_current_line_number($xml_parser), $buf));
		}
		xml_parser_free($xml_parser);

		return $this->items;
	}

	function escape($str)
	{
		// Unescape already-escaped chars (&lt;, &gt;, &amp;, ...) in RSS body before htmlspecialchars()
		$str = strtr($str, array_flip(get_html_translation_table(ENT_COMPAT)));
		// Escape
		$str = htmlspecialchars($str);
		// Encoding conversion
		$str = mb_convert_encoding($str, $this->cont['SOURCE_ENCODING'], $this->encoding);
		return trim($str);
	}

	// Tag start
	function start_element($parser, $name, $attrs)
	{
		if ($this->is_item) {
			$this->tag     = $name;
		} else if ($name == 'ITEM') {
			$this->is_item = TRUE;
		}
	}

	// Tag end
	function end_element($parser, $name)
	{
		if (! $this->is_item || $name != 'ITEM') return;

		$item = array_map(array(& $this, 'escape'), $this->item);
		$this->item = array();

		if (isset($item['DC:DATE'])) {
			$time = xpwiki_plugin_showrss::plugin_showrss_get_timestamp($item['DC:DATE']);
			
		} else if (isset($item['PUBDATE'])) {
			$time = xpwiki_plugin_showrss::plugin_showrss_get_timestamp($item['PUBDATE']);
			
		} else if (isset($item['DESCRIPTION']) &&
			($description = trim($item['DESCRIPTION'])) != '' &&
			($time = strtotime($description)) != -1) {
				$time -= $this->cont['LOCALZONE'];

		} else {
			$time = time() - $this->cont['LOCALZONE'];
		}
		$item['_TIMESTAMP'] = $time;
		$date = $this->func->get_date('Y-m-d', $item['_TIMESTAMP']);

		$this->items[$date][] = $item;
		$this->is_item        = FALSE;
	}

	function character_data($parser, $data)
	{
		if (! $this->is_item) return;
		if (! isset($this->item[$this->tag])) $this->item[$this->tag] = '';
		$this->item[$this->tag] .= $data;
	}
}
?>