<?php
/*
 * Created on 2007/06/02 by nao-pon http://hypweb.net/
 * $Id: render.inc.php,v 1.1 2007/06/03 05:24:09 nao-pon Exp $
 */

function xpwiki_onPageWriteAfter_render (&$xpwiki_func, &$page, &$postdata, &$notimestamp, &$mode) {

	if ( $mode !== 'update' ) {
		// Clear cache render_*
		$base = $xpwiki_func->cont['CACHE_DIR'];
		if (function_exists('glob')) {
			foreach (glob($base . "render_*") as $file) {
				unlink($file);
			}
		} else {
			if ($dir = @opendir($base)) {
				while($file = readdir($dir)) {
					if (substr($file, 0, 7) === 'render_') unlink($base . '/' . $file);
				}
			}
		}
	}
}

?>
