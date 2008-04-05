<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: amazon.inc.php,v 1.7 2008/04/05 04:53:11 nao-pon Exp $
// Id: amazon.inc.php,v 1.1 2003/07/24 13:00:00 閑舎
//
// Amazon plugin: Book-review maker via amazon.com/amazon.jp
//
// Copyright:
//	2004-2005 PukiWiki Developers Team
//	2003 閑舎 <raku@rakunet.org> (Original author)
//
// License: GNU/GPL
//
// ChangeLog:
// * 2004/04/03 PukiWiki Developer Team (arino <arino@users.sourceforge.jp>)
//        - replace plugin_amazon_get_page().
//        - PLUGIN_AMAZON_XML 'xml.amazon.com' -> 'xml.amazon.co.jp'
// * 0.6  URL が存在しない場合、No image を表示、画像配置など修正。
//        インラインプラグインの呼び出し方を修正。
//	  ASIN 番号部分をチェックする。
//	  画像、タイトルのキャッシュによる速度の大幅アップ。
// * 0.7  ブックレビュー生成のデバッグ、認証問題の一応のクリア。
// * 0.8  amazon 全商品の画像を表示。
//	  アソシエイト ID に対応。
// * 0.9  RedHat9+php4.3.2+apache2.0.46 で画像が途中までしか読み込まれない問題に対処。
//        日本語ページの下にブックレビューを作ろうとすると文字化けして作れない問題の解決。
//        書籍でなく CD など、ASIN 部分が長くてもタイトルをうまく拾うようにする。
//        写影のみ取り込むのでなければ、B000002G6J.01 と書かず B000002G6J と書いても写影が出るようにする。
//	  ASIN に対応するキャッシュ画像/キャッシュタイトルをそれぞれ削除する機能追加。
//	  proxy 対応(試験的)。
//	  proxy 実装の過程で一般ユーザのための AID はなくとも自動生成されることがわかり、削除した。
// * 1.0  ブックレビューでなく、レビューとする。
//        画像のキャッシュを削除する期限を設ける。
//        タイトル、写影を Web Services の XML アクセスの方法によって get することで時間を短縮する。
//        レビューページ生成のタイミングについて注を入れる。
// * 1.1  編集制限をかけている場合、部外者がレビューを作ろうとして、ページはできないが ASIN4774110655.tit などのキャッシュができるのを解決。
//        画像の最後が 01 の場合、image を削除すると noimage.jpg となってしまうバグを修正。
//        1.0 で導入した XML アクセスは高速だが、返す画像情報がウソなので、09 がだめなら 01 をトライする、で暫定的に解決。
//
// Caution!:
// * 著作権が関連する為、www.amazon.co.jp のアソシエイトプログラムを確認の上ご利用下さい。
// * レビューは、amazon プラグインが呼び出す編集画面はもう出来て PukiWiki に登録されているので、
//   中止するなら全文を削除してページの更新ボタンを押すこと。
// * 下の PLUGIN_AMAZON_AID、PROXY サーバの部分、expire の部分を適当に編集して使用してください(他はそのままでも Ok)。
//
// Thanks to: Reimy and PukiWiki Developers Team
//
class xpwiki_plugin_amazon extends xpwiki_plugin {
	
	/////////////////////////////////////////////////
	
	function plugin_amazon_init()
	{
		/////////////////////////////////////////////////
		// Settings
		
		// Amazon associate ID
		$this->cont['PLUGIN_AMAZON_AID'] = '';
	
		// Expire caches per ? days
		$this->cont['PLUGIN_AMAZON_EXPIRE_IMAGECACHE'] =    1;
		$this->cont['PLUGIN_AMAZON_EXPIRE_TITLECACHE'] =  356;
	
		// Alternative image for 'Image not found'
		$this->cont['PLUGIN_AMAZON_NO_IMAGE'] =  $this->cont['IMAGE_DIR'] . 'noimage.png';
	
		// URI prefixes
		switch($this->cont['LANG']){
		case 'ja':
			// Amazon shop
			$this->cont['PLUGIN_AMAZON_SHOP_URI'] =  'http://www.amazon.co.jp/exec/obidos/ASIN/';
	
			break;
		default:
			// Amazon shop
			$this->cont['PLUGIN_AMAZON_SHOP_URI'] =  'http://www.amazon.com/exec/obidos/ASIN/';
	
			break;
		}

		if ($this->cont['PLUGIN_AMAZON_AID'] == '') {
			$this->root->amazon_aid = '';
		} else {
			$this->root->amazon_aid = $this->cont['PLUGIN_AMAZON_AID'] . '/';
		}
		$this->root->amazon_body = <<<EOD
-作者: [[ここ編集のこと]]
-評者: お名前
-日付: &date;
**お薦め対象
[[ここ編集のこと]]

#amazon(,clear)
**感想
[[ここ編集のこと]]

// まず、このレビューを止める場合、全文を削除し、ページの[更新ボタン]を押してください！(PukiWiki にはもう登録されています)
// 続けるなら、上の、[[ここ編集のこと]]部分を括弧を含めて削除し、書き直してください。
// お名前、部分はご自分の名前に変更してください。私だと、閑舎、です。
// **お薦め対象、より上は、新しい行を追加しないでください。目次作成に使用するので。
// //で始まるコメント行は、最終的に全部カットしてください。目次が正常に作成できない可能性があります。
#comment
EOD;
	}
	
