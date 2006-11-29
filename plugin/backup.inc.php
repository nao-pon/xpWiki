<?php
class xpwiki_plugin_backup extends xpwiki_plugin {
	function plugin_backup_init () {


	// PukiWiki - Yet another WikiWikiWeb clone.
	// $Id: backup.inc.php,v 1.2 2006/11/29 13:09:05 nao-pon Exp $
	// Copyright (C)
	//   2002-2005 PukiWiki Developers Team
	//   2001-2002 Originally written by yu-ji
	// License: GPL v2 or (at your option) any later version
	//
	// Backup plugin
	
	// Prohibit rendering old wiki texts (suppresses load, transfer rate, and security risk)
		$this->cont['PLUGIN_BACKUP_DISABLE_BACKUP_RENDERING'] =  $this->cont['PKWK_SAFE_MODE'] || $this->cont['PKWK_OPTIMISE'];

	}
	
	function plugin_backup_action()
	{
	//	global $vars, $do_backup, $hr;
	//	global $_msg_backuplist, $_msg_diff, $_msg_nowdiff, $_msg_source, $_msg_backup;
	//	global $_msg_view, $_msg_goto, $_msg_deleted;
	//	global $_title_backupdiff, $_title_backupnowdiff, $_title_backupsource;
	//	global $_title_backup, $_title_pagebackuplist, $_title_backuplist;
	
		if (! $this->root->do_backup) return;
	
		$page = isset($this->root->vars['page']) ? $this->root->vars['page']  : '';
		if ($page == '') return array('msg'=>$this->root->_title_backuplist, 'body'=>$this->plugin_backup_get_list_all());
	
		$this->func->check_readable($page, true, true);
		$s_page = htmlspecialchars($page);
		$r_page = rawurlencode($page);
	
		$action = isset($this->root->vars['action']) ? $this->root->vars['action'] : '';
		if ($action == 'delete') return $this->plugin_backup_delete($page);
	
		$s_action = $r_action = '';
		if ($action != '') {
			$s_action = htmlspecialchars($action);
			$r_action = rawurlencode($action);
		}
	
		$s_age  = (isset($this->root->vars['age']) && is_numeric($this->root->vars['age'])) ? $this->root->vars['age'] : 0;
		if ($s_age <= 0) return array( 'msg'=>$this->root->_title_pagebackuplist, 'body'=>$this->plugin_backup_get_list($page));
	
		$script = $this->func->get_script_uri();
	
		$body  = '<ul>' . "\n";
		$body .= ' <li><a href="' . $script . '?cmd=backup">' . $this->root->_msg_backuplist . '</a></li>' ."\n";
	
		$href    = $script . '?cmd=backup&amp;page=' . $r_page . '&amp;age=' . $s_age;
		$is_page = $this->func->is_page($page);
	
		if ($is_page && $action != 'diff')
			$body .= ' <li>' . str_replace('$1', '<a href="' . $href .
			'&amp;action=diff">' . $this->root->_msg_diff . '</a>',
			$this->root->_msg_view) . '</li>' . "\n";
	
		if ($is_page && $action != 'nowdiff')
			$body .= ' <li>' . str_replace('$1', '<a href="' . $href .
			'&amp;action=nowdiff">' . $this->root->_msg_nowdiff . '</a>',
			$this->root->_msg_view) . '</li>' . "\n";
	
		if ($action != 'source')
			$body .= ' <li>' . str_replace('$1', '<a href="' . $href .
			'&amp;action=source">' . $this->root->_msg_source . '</a>',
			$this->root->_msg_view) . '</li>' . "\n";
	
		if (! $this->cont['PLUGIN_BACKUP_DISABLE_BACKUP_RENDERING'] && $action)
			$body .= ' <li>' . str_replace('$1', '<a href="' . $href .
			'">' . $this->root->_msg_backup . '</a>',
			$this->root->_msg_view) . '</li>' . "\n";
	
		if ($is_page) {
			$body .= ' <li>' . str_replace('$1',
			'<a href="' . $script . '?' . $r_page . '">' . $s_page . '</a>',
			$this->root->_msg_goto) . "\n";
		} else {
			$body .= ' <li>' . str_replace('$1', $s_page, $this->root->_msg_deleted) . "\n";
		}
	
		$backups = $this->func->get_backup($page);
		$backups_count = count($backups);
		if ($s_age > $backups_count) $s_age = $backups_count;
	
		if ($backups_count > 0) {
			$body .= '  <ul>' . "\n";
			foreach($backups as $age => $val) {
				$date = $this->func->format_date($val['time'], TRUE);
				$body .= ($age == $s_age) ?
					'   <li><em>' . $age . ' ' . $date . '</em></li>' . "\n" :
					'   <li><a href="' . $script . '?cmd=backup&amp;action=' .
				$r_action . '&amp;page=' . $r_page . '&amp;age=' . $age .
				'">' . $age . ' ' . $date . '</a></li>' . "\n";
			}
			$body .= '  </ul>' . "\n";
		}
		$body .= ' </li>' . "\n";
		$body .= '</ul>'  . "\n";
	
		if ($action == 'diff') {
			$title = & $this->root->_title_backupdiff;
			$old = ($s_age > 1) ? join('', $backups[$s_age - 1]['data']) : '';
			$cur = join('', $backups[$s_age]['data']);
			$body .= $this->plugin_backup_diff($this->func->do_diff($old, $cur));
		} else if ($s_action == 'nowdiff') {
			$title = & $this->root->_title_backupnowdiff;
			$old = join('', $backups[$s_age]['data']);
			$cur = join('', $this->func->get_source($page));
			$body .= $this->plugin_backup_diff($this->func->do_diff($old, $cur));
		} else if ($s_action == 'source') {
			$title = & $this->root->_title_backupsource;
			$body .= '<pre>' . htmlspecialchars(join('', $backups[$s_age]['data'])) .
			'</pre>' . "\n";
		} else {
			if ($this->cont['PLUGIN_BACKUP_DISABLE_BACKUP_RENDERING']) {
				$this->func->die_message('This feature is prohibited');
			} else {
				$title = & $this->root->_title_backup;
				$body .= $this->root->hr . "\n" .
				$this->func->drop_submit($this->func->convert_html($backups[$s_age]['data']));
			}
		}
	
		return array('msg'=>str_replace('$2', $s_age, $title), 'body'=>$body);
	}
	
