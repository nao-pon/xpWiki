<?php
//
// Created on 2006/10/25 by nao-pon http://hypweb.net/
// $Id: loader.php,v 1.5 2006/12/18 14:27:01 nao-pon Exp $
//

// 変数初期化
//$type  = preg_replace("/[^\w.-]+/","",@ $_GET['type']);
$src   = preg_replace("/[^\w.-]+/","",@ $_GET['src']);
$block = (isset($_GET['b']))? 'b_' : '';
$addcss = $dir = $out = $type = $src_file = '';

if (preg_match("/^(.+)\.([^.]+)$/",$src,$match)) {
	$type = $match[2];
	$src = $match[1];
	if (substr($src, -5) === '.page') {
		$type = 'pagecss';
		$src = substr($src, 0, strlen($src) - 5);
	}
}

if (!$type || !$src) { exit(); }

$basedir = ($type === "png" || $type === "gif")? "image/" : "";

if (file_exists("{$skin_dirname}/{$basedir}{$type}/{$src}.{$type}")) {
	if ($type !== 'css') {
		// html側に指定ファイルがあれば、それにリダイレクト
		header("Location: {$basedir}{$type}/{$src}.{$type}");
		exit();
	} else {
		// CSS は上書き
		$addcss = join('', file("{$skin_dirname}/{$basedir}{$type}/{$src}.{$type}"));
	}
}

switch ($type) {
	case 'css':
		$c_type = 'text/css';
		$dir = $block.basename(dirname($skin_dirname));
		break;
	case 'js':
		$c_type = 'application/x-javascript';
		break;
	case 'png':
		$c_type = 'image/png';
		break;
	case 'gif':
		$c_type = 'image/gif';
		break;
	case 'pagecss':
		$c_type = 'text/css';
		$dir = $block.basename(dirname($skin_dirname));
		$src_file = $mydirname = dirname($skin_dirname) . '/private/cache/' . $src . '.css';
		break;
	case 'xml':
		$c_type = 'application/xml; charset=utf-8';
		break;
	default:
		exit();
}

if (!$src_file)
	$src_file = dirname(__FILE__)."/skin/{$basedir}{$type}/".preg_replace("/[^\w.]/","",$src).".$type";

// default.LANG
if ($type === "js" && substr($src,0,7) == "default") {
	if (!file_exists($src_file)) {
		$src = dirname(__FILE__)."/js/default.en.js";
	}
}

if (file_exists($src_file)) {
	$filetime = filemtime($src_file);
	$etag = md5($type.$dir.$src.$filetime.$addcss);
	/*
	$notmod = ($etag == @$_SERVER["HTTP_IF_NONE_MATCH"]);
	if ($notmod || isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
		if ($notmod || @strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) >= $filetime) {
			header( "HTTP/1.1 304 Not Modified" );
			header( "Etag: ". $etag );
			exit();
		}
	}
	*/
	if ($etag == @$_SERVER["HTTP_IF_NONE_MATCH"]) {
		header( "HTTP/1.1 304 Not Modified" );
		header( "Etag: ". $etag );
		exit();
	}
	$out = join("",file($src_file));
	if ($dir) {
		$out = str_replace('$dir', $dir, $out . "\n" . $addcss);
	}
	header( "Content-Type: " . $c_type );
	header( "Last-Modified: " . gmdate( "D, d M Y H:i:s", $filetime ) . " GMT" );
	header( "Etag: ". $etag );
	header( "Content-length: ".strlen($out) );
} else {
	//$out = 'File not found.';
	header( "HTTP/1.1 404 Not Found" );
	//header( "Content-length: ".strlen($out) );
}
echo $out;
exit();
?>