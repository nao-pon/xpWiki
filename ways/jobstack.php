<?php
/*
 * Created on 2008/05/13 by nao-pon http://hypweb.net/
 * $Id: jobstack.php,v 1.3 2008/05/15 23:53:06 nao-pon Exp $
 */

error_reporting(0);

ignore_user_abort(TRUE);

include_once $mytrustdirpath . '/include.php';

$xpwiki = new XpWiki($mydirname);
$xpwiki->init('#RenderMode');

$sql = 'SELECT `key`, `data` FROM '.$xpwiki->db->prefix($xpwiki->root->mydirname.'_cache').' WHERE `plugin`=\'jobstack\' ORDER BY `mtime` ASC LIMIT 1';

if ($res = $xpwiki->db->query($sql)) {
	list($key, $data) = $xpwiki->db->fetchRow($res);
	$xpwiki->func->cache_del_db($key, 'jobstack');
	$data = unserialize($data);
	switch ($data['action']) {
		case 'http_get':
			$xpwiki->func->http_request($data['url']);
			break;
		case 'plain_up':
			xpwiki_jobstack_plain_up($xpwiki, $data['page'], $data['mode']);
			break;
	}
}

$file = $mytrustdirpath . '/skin/image/gif/blank.gif';

header('Content-Type: image/gif');
header('Content-Length: ' . filesize($file));
header('Expires: Thu, 01 Dec 1994 16:00:00 GMT');
header('Last-Modified: '. gmdate('D, d M Y H:i:s'). ' GMT');
header('Cache-Control: no-cache, no-store, must-revalidate, pre-check=0, post-check=0');
header('Pragma: no-cache');

readfile($file);

function xpwiki_jobstack_plain_up (& $xpwiki, $page, $mode) {
	if ($xpwiki->func->is_page($page)) {
		$notimestamp = FALSE;
		if ($mode === 'update_notimestamp') {
			$notimestamp = TRUE;
			$mode = 'update';
		}
		$xpwiki->func->plain_db_write($page, $mode, FALSE, $notimestamp);
	}
	
	// 古いレンダーキャッシュファイルの削除 (1日1回程度)
	$pagemove_time = @ filemtime($xpwiki->cont['CACHE_DIR'] . 'pagemove.time');
	if ($pagemove_time) {
		$render_cache_clr = @ filemtime($xpwiki->cont['CACHE_DIR'] . 'render_cache_clr.time');	
		if ($render_cache_clr < $xpwiki->cont['UTC'] - 86400) {
			touch($xpwiki->cont['CACHE_DIR'] . 'render_cache_clr.time');
			if ($handle = opendir($xpwiki->cont['RENDER_CACHE_DIR'])) {
				while (false !== ($file = readdir($handle))) {
					if (substr($file, 0, 7) === 'render_') {
						$file = $xpwiki->cont['RENDER_CACHE_DIR'] . $file;
						if (filemtime($file) < $pagemove_time) {
							unlink($file);
						}
					}
				}
				closedir($handle);
			}
		}
	}
}
?>