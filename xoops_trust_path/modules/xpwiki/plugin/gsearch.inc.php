<?php
class xpwiki_plugin_gsearch extends xpwiki_plugin {
	// #gsearch(type,keyword[,max:<int>][,col:<int>][,target:<string>])

	var $appid = '';
	var $appid_upg = '';
	var $params;

	function plugin_gsearch_init()
	{
		$this->config = array(
			//////// Config ///////
			'safe'       => 'moderate', // [active|moderate|off] セーフサーチ  active:強, moderate:中, off:なし
			'filter'     => 1,          // [0|1] 重複した結果の除外
			'ng_site'    => "",         // 除外サイト([SPACE]区切りで最大30サイト)
			//'coloration' => "any", // 画像検索対象の色指定[any|color|bw]
			//'format_web' => "any", // 検索対象[any|html|msword|pdf|ppt|rss|txt|xls](Web)
			//'format_img' => "any", // 検索対象[any|bmp|gif|jpeg|png](Image)
			//'format_mov' => "any", // 検索対象[any|avi|flash|mpeg|msmedia|quicktime|realmedia](Movie)
			'max_web'    => 8, // 検索件数の規定値(Web)
			'max_img'    => 5, // 検索件数の規定値(Image)
			'max_mov'    => 4, // 検索件数の規定値(Movie)
			'col_web'    => 1, // 表示列数の規定値(Web)
			'col_img'    => 5, // 表示列数の規定値(Image)
			'col_mov'    => 4, // 表示列数の規定値(Movie)
			'cache_time' => 14400, // Cache time (min) 14400m = 10 days
			//////// Config ///////
		);
		$this->appkey = '';
		$this->appid_upg = '';
	}

	function plugin_gsearch_convert()
	{
		error_reporting(E_ALL);
		$args = func_get_args();
		if (count($args) < 2)
		{
			return "<p>{$this->msg['err_option']}</p>";
		}

		$this->load_language();

		$mode = array_shift($args);
		$query = array_shift($args);

		// mode 判定
		$mode = trim(strtolower($mode));
		switch($mode)
		{
			case "images":
			case "image":
			case "img":
				$mode = "img";
				break;
			case "video":
			case "movie":
			case "mov":
				$mode = "mov";
				break;
			default:
				// web
				$mode = "web";
		}

		$prms = array(
				"target"=>$this->root->link_target,
				"max"=>$this->config['max_'.$mode],
				"col"=>$this->config['col_'.$mode],
				'safe' => $this->config['safe'],
				'filter' => $this->config['filter']
				);
		$this->fetch_options ($prms, $args);
		
		$prms['safe'] = strtolower($prms['safe']);
		if (!in_array($prms['safe'], array('active', 'moderate', 'off'))) {
			$prms['safe'] = $this->config['safe'];
		}
		$prms['filter'] = $prms['filter']? 1 : 0;
		
		$this->params = $prms;
		
		$ret = $this->plugin_gsearch_get($mode,$query);

		$this->func->add_tag_head('gsearch.css');
		return "<div class='gsearch'>{$ret}</div>";

	}

	function plugin_gsearch_get($mode,$query)
	{
		$ttl = $this->config['cache_time'] * 60;
		$key = md5($mode.$query.join('',$this->params));

		// キャッシュ判定
		if (! $html = $this->func->cache_get_db($key, 'gsearch')) {
			$html = $this->plugin_gsearch_gethtml($mode,$query);

			// キャッシュ保存
			if ($html) {
				if ($html === $this->msg['err_badres']) {
					$ttl = 3600; // 1h
				}
				$this->func->cache_save_db($html, 'gsearch', $ttl, $key);
			}

			// Update plainDB
			$this->func->need_update_plaindb();
		}

		return $html;

	}

