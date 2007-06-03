<?php
class xpwiki_plugin_edit extends xpwiki_plugin {
	function plugin_edit_init () {


	// PukiWiki - Yet another WikiWikiWeb clone.
	// $Id: edit.inc.php,v 1.20 2007/06/03 05:27:05 nao-pon Exp $
	// Copyright (C) 2001-2006 PukiWiki Developers Team
	// License: GPL v2 or (at your option) any later version
	//
	// Edit plugin (cmd=edit)
	
		// Remove #freeze written by hand
		$this->cont['PLUGIN_EDIT_FREEZE_REGEX'] = '/^(?:#freeze(?!\w)\s*)+/im';
	}
	
	function plugin_edit_action()
	{
		if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits editing');
	
		$page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';

		if ($page && $this->root->page_case_insensitive) {
			$this->func->get_pagename_realcase($page);
			$this->root->get['page'] = $this->root->post['page'] = $this->root->vars['page'] = $page;
		}
		
		$this->func->check_editable($page, true, true);
		
		if (isset($this->root->vars['preview']) || ($this->root->load_template_func && isset($this->root->vars['template']))) {
			return $this->plugin_edit_preview();
		} else if (isset($this->root->vars['write'])) {
			if ($this->func->check_riddle()) {
				return $this->plugin_edit_write();
			} else {
				return $this->plugin_edit_preview(TRUE);
			}
		} else if (isset($this->root->vars['cancel'])) {
			return $this->plugin_edit_cancel();
		}
	
		$source = $this->func->get_source($page);
		$postdata = $this->root->vars['original'] = @join('', $source);
		if (! empty($this->root->vars['paraid'])) {
			$postdata = $this->plugin_edit_parts($this->root->vars['paraid'], $source);
			if ($postdata === FALSE) {
				unset($this->root->vars['paraid']);
				$postdata = $this->root->vars['original']; // なかったことに :)
			}
		}

		if ($postdata == '') $postdata = $this->func->auto_template($page);
		
		// Q & A 認証
		$options = $this->get_riddle();

		return array('msg'=>$this->root->_title_edit, 'body'=>$this->func->edit_form($page, $postdata, FALSE, TRUE, $options));
	}
	
	// Preview
	function plugin_edit_preview($ng_riddle = FALSE)
	{
	//	global $vars;
	//	global $_title_preview, $_msg_preview, $_msg_preview_delete;
	
		$page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
	
		// Loading template
		if (isset($this->root->vars['template_page']) && $this->func->is_page($this->root->vars['template_page'])) {
	
			$this->root->vars['msg'] = join('', $this->func->get_source($this->root->vars['template_page']));
	
			// Cut fixed anchors
			$this->root->vars['msg'] = preg_replace('/^(\*{1,6}.*)\[#[A-Za-z][\w-]+\](.*)$/m', '$1$2', $this->root->vars['msg']);
		}
	
		$this->root->vars['msg'] = preg_replace($this->cont['PLUGIN_EDIT_FREEZE_REGEX'], '', $this->root->vars['msg']);
		$postdata = $this->root->vars['msg'];
	
		if (isset($this->root->vars['add']) && $this->root->vars['add']) {
			if (isset($this->root->vars['add_top']) && $this->root->vars['add_top']) {
				$postdata  = $postdata . "\n\n" . @join('', $this->func->get_source($page));
			} else {
				$postdata  = @join('', $this->func->get_source($page)) . "\n\n" . $postdata;
			}
		}
	
		$body = $this->root->_msg_preview . '<br />' . "\n";
		if ($postdata == '')
			$body .= '<strong>' . $this->root->_msg_preview_delete . '</strong>';
		$body .= '<br />' . "\n";
		
		$this->root->rtf['preview'] = TRUE;
		if ($postdata) {
			$postdata = $this->func->make_str_rules($postdata);
			$postdata = explode("\n", $postdata);
			$postdata = $this->func->drop_submit($this->func->convert_html($postdata));
			$body .= '<div id="preview">' . $postdata . '</div>' . "\n";
		}
		
		// Q & A 認証
		$options = $this->get_riddle();

		$body .= $this->func->edit_form($page, $this->root->vars['msg'], $this->root->vars['digest'], TRUE, $options);
	
		return array('msg'=>(!$ng_riddle)? $this->root->_title_preview : $this->root->_title_ng_riddle, 'body'=>$body);
	}
	
