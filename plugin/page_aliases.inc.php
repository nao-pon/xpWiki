<?php
/*
 * Created on 2007/08/30 by nao-pon http://hypweb.net/
 * $Id: page_aliases.inc.php,v 1.1 2007/08/30 05:39:05 nao-pon Exp $
 */

class xpwiki_plugin_page_aliases extends xpwiki_plugin {

	function plugin_page_aliases_action() {
		return array('msg'=>'Page aliases list', 'body'=>$this->get());
	}

	function plugin_page_aliases_convert() {
		return $this->get();
	}
	
	function get() {
		$result = array_intersect($this->root->page_aliases, $this->func->get_existpages());
		//$result = $this->root->page_aliases;
		$ret = "- Page aliases list\n";
		foreach($result as $_alias => $_page) {
			$ret .= "-- [[{$_alias}]] &#187; [[{$_page}]]\n";
		}
//		$ret = "|Alias|Page|h\n";
//		foreach($this->root->page_aliases as $_alias => $_page) {
//			$ret .= "|[[{$_alias}]]|[[{$_page}]]|\n";
//		}
		$ret = $this->func->convert_html($ret);
		return '<p>'.$ret.'</p>';
	}
}
?>