<?php
//
// Created on 2006/12/04 by nao-pon http://hypweb.net/
// $Id: rss.inc.php,v 1.1 2006/12/05 00:03:29 nao-pon Exp $
//

// CACHE_DIR/plugin/*.rss ¤òºï½ü
$base = $this->cont['CACHE_DIR'].'/plugin';
if ($dir = @opendir($base))
{
	while($file = readdir($dir))
	{
		if (substr($file, -4) === '.rss') unlink($base . '/' . $file);
	}
}
?>