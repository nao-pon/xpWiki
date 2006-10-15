<?php
//
// Created on 2006/10/15 by nao-pon http://hypweb.net/
// $Id: base_func.php,v 1.1 2006/10/15 10:47:05 nao-pon Exp $
//
class XpWikiBaseFunc {
	
	// xpWiki functions.
	// This will be overwrited by XpwikiXoopsWapper class.
		
	function get_zonetime () {
		return date("Z");	
	}
	
	function set_user_info () {
		$this->root->userinfo['admin'] = FALSE;
		$this->root->userinfo['uid'] = 0;
		$this->root->userinfo['uname'] = '';
		$this->root->userinfo['gids'] = array();
		$this->root->userinfo['ucd'] = '';
	}
}
?>