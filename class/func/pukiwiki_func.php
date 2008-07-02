<?php
//
// Created on 2006/10/02 by nao-pon http://hypweb.net/
// $Id: pukiwiki_func.php,v 1.180 2008/07/02 23:59:46 nao-pon Exp $
//
class XpWikiPukiWikiFunc extends XpWikiBaseFunc {

//----- Start file.php -----//
	
	
	// Get source(wiki text) data of the page
	function get_source($page = NULL, $lock = TRUE, $join = FALSE)
	{
		//$result = NULL;	// File is not found
		$result = $join ? '' : array();
			// Compat for "implode('', get_source($file))",
			// 	-- this is slower than "get_source($file, TRUE, TRUE)"
			// Compat for foreach(get_source($file) as $line) {} not to warns
	
		$path = $this->get_filename($page);
		if (file_exists($path)) {
	
			if ($lock) {
				$fp = @fopen($path, 'r');
				if ($fp === FALSE) return FALSE;
				flock($fp, LOCK_SH);
			}
	
			if ($join) {
				// Returns a value
				$size = filesize($path);
				if ($size === FALSE) {
					$result = FALSE;
				} else if ($size === 0) {
					$result = '';
				} else {
					if (is_int($join)) { $size = min($join, $size); }
					$result = fread($fp, $size);
					if ($result !== FALSE) {
						// Removing line-feeds
						$result = str_replace("\r", '', $result);
					}
				}
			} else {
				// Returns an array
				$result = file($path);
				if ($result !== FALSE) {
					// Removing line-feeds
					$result = str_replace("\r", '', $result);
				}
				
				// pginfo取得(キャッシュさせる)
				#freeze があるかもしれないので先頭の2行で判定
				$this->get_pginfo($page, array_slice($result,0,2));
			}
	
			if ($lock) {
				@fclose($fp);
			}
		}
	
		return $result;
	}
	
	// Get last-modified filetime of the page
	function get_filetime($page)
	{
		return $this->is_page($page) ? filemtime($this->get_filename($page)) - $this->cont['LOCALZONE'] : 
			$this->is_page($this->get_pagename_realcase($page)) ? filemtime($this->get_filename($page)) - $this->cont['LOCALZONE'] : 0;
	}
	
	// Get physical file name of the page
	function get_filename($page)
	{
		return $this->cont['DATA_DIR'] . $this->encode($page) . '.txt';
	}
	
	// Put a data(wiki text) into a physical file(diff, backup, text)
	function page_write($page, $postdata, $notimestamp = FALSE)
	{
		if (
			$this->cont['PKWK_READONLY']
			||
			(
			empty($this->root->rtf['no_checkauth_on_write']) &&
				(
				! $this->check_readable_page($page, FALSE, FALSE)
				||
				($this->root->plugin_follow_freeze && $this->is_freeze($page))
				||
				($this->root->plugin_follow_editauth && ! $this->check_editable_page($page, FALSE, FALSE))
				)
			)
		) {
			return; // Do nothing
		}
		
		$postdata = trim($postdata, "\r\n");
		
		if (! $this->root->plugin_follow_freeze) {
			// remove "#freeze"
			$postdata = preg_replace('/^#freeze\n/', '', $postdata);
		}
		
		$empty_page_making = FALSE;
		// set mode. use at custum events.
		if (!$this->is_page($page) && $postdata) {
			$mode = "insert";
			// Empty page making by plugin.
			if ($postdata === "\t") {
				$postdata = '';
				$empty_page_making = TRUE;
			}
		} else if ($postdata === '') {
			$mode = "delete";
			$this->root->post['alias'] = '';
		} else {
			$mode = "update";
		}

		// onPageWriteBefore
		$this->do_onPageWriteBefore ($page, $postdata, $notimestamp, $mode);

		$postdata = $this->make_str_rules($postdata);
		$oldpostdata = $this->is_page($page) ? $this->get_source($page, TRUE, TRUE) : '';
		
		// Page aliases
		$need_autolink_update = false;
		if (isset($this->root->post['alias'])) {
			$need_autolink_update = $this->put_page_alias($page, $this->root->post['alias']);
		}
		
		if ($postdata) {
			$reading = (!empty($this->root->vars['reading']) && $this->get_page_reading($page) !== $this->root->vars['reading'])? $this->root->vars['reading'] : '';
			$rm_postdata = $this->remove_pginfo($postdata);
			// Page order
			$pgorder = NULL;
			if (isset($this->root->post['pgorder'])) {
				$pgorder = min(9, max(0, floatval($this->root->post['pgorder'])));
				if ($this->get_page_order($page) === $pgorder) $pgorder = NULL;
			}
			if ($mode === 'update') {
				// ページの内容変更がない場合何もしない
				if (!$reading && is_null($pgorder)
					&& rtrim($rm_postdata, "\r\n") === rtrim($this->remove_pginfo($oldpostdata), "\r\n")) {
					// For AutoLink
					if ($need_autolink_update){
						$this->autolink_dat_update();
						$this->delete_caches();
					}
					return;
				}
			}
			// ページ情報
			if (!($mode === 'insert' && !$rm_postdata)) {
				$pginfo = $this->get_pginfo($page);
				if ($mode === 'insert') {
					if ($pginfo['eaids'] === $this->root->userinfo['uid']) {
						$pginfo['eaids'] = 'none';
					}
					if ($pginfo['vaids'] === $this->root->userinfo['uid']) {
						$pginfo['vaids'] = 'none';
					}
				}
				$pginfo['lastuid'] = $this->root->userinfo['uid'];
				$pginfo['lastucd'] = $this->root->userinfo['ucd'];
				$pginfo['lastuname'] = $this->root->userinfo['uname'];
				if ($this->root->userinfo['uname'] !== $this->root->cookie['name'] && $this->root->cookie['name']) {
					$pginfo['lastuname'] = $this->root->cookie['name'].'('.$pginfo['lastuname'].')';
				}
				$pginfo['lastuname'] = htmlspecialchars($pginfo['lastuname']);
				if (! is_null($pgorder)) $pginfo['pgorder'] = $pgorder;
				$pginfo_str = '#pginfo('.join("\t",$pginfo).')'."\n";
				$postdata = $pginfo_str . $rm_postdata;
				// ページ頭文字読み
				if ($reading) {
					$pginfo['reading'] = $reading;
				}
				if (! $this->root->plugin_follow_freeze) {
					// Is freeze?
					if ($this->is_freeze($page)) {
						$postdata = '#freeze' . "\n" . $postdata; 
					}
				}
			}
		}
		
		$_user_abort_last = ignore_user_abort(TRUE);
		
		// Create and write diff
		$diffdata = $this->do_diff($oldpostdata, $postdata);
		$this->file_write($this->cont['DIFF_DIR'], $page, $diffdata);

		// delete recent add data
		if ($mode === 'delete') { 
			$this->push_page_changes($page, '', TRUE);
			// Update RecentDeleted (Add the $page)
			$this->add_recent($page, $this->root->whatsdeleted, '', $this->root->maxshow_deleted);
		} else {
			// 追加履歴保存
			$this->push_page_changes($page, preg_replace('/^[^+].*\n/m', '', $diffdata));
		}
		
		// Create backup
		$this->make_backup($page, ($mode === 'delete'), $notimestamp); // Is $postdata null?
	
		// Create wiki text
		$this->file_write($this->cont['DATA_DIR'], $page, $postdata, $notimestamp);
		
		// Clear fstat cache.
		clearstatcache();
		
		// pginfo DB write
		if (empty($pginfo)) {
			$pginfo = $this->get_pginfo($page);
		}
		$this->pginfo_db_write($page, $mode, $pginfo, $notimestamp);
		
		if (! $empty_page_making) {
			/*
			if ($this->root->trackback) {
				// TrackBack Ping
				$_diff = explode("\n", $diffdata);
				$plus  = join("\n", preg_replace('/^\+/', '', preg_grep('/^\+/', $_diff)));
				$minus = join("\n", preg_replace('/^-/',  '', preg_grep('/^-/',  $_diff)));
				$this->tb_send($page, $plus, $minus);
			}
			*/

			// For AutoLink -> moved to "plain_db_write()"
			/*
			if ($need_autolink_update || $mode !== 'update'){
				$this->autolink_dat_update();
			}
			*/
			
			// Update autoalias.dat (AutoAliasName)
			if ($this->root->autoalias && $page === $this->root->aliaspage) {
				$aliases = $this->get_autoaliases();
				if (empty($aliases)) {
					// Remove
					@unlink($this->cont['CACHE_DIR'] . $this->cont['PKWK_AUTOALIAS_REGEX_CACHE']);
				} else {
					// Create or Update
					$this->autolink_pattern_write($this->cont['CACHE_DIR'] . $this->cont['PKWK_AUTOALIAS_REGEX_CACHE'],
						$this->get_autolink_pattern(array_keys($aliases), $this->root->autoalias, true));
				}
			}
			
			// Update interwiki.dat
			if ($this->root->interwiki && $page === $this->root->interwiki) {
				$this->interwiki_dat_update(explode("\n", $postdata));
			}
			
			// onPageWriteAfter
			$this->do_onPageWriteAfter($page, $postdata, $notimestamp, $mode, $diffdata);

			// 更新通知メール
			$diff_compact = preg_replace('/^[^+-].*\n/m', '', $diffdata);
			if ($this->root->notify) {
				$footer['ACTION'] = 'Page update';
				$footer['PAGE']   = $page;
				$footer['USER_AGENT']  = TRUE;
				$footer['REMOTE_ADDR'] = TRUE;
				$this->pkwk_mail_notify(
					$this->root->notify_subject,
					$this->get_page_uri($page, true) . "\n\n" . ($this->root->notify_diff_only? $diff_compact : $diffdata),
					$footer
				);
			}
			
			// System notification
			$tags['POST_URL'] = $this->get_page_uri($page, true);
			$tags['PAGE_NAME'] = $page;
			$tags['POST_DATA'] = $postdata;
			$tags['POSTER_NAME'] = $this->root->userinfo['uname'];
			$tags['POST_DIFF'] = preg_replace('/^/m', ' ', $diff_compact);
			if ($mode === 'insert') {
				$tags['POST_DIFF'] = 'New page.';
			} else if ($mode === 'update') {

			} else	if ($mode === 'delete') {
				$tags['POST_DATA'] = 'Page deleted.';
			}
			$this->system_notification($page, 'page', $this->get_pgid_by_name($page), 'page_update', $tags);
			
			list($pgid1, $pgid2) = $this->get_pgids_by_name($page);
			$this->system_notification($page, 'page1', $pgid1, 'page_update', $tags);
			if ($pgid2) $this->system_notification($page, 'page2', $pgid2, 'page_update', $tags);
			
			$this->system_notification($page, 'global', 0, 'page_update', $tags);
		}
		
		ignore_user_abort($_user_abort_last);
	}
	
	function autolink_dat_update () {
		if (!$this->root->autolink) return;
		
		// Get WHOLE page list (always as guest)
		$pages = $this->get_existpages(FALSE, '', array('asguest' => TRUE));
		
		$this->autolink_pattern_write($this->cont['CACHE_DIR'] . $this->cont['PKWK_AUTOLINK_REGEX_CACHE'],
			$this->get_autolink_pattern($pages, $this->root->autolink, false));
	}

	function interwiki_dat_update ($lines) {
		// Set default item
		$interwikinames['cmd'] = array('./?cmd=', '');
		foreach ($lines as $line) {
			if (preg_match('/\[(' . '(?:(?:https?|ftp|news):\/\/|\.\.?\/)' .
			 '[^\s]*)\s([^\]]+)\]\s*([^\s]*)/',
			 //'[!~*\'();\/?:\@&=+\$,%#\w.-]*)\s([^\]]+)\]\s?([^\s]*)/',
			 $line, $matches)) {
				$interwikinames[$matches[2]] = array($matches[1], $matches[3]);
			}
		}
		// Update
		$fp = fopen($this->cont['CACHE_DIR'] . 'interwiki.dat', 'wb') or
				$this->die_message('Cannot open ' . 'interwiki.dat');
		stream_set_write_buffer($fp, 0);
		flock($fp, LOCK_EX);
		rewind($fp);
		fwrite($fp, serialize($interwikinames));
		fclose($fp);
		
		return $interwikinames;
	}
	
	function delete_caches () {
		if (!empty($GLOBALS['xpwiki_cache_deletes'])) {
			foreach($GLOBALS['xpwiki_cache_deletes'] as $dir => $targets) {
				if ($dir_h = @opendir($dir)) {
					$pats = array();					
					foreach($targets as $target) {
						if ($target === '*') {
							$pats = array('true');
							break;
						}
						if ($target{0} === '*') {
							$target = substr($target, 1);
							$pats[] = '(substr($file, '.(strlen($target) * -1).') === \''.$target.'\')';
						} else if (substr($target, -1, 1) === '*') {
							$target = substr($target, 0, (strlen($target) - 1));
							$pats[] = '(substr($file, 0, '.strlen($target).') === \''.$target.'\')';
						} else {
							$pats[] = '($file === \''.$target.'\')';
						}
					}
					$func = create_function('$file', 'return ('.join(' || ', $pats).');');
					while($file = readdir($dir_h)) {
						if ($func($file)) {
							if ($file{0} !== '.') unlink($dir . $file);
						}
					}
					closedir($dir_h);
				}
			}
		}
		$GLOBALS['xpwiki_cache_deletes'] = array();
		
		if (!empty($GLOBALS['xpwiki_cache_reflash_functions'])) {
			foreach($GLOBALS['xpwiki_cache_reflash_functions'] as $function) {
				if (isset($function['name'])) {
					if (!isset($function['arg'])) $function['arg'] = '';
					call_user_func($function['name'], $function['arg']);
				}
			}
		}
		$GLOBALS['xpwiki_cache_reflash_functions'] = array();
	}
	
	// Modify original text with user-defined / system-defined rules
	function make_str_rules($source)
	{
		if (! $source) return $source;
		$this->escape_multiline_pre($source, TRUE);
		$lines = explode("\n", $source);
		$count = count($lines);
	
		$modify    = TRUE;
		$multiline = 0;
		$matches   = array();
		for ($i = 0; $i < $count; $i++) {
			$line = & $lines[$i]; // Modify directly
	
			// Ignore null string and preformatted texts
			if ($line === '' || $line{0} === ' ' || $line{0} === "\t") continue;
	
			// Modify this line?
			if ($modify) {
				if (! $this->cont['PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK'] &&
				    $multiline === 0 &&
				    preg_match('/^#[^{]+(\{\{+)\s*$/', $line, $matches)) {
				    	// Multiline convert plugin start
					$modify    = FALSE;
					$multiline = strlen($matches[1]); // Set specific number
				}
			} else {
				if (! $this->cont['PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK'] &&
				    $multiline !== 0 &&
				    preg_match('/^\}{' . $multiline . '}\s*$/', $line)) {
				    	// Multiline convert plugin end
					$modify    = TRUE;
					$multiline = 0;
				}
			}
			//if ($modify === FALSE) continue;
	
			// Replace with $str_rules
			foreach ($this->root->str_rules as $pattern => $replacement)
				$line = preg_replace('/' . $pattern . '/', $replacement, $line);
			
			// Adding fixed anchor into headings
			if ($this->root->fixed_heading_anchor &&
			    preg_match('/^(\*{1,5}.*?)(?:\[#([A-Za-z][\w-]*)\]\s*)?$/', $line, $matches) &&
			    (! isset($matches[2]) || $matches[2] === '')) {
				// Generate unique id
				$anchor = $this->generate_fixed_heading_anchor_id($matches[1]);
				$line = rtrim($matches[1]) . ' [#' . $anchor . ']';
			}
			
			// ref プラグインアップロード用ID
			$anchor = '';
			$line = preg_replace('/((?:&|#)ref\()UNQ_[\d]{17}/', '$1', $line);
			while(preg_match('/(?:&|#)ref\((,[^)]+)?\);?/',$line)) {
				$anchor = $this->generate_fixed_heading_anchor_id($line.$anchor);
				$line = preg_replace('/((?:&|#)ref\()((,[^)]+)?\);?)/',"$1ID\$".$anchor."$2",$line,1);	
			}
			if ($this->root->easy_ref_syntax) {
				while(preg_match('/\{\{((?:,|\|).*?)?\}\}/',$line)) {
					$anchor = $this->generate_fixed_heading_anchor_id($line.$anchor);
					$line = preg_replace('/\{\{((?:,|\|).*?)?\}\}/',"{{ID\$".$anchor."$1}}",$line,1);	
				}
			}
		}
	
		// Multiline part has no stopper
		if (! $this->cont['PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK'] &&
		    $modify === FALSE && $multiline !== 0)
			$lines[] = str_repeat('}', $multiline);
	
		$lines = implode("\n", $lines);
		$this->escape_multiline_pre($lines, FALSE);
		return $lines;
	}
	
	// Generate ID
	function generate_fixed_heading_anchor_id($seed)
	{
		// A random alphabetic letter + 7 letters of random strings from md()
		return chr(mt_rand(ord('a'), ord('z'))) .
			substr(md5(uniqid(substr($seed, 0, 100), TRUE)),
			mt_rand(0, 24), 7);
	}
	
	// Read top N lines as an array
	// (Use PHP file() function if you want to get ALL lines)
	function file_head($file, $count = 1, $lock = TRUE, $buffer = 8192)
	{
		$array = array();
	
		$fp = @fopen($file, 'r');
		if ($fp === FALSE) return FALSE;
		set_file_buffer($fp, 0);
		if ($lock) flock($fp, LOCK_SH);
		rewind($fp);
		$index = 0;
		while (! feof($fp)) {
			$line = fgets($fp, $buffer);
			if ($line !== FALSE) $array[] = $line;
			if (++$index >= $count) break;
		}
		if (! fclose($fp)) return FALSE;
	
		return $array;
	}
	
	// Output to a file
	function file_write($dir, $page, $str, $notimestamp = FALSE)
	{
		if (
			$this->cont['PKWK_READONLY']
			||
			(
			empty($this->root->rtf['no_checkauth_on_write']) &&
				(
				! $this->check_readable_page($page, FALSE, FALSE)
				||
				($this->root->plugin_follow_freeze && $this->is_freeze($page))
				||
				($this->root->plugin_follow_editauth && ! $this->check_editable_page($page, FALSE, FALSE))
				)
			)
		) {
			return; // Do nothing
		}
		
		if ($dir !== $this->cont['DATA_DIR'] && $dir !== $this->cont['DIFF_DIR']) die('file_write(): Invalid directory');
	
		$page = $this->strip_bracket($page);
		
		// ページキャッシュを破棄
		if ($dir === $this->cont['DATA_DIR']) {
			$this->clear_page_cache ($page);
		}
		
		$file = $dir . $this->encode($page) . '.txt';
		$file_exists = file_exists($file);
	
		// ----
		// Delete?
	
		if ($dir === $this->cont['DATA_DIR'] && $str === '') {
			// Page deletion
			if (! $file_exists) return; // Ignore null posting for $this->cont['DATA_DIR']
	
			// Remove the page
			unlink($file);
	
			// Clear is_page() cache & clearstatcache()
			$this->is_page('', TRUE);
	
			return;
	
		} else if ($dir === $this->cont['DIFF_DIR'] && $str === " \n") {
			return; // Ignore null posting for $this->cont['DIFF_DIR']
		}
	
		// ----
		// File replacement (Edit)
	
		if (! $this->is_pagename($page))
			$this->die_message(str_replace('$1', htmlspecialchars($page),
			            str_replace('$2', 'WikiName', $this->root->_msg_invalidiwn)));
	
		$str = rtrim(preg_replace('/' . "\r" . '/', '', $str)) . "\n";
		$timestamp = ($file_exists && $notimestamp) ? filemtime($file) : FALSE;
	
		$fp = fopen($file, 'a') or die('fopen() failed: ' .
			htmlspecialchars(basename($dir) . '/' . $this->encode($page) . '.txt') .	
			'<br />' . "\n" .
			'Maybe permission is not writable or filename is too long');
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		ftruncate($fp, 0);
		rewind($fp);
		fputs($fp, $str);
		fclose($fp);
	
		if ($timestamp) $this->pkwk_touch_file($file, $timestamp);
	
		// Optional actions
		if ($dir === $this->cont['DATA_DIR']) {
	
			// Command execution per update
			if (isset($this->cont['PKWK_UPDATE_EXEC']) && $this->cont['PKWK_UPDATE_EXEC'])
				system($this->cont['PKWK_UPDATE_EXEC'] . ' > /dev/null &');
	
		}
			
		// Clear $this->is_page() cache & clearstatcache()
		$this->is_page('', TRUE);

	}
	
