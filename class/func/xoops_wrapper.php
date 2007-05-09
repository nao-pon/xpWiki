<?php
//
// Created on 2006/10/11 by nao-pon http://hypweb.net/
// $Id: xoops_wrapper.php,v 1.25 2007/05/09 12:08:37 nao-pon Exp $
//
class XpWikiXoopsWrapper extends XpWikiBackupFunc {
	
	function & get_db_connection () {
		return XoopsDatabaseFactory::getDatabaseConnection();
	}
	
	function set_moduleinfo () {
		
		$this->cont['ROOT_PATH'] = XOOPS_ROOT_PATH . "/";
		$this->cont['ROOT_URL']  = XOOPS_URL . "/";
		
		$module_handler =& xoops_gethandler('module');
		$XoopsModule =& $module_handler->getByDirname($this->root->mydirname);
		$config_handler =& xoops_gethandler('config');
		
		$this->root->module = $XoopsModule->getInfo();
		$this->root->module['title'] = $XoopsModule->name();
		$this->root->module['config'] =& $config_handler->getConfigsByCat(0, $XoopsModule->mid());
		$this->root->module['platform'] = "xoops";
		
		if (empty($this->root->module['config']['comment_forum_id']) ||
			!file_exists(XOOPS_ROOT_PATH . '/modules/' . $this->root->module['config']['comment_dirname'])) {
			$this->root->allow_pagecomment = FALSE;
		}
	}

	function set_siteinfo () {
		
		$config_handler =& xoops_gethandler('config');
		$xoopsConfig =& $config_handler->getConfigsByCat(XOOPS_CONF);
		
		$this->root->siteinfo['rooturl'] = XOOPS_URL.'/';
		$this->root->siteinfo['loginurl'] = XOOPS_URL.'/user.php';
		$this->root->siteinfo['sitename'] = $xoopsConfig['sitename'];
		$this->root->siteinfo['anonymous'] = $xoopsConfig['anonymous'];
	}
		
	function set_userinfo () {
		
		global $xoopsUser;
		
		$module_handler =& xoops_gethandler('module');
		$XoopsModule =& $module_handler->getByDirname($this->root->mydirname);
		
		if (is_object($xoopsUser))
		{
			$this->root->userinfo['admin'] = $xoopsUser->isAdmin($XoopsModule->mid());
			$this->root->userinfo['uid'] = (int)$xoopsUser->uid();
			$this->root->userinfo['uname'] = $xoopsUser->uname();
			$this->root->userinfo['uname_s'] = htmlspecialchars($this->root->userinfo['uname']);
			$this->root->userinfo['gids'] = $xoopsUser->getGroups();
		}
		else
		{
			parent::set_userinfo();
		}
	}
	
	function get_userinfo_by_id ($uid) {
		$config_handler =& xoops_gethandler('config');
		$xoopsConfig =& $config_handler->getConfigsByCat(XOOPS_CONF);

		$result = parent::get_userinfo_by_id($uid, $xoopsConfig['anonymous']);
		$user_handler =& xoops_gethandler('user');
		$user =& $user_handler->get( $uid );
		if (is_object($user)) {
			$result['uname'] = $user->uname();
			$result['email'] = $user->email();
		}
		return $result;
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
		$language = preg_replace('/^(.*)(utf)?$/i', '$1', $xoopsConfig['language']);
		switch (strtolower($language)) {
			case "japanese" :
			case "japaneseutf" :
				return "ja";
			case "english" :
				return "en";
			default:
				return $default;
		}
	}

	function get_setlang ($default) {
		if (defined('EASIESTML_LANGS')) return 'easiestml_lang'; // GIJOE's EMLH
		else if (defined('SYSUTIL_ML_PARAM_NAME')) return SYSUTIL_ML_PARAM_NAME; // nobunobu's sysutil
		else if (defined('CUBE_UTILS_ML_PARAM_NAME')) return CUBE_UTILS_ML_PARAM_NAME; // nobunobu's cubeUtils
		else return $default;
	}
	
	function get_setlang_c ($default) {
		if (defined('EASIESTML_LANGS')) return 'easiestml_lang'; // GIJOE's EMLH
		else if (defined('SYSUTIL_ML_PARAM_NAME')) return SYSUTIL_ML_PARAM_NAME; // nobunobu's sysutil
		else if (defined('CUBE_UTILS_ML_PARAM_NAME')) return CUBE_UTILS_ML_PARAM_NAME; // nobunobu's cubeUtils
		else return $default;
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
		}
	
