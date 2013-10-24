<?php
// PukiWiki - Yet another WikiWikiWeb clone
//
// Clear plugin - inserts a CSS class 'clear', to set 'clear:both'


class xpwiki_plugin_clear extends xpwiki_plugin {
	function plugin_clear_init () {

	}

	function plugin_clear_convert() {
		$args = func_get_args();
		$class = $this->getClass($args);
		return '<div class="'.$class .'"></div>';
	}
	
	function plugin_clear_inline() {
		$args = func_get_args();
		array_pop($args);
		$class = $this->getClass($args);
		return '<span class="'.$class .'" style="display: block;"></span>';
	}
	
	private function getClass($args) {
		list($side) = array_pad($args, 1, '');
		$side = strtolower($side);
		$class = 'clear';
		if (in_array($side, array('left', 'right'))) {
			$class .= '_'.$side;
		}
		return $class;
	}
}
