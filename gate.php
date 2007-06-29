<?php
/*
 * Created on 2007/06/29 by nao-pon http://hypweb.net/
 * $Id: gate.php,v 1.1 2007/06/29 08:44:45 nao-pon Exp $
 */

/*
 * $mydirname      : Module dirname
 * $mydirpath      : Module dirpath
 * $mytrustdirname : Trust dirname (xpwiki)
 */

$xwGateOption['nocommonAllowWays'] = array();
$xwGateOption['nodosAllowWays'] = array();
$xwGateOption['noumbAllowWays'] = array();

$mytrustdirpath = dirname( __FILE__ ) ;

$way = (isset($_GET['way']))? $_GET['way'] : ((isset($_POST['way']))? $_POST['way'] : '');
$way = preg_replace( '/[^a-zA-Z0-9_-]/' , '' , $way);

if ($xwGateOption['xmode']) {
	if (!in_array($way, $xwGateOption['nocommonAllowWays'])) goOut('Bad request.');
}

if ($xwGateOption['nodos']) {
	if (!in_array($way, $xwGateOption['nodosAllowWays'])) goOut('Bad request.');
}

if ($xwGateOption['noumb']) {
	if (!in_array($way, $xwGateOption['noumbAllowWays'])) goOut('Bad request.');
}

$file_php = $mytrustdirpath . '/ways/' . $way . '.php';
if (file_exists($file_php)) {
	include $file_php;
} else {
	goOut('File not found.');
}

function goOut($str = '') {
	error_reporting(0);
	while( ob_get_level() ) {
		ob_end_clean() ;
	}
	header("HTTP/1.0 404 Not Found");
	exit($str);
}
?>