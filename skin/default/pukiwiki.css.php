<?php
define('_LEGACY_PREVENT_LOAD_CORE_', TRUE); // for XOOPS Cube Legacy
$xoopsOption['nocommon'] = true;
require '../../../../mainfile.php' ;
if( ! defined( 'XOOPS_TRUST_PATH' ) ) die( 'set XOOPS_TRUST_PATH in mainfile.php' ) ;

require '../../mytrustdirname.php' ; // set $mytrustdirname

// Base
$base   = isset($_GET['base'])   ? "_".preg_replace("/[^\w-]+/","",$_GET['base'])    : '';
$class = "div.xpwiki".$base;

$overwrite = <<<EOD
/* Here is an overwriting section.
 * Please use $class in selector. */

EOD;

include XOOPS_TRUST_PATH.'/modules/'.$mytrustdirname.'/skin/'.basename(__FILE__); 
?>