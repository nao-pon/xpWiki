<?php
/*
 * Created on 2012/01/14 by nao-pon http://hypweb.net/
 * $Id: bbcode_image.php,v 1.3 2012/01/30 12:03:42 nao-pon Exp $
 */

$_patterns = $_replaces = array();
$_patterns_c = $_replaces_c = array();

// BB Code url
$_patterns_c[] = '/\[url=([\'"]?)((?:ht|f)tp[s]?:\/\/[!~*\'();\/?:\@&=+\$,%#_0-9a-zA-Z.-]+)\\1\](.+)\[\/url\]/sU';
$_replaces_c[] = function($m) { return '[['.str_replace(array("\r\n", "\r", "\n"), '&br;', $m[3]).':'.$m[2].']]'; };

$_patterns_c[] = '/\[url=([\'"]?)([!~*\'();\/?:\@&=+\$,%#_0-9a-zA-Z.-]+)\\1\](.+)\[\/url\]/sU';
$_replaces_c[] = function($m) { return '[['.str_replace(array("\r\n", "\r", "\n"), '&br;', $m[3]).':http://'.$m[2].']]'; };

$_patterns_c[] = '/\[siteurl=([\'"]?)\/?([!~*\'();?:\@&=+\$,%#_0-9a-zA-Z.-][!~*\'();\/?:\@&=+\$,%#_0-9a-zA-Z.-]+)\\1\](.+)\[\/siteurl\]/sU';
$_replaces_c[] = function($m) { return '[['.str_replace(array("\r\n", "\r", "\n"), '&br;', $m[3]).':site://'.$m[2].']]'; };

// BB Code image with align
$_patterns[] = '/\[img\s+align=([\'"]?)(left|center|right)\1(?:\s+title=([\'"]?)([^\'"][^\]\s]*)\3)?(?:\s+w(?:idth)?=([\'"]?)([\d]+)\5)?(?:\s+h(?:eight)?=([\'"]?)([\d]+)\7)?]([!~*\'();\/?:\@&=+\$,%#_0-9a-zA-Z.-]+)\[\/img\]/U';
$_replaces[] = '&ref($9,$2,"t:$4",mw:$6,mw:$8);';

// BB Code image normal
$_patterns[] = '/\[img(?:\s+title=([\'"]?)([^\'"][^\]\s]*)\1)?(?:\s+w(?:idth)?=([\'"]?)([\d]+)\3)?(?:\s+h(?:eight)?=([\'"]?)([\d]+)\5)?]([!~*\'();\/?:\@&=+\$,%#_0-9a-zA-Z.-]+)\[\/img\]/U';
$_replaces[] = '&ref($7,"t:$2",mw:$4,mw:$6);';

// BB Code siteimage with align
$_patterns[] = '/\[siteimg\s+align=([\'"]?)(left|center|right)\1(?:\s+title=([\'"]?)([^\'"][^\]\s]*)\3)?(?:\s+w(?:idth)?=([\'"]?)([\d]+)\5)?(?:\s+h(?:eight)?=([\'"]?)([\d]+)\7)?]\/?([!~*\'();?\@&=+\$,%#_0-9a-zA-Z.-][!~*\'();\/?\@&=+\$,%#_0-9a-zA-Z.-]+)\[\/siteimg\]/U';
$_replaces[] = '&ref(site://$9,$2,"t:$4",mw:$6,mw:$8);';

// BB Code siteimage normal
$_patterns[] = '/\[siteimg(?:\s+title=([\'"]?)([^\'"][^\]\s]*)\1)?(?:\s+w(?:idth)?=([\'"]?)([\d]+)\3)?(?:\s+h(?:eight)?=([\'"]?)([\d]+)\5)?]\/?([!~*\'();?\@&=+\$,%#_0-9a-zA-Z.-][!~*\'();\/?\@&=+\$,%#_0-9a-zA-Z.-]+)\[\/siteimg\]/U';
$_replaces[] = '&ref(site://$7,"t:$2",mw:$4,mw:$6);';

$root->str_rules['bbcode_image'] = array($_patterns, $_replaces);
$root->str_rules_callback['bbcode_image'] = array($_patterns_c, $_replaces_c);
unset($_patterns, $_replaces, $_patterns_c, $_replaces_c);
