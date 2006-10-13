<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: pukiwiki.css.php,v 1.1 2006/10/13 13:17:49 nao-pon Exp $
// Copyright (C)
//   2002-2005 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// Default CSS

// Send header
header('Content-Type: text/css');
$matches = array();
if(ini_get('zlib.output_compression') && preg_match('/\b(gzip|deflate)\b/i', $_SERVER['HTTP_ACCEPT_ENCODING'], $matches)) {
	header('Content-Encoding: ' . $matches[1]);
	header('Vary: Accept-Encoding');
}

// Default charset
$charset = isset($_GET['charset']) ? $_GET['charset']  : '';
switch ($charset) {
	case 'Shift_JIS': break; /* this @charset is for Mozilla's bug */
	default: $charset ='iso-8859-1';
}

// Media
$media   = isset($_GET['media'])   ? $_GET['media']    : '';
if ($media != 'print') $media = 'screen';

// Output CSS ----
?>
@charset "<?php echo $charset ?>";

div.xpwiki { width:100%; }

div.xpwiki pre,
div.xpwiki dl,
div.xpwiki  ol,
div.xpwiki  p,
div.xpwiki  blockquote { line-height:130%; }

div.xpwiki blockquote { margin-left:32px; }

div.xpwiki table { width: auto; }

div.xpwiki,
div.xpwiki td {
	color:black;
	background-color:white;
	/*margin-left:2%;
	margin-right:2%;*/
	font-size:100%;
	font-family:verdana, arial, helvetica, Sans-Serif;
}

div.xpwiki a:link {
<?php	if ($media == 'print') { ?>
	text-decoration: underline;
<?php	} else { ?>
	color:#215dc6;
	background-color:inherit;
	text-decoration:none;
	font-weight: none;
<?php	} ?>
}

div.xpwiki a:active {
	color:#215dc6;
	background-color:#CCDDEE;
	text-decoration:none;
	font-weight: none;
}

div.xpwiki a:visited {
<?php	if ($media == 'print') { ?>
	text-decoration: underline;
<?php	} else { ?>
	color:#a63d21;
	background-color:inherit;
	text-decoration:none;
	font-weight: none;
<?php	} ?>
}

div.xpwiki a:hover {
	color:#215dc6;
	background-color:#CCDDEE;
	text-decoration:underline;
	font-weight: none;
}

