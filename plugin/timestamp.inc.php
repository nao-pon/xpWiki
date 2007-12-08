<?php
/*
 * Created on 2007/06/03 by nao-pon http://hypweb.net/
 * $Id: timestamp.inc.php,v 1.2 2007/12/08 12:11:14 nao-pon Exp $
 */

class xpwiki_plugin_timestamp extends xpwiki_plugin {
	function plugin_timestamp_init () {
	}
	
	function plugin_timestamp_action () {
		$pmode = (isset($this->root->vars['pmode']))? $this->root->vars['pmode'] : ''; 
		if ($pmode === 'makedata') { return $this->makedata(); }
		else { return; }
	}
	
	function makedata () {
		
		// 管理画面モード指定
		if ($this->root->module['platform'] == "xoops") {
			$this->root->runmode = "xoops_admin";
		}
		
		$dat = '';
		if ($dir = @opendir($this->cont['DATA_DIR'])) {
			while($file = readdir($dir)) {
				if (substr($file, -4) === '.txt' && $file !== '526563656E744368616E676573.txt') {
					$time = filemtime($this->cont['DATA_DIR'].$file);
					$dat .= $file."\t".$time."\n";
				}
			}
		}
		
		$ng = '';
		$datafile = $this->cont['DATA_DIR'].'.timestamp';
		if ($fp = fopen($datafile, 'wb')) {
			fwrite($fp, $dat);
			fclose($fp);
		} else {
			$ng = 'NOT ';
		}
		
		$ret['msg'] = $ng.'Maked timestamp data.';
		$ret['body'] = $ng.'Maked a file "'.$this->cont['DATA_DIR'].'.timestamp"';
		return $ret;
	}
}
?>
