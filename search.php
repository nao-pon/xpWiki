<?php

eval( '

function '. $mydirname .'_global_search( $keywords , $andor , $limit , $offset , $userid )
{
	// for XOOPS Search module
	static $readed = array();
	$md5 = md5($keywords . $andor . $limit . $offset . $userid);
	if(isset($readed[$md5])) { return array() ; }
	$readed[$md5] = TRUE;
	return xpwiki_global_search_base( "'.$mydirname.'" , $keywords , $andor , $limit , $offset , $userid ) ;
}

' ) ;


if( ! function_exists( 'xpwiki_global_search_base' ) ) {

function xpwiki_global_search_base( $mydirname , $keywords , $andor , $limit , $offset , $userid )
{
	
	static $xpwiki = array();
	
	if (empty($xpwiki[$mydirname])) {
		// 必要なファイルの読み込み
		$mytrustdirpath = dirname( __FILE__ ) ;
		include_once "$mytrustdirpath/include.php";
		
		// XpWiki オブジェクト作成
		$xpwiki[$mydirname] = new XpWiki($mydirname);
		
		// xoopsSearch extension 読み込み
		$xpwiki[$mydirname]->load_extensions("xoopsSearch");
		
		// 初期化
		$xpwiki[$mydirname]->init();
	}
	
	// データ取得
	return $xpwiki[$mydirname]->extension->xoopsSearch->get ( $keywords , $andor , $limit , $offset , $userid );
}

}


?>