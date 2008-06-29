<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: keitai.skin.php,v 1.9 2008/06/29 23:50:57 nao-pon Exp $
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

$pagename = (isset($this->root->vars['page']))? $this->root->vars['page'] : '';
$pageno = (isset($this->root->vars['p']) && is_numeric($this->root->vars['p'])) ? $this->root->vars['p'] : 0;
$edit = (isset($this->root->vars['cmd']) && $this->root->vars['cmd'] === 'edit') ||
	(isset($this->root->vars['plugin']) && $this->root->vars['plugin'] === 'edit');
$read = (isset($this->root->vars['cmd']) && $this->root->vars['cmd'] === 'read') ||
	(isset($this->root->vars['plugin']) && $this->root->vars['plugin'] === 'read');
$this->root->max_size = $this->root->max_size * 1024 - 500; // Make 500bytes spare for HTTP Header & Pageing navi.
$link = $_LINK;
$lang = $_LANG['skin'];
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
	$body ='[ ' . join(' ', $heads) . ' ]<hr>' . $body;
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
$navi .= ' <a href="' . $this->root->script . '?cmd=menu&amp;refer=' . rawurlencode($pagename) . '" ' . $this->root->accesskey . '="5">'.$this->make_link('&pb5;').'Menu</a>';
$navi .= ' ' . $_empty;

$navi .= '<br>';

if ($rw) {
	$navi .= '<a href="' . $link['new']  . '" ' . $this->root->accesskey . '="7">'.$this->make_link('&pb7;').'New&nbsp;</a>';
} else {
	$navi .= $_empty;
}
if (!$is_freeze && $is_editable) {
	$navi .= ' <a href="' . $link['edit'] . '" ' . $this->root->accesskey . '="8">'.$this->make_link('&pb8;').'Edit</a>';
} else {
	$navi .= ' ' . $_empty;
}
$navi .= ' <a href="' . $link['related'] . '" ' . $this->root->accesskey . '="9">'.$this->make_link('&pb9;').'Rel </a>';

$navi .= '<br>';

$navi .= '<a href="' . $link['search'] . '" ' . $this->root->accesskey . '="*">*]Srch</a>';
$navi .= ' <a href="' . $link['recent'] . '" ' . $this->root->accesskey . '="0">'.$this->make_link('&pb0;').'Rect</a>';
$navi .= ' <a href="' . $link['list'] . '" ' . $this->root->accesskey . '="#">'.$this->make_link('&pb#;').'List</a>';

$navi = '<center>' . $navi . '</center>';

$topicpath = '';
if (!$is_top) {
   $topicpath = '<div style="font-size:0.9em">' . $this->do_plugin_inline('topicpath','',$_dum) . '</div><hr>';
}

$head = '<head><title>' . mb_convert_encoding($title, 'SJIS', $this->cont['SOURCE_ENCODING']) . '</title></head>';
$header = '<div id="' . $dirname . '_navigator">' . $navi . '</div>';
$header .= $this->do_plugin_convert('easylogin');
$header .= '<hr>' . $topicpath;

$footnotes = '<hr>';
if ($notes) {
	$footnotes = '<div>' . $notes . '</div><hr>';
}
// Build footer
ob_start(); ?>
<div style="font-size:0.9em">
<?php echo $footnotes ?>
<?php if ($is_page) echo $this->do_plugin_convert('counter') ?>
<?php if ($lastmodified != '') { ?>
<div><?php echo $lang['lastmodify'] ?>: <?php echo $lastmodified ?> by <?php echo $pginfo['lastuname'] ?></div>
<?php } ?>
<?php if ($is_page) { ?>
<div><?php echo $lang['pagealias'] ?>: <?php echo $pginfo['alias'] ?></div>
<div><?php echo $lang['pageowner'] ?>: <?php echo $pginfo['uname'] ?></div>
<?php } ?>
<div><?php echo $lang['siteadmin'] ?>: <a href="<?php echo $this->root->modifierlink ?>"><?php echo $this->root->modifier ?></a></div>
</div>
<?php
$footer = ob_get_contents();
ob_end_clean();

if (HypCommonFunc::get_version() >= '20080617.2') {
	HypCommonFunc::loadClass('HypKTaiRender');
	$r = new HypKTaiRender();
	$r->set_myRoot($this->root->siteinfo['host']);
	$r->contents['header'] = $header;
	$r->contents['body'] = $body;
	$r->contents['footer'] = $footer;
	$r->Config_redirect = $this->cont['HOME_URL'] . 'gate.php?way=redirect_SJIS&amp;xmode=2&amp;l=';
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
