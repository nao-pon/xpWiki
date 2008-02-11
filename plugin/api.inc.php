<?php
/*
 * Created on 2007/04/11 by nao-pon http://hypweb.net/
 * $Id: api.inc.php,v 1.7 2008/02/11 01:02:41 nao-pon Exp $
 */

class xpwiki_plugin_api extends xpwiki_plugin {
	function plugin_api_init () {
	}
	
	function plugin_api_action () {

		$cmd = (isset($this->root->vars['pcmd']))? (string)$this->root->vars['pcmd'] : '';
		
		switch ($cmd) {
			case 'autolink':
				$this->autolink();
		}
		
		exit();
		
	}
	
	function autolink ($need_ret = false, $base = null) {
		if (is_null($base)) {
			$base = '';
			$base = (isset($this->root->vars['base']))? (string)$this->root->vars['base'] : '';
		}
		$base = trim($base, '/');
		
		$cache = $this->cont['CACHE_DIR'].sha1($base).'.autolink.api';
		
		if (file_exists($cache)) {
			$out = file_get_contents($cache);
		} else {
			$pages = array();
			if (!$base || $this->func->is_page($base)) {
				// Get WHOLE page list (always as guest)
				$temp[0] = $this->root->userinfo['admin'];
				$temp[1] = $this->root->userinfo['uid'];
				$this->root->userinfo['admin'] = FALSE;
				$this->root->userinfo['uid'] = 0;
				
				$options['where'] = '`name` NOT LIKE ":%"';
				$pages = $this->func->get_existpages(FALSE, ($base ? ($base . '/') : ''), $options);
				
				$this->root->userinfo['admin'] = $temp[0];
				$this->root->userinfo['uid'] = $temp[1];
				
				// Get all aliases
				$all_aliases = array_keys(array_intersect($this->root->page_aliases,  $this->func->get_existpages()));
				if ($base) {
					// Extract from all aliases
					foreach($all_aliases as $_aliase) {
						if (strpos($_aliase, $base . '/') === 0) {
							$pages[] = $_aliase;
						}
					}
					// Strip $base
					$pages = array_map(create_function('$page','return substr($page,'.(strlen($base)+1).');'), $pages);
				} else {
					// Merge with all aliases
					$pages = array_merge($pages, $all_aliases);
				}
			}
			
			if ($pages) {
				//sort($pages, SORT_STRING);
				$out = $this->func->get_matcher_regex_safe($pages);
			} else {
				$out = '(?!)';
			}
			
			$fp = fopen($cache, 'w');
			fwrite($fp, $out);
			fclose($fp);
		}
		if ($need_ret) {
			return $out;
		} else {
			$this->output($out);
		}
	}
	
	function output ($str) {
		header ("Content-Type: text/plain; charset=".$this->cont['CONTENT_CHARSET']);
		header ("Content-Length: ".strlen($str));
		echo $str;
		exit();
	}
}

?>