		if ($subject == '' || ($message == '' && empty($footer))) return FALSE;
	
		// Subject:
		if (isset($footer['PAGE'])) $subject = str_replace('$page', $footer['PAGE'], $subject);
	
		// Footer
		$footer['UID'] = $this->root->userinfo['uid'];
		$footer['UNAME'] = $this->root->userinfo['uname'] . ' [' . $this->root->userinfo['ucd'] . ']';
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
	
		$config_handler =& xoops_gethandler('config');
		$xoopsConfig =& $config_handler->getConfigsByCat(XOOPS_CONF);
		
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

	// ユーザーが所属するグループIDを得る
	function get_mygroups($uid = NULL){
		if (is_null($uid)) $uid = $this->root->userinfo['uid'];
		$XM =& xoops_gethandler('member');
		return $XM->getGroupsByUser($uid);
	}
	// グループ一覧を得る
	function get_group_list()
	{
		$XM =& xoops_gethandler('member');
		$ret = $XM->getGroupList();
		unset($ret[1]);
		return $ret;
	}
	
	// 登録ユーザー一覧を得る
	function get_allusers($sort=true)
	{
		$sort = $sort? 1 : 0;
		static $users = array();
		if (isset($users[$sort])) return $users[$sort];
		
		$X_M = xoops_gethandler('member');
		$ret = $X_M->getUserList();

		if ($sort) asort($ret);
		$users[$sort] = $ret;
		return $ret;
	}
	
	// ユーザー名を得る
	function getUnameFromId ($uid) {
		static $user = NULL;
		if (is_null($user)) {
			$user = new XoopsUser();
		}
		return $user->getUnameFromId($uid);
	}
	
	// 管理者権限があるか調べる
	function check_admin ($uid = NULL) {
		if (is_null($uid)) $uid = $this->root->userinfo['uid'];
		
		if (!$uid) return FALSE;
		
		$module_handler =& xoops_gethandler('module');
		$member_handler =& xoops_gethandler('member');
		
		$XoopsModule =& $module_handler->getByDirname($this->root->mydirname);
		$xoopsUser =& $member_handler->getUser($uid);
		return $xoopsUser->isAdmin($XoopsModule->mid());
	}
	
	// 最終更新者名を得る
	function get_lasteditor($pginfo, $withlink = TRUE, $withucd = TRUE) {
		
		if ($pginfo['lastuid']) {
			if ($withlink) {
				$lasteditor = '<a href="'.XOOPS_URL.'/userinfo.php?uid='.$pginfo['lastuid'].'">' . $pginfo['lastuname'] . '</a>';
			} else {
				$lasteditor = $pginfo['lastuname'];
			}
		} else {
			if ($withucd) {
				$lasteditor = $pginfo['lastuname']. ($pginfo['lastucd']? '['.$pginfo['lastucd'].']' : '');
			} else {
				$lasteditor = $pginfo['lastuname'];
			}
		}
		return $lasteditor;
	}
	// ページコメント取得
	function get_page_comments ($page) {
		
		if (!$this->root->allow_pagecomment) return '';
		
		$pgid = $this->get_pgid_by_name($page);
		if (!$pgid) return '';
		
		require_once XOOPS_ROOT_PATH.'/class/template.php';
		$tpl = new XoopsTpl();
		// assign
		$tpl->assign(
			array(
				'mod_config' => $this->root->module['config'] ,
				'content' => array (
								'id' => $pgid,
								'subject' => $page,
							),
			)
		);
		return $tpl->fetch( 'db:'.$this->root->mydirname.'_main_d3comment.html' ) ;	
	}
	
	// ページコメント件数取得
	function count_page_comments ($page) {
		if (!$this->root->allow_pagecomment) return 0;
		
		$pgid = $this->get_pgid_by_name($page);
		if (!$pgid) return 0;

		$count = 0;		
		$sql = "SELECT COUNT(t.topic_id) FROM ".$this->xpwiki->db->prefix($this->root->module['config']['comment_dirname']."_topics")." t WHERE t.forum_id={$this->root->module['config']['comment_forum_id']} AND ! t.topic_invisible AND topic_external_link_id=$pgid" ;
		if( $trs = $this->xpwiki->db->query( $sql ) ) {
			list( $count ) = $this->xpwiki->db->fetchRow( $trs ) ;
		}
		return $count;
	}
	
	// リダイレクト
	function redirect_header($url, $wait = 3, $title = '', $addredirect = true) {
		redirect_header($url, $wait, $title, $addredirect);
		exit;
	}
}
?>