<?php
class xpwiki_plugin_related extends xpwiki_plugin {
	function plugin_related_init () {



	}
	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: related.inc.php,v 1.3 2007/09/19 11:27:15 nao-pon Exp $
	//
	// Related plugin: Show Backlinks for the page
	
	function plugin_related_convert()
	{
		$args[0] = 0;
		if (func_num_args()) {
			$args  = func_get_args();
			$args[0] = intval($args[0]);
		}
		$max = ($args[0])? $args[0] : 0;
	
		return $this->func->make_related($this->root->vars['page'], 'p', $max);
	}
	
	// Show Backlinks: via related caches for the page
	function plugin_related_action()
	{
	//	global $vars, $script, $defaultpage, $whatsnew;
	
		$_page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
		if ($_page === '') $_page = $this->root->defaultpage;
	
		// Get related from cache
		$data = $this->func->links_get_related_db($_page);
		if (! empty($data)) {
			// Hide by array keys (not values)
			foreach(array_keys($data) as $page)
				if ($page === $this->root->whatsnew ||
				    $this->func->check_non_list($page))
					unset($data[$page]);
		}
	
		// Result
		$r_word = rawurlencode($_page);
		$s_word = htmlspecialchars($_page);
		$msg = 'Backlinks for: ' . $s_word;
		$retval  = '<a href="' . $this->root->script . '?' . $r_word . '">' .
		'Return to ' . $s_word .'</a><br />'. "\n";
	
		if (empty($data)) {
			$retval .= '<ul><li>No related pages found.</li></ul>' . "\n";	
		} else {
			// Show count($data)?
			ksort($data);
			$retval .= '<ul>' . "\n";
			foreach ($data as $page=>$time) {
				//$r_page  = rawurlencode($page);
				//$s_page  = htmlspecialchars($page);
				//$passage = $this->func->get_passage($time);
				//$retval .= ' <li><a href="' . $this->root->script . '?' . $r_page . '">' . $s_page .
				//'</a> ' . $passage . '</li>' . "\n";
				$retval .= ' <li>' . $this->func->make_pagelink($page) . '</li>' . "\n";
			}
			$retval .= '</ul>' . "\n";
		}
		return array('msg'=>$msg, 'body'=>$retval);
	}
}
?>