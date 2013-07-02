<?php
/*
 * Created on 2007/08/30 by nao-pon http://hypweb.net/
 */

class xpwiki_plugin_page_aliases extends xpwiki_plugin {

	function plugin_page_aliases_action() {
		return array('msg'=>'Page aliases list', 'body'=>$this->get());
	}

	function can_call_otherdir_convert() {
		return 1;
	}

	function can_call_otherdir_inline() {
		return 1;
	}

	function plugin_page_aliases_convert() {
		return $this->get();
	}

	function plugin_page_aliases_inline() {

		$arg = func_get_args();

		// get body "{...}"
		$body = array_pop($arg);

		$page = '';
		if (isset($arg[0])) {
			$page = $arg[0];
		}
		if (!$page || $this->func->is_page($page)) {
			$page = $this->root->vars['page'];
		}

		$pagealiases = $this->func->get_page_alias($page, true, false, 'relative');

		return $pagealiases? join(', ', $pagealiases) : $this->root->_LANG['skin']['none'];

	}

	function get() {
		$result = $this->func->get_pagealiases($this->func->get_existpages(), true);
		$ret = "- Page aliases list\n";
		foreach($result as $_alias => $pgid) {
			$_page = $this->func->get_name_by_pgid($pgid);
			$ret .= "-- [[{$_alias}]] &#187; [[{$_page}]]\n";
		}
		return $this->func->convert_html($ret);
	}
}
?>