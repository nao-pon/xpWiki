<?php
class xpwiki_plugin_read extends xpwiki_plugin {
	function plugin_read_init () {



	}
	// PukiWiki - Yet another WikiWikiWeb clone.
	// $Id: read.inc.php,v 1.2 2007/05/28 07:57:42 nao-pon Exp $
	//
	// Read plugin: Show a page and InterWiki
	
	function plugin_read_action()
	{
	//	global $vars, $_title_invalidwn, $_msg_invalidiwn;
	
		$page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
	
		if ($this->func->is_page($page)) {
			// ページを表示
			$this->func->check_readable($page, true, true);
			$this->func->header_lastmod($page);
			return array('msg'=>'', 'body'=>'');
	
		} else if (! $this->cont['PKWK_SAFE_MODE'] && $this->func->is_interwiki($page)) {
			return $this->func->do_plugin_action('interwiki'); // InterWikiNameを処理
	
		} else if ($this->func->is_pagename($page)) {
			// Case insensitive ?
			if (@ $this->root->page_case_insensitive) {
				$page = $this->func->get_pagename_realcase($page);
				if ($this->func->is_page($page)) {
					$this->root->get['page'] = $this->root->post['page'] = $this->root->vars['page'] = $page;
					// ページを表示
					$this->func->check_readable($page, true, true);
					$this->func->header_lastmod($page);
					return array('msg'=>'', 'body'=>'');
				}
			}
			$this->root->vars['cmd'] = 'edit';
			return $this->func->do_plugin_action('edit'); // 存在しないので、編集フォームを表示
	
		} else {
			// 無効なページ名
			return array(
				'msg'=>$this->root->_title_invalidwn,
			'body'=>str_replace('$1', htmlspecialchars($page),
				str_replace('$2', 'WikiName', $this->root->_msg_invalidiwn))
			);
		}
	}
}
?>