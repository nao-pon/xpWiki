<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: isbn.inc.php,v 1.9 2008/05/15 23:52:14 nao-pon Exp $
//
// *0.5: URL が存在しない場合、画像を表示しない。
//			 Thanks to reimy.
//	 GNU/GPL にしたがって配布する。
//

class xpwiki_plugin_isbn extends xpwiki_plugin {
	function plugin_isbn_init () {
		/////////////////////////////////////////////////
		// AmazonアソシエイトID
		$this->config['AMAZON_ASE_ID'] = '';
		// amazon 商品情報問合せ URI(dev-t はマニュアルのディフォルト値)
		$this->config['ISBN_AMAZON_XML'] = 'http://xml.amazon.co.jp/onca/xml3?t=webservices-20&dev-t=GTYDRES564THU&type=lite&page=1&f=xml&locale=jp&AsinSearch=';
		// amazon shop URI (_ISBN_ に商品IDがセットされる)
		$this->config['ISBN_AMAZON_SHOP'] = 'http://www.amazon.co.jp/exec/obidos/ASIN/_ISBN_/ref=nosim/'.$this->config['AMAZON_ASE_ID'];
		// amazon UsedShop URI (_ISBN_ に商品IDがセットされる)
		$this->config['ISBN_AMAZON_USED'] = 'http://www.amazon.co.jp/exec/obidos/tg/detail/offer-listing/-/_ISBN_/all/ref='.$this->config['AMAZON_ASE_ID'];
	
		/////////////////////////////////////////////////
		// expire 画像キャッシュを何日で削除するか
		$this->config['ISBN_AMAZON_EXPIRE_IMG'] = 10;
		// expire タイトルキャッシュを何日で削除するか
		$this->config['ISBN_AMAZON_EXPIRE_TIT'] = 1;
		// NoImage file.
		$this->config['NOIMAGE'] = $this->cont['IMAGE_DIR'] . 'noimage.png';

		// 言語ファイルの読み込み
		$this->load_language();
	
	}
	
	
	function plugin_isbn_convert() {
		if (HypCommonFunc::get_version() < 20080224) {
			return '#amazon require "HypCommonFunc" >= Ver. 20080224';
		}

		if (func_num_args() < 1 or func_num_args() > 3) {
			return false;
		}
		$aryargs = func_get_args();
		$isbn = htmlspecialchars($aryargs[0]);	// for XSS
		$isbn = str_replace("-","",$isbn);
	
		$align = "right"; //規定値
		$title = '';
		$header = '';
		$alt = $title = $h_title = $price = $header = $listprice = $usedprice = '';
		switch (func_num_args())
		{
			case 3:
				if (strtolower($aryargs[2]) == 'left') $align = "left";
				elseif (strtolower($aryargs[2]) == 'clear') $align = "clear";
				elseif (strtolower($aryargs[2]) == 'header' || $aryargs[2] == 'h') $header = "header";
				elseif (strtolower($aryargs[2]) == 'info') $header = "info";
				elseif (strtolower($aryargs[2]) == 'img' || $aryargs[2] == 'image') $title = "image";
				else $title = htmlspecialchars($aryargs[2]);
			case 2:
				if (strtolower($aryargs[1]) == 'left') $align = "left";
				elseif (strtolower($aryargs[1]) == 'clear') $align = "clear";
				elseif (strtolower($aryargs[1]) == 'header' || $aryargs[1] == 'h') $header = "header";
				elseif (strtolower($aryargs[1]) == 'info') $header = "info";
				elseif (strtolower($aryargs[1]) == 'img' || $aryargs[1] == 'image') $title = "image";
				else $title = htmlspecialchars($aryargs[1]);
			case 1:
				if (strtolower($aryargs[0]) == 'clear') 
				{
					$align = "clear";
					$isbn = "";
				}
		}
		if ($isbn)
		{
			$tmpary = $this->plugin_isbn_get_isbn_title($isbn);
			$alt = $this->plugin_isbn_get_caption($tmpary);
			$price = ($tmpary[2])? "<div style=\"text-align:right;\">".str_replace('$1', $tmpary[2], $this->msg['price'])."</div>" : '';
			$off = 0;
			$_price = (int) trim(str_replace(",","",$tmpary[2]));
			$_listprice = (int) trim(str_replace(",","",$tmpary[8]));
			if ($_price && $_listprice && ($_price < $_listprice))
			{
				$off = 100 - (($_price/$_listprice) * 100);
				$price = "<div style=\"text-align:right;\">".str_replace(array('$1','$2','$3'), array($tmpary[8],$tmpary[2],$off),$this->msg['price_down'])."</div>";
				$listprice = '';
			} else {
				$listprice = ($tmpary[8] && $_price !== $_listprice)? "<div style=\"text-align:right;\">".str_replace('$1', $tmpary[8], $this->msg['price'])."</div>" : '';
			}
			$usedprice = ($tmpary[9])? "<div style=\"text-align:right;\">".str_replace('$1', $tmpary[9], $this->msg['used'])."</div>" : '';
			
			if ($title != '') {			// タイトル指定か自動取得か
				$h_title = $title;
			} else {					// タイトル自動取得
				$title = "[ $tmpary[1] ]<br />$tmpary[0]";
				$h_title = "$tmpary[0]";
			}
		}
		if ($header != "info")
			return $this->plugin_isbn_print_isbn_img($isbn, $align, $alt, $title, $h_title, $price, $header,$listprice,$usedprice);
		else
		{
			return $this->plugin_isbn_get_info($tmpary,$isbn);
		}
	}
	
