<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: backup.inc.php,v 1.7 2007/10/26 02:00:58 nao-pon Exp $
// Copyright (C)
//   2002-2005 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// Backup plugin

class xpwiki_plugin_backup extends xpwiki_plugin {
	function plugin_backup_init () {
		// Prohibit rendering old wiki texts (suppresses load, transfer rate, and security risk)
		$this->cont['PLUGIN_BACKUP_DISABLE_BACKUP_RENDERING'] =  $this->cont['PKWK_SAFE_MODE'] || $this->cont['PKWK_OPTIMISE'];
	}
	
	function plugin_backup_action() {
	
		if (! $this->root->do_backup) return;
	
		$page = isset($this->root->vars['page']) ? $this->root->vars['page']  : '';
		if ($page === '') return array('msg'=>$this->root->_title_backuplist, 'body'=>$this->plugin_backup_get_list_all());
	
		$this->func->check_readable($page, true, true);
		$s_page = htmlspecialchars($page);
		$pgid = $this->func->get_pgid_by_name($page);
	
		$action = isset($this->root->vars['action']) ? $this->root->vars['action'] : '';
		if ($action === 'delete') return $this->plugin_backup_delete($page);
	
		$s_action = $r_action = '';
		if ($action != '') {
			$s_action = htmlspecialchars($action);
			$r_action = rawurlencode($action);
		}
		
		$script = $this->func->get_script_uri();
		
		$view_now = ($action === 'diff' || $action === 'source');
		
		$edit_icon = '<a href="' . $script . '?cmd=edit&amp;pgid=' . $pgid . '&amp;backup=$1" title="' . htmlspecialchars($this->root->_msg_backupedit) . '"><img src="' . $this->cont['IMAGE_DIR'] . 'edit.png" alt="' . htmlspecialchars($this->root->_msg_backupedit) . '" width="20" height="20" /></a>';

		
		$s_age = (isset($this->root->vars['age'])) ? $this->root->vars['age'] : 0;
		if ($view_now && ($s_age === 'Cur' || !$s_age)) {
			$s_age = 'Cur';
			$is_now = TRUE;
			$data_age = ($action === 'diff')? 'last' : 'none';
		} else {
			$s_age = intval($s_age);
			if (!$s_age) return array( 'msg'=>$this->root->_title_pagebackuplist, 'body'=>$this->plugin_backup_get_list($page));
			$is_now = FALSE;
			$data_age = $s_age;
			if ($action === 'diff') $data_age .= ',' . ($s_age - 1);
		}

		$backups = $this->func->get_backup($page, 0, $data_age);
		$backups_count = count($backups);

		if (!$is_now && ($s_age > $backups_count || !$s_age)) {
			return array( 'msg'=>$this->root->_title_pagebackuplist, 'body'=>$this->plugin_backup_get_list($page));
		}

		$body  = '<ul>' . "\n";
		if (!$is_now) $body .= ' <li><a href="' . $script . '?cmd=backup">' . $this->root->_msg_backuplist . '</a></li>' ."\n";

		$href    = $script . '?cmd=backup&amp;pgid=' . $pgid . '&amp;age=' . $s_age;
		$is_page = $this->func->is_page($page);
		$editable = $this->func->check_editable($page, FALSE, FALSE);
	
		if ($s_age && $is_page && $action != 'diff')
			$body .= ' <li>' . str_replace('$1', '<a href="' . $href .
			'&amp;action=diff">' . $this->root->_msg_diff . '</a>',
			$this->root->_msg_view) . '</li>' . "\n";
	
		if (is_numeric($s_age) && $is_page && $action != 'nowdiff')
			$body .= ' <li>' . str_replace('$1', '<a href="' . $href .
			'&amp;action=nowdiff">' . $this->root->_msg_nowdiff . '</a>',
			$this->root->_msg_view) . '</li>' . "\n";
	
		if ($s_age && $action != 'source')
			$body .= ' <li>' . str_replace('$1', '<a href="' . $href .
			'&amp;action=source">' . $this->root->_msg_source . '</a>',
			$this->root->_msg_view) . '</li>' . "\n";
	
		if (is_numeric($s_age) && ! $this->cont['PLUGIN_BACKUP_DISABLE_BACKUP_RENDERING'] && $action)
			$body .= ' <li>' . str_replace('$1', '<a href="' . $href .
			'">' . $this->root->_msg_backup . ' No.' . $s_age . '</a>',
			$this->root->_msg_view) . '</li>' . "\n";
	
		if (is_numeric($s_age) && $is_page && ($action === 'source' || !$action) && $editable)
			$body .= ' <li><a href="' . $script . '?cmd=edit&amp;pgid=' . $pgid . '&amp;backup=' . $s_age .
			'">' . str_replace('$1', $s_age, $this->root->_msg_backupedit) . '</a></li>' . "\n";

		if ($is_page) {
			$body .= ' <li>' . str_replace('$1',
			'<a href="' . $this->func->get_page_uri($page, true) . '">' . $s_page . '</a>',
			$this->root->_msg_goto) . "</li>\n";
		} else {
			$body .= ' <li>' . str_replace('$1', $s_page, $this->root->_msg_deleted) . "<li>\n";
		}
		$body .= '</ul>' . "\n";
		
		$header[0] = '';
		$list = '';
		$navi = '';
		if ($backups_count || $is_now) {
			// list
			$list .= '  <ul>' . "\n";
			foreach($backups as $age => $val) {
				$_name = '_title_backup' . $action;
				$title = $this->root->$_name;
				$s_title = htmlspecialchars(str_replace(array('$1', '$2'), array($page, $age), $title));
				$date = $this->func->format_date($val['time']);
				$lasteditor = $this->func->get_lasteditor($this->func->get_pginfo('',$val['data']));
				$list .= ($age == $s_age) ?
					'   <li><em>' . $age . ': ' . $date . ' ' . $lasteditor . '</em></li>' . "\n" :
					'   <li><a href="' . $script . '?cmd=backup&amp;action=' .
					$r_action . '&amp;pgid=' . $pgid . '&amp;age=' . $age .
					'" title="' . $s_title . '">' . $age . ': ' . $date . '</a> ' . $lasteditor . '</li>' . "\n";
				if ($age == $s_age) {
					$header[1] = $this->make_age_label($age, $date, $lasteditor);
					if ($editable) $header[1] .= ' ' . str_replace('$1', $age, $edit_icon);
				}
			}
			if ($view_now) {
				if ($action === 'diff') {
					$title = $this->root->_title_diff;
				} else if ($action === 'source') {
					$title = $this->root->_source_messages['msg_title'];
				} else {
					$title = '';
				}
				$s_title = htmlspecialchars(str_replace('$1', $page, $title));
				$date = $this->func->format_date($this->func->get_filetime($page));
				$lasteditor = $this->func->get_lasteditor($this->func->get_pginfo($page));
				$list .= ($is_now) ?
					'   <li><em>' . $this->root->_msg_current . ': ' . $date . ' ' . $lasteditor . '</em></li>' . "\n" :
					'   <li><a href="' . $script . '?cmd=backup&amp;action=' .
					$r_action . '&amp;pgid=' . $pgid . '&amp;age=Cur'.
					'" title="' . $s_title . '">' . $this->root->_msg_current . ': ' . $date . '</a> ' . $lasteditor . '</li>' . "\n";
				$list .= '  </ul>' . "\n";
				if ($is_now) {
					$header[1] = $this->make_age_label($this->root->_msg_current, $date, $lasteditor);
				}
			}
			
			// navi
			$navi_link = array('', '');
			$nav_href = $script . '?cmd=backup&amp;pgid=' . $pgid . '&amp;action=' . $action . '&amp;age=';
			if ($s_age > 1 || ($is_now && $backups_count)) {
				$age = $is_now? $backups_count : $s_age - 1;
				$date = $this->func->format_date($backups[$age]['time']);
				$lasteditor = $this->func->get_lasteditor($this->func->get_pginfo('',$backups[$age]['data']));
				$title = htmlspecialchars(strip_tags($this->make_age_label($age, $date, $lasteditor)));

				$navi_link[0] = '<a href="'.$nav_href . $age .'" title="' . $title . '">&#171; ' . $this->root->_navi_prev . '</a>';
			}
			if (!$is_now && ($s_age < $backups_count || $view_now)) {
				if ($s_age < $backups_count) {
					$age = $s_age + 1;
					$date = $this->func->format_date($backups[$age]['time']);
					$lasteditor = $this->func->get_lasteditor($this->func->get_pginfo('',$backups[$age]['data']));
				} else {
					$age = 'Cur';
					$date = $this->func->format_date($this->func->get_filetime($page));
					$lasteditor = $this->func->get_lasteditor($this->func->get_pginfo($page));
				}
				$title = htmlspecialchars(strip_tags($this->make_age_label($age, $date, $lasteditor)));
				$navi_link[1] = '<a href="'.$nav_href . $age .'" title="' . $title . '">' . $this->root->_navi_next . ' &#187;</a>';
			}
			$navi = '<div>' . $navi_link[0] . '&nbsp;&nbsp;' . $navi_link[1] .'</div>';
		}
		
		$body .= $navi;
		
		if ($action === 'diff') {
			if ($s_age > 1 || ($is_now && $backups_count)) {
				$val = $is_now ? $backups[$backups_count] : $backups[$s_age - 1];
				$old = $val['data'];
				$date = $this->func->format_date($val['time']);
				$lasteditor = $this->func->get_lasteditor($this->func->get_pginfo('',$val['data']));
				$age = $is_now? $backups_count : ($s_age - 1);
				$header[0] = $this->make_age_label($age, $date, $lasteditor);
				if ($editable) $header[0] .= ' ' . str_replace('$1', $age, $edit_icon);
			} else {
				$header[0] = '';
				$old = array();
			}
			if ($is_now) {
				$title = $this->root->_title_diff;
				$cur = $this->func->get_source($page);
			} else {
				$title = $this->root->_title_backupdiff;
				$cur = $backups[$s_age]['data'];
			}
			$old = $this->func->remove_pginfo($old);
			$cur = $this->func->remove_pginfo($cur);
			$body .= $this->func->compare_diff($old, $cur, $header);
		} else if ($action === 'nowdiff') {
			$title = $this->root->_title_backupnowdiff;
			$old = $backups[$s_age]['data'];
			$cur = $this->func->get_source($page);
			$header[0] = $header[1];
			$header[1] = $this->make_age_label($this->root->_msg_current, $this->func->format_date($this->func->get_filetime($page)), $this->func->get_lasteditor($this->func->get_pginfo($page)));
			$old = $this->func->remove_pginfo($old);
			$cur = $this->func->remove_pginfo($cur);
			$body .= $this->func->compare_diff($old, $cur, $header);
		} else if ($action === 'source') {
			if ($is_now) {
				$title = $this->root->_source_messages['msg_title'];
				$data = $this->func->get_source($page, TRUE, TRUE);
			} else {
				$title = $this->root->_title_backupsource;
				$data = join('', $backups[$s_age]['data']);
			}
			$sorce = htmlspecialchars($this->func->remove_pginfo($data));
			$body .=<<<EOD
<div class="edit_form">
 <form>
  <textarea id="xpwiki_backup_textarea" readonly="readonly" rows="{$this->root->rows}" cols="{$this->root->cols}">{$sorce}</textarea>
 </form>
 <script type="text/javascript"><!--
 document.observe("dom:loaded", function(){new Resizable('xpwiki_backup_textarea', {mode:'xy'});});
 document.write('<div style="float:right;font-size:80%;padding:3px;border:1px solid gray;cursor:pointer;" onmousedown="this.innerHTML=XpWiki.textaraWrap(\'xpwiki_backup_textarea\');">'+wikihelper_msg_nowrap+'</div>');
 //--></script>
</div>
EOD;
		} else {
			if ($this->cont['PLUGIN_BACKUP_DISABLE_BACKUP_RENDERING']) {
				$this->func->die_message('This feature is prohibited');
			} else {
				$title = & $this->root->_title_backup;
				$body .= $this->root->hr . "\n";
				
				$this->root->rtf['preview'] = TRUE;
				$src = join('', $backups[$s_age]['data']);
				$src = $this->func->make_str_rules($src);
				$src = explode("\n", $src);
				
				$body .= $this->func->drop_submit($this->func->convert_html($src));
			}
		}
		
		$body .= $navi;
		
		if ($list) {
			$href = $script . '?cmd=backup&amp;pgid=' . $pgid;
			$body .= '<hr style="clear:both;" />'. "\n";
			if ($backups_count) {
				$body .= '<ul><li><a href="'.$href.'">'. str_replace('$1', $s_page, $this->root->_title_pagebackuplist) . "</a>\n" . $list . '</li></ul>';
			} else {
				$body .= $list;
			}
		}
		
		return array('msg'=>str_replace('$2', $s_age, $title), 'body'=>$body);
	}
	