	// Update RecentDeleted
	function add_recent($page, $recentpage, $subject = '', $limit = 0)
	{
		if ($this->cont['PKWK_READONLY'] || $limit === 0 || $page === '' || $recentpage === '' ||
		    $this->check_non_list($page) || !$this->check_readable_page($page, FALSE, FALSE, 0)) return;
		
		// set mode
		$mode = ($this->is_page($recentpage))? 'update' : 'insert';
		$pginfo = $this->get_pginfo($recentpage);
		
		// Load
		$lines = $matches = array();
		foreach ($this->get_source($recentpage) as $line)
			if (preg_match('/^-(.+) - (\[\[.+\]\])$/', $line, $matches))
				$lines[$matches[2]] = $line;
	
		$_page = '[[' . $page . ']]';
	
		// Remove a report about the same page
		if (isset($lines[$_page])) unset($lines[$_page]);
	
		// Add
		array_unshift($lines, '-' . $this->format_date($this->cont['UTIME']) . ' - ' . $_page .
			htmlspecialchars($subject) . "\n");
	
		// Get latest $limit reports
		$lines = array_splice($lines, 0, $limit);
	
		// Update
		$fp = fopen($this->get_filename($recentpage), 'w') or
			$this->die_message('Cannot write page file ' .
			htmlspecialchars($recentpage) .
			'<br />Maybe permission is not writable or filename is too long');
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		rewind($fp);
		fputs($fp, '#freeze'    . "\n");
		fputs($fp, '#norelated' . "\n"); // :)
		fputs($fp, join('', $lines));
		fclose($fp);
		
		// Clear fstat cache.
		clearstatcache();
				
		// pginfo DB write
		$this->pginfo_db_write($recentpage, $mode, $pginfo);
	}
	
	// Re-create PKWK_MAXSHOW_CACHE (Heavy)
	function put_lastmodified()
	{
		return;
	}
	
	// update autolink data
	function autolink_pattern_write($filename, $autolink_pattern)
	{
		list($pattern, $pattern_a, $forceignorelist) = $autolink_pattern;
	
		$fp = fopen($filename, 'w') or
				$this->die_message('Cannot open ' . $filename);
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		rewind($fp);
		fputs($fp, $pattern   . "\n");
		fputs($fp, $pattern_a . "\n");
		fputs($fp, join("\t", $forceignorelist) . "\n");
		fclose($fp);
	}
	
	// Get elapsed date of the page
	function get_pg_passage($page, $sw = TRUE)
	{
		if (! $this->root->show_passage) return '';
	
		$time = $this->get_filetime($page);
		$pg_passage = ($time !== 0) ? $this->get_passage($time) : '';
	
		return $sw ? '<small>' . $pg_passage . '</small>' : ' ' . $pg_passage;
	}
	
	// Last-Modified header
	function header_lastmod($page = NULL)
	{
		if ($this->root->lastmod && $this->is_page($page)) {
			$this->pkwk_headers_sent(false);
			header('Last-Modified: ' .
				date('D, d M Y H:i:s', $this->get_filetime($page)) . ' GMT');
		}
	}
	
	// Get a page list of this wiki
	function get_existpages($dir = NULL, $ext = '.txt')
	{
		// 通常はDB版へ丸投げ
		//if (!is_string($nocheck) || $nocheck === DATA_DIR)
		//	return $this->get_existpages_db($nocheck,$base,$limit,$order,$nolisting,$nochiled,$nodelete);
		
		// PukiWiki 1.4 互換
		//$dir = ($nocheck === FALSE)? NULL : $nocheck;
		//$ext = ($base)? $base : '.txt';
		
		if (is_null($dir)) {$dir = $this->cont['DATA_DIR'];}
		$aryret = array();
	
		$pattern = '((?:[0-9A-F]{2})+)';
		if ($ext !== '') $ext = preg_quote($ext, '/');
		$pattern = '/^' . $pattern . $ext . '$/';
	
		$dp = @opendir($dir) or
			$this->die_message($dir . ' is not found or not readable.');
		$matches = array();
		while ($file = readdir($dp)) {
			if (preg_match($pattern, $file, $matches)) {
				$_page = $this->decode($matches[1]);
				$aryret[$file] = $_page;
			}
		}
		closedir($dp);
	
		return $aryret;
	}
	
	// Get PageReading(pronounce-annotated) data in an array()
	function get_readings()
	{
		$pages = $this->get_existpages();
	
		$readings = array();
		foreach ($pages as $page) 
			$readings[$page] = '';
	
		$deletedPage = FALSE;
		$matches = array();
		foreach ($this->get_source($this->root->pagereading_config_page) as $line) {
			$line = chop($line);
			if(preg_match('/^-\[\[([^]]+)\]\]\s+(.+)$/', $line, $matches)) {
				if(isset($readings[$matches[1]])) {
					// This page is not clear how to be pronounced
					$readings[$matches[1]] = $matches[2];
				} else {
					// This page seems deleted
					$deletedPage = TRUE;
				}
			}
		}
	
		// If enabled ChaSen/KAKASI execution
		if($this->root->pagereading_enable) {
	
			// Check there's non-clear-pronouncing page
			$unknownPage = FALSE;
			foreach ($readings as $page => $reading) {
				if($reading === '') {
					$unknownPage = TRUE;
					break;
				}
			}
	
			// Execute ChaSen/KAKASI, and get annotation
			if($unknownPage) {
				switch(strtolower($this->root->pagereading_kanji2kana_converter)) {
				case 'chasen':
					if(! file_exists($this->root->pagereading_chasen_path))
						$this->die_message('ChaSen not found: ' . $this->root->pagereading_chasen_path);
	
					$tmpfname = tempnam(realpath($this->cont['CACHE_DIR']), 'PageReading');
					$fp = fopen($tmpfname, 'w') or
						$this->die_message('Cannot write temporary file "' . $tmpfname . '".' . "\n");
					foreach ($readings as $page => $reading) {
						if($reading !== '') continue;
						fputs($fp, mb_convert_encoding($page . "\n",
							$this->root->pagereading_kanji2kana_encoding, $this->cont['SOURCE_ENCODING']));
					}
					fclose($fp);
	
					$chasen = "{$this->root->pagereading_chasen_path} -F %y $tmpfname";
					$fp     = popen($chasen, 'r');
					if($fp === FALSE) {
						unlink($tmpfname);
						$this->die_message('ChaSen execution failed: ' . $chasen);
					}
					foreach ($readings as $page => $reading) {
						if($reading !== '') continue;
	
						$line = fgets($fp);
						$line = mb_convert_encoding($line, $this->cont['SOURCE_ENCODING'],
							$this->root->pagereading_kanji2kana_encoding);
						$line = chop($line);
						$readings[$page] = $line;
					}
					pclose($fp);
	
					unlink($tmpfname) or
						$this->die_message('Temporary file can not be removed: ' . $tmpfname);
					break;
	
				case 'kakasi':	/*FALLTHROUGH*/
				case 'kakashi':
					if(! file_exists($this->root->pagereading_kakasi_path))
						$this->die_message('KAKASI not found: ' . $this->root->pagereading_kakasi_path);
	
					$tmpfname = tempnam(realpath($this->cont['CACHE_DIR']), 'PageReading');
					$fp       = fopen($tmpfname, 'w') or
						$this->die_message('Cannot write temporary file "' . $tmpfname . '".' . "\n");
					foreach ($readings as $page => $reading) {
						if($reading !== '') continue;
						fputs($fp, mb_convert_encoding($page . "\n",
							$this->root->pagereading_kanji2kana_encoding, $this->cont['SOURCE_ENCODING']));
					}
					fclose($fp);
	
					$kakasi = "{$this->root->pagereading_kakasi_path} -kK -HK -JK < $tmpfname";
					$fp     = popen($kakasi, 'r');
					if($fp === FALSE) {
						unlink($tmpfname);
						$this->die_message('KAKASI execution failed: ' . $kakasi);
					}
	
					foreach ($readings as $page => $reading) {
						if($reading !== '') continue;
	
						$line = fgets($fp);
						$line = mb_convert_encoding($line, $this->cont['SOURCE_ENCODING'],
							$this->root->pagereading_kanji2kana_encoding);
						$line = chop($line);
						$readings[$page] = $line;
					}
					pclose($fp);
	
					unlink($tmpfname) or
						$this->die_message('Temporary file can not be removed: ' . $tmpfname);
					break;
	
				case 'none':
					$patterns = $replacements = $matches = array();
					foreach ($this->get_source($this->root->pagereading_config_dict) as $line) {
						$line = chop($line);
						if(preg_match('|^ /([^/]+)/,\s*(.+)$|', $line, $matches)) {
							$patterns[]     = $matches[1];
							$replacements[] = $matches[2];
						}
					}
					foreach ($readings as $page => $reading) {
						if($reading !== '') continue;
	
						$readings[$page] = $page;
						foreach ($patterns as $no => $pattern)
							$readings[$page] = mb_convert_kana(mb_ereg_replace($pattern,
								$replacements[$no], $readings[$page]), 'aKCV');
					}
					break;
	
				default:
					$this->die_message('Unknown kanji-kana converter: ' . $this->root->pagereading_kanji2kana_converter . '.');
					break;
				}
			}
	
			if($unknownPage || $deletedPage) {
	
				asort($readings); // Sort by pronouncing(alphabetical/reading) order
				$body = '';
				foreach ($readings as $page => $reading)
					$body .= '-[[' . $page . ']] ' . $reading . "\n";
	
				$this->page_write($this->root->pagereading_config_page, $body);
			}
		}
	
		// Pages that are not prounouncing-clear, return pagenames of themselves
		foreach ($pages as $page) {
			if($readings[$page] === '')
				$readings[$page] = $page;
		}
	
		return $readings;
	}
	
	// Get a list of encoded files (must specify a directory and a suffix)
	function get_existfiles($dir, $ext)
	{
		$pattern = '/^(?:[0-9A-F]{2})+' . preg_quote($ext, '/') . '$/';
		$aryret = array();
		$dp = @opendir($dir) or $this->die_message($dir . ' is not found or not readable.');
		while ($file = readdir($dp))
			if (preg_match($pattern, $file))
				$aryret[] = $dir . $file;
		closedir($dp);
		return $aryret;
	}
	
	// Get a list of related pages of the page
	function links_get_related($page)
	{
		static $links = array();
	
		if (isset($links[$this->root->mydirname][$page])) return $links[$this->root->mydirname][$page];
	
		// If possible, merge related pages generated by make_link()
		$links[$this->root->mydirname][$page] = ($page === $this->root->vars['page']) ? $this->root->related : array();
	
		// Get repated pages from DB
		$links[$this->root->mydirname][$page] += $this->links_get_related_db($page);
	
		return $links[$this->root->mydirname][$page];
	}
	
	// _If needed_, re-create the file to change/correct ownership into PHP's
	// NOTE: Not works for Windows
	function pkwk_chown($filename, $preserve_time = TRUE)
	{
		static $php_uid; // PHP's UID
	
		if (! isset($php_uid)) {
			if (extension_loaded('posix')) {
				$php_uid = posix_getuid(); // Unix
			} else {
				$php_uid = 0; // Windows
			}
		}
	
		// Lock for pkwk_chown()
		$lockfile = $this->cont['CACHE_DIR'] . 'pkwk_chown.lock';
		$flock = fopen($lockfile, 'a') or
			die('pkwk_chown(): fopen() failed for: CACHEDIR/' .
				basename(htmlspecialchars($lockfile)));
		flock($flock, LOCK_EX);
	
		// Check owner
		$stat = stat($filename) or
			die('pkwk_chown(): stat() failed for: '  . basename(htmlspecialchars($filename)));
		if ($stat[4] === $php_uid) {
			// NOTE: Windows always here
			$result = TRUE; // Seems the same UID. Nothing to do
		} else {
			$tmp = $filename . '.' . getmypid() . '.tmp';
	
			// Lock source $filename to avoid file corruption
			// NOTE: Not 'r+'. Don't check write permission here
			$ffile = fopen($filename, 'r') or
				die('pkwk_chown(): fopen() failed for: ' .
					basename(htmlspecialchars($filename)));
	
			// Try to chown by re-creating files
			// NOTE:
			//   * touch() before copy() is for 'rw-r--r--' instead of 'rwxr-xr-x' (with umask 022).
			//   * (PHP 4 < PHP 4.2.0) touch() with the third argument is not implemented and retuns NULL and Warn.
			//   * @unlink() before rename() is for Windows but here's for Unix only
			flock($ffile, LOCK_EX);
			$result = touch($tmp) && copy($filename, $tmp) &&
				($preserve_time ? (touch($tmp, $stat[9], $stat[8]) || touch($tmp, $stat[9])) : TRUE) &&
				rename($tmp, $filename);
	
			fclose($ffile) or die('pkwk_chown(): fclose() failed');
	
			if ($result === FALSE) @unlink($tmp);
		}
	
		// Unlock for pkwk_chown()
		fclose($flock) or die('pkwk_chown(): fclose() failed for lock');
	
		return $result;
	}
	
	// touch() with trying pkwk_chown()
	function pkwk_touch_file($filename, $time = FALSE, $atime = FALSE)
	{
		// Is the owner incorrected and unable to correct?
		if (! file_exists($filename) || $this->pkwk_chown($filename)) {
			if ($time === FALSE) {
				$result = touch($filename);
			} else if ($atime === FALSE) {
				$result = touch($filename, $time);
			} else {
				$result = touch($filename, $time, $atime);
			}
			return $result;
		} else {
			die('pkwk_touch_file(): Invalid UID and (not writable for the directory or not a flie): ' .
				htmlspecialchars(basename($filename)));
		}
	}
//----- End file.php -----//

//----- Start convert_html.php -----//
	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: pukiwiki_func.php,v 1.180 2008/07/02 23:59:46 nao-pon Exp $
	// Copyright (C)
	//   2002-2005 PukiWiki Developers Team
	//   2001-2002 Originally written by yu-ji
	// License: GPL v2 or (at your option) any later version
	//
	// function 'convert_html()', wiki text parser
	// and related classes-and-functions
	
	function convert_html($lines, $page_as = '')
	{
		static $contents_id = array();
		static $real_nest = array();
		static $digests = array();
		if (!isset( $contents_id[$this->xpwiki->pid] )) {$contents_id[$this->xpwiki->pid] = 0;}
		if (!isset( $real_nest[$this->xpwiki->pid] )) {$real_nest[$this->xpwiki->pid] = 0;}
		if (!isset( $digests[$this->root->mydirname] )) {$digests[$this->root->mydirname] = array();}
		
		if ($page_as !== '') {
			$_page = $this->root->vars['page'];
			$this->root->vars['page'] = $this->root->post['page'] = $this->root->get['page'] = $page_as;
		}
		
		// Set nest level
		if (!isset($this->root->rtf['convert_nest'])) {
			$this->root->rtf['convert_nest'] = 1;
		} else {
			++$this->root->rtf['convert_nest'];
		}
		++$real_nest[$this->xpwiki->pid];
		
		// 編集権限がない場合の挙動指定
		$_PKWK_READONLY = $this->set_readonly_by_editauth($this->root->vars['page']);
		
		// Set digest
		if ($this->root->vars['page'] !== '') {
			if (!isset($digests[$this->root->mydirname][$this->root->vars['page']])) {
				$digests[$this->root->mydirname][$this->root->vars['page']] = md5($this->get_source($this->root->vars['page'], TRUE, TRUE));
			}
			$this->root->digest = $digests[$this->root->mydirname][$this->root->vars['page']];
		} else {
			$this->root->digest = '';
		}
		
		if (! is_array($lines)) $lines = explode("\n", $lines);

		// remove pginfo
		$lines = $this->remove_pginfo($lines);
		
		
		if ($this->root->render_mode === 'render') {
			$contentId = uniqid(''); 
		} else {	
			$contentId = ++$contents_id[$this->xpwiki->pid];
		}
		$body = & new XpWikiBody($this->xpwiki, $contentId);
		
		$body->parse($lines);
		
		$ret = $body->toString();
		
		$body = null;
		
		// Auto link
		if ($real_nest[$this->xpwiki->pid] === 1) {

			$ext_autolink_obj = null;
			$ext_autolinks_pre = $ext_autolinks_aft = array();

			if (!isset($this->root->ext_autolinks)) {
				$this->root->ext_autolinks = array();
			}
			
			// Is upper directory hierarchy omissible?
			if ($this->root->autolink && $this->root->autolink_omissible_upper) {
				$_omissible_upper = (isset($this->root->vars['page']))? $this->root->vars['page'] : '';
				$_omissible_upper = preg_replace('#^(.*)/[^/]+$#', "$1", $_omissible_upper);
				if ($_omissible_upper) {
					$this->root->ext_autolinks[] = array(
						'priority' => $this->root->autolink_omissible_upper_priority,
						'base' => $_omissible_upper,
						'len'  => $this->root->autolink_omissible_upper,
						'enc'  => $this->cont['CONTENT_CHARSET']
					);
				}
			}
			
			if (!empty($this->root->ext_autolinks)) {
				foreach($this->root->ext_autolinks as $_autos) {
					if (empty($_autos['priority'])) {
						$_autos['priority'] = 40;
						$ext_autolinks_aft[] = $_autos;
					} else if ($_autos['priority'] <= 50) {
						$ext_autolinks_aft[] = $_autos;
					} else {
						$ext_autolinks_pre[] = $_autos;
					}
				}
			}
			
			// External AutoLink Pre
			if ($ext_autolinks_pre) {
				if (!is_object($ext_autolink_obj)) {
					include_once(dirname(dirname(__FILE__)).'/ext_autolink.php');
					$ext_autolink_obj = new XpWikiPukiExtAutoLink($this->xpwiki);
				}
				$ext_autolink_obj->ext_autolinks = $ext_autolinks_pre;
				$ext_autolink_obj->ext_autolink($ret);
			}

			// Internal Autolink
			if ($this->root->autolink) {
				$this->int_autolink_proc($ret);
			}
			
			// External AutoLink After
			if (! empty($ext_autolinks_aft)) {
				if (!is_object($ext_autolink_obj)) {
					include_once(dirname(dirname(__FILE__)).'/ext_autolink.php');
					$ext_autolink_obj = new XpWikiPukiExtAutoLink($this->xpwiki);
				}
				$ext_autolink_obj->ext_autolinks = $ext_autolinks_aft;
				$ext_autolink_obj->ext_autolink($ret);
			}
			
			// Remove No Autolink tags
			$ret = str_replace(array('<!--NA-->', '<!--/NA-->'), '', $ret);
		}
		
		//if ($this->root->rtf['convert_nest'] > 1) $this->cont['PKWK_READONLY'] = $_PKWK_READONLY;
		$this->cont['PKWK_READONLY'] = $_PKWK_READONLY;

		if ($page_as) {
			$this->root->vars['page'] = $this->root->post['page'] = $this->root->get['page'] = $_page;
		}
		
		--$this->root->rtf['convert_nest'];
		--$real_nest[$this->xpwiki->pid];
		return $ret;
	}
	
	// Internal Autolink
	function int_autolink_proc (& $str) {
		if (! $this->root->autolink || ! file_exists($this->cont['CACHE_DIR'].$this->cont['PKWK_AUTOLINK_REGEX_CACHE'])) return ;
		
		@ list ($auto, $auto_a, $forceignorepages) = file($this->cont['CACHE_DIR'].$this->cont['PKWK_AUTOLINK_REGEX_CACHE']);
		if ($this->root->page_case_insensitive) $forceignorepages = strtolower($forceignorepages);
		$this->rt_global['forceignorepages'] = explode("\t", trim($forceignorepages));
		
		list($pat_pre, $pat_aft) = $this->get_autolink_regex_pre_after($this->root->page_case_insensitive);

		// ページ数が多い場合は、セパレータ \t で複数パターンに分割されている
		$auto = explode("\t",trim($auto));
		foreach($auto as $pat)
		{
			$pattern = $pat_pre.$pat.$pat_aft;
			$str = preg_replace_callback($pattern, array(& $this, 'int_auto_link_replace'), $str);
		}
		
		return ;
	}
	
	function int_auto_link_replace($match)
	{
		if (!empty($match[1])) return $match[1];
		$alias = $name = $match[3];
		
		// 無視リストに含まれているページを捨てる
		if (in_array(($this->root->page_case_insensitive ? strtolower($name) : $name), $this->rt_global['forceignorepages'])) { return $match[0]; }
		
		return $this->make_pagelink($name, $alias, '', '', 'autolink');
	}
	
