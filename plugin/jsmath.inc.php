<?php
/*
 * Created on 2008/03/04 by nao-pon http://hypweb.net/
 * $Id: jsmath.inc.php,v 1.1 2008/03/06 23:55:13 nao-pon Exp $
 */

class xpwiki_plugin_jsmath extends xpwiki_plugin {
	function plugin_jsmath_init () {
		$this->config['checkPath'] = $this->cont['ROOT_PATH'] . 'jsMath/';
		$this->config['jsUrl'] = $this->cont['ROOT_URL'] . 'jsMath/easy/load.js';
	}
	
	function plugin_jsmath_convert () {
		if (! file_exists($this->config['checkPath'])) {
			$into = (! $this->root->userinfo['admin'])? '' : ' (Into: ' . $this->config['checkPath'] . ')';
			return '<div>jsMath not found. Please install <a href="http://www.math.union.edu/~dpvc/jsMath/download/jsMath.html">jsMath</a> library.' . $into . '</div>' . "\n";
		}
	
		$this->func->add_tag_head('jsmath.css');
		$this->func->add_js_head($this->config['jsUrl']);

		return '';
	}
}
?>