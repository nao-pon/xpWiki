<?php

if( defined( 'FOR_XOOPS_LANG_CHECKER' ) ) $mydirname = 'wraps' ;
$constpref = '_MI_' . strtoupper( $mydirname ) ;

if( defined( 'FOR_XOOPS_LANG_CHECKER' ) || ! defined( $constpref.'_LOADED' ) ) {

// a flag for this language file has already been read or not.
define( $constpref.'_LOADED' , 1 ) ;

// Names of blocks for this module (Not all module has blocks)
define( $constpref."_BNAME_A_PAGE","Show page  ({$mydirname})");
define( $constpref."_BDESC_A_PAGE","The content can be displayed in the block by specifying page name.");

define( $constpref.'_MODULE_DESCRIPTION' , 'A wiki module based on PukiWiki.' ) ;

define( $constpref.'_PLUGIN_CONVERTER' , 'Plugin Converter' ) ;
define( $constpref.'_SKIN_CONVERTER' , 'Skin Converter' ) ;
define( $constpref.'_ADMIN_TOOLS' , 'Admin Tools' ) ;

define( $constpref.'_COM_DIRNAME','Comment-integration: dirname of d3forum');
define( $constpref.'_COM_FORUM_ID','Comment-integration: forum ID');
}


?>