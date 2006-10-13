<?php
class xpwiki_plugin_tdiary extends xpwiki_plugin {
	function plugin_tdiary_init () {


	/*
	 tdiary plugin for pukiwiki
	 Author nazuna.(http://nazuna.sumomo.ne.jp/)
	 v1.01 2005/02/24
		*/
	
	// Usage (a part of)
		$this->cont['PLUGIN_TDIARY_USAGE'] =  '(tdiary-theme-name)';

	}
	
	function plugin_tdiary_convert() {
		// 引数の数をチェック
		$argc = func_num_args();
		if($argc != 1)
			return '<p>#tdiary(): Usage:' . $this->cont['PLUGIN_TDIARY_USAGE'] . "</p>\n";
	
		$argv = func_get_args();
		$theme_name = $argv[0];
		if(preg_match('/^([0-9A-Za-z-_])+$/', $theme_name)) {
			
			$theme_css = $this->cont['DATA_HOME'] . 'skin/tdiary_theme/' . $theme_name . '/' . $theme_name . '.css';
			if (file_exists($theme_css)) {
				$this->cont['SKIN_FILE'] = $this->cont['DATA_HOME'] . $this->cont['TDIARY_DIR'] . 'tdiary.skin.php';
				$this->cont['TDIARY_THEME'] =  $theme_name;
			}
		}
		else {
			return '<p>#tdiary(): Usage:' . $this->cont['PLUGIN_TDIARY_USAGE'] . "</p>\n";
		}
	
		return '';
	}
}
?>