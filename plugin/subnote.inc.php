<?php
/*
 * Created on 2007/10/05 by nao-pon http://hypweb.net/
 * $Id: subnote.inc.php,v 1.3 2009/03/20 06:27:32 nao-pon Exp $
 */

class xpwiki_plugin_subnote extends xpwiki_plugin {
	function plugin_subnote_init () {
		$this->config['parames'] = $this->root->note_popup_position;
		$this->config['parames']['format'] = '%s';
		$this->config['parames']['nonew'] = FALSE;
		$this->config['parames']['popup'] = FALSE;
		$this->config['elapses'] = array(
			60 * 60 * 24 * 1 => ' <span class="new1" title="%s">New!</span>',  // 1day
			60 * 60 * 24 * 5 => ' <span class="new5" title="%s">New</span>');  // 5days
	}
	
	//function can_call_otherdir_inline() {
	//	return 1;
	//}
	
	function plugin_subnote_convert() {
		if (strpos($this->root->vars['page'],  $this->root->notepage . '/') === 0) {
			return '#subnote can not use in the Note page.';
		}
		
		$page = $this->root->notepage . '/' . $this->root->vars['page'];
		
		if (! $this->func->check_readable($page, false, false)) return '';
		
		$anchor = '';
		$popup_pos = '';
		$op = func_get_args();
		$parames = $this->config['parames'];
		$this->fetch_options($parames, $op);
		foreach(array('top', 'left', 'bottom', 'right', 'width', 'height') as $_prm) {
			if (preg_match('/^(\d+)(%|p(?:x|c|t)|e(?:m|x)|in|(?:c|m)m)?/', $parames[$_prm], $_match)) {
			 	if (empty($_match[2])) $_match[2] = 'px';
			 	$popup_pos .= ',' . $_prm . ':\'' . $_match[1] . $_match[2] . '\'';
			}
		}

		$js = 'XpWiki.domInitFunctions.push(function(){XpWiki.pagePopup({dir:\'' . htmlspecialchars($this->root->mydirname, ENT_QUOTES) .
			'\',page:\'' . htmlspecialchars($page . $anchor, ENT_QUOTES) . '\'' .
			$popup_pos . '});});';
		
		$this->func->add_js_var_head($js);
		
		return '';
	}
	
	function plugin_subnote_inline() {
		$op = func_get_args();
		
		list($alias, $alias_main) = array_pad(explode('|', array_pop($op)), 2, '');
		$anchor = '';
		if (isset($op[0]) && $op[0][0] === '#') {
			$anchor = array_shift($op);
		}
		
		if (empty($this->cont['PAGENAME'])) return '';
		
		$parames = $this->config['parames'];
		$this->fetch_options($parames, $op);
		
		$parames['format'] = htmlspecialchars($parames['format']);
		
		$prefix = $this->root->notepage . '/';
		$page = $this->cont['PAGENAME'];
		
		if ($parames['popup']) {
			$options['popup']['use'] = 1;
			$options['popup']['position'] = '';
			foreach(array('top', 'left', 'bottom', 'right', 'width', 'height') as $_prm) {
				if ($parames[$_prm]) {
					if (preg_match('/^(\d+)(%|px)?/', $parames[$_prm], $_match)) {
					 	if (empty($_match[2])) $_match[2] = 'px';
					 	$options['popup']['position'] .= ',' . $_prm . ':\'' . $_match[1] . $_match[2] . '\'';
					}
				}
			}
		} else {
			$options = array();
		}

		if (strpos($page, $prefix) === 0) {
			// Note �ڡ���
			$page = substr($page, strlen($prefix));
			$alias = $alias_main? $alias_main : $alias;
			$alias = $alias? $alias : '#compact:'.$this->func->page_dirname($page);
			return sprintf($parames['format'], $this->func->make_pagelink($page, $alias, $anchor, '', 'pagelink', $options));
		}
		
		$page = $prefix . $page;
		
		if ($this->cont['PAGENAME'][0] === ':' || ! $this->func->check_readable($page, false, false) || (! $this->func->is_page($page)) && ! $this->func->check_editable($page, false, false)) return '';
		
		$new = '';
		$timestamp = $this->func->get_filetime($page);
		// Add 'New!' string by the elapsed time
		$erapse = $this->cont['UTIME'] - $timestamp;
		foreach ($this->config['elapses'] as $limit=>$tag) {
			if ($erapse <= $limit) {
				$new = sprintf($tag, $this->func->get_passage($timestamp));
				break;
			}
		}
		
		$alias = $alias? $alias : htmlspecialchars($page);
		return sprintf($parames['format'], $this->func->make_pagelink($page, $alias, $anchor, '', 'pagelink', $options).$new);
	}
}
?>