	// Returns inline-related object
	function & Factory_Inline($text)
	{
		// Check the first letter of the line
		if (substr($text, 0, 1) === '~') {
			$ret = & new XpWikiParagraph($this->xpwiki, ' ' . substr($text, 1));
		} else {
			$ret = & new XpWikiInline($this->xpwiki, $text);
		}
		return $ret;
	}
	
	function & Factory_DList($text)
	{
		$out = explode('|', ltrim($text), 2);
		if (count($out) < 2) {
			return $this->Factory_Inline($text);
		} else {
			$ret = & new XpWikiDList($this->xpwiki, $out);
			return $ret;
		}
	}
	
	// '|'-separated table
	function & Factory_Table($text)
	{
		if (! preg_match('/^\|(.+)\|([hHfFcC]?)$/', $text, $out)) {
			return $this->Factory_Inline($text);
		} else {
			$ret = & new XpWikiTable($this->xpwiki, $out);
			return $ret;
		}
	}
	
	// Comma-separated table
	function & Factory_YTable($text)
	{
		if ($text === ',') {
			return $this->Factory_Inline($text);
		} else {
			$ret = & new XpWikiYTable($this->xpwiki, $this->csv_explode(',', substr($text, 1)));
			return $ret;
		}
	}
	
	function & Factory_Div($text)
	{
		$matches = array();
	
		// Seems block plugin?
		if ($this->cont['PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK']) {
			// Usual code
			if (preg_match('/^\#([^\(]+)(?:\((.*)\))?/', $text, $matches) &&
			    $this->exist_plugin_convert($matches[1])) {
				$ret = & new XpWikiDiv($this->xpwiki, $matches);
				return $ret;
			}
		} else {
			// Hack code
			if(preg_match('/^#([^\(\{]+)(?:\(([^\r]*)\))?(\{*)/', $text, $matches) &&
			   $this->exist_plugin_convert($matches[1])) {
				$len  = strlen($matches[3]);
				$body = array();
				$ret = false;
				if ($len === 0) {
					$ret = & new XpWikiDiv($this->xpwiki, $matches); // Seems legacy block plugin
				} else if (preg_match('/\{{' . $len . '}\s*\r(.*)\r\}{' . $len . '}/', $text, $body)) { 
					$matches[2] .= "\r" . $body[1] . "\r";
					$ret = & new XpWikiDiv($this->xpwiki, $matches); // Seems multiline-enabled block plugin
				}
				if ($ret) return $ret;
			}
		}
		$ret = & new XpWikiParagraph($this->xpwiki, $text);
		return $ret;
	}
//----- End convert_html.php -----//

//----- Start func.php -----//
	// PukiWiki - Yet another WikiWikiWeb clone.
	// $Id: pukiwiki_func.php,v 1.180 2008/07/02 23:59:46 nao-pon Exp $
	// Copyright (C)
	//   2002-2006 PukiWiki Developers Team
	//   2001-2002 Originally written by yu-ji
	// License: GPL v2 or (at your option) any later version
	//
	// General functions
	
	function is_interwiki($str)
	{
			return preg_match('/^' . $this->root->InterWikiName . '$/', $str);
	}
	
	function is_pagename($str)
	{
		$is_pagename = (! $this->is_interwiki($str) &&
			  preg_match('/^(?!\/)' . $this->root->BracketName . '$(?<!\/$)/', $str) &&
			! preg_match('#(^|/)\.{1,2}(/|$)#', $str));
	
		if (isset($this->cont['SOURCE_ENCODING'])) {
			switch($this->cont['SOURCE_ENCODING']){
			case 'UTF-8': $pattern =
				'/^(?:[\x00-\x7F]|(?:[\xC0-\xDF][\x80-\xBF])|(?:[\xE0-\xEF][\x80-\xBF][\x80-\xBF]))+$/';
				break;
			case 'EUC-JP': $pattern =
				'/^(?:[\x00-\x7F]|(?:[\x8E\xA1-\xFE][\xA1-\xFE])|(?:\x8F[\xA1-\xFE][\xA1-\xFE]))+$/';
				break;
			}
			if (isset($pattern) && $pattern !== '')
				$is_pagename = ($is_pagename && preg_match($pattern, $str));
		}
	
		return $is_pagename;
	}
	
	function is_url($str, $only_http = FALSE)
	{
		$scheme = $only_http ? 'https?' : 'https?|ftp|news';
		return preg_match('/^(' . $scheme . ')(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]*)$/', $str);
	}
	
	// If the page exists
	function is_page($page, $clearcache = FALSE)
	{
		static $cache = array();
		
		if ($clearcache) {
			if (!$page) {
				clearstatcache();
				$cache[$this->root->mydirname] = array();
			}
			if (isset($cache[$this->root->mydirname][$page])) {
				unset($cache[$this->root->mydirname][$page]);
			}
		}
		if (! isset($cache[$this->root->mydirname][$page])) {
			$cache[$this->root->mydirname][$page] = file_exists($this->get_filename($page));
		}
		
		return $cache[$this->root->mydirname][$page];
	}
	
	function is_editable($page)
	{
		static $is_editable = array();
	
		if (! isset($is_editable[$this->root->mydirname][$page])) {
			$is_editable[$this->root->mydirname][$page] = (
				$this->is_pagename($page) &&
				! $this->is_freeze($page) &&
				! in_array($page, $this->root->cantedit)
			);
		}
	
		return $is_editable[$this->root->mydirname][$page];
	}
	
	function is_freeze($page, $clearcache = FALSE)
	{
		static $is_freeze = array();
	
		if ($clearcache === TRUE) $is_freeze = array();
		if (isset($is_freeze[$this->root->mydirname][$page])) return $is_freeze[$this->root->mydirname][$page];
	
			if (! $this->root->function_freeze || ! $this->is_page($page)) {
			$is_freeze[$this->root->mydirname][$page] = FALSE;
			return FALSE;
		} else {
			$fp = fopen($this->get_filename($page), 'rb') or
				die('is_freeze(): fopen() failed: ' . htmlspecialchars($page));
			flock($fp, LOCK_SH);
			rewind($fp);
			$buffer = fgets($fp, 9);
			fclose($fp) or die('is_freeze(): fclose() failed: ' . htmlspecialchars($page));
	
				$is_freeze[$this->root->mydirname][$page] = ($buffer !== FALSE && rtrim($buffer, "\r\n") === '#freeze');
			return $is_freeze[$this->root->mydirname][$page];
		}
	}
	
	// Handling $non_list
	// $non_list will be preg_quote($str, '/') later.
	function check_non_list($page = '')
	{
		static $regex;

		if (! isset($regex[$this->root->mydirname])) $regex[$this->root->mydirname] = '/' . $this->root->non_list . '/';

		return preg_match($regex[$this->root->mydirname], $page);
	}
	
	// Auto template
	function auto_template($page)
	{
		if (! $this->root->auto_template_func) return '';
	
		$body = '';
		$matches = array();
		foreach ($this->root->auto_template_rules as $rule => $template) {
			$rule_pattrn = '/' . $rule . '/';
	
			if (! preg_match($rule_pattrn, $page, $matches)) continue;
			
			if (!is_array($template)) {
				$template = array($template);
			}
			foreach($template as $_template) {
				$template_page = preg_replace($rule_pattrn, $_template, $page);
				if ($this->is_page($template_page)) break;
			}
			
			if (! $this->is_page($template_page)) continue;
			
			$body = $this->get_source($template_page, TRUE, TRUE);
	
			// Remove fixed-heading anchors, '#freeze' etc.
			$this->cleanup_template_source($body);
	
			$count = count($matches);
			for ($i = 0; $i < $count; $i++)
				$body = str_replace('$' . $i, $matches[$i], $body);
	
			break;
		}
		return $body;
	}
	
	// Expand all search-words to regexes and push them into an array
	function get_search_words($words = array(), $do_escape = FALSE)
	{
		static $init, $mb_convert_kana, $pre, $post, $quote = '/';
	
		if (! isset($init)) {
			// function: mb_convert_kana() is for Japanese code only
			if ($this->cont['LANG'] === 'ja' && function_exists('mb_convert_kana')) {
				$mb_convert_kana = create_function('$str, $option',
					'return mb_convert_kana($str, $option, "'.$this->cont["SOURCE_ENCODING"].'");');
			} else {
				$mb_convert_kana = create_function('$str, $option',
					'return $str;');
			}
			if ($this->cont['SOURCE_ENCODING'] === 'EUC-JP') {
				// Perl memo - Correct pattern-matching with EUC-JP
				// http://www.din.or.jp/~ohzaki/perl.htm#JP_Match (Japanese)
				$pre  = '(?<!\x8F)';
				$post =	'(?=(?:[\xA1-\xFE][\xA1-\xFE])*' . // JIS X 0208
					'(?:[\x00-\x7F\x8E\x8F]|\z))';     // ASCII, SS2, SS3, or the last
			} else {
				$pre = $post = '';
			}
			$init = TRUE;
		}
	
		if (! is_array($words)) $words = array($words);
	
		// Generate regex for the words
		$regex = array();
		foreach ($words as $word) {
			$word = trim($word);
			if ($word === '') continue;
	
			// Normalize: ASCII letters = to single-byte. Others = to Zenkaku and Katakana
			$word_nm = $mb_convert_kana($word, 'aKCV');
			$nmlen   = mb_strlen($word_nm, $this->cont['SOURCE_ENCODING']);
	
			// Each chars may be served ...
			$chars = array();
			for ($pos = 0; $pos < $nmlen; $pos++) {
				$char = mb_substr($word_nm, $pos, 1, $this->cont['SOURCE_ENCODING']);
	
				// Just normalized one? (ASCII char or Zenkaku-Katakana?)
				$or = array(preg_quote($do_escape ? htmlspecialchars($char) : $char, $quote));
				if (strlen($char) === 1) {
					// An ASCII (single-byte) character
					foreach (array(strtoupper($char), strtolower($char)) as $_char) {
						if ($char !== '&') $or[] = preg_quote($_char, $quote); // As-is?
						$ascii = ord($_char);
						$or[] = sprintf('&#(?:%d|x%x);', $ascii, $ascii); // As an entity reference?
						$or[] = preg_quote($mb_convert_kana($_char, 'A'), $quote); // As Zenkaku?
					}
				} else {
					// NEVER COME HERE with mb_substr(string, start, length, 'ASCII')
					// A multi-byte character
					$or[] = preg_quote($mb_convert_kana($char, 'c'), $quote); // As Hiragana?
					$or[] = preg_quote($mb_convert_kana($char, 'k'), $quote); // As Hankaku-Katakana?
				}
				$chars[] = '(?:' . join('|', array_unique($or)) . ')'; // Regex for the character
			}
	
			$regex[$word] = $pre . join('', $chars) . $post; // For the word
		}
	
		return $regex; // For all words
	}
	
	// 'Search' main function
	function do_search($word, $type = 'AND', $non_format = FALSE, $base = '')
	{
		$retval = array();
	
		$b_type = ($type === 'AND'); // AND:TRUE OR:FALSE
		$keys = $this->get_search_words(preg_split('/\s+/', $word, -1, PREG_SPLIT_NO_EMPTY));
		foreach ($keys as $key=>$value)
			$keys[$key] = '/' . $value . '/S';
	
		$pages = $this->get_existpages();
	
		// Avoid
		if ($base !== '') {
			$pages = preg_grep('/^' . preg_quote($base, '/') . '/S', $pages);
		}
		if (! $this->root->search_non_list) {
			$pages = array_diff($pages, preg_grep('/' . $this->root->non_list . '/S', $pages));
		}
		$pages = array_flip($pages);
		unset($pages[$this->root->whatsnew]);
	
		$count = count($pages);
		foreach (array_keys($pages) as $page) {
			$b_match = FALSE;
	
			// Search for page name
			if (! $non_format) {
				foreach ($keys as $key) {
					$b_match = preg_match($key, $page);
					if ($b_type xor $b_match) break; // OR
				}
				if ($b_match) continue;
			}
	
			// Search auth for page contents
			if ($this->root->search_auth && ! $this->check_readable($page, false, false)) {
				unset($pages[$page]);
				--$count;
			}
	
			// Search for page contents
			foreach ($keys as $key) {
				$b_match = preg_match($key, $this->get_source($page, TRUE, TRUE));
				if ($b_type xor $b_match) break; // OR
			}
			if ($b_match) continue;
	
			unset($pages[$page]); // Miss
		}
		if ($non_format) return array_keys($pages);
	
		$r_word = rawurlencode($word);
		$s_word = htmlspecialchars($word);
		if (empty($pages))
			return str_replace('$1', $s_word, $this->root->_msg_notfoundresult);
	
		ksort($pages, SORT_STRING);
	
		$retval = '<ul>' . "\n";
		foreach (array_keys($pages) as $page) {
			$r_page  = rawurlencode($page);
			$s_page  = htmlspecialchars($page);
			$passage = $this->root->show_passage ? ' ' . $this->get_passage($this->get_filetime($page)) : '';
			$retval .= ' <li><a href="' . $this->root->script . '?' .
				$r_page . '&amp;word=' . $r_word . '">' . $s_page .
				'</a>' . $passage . '</li>' . "\n";
		}
		$retval .= '</ul>' . "\n";
	
		$retval .= str_replace('$1', $s_word, str_replace('$2', count($pages),
			str_replace('$3', $count, $b_type ? $this->root->_msg_andresult : $this->root->_msg_orresult)));
	
		return $retval;
	}
	
	// Argument check for program
	function arg_check($str)
	{
		return isset($this->root->vars['cmd']) && (strpos($this->root->vars['cmd'], $str) === 0);
	}
	
	// Encode page-name
	function encode($str)
	{
		$str = strval($str);
		return ($str === '') ? '' : strtoupper(bin2hex($str));
		// Equal to strtoupper(join('', unpack('H*0', $key)));
		// But PHP 4.3.10 says 'Warning: unpack(): Type H: outside of string in ...'
	}
	
	// Decode page name
	function decode($str)
	{
		return $this->hex2bin($str);
	}
	
	// Inversion of bin2hex()
	function hex2bin($hex_string)
	{
		// preg_match : Avoid warning : pack(): Type H: illegal hex digit ...
		// (string)   : Always treat as string (not int etc). See BugTrack2/31
		return preg_match('/^[0-9a-f]+$/i', $hex_string) ?
			pack('H*', (string)$hex_string) : $hex_string;
	}
	
	// Remove [[ ]] (brackets)
	function strip_bracket($str)
	{
		$match = array();
		if (preg_match('/^\[\[(.*)\]\]$/', $str, $match)) {
			return $match[1];
		} else {
			return $str;
		}
	}
	
	// Create list of pages
	function page_list($pages, $cmd = 'read', $withfilename = FALSE)
	{
		// ソートキーを決定する。 ' ' < '[a-zA-Z]' < 'zz'という前提。
		$symbol = ' ';
		$other = 'zz';
	
		$retval = '';
	
		if($this->root->pagereading_enable) {
			mb_regex_encoding($this->cont['SOURCE_ENCODING']);
		}
		list($readings, $titles) = $this->get_readings($pages);
	
		$list = $matches = array();
	
		// Shrink URI for read
		if ($cmd === 'read') {
			$href = $this->root->script . ($this->root->static_url? '' : '?');
		} else {
			$href = $this->root->script . '?cmd=' . $cmd . '&amp;page=';
		}
	
		foreach($pages as $file=>$page) {
			$r_page  = ($cmd === 'read' && $this->root->static_url)? $this->get_page_uri($page) : rawurlencode($page);
			$s_page  = htmlspecialchars($page, ENT_QUOTES);
			$passage = $this->get_pg_passage($page);
			$title = (empty($titles[$page]))? '' : ' [ ' . htmlspecialchars($titles[$page]) . ' ]';
	
			$str = '   <li><a href="' . $href . $r_page . '">' .
				$s_page . '</a>' . $passage . $title;
	
			if ($withfilename) {
				$s_file = htmlspecialchars($file);
				$str .= "\n" . '    <ul><li>' . $s_file . '</li></ul>' .
					"\n" . '   ';
			}
			$str .= '</li>';
	
			if($this->root->pagereading_enable) {
				// WARNING: Japanese code hard-wired
				$katakana = 'ァ-ヶ';
				$kanji = 'ぁ-ん亜-熙';
				if ($this->cont['SOURCE_ENCODING'] === 'UTF-8') {
					$katakana = mb_convert_encoding($katakana, 'UTF-8', 'EUC-JP');
					$kanji = mb_convert_encoding($kanji, 'UTF-8', 'EUC-JP');
				}
				if(mb_ereg('^([A-Za-z])', mb_convert_kana($page, 'a'), $matches)) {
					$head = $matches[1];
				} elseif (isset($readings[$page]) && mb_ereg('^([' . $katakana . '])', $readings[$page], $matches)) { // here
					$head = $matches[1];
				} elseif (mb_ereg('^[ -~]|[^' . $kanji . ']', $page)) { // and here
					$head = $symbol;
				} else {
					$head = $other;
				}
			} else {
				$head = (preg_match('/^([A-Za-z])/', $page, $matches)) ? $matches[1] :
					(preg_match('/^([ -~])/', $page) ? $symbol : $other);
			}
			if ($this->root->page_case_insensitive) {
				$head = strtoupper($head);
			}
			$list[$head][$page] = $str;
		}
		ksort($list);
	
		$cnt = 0;
		$arr_index = array();
		$retval .= '<ul>' . "\n";
		foreach ($list as $head=>$pages) {
			if ($head === $symbol) {
				$head = $this->root->_msg_symbol;
			} else if ($head === $other) {
				$head = $this->root->_msg_other;
			}
	
			if ($this->root->list_index) {
				++$cnt;
				$arr_index[] = '<a id="top_' . $cnt .
					'" href="#head_' . $cnt . '">&nbsp;<strong>' .
					$head . '</strong>&nbsp;</a>';
				$retval .= ' <li><a id="head_' . $cnt . '" href="#top_' . $cnt .
					'"><strong>' . $head . '</strong></a>' . "\n" .
					'  <ul>' . "\n";
			}
			ksort($pages);
			$retval .= join("\n", $pages);
			if ($this->root->list_index)
				$retval .= "\n  </ul>\n </li>\n";
		}
		$retval .= '</ul>' . "\n";
		if ($this->root->list_index && $cnt > 0) {
			$top = array();
			while (! empty($arr_index))
				$top[] = join('|', array_splice($arr_index, 0, 16)) . "\n";
	
			$retval = '<div id="top" style="text-align:center">' . "\n" .
				join('<br />', $top) . '</div>' . "\n" . $retval;
		}
		return $retval;
	}
	
	// Show text formatting rules
	function catrule()
	{
		if (! $this->is_page($this->root->rule_page)) {
			return '<p>Sorry, page \'' . htmlspecialchars($this->root->rule_page) .
				'\' unavailable.</p>';
		} else {
			return $this->convert_html($this->get_source($this->root->rule_page));
		}
	}
	
	// Show (critical) error message
	function die_message($msg)
	{
		$title = $page = 'Runtime error';
		$body = <<<EOD
	<h3>Runtime error</h3>
	<strong>Error message : $msg</strong>
EOD;
	
		$this->pkwk_common_headers();
		if(isset($this->cont['SKIN_FILE']) && file_exists($this->cont['SKIN_FILE']) && is_readable($this->cont['SKIN_FILE'])) {
			$this->catbody($title, $page, $body);
		} else {
			header('Content-Type: text/html; charset='.$this->cont['CONTENT_CHARSET']);
			print <<<EOD
	<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
	<html>
	 <head>
	  <title>$title</title>
	  <meta http-equiv="content-type" content="text/html; charset={$this->cont['CONTENT_CHARSET']}">
	 </head>
	 <body>
	 $body
	 </body>
	</html>
EOD;
		}
		exit;
	}
	
