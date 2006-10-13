<?php
class xpwiki_plugin_counter extends xpwiki_plugin {
	function plugin_counter_init () {


	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: counter.inc.php,v 1.1 2006/10/13 13:17:49 nao-pon Exp $
	// Copyright (C)
	//   2002-2005 PukiWiki Developers Team
	//   2002 Y.MASUI GPL2 http://masui.net/pukiwiki/ masui@masui.net
	// License: GPL2
	//
	// Counter plugin
	
	// Counter file's suffix
		$this->cont['PLUGIN_COUNTER_SUFFIX'] =  '.count';

	}
	
	// Report one
	function plugin_counter_inline()
	{
	//	global $vars;
	
		// BugTrack2/106: Only variables can be passed by reference from PHP 5.0.5
		$args = func_get_args(); // with array_shift()
	
		$arg = strtolower(array_shift($args));
		switch ($arg) {
		case ''     : $arg = 'total'; /*FALLTHROUGH*/
		case 'total': /*FALLTHROUGH*/
		case 'today': /*FALLTHROUGH*/
		case 'yesterday':
			$counter = $this->plugin_counter_get_count($this->root->vars['page']);
			return $counter[$arg];
		default:
			return '&counter([total|today|yesterday]);';
		}
	}
	
	// Report all
	function plugin_counter_convert()
	{
	//	global $vars;
	
		$counter = $this->plugin_counter_get_count($this->root->vars['page']);
		return <<<EOD
<div class="counter">
Counter:   {$counter['total']},
today:     {$counter['today']},
yesterday: {$counter['yesterday']}
</div>
EOD;
	}
	
	// Return a summary
	function plugin_counter_get_count($page)
	{
	//	global $vars;
	//	static $counters = array();
		static $counters = array();
		if (!isset($counters[$this->xpwiki->pid])) {$counters[$this->xpwiki->pid] = array();}
	//	static $default;
		static $default = array();
		if (!isset($default[$this->xpwiki->pid])) {$default[$this->xpwiki->pid] = array();}
	
		if (! isset($default[$this->xpwiki->pid]))
			$default[$this->xpwiki->pid] = array(
				'total'     => 0,
			'date'      => $this->func->get_date('Y/m/d'),
			'today'     => 0,
			'yesterday' => 0,
			'ip'        => '');
	
		if (! $this->func->is_page($page)) return $default[$this->xpwiki->pid];
		if (isset($counters[$this->xpwiki->pid][$page])) return $counters[$this->xpwiki->pid][$page];
	
		// Set default
		$counters[$this->xpwiki->pid][$page] = $default[$this->xpwiki->pid];
		$modify = FALSE;
	
		$file = $this->cont['COUNTER_DIR'] . $this->func->encode($page) . $this->cont['PLUGIN_COUNTER_SUFFIX'];
		$fp = fopen($file, file_exists($file) ? 'r+' : 'w+')
			or die('counter.inc.php: Cannot open COUTER_DIR/' . basename($file));
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		rewind($fp);
		foreach ($default[$this->xpwiki->pid] as $key=>$val) {
			// Update
			$counters[$this->xpwiki->pid][$page][$key] = rtrim(fgets($fp, 256));
			if (feof($fp)) break;
		}
		if ($counters[$this->xpwiki->pid][$page]['date'] != $default[$this->xpwiki->pid]['date']) {
			// New day
			$modify = TRUE;
			$is_yesterday = ($counters[$this->xpwiki->pid][$page]['date'] == $this->func->get_date('Y/m/d', strtotime('yesterday', $this->cont['UTIME'])));
			$counters[$this->xpwiki->pid][$page]['ip']        = $_SERVER['REMOTE_ADDR'];
			$counters[$this->xpwiki->pid][$page]['date']      = $default[$this->xpwiki->pid]['date'];
			$counters[$this->xpwiki->pid][$page]['yesterday'] = $is_yesterday ? $counters[$this->xpwiki->pid][$page]['today'] : 0;
			$counters[$this->xpwiki->pid][$page]['today']     = 1;
			$counters[$this->xpwiki->pid][$page]['total']++;
	
		} else if ($counters[$this->xpwiki->pid][$page]['ip'] != $_SERVER['REMOTE_ADDR']) {
			// Not the same host
			$modify = TRUE;
			$counters[$this->xpwiki->pid][$page]['ip']        = $_SERVER['REMOTE_ADDR'];
			$counters[$this->xpwiki->pid][$page]['today']++;
			$counters[$this->xpwiki->pid][$page]['total']++;
		}
		// Modify
		if ($modify && $this->root->vars['cmd'] == 'read') {
			rewind($fp);
			ftruncate($fp, 0);
			foreach (array_keys($default[$this->xpwiki->pid]) as $key)
				fputs($fp, $counters[$this->xpwiki->pid][$page][$key] . "\n");
		}
		flock($fp, LOCK_UN);
		fclose($fp);
	
		return $counters[$this->xpwiki->pid][$page];
	}
}
?>