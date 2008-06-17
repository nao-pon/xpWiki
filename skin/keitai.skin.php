<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: keitai.skin.php,v 1.6 2008/06/17 00:21:39 nao-pon Exp $
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
$dirname = $this->root->mydirname;

// ----
// Modify
$heads = array();

if ($page_comments_count) {
	$heads[] = $page_comments_count;
}

if ($page_comments) {
	$body .= '<hr>' . $page_comments;
}

if ($heads) {
	$body ='[ ' . join(' ', $heads) . ' ]<hr>' . $body;;
}

// Ignore &dagger;s
$body = preg_replace('#<a[^>]+>' . preg_quote($this->root->_symbol_anchor, '#') . '</a>#S', '', $body);

// ----
// Top navigation (text) bar
$_empty = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
$navi = '';
$navi .= '<a href="'.$this->cont['ROOT_URL'].'" name="h" ' . $this->root->accesskey . '="1">'.$this->make_link('&pb1;').'Home</a>';
$navi .= ' <a href="#xpwiki_navigator" ' . $this->root->accesskey . '="2">'.$this->make_link('&pb2;').'Head</a>';
$navi .= ' <a href="' . $link['top']  . '" ' . $this->root->accesskey . '="3">'.$this->make_link('&pb3;').'Top&nbsp;</a>';

$navi .= '<br>';

$navi .= $_empty;
$navi .= ' <a href="' . $this->root->script . '?' . rawurlencode($this->root->menubar) . '" ' . $this->root->accesskey . '="5">'.$this->make_link('&pb5;').'Menu</a>';
$navi .= ' ' . $_empty;

$navi .= '<br>';

if ($rw) {
	$navi .= '<a href="' . $link['new']  . '" ' . $this->root->accesskey . '="7">'.$this->make_link('&pb7;').'New&nbsp;</a>';
	if (!$is_freeze && $is_editable) {
		$navi .= ' <a href="' . $link['edit'] . '" ' . $this->root->accesskey . '="8">'.$this->make_link('&pb8;').'Edit</a>';
	} else {
		$navi .= ' ' . $_empty;
	}
	if ($is_read && $this->root->function_freeze) {
		if (! $is_freeze) {
			$navi .= ' <a href="' . $link['freeze']   . '" ' . $this->root->accesskey . '="9">'.$this->make_link('&pb9;').'Frez</a>';
		} else {
			$navi .= ' <a href="' . $link['unfreeze'] . '" ' . $this->root->accesskey . '="9">'.$this->make_link('&pb9;').'Ufrz</a>';
		}
	} else {
		$navi .= ' ' . $_empty;
	}
}

$navi .= '<br>';

$navi .= '<a href="' . $link['search'] . '" ' . $this->root->accesskey . '="*">*]Srch</a>';
$navi .= ' <a href="' . $link['recent'] . '" ' . $this->root->accesskey . '="0">'.$this->make_link('&pb0;').'Rect</a>';
if ($is_read) {
	$navi .= ' <a href="' . $link['diff'] . '" ' . $this->root->accesskey . '="#">'.$this->make_link('&pb#;').'Diff</a>';
} else {
	$navi .= ' ' . $_empty;
}
$navi = '<center>' . $navi . '</center>';

$topicpath = '';
if (!$is_top) {
   $topicpath = '<div style="font-size:0.9em">' . $this->do_plugin_inline('topicpath','',$_dum) . '</div><hr>';
}

$head = '<head><title>' . mb_convert_encoding($title, 'SJIS', $this->cont['SOURCE_ENCODING']) . '</title></head>';
$header = '<div id="' . $dirname . '_navigator">' . $navi . '</div><hr>' . $topicpath;
$footer = '';

if (HypCommonFunc::get_version() >= '20080617') {
	HypCommonFunc::loadClass('HypKTaiRender');
	$r = new HypKTaiRender();
	$r->set_myRoot($this->root->siteinfo['host']);
	$r->contents['header'] = $header;
	$r->contents['body'] = $body;
	$r->contents['footer'] = $footer;
	$r->doOptimize();
	$body = $r->outputBody;
	
	$r = NULL;
	unset($r);
} else {
	$body = '"keitai.skin" require HypCommonFunc >= 20080617';
}

$out = '<html>' . $head . '<body>' .  $body . '</body></html>';

// ----
// Output HTTP headers
$this->pkwk_headers_sent();
// Force Shift JIS encode for Japanese embedded browsers and devices
header('Content-Type: text/html; charset=Shift_JIS');
header('Content-Length: ' . strlen($out));

// Output
echo $out;
