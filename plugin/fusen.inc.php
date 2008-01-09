<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// fusen.inc.php
// 付箋プラグイン
// ohguma@rnc-com.co.jp
//
// v 1.0 2005/03/12 初版
// v 1.1 2005/03/16 FUSEN_SCRIPT_FILEにDATA_HOME追加,
//                  付箋削除時に線消去,
//                  付箋中での#fusen削除,
//                  付箋中にformがある場合の不具合解消.
// v 1.2 2005/03/16 XSS対策,削除確認追加
// v 1.3 2005/03/17 XHTML1.1対応?
//                  背景透明の設定不備修正
// v 1.4 2005/03/18 検索機能追加,
//                  付箋更新時に添付元ファイルの最終更新日を更新
// v 1.5 2005/03/18 検索機能修正(convert_html後の表示内容で検索),
//                  付箋更新時にRecentChangesを反映,
//                  XSS対策の修正
// v 1.6 2005/03/28 新規追加時のID付与の問題修正
// v 1.7 2005/04/02 HELP修正,入力画面変更
//                  付箋データ保持方法変更
// v 1.8 2005/04/03 付箋が0枚になった際のバグ修正
//                  AJAX対応(auto set, リアルタイム更新対応)
//

/////////////////////////////////////////////////
// xpWiki - XOOPS's PukiWiki module.
//
// fusen.inc.php for xpWiki by nao-pon
// http://xoops.hypweb.net
// $Id: fusen.inc.php,v 1.1 2008/01/09 02:39:24 nao-pon Exp $
// 

class xpwiki_plugin_fusen extends xpwiki_plugin {
	function plugin_fusen_init () {
		$this->load_language();
		
		// Attach filename of FUSEN data.
		$this->cont['FUSEN_ATTACH_FILENAME'] = 'fusen.dat';
	
		// FUSEN border style.
		// Normal
		$this->cont['FUSEN_STYLE_BORDER_NORMAL'] =  '#000000 1px solid';
		// Locked
		$this->cont['FUSEN_STYLE_BORDER_LOCK'] =  '#836FFF 1px solid';
		// Deleted
		$this->cont['FUSEN_STYLE_BORDER_DEL'] =  '#333333 1px dotted';
		// Selected
		$this->cont['FUSEN_STYLE_BORDER_SELECT'] =  'red 1px solid';

	}
	
