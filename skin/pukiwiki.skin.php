<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: pukiwiki.skin.php,v 1.8 2006/11/14 01:14:45 nao-pon Exp $
// Copyright (C)
//   2002-2006 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// PukiWiki default skin

// ------------------------------------------------------------
// Settings (define before here, if you want)

// Set site identities
$_IMAGE['skin']['logo']     = 'pukiwiki.png';
$_IMAGE['skin']['favicon']  = ''; // Sample: 'image/favicon.ico';

// SKIN_DEFAULT_DISABLE_TOPICPATH
//   1 = Show reload URL
//   0 = Show topicpath
if (! isset($this->cont['SKIN_DEFAULT_DISABLE_TOPICPATH']))
	$this->cont['SKIN_DEFAULT_DISABLE_TOPICPATH'] = 0; // 1, 0

// Show / Hide navigation bar UI at your choice
// NOTE: This is not stop their functionalities!
if (! isset($this->cont['PKWK_SKIN_SHOW_NAVBAR']))
	$this->cont['PKWK_SKIN_SHOW_NAVBAR'] = 1; // 1, 0

// Show / Hide toolbar UI at your choice
// NOTE: This is not stop their functionalities!
if (! isset($this->cont['PKWK_SKIN_SHOW_TOOLBAR']))
	$this->cont['PKWK_SKIN_SHOW_TOOLBAR'] = 1; // 1, 0

// ------------------------------------------------------------
// Code start

// Prohibit direct access
//if (! isset($this->cont['UI_LANG'])) die('UI_LANG is not set');
//if (! isset($this->root->_LANG)) die('$_LANG is not set');
//if (! isset($this->cont['PKWK_READONLY'])) die('PKWK_READONLY is not set');

$lang  = & $_LANG['skin'];
$link  = & $_LINK;
$image = & $_IMAGE['skin'];
$rw    = ! $this->cont['PKWK_READONLY'];

// Decide charset for CSS
$css_charset = 'iso-8859-1';
switch($this->cont['UI_LANG']){
	case 'ja': $css_charset = 'Shift_JIS'; break;
}

// ------------------------------------------------------------
// Output
/*
// HTTP headers
//$this->root->pkwk_common_headers();
header('Cache-control: no-cache');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=' . $this->cont['CONTENT_CHARSET']);

// HTML DTD, <html>, and receive content-type
if (isset($pkwk_dtd)) {
	$meta_content_type = pkwk_output_dtd($pkwk_dtd);
} else {
	$meta_content_type = pkwk_output_dtd();
}
*/

$favicon = ($image['favicon'])? "<link rel=\"SHORTCUT ICON\" href=\"{$image['favicon']}\" />" : "";
$dirname = $this->root->mydirname;

$this->root->html_header = <<<EOD
$favicon
$head_pre_tag
<link rel="stylesheet" type="text/css" media="screen" href="{$this->cont['HOME_URL']}{$this->cont['SKIN_DIR']}pukiwiki.css.php?charset={$css_charset}&amp;base={$dirname}" charset="{$css_charset}" />
<link rel="stylesheet" type="text/css" media="print"  href="{$this->cont['HOME_URL']}{$this->cont['SKIN_DIR']}pukiwiki.css.php?charset={$css_charset}&amp;base={$dirname}&amp;media=print" charset="{$css_charset}" />
<link rel="alternate" type="application/rss+xml" title="RSS" href="{$link['rss']}" />
$head_tag
EOD;
?>

<div class="xpwiki_<?php echo $dirname ?>">
<div id="header">
 <a href="<?php echo $link['top'] ?>"><img id="logo" name="logo" src="<?php echo $this->cont['IMAGE_DIR'] . $image['logo'] ?>" width="80" height="80" alt="[PukiWiki]" title="[PukiWiki]" /></a>

 <h1 class="title"><?php echo $page ?></h1>

<?php if ($is_page) { ?>
 <?php if($this->cont['SKIN_DEFAULT_DISABLE_TOPICPATH']) { ?>
   <a href="<?php echo $link['reload'] ?>"><span class="small"><?php echo $link['reload'] ?></span></a>
 <?php } else if (!$is_top) { ?>
   <span class="small">
   <?php $_plugin = $this->get_plugin_instance("topicpath");echo $_plugin->plugin_topicpath_inline();?>
   </span>
 <?php } ?>
<?php } ?>

