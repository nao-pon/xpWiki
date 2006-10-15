<?php
//
// Created on 2006/10/03 by nao-pon http://hypweb.net/
// $Id: include.php,v 1.2 2006/10/15 10:47:05 nao-pon Exp $
//

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
include_once(dirname(__FILE__)."/class/plugin.php");
include_once(dirname(__FILE__)."/class/convert_html.php");
include_once(dirname(__FILE__)."/class/make_link.php");
include_once(dirname(__FILE__)."/class/diff.php");
include_once(dirname(__FILE__)."/class/config.php");

?>