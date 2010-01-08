<?php
/*
 * Created on 2009/11/10 by nao-pon http://xoops.hypweb.net/
 * $Id: bitly.inc.php,v 1.2 2010/01/08 13:58:24 nao-pon Exp $
 */

class xpwiki_plugin_bitly extends xpwiki_plugin {
	function plugin_bitly_init() {

	}

	function can_call_otherdir_inline() {
		return 1;
	}

	function plugin_bitly_inline() {
		$args = func_get_args();
		$body = array_pop($args);
		if (! $args) {
			if ($this->root->render_mode === 'main') {
				$args = array($this->func->get_page_uri($this->root->vars['page'], true));
			} else {
				$this->root->pagecache_min = 0;
				$this->root->rtf['disable_render_cache'] = TRUE;
				$args = array(rtrim($this->cont['ROOT_URL'], '/') . $_SERVER['REQUEST_URI']);
			}
		}
		if ($args) {
			$url = array_pop($args);
			$title = preg_replace('#^https?://#i', '', $url);
			if ($title !== $url) {
				$title = htmlspecialchars($title);
				$url = $this->func->bitly($url, FALSE, TRUE);
				if ($body) {
					$body = preg_replace('#</?a[^>]*?>#i', '', $body);
				} else {
					$body = htmlspecialchars($url);
				}
				return '<a href="' . htmlspecialchars($url) . '" title="' . $title . '">' . $body . '</a>';
			}
		}
		return FALSE;
	}
}
