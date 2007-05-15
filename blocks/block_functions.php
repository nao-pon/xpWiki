<?php

function b_xpwiki_a_page_show( $options )
{
	$mydirname = empty( $options[0] ) ? 'xpwiki' : $options[0] ;
	$page = empty( $options[1] ) ? '' : $options[1] ;
	$width = empty( $options[2] ) ? '100%' : $options[2] ;
	$this_template = empty( $options[3] ) ? 'db:'.$mydirname.'_block_a_page.html' : trim( $options[3] ) ;
	
	if( preg_match( '/[^0-9a-zA-Z_-]/' , $mydirname ) ) die( 'Invalid mydirname' ) ;
	
	// 必要なファイルの読み込み (固定値:変更の必要なし)
	include_once XOOPS_TRUST_PATH."/modules/xpwiki/include.php";
	 
	// インスタンス化 (引数: モジュールディレクトリ名)
	$xw = new XpWiki($mydirname);
	 
	// ブロック用として取得 (引数: ページ名, 表示幅)
	$str = $xw->get_html_for_block ($page, $width);
	 
	// オブジェクトを破棄
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
	$page = empty( $options[1] ) ? '' : $options[1] ;
	$width = empty( $options[2] ) ? '100%' : $options[2] ;
	$this_template = empty( $options[3] ) ? 'db:'.$mydirname.'_block_a_page.html' : trim( $options[3] ) ;

	if( preg_match( '/[^0-9a-zA-Z_-]/' , $mydirname ) ) die( 'Invalid mydirname' ) ;

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
	\n" ;

	return $form;
}

?>