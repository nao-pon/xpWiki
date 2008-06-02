<?php
//
// Created on 2006/10/25 by nao-pon http://hypweb.net/
// $Id: loader.php,v 1.47 2008/06/02 07:14:55 nao-pon Exp $
//

ignore_user_abort(FALSE);
error_reporting(0);

// ブラウザキャッシュ有効時間(秒)
$maxage = 86400; // 60*60*24 (1day)

// スマイリーキャッシュ有効時間(秒)
$face_tag_maxage = 86400; // 60*60*24 (1day)

// clear output buffer
while( ob_get_level() ) {
	ob_end_clean() ;
}

// 変数初期化
$src   = preg_replace("/[^\w.\-%]+/","",@ $_GET['src']);
$prefix = (isset($_GET['b']))? 'b_' : '';
$prefix = (isset($_GET['r']))? 'r_' : $prefix;
$nocache = (isset($_GET['nc']));
$js_lang = $charset = $pre_width = $cache_file = $gzip_fname = $dir = $out = $type = $src_file = '';
$addcss = array();
$length = $addcsstime = $facetagtime = 0;
$face_remake = $js_replace = $replace = false;
$root_path = dirname($skin_dirname);
$cache_path = $root_path.'/private/cache/';
$face_tag_ver = 1.0;
$method = empty($_SERVER['REQUEST_METHOD'])? 'GET' : strtoupper($_SERVER['REQUEST_METHOD']);
$pre_id = '';

if ($src === 'favicon') {
	require XOOPS_TRUST_PATH.'/class/hyp_common/favicon/favicon.php';
	exit();
}

if (preg_match("/^(.+)\.([^.]+)$/",$src,$match)) {
	$type = $match[2];
	$src = $match[1];
	if (substr($src, -5) === '.page') {
		$type = 'pagecss';
		$src = substr($src, 0, strlen($src) - 5);
	}
	if (substr($src, -7) === '.pcache') {
		//$src = substr($src, 0, strlen($src) - 7);
		$src_file = $cache_path . 'plugin/' . $src . '.' . $type;
	}
}

if (!$type || !$src) {
	header( 'HTTP/1.1 404 Not Found' );
	exit();
}

$basedir = ($type === "png" || $type === "gif")? "image/" : "";

// CSS 以外は html側に指定ファイルがあれば、それにリダイレクト
if ($type !== 'css') {
	if (file_exists("{$skin_dirname}/{$basedir}{$type}/{$src}.{$type}")) {
		header("Location: {$basedir}{$type}/{$src}.{$type}");
		exit();
	}
}

