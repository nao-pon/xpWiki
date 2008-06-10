<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: keitai.skin.php,v 1.4 2008/06/10 00:28:29 nao-pon Exp $
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
$edit = (isset($this->root->vars['cmd']) && $this->root->vars['cmd'] === 'edit') ||
	(isset($this->root->vars['plugin']) && $this->root->vars['plugin'] === 'edit');
$read = (isset($this->root->vars['cmd']) && $this->root->vars['cmd'] === 'read') ||
	(isset($this->root->vars['plugin']) && $this->root->vars['plugin'] === 'read');
$this->root->max_size = $this->root->max_size * 1024 - 500; // Make 500bytes spare for HTTP Header & Pageing navi.
$link = $_LINK;
$rw = ! $this->cont['PKWK_READONLY'];

// ----
// Modify

// Ignore &dagger;s
$body = preg_replace('#<a[^>]+>' . preg_quote($this->root->_symbol_anchor, '#') . '</a>#S', '', $body);

// For shows inline image by "ref"
$body = preg_replace('#(<div[^>]+>)?((<a[^>]+>)?<)img([^>]*) class="m"([^>]*>(?(3)</a>))(?(1)</div>)#iS', '$2pic$4$5', $body);

// Shrink IMG tags (= images) with character strings
// With ALT option
$body = preg_replace('#(<div[^>]+>)?(<a[^>]+>)?<img[^>]*alt="([^"]+)"[^>]*>(?(2)(</a>))(?(1)</div>)#iS', '[$2$3$4]', $body);
// Without ALT option
$body = preg_replace('#(<div[^>]+>)?(<a[^>]+>)?<img[^>]+>(?(2)(</a>))(?(1)</div>)#iS', '[$2img$3]', $body);
$body = str_replace('[img]', '', $body);

// Reformat IMG tags
$body = str_replace('<pic', '<img', $body);

// Remove etc.
if (HypCommonFunc::get_version() >= 20080609) $body = HypCommonFunc::html_diet_for_hp($body, $this->root->siteinfo['host'], $this->cont['SOURCE_ENCODING']);

// ----
// Top navigation (text) bar

$navi = array();
$navi[] = '<a href="#h" name="h" ' . $this->root->accesskey . '="0">0:Top</a>';
//$navi[] = '<a href="' . $link['top']  . '" ' . $this->root->accesskey . '="1">1:Home</a>';
$navi[] = '<a href="' . $this->root->script . '?' . rawurlencode($this->root->menubar) . '" ' . $this->root->accesskey . '="1">1:Menu</a>';
if ($rw) {
	$navi[] = '<a href="' . $link['new']  . '" ' . $this->root->accesskey . '="2">2.New</a>';
	if (!$is_freeze && $is_editable) $navi[] = '<a href="' . $link['edit'] . '" ' . $this->root->accesskey . '="3">3:Edit</a>';
	if ($is_read && $this->root->function_freeze) {
		if (! $is_freeze) {
			$navi[] = '<a href="' . $link['freeze']   . '" ' . $this->root->accesskey . '="4">4:Frez</a>';
		} else {
			$navi[] = '<a href="' . $link['unfreeze'] . '" ' . $this->root->accesskey . '="4">4:Ufrz</a>';
		}
	}
}
if ($is_read) $navi[] = '<a href="' . $link['diff'] . '" ' . $this->root->accesskey . '="5">5:Diff</a>';
$navi[] = '<a href="' . $link['recent'] . '" ' . $this->root->accesskey . '="6">6:Rect</a>';
$navi[] = '<a href="' . $link['search'] . '" ' . $this->root->accesskey . '="7">7:Srch</a>';

$navi = join(' | ', $navi);

$topicpath = '';
if (!$is_top) {
   $topicpath = $this->do_plugin_inline('topicpath','',$_dum) . '<hr>';
}

$header = '<html><head><title>' . $title . '</title></head><body>' . $navi . '$h_navi<hr>' . $topicpath;
$footer = '$f_navi</body></html>';

if (HypCommonFunc::get_version() >= 20080609) {
	$header = HypCommonFunc::html_diet_for_hp($header, $this->root->siteinfo['host'], $this->cont['SOURCE_ENCODING']);
	$footer = HypCommonFunc::html_diet_for_hp($footer, $this->root->siteinfo['host'], $this->cont['SOURCE_ENCODING']);
}

$extra_len = strlen($header.$footer);

// To Shift-JIS
$body = mb_convert_encoding($body, 'SJIS', $this->cont['SOURCE_ENCODING']);

$h_navi = $f_navi = '';

// Get one page
if (strlen($body) > $this->root->max_size) {
	if (HypCommonFunc::get_version() >= 20080609) {
		$bodys = HypCommonFunc::html_split($body, ($this->root->max_size - $extra_len), 'SJIS');
		$body = $bodys[$pageno];
		$pagecount = count($bodys);
	} else {
		$body = substr($body, $pageno * ($this->root->max_size - $extra_len), ($this->root->max_size - $extra_len));
		$pagecount = ceil(strlen($body) / $this->root->max_size);
	}
	
	// Previous / Next block
	$pager = array();

	if ($read) {
		$base = '?cmd=read&amp;page=' . $r_page;
	} else {
		$querys = array();
		foreach($_GET as $key => $val) {
			if ($key !== 'p') {
				$querys[] = $key . (($val !== '') ? '=' . rawurlencode($val) : '');
			}
		}
		$base = '?' . join('&amp;', $querys);
	}
	$prev = $pageno - 1;
	$next = $pageno + 1;
	if ($pageno > 0) {
		if ($prev > 0) {
			$pager[] = '<a href="' . $base . '">|&lt;</a>';
		}
		$pager[] = '<a href="' . $base .
			(($prev > 0)? '&amp;p=' . $prev : '') . '" ' . $this->root->accesskey . '="*">*&lt;</a>';
	}
	$pager[] = $next . '/' . $pagecount . ' ';
	if ($pageno < $pagecount - 1) {
		$pager[] = '<a href="' . $base .
			'&amp;p=' . $next . '" ' . $this->root->accesskey . '="#">#&gt;</a>';
		if ($pageno < $pagecount - 2) {
			$pager[] = '<a href="' . $base . '&amp;p=' . ($pagecount - 1) . '">&gt;|</a>';
		}
	}

	$pager = '<center>' . join(' ', $pager) . '</center>';
	$h_navi = '<br>' . $pager;
	$f_navi = '<hr>' . $pager;

}

// Replace h_navi & f_navi
$header = str_replace('$h_navi', $h_navi, $header);
$footer = str_replace('$f_navi', $f_navi, $footer);

// To Shift-JIS
$header = mb_convert_encoding($header, 'SJIS', $this->cont['SOURCE_ENCODING']);
$footer = mb_convert_encoding($footer, 'SJIS', $this->cont['SOURCE_ENCODING']);

$out = $header . $body . $footer;

// ----
// Output HTTP headers
$this->pkwk_headers_sent();
// Force Shift JIS encode for Japanese embedded browsers and devices
header('Content-Type: text/html; charset=Shift_JIS');
header('Content-Length: ' . strlen($out));

// Output
echo $out;