	// Inline: Show edit (or unfreeze text) link
	function plugin_edit_inline()
	{
	//	static $usage = '&edit(pagename#anchor[[,noicon],nolabel])[{label}];';
		static $usage = array();
		if (!isset($usage[$this->xpwiki->pid])) {$usage[$this->xpwiki->pid] = '&edit(pagename#anchor[[,noicon],nolabel])[{label}];';}
	
	//	global $script, $vars, $fixed_heading_anchor_edit;
	
		if ($this->cont['PKWK_READONLY']) return ''; // Show nothing 
	
		// Arguments
		$args = func_get_args();
	
		// {label}. Strip anchor tags only
		$s_label = $this->func->strip_htmltag(array_pop($args), FALSE);
	
		$page    = array_shift($args);
		if ($page == NULL) $page = '';
		$_noicon = $_nolabel = $_paraedit = FALSE;
		foreach($args as $arg){
			switch(strtolower($arg)){
			case ''        :                    break;
			case 'paraedit': $_paraedit = TRUE; break;
			case 'nolabel' : $_nolabel  = TRUE; break;
			case 'noicon'  : $_noicon   = TRUE; break;
			default        : return $usage[$this->xpwiki->pid];
			}
		}
		if ($_paraedit) $_nolabel = TRUE;
		
		// Separate a page-name and a fixed anchor
		list($s_page, $id, $editable) = $this->func->anchor_explode($page, TRUE);
		
		// Default: This one
		if ($s_page == '') $s_page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';

		// 編集権限チェック
		$is_editable = $this->func->check_editable($s_page,FALSE,FALSE);
	
		// $s_page fixed
		$isfreeze = $this->func->is_freeze($s_page);
		$ispage   = $this->func->is_page($s_page);
		if ($_paraedit && ($isfreeze || !$is_editable)) return ''; // Show nothing
	
		// Paragraph edit enabled or not
		$short = htmlspecialchars('Edit');
		if ($this->root->fixed_heading_anchor_edit && $editable && $ispage && ! $isfreeze) {
			// Paragraph editing
			$id    = rawurlencode($id);
			$title = htmlspecialchars(sprintf('Edit %s', $page));
			$icon = '<img src="' . $this->cont['IMAGE_DIR'] . 'paraedit.png' .
			'" width="9" height="9" alt="' .
			$short . '" title="' . $title . '" /> ';
			$class = ' class="anchor_super"';
		} else {
			// Normal editing / unfreeze
			$id    = '';
			if ($isfreeze) {
				$title = 'Unfreeze %s';
				$icon  = 'unfreeze.png';
			} else {
				$title = 'Edit %s';
				$icon  = 'edit.png';
			}
			$title = htmlspecialchars(sprintf($title, $s_page));
			$icon = '<img src="' . $this->cont['IMAGE_DIR'] . $icon .
			'" width="20" height="20" alt="' .
			$short . '" title="' . $title . '" />';
			$class = '';
		}
		if ($_noicon) $icon = ''; // No more icon
		if ($_nolabel) {
			if (!$_noicon) {
				$s_label = '';     // No label with an icon
			} else {
				$s_label = $short; // Short label without an icon
			}
		} else {
			if ($s_label == '') $s_label = $title; // Rich label with an icon
		}
	
		// URL
		if ($isfreeze) {
			$url   = $this->root->script . '?cmd=unfreeze&amp;page=' . rawurlencode($s_page);
		} else {
			$s_id = ($id == '') ? '' : '&amp;paraid=' . $id;
			$url  = $this->root->script . '?cmd=edit&amp;page=' . rawurlencode($s_page) . $s_id;
		}
		$atag  = '<a' . $class . ' href="' . $url . '" title="' . $title . '">';
	//	static $atags = '</a>';
		static $atags = array();
		if (!isset($atags[$this->xpwiki->pid])) {$atags[$this->xpwiki->pid] = '</a>';}
	
		if ($ispage) {
			// Normal edit link
			return $atag . $icon . $s_label . $atags[$this->xpwiki->pid];
		} else {
			// Dangling edit link
			return '<span class="noexists">' . $atag . $icon . $atags[$this->xpwiki->pid] .
			$s_label . $atag . '?' . $atags[$this->xpwiki->pid] . '</span>';
		}
	}
	
