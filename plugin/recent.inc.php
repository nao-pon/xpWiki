<?php
// $Id: recent.inc.php,v 1.14 2008/03/21 02:48:22 nao-pon Exp $
// Copyright (C)
//   2002-2006 PukiWiki Developers Team
//   2002      Y.MASUI http://masui.net/pukiwiki/ masui@masui.net
// License: GPL version 2
//
// Recent plugin -- Show RecentChanges list
//   * Usually used at 'MenuBar' page
//   * Also used at special-page, without no #recnet at 'MenuBar'

class xpwiki_plugin_recent extends xpwiki_plugin {
	function plugin_recent_init () {

		// Default number of 'Show latest N changes'
		$this->cont['PLUGIN_RECENT_DEFAULT_LINES'] =  10;
	
		// Limit number of executions
		$this->cont['PLUGIN_RECENT_EXEC_LIMIT'] =  2; // N times per one output
	
		// ----
		$this->cont['PLUGIN_RECENT_USAGE'] =  '#recent([Base Page,][Number to show])';
	
		// Place of the cache of 'RecentChanges'
		$this->cont['PLUGIN_RECENT_CACHE'] =  $this->cont['CACHE_DIR'] . $this->cont['PKWK_MAXSHOW_CACHE'];

	}
	
	function plugin_recent_convert()
	{
		static $exec_count = array();

		if (!isset($exec_count[$this->xpwiki->pid])) {$exec_count[$this->xpwiki->pid] = 1;}

		$prefix = "";
		$recent_lines = 0;
		if(func_num_args()>0) {
			$args = func_get_args();
			$recent_lines = (int)$args[0];
			$prefix = $args[0];
			
			// Other xpWiki dir
			if (strpos($prefix, ':') !== FALSE) {
				list($dir, $_prefix) = explode(':', $prefix, 2);
				if ($this->func->isXpWikiDirname($dir)) {
					$args[0] = $_prefix;
					$otherObj = & XpWiki::getSingleton($dir);
					if ($otherObj->isXpWiki) {
						$otherObj->init('#RenderMode');
						return $otherObj->func->do_plugin_convert('recent', $this->func->csv_implode(',', $args));
					} else {
						return sprintf($this->root->_recent_plugin_frame, $prefix, 0, '');
					}
				}
			}
			
			$prefix = preg_replace("/\/$/","",$prefix);
			if ($this->func->is_page($prefix) || ! $prefix)
			{
				if (isset($args[1]) && is_numeric($args[1]))
					$recent_lines = $args[1];
			}
			else if (isset($args[1]))
			{
				$prefix = $args[1];
				$prefix = preg_replace("/\/$/","",$prefix);
				if ($this->func->is_page($prefix) || ! $prefix)
				{
					if (isset($args[0]) && is_numeric($args[0]))
						$recent_lines = $args[0];
				}
				else
					$prefix = "";
			}
			else
				$prefix = "";
		}
		$_prefix = ($prefix)? $prefix . '/' : '';
		$prefix_page = ($prefix)? $this->func->make_pagelink($prefix).' ' : '';
		if (!$recent_lines) $recent_lines = $this->cont['PLUGIN_RECENT_DEFAULT_LINES'];
		
		// Show only N times
		if ($exec_count[$this->xpwiki->pid] > $this->cont['PLUGIN_RECENT_EXEC_LIMIT']) {
			return '#recent(): You called me too much' . '<br />' . "\n";
		} else {
			++$exec_count[$this->xpwiki->pid];
		}
		
		$res = array();
		if ($this->root->render_mode === 'block' && isset($GLOBALS['Xpwiki_'.$this->root->mydirname]['page'])) {
			$res = $this->func->set_current_page($GLOBALS['Xpwiki_'.$this->root->mydirname]['page']);
			$this->root->pagecache_min = 0;
		}
	
		// Get latest N changes
		$lines = $this->func->get_existpages(FALSE, $_prefix, array('limit' =>$recent_lines, 'order' => ' ORDER BY editedtime DESC', 'nolisting' => TRUE, 'withtime' =>TRUE));
		
		$date = $items = '';
		foreach ($lines as $line) {
			list($time, $page) = explode("\t", rtrim($line));
	
			$_date = $this->func->get_date($this->root->date_format, $time);
			if ($date != $_date) {
				// End of the day
				if ($date != '') $items .= '</ul>' . "\n";
	
				// New day
				$date = $_date;
				$items .= '<strong>' . $date . '</strong>' . "\n" .
				'<ul class="recent_list">' . "\n";
			}
	
			$s_page = htmlspecialchars($page);
			if($page === $this->root->vars['page']) {
				// No need to link to the page you just read, or notify where you just read
				$items .= ' <li><!--NA-->' . str_replace('/', '/' . $this->root->hierarchy_insert, $s_page) . '<!--/NA--></li>' . "\n";
			} else {
				$r_page = rawurlencode($page);
				$passage = $this->root->show_passage ? ' ' . $this->func->get_passage($time) : '';
				$compact = ($prefix)? '#compact:'.$prefix : '';
				$items .= ' <li>'.$this->func->make_pagelink($page, $compact).'</li>' . "\n";

			}
		}
		// End of the day
		if ($date != '') $items .= '</ul>' . "\n";
		
		if ($res) $this->func->set_current_page($res['page']);
		
		return sprintf($this->root->_recent_plugin_frame, $prefix_page, count($lines), $items);
	}
}
?>