	function plugin_isbn_inline()
	{
		if (HypCommonFunc::get_version() < 20080224) {
			return '&amazon require "HypCommonFunc" >= Ver. 20080224';
		}

		$prms = func_get_args();
		$body = array_pop($prms); // {}内
		$body = preg_replace('#</?(a|span)[^>]*>#i','',$body);
		$body = preg_replace('#(?:alt|title)=("|\').*\1#i','',$body);
		list($isbn,$option) = array_pad($prms,2,"");
		$option = htmlspecialchars($option); // for XSS
		$isbn = htmlspecialchars($isbn); // for XSS
		$isbn = str_replace("-","",$isbn);
		
		$tmpary = array();
		$tmpary = $this->plugin_isbn_get_isbn_title($isbn);
		if ($tmpary[2]) $price = "<div style=\"text-align:right;\">".str_replace('$1', $tmpary[2], $this->msg['currency'])."</div>";
		$title = $tmpary[0];
		//$text = htmlspecialchars(preg_replace('#</?(a|span)[^>]*>#i','',$option));
		$alt = $this->plugin_isbn_get_caption($tmpary);
		$amazon_a = '<a href="'.str_replace('_ISBN_',$isbn,$this->config['ISBN_AMAZON_SHOP']).'" target="_blank" title="'.$alt.'">';
		$match = array();
		if (!preg_match("/(s|l|m)?ima?ge?/i",$option,$match))
		{
			if ($option || $body) $title = $option.$body;
			return $amazon_a . $title . '</a>';
		} else {
			$size = '';
			if (!empty($match[1])) {
				$size = strtoupper($match[1]);
				if ($size === 'M') {
					$size = '';
				} else {
					$size .= '-';
				}
			}
			$url = $this->plugin_isbn_cache_image_fetch($size.$isbn, $this->cont['CACHE_DIR']);
			return $amazon_a.'<img src="'.$url.'" alt="'.$alt.'" /></a>';
		}
	}
	
	function plugin_isbn_get_caption($data)
	{
		$off = "";
		$_price = (int) trim(str_replace(",","",$data[2]));
		$_listprice = (int) trim(str_replace(",","",$data[8]));
		if ($_price && $_listprice && ($_price != $_listprice))
		{
			$off = (int)(100 - (($_price/$_listprice) * 100));
			$off = " ({$off}% Off)";
		}
	
		//改行文字セット IE は "&#13;&#10;"
		$br = (strstr($this->root->ua, "MSIE"))? "&#13;&#10;" : " ";
	
		$alt = "[ $data[1] ]{$br}$data[0]";
		if ($data[8]) $alt .= "{$br}{$this->msg['info_price']}: ".str_replace('$1',$data[8],$this->msg['currency']);
		if ($data[2]) $alt .= "{$br}{$this->msg['info_amazon']}: ".str_replace('$1',$data[2],$this->msg['currency']).$off;
		if ($data[9]) $alt .= "{$br}{$this->msg['info_used']}: ".str_replace('$1',$data[9],$this->msg['currency_from']);
		//if ($data[3]) $alt .= "{$br}{$this->msg['info_author']}: $data[3]";
		//if ($data[4]) $alt .= "{$br}{$this->msg['info_artist']}: $data[4]";
		if ($data[5]) $alt .= "{$br}{$this->msg['info_sale_date']}: $data[5]";
		if ($data[6]) $alt .= "{$br}{$this->msg['info_sales']}: ". strip_tags($data[6]);
		if ($data[7]) $alt .= "{$br}{$this->msg['info_status']}: $data[7]";
		return $alt;
	}
	