	// Have the time (as microtime)
	function getmicrotime()
	{
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$sec + (float)$usec);
	}
	
	// Get the date
	function get_date($format, $timestamp = NULL)
	{
		$format = preg_replace('/(?<!\\\)T/',	"_\\Z\\ONE_", $format);
	
		$time = ((preg_match('/(?<!\\\)(O|P|r)/', $format))? date('Z') : $this->cont['ZONETIME']) + (($timestamp !== NULL) ? $timestamp : $this->cont['UTIME']);
	
		return str_replace("_ZONE_", $this->cont['ZONE'], date($format, $time));
	}
	
	// Format date string
	function format_date($val, $paren = FALSE)
	{
		$date = $this->get_date($this->root->date_format, $val) .
			' (' . $this->root->weeklabels[$this->get_date('w', $val)] . ') ' .
			$this->get_date($this->root->time_format, $val);
	
		return $paren ? '(' . $date . ')' : $date;
	}
	
	// Get short string of the passage, 'N seconds/minutes/hours/days/years ago'
	function get_passage($time, $paren = TRUE)
	{
		static $units = array('m'=>60, 'h'=>24, 'd'=>1);
	
		$time = max(0, ($this->cont['UTIME'] - $time) / 60); // minutes
	
		foreach ($units as $unit=>$card) {
			if ($time < $card) break;
			$time /= $card;
		}
		$time = floor($time) . $unit;
	
		return $paren ? '(' . $time . ')' : $time;
	}
	
	// Hide <input type="(submit|button|image)"...>
	function drop_submit($str)
	{
		return preg_replace('/<input([^>]+)type="(submit|button|image)"/i',
			'<input$1type="hidden"', $str);
	}
	
	// Generate AutoLink patterns (thx to hirofummy)
	function get_autolink_pattern(& $pages, $min_len = -1, $make_a = true)
	{
		$config = &new XpWikiConfig($this->xpwiki, 'AutoLink');
		$config->read();
		$ignorepages      = $config->get('IgnoreList');
		$forceignorepages = $config->get('ForceIgnoreList');
		unset($config);
		$auto_pages = array_merge($ignorepages, $forceignorepages);
	
		if ($min_len === -1) {
			$min_len = $this->root->autolink;	// set $this->root->autolink, when omitted.
		}
		
		$_aliases = array_keys(array_intersect($this->root->page_aliases, $pages));
		foreach (array_merge($pages, $_aliases) as $page) {
			if (strlen($page) >= $min_len) {
				$auto_pages[] = $page;
			}
		}
		
		if (empty($auto_pages)) {
			$result = $result_a = $this->root->nowikiname ? '(?!)' : $this->root->WikiName;
		} else {
			if ($make_a) {
				$auto_pages_a = array_values(preg_grep('/^[A-Z]+$/i', $auto_pages));
				$auto_pages   = array_values(array_diff($auto_pages,  $auto_pages_a));
				list($result)   = explode("\t", $this->get_matcher_regex_safe($auto_pages));
				list($result_a) = explode("\t", $this->get_matcher_regex_safe($auto_pages_a));
			} else {
				$result   = $this->get_matcher_regex_safe($auto_pages);
				$result_a = '(?!)';
			}
		}
		return array($result, $result_a, $forceignorepages);
	}
	
	function get_matcher_regex_safe ($pages, $spliter = "\t", $array_fix = true, $nest = 0) {
		
		if ($array_fix) {
			$pages = array_map('trim', $pages);
			if ($this->root->page_case_insensitive) $pages = array_map('strtolower', $pages);
			$pages = array_unique($pages);
			foreach(array_keys($pages, '') as $key) {
				unset($pages[$key]);
			}
			sort($pages, SORT_STRING);
		}
		
		++$nest;
		$reg = $this->get_matcher_regex_safe_sub($pages);
		$regs = preg_split("/(\d+)\x08/", $reg, -1, PREG_SPLIT_DELIM_CAPTURE);
		$pats = array();
		$index = 0;
		reset($regs);
		while (list($key, $pat) = each($regs)) {
			list($key, $val) = each($regs);
			if (!$val) $val = count($pages);
			if (@ preg_match('/' . $pat. '/', '') === false) {
				if ($nest <= 10) {
					$count = $val - $index;
					$split = floor(($val - $index) / 2);
					$pages1 = array_slice($pages, $index, $split);
					$pages2 = array_slice($pages, $split, $count - $split);
					$pats[] = $this->get_matcher_regex_safe($pages2, $spliter, false, $nest);
					$pats[] = $this->get_matcher_regex_safe($pages1, $spliter, false, $nest);
					$index = $val;
				}
			} else {
				$pats[] = $pat;
			}
		}
		return join($spliter, $pats);
	}
	
	function get_matcher_regex_safe_sub (& $array, $offset = 0, $sentry = NULL, $pos = 0, $nest = 0)
	{
		++$nest;
		$limit = 1024 * 32 - 10;
		
		if (empty($array)) return '(?!)'; // Zero
		if ($sentry === NULL) $sentry = count($array);
		
		// Too short. Skip this
		$skip = ($pos >= mb_strlen($array[$offset]));
		if ($skip) ++$offset;

		// Generate regex for each value
		$regex = '';
		$index = $offset;
		$multi = FALSE;
		$reglen = 0;
		while ($index < $sentry) {
			if ($index !== $offset) {
				$multi = TRUE;
				if ($nest === 1 && strlen($regex) - $reglen > $limit) {
					$reglen = strlen($regex);
					$regex .= ')'.($index)."\x08(?:";
				} else {
					$regex .= '|'; // OR
				}
			}

			// Get one character from left side of the value
			$char = mb_substr($array[$index], $pos, 1);

			// How many continuous keys have the same letter
			// at the same position?
			for ($i = $index; $i < $sentry; $i++)
				if (mb_substr($array[$i], $pos, 1) !== $char) break;
			
			if ($index < ($i - 1)) {
				// Some more keys found
				// Recurse
				$regex .= str_replace(' ', '\\ ', preg_quote($char, '/')) .
				$this->get_matcher_regex_safe_sub($array, $index, $i, $pos + 1, $nest);
			} else {
				// Not found
				$regex .= str_replace(' ', '\\ ',
				preg_quote(mb_substr($array[$index], $pos), '/'));
			}
			$index = $i;
		}
		
		if ($skip || $multi) $regex = '(?:' . $regex . ')';
		if ($skip) $regex .= '?'; // Match for $pages[$offset - 1]
		return $regex;
	}
	
	// Generate a regex, that just matches with all $array values
	// NOTE: All array_keys($array) must be continuous integers, like 0 ... N
	//       Also, all $array values must be strings.
	// $offset = (int) $array[$offset] is the first value to check
	// $sentry = (int) $array[$sentry - 1] is the last value to check  
	// $pos    = (int) Position of letter to start checking. (0 = the first letter)
	function get_matcher_regex(& $array, $offset = 0, $sentry = NULL, $pos = 0)
	{
		if (empty($array)) return '(?!)'; // Zero
		if ($sentry === NULL) $sentry = count($array);
	
		// Too short. Skip this
		$skip = ($pos >= mb_strlen($array[$offset]));
		if ($skip) ++$offset;
	
		// Generate regex for each value
		$regex = '';
		$index = $offset;
		$multi = FALSE;
		while ($index < $sentry) {
			if ($index !== $offset) {
				$multi = TRUE;
				$regex .= '|'; // OR
			}
	
			// Get one character from left side of the value
			$char = mb_substr($array[$index], $pos, 1);
	
			// How many continuous keys have the same letter
			// at the same position?
			for ($i = $index; $i < $sentry; $i++)
				if (mb_substr($array[$i], $pos, 1) !== $char) break;
	
			if ($index < ($i - 1)) {
				// Some more keys found
				// Recurse
				$regex .= str_replace(' ', '\\ ', preg_quote($char, '/')) .
				$this->get_matcher_regex($array, $index, $i, $pos + 1);
			} else {
				// Not found
				$regex .= str_replace(' ', '\\ ',
				preg_quote(mb_substr($array[$index], $pos), '/'));
			}
			$index = $i;
		}
	
		if ($skip || $multi) $regex = '(?:' . $regex . ')';
		if ($skip) $regex .= '?'; // Match for $pages[$offset - 1]
	
		return $regex;
	}
	// Compat
	function get_autolink_pattern_sub(& $pages, $start, $end, $pos)
	{
		return $this->get_matcher_regex($pages, $start, $end, $pos);
	}
	
	// Load/get setting pairs from AutoAliasName
	function get_autoaliases($word = '')
	{
		static $pairs;
	
		if (! isset($pairs[$this->root->mydirname])) {
			$pairs[$this->root->mydirname] = array();
			$pattern = <<<EOD
	\[\[                # open bracket
	((?:(?!\]\]).)+)>   # (1) alias name
	((?:(?!\]\]).)+)    # (2) alias link
	\]\]                # close bracket
EOD;
			$postdata = $this->get_source($this->root->aliaspage, TRUE, TRUE);
			$matches  = array();
			$count = 0;
			$max   = max($this->root->autoalias_max_words, 0);
			if (preg_match_all('/' . $pattern . '/x', $postdata, $matches, PREG_SET_ORDER)) {
				foreach($matches as $key => $value) {
					if ($count ===  $max) break;
					$name = trim($value[1]);
					if (! isset($pairs[$this->root->mydirname][$name])) {
						++$count;
						 $pairs[$this->root->mydirname][$name] = trim($value[2]);
					}
					unset($matches[$key]);
				}
			}
		}
	
		if ($word === '') {
			// An array(): All pairs
			return $pairs[$this->root->mydirname];
		} else {
			// A string: Seek the pair
			if (isset($pairs[$this->root->mydirname][$word])) {
				return $pairs[$this->root->mydirname][$word];
			} else {
				return '';
			}
		}
	}
	
	// Get absolute-URI of this script
	function get_script_uri($init_uri = '')
	{
		// for compatibility
		return $this->cont['HOME_URL'];
	}
	
	// Remove null(\0) bytes from variables
	//
	// NOTE: PHP had vulnerabilities that opens "hoge.php" via fopen("hoge.php\0.txt") etc.
	// [PHP-users 12736] null byte attack
	// http://ns1.php.gr.jp/pipermail/php-users/2003-January/012742.html
	//
	// 2003-05-16: magic quotes gpcの復元処理を統合
	// 2003-05-21: 連想配列のキーはbinary safe
	//
	function input_filter($param)
	{
		static $magic_quotes_gpc = NULL;
		if ($magic_quotes_gpc === NULL)
		    $magic_quotes_gpc = get_magic_quotes_gpc();
	
		if (is_array($param)) {
			return array_map(array(& $this, 'input_filter'), $param);
		} else {
			$result = str_replace("\0", '', $param);
			if ($magic_quotes_gpc) $result = stripslashes($result);
			return $result;
		}
	}
	
	// Compat for 3rd party plugins. Remove this later
	function sanitize($param) {
		return $this->input_filter($param);
	}
	
	// Explode Comma-Separated Values to an array
	function csv_explode($separator, $string)
	{
		$retval = $matches = array();
	
		$_separator = preg_quote($separator, '/');
		if (! preg_match_all('/("[^"]*(?:""[^"]*)*"|[^' . $_separator . ']*)' .
		    $_separator . '/', $string . $separator, $matches))
			return array();
	
		foreach ($matches[1] as $str) {
			$len = strlen($str);
			if ($len > 1 && $str{0} === '"' && $str{$len - 1} === '"')
				$str = str_replace('""', '"', substr($str, 1, -1));
			$retval[] = $str;
		}
		return $retval;
	}
	
	// Implode an array with CSV data format (escape double quotes)
	function csv_implode($glue, $pieces)
	{
		$_glue = ($glue !== '') ? '\\' . $glue{0} : '';
		$arr = array();
		foreach ($pieces as $str) {
			if (ereg('[' . $_glue . '"' . "\n\r" . ']', $str))
				$str = '"' . str_replace('"', '""', $str) . '"';
			$arr[] = $str;
		}
		return join($glue, $arr);
	}
//----- End func.php -----//

//----- Start make_link.php -----//
	// PukiWiki - Yet another WikiWikiWeb clone.
	// $Id: pukiwiki_func.php,v 1.180 2008/07/02 23:59:46 nao-pon Exp $
	// Copyright (C)
	//   2003-2005 PukiWiki Developers Team
	//   2001-2002 Originally written by yu-ji
	// License: GPL v2 or (at your option) any later version
	//
	// Hyperlink-related functions
	
	// Hyperlink decoration
	function make_link($string, $page = '')
	{
		static $converter = array();
		static $converter_pool = array();
	
		if (! isset($converter[$this->xpwiki->pid])) $converter[$this->xpwiki->pid] = new XpWikiInlineConverter($this->xpwiki);
	
		if (! isset($converter_pool[$this->xpwiki->pid])) {
			$converter_pool[$this->xpwiki->pid] = array();
			$clone = NULL;
		} else {
			$clone = array_shift($converter_pool[$this->xpwiki->pid]);
		}
		if ($clone === NULL) {
			$clone = $converter[$this->xpwiki->pid]->get_clone($converter[$this->xpwiki->pid]);
		}
	
		$result = $clone->convert($string, ($page !== '') ? $page : $this->root->vars['page']);
		$converter_pool[$this->xpwiki->pid][] = $clone; // For recycling
	
		return $result;
	}
	
	// Make hyperlink for the page
	function make_pagelink($page, $alias = '', $anchor = '', $refer = '', $class = 'pagelink', $options = array())
	{
		// check alias page
		if (!$this->is_page($page) && isset($this->root->page_aliases[$page])) {
			if (!$alias) $alias = $page;
			$page = $this->root->page_aliases[$page];
		}

		$s_page = htmlspecialchars($this->strip_bracket($page));
		
		if ($page && !$this->is_pagename($page)) {
			return $s_page;
		}
		
		if ($this->root->page_case_insensitive) {
			$this->get_pagename_realcase ($page);
		}
		
		$compact_base = false;
		if (preg_match('/^#compact:(.+)/', $alias, $match)) {
			$alias = '';
			$compact_base = trim($match[1]);
		}
		if ($alias) {
			$s_alias = $alias;
		} else {
			$s_alias = ($this->root->pagename_num2str && $this->is_page($page)) ? preg_replace('/\/(?:[0-9\-]+|[B0-9][A-Z0-9]{9})$/','/'.$this->get_heading($page),$s_page) : $s_page;
		}
		if ($compact_base) {
			$s_alias = preg_replace('/^'.preg_quote(htmlspecialchars($compact_base).'/', '/').'/', '', $s_alias);
		}
		if ($this->root->hierarchy_insert) {
			$s_alias = preg_replace('#((?:^|\G|>)[^<]*?)/#', '$1/' . $this->root->hierarchy_insert, $s_alias);
		}
		
		// Remake
		$s_page = htmlspecialchars($page);
	
		if ($page === '') return '<a href="' . $anchor . '" class="'.$class.'">' . $s_alias . '</a>';
		
		if ($class === 'autolink' && $page === $this->root->vars['page']) {
			return '<span class="thispage">' . $s_alias . '</span>';
		}
		
		$r_page  = rawurlencode($page);
		$r_refer = ($refer === '') ? '' : '&amp;refer=' . rawurlencode($refer);
	
		if (! isset($this->root->related[$page]) && $page !== $this->root->vars['page'] && $this->is_page($page))
			$this->root->related[$page] = $this->get_filetime($page);
	
		if (! isset($this->root->notyets[$page]) && $page !== $this->root->vars['page'] && !$this->is_page($page))
			$this->root->notyets[$page] = TRUE;

		if ($class === 'autolink' || !empty($options['nocheck']) || $this->is_page($page)) {
			// ownpage
			if ($this->root->vars['cmd'] === 'read' && $this->cont['PAGENAME'] === $page && $anchor === '') {
				return '<span class="thispage">' . $s_alias . '</span>';
			}
			
			// Hyperlink to the page
			if ($this->root->link_compact) {
				$title   = '';
			} else {
				$title   = ' title="' . $s_page . $this->get_pg_passage($page, FALSE) . '"';
			}
	
			// AutoLink marker
			if ($class === 'autolink') {
				$al_left  = '<!--autolink-->';
				$al_right = '<!--/autolink-->';
			} else {
				$al_left = $al_right = '';
			}
			
			// Popup link (renderer)
			if ($this->root->render_mode === 'render' && ($this->root->render_popuplink === 1 || ($this->root->render_popuplink === 2 && $class === 'autolink')) && !isset($options['popup']['use'])) {
				$options['popup']['use'] = 1;
				static $popup_pos = NULL;
				if (is_null($popup_pos)) {
					$popup_pos = '';
					foreach(array('top', 'left', 'bottom', 'right', 'width', 'height') as $_prm) {
						if (isset($this->root->render_popuplink_position[$_prm])) {
							if (preg_match('/^(\d+)(%|p(?:x|c|t)|e(?:m|x)|in|(?:c|m)m)?/', $this->root->render_popuplink_position[$_prm], $_match)) {
							 	if (empty($_match[2])) $_match[2] = 'px';
							 	$popup_pos .= ',' . $_prm . ':\'' . $_match[1] . $_match[2] . '\'';
							}
						}
					}
				}
				$options['popup']['position'] = $popup_pos;
			}
			
			// Popup link
			$onclick = '';
			if (isset($options['popup']['use'])) {
				$onclick = ' onclick="return XpWiki.pagePopup({dir:\'' . htmlspecialchars($this->root->mydirname, ENT_QUOTES) .
				'\',page:\'' . htmlspecialchars($page . $anchor, ENT_QUOTES) . '\'' .
				(isset($options['popup']['position'])? $options['popup']['position'] : '' ) .
				'});"';
				$class .= '_popup';
			}
			
			$link = ($this->root->vars['cmd'] === 'read' && $this->cont['PAGENAME'] === $page)? '' : $this->get_page_uri($page, TRUE);
			return $al_left . '<a ' . 'href="' . $link . $anchor .
				'"' . $title . ' class="' . $class . '"' . $onclick . '>' . $s_alias . '</a>' . $al_right;
		} else {
			// Dangling link
			if ($this->cont['PKWK_READONLY'] === 1 || ! $this->check_editable($page,false,false)) return $s_alias; // No dacorations
			
			$title = htmlspecialchars(str_replace('$1', $page, $this->root->_title_edit));
			$retval = $s_alias . '<a href="' .
				$this->root->script . '?cmd=edit&amp;page=' . $r_page . $r_refer . '" class="' . $class . '" title="' . $title . '">' .
				$this->root->_symbol_noexists . '</a>';
	
			if ($this->root->link_compact) {
				return $retval;
			} else {
				return '<span class="noexists">' . $retval . '</span>';
			}
		}
	}
	
	// Resolve relative / (Unix-like)absolute path of the page
	function get_fullname($name, $refer)
	{
		// 'Here'
		if ($name === '' || $name === './') return $refer;
	
		// Absolute path
		if ($name{0} === '/') {
			$name = substr($name, 1);
			return ($name === '') ? $this->root->defaultpage : $name;
		}
	
		// Relative path from 'Here'
		if (substr($name, 0, 2) === './') {
			$arrn    = preg_split('#/#', $name, -1, PREG_SPLIT_NO_EMPTY);
			$arrn[0] = $refer;
			return join('/', $arrn);
		}
	
		// Relative path from dirname()
		if (substr($name, 0, 3) === '../') {
			$arrn = preg_split('#/#', $name,  -1, PREG_SPLIT_NO_EMPTY);
			$arrp = preg_split('#/#', $refer, -1, PREG_SPLIT_NO_EMPTY);
	
			while (! empty($arrn) && $arrn[0] === '..') {
				array_shift($arrn);
				array_pop($arrp);
			}
			$name = ! empty($arrp) ? join('/', array_merge($arrp, $arrn)) :
				(! empty($arrn) ? $this->root->defaultpage . '/' . join('/', $arrn) : $this->root->defaultpage);
		}
	
		return $name;
	}
	
	// Render an InterWiki into a URL
	function & get_interwiki_url($name, & $param)
	{
		static $interwikinames = array();
		static $encode_aliases = array('sjis'=>'SJIS', 'euc'=>'EUC-JP', 'utf8'=>'UTF-8');
		$false = FALSE;
		
		if (! isset($interwikinames[$this->root->mydirname])) {
			$interwiki_dat = $this->cont['CACHE_DIR'] . 'interwiki.dat';
			if (file_exists($interwiki_dat)) {
				$interwikinames[$this->root->mydirname] = unserialize(file_get_contents($interwiki_dat));
			} else {
				$interwikinames[$this->root->mydirname] = $this->interwiki_dat_update($this->get_source($this->root->interwiki));
			}
		}
	
		if (! isset($interwikinames[$this->root->mydirname][$name])) {
			// Inner other xpwiki
			if ($this->isXpWikiDirname($name)) {
				$interwikinames[$this->root->mydirname][$name] = array($name, 'inner');
			} else {
				return $false;
			}
		}
	
		list($url, $opt) = $interwikinames[$this->root->mydirname][$name];
		
		// Encoding
		switch ($opt) {
	
		case '':    /* FALLTHROUGH */
		case 'std': // Simply URL-encode the string, whose base encoding is the internal-encoding
			$param = rawurlencode($param);
			break;
	
		case 'asis': /* FALLTHROUGH */
		case 'raw' : // Truly as-is
			$param = $param;
			break;
	
		case 'yw': // YukiWiki
			if (! preg_match('/' . $this->root->WikiName . '/', $param))
				$param = '[[' . mb_convert_encoding($param, 'SJIS', $this->cont['SOURCE_ENCODING']) . ']]';
			break;
	
		case 'moin': // MoinMoin
			$param = str_replace('%', '_', rawurlencode($param));
			break;

		// 二重にURLエンコードする
		case 'dbl':
			$param = rawurlencode(rawurlencode($param));
			break;
					
		// HexEncode系
		case 'hex_utf8':
		case 'wiki_utf8':
			$param = $this->encode(mb_convert_encoding($param,'UTF-8',$this->cont['SOURCE_ENCODING']));
			break;
		case 'hex_sjis':
		case 'wiki_sjis':
			$param = $this->encode(mb_convert_encoding($param,'SJIS',$this->cont['SOURCE_ENCODING']));
			break;
		case 'hex_euc-jp':
		case 'wiki_euc-jp':
			$param = $this->encode(mb_convert_encoding($param,'EUC-JP',$this->cont['SOURCE_ENCODING']));
			break;
		
		// Inner other xpwiki
		case 'inner':
		case 'xpwiki':
			if (strpos($url, '?') !== FALSE) {
				list($url, $prefix) = explode('?', $url, 2);
				$param = $prefix . $param;
			}
			$otherObj = & XpWiki::getInitedSingleton(basename($url));
			if ($otherObj->isXpWiki) {

				if ($param !== '') {
					if (!$otherObj->func->is_pagename($param))
						return $false;
				}

				return $otherObj;
			}
			return $false;
			
			break;
		default:
			// Alias conversion of $opt
			if (isset($encode_aliases[$opt])) $opt = & $encode_aliases[$opt];
	
			// Encoding conversion into specified encode, and URLencode
			$param = rawurlencode(mb_convert_encoding($param, $opt, $this->cont['SOURCE_ENCODING']));
		}
	
		// Replace or Add the parameter
		if (strpos($url, '$1') !== $false) {
			$url = str_replace('$1', $param, $url);
		} else {
			$url .= $param;
		}
	
		if (! preg_match('/(?:(?:https?|ftp|news):\/\/|\.\.?\/)[!~*\'();\/?:\@&=+\$,%#\w.-]*/', $url) || strlen($url) > 512) return $false;
	
		return $url;
	}
