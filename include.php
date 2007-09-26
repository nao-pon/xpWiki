<?php
//
// Created on 2006/10/03 by nao-pon http://hypweb.net/
// $Id: include.php,v 1.8 2007/09/26 02:10:00 nao-pon Exp $
//

// Load & check a class HypCommonFunc
if(!class_exists('HypCommonFunc')) {
	include_once(dirname(dirname(dirname(__FILE__))).'/class/hyp_common/hyp_common_func.php');
}
$hyp_common_methods = get_class_methods('HypCommonFunc');
if (is_null($hyp_common_methods) || ! in_array('get_version', $hyp_common_methods) || HypCommonFunc::get_version() < 20070926) {
	$xpwiki_error[] = '[Warning] Please install or update a newest HypCommonFunc into "XOOPS_TRUST_PATH/class/".';
}

include_once(dirname(__FILE__)."/class/xpwiki.php");

include_once(dirname(__FILE__)."/class/root.php");

include_once(dirname(__FILE__)."/class/func/base_func.php");
include_once(dirname(__FILE__)."/class/func/pukiwiki_func.php");
if (extension_loaded('zlib')) {
	include_once(dirname(__FILE__)."/class/func/backup_gzip.php");
} else {
	include_once(dirname(__FILE__)."/class/func/backup_text.php");
}
include_once(dirname(__FILE__)."/class/func/xoops_wrapper.php");
include_once(dirname(__FILE__)."/class/func/xpwiki_func.php");

include_once(dirname(__FILE__)."/class/extension.php");

include_once(dirname(__FILE__)."/class/plugin.php");

include_once(dirname(__FILE__)."/class/convert_html.php");

include_once(dirname(__FILE__)."/class/make_link.php");

include_once(dirname(__FILE__)."/class/diff.php");

include_once(dirname(__FILE__)."/class/config.php");

// add compat functions
include_once(dirname(__FILE__)."/include/compat.php");

?>