<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: keitai.skin.php,v 1.25 2009/01/25 00:49:50 nao-pon Exp $
// Copyright (C) 2003-2006 PukiWiki Developers Team
// License: GPL v2 or (at your option) any later version
//
// Skin for Embedded devices

// ----
// Prohibit direct access
if (! isset($this->cont['UI_LANG'])) die('UI_LANG is not set');


$style = array(
	'siteTitle' => 'text-align:center;background-color:#A6DDAF;font-size:large',
	'easyLogin' => 'text-align:center;background-color:#DBBCA6;font-size:small',
	'wikiTitle' => 'text-align:center;background-color:#A6D5DB;font-size:large',
	'pageTitle' => 'text-align:center;background-color:#EAFFCC',
	'pageMenu'  => 'background-color:#CED9DB;font-size:small',
	'pageFooter'=> 'background-color:#CED9DB;font-size:small',
	'pageInfo'  => 'background-color:#EAFFCC;font-size:small',
);
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
	$body ='<div style="text-align:right;font-size:x-small">[ ' . join(' ', $heads) . ' ]</div><hr>' . $body;
}

// Ignore _symbol_anchor
$body = preg_replace('#<a[^>]+?>' . preg_quote($this->root->_symbol_anchor, '#') . '</a>#S', '', $body);
$body = preg_replace('/<a href="#'.$this->root->mydirname.'_navigator"[^>]*?>.+?<\/a>/sS', '', $body);

$body = str_replace($this->root->_symbol_noexists, '<span style="font-size:xx-small">((i:f9be))</span>', $body);

$head = '<head><title>' . mb_convert_encoding($title, 'SJIS', $this->cont['SOURCE_ENCODING']) . '</title></head>';

$header = '';
$header .= sprintf('<div style="%s" id="header">%s <a href="%s" %s="1">%s</a></div>',
	$style['siteTitle'],
	$this->make_link('&pb1;'),
	$this->cont['ROOT_URL'],
	$this->root->accesskey,
	htmlspecialchars($this->root->siteinfo['sitename']) );

$header .= sprintf('<div style="%s">%s</div>',
	$style['easyLogin'],
	$this->do_plugin_convert('easylogin') );

$header .= sprintf('<div style="%s">%s <a href="%s" %s="3">%s</a><a href="%s">%s</a></div>',
	$style['wikiTitle'],
	$this->make_link('&pb3;'),
	$link['top'],
	$this->root->accesskey,
	htmlspecialchars($this->root->module['title']),
	$link['rss'],
	'((e:f699))' );

if ($read && $pagename) {
	$pageTitle = $this->make_pagelink($pagename) . '<a href="' . $link['related'] . '">((i:f981))</a>';
} else {
	$pageTitle = strip_tags($this->xpwiki->title);
}
$header .= sprintf('<div style="%s">%s</div>',
	$style['pageTitle'],
	$pageTitle );

$header .= '<div style="' . $style['pageMenu'] . '">';
$header .= '<table align="center"><tr><td>';
$header .= '<div style="' . $style['pageMenu'] . '">';

$header .= sprintf('%s <a href="#header" %s="2">%s</a><br />',
$this->make_link('&pb2;'),
$this->root->accesskey,
$lang['header']
);

$header .= sprintf('%s <a href="#footer" %s="8">%s</a><br />',
$this->make_link('&pb8;'),
$this->root->accesskey,
$lang['footer']
);

if ($pagename) {
	$header .= sprintf('%s <a href="%s?cmd=menu&amp;refer=%s" %s="5">%s</a><br />',
	$this->make_link('&pb5;'),
	$this->root->script,
	rawurlencode($pagename),
	$this->root->accesskey,
	$lang['menu']
	);
} else {
	$header .= '<br />';
}

if (!$is_freeze && $is_editable) {
	$header .= sprintf('%s <a href="%s" %s="9">%s</a><br />',
	$this->make_link('&pb9;'),
	$link['edit'],
	$this->root->accesskey,
	$lang['edit']
	);
} else {
	$header .= '<br />';
}
$header .= '</div>';
$header .= '</td><td style="background-color:#fff"> </td><td>';
$header .= '<div style="' . $style['pageMenu'] . ';text-align:right">';

$header .= sprintf('<a href="%s" %s="7">%s</a> %s<br />',
$link['new'],
$this->root->accesskey,
$lang['new'],
$this->make_link('&pb7;') );

$header .= sprintf('<a href="%s" %s="*">%s</a> %s<br />',
$link['search'],
$this->root->accesskey,
$lang['search_s'],
'[*]' );

$header .= sprintf('<a href="%s" %s="0">%s</a> %s<br />',
$link['recent'],
$this->root->accesskey,
$lang['recent_s'],
$this->make_link('&pb0;') );

$header .= sprintf('<a href="%s" %s="#">%s</a> %s<br />',
$link['list'],
$this->root->accesskey,
$lang['list'],
$this->make_link('&pb#;') );

$header .= '</div>';
$header .= '</td></tr></table>';
$header .= '</div>';