//----- End make_link.php -----//

//----- Start trackback.php -----//
	
	// Get TrackBack ID from page name
	function tb_get_id($page)
	{
		return $this->get_pgid_by_name($page);
	}
	
	// Get page name from TrackBack ID
	function tb_id2page($tb_id)
	{
		static $pages, $cache = array();
	
		if (isset($cache[$this->xpwiki->pid][$tb_id])) return $cache[$this->xpwiki->pid][$tb_id];
	
		if (! isset($pages[$this->xpwiki->pid])) $pages[$this->xpwiki->pid] = $this->get_existpages();
		foreach ($pages[$this->xpwiki->pid] as $page) {
			$_tb_id = $this->tb_get_id($page);
			$cache[$this->xpwiki->pid][$_tb_id] = $page;
			unset($pages[$this->xpwiki->pid][$page]);
			if ($tb_id === $_tb_id) return $cache[$this->xpwiki->pid][$tb_id]; // Found
		}
	
		$cache[$this->xpwiki->pid][$tb_id] = FALSE;
		return $cache[$this->xpwiki->pid][$tb_id]; // Not found
	}
	
	// Get file name of TrackBack ping data
	function tb_get_filename($page, $ext = '.txt')
	{
		return $this->cont['TRACKBACK_DIR'] . $this->encode($page) . $ext;
	}
	
	// Count the number of TrackBack pings included for the page
	function tb_count($page, $ext = '.txt')
	{
		$filename = $this->tb_get_filename($page, $ext);
		return file_exists($filename) ? count(file($filename)) : 0;
	}
	
	// Send TrackBack ping(s) automatically
	// $plus  = Newly added lines may include URLs
	// $minus = Removed lines may include URLs
	function tb_send($page, $plus, $minus = '')
	{
		$script = $this->get_script_uri();
	
		// Disable 'max execution time' (php.ini: max_execution_time)
		if (ini_get('safe_mode') == '0') set_time_limit(120);
	
		// Get URLs from <a>(anchor) tag from convert_html()
		$links = array();
		$plus  = $this->convert_html($plus); // WARNING: heavy and may cause side-effect
		preg_match_all('#href="(https?://[^"]+)"#', $plus, $links, PREG_PATTERN_ORDER);
		$links = array_unique($links[1]);
	
		// Reject from minus list
		if ($minus !== '') {
			$links_m = array();
			$minus = $this->convert_html($minus); // WARNING: heavy and may cause side-effect
			preg_match_all('#href="(https?://[^"]+)"#', $minus, $links_m, PREG_PATTERN_ORDER);
			$links_m = array_unique($links_m[1]);
	
			$links = array_diff($links, $links_m);
		}
	
		// Reject own URL (Pattern _NOT_ started with '$script' and '?')
		$links = preg_grep('/^(?!' . preg_quote($script, '/') . '\?)./', $links);
	
		// No link, END
		if (! is_array($links) || empty($links)) return;
	
		$r_page  = rawurlencode($page);
		$excerpt = $this->strip_htmltag($this->convert_html($this->get_source($page)));
	
		// Sender's information
		$putdata = array(
			'title'     => $page, // Title = It's page name
			'url'       => $script . '?' . $r_page, // will be rawurlencode() at send phase
			'excerpt'   => mb_strimwidth(preg_replace("/[\r\n]/", ' ', $excerpt), 0, 255, '...'),
			'blog_name' => $this->root->module_title . ' (' . $this->cont['PLUGIN_TRACKBACK_VERSION'] . ')',
			'charset'   => $this->cont['SOURCE_ENCODING'] // Ping text encoding (Not defined)
		);
	
		foreach ($links as $link) {
			$tb_id = $this->tb_get_url($link);  // Get Trackback ID from the URL
			if (empty($tb_id)) continue; // Trackback is not supported
	
			$result = $this->http_request($tb_id, 'POST', '', $putdata, 2, $this->cont['CONTENT_CHARSET']);
			// FIXME: Create warning notification space at pukiwiki.skin!
		}
	}
	
	// Remove TrackBack ping data
	function tb_delete($page)
	{
		$filename = $this->tb_get_filename($page);
		if (file_exists($filename)) @unlink($filename);
	}
	
	// Import TrackBack ping data from file
	function tb_get($file, $key = 1)
	{
		if (! file_exists($file)) return array();
	
		$result = array();
		$fp = @fopen($file, 'r');
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		rewind($fp);
		while ($data = @fgetcsv($fp, 8192, ',')) {
			// $data[$key] = URL
			$result[rawurldecode($data[$key])] = $data;
		}
		fclose ($fp);
	
		return $result;
	}
	
	// Get a RDF comment to bury TrackBack-ping-URI under HTML(XHTML) output
	function tb_get_rdf($page)
	{
		$_script = $this->get_script_uri(); // Get absolute path
		$r_page = rawurlencode($page);
		$tb_id  = $this->tb_get_id($page);
		// $dcdate = substr_replace(get_date('Y-m-d\TH:i:sO', $time), ':', -2, 0);
		// dc:date="$dcdate"
	
		return <<<EOD
	<!--
	<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	  xmlns:dc="http://purl.org/dc/elements/1.1/"
	  xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
	 <rdf:Description
	   rdf:about="$_script?$r_page"
	   dc:identifier="$_script?$r_page"
	   dc:title="$page"
	   trackback:ping="$_script?tb_id=$tb_id" />
	</rdf:RDF>
	-->
EOD;
	}
	
	// HTTP-GET from $uri, and reveal the TrackBack Ping URL
	function tb_get_url($url)
	{
		// Don't go across HTTP-proxy server
		$parse_url = parse_url($url);
		if (empty($parse_url['host']) ||
		   ($this->root->use_proxy && ! $this->in_the_net($this->root->no_proxy, $parse_url['host'])))
			return '';
	
		$data = $this->http_request($url);
		if ($data['rc'] !== 200) return '';
	
		$matches = array();
		if (! preg_match_all('#<rdf:RDF[^>]*xmlns:trackback=[^>]*>(.*?)</rdf:RDF>#si', $data['data'],
		    $matches, PREG_PATTERN_ORDER))
			return '';
	
		$obj = new TrackBack_XML();
		foreach ($matches[1] as $body) {
			$tb_url = $obj->parse($body, $url);
			if ($tb_url !== FALSE) return $tb_url;
		}
	
		return '';
	}
	
	// Save or update referer data
	function ref_save($page)
	{
		if ($this->cont['PKWK_READONLY'] ||
			! $this->root->referer ||
			! empty($this->cont['page_show']) ||
			empty($_SERVER['HTTP_REFERER'])) return TRUE;
	
		$url = $_SERVER['HTTP_REFERER'];
	
		// Validate URI (Ignore own)
		$parse_url = parse_url($url);
		if (empty($parse_url['host']) || $parse_url['host'] === $_SERVER['HTTP_HOST'])
			return TRUE;
	
		if (! is_dir($this->cont['TRACKBACK_DIR']))      die('No such directory: TRACKBACK_DIR');
		if (! is_writable($this->cont['TRACKBACK_DIR'])) die('Permission denied to write: TRACKBACK_DIR');
	
		// Update referer data
		if (ereg("[,\"\n\r]", $url))
			$url = '"' . str_replace('"', '""', $url) . '"';
	
		$filename = $this->tb_get_filename($page, '.ref');
		$data     = $this->tb_get($filename, 3);
		$d_url    = rawurldecode($url);
		if (! isset($data[$d_url])) {
			$data[$d_url] = array(
				'',    // [0]: Last update date
				$this->cont['UTIME'], // [1]: Creation date
				0,     // [2]: Reference counter
				$url,  // [3]: Referer header
				1      // [4]: Enable / Disable flag (1 = enable)
			);
		}
		$data[$d_url][0] = $this->cont['UTIME'];
		$data[$d_url][2]++;
	
		$fp = fopen($filename, 'w');
		if ($fp === FALSE) return FALSE;	
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		rewind($fp);
		foreach ($data as $line)
			fwrite($fp, join(',', $line) . "\n");
		fclose($fp);
	
		return TRUE;
	}
//----- End trackback.php -----//

//----- Start auth.php -----//
	
	// Passwd-auth related ----
	
	function pkwk_login($pass = '')
	{
		if ($this->root->userinfo['admin']) {
			return TRUE;
		} else if (! $this->cont['PKWK_READONLY'] && isset($this->root->adminpass) &&
			$this->pkwk_hash_compute($pass, $this->root->adminpass) === $this->root->adminpass) {
			return TRUE;
		} else {
			sleep(2);       // Blocking brute force attack
			return FALSE;
		}
	}
	
	// Compute RFC2307 'userPassword' value, like slappasswd (OpenLDAP)
	// $phrase : Pass-phrase
	// $scheme : Specify '{scheme}' or '{scheme}salt'
	// $prefix : Output with a scheme-prefix or not
	// $canonical : Correct or Preserve $scheme prefix
	function pkwk_hash_compute($phrase = '', $scheme = '{x-php-md5}', $prefix = TRUE, $canonical = FALSE)
	{
		if (! is_string($phrase) || ! is_string($scheme)) return FALSE;
	
		if (strlen($phrase) > $this->cont['PKWK_PASSPHRASE_LIMIT_LENGTH'])
			die('pkwk_hash_compute(): malicious message length');
	
		// With a {scheme}salt or not
		$matches = array();
		if (preg_match('/^(\{.+\})(.*)$/', $scheme, $matches)) {
			$scheme = & $matches[1];
			$salt   = & $matches[2];
		} else if ($scheme !== '') {
			$scheme  = ''; // Cleartext
			$salt    = '';
		}
	
		// Compute and add a scheme-prefix
		switch (strtolower($scheme)) {
	
		// PHP crypt()
		case '{x-php-crypt}' :
			$hash = ($prefix ? ($canonical ? '{x-php-crypt}' : $scheme) : '') .
				($salt !== '' ? crypt($phrase, $salt) : crypt($phrase));
			break;
	
		// PHP md5()
		case '{x-php-md5}'   :
			$hash = ($prefix ? ($canonical ? '{x-php-md5}' : $scheme) : '') .
				md5($phrase);
			break;
	
		// PHP sha1()
		case '{x-php-sha1}'  :
			$hash = ($prefix ? ($canonical ? '{x-php-sha1}' : $scheme) : '') .
				sha1($phrase);
			break;
	
		// LDAP CRYPT
		case '{crypt}'       :
			$hash = ($prefix ? ($canonical ? '{CRYPT}' : $scheme) : '') .
				($salt !== '' ? crypt($phrase, $salt) : crypt($phrase));
			break;
	
		// LDAP MD5
		case '{md5}'         :
			$hash = ($prefix ? ($canonical ? '{MD5}' : $scheme) : '') .
				base64_encode($this->hex2bin(md5($phrase)));
			break;
	
		// LDAP SMD5
		case '{smd5}'        :
			// MD5 Key length = 128bits = 16bytes
			$salt = ($salt !== '' ? substr(base64_decode($salt), 16) : substr(crypt(''), -8));
			$hash = ($prefix ? ($canonical ? '{SMD5}' : $scheme) : '') .
				base64_encode($this->hex2bin(md5($phrase . $salt)) . $salt);
			break;
	
		// LDAP SHA
		case '{sha}'         :
			$hash = ($prefix ? ($canonical ? '{SHA}' : $scheme) : '') .
				base64_encode($this->hex2bin(sha1($phrase)));
			break;
	
		// LDAP SSHA
		case '{ssha}'        :
			// SHA-1 Key length = 160bits = 20bytes
			$salt = ($salt !== '' ? substr(base64_decode($salt), 20) : substr(crypt(''), -8));
			$hash = ($prefix ? ($canonical ? '{SSHA}' : $scheme) : '') .
				base64_encode($this->hex2bin(sha1($phrase . $salt)) . $salt);
			break;
	
		// LDAP CLEARTEXT and just cleartext
		case '{cleartext}'   : /* FALLTHROUGH */
		case ''              :
			$hash = ($prefix ? ($canonical ? '{CLEARTEXT}' : $scheme) : '') .
				$phrase;
			break;
	
		// Invalid scheme
		default:
			$hash = FALSE;
			break;
		}
	
		return $hash;
	}
	
	
	// Basic-auth related ----
	
	// Check edit-permission
	function check_editable($page, $auth_flag = TRUE, $exit_flag = TRUE)
	{
		if ($this->edit_auth($page, $auth_flag, $exit_flag) && $this->is_editable($page)) {
			// Editable
			return TRUE;
		} else {
			// Not editable
			if ($exit_flag === FALSE) {
				return FALSE; // Without exit
			} else {
				// With exit
				$body = $title = str_replace('$1',
					htmlspecialchars($this->strip_bracket($page)), $this->root->_title_cannotedit);
				if ($this->is_freeze($page))
					$body .= '(<a href="' . $this->root->script . '?cmd=unfreeze&amp;page=' .
						rawurlencode($page) . '">' . $this->root->_msg_unfreeze . '</a>)';
				$page = str_replace('$1', $this->make_search($page), $this->root->_title_cannotedit);
				$this->catbody($title, $page, $body);
				exit;
			}
		}
	}
	
	// Check read-permission
	function check_readable($page, $auth_flag = TRUE, $exit_flag = TRUE)
	{
		return $this->read_auth($page, $auth_flag, $exit_flag);
	}
	
	function edit_auth($page, $auth_flag = TRUE, $exit_flag = TRUE)
	{
		if (!$this->check_editable_page($page, $auth_flag, $exit_flag)) {
			return FALSE;
		}
		return $this->root->edit_auth ?  $this->basic_auth($page, $auth_flag, $exit_flag,
		$this->root->edit_auth_pages, $this->root->_title_cannotedit) : TRUE;
	}
	
	function read_auth($page, $auth_flag = TRUE, $exit_flag = TRUE)
	{
		if (!$this->check_readable_page($page, $auth_flag, $exit_flag)) {
			return FALSE;
		}
		return $this->root->read_auth ?  $this->basic_auth($page, $auth_flag, $exit_flag,
		$this->root->read_auth_pages, $this->root->_title_cannotread) : TRUE;
	}
	
	// Basic authentication
	function basic_auth($page, $auth_flag, $exit_flag, $auth_pages, $title_cannot)
	{
		// Checked by:
		$target_str = '';
		if ($this->root->auth_method_type === 'pagename') {
			$target_str = $page; // Page name
		} else if ($this->root->auth_method_type === 'contents') {
			$target_str = $this->get_source($page, TRUE, TRUE); // Its contents
		}
	
		$user_list = array();
		foreach($auth_pages as $key=>$val)
			if (preg_match($key, $target_str))
				$user_list = array_merge($user_list, explode(',', $val));
	
		if (empty($user_list)) return TRUE; // No limit
	
		$matches = array();
		if (! isset($_SERVER['PHP_AUTH_USER']) &&
			! isset($_SERVER ['PHP_AUTH_PW']) &&
			isset($_SERVER['HTTP_AUTHORIZATION']) &&
			preg_match('/^Basic (.*)$/', $_SERVER['HTTP_AUTHORIZATION'], $matches))
		{
	
			// Basic-auth with $_SERVER['HTTP_AUTHORIZATION']
			list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) =
				explode(':', base64_decode($matches[1]));
		}
	
		if ($this->cont['PKWK_READONLY'] ||
			! isset($_SERVER['PHP_AUTH_USER']) ||
			! in_array($_SERVER['PHP_AUTH_USER'], $user_list) ||
			! isset($this->root->auth_users[$_SERVER['PHP_AUTH_USER']]) ||
			$this->pkwk_hash_compute(
				$_SERVER['PHP_AUTH_PW'],
				$this->root->auth_users[$_SERVER['PHP_AUTH_USER']]
				) !== $this->root->auth_users[$_SERVER['PHP_AUTH_USER']])
		{
			// Auth failed
			if (empty($this->cont['page_show'])) {
				$this->pkwk_common_headers();
				if ($auth_flag) {
					header('WWW-Authenticate: Basic realm="' . $this->root->_msg_auth . '"');
					header('HTTP/1.0 401 Unauthorized');
				}
				if ($exit_flag) {
					$body = $title = str_replace('$1',
						htmlspecialchars($this->strip_bracket($page)), $title_cannot);
					$page = str_replace('$1', $this->make_search($page), $title_cannot);
					$this->catbody($title, $page, $body);
					exit;
				}
			}
			return FALSE;
		} else {
			return TRUE;
		}
	}
//----- End auth.php -----//