switch ($type) {
	case 'css':
		$c_type = 'text/css';
		
		$pre_id = preg_replace('/[^\w_\-#]+/', '', @ $_GET['pre']);
		
		// Skin dir
		$skin = isset($_GET['skin']) ? preg_replace('/[^\w.-]+/','',$_GET['skin'])  : 'default';
		if (!$skin) $skin = 'default';

		$_is_tdiary = (substr($skin, 0, 3) === 'tD-');

		$dir = $prefix.basename($root_path);
		// Default CSS
		if ($src === 'main') {
			// Default charset
			if (isset($_GET['charset'])) $charset = preg_replace('/[^\w.-]+/','',$_GET['charset']);
			$c_type = 'text/css' . ($charset ? '; charset=' . $charset : '');
			// tDiary
			if ($_is_tdiary) {
				$src .= '_tdiary';
			}
			// Media
			$media = isset($_GET['media'])? $_GET['media'] : '';
			$media = ($media === 'print')? '_print' : '';
			$src .= $media;
			// Pre Width
			$pre_width = (isset($_GET['pw']) && preg_match('/^([0-9]{2,4}(px|%)|auto)$/',$_GET['pw']))? $_GET['pw'] : 'auto';
		}
		
		// tDiary's Skin
		if ($_is_tdiary) {
			$skin = 'tdiary_theme';
		}
		
		// CSS over write (css dir)
		$addcss_file = "{$skin_dirname}/{$basedir}css/{$src}.css";
		if (file_exists($addcss_file)) {
			$addcss[] = $addcss_file;
			$addcsstime = filemtime($addcss_file);
		}
		// CSS over write (skin dir)
		$addcss_file = "{$skin_dirname}/{$basedir}{$skin}/{$src}.css";
		if (file_exists($addcss_file)) {
			$addcss[] = $addcss_file;
			$addcsstime = max($addcsstime, filemtime($addcss_file));
		}
		if ($prefix) {
			$css_src = ($prefix === 'b_') ? $src . '_block' : $src . '_render';
			// CSS over write (css dir)
			$addcss_file = "{$skin_dirname}/{$basedir}css/{$css_src}.css";
			if (file_exists($addcss_file)) {
				$addcss[] = $addcss_file;
				$addcsstime = max($addcsstime, filemtime($addcss_file));
			}
			// CSS over write (skin dir)
			$addcss_file = "{$skin_dirname}/{$basedir}{$skin}/{$css_src}.css";
			if (file_exists($addcss_file)) {
				$addcss[] = $addcss_file;
				$addcsstime = max($addcsstime, filemtime($addcss_file));
			}
		}
		
		$replace = true;
		$cache_file = $cache_path.$skin.'_'.$src.'_'.$dir.($pre_width?'_'.$pre_width:'').($charset?'_'.$charset:'').'.'.$type;
		$gzip_fname = $cache_file.'.gz';
		break;
	case 'js':
		$module_url = XOOPS_URL.'/'.basename(dirname($root_path));
		$wikihelper_root_url = $module_url . '/' . basename($root_path);
		$wikihelper_root_url_md5 = md5($wikihelper_root_url);
		$face_cache = $cache_path . $wikihelper_root_url_md5 .'_facemarks.js';
		if (substr($src, 0, 7) === "default") {
			$js_replace = true;
			$replace = true;
			$js_lang = substr($src, 8);
			$src_file = $root_path . '/language/xpwiki/' . $js_lang . '/' . 'default.js';
			// Check Trust
			if (! file_exists($src_file)) {
				$src_file = dirname(__FILE__) . '/language/xpwiki/' . $js_lang . '/' . 'default.js';
			}
			// none
			if (! file_exists($src_file)) {
				$src_file = dirname(__FILE__) . '/language/xpwiki/en/default.js';
			}
		} else 	if ($src === 'main') {
			$face_remake = (!file_exists($face_cache) || filemtime($face_cache) + $face_tag_maxage < time());
			if ($face_remake) {
				$facetagtime = time();
			} else {
				$facetagtime = filemtime($face_cache);
			}
			$replace = true;
			$js_replace = true;
		} else if ($src === 'wikihelper_loader') {
			$replace = true;
			$js_replace = true;			
		}
		$c_type = 'application/x-javascript';
		$cache_file = $cache_path . $src . ($js_replace? '_' . $wikihelper_root_url_md5 : '') . '.' . $type;
		$gzip_fname = $cache_file . '.gz';
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
		$src_file = $root_path . '/private/cache/' . $src . '.css';
		$replace = true;
		$cache_file = $cache_path.$src.'_'.$dir.'.'.$type;
		$gzip_fname = $cache_file.'.gz';
		break;
	case 'xml':
		$c_type = 'application/xml; charset=utf-8';
		break;
	case 'html':
		$charset = strtolower(preg_replace("/[^\w_\-]+/","",@ $_GET['charset']));
		$c_type = 'text/html; charset=' . $charset;
		break;
	default:
		exit();
}

if (!$src_file) {
	$src_file = dirname(__FILE__)."/skin/{$basedir}{$type}/".preg_replace("/[^\w.]/","",$src).".$type";
}

