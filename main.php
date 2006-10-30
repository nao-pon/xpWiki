<?php

// Now debuging....
error_reporting(E_ALL);

$mytrustdirname = basename( dirname( __FILE__ ) ) ;
$mytrustdirpath = dirname( __FILE__ ) ;

// check permission of 'module_read' of this module
// (already checked by common.php)

// language files
$language = empty( $xoopsConfig['language'] ) ? 'english' : $xoopsConfig['language'] ;
if( file_exists( "$mydirpath/language/$language/main.php" ) ) {
	// user customized language file (already read by common.php)
	// include_once "$mydirpath/language/$language/main.php" ;
} else if( file_exists( "$mytrustdirpath/language/$language/main.php" ) ) {
	// default language file
	include_once "$mytrustdirpath/language/$language/main.php" ;
} else {
	// fallback english
	include_once "$mytrustdirpath/language/english/main.php" ;
}

include_once "$mytrustdirpath/include.php";

$xpwiki = new XpWiki($mydirname);

// initialize
// $xpwiki->init("[Page name]"); (if show a page.)
$xpwiki->init();

// execute
$xpwiki->execute();

// gethtml
$xpwiki->catbody();

if ($xpwiki->runmode == "xoops") {
	
	// get contents as array.
	//$xpwiki_outputs = $xpwiki->getcontent_as_array();
	
	// template name
	//$xoopsOption['template_main'] = $xpwiki->template_name;
	
	// xoops header
	include XOOPS_ROOT_PATH.'/header.php';
	
	// output to template
	// page title
	//$xoopsTpl->assign("xoops_pagetitle",$xpwiki_outputs['header']['title']."-".$xoopsModule->name());
	
	// contents
	//$xoopsTpl->assign("md_xpwiki_outputs",$xpwiki_outputs);
	
	/*
	// page comment
	if ($use_xoops_comments && $show_comments)
	{
		$HTTP_GET_VARS['pgid'] = $_GET['pgid'] = $pgid;
		$xoopsTpl->assign('show_comments', true);
		$xoopsTpl->assign('comments_title', $xpwiki->get_lang('page_comment_title'));
		include_once XOOPS_ROOT_PATH.'/include/comment_view.php';
	}
	*/

	
	//$xpwiki->catbody();

	$xoopsTpl->assign("xoops_pagetitle",$xpwiki->title);
	$xoopsTpl->assign("xoops_module_header", $xpwiki->root->html_header . $xoopsTpl->get_template_vars("xoops_module_header"));
	
	echo $xpwiki->html;
	
	// xoops footer
	include XOOPS_ROOT_PATH.'/footer.php';

} else if ($xpwiki->runmode == "standalone") {
	
	while( ob_get_level() ) {
		ob_end_clean() ;
	}
	echo $xpwiki->html;

}
exit;