</div>

<div id="navigator">
<?php if($this->cont['PKWK_SKIN_SHOW_NAVBAR']) { ?>
<?php
if (!function_exists("_navigator")){
function _navigator($this, $key, $value = '', $javascript = ''){
	$lang = & $this->root->_LANG['skin'];
	$link = & $this->root->_LINK;
	if (! isset($lang[$key])) { echo 'LANG NOT FOUND'; return FALSE; }
	if (! isset($link[$key])) { echo 'LINK NOT FOUND'; return FALSE; }
	if (! $this->cont['PKWK_ALLOW_JAVASCRIPT']) $javascript = '';

	echo '<a href="' . $link[$key] . '" ' . $javascript . '>' .
		(($value === '') ? $lang[$key] : $value) .
		'</a>';

	return TRUE;
}
}
?>
 [ <?php _navigator($this,'top') ?> ] &nbsp;

<?php if ($is_page) { ?>
 [
 <?php if ($rw) { ?>
	<?php if (!$is_freeze && $is_editable) { ?>
		<?php _navigator($this,'edit') ?> |
	<?php } ?>
	<?php if ($is_read && $this->root->function_freeze) { ?>
		<?php (! $is_freeze) ? _navigator($this,'freeze') : _navigator($this,'unfreeze') ?> |
	<?php } ?>
	<?php if ($is_owner) { ?>
		<?php _navigator($this,'pginfo') ?> |
	<?php } ?>
 <?php } ?>
 <?php _navigator($this,'diff') ?>
 <?php if ($this->root->do_backup) { ?>
	| <?php _navigator($this,'backup') ?>
 <?php } ?>
 <?php if ($rw && (bool)ini_get('file_uploads')) { ?>
	| <?php _navigator($this,'upload') ?>
 <?php } ?>
 | <?php _navigator($this,'reload') ?>
 ] &nbsp;
<?php } ?>

 [
 <?php if ($rw) { ?>
	<?php _navigator($this,'new') ?> |
 <?php } ?>
   <?php _navigator($this,'list') ?>
 <?php if ($this->arg_check('list')) { ?>
	| <?php _navigator($this,'filelist') ?>
 <?php } ?>
 | <?php _navigator($this,'search') ?>
 | <?php _navigator($this,'recent') ?>
 | <?php _navigator($this,'help')   ?>
 ]

<?php if ($this->root->trackback) { ?> &nbsp;
 [ <?php _navigator($this,'trackback', $lang['trackback'] . '(' . $this->tb_count($_page) . ')',
 	($trackback_javascript == 1) ? 'onclick="OpenTrackback(this.href); return false"' : '') ?> ]
<?php } ?>
<?php if ($this->root->referer)   { ?> &nbsp;
 [ <?php _navigator($this,'refer') ?> ]
<?php } ?>
<?php } // PKWK_SKIN_SHOW_NAVBAR ?>
</div>

<?php echo $this->root->hr ?>

<?php if ($this->arg_check('read') && $this->exist_plugin_convert('menu') && $this->root->show_menu_bar) { ?>
<table border="0" style="width:100%">
 <tr>
  <td class="menubar">
   <div id="menubar"><?php echo $this->do_plugin_convert('menu') ?></div>
  </td>
  <td valign="top">
   <div id="body"><?php echo $body ?></div>
  </td>
 </tr>
</table>
<?php } else { ?>
<div id="body"><?php echo $body ?></div>
<?php } ?>

<?php if ($notes != '') { ?>
<div id="note"><?php echo $notes ?></div>
<?php } ?>

<?php if ($attaches != '') { ?>
<div id="attach">
<?php echo $this->root->hr ?>
<?php echo $attaches ?>
</div>
<?php } ?>

<?php echo $this->root->hr ?>

