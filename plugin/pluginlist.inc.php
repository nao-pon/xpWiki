<?php
/*
 * Created on 2008/03/25 by nao-pon http://hypweb.net/
 * $Id: pluginlist.inc.php,v 1.1 2008/03/25 03:03:06 nao-pon Exp $
 */

class xpwiki_plugin_pluginlist extends xpwiki_plugin {
	function plugin_pluginlist_init () {
	
	}
	
	function plugin_pluginlist_convert () {
		return $this->build_list();
	}
	
	function build_list () {
		$plugins = array();
		if ($dh = opendir($this->root->mytrustdirpath . '/plugin/')) {
			while (($file = readdir($dh)) !== false) {
				if (preg_match('/^([a-z_]+)\.inc\.php$/i', $file, $match)) {
					$plugins[] = $match[1];	
				}
			}
			closedir($dh);
		}
		$inlines = $blocks = array();
		foreach($plugins as $name) {
			$checks[] = $name;
			if ($this->func->exist_plugin_convert($name)) {
				$blocks[] = '#' . $name;
			}
			if ($this->func->exist_plugin_inline($name)) {
				$inlines[] = '&' . $name;
			}
		}
		$html = join('<br />', $blocks);
		$html .= join('<br />', $inlines);
		return $html;
	}
}
?>