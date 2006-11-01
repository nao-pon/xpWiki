<?php
//
// Created on 2006/10/31 by nao-pon http://hypweb.net/
// $Id: tag.inc.php,v 1.2 2006/11/01 00:38:38 nao-pon Exp $
//
if (file_exists($this->cont['CACHE_DIR'] . $this->encode($page) . '_page.tag')) {
	// ページのtagデータファイルがある場合
	if ($mode === 'delete' || ($mode === 'update' && strpos($postdata,'&tag') === FALSE)) {
		// ページ削除または&tag();を削除した場合
		$tag_plugin =& $this->get_plugin_instance('tag');
		if ($tag_plugin !== FALSE) {
			$aryargs = array($page, array());
			call_user_func_array(array($this->root->plugin_tag, 'renew_tagcache'), $aryargs);
		}
		unset($tag_plugin);
	} else if ($mode === 'update' && $notimestamp) {
		// ページ編集でページタイムスタンプを保持する場合
		$this->pkwk_touch_file($this->cont['CACHE_DIR'] . $this->encode($page) . '_page.tag',1);
	}
}
?>