	// Delete backup
	function plugin_backup_delete($page)
	{
	//	global $vars, $_title_backup_delete, $_title_pagebackuplist, $_msg_backup_deleted;
	//	global $_msg_backup_adminpass, $_btn_delete, $_msg_invalidpass;
	
		if (! $this->func->_backup_file_exists($page))
			return array('msg'=>$this->root->_title_pagebackuplist, 'body'=>$this->plugin_backup_get_list($page)); // Say "is not found"

		$body = '';
		if (isset($this->root->vars['pass'])) {
			if ($this->func->pkwk_login($this->root->vars['pass'])) {
				$this->func->_backup_delete($page);
				return array(
					'msg'  => $this->root->_title_backup_delete,
				'body' => str_replace('$1', $this->func->make_pagelink($page), $this->root->_msg_backup_deleted)
				);
			} else {
				$body = '<p><strong>' . $this->root->_msg_invalidpass . '</strong></p>' . "\n";
			}
		}
	
		$script = $this->func->get_script_uri();
		$s_page = htmlspecialchars($page);
		$body .= <<<EOD
<p>{$this->root->_msg_backup_adminpass}</p>
<form action="$script" method="post">
 <div>
  <input type="hidden"   name="cmd"    value="backup" />
  <input type="hidden"   name="page"   value="$s_page" />
  <input type="hidden"   name="action" value="delete" />
  <input type="password" name="pass"   size="12" />
  <input type="submit"   name="ok"     value="{$this->root->_btn_delete}" />
 </div>
</form>
EOD;
		return	array('msg'=>$this->root->_title_backup_delete, 'body'=>$body);
	}
	
	function plugin_backup_diff($str)
	{
	//	global $_msg_addline, $_msg_delline, $hr;
		$ul = <<<EOD
{$this->root->hr}
<ul>
 <li>{$this->root->_msg_addline}</li>
 <li>{$this->root->_msg_delline}</li>
</ul>
EOD;
	
		return $ul . '<pre>' . $this->func->diff_style_to_css(htmlspecialchars($str)) . '</pre>' . "\n";
	}
	
	function plugin_backup_get_list($page)
	{
	//	global $_msg_backuplist, $_msg_diff, $_msg_nowdiff, $_msg_source, $_msg_nobackup;
	//	global $_title_backup_delete;
	
		$script = $this->func->get_script_uri();
		$r_page = rawurlencode($page);
		$s_page = htmlspecialchars($page);
		$retval = array();
		$retval[0] = <<<EOD
<ul>
 <li><a href="$script?cmd=backup">{$this->root->_msg_backuplist}</a>
  <ul>
EOD;
		$retval[1] = "\n";
		$retval[2] = <<<EOD
  </ul>
 </li>
</ul>
EOD;
	
		$backups = $this->func->_backup_file_exists($page) ? $this->func->get_backup($page) : array();
		if (empty($backups)) {
			$msg = str_replace('$1', $this->func->make_pagelink($page), $this->root->_msg_nobackup);
			$retval[1] .= '   <li>' . $msg . '</li>' . "\n";
			return join('', $retval);
		}
	
		if (! $this->cont['PKWK_READONLY']) {
			$retval[1] .= '   <li><a href="' . $script . '?cmd=backup&amp;action=delete&amp;page=' .
			$r_page . '">';
			$retval[1] .= str_replace('$1', $s_page, $this->root->_title_backup_delete);
			$retval[1] .= '</a></li>' . "\n";
		}
	
		$href = $script . '?cmd=backup&amp;page=' . $r_page . '&amp;age=';
		$_anchor_from = $_anchor_to   = '';
		foreach ($backups as $age=>$data) {
			if (! $this->cont['PLUGIN_BACKUP_DISABLE_BACKUP_RENDERING']) {
				$_anchor_from = '<a href="' . $href . $age . '">';
				$_anchor_to   = '</a>';
			}
			$date = $this->func->format_date($data['time'], TRUE);
			$retval[1] .= <<<EOD
   <li>$_anchor_from$age $date$_anchor_to
     [ <a href="$href$age&amp;action=diff">{$this->root->_msg_diff}</a>
     | <a href="$href$age&amp;action=nowdiff">{$this->root->_msg_nowdiff}</a>
     | <a href="$href$age&amp;action=source">{$this->root->_msg_source}</a>
     ]
   </li>
EOD;
		}
	
		return join('', $retval);
	}
	
	// List for all pages
	function plugin_backup_get_list_all($withfilename = FALSE)
	{
		// 閲覧権限のないページを省く
		$pages = array_intersect($this->func->get_existpages($this->cont['BACKUP_DIR'], $this->cont['BACKUP_EXT']), $this->func->get_existpages(FALSE, "", 0, "", FALSE, FALSE, FALSE));
		
		$pages = array_diff($pages, $this->root->cantedit);

		if (empty($pages)) {
			return '';
		} else {
			return $this->func->page_list($pages, 'backup', $withfilename);
		}
	}
}
?>