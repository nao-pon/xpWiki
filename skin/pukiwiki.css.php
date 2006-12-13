<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: pukiwiki.css.php,v 1.6 2006/12/13 04:57:57 nao-pon Exp $
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

// Over write
$overwrite = (empty($overwrite))? '' : $overwrite;

// Etag
$etag = md5($base.$charset.$media.$overwrite.filemtime(__FILE__));

// Not Modified?
if ($etag == @$_SERVER["HTTP_IF_NONE_MATCH"]) {
	header( "HTTP/1.1 304 Not Modified" );
	header( "Etag: ". $etag );
	exit();
}

// Output buffering start
ob_start();

// Output CSS ----
?>
@charset "<?php echo $charset ?>";

/* pukiwiki helper */
div#wikihelper_base {
	position:absolute;
	top:-1000px;
	left:-1000px;
	background-color:white;
	filter:alpha(opacity=85);
	-moz-opacity: 0.85;
	opacity: 0.85;
}

<?php echo $class ?> { width:100%; }

<?php echo $class ?> pre,
<?php echo $class ?> dl,
<?php echo $class ?> ol,
<?php echo $class ?> p,
<?php echo $class ?> blockquote { line-height:130%; }

<?php echo $class ?> blockquote { margin-left:32px; }

<?php echo $class ?> table { width: auto; }

<?php echo $class ?>,
<?php echo $class ?> td {
	color:black;
	background-color:white;
	/*margin-left:2%;
	margin-right:2%;*/
	font-size:100%;
	font-family:verdana, arial, helvetica, Sans-Serif;
}

<?php echo $class ?> a:link {
<?php	if ($media == 'print') { ?>
	text-decoration: underline;
<?php	} else { ?>
	color:#215dc6;
	background-color:inherit;
	text-decoration:none;
	font-weight: none;
<?php	} ?>
}

<?php echo $class ?> a:active {
	color:#215dc6;
	background-color:#CCDDEE;
	text-decoration:none;
	font-weight: none;
}

<?php echo $class ?> a:visited {
<?php	if ($media == 'print') { ?>
	text-decoration: underline;
<?php	} else { ?>
	color:#a63d21;
	background-color:inherit;
	text-decoration:none;
	font-weight: none;
<?php	} ?>
}

<?php echo $class ?> a:hover {
	color:#215dc6;
	background-color:#CCDDEE;
	text-decoration:underline;
	font-weight: none;
}

