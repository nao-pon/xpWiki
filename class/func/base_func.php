<?php
//
// Created on 2006/10/15 by nao-pon http://hypweb.net/
// $Id: base_func.php,v 1.3 2006/10/15 14:11:55 nao-pon Exp $
//
class XpWikiBaseFunc {
	
	// xpWiki functions.
	// This will be overwrited by XpwikiXoopsWapper class.
		
	function get_zonetime () {
		return date("Z");	
	}
	
	function set_moduleinfo () {
		
		$this->root->module['name'] = 'xpWiki';
		$this->root->module['version'] = '0.1';
		
	}
	
	function set_userinfo () {
		$this->root->userinfo['admin'] = FALSE;
		$this->root->userinfo['uid'] = 0;
		$this->root->userinfo['uname'] = '';
		$this->root->userinfo['uname_s'] = htmlspecialchars($this->root->userinfo['uname']);
		$this->root->userinfo['gids'] = array();
	}
	
	function get_lang ($default) {
		return $default;
	}
}
?>