<?php if ($this->cont['PKWK_SKIN_SHOW_TOOLBAR']) { ?>
<!-- Toolbar -->
<div id="toolbar">
<?php

// Set toolbar-specific images
$this->root->_IMAGE['skin']['reload']   = 'reload.png';
$this->root->_IMAGE['skin']['new']      = 'new.png';
$this->root->_IMAGE['skin']['edit']     = 'edit.png';
$this->root->_IMAGE['skin']['freeze']   = 'freeze.png';
$this->root->_IMAGE['skin']['unfreeze'] = 'unfreeze.png';
$this->root->_IMAGE['skin']['diff']     = 'diff.png';
$this->root->_IMAGE['skin']['upload']   = 'file.png';
$this->root->_IMAGE['skin']['copy']     = 'copy.png';
$this->root->_IMAGE['skin']['rename']   = 'rename.png';
$this->root->_IMAGE['skin']['top']      = 'top.png';
$this->root->_IMAGE['skin']['list']     = 'list.png';
$this->root->_IMAGE['skin']['search']   = 'search.png';
$this->root->_IMAGE['skin']['recent']   = 'recentchanges.png';
$this->root->_IMAGE['skin']['backup']   = 'backup.png';
$this->root->_IMAGE['skin']['help']     = 'help.png';
$this->root->_IMAGE['skin']['rss']      = 'rss.png';
$this->root->_IMAGE['skin']['rss10']    = & $this->root->_IMAGE['skin']['rss'];
$this->root->_IMAGE['skin']['rss20']    = 'rss20.png';
$this->root->_IMAGE['skin']['rdf']      = 'rdf.png';

if (!function_exists("_toolbar")){
function _toolbar($this, $key, $x = 20, $y = 20){
	$lang  = & $this->root->_LANG['skin'];
	$link  = & $this->root->_LINK;
	$image = & $this->root->_IMAGE['skin'];
	if (! isset($lang[$key]) ) { echo $key.'LANG NOT FOUND';  return FALSE; }
	if (! isset($link[$key]) ) { echo 'LINK NOT FOUND';  return FALSE; }
	if (! isset($image[$key])) { echo 'IMAGE NOT FOUND'; return FALSE; }

	echo '<a href="' . $link[$key] . '">' .
		'<img src="' . $this->cont['IMAGE_DIR'] . $image[$key] . '" width="' . $x . '" height="' . $y . '" ' .
			'alt="' . $lang[$key] . '" title="' . $lang[$key] . '" />' .
		'</a>';
	return TRUE;
}
}
?>
 <?php _toolbar($this, 'top') ?>

<?php if ($is_page) { ?>
 &nbsp;
 <?php if ($rw) { ?>
 	<?php if (!$is_freeze && $is_editable) { ?>
		<?php _toolbar($this, 'edit') ?>
	<?php } ?>
	<?php if ($is_read && $this->root->function_freeze) { ?>
		<?php if (! $is_freeze) { _toolbar($this, 'freeze'); } else { _toolbar($this, 'unfreeze'); } ?>
	<?php } ?>
 <?php } ?>
 <?php _toolbar($this, 'diff') ?>
<?php if ($this->root->do_backup) { ?>
	<?php _toolbar($this, 'backup') ?>
<?php } ?>
<?php if ($rw) { ?>
	<?php if ((bool)ini_get('file_uploads')) { ?>
		<?php _toolbar($this, 'upload') ?>
	<?php } ?>
	<?php _toolbar($this, 'copy') ?>
	<?php _toolbar($this, 'rename') ?>
<?php } ?>
 <?php _toolbar($this, 'reload') ?>
<?php } ?>
 &nbsp;
<?php if ($rw) { ?>
	<?php _toolbar($this, 'new') ?>
<?php } ?>
 <?php _toolbar($this, 'list')   ?>
 <?php _toolbar($this, 'search') ?>
 <?php _toolbar($this, 'recent') ?>
 &nbsp; <?php _toolbar($this, 'help') ?>
 &nbsp; <?php _toolbar($this, 'rss10', 36, 14) ?>
</div>
<?php } // PKWK_SKIN_SHOW_TOOLBAR ?>

<?php if ($lastmodified != '') { ?>
<div id="lastmodified">Last-modified: <?php echo $lastmodified ?></div>
<?php } ?>

<?php if ($related != '') { ?>
<div id="related">Link: <?php echo $related ?></div>
<?php } ?>

<div id="footer">
 <p>Site admin: <a href="<?php echo $this->root->modifierlink ?>"><?php echo $this->root->modifier ?></a></p>
 <?php echo $this->cont['S_COPYRIGHT'] ?>.
 Powered by PHP <?php echo PHP_VERSION ?>. HTML convert time: <?php echo $taketime ?> sec.
</div>
</div>