<?php echo $class ?> h1,
<?php echo $class ?> h2 {
	font-family:verdana, arial, helvetica, Sans-Serif;
	color:inherit;
	background-color:#DDEEFF;
	padding:.3em;
	border:0px;
	margin:0px 0px .5em 0px;
	text-align: left;
}
<?php echo $class ?> h3 {
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
<?php echo $class ?> h4 {
	font-family:verdana, arial, helvetica, Sans-Serif;
	border-left:   18px solid #DDEEFF;

	color:inherit;
	background-color:#FFFFFF;
	padding:.3em;
	margin:0px 0px .5em 0px;
	text-align: left;
}
<?php echo $class ?> h5,
<?php echo $class ?> h6 {
	font-family:verdana, arial, helvetica, Sans-Serif;
	color:inherit;
	background-color:#DDEEFF;
 	padding:.3em;
 	border:0px;
 	margin:0px 0px .5em 0px;
	text-align: left;
}

<?php echo $class ?> h1.title {
	font-size: 30px;
	font-weight:bold;
	background-color:transparent;
	padding: 12px 0px 0px 0px;
	border: 0px;
	margin: 12px 0px 0px 0px;
}

<?php echo $class ?> dt {
	font-weight:bold;
	margin-top:1em;
	margin-left:1em;
}

<?php echo $class ?> pre {
	border-top:#DDDDEE 1px solid;
	border-bottom:#888899 1px solid;
	border-left:#DDDDEE 1px solid;
	border-right:#888899 1px solid;
	padding:.5em;
	margin-left:1em;
	margin-right:2em;
	color:black;
	background-color:#F0F8FF;

	white-space: -moz-pre-wrap; /* Mozilla */
	white-space: -pre-wrap;     /* Opera 4-6 */
	white-space: -o-pre-wrap;   /* Opera 7 */
	white-space: pre-wrap;      /* CSS3 */
	word-wrap: break-word;      /* IE 5.5+ */
}

<?php echo $class ?> img {
	border:none;
	vertical-align:middle;
}

<?php echo $class ?> ul {
	margin-top:.5em;
	margin-bottom:.5em;
	line-height:130%;
}

<?php echo $class ?> em { font-style:italic; }

<?php echo $class ?> strong { font-weight:bold; }

<?php echo $class ?> thead td.style_td,
<?php echo $class ?> tfoot td.style_td {
	color:inherit;
	background-color:#D0D8E0;
}
<?php echo $class ?> thead th.style_th,
<?php echo $class ?> tfoot th.style_th {
	color:inherit;
	background-color:#E0E8F0;
}
<?php echo $class ?> .style_table {
	padding:0px;
	border:0px;
	margin:auto;
	text-align:left;
	color:inherit;
	background-color:#ccd5dd;
}
<?php echo $class ?> .style_th {
	padding:5px;
	margin:1px;
	text-align:center;
	color:inherit;
	background-color:#EEEEEE;
}
<?php echo $class ?> .style_td {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#EEF5FF;
}

<?php echo $class ?> ul.list1 { list-style-type:disc; }
<?php echo $class ?> ul.list2 { list-style-type:circle; }
<?php echo $class ?> ul.list3 { list-style-type:square; }
<?php echo $class ?> ol.list1 { list-style-type:decimal; }
<?php echo $class ?> ol.list2 { list-style-type:lower-roman; }
<?php echo $class ?> ol.list3 { list-style-type:lower-alpha; }
<?php echo $class ?> li { list-style-type:normal; }

<?php echo $class ?> div.ie5 { text-align:center; }

<?php echo $class ?> span.noexists {
	color:inherit;
	background-color:#FFFACC;
}

<?php echo $class ?> .small { font-size:80%; }

<?php echo $class ?> .super_index {
	color:#DD3333;
	background-color:inherit;
	font-weight:bold;
	font-size:60%;
	vertical-align:super;
}

<?php echo $class ?> a.note_super {
	color:#DD3333;
	background-color:inherit;
	font-weight:bold;
	font-size:60%;
	vertical-align:super;
}

<?php echo $class ?> div.jumpmenu {
	font-size:60%;
	text-align:right;
}

<?php echo $class ?> hr.full_hr {
	border-style:ridge;
	border-color:#333333;
	border-width:1px 0px;
}
<?php echo $class ?> hr.note_hr {
	width:90%;
	border-style:ridge;
	border-color:#333333;
	border-width:1px 0px;
	text-align:center;
	margin:1em auto 0em auto;
}

<?php echo $class ?> span.size1 {
	font-size:xx-small;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
<?php echo $class ?> span.size2 {
	font-size:x-small;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
<?php echo $class ?> span.size3 {
	font-size:small;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
<?php echo $class ?> span.size4 {
	font-size:medium;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
<?php echo $class ?> span.size5 {
	font-size:large;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
<?php echo $class ?> span.size6 {
	font-size:x-large;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
<?php echo $class ?> span.size7 {
	font-size:xx-large;
	line-height:130%;
	text-indent:0px;
	display:inline;
}

/* html.php/catbody() */
<?php echo $class ?> strong.word0 {
	background-color:#FFFF66;
	color:black;
}
<?php echo $class ?> strong.word1 {
	background-color:#A0FFFF;
	color:black;
}
<?php echo $class ?> strong.word2 {
	background-color:#99FF99;
	color:black;
}
<?php echo $class ?> strong.word3 {
	background-color:#FF9999;
	color:black;
}
<?php echo $class ?> strong.word4 {
	background-color:#FF66FF;
	color:black;
}
<?php echo $class ?> strong.word5 {
	background-color:#880000;
	color:white;
}
<?php echo $class ?> strong.word6 {
	background-color:#00AA00;
	color:white;
}
<?php echo $class ?> strong.word7 {
	background-color:#886800;
	color:white;
}
<?php echo $class ?> strong.word8 {
	background-color:#004699;
	color:white;
}
<?php echo $class ?> strong.word9 {
	background-color:#990099;
	color:white;
}
<?php echo $class ?> div.commentbody table {
	width: 100%;
}

/* html.php/edit_form() */
<?php echo $class ?> .edit_form { clear:both; }
<?php echo $class ?> .edit_form textarea {
	width: 98%;
	margin-right: 2%;
}

/* pukiwiki.skin.php */
<?php echo $class ?> div#header {
	padding:0px;
	margin:0px;
}

<?php echo $class ?> div#navigator {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	clear:both;
	padding:4px 0px 0px 0px;
	margin:0px;
<?php   } ?>
}

<?php echo $class ?> td.menubar {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	width:9em;
	vertical-align:top;
<?php   } ?>
}

<?php echo $class ?> div#menubar {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	width:9em;
	padding:0px;
	margin:4px;
	word-break:break-all;
	font-size:90%;
	overflow:hidden;
<?php   } ?>
}

<?php echo $class ?> div#menubar ul {
	margin:0px 0px 0px .5em;
	padding:0px 0px 0px .5em;
}

<?php echo $class ?> div#menubar ul li { line-height:110%; }

<?php echo $class ?> div#menubar h4 { font-size:110%; }

<?php echo $class ?> div#body {
	padding:0px;
	margin:0px 0px 0px .5em;
}

<?php echo $class ?> div#note {
	clear:both;
	padding:0px;
	margin:0px;
}

<?php echo $class ?> div#attach {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	clear:both;
	padding:0px;
	margin:0px;
<?php   } ?>
}

<?php echo $class ?> div#toolbar {
<?php   if ($media == 'print') { ?>
        display:none;
<?php   } else { ?>
	clear:both;
	padding:0px;
	margin:0px;
	text-align:right;
<?php   } ?>
}

<?php echo $class ?> div#lastmodified {
	font-size:80%;
	padding:0px;
	margin:0px;
}

