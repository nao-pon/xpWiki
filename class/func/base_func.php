<?php
//
// Created on 2006/10/15 by nao-pon http://hypweb.net/
// $Id: base_func.php,v 1.7 2006/10/27 11:57:32 nao-pon Exp $
//
class XpWikiBaseFunc {
	
	// xpWiki functions.
	// This will be overwrited by XpwikiXoopsWapper class.
		
	function get_zonetime () {
		return date("Z");	
	}
	
	function set_moduleinfo () {
		
		$this->root->module['name'] = 'xpWiki';
		$this->root->module['version'] = '0.5';
		$this->root->module['credits'] = '&copy; 2006- hypweb.net';
		$this->root->module['author'] = 'nao-pon';
		$this->root->module['platform'] = 'standalone';
		
	}
	
	function set_siteinfo () {
		$this->root->siteinfo['root_url'] = '';
		$this->root->siteinfo['site_name'] = '';
	}
	
	function set_userinfo () {
		$this->root->userinfo['admin'] = FALSE;
		$this->root->userinfo['uid'] = 0;
		$this->root->userinfo['uname'] = '';
		$this->root->userinfo['uname_s'] = htmlspecialchars($this->root->userinfo['uname']);
		$this->root->userinfo['gids'] = array();
	}

	function get_userinfo_by_id ($uid, $defname=NULL) {
		if (is_null($defname)) {
			$defname = $this->root->anonymous;
		}
		$result = array(
			'uname' => $defname,
			'email' => '' 
			);
		return $result;
	}
	
	function get_lang ($default) {
		return $default;
	}
}
?>