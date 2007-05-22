<?php
//
// Created on 2006/11/07 by nao-pon http://hypweb.net/
// $Id: check.func.php,v 1.4 2007/05/22 02:32:54 nao-pon Exp $
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
		array_unshift($msg, "<span style=\"color:#ff0000;\">Error: Could not write a file in next directories. Please check permission and retry.</span><br />");
	}
	
	return $msg;
}

// when onInstall & onUpdate
function xpwikifunc_defdata_check ($mydirname) {
	$msg = array();
	
	$config_handler =& xoops_gethandler('config');
	$xoopsConfig =& $config_handler->getConfigsByCat(XOOPS_CONF);
	$language = $xoopsConfig['language'];
	$utf8from = '';
	
	switch (strtolower($language)) {
		case 'japanese' :
		case 'japaneseutf' :
		case 'ja_utf8' :
		case 'japanese_utf8' :
			$lang = 'ja';
			if ('utf-8' === strtolower(_CHARSET)) {
				$utf8from = 'EUC-JP';
			}
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
				if ($file !== '.' && $file !== '..' && ! is_dir($from.'/'.$file)) {
					if (! file_exists($to.'/'.$file)) {
						copy($from.'/'.$file, $to.'/'.$file);
						if ($utf8from) {
							xpwikifunc_conv_utf($to.'/'.$file, $utf8from);
						}
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

function xpwikifunc_conv_utf($file, $utf8from) {
	$dat = join('', file($file));
	$dat = mb_convert_encoding($dat, 'UTF-8', $utf8from);
	if ($fp = fopen($file, 'wb')) {
		fwrite($fp, $dat);
		fclose($fp);
	}
	return ;
}
?>