//----- Start backup.php -----//
	
	function make_backup($page, $delete = FALSE, $notimestamp = FALSE)
	{
		if ($this->cont['PKWK_READONLY'] === 1 || ! $this->root->do_backup) return;
	
		if ($this->root->del_backup && $delete) {
			$this->_backup_delete($page);
			return;
		}
	
		if (! $this->is_page($page)) return;
		
		$backups = $this->get_backup($page);
		$count = count($backups);

		// Are those who update it different from last time?
		$pginfo_last = $this->get_pginfo($page);
		$diff_user = ! (($this->root->userinfo['uid'] && $pginfo_last['lastuid'] === $this->root->userinfo['uid']) || ($this->root->userinfo['ucd'] && $pginfo_last['lastucd'] === $this->root->userinfo['ucd']));		

		// Rotation judgment
		if (! $diff_user && $notimestamp) {
			// The time stamp was not renewed in last time and the same user.
			$rotate = FALSE;
		} else if ($diff_user && $this->root->backup_everytime_others) {
			// Setting that rotates without fail when different user updating.
			$rotate = TRUE;
		} else {
			// Normal rotation judgment
			$lastmod = $this->_backup_get_filetime($page);
			$rotate = $lastmod === 0 || $this->cont['UTIME'] - $lastmod > 60 * 60 * $this->root->cycle;
		}

		if ($rotate) {
			$count++;
			// The element that exceeds one addition (Maximum - 1) to the immediate aftermath is thrown away.
			if ($count > $this->root->maxage)
				array_splice($backups, 0, $count - $this->root->maxage);
		
			$strout = '';
			foreach($backups as $age=>$data) {
				$strout .= $this->cont['PKWK_SPLITTER'] . ' ' . $data['time'] . "\n"; // Splitter format
				$strout .= join('', $data['data']);
				unset($backups[$age]);
			}
			$strout = preg_replace("/([^\n])\n*$/", "$1\n", $strout);
	
			// Escape 'lines equal to PKWK_SPLITTER', by inserting a space
			$body = preg_replace('/^(' . preg_quote($this->cont['PKWK_SPLITTER']) . "\s\d+)$/", '$1 ', $this->get_source($page));
			$body = $this->cont['PKWK_SPLITTER'] . ' ' . $this->get_filetime($page) . "\n" . join('', $body);
			//$body = $this->cont['PKWK_SPLITTER'] . ' ' . $this->cont['UTIME'] . "\n" . join('', $body);
			$body = preg_replace("/\n*$/", "\n", $body);
	
			$fp = $this->_backup_fopen($page, 'wb')
				or $this->die_message('Cannot open ' . htmlspecialchars($this->_backup_get_filename($page)) .
				'<br />Maybe permission is not writable or filename is too long');
			$this->_backup_fputs($fp, $strout);
			$this->_backup_fputs($fp, $body);
			$this->_backup_fclose($fp);
		}
	}
	function get_backup($page, $age = 0, $data_age = '')
	{
		$lines = $this->_backup_file($page);
		if (! is_array($lines)) return array();
		
		$data_ages = explode(',', $data_age);

		$_age = 0;
		$retvars = $match = array();
		$regex_splitter = '/^' . preg_quote($this->cont['PKWK_SPLITTER']) . '\s(\d+)$/';
		$linecnt = 0;
		$temp_last = array();
		foreach($lines as $index => $line) {
			$line = rtrim($line) . "\n";
			if (preg_match($regex_splitter, $line, $match)) {
				$linecnt = 0;
				// A splitter, tells new data of backup will come
				++$_age;
				if ($age > 0 && $_age > $age) return $retvars[$age];
	
				// Allocate
				$temp_last = $retvars[$_age] = array('time'=>$match[1], 'data'=>array());
			} else {
				// The first ... the last line of the data
				$linecnt++;
				if (!$data_age || in_array($_age, $data_ages) || $linecnt < 3) {
					$retvars[$_age]['data'][] = $line;
				}
				$temp_last['data'][] = $line;
			}
			unset($lines[$index]);
		}
		if ($age > 0 && $_age >= $age) return $retvars[$age];
		if ($temp_last && in_array('last', $data_ages)) {
			$retvars[$_age] = $temp_last;
		}
		return $retvars;
	}
	function _backup_get_filename($page)
	{
		return $this->cont['BACKUP_DIR'] . $this->encode($page) . $this->cont['BACKUP_EXT'];
	}
	function _backup_file_exists($page)
	{
		return file_exists($this->_backup_get_filename($page));
	}
	
	function _backup_get_filetime($page)
	{
		return $this->_backup_file_exists($page) ?
			filemtime($this->_backup_get_filename($page)) - $this->cont['LOCALZONE'] : 0;
	}
	function _backup_delete($page)
	{
		return unlink($this->_backup_get_filename($page));
	}
//----- End backup.php -----//

//----- Start diff.php -----//
	
	// Create diff-style data between arrays
	function do_diff($strlines1, $strlines2)
	{
		$obj = new XpWikiline_diff();
		$str = $obj->str_compare(rtrim($strlines1), rtrim($strlines2));
		$obj = null;
		return $str;
	}
	
	// Visualize diff-style-text to text-with-CSS
	//   '+Added'   => '<span added>Added</span>'
	//   '-Removed' => '<span removed>Removed</span>'
	//   ' Nothing' => 'Nothing'
	function diff_style_to_css($str = '')
	{
		// Cut diff markers ('+' or '-' or ' ')
		$str = preg_replace('/^\-(.*)$/m', '<span class="diff_removed">$1</span>', $str);
		$str = preg_replace('/^\+(.*)$/m', '<span class="diff_added"  >$1</span>', $str);
		return preg_replace('/^ (.*)$/m',  '$1', $str);
	}
	
	// Merge helper (when it conflicts)
	function do_update_diff($pagestr, $poststr, $original)
	{
		$obj = new XpWikiline_diff();
	
		$obj->set_str('left', $original, $pagestr);
		$obj->compare();
		$diff1 = $obj->toArray();
	
		$obj->set_str('right', $original, $poststr);
		$obj->compare();
		$diff2 = $obj->toArray();
	
		$arr = $obj->arr_compare('all', $diff1, $diff2);

		$obj = null;
	
		if ($this->cont['PKWK_DIFF_SHOW_CONFLICT_DETAIL']) {
	
			$this->root->do_update_diff_table = <<<EOD
	<p>l : between backup data and stored page data.<br />
	 r : between backup data and your post data.</p>
	<table class="style_table">
	 <tr>
	  <th>l</th>
	  <th>r</th>
	  <th>text</th>
	 </tr>
EOD;
			$tags = array('th', 'th', 'td');
			foreach ($arr as $_obj) {
				$this->root->do_update_diff_table .= '<tr>';
				$params = array($_obj->get('left'), $_obj->get('right'), $_obj->text());
				foreach ($params as $key=>$text) {
					$text = htmlspecialchars($text);
					if (trim($text) === '') $text = '&nbsp;';
					$this->root->do_update_diff_table .= '<' . $tags[$key] .
						' class="style_' . $tags[$key] . '">' . $text .
						'</' . $tags[$key] . '>';
				}
				$this->root->do_update_diff_table .= '</tr>' . "\n";
			}
			$this->root->do_update_diff_table .= '</table>' . "\n";
		}
	
		$body = '';
		foreach ($arr as $_obj) {
			if ($_obj->get('left') !== '-' && $_obj->get('right') !== '-')
				$body .= $_obj->text();
		}
	
		$auto = 1;

		return array(rtrim($body) . "\n", $auto);
	}
//----- End diff.php -----//

