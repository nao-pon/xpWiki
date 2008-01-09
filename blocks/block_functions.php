<?php
function b_xpwiki_notification_show( $options )
{
	$mydirname = empty( $options[0] ) ? 'xpwiki' : $options[0] ;
	if( preg_match( '/[^0-9a-zA-Z_-]/' , $mydirname ) ) die( 'Invalid mydirname' ) ;
	
	$pgid = (!empty($_GET['pgid']))? intval($_GET['pgid']) : 0;
	
	if (!isset($GLOBALS['Xpwiki_'.$mydirname]) || !$pgid) return false;
	
	$this_template = empty( $options[1] ) ? 'db:'.$mydirname.'_block_notification.html' : trim( $options[1] ) ;
	
	include_once XOOPS_TRUST_PATH."/modules/xpwiki/include.php";
	$xw = XpWiki::getSingleton($mydirname);
	$xw->init('#RenderMode');
	
	$notification = $xw->func->get_notification_select($pgid);

	if ($notification) {
		$block = array( 
			'mydirname' => $mydirname ,
			'mod_url' => XOOPS_URL.'/modules/'.$mydirname ,
			'content'  => $notification ,
		) ;
		require_once XOOPS_ROOT_PATH.'/class/template.php' ;
		$tpl =& new XoopsTpl() ;
		$tpl->assign( 'block' , $block ) ;
		$ret['content'] = $tpl->fetch( $this_template ) ;
	} else {
		$ret = false;
	}
	return $ret;
}

function b_xpwiki_notification_edit( $options )
{
	$mydirname = empty( $options[0] ) ? 'xpwiki' : $options[0] ;
	if( preg_match( '/[^0-9a-zA-Z_-]/' , $mydirname ) ) die( 'Invalid mydirname' ) ;

	$this_template = empty( $options[1] ) ? 'db:'.$mydirname.'_block_notification.html' : trim( $options[3] ) ;

	$form = "
		<input type='hidden' name='options[0]' value='$mydirname' />
		<label for='this_template'>"._MB_XPWIKI_THISTEMPLATE."</label>&nbsp;:
		<input type='text' size='60' name='options[3]' id='this_template' value='".htmlspecialchars($this_template,ENT_QUOTES)."' />
		<br />
	\n" ;

	return $form;
}

function b_xpwiki_a_page_show( $options )
{
	$mydirname = empty( $options[0] ) ? 'xpwiki' : $options[0] ;
	$page = empty( $options[1] ) ? '' : $options[1] ;
	$width = empty( $options[2] ) ? '100%' : $options[2] ;
	$this_template = empty( $options[3] ) ? 'db:'.$mydirname.'_block_a_page.html' : trim( $options[3] ) ;
	$div_class = empty( $options[4] ) ? 'xpwiki_b_' . $mydirname : $options[4];
	$css = isset( $options[5] ) ? $options[5] : 'main.css';
	$disabled_pagecache = empty($options[6])? false : true;
	$configs = array();
	
	if( preg_match( '/[^0-9a-zA-Z_-]/' , $mydirname ) ) die( 'Invalid mydirname' ) ;
	
	// 必要なファイルの読み込み (固定値:変更の必要なし)
	include_once XOOPS_TRUST_PATH."/modules/xpwiki/include.php";
	 
	// インスタンス化 (引数: モジュールディレクトリ名)
	$xw = new XpWiki($mydirname);
	
	// ページキャッシュを常に無効にする?
	if ($disabled_pagecache) {
		$configs['root']['pagecache_min'] = 0;
	}
	
	// ブロック用として取得 (引数: ページ名, 表示幅)
	$str = $xw->get_html_for_block($page, $width, $div_class, $css, $configs);
	 
	// オブジェクトを破棄
	$xw = null;
	unset($xw); 

	$constpref = '_MB_' . strtoupper( $mydirname ) ;

	$block = array( 
		'mydirname' => $mydirname ,
		'mod_url' => XOOPS_URL.'/modules/'.$mydirname ,
		'pagename' => $page ,
		'content'  => $str ,
	) ;

	$tpl =& new XoopsTpl() ;
	$tpl->assign( 'block' , $block ) ;
	$ret['content'] = $tpl->fetch( $this_template ) ;
	return $ret ;
}

function b_xpwiki_a_page_edit( $options )
{
	$mydirname = empty( $options[0] ) ? 'xpwiki' : $options[0] ;
	if( preg_match( '/[^0-9a-zA-Z_-]/' , $mydirname ) ) die( 'Invalid mydirname' ) ;

	$page = empty( $options[1] ) ? '' : $options[1] ;
	$width = empty( $options[2] ) ? '100%' : $options[2] ;
	$this_template = empty( $options[3] ) ? 'db:'.$mydirname.'_block_a_page.html' : trim( $options[3] ) ;
	$div_class = empty( $options[4] ) ? 'xpwiki_b_' . $mydirname : trim( $options[4] );
	$css = isset( $options[5] ) ? trim( $options[5] ) : 'main.css';
	$disabled_pagecache = empty($options[6])? 1 : 0;
	$check_pagecache = array('', '');
	$check_pagecache[$disabled_pagecache] = ' checked="checked"';
	
	$form = "
		<input type='hidden' name='options[0]' value='$mydirname' />
		<label for='pagename'>"._MB_XPWIKI_PAGENAME."</label>&nbsp;:
		<input type='text' size='20' name='options[1]' id='pagename' value='".$page."' />
		<br />
		<label for='blockwidth'>"._MB_XPWIKI_WIDTH."</label>&nbsp;:
		<input type='text' size='20' name='options[2]' id='blockwidth' value='".$width."' />
		<br />
		<label for='this_template'>"._MB_XPWIKI_THISTEMPLATE."</label>&nbsp;:
		<input type='text' size='60' name='options[3]' id='this_template' value='".htmlspecialchars($this_template,ENT_QUOTES)."' />
		<br />
		<label for='divclass'>"._MB_XPWIKI_DIVCLASS."</label>&nbsp;:
		<input type='text' size='30' name='options[4]' id='divclass' value='".htmlspecialchars($div_class,ENT_QUOTES)."' />
		<br />
		<label for='this_css'>"._MB_XPWIKI_THISCSS."</label>&nbsp;:
		<input type='text' size='30' name='options[5]' id='this_css' value='".htmlspecialchars($css,ENT_QUOTES)."' />
		<br />
		<label>"._MB_XPWIKI_DISABLEDPAGECACHE."</label>&nbsp;:
		<input type='radio' name='options[6]' value='1'{$check_pagecache[0]} />Yes &nbsp; <input type='radio' name='options[6]' value='0'{$check_pagecache[1]} />No
		<br />
		\n" ;
	return $form;
}

?>