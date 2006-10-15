<?php
//
// Created on 2006/10/11 by nao-pon http://hypweb.net/
// $Id: xoops_wrapper.php,v 1.2 2006/10/15 05:59:29 nao-pon Exp $
//
class XpWikiXoopsWrapper extends XpWikiBackupFunc {
	
	function XpWikiXoopsWrapper () {
	}

	function setModuleInfo () {
		
		$this->cont['ROOT_PATH'] = XOOPS_ROOT_PATH . "/";
		$this->cont['ROOT_URL']  = XOOPS_URL . "/";
		
		$XoopsModule =& XoopsModule::getByDirname($this->root->mydirname);
		$this->root->module['name'] = $XoopsModule->getInfo('name');
		$this->root->module['version'] = $XoopsModule->getInfo('version');
		
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
		global $xoopsConfig;
		return $xoopsConfig['default_TZ'] * 3600; //default_TZ	
	}
}
?>