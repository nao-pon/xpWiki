<?php

if( defined( 'FOR_XOOPS_LANG_CHECKER' ) ) $mydirname = 'xpwiki' ;
$constpref = '_MI_' . strtoupper( $mydirname ) ;

if( defined( 'FOR_XOOPS_LANG_CHECKER' ) || ! defined( $constpref.'_LOADED' ) ) {

// a flag for this language file has already been read or not.
define( $constpref.'_LOADED' , 1 ) ;

define( $constpref.'_MODULE_DESCRIPTION' , 'PukiWikiベースのWikiモジュール' ) ;

define( $constpref.'_PLUGIN_CONVERTER' , 'プラグイン変換ツール' ) ;
define( $constpref.'_SKIN_CONVERTER' , 'スキン変換ツール' ) ;

}


?>