<?php
/*
 * Created on 2009/11/10 by nao-pon http://xoops.hypweb.net/
 * $Id: bitly.inc.php,v 1.1 2009/11/17 09:03:36 nao-pon Exp $
 */

class xpwiki_plugin_bitly extends xpwiki_plugin {
	function plugin_bitly_init() {

	}

	function plugin_bitly_inline() {
		$args = func_get_args();
		$body = array_pop($args);
		if ($args) {
			$url = array_pop($args);
			$title = preg_replace('#^https?://#i', '', $url);
			if ($title !== $url) {
				$title = htmlspecialchars($title);
				$url = $this->func->bitly($url, FALSE, TRUE);
				return '<a href="' . htmlspecialchars($url) . '" title="' . $title . '">' . htmlspecialchars($url) . '</a>';
			}
		}
		return FALSE;
	}
}
