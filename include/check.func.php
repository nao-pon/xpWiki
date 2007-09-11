<?php
//
// Created on 2006/11/07 by nao-pon http://hypweb.net/
// $Id: check.func.php,v 1.9 2007/09/11 06:03:53 nao-pon Exp $
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
function xpwikifunc_defdata_check ($mydirname, $mode = 'install') {
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
	
	$from_base = dirname(dirname(__FILE__)).'/InitialData/'.$lang.'/';
	$timestamp = array();
	
	foreach(file($from_base.'wiki/.timestamp') as $line) {
		list($file, $time) = explode("\t", $line);
		$timestamp[$file] = intval(trim($time)) - 32400 + date('Z');
	}

	foreach ($dirs as $from=>$to) {
		$dir = $from;
		$from = $from_base.$from;
		$to   = XOOPS_ROOT_PATH.'/modules/'.$mydirname.'/'.$to;
		
		if ($handle = opendir($from)) {
			while (false !== ($file = readdir($handle))) {
				if ($file !== '.' && $file !== '..' && ! is_dir($from.'/'.$file)) {
					if ($mode === 'install' || $dir !== 'wiki' || substr($file, -4) !== '.txt') {
						if (! file_exists($to.'/'.$file)) {
							copy($from.'/'.$file, $to.'/'.$file);
							if ($utf8from) {
								xpwikifunc_conv_utf($to.'/'.$file, $utf8from);
							}
							if ($dir === 'wiki' && isset($timestamp[$file])) {
								touch($to.'/'.$file, $timestamp[$file]);
							}
							$msg[] = "Copied a file '{$file}'.<br />";
						}
					} else {
						// wiki¥Ú¡¼¥¸
						if (! file_exists($to.'/'.$file) || filemtime($to.'/'.$file) < $timestamp[$file]) {
							if (! isset($xpwiki)) {
								include_once dirname(dirname(__FILE__)) . '/include.php';
								$xpwiki = new XpWiki($mydirname);
								$xpwiki->init('#RenderMode');
							}
							$src = join('', file($from.'/'.$file));
							if ($utf8from) {
								$src = mb_convert_encoding($src, 'UTF-8', $utf8from);
							}
							$name = $xpwiki->func->decode(str_replace('.txt', '', $file));
							$xpwiki->func->page_write($name, $src);
						}
					}
				}
			}
			closedir($handle);
		}
	}
	
	if (isset($xpwiki)) { $xpwiki = null; }
	
	// Remove facemarks.js
	@ unlink(XOOPS_ROOT_PATH.'/modules/'.$mydirname.'/private/cache/facemarks.js');
	
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