div.xpwiki h1,
div.xpwiki h2 {
	font-family:verdana, arial, helvetica, Sans-Serif;
	color:inherit;
	background-color:#DDEEFF;
	padding:.3em;
	border:0px;
	margin:0px 0px .5em 0px;
	text-align: left;
}
div.xpwiki h3 {
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
div.xpwiki h4 {
	font-family:verdana, arial, helvetica, Sans-Serif;
	border-left:   18px solid #DDEEFF;

	color:inherit;
	background-color:#FFFFFF;
	padding:.3em;
	margin:0px 0px .5em 0px;
	text-align: left;
}
div.xpwiki h5,
div.xpwiki h6 {
	font-family:verdana, arial, helvetica, Sans-Serif;
	color:inherit;
	background-color:#DDEEFF;
 	padding:.3em;
 	border:0px;
 	margin:0px 0px .5em 0px;
	text-align: left;
}

div.xpwiki h1.title {
	font-size: 30px;
	font-weight:bold;
	background-color:transparent;
	padding: 12px 0px 0px 0px;
	border: 0px;
	margin: 12px 0px 0px 0px;
}

div.xpwiki dt {
	font-weight:bold;
	margin-top:1em;
	margin-left:1em;
}

div.xpwiki pre {
	border-top:#DDDDEE 1px solid;
	border-bottom:#888899 1px solid;
	border-left:#DDDDEE 1px solid;
	border-right:#888899 1px solid;
	padding:.5em;
	margin-left:1em;
	margin-right:2em;
	white-space:pre;
	color:black;
	background-color:#F0F8FF;
}

div.xpwiki img {
	border:none;
	vertical-align:middle;
}

div.xpwiki ul {
	margin-top:.5em;
	margin-bottom:.5em;
	line-height:130%;
}

div.xpwiki em { font-style:italic; }

div.xpwiki strong { font-weight:bold; }

div.xpwiki thead td.style_td,
div.xpwiki tfoot td.style_td {
	color:inherit;
	background-color:#D0D8E0;
}
div.xpwiki thead th.style_th,
div.xpwiki tfoot th.style_th {
	color:inherit;
	background-color:#E0E8F0;
}
div.xpwiki .style_table {
	padding:0px;
	border:0px;
	margin:auto;
	text-align:left;
	color:inherit;
	background-color:#ccd5dd;
}
div.xpwiki .style_th {
	padding:5px;
	margin:1px;
	text-align:center;
	color:inherit;
	background-color:#EEEEEE;
}
div.xpwiki .style_td {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#EEF5FF;
}

div.xpwiki ul.list1 { list-style-type:disc; }
div.xpwiki ul.list2 { list-style-type:circle; }
div.xpwiki ul.list3 { list-style-type:square; }
div.xpwiki ol.list1 { list-style-type:decimal; }
div.xpwiki ol.list2 { list-style-type:lower-roman; }
div.xpwiki ol.list3 { list-style-type:lower-alpha; }
div.xpwiki li { list-style-type:normal; }

div.xpwiki div.ie5 { text-align:center; }

div.xpwiki span.noexists {
	color:inherit;
	background-color:#FFFACC;
}

div.xpwiki .small { font-size:80%; }

div.xpwiki .super_index {
	color:#DD3333;
	background-color:inherit;
	font-weight:bold;
	font-size:60%;
	vertical-align:super;
}

div.xpwiki a.note_super {
	color:#DD3333;
	background-color:inherit;
	font-weight:bold;
	font-size:60%;
	vertical-align:super;
}

div.xpwiki div.jumpmenu {
	font-size:60%;
	text-align:right;
}

div.xpwiki hr.full_hr {
	border-style:ridge;
	border-color:#333333;
	border-width:1px 0px;
}
div.xpwiki hr.note_hr {
	width:90%;
	border-style:ridge;
	border-color:#333333;
	border-width:1px 0px;
	text-align:center;
	margin:1em auto 0em auto;
}

div.xpwiki span.size1 {
	font-size:xx-small;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
div.xpwiki span.size2 {
	font-size:x-small;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
div.xpwiki span.size3 {
	font-size:small;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
div.xpwiki span.size4 {
	font-size:medium;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
div.xpwiki span.size5 {
	font-size:large;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
div.xpwiki span.size6 {
	font-size:x-large;
	line-height:130%;
	text-indent:0px;
	display:inline;
}
div.xpwiki span.size7 {
	font-size:xx-large;
	line-height:130%;
	text-indent:0px;
	display:inline;
}

/* html.php/catbody() */
div.xpwiki strong.word0 {
	background-color:#FFFF66;
	color:black;
}
div.xpwiki strong.word1 {
	background-color:#A0FFFF;
	color:black;
}
div.xpwiki strong.word2 {
	background-color:#99FF99;
	color:black;
}
div.xpwiki strong.word3 {
	background-color:#FF9999;
	color:black;
}
div.xpwiki strong.word4 {
	background-color:#FF66FF;
	color:black;
}
div.xpwiki strong.word5 {
	background-color:#880000;
	color:white;
}
div.xpwiki strong.word6 {
	background-color:#00AA00;
	color:white;
}
div.xpwiki strong.word7 {
	background-color:#886800;
	color:white;
}
div.xpwiki strong.word8 {
	background-color:#004699;
	color:white;
}
div.xpwiki strong.word9 {
	background-color:#990099;
	color:white;
}

/* html.php/edit_form() */
div.xpwiki .edit_form { clear:both; }
div.xpwiki .edit_form textarea {
	width: 98%;
	margin-right: 2%;
}

/* pukiwiki.skin.php */
div.xpwiki div#header {
	padding:0px;
	margin:0px;
}

div.xpwiki div#navigator {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	clear:both;
	padding:4px 0px 0px 0px;
	margin:0px;
<?php   } ?>
}

div.xpwiki td.menubar {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	width:9em;
	vertical-align:top;
<?php   } ?>
}

div.xpwiki div#menubar {
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

div.xpwiki div#menubar ul {
	margin:0px 0px 0px .5em;
	padding:0px 0px 0px .5em;
}

div.xpwiki div#menubar ul li { line-height:110%; }

div.xpwiki div#menubar h4 { font-size:110%; }

div.xpwiki div#body {
	padding:0px;
	margin:0px 0px 0px .5em;
}

div.xpwiki div#note {
	clear:both;
	padding:0px;
	margin:0px;
}

div.xpwiki div#attach {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	clear:both;
	padding:0px;
	margin:0px;
<?php   } ?>
}

div.xpwiki div#toolbar {
<?php   if ($media == 'print') { ?>
        display:none;
<?php   } else { ?>
	clear:both;
	padding:0px;
	margin:0px;
	text-align:right;
<?php   } ?>
}

