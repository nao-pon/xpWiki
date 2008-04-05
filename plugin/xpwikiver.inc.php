<?php
//
// Created on 2006/11/28 by nao-pon http://hypweb.net/
// $Id: xpwikiver.inc.php,v 1.4 2008/04/05 04:53:11 nao-pon Exp $
//
class xpwiki_plugin_xpwikiver extends xpwiki_plugin {

	function plugin_xpwikiver_init() {
		return $this->time_limit = 1800; // 30 min
	}

	function plugin_xpwikiver_convert() {
		return '<p>' . $this->get_ver() . '</p>';
	}


	function plugin_xpwikiver_inline() {
		return $this->get_ver();
	}
	
	function get_ver() {
		
		$c_file = $this->cont['CACHE_DIR'] . 'plugin/xpwikiver.dat';
		
		if (file_exists($c_file) && filemtime($c_file) + $this->time_limit > $this->cont['UTC']) {
			return file_get_contents($c_file);
		}
		
		$url = 'http://cvs.sourceforge.jp/cgi-bin/viewcvs.cgi/*checkout*/hypweb/XOOPS_TRUST/modules/xpwiki/xoops_version.php?content-type=text%2Fplain';
		
		$dat = $this->func->http_request($url);
		
		$ver = '[Error]';

		if ($dat['rc'] === 200) {
			$data = $dat['data'];

			if (preg_match('/\$modversion\[\'version\'\]\s*=\s*\'([0-9.]+)/', $data, $match)) {
				$ver = $match[1];
			}
	
		}
		
		if ($fp = fopen($c_file,"wb")) {
			fputs($fp, $ver);
			fclose($fp);
		}
		
		return $ver;		
	}
}
?>