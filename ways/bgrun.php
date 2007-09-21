<?php
/*
 * Created on 2007/09/21 by nao-pon http://hypweb.net/
 * $Id: bgrun.php,v 1.1 2007/09/21 06:18:40 nao-pon Exp $
 */

error_reporting(0);

$page = (isset($_GET['page']))? strval($_GET['page']) : '';

if ($page === '') exit();

include_once "$mytrustdirpath/include.php";

$xpwiki = new XpWiki($mydirname);
$xpwiki->init('#RenderMode');

if ($xpwiki->func->is_page($page)) {
	$_udp_file = $xpwiki->cont['CACHE_DIR'].$xpwiki->func->encode($page).".udp";
	if (file_exists($_udp_file)) {
		$_udp_mode = join('',file($_udp_file));
		unlink($_udp_file);
		$xpwiki->func->plain_db_write($page, $_udp_mode);
	}
}

$file = $mytrustdirpath . '/skin/image/gif/blank.gif';

header('Content-Type: image/gif');
header('Content-Length: ' . filesize($file));

readfile($file);

?>