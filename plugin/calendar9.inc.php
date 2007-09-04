<?php
/*
 * Created on 2007/08/30 by nao-pon http://hypweb.net/
 * $Id: calendar9.inc.php,v 1.3 2007/09/04 06:24:22 nao-pon Exp $
 */

class xpwiki_plugin_calendar9 extends xpwiki_plugin {
	function plugin_calendar9_init () {


	// $Id: calendar9.inc.php Ver.1.5
	// *引数にoffと書くことで今日の日記を表示しないようにした。
	// * calendar3を改造しました。
	
	// 変更内容
	//   PageName/DayOff に設定した休日情報を元に休日を表示する
	//   月末が週の途中の場合は週末までの翌月データを表示する
	//   prototype.js を使用してAJAX対応に
	//  2007/07/17 Ver.1.1
	//   読込み中に編集できないように修正
	//   Ajaxの通信をXML形式にしてSafari対応
	//   文字コード指定(SOURCE_ENCODING)対応
	//   週の開始日の指定に対応
	//  2007/07/19 Ver.1.2
	//   週の開始日をパラメータからも指定できるようにした
	//   休日判定の精度を上げた
	//  2007/08/17 Ver.1.3
	//   Ajaxで読込むXMLデータに & < > ' " の文字があるときに読込めないバグを修正
	//  2007/08/20 Ver.1.4
	//   スキン使用時にきれいに表示されない現象の対応
	//  2007/08/21 Ver.1.5
	//   凍結されたページをチェックしないで更新していたので更新できないように修正
	//
	
	// prototype.js の指定
	//	$this->cont['PROTOTYPEJS_FILE'] =  DATA_HOME . 'skin/prototype.js';
	
	// 週の開始日 0:日 1:月 2:火 3:水 4:木 5:金 6:土
		$this->cont['BEGINNING_DAY_OF_WEEK'] =  0;

	}
	