	function plugin_fusen_convert() {

		$base = '';
		$divclass = 'xpwiki_' . $this->root->mydirname;
		if ($this->root->render_mode === 'render') {
			return false;
		} else if ($this->root->render_mode === 'block') {
			if (empty($GLOBALS['Xpwiki_'.$this->root->mydirname]['is_read'])
				|| 
				!empty($GLOBALS['Xpwiki_'.$this->root->mydirname]['cache']['fusen']['loaded'])) {
				return '';
			}
			$this->func->set_current_page($GLOBALS['Xpwiki_'.$this->root->mydirname]['page']);
			$base = 'xpwiki_body';
			$divclass = 'xpwiki_b_' . $this->root->mydirname;
			$this->root->pagecache_min = 0;
		}
		//static $loaded = array();
		//if (!isset($loaded[$this->root->mydirname])) {$loaded[$this->root->mydirname] = false;}
		
		// パラメータ
		$off = $from_skin = $refresh = 0;
		$background = $height = '';
		foreach(func_get_args() as $prm)
		{
			$arg = array();
			if (preg_match("/^r(efresh)?:([\d]+)/",$prm,$arg))
				$refresh =($arg[2])? $arg[2] : 0;
			if (preg_match("/^h(eight)?:([\d]+)/",$prm,$arg))
				$height = min($arg[2],10000);
			if ($prm == 'FROM_SKIN')
				$from_skin = 1;
			if (strtolower($prm) == 'off')
				$off = 1;
		}
		
		//読み込みチェック
		if ($this->root->rtf['convert_nest'] > 1)
		{
			if ($off) return '';
			return "<p>".$this->func->make_pagelink($this->root->vars['page'],$this->func->strip_bracket($this->root->vars['page'])."の付箋を表示")."</p>";
		}
		if (!empty($GLOBALS['Xpwiki_'.$this->root->mydirname]['cache']['fusen']['loaded']))
		{
			return '';
		}
		
		// $pwm_plugin_flg セット
		$this->root->pwm_plugin_flg['fusen']['convert'] = true;
		
		if ($off) return '';
		
		// 初期化
		$this->func->add_tag_head('fusen.css');
		$this->func->add_tag_head('fusen.js');

		$GLOBALS['Xpwiki_'.$this->root->mydirname]['cache']['fusen']['loaded'] = true;
		$refer = $this->root->vars['page'];
		$border_normal = $this->cont['FUSEN_STYLE_BORDER_NORMAL'];
		$border_lock = $this->cont['FUSEN_STYLE_BORDER_LOCK'];
		$border_del = $this->cont['FUSEN_STYLE_BORDER_DEL'];
		$border_select = $this->cont['FUSEN_STYLE_BORDER_SELECT'];
		$fusen_data = $this->plugin_fusen_data($refer);
		$name = $this->cont['USER_NAME_REPLACE'];
		$jname = $this->plugin_fusen_jsencode($name);
		
		if ($height)
		{
			$board = '<div class="fusen_board" style="height:'.$height.'px;"></div>';
		}
		else
		{
			//$background = 'background:transparent none;';
			$board = '';
		}
		
		$wiki_helper = '';
		
		$selected = 0;
		$refresh_str = $this->msg['cap_refresh'] . ':<select name="fusen_menu_interval" id="fusen_menu_interval" size="1" onchange="fusen_setInterval(this.value);window.focus();">';
		$refresh_str .= '<option value="0">' . $this->msg['cap_none'];
		foreach(array(10,20,30,60) as $sec)
		{
			if (!$selected && $refresh && $sec >= $refresh)
			{
				$select = ' selected="true"';
				$selected = $sec;
			}
			else
				$select = '';
			$msec = $sec * 1000;
			$refresh_str .= '<option value="'.$msec.'"'.$select.'>'.$sec.$this->msg['cap_second'];
		}
		$refresh_str .= '</select>';
		$refresh = $selected * 1000;
		
		$html = $this->plugin_fusen_gethtml($fusen_data);
		//if (!$html) $html = '<p></p>';
		
		$fusen_post = $this->cont['HOME_URL'];
		$fusen_url = $this->cont['LOADER_URL'] . '?src=fusen_' . $this->func->get_pgid_by_name($this->root->vars['page']) . '.pcache.xml';
		$X_ucd = ''; //WIKI_UCD_DEF;
		$js_refer = $this->plugin_fusen_jsencode($refer);
		$auth = $this->func->is_owner($refer)? 1 : 0;
		
		$burn = ($auth)? "(<a href=\"JavaScript:fusen_burn()\" title=\"{$this->msg['cap_dustbox_empty']}\">{$this->msg['cap_empty']}</a>)" : "";
		$js_massages = '';
		foreach($this->msg['js_messages'] as $key => $val) {
			$js_massages .= 'fusenMsgs[\'' . $key . '\'] = "' . str_replace('"', '&quot;' ,preg_replace('/[\r\n]/', '', $val)) . '";' . "\n";
		}
		
		return <<<EOD
<script type="text/javascript">
//<![CDATA[
fusenVar['base'] = "{$base}";
fusenVar['BorderObj'] = {"normal":"{$border_normal}", "lock":"{$border_lock}", "del":"{$border_del}", "select":"{$border_select}"};
fusenVar['JsonUrl'] = "{$fusen_url}";
fusenVar['PostUrl'] = "{$fusen_post}";
fusenVar['Interval'] = {$refresh};
fusenVar['admin'] = {$auth};
fusenVar['uid'] = {$this->root->userinfo['uid']};
fusenVar['ucd'] = "{$this->cont['USER_CODE_REPLACE']}";
fusenVar['FromSkin'] = {$from_skin};
{$js_massages}
//]]>
</script>
<fieldset class="fusen_fieldset">
<legend>{$this->msg['cap_fusen_menu']}&nbsp;</legend>
<div id="fusen_top_menu" class="fusen_top_menu" style="visibility: hidden;">
<form action="" onsubmit="return false;" style="padding:0px;margin:0px;">
  <img src="{$this->cont['LOADER_URL']}?src=fusen.gif" width="20" height="20" alt="{$this->msg['cap_fusen_func']}" title="{$this->msg['cap_fusen_func']}" />
  [<a href="JavaScript:fusen_new()" title="{$this->msg['cap_menu_new']}">{$this->msg['btn_menu_new']}</a>]
  [<a href="JavaScript:fusen_dustbox()" title="{$this->msg['cap_menu_dust']}">{$this->msg['btn_menu_dust']}</a>{$burn}]
  [<a href="JavaScript:fusen_transparent()" title="{$this->msg['cap_menu_transparent']}">{$this->msg['btn_menu_transparent']}</a>]
  [<a href="JavaScript:fusen_init(1)" title="{$this->msg['cap_menu_refresh']}">{$this->msg['btn_menu_refresh']}</a>]
  [<a href="JavaScript:fusen_show('fusen_list')" title="{$this->msg['cap_menu_list']}">{$this->msg['btn_menu_list']}</a>]
  [<a href="JavaScript:fusen_show('fusen_help')" title="{$this->msg['cap_menu_help']}">{$this->msg['btn_menu_help']}</a>]&nbsp;
  {$this->msg['cap_menu_search']}:<input type="text" onkeyup="JavaScript:fusen_grep(this.value)" />
  {$refresh_str}
</form>
</div>
<noscript>
<div class="fusen_top_menu">{$this->msg['msg_not_work']}</div>
</noscript>
</fieldset>
<div class="{$divclass}" id="fusen_container">
	<div id="fusen_editbox" class="fusen_editbox">
	  <div class="fusen_editbox_title">{$this->msg['cap_fusen_edit']}</div>
	  <form id="edit_frm" method="post" action="" style="padding:0px; margin:0px" onsubmit="fusen_save(); return false;">
	      <textarea name="body" id="edit_body" cols="50" rows="5"></textarea>
	      {$this->msg['cap_fore_color']}:<select id="edit_tc" name="tc" size="1">
	        <option id="tc000000" value="#000000" style="color: #000000" selected>&#9632;{$this->msg['cap_black']}</option>
	        <option id="tc999999" value="#999999" style="color: #999999">&#9632;{$this->msg['cap_gray']}</option>
	        <option id="tcff0000" value="#ff0000" style="color: #ff0000">&#9632;{$this->msg['cap_red']}</option>
	        <option id="tc00ff00" value="#00ff00" style="color: #00ff00">&#9632;{$this->msg['cap_green']}</option>
	        <option id="tc0000ff" value="#0000ff" style="color: #0000ff">&#9632;{$this->msg['cap_blue']}</option>
	      </select>
	      {$this->msg['cap_back_color']}:<select id="edit_bg" name="bg" size="1">
	        <option id="bgffffff" value="#ffffff" style="background-color: #ffffff" selected>{$this->msg['cap_white']}</option>
	        <option id="bgffaaaa" value="#ffaaaa" style="background-color: #ffaaaa">{$this->msg['cap_lightred']}</option>
	        <option id="bgaaffaa" value="#aaffaa" style="background-color: #aaffaa">{$this->msg['cap_lightgreen']}</option>
	        <option id="bgaaaaff" value="#aaaaff" style="background-color: #aaaaff">{$this->msg['cap_lightblue']}</option>
	        <option id="bgffffaa" value="#ffffaa" style="background-color: #ffffaa">{$this->msg['cap_lightyellow']}</option>
	        <option id="bgransparent" value="transparent">{$this->msg['cap_transparent']}</option>
	      </select><br />
	      {$this->msg['cap_name']}:<input type="text" name="name" id="edit_name" value="{$name}"/>&nbsp;
	      {$this->msg['cap_lineid']}:<input type="text" name="ln" id="edit_ln" size="4" /><br />
	      <input type="submit" value="{$this->msg['btn_write']}" />&nbsp;
	      <input type="button" value="{$this->msg['btn_close']}" onclick="fusen_editbox_hide();" />
	      <input type="hidden" name="id" id="edit_id" value="-1" />
	      <input type="hidden" name="z" id="edit_z" value="1" />
	      <input type="hidden" name="l" id="edit_l" />
	      <input type="hidden" name="t" id="edit_t" />
	      <input type="hidden" name="w" id="edit_w" value="0" />
	      <input type="hidden" name="h" id="edit_h" value="0" />
	      <input type="hidden" name="fix" id="edit_fix" value="0" />
	      <input type="hidden" name="bx" id="edit_bx" value="0" />
	      <input type="hidden" name="by" id="edit_by" value="0" />
	      <input type="hidden" name="mode" id="edit_mode" value="edit" />
	      <input type="hidden" name="plugin" value="fusen" />
	      <input type="hidden" name="refer" value="{$refer}" />
	      <input type="hidden" name="page" value="{$refer}" />
	  </form>
	  <div class="fusen_editbox_footer">
	  <form action="" onsubmit="return false;" style="width:auto;padding:0px;margin:0px;">
	    [<a href="JavaScript:fusen_dustbox()" title="{$this->msg['cap_menu_dust']}">{$this->msg['btn_menu_dust']}</a>]
	    [<a href="JavaScript:fusen_transparent()" title="{$this->msg['cap_menu_transparent']}">{$this->msg['btn_menu_transparent']}</a>]
	    [<a href="JavaScript:fusen_show('fusen_list')" title="{$this->msg['cap_menu_list']}">{$this->msg['btn_menu_list']}</a>]
	    [<a href="JavaScript:fusen_show('fusen_help')" title="{$this->msg['cap_menu_help']}">{$this->msg['btn_menu_help']}</a>]&nbsp;
	    {$this->msg['cap_menu_search']}:<input type="text" size="20" onkeyup="JavaScript:fusen_grep(this.value)" />
	  </form>
	  </div>
	</div>
	<div id="fusen_help" class="fusen_help"></div>
	<div id="fusen_list" class="fusen_list"></div>
	<div id="fusen_area">$html</div>
</div>
{$board}
EOD;
	}
	
	
	function plugin_fusen_action() {
		
		$id = preg_replace('/id/', '', $this->root->vars['id']);
		
		// 規定外のモード
		if ($id < 0 || !in_array($this->root->vars['mode'],array('set','del','lock','unlock','recover','edit','burn','del_m'))) {
			ob_clean();
			exit;
		}
		
		// ゲストユーザーの投稿制限(SPAM対策)
		$plugin_fusen_setting['max_chr'] = 500; // 最大文字数
		$plugin_fusen_setting['max_link'] = 3;  // http:// の最大個数
		$plugin_fusen_setting['max_a_tag'] = 0; // <a>タグの最大個数
		
		$refer = $this->root->vars['page'] = $this->root->vars['page'] = $this->root->vars['refer'];
		
		// コンバートしないでファイル読み込み
		$dat = $this->plugin_fusen_data($refer,false);

		$auth = false;
		
		// 一括ゴミ箱モード
		if ($this->root->vars['mode'] == "del_m")
		{
			$ids = explode(",",$id);
			$id = "";
		}
		
		if ($id && array_key_exists($id,$dat))
		{
			if ($this->func->is_owner($refer)) $auth = true;
			else if ($dat[$id]['uid'] && $dat[$id]['uid'] == $this->root->userinfo['uid']) $auth = true;
			else if (!$dat[$id]['uid'] && $dat[$id]['ucd'] && $dat[$id]['ucd'] == $this->root->userinfo['ucd']) $auth = true;
		}
		else
		{
			// 一括モード
			if ($this->root->vars['mode'] == "burn")
			{
				if ($this->func->is_owner($refer)) $auth = true;
			}
			else if ($this->root->vars['mode'] == "del_m")
			{
				$auth = false;
			}
			else
			{
				$auth = true;
			}
		}
		
		// ID確定,データ取得
		switch ($this->root->vars['mode'])
		{
			case 'set':
			case 'del':
			case 'lock':
			case 'unlock':
			case 'recover':
				if (!array_key_exists($id,$dat)) $this->func->die_message('The data is not accumulated just.'."($id)");
			case 'burn':
			case 'del_m':
				// ページHTMLキャッシュを削除
				//$this->func->delete_page_html($refer,"html");
				// touch
				if ($id) $dat[$id]['tt'] = time();
				//値更新
				switch ($this->root->vars['mode'])
				{
					case 'set':
						if (!$dat[$id]['lk']) $auth = true;
						$dat[$id]['x'] = (preg_match('/^\d+$/', $this->root->vars['l']) ? $this->root->vars['l'] : '');
						$dat[$id]['y'] = (preg_match('/^\d+$/', $this->root->vars['t']) ? $this->root->vars['t'] : '');
						$dat[$id]['bx'] = (preg_match('/^\d+$/', $this->root->vars['l']) ? $this->root->vars['bx'] : 0);
						$dat[$id]['by'] = (preg_match('/^\d+$/', $this->root->vars['t']) ? $this->root->vars['by'] : 0);
						$dat[$id]['w'] = (preg_match('/^\d+$/', $this->root->vars['w']) ? $this->root->vars['w'] : 0);
						$dat[$id]['h'] = (preg_match('/^\d+$/', $this->root->vars['h']) ? $this->root->vars['h'] : 0);
						$dat[$id]['fix'] = (!empty($this->root->vars['fix'])) ? (int)$this->root->vars['fix'] : 0;
						$dat[$id]['z'] = (preg_match('/^\d+$/', $this->root->vars['z']) ? $this->root->vars['z'] : '');
						break;
					case 'lock':
						$dat[$id]['lk'] = true;
						break;
					case 'unlock':
						$dat[$id]['lk'] = false;
						break;
					case 'del':
						if (empty($dat[$id]['del']))
						{
							$dat[$id]['del'] = true;
							$dat[$id]['lk'] = false;
							//$dat[$id]['ln'] = '';
						}
						else
						{
							unset($dat[$id]);
							// plane_text DB を更新
							$this->func->need_update_plaindb($refer);
						}
						foreach($dat as $k=>$v)
						{
							if ($dat[$k]['ln'] == 'id'.$id) $dat[$k]['ln'] = '';
						}
						break;
					case 'recover':
						$dat[$id]['del'] = false;
						break;
					case 'burn':
						$burned = false;
						foreach($dat as $k=>$v)
						{
							if (!empty($dat[$k]['del']))
							{
								unset($dat[$k]);
								$burned = true;
							}
						}
						//if ($burned) $this->func->need_update_plaindb($refer);
						break;
					case 'del_m':
						foreach($ids as $id)
						{
							$_auth = false;
							if ($this->func->is_owner($refer)) $_auth = true;
							else if ($dat[$id]['uid'] && $dat[$id]['uid'] == $this->root->userinfo['uid']) $_auth = true;
							else if (!$dat[$id]['uid'] && $dat[$id]['ucd'] && $dat[$id]['ucd'] === $this->root->userinfo['ucd']) $_auth = true;
							if ($_auth)
							{
								$dat[$id]['del'] = true;
								$dat[$id]['lk'] = false;
							}
							if ($_auth) $auth = true;
						}
						break;
				}
				break;
			case 'edit':
				if ($id == '')
				{
					krsort($dat);
					$id = array_shift(array_keys($dat)) + 1;
					$mt = date("ymdHis");
					$uid = $this->root->userinfo['uid'];
					$ucd = $this->root->userinfo['ucd'];
					$name = $this->root->vars['name'];
					//$this->func->make_user_link($name);
				}
				else
				{
					//if (!$dat[$id]['lk']) $auth = true;
					if (!array_key_exists($id,$dat)) $this->func->die_message('The data is not accumulated just.'."($id)");
					$mt = $dat[$id]['mt'];
					$uid = $dat[$id]['uid'];
					$ucd = $dat[$id]['ucd'];
					$name = $dat[$id]['name'];
				}
				if ($auth)
				{
					$txt = str_replace(array("\r\n","\r"),"\n",$this->root->vars['body']);
					
					// SPAM判定(ゲストのみ)
					if (!$this->root->userinfo['uid'])
					{
						$match = array();
						// 最大文字数(1000文字以上)
						if (strlen($txt) > $plugin_fusen_setting['max_chr']) exit();
						// <a>タグ検出
						if (preg_match_all("#<a[^>]*>#i",$txt,$match,PREG_PATTERN_ORDER))
						{
							if (count($match[0]) > $plugin_fusen_setting['max_a_tag']) exit();
						}
						// http:// の個数(5個以上)
						if (preg_match_all("#https?://#i",$txt,$match,PREG_PATTERN_ORDER))
						{
							if (count($match[0]) > $plugin_fusen_setting['max_link']) exit();
						}
					}
					
					$txt = preg_replace('/^#fusen/m', '&#35;fusen', $txt);
					//$txt = $this->func->user_rules_str($this->func->auto_br($txt));
					$txt = rtrim($txt);
					
					$et = date("ymdHis");
					$fix = (!empty($this->root->vars['fix']))? (int)$this->root->vars['fix'] : 0;
					$w = (preg_match('/^\d+$/', $this->root->vars['w']))? $this->root->vars['w'] : 0;
					$h = (preg_match('/^\d+$/', $this->root->vars['h']))? $this->root->vars['h'] : 0;
					
					$ma = array();
					$dat[$id] = array(
						'ln' => (preg_match('/^(id)?(\d+)$/', $this->root->vars['ln'], $ma) ? $ma[2] : ''),
					'x' => (preg_match('/^\d+$/', $this->root->vars['l']) ? $this->root->vars['l'] : 100),
					'y' => (preg_match('/^\d+$/', $this->root->vars['t']) ? $this->root->vars['t'] : 100),
					'bx' => (preg_match('/^\d+$/', $this->root->vars['bx']) ? $this->root->vars['bx'] : 0),
					'by' => (preg_match('/^\d+$/', $this->root->vars['by']) ? $this->root->vars['by'] : 0),
					'z' => 1,
					'tc' => (preg_match('/^#[\dA-F]{6}$/i', $this->root->vars['tc']) ? $this->root->vars['tc'] : '#000000'),
					'bg' => (preg_match('/^(#[\dA-F]{6}|transparent)$/i', $this->root->vars['bg']) ? $this->root->vars['bg'] : '#ffffff'),
					'lk' => false,
					'txt' => $txt,
					'name' => $name,
					'mt' => $mt,
					'et' => $et,
					'tt' => time(),
					'uid' => $uid,
					'ucd' => $ucd,
					'fix' => $fix,
					'w' => $w,
					'h' => $h,
				);
					
					ksort($dat);
					
					// NULLバイト削除
					$dat = $this->func->input_filter($dat);
					
					// plane_text DB 更新を指示
					$this->func->need_update_plaindb($refer);
					
					// ページHTMLキャッシュとRSSキャッシュを削除
					$this->func->clear_page_cache($refer);
					$GLOBALS['xpwiki_cache_deletes'][$this->cont['CACHE_DIR'].'plugin/'][] = '*.rss';
					
				}
				break;
			default:
				$this->func->die_message('Illegitimate parameter was used.');
		}

		if ($auth) {
			//書き込み
			if (!$this->func->exist_plugin('attach'))
			{
				exit ('attach.inc.php not found or not correct version.');
			}
			
			$atatch_obj = $this->func->get_plugin_instance('attach');
			
			if (count($dat) < 1) $dat = array();
			
			$fname = $this->cont['UPLOAD_DIR'] . $this->func->encode($refer) . '_' . $this->func->encode($this->cont['FUSEN_ATTACH_FILENAME']);
			if ($fp = fopen($fname.".tmp", "wb"))
			{
				flock($fp, LOCK_EX);
				fputs($fp, $this->cont['FUSEN_ATTACH_FILENAME'] . "\n");
				fputs($fp, serialize($dat));
				fclose($fp);
				$this->root->pukiwiki_allow_extensions = "";
				$options = array(
							'overwrite' => TRUE,
							'changelog' => '[Fusen:' . $id . ' by ' . htmlspecialchars($name) . ']' . htmlspecialchars($txt),
							);
				if ($this->root->vars['mode'] == 'edit')
				{
					// 編集時はタイムスタンプを更新する
					$ret = $atatch_obj->do_upload($refer, $this->cont['FUSEN_ATTACH_FILENAME'], $fname.".tmp",FALSE,NULL,FALSE,$options);
				}
				else
				{
					// その他はタイムスタンプを更新しない
					$ret = $atatch_obj->do_upload($refer, $this->cont['FUSEN_ATTACH_FILENAME'], $fname.".tmp",FALSE,NULL,TRUE,$options);
				}
			}
			
			// キャッシュ破棄
			@unlink($this->cont['CACHE_DIR'] . 'plugin/' . $this->func->get_pgid_by_name($refer).".fusen");
			$GLOBALS['xpwiki_cache_deletes'][$this->cont['CACHE_DIR'].'plugin/'][] = '*' . $this->func->get_pgid_by_name($refer) . '.fusen';			
			$this->func->delete_caches();
			clearstatcache();
			
			// コンバートして再読み込み
			$dat = $this->plugin_fusen_data($refer);
			// JSONファイル書き込み
			$this->plugin_fusen_putjson($dat,$refer);
		}
		ob_clean();
		exit;
	}
	
