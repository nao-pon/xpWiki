<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: rss.inc.php,v 1.6 2006/12/06 04:15:02 nao-pon Exp $
//
// RSS plugin: Publishing RSS of RecentChanges
//
// Usage: plugin=rss[&ver=[0.91|1.0|2.0]] (Default: 0.91)
//
// NOTE for acronyms
//   RSS 0.9,  1.0  : RSS means 'RDF Site Summary'
//   RSS 0.91, 0.92 : RSS means 'Rich Site Summary'
//   RSS 2.0        : RSS means 'Really Simple Syndication' (born from RSS 0.92)

class xpwiki_plugin_rss extends xpwiki_plugin {
	function plugin_rss_init () {

	}
	
	function plugin_rss_action()
	{
		$version = isset($this->root->vars['ver']) ? $this->root->vars['ver'] : '';
		$base = isset($this->root->vars['p']) ? $this->root->vars['p'] : '';
		$s_base = $base ? '/' . htmlspecialchars($base) : '';
		switch($version){
		case '':  $version = '1.0'; break; // Default
		case '1': $version = '1.0';  break; // Sugar
		case '2': $version = '2.0';  break; // Sugar
		case '0.91': /* FALLTHROUGH */
		case '1.0' : /* FALLTHROUGH */
		case '2.0' : break;
		default: die('Invalid RSS version!!');
		}
		
		// キャッシュファイル名
		$c_file = $this->cont['CACHE_DIR'] . 'plugin/' . md5($version.$base) . $this->cont['UI_LANG'] . '.rss';

		// 念のためバッファをクリア
		while( ob_get_level() ) { ob_end_clean() ; }
		
		if (file_exists($c_file)) {
			$filetime = filemtime($c_file);
			$etag = md5($c_file.$filetime);
					
			if ($etag === @$_SERVER["HTTP_IF_NONE_MATCH"]) {
				header( "HTTP/1.1 304 Not Modified" );
				header( "Etag: ". $etag );
				header('Cache-Control: private');
				header('Pragma:');
				//header('Expires:');
				exit();
			}
			
			$out = join('', file($c_file));

		} else {
			// バッファリング
			ob_start();
					
			$lang = $this->cont['LANG'];
			$page_title_utf8 = $this->root->siteinfo['sitename'] . '::' . $this->root->page_title . $s_base;
			$self = $this->func->get_script_uri();
			$maketime = $date = substr_replace($this->func->get_date('Y-m-d\TH:i:sO'), ':', -2, 0);
			$rss_css = $this->cont['HOME_URL'] . 'skin/loader.php?type=xml&amp;src=rss.' . $this->cont['UI_LANG'];
		
			// Creating <item>
			$items = $rdf_li = '';
			
			// ゲスト扱いで一覧を取得
			$_userinfo = $this->root->userinfo;
			$this->root->userinfo['admin'] = FALSE;
			$this->root->userinfo['uid'] = 0;
			$this->root->userinfo['uname'] = '';
			$this->root->userinfo['uname_s'] = '';
			$this->root->userinfo['gids'] = array();
			
			$lines = $this->func->get_existpages(FALSE, ($base ? $base . '/' : ''), array('limit' => $this->root->rss_max, 'order' => ' ORDER BY editedtime DESC', 'nolisting' => TRUE, 'withtime' =>TRUE));
			
			$this->root->userinfo = $_userinfo;
			
			foreach ($lines as $line) {
				list($time, $page) = explode("\t", rtrim($line));
				$r_page = rawurlencode($page);
				$title  = $page;
		
				switch ($version) {
				case '0.91': /* FALLTHROUGH */
				case '2.0':
					$date = $this->func->get_date('D, d M Y H:i:s T', $time);
					$date = ($version == '0.91') ?
						' <description>' . $date . '</description>' :
						' <pubDate>'     . $date . '</pubDate>';
					$items .= <<<EOD
<item>
 <title>$title</title>
 <link>$self?$r_page</link>
$date
</item>

EOD;
					break;
		
				case '1.0':
					// Add <item> into <items>
					$rdf_li .= '    <rdf:li rdf:resource="' . $self .
					'?' . $r_page . '" />' . "\n";
		
					$date = substr_replace($this->func->get_date('Y-m-d\TH:i:sO', $time), ':', -2, 0);
					
					// 追加情報取得
					$added = $this->func->get_page_changes($page);
					// form script embed object 削除
					$added = preg_replace('#<(form|script|embed|object).+?/\\1>#is', '',$added);
					// タグ中の class, id, name 属性を指定を削除
					$added = preg_replace('/(<[^>]*)\s+(?:class|id|name)=[^\s>]+([^>]*>)/', '$1$2', $added);
					
					// 指定ページの本文取得
					$a_page = new XpWiki($this->root->mydirname);
					$a_page->init($page);
					$a_page->root->rtf['use_cache_always'] = TRUE;
					$a_page->execute();
					$html = $a_page->body;
					// form script embed object 削除
					$html = preg_replace('#<(form|script|embed|object).+?/\\1>#is', '',$html);
					// タグ中の class, id, name 属性を指定を削除
					$html = preg_replace('/(<[^>]*)\s+(?:class|id|name)=[^\s>]+([^>]*>)/', '$1$2', $html);
					
					$description = strip_tags(($added ? $added . '&#182;' : '') . $html);
					$description = preg_replace('/(\s+|&'.$this->root->entity_pattern.';)/i', '', $description);
					$description = mb_substr($description, 0, 250);
					
					if ($added) $html = '<dl><dt>Changes</dt><dd>' . $added . '</dd></dl><hr />' . $html;
					
					$trackback_ping = '';
					if ($this->root->trackback) {
						$tb_id = md5($r_page);
						$trackback_ping = ' <trackback:ping>' . $self .
						'?tb_id=' . $tb_id . '</trackback:ping>';
					}
					$items .= <<<EOD
<item rdf:about="$self?$r_page">
 <title>$title</title>
 <link>$self?$r_page</link>
 <description>$description</description>
 <content:encoded><![CDATA[
 $html
 ]]></content:encoded>
 <dc:date>$date</dc:date>
 <dc:identifier>$self?$r_page</dc:identifier>
$trackback_ping
</item>

EOD;
					break;
				}
			}
		
			// Feeding start
			print '<?xml version="1.0" encoding="UTF-8"?>' . "\n\n";
		
			//$r_whatsnew = rawurlencode($this->root->whatsnew);
			$link = $base? $self . '?' . rawurlencode($base) : $self;
			
			switch ($version) {
			case '0.91':
				print '<!DOCTYPE rss PUBLIC "-//Netscape Communications//DTD RSS 0.91//EN"' .
			' "http://my.netscape.com/publish/formats/rss-0.91.dtd">' . "\n";
				 /* FALLTHROUGH */
		
			case '2.0':
				print <<<EOD
<rss version="$version">
 <channel>
  <title>$page_title_utf8</title>
  <link>$link</link>
  <description>xpWiki RecentChanges</description>
  <language>$lang</language>

$items
 </channel>
</rss>
EOD;
				break;
		
			case '1.0':
				$xmlns_trackback = $this->root->trackback ?
					'  xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/"' : '';
				print <<<EOD
<?xml-stylesheet type="text/xsl" media="screen" href="{$rss_css}" ?>
<rdf:RDF
  xmlns:dc="http://purl.org/dc/elements/1.1/"
$xmlns_trackback
  xmlns="http://purl.org/rss/1.0/"
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xml:lang="$lang">
 <channel rdf:about="$self?$r_whatsnew">
  <title>$page_title_utf8</title>
  <link>$link</link>
  <description>xpWiki RecentChanges</description>
  <dc:date>$maketime</dc:date>
  <items>
   <rdf:Seq>
$rdf_li
   </rdf:Seq>
  </items>
 </channel>

$items
</rdf:RDF>
EOD;
				break;
			}
			$out = mb_convert_encoding(ob_get_contents(), 'UTF-8', $this->cont['CONTENT_CHARSET']);
			ob_end_clean();
			
			//キャッシュ書き込み
			if ($fp = @fopen($c_file,"wb"))
			{
				fputs($fp, $out);
				fclose($fp);
			}
			$filetime = filemtime($c_file);
			$etag = md5($c_file.$filetime);
		}
		//$this->func->pkwk_common_headers();
		header('Content-Type: application/xml; charset=utf-8');
		header('Content-Length: ' . strlen($out));
		header('Cache-Control: private');
		header('Pragma:');
		//header('Expires:');
		header('Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $filetime ) . ' GMT' );
		header('Etag: '. $etag );
		echo $out;
		exit;
	}
}
?>