//----- Start html.php -----//
	// PukiWiki - Yet another WikiWikiWeb clone.
	// $Id: pukiwiki_func.php,v 1.180 2008/07/02 23:59:46 nao-pon Exp $
	// Copyright (C)
	//   2002-2006 PukiWiki Developers Team
	//   2001-2002 Originally written by yu-ji
	// License: GPL v2 or (at your option) any later version
	//
	// HTML-publishing related functions
	
	// Show page-content
	function catbody($title, $page, $body)
	{
		// #noattach
		if (isset($this->root->nonflag['attach'])) {
			$attach =& $this->get_plugin_instance('attach');
			$attach->listed = TRUE;	
		}

		// #norelated
		if (isset($this->root->nonflag['related'])) {
			$this->root->related_link = 0;
		}

		// #nopagecomment
		if (isset($this->root->nonflag['pagecomment'])) {
			$this->root->allow_pagecomment = FALSE;
		}

		if ($this->cont['UA_PROFILE'] !== 'keitai') $body = '<div id="xpwiki_body">'.$body.'</div>';
		
		$_LINK = $this->root->_IMAGE = array();
	
		// Add JavaScript header when ...
		if ($this->root->trackback && $this->root->trackback_javascript) $this->root->javascript = 1; // Set something If you want
		if (! $this->cont['PKWK_ALLOW_JAVASCRIPT']) unset($this->root->javascript);
	
		$_page  = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
		$r_page = rawurlencode($_page);
		
		// Page infomation
		$pginfo = $this->get_pginfo($_page);
		
		// Pagename alias
		$pginfo['alias'] = join(', ',$this->get_page_alias($_page, true));
		
		// Set skin functions
		$navigator = create_function('&$this, $key, $value = \'\', $javascript = \'\'', 'return XpWikiFunc::skin_navigator($this, $key, $value, $javascript);');
		$toolbar   = create_function('&$this, $key, $x = 20, $y = 20',                  'return XpWikiFunc::skin_toolbar($this, $key, $x, $y);');
		$ajax_edit_js = ($this->root->use_ajax_edit)? ' onclick="return xpwiki_ajax_edit(\''.htmlspecialchars($r_page, ENT_QUOTES).'\');"' : '';
		
		// Set $_LINK for skin
		$_LINK['add']      = "{$this->root->script}?cmd=add&amp;page=$r_page#{$this->root->mydirname}_header";
		$_LINK['backup']   = "{$this->root->script}?cmd=backup&amp;page=$r_page#{$this->root->mydirname}_header";
		$_LINK['copy']     = "{$this->root->script}?plugin=template&amp;refer=$r_page#{$this->root->mydirname}_header";
		$_LINK['diff']     = "{$this->root->script}?cmd=backup&amp;page=$r_page&amp;action=diff#{$this->root->mydirname}_header";
		$_LINK['edit']     = "{$this->root->script}?cmd=edit&amp;page=$r_page#{$this->root->mydirname}_header";
		$_LINK['filelist'] = "{$this->root->script}?cmd=filelist#{$this->root->mydirname}_header";
		$_LINK['attaches'] = "{$this->root->script}?plugin=attach&pcmd=list#{$this->root->mydirname}_header";
		$_LINK['freeze']   = "{$this->root->script}?cmd=freeze&amp;page=$r_page#{$this->root->mydirname}_header";
		$_LINK['pginfo']   = "{$this->root->script}?cmd=pginfo&amp;page=$r_page#{$this->root->mydirname}_header";
		$_LINK['help']     = "{$this->root->script}?" . rawurlencode($this->root->help_page) . "#{$this->root->mydirname}_header";
		$_LINK['list']     = "{$this->root->script}?cmd=list#{$this->root->mydirname}_header";
		$_LINK['new']      = "{$this->root->script}?plugin=newpage&amp;refer=$r_page#{$this->root->mydirname}_header";
		$_LINK['rdf']      = "{$this->root->script}?cmd=rss&amp;ver=1.0";
		$_LINK['recent']   = "{$this->root->script}?" . rawurlencode($this->root->whatsnew) . "#{$this->root->mydirname}_header";
		$_LINK['refer']    = "{$this->root->script}?plugin=referer&amp;page=$r_page#{$this->root->mydirname}_header";
		$_LINK['related']  = "{$this->root->script}?plugin=related&amp;page=$r_page#{$this->root->mydirname}_header";
		$_LINK['reload']   = "{$this->root->script}" . $this->get_page_uri($_page);
		$_LINK['rename']   = "{$this->root->script}?plugin=rename&amp;refer=$r_page#{$this->root->mydirname}_header";
		$_LINK['rss']      = "{$this->root->script}?cmd=rss";
		$_LINK['rss10']    = "{$this->root->script}?cmd=rss&amp;ver=1.0"; // Same as 'rdf'
		$_LINK['rss20']    = "{$this->root->script}?cmd=rss&amp;ver=2.0";
		$_LINK['atom']     = "{$this->root->script}?cmd=rss&amp;ver=atom";
		$_LINK['search']   = "{$this->root->script}?cmd=search#{$this->root->mydirname}_header";
		$_LINK['top']      = $this->get_page_uri($this->root->defaultpage, true);
		if ($this->root->trackback) {
			$tb_id = $this->tb_get_id($_page);
			$_LINK['trackback'] = "{$this->root->script}?plugin=tb&amp;__mode=view&amp;tb_id=$tb_id";
		}
		$_LINK['unfreeze'] = "{$this->root->script}?cmd=unfreeze&amp;page=$r_page#{$this->root->mydirname}_header";
		$_LINK['upload']   = "{$this->root->script}?plugin=attach&amp;pcmd=upload&amp;page=$r_page#{$this->root->mydirname}_header";
		
		// Set _LANG
		$_LANG =& $this->root->_LANG;
		
		// Compat: Skins for 1.4.4 and before
		$link_add       = & $_LINK['add'];
		$link_new       = & $_LINK['new'];	// New!
		$link_edit      = & $_LINK['edit'];
		$link_diff      = & $_LINK['diff'];
		$link_top       = & $_LINK['top'];
		$link_list      = & $_LINK['list'];
		$link_filelist  = & $_LINK['filelist'];
		$link_search    = & $_LINK['search'];
		$link_whatsnew  = & $_LINK['recent'];
		$link_backup    = & $_LINK['backup'];
		$link_help      = & $_LINK['help'];
		$link_trackback = & $_LINK['trackback'];	// New!
		$link_rdf       = & $_LINK['rdf'];		// New!
		$link_rss       = & $_LINK['rss'];
		$link_rss10     = & $_LINK['rss10'];		// New!
		$link_rss20     = & $_LINK['rss20'];		// New!
		$link_freeze    = & $_LINK['freeze'];
		$link_unfreeze  = & $_LINK['unfreeze'];
		$link_upload    = & $_LINK['upload'];
		$link_template  = & $_LINK['copy'];
		$link_refer     = & $_LINK['refer'];	// New!
		$link_rename    = & $_LINK['rename'];
	
		// Init flags
		// ブロック用にglobal変数にも保存
		$GLOBALS['Xpwiki_'.$this->root->mydirname]['is_page']     = $is_page = ($this->is_pagename($_page) && ! $this->arg_check('backup') && $_page !== $this->root->whatsnew);
		$GLOBALS['Xpwiki_'.$this->root->mydirname]['is_read']     = $is_read = ($this->arg_check('read') && $this->is_page($_page));
		$GLOBALS['Xpwiki_'.$this->root->mydirname]['is_freeze']   = $is_freeze = $this->is_freeze($_page);
		$GLOBALS['Xpwiki_'.$this->root->mydirname]['is_admin']    = $is_admin = $this->root->userinfo['admin'];
		$GLOBALS['Xpwiki_'.$this->root->mydirname]['is_owner']    = $is_owner = $this->is_owner($_page);
		$GLOBALS['Xpwiki_'.$this->root->mydirname]['is_editable'] = $is_editable = $this->check_editable($_page, FALSE, FALSE);
		$GLOBALS['Xpwiki_'.$this->root->mydirname]['is_top']      = $is_top = ($_page === $this->root->defaultpage)? TRUE : FALSE;
		$GLOBALS['Xpwiki_'.$this->root->mydirname]['page']        = $_page;
		$GLOBALS['Xpwiki_'.$this->root->mydirname]['pgid']        = (int)@$this->root->get['pgid'];
		
		$trackback_javascript = $this->root->trackback_javascript;
		
		// nofollow
		if ($this->root->nofollow || ! $is_read) {
			$this->root->head_pre_tags[] = '<meta name="robots" content="NOINDEX,NOFOLLOW" />';
		}
		
		// Page Comments
		$page_comments = ($is_read && $this->root->allow_pagecomment && $this->root->enable_pagecomment)? '<div id="pageComments" class="NoWikiHelper">' . $this->get_page_comments($_page) . '</div>' : '';
		$page_comments_count = ($page_comments)? '<a href="#pageComments">' . $this->root->_LANG['skin']['comments'] . '(' . $this->count_page_comments($_page) . ')</a>': '';
		
		// System notification
		if ($this->root->show_system_notification_skin) {
			$system_notification = $this->get_notification_select();
			if ($system_notification) {
				$system_notification = '<div class="system_notification"><hr class="notification" />'.$system_notification.'</div>';
			}
		} else {
			$system_notification = '';
		}
		
		// Countup counter
		if ($is_page && $this->exist_plugin_convert('counter')) {
			$this->do_plugin_convert('counter');
		}
		
		// Last modification date (string) of the page
		$lastmodified = $is_read ?  $this->format_date($this->get_filetime($_page)) .
			' (' . $this->cont['ZONE'] . ')' .
			' ' . $this->get_pg_passage($_page, FALSE) : '';
	
		// List of attached files to the page
		$plugin = & $this->get_plugin_instance("attach");
		$attaches = ($this->root->attach_link && $is_read && $this->exist_plugin_action('attach')) ?
		$plugin->attach_filelist() : '';
	
		// List of related pages
		$related  = ($this->root->related_link && $is_read) ? $this->make_related($_page, '', $this->root->related_show_max) : '';
	
		// List of footnotes
		ksort($this->root->foot_explain, SORT_NUMERIC);
		$notes = ! empty($this->root->foot_explain) ? $this->root->note_hr . join("\n", $this->root->foot_explain) : '';
		
		// Head Tags
		list($head_pre_tag, $head_tag) = $this->get_additional_headtags();
		$cssprefix = $this->root->css_prefix ? 'pre=' . rawurlencode($this->root->css_prefix) . '&amp;' : '';
		
		// 1.3.x compat
		// Last modification date (UNIX timestamp) of the page
		$fmt = $is_read ? $this->get_filetime($_page) + $this->cont['LOCALZONE'] : 0;
	
		// Search words
		if ($this->root->search_word_color && isset($this->root->vars['word'])) {
			$body = '<div class="small">' . $this->root->_msg_word
			      . preg_replace('/&amp;#(\d+;)/', '&#$1', htmlspecialchars($this->root->vars['word']))
			      . '</div>' . $this->root->hr . "\n" . $body;
	
			// BugTrack2/106: Only variables can be passed by reference from PHP 5.0.5
			// with array_splice(), array_flip()
			$words = preg_split('/\s+/', $this->root->vars['word'], -1, PREG_SPLIT_NO_EMPTY);
			$words = array_splice($words, 0, 10); // Max: 10 words
			$words = array_flip($words);
	
			$keys = array();
			foreach ($words as $word=>$id) $keys[$word] = strlen($word);
			arsort($keys, SORT_NUMERIC);
			$keys = $this->get_search_words(array_keys($keys), TRUE);
			$id = 0;
			foreach ($keys as $key=>$pattern) {
				$s_key    = htmlspecialchars($key);
				$pattern  = '/' .
					'<textarea[^>]*>.*?<\/textarea>' .	// Ignore textareas
					'|' . '<[^>]*>' .			// Ignore tags
					'|' . ( preg_match('/^(&?#?[\d]+|#?[\d]+;|&#?|#|;)$/', $key)?
					'&[^;]+;' :					// Ignore entities
					'&#[^\d]+;' ) .				// Ignore entities (Not numerical entities only)
					'|' . '(' . $pattern . ')' .		// $matches[1]: Regex for a search word
					'/sS';
				$decorate_Nth_word = create_function(
					'$matches',
					'return (isset($matches[1])) ? ' .
						'\'<strong class="word' .
							$id .
						'">\' . $matches[1] . \'</strong>\' : ' .
						'$matches[0];'
				);
				$body  = preg_replace_callback($pattern, $decorate_Nth_word, $body);
				$notes = preg_replace_callback($pattern, $decorate_Nth_word, $notes);
				++$id;
			}
		}
	
		$longtaketime = $this->getmicrotime() - $this->cont['MUTIME'];
		$taketime     = sprintf('%01.03f', $longtaketime);
		$this->root->_LINK = $_LINK;
		require($this->cont['SKIN_FILE']);
	}
	
	// Show 'edit' form
	function edit_form($page, $postdata, $digest = FALSE, $b_template = TRUE, $options = array())
	{
		$ajax = (isset($this->root->vars['ajax']));
		
		if (! isset($this->root->vars['orgkey'])) $this->root->vars['orgkey'] = '';
		
		// #pginfo 削除
		$postdata = $this->remove_pginfo($postdata);
		
		// Newly generate $digest or not
		if ($digest === FALSE) $digest = md5($this->get_source($page, TRUE, TRUE));
	
		$refer = $template = '';
	 
	 	// Add plugin
		$addtag = $add_top = '';
		if(isset($this->root->vars['add'])) {
			$addtag  = '<input type="hidden" name="add"    value="true" />';
			$add_top = isset($this->root->vars['add_top']) ? ' checked="checked"' : '';
			$add_top = '<input type="checkbox" name="add_top" ' .
				'id="_edit_form_add_top" value="true"' . $add_top . ' />' . "\n" .
				'  <label for="_edit_form_add_top">' .
				'<span class="small">' . $this->root->_btn_addtop . '</span>' .
				'</label>';
		}
	
		if($this->root->load_template_func && $b_template) {
			$pages  = array();
			foreach($this->get_existpages() as $_page) {
				if ($_page === $this->root->whatsnew || $this->check_non_list($_page))
					continue;
				$s_page = htmlspecialchars($_page);
				$pages[$_page] = '   <option value="' . $s_page . '">' .
				$s_page . '</option>';
			}
			ksort($pages);
			$s_pages  = join("\n", $pages);
			$template_onclick = $ajax ? ' onclick="return xpwiki_ajax_edit_submit(1);"' : '';
			$template = <<<EOD
	  <select name="template_page">
	   <option value="">-- {$this->root->_btn_template} --</option>
	$s_pages
	  </select>
	  <input type="submit" name="template" value="{$this->root->_btn_load}" accesskey="r" onmousedown="(function(){if(\$('edit_write_hidden')){Element.remove(\$('edit_write_hidden'))}})();"{$template_onclick} />
	  <br />
EOD;
	
			if (isset($this->root->vars['refer']) && $this->root->vars['refer'] !== '')
				$refer = '[[' . $this->strip_bracket($this->root->vars['refer']) . ']]' . "\n\n";
		}
		
		$r_page      = rawurlencode($page);
		$s_page      = htmlspecialchars($page);
		$s_id        = isset($this->root->vars['paraid']) ? htmlspecialchars($this->root->vars['paraid']) : '';
		
		if (!$s_id) {
			$othor_option = '<input type="checkbox" id="xpwiki_othor_option" name="other" onclick="Element.toggle($(\'xpwiki_edit_other\'));" /><label for="xpwiki_othor_option"> ' . $this->root->_btn_other_op . '</label>';

			// Othor options
			if (!empty($this->root->rtf['preview'])) {
				$pgtitle_str = htmlspecialchars($this->root->vars['pgtitle']);
				$reading_str = htmlspecialchars($this->root->vars['reading']);
				$alias_str = htmlspecialchars($this->root->vars['alias']);
				$order_val = floatval($this->root->vars['pgorder']);
			} else {
				$pgtitle_str = $this->extract_pgtitle($postdata);
				$reading_str = htmlspecialchars($this->get_page_reading($page));
				$alias_str = htmlspecialchars($this->get_page_alias($page));
				$order_val = floatval($this->get_page_order($page));
			}
			
			$pgtitle = $this->root->_btn_pgtitle . ': <input type="text" name="pgtitle" size="50" value="'.$pgtitle_str.'" /><br />';
			
			if ($this->root->pagereading_enable) {
				$reading = $this->root->_btn_reading . ': <input type="text" name="reading" size="15" value="'.$reading_str.'" />&nbsp;&nbsp; ';
			} else  {
				$reading = '<input type="hidden" name="reading" size="15" value="'.$reading_str.'" />';
			}

			// page order
			$pageorder = $this->root->_btn_pgorder . ': <input type="text" name="pgorder" size="5" value="'.$order_val.'" /><br />';
			
			// alias
			$alias = $this->root->_btn_alias . ': <input type="text" name="alias" size="40" value="'.$alias_str.'" /><br />';
			
			// 添付ファイルリスト
			$attaches = '';
			if (!$ajax && $this->root->show_attachlist_editform && $this->is_page($page)) {
				$plugin = & $this->get_plugin_instance("attach");
				$attaches = ($plugin) ? $plugin->attach_filelist() : '';
				if ($attaches) $attaches = $this->root->hr . '<p>' . $attaches . '</p>';
			}
			$title = '<h3>'.str_replace('$1', $s_page, $this->root->_title_edit).'</h3>';
		} else {
			$othor_option = $pgtitle = $reading = $attaches = $alias = $pageorder = '';
			$title = '<h3>'.str_replace('$1', '# '.$this->root->vars['paraid'], $this->root->_title_edit).'</h3>';
		}
		
		$originalkey = '';
		$s_postdata  = htmlspecialchars($refer . $postdata);
		$originalkey = htmlspecialchars((string)$this->root->vars['orgkey']);
		$s_digest    = htmlspecialchars($digest);
		$b_preview   = isset($this->root->vars['preview']); // TRUE when preview
		$btn_preview = $b_preview ? $this->root->_btn_repreview : $this->root->_btn_preview;
		
		// Q & A 認証
		$riddle = '';
		if (isset($options['riddle'])) {
			$riddle = '<p>' . $this->root->_btn_riddle . '<br />' .
				'&nbsp;&nbsp;<strong>Q:</strong> ' . htmlspecialchars($options['riddle']) . '<br />' .
				'&nbsp;&nbsp;<strong>A:</strong> <input type="text" name="riddle'.md5($this->cont['HOME_URL'].$options['riddle']) .
				'" size="30" value="" autocomplete="off" onkeyup="(function(e){if(e.value&&!$(\'edit_write_hidden\')){var w=document.createElement(\'input\');w.id=\'edit_write_hidden\';w.type=\'hidden\';w.name=\'write\';e.parentNode.appendChild(w);}})(this)" /><br />' .
				'</p>';	
		}
	
		// Checkbox 'do not change timestamp'
		$add_notimestamp = '&nbsp; ';
		if ($this->is_page($page) && ($this->root->notimeupdate === 1 || ($this->root->notimeupdate > 1 && $this->root->userinfo['admin']))) {
			$checked_time = isset($this->root->vars['notimestamp']) ? ' checked="checked"' : '';
			$add_notimestamp = '<input type="checkbox" name="notimestamp" ' .
				'id="_edit_form_notimestamp" value="true"' . $checked_time . ' />' . "\n" .
				'   ' . '<label for="_edit_form_notimestamp"><span class="small">' .
				$this->root->_btn_notchangetimestamp . '</span></label>' . "\n" .
				$add_notimestamp .
				'&nbsp;';
		}
		
		// popup
		$popup = ($this->root->viewmode === 'popup')? '<input type="hidden" name="popup" value="1" />' : '';
		
		if ($ajax) {
			$ajax_submit = ' onSubmit="return xpwiki_ajax_edit_submit()"';
			$ajax_cancel = ' onSubmit="return xpwiki_ajax_edit_cancel()"';
			$nonconvert = (empty($this->vars['nonconvert']))? '' : '<input type="hidden" name="nonconvert" value="1" />';
			$enc_hint = '<input type="hidden" name="encode_hint" value="' . $this->cont['PKWK_ENCODING_HINT'] . '" />'
			          . '<input type="hidden" name="charset" value="UTF-8" />';
			$attaches = '';
			if ($s_id) {
				$othor_option = $template = $reading = $alias = $pageorder = '';
				$form_class = 'edit_form_ajax';
			} else {
				$form_class = 'edit_form';
			}
			$othor_hide = 'style="display:none;"';
			$othor_hide_js = '';
		} else {
			$nonconvert = $ajax_submit = $ajax_cancel = $enc_hint = $othor_hide = '';
			$form_class = 'edit_form';
			$othor_hide_js = '<script type="text/javascript">$(\'xpwiki_edit_other\').style.display = \'none\';</script>';
		}
		
		// 'margin-bottom', 'float:left', and 'margin-top'
		// are for layout of 'cancel button'
		$body = <<<EOD
<div class="{$form_class}">
 $title
 <form action="{$this->root->script}" method="post" style="margin-bottom:0px;" id="xpwiki_edit_form"{$ajax_submit}>
  $template
  $addtag
  $othor_option
  <div id="xpwiki_edit_other" {$othor_hide}>
  $pgtitle
  $reading
  $pageorder
  $alias
  </div>
  $nonconvert
  $enc_hint
  $popup
  <input type="hidden" name="cmd"    value="edit" />
  <input type="hidden" name="page"   value="$s_page" />
  <input type="hidden" name="digest" value="$s_digest" />
  <input type="hidden" name="paraid" value="$s_id" />
  <input type="hidden" name="orgkey" value="$originalkey" />
  <textarea id="xpwiki_edit_textarea" name="msg" rows="{$this->root->rows}" cols="{$this->root->cols}">$s_postdata</textarea>
  $riddle
  <div style="float:left;">
   <input type="submit" name="preview" value="$btn_preview" accesskey="p" id="edit_preview" onmousedown="(function(){if(\$('edit_write_hidden')){Element.remove(\$('edit_write_hidden'))};xpwiki_ajax_edit_var['mode']='preview';})();" />
   <input type="submit" name="write"   value="{$this->root->_btn_update}" accesskey="s" id="edit_write" onmousedown="(function(){if(\$('edit_write_hidden')){Element.remove(\$('edit_write_hidden'))};xpwiki_ajax_edit_var['mode']='write';})();" />
   $add_top
   $add_notimestamp
  </div>
  $s_original
 </form>
 $othor_hide_js
 <div id="xpwiki_cancel_form">
 <form action="{$this->root->script}" method="post" style="margin-top:0px;"{$ajax_cancel}>
  <input type="hidden" name="cmd"    value="edit" />
  <input type="hidden" name="page"   value="$s_page" />
  <input type="hidden" name="paraid" value="$s_id" />
  <input type="submit" name="cancel" value="{$this->root->_btn_cancel}" accesskey="c" />
 </form>
 </div>
</div>
$attaches
EOD;
	
		if (isset($this->root->vars['help'])) {
			$body .= $this->root->hr . $this->catrule();
		} else {
			$sdir = htmlspecialchars($this->root->mydirname, ENT_QUOTES);
			$body .= '<ul><li><a class="pagelink_popup" href="' .
				$this->root->script . '?cmd=edit&amp;help=true&amp;page=' . $r_page .
				'" onclick="return XpWiki.pagePopup({dir:\''.$sdir.'\',page:\'FormattingRules\'});">' . $this->root->_msg_help . '</a></li></ul>';
		}
	
		return $body;
	}
	
	// Related pages
	function make_related($page, $tag = '', $max = 0)
	{
		$links = $this->links_get_related($page);
	
		if (!$max && $tag) {
			ksort($links);
		} else {
			arsort($links);
		}
	
		$_links = array();
		$i = 0;
		foreach ($links as $_page=>$lastmod) {
			if ($this->check_non_list($_page)) continue;
			
			$i++;
			//$r_page   = rawurlencode($page);
			//$s_page   = htmlspecialchars($page);
			//$passage  = $this->get_passage($lastmod);
			//$_links[] = $tag ?
			//	'<a href="' . $this->root->script . '?' . $r_page . '" title="' .
			//	$s_page . ' ' . $passage . '">' . $s_page . '</a>' :
			//	'<a href="' . $this->root->script . '?' . $r_page . '">' .
			//	$s_page . '</a>' . $passage;
			if ($max && $i > $max) {
				$_links[] = '[ <a href="' . $this->root->script . '?cmd=related&amp;page='.rawurlencode($page).'">Show All</a> ]';
				break;
			}
			$_links[] = $this->make_pagelink($_page);
		}
		if (empty($_links)) return ''; // Nothing
	
		if ($tag === 'p') { // From the line-head
			$margin = $this->root->_ul_left_margin + $this->root->_ul_margin;
			$style  = sprintf($this->root->_list_pad_str, 1, $margin, $margin);
			$retval =  "\n" . '<ul' . $style . '>' . "\n" .
				'<li>' . join($this->root->rule_related_str, $_links) . '</li>' . "\n" .
				'</ul>' . "\n";
		} else if ($tag) {
			$retval = join($this->root->rule_related_str, $_links);
		} else {
			$retval = join($this->root->related_str, $_links);
		}
	
		return $retval;
	}
	
	// User-defined rules (convert without replacing source)
	function make_line_rules($str)
	{
		static $pattern, $replace;
	
		if (! isset($pattern[$this->xpwiki->pid])) {
			$pattern[$this->xpwiki->pid] = array_map(create_function('$a',
				'return \'/\' . $a . \'/\';'), array_keys($this->root->line_rules));
			$replace[$this->xpwiki->pid] = array_values($this->root->line_rules);
			unset($this->root->line_rules);
		}
	
		return preg_replace($pattern[$this->xpwiki->pid], $replace[$this->xpwiki->pid], $str);
	}
	
	// Remove all HTML tags(or just anchor tags), and WikiName-speific decorations
	function strip_htmltag($str, $all = TRUE)
	{
		static $noexists_pattern;
	
		if (! isset($noexists_pattern[$this->xpwiki->pid]))
			$noexists_pattern[$this->xpwiki->pid] = '#<span class="noexists">([^<]*)<a[^>]+>' .
				preg_quote($this->root->_symbol_noexists, '#') . '</a></span>#';
	
		// Strip Dagnling-Link decoration (Tags and "$_symbol_noexists")
		$str = preg_replace($noexists_pattern[$this->xpwiki->pid], '$1', $str);
	
		if ($all) {
			// All other HTML tags
			return preg_replace('#<[^>]+>#',        '', $str);
		} else {
			// All other anchor-tags only
			return preg_replace('#<a[^>]+>|</a>#i', '', $str);
		}
	}
	
	// Remove AutoLink marker with AutLink itself
	function strip_autolink($str)
	{
		return preg_replace('#<!--autolink--><a [^>]+>|</a><!--/autolink-->#', '', $str);
	}
	
	// Make a backlink. searching-link of the page name, by the page name, for the page name
	function make_search($page)
	{
		$s_page = htmlspecialchars($page);
		$r_page = rawurlencode($page);
	
		return '<a href="' . $this->root->script . '?plugin=related&amp;page=' . $r_page .
			'" title="Backlinks for: ' . $s_page . '">' . str_replace('/', '/' . $this->root->hierarchy_insert, $s_page) . '</a> ';
	}
	
	// Make heading string (remove heading-related decorations from Wiki text)
	function make_heading(& $str, $strip = TRUE)
	{
		// Cut fixed-heading anchors
		$id = '';
		$matches = array();
		if (preg_match('/^(\*{0,5})(.*?)\[#([A-Za-z][\w-]+)\](.*?)$/m', $str, $matches)) {
			$str = $matches[2] . $matches[4];
			$id  = & $matches[3];
		} else {
			$str = preg_replace('/^\*{0,5}/', '', $str);
		}
	
		// Cut footnotes and tags
		if ($strip === TRUE)
			$str = $this->strip_htmltag($this->make_link(preg_replace($this->root->NotePattern, '', $str)));
	
		return $id;
	}
	
	// Separate a page-name(or URL or null string) and an anchor
	// (last one standing) without sharp
	function anchor_explode($page, $strict_editable = FALSE)
	{
		$pos = strrpos($page, '#');
		if ($pos === FALSE) return array($page, '', FALSE);
	
		// Ignore the last sharp letter
		if ($pos + 1 === strlen($page)) {
			$pos = strpos(substr($page, $pos + 1), '#');
			if ($pos === FALSE) return array($page, '', FALSE);
		}
	
		$s_page = substr($page, 0, $pos);
		$anchor = substr($page, $pos + 1);
	
		if($strict_editable === TRUE &&  preg_match('/^[a-z][a-f0-9]{7}$/', $anchor)) {
			return array ($s_page, $anchor, TRUE); // Seems fixed-anchor
		} else {
			return array ($s_page, $anchor, FALSE);
		}
	}
	
	// Check HTTP header()s were sent already, or
	// there're blank lines or something out of php blocks
	function pkwk_headers_sent($buf_clear = true)
	{
		if ($this->cont['PKWK_OPTIMISE']) return;
		
		if ($buf_clear) {
			// clear output buffer
			while( ob_get_level() ) {
				ob_end_clean() ;
			}
		}
			
		$file = $line = '';
		if (version_compare(PHP_VERSION, '4.3.0', '>=')) {
			if (headers_sent($file, $line))
			    die('Headers already sent at ' .
			    	htmlspecialchars($file) .
				' line ' . $line . '.');
		} else {
			if (headers_sent())
				die('Headers already sent.');
		}
	}
	
	// Output common HTTP headers
	function pkwk_common_headers()
	{
		if (! $this->cont['PKWK_OPTIMISE']) $this->pkwk_headers_sent(false);
	
		if(isset($this->cont['PKWK_ZLIB_LOADABLE_MODULE'])) {
			$matches = array();
			if(ini_get('zlib.output_compression') &&
			    preg_match('/\b(gzip|deflate)\b/i', $_SERVER['HTTP_ACCEPT_ENCODING'], $matches)) {
			    	// Bug #29350 output_compression compresses everything _without header_ as loadable module
			    	// http://bugs.php.net/bug.php?id=29350
				header('Content-Encoding: ' . $matches[1]);
				header('Vary: Accept-Encoding');
			}
		}
	}
	
	// Output HTML DTD, <html> start tag. Return content-type.
	function pkwk_output_dtd($pkwk_dtd = NULL, $charset = NULL)
	{
		if (is_null($pkwk_dtd)) {$pkwk_dtd = $this->cont['PKWK_DTD_XHTML_1_1'];}
		if (is_null($charset)) {$charset = $this->cont['CONTENT_CHARSET'];}
			
			static $called;
		if (isset($called[$this->xpwiki->pid])) die('pkwk_output_dtd() already called. Why?');
		$called[$this->xpwiki->pid] = TRUE;
	
		$type = $this->cont['PKWK_DTD_TYPE_XHTML'];
		$option = '';
		switch($pkwk_dtd){
		case $this->cont['PKWK_DTD_XHTML_1_1']             :
			$version = '1.1' ;
			$dtd     = 'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd';
			break;
		case $this->cont['PKWK_DTD_XHTML_1_0_STRICT']      :
			$version = '1.0' ;
			$option  = 'Strict';
			$dtd     = 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd';
			break;
		case $this->cont['PKWK_DTD_XHTML_1_0_TRANSITIONAL']:
			$version = '1.0' ;
			$option  = 'Transitional';
			$dtd     = 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd';
			break;
	
		case $this->cont['PKWK_DTD_HTML_4_01_STRICT']      :
			$type    = $this->cont['PKWK_DTD_TYPE_HTML'];
			$version = '4.01';
			$dtd     = 'http://www.w3.org/TR/html4/strict.dtd';
			break;
		case $this->cont['PKWK_DTD_HTML_4_01_TRANSITIONAL']:
			$type    = $this->cont['PKWK_DTD_TYPE_HTML'];
			$version = '4.01';
			$option  = 'Transitional';
			$dtd     = 'http://www.w3.org/TR/html4/loose.dtd';
			break;
	
		default: die('DTD not specified or invalid DTD');
			break;
		}
	
		$charset = htmlspecialchars($charset);
	
		// Output XML or not
		if ($type === $this->cont['PKWK_DTD_TYPE_XHTML']) echo '<?xml version="1.0" encoding="' . $charset . '" ?>' . "\n";
	
		// Output doctype
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD ' .
			($type === $this->cont['PKWK_DTD_TYPE_XHTML'] ? 'XHTML' : 'HTML') . ' ' .
			$version .
			($option !== '' ? ' ' . $option : '') .
			'//EN" "' .
			$dtd .
			'">' . "\n";
	
		// Output <html> start tag
		echo '<html';
		if ($type === $this->cont['PKWK_DTD_TYPE_XHTML']) {
			echo ' xmlns="http://www.w3.org/1999/xhtml"'; // dir="ltr" /* LeftToRight */
			echo ' xml:lang="' . $this->cont['LANG'] . '"';
			if ($version === '1.0') echo ' lang="' . $this->cont['LANG'] . '"'; // Only XHTML 1.0
		} else {
			echo ' lang="' . $this->cont['LANG'] . '"'; // HTML
		}
		echo '>' . "\n"; // <html>
	
		// Return content-type (with MIME type)
		if ($type === $this->cont['PKWK_DTD_TYPE_XHTML']) {
			// NOTE: XHTML 1.1 browser will ignore http-equiv
			return '<meta http-equiv="content-type" content="application/xhtml+xml; charset=' . $charset . '" />' . "\n";
		} else {
			return '<meta http-equiv="content-type" content="text/html; charset=' . $charset . '" />' . "\n";
		}
	}
//----- End html.php -----//