	// Delete backup
	function plugin_backup_delete($page) {
	
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
	
	function plugin_backup_diff($str) {
		$ul = <<<EOD
{$this->root->hr}
<ul>
 <li>{$this->root->_msg_addline}</li>
 <li>{$this->root->_msg_delline}</li>
</ul>
EOD;
	
		return $ul . '<pre>' . $this->func->diff_style_to_css(htmlspecialchars($str)) . '</pre>' . "\n";
	}
	
	function plugin_backup_get_list($page) {
		$script = $this->func->get_script_uri();
		$s_page = htmlspecialchars($page);
		$pgid = $this->func->get_pgid_by_name($page);
		$retval = array();
		$page_link = $this->func->make_pagelink($page);
		$retval[0] = <<<EOD
<ul>
 <li><a href="$script?cmd=backup">{$this->root->_msg_backuplist}</a></li>
 <li>$page_link
  <ul>
EOD;
		$retval[1] = "\n";
		$retval[2] = <<<EOD
  </ul>
 </li>
</ul>
EOD;
	
		$backups = $this->func->_backup_file_exists($page) ? $this->func->get_backup($page, 0, 'none') : array();
		if (empty($backups)) {
			$msg = str_replace('$1', $this->func->make_pagelink($page), $this->root->_msg_nobackup);
			$retval[1] .= '   <li>' . $msg . '</li>' . "\n";
			return join('', $retval);
		}
	
		if (! $this->cont['PKWK_READONLY']) {
 			$retval[1] .= '   <li><a href="' . $script . '?cmd=backup&amp;action=delete&amp;pgid=' . $pgid . '">';
			$retval[1] .= str_replace('$1', $s_page, $this->root->_title_backup_delete);
			$retval[1] .= '</a></li>' . "\n";
		}
	
		$href = $script . '?cmd=backup&amp;pgid=' . $pgid . '&amp;age=';
		$_anchor_from = $_anchor_to   = '';
		foreach ($backups as $age=>$data) {
			if (! $this->cont['PLUGIN_BACKUP_DISABLE_BACKUP_RENDERING']) {
				$_anchor_from = '<a href="' . $href . $age . '">';
				$_anchor_to   = '</a>';
			}
			$date = $this->func->format_date($data['time'], TRUE);
			$lasteditor = $this->func->get_lasteditor($this->func->get_pginfo('',$data['data']));
			$retval[1] .= <<<EOD
   <li>$_anchor_from$age $date$_anchor_to
     [ <a href="$href$age&amp;action=diff">{$this->root->_msg_diff}</a>
     | <a href="$href$age&amp;action=nowdiff">{$this->root->_msg_nowdiff}</a>
     | <a href="$href$age&amp;action=source">{$this->root->_msg_source}</a>
     ]
     $lasteditor
   </li>
EOD;
		}
		$date = $this->func->format_date($this->func->get_filetime($page), TRUE);
		$page_link = $this->func->make_pagelink($page, $this->root->_msg_current . ' ' . $date);
		$lasteditor = $this->func->get_lasteditor($this->func->get_pginfo($page));
		$retval[1] .= <<<EOD
   <li>$page_link
     [ <a href="{$href}Cur&amp;action=diff">{$this->root->_msg_diff}</a>
     | <a href="{$href}Cur&amp;action=source">{$this->root->_msg_source}</a>
     ]
     $lasteditor
   </li>
EOD;
		return join('', $retval);
	}
	
	// List for all pages
	function plugin_backup_get_list_all($withfilename = FALSE) {
		// 閲覧権限のないページを省く
		$pages = array_intersect($this->func->get_existpages($this->cont['BACKUP_DIR'], $this->cont['BACKUP_EXT']), $this->func->get_existpages(FALSE, "", array('nodelete' => FALSE)));
		
		$pages = array_diff($pages, $this->root->cantedit);

		if (empty($pages)) {
			return '';
		} else {
			return $this->func->page_list($pages, 'backup', $withfilename);
		}
	}
	
	function make_age_label($age, $date, $lasteditor) {
		return $age . ': ' . $date . ' <small>' . $lasteditor . '</small>';
	}
}
?>