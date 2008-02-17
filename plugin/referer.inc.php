<?php
class xpwiki_plugin_referer extends xpwiki_plugin {
	function plugin_referer_init () {


	// $Id: referer.inc.php,v 1.8 2008/02/17 14:27:06 nao-pon Exp $
	/*
	 * PukiWiki Referer プラグイン(リンク元表示プラグイン)
	 * (C) 2003, Katsumi Saito <katsumi@jo1upk.ymt.prug.or.jp>
	 * License: GPL
		*/
	
		$this->cont['CONFIG_REFERER'] =  'plugin/referer/config';

	}
	
	function plugin_referer_action()
	{
	//	global $vars, $referer;
	//	global $_referer_msg;
		// CSS
		$this->func->add_tag_head('referer.css');
		
		// Setting: Off
		if (! $this->root->referer) return array('msg'=>'','body'=>'');
	
		if (isset($this->root->vars['page']) && $this->func->is_page($this->root->vars['page'])) {
			$sort = (empty($this->root->vars['sort'])) ? '0d' : $this->root->vars['sort'];
			return array(
				'msg'  => $this->root->vars['page'] . ' ' . $this->root->_referer_msg['msg_H0_Refer'],
				'body' => $this->plugin_referer_body($this->root->vars['page'], $sort));
		}
		$pages = $this->func->get_existpages($this->cont['TRACKBACK_DIR'], '.ref');
	
		if (empty($pages)) {
			return array('msg'=>'', 'body'=>'');
		} else {
			return array(
				'msg'  => 'referer list',
			'body' => $this->func->page_list($pages, 'referer', FALSE));
		}
	}
	
