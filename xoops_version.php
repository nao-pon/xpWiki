<?php

// language file (modinfo.php)
if( file_exists( dirname(__FILE__).'/language/'.@$xoopsConfig['language'].'/modinfo.php' ) ) {
	include dirname(__FILE__).'/language/'.@$xoopsConfig['language'].'/modinfo.php' ;
} else if( file_exists( dirname(__FILE__).'/language/english/modinfo.php' ) ) {
	include dirname(__FILE__).'/language/english/modinfo.php' ;
}
$constpref = '_MI_' . strtoupper( $mydirname ) ;


$modversion['name'] = $mydirname ;
$modversion['version'] = 0.1 ;
$modversion['description'] = constant($constpref.'_MODULE_DESCRIPTION') ;
$modversion['credits'] = "hypweb.net";
$modversion['author'] = "nao-pon" ;
$modversion['help'] = "" ;
$modversion['license'] = "GPL" ;
$modversion['official'] = 0 ;
$modversion['image'] = 'module_icon.php' ;
$modversion['dirname'] = $mydirname ;

// Any tables can't be touched by modulesadmin.
$modversion['sqlfile'] = false ;
$modversion['tables'] = array() ;

// Admin things
$modversion['hasAdmin'] = 1 ;
$modversion['adminindex'] = 'index.php?mode=admin' ;
$modversion['adminmenu'] = 'admin_menu.php' ;

// Search
$modversion['hasSearch'] = 0 ;
$modversion['search']['file'] = 'search.php' ;
$modversion['search']['func'] = $mydirname.'_global_search' ;

// Menu
$modversion['hasMain'] = 1 ;

// There are no submenu (use menu moudle instead of mainmenu)
$modversion['sub'] = array() ;

// All Templates can't be touched by modulesadmin.
$modversion['templates'] = array() ;

// Blocks
$modversion['blocks'] = array() ;

// Comments
$modversion['hasComments'] = 0 ;

// Configs
$modversion['config'] = array() ;

// Notification
$modversion['hasNotification'] = 0 ;

$modversion['onInstall'] = 'oninstall.php' ;
$modversion['onUpdate'] = 'onupdate.php' ;
$modversion['onUninstall'] = 'onuninstall.php' ;


?>