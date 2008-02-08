<?php
class xpwiki_plugin_menu extends xpwiki_plugin {
	function plugin_menu_init () {


	/////////////////////////////////////////////////
	// PukiWiki - Yet another WikiWikiWeb clone.
	//
	// $Id: menu.inc.php,v 1.6 2008/02/08 03:02:18 nao-pon Exp $
	//
	
	// サブメニューを使用する
		$this->cont['MENU_ENABLE_SUBMENU'] =  FALSE;
	
	// サブメニューの名称
		$this->cont['MENU_SUBMENUBAR'] =  'MenuBar';

	}
	
	function plugin_menu_convert()
	{
	//	global $vars, $menubar;
	//	static $menu = NULL;
		static $menu = array();
		if (!isset($menu[$this->xpwiki->pid])) {$menu[$this->xpwiki->pid] = NULL;}
	
		$num = func_num_args();
		if ($num > 0) {
			// Try to change default 'MenuBar' page name (only)
			if ($num > 1)       return '#menu(): Zero or One argument needed';
			if ($menu[$this->xpwiki->pid] !== NULL) return '#menu(): Already set: ' . htmlspecialchars($menu[$this->xpwiki->pid]);
			$args = func_get_args();
			if (! $this->func->is_page($args[0])) {
				return '#menu(): No such page: ' . htmlspecialchars($args[0]);
			} else {
				$menu[$this->xpwiki->pid] = $args[0]; // Set
				return '';
			}
	
		} else {
			// Output menubar page data
			$page = ($menu[$this->xpwiki->pid] === NULL) ? $this->root->menubar : $menu[$this->xpwiki->pid];
	
			if ($this->cont['MENU_ENABLE_SUBMENU']) {
				$path = explode('/', $this->func->strip_bracket($this->root->vars['page']));
				while(! empty($path)) {
					$_page = join('/', $path) . '/' . $this->cont['MENU_SUBMENUBAR'];
					if ($this->func->is_page($_page)) {
						$page = $_page;
						break;
					}
					array_pop($path);
				}
			}
	
			if (! $this->func->is_page($page)) {
				return '';
			} else if ($this->root->vars['page'] == $page) {
				return '<!-- #menu(): You already view ' . htmlspecialchars($page) . ' -->';
			} else {
				// Cut fixed anchors
				$menutext = preg_replace('/^(\*{1,5}.*)\[#[A-Za-z][\w-]+\](.*)$/m', '$1$2', $this->func->get_source($page));
				// remove xoops_block if runmode = xoops.
				if ($this->root->render_mode === 'block' && $this->root->runmode === "xoops") {
					$menutext = preg_replace("/^#xoopsblock.*$/m","",$menutext);
				}
				return '<div class="menuber">' . preg_replace('/<ul[^>]*>/', '<ul>', $this->func->convert_html($menutext)) . '</div>';  
			}
		}
	}
}
?>