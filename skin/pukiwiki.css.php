<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: pukiwiki.css.php,v 1.30 2007/09/11 06:28:36 nao-pon Exp $
// Copyright (C)
//   2002-2005 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// Default CSS

// Default charset
$charset = isset($_GET['charset']) ? $_GET['charset']  : '';
switch ($charset) {
	case 'Shift_JIS': break; /* this @charset is for Mozilla's bug */
	default: $charset ='iso-8859-1';
}

// Media
$media   = isset($_GET['media'])   ? $_GET['media']    : '';
if ($media != 'print') $media = 'screen';

// Base
$base   = isset($_GET['base'])   ? "_".preg_replace("/[^\w-]+/","",$_GET['base'])    : '';
$class = "div.xpwiki".$base;

// Pre Width
$pre_width = isset($_GET['pw']) ? $_GET['pw'] : 'auto';

// Over write
$overwrite = (empty($overwrite))? '' : $overwrite;

// Etag
$etag = md5($base.$charset.$media.$overwrite.$pre_width.filemtime(__FILE__));

// Not Modified?
if ($etag === @$_SERVER["HTTP_IF_NONE_MATCH"]) {
	header( "HTTP/1.1 304 Not Modified" );
	header( "Etag: ". $etag );
	exit();
}

// Output buffering start
ob_start();

// Output CSS ----
?>
@charset "<?php echo $charset ?>";

#xpwiki_loading {
	background-color: white;
	position: absolute;
	text-align: center;
	z-index: 200;
	filter:alpha(opacity=50);
	-moz-opacity: 0.5;
	opacity: 0.5;
	cursor: wait;
}

$class {
	background-color:inherit;
	width:100%;
}

$class pre,
$class dl,
$class p,
$class blockquote { line-height:130%; }

$class blockquote { margin-left:32px; }

$class table { width: auto; }

$class,
$class td {
	color:black;
	/*background-color:white;*/
	font-size:100%;
	font-family:verdana, arial, helvetica, Sans-Serif;
}

$class a:link {
<?php	if ($media === 'print') { ?>
	text-decoration: underline;
<?php	} else { ?>
	color:#215dc6;
	background-color:inherit;
	text-decoration:none;
	font-weight: inherit;
<?php	} ?>
}

$class a:active {
	color:#215dc6;
	background-color:#CCDDEE;
	text-decoration:none;
	font-weight: inherit;
}

$class a:visited {
<?php	if ($media === 'print') { ?>
	text-decoration: underline;
<?php	} else { ?>
	color:#a63d21;
	background-color:inherit;
	text-decoration:none;
	font-weight: inherit;
<?php	} ?>
}

$class a:hover {
	color:#215dc6;
	background-color:#CCDDEE;
	text-decoration:underline;
	font-weight: inherit;
}

<?php if ($media === 'screen') { ?>
$class a.ext {
	border-bottom: 1px blue dotted;
	background-image: url(../loader.php?src=ext.png);
	background-repeat: no-repeat;
	background-position: left 3px;
	padding-left: 12px;
}

$class a.pagelink {
	/*border-bottom: 1px silver dotted;*/
}

$class a.autolink {
	/*border-bottom: 1px silver dotted;*/
}

$class a.ext_autolink {
	border-bottom: 1px silver dotted;
	color: inherit;
	font-weight: inherit;
}
<?php	} ?>

$class h1,
$class h2 {
	font-family:verdana, arial, helvetica, Sans-Serif;
	color:inherit;
	background-color:#DDEEFF;
	padding:.3em;
	border:0px;
	margin:0px 0px .5em 0px;
	text-align: left;
}
$class h3 {
	font-family:verdana, arial, helvetica, Sans-Serif;
	border-bottom:  3px solid #DDEEFF;
	border-top:     1px solid #DDEEFF;
	border-left:   10px solid #DDEEFF;
	border-right:   5px solid #DDEEFF;

	color:inherit;
	background-color:#FFFFFF;
	padding:.3em;
	margin:0px 0px .5em 0px;
	text-align: left;
}
$class h4 {
	font-family:verdana, arial, helvetica, Sans-Serif;
	border-left:   18px solid #DDEEFF;

	color:inherit;
	background-color:#FFFFFF;
	padding:.3em;
	margin:0px 0px .5em 0px;
	text-align: left;
}
$class h5,
$class h6 {
	font-family:verdana, arial, helvetica, Sans-Serif;
	color:inherit;
	background-color:#DDEEFF;
 	padding:.3em;
 	border:0px;
 	margin:0px 0px .5em 0px;
	text-align: left;
}