	//添付ファイル読み込み
	function plugin_fusen_data($page, $convert=true)
	{
		$fname = $this->func->encode($page) . '_' . $this->func->encode($this->cont['FUSEN_ATTACH_FILENAME']);
		if (!file_exists($this->cont['UPLOAD_DIR'] . $fname)) return array();
		$data = file($this->cont['UPLOAD_DIR'] . $fname);
		if (!$data || trim(array_shift($data)) != $this->cont['FUSEN_ATTACH_FILENAME']) return array();
		
		$data = unserialize(join('',$data));
		
		if (!$convert) return $data;
		
		// キャッシュチェック
		$cfile = $this->cont['CACHE_DIR'] . 'plugin/' . $this->cont['UI_LANG'] . '.' . $this->func->get_pgid_by_name($page).".fusen";
		if (file_exists($cfile) && (@ $this->root->rtf['use_cache_always'] || filemtime($cfile) > time() - $this->root->pagecache_min * 60)) {
			return unserialize(join('',file($cfile)));
		}
		
		// 一括してコンバートする
		$str = '';
		foreach ($data as $k => $dat)
		{
			$str .= "###fusen_data_convert###{$k}\n\n".$dat['txt']."\n\n";
		}
		
		$this->fusen_convert_html($str,$page);
		
		$str = trim(str_replace("\r","",$str));
		
		$str_ary = preg_split("/<p>###fusen_data_convert###/",$str);
		array_shift($str_ary);
		foreach ($str_ary as $str)
		{
			list($id,$dat) = explode("\n",$str,2);
			$data[rtrim($id,'</p>')]['disp'] = trim($dat);
		}
		
		if($fp = fopen($cfile, "wb"))
		{
			flock($fp, LOCK_EX);
			fputs($fp, serialize($data));
			fclose($fp);
		}
	
		return $data;
	}
	
