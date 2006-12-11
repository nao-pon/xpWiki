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
	
	// xoops header
	include XOOPS_ROOT_PATH.'/header.php';
	
	$xoopsTpl->assign("xoops_pagetitle",$xpwiki->root->pagetitle);
	$xoopsTpl->assign("xoops_module_header", $xpwiki->root->html_header . $xoopsTpl->get_template_vars("xoops_module_header"));
	
	echo $xpwiki->html;

	// xoops footer
	include XOOPS_ROOT_PATH.'/footer.php';

} else if ($xpwiki->runmode == "xoops_admin") {

	$xoopsOption['pagetype'] = 'admin' ;
	require XOOPS_ROOT_PATH.'/include/cp_functions.php' ;
	
	// language files
	$mydirpath = $xpwiki->root->mydirpath;
	$mytrustdirpath = $xpwiki->root->mytrustdirpath ;
	$language = empty( $xoopsConfig['language'] ) ? 'english' : $xoopsConfig['language'] ;
	if( file_exists( "$mydirpath/language/$language/admin.php" ) ) {
		// user customized language file
		include_once "$mydirpath/language/$language/admin.php" ;
	} else if( file_exists( "$mytrustdirpath/language/$language/admin.php" ) ) {
		// default language file
		include_once "$mytrustdirpath/language/$language/admin.php" ;
	} else {
		// fallback english
		include_once "$mytrustdirpath/language/english/admin.php" ;
	}

	// xoops admin header
	xoops_cp_header() ;

	// mymenu
	//$mymenu_fake_uri = '' ;
	include dirname(__FILE__).'/admin/mymenu.php' ;

	// Decide charset for CSS
	$css_charset = 'iso-8859-1';
	switch($xpwiki->cont['UI_LANG']){
		case 'ja': $css_charset = 'Shift_JIS'; break;
	}
	$dirname = $xpwiki->root->mydirname;
	// Head Tags
	list($head_pre_tag, $head_tag) = $xpwiki->func->get_additional_headtags($xpwiki->func);
	
	echo <<<EOD
$head_pre_tag
<link rel="stylesheet" type="text/css" media="screen" href="{$xpwiki->cont['HOME_URL']}{$xpwiki->cont['SKIN_DIR']}pukiwiki.css.php?charset={$css_charset}&amp;base={$dirname}" charset="{$css_charset}" />	
$head_tag
EOD;
	
	echo $xpwiki->html;
	
	// xoops admin footer
	xoops_cp_footer() ;	

} else if ($xpwiki->runmode == "standalone") {
	
	while( ob_get_level() ) {
		ob_end_clean() ;
	}
	echo $xpwiki->html;

}

exit;