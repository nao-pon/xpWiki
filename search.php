<?php

eval( '

function '. $mydirname .'_global_search( $keywords , $andor , $limit , $offset , $userid )
{
	// for XOOPS Search module
	static $readed = FALSE;
	if($readed) { return array() ; }
	$readed = TRUE;
	return xpwiki_global_search_base( "'.$mydirname.'" , $keywords , $andor , $limit , $offset , $userid ) ;
}

' ) ;


if( ! function_exists( 'xpwiki_global_search_base' ) ) {

function xpwiki_global_search_base( $mydirname , $keywords , $andor , $limit , $offset , $userid )
{
	// not implemented for uid specifications
	if( ! empty( $userid ) ) {
		return array() ;
	}

	include_once dirname( __FILE__ ) . '/include.php';

	$xpwiki = new XpWiki($mydirname);
	$xpwiki->init();
	
	// for XOOPS Search module
	$showcontext = empty( $_GET['showcontext'] ) ? 0 : 1 ;

	if( is_array( $keywords ) && count( $keywords ) > 0 ) {
		$word = join(' ',$keywords);
	} else {
		$word = '';
	}
	//echo $word;
	$results = $xpwiki->func->do_search($word, strtoupper($andor), TRUE);
	
	rsort($results);
	$results = array_splice($results, $offset, $limit);
	
	$ret = array() ;
	$context = '' ;
	$make_context_func = function_exists( 'search_make_context' )? 'search_make_context' : (function_exists( 'xoops_make_context' )? 'xoops_make_context' : '');
	foreach($results as $page) {

		// get context for module "search"
		if( $make_context_func && $showcontext ) {

			$pobj = new XpWiki($mydirname);
			$pobj->init($page);
			$pobj->root->rtf['use_cache_always'] = TRUE;
			$pobj->execute();
			$text = $pobj->body;

			$full_context = strip_tags( $text ) ;
			if( function_exists( 'easiestml' ) ) $full_context = easiestml( $full_context ) ;
			$context = $make_context_func( $full_context , $keywords ) ;
		}

		$ret[] = array(
			"image" => "" ,
			"link" => 'index.php?' . rawurlencode($page) . '&amp;word=' . rawurlencode($word), 
			"title" => htmlspecialchars($page) ,
			"time" => $xpwiki->func->get_filetime($page) ,
			"uid" => "0" ,
			"context" => $context
		) ;
	}

	return $ret ;
}

}


?>