	// Write, add, or insert new comment
	function plugin_edit_write()
	{
	//	global $vars, $trackback;
	//	global $_title_collided, $_msg_collided_auto, $_msg_collided, $_title_deleted;
	//	global $notimeupdate, $_msg_invalidpass, $do_update_diff_table;
	
		$page   = isset($this->root->vars['page'])   ? $this->root->vars['page']   : '';
		$add    = isset($this->root->vars['add'])    ? $this->root->vars['add']    : '';
		$digest = isset($this->root->vars['digest']) ? $this->root->vars['digest'] : '';
		$paraid = isset($this->root->vars['paraid']) ? $this->root->vars['paraid'] : '';
	
		$this->root->vars['msg'] = preg_replace($this->cont['PLUGIN_EDIT_FREEZE_REGEX'], '', $this->root->vars['msg']);
		$this->root->vars['msg'] = $this->func->remove_pginfo($this->root->vars['msg']);

		$this->root->vars['original'] = $this->func->remove_pginfo($this->root->vars['original']);
		$msg = & $this->root->vars['msg']; // Reference
		
		// ParaEdit
		$hash = '';
		if ($paraid) {
			$source = preg_split('/([^\n]*\n)/', $this->root->vars['original'], -1,
				PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
			if ($this->plugin_edit_parts($paraid, $source, $msg) !== FALSE) {
				$fullmsg = join('', $source);
			} else {
				// $this->root->vars['msg']だけがページに書き込まれてしまうのを防ぐ。
				$fullmsg = rtrim($this->root->vars['original']) . "\n\n" . $msg;
			}
			$msg = $fullmsg;
			$hash = '#' . $paraid;
		}
		
		// 文末処理
		$msg = rtrim($msg)."\n";
		
		// 改行・TAB・スペースのみだったら削除とみなす
		$msg = preg_replace('/^[ \s]+$/', '', $msg);
	
		$retvars = array();
	
		// Collision Detection
		$oldpagesrc = join('', $this->func->get_source($page));
		$oldpagemd5 = md5($oldpagesrc);
		if ($digest != $oldpagemd5) {
			$this->root->vars['digest'] = $oldpagemd5; // Reset
			unset($this->root->vars['paraid']); // 更新が衝突したら全文編集に切り替え
	
			$original = isset($this->root->vars['original']) ? $this->root->vars['original'] : '';
			$oldpagesrc = $this->func->remove_pginfo($oldpagesrc);
			list($postdata_input, $auto) = $this->func->do_update_diff($oldpagesrc, $msg, $original);
	
			$retvars['msg' ] = $this->root->_title_collided;
			$retvars['body'] = ($auto ? $this->root->_msg_collided_auto : $this->root->_msg_collided) . "\n";
			$retvars['body'] .= $this->root->do_update_diff_table;
			$retvars['body'] .= $this->func->edit_form($page, $postdata_input, $oldpagemd5, FALSE);
			return $retvars;
		}
	
		// Action?
		if ($add) {
			// Add
			if (isset($this->root->vars['add_top']) && $this->root->vars['add_top']) {
				$postdata  = $msg . "\n\n" . @join('', $this->func->get_source($page));
			} else {
				$postdata  = @join('', $this->func->get_source($page)) . "\n\n" . $msg;
			}
		} else {
			// Edit or Remove
			$postdata = & $msg; // Reference
		}

		// NULL POSTING, OR removing existing page
		if ($postdata == '') {
			$this->func->page_write($page, $postdata);
			$retvars['msg' ] = $this->root->_title_deleted;
			$retvars['body'] = str_replace('$1', htmlspecialchars($page), $this->root->_title_deleted);
	
			if ($this->root->trackback) $this->func->tb_delete($page);
	
			return $retvars;
		}
	
		// $notimeupdate: Checkbox 'Do not change timestamp'
		$notimestamp = isset($this->root->vars['notimestamp']) && $this->root->vars['notimestamp'] != '';
		if ($this->root->notimeupdate > 1 && $notimestamp && ! $this->func->pkwk_login($this->root->vars['pass'])) {
			// Enable only administrator & password error
			$retvars['body']  = '<p><strong>' . $this->root->_msg_invalidpass . '</strong></p>' . "\n";
			$retvars['body'] .= $this->func->edit_form($page, $msg, $digest, FALSE);
			return $retvars;
		}

		$this->func->page_write($page, $postdata, $this->root->notimeupdate != 0 && $notimestamp);
		$this->func->pkwk_headers_sent();
		header('Location: ' . $this->func->get_script_uri() . '?' . rawurlencode($page) . $hash);
		exit;
	}
	
	// Cancel (Back to the page / Escape edit page)
	function plugin_edit_cancel()
	{
	//	global $vars;
		// ParaEdit
		$paraid = isset($this->root->vars['paraid']) ? $this->root->vars['paraid'] : '';
		$hash = '';
		if ($paraid) {
			$hash = '#' . $paraid;
		}
		
		$this->func->pkwk_headers_sent();
		header('Location: ' . $this->func->get_script_uri() . '?' . rawurlencode($this->root->vars['page']) . $hash);
		exit;
	}
	
	// ソースの一部を抽出/置換する
	function plugin_edit_parts($id, & $source, $postdata = '')
	{
		$postdata = rtrim($postdata)."\n\n";
		
		// 改行・TAB・スペースのみだったら削除とみなす
		$postdata = preg_replace('/^[ \s]+$/', '', $postdata);
		
		$heads = preg_grep('/^\*{1,6}.+\[#[A-Za-z][\w-]+\].*$/', $source);
		$heads[count($source)] = ''; // Sentinel
	
		while (list($start, $line) = each($heads)) {
			if (preg_match("/\[#$id\]/", $line)) {
				list($end, $line) = each($heads);
				return join('', array_splice($source, $start, $end - $start, $postdata));
			}
		}
		return FALSE;
	}
	
	// Q & A 認証用 $option 取得
	function get_riddle () {
		if ($this->root->userinfo['admin'] ||
			$this->root->riddle_auth === 0 ||
			($this->root->riddle_auth === 1 && $this->root->userinfo['uid'] !== 0)
		) {
			$options = array();
		} else {
			$options['riddle'] = array_rand($this->root->riddles);
		}
		return $options;	
	}
}
?>