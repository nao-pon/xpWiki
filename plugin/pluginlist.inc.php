<?php
/*
 * Created on 2008/03/25 by nao-pon http://hypweb.net/
 * $Id: pluginlist.inc.php,v 1.2 2008/03/25 05:16:12 nao-pon Exp $
 */

class xpwiki_plugin_pluginlist extends xpwiki_plugin {
	function plugin_pluginlist_init () {
	
	}
	
	function plugin_pluginlist_convert () {
		return '<p>' . $this->build_list() . '</p>';
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
		$cmds = $inlines = $blocks = array();
		foreach($plugins as $name) {
			$checks[] = $name;
			if ($this->func->exist_plugin_convert($name)) {
				$blocks[] = $name;
			}
			if ($this->func->exist_plugin_inline($name)) {
				$inlines[] = $name;
			}
			if ($this->func->exist_plugin_action($name)) {
				$cmds[] = $name;
			}
		}
		sort($blocks);
		sort($inlines);
		$html = '<h4>Block plugins</h4><ul><li>#';
		$html .= join('</li><li>#', $blocks);
		$html .= '</li></ul><hr /><h4>Inline plugins</h4><ul><li>&amp;';
		$html .= join(';</li><li>&amp;', $inlines);
		$html .= ';</li></ul><hr /><h4>Command plugins</h4><ul><li>';
		$html .= join('</li><li>', $cmds);
		$html .= '</li></ul>';
		return $html;
	}
}
?>