	//PHPオブジェクトをJSONへ変換
	function plugin_fusen_getjson($fusen_data)
	{
		// ゲストとして処理
		$_userinfo = $this->root->userinfo;
		$this->root->userinfo['uid'] = $this->root->userinfo['admin'] = 0;
		
		// 付箋・線データ作成
		$json = '{';
		foreach ($fusen_data as $k => $dat) {
			//付箋番号が数字でない場合は飛ばす。
			if (!preg_match('/\d+/', $k)) continue;
			$id = 'id' . $k;
	
			//#fusenプラグインのネスト禁止
			$dat['txt'] = preg_replace('/^#fusen/m', '&#35;fusen', $dat['txt']);
	
			// XSS対策(付箋データが直接改ざんされる事態も想定)
			if (!preg_match('/^\d+$/', $dat['x'])) $dat['x'] = 100 + $k;
			if (!preg_match('/^\d+$/', $dat['y'])) $dat['y'] = 100 + $k;
			if (!preg_match('/^\d+$/', $dat['bx'])) $dat['bx'] = 0;
			if (!preg_match('/^\d+$/', $dat['by'])) $dat['by'] = 0;
			if (!preg_match('/^\d+$/', $dat['z'])) $dat['z'] = 1;
			if (!preg_match('/^#[\dA-F]{6}$/i', $dat['tc'])) $dat['tc'] = '#000000';
			if (!preg_match('/^(#[\dA-F]{6}|transparent)$/i', $dat['bg'])) $dat['bg'] = '#ffffff';
			if (!preg_match('/^(id)?\d+$/', $dat['ln'])) $dat['ln'] = '';
			if (!preg_match('/^\d+$/', $dat['mt'])) $dat['mt'] = 0;
			if (!preg_match('/^\d+$/', $dat['et'])) $dat['et'] = 0;
			
			// ~\n -> \n
			$dat['txt'] = preg_replace("/~$/m","",$dat['txt']);
			
			// 改行文字等除去
			$dat['disp'] = str_replace(array("\r","\n","\t"),'',$dat['disp']);
	
			// JSONの構成
			if ($json != '{') $json .= ",\n";
			$json .=  $k . ':{';
			$json .= '"x":' . $dat['x'] . ',';
			$json .= '"y":' . $dat['y'] . ',';
			$json .= '"bx":' . $dat['bx'] . ',';
			$json .= '"by":' . $dat['by'] . ',';
			$json .= '"z":' . $dat['z'] . ',';
			$json .= '"tc":"' . $dat['tc'] . '",';
			$json .= '"bg":"' . $dat['bg'] . '",';
			$json .= '"disp":"' .$this->plugin_fusen_jsencode($dat['disp']) . '",';
			$json .= '"txt":"' . $this->plugin_fusen_jsencode(htmlspecialchars($dat['txt'])) . '",';
			$json .= '"name":"' . $this->plugin_fusen_jsencode($this->func->make_link($dat['name'])) . '",';
			$json .= '"mt":"' . ($dat['mt']? $dat['mt'] : "" ) . '",';
			$json .= '"et":"' . ($dat['et']? $dat['et'] : "" ) . '",';
			$json .= '"tt":' . ($dat['tt']? $dat['tt'] : 0 ) . ',';
			$json .= '"uid":' . ($dat['uid']? $dat['uid'] : 0 ) . ',';
			$json .= '"ucd":"' . ($dat['ucd']? $dat['ucd'] : "" ) . '",';
			$json .= '"fix":' . (empty($dat['fix'])? 0 : (int)$dat['fix'] ) . ',';
			$json .= '"w":' . ($dat['w']? $dat['w'] : 0 ) . ',';
			$json .= '"h":' . ($dat['h']? $dat['h'] : 0 ) . '';
			if (isset($dat['ln']) && $dat['ln']) $json .= ',"ln":' . preg_replace('/^id/', '', $dat['ln']) ;
			if (isset($dat['lk']) && $dat['lk']) $json .= ',"lk":' . $dat['lk'];
			if (isset($dat['del']) && $dat['del']) $json .= ',"del":' . $dat['del'] ;
			$json .= '}';
		}
		$json .= '}';
		
		//ログイン情報戻し
		$this->root->userinfo = $_userinfo;
		
		return $json;
	}
	