	function plugin_amazon_convert()
	{
		if (HypCommonFunc::get_version() < 20080224) {
			return '#amazon require "HypCommonFunc" >= Ver. 20080224';
		}
		if (func_num_args() > 3) {
			if ($this->cont['PKWK_READONLY']) return ''; // Show nothing
	
			return '#amazon([ASIN-number][,left|,right]' .
			'[,book-title|,image|,delimage|,deltitle|,delete])';
	
		} else if (func_num_args() == 0) {
			// レビュー作成
			if ($this->cont['PKWK_READONLY']) return ''; // Show nothing
	
			$s_page = htmlspecialchars($this->root->vars['page']);
			if ($s_page == '') $s_page = isset($this->root->vars['refer']) ? $this->root->vars['refer'] : '';
			$ret = <<<EOD
<form action="{$this->root->script}" method="post">
 <div>
  <input type="hidden" name="plugin" value="amazon" />
  <input type="hidden" name="refer" value="$s_page" />
  ASIN:
  <input type="text" name="asin" size="30" value="" />
  <input type="submit" value="レビュー編集" /> (ISBN 10 桁 or ASIN 12 桁)
 </div>
</form>
EOD;
			return $ret;
		}
	
		$aryargs = array_pad(func_get_args(),3,"");
	
		$align = strtolower($aryargs[1]);
		if ($align == 'clear') return '<div style="clear:both"></div>'; // 改行挿入
		if ($align != 'left') $align = 'right'; // 配置決定
	
		$this->root->asin_all = htmlspecialchars($aryargs[0]);  // for XSS
		if ($this->is_asin() == FALSE && $align != 'clear') return FALSE;
	
		if ($aryargs[2] != '') {
			// タイトル指定
			$title = $alt = htmlspecialchars($aryargs[2]); // for XSS
			if ($alt == 'image') {
				$alt = $this->plugin_amazon_get_asin_title();
				if ($alt == '') return FALSE;
				$title = '';
			} else if ($alt == 'delimage') {
				if (unlink($this->cont['CACHE_DIR'] . 'ASIN' . $this->root->asin . '.jpg')) {
					return 'Image of ' . $this->root->asin . ' deleted...';
				} else {
					return 'Image of ' . $this->root->asin . ' NOT DELETED...';
				}
			} elseif ($alt == 'deltitle') {
				if (unlink($this->cont['CACHE_DIR'] . 'ASIN' . $this->root->asin . '.tit')) {
					return 'Title of ' . $this->root->asin . ' deleted...';
				} else {
					return 'Title of ' . $this->root->asin . ' NOT DELETED...';
				}
			} elseif ($alt == 'delete') {
				if ((unlink($this->cont['CACHE_DIR'] . 'ASIN' . $this->root->asin . '.jpg') &&
				     unlink($this->cont['CACHE_DIR'] . 'ASIN' . $this->root->asin . '.tit'))) {
					return 'Title and Image of ' . $this->root->asin . ' deleted...';
				} else {
					return 'Title and Image of ' . $this->root->asin . ' NOT DELETED...';
				}
			}
		} else {
			// タイトル自動取得
			$alt = $title = $this->plugin_amazon_get_asin_title();
			if ($alt == '') return FALSE;
		}
	
		return $this->plugin_amazon_print_object($align, $alt, $title);
	}
	