	function plugin_isbn_get_info($data,$isbn)
	{
		$alt = $this->plugin_isbn_get_caption($data);
		$amazon_a = '<a href="'.str_replace('_ISBN_',$isbn,$this->config['ISBN_AMAZON_SHOP']).'" target="_blank" title="'.$alt.'">';
		$amazon_s1 = "<a href=\"http://www.amazon.co.jp/exec/obidos/external-search/?mode=blended&amp;keyword=";
		$amazon_s2 = "&amp;tag=".$this->config['AMAZON_ASE_ID']."&amp;encoding-string-jp=%93%FA%96%7B%8C%EA&amp;Go.x=14&amp;Go.y=5\" target=\"_blank\" alt=\"Amazon Serach\" title=\"Amazon Serach\">";
		/*
		if (@$data[3])
		{
			$artists = array();
			foreach(split(", ",$data[3]) as $tmp)
			{
				$artists[] = $amazon_s1 . $this->plugin_isbn_jp_enc($tmp,"sjis") . $amazon_s2 . $tmp . "</a>";
			}
			$data[3] = join(", ",$artists);
		}
		if (@$data[4])
		{
			$artists = array();
			foreach(split(", ",$data[4]) as $tmp)
			{
				$artists[] = $amazon_s1 . $this->plugin_isbn_jp_enc($tmp,"sjis") . $amazon_s2 . $tmp . "</a>";
			}
			$data[4] = join(", ",$artists);
		}
		if (@$data[6])
			$data[6] = $amazon_s1 . $this->plugin_isbn_jp_enc($data[6],"sjis") . $amazon_s2 . $data[6] . "</a>";
		*/
		
		$off = "";
		$_price = (int) trim(str_replace(",","",$data[2]));
		$_listprice = (int) trim(str_replace(",","",$data[8]));
		if ($_price && $_listprice && ($_price != $_listprice))
		{
			$off = (int)(100 - (($_price/$_listprice) * 100));
			$off = " ({$off}% Off)";
		}
		if (@$data[9])
			$data[9] = '<a href="'.str_replace('_ISBN_',$isbn,$this->config['ISBN_AMAZON_USED']).'" target="_blank" alt="Amazon Used Serach" title="Amazon Used Serach">'.$data[9].'円〜</a>';
	
		$td_title_style = " style=\"text-align:right;\" nowrap=\"true\"";
		
		$addrow = '';
		if (@ $data[3]) {
			foreach(explode('<br />', $data[3]) as $tmp){
				list($cap, $val) = explode(':', $tmp, 2);
				$cap = trim($cap);
				$val = trim($val);
				$addrow .= "<tr><td$td_title_style>{$cap}:</td><td style=\"text-align:left;\">{$val}</td></tr>";
			}
		}
		
		$ret = "<div><table style=\"width:auto;\">";
		if (@$data[1]) $ret .= "<tr><td$td_title_style>{$this->msg['info_category']}</td><td style=\"text-align:left;\">$data[1]</td></tr>";
		if (@$data[0]) $ret .= "<tr><td$td_title_style>{$this->msg['info_title']}</td><td style=\"text-align:left;\">{$amazon_a}$data[0]</a></td></tr>";
		if (@$data[8]) $ret .= "<tr><td$td_title_style>{$this->msg['info_price']}</td><td style=\"text-align:left;\">".str_replace('$1',$data[8],$this->msg['currency'])."</td></tr>";
		if (@$data[2]) $ret .= "<tr><td$td_title_style>{$this->msg['info_amazon']}</td><td style=\"text-align:left;\">".str_replace('$1',$data[2],$this->msg['currency'])."$off</td></tr>";
		if (@$data[9]) $ret .= "<tr><td$td_title_style>{$this->msg['info_used']}</td><td style=\"text-align:left;\">$data[9]</td></tr>";
		//if (@$data[3]) $ret .= "<tr><td$td_title_style>{$this->msg['info_author']}</td><td style=\"text-align:left;\">$data[3]</td></tr>";
		//if (@$data[4]) $ret .= "<tr><td$td_title_style>{$this->msg['info_artist']}</td><td style=\"text-align:left;\">$data[4]</td></tr>";
		if ($addrow)   $ret .= $addrow;
		if (@$data[5]) $ret .= "<tr><td$td_title_style>{$this->msg['info_sale_date']}</td><td style=\"text-align:left;\">$data[5]</td></tr>";
		if (@$data[6]) $ret .= "<tr><td$td_title_style>{$this->msg['info_sales']}</td><td style=\"text-align:left;\">$data[6]</td></tr>";
		if (@$data[7]) $ret .= "<tr><td$td_title_style>{$this->msg['info_status']}</td><td style=\"text-align:left;\">$data[7]</td></tr>";
		$ret .= "</table></div>";
		return $ret;
	}
	