	//JSON向けエンコード
	function plugin_fusen_jsencode($str) {
		$str = preg_replace('/(\x22|\x2F|\x5C)/', '\\\$1', $str);
		$str = str_replace(array("\x00","\x08","\x09","\x0A","\x0C","\x0D"), array('','\b','\t','\n','\f','\r'), $str);
		return $str;
	}
	
	//PHPオブジェクトをHTMLへ変換
	function plugin_fusen_gethtml($fusen_data, $page = null)
	{
		if (is_null($page)) {
			$page = @ $this->root->vars['page'];
		}
		
		if ($page) {
			//JSONファイル書き込み
			$this->plugin_fusen_putjson($fusen_data, $page);
		}
		
		if (!$fusen_data) return '';
		
		// 付箋・線データ作成
		$ret = '';
		foreach ($fusen_data as $k => $dat) {
			//付箋番号が数字でない場合は飛ばす。
			if (!preg_match('/\d+/', $k)) continue;
			$id = 'id' . $k;
	
			//#fusenプラグインのネスト禁止
			$dat['txt'] = preg_replace('/^#fusen/m', '&#35;fusen', $dat['txt']);
	
			// XSS対策(付箋データが直接改ざんされる事態も想定)
			if (!preg_match('/^\d+$/', $dat['x'])) $dat['x'] = 100 + $k;
			if (!preg_match('/^\d+$/', $dat['y'])) $dat['y'] = 100 + $k;
			if (!preg_match('/^\d+$/', $dat['bx'])) $dat['bx'] = 0;
			if (!preg_match('/^\d+$/', $dat['by'])) $dat['by'] = 0;
			if (!preg_match('/^\d+$/', $dat['z'])) $dat['z'] = 1;
			if (!preg_match('/^#[\dA-F]{6}$/i', $dat['tc'])) $dat['tc'] = '#000000';
			if (!preg_match('/^(#[\dA-F]{6}|transparent)$/i', $dat['bg'])) $dat['bg'] = '#ffffff';
			if (!preg_match('/^(id)?\d+$/', $dat['ln'])) $dat['ln'] = '';
	
			// HTMLの構成
			
			if ($dat['lk']) $border = $this->cont['FUSEN_STYLE_BORDER_LOCK'];
			else if (!empty($dat['del'])) $border = $this->cont['FUSEN_STYLE_BORDER_DEL'];
			else $border = $this->cont['FUSEN_STYLE_BORDER_NORMAL'];
			
			// SEOスパムと誤認されないように
			//$del = (empty($dat['del']))? "" : " visibility: hidden;";
			if (!empty($dat['del'])) continue;
			$del = '';
			
			$date = ($dat['et'])? " : ".substr($dat['et'],0,2)."/".substr($dat['et'],2,2)."/".substr($dat['et'],4,2)." ".substr($dat['et'],6,2).":".substr($dat['et'],8,2) : "";
			
			// Fix?
			$fix_style = "";
			if ($dat['fix'])
			{
				$fix_style .= "overflow:hidden;";
				$fix_style .= "white-space:normal;";
				$fix_style .= "width:{$dat['w']}px;";
				$fix_style .= ($dat['fix'] == 1)? "height:{$dat['h']}px;" : "height:auto;";
			}
	
			
			$ret .= "<div class=\"fusen_body_trans\" style=\"left:{$dat['x']}px; top:{$dat['y']}px; color:{$dat['tc']}; background-color:{$dat['bg']}; border:{$border};{$del}{$fix_style}\">\n";
			$ret .= "<div class=\"fusen_menu\">id.{$k}: </div>\n";
			$ret .= "<div class=\"fusen_info\"><span class=\"fusen_name\">".$this->func->make_link($dat['name']).'</span> : <span class="fusen_date">'.$date."</span></div>\n";
			$ret .= "<div class=\"fusen_contents\">{$dat['disp']}</div>\n";
			$ret .= "</div>\n";
		}
		return $ret;
	}
	
