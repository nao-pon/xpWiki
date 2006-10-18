<?php
//
// Created on 2006/10/16 by nao-pon http://hypweb.net/
// $Id: index.php,v 1.2 2006/10/18 05:05:59 nao-pon Exp $
//

$xoopsOption['nocommon'] = TRUE;
require '../../../../mainfile.php' ;
if( ! defined( 'XOOPS_TRUST_PATH' ) ) die( 'set XOOPS_TRUST_PATH into mainfile.php' ) ;

require '../../mytrustdirname.php' ; // set $mytrustdirname
require XOOPS_TRUST_PATH.'/modules/'.$mytrustdirname.'/javascript.php' ;

?>