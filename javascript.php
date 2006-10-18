<?php
//
// Created on 2006/10/16 by nao-pon http://hypweb.net/
// $Id: javascript.php,v 1.1 2006/10/18 03:02:08 nao-pon Exp $
//

$src = (@$_GET['src'])?
	dirname(__FILE__)."/js/".preg_replace("/[^\w.]/","",$_GET['src']).".js"
	 : "";

// default.LANG
if (substr($_GET['src'],0,7) == "default") {
	if (!file_exists($src)) {
		$src = dirname(__FILE__)."/js/default.en.js";
	}
}

if ($src && file_exists($src)) {
	$filetime = filemtime($src);
	$etag = md5($_GET['src'].$filetime);
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
	$out = join("",file($src));
}

header( "Content-Type: application/x-javascript" );
header( "Last-Modified: " . gmdate( "D, d M Y H:i:s", $filetime ) . " GMT" );
header( "Etag: ". $etag );
header( "Content-length: ".strlen($out) );
echo $out;
exit();

?>