	//JSONキャッシュファイル書き込み
	function plugin_fusen_putjson($dat,$page)
	{
		$fname = $this->cont['CACHE_DIR'] . "plugin/fusen_". $this->func->get_pgid_by_name($page) . ".pcache.xml";
		$json = $this->plugin_fusen_getjson($dat);
		$to = 'UTF-8';
		$json = '<?xml version="1.0" encoding="UTF-8"?'.'>'."\n".'<fusen><![CDATA[' . str_replace("\0","",mb_convert_encoding($json, $to, $this->cont['SOURCE_ENCODING'])) . ']]></fusen>';
		
		// 変更チェック
		$old = @join('',@file($fname));
		if ($json == $old) return;
		
		$fp = false;
		$count = 0;
		while(!$fp && ++$count < 6)
		{
			if($fp = fopen($fname, "wb"))
			{
				flock($fp, LOCK_EX);
				fputs($fp, $json);
				fclose($fp);
			}
			else
			{
				sleep(1);
			}
		}
	}
	
	function fusen_convert_html(&$str,$page)
	{
		// グローバル変数退避
		$_userinfo = $this->root->userinfo;
		$_related_link = $this->root->related_link;
		
		$this->root->userinfo['admin'] = $this->root->userinfo['uid'] = 0;	//常にゲスト扱い
		$this->root->related_link = 0;	// 関連するページをリストアップしない
		$this->root->vars['cmd'] = "read"; //閲覧モードでコンバート
		
		$str = $this->func->convert_html($str, $page);
		
		// グローバル変数戻し
		$this->root->userinfo = $_userinfo;
		$this->root->related_link = $_related_link;
		
		return $str;
	}
}
?>