$class h1.title {
	font-size: 30px;
	font-weight:bold;
	background-color:transparent;
	padding: 10px 0px 0px 0px;
	border: 0px;
	margin: 10px 0px 0px 0px;
}

$class dt {
	font-weight:bold;
	margin-top:1em;
	margin-left:1em;
}

/* Pre base */
$class div.pre {
	width: <?php echo $pre_width ?>;
	overflow: auto;
	margin: 10px;
}

$class pre {
	border-top:#DDDDEE 1px solid;
	border-bottom:#888899 1px solid;
	border-left:#DDDDEE 1px solid;
	border-right:#888899 1px solid;
	padding:.5em;
	margin-left:1em;
	margin-right:2em;
	color:black;
	background-color:#F0F8FF;
	width: auto;

	white-space: -moz-pre-wrap; /* Mozilla */
	white-space: -pre-wrap;     /* Opera 4-6 */
	white-space: -o-pre-wrap;   /* Opera 7 */
	white-space: pre-wrap;      /* CSS3 */
	word-wrap: break-word;      /* IE 5.5+ */
}

/* For renderer on XOOPS */
$class div.xoopsCode pre {
	border: none;
	padding: 0px;
	margin: 3px;
	color:black;
	background-color:transparent;
	
	white-space: -moz-pre-wrap; /* Mozilla */
	white-space: -pre-wrap;     /* Opera 4-6 */
	white-space: -o-pre-wrap;   /* Opera 7 */
	white-space: pre-wrap;      /* CSS3 */
	word-wrap: break-word;      /* IE 5.5+ */
}

$class img {
	border:none;
	vertical-align:middle;
}

$class form {
	margin-top: 10px;
	margin-bottom: 10px;
}

$class em { font-style:italic; }

$class strong { font-weight:bold; }

$class thead td.style_td,
$class tfoot td.style_td {
	color:inherit;
	background-color:#D0D8E0;
}
$class thead th.style_th,
$class tfoot th.style_th {
	color:inherit;
	background-color:#E0E8F0;
}
$class .style_table {
	padding:0px;
	border:0px;
	margin:auto;
	text-align:left;
	color:inherit;
	background-color:#ccd5dd;
}
$class .style_th {
	padding:5px;
	margin:1px;
	text-align:center;
	color:inherit;
	background-color:#EEEEEE;
}
$class .style_td {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#EEF5FF;
}

$class ul,
$class ol {
	margin-top:.5em;
	margin-bottom:.5em;
	line-height:130%;
	
	list-style-type:inherit;
	list-style-position:outside;
	list-style-image:none;
	marker-offset:auto;
	color: inherit;
}

$class li {
	list-style-type:inherit;
	list-style-position:outside;
	list-style-image:none;
	marker-offset:auto;
	color: inherit;
}

$class ul.list1 { list-style-type:disc; }
$class ul.list2 { list-style-type:circle; }
$class ul.list3 { list-style-type:square; }
$class ol.list1 { list-style-type:decimal; }
$class ol.list2 { list-style-type:lower-roman; }
$class ol.list3 { list-style-type:lower-alpha; }

$class div.ie5 { text-align:center; }

$class span.noexists {
	color:inherit;
	background-color:#FFFACC;
}

$class .small { font-size:80%; }

$class .super_index {
	color:#DD3333;
	background-color:inherit;
	font-weight:bold;
	font-size:60%;
	vertical-align:super;
}

$class a.note_super {
	color:#DD3333;
	background-color:inherit;
	font-weight:bold;
	font-size:60%;
	vertical-align:super;
}

$class div.jumpmenu {
	font-size:60%;
	text-align:right;
}

$class hr.full_hr {
	border-style:ridge;
	border-color:#333333;
	border-width:1px 0px;
}
$class hr.note_hr {
	width:90%;
	border-style:ridge;
	border-color:#333333;
	border-width:1px 0px;
	text-align:center;
	margin:1em auto 0em auto;
}