	function plugin_amazon_action()
	{
		if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits editing');
	
		$s_page   = isset($this->root->vars['refer']) ? $this->root->vars['refer'] : '';
		$this->root->asin_all = isset($this->root->vars['asin']) ?
			htmlspecialchars(rawurlencode($this->func->strip_bracket($this->root->vars['asin']))) : '';
	
		if (! $this->is_asin()) {
			$retvars['msg']   = 'ブックレビュー編集';
			$retvars['refer'] = & $s_page;
			$retvars['body']  = $this->plugin_amazon_convert();
			return $retvars;
	
		} else {
			$r_page     = $s_page . '/' . $this->root->asin;
			$auth_user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
	
			$this->func->pkwk_headers_sent();
			if ($this->root->edit_auth && ($auth_user == '' || ! isset($this->root->edit_auth_users[$auth_user]) ||
			    $this->root->edit_auth_users[$auth_user] != $_SERVER['PHP_AUTH_PW'])) {
			   	// Edit-auth failed. Just look the page
				$this->func->send_location($r_page);
			} else {
				$title = $this->plugin_amazon_get_asin_title();
				if ($title == '' || preg_match('#^/#', $s_page)) {
					// Invalid page name
					$this->func->send_location($s_page);
				} else {
					$body = '#amazon(' . $this->root->asin_all . ',,image)' . "\n" .
					'*' . $title . "\n" . $this->root->amazon_body;
					$this->plugin_amazon_review_save($r_page, $body);
					$this->func->send_location('', '', $this->func->get_script_uri() .
					'?cmd=edit&page=' . rawurlencode($r_page));
				}
			}
			exit;
		}
	}
	
	function plugin_amazon_inline()
	{
		if (HypCommonFunc::get_version() < 20080224) {
			return '&amazon require "HypCommonFunc" >= Ver. 20080224';
		}

		list($this->root->asin_all) = func_get_args();
	
		$this->root->asin_all = htmlspecialchars($this->root->asin_all); // for XSS
		if (! $this->is_asin()) return FALSE;
	
		$title = $this->plugin_amazon_get_asin_title();
		if ($title == '') {
			return FALSE;
		} else {
			return '<a href="' . $this->cont['PLUGIN_AMAZON_SHOP_URI'] .
			$this->root->asin . '/' . $this->root->amazon_aid . 'ref=nosim">' . $title . '</a>' . "\n";
		}
	}
	
	function plugin_amazon_print_object($align, $alt, $title)
	{
		$url      = $this->plugin_amazon_cache_image_fetch($this->cont['CACHE_DIR']);
		$url_shop = $this->cont['PLUGIN_AMAZON_SHOP_URI'] . $this->root->asin . '/' . $this->root->amazon_aid . 'ref=nosim';
		$center   = 'text-align:center';
	
		if ($title == '') {
			// Show image only
			$div  = '<div style="float:' . $align . ';margin:16px 16px 16px 16px;' . $center . '">' . "\n";
			$div .= ' <a href="' . $url_shop . '"><img src="' . $url . '" alt="' . $alt . '" /></a>' . "\n";
			$div .= '</div>' . "\n";
	
		} else {
			// Show image and title
			$div  = '<div style="float:' . $align . ';padding:.5em 1.5em .5em 1.5em;' . $center . '">' . "\n";
			$div .= ' <table style="width:110px;border:0;' . $center . '">' . "\n";
			$div .= '  <tr><td style="' . $center . '">' . "\n";
			$div .= '   <a href="' . $url_shop . '"><img src="' . $url . '" alt="' . $alt  .'" /></a></td></tr>' . "\n";
			$div .= '  <tr><td style="' . $center . '"><a href="' . $url_shop . '">' . $title . '</a></td></tr>' . "\n";
			$div .= ' </table>' . "\n";
			$div .= '</div>' . "\n";
		}
		return $div;
	}
	
	function plugin_amazon_get_asin($asin)
	{
		$false = array('', '');
		if (!$asin) return $false;
	
		$nocache = $nocachable = 0;
		
		$cache_dir = $this->cont['CACHE_DIR'] . 'plugin/';
		
		if (file_exists($cache_dir) === FALSE || is_writable($cache_dir) === FALSE) $nocachable = 1; // キャッシュ不可の場合
	
		if ($dat = $this->plugin_amazon_cache_fetch($cache_dir, $asin)) {
			list($title, $image) = $dat;
		} else {
			$nocache = 1; // キャッシュ見つからず

			$ama = new HypSimpleAmazon($this->cont['PLUGIN_AMAZON_AID']);
			$ama->encoding = $this->cont['SOURCE_ENCODING'];
			$ama->itemLookup($this->root->asin);
			$tmpary = $ama->getCompactArray();
			$ama = NULL;
			$title = $tmpary['Items'][0]['TITLE'];
			$image = $tmpary['Items'][0]['MIMG'];
		}
	
		if ($title === '') {
			return $false;
		} else {
			if ($nocache == 1 && $nocachable != 1)
				$this->plugin_amazon_cache_save($title . "\t" . $image, $cache_dir);
			return array($title, $image);
		}
	}

