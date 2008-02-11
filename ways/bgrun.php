<?php
/*
 * Created on 2007/09/21 by nao-pon http://hypweb.net/
 * $Id: bgrun.php,v 1.5 2008/02/11 01:02:41 nao-pon Exp $
 */

error_reporting(0);

$page = (isset($_GET['page']))? strval($_GET['page']) : '';

if ($page === '') exit();

ignore_user_abort(TRUE);

include_once "$mytrustdirpath/include.php";

$xpwiki = new XpWiki($mydirname);
$xpwiki->init('#RenderMode');

if ($xpwiki->func->is_page($page)) {
	$_udp_file = $xpwiki->cont['CACHE_DIR'].$xpwiki->func->encode($page).".udp";
	if (file_exists($_udp_file)) {
		$_udp_mode = file_get_contents($_udp_file);
		unlink($_udp_file);
		$xpwiki->func->plain_db_write($page, $_udp_mode);
	}
}

// 古いレンダーキャッシュファイルの削除 (1日1回程度)
$pagemove_time = @ filemtime($xpwiki->cont['CACHE_DIR'] . 'pagemove.time');
if ($pagemove_time) {
	$render_cache_clr = @ filemtime($xpwiki->cont['CACHE_DIR'] . 'render_cache_clr.time');	
	if ($render_cache_clr < time() - 86400) {
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

$file = $mytrustdirpath . '/skin/image/gif/blank.gif';

header('Content-Type: image/gif');
header('Content-Length: ' . filesize($file));

readfile($file);

?>