	function plugin_isbn_print_isbn_img($isbn, $align, $alt, $title, $h_title, $price, $header="",$listprice,$usedprice)
	{
		$clear = ($align === 'clear')? '<div style="clear:both"></div>' : '';

		if (! $isbn) return $clear;
		
		$amazon_a = '<a href="'.str_replace('_ISBN_',$isbn,$this->config['ISBN_AMAZON_SHOP']).'" target="_blank" title="'.$alt.'">';
	
		if (! ($url = $this->plugin_isbn_cache_image_fetch($isbn, $this->cont['CACHE_DIR']))) return false;
	
		if ($title == 'image') {				// タイトルがなければ、画像のみ表示
			return <<<EOD
<div style="float:$align;padding:.5em 1.5em .5em 1.5em">
 {$amazon_a}<img src="$url" alt="$alt" /></a>
</div>
{$clear}
EOD;
		} else {					// 通常表示
			$img_size = @getimagesize(str_replace(XOOPS_URL,XOOPS_ROOT_PATH,$url));
			//echo str_replace(XOOPS_URL,XOOPS_ROOT_PATH,$url);
			
			if (substr($isbn,0,1) == "B"){
					$code = "ASIN: ".$isbn;
			} else {
					$code = "ISBN: ".substr($isbn,0,1)."-".substr($isbn,1,3)."-".substr($isbn,4,5)."-".substr($isbn,9,1);
			}
			 if ($header != "header"){
	return <<<EOD
<div style="float:$align;padding:.5em 1.5em .5em 1.5em;text-align:center">
 {$amazon_a}<img src="$url" alt="$alt" /></a><br/>
 <table style="width:{$img_size[0]}px;border:0"><tr>
	<td style="text-align:left">{$amazon_a}$title</a></td>
 </tr></table>
</div>
{$clear}
EOD;
			} else {
	return <<<EOD
<div style="float:$align;padding:.5em 1.5em .5em 1.5em;text-align:center">
 {$amazon_a}<img src="$url" alt="$alt" /></a></div>
<h3 class="isbn_head">{$amazon_a}{$h_title}</a></h3>
<div style="text-align:right;">{$code}</div>
$listprice
$price
$usedprice
$clear
EOD;
			}
		}
	}
	
	function plugin_isbn_get_isbn_title(& $isbn, $check = true) {
		include_once XOOPS_TRUST_PATH . '/class/hyp_common/hsamazon/hyp_simple_amazon.php';
		$ama = new HypSimpleAmazon();
		$isbn = $ama->ISBN2ASIN($isbn);
 
		$nocache = $nocachable = 0;
		$title = $category = $price = $author = $artist = $releasedate = $manufacturer = $availability = $listprice = $usedprice = '';
		if ($title = $this->plugin_isbn_cache_fetch($isbn, $this->cont['CACHE_DIR'].'plugin/', $check)) {
			list($title,$category,$price,$author,$artist,$releasedate,$manufacturer,$availability,$listprice,$usedprice) = $title;
		} else {
			$title = 'ISBN:' . $isbn;
		}
		$tmpary = array($title,$category,$price,$author,$artist,$releasedate,$manufacturer,$availability,$listprice,$usedprice);
		return $tmpary;
	}
	
