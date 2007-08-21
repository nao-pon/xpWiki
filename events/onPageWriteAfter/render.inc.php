<?php
/*
 * Created on 2007/06/02 by nao-pon http://hypweb.net/
 * $Id: render.inc.php,v 1.2 2007/08/21 06:11:05 nao-pon Exp $
 */

function xpwiki_onPageWriteAfter_render (&$xpwiki_func, &$page, &$postdata, &$notimestamp, &$mode) {

	if ( $mode !== 'update' ) {
		// Clear cache render_*
		$GLOBALS['xpwiki_cache_deletes'][$xpwiki_func->cont['CACHE_DIR']][] = 'render_*';
	}
}

?>
