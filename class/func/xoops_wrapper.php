<?php
//
// Created on 2006/10/11 by nao-pon http://hypweb.net/
// $Id: xoops_wrapper.php,v 1.7 2006/10/23 08:13:48 nao-pon Exp $
//
class XpWikiXoopsWrapper extends XpWikiBackupFunc {
	
	function set_moduleinfo () {
		
		$this->cont['ROOT_PATH'] = XOOPS_ROOT_PATH . "/";
		$this->cont['ROOT_URL']  = XOOPS_URL . "/";
		
		$module_handler =& xoops_gethandler('module');
		$XoopsModule =& $module_handler->getByDirname($this->root->mydirname);
		
		$this->root->module = $XoopsModule->getInfo();
	}

	function set_siteinfo () {
		
		$config_handler =& xoops_gethandler('config');
		$xoopsConfig =& $config_handler->getConfigsByCat(XOOPS_CONF);
		
		$this->root->siteinfo['rooturl'] = XOOPS_URL."/";
		$this->root->siteinfo['sitename'] = $xoopsConfig['sitename'];
	}
		
	function set_userinfo () {
		
		global $xoopsUser;
		
		$module_handler =& xoops_gethandler('module');
		$XoopsModule =& $module_handler->getByDirname($this->root->mydirname);
		
		if (is_object($xoopsUser))
		{
			$this->root->userinfo['admin'] = $xoopsUser->isAdmin($XoopsModule->mid());
			$this->root->userinfo['uid'] = $xoopsUser->uid();
			$this->root->userinfo['uname'] = $xoopsUser->uname();
			$this->root->userinfo['uname_s'] = htmlspecialchars($this->root->userinfo['uname']);
			$this->root->userinfo['gids'] = $xoopsUser->getGroups();
		}
		else
		{
			parent::set_userinfo();
		}
	}
	
	function check_editable($page, $auth_flag = TRUE, $exit_flag = TRUE)
	{
		//	global $script, $_title_cannotedit, $_msg_unfreeze;
	
		if ($this->edit_auth($page, $auth_flag, $exit_flag) && $this->is_editable($page)) {
			// Editable
			return TRUE;
		} else {
			// Not editable
			if ($exit_flag === FALSE) {
				return FALSE; // Without exit
			} else {
				// With exit
				$body = $title = str_replace('$1',
					htmlspecialchars($this->strip_bracket($page)), $this->root->_title_cannotedit);
				if ($this->is_freeze($page))
					$body .= '(<a href="' . $this->root->script . '?cmd=unfreeze&amp;page=' .
						rawurlencode($page) . '">' . $this->root->_msg_unfreeze . '</a>)';
				
				redirect_header($this->root->script."?".rawurlencode($page), 3, $body);
				exit;
			}
		}
	}
	
	function get_zonetime () {
		$config_handler =& xoops_gethandler('config');
		$xoopsConfig =& $config_handler->getConfigsByCat(XOOPS_CONF);
		return $xoopsConfig['default_TZ'] * 3600; //default_TZ	
	}
	
	function get_lang ($default) {
		$config_handler =& xoops_gethandler('config');
		$xoopsConfig =& $config_handler->getConfigsByCat(XOOPS_CONF);
		$language = $xoopsConfig['language'];
		switch (strtolower($language)) {
			case "japanese" :
				return "ja";
			case "english" :
				return "en";
			default:
				return $default;
		}
	}
}
?>