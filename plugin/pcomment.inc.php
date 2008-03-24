<?php
class xpwiki_plugin_pcomment extends xpwiki_plugin {
	function plugin_pcomment_init () {


	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: pcomment.inc.php,v 1.12 2008/03/24 09:31:45 nao-pon Exp $
	//
	// pcomment plugin - Show/Insert comments into specified (another) page
	//
	// Usage: #pcomment([page][,max][,options])
	//
	//   page -- An another page-name that holds comments
	//           (default:PLUGIN_PCOMMENT_PAGE)
	//   max  -- Max number of recent comments to show
	//           (0:Show all, default:PLUGIN_PCOMMENT_NUM_COMMENTS)
	//
	// Options:
	//   above -- Comments are listed above the #pcomment (added by chronological order)
	//   below -- Comments are listed below the #pcomment (by reverse order)
	//   reply -- Show radio buttons allow to specify where to reply
	
	// Default recording page name (%s = $vars['page'] = original page name)
		switch ($this->cont['LANG']) {
		case 'ja': $this->cont['PLUGIN_PCOMMENT_PAGE'] =  '[[コメント/%s]]'; break;
		default:   $this->cont['PLUGIN_PCOMMENT_PAGE'] =  '[[Comments/%s]]'; break;
		}
	
		$this->cont['PLUGIN_PCOMMENT_NUM_COMMENTS'] =      10; // Default 'latest N posts'
$this->cont['PLUGIN_PCOMMENT_DIRECTION_DEFAULT'] =  1; // 1: above 0: below
		$this->cont['PLUGIN_PCOMMENT_SIZE_MSG'] =   70;
		$this->cont['PLUGIN_PCOMMENT_SIZE_NAME'] =  15;
	
	// Auto log rotation
		$this->cont['PLUGIN_PCOMMENT_AUTO_LOG'] =  0; // 0:off 1-N:number of comments per page
	
	// Update recording page's timestamp instead of parent's page itself
		$this->cont['PLUGIN_PCOMMENT_TIMESTAMP'] =  0;
	
	// ----
		$this->cont['PLUGIN_PCOMMENT_FORMAT_NAME'] = 	'[[$name]]';
		$this->cont['PLUGIN_PCOMMENT_FORMAT_MSG'] = 	'$msg';
		$this->cont['PLUGIN_PCOMMENT_FORMAT_NOW'] = 	'&new{$now};';
	
	// "\x01", "\x02", "\x03", and "\x08" are used just as markers
		$this->cont['PLUGIN_PCOMMENT_FORMAT_STRING'] = 
	"\x08" . 'MSG' . "\x08" . ' -- ' . "\x08" . 'NAME' . "\x08" . ' ' . "\x08" . 'DATE' . "\x08";

	}
	