	// Referer 明細行編集
	function plugin_referer_body($page, $sort)
	{
	//	global $script, $_referer_msg;
	
		$data = $this->func->tb_get($this->func->tb_get_filename($page, '.ref'));

		$title = '<h2>' . $this->func->make_pagelink($page) . '</h2>';
		$list = '<div style="text-align:right;"><a href="'.$this->root->script.'?plugin=referer#header">' . $this->root->_referer_msg['msg_Hed_Referer'].' '.$this->root->_title_list . '</a></div>';


		if (empty($data)) return $title . $list . '<p>no data.</p>';
	
		$bg = $this->plugin_referer_set_color();
	
		$arrow_last = $arrow_1st = $arrow_ctr = '';
		$color_last = $color_1st = $color_ctr = $color_ref = $bg['etc'];
		$sort_last = '0d';
		$sort_1st  = '1d';
		$sort_ctr  = '2d';
	
		switch ($sort) {
		case '0d': // 0d 最終更新日時(新着順)
			usort($data, create_function('$a,$b', 'return $b[0] - $a[0];'));
			$color_last = $bg['cur'];
			$arrow_last = $this->root->_referer_msg['msg_Chr_darr'];
			$sort_last = '0a';
			break;
		case '0a': // 0a 最終更新日時(日付順)
			usort($data, create_function('$a,$b', 'return $a[0] - $b[0];'));
			$color_last = $bg['cur'];
			$arrow_last = $this->root->_referer_msg['msg_Chr_uarr'];
	//		$sort_last = '0d';
			break;
		case '1d': // 1d 初回登録日時(新着順)
			usort($data, create_function('$a,$b', 'return $b[1] - $a[1];'));
			$color_1st = $bg['cur'];
			$arrow_1st = $this->root->_referer_msg['msg_Chr_darr'];
			$sort_1st = '1a';
			break;
		case '1a': // 1a 初回登録日時(日付順)
			usort($data, create_function('$a,$b', 'return $a[1] - $b[1];'));
			$color_1st = $bg['cur'];
			$arrow_1st = $this->root->_referer_msg['msg_Chr_uarr'];
	//		$sort_1st = '1d';
			break;
		case '2d': // 2d カウンタ(大きい順)
			usort($data, create_function('$a,$b', 'return $b[2] - $a[2];'));
			$color_ctr = $bg['cur'];
			$arrow_ctr = $this->root->_referer_msg['msg_Chr_darr'];
			$sort_ctr = '2a';
			break;
		case '2a': // 2a カウンタ(小さい順)
			usort($data, create_function('$a,$b', 'return $a[2] - $b[2];'));
			$color_ctr = $bg['cur'];
			$arrow_ctr = $this->root->_referer_msg['msg_Chr_uarr'];
	//		$sort_ctr = '2d';
			break;
		case '3': // 3 Referer
			usort($data, create_function('$a,$b',
			'return ($a[3] == $b[3]) ? 0 : (($a[3] > $b[3]) ? 1 : -1);'));
			$color_ref = $bg['cur'];
			break;
		}
	
		$body = '';
		$i = 0;
		foreach ($data as $arr) {
			$i++;
			// 0:最終更新日時, 1:初回登録日時, 2:参照カウンタ, 3:Referer ヘッダ, 4:利用可否フラグ(1は有効)
			list($ltime, $stime, $count, $url, $enable) = $arr;
	
			// 非ASCIIキャラクタ(だけ)をURLエンコードしておく BugTrack/440
			$e_url = htmlspecialchars(preg_replace('/([" \x80-\xff]+)/e', 'rawurlencode("$1")', $url));
			$s_url = htmlspecialchars(mb_convert_encoding(rawurldecode($url), $this->cont['SOURCE_ENCODING'], 'auto'));
			$s_url = str_replace('&amp;amp;', '&amp;', $s_url);
			
			$s_url = preg_replace('#^https?://#i', '', $s_url);
			
			$lpass = $this->func->get_passage($ltime, FALSE); // 最終更新日時からの経過時間
			$spass = $this->func->get_passage($stime, FALSE); // 初回登録日時からの経過時間
			$ldate = $this->func->get_date($this->root->_referer_msg['msg_Fmt_Date'], $ltime); // 最終更新日時文字列
			$sdate = $this->func->get_date($this->root->_referer_msg['msg_Fmt_Date'], $stime); // 初回登録日時文字列
			
			$class = ' class="' . (($i % 2)? 'even' : 'odd') . '"';
			$body .=
				' <tr'.$class.'>' . "\n" .
			'  <td nowrap="nowrap">' . $ldate . '</td>' . "\n" .
			'  <td nowrap="nowrap"><small>(' . $lpass . ')</small></td>' . "\n";
	
			$body .= ($count == 1) ?
				'  <td colspan="2">&nbsp;&#8656;</td>' . "\n" :
				'  <td nowrap="nowrap">' . $sdate . '</td>' . "\n" .
			'  <td nowrap="nowrap"><small>(' . $spass . ')</small></td>' . "\n";
	
			$body .= '  <td style="text-align:right;font-weight:bold;">' . $count . '</td>' . "\n";
	
			// 適用不可データのときはアンカーをつけない
			$body .= $this->plugin_referer_ignore_check($url) ?
				'  <td>' . $s_url . '</td>' . "\n" :
				'  <td><a href="' . $e_url . '" rel="nofollow" class="referer">' . $this->func->get_favicon_img($e_url) . ' ' . $s_url . '</a></td>' . "\n";
	
			$body .= ' </tr>' . "\n";
		}
		$href = $this->root->script . '?plugin=referer&amp;page=' . rawurlencode($page);
		return <<<EOD
$title
$list
<table style="" border="1" cellspacing="1" summary="Referer" class="referer">
 <tr class="head">
  <th style="background-color:$color_last" colspan="2">
   <a href="$href&amp;sort=$sort_last">{$this->root->_referer_msg['msg_Hed_LastUpdate']}$arrow_last</a>
  </th>
  <th style="background-color:$color_1st" colspan="2">
   <a href="$href&amp;sort=$sort_1st">{$this->root->_referer_msg['msg_Hed_1stDate']}$arrow_1st</a>
  </th>
  <th style="background-color:$color_ctr;text-align:right">
   <a href="$href&amp;sort=$sort_ctr">{$this->root->_referer_msg['msg_Hed_RefCounter']}$arrow_ctr</a>
  </th>
  <th style="background-color:$color_ref">
   <a href="$href&amp;sort=3">{$this->root->_referer_msg['msg_Hed_Referer']}</a>
   </th>
 </tr>
 $body
</table>
EOD;
	}
	
	function plugin_referer_set_color()
	{
	//	static $color;
		static $color = array();
	
		if (!isset($color[$this->xpwiki->pid])) {
			// Default color
			$color[$this->xpwiki->pid] = array('cur' => '#88ff88', 'etc' => '#cccccc');
	
			$config = new XpWikiConfig($this->xpwiki, $this->cont['CONFIG_REFERER']);
			$config->read();
			$pconfig_color = $config->get('COLOR');
			unset($config);
	
			// BGCOLOR(#88ff88)
			$matches = array();
			foreach ($pconfig_color as $x)
				$color[$this->xpwiki->pid][$x[0]] = htmlspecialchars(
					preg_match('/BGCOLOR\(([^)]+)\)/si', $x[1], $matches) ?
						$matches[1] : $x[1]);
		}
		return $color[$this->xpwiki->pid];
	}
	
	function plugin_referer_ignore_check($url)
	{
	//	static $ignore_url;
		static $ignore_url = array();
		if (!isset($ignore_url[$this->xpwiki->pid])) {$ignore_url[$this->xpwiki->pid] = array();}
	
		// config.php
		if (! isset($ignore_url[$this->xpwiki->pid])) {
			$config = new XpWikiConfig($this->xpwiki, $this->cont['CONFIG_REFERER']);
			$config->read();
			$ignore_url[$this->xpwiki->pid] = $config->get('IGNORE');
			unset($config);
		}
	
		foreach ($ignore_url[$this->xpwiki->pid] as $x)
			if (strpos($url, $x) !== FALSE)
				return 1;
		return 0;
	}
}
?>