	function plugin_calendar9_convert()
	{
	//	global $script,$vars,$post,$get,$weeklabels,$WikiName,$BracketName;
	//	global $_calendar9_plugin_edit, $_calendar9_plugin_empty;
		$this->func->add_tag_head('prototype.js');
		$this->func->add_tag_head('calendar.css');
		
		$date_str = $this->func->get_date('Ym');
		$base = $this->func->strip_bracket($this->root->vars['page']);
		$beginningDayOfWeek = $this->cont['BEGINNING_DAY_OF_WEEK'];
		
		// パラメータ解析
		if (func_num_args() > 0) {
			$args = func_get_args();
			foreach ($args as $arg) {
				if (is_numeric($arg) && strlen($arg) == 6) {
					$date_str = $arg;
				}
				else if (is_numeric($arg) && strlen($arg) == 1) {
					$beginningDayOfWeek = $arg;
				}
				else {
					$base = $this->func->strip_bracket($arg);
				}
			}
		}
		
		
		$this_weeklabels = array_merge(array_slice($this->root->weeklabels, $beginningDayOfWeek, 7 - $beginningDayOfWeek), array_slice($this->root->weeklabels, 0, $beginningDayOfWeek));
		$diffday = 7 - $beginningDayOfWeek;
		
		if ($base == '*') {
			$base = '';
			$prefix = '';
		}
		else {
			$prefix = $base.'/';
		}
		$r_base = rawurlencode($base);
		$s_base = htmlspecialchars($base);
		$r_prefix = rawurlencode($prefix);
		$s_prefix = htmlspecialchars($prefix);
		
		$yr = substr($date_str,0,4);
		$mon = substr($date_str,4,2);
		
		if ($yr != $this->func->get_date('Y') || $mon != $this->func->get_date('m')) {
			$now_day = 1;
			$other_month = 1;
		}
		else {
			$now_day = $this->func->get_date('d');
			$other_month = 0;
		}
		
		// 休日読み込み
		$dayoff_list = array();
		$day_type = array();		// 0:祝日、休日 1:記念日(タイトルのみ) 2:土曜スタイル
		$day_title = array();
		$page = $prefix.'DayOff';
		$r_page = rawurlencode($page);
		$s_page = htmlspecialchars($page);
		foreach ($this->func->get_source($s_page) as $line) {
			$line = rtrim($line, "\n");
			$d = split(",", $line);
			$dayoff_list[] = $d[0];
			if($d[2] != "") {
				$day_type[$d[0]] = $d[2];
			}
			if($d[1] != "") {
				$day_title[$d[0]] = $d[1];
			}
		}
		
		$today = getdate(mktime(0,0,0,$mon,$now_day,$yr) - $this->cont['LOCALZONE'] + $this->cont['ZONETIME']);
		
		$m_num = $today['mon'];
		$d_num = $today['mday'];
		$year = $today['year'];
		
		$f_today = getdate(mktime(0,0,0,$m_num,1,$year) - $this->cont['LOCALZONE'] + $this->cont['ZONETIME']);
		$wday = $f_today['wday'];
		$day = 1;
		
		$m_name = "$year.$m_num";
		
		$y = substr($date_str,0,4)+0;
		$m = substr($date_str,4,2)+0;
		
		$prev_date_str = ($m == 1) ?
			sprintf('%04d%02d',$y - 1,12) : sprintf('%04d%02d',$y,$m - 1);
		
		$next_date_str = ($m == 12) ?
			sprintf('%04d%02d',$y + 1,1) : sprintf('%04d%02d',$y,$m + 1);
		
		$ret = '';
		$ret .= <<<EOD
<script>
<!--
// calendar9.inc.php http://www.sakasama.com/dive/
var browserOP = window.opera;             // OP
var browserGK = document.getElementById;  // Gecko or IE
var browserIE = document.all;             // IE

function showResponse(orgRequest) {
	var xmlRes = orgRequest.responseXML;
	if(xmlRes.getElementsByTagName("editform")[0].firstChild) {
		xpwiki_ajax_edit_var['func_update_post'] = thisreload;
		xpwiki_ajax_edit_var['func_preview_pre'] = day_edit_close;
		$('xpwiki_cal9_editarea').innerHTML = xmlRes.getElementsByTagName("editform")[0].firstChild.nodeValue;
		$('xpwiki_cancel_form').innerHTML = '<button id="c9cancel" onmousedown="day_edit_close()">{$this->root->_btn_cancel}</button>';
		
		wikihelper_initTexts($('xpwiki_cal9_editarea'));
	} else {
		$("editarea").value = "";
		$("editarea").disabled = false;
	}
}

function day_edit(id,freeze) {
	var windowTop;
	var windowLeft;
	var windowWidht;
	var windowHeight;
	
	
	windowTop = document.documentElement.scrollTop || document.body.scrollTop || 0;
	windowLeft = document.documentElement.scrollLeft || document.body.scrollLeft || 0;
	if(browserIE) {
		windowWidth = document.body.scrollWidth || document.documentElement.scrollWidth || 0;
		windowHeight = document.body.scrollHeight || document.documentElement.scrollHeight || 0;
	}
	else {
		windowWidth = document.documentElement.scrollWidth || document.body.scrollWidth || 0;
		windowHeight = document.documentElement.scrollHeight || document.body.scrollHeight || 0;
	}

	xpwiki_ajax_edit_var['id'] = 'xpwiki_body';
	xpwiki_ajax_edit_var["html"] = $(xpwiki_ajax_edit_var["id"]).innerHTML;
	
	// HTML BODYオブジェクト取得
	var objBody = document.getElementsByTagName("body").item(0);
	
	// 背景半透明オブジェクト作成
	var objBack = document.createElement("div");
	objBack.setAttribute('id', 'popupback');
	objBack.onclick = function() { day_edit_close(); }
	Element.setStyle(objBack, {'display': 'none'});
	Element.setStyle(objBack, {'position': 'absolute'});
	Element.setStyle(objBack, {'z-index': 90});
	Element.setStyle(objBack, {'text-align': 'center'});
	Element.setStyle(objBack, {'background-color': 'black'});
	Element.setStyle(objBack, {'filter': 'alpha(opacity=75);'});		// IE
	Element.setStyle(objBack, {'-moz-opacity': '0.75'});		// FF
	
	objBack.style.top = 0;
	objBack.style.left = 0;
	objBack.style.width = windowWidth + "px";
	objBack.style.height = windowHeight + "px";
	objBody.appendChild(objBack);
	
	// 入力ボックスオブジェクト作成
	var objPopup = document.createElement("div");
	objPopup.setAttribute('id', 'popupmain');
	var editHtml = 
	    '<div id="xpwiki_cal9_editarea">'
	  + '[ <span id="pagename">' + id + '</span> ]<br/>'
	  + '<textarea id="editarea" style="width:100%;border:1px solid" rows=20 disabled=true>Now loading...</textarea><br/>'
	  + '</div>';
	Element.update(objPopup, editHtml);
	
	Element.setStyle(objPopup, {'display': 'none'});
	Element.setStyle(objPopup, {'position': 'absolute'});
	Element.setStyle(objPopup, {'z-index': 100});
	Element.setStyle(objPopup, {'border': '2px black solid'});
	Element.setStyle(objPopup, {'background-color': 'white'});
	Element.setStyle(objPopup, {'padding': '20px'});
	
	objPopup.style.top = (windowTop + 20) + "px";
	objPopup.style.left = windowLeft + "px";
	objPopup.style.width = "700px";
	objPopup.style.left = ((windowWidth / 2) - 350) + "px";
	$('xpwiki_body').appendChild(objPopup);
	
	objBack.show();
	objPopup.show();
	
	// ページ情報を読込み反映する
	var url = location.pathname + '?cmd=edit';
	var pars = '';
	// pars +=  'mode=get'
	pars += 'page=' + encodeURIComponent(id);
	pars += '&ajax=1';
	pars += 'encode_hint=' + encodeURIComponent("{$this->cont['PKWK_ENCODING_HINT']}");
	
	if(freeze == 1) {
		$('c9update').disabled = true;
		$('c9update').hide();
	}
	var myAjax = new Ajax.Request(
		url, 
		{
			method: 'post',
			parameters: pars,
			onComplete: showResponse
		});
}

// ポップアップウィンドウを閉じる
function day_edit_close() {
	wikihelper_hide_helper();
	Element.remove($("popupback"));
	Element.remove($("popupmain"));
}

function thisreload() {
	window.location.reload();
}

// 内容の更新
function day_update() {
	var id = $('pagename').innerHTML;
	var url = location.pathname + '?cmd=calendar9';
	var pars = ''
	pars +=  'mode=set';
	pars += '&page=' + encodeURIComponent(id);
	pars += '&text=' + encodeURIComponent($('editarea').value);
	var myAjax = new Ajax.Request(
		url, 
		{
			method: 'post',
			parameters: pars,
			onComplete: thisreload
		});
}

function day_focus(id) {
	var tdobj = $(id);
	tdobj.style.border = "red 1px solid";
}

function day_unfocus(id, orgstyle) {
	var tdobj = $(id);
	tdobj.style.border = "#eeeeee 1px solid";
}

-->
</script>
   <table class="style_calendar" cellspacing="1" border="0" summary="calendar body" style="width:98%;">
    <tr>
     <td class="style_td_caltop" colspan="7">
      <a href="{$this->root->script}?plugin=calendar9&amp;file=$r_base&amp;date=$prev_date_str">&lt;&lt;</a>
      <strong>$m_name</strong>
      <a href="{$this->root->script}?plugin=calendar9&amp;file=$r_base&amp;date=$next_date_str">&gt;&gt;</a>
EOD;
		
		if ($prefix) {
			$ret .= "\n      <br />[<a href=\"{$this->root->script}?$r_base\">$s_base</a>]";
		}
		
		$ret .= "\n     </td>\n    </tr>\n    <tr>\n";
		
		foreach($this_weeklabels as $label) {
			$ret .= "     <td class=\"style_td_week\">$label</td>\n";
		}
		
		$ret .= "    </tr>\n    <tr>\n";
		// Blank
		for ($i = 0; $i < ($wday + $diffday) % 7; $i++) {
			$ret .= "     <td class=\"style_td_blank\">&nbsp;</td>\n";
		}
		
		$next_month = false;
		while (true) {
			// 次の月のデータを処理
			if(checkdate($m_num,$day,$year) == false) {
				$next_month = true;
				$day = 1;
				$m_num++;
				if($m_num > 12) {
					$m_num = 1;
					$year++;
				}
			}
			if($next_month == true and ($wday + $diffday) % 7 == 0) {
				break;
			}
			$dt = sprintf('%4d-%02d-%02d', $year, $m_num, $day);
			$dtnum = $year * 10000 + $m_num * 100 + $day;
			$dtkey = '';
			$titlecolor = '';
			$page = $prefix.$dt;
			$r_page = rawurlencode($page);
			$s_page = htmlspecialchars($page);
			//$href = $this->root->script . '?cmd=read&amp;page=' . $r_page;
			//$href = $this->func->get_page_uri($page, true);
			
			if (($wday + $diffday) % 7 == 0 and $day > 1) {
				$ret .= "    </tr>\n    <tr>\n";
			}
			
			// バックカラーの設定
			$style = 'style_td_day';		// Weekday
			
			$matchMonthDay = 0;
			$matchFull = 0;
			if (array_search($dtnum % 10000, $dayoff_list) == true) {
				$matchMonthDay = 1;
				$dtkey = substr($dtnum, 4, 4);
			}
			if(array_search($dtnum, $dayoff_list) == true) {
				$matchFull = 1;
				$dtkey = $dtnum;
			}
			
			if ($wday == 0) {		// Sunday 
				$style = 'style_td_sun';
			}
			if ($wday == 6) {		//  Saturday is_page
				$style = 'style_td_sat';
			}
			if ($matchFull == 1 || $matchMonthDay == 1) {		// Day-Off
				if ($day_type[$dtkey] == 0) {
					$style = 'style_td_sun';
					$titlecolor = 'red';
				}
				else if ($day_type[$dtkey] == 2) {
					$style = 'style_td_sat';
				}
				else {
					$titlecolor = 'black';
				}
			}
			if (!$other_month && ($day == $today['mday']) && ($m_num == $today['mon']) && ($year == $today['year'])) {		// Today
				$style = 'style_td_today';
			}
			
			// 各日付のタイトルを取得してタイトルのみ出力する
			$strr = "";
			if(array_key_exists($dtkey, $day_title)) {
				$strr = '<font color="' . $titlecolor . '">' . $day_title[$dtkey] . "</font><br />\n";
			}
			
			$freeze = -1;
			
			$_page = $page;
			$i = 0;

			// 日付へのリンク
			while($this->func->is_page($_page)) {
				$subtitle = '';
				
				// タイトルと内容の取得を行う
				if ($this->func->check_readable($_page, false, false)) {
					foreach ($this->func->get_source($_page) as $line) {
						if($freeze == -1) {
							if(strncmp($line, "#freeze", 7) == 0) {
								$freeze = 1;
							}
							else {
								$freeze = 0;
							}
						}
						if( $line{0} === '*' || $line{0} === '#' || !trim($line)) {
							continue;
						}
						else {
							$subtitle = strip_tags($this->func->convert_html($line));
							break;
						}
					}
				
					// ボックスに入れるテキストを作成する。
					$href = $this->func->get_page_uri($_page, true);
					$linkstr = '<a href="' . $href . '" onmousedown="Event.stop(event||window.event)">' . $this->func->get_heading($_page) . '</a>'."\n";
					if($subtitle) {
						$linkstr .= '<br />' . $subtitle;
					}
					
					$strr .= '<div style="border:solid 1px #aaaaaa;margin:1px;padding:1px;background-color:white;font-weight:normal;">' . $linkstr . '</div>';
				}
				++$i;
				$_page = $base.'/'.$dt.'-'.$i;
			}

			if ($this->func->check_editable($_page, false, false) && $freeze !== 1) {
				$r_page = rawurlencode($_page);
				$link = "<a href=\"{$this->root->script}?cmd=edit&amp;page=$r_page\" title=\"$s_page\" style=\"font-weight:bold;\">$day</a>";
				$js = " onmouseover=\"day_focus('$dt')\" onmouseout=\"day_unfocus('$dt', '$style')\" onmousedown=\"day_edit('$_page',".$freeze.")\"";
			} else {
				$link = "<span style=\"font-weight:bold;\">$day</span>";
				$js = '';
			}
			// 
			$ret .= "     <td class=\"$style\" style=\"border:#eeeeee 1px solid;width:14.2%;height:50px;text-align:left;vertical-align:top;\" id=\"$dt\"{$js}>\n      $link <div class=\"related\" style=\"margin:3px 0px 0px 0px;text-align:left;\">$strr</div>\n     </td>\n";		//日付は上部に表示します。その日の内容は小さめのフォントで
			$day++;
			$wday = ++$wday % 7;
		}
		
		$ret .= "    </tr>\n   </table>\n";
		
		return $ret;
	}
	