	function plugin_pcomment_action()
	{
	//	global $vars;
	
		if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits editing');
	
		if (! isset($this->root->vars['msg']) || $this->root->vars['msg'] == '') return array();
		$refer = isset($this->root->vars['refer']) ? $this->root->vars['refer'] : '';
	
		$retval = $this->plugin_pcomment_insert();
		if ($retval['collided']) {
			$this->root->vars['page'] = $refer;
			return $retval;
		}
	
		$this->func->pkwk_headers_sent();
		
		if ($this->root->render_mode !== 'render') {
			$back = ($refer)? $this->func->get_page_uri($refer) : ($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER'] : $this->root->script;
		} else {
			$back = ($refer)? $this->root->siteinfo['host'].$refer : ($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER'] : $this->cont['ROOT_URL'];
		}

		$this->func->send_location('', '', $back);
	}
	
	function plugin_pcomment_convert()
	{
	//	global $vars;
	//	global $_pcmt_messages;
	
		$ret = '';
	
		$params = array(
			'noname'=>FALSE,
		'nodate'=>FALSE,
		'below' =>FALSE,
		'above' =>FALSE,
		'reply' =>FALSE,
		'_args' =>array()
		);
	
		foreach(func_get_args() as $arg)
			$this->plugin_pcomment_check_arg($arg, $params);
		
		$vars_page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
		$page  = (isset($params['_args'][0]) && $params['_args'][0] != '') ? $params['_args'][0] :
			sprintf($this->cont['PLUGIN_PCOMMENT_PAGE'], $this->func->strip_bracket($vars_page));
		$count = isset($params['_args'][1]) ? intval($params['_args'][1]) : 0;
		if ($count == 0) $count = $this->cont['PLUGIN_PCOMMENT_NUM_COMMENTS'];
	
		$_page = $this->func->get_fullname($this->func->strip_bracket($page), $vars_page);
		if (!$this->func->is_pagename($_page))
			return sprintf($this->root->_pcmt_messages['err_pagename'], htmlspecialchars($_page));
	
		$dir = $this->cont['PLUGIN_PCOMMENT_DIRECTION_DEFAULT'];
		if ($params['below']) {
			$dir = 0;
		} elseif ($params['above']) {
			$dir = 1;
		}
	
		list($comments, $digest) = $this->plugin_pcomment_get_comments($_page, $count, $dir, $params['reply']);
	
		if ($this->cont['PKWK_READONLY'] === 1) {
			$form_start = $form = $form_end = '';
		} else if ($this->cont['PKWK_READONLY'] === 2 && ! $this->func->check_editable_page($_page, FALSE, FALSE)) {
			$form_start = $form = $form_end = '';
		} else {
			// Show a form
			$this->root->rtf['disable_render_cache'] = true;
			if ($this->root->render_mode === 'render') {
				$this->func->add_tag_head("default.{$this->cont['UI_LANG']}{$this->cont['FILE_ENCORD_EXT']}.js");
			}
			if ($params['noname']) {
				$title = $this->root->_pcmt_messages['msg_comment'];
				$name = '';
			} else {
				$title = $this->root->_pcmt_messages['btn_name'];
				$name = '<input type="text" name="name" value="' . $this->cont['USER_NAME_REPLACE'] . '"size="' . $this->cont['PLUGIN_PCOMMENT_SIZE_NAME'] . '" />';
			}
	
			$radio   = $params['reply'] ?
				'<input type="radio" name="reply" value="0" tabindex="0" checked="checked" />' : '';
			$comment = '<input type="text" name="msg" rel="wikihelper" size="' . $this->cont['PLUGIN_PCOMMENT_SIZE_MSG'] . '" />';
	
			$s_page   = htmlspecialchars($_page);
			if ($this->root->render_mode !== 'render') {
				$s_refer = htmlspecialchars($vars_page);
			} else {
				$s_refer = htmlspecialchars($_SERVER['REQUEST_URI']);
			}
			$s_nodate = htmlspecialchars($params['nodate']);
	
			$form_start = '<form action="' . $this->func->get_script_uri() . '" method="post">' . "\n";
			$form = <<<EOD
  <div>
  <input type="hidden" name="digest" value="$digest" />
  <input type="hidden" name="plugin" value="pcomment" />
  <input type="hidden" name="refer"  value="$s_refer" />
  <input type="hidden" name="page"   value="$s_page" />
  <input type="hidden" name="nodate" value="$s_nodate" />
  <input type="hidden" name="dir"    value="$dir" />
  <input type="hidden" name="count"  value="$count" />
  $radio $title $name $comment
  <input type="submit" value="{$this->root->_pcmt_messages['btn_comment']}" />
  </div>
EOD;
			$form_end = '</form>' . "\n";
		}
	
		if (! $this->func->is_page($_page)) {
			$link   = $this->func->make_pagelink($_page);
			$recent = $this->root->_pcmt_messages['msg_none'];
		} else {
			$msg    = ($this->root->_pcmt_messages['msg_all'] != '') ? $this->root->_pcmt_messages['msg_all'] : $_page;
			$link   = $this->func->make_pagelink($_page, $msg);
			$recent = ! empty($count) ? sprintf($this->root->_pcmt_messages['msg_recent'], $count) : '';
		}
	
		if ($dir) {
			return '<div>' .
			'<p>' . $recent . ' ' . $link . '</p>' . "\n" .
			$form_start .
				$comments . "\n" .
				$form .
			$form_end .
			'</div>' . "\n";
		} else {
			return '<div>' .
			$form_start .
				$form .
				$comments. "\n" .
			$form_end .
			'<p>' . $recent . ' ' . $link . '</p>' . "\n" .
			'</div>' . "\n";
		}
	}
	
	function plugin_pcomment_insert()
	{
	//	global $script, $vars, $now;
	//	global $_title_updated, $_no_name, $_pcmt_messages;
	
		$refer = isset($this->root->vars['refer']) ? $this->root->vars['refer'] : '';
		$page  = isset($this->root->vars['page'])  ? $this->root->vars['page']  : '';
		$this->root->vars['page'] = $page = $this->func->get_fullname($page, $refer);
	
		if (! $this->func->is_pagename($page))
			return array(
				'msg' =>'Invalid page name',
				'body'=>'Cannot add comment' ,
				'collided'=>TRUE
			);
		
		$this->func->check_editable($page, true, true);
	
		$ret = array('msg' => $this->root->_title_updated, 'collided' => FALSE);
	
		$msg = str_replace('$msg', rtrim($this->root->vars['msg']), $this->cont['PLUGIN_PCOMMENT_FORMAT_MSG']);
		$name = (! isset($this->root->vars['name']) || $this->root->vars['name'] == '') ? $this->root->_no_name : $this->root->vars['name'];
		// save name to cookie
		if ($name) { $this->func->save_name2cookie($name); }
		$name = ($name == '') ? '' : str_replace('$name', $name, $this->cont['PLUGIN_PCOMMENT_FORMAT_NAME']);
		$date = (! isset($this->root->vars['nodate']) || $this->root->vars['nodate'] != '1') ?
			str_replace('$now', $this->root->now, $this->cont['PLUGIN_PCOMMENT_FORMAT_NOW']) : '';
		if ($date != '' || $name != '') {
			$msg = str_replace("\x08" . 'MSG'  . "\x08", $msg,  $this->cont['PLUGIN_PCOMMENT_FORMAT_STRING']);
			$msg = str_replace("\x08" . 'NAME' . "\x08", $name, $msg);
			$msg = str_replace("\x08" . 'DATE' . "\x08", $date, $msg);
		}
	
		$reply_hash = isset($this->root->vars['reply']) ? $this->root->vars['reply'] : '';
		if ($reply_hash || ! $this->func->is_page($page)) {
			$msg = preg_replace('/^\-+/', '', $msg);
		}
		$msg = rtrim($msg);
	
		if (! $this->func->is_page($page)) {
			$postdata = '[[' . htmlspecialchars($this->func->strip_bracket($refer)) . ']]' . "\n\n" .
			'-' . $msg . "\n";
		} else {
			$postdata = $this->func->get_source($page);
			$this->func->escape_multiline_pre($postdata, TRUE);
			$count    = count($postdata);
	
			$digest = isset($this->root->vars['digest']) ? $this->root->vars['digest'] : '';
			if (md5(join('', $postdata)) != $digest) {
				$ret['msg']  = $this->root->_pcmt_messages['title_collided'];
				$ret['body'] = $this->root->_pcmt_messages['msg_collided'];
			}
	
			$start_position = 0;
			while ($start_position < $count) {
				if (preg_match('/^\-/', $postdata[$start_position])) break;
				++$start_position;
			}
			$end_position = $start_position;
	
			$dir = isset($this->root->vars['dir']) ? $this->root->vars['dir'] : '';
	
			// Find the comment to reply
			$level   = 1;
			$b_reply = FALSE;
			if ($reply_hash != '') {
				while ($end_position < $count) {
					$matches = array();
					if (preg_match('/^(\-{1,2})(?!\-)(.*)$/', $postdata[$end_position++], $matches)
						&& md5($matches[2]) == $reply_hash)
					{
						$b_reply = TRUE;
						$level   = strlen($matches[1]) + 1;
	
						while ($end_position < $count) {
							if (preg_match('/^(\-{1,6})(?!\-)/', $postdata[$end_position], $matches)
								&& strlen($matches[1]) < $level) break;
							++$end_position;
						}
						break;
					}
				}
			}
	
			if ($b_reply == FALSE)
				$end_position = ($dir == '0') ? $start_position : $count;
	
			// Insert new comment
			array_splice($postdata, $end_position, 0, str_repeat('-', $level) . $msg . "\n");
	
			if ($this->cont['PLUGIN_PCOMMENT_AUTO_LOG']) {
				$_count = isset($this->root->vars['count']) ? $this->root->vars['count'] : '';
				$this->plugin_pcomment_auto_log($page, $dir, $_count, $postdata);
			}
			$postdata = join('', $postdata);
		}
		$this->func->escape_multiline_pre($postdata, FALSE);
		$this->func->page_write($page, $postdata, $this->cont['PLUGIN_PCOMMENT_TIMESTAMP']);
	
		if ($this->cont['PLUGIN_PCOMMENT_TIMESTAMP']) {
			if ($refer !== '') $this->func->pkwk_touch_file($this->func->get_filename($refer));
			$this->func->put_lastmodified();
		}
	
		return $ret;
	}
	
	// Auto log rotation
	function plugin_pcomment_auto_log($page, $dir, $count, & $postdata)
	{
		if (! $this->cont['PLUGIN_PCOMMENT_AUTO_LOG']) return;
	
		$keys = array_keys(preg_grep('/(?:^-(?!-).*$)/m', $postdata));
		if (count($keys) < ($this->cont['PLUGIN_PCOMMENT_AUTO_LOG'] + $count)) return;
	
		if ($dir) {
			// Top N comments (N = PLUGIN_PCOMMENT_AUTO_LOG)
			$old = array_splice($postdata, $keys[0], $keys[$this->cont['PLUGIN_PCOMMENT_AUTO_LOG']] - $keys[0]);
		} else {
			// Bottom N comments
			$old = array_splice($postdata, $keys[count($keys) - $this->cont['PLUGIN_PCOMMENT_AUTO_LOG']]);
		}
	
		// Decide new page name
		$i = 0;
		do {
			++$i;
			$_page = $page . '/' . $i;
		} while ($this->func->is_page($_page));
	
		$this->func->page_write($_page, '[[' . $page . ']]' . "\n\n" . join('', $old));
	
		// Recurse :)
		$this->plugin_pcomment_auto_log($page, $dir, $count, $postdata);
	}
	
	// Check arguments
	function plugin_pcomment_check_arg($val, & $params)
	{
		if ($val != '') {
			$l_val = strtolower($val);
			foreach (array_keys($params) as $key) {
				if (strpos($key, $l_val) === 0) {
					$params[$key] = TRUE;
					return;
				}
			}
		}
	
		$params['_args'][] = $val;
	}
	
	function plugin_pcomment_get_comments($page, $count, $dir, $reply)
	{
	//	global $_msg_pcomment_restrict;
	
		if (! $this->func->check_readable($page, false, false))
			return array(str_replace('$1', $page, $this->root->_msg_pcomment_restrict));
	
		$reply = (! $this->cont['PKWK_READONLY'] && $reply); // Suprress radio-buttons
	
		$data = $this->func->get_source($page);
		$data = preg_replace('/^#pcomment\(?.*/i', '', $data);	// Avoid eternal recurse
	
		if (! is_array($data)) return array('', 0);
	
		$digest = md5(join('', $data));
	
		// Get latest N comments
		$num  = $cnt     = 0;
		$cmts = $matches = array();
		if ($dir) $data = array_reverse($data);
		foreach ($data as $line) {
			if ($count > 0 && $dir && $cnt == $count) break;
	
			if (preg_match('/^(\-{1,2})(?!\-)(.+)$/', $line, $matches)) {
				if ($count > 0 && strlen($matches[1]) == 1 && ++$cnt > $count) break;
	
				// Ready for radio-buttons
				if ($reply) {
					++$num;
					$cmts[] = $matches[1] . "\x01" . $num . "\x02" .
					md5($matches[2]) . "\x03" . $matches[2] . "\n";
					continue;
				}
			}
			$cmts[] = $line;
		}
		$data = $cmts;
		if ($dir) $data = array_reverse($data);
		unset($cmts, $matches);
	
		// Remove lines before comments
		while (! empty($data) && substr($data[0], 0, 1) != '-')
			array_shift($data);
	
		$comments = $this->func->convert_html($data, $page);
		unset($data);
	
		// Add radio buttons
		if ($reply)
			$comments = preg_replace('/<li>' . "\x01" . '(\d+)' . "\x02" . '(.*)' . "\x03" . '/',
			'<li class="pcmt"><input class="pcmt" type="radio" name="reply" value="$2" tabindex="$1" />',
			$comments);
	
		return array($comments, $digest);
	}
}
?>