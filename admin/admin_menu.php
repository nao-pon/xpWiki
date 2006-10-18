<?php

$constpref = '_MI_' . strtoupper( $mydirname ) ;

$adminmenu[1]['title'] = constant( $constpref.'_PLUGIN_CONVERTER' ) ;
$adminmenu[1]['link']  = "admin/index.php?page=plugin_conv" ;

$adminmenu[2]['title'] = constant( $constpref.'_SKIN_CONVERTER' ) ;
$adminmenu[2]['link']  = "admin/index.php?page=skin_conv" ;

?>