if (file_exists($src_file)) {
	$filetime = max(filemtime(__FILE__), filemtime($src_file), $addcsstime, $facetagtime);

	$etag = md5($type.$dir.$pre_width.$charset.$src.$filetime.$pre_id);
		
	// ブラウザのキャッシュをチェック
	if ($etag == @$_SERVER['HTTP_IF_NONE_MATCH']) {
		header( 'HTTP/1.1 304 Not Modified' );
		if ($nocache) {
			header( 'Expires: Thu, 01 Dec 1994 16:00:00 GMT' );
			header( 'Cache-Control: no-cache, must-revalidate' );
			header( 'Cache-Control: post-check=0, pre-check=0', false );
			header( 'Pragma: no-cache' );
		} else {
			header( 'Cache-Control: public, max-age=' . $maxage );
		}
		header( 'Etag: '. $etag );
		exit();
	}

	// gzip 受け入れ不可能?
	if (! preg_match('/\b(gzip)\b/i', $_SERVER['HTTP_ACCEPT_ENCODING'])
		|| strpos(strtolower(@ $_SERVER['HTTP_USER_AGENT']), 'safari') !== false
	) {
		$gzip_fname = '';
	}
	
	// キャッシュ判定
	if ($gzip_fname && file_exists($gzip_fname) && filemtime($gzip_fname) >= $filetime) {
		// html側/private/cache に 有効な gzip ファイルがある場合
		header( 'Content-Type: ' . $c_type );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $filetime ) . ' GMT' );
		header( 'Cache-Control: max-age=' . $maxage );
		header( 'Etag: '. $etag );
		header( 'Content-length: '.filesize($gzip_fname) );
		header( 'Content-Encoding: gzip' );
		header( 'Vary: Accept-Encoding' );
		
		if ($method !== 'HEAD') readfile($gzip_fname);
		exit();
	} else if ($replace && file_exists($cache_file) && filemtime($cache_file) >= $filetime) {
		// html側/private/cache に 有効なキャッシュファイルがある場合
		header( 'Content-Type: ' . $c_type );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $filetime ) . ' GMT' );
		header( 'Cache-Control: max-age=' . $maxage );
		header( 'Etag: '. $etag );
		header( 'Content-length: '.filesize($cache_file) );
		
		if ($method !== 'HEAD') readfile($cache_file);
		exit();
	}
	
	// 置換処理が必要?
	if ($replace) {
		if ($type === 'css' || $type === 'pagecss') {
			$replace_src = 0;
			
			if ($type === 'css') {
				$conf_file = "{$skin_dirname}/{$basedir}{$skin}/css.conf";
				if (file_exists($conf_file)) {
					$conf = parse_ini_file($conf_file, true);
					if (! empty($conf[$src]['replace'])) {
						$replace_src = 1;
						$src_file = "{$skin_dirname}/{$basedir}{$skin}/{$src}.css";
					}
				}
			}
			
			$out = file_get_contents($src_file);
			
			if ($type === 'pagecss') {
				xpwiki_pagecss_filter($out);
			}
			
			if ($pre_id) $pre_id .= ' ';
			$addcss_src = '';
			if (! $replace_src && $addcss) {
				foreach ($addcss as $_file) {
					$addcss_src .= file_get_contents($_file) . "\n";
				}
			}
			$out = str_replace(array('$dir', '$class', '$pre_width', '$charset'),
								array($dir, $pre_id.'div.xpwiki_'.$dir, $pre_width, $charset),
								$out . "\n" . $addcss_src);
		}
		if ($type === 'js') {
			$out = file_get_contents($src_file);
			if ($src === 'main') {
				chdir($root_path);
				include_once XOOPS_ROOT_PATH.'/include/common.php';
				chdir($skin_dirname);
				include_once dirname( __FILE__ ) . '/include.php';
				$xpwiki = new XpWiki(basename($root_path));
				$xpwiki->init('#RenderMode');
				$encode_hint = $xpwiki->cont['PKWK_ENCODING_HINT'];
				if (!$face_remake) {
					list($face_tag, $face_tag_full, $_face_tag_ver) = array_pad(file($face_cache), 3, '');
					if (!$face_tag_full) $face_tag_full = $face_tag;
					if ($_face_tag_ver < $face_tag_ver) {
						$face_remake = true;
					}
				}
				if ($face_remake) {
					list($face_tag, $face_tag_full) = xpwiki_make_facemarks ($skin_dirname, $face_cache, $face_tag_ver);
				}
				$out = str_replace(
					array('$face_tag_full', '$face_tag', '$module_url', '$encode_hint', '$charset'),
					array($face_tag_full, $face_tag, $module_url, $encode_hint, $xpwiki->cont['SOURCE_ENCODING']),
				$out);
			}
			if ($js_replace) {
				$out = str_replace('$wikihelper_root_url', $wikihelper_root_url, $out);
			}
		}
		$length = strlen($out);
		
		// 置換処理した場合は、通常の形式でもキャッシュする
		if ($fp = fopen($cache_file, 'wb')) {
			fwrite($fp, $out);
			fclose($fp);
			touch($cache_file, $filetime);
		}
	}

	// html側/private/cache に gzip 圧縮してキャッシュする
	$is_gz = false;
	if ($gzip_fname && extension_loaded('zlib')) {
		if (!$replace) {
			$out = file_get_contents($src_file);
		}
		if ($gzip_out = gzencode($out)) {
			if ($fp = fopen($gzip_fname, 'wb')) {
				fwrite($fp, $gzip_out);
				fclose($fp);
				touch($gzip_fname, $filetime);
				$is_gz = true;
				$replace = true;
				$out = $gzip_out;
				$length = strlen($out);
			}
		}
	}
	
	if (!$length) { $length = filesize($src_file); }
	
	header( 'Content-Type: ' . $c_type );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $filetime ) . ' GMT' );
	if ($nocache) {
		header( 'Expires: Thu, 01 Dec 1994 16:00:00 GMT' );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
	} else {
		header( 'Cache-Control: public, max-age=' . $maxage );
	}
	header( 'Etag: '. $etag );
	header( 'Content-length: '.$length );
	if ($is_gz) {
		header( 'Content-Encoding: gzip' );
		header( 'Vary: Accept-Encoding' );
	}
	
	if ($method !== 'HEAD') {
		if ($replace) {
			echo $out;
		} else {
			readfile($src_file);
		}
	}
	exit();
} else {
	header( 'HTTP/1.1 404 Not Found' );
	exit();
}

