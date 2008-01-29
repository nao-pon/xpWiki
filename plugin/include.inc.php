<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: include.inc.php,v 1.5 2008/01/29 23:54:36 nao-pon Exp $
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

class xpwiki_plugin_include extends xpwiki_plugin {
	function plugin_include_init () {
	
		// Default value of 'title|notitle' option
		$this->cont['PLUGIN_INCLUDE_WITH_TITLE'] =  TRUE;	// Default: TRUE(title)
	
		// Max pages allowed to be included at a time
		$this->cont['PLUGIN_INCLUDE_MAX'] =  4;
	
		// Usage
		$this->cont['PLUGIN_INCLUDE_USAGE'] =  '#include(): Usage: (a-page-name-you-want-to-include[,title|,notitle])';
		
		// Other xpWiki default mode ('html' or 'source'')
		$this->otherIncludeMode = 'html';
	}
	
	function plugin_include_convert()
	{
		static $included = array();
		if (!isset($included[$this->xpwiki->pid])) {$included[$this->xpwiki->pid] = array();}
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
		$pageKey = $page = isset($args[0]) ? $this->func->get_fullname($this->func->strip_bracket(array_shift($args)), $root) : '';
		
		$options = array(
			'title'   => FALSE,
			'notitle' => FALSE,
			'source'  => FALSE,
			'html'    => FALSE,
		);
		$this->fetch_options ($options, $args);
		
		if ($options['source']) {
			$this->otherIncludeMode = 'source';
		} else if ($options['html']) {
			$this->otherIncludeMode = 'html';
		}
		
		
		$targetObj = NULL;
		$isThis = TRUE;
		if (strpos($page, ':') !== FALSE) {
			list($other_dir, $_page) = explode(':', $page, 2);
			$targetObj =& XpWiki::getSingleton($other_dir);
			if ($targetObj->isXpWiki) {
				$targetObj->init('#RenderMode');
				$page = $_page;
				$isThis = FALSE;
			} else {
				$targetObj = NULL;
			}
		}
		if (is_null($targetObj)) {
			$targetObj =& $this;
		}
		
		// Include A page, that probably includes another pages
		if ($targetObj->func->check_readable($page, false, false)) {
//			$with_title = $this->cont['PLUGIN_INCLUDE_WITH_TITLE'];
//			if (isset($args[0])) {
//				switch(strtolower(array_shift($args))) {
//				case 'title'  : $with_title = TRUE;  break;
//				case 'notitle': $with_title = FALSE; break;
//				}
//			}
			$with_title = ($options['title']? TRUE : ($options['notitle']? FALSE : $this->cont['PLUGIN_INCLUDE_WITH_TITLE']));
		
			$s_page = htmlspecialchars($page);
			$r_page = rawurlencode($page);
			$link = '<a href="' . $this->func->get_page_uri($page, TRUE) . '">' . $s_page . '</a>'; // Read link
		
			// I'm stuffed
			$pageKey4disp = htmlspecialchars($pageKey);
			if ($this->root->render_mode === 'main' && isset($included[$this->xpwiki->pid][$pageKey])) {
				return '#include('.$pageKey4disp.'): Included already: ' . $link . '<br />' . "\n";
			} if (! $targetObj->func->is_page($page)) {
				return '#include(): No such page: ' . $s_page . '<br />' . "\n";
			} if ($count[$this->xpwiki->pid] > $this->cont['PLUGIN_INCLUDE_MAX']) {
				return '#include(): Limit exceeded: ' . $link . '<br />' . "\n";
			} else {
				++$count[$this->xpwiki->pid];
			}

			// for renderer mode
			$this->root->rtf['disable_render_cache'] = TRUE;
		
			// One page, only one time, at a time
			$included[$this->xpwiki->pid][$pageKey] = TRUE;
	
			if ($this->root->render_mode === 'render') {
				$_PKWK_READONLY = $this->cont['PKWK_READONLY'];
				$this->cont['PKWK_READONLY'] = $this->root->rtf['PKWK_READONLY'];
			}
			
			if ($this->otherIncludeMode === 'html') {
				if (!$isThis) {
					$targetObj->root->rtf = $this->root->rtf;
					$targetObj->root->foot_explain = $this->root->foot_explain;
					$targetObj->head_pre_tags = $this->root->head_pre_tags;
					$targetObj->root->head_tags = $this->root->head_tags;
				}
				$body = $targetObj->func->convert_html($targetObj->func->get_source($page), $page);
				if (!$isThis) {
					$this->root->rtf = $targetObj->root->rtf;
					$this->root->foot_explain = $targetObj->root->foot_explain;
					$this->root->head_pre_tags = $targetObj->root->head_pre_tags;
					$this->root->head_tags = $targetObj->root->head_tags;
				}
			} else {
				$body = $this->func->convert_html($targetObj->func->get_source($page), $page);
			}
			
			if ($this->root->render_mode === 'render') {
				$this->cont['PKWK_READONLY'] = $_PKWK_READONLY;
			}
		} else {
			$body = str_replace('$1', $page, $this->root->_msg_include_restrict);
		}
	
		// Put a title-with-edit-link, before including document
		if ($with_title) {
			$link = '<a href="' . $targetObj->root->script . '?cmd=edit&amp;page=' . $r_page .
			'">' . $s_page . '</a>';
			if ($page === $targetObj->root->menubar) {
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