	function plugin_calendar9_action()
	{
	//	global $vars;
		
		// Ajaxの処理
		if($this->root->vars['mode'] == 'get') {
			// 指定日のページデータを返す
			$s_page = htmlspecialchars($this->root->vars['page']);
			$res = join('', $this->func->get_source($s_page));
			
			if ($res == '') $res = $this->func->auto_template($this->root->vars['page']);
			$res = trim($this->func->remove_pginfo($res));
			
			// xml special chars
			$res = str_replace("&", "&amp;", $res);
			$res = str_replace("<", "&lt;", $res);
			$res = str_replace(">", "&gt;", $res);
			$res = str_replace('"', "&quot;", $res);
			$res = str_replace("'", "&apos;", $res);
			ob_clean();
			$res = mb_convert_encoding($res, 'UTF-8', $this->cont['SOURCE_ENCODING']);
			$xml = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<calendar9>$res</calendar9>
EOD;
			header ('Content-type: application/xml') ;
			header ('Content-Length: '. strlen($xml));
			echo $xml;
			exit;
		}
		if($this->root->vars['mode'] == 'set') {
			// 受け取ったデータを指定日に書き込む
			$s_page = htmlspecialchars($this->root->vars['page']);
			$source = htmlspecialchars($this->root->vars['text']);
			// xml special chars
			$source = str_replace("&apos;", "'", $source);
			$source = str_replace("&quot;", '"', $source);
			$source = str_replace("&lt;", "<", $source);
			$source = str_replace("&lt;", "<", $source);
			$source = str_replace("&gt;", ">", $source);
			$source = str_replace("&amp;", "&", $source);
			// 書き込み
			$this->func->page_write($s_page, $source);
			ob_clean();
			exit;
		}
		
		
		$page = $this->func->strip_bracket($this->root->vars['page']);
		$this->root->vars['page'] = '*';
		if ($this->root->vars['file'])
		{
			$this->root->vars['page'] = $this->root->vars['file'];
		}
		
		$date = $this->root->vars['date'];
		
		if ($date == '')
		{
			$date = $this->func->get_date("Ym");
		}
		$yy = sprintf("%04d.%02d",substr($date,0,4),substr($date,4,2));
		
		$aryargs = array($this->root->vars['page'],$date);
		$s_page = htmlspecialchars($this->root->vars['page']);
		
		$ret['msg'] = "calendar $s_page/$yy";
		$ret['body'] = call_user_func_array (array(& $this, "plugin_calendar9_convert"),$aryargs);
		
		$this->root->vars['page'] = $page;
		
		return $ret;
	}
}
?>