$footnotes = '<hr />';
if ($notes) {
	$footnotes = '<div>' . $notes . '</div><hr>';
}

// page info
$pageinfo = '';
if ($is_page) {
	$pageinfo = <<<EOD
<div style="{$style['pageInfo']}">
<h4>{$lang['pageinfo']}</h4>
{$lang['pagename']} : $_page<br />
{$lang['pagealias']} : {$pginfo['alias']}<br />
{$lang['pageowner']} : {$pginfo['pageowner']}
<h4>{$lang['readable']}</h4>
{$lang['groups']} : {$pginfo['readableGroups']}<br />
{$lang['users']} : {$pginfo['readableUsers']}
<h4>{$lang['editable']}</h4>
{$lang['groups']} : {$pginfo['editableGroups']}<br />
{$lang['users']} : {$pginfo['editableUsers']}
</div>
EOD;
}


// Build footer
ob_start(); ?>
<div style="<?php echo $style['pageFooter'] ?>" id="footer">
<?php echo $footnotes ?>
<?php if ($is_page) echo $this->do_plugin_convert('counter') ?>
<?php if ($lastmodified != '') { ?>
<div><?php echo $lang['lastmodify'] ?>: <?php echo $lastmodified ?> by <?php echo $pginfo['lastuname'] ?></div>
<?php } ?>
<p><?php echo $lang['siteadmin'] ?>: <a href="<?php echo $this->root->modifierlink ?>"><?php echo $this->root->modifier ?></a></p>
</div>
<?php
$footer = ob_get_contents();
ob_end_clean();

$ctype = 'text/html';
if (HypCommonFunc::get_version() >= '20080617.2') {
	HypCommonFunc::loadClass('HypKTaiRender');
	if (HypCommonFunc::get_version() < '20080925') {
		$r = new HypKTaiRender();
	} else {
		$r =& HypKTaiRender::getSingleton();
	}
	$r->set_myRoot($this->root->siteinfo['host']);
	$r->Config_hypCommonURL = $this->cont['ROOT_URL'] . 'class/hyp_common';
	$r->Config_redirect = $this->root->k_tai_conf['redirect'];
	$r->Config_emojiDir = $this->cont['ROOT_URL'] . 'images/emoji';
	if (! empty($this->root->k_tai_conf['showImgHosts'])) {
		$r->Config_showImgHosts = $this->root->k_tai_conf['showImgHosts'];
	}
	if (! empty($this->root->k_tai_conf['directImgHosts'])) {
		$r->Config_directImgHosts = $this->root->k_tai_conf['directImgHosts'];
	}
	if (! empty($this->root->k_tai_conf['directLinkHosts'])) {
		$r->Config_directLinkHosts = $this->root->k_tai_conf['directLinkHosts'];
	}
	if ($this->cont['PKWK_ENCODING_HINT']) {
		$r->Config_encodeHintWord = $this->cont['PKWK_ENCODING_HINT'];
	}

	if (! empty($this->root->k_tai_conf['googleAdsense']['config'])) {
		$r->Config_googleAdSenseConfig = $this->root->k_tai_conf['googleAdsense']['config'];
		$r->Config_googleAdSenseBelow = $this->root->k_tai_conf['googleAdsense']['below'];
	}

	$googleAnalytics = '';
	if ($this->root->k_tai_conf['googleAnalyticsId']) {
		$googleAnalytics = $r->googleAnalyticsGetImgTag($this->root->k_tai_conf['googleAnalyticsId'], $title);
	}

	$r->inputEncode = $this->cont['SOURCE_ENCODING'];
	$r->outputEncode = 'SJIS';
	$r->outputMode = 'xhtml';
	$r->langcode = $this->cont['LANG'];

	if (! empty($_SESSION['hyp_redirect_message'])){
		$header = $this->root->k_tai_conf['rebuilds']['redirectMessage']['above'] . $_SESSION['hyp_redirect_message'] . $this->root->k_tai_conf['rebuilds']['redirectMessage']['below'] . $header;
		unset($_SESSION['hyp_redirect_message']);
	}

	$r->contents['header'] = $header . $googleAnalytics;
	$r->contents['body'] = $body . $pageinfo;
	$r->contents['footer'] = $footer;

	$r->doOptimize();
	if (method_exists($r, 'getHtmlDeclaration')) {
		$htmlDec = $r->getHtmlDeclaration();
	} else {
		$htmlDec = '<?xml version="1.0" encoding="Shift_JIS"?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">';
	}
	$body = $r->outputBody;
	$ctype = $r->getOutputContentType();
	$r = NULL;
	unset($r);
} else {
	$body = '"keitai.skin" require HypCommonFunc >= 20080617';
}

$out = $htmlDec . $head . '<body>' .  $body . '</body></html>';

// ----
// Output HTTP headers
$this->pkwk_headers_sent();
// Force Shift JIS encode for Japanese embedded browsers and devices
header('Content-Type: '.$ctype.'; charset=Shift_JIS');
header('Content-Length: ' . strlen($out));
header('Cache-Control: no-cache');

// Output
echo $out;