<?php echo $class ?> div#related {
<?php   if ($media == 'print') { ?>
        display:none;
<?php   } else { ?>
	font-size:80%;
	padding:0px;
	margin:16px 0px 0px 0px;
<?php   } ?>
}

<?php echo $class ?> div#footer {
	font-size:70%;
	padding:0px;
	margin:16px 0px 0px 0px;
}

<?php echo $class ?> div#banner {
	float:right;
	margin-top:24px;
}

<?php echo $class ?> div#preview {
	color:inherit;
	background-color:#F5F8FF;
}

<?php echo $class ?> img#logo {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	float:left;
	margin-right:20px;
<?php   } ?>
}

/* aname.inc.php */
<?php echo $class ?> .anchor {}
<?php echo $class ?> .anchor_super {
	font-size:xx-small;
	vertical-align:super;
}

/* br.inc.php */
<?php echo $class ?> br.spacer {}

/* calendar*.inc.php */
<?php echo $class ?> .style_calendar {
	padding:0px;
	border:0px;
	margin:3px;
	color:inherit;
	background-color:#CCD5DD;
	text-align:center;
}
<?php echo $class ?> .style_td_caltop {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#EEF5FF;
	font-size:80%;
	text-align:center;
}
<?php echo $class ?> .style_td_today {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#FFFFDD;
	text-align:center;
}
<?php echo $class ?> .style_td_sat {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#DDE5FF;
	text-align:center;
}
<?php echo $class ?> .style_td_sun {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#FFEEEE;
	text-align:center;
}
<?php echo $class ?> .style_td_blank {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#EEF5FF;
	text-align:center;
}
<?php echo $class ?> .style_td_day {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#EEF5FF;
	text-align:center;
}
<?php echo $class ?> .style_td_week {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#DDE5EE;
	font-size:80%;
	font-weight:bold;
	text-align:center;
}

/* calendar_viewer.inc.php */
<?php echo $class ?> div.calendar_viewer {
	color:inherit;
	background-color:inherit;
	margin-top:20px;
	margin-bottom:10px;
	padding-bottom:10px;
}
<?php echo $class ?> span.calendar_viewer_left {
	color:inherit;
	background-color:inherit;
	float:left;
}
<?php echo $class ?> span.calendar_viewer_right {
	color:inherit;
	background-color:inherit;
	float:right;
}

/* clear.inc.php */
<?php echo $class ?> .clear {
	margin:0px;
	clear:both;
}

/* counter.inc.php */
<?php echo $class ?> div.counter { font-size:70%; }

/* diff.inc.php */
<?php echo $class ?> span.diff_added {
	color:blue;
	background-color:inherit;
}

<?php echo $class ?> span.diff_removed {
	color:red;
	background-color:inherit;
}

/* hr.inc.php */
<?php echo $class ?> hr.short_line {
	text-align:center;
	width:80%;
	border-style:solid;
	border-color:#333333;
	border-width:1px 0px;
}

/* include.inc.php */
<?php echo $class ?> h5.side_label { text-align:center; }

/* navi.inc.php */
<?php echo $class ?> ul.navi {
	margin:0px;
	padding:0px;
	text-align:center;
}
<?php echo $class ?> li.navi_none {
	display:inline;
	float:none;
}
<?php echo $class ?> li.navi_left {
	display:inline;
	float:left;
	text-align:left;
}
<?php echo $class ?> li.navi_right {
	display:inline;
	float:right;
	text-align:right;
}

/* new.inc.php */
<?php echo $class ?> span.comment_date { font-size:x-small; }
<?php echo $class ?> span.new1 {
	color:red;
	background-color:transparent;
	font-size:x-small;
}
<?php echo $class ?> span.new5 {
	color:green;
	background-color:transparent;
	font-size:xx-small;
}

/* popular.inc.php */
<?php echo $class ?> span.counter { font-size:70%; }
<?php echo $class ?> ul.popular_list {
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
<?php echo $class ?> ul.recent_list {
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
<?php echo $class ?> div.img_margin {
	margin-left:32px;
	margin-right:32px;
}

/* vote.inc.php */
<?php echo $class ?> td.vote_label {
	color:inherit;
	background-color:#FFCCCC;
}
<?php echo $class ?> td.vote_td1 {
	color:inherit;
	background-color:#DDE5FF;
}
<?php echo $class ?> td.vote_td2 {
	color:inherit;
	background-color:#EEF5FF;
}
<?php
// Over write
echo $overwrite;

// Send header
header('Content-Type: text/css');
$matches = array();
if(ini_get('zlib.output_compression') && preg_match('/\b(gzip|deflate)\b/i', $_SERVER['HTTP_ACCEPT_ENCODING'], $matches)) {
	header('Content-Encoding: ' . $matches[1]);
	header('Vary: Accept-Encoding');
}
header( "Last-Modified: " . gmdate( "D, d M Y H:i:s", filemtime(__FILE__) ) . " GMT" );
header( "Etag: ". $etag );
header( "Content-length: ". ob_get_length() );
ob_end_flush()
?>