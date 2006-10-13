<?php

eval( '

function '.$mydirname.'_global_search( $keywords , $andor , $limit , $offset , $userid )
{
	return wraps_global_search_base( "'.$mydirname.'" , $keywords , $andor , $limit , $offset , $userid ) ;
}

' ) ;


if( ! function_exists( 'wraps_global_search_base' ) ) {

function xpwiki_global_search_base( $mydirname , $keywords , $andor , $limit , $offset , $userid )
{
	// not implemented for uid specifications
	if( ! empty( $userid ) ) {
		return array() ;
	}

	$db =& Database::getInstance() ;

	// XOOPS Search module
	$showcontext = empty( $_GET['showcontext'] ) ? 0 : 1 ;
	$select4con = $showcontext ? "`body` AS text" : "'' AS text" ;

	if( is_array( $keywords ) && count( $keywords ) > 0 ) {
		switch( strtolower( $andor ) ) {
			case "and" :
				$whr = "" ;
				foreach( $keywords as $keyword ) {
					$whr .= "`body` LIKE '%$keyword%' AND " ;
				}
				$whr .= "1" ;
				break ;
			case "or" :
				$whr = "" ;
				foreach( $keywords as $keyword ) {
					$whr .= "`body` LIKE '%$keyword%' OR " ;
				}
				$whr .= "0" ;
				break ;
			default :
				$whr = "`body` LIKE '%{$keywords[0]}%'" ;
				break ;
		}
	} else {
		$whr = 1 ;
	}

	$sql = "SELECT `filename`,`title`,`mtime`,$select4con FROM ".$db->prefix( $mydirname."_indexes WHERE ($whr) ORDER BY 1" ) ;
	$result = $db->query( $sql , $limit , $offset ) ;
	$ret = array() ;
	$context = '' ;
	while( list( $filename , $title , $mtime , $text ) = $db->fetchRow( $result ) ) {

		// get context for module "search"
		if( function_exists( 'search_make_context' ) && $showcontext ) {
			$full_context = strip_tags( $text ) ;
			if( function_exists( 'easiestml' ) ) $full_context = easiestml( $full_context ) ;
			$context = search_make_context( $full_context , $keywords ) ;
		}

		$ret[] = array(
			"image" => "" ,
			"link" => "index.php/$filename" ,
			"title" => $title ,
			"time" => $mtime ,
			"uid" => "0" ,
			"context" => $context
		) ;
	}

	return $ret ;
}

}


?>