function xpwiki_make_facemarks ($skin_dirname, $cache, $face_tag_ver) {
	include_once XOOPS_TRUST_PATH."/modules/xpwiki/include.php";
	$wiki =& XpWiki::getInitedSingleton( basename(dirname($skin_dirname)) );
	$tags_full = $tags = array();
	foreach($wiki->root->wikihelper_facemarks as $key => $img) {
		$key = htmlspecialchars($key, ENT_QUOTES);
		$q_key = str_replace("'", "\'", $key);
		if ($img{0} === '*') {
			$img = substr($img, 1);
			$tags_full[] = '\'<img src="'.$img.'" border="0" title="'.$key.'" alt="'.$key.'" onClick="javascript:wikihelper_face(\\\''.$q_key.'\\\');return false;" />\'';
			continue;
		}
		$tags[] = '\'<img src="'.$img.'" border="0" title="'.$key.'" alt="'.$key.'" onClick="javascript:wikihelper_face(\\\''.$q_key.'\\\');return false;" />\'';
		$tags_full[] = '\'<img src="'.$img.'" border="0" title="'.$key.'" alt="'.$key.'" onClick="javascript:wikihelper_face(\\\''.$q_key.'\\\');return false;" />\'';
	}
	$tags = array(join('+', $tags) ,join('+', $tags_full), $face_tag_ver);
	if ($fp = fopen($cache, 'wb')) {
		fwrite($fp, join("\n", $tags));
		fclose($fp);
	}
	return $tags;
}

function xpwiki_pagecss_filter (& $css, $chrctor) {
	if (! extension_loaded('mbstring') && ! class_exists('HypMBString')) {
		include(XOOPS_TRUST_PATH . '/class/hyp_common/mbemulator/mb-emulator.php');
	}
	$css = mb_convert_kana($css, 'asKV', mb_detect_encoding($css));
	$css = preg_replace('/(expression|javascript|vbscript|@import|cookie|eval|behavior|behaviour|binding|include-source|@i|[\x00-\x08\x0e-\x1f\x7f]+|\\\(?![\'"{};:()#A*]))/i', '', $css);
	$css = str_replace(array('*/', '<', '>', '&#'), array('*/  ', '&lt;', '&gt;', ''), $css);
}

// file_get_contents -- Reads entire file into a string
// (PHP 4 >= 4.3.0, PHP 5)
if (! function_exists('file_get_contents')) {
	function file_get_contents($filename, $incpath = false, $resource_context = null)
	{
		if (false === $fh = fopen($filename, 'rb', $incpath)) {
			trigger_error('file_get_contents() failed to open stream: No such file or directory', E_USER_WARNING);
			return false;
		}
 
		clearstatcache();
		if ($fsize = @filesize($filename)) {
			$data = fread($fh, $fsize);
		} else {
			$data = '';
			while (!feof($fh)) {
				$data .= fread($fh, 8192);
			}
		}
 
		fclose($fh);
		return $data;
	}
}
?>