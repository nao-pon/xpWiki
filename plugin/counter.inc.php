<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: counter.inc.php,v 1.6 2008/05/14 07:16:41 nao-pon Exp $
// Copyright (C)
//   2002-2005 PukiWiki Developers Team
//   2002 Y.MASUI GPL2 http://masui.net/pukiwiki/ masui@masui.net
// License: GPL2
//
// Counter plugin

class xpwiki_plugin_counter extends xpwiki_plugin {
	function plugin_counter_init () {

	// Counter file's suffix
		$this->cont['PLUGIN_COUNTER_SUFFIX'] =  '.count';

	}
	
	// Report one
	function plugin_counter_inline()
	{
		// BugTrack2/106: Only variables can be passed by reference from PHP 5.0.5
		$args = func_get_args(); // with array_shift()
	
		$arg = strtolower(array_shift($args));
		switch ($arg) {
		case ''     : $arg = 'total'; /*FALLTHROUGH*/
		case 'total': /*FALLTHROUGH*/
		case 'today': /*FALLTHROUGH*/
		case 'yesterday':
			$res = array();
			if ($this->root->render_mode === 'block' && isset($GLOBALS['Xpwiki_'.$this->root->mydirname]['page'])) {
				$res = $this->func->set_current_page($GLOBALS['Xpwiki_'.$this->root->mydirname]['page']);
				$this->root->pagecache_min = 0;
			}
			$counter = $this->plugin_counter_get_count($this->root->vars['page']);
			if ($res) $this->func->set_current_page($res['page']);
			return $counter[$arg];
		default:
			return '&counter([total|today|yesterday]);';
		}
	}
	
	// Report all
	function plugin_counter_convert()
	{
		$res = array();
		if ($this->root->render_mode === 'block' && isset($GLOBALS['Xpwiki_'.$this->root->mydirname]['page'])) {
			$res = $this->func->set_current_page($GLOBALS['Xpwiki_'.$this->root->mydirname]['page']);
			$this->root->pagecache_min = 0;
		}

		$counter = $this->plugin_counter_get_count($this->root->vars['page']);
		
		if ($res) $this->func->set_current_page($res['page']);
		
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
		static $counters = array();
		if (!isset($counters[$this->xpwiki->pid])) {$counters[$this->xpwiki->pid] = array();}
		static $default = array();
		if (!isset($default[$this->xpwiki->pid]))
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
		$writed = $modify = FALSE;
	
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
		if ($counters[$this->xpwiki->pid][$page]['date'] !== $default[$this->xpwiki->pid]['date']) {
			// New day
			$modify = TRUE;
			$is_yesterday = ($counters[$this->xpwiki->pid][$page]['date'] === $this->func->get_date('Y/m/d', strtotime('yesterday', $this->cont['UTC'])));
			$counters[$this->xpwiki->pid][$page]['ip']        = $_SERVER['REMOTE_ADDR'];
			$counters[$this->xpwiki->pid][$page]['date']      = $default[$this->xpwiki->pid]['date'];
			$counters[$this->xpwiki->pid][$page]['yesterday'] = $is_yesterday ? $counters[$this->xpwiki->pid][$page]['today'] : 0;
			$counters[$this->xpwiki->pid][$page]['today']     = 1;
			$counters[$this->xpwiki->pid][$page]['total']++;
	
		} else if ($counters[$this->xpwiki->pid][$page]['ip'] !== $_SERVER['REMOTE_ADDR']) {
			// Not the same host
			$modify = TRUE;
			$counters[$this->xpwiki->pid][$page]['ip']        = $_SERVER['REMOTE_ADDR'];
			$counters[$this->xpwiki->pid][$page]['today']++;
			$counters[$this->xpwiki->pid][$page]['total']++;
		}
		// Modify
		if ($modify && $this->root->vars['cmd'] === 'read') {
			rewind($fp);
			ftruncate($fp, 0);
			foreach (array_keys($default[$this->xpwiki->pid]) as $key) {
				fputs($fp, $counters[$this->xpwiki->pid][$page][$key] . "\n");
				$$key = $counters[$this->xpwiki->pid][$page][$key];
			}
			$writed = TRUE;
		}
		fclose($fp);
		
		// DB¤ò¹¹¿·
		if ($writed && isset($total)) {
			$pgid = $this->func->get_pgid_by_name($page);
			
			$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_count")." SET `count`='$total',`today`='$date',`today_count`='$today',`yesterday_count`='$yesterday',`ip`='$ip' WHERE `pgid`='$pgid' LIMIT 1";
			$result=$this->xpwiki->db->queryF($query);
			
			if (!mysql_affected_rows())
			{
				$query = "INSERT INTO ".$this->xpwiki->db->prefix($this->root->mydirname."_count")." (`pgid`,`count`,`today`,`today_count`,`yesterday_count`,`ip`) VALUES('$pgid','$total','$date','$today','$yesterday','$ip')";
				$result=$this->xpwiki->db->queryF($query);
			}
		}
	
		return $counters[$this->xpwiki->pid][$page];
	}
}
?>