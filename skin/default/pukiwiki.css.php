<?php
$xoopsOption['nocommon'] = true;
require '../../../../mainfile.php' ;
if( ! defined( 'XOOPS_TRUST_PATH' ) ) die( 'set XOOPS_TRUST_PATH in mainfile.php' ) ;

require '../../mytrustdirname.php' ; // set $mytrustdirname
include XOOPS_TRUST_PATH.'/modules/'.$mytrustdirname.'/skin/'.basename(__FILE__); 
?>