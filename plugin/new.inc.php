<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: new.inc.php,v 1.5 2008/04/24 00:02:15 nao-pon Exp $
//
// New! plugin
//
// Usage:
//	&new([nodate]){date};     // Check the date string
//	&new(pagename[,nolink]);  // Check the pages's timestamp
//	&new(pagename/[,nolink]);
//		// Check multiple pages started with 'pagename/',
//		// and show the latest one

class xpwiki_plugin_new extends xpwiki_plugin {
	
	function plugin_new_init()
	{
		$this->cont['PLUGIN_NEW_DATE_FORMAT'] =  '<span class="comment_date">%s</span>';

		// Elapsed time => New! message with CSS
		$messages['_plugin_new_elapses'] = array(
			60 * 60 * 24 * 1 => ' <span class="new1" title="%s">New!</span>',  // 1day
			60 * 60 * 24 * 5 => ' <span class="new5" title="%s">New</span>');  // 5days
		$this->func->set_plugin_messages($messages);
	}
	
	function plugin_new_inline()
	{
	
		$retval = '';
		$args = func_get_args();
		$date = $this->func->strip_autolink(array_pop($args)); // {date} always exists
	
		if($date !== '') {
			// Show 'New!' message by the time of the $date string
			if (func_num_args() > 2) return '&new([nodate]){date};';
	
			$_date = preg_replace('/\([^)]+\)/', '', $date);
			$timestamp = strtotime($_date);
			if ($timestamp === -1 || $timestamp === FALSE) return '&new([nodate]){date};: Invalid date string "'.htmlspecialchars($date).'"';
			$timestamp -= $this->cont['ZONETIME'];
	
			$retval = in_array('nodate', $args) ? '' : htmlspecialchars($date);
		} else {
			// Show 'New!' message by the timestamp of the page
			if (func_num_args() > 3) return '&new(pagename[,nolink]);';
	
			$name = $this->func->strip_bracket(! empty($args) ? array_shift($args) : $this->root->vars['page']);
			$page = $this->func->get_fullname($name, $this->root->vars['page']);
			$nolink = in_array('nolink', $args);
	
			if (substr($page, -1) == '/') {
				// Check multiple pages started with "$page"
				$timestamp = 0;
				//$regex = '/^' . preg_quote($page, '/') . '/';
				//foreach (preg_grep($regex, $this->func->get_existpages()) as $page) {
				//	// Get the latest pagename and its timestamp
				//	$_timestamp = $this->func->get_filetime($page);
				//	if ($timestamp < $_timestamp) {
				//		$timestamp = $_timestamp;
				//		$retval    = $nolink ? '' : $this->func->make_pagelink($page);
				//	}
				//}
				$_page = $this->func->get_existpages(FALSE, $page, array('limit' => 1, 'order' => ' ORDER BY `editedtime` DESC', 'withtime' => TRUE));
				if ($_page) {
					list($timestamp, ) = explode("\t", array_shift($_page));
					$retval    = $nolink ? '' : $this->func->make_pagelink(substr($page, 0, strlen($page) - 1));
				}
				
				if ($timestamp == 0)
					return '&new(' . $page . '[,nolink]);: No such pages;';
			} else {
				// Check a page
				if ($this->func->is_page($page)) {
					$timestamp = $this->func->get_filetime($page);
					$retval    = $nolink ? '' : $this->func->make_pagelink($page, $name);
				} else {
					return '&new(pagename[,nolink]): No such page;';
				}
			}
		}
	
		// Add 'New!' string by the elapsed time
		$erapse = $this->cont['UTIME'] - $timestamp;
		foreach ($this->root->_plugin_new_elapses as $limit=>$tag) {
			if ($erapse <= $limit) {
				$retval .= sprintf($tag, $this->func->get_passage($timestamp));
				break;
			}
		}
	
		if($date !== '') {
			// Show a date string
			return sprintf($this->cont['PLUGIN_NEW_DATE_FORMAT'], $retval);
		} else {
			// Show a page name
			return $retval;
		}
	}
}
?>