	function plugin_gsearch_gethtml($mode,$query)
	{
		$search_url = 'http://ajax.googleapis.com/ajax/services/search/$mode?v=1.0&userip='.$_SERVER['REMOTE_ADDR'];

		$qs = $this->func->htmlspecialchars($query);
		// REST リクエストの構築
		$query = rawurlencode(mb_convert_encoding(trim($query),"UTF-8",$this->cont['SOURCE_ENCODING']));
		$max = min((int)$this->params['max'], 8);
		
		$search_url .= "&q={$query}&rsz={$max}";
		$search_url .= '&hl=' . $this->cont['LANG'];
		$search_url .= '&safe=' . $this->params['safe'];
		$search_url .= '&filter=' . $this->params['filter'];
		$search_url .= '&hl=' . $this->cont['LANG'];
		if ($this->appkey) $search_url .= '&key' . $this->appkey;
		
		$mode = trim(strtolower($mode));
		switch($mode)
		{
			case "images":
			case "image":
			case "img":
				$mode = "images";
				break;
			case "video":
			case "movie":
			case "mov":
				$mode = "video";
				break;
			case "web":
			default:
				$mode = "web";
		}
		$url = str_replace('$mode', $mode, $search_url);
		//exit($url);
		$src = '';
		
		// データ取得
		$res = $this->func->http_request($url);
		if ($res['rc'] == 200 && $res['data'])
		{
			$res = json_decode($res['data'], true);
			
			//$src .= '<div><pre>'. $url . "\n" . print_r($res, true) . '</pre></div>';
			
			// 該当データなし
			if (!$res['responseData']['results'])
			{
				return sprintf($this->msg['msg_notfound'],$qs,$this->msg['msg_'.$mode]);
			}
		}
		else
		{
			// データ取得エラー
			return $this->msg['err_badres'];

		}

		// 該当データなし
		if (!$res['responseData']['results'])
		{
			return sprintf($this->msg['msg_notfound'],$qs,$this->msg['msg_'.$mode]);
		}

		$func = "plugin_gsearch_build_".$mode;
		$target = $this->func->htmlspecialchars($this->params['target']);
		$html = $this->$func($res['responseData'],$target,$this->params['col']);
		
		if (isset($res['responseData']['cursor']) && isset($res['responseData']['cursor']['moreResultsUrl'])) {
			$html .= '<div class="more"><a href="'.$res['responseData']['cursor']['moreResultsUrl'].'" target="'.$target.'">'.sprintf($this->msg['msg_more'],$qs,$this->msg['msg_'.$mode]).'</a></div>';
		}
		
		return $html . $src;
	}

	function plugin_gsearch_build_web($res,$target,$col)
	{

		$linkurl = 'Url'; // 'URL' or 'ClickUrl'

		$dats = array();
		if (isset($res['results'][0]))
		{
			$dats = $res['results'];
		}
		else
		{
			$dats[0] = (empty($res['results']))? array() : $res['results'];
		}

		$html = "";
		if ($dats)
		{
			$html = $sdiv = $ediv = "";
			if ($col > 1)
			{
				$sdiv = "<div style='float:left;width:".(intval(99/$col*10)/10)."%'>";
				$ediv = "</div><div style='clear:left;'></div>";
			}
			$cnt = 0;
			$limit = ceil(count($dats)/$col);
			$html .= $sdiv."<ul>";
			mb_convert_variables($this->cont['SOURCE_ENCODING'],"UTF-8",$dats);
			foreach ($dats as $dat)
			{
				if ($this->plugin_gsearch_check_ngsite($dat['url'])) {continue;}
				if ($cnt++ % $limit === 0 && $cnt !== 1) $html .= "</ul></div>".$sdiv."<ul>";
				$html .= "<li>";
				$html .= "<a href='".$dat['url']."' target='{$target}'>".$dat['titleNoFormatting']."</a>";
				$html .= "<div class='quotation'>".$this->func->make_link(strip_tags($dat['content']))."</div>";
				$html .= "</li>";
			}
			$html .= "</ul>".$ediv;
		}

		return $html;
	}

