<?php
//
// Created on 2006/12/04 by nao-pon http://hypweb.net/
// $Id: rss.inc.php,v 1.2 2007/01/11 08:33:29 nao-pon Exp $
//

function xpwiki_onPageWriteAfter_rss(&$xpwiki_func, &$page, &$postdata, &$notimestamp, &$mode, &$diffdata) {
	// CACHE_DIR/plugin/*.rss ¤òºï½ü
	$base = $xpwiki_func->cont['CACHE_DIR'].'/plugin';
	if ($dir = @opendir($base))
	{
		while($file = readdir($dir))
		{
			if (substr($file, -4) === '.rss') unlink($base . '/' . $file);
		}
	}
}
?>