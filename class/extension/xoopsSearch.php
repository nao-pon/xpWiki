<?php
//
// Created on 2006/11/27 by nao-pon http://hypweb.net/
// $Id: xoopsSearch.php,v 1.1 2006/11/28 00:15:59 nao-pon Exp $
//
class XpWikiExtension_xoopsSearch extends XpWikiExtension {

// $this->xpwiki : Parent XpWiki object.
// $this->root   : Global variable.
// $this->cont   : Constant.
// $this->func   : XpWiki functions.

	function get ($keywords , $andor , $limit , $offset , $userid) {

		// for XOOPS Search module
		$showcontext = empty( $_GET['showcontext'] ) ? 0 : 1 ;

		$where_readable = $this->func->get_readable_where('p.');
		$where = "p.editedtime != 0 AND p.name NOT LIKE ':config/%' AND p.name != ':RenameLog'";
		if ($where_readable) {
			$where = "$where AND ($where_readable)";
		}
		
		$sql = "SELECT p.pgid,p.name,p.editedtime,p.title,p.uid FROM ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." p LEFT JOIN ".$this->xpwiki->db->prefix($this->root->mydirname."_plain")." t ON t.pgid=p.pgid WHERE ($where) ";
		if ( $userid != 0 ) {
			$sql .= "AND (p.uid=".$userid.") ";
		}

		if ( is_array($keywords) && $keywords ) {
			// 英数字は半角,カタカナは全角,ひらがなはカタカナに
			$sql .= "AND (";
			$i = 0;
			foreach ($keywords as $keyword) {
				if ($i++ !== 0) $sql .= " $andor ";
				if (function_exists("mb_convert_kana"))
				{
					// 英数字は半角,カタカナは全角,ひらがなはカタカナに
					$word = addslashes(mb_convert_kana($keyword,'aKCV'));
				} else {
					$word = addslashes($keyword);
				}
				$sql .= "(p.name LIKE '%{$word}%' OR t.plain LIKE '%{$word}%')";
			}
			$sql .= ") ";
		}
		$sql .= "ORDER BY p.editedtime DESC";
		
		//exit($sql);
		$result = $this->xpwiki->db->query($sql,$limit,$offset);
		
		$ret = array();
		
		if (!$keywords) $keywords = array();
		$sword = rawurlencode(join(' ',$keywords));
		
		$context = '' ;
		$make_context_func = function_exists( 'search_make_context' )? 'search_make_context' : (function_exists( 'xoops_make_context' )? 'xoops_make_context' : '');

		while($myrow = $this->xpwiki->db->fetchArray($result)) {
			// get context for module "search"
			if( $make_context_func && $showcontext ) {
	
				$pobj = new XpWiki($this->root->mydirname);
				$pobj->init($myrow['name']);
				$pobj->root->rtf['use_cache_always'] = TRUE;
				$pobj->execute();
				$text = $pobj->body;
	
				$full_context = strip_tags( $text ) ;
				if( function_exists( 'easiestml' ) ) $full_context = easiestml( $full_context ) ;
				$context = $make_context_func( $full_context , $keywords ) ;
			}

			$title = ($myrow['title'])? ' ['.$myrow['title'].']' : '';
			$ret[] = array(
				'link'    => 'index.php?' . rawurlencode($myrow['name']) . '&amp;word=' . $sword,
				'title'   => htmlspecialchars($myrow['name'].$title, ENT_QUOTES),
				'image'   => '',
				'time'    => $myrow['editedtime'],
				'uid'     => $myrow['uid'],
				'page'    => $myrow['name'],
				'context' => $context );
		}
		return $ret;
	}
}

?>