	// キャッシュがあるか調べる
	function plugin_amazon_cache_fetch($dir, $asin)
	{
		$filename = $dir . $asin . '.aws';
	
		$get_tit = 0;
		if (! is_readable($filename)) {
			$get_tit = 1;
		} elseif ($this->cont['PLUGIN_AMAZON_EXPIRE_TITLECACHE'] * 3600 * 24 < $this->cont['UTC'] - filemtime($filename)) {
			$get_tit = 1;
		}
	
		if ($get_tit) return FALSE;
	
		if ($dat = file_get_contents($filename)){
			return explode("\t", $dat);
		} else {
			return FALSE;
		}
	}

	function plugin_amazon_get_asin_title()
	{
		if ($this->root->asin == '') return '';
	
		list($title, $image) = $this->plugin_amazon_get_asin($this->root->asin);
		
		return $title;
	}
	
	function plugin_amazon_cache_save($data, $dir)
	{
		$filename = $dir . $this->root->asin_all . '.aws';
		$fp = fopen($filename, 'w');
		fwrite($fp, $data);
		fclose($fp);
	
		return $filename;
	}
	
	// 画像キャッシュがあるか調べる
	function plugin_amazon_cache_image_fetch($dir)
	{
		$filename = $dir . 'ASIN' . $this->root->asin . '.jpg';
	
		$get_img = 0;
		if (! is_readable($filename)) {
			$get_img = 1;
		} elseif ($this->cont['PLUGIN_AMAZON_EXPIRE_IMAGECACHE'] * 3600 * 24 < $this->cont['UTC'] - filemtime($filename)) {
			$get_img = 1;
		}
	
		if ($get_img) {
			list($title, $url) = $this->plugin_amazon_get_asin($this->root->asin);
	
			$body = $url? $this->plugin_amazon_get_page($url) : '';
			if ($body != '') {
				$tmpfile = $dir . 'ASIN' . $this->root->asin . '.jpg.0';
				$fp = fopen($tmpfile, 'wb');
				fwrite($fp, $body);
				fclose($fp);
				$size = getimagesize($tmpfile);
				unlink($tmpfile);
			}
			if ($body == '' || $size[1] <= 1) { // 通常は1が返るが念のため0の場合も(reimy)
				// キャッシュを PLUGIN_AMAZON_NO_IMAGE のコピーとする
				$fp = fopen($this->cont['PLUGIN_AMAZON_NO_IMAGE'], 'rb');
				if (! $fp) return FALSE;
				
				$body = '';
				while (! feof($fp)) $body .= fread($fp, 4096);
				fclose ($fp);
			}
			$this->plugin_amazon_cache_image_save($body, $this->cont['CACHE_DIR']);
		}
		return str_replace($this->cont["DATA_HOME"], $this->cont["HOME_URL"], $filename);
	}
	
	// Save image cache
	function plugin_amazon_cache_image_save($data, $dir)
	{
		$filename = $dir . 'ASIN' . $this->root->asin . '.jpg';
		$fp = fopen($filename, 'wb');
		fwrite($fp, $data);
		fclose($fp);
	
		return $filename;
	}
	
	// Save book data
	function plugin_amazon_review_save($page, $data)
	{
		$filename = $this->cont['DATA_DIR'] . $this->func->encode($page) . '.txt';
		if (! is_readable($filename)) {
			//$fp = fopen($filename, 'w');
			//fwrite($fp, $data);
			//fclose($fp);
			$this->func->page_write($page, $data);
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	function plugin_amazon_get_page($url)
	{
		$data = $this->func->http_request($url);
		return ($data['rc'] == 200) ? $data['data'] : '';
	}
	
	// is ASIN?
	function is_asin()
	{
		include_once XOOPS_TRUST_PATH . '/class/hyp_common/hsamazon/hyp_simple_amazon.php';
		$ama = new HypSimpleAmazon();
		$this->root->asin = $ama->ISBN2ASIN($this->root->asin_all);
		$ama = NULL;
		
		if (! preg_match('/[a-z0-9]{10}/i', $this->root->asin)) {
			$this->root->asin = '';
			return FALSE;
		}
		return TRUE;
	}
}
?>