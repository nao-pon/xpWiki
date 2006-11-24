<?php
//
// Created on 2006/10/29 by nao-pon http://hypweb.net/
// $Id: whatsnew.php,v 1.3 2006/11/24 13:47:07 nao-pon Exp $
//

class XpWikiExtension_whatsnew extends XpWikiExtension {

// $this->xpwiki : Parent XpWiki object.
// $this->root   : Global variable.
// $this->cont   : Constant.
// $this->func   : XpWiki functions.

	function get ($limit, $offset) {

		$i    = 0;
		$ret  = array();
		$desc = '';
	
		$recent_dat  = $this->cont['PKWK_MAXSHOW_CACHE'];
		//$recent_line = @file($this->cont['CACHE_DIR'] . $recent_dat);
		//$recent_arr  = array_slice($recent_line, 0, $limit);
		$recent_arr = $this->func->get_existpages_db(FALSE,'',$limit," ORDER BY editedtime DESC",TRUE,FALSE,TRUE,TRUE);
		
		foreach($recent_arr as $line) {
			list($time, $base) = explode("\t", trim($line));
			$localtime = $time + ($this->cont['ZONETIME']);
	
			$ret[$i]['link']  = $this->root->script."?".rawurlencode($base);
			$ret[$i]['title'] = $base;
			$ret[$i]['time']  = $localtime;
			
			// 指定ページの本文取得
			//$page = new XpWiki($this->root->mydirname);
			//$page->init($base);
			//$page->root->rtf['use_cache_always'] = TRUE;
			//$page->execute();
			//$desc = $page->body;
			
			$ret[$i]['description'] = $this->func->get_plain_text_db($base);
			$i++;
		}
		return $ret;
	}
}
?>