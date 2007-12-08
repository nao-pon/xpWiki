<?php
class xpwiki_plugin_include extends xpwiki_plugin {
	function plugin_include_init () {


	// PukiWiki - Yet another WikiWikiWeb clone.
	// $Id: include.inc.php,v 1.3 2007/12/08 11:31:14 nao-pon Exp $
	//
	// Include-once plugin
	
	//--------
	//	| PageA
	//	|
	//	| // #include(PageB)
	//	---------
	//		| PageB
	//		|
	//		| // #include(PageC)
	//		---------
	//			| PageC
	//			|
	//		--------- // PageC end
	//		|
	//		| // #include(PageD)
	//		---------
	//			| PageD
	//			|
	//		--------- // PageD end
	//		|
	//	--------- // PageB end
	//	|
	//	| #include(): Included already: PageC
	//	|
	//	| // #include(PageE)
	//	---------
	//		| PageE
	//		|
	//	--------- // PageE end
	//	|
	//	| #include(): Limit exceeded: PageF
	//	| // When PLUGIN_INCLUDE_MAX == 4
	//	|
	//	|
	//-------- // PageA end
	
	// ----
	
	// Default value of 'title|notitle' option
		$this->cont['PLUGIN_INCLUDE_WITH_TITLE'] =  TRUE;	// Default: TRUE(title)
	
	// Max pages allowed to be included at a time
		$this->cont['PLUGIN_INCLUDE_MAX'] =  4;
	
	// ----
		$this->cont['PLUGIN_INCLUDE_USAGE'] =  '#include(): Usage: (a-page-name-you-want-to-include[,title|,notitle])';

	}
	
	function plugin_include_convert()
	{
	//	global $script, $vars, $get, $post, $menubar, $_msg_include_restrict;
	//	static $included = array();
		static $included = array();
		if (!isset($included[$this->xpwiki->pid])) {$included[$this->xpwiki->pid] = array();}
	//	static $count = 1;
		static $count = array();
		if (!isset($count[$this->xpwiki->pid])) {$count[$this->xpwiki->pid] = 1;}
	
		if (func_num_args() == 0) return $this->cont['PLUGIN_INCLUDE_USAGE'] . '<br />' . "\n";;
	
		// $menubar will already be shown via menu plugin
		if (! isset($included[$this->xpwiki->pid][$this->root->menubar])) $included[$this->xpwiki->pid][$this->root->menubar] = TRUE;
	
		// Loop yourself
		$root = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
		$included[$this->xpwiki->pid][$root] = TRUE;
	
		// Get arguments
		$args = func_get_args();
		// strip_bracket() is not necessary but compatible
		$page = isset($args[0]) ? $this->func->get_fullname($this->func->strip_bracket(array_shift($args)), $root) : '';
		$with_title = $this->cont['PLUGIN_INCLUDE_WITH_TITLE'];
		if (isset($args[0])) {
			switch(strtolower(array_shift($args))) {
			case 'title'  : $with_title = TRUE;  break;
			case 'notitle': $with_title = FALSE; break;
			}
		}
	
		$s_page = htmlspecialchars($page);
		$r_page = rawurlencode($page);
		$link = '<a href="' . $this->root->script . '?' . $r_page . '">' . $s_page . '</a>'; // Read link
	
		// I'm stuffed
		if (isset($included[$this->xpwiki->pid][$page])) {
			return '#include(): Included already: ' . $link . '<br />' . "\n";
		} if (! $this->func->is_page($page)) {
			return '#include(): No such page: ' . $s_page . '<br />' . "\n";
		} if ($count[$this->xpwiki->pid] > $this->cont['PLUGIN_INCLUDE_MAX']) {
			return '#include(): Limit exceeded: ' . $link . '<br />' . "\n";
		} else {
			++$count[$this->xpwiki->pid];
		}
	
		// One page, only one time, at a time
		$included[$this->xpwiki->pid][$page] = TRUE;
	
		// Include A page, that probably includes another pages
		if ($this->func->check_readable($page, false, false)) {
			$body = $this->func->convert_html($this->func->get_source($page), $page);
		} else {
			$body = str_replace('$1', $page, $this->root->_msg_include_restrict);
		}
	
		// Put a title-with-edit-link, before including document
		if ($with_title) {
			$link = '<a href="' . $this->root->script . '?cmd=edit&amp;page=' . $r_page .
			'">' . $s_page . '</a>';
			if ($page === $this->root->menubar) {
				$body = '<span align="center"><h5 class="side_label">' .
				$link . '</h5></span><small>' . $body . '</small>';
			} else {
				$body = '<h1>' . $link . '</h1>' . "\n" . $body . "\n";
			}
		}
	
		return $body;
	}
}
?>