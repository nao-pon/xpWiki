<?php
//
// Created on 2006/10/25 by nao-pon http://hypweb.net/
// $Id: loader.php,v 1.15 2007/09/06 09:09:39 nao-pon Exp $
//

error_reporting(0);

// 変数初期化
$src   = preg_replace("/[^\w.-]+/","",@ $_GET['src']);
$prefix = (isset($_GET['b']))? 'b_' : '';
$prefix = (isset($_GET['r']))? 'r_' : '';
$addcss = $dir = $out = $type = $src_file = '';
$root_path = dirname($skin_dirname);
$face_cache = $root_path . '/private/cache/facemarks.js';
$face_cache_time = @ filemtime($face_cache);

$js_replace = false;

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
		$dir = $prefix.basename($root_path);
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
		$dir = $prefix.basename($root_path);
		$src_file = $mydirname = $root_path . '/private/cache/' . $src . '.css';
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
if ($type === "js" && substr($src,0,7) === "default") {
	$js_replace = true;
	if (!file_exists($src_file)) {
		$src_file = dirname(__FILE__)."/skin/js/default.en.js";
	}
}

if (file_exists($src_file)) {
	$filetime = filemtime($src_file);
	$etag = md5($type.$dir.$src.$filetime.$addcss.$face_cache_time);
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
	
	// Ready gzip file?
	$gzips = array('prototype.js', 'effects.js', 'lightbox.js');
	$is_gz = false;
	if (in_array($src.'.'.$type, $gzips)
		&& strpos(strtolower($_SERVER['HTTP_ACCEPT_ENCODING']), 'gzip') !== false
		&& strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'safari') === false
	) {
		$src_file .= '.gz';
		$is_gz = true;
	} 
	
	$out = join("",file($src_file));
	if ($dir) {
		$out = str_replace('$dir', $dir, $out . "\n" . $addcss);
		$out = str_replace('$class', 'div.xpwiki_'.$dir, $out);
	}
	if ($type === 'js') {
		if ($src === 'main') {
			$js_replace = true;
			if (! file_exists($face_cache)) {
				include XOOPS_ROOT_PATH.'/include/common.php';
				list($face_tag, $face_tag_full) = xpwiki_make_facemarks ($skin_dirname, $face_cache);
			} else {
				list($face_tag, $face_tag_full) = array_pad(file($face_cache), 2, '');
				if (!$face_tag_full) $face_tag_full = $face_tag;
			}
			$out = str_replace(array('$face_tag_full', '$face_tag'), array($face_tag_full, $face_tag), $out);
		}
		if ($js_replace) {
			$xoops_root_path = XOOPS_ROOT_PATH;
			if ( substr(PHP_OS, 0, 3) === 'WIN' ) {
				$root_path = str_replace('\\', '/', $root_path);
				$xoops_root_path = str_replace('\\', '/', $xoops_root_path);
			}
			$out = str_replace('$wikihelper_root_url', str_replace($xoops_root_path, XOOPS_URL, $root_path) , $out);
		}
	}
	header( "Content-Type: " . $c_type );
	header( "Last-Modified: " . gmdate( "D, d M Y H:i:s", $filetime ) . " GMT" );
	header( "Etag: ". $etag );
	header( "Content-length: ".strlen($out) );
	if ($is_gz) {
		header( 'Content-Encoding: gzip' );
	}
} else {
	//$out = 'File not found.';
	header( "HTTP/1.1 404 Not Found" );
	//header( "Content-length: ".strlen($out) );
}
echo $out;
exit();

function xpwiki_make_facemarks ($skin_dirname, $cache) {
	//include XOOPS_ROOT_PATH.'/include/common.php';
	include_once XOOPS_TRUST_PATH."/modules/xpwiki/include.php";
	$wiki =& XpWiki::getSingleton( basename(dirname($skin_dirname)) );
	$wiki->init('#RenderMode');
	$tags_full = $tags = array();
	foreach($wiki->root->wikihelper_facemarks as $key => $img) {
		$key = htmlspecialchars($key, ENT_QUOTES);
		$q_key = str_replace("'", "\'", $key);
		if ($img{0} === '*') {
			$img = substr($img, 1);
			$tags_full[] = '\'<img src="'.$img.'" border="0" title="'.$key.'" alt="'.$key.'" onClick="javascript:wikihelper_face(\\\''.$q_key.'\\\');return false;" \'+\'/\'+\'>\'+';
			continue;
		}
		$tags[] = '\'<img src="'.$img.'" border="0" title="'.$key.'" alt="'.$key.'" onClick="javascript:wikihelper_face(\\\''.$q_key.'\\\');return false;" \'+\'/\'+\'>\'+';
		$tags_full[] = '\'<img src="'.$img.'" border="0" title="'.$key.'" alt="'.$key.'" onClick="javascript:wikihelper_face(\\\''.$q_key.'\\\');return false;" \'+\'/\'+\'>\'+';
	}
	$tags = array(join('', $tags) ,join('', $tags_full));
	if ($fp = fopen($cache, 'wb')) {
		fwrite($fp, join("\n", $tags));
		fclose($fp);
	}
	return $tags;
}
?>