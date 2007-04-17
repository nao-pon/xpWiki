<?php
class xpwiki_plugin_calendar2 extends xpwiki_plugin {
	function plugin_calendar2_init () {



	}
	// $Id: calendar2.inc.php,v 1.2 2007/04/17 23:40:39 nao-pon Exp $
	//
	// Calendar2 plugin
	//
	// Usage:
	//	#calendar2({[pagename|*],[yyyymm],[off]})
	//	off: Don't view today's
	
	function plugin_calendar2_convert()
	{
	//	global $script, $vars, $post, $get, $weeklabels, $WikiName, $BracketName;
	//	global $_calendar2_plugin_edit, $_calendar2_plugin_empty;
	
		$date_str = $this->func->get_date('Ym');
		$base     = $this->func->strip_bracket($this->root->vars['page']);
	
		$today_view = TRUE;
		if (func_num_args()) {
			$args = func_get_args();
			foreach ($args as $arg) {
				if (is_numeric($arg) && strlen($arg) == 6) {
					$date_str = $arg;
				} else if ($arg == 'off') {
					$today_view = FALSE;
				} else {
					$base = $this->func->strip_bracket($arg);
				}
			}
		}
		if ($base == '*') {
			$base   = '';
			$prefix = '';
		} else {
			$prefix = $base . '/';
		}
		$r_base   = rawurlencode($base);
		$s_base   = htmlspecialchars($base);
		$r_prefix = rawurlencode($prefix);
		$s_prefix = htmlspecialchars($prefix);
	
		$yr  = substr($date_str, 0, 4);
		$mon = substr($date_str, 4, 2);
		if ($yr != $this->func->get_date('Y') || $mon != $this->func->get_date('m')) {
			$now_day = 1;
			$other_month = 1;
		} else {
			$now_day = $this->func->get_date('d');
			$other_month = 0;
		}
	
		$today = getdate(mktime(0, 0, 0, $mon, $now_day, $yr) - $this->cont['LOCALZONE'] + $this->cont['ZONETIME']);
	
		$m_num = $today['mon'];
		$d_num = $today['mday'];
		$year  = $today['year'];
	
		$f_today = getdate(mktime(0, 0, 0, $m_num, 1, $year) - $this->cont['LOCALZONE'] + $this->cont['ZONETIME']);
		$wday = $f_today['wday'];
		$day  = 1;
	
		$m_name = $year . '.' . $m_num;
	
		$y = substr($date_str, 0, 4) + 0;
		$m = substr($date_str, 4, 2) + 0;
	
		$prev_date_str = ($m == 1) ?
			sprintf('%04d%02d', $y - 1, 12) : sprintf('%04d%02d', $y, $m - 1);
	
		$next_date_str = ($m == 12) ?
			sprintf('%04d%02d', $y + 1, 1) : sprintf('%04d%02d', $y, $m + 1);
	
		$ret = '';
		if ($today_view) {
			$ret = '<table border="0" summary="calendar frame">' . "\n" .
			' <tr>' . "\n" .
			'  <td valign="top">' . "\n";
		}
		$ret .= <<<EOD
   <table class="style_calendar" cellspacing="1" width="150" border="0" summary="calendar body">
    <tr>
     <td class="style_td_caltop" colspan="7">
      <a href="{$this->root->script}?plugin=calendar2&amp;file=$r_base&amp;date=$prev_date_str">&lt;&lt;</a>
      <strong>$m_name</strong>
      <a href="{$this->root->script}?plugin=calendar2&amp;file=$r_base&amp;date=$next_date_str">&gt;&gt;</a>
EOD;
	
		if ($prefix) $ret .= "\n" .
		'      <br />[<a href="' . $this->root->script . '?' . $r_base . '">' . $s_base . '</a>]';
	
		$ret .= "\n" .
		'     </td>' . "\n" .
		'    </tr>'  . "\n" .
		'    <tr>'   . "\n";
	
		foreach($this->root->weeklabels as $label)
			$ret .= '     <td class="style_td_week">' . $label . '</td>' . "\n";
	
		$ret .= '    </tr>' . "\n" .
		'    <tr>'  . "\n";
		// Blank
		for ($i = 0; $i < $wday; $i++)
			$ret .= '     <td class="style_td_blank">&nbsp;</td>' . "\n";
	
		while (checkdate($m_num, $day, $year)) {
			$dt     = sprintf('%4d-%02d-%02d', $year, $m_num, $day);
			$page   = $prefix . $dt;
			$r_page = rawurlencode($page);
			$s_page = htmlspecialchars($page);
	
			if ($wday == 0 && $day > 1)
				$ret .=
				'    </tr>' . "\n" .
			'    <tr>' . "\n";
	
			$style = 'style_td_day'; // Weekday
			if (! $other_month && ($day == $today['mday']) && ($m_num == $today['mon']) && ($year == $today['year'])) { // Today
				$style = 'style_td_today';
			} else if ($wday == 0) { // Sunday
				$style = 'style_td_sun';
			} else if ($wday == 6) { //  Saturday
				$style = 'style_td_sat';
			}
	
			if ($this->func->is_page($page)) {
				$link = '<a href="' . $this->root->script . '?' . $r_page . '" title="' . $s_page .
				'"><strong>' . $day . '</strong></a>';
			} else {
				if ($this->cont['PKWK_READONLY']) {
					$link = '<span class="small">' . $day . '</small>';
				} else {
					$link = $this->root->script . '?cmd=edit&amp;page=' . $r_page . '&amp;refer=' . $r_base;
					$link = '<a class="small" href="' . $link . '" title="' . $s_page . '">' . $day . '</a>';
				}
			}
	
			$ret .= '     <td class="' . $style . '">' . "\n" .
			'      ' . $link . "\n" .
			'     </td>' . "\n";
			++$day;
			$wday = ++$wday % 7;
		}
	
		if ($wday > 0)
			while ($wday++ < 7) // Blank
				$ret .= '     <td class="style_td_blank">&nbsp;</td>' . "\n";
	
		$ret .= '    </tr>'   . "\n" .
		'   </table>' . "\n";
	
		if ($today_view) {
			$tpage = $prefix . sprintf('%4d-%02d-%02d', $today['year'],
			$today['mon'], $today['mday']);
			$r_tpage = rawurlencode($tpage);
			if ($this->func->is_page($tpage)) {
				$_page = $this->root->vars['page'];
				$this->root->get['page'] = $this->root->post['page'] = $this->root->vars['page'] = $tpage;
				$str = $this->func->convert_html($this->func->get_source($tpage));
				$str .= '<hr /><a class="small" href="' . $this->root->script .
				'?cmd=edit&amp;page=' . $r_tpage . '">' .
				$this->root->_calendar2_plugin_edit . '</a>';
				$this->root->get['page'] = $this->root->post['page'] = $this->root->vars['page'] = $_page;
			} else {
				$str = sprintf($this->root->_calendar2_plugin_empty,
				$this->func->make_pagelink(sprintf('%s%4d-%02d-%02d', $prefix,
				$today['year'], $today['mon'], $today['mday'])));
			}
			$ret .= '  </td>' . "\n" .
			'  <td valign="top">' . $str . '</td>' . "\n" .
			' </tr>'   . "\n" .
			'</table>' . "\n";
		}
	
		return $ret;
	}
	
	function plugin_calendar2_action()
	{
	//	global $vars;
	
		$page = $this->func->strip_bracket($this->root->vars['page']);
		$this->root->vars['page'] = '*';
		if ($this->root->vars['file']) $this->root->vars['page'] = $this->root->vars['file'];
	
		$date = $this->root->vars['date'];
	
		if ($date == '') $date = $this->func->get_date('Ym');
		$yy = sprintf('%04d.%02d', substr($date, 0, 4),substr($date, 4, 2));
	
		$aryargs = array($this->root->vars['page'], $date, 'off');
		$s_page  = htmlspecialchars($this->root->vars['page']);
	
		$ret['msg']  = 'calendar ' . $s_page . '/' . $yy;
		$ret['body'] = call_user_func_array (array(& $this, "plugin_calendar2_convert"), $aryargs);

		$args_array = array($this->root->vars['page'], str_replace('.', '-', $yy), 'past', '-');
		$plugin = & $this->func->get_plugin_instance('calendar_viewer');
		$ret['body'] .= call_user_func_array (array(& $plugin, "plugin_calendar_viewer_convert"), $args_array);

		$this->root->vars['page'] = $page;
	
		return $ret;
	}
}
?>