div.xpwiki div#lastmodified {
	font-size:80%;
	padding:0px;
	margin:0px;
}

div.xpwiki div#related {
<?php   if ($media == 'print') { ?>
        display:none;
<?php   } else { ?>
	font-size:80%;
	padding:0px;
	margin:16px 0px 0px 0px;
<?php   } ?>
}

div.xpwiki div#footer {
	font-size:70%;
	padding:0px;
	margin:16px 0px 0px 0px;
}

div.xpwiki div#banner {
	float:right;
	margin-top:24px;
}

div.xpwiki div#preview {
	color:inherit;
	background-color:#F5F8FF;
}

div.xpwiki img#logo {
<?php   if ($media == 'print') { ?>
	display:none;
<?php   } else { ?>
	float:left;
	margin-right:20px;
<?php   } ?>
}

/* aname.inc.php */
div.xpwiki .anchor {}
div.xpwiki .anchor_super {
	font-size:xx-small;
	vertical-align:super;
}

/* br.inc.php */
div.xpwiki br.spacer {}

/* calendar*.inc.php */
div.xpwiki .style_calendar {
	padding:0px;
	border:0px;
	margin:3px;
	color:inherit;
	background-color:#CCD5DD;
	text-align:center;
}
div.xpwiki .style_td_caltop {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#EEF5FF;
	font-size:80%;
	text-align:center;
}
div.xpwiki .style_td_today {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#FFFFDD;
	text-align:center;
}
div.xpwiki .style_td_sat {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#DDE5FF;
	text-align:center;
}
div.xpwiki .style_td_sun {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#FFEEEE;
	text-align:center;
}
div.xpwiki .style_td_blank {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#EEF5FF;
	text-align:center;
}
div.xpwiki .style_td_day {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#EEF5FF;
	text-align:center;
}
div.xpwiki .style_td_week {
	padding:5px;
	margin:1px;
	color:inherit;
	background-color:#DDE5EE;
	font-size:80%;
	font-weight:bold;
	text-align:center;
}

/* calendar_viewer.inc.php */
div.xpwiki div.calendar_viewer {
	color:inherit;
	background-color:inherit;
	margin-top:20px;
	margin-bottom:10px;
	padding-bottom:10px;
}
div.xpwiki span.calendar_viewer_left {
	color:inherit;
	background-color:inherit;
	float:left;
}
div.xpwiki span.calendar_viewer_right {
	color:inherit;
	background-color:inherit;
	float:right;
}

/* clear.inc.php */
div.xpwiki .clear {
	margin:0px;
	clear:both;
}

/* counter.inc.php */
div.xpwiki div.counter { font-size:70%; }

/* diff.inc.php */
div.xpwiki span.diff_added {
	color:blue;
	background-color:inherit;
}

div.xpwiki span.diff_removed {
	color:red;
	background-color:inherit;
}

/* hr.inc.php */
div.xpwiki hr.short_line {
	text-align:center;
	width:80%;
	border-style:solid;
	border-color:#333333;
	border-width:1px 0px;
}

/* include.inc.php */
div.xpwiki h5.side_label { text-align:center; }

/* navi.inc.php */
div.xpwiki ul.navi {
	margin:0px;
	padding:0px;
	text-align:center;
}
div.xpwiki li.navi_none {
	display:inline;
	float:none;
}
div.xpwiki li.navi_left {
	display:inline;
	float:left;
	text-align:left;
}
div.xpwiki li.navi_right {
	display:inline;
	float:right;
	text-align:right;
}

/* new.inc.php */
div.xpwiki span.comment_date { font-size:x-small; }
div.xpwiki span.new1 {
	color:red;
	background-color:transparent;
	font-size:x-small;
}
div.xpwiki span.new5 {
	color:green;
	background-color:transparent;
	font-size:xx-small;
}

/* popular.inc.php */
div.xpwiki span.counter { font-size:70%; }
div.xpwiki ul.popular_list {
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
div.xpwiki ul.recent_list {
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
div.xpwiki div.img_margin {
	margin-left:32px;
	margin-right:32px;
}

/* vote.inc.php */
div.xpwiki td.vote_label {
	color:inherit;
	background-color:#FFCCCC;
}
div.xpwiki td.vote_td1 {
	color:inherit;
	background-color:#DDE5FF;
}
div.xpwiki td.vote_td2 {
	color:inherit;
	background-color:#EEF5FF;
}