//----- Start mail.php -----//
	// PukiWiki - Yet another WikiWikiWeb clone.
	// $Id: pukiwiki_func.php,v 1.180 2008/07/02 23:59:46 nao-pon Exp $
	// Copyright (C)
	//   2003-2005 PukiWiki Developers Team
	//   2003      Originally written by upk
	// License: GPL v2 or (at your option) any later version
	//
	// E-mail related functions
	
	// Send a mail to the administrator
	function pkwk_mail_notify($subject, $message, $footer = array())
	{
		static $_to, $_headers, $_after_pop;
	
		// Init and lock
		if (! isset($_to[$this->xpwiki->pid])) {
			if (! $this->cont['PKWK_OPTIMISE']) {
				// Validation check
				$func = 'pkwk_mail_notify(): ';
				$mail_regex   = '/[^@]+@[^@]{1,}\.[^@]{2,}/';
				if (! preg_match($mail_regex, $this->root->notify_to))
					die($func . 'Invalid $this->root->notify_to');
				if (! preg_match($mail_regex, $this->root->notify_from))
					die($func . 'Invalid $this->root->notify_from');
				if ($this->root->notify_header !== '') {
					$header_regex = "/\A(?:\r\n|\r|\n)|\r\n\r\n/";
					if (preg_match($header_regex, $this->root->notify_header))
						die($func . 'Invalid $this->root->notify_header');
					if (preg_match('/^From:/im', $this->root->notify_header))
						die($func . 'Redundant \'From:\' in $this->root->notify_header');
				}
			}
	
			$_to[$this->xpwiki->pid]      = $this->root->notify_to;
			$_headers[$this->xpwiki->pid] =
				'X-Mailer: PukiWiki/' . $this->cont['S_VERSION'] .
				' PHP/' . phpversion() . "\r\n" .
				'From: ' . $this->root->notify_from;
				
			// Additional header(s) by admin
			if ($this->root->notify_header !== '') $_headers[$this->xpwiki->pid] .= "\r\n" . $this->root->notify_header;
	
			$_after_pop[$this->xpwiki->pid] = $this->root->smtp_auth;
		}
	
		if ($subject === '' || ($message === '' && empty($footer))) return FALSE;
	
		// Subject:
		if (isset($footer['PAGE'])) $subject = str_replace('$page', $footer['PAGE'], $subject);
	
		// Footer
		if (isset($footer['REMOTE_ADDR'])) $footer['REMOTE_ADDR'] = & $_SERVER['REMOTE_ADDR'];
		if (isset($footer['USER_AGENT']))
			$footer['USER_AGENT']  = '(' . $this->cont['UA_PROFILE'] . ') ' . $this->cont['UA_NAME'] . '/' . $this->cont['UA_VERS'];
		if (! empty($footer)) {
			$_footer = '';
			if ($message !== '') $_footer = "\n" . str_repeat('-', 30) . "\n";
			foreach($footer as $key => $value)
				$_footer .= $key . ': ' . $value . "\n";
			$message .= $_footer;
		}
	
		// Wait POP/APOP auth completion
		if ($_after_pop[$this->xpwiki->pid]) {
			$result = $this->pop_before_smtp();
			if ($result !== TRUE) die($result);
		}
	
		ini_set('SMTP', $this->root->smtp_server);
		mb_language($this->cont['LANG']);
		if ($_headers[$this->xpwiki->pid] === '') {
			return mb_send_mail($_to[$this->xpwiki->pid], $subject, $message);
		} else {
			return mb_send_mail($_to[$this->xpwiki->pid], $subject, $message, $_headers[$this->xpwiki->pid]);
		}
	}
	
	// APOP/POP Before SMTP
	function pop_before_smtp($pop_userid = '', $pop_passwd = '',
		$pop_server = 'localhost', $pop_port = 110)
	{
		$pop_auth_use_apop = TRUE;	// Always try APOP, by default
		$must_use_apop     = FALSE;	// Always try POP for APOP-disabled server
		if (isset($GLOBALS['pop_auth_use_apop'])) {
			// Force APOP only, or POP only
			$pop_auth_use_apop = $must_use_apop = $GLOBALS['pop_auth_use_apop'];
		}
	
		// Compat: GLOBALS > function arguments
		foreach(array('pop_userid', 'pop_passwd', 'pop_server', 'pop_port') as $global) {
			if(isset($GLOBALS[$global]) && $GLOBALS[$global] !== '')
				$$global = $GLOBALS[$global];
		}
	
		// Check
		$die = '';
		foreach(array('pop_userid', 'pop_server', 'pop_port') as $global)
			if($$global === '') $die .= 'pop_before_smtp(): $' . $global . ' seems blank' . "\n";
		if ($die) return ($die);
	
		// Connect
		$errno = 0; $errstr = '';
		$fp = @fsockopen($pop_server, $pop_port, $errno, $errstr, 30);
		if (! $fp) return ('pop_before_smtp(): ' . $errstr . ' (' . $errno . ')');
	
		// Greeting message from server, may include <challenge-string> of APOP
		$message = fgets($fp, 1024); // 512byte max
		if (! preg_match('/^\+OK/', $message)) {
			fclose($fp);
			return ('pop_before_smtp(): Greeting message seems invalid');
		}
	
		$challenge = array();
		if ($pop_auth_use_apop &&
		   (preg_match('/<.*>/', $message, $challenge) || $must_use_apop)) {
			$method = 'APOP'; // APOP auth
			if (! isset($challenge[0])) {
				$response = md5($this->cont['UTC']); // Someting worthless but variable
			} else {
				$response = md5($challenge[0] . $pop_passwd);
			}
			fputs($fp, 'APOP ' . $pop_userid . ' ' . $response . "\r\n");
		} else {
			$method = 'POP'; // POP auth
			fputs($fp, 'USER ' . $pop_userid . "\r\n");
			$message = fgets($fp, 1024); // 512byte max
			if (! preg_match('/^\+OK/', $message)) {
				fclose($fp);
				return ('pop_before_smtp(): USER seems invalid');
			}
			fputs($fp, 'PASS ' . $pop_passwd . "\r\n");
		}
	
		$result = fgets($fp, 1024); // 512byte max, auth result
		$auth   = preg_match('/^\+OK/', $result);
	
		if ($auth) {
			fputs($fp, 'STAT' . "\r\n"); // STAT, trigger SMTP relay!
			$message = fgets($fp, 1024); // 512byte max
		}
	
		// Disconnect anyway
		fputs($fp, 'QUIT' . "\r\n");
		$message = fgets($fp, 1024); // 512byte max, last '+OK'
		fclose($fp);
	
		if (! $auth) {
			return ('pop_before_smtp(): ' . $method . ' authentication failed');
		} else {
			return TRUE;	// Success
		}
	}
//----- End mail.php -----//

//----- Start config.php -----//
//----- End config.php -----//

//----- Start proxy.php -----//
		function http_request($url, $method = 'GET', $headers = '', $post = array(),
		$redirect_max = NULL, $content_charset = '')
	{
	if (is_null($redirect_max)) {$redirect_max = $this->cont['PKWK_HTTP_REQUEST_URL_REDIRECT_MAX'];}
		$rc  = array();
		$arr = parse_url($url);
	
		$via_proxy = $this->root->use_proxy ? ! $this->in_the_net($this->root->no_proxy, $arr['host']) : FALSE;
	
		// query
		$arr['query'] = isset($arr['query']) ? '?' . $arr['query'] : '';
		// port
		$arr['port']  = isset($arr['port'])  ? $arr['port'] : 80;
	
		$url_base = $arr['scheme'] . '://' . $arr['host'] . ':' . $arr['port'];
		$url_path = isset($arr['path']) ? $arr['path'] : '/';
		$url = ($via_proxy ? $url_base : '') . $url_path . $arr['query'];
	
		$query = $method . ' ' . $url . ' HTTP/1.0' . "\r\n";
		$query .= 'Host: ' . $arr['host'] . "\r\n";
		$query .= 'User-Agent: Mozilla/5.0(xpWiki/' . $this->cont['S_VERSION'] . ")\r\n";
	
		// Basic-auth for HTTP proxy server
		if ($this->root->need_proxy_auth && isset($this->root->proxy_auth_user) && isset($this->root->proxy_auth_pass))
			$query .= 'Proxy-Authorization: Basic '.
				base64_encode($this->root->proxy_auth_user . ':' . $this->root->proxy_auth_pass) . "\r\n";
	
		// (Normal) Basic-auth for remote host
		if (isset($arr['user']) && isset($arr['pass']))
			$query .= 'Authorization: Basic '.
				base64_encode($arr['user'] . ':' . $arr['pass']) . "\r\n";
	
		$query .= $headers;
	
		if (strtoupper($method) === 'POST') {
			// 'application/x-www-form-urlencoded', especially for TrackBack ping
			$POST = array();
			foreach ($post as $name=>$val) $POST[] = $name . '=' . urlencode($val);
			$data = join('&', $POST);
	
			if (preg_match('/^[a-zA-Z0-9_-]+$/', $content_charset)) {
				// Legacy but simple
				$query .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
			} else {
				// With charset (NOTE: Some implementation may hate this)
				$query .= 'Content-Type: application/x-www-form-urlencoded' .
					'; charset=' . strtolower($content_charset) . "\r\n";
			}
	
			$query .= 'Content-Length: ' . strlen($data) . "\r\n";
			$query .= "\r\n";
			$query .= $data;
		} else {
			$query .= "\r\n";
		}
	
		$errno  = 0;
		$errstr = '';
		$fp = fsockopen(
			$via_proxy ? $this->root->proxy_host : $arr['host'],
			$via_proxy ? $this->root->proxy_port : $arr['port'],
			$errno, $errstr, 30);
		if ($fp === FALSE) {
			return array(
				'query'  => $query, // Query string
				'rc'     => $errno, // Error number
				'header' => '',     // Header
				'data'   => $errstr // Error message
			);
		}
		fputs($fp, $query);
		$response = '';
		while (! feof($fp)) $response .= fread($fp, 4096);
		fclose($fp);
	
		$resp = explode("\r\n\r\n", $response, 2);
		$rccd = explode(' ', $resp[0], 3); // array('HTTP/1.1', '200', 'OK\r\n...')
		$rc   = (integer)$rccd[1];
	
		switch ($rc) {
		case 301: // Moved Permanently
		case 302: // Moved Temporarily
			$matches = array();
			if (preg_match('/^Location: (.+)$/m', $resp[0], $matches)
				&& --$redirect_max > 0)
			{
				$url = trim($matches[1]);
				if (! preg_match('/^https?:\//', $url)) {
					// Relative path to Absolute
					if ($url{0} !== '/')
						$url = substr($url_path, 0, strrpos($url_path, '/')) . '/' . $url;
					$url = $url_base . $url; // Add sheme, host
				}
				// Redirect
				return $this->http_request($url, $method, $headers, $post, $redirect_max);
			}
		}
		return array(
			'query'  => $query,   // Query String
			'rc'     => $rc,      // Response Code
			'header' => $resp[0], // Header
			'data'   => $resp[1]  // Data
		);
	}
	
	// Check if the $host is in the specified network(s)
	function in_the_net($networks = array(), $host = '')
	{
		if (empty($networks) || $host === '') return FALSE;
		if (! is_array($networks)) $networks = array($networks);
	
		$matches = array();
	
		if (preg_match($this->cont['PKWK_CIDR_NETWORK_REGEX'], $host, $matches)) {
			$ip = $matches[1];
		} else {
			$ip = gethostbyname($host); // May heavy
		}
		$l_ip = ip2long($ip);
	
		foreach ($networks as $network) {
			if (preg_match($this->cont['PKWK_CIDR_NETWORK_REGEX'], $network, $matches) &&
			    is_long($l_ip) && long2ip($l_ip) === $ip) {
				// $host seems valid IPv4 address
				// Sample: '10.0.0.0/8' or '10.0.0.0/255.0.0.0'
				$l_net = ip2long($matches[1]); // '10.0.0.0'
				$mask  = isset($matches[2]) ? $matches[2] : 32; // '8' or '255.0.0.0'
				$mask  = is_numeric($mask) ?
					pow(2, 32) - pow(2, 32 - $mask) : // '8' means '8-bit mask'
					ip2long($mask);                   // '255.0.0.0' (the same)
	
				if (($l_ip & $mask) === $l_net) return TRUE;
			} else {
				// $host seems not IPv4 address. May be a DNS name like 'foobar.example.com'?
				foreach ($networks as $network)
					if (preg_match('/\.?\b' . preg_quote($network, '/') . '$/', $host))
						return TRUE;
			}
		}
	
		return FALSE; // Not found
	}
//----- End proxy.php -----//

//----- Start link.php -----//
	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: pukiwiki_func.php,v 1.180 2008/07/02 23:59:46 nao-pon Exp $
	// Copyright (C) 2003-2006 PukiWiki Developers Team
	// License: GPL v2 or (at your option) any later version
	//
	// Backlinks / AutoLinks related functions
	
	// ------------------------------------------------------------
	// DATA STRUCTURE of *.ref and *.rel files
	
	// CACHE_DIR/encode('foobar').ref
	// ---------------------------------
	// Page-name1<tab>0<\n>
	// Page-name2<tab>1<\n>
	// ...
	// Page-nameN<tab>0<\n>
	//
	//	0 = Added when link(s) to 'foobar' added clearly at this page
	//	1 = Added when the sentence 'foobar' found from the page
	//	    by AutoLink feature
	
	// CACHE_DIR/encode('foobar').rel
	// ---------------------------------
	// Page-name1<tab>Page-name2<tab> ... <tab>Page-nameN
	//
	//	List of page-names linked from 'foobar'
	
	// ------------------------------------------------------------
	
	
	// データベースから関連ページを得る
	function links_get_related_db($page)
	{
		$ref_name = $this->cont['CACHE_DIR'] . $this->encode($page) . '.ref';
		if (! file_exists($ref_name)) return array();
	
		$times = array();
		foreach (file($ref_name) as $line) {
			list($_page) = explode("\t", rtrim($line));
			$time = $this->get_filetime($_page);	
			if($time !== 0) $times[$_page] = $time;
		}
		return $times;
	}
	
	//ページの関連を更新する
	function links_update($page)
	{
		if ($this->cont['PKWK_READONLY']) return; // Do nothing
	
		if (ini_get('safe_mode') == '0') set_time_limit(120);
	
		$time = $this->is_page($page, TRUE) ? $this->get_filetime($page) : 0;
	
		$rel_old        = array();
		$rel_file       = $this->cont['CACHE_DIR'] . $this->encode($page) . '.rel';
		$rel_file_exist = file_exists($rel_file);
		if ($rel_file_exist === TRUE) {
			$lines = file($rel_file);
			unlink($rel_file);
			if (isset($lines[0]))
				$rel_old = explode("\t", rtrim($lines[0]));
		}
		$rel_new  = array(); // 参照先
		$rel_auto = array(); // オートリンクしている参照先
		$links    = $this->links_get_objects($page, TRUE);
		foreach ($links as $_obj) {
			if (! isset($_obj->type) || $_obj->type !== 'pagename' ||
			    $_obj->name === $page || $_obj->name === '')
				continue;
	
			if (is_a($_obj, 'Link_autolink')) { // 行儀が悪い
				$rel_auto[] = $_obj->name;
			} else if (is_a($_obj, 'Link_autoalias')) {
				$_alias = $this->get_autoaliases($_obj->name);
				if ($this->is_pagename($_alias)) {
					$rel_auto[] = $_alias;
				}
			} else {
				$rel_new[]  = $_obj->name;
			}
		}
		$rel_new = array_unique($rel_new);
		
		// autolinkしか向いていないページ
		$rel_auto = array_diff(array_unique($rel_auto), $rel_new);
	
		// 全ての参照先ページ
		$rel_new = array_merge($rel_new, $rel_auto);
	
		// .rel:$pageが参照しているページの一覧
		if ($time) {
			// ページが存在している
			if (! empty($rel_new)) {
	    			$fp = fopen($rel_file, 'w')
	    				or $this->die_message('cannot write ' . htmlspecialchars($rel_file));
				fputs($fp, join("\t", $rel_new));
				fclose($fp);
			}
		}
	
		// .ref:$_pageを参照しているページの一覧
		$this->links_add($page, array_diff($rel_new, $rel_old), $rel_auto);
		$this->links_delete($page, array_diff($rel_old, $rel_new));
	
		// $pageが新規作成されたページで、AutoLinkの対象となり得る場合
		if ($time && ! $rel_file_exist && $this->root->autolink
			&& (preg_match("/^{$this->root->WikiName}$/", $page) ? $this->root->nowikiname : strlen($page) >= $this->root->autolink))
		{
			// $pageを参照していそうなページを一斉更新する(おい)
			$this->root->search_non_list = 1;
			$pages           = $this->do_search($page, 'AND', TRUE);
			foreach ($pages as $_page) {
				if ($_page !== $page)
					$this->links_update($_page);
			}
		}
		$ref_file = $this->cont['CACHE_DIR'] . $this->encode($page) . '.ref';
	
		// $pageが削除されたときに、
		if (! $time && file_exists($ref_file)) {
			foreach (file($ref_file) as $line) {
				list($ref_page, $ref_auto) = explode("\t", rtrim($line));
	
				// $pageをAutoLinkでしか参照していないページを一斉更新する(おいおい)
				if ($ref_auto)
					$this->links_delete($ref_page, array($page));
			}
		}
	}
	
	// Init link cache (Called from link plugin)
	function links_init()
	{
		if ($this->cont['PKWK_READONLY']) return; // Do nothing
	
	
		// Init database
		foreach ($this->get_existfiles($this->cont['CACHE_DIR'], '.ref') as $cache)
			unlink($cache);
		foreach ($this->get_existfiles($this->cont['CACHE_DIR'], '.rel') as $cache)
			unlink($cache);
	
		$ref   = array(); // 参照元
		foreach ($this->get_existpages() as $page) {
			if ($page === $this->root->whatsnew) continue;
			
			if (ini_get('safe_mode') === '0') set_time_limit(60);
			
			$rel   = array(); // 参照先
			$links = $this->links_get_objects($page);
			foreach ($links as $_obj) {
				if (! isset($_obj->type) || $_obj->type !== 'pagename' ||
				    $_obj->name === $page || $_obj->name === '')
					continue;
	
				$_name = $_obj->name;
				if (is_a($_obj, 'Link_autoalias')) {
					$_alias = $this->get_autoaliases($_name);
					if (! $this->is_pagename($_alias))
						continue;	// not PageName
					$_name = $_alias;
				}
				$rel[] = $_name;
				if (! isset($ref[$_name][$page]))
					$ref[$_name][$page] = 1;
				if (! is_a($_obj, 'Link_autolink'))
					$ref[$_name][$page] = 0;
			}
			$rel = array_unique($rel);
			if (! empty($rel)) {
				$fp = fopen($this->cont['CACHE_DIR'] . $this->encode($page) . '.rel', 'w')
					or $this->die_message('cannot write ' . htmlspecialchars($this->cont['CACHE_DIR'] . $this->encode($page) . '.rel'));
				fputs($fp, join("\t", $rel));
				fclose($fp);
			}
		}
	
		foreach ($ref as $page=>$arr) {
			$fp  = fopen($this->cont['CACHE_DIR'] . $this->encode($page) . '.ref', 'w')
				or $this->die_message('cannot write ' . htmlspecialchars($this->cont['CACHE_DIR'] . $this->encode($page) . '.ref'));
			foreach ($arr as $ref_page=>$ref_auto)
				fputs($fp, $ref_page . "\t" . $ref_auto . "\n");
			fclose($fp);
		}
	}
	
	function links_add($page, $add, $rel_auto)
	{
		if ($this->cont['PKWK_READONLY']) return; // Do nothing
	
		$rel_auto = array_flip($rel_auto);
		
		foreach ($add as $_page) {
			$all_auto = isset($rel_auto[$_page]);
			$is_page  = $this->is_page($_page);
			$ref      = $page . "\t" . ($all_auto ? 1 : 0) . "\n";
	
			$ref_file = $this->cont['CACHE_DIR'] . $this->encode($_page) . '.ref';
			if (file_exists($ref_file)) {
				foreach (file($ref_file) as $line) {
					list($ref_page, $ref_auto) = explode("\t", rtrim($line));
					if (! $ref_auto) $all_auto = FALSE;
					if ($ref_page !== $page) $ref .= $line;
				}
				unlink($ref_file);
			}
			if ($is_page || ! $all_auto) {
				$fp = fopen($ref_file, 'w')
					 or $this->die_message('cannot write ' . htmlspecialchars($ref_file));
				fputs($fp, $ref);
				fclose($fp);
			}
		}
	}
	
	function links_delete($page, $del)
	{
		if ($this->cont['PKWK_READONLY']) return; // Do nothing
	
		foreach ($del as $_page) {
			$ref_file = $this->cont['CACHE_DIR'] . $this->encode($_page) . '.ref';
			if (! file_exists($ref_file)) continue;
	
			$all_auto = TRUE;
			$is_page = $this->is_page($_page);
	
			$ref = '';
			foreach (file($ref_file) as $line) {
				list($ref_page, $ref_auto) = explode("\t", rtrim($line));
				if ($ref_page !== $page) {
					if (! $ref_auto) $all_auto = FALSE;
					$ref .= $line;
				}
			}
			unlink($ref_file);
			if (($is_page || ! $all_auto) && $ref !== '') {
				$fp = fopen($ref_file, 'w')
					or $this->die_message('cannot write ' . htmlspecialchars($ref_file));
				fputs($fp, $ref);
				fclose($fp);
			}
		}
	}
	
	function & links_get_objects($page, $refresh = FALSE)
	{
		static $obj;
	
		if (! isset($obj) || $refresh)
			$obj = & new XpWikiInlineConverter($this->xpwiki, NULL, array('note'));
	
		$result = $obj->get_objects(join('', preg_grep('/^(?!\/\/|\s)./', $this->get_source($page))), $page);
		return $result;
	}
//----- End link.php -----//
}
?>