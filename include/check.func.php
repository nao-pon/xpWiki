<?php
//
// Created on 2006/11/07 by nao-pon http://hypweb.net/
// $Id: check.func.php,v 1.1 2006/11/07 07:02:49 nao-pon Exp $
//

// when onInstall & onUpdate
function xpwikifunc_permission_check ($mydirname) {
	$msg = array();
	
	$dirs = array(
		'attach',
		'attach/s',
		'private/backup',
		'private/cache',
		'private/cache/page',
		'private/cache/plugin',
		'private/counter',
		'private/diff',
		'private/trackback',
		'private/wiki'
	);
	
	foreach($dirs as $dir) {
		$dir = XOOPS_ROOT_PATH.'/modules/'.$mydirname.'/'.$dir;
		$checkfile = $dir.'/.check';
		if (@touch($checkfile)) {
			unlink($checkfile);
		} else {
			$msg[] = " - {$dir}<br />";
		}
	}
	
	if ($msg) {
		array_unshift($msg, "<span style=\"color:#ff0000;\">Warning: Could not write a file in next directories. Please check permission and do module update.</span><br />");
	}
	
	return $msg;
}

// when onInstall & onUpdate
function xpwikifunc_defdata_check ($mydirname) {
	$msg = array();
	
	$config_handler =& xoops_gethandler('config');
	$xoopsConfig =& $config_handler->getConfigsByCat(XOOPS_CONF);
	$language = $xoopsConfig['language'];
	
	switch (strtolower($language)) {
		case 'japanese' :
			$lang = 'ja';
			break;
		case 'english' :
			$lang = 'en';
			break;
		default:
			$lang = 'en';
	}

	$dirs = array(
		'cache' => 'private/cache',
		'wiki'  => 'private/wiki'
	);

	foreach ($dirs as $from=>$to) {
		$from = dirname(dirname(__FILE__)).'/InitialData/'.$lang.'/'.$from;
		$to   = XOOPS_ROOT_PATH.'/modules/'.$mydirname.'/'.$to;

		if ($handle = opendir($from)) {
			while (false !== ($file = readdir($handle))) {
				if ($file !== '.' && $file !== '..') {
					if (! file_exists($to.'/'.$file)) {
						copy($from.'/'.$file, $to.'/'.$file);
						touch($to.'/'.$file, filemtime($from.'/'.$file));
						$msg[] = "Copied a file '{$file}'.<br />";
					}
				}
			}
			closedir($handle);
		}
	}
	
	return $msg;
}
?>