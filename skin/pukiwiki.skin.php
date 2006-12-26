<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: pukiwiki.skin.php,v 1.15 2006/12/26 00:10:47 nao-pon Exp $
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
if (! isset($this->cont['PKWK_SKIN_SHOW$toolbar']))
	$this->cont['PKWK_SKIN_SHOW$toolbar'] = 1; // 1, 0

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

<div id="navigator">

<?php if($this->cont['PKWK_SKIN_SHOW_NAVBAR']) { ?>

<div class="navigator_wiki">
 [
 <?php if ($rw) { ?>
	<?php $navigator($this,'new') ?> |
 <?php } ?>
   <?php $navigator($this,'list') ?>
 <?php if ($this->arg_check('list')) { ?>
	| <?php $navigator($this,'filelist') ?>
 <?php } ?>
 | <?php $navigator($this,'search') ?>
 | <?php $navigator($this,'recent') ?>
 | <?php $navigator($this,'help')   ?>
 ]
</div><!--/navigator_wiki-->

<div id="header">

 <h1 class="title"><?php echo $page ?></h1>

<?php if ($is_page) { ?>
 <?php if($this->cont['SKIN_DEFAULT_DISABLE_TOPICPATH']) { ?>
   <a href="<?php echo $link['reload'] ?>"><span class="small"><?php echo $link['reload'] ?></span></a>
 <?php } else if (!$is_top) { ?>
   <span class="small">
   <?php echo $this->do_plugin_inline('topicpath','',$_dum); ?>
   </span>
 <?php } ?>
<?php } ?>

</div>

<div class="navigator_page">
<?php if ($is_page) { ?>
 [
 <?php if ($rw) { ?>
	<?php if (!$is_freeze && $is_editable) { ?>
		<?php $navigator($this,'edit') ?> |
	<?php } ?>
	<?php if ($is_read && $this->root->function_freeze) { ?>
		<?php (! $is_freeze) ? $navigator($this,'freeze') : $navigator($this,'unfreeze') ?> |
	<?php } ?>
	<?php if ($is_owner) { ?>
		<?php $navigator($this,'pginfo') ?> |
	<?php } ?>
 <?php } ?>
 <?php $navigator($this,'diff') ?>
 <?php if ($this->root->do_backup) { ?>
	| <?php $navigator($this,'backup') ?>
 <?php } ?>
 <?php if ($rw && (bool)ini_get('file_uploads')) { ?>
	| <?php $navigator($this,'upload') ?>
 <?php } ?>
 | <?php $navigator($this,'reload') ?>
 ] &nbsp;
<?php } else { ?>
 [ <?php $navigator($this, 'top')?> ]
<?php } ?>
</div><!--/navigator_page-->

<hr style="clear: both;" />

<div class="navigator_info">
<?php if ($this->root->trackback) { ?>
 [ <?php $navigator($this,'trackback', $lang['trackback'] . '(' . $this->tb_count($_page) . ')',
 	($trackback_javascript == 1) ? 'onclick="OpenTrackback(this.href); return false"' : '') ?> ]
<?php } ?>
<?php if ($this->root->referer)   { ?> &nbsp;
 [ <?php $navigator($this,'refer') ?> ]
<?php } ?>
<?php if ($page_comments_count)   { ?> &nbsp;
 [ <?php echo $page_comments_count ?> ]
<?php } ?>
</div><!--/navigator_info-->

<?php } // PKWK_SKIN_SHOW_NAVBAR ?>
</div><!--/#navigator-->


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

<?php if ($page_comments) { ?>
<?php echo $this->root->hr ?>
<div class="commentbody"><?php echo $page_comments ?></div>
<?php } ?>

<?php echo $this->root->hr ?>

<?php if ($this->cont['PKWK_SKIN_SHOW$toolbar']) { ?>
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
$this->root->_IMAGE['skin']['rss']      = 'feed-rss.png';
$this->root->_IMAGE['skin']['rss10']    = 'feed-rss1.png';
$this->root->_IMAGE['skin']['rss20']    = 'feed-rss2.png';
$this->root->_IMAGE['skin']['atom']     = 'feed-atom.png';
$this->root->_IMAGE['skin']['rdf']      = 'rdf.png';

?>
 <?php $toolbar($this, 'top') ?>

<?php if ($is_page) { ?>
 &nbsp;
 <?php if ($rw) { ?>
 	<?php if (!$is_freeze && $is_editable) { ?>
		<?php $toolbar($this, 'edit') ?>
	<?php } ?>
	<?php if ($is_read && $this->root->function_freeze) { ?>
		<?php if (! $is_freeze) { $toolbar($this, 'freeze'); } else { $toolbar($this, 'unfreeze'); } ?>
	<?php } ?>
 <?php } ?>
 <?php $toolbar($this, 'diff') ?>
<?php if ($this->root->do_backup) { ?>
	<?php $toolbar($this, 'backup') ?>
<?php } ?>
<?php if ($rw) { ?>
	<?php if ((bool)ini_get('file_uploads')) { ?>
		<?php $toolbar($this, 'upload') ?>
	<?php } ?>
	<?php $toolbar($this, 'copy') ?>
	<?php $toolbar($this, 'rename') ?>
<?php } ?>
 <?php $toolbar($this, 'reload') ?>
<?php } ?>
 &nbsp;
<?php if ($rw) { ?>
	<?php $toolbar($this, 'new') ?>
<?php } ?>
 <?php $toolbar($this, 'list')   ?>
 <?php $toolbar($this, 'search') ?>
 <?php $toolbar($this, 'recent') ?>
 &nbsp; <?php $toolbar($this, 'help') ?>
 &nbsp; <?php $toolbar($this, 'rss10', 14, 14) ?>
 <?php $toolbar($this, 'rss20', 14, 14) ?>
 <?php $toolbar($this, 'atom', 14, 14) ?>
</div>
<?php } // PKWK_SKIN_SHOW$toolbar ?>

<?php if ($is_page) echo $this->do_plugin_convert('counter') ?>

<?php if ($lastmodified != '') { ?>
<div id="lastmodified">Last-modified: <?php echo $lastmodified ?> by <?php echo $pginfo['lastuname'] ?></div>
<?php } ?>

<?php if ($related != '') { ?>
<div id="related">Link: <?php echo $related ?></div>
<?php } ?>

<div id="footer">
 <div>Page owner: <?php echo $pginfo['uname'] ?></div>
 <div>Site admin: <a href="<?php echo $this->root->modifierlink ?>"><?php echo $this->root->modifier ?></a></div>
 <?php echo $this->cont['S_COPYRIGHT'] ?>.
 Powered by PHP <?php echo PHP_VERSION ?>. HTML convert time: <?php echo $taketime ?> sec.
</div>
</div>
