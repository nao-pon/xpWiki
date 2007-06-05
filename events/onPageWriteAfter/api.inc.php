<?php
/*
 * Created on 2007/05/22 by nao-pon http://hypweb.net/
 * $Id: api.inc.php,v 1.2 2007/06/05 00:24:09 nao-pon Exp $
 */

function xpwiki_onPageWriteAfter_api (&$xpwiki_func, &$page, &$postdata, &$notimestamp, &$mode) {

	if ( $mode !== 'update' ) {
		// Clear cache *.autolink.api
		$base = $xpwiki_func->cont['CACHE_DIR'];
		if (function_exists('glob')) {
			chdir($base);
			foreach (glob("*.autolink.api") as $file) {
				unlink($base . $file);
			}
			chdir($this->cont['DATA_HOME']);
		} else {
			if ($dir = @opendir($base))
			{
				while($file = readdir($dir))
				{
					if (substr($file, -13) === '.autolink.api') unlink($base . $file);
				}
			}
		}
	}
}

?>
