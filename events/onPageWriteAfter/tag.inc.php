<?php
//
// Created on 2006/10/31 by nao-pon http://hypweb.net/
// $Id: tag.inc.php,v 1.1 2006/10/31 06:13:23 nao-pon Exp $
//
if ($mode === 'delete') {
	$tag_plugin =& $this->get_plugin_instance('tag');
	if ($tag_plugin !== FALSE) {
		$aryargs = array($page, array());
		call_user_func_array(array($this->root->plugin_tag, 'renew_tagcache'), $aryargs);
	}
}
?>