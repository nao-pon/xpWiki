<?php
//
// Created on 2006/10/25 by nao-pon http://hypweb.net/
// $Id: loader.php,v 1.1 2006/10/27 11:42:18 nao-pon Exp $
//

// 変数初期化
$type = preg_replace("/[^\w.-]+/","",@ $_GET['type']);
$src  = preg_replace("/[^\w.-]+/","",@ $_GET['src']);

if (!$type || !$src) { exit(); }

$basedir = ($type === "png" || $type === "gif")? "image/" : "";

// html側に指定ファイルがあれば、それにリダイレクト
if (file_exists("{$skin_dirname}/{$basedir}{$type}/{$src}.{$type}")) {
	header("Location: {$basedir}{$type}/{$src}.{$type}");
	exit();
}

switch ($type) {
	case "css":
		$c_type = "text/css";
		break;
	case "js":
		$c_type = "application/x-javascript";
		break;
	case "png":
		$c_type = "image/png";
		break;
	case "gif":
		$c_type = "image/gif";
		break;
	default:
		exit();
}

$src_file = dirname(__FILE__)."/skin/{$basedir}{$type}/".preg_replace("/[^\w.]/","",$src).".$type";

// default.LANG
if ($type === "js" && substr($src,0,7) == "default") {
	if (!file_exists($src_file)) {
		$src = dirname(__FILE__)."/js/default.en.js";
	}
}

if (file_exists($src_file)) {
	$filetime = filemtime($src_file);
	$etag = md5($type.$src.$filetime);
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
}

header( "Content-Type: " . $c_type );
header( "Last-Modified: " . gmdate( "D, d M Y H:i:s", $filetime ) . " GMT" );
header( "Etag: ". $etag );
header( "Content-length: ".strlen($out) );
echo $out;
exit();

?>