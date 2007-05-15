<?php
/*
 * Created on 2007/05/13 by nao-pon http://hypweb.net/
 * $Id: setup.php,v 1.1 2007/05/15 06:28:57 nao-pon Exp $
 */

$ng = $out = '';

// Install setting
if (! file_exists($mydirpath . '/.installed')) {
	@ touch ($mydirpath . '/.installed');
	
	// Set imagemagick, jpegtran path.
	$out .= "* Now imagemagick & jpegtran path setting.\n";
	$dat = $path = "";
	
	if ( substr(PHP_OS, 0, 3) !== 'WIN' ) { 
		
		$dat .= "<?php\n";
		
		if ( ini_get('safe_mode') ) {
			if (chmod($mydirpath . '/include/hyp_common/image_magick.cgi', 0705)) {
				$out .= '- chmod( '. $mydirpath . '/include/hyp_common/image_magick.cgi, 0705 ) - OK.' . "\n";
				$_path = XOOPS_URL . '/modules/' . $mytrustdirname . '/include/hyp_common/image_magick.cgi';
				$dat .= "define('HYP_IMAGE_MAGICK_URL', '{$_path}');\n";
			} else {
				$ng  .= '- chmod( '. $mydirpath . '/include/hyp_common/image_magick.cgi, 0705 ) - NG.' . "\n";
			}
		}

		$exec = array();
		@ exec( "whereis -b convert" , $exec) ;
		if ($exec)
		{
			$path = array_pad(explode(" ",$exec[0]),2,"");
			$path = (preg_match("#^(/.+/)convert$#",$path[1],$match))? $match[1] : "";
			$dat .= "define('HYP_IMAGEMAGICK_PATH', '{$path}');\n";
		}
		
		$exec = array();
		@ exec( "whereis -b jpegtran" , $exec) ;
		if ($exec)
		{
			$path = array_pad(explode(" ",$exec[0]),2,"");
			$path = (preg_match("#^(/.+/)jpegtran$#",$path[1],$match))? $match[1] : "";
			$dat .= "define('HYP_JPEGTRAN_PATH', '{$path}');\n";
		}
		$dat .= "?>\n";
	}
	
	$filename = XOOPS_TRUST_PATH . '/class/hyp_common/execpath.inc.php';
	
	if ($dat && ($fp = @fopen($filename,"wb")))
	{
		fputs($fp, $dat);
		fclose($fp);
		$out .= "Edited a file ( {$filename} ) - OK\n";
	}
	else
	{
		$ng .= "Edited a file ( {$filename} ) - NG\n";
	}
	
	// permission
	$out .= "* Now permission setting.\n";

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
		if (chmod($mydirpath . '/' . $dir, 0707)) {
			$out .= '- chmod( '.$mydirpath .'/'.$dir.', 0707 ) - OK.' . "\n";
		} else {
			$ng  .= '- chmod( '.$mydirpath .'/'.$dir.', 0707 ) - NG.' . "\n";
		}
	}

	$out .= str_repeat('-', 40) . "\n";
}

// VerUP to 2
if (@ $myhtml_version < 2) {

	$base = $mydirpath . '/';
	$trust = XOOPS_TRUST_PATH . '/modules/' . $mytrustdirname . '/InitialData/VerUp/2/';
	$rmfiles = array('blocks.php');
	$mkdirs = array('blocks');
	$cpfiles = array('mytrustdirname.php','attach/s/.htaccess','blocks/blocks.php');
	
	files_copy ($base, $trust, $rmfiles, $mkdirs, $cpfiles);
}

// Finish
$out .= "All processing was completed.\n";

if ($ng) {
	$out .= "But next commands was not executed. Please do yourself by FTP etc.\n";
	$out .= $ng;
	$out .= str_repeat('-', 40) . "\n";
}

if (php_sapi_name() == "cli")
{
	echo "Content-Length: ".strlen($out)."\n";
	echo "Content-Type: text/plain\n\n";
}
else
{
	@ini_set('default_charset','');
	@mb_http_output('pass');
	header("Content-Length: ".strlen($out));
	header("Content-Type: text/plain");
}
echo $out;
exit();

// Functions

function files_copy ($base, $trust, $rmfiles, $mkdirs, $cpfiles) {
	global $out, $ng;
	
	$out .= "* Now copying new files.\n";
	
	foreach($rmfiles as $file) {
		if (file_exists($base . $file)) {
			if (@ unlink($base . $file)) {
				$out .= '- Delete file( '.$base . $file .' ) - OK.' . "\n";
			} else {
				$ng  .= '- Delete file( '.$base . $file .' ) - NG.' . "\n";
			}
		}
	}

	foreach($mkdirs as $dir) {
		if (! file_exists($base . $dir)) {
			if (@ mkdir($base . $dir)) {
				$out .= '- Make dirctory( '.$base . $dir .' ) - OK.' . "\n";
			} else {
				$ng  .= '- Make dirctory( '.$base . $dir .' ) - NG.' . "\n";
			}
		}
	}
	
	foreach($cpfiles as $file) {
		if (@ copy($trust . $file, $base . $file)) {
			$out .= '- File copy ('.$trust . $file .' TO ' . $base . $file . ' ) - OK.' . "\n";
		} else {
			$ng  .= '- File copy ('.$trust . $file .' TO ' . $base . $file . ' ) - NG.' . "\n";
		}
	}

	$out .= str_repeat('-', 40) . "\n";

}
?>