$class span.size1 {
	font-size:xx-small;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
$class span.size2 {
	font-size:x-small;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
$class span.size3 {
	font-size:small;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
$class span.size4 {
	font-size:medium;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
$class span.size5 {
	font-size:large;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
$class span.size6 {
	font-size:x-large;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
$class span.size7 {
	font-size:xx-large;
	line-height:130%;
	text-indent:0px;
	display:inline;
}

$class div.commentbody {
	margin-left:30px;
	margin-right:10px;
}

$class div.commentbody h2 {
	background-color: transparent;
}

$class div.commentbody textarea {
	height: 7em;
}

$class div.system_notification {
	margin-left:30px;
	margin-right:10px;
}

$class div.system_notification h4 {
	/*
	background-color: transparent;
	border: none;
	*/
}

div.system_notification table {
	width: auto;
	margin-left: auto;
	margin-right: auto;
}

/* html.php/catbody() */
$class strong.word0 {
	background-color:#FFFF66;
	color:black;
}
$class strong.word1 {
	background-color:#A0FFFF;
	color:black;
}
$class strong.word2 {
	background-color:#99FF99;
	color:black;
}
$class strong.word3 {
	background-color:#FF9999;
	color:black;
}
$class strong.word4 {
	background-color:#FF66FF;
	color:black;
}
$class strong.word5 {
	background-color:#880000;
	color:white;
}
$class strong.word6 {
	background-color:#00AA00;
	color:white;
}
$class strong.word7 {
	background-color:#886800;
	color:white;
}
$class strong.word8 {
	background-color:#004699;
	color:white;
}
$class strong.word9 {
	background-color:#990099;
	color:white;
}
$class div.commentbody table {
	width: 100%;
}

/* html.php/edit_form() */
$class .edit_form { clear:both; }
$class .edit_form textarea {
	width: 98%;
	margin-right: 2%;
}
$class .edit_form_ajax {
	border: 1px gray solid;
	padding: 5px;
}
$class .edit_form_ajax textarea {
	width: 98%;
	margin-right: 2%;
	height: 250px;
}

/* pukiwiki.skin.php */
$class div.header {
	padding:0px;
	margin:0px;
	background-color: transparent;
}

$class div.navigator {
<?php   if ($media === 'print') { ?>
	display:none;
<?php   } else { ?>
	clear:both;
	padding:4px 0px 0px 0px;
	margin:0px;
	font-size:90%;
<?php   } ?>
}

$class div.navigator_wiki {
	width: auto;
	float: right;
	padding-top: 15px;
	padding-left: 1em;
}

$class div.navigator_page {
	text-align: center;
}

$class div.navigator_info {
	text-align: right;
	margin-bottom: 10px;
}

$class td.menubar {
<?php   if ($media === 'print') { ?>
	display:none;
<?php   } else { ?>
	width:10em;
	vertical-align:top;
<?php   } ?>
}

$class div.menubar {
<?php   if ($media === 'print') { ?>
	display:none;
<?php   } else { ?>
	/*width:9em;*/
	padding:0px;
	margin:4px;
	word-break:break-all;
	font-size:90%;
	overflow:hidden;
<?php   } ?>
}

$class div.menubar ul {
	margin:0px 0px 0px .5em;
	padding:0px 0px 0px .5em;
}

$class div.menubar ul li { line-height:110%; }

$class div.menubar h4 { font-size:110%; }

$class div.body {
	padding:0px;
	margin:0px 0px 0px .5em;
}

$class div.footnotes {
	clear:both;
	padding:0px;
	margin:0px;
}

$class div.attach {
<?php   if ($media === 'print') { ?>
	display:none;
<?php   } else { ?>
	clear:both;
	padding:0px;
	margin:0px;
	font-size:90%;
<?php   } ?>
}

$class div.toolbar {
<?php   if ($media === 'print') { ?>
        display:none;
<?php   } else { ?>
	clear:both;
	padding:0px;
	margin:0px;
	text-align:right;
<?php   } ?>
}

$class div.lastmodified {
	font-size:80%;
	padding:0px;
	margin:0px;
}

$class div.related {
<?php   if ($media === 'print') { ?>
        display:none;
<?php   } else { ?>
	font-size:80%;
	padding:0px;
	margin:16px 0px 0px 0px;
<?php   } ?>
}

$class div.footer {
	font-size:70%;
	padding:0px;
	margin:16px 0px 0px 0px;
}

$class div.banner {
	float:right;
	margin-top:24px;
}

$class div.preview {
	color:inherit;
	background-color:#F5F8FF;
}

$class div.ajax_preview {
	overflow: auto;
	max-height: 250px;
	border: 1px gray solid;
}

$class img.logo {
<?php   if ($media === 'print') { ?>
	display:none;
<?php   } else { ?>
	float:left;
	margin-right:20px;
<?php   } ?>
}

/* aname.inc.php */
$class .anchor {}
$class .anchor_super {
	font-size:xx-small;
	vertical-align:super;
}

/* br.inc.php */
$class br.spacer {}

/* calendar_viewer.inc.php */
$class div.calendar_viewer {
	color:inherit;
	background-color:inherit;
	margin-top:20px;
	margin-bottom:10px;
	padding-bottom:10px;
}
$class span.calendar_viewer_left {
	color:inherit;
	background-color:inherit;
	float:left;
}
$class span.calendar_viewer_right {
	color:inherit;
	background-color:inherit;
	float:right;
}

/* areaedit.inc.php */
$class .area_on
{
	background-color: #ffe4e1;
}
$class .area_off
{
	background-color: transparent;
}

/* clear.inc.php */
$class .clear {
	margin:0px;
	clear:both;
}

/* counter.inc.php */
$class div.counter { font-size:70%; }

/* diff.inc.php */
$class span.diff_added {
	color:blue;
	background-color:inherit;
}

$class span.diff_removed {
	color:red;
	background-color:inherit;
}

/* hr.inc.php */
$class hr.short_line {
	text-align:center;
	width:80%;
	border-style:solid;
	border-color:#333333;
	border-width:1px 0px;
}

/* include.inc.php */
$class h5.side_label { text-align:center; }

/* navi.inc.php */
$class ul.navi {
	margin:0px;
	padding:0px;
	text-align:center;
}
$class li.navi_none {
	display:inline;
	float:none;
}
$class li.navi_left {
	display:inline;
	float:left;
	text-align:left;
}
$class li.navi_right {
	display:inline;
	float:right;
	text-align:right;
}

/* new.inc.php */
$class span.comment_date { font-size:x-small; }
$class span.new1 {
	color:red;
	background-color:transparent;
	font-size:x-small;
}
$class span.new5 {
	color:green;
	background-color:transparent;
	font-size:xx-small;
}

/* popular.inc.php */
$class span.counter { font-size:70%; }
$class ul.popular_list {
<?php
/*
	padding:0px;
	border:0px;
	margin:0px 0px 0px 1em;
	word-wrap:break-word;
	word-break:break-all;
*/
?>
}

/* recent.inc.php,showrss.inc.php */
$class ul.recent_list {
<?php
/*
	padding:0px;
	border:0px;
	margin:0px 0px 0px 1em;
	word-wrap:break-word;
	word-break:break-all;
*/
?>
}

/* ref.inc.php */
$class div.img_margin {
	margin-left:32px;
	margin-right:32px;
}

/* vote.inc.php */
$class td.vote_label {
	color:inherit;
	background-color:#FFCCCC;
}
$class td.vote_td1 {
	color:inherit;
	background-color:#DDE5FF;
}
$class td.vote_td2 {
	color:inherit;
	background-color:#EEF5FF;
}
<?php
// Over write
echo $overwrite;

$out = str_replace('$class', $class, ob_get_contents());

while( ob_get_level() ) {
	ob_end_clean() ;
}

// Send header
header('Content-Type: text/css');
$matches = array();
if(ini_get('zlib.output_compression') && preg_match('/\b(gzip|deflate)\b/i', $_SERVER['HTTP_ACCEPT_ENCODING'], $matches)) {
	header('Content-Encoding: ' . $matches[1]);
	header('Vary: Accept-Encoding');
}
header( "Last-Modified: " . gmdate( "D, d M Y H:i:s", filemtime(__FILE__) ) . " GMT" );
header( "Etag: ". $etag );
header( "Content-length: ". strlen($out) );
echo $out;
?>