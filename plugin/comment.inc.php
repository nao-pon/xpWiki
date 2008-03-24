<?php
class xpwiki_plugin_comment extends xpwiki_plugin {
	function plugin_comment_init () {


	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: comment.inc.php,v 1.6 2008/03/24 09:25:16 nao-pon Exp $
	// Copyright (C)
	//   2002-2005 PukiWiki Developers Team
	//   2001-2002 Originally written by yu-ji
	// License: GPL v2 or (at your option) any later version
	//
	// Comment plugin
	
		$this->cont['PLUGIN_COMMENT_DIRECTION_DEFAULT'] =  '1'; // 1: above 0: below
		$this->cont['PLUGIN_COMMENT_SIZE_MSG'] =   70;
		$this->cont['PLUGIN_COMMENT_SIZE_NAME'] =  15;
	
	// ----
		$this->cont['PLUGIN_COMMENT_FORMAT_MSG'] =   '$msg';
		$this->cont['PLUGIN_COMMENT_FORMAT_NAME'] =  '[[$name]]';
		$this->cont['PLUGIN_COMMENT_FORMAT_NOW'] =   '&new{$now};';
		$this->cont['PLUGIN_COMMENT_FORMAT_STRING'] =  "\x08MSG\x08 -- \x08NAME\x08 \x08NOW\x08";

	}
	
	function plugin_comment_action()
	{
	//	global $script, $vars, $now, $_title_updated, $_no_name;
	//	global $_msg_comment_collided, $_title_comment_collided;
	
		if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits editing');
	
		if (! isset($this->root->vars['msg'])) return array('msg'=>'', 'body'=>''); // Do nothing
	
		$this->root->vars['msg'] = str_replace("\n", '', $this->root->vars['msg']); // Cut LFs
		$head = '';
		$match = array();
		if (preg_match('/^(-{1,2})-*\s*(.*)/', $this->root->vars['msg'], $match)) {
			$head        = & $match[1];
			$this->root->vars['msg'] = & $match[2];
		}
		if ($this->root->vars['msg'] == '') return array('msg'=>'', 'body'=>''); // Do nothing
	
		$comment  = str_replace('$msg', $this->root->vars['msg'], $this->cont['PLUGIN_COMMENT_FORMAT_MSG']);
		if(isset($this->root->vars['name']) || ($this->root->vars['nodate'] != '1')) {
			$_name = (! isset($this->root->vars['name']) || $this->root->vars['name'] == '') ? $this->root->_no_name : $this->root->vars['name'];
			// save name to cookie
			if ($_name) { $this->func->save_name2cookie($_name); }
			$_name = ($_name == '') ? '' : str_replace('$name', $_name, $this->cont['PLUGIN_COMMENT_FORMAT_NAME']);
			$_now  = ($this->root->vars['nodate'] == '1') ? '' :
				str_replace('$now', $this->root->now, $this->cont['PLUGIN_COMMENT_FORMAT_NOW']);
			$comment = str_replace("\x08MSG\x08",  $comment, $this->cont['PLUGIN_COMMENT_FORMAT_STRING']);
			$comment = str_replace("\x08NAME\x08", $_name, $comment);
			$comment = str_replace("\x08NOW\x08",  $_now,  $comment);
		}
		$comment = '-' . $head . ' ' . $comment;
	
		$postdata    = '';
		$comment_no  = 0;
		$above       = (isset($this->root->vars['above']) && $this->root->vars['above'] == '1');
		
		$postdata_old = $this->func->get_source($this->root->vars['refer']);
		$this->func->escape_multiline_pre($postdata_old, TRUE);
		foreach ($postdata_old as $line) {
			if (! $above) $postdata .= $line;
			if (preg_match('/^#comment/i', $line) && $comment_no++ == $this->root->vars['comment_no']) {
				if ($above) {
					$postdata = rtrim($postdata) . "\n" .
					$comment . "\n" .
					"\n";  // Insert one blank line above #commment, to avoid indentation
				} else {
					$postdata = rtrim($postdata) . "\n" .
					$comment . "\n"; // Insert one blank line below #commment
				}
			}
			if ($above) $postdata .= $line;
		}
	
		$title = $this->root->_title_updated;
		$body = '';
		if (md5($this->func->get_source($this->root->vars['refer'], TRUE, TRUE)) !== $this->root->vars['digest']) {
			$title = $this->root->_title_comment_collided;
			$body  = $this->root->_msg_comment_collided . $this->func->make_pagelink($this->root->vars['refer']);
		}
		
		$this->func->escape_multiline_pre($postdata, FALSE);
		$this->func->page_write($this->root->vars['refer'], $postdata);
	
		$retvars['msg']  = $title;
		$retvars['body'] = $body;
	
		$this->root->vars['page'] = $this->root->vars['refer'];
	
		return $retvars;
	}
	
	function plugin_comment_convert()
	{
	//	global $vars, $digest, $_btn_comment, $_btn_name, $_msg_comment;
	//	static $numbers = array();
		static $numbers = array();
		if (!isset($numbers[$this->xpwiki->pid])) {$numbers[$this->xpwiki->pid] = array();}
	//	static $comment_cols = PLUGIN_COMMENT_SIZE_MSG;
		static $comment_cols = array();
		if (!isset($comment_cols[$this->xpwiki->pid])) {$comment_cols[$this->xpwiki->pid] = $this->cont['PLUGIN_COMMENT_SIZE_MSG'];}
	
		if ($this->cont['PKWK_READONLY']) return ''; // Show nothing
	
		if (! isset($numbers[$this->xpwiki->pid][$this->root->vars['page']])) $numbers[$this->xpwiki->pid][$this->root->vars['page']] = 0;
		$comment_no = $numbers[$this->xpwiki->pid][$this->root->vars['page']]++;
	
		$options = func_num_args() ? func_get_args() : array();
		if (in_array('noname', $options)) {
			$nametags = '<label for="_p_comment_comment_' . $comment_no . '">' .
			$this->root->_msg_comment . '</label>';
		} else {
			$nametags = '<label for="_p_comment_name_' . $comment_no . '">' .
			$this->root->_btn_name . '</label>' .
			'<input type="text" name="name" value="'.$this->cont['USER_NAME_REPLACE'].'" id="_p_comment_name_' .
			$comment_no .  '" size="' . $this->cont['PLUGIN_COMMENT_SIZE_NAME'] .
			'" />' . "\n";
		}
		$nodate = in_array('nodate', $options) ? '1' : '0';
		$above  = in_array('above',  $options) ? '1' :
			(in_array('below', $options) ? '0' : $this->cont['PLUGIN_COMMENT_DIRECTION_DEFAULT']);
	
		$script = $this->func->get_script_uri();
		$s_page = htmlspecialchars($this->root->vars['page']);
		$string = <<<EOD
<br />
<form action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="comment" />
  <input type="hidden" name="refer"  value="$s_page" />
  <input type="hidden" name="comment_no" value="$comment_no" />
  <input type="hidden" name="nodate" value="$nodate" />
  <input type="hidden" name="above"  value="$above" />
  <input type="hidden" name="digest" value="{$this->root->digest}" />
  $nametags
  <input type="text"   name="msg" rel="wikihelper" id="_p_comment_comment_{$comment_no}" size="{$comment_cols[$this->xpwiki->pid]}" />
  <input type="submit" name="comment" value="{$this->root->_btn_comment}" />
 </div>
</form>
EOD;
	
		return $string;
	}
}
?>