	function plugin_gsearch_build_images($res,$target,$col)
	{
		$dats = array();
		if (isset($res['results'][0]))
		{
			$dats = $res['results'];
		}
		else
		{
			$dats[0] = (empty($res['results']))? array() : $res['results'];
		}

		$html = "";
		if ($dats)
		{
			$cnt = 0;
			$html = "<table><tr>";
			mb_convert_variables($this->cont['SOURCE_ENCODING'],"UTF-8",$dats);
			foreach ($dats as $dat)
			{
				if ($this->plugin_gsearch_check_ngsite($dat['url'])) {continue;}
				$title = "[".$dat['titleNoFormatting']."]".$this->func->htmlspecialchars($dat['contentNoFormatting']);
				$size = $dat['width']." x ".$dat['height'];
				$site = "[ <a href=\"".$this->func->htmlspecialchars($dat['originalContextUrl'])."\" target='{$target}'>Site</a> ]";

				if ($cnt++ % $col === 0 && $cnt !== 1) $html .= "</tr><tr>";
				$html .= "<td style='text-align:center;vertical-align:middle;'>";
				$html .= "<a class=\"img\" href=\"".$dat['unescapedUrl']."\" target=\"{$target}\" title=\"{$title}\" type=\"img\"><img src=\"{$dat['tbUrl']}\" width=\"{$dat['tbWidth']}\" height=\"{$dat['tbHeight']}\" alt=\"{$title}\" title=\"{$title}\" /></a>";
				$html .= "<br /><small>".$size."<br />".$site."</small>";
				$html .= "</td>";
			}
			$html .= "</tr></table>";
		}

		return $html;
	}

	function plugin_gsearch_build_video($res,$target,$col)
	{
		$dats = array();
		if (isset($res['results'][0]))
		{
			$dats = $res['results'];
		}
		else
		{
			$dats[0] = (empty($res['results']))? array() : $res['results'];
		}

		$html = "";
		if ($dats)
		{
			$cnt = 0;
			$html = "<table><tr>";
			mb_convert_variables($this->cont['SOURCE_ENCODING'],"UTF-8",$dats);
			foreach ($dats as $dat)
			{
				if ($this->plugin_gsearch_check_ngsite($dat['url'])) {continue;}
				$title = "[".$dat['titleNoFormatting']."]".$this->func->htmlspecialchars($dat['contentNoFormatting']);
				$site = " [ <a href=\"".$this->func->htmlspecialchars($dat['url'])."\" target='{$target}'>Site</a> ]";
				$min = (int)($dat['duration'] / 60);
				$sec = sprintf("%02d",($dat['duration'] % 60));
				$length = $min.":".$sec;

				if ($cnt++ % $col === 0 && $cnt !== 1) $html .= "</tr><tr>";
				$html .= "<td style='text-align:center;vertical-align:middle;'>";
				$html .= "<a class=\"img\" href=\"".$dat['playUrl']."\" target=\"{$target}\" title=\"{$title}\" type=\"img\"><img src=\"{$dat['tbUrl']}\" width=\"{$dat['tbWidth']}\" height=\"{$dat['tbHeight']}\" alt=\"{$title}\" title=\"{$title}\" /></a>";
				$html .= "<br /><small>".$length."<br />".$site.'<small>';
				$html .= "</td>";
			}
			$html .= "</tr></table>";
		}

		return $html;
	}

	function plugin_gsearch_check_ngsite($url)
	{
		static $ngsites = array();
		if (!isset($ngsites[$this->xpwiki->pid])) {$ngsites[$this->xpwiki->pid] = null;}
		if (is_null($ngsites[$this->xpwiki->pid]))
		{
			$ngsites[$this->xpwiki->pid] = explode(" ",$this->config['ng_site']);
		}
		foreach($ngsites[$this->xpwiki->pid] as $ngsite)
		{
			if ($ngsite && preg_match("#".preg_quote($ngsite,"#")."#i",$url))
			{
				return true;
			}
		}
		return false;
	}

}
?>