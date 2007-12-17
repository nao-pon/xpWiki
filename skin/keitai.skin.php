<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: keitai.skin.php,v 1.2 2007/12/17 07:52:28 nao-pon Exp $
// Copyright (C) 2003-2006 PukiWiki Developers Team
// License: GPL v2 or (at your option) any later version
//
// Skin for Embedded devices

// ----
// Prohibit direct access
if (! isset($this->cont['UI_LANG'])) die('UI_LANG is not set');

/////////////////////////////////////////////////
// xpWiki run mode
$this->root->runmode = "standalone";

$pageno = (isset($this->root->vars['p']) && is_numeric($this->root->vars['p'])) ? $this->root->vars['p'] : 0;
$edit = (isset($this->root->vars['cmd'])    && $this->root->vars['cmd']    == 'edit') ||
	(isset($this->root->vars['plugin']) && $this->root->vars['plugin'] == 'edit');

$this->root->max_size = --$this->root->max_size * 1024; // Make 1KByte spare (for $navi, etc)
$link = $_LINK;
$rw = ! $this->cont['PKWK_READONLY'];

// ----
// Modify

// Ignore &dagger;s
//$body = preg_replace('#<a[^>]+>' . preg_quote($this->root->_symbol_anchor, '#') . '</a>#', '', $body);

// Shrink IMG tags (= images) with character strings
// With ALT option
$body = preg_replace('#(<div[^>]+>)?(<a[^>]+>)?<img[^>]*alt="([^"]+)"[^>]*>(?(2)</a>)(?(1)</div>)#i', '[$3]', $body);
// Without ALT option
$body = preg_replace('#(<div[^>]+>)?(<a[^>]+>)?<img[^>]+>(?(2)</a>)(?(1)</div>)#i', '[img]', $body);

// ----

// Check content volume, Page numbers, divided by this skin
$pagecount = ceil(strlen($body) / $this->root->max_size);

// Too large contents to edit
if ($edit && $pagecount > 1)
   	die('Unable to edit: Too large contents for your device');

// Get one page
$body = substr($body, $pageno * $this->root->max_size, $this->root->max_size);

// ----
// Top navigation (text) bar

$navi = array();
$navi[] = '<a href="' . $link['top']  . '" ' . $this->root->accesskey . '="0">0.Top</a>';
if ($rw) {
	$navi[] = '<a href="' . $link['new']  . '" ' . $this->root->accesskey . '="1">1.New</a>';
	$navi[] = '<a href="' . $link['edit'] . '" ' . $this->root->accesskey . '="2">2.Edit</a>';
	if ($is_read && $this->root->function_freeze) {
		if (! $is_freeze) {
			$navi[] = '<a href="' . $link['freeze']   . '" ' . $this->root->accesskey . '="3">3.Freeze</a>';
		} else {
			$navi[] = '<a href="' . $link['unfreeze'] . '" ' . $this->root->accesskey . '="3">3.Unfreeze</a>';
		}
	}
}
$navi[] = '<a href="' . $this->root->script . '?' . rawurlencode($this->root->menubar) . '" ' . $this->root->accesskey . '="4">4.Menu</a>';
$navi[] = '<a href="' . $link['recent'] . '" ' . $this->root->accesskey . '="5">5.Recent</a>';

// Previous / Next block
if ($pagecount > 1) {
	$prev = $pageno - 1;
	$next = $pageno + 1;
	if ($pageno > 0) {
		$navi[] = '<a href="' . $this->root->script . '?cmd=read&amp;page=' . $r_page .
			'&amp;p=' . $prev . '" ' . $this->root->accesskey . '="7">7.Prev</a>';
	}
	$navi[] = $next . '/' . $pagecount . ' ';
	if ($pageno < $pagecount - 1) {
		$navi[] = '<a href="' . $this->root->script . '?cmd=read&amp;page=' . $r_page .
			'&amp;p=' . $next . '" ' . $this->root->accesskey . '="8">8.Next</a>';
	}
}

$navi = join(' | ', $navi);

// ----
// Output HTTP headers
$this->pkwk_headers_sent();
if(TRUE) {
	// Force Shift JIS encode for Japanese embedded browsers and devices
	header('Content-Type: text/html; charset=Shift_JIS');
	$title = mb_convert_encoding($title, 'SJIS', $this->cont['SOURCE_ENCODING']);
	$body  = mb_convert_encoding($body,  'SJIS', $this->cont['SOURCE_ENCODING']);
} else {
	header('Content-Type: text/html; charset=' . $this->cont['CONTENT_CHARSET']);
}

// Output
?><html><head><title><?php
	echo $title
?></title></head><body><?php
	echo $navi
?><hr><?php
	echo $body
?></body></html>
