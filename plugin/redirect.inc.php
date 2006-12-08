<?php
//
// Created on 2006/12/08 by nao-pon http://hypweb.net/
// $Id: redirect.inc.php,v 1.1 2006/12/08 06:03:28 nao-pon Exp $
//
class xpwiki_plugin_redirect extends xpwiki_plugin {
	function plugin_redirect_action()
	{
		if (empty($this->root->vars['to'])) return FALSE;
		$to = preg_replace('#^(\.*/)+#' , '', trim($this->root->vars['to']));
		header('Location: '.$this->cont['ROOT_URL'].$to);
		exit();
	}
}
?>