	// キャッシュがあるか調べる
	function plugin_isbn_cache_fetch($target, $dir, $check = true) {
	//	global $vars;
	
		$filename = $dir . $target . '.isbn';
		
		if (!file_exists($filename) ||
			($check && $this->config['ISBN_AMAZON_EXPIRE_TIT'] * 3600 * 24 < $this->cont['UTC'] - filemtime($filename))) {
			// データを取りに行く
			include_once XOOPS_TRUST_PATH . '/class/hyp_common/hsamazon/hyp_simple_amazon.php';
			$ama = new HypSimpleAmazon($this->config['AMAZON_ASE_ID']);
			$ama->encoding = $this->cont['SOURCE_ENCODING'];
			$ama->itemLookup($target);
			$tmpary = $ama->getCompactArray();
			$ama = NULL;
			
			$title = '';
			if (!empty($tmpary['Items'])) {
				$tmpary = $tmpary['Items'][0];
				$title = $tmpary['TITLE'];
				$category = @$tmpary['BINDING'];
				$price = @$tmpary['PRICE'];
				$author = @$tmpary['CREATOR'];
				$artist = '';
				$releasedate = @$tmpary['RELEASEDATE'];
				$manufacturer = @ $tmpary['MANUFACTURER'];
				$availability = @ $tmpary['AVAILABILITY'];
				$listprice = @ $tmpary['LISTPRICE'];
				$usedprice = @ $tmpary['USEDPRICE'];
				$simg = $tmpary['SIMG']; //[10]
				$mimg = $tmpary['MIMG']; //[11]
				$limg = $tmpary['LIMG']; //[12]
			}

			if ($title != '') {				// タイトルがあれば、できるだけキャッシュに保存
				$title = "$title<>$category<>$price<>$author<>$artist<>$releasedate<>$manufacturer<>$availability<>$listprice<>$usedprice<>$simg<>$mimg<>$limg";
				$this->plugin_isbn_cache_save($title, $filename);
			}

			// Update plainDB
			$this->func->need_update_plaindb();

		} else {
			$title = file_get_contents($filename);
		}
		if (strlen($title) > 0) {
			return explode("<>",$title);
		} else {
			return array();
		}
	}
	
	// 画像キャッシュがあるか調べる
	function plugin_isbn_cache_image_fetch($target, $dir, $check=true) {
		$_target = $target = strtoupper($target);
		$filename = $dir."ASIN".$target.".jpg";
	
		if (!is_readable($filename) || (is_readable($filename) && $check && $this->config['ISBN_AMAZON_EXPIRE_IMG'] * 3600 * 24 < $this->cont['UTC'] - filemtime($filename))) {
			$size = 'M';
			$isbn = $target;
			if (preg_match("/^(?:(s|m|l)-)(.+)/i",$target,$match)) {
				$size = strtoupper($match[1]);
				$isbn = $match[2];
			}
			$ary = $this->plugin_isbn_cache_fetch($isbn, $this->cont['CACHE_DIR'].'plugin/');
			if ($size === 'S') {
				$url = $ary[10];
			} else if ($size === 'L') {
				$url = $ary[12];
			} else {
				$url = $ary[11];
			}
			
			if ($url) {
				$data = $this->func->http_request($url);
				if ($data['rc'] == 200 && $data['data']) {
					$data = $data['data'];
				} else 	{
					$data = @join(@file($this->config['NOIMAGE']));
				}
			} else {
				// キャッシュを NOIMAGE のコピーとする
				$data = @join(@file($this->config['NOIMAGE']));
			}
			$this->plugin_isbn_cache_image_save($data, $filename);
			return str_replace($this->cont["DATA_HOME"], $this->cont["HOME_URL"], $filename);
		} else
			return str_replace($this->cont["DATA_HOME"], $this->cont["HOME_URL"], $filename);
	}
	
	// キャッシュを保存
	function plugin_isbn_cache_save($data, $filename) {
		$fp = fopen($filename, "w");
		fwrite($fp, $data);
		fclose($fp);
		return $filename;
	}
	
	// 画像キャッシュを保存
	function plugin_isbn_cache_image_save($data, $filename) {
		$fp = fopen($filename, "w");
		fwrite($fp, $data);
		fclose($fp);
		return $filename;
	}
	
	// 文字列をURLエンコード
	function plugin_isbn_jp_enc($word,$mode){
		switch( $mode ){
			case "sjis" : return rawurlencode(mb_convert_encoding($word, "SJIS", "EUC-JP"));
			case "euc" : return rawurlencode($word);
			case "utf8" : return rawurlencode(mb_convert_encoding($word, "UTF-8", "EUC-JP"));
		}
		return true;
	}
}
?>