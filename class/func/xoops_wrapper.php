<?php
//
// Created on 2006/10/11 by nao-pon http://hypweb.net/
// $Id: xoops_wrapper.php,v 1.8 2006/10/24 00:16:45 nao-pon Exp $
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

	function pkwk_mail_notify($subject, $message, $footer = array())
	{
		static $_to, $_headers, $_after_pop;
	
		// Init and lock
		if (! isset($_to[$this->xpwiki->pid])) {
			if (! $this->cont['PKWK_OPTIMISE']) {
				// Validation check
				$func = 'pkwk_mail_notify(): ';
				$mail_regex   = '/[^@]+@[^@]{1,}\.[^@]{2,}/';
				//if (! preg_match($mail_regex, $this->root->notify_to))
				//	die($func . 'Invalid $this->root->notify_to');
				//if (! preg_match($mail_regex, $this->root->notify_from))
				//	die($func . 'Invalid $this->root->notify_from');
				if ($this->root->notify_header != '') {
					$header_regex = "/\A(?:\r\n|\r|\n)|\r\n\r\n/";
					if (preg_match($header_regex, $this->root->notify_header))
						die($func . 'Invalid $this->root->notify_header');
					if (preg_match('/^From:/im', $this->root->notify_header))
						die($func . 'Redundant \'From:\' in $this->root->notify_header');
				}
			}
			
			$_to[$this->xpwiki->pid]      = $this->root->notify_to;
			$_headers[$this->xpwiki->pid] =
				'X-Mailer: xpWiki/' . $this->cont['S_VERSION'] .
				' PHP/' . phpversion() . "\r\n";
							
			// Additional header(s) by admin
			if ($this->root->notify_header != '') $_headers[$this->xpwiki->pid] .= "\r\n" . $this->root->notify_header;

	
			//$_after_pop[$this->xpwiki->pid] = $this->root->smtp_auth;
		}
	
		if ($subject == '' || ($message == '' && empty($footer))) return FALSE;
	
		// Subject:
		if (isset($footer['PAGE'])) $subject = str_replace('$page', $footer['PAGE'], $subject);
	
		// Footer
		if (isset($footer['REMOTE_ADDR'])) $footer['REMOTE_ADDR'] = & $_SERVER['REMOTE_ADDR'];
		if (isset($footer['USER_AGENT']))
			$footer['USER_AGENT']  = '(' . $this->cont['UA_PROFILE'] . ') ' . $this->cont['UA_NAME'] . '/' . $this->cont['UA_VERS'];
		if (! empty($footer)) {
			$_footer = '';
			if ($message != '') $_footer = "\n" . str_repeat('-', 30) . "\n";
			foreach($footer as $key => $value)
				$_footer .= $key . ': ' . $value . "\n";
			$message .= $_footer;
		}
	
		// Wait POP/APOP auth completion
		//if ($_after_pop[$this->xpwiki->pid]) {
		//	$result = $this->pop_before_smtp();
		//	if ($result !== TRUE) die($result);
		//}
	
		global $xoopsConfig;
		$xoopsMailer =& getMailer();
		$xoopsMailer->useMail();
		$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
		$xoopsMailer->setFromName($xoopsConfig['sitename']);
		$xoopsMailer->setSubject($subject);
		$xoopsMailer->setBody($message);
		$xoopsMailer->setToEmails($xoopsConfig['adminmail']);
		$xoopsMailer->headers = explode("\r\n",rtrim($_headers[$this->xpwiki->pid]));
		$xoopsMailer->send();
		$xoopsMailer->reset();
		
		return true;
	}

}
?>