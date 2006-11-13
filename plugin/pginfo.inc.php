<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: pginfo.inc.php,v 1.2 2006/11/13 11:56:22 nao-pon Exp $
//

class xpwiki_plugin_pginfo extends xpwiki_plugin {

	function plugin_pginfo_init()
	{
		// 言語ファイルの読み込み
		$this->load_language();
	}
	
	function plugin_pginfo_convert() {
		return '';
	}
	
	function plugin_pginfo_action()
	{
		$pmode = (empty($this->root->post['pmode']))? '' : $this->root->post['pmode'];
		$page = (empty($this->root->vars['page']))? '' : $this->root->vars['page'];
		if (!$page) {
			if ($pmode === 'update') {
				// DB Update
				exit('Do not work this version.'."<script>parent.xpwiki_pginfo_done();parent.xpwiki_pginfo_blink('stop');</script>");
				return $this->do_dbupdate();
			} else { 
				// 管理画面モード指定
				if ($this->root->module['platform'] == "xoops") {
					$this->root->runmode = "xoops_admin";
				}
				return $this->show_admin_form();
			}
		}
		
		// ページオーナーチェック
		if (!$this->func->is_owner($page)) {
			$ret['msg'] = $this->msg['no_parmission_title'];
			$ret['body'] = $this->msg['no_parmission'];
			return $ret;
		}
		
		if ($pmode === 'setparm'){
			// 登録処理
			return $this->save_parm($page);
		} else {
			// ページ毎の権限設定フォーム
			return $this->show_page_form($page);
		}
	}
	
	
	
	// 登録処理
	function save_parm ($page) {
	// inherit = 0:継承指定なし, 1:規定値継承指定, 2:強制継承指定
	//           3:規定値継承した値, 4:強制継承した値
		
		// ソース読み込み
		$src = $this->func->get_source($page);
		
		// ページ情報読み込み
		$pginfo = $this->func->get_pginfo($page);
		
		// 下層ページの一覧
		$cpages = $this->func->get_existpages(NULL, '.txt', $page);
		$child_dat = array();
		$do_child = FALSE;
		
		// #pginfo 再構築
		if ($pginfo['einherit'] !== 4)
		{
			$pginfo['einherit'] = (int)@$this->root->post['einherit'];
			if ($pginfo['einherit'] === 3) {
				//設定解除
				$_pginfo = $this->func->pageinfo_inherit($page);
				$pginfo['egids'] = $_pginfo['egids'];
				$pginfo['eaids'] = $_pginfo['eaids'];
				// 下層ページも設定解除
				if ($cpages) {
					foreach ($cpages as $_page) {
						$child_dat[$_page]['einherit'] = 3;
						$child_dat[$_page]['egids'] = $_pginfo['egids'];
						$child_dat[$_page]['eaids'] = $_pginfo['eaids'];
					}
					$do_child = TRUE;
				}
			} else {
				$egid = @$this->root->post['egid'];
				if ($egid === 'select') {
					$egid = @join('&', @$this->root->post['egids']);
					if (!$egid) {$egid = 'none';}
				}
				$pginfo['egids'] = $egid;
				
				$eaid = @$this->root->post['eaid'];
				if ($eaid === 'select') {
					$eaid = @str_replace(',', '&', @$this->root->post['eaids']);
					if (!$eaid) {$eaid = 'none';}
				}
				$pginfo['eaids'] = $eaid;
			}
			// 下層ページの継承指定
			if ($cpages) {
				if ($pginfo['einherit'] === 1 || $pginfo['einherit'] === 2) {
					foreach ($cpages as $_page) {
						$child_dat[$_page]['einherit'] = $pginfo['einherit'] + 2;
						$child_dat[$_page]['egids'] = $pginfo['egids'];
						$child_dat[$_page]['eaids'] = $pginfo['eaids'];
					}
					$do_child = TRUE;
				}
			}
		}
		
		if ($pginfo['vinherit'] !== 4)
		{
			$pginfo['vinherit'] = (int)@$this->root->post['vinherit'];
			if ($pginfo['vinherit'] === 3) {
				//設定解除
				$_pginfo = $this->func->pageinfo_inherit($page);
				$pginfo['vgids'] = $_pginfo['vgids'];
				$pginfo['vaids'] = $_pginfo['vaids'];
				// 下層ページも設定解除
				if ($cpages) {
					foreach ($cpages as $_page) {
						$child_dat[$_page]['vinherit'] = 3;
						$child_dat[$_page]['vgids'] = $_pginfo['vgids'];
						$child_dat[$_page]['vaids'] = $_pginfo['vaids'];
					}
					$do_child = TRUE;
				}
			} else {
				$vgid = @$this->root->post['vgid'];
				if ($vgid === 'select') {
					$vgid = @join('&', @$this->root->post['vgids']);
					if (!$vgid) {$vgid = 'none';}
				}
				$pginfo['vgids'] = $vgid;
				
				$vaid = @$this->root->post['vaid'];
				if ($vaid === 'select') {
					$vaid = @str_replace(',', '&', @$this->root->post['vaids']);
					if (!$vaid) {$vaid = 'none';}
				}
				$pginfo['vaids'] = $vaid;
			}
			if ($cpages) {
				// 下層ページの継承指定
				if ($pginfo['vinherit'] === 1 || $pginfo['vinherit'] === 2) {
					foreach ($cpages as $_page) {
						$child_dat[$_page]['vinherit'] = $pginfo['vinherit'] + 2;
						$child_dat[$_page]['vgids'] = $pginfo['vgids'];
						$child_dat[$_page]['vaids'] = $pginfo['vaids'];
					}
					$do_child = TRUE;
				}
			}
		}
		$pginfo_str = '#pginfo('.join("\t",$pginfo).')'."\n";
		
		// 凍結されている? #freeze は必ずファイル先頭
		$buf = array_shift($src);
		if (rtrim($buf) !== '#freeze') {
			array_unshift($src, $buf);
			$buf = '';
		}
		// #pginfo 差し替え
		$src = preg_replace("/^#pginfo\(.*\)\s*/m", '', join('', $src));
		$src = $buf . $pginfo_str . $src;		
		
		// ページ保存
		$this->func->file_write($this->cont['DATA_DIR'], $page, $src, TRUE);
		
		// 下層ページ更新
		if ($do_child) {
			$this->save_parm_child ($child_dat);
		}

		$msg  = $this->msg['done_ok'];
		$body = '';
		return array('msg'=>$msg, 'body'=>$body);

	}
	
	function save_parm_child ($dat) {
		foreach ($dat as $page=>$_pginfo) {
			// ソース読み込み
			$src = $this->func->get_source($page);
			
			// ページ情報読み込み
			$pginfo = $this->func->get_pginfo($page);
			
			// 継承チェック & 上書き
			$do = FALSE;
			if (isset($_pginfo['einherit']) && ($pginfo['einherit'] > 2 || $_pginfo['einherit'] === 4)) {
				$pginfo['einherit'] = $_pginfo['einherit'];
				$pginfo['egids'] = $_pginfo['egids'];
				$pginfo['eaids'] = $_pginfo['eaids'];				
				$do = TRUE;
			}
			if (isset($_pginfo['vinherit']) && ($pginfo['vinherit'] > 2 || $_pginfo['vinherit'] === 4)) {
				$pginfo['vinherit'] = $_pginfo['vinherit'];
				$pginfo['vgids'] = $_pginfo['vgids'];
				$pginfo['vaids'] = $_pginfo['vaids'];				
				$do = TRUE;
			}

			// 保存	
			if ($do) {
				$pginfo_str = '#pginfo('.join("\t",$pginfo).')'."\n";
				
				// 凍結されている? #freeze は必ずファイル先頭
				$buf = array_shift($src);
				if (rtrim($buf) !== '#freeze') {
					array_unshift($src, $buf);
					$buf = '';
				}
				// #pginfo 差し替え
				$src = preg_replace("/^#pginfo\(.*\)\s*/m", '', join('', $src));
				$src = $buf . $pginfo_str . $src;		
				
				// ページ保存
				$this->func->file_write($this->cont['DATA_DIR'], $page, $src, TRUE);
			}
		}
	}
	
	// ページ毎の権限設定フォーム
	function show_page_form ($page) {

		$this->root->head_pre_tags[] = '<script type="text/javascript" src="'.$this->cont['HOME_URL'].'skin/loader.php?type=js&amp;src=prototype"></script>';
		$this->root->head_pre_tags[] = '<script type="text/javascript" src="'.$this->cont['HOME_URL'].'skin/loader.php?type=js&amp;src=log"></script>';
		$this->root->head_pre_tags[] = '<script type="text/javascript" src="'.$this->cont['HOME_URL'].'skin/loader.php?type=js&amp;src=suggest"></script>';
		$this->root->head_pre_tags[] = '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->cont['HOME_URL'].'skin/loader.php?type=css&amp;src=suggest" />';
		
		
		$pginfo = $this->func->get_pginfo($page);
		$spage = htmlspecialchars($page);
		
		$s_['einhelit'] = array_pad(array(), 4, '');
		$s_['einhelit'][$pginfo['einherit']] = ' checked="checked"';
		$s_['vinhelit'] = array_pad(array(), 4, '');
		$s_['vinhelit'][$pginfo['vinherit']] = ' checked="checked"';
		
		$efor_remove = $vfor_remove = $this->msg['for_remove'];
		$s_['edisable'] = $s_['vdisable'] = $s_['ecannot'] = $s_['vcannot'] = '';
		if ($pginfo['einherit'] === 4) {
			$s_['edisable'] = ' disabled="disabled "';
			$s_['ecannot'] = $this->msg['can_not_set'].'<br />';
			$efor_remove = '';
		}
		if ($pginfo['vinherit'] === 4) {
			$s_['vdisable'] = ' disabled="disabled "';
			$s_['vcannot'] = $this->msg['can_not_set'].'<br />';
			$vfor_remove = '';
		}
		
		foreach(array('eaids','egids','vaids','vgids') as $key) {
			$s_[$key]['all'] = $s_[$key]['none'] = $s_[$key]['select'] = '';  
			if ($pginfo[$key] === 'none' || $pginfo[$key] === 'all') {
				$$key = $pginfo[$key];
				$s_[$key][$pginfo[$key]] = ' checked="true"';
			} else {
				$$key = explode("&", $pginfo[$key]);
				$s_[$key]["select"] = ' checked="true"';
			}
		}
		$edit_group_list = $this->func->make_grouplist_form('egids', $egids, $s_['edisable']);
		$edit_user_list = '';
		if ($eaids && is_array($eaids)) {
			foreach($eaids as $eaid) {
				if ($pginfo['einherit'] === 4) {
					$edit_user_list .= htmlspecialchars($this->func->getUnameFromId($eaid)).'['.$eaid.'] '; 
				} else {
					$edit_user_list .= '<span class="exist">'.htmlspecialchars($this->func->getUnameFromId($eaid)).'['.$eaid.'] </span>'; 
				}
			}
		}
		
		$view_group_list = $this->func->make_grouplist_form('vgids', $vgids, $s_['vdisable']);
		$view_user_list = '';
		if ($eaids && is_array($vaids)) {
			foreach($vaids as $vaid) {
				if ($pginfo['vinherit'] === 4) {
					$view_user_list .= htmlspecialchars($this->func->getUnameFromId($vaid)).'['.$vaid.'] '; 
				} else {
					$view_user_list .= '<span class="exist">'.htmlspecialchars($this->func->getUnameFromId($vaid)).'['.$vaid.'] </span>'; 
				}
			}
		}

		
		$e_default = ($pginfo['einherit'] === 3)? '<p>'.$this->msg['default_inherit'].'</p>' : '';
		$v_default = ($pginfo['vinherit'] === 3)? '<p>'.$this->msg['default_inherit'].'</p>' : '';


		$ret['msg'] = $this->msg['title_permission'];
		$ret['body'] = '';
		$ret['body'] = <<<EOD
<script>
var XpWikiSuggest1 = null;
var onLoadHandler = function(){
	XpWikiSuggest1 = new XpWikiUnameSuggest('{$this->cont['HOME_URL']}','xpwiki_tag_input1','xpwiki_suggest_list1','xpwiki_tag_hidden1','xpwiki_tag_list1');
	XpWikiSuggest2 = new XpWikiUnameSuggest('{$this->cont['HOME_URL']}','xpwiki_tag_input2','xpwiki_suggest_list2','xpwiki_tag_hidden2','xpwiki_tag_list2');
};
if (window.addEventListener) {
    window.addEventListener("load", onLoadHandler, true);
} else {
    window.attachEvent("onload", onLoadHandler);
}
</script>

<form action="{$this->root->script}" method="post">
<p>
 <ul>
  <li><a href="#xpwiki_edit_parmission">{$this->msg['edit_permission']}</a></li>
  <li><a href="#xpwiki_view_parmission">{$this->msg['view_parmission']}</a></li>
 </ul>
</p>
<h2 id="xpwiki_edit_parmission">{$this->msg['edit_permission']}</h2>
<p>
 {$s_['ecannot']}
 <input name="einherit" id="_edit_permission_none" type="radio" value="3"{$s_['einhelit'][3]}{$s_['edisable']} /><label for="_edit_permission_none"> {$this->msg['permission_none']}</label><br />
</p>
{$e_default}
<h3>{$this->msg['lower_page_inherit']}</h3>
<p>
 <input name="einherit" id="_edit_inherit_default" type="radio" value="1"{$s_['einhelit'][1]}{$s_['edisable']} /><label for="_edit_inherit_default"> {$this->msg['inherit_default']}</label><br />
 <input name="einherit" id="_edit_inherit_forced" type="radio" value="2"{$s_['einhelit'][2]}{$s_['edisable']} /><label for="_edit_inherit_forced"> {$this->msg['inherit_forced']}</label><br />
 <input name="einherit" id="_edit_inherit_onlythis" type="radio" value="0"{$s_['einhelit'][0]}{$s_['edisable']} /><label for="_edit_inherit_onlythis"> {$this->msg['inherit_onlythis']}</label><br />
</p>
<h4>{$this->msg['parmission_setting']}</h4>
<table style="margin-left:2em;"><tr>
 <td>
  <input name="egid" id="_egid1" type="radio" value="all"{$s_['egids']['all']}{$s_['edisable']} /><label for="_egid1"> {$this->msg['admit_all_group']}</label><br />
  <input name="egid" id="_egid2" type="radio" value="none"{$s_['egids']['none']}{$s_['edisable']} /><label for="_egid2"> {$this->msg['not_admit_all_group']}</label><br />
  <input name="egid" id="_egid3" type="radio" value="select"{$s_['egids']['select']}{$s_['edisable']} /><label for="_egid3"> {$this->msg['admit_select_group']}</label><br />
  <div style="margin-left:2em;">{$edit_group_list}</div>
 </td>
 <td>
  <input name="eaid" id="_eaid1" type="radio" value="all"{$s_['eaids']['all']}{$s_['edisable']} /><label for="_eaid1"> {$this->msg['admit_all_user']}</label><br />
  <input name="eaid" id="_eaid2" type="radio" value="none"{$s_['eaids']['none']}{$s_['edisable']} /><label for="_eaid2"> {$this->msg['not_admit_all_user']}</label><br />
  <input name="eaid" id="_eaid3" type="radio" value="select"{$s_['eaids']['select']}{$s_['edisable']} /><label for="_eaid3"> {$this->msg['admit_select_user']}</label><br />
  <div style="margin-left:2em;">
    <div id="xpwiki_tag_list1" class="xpwiki_tag_list">{$edit_user_list}</div>
    <input type="hidden" name="eaids" id="xpwiki_tag_hidden1" value="" />
    {$this->msg['search_user']}: <input type="text" size="25" id="xpwiki_tag_input1" name="xpwiki_tag_input1" autocomplete='off' class="form_text"{$s_['edisable']} /><br />
    {$efor_remove}
    <div id='xpwiki_suggest_list1' class="auto_complete"></div>
  </div>
 </td>
</tr></table>

<hr />

<h2 id="xpwiki_view_parmission">{$this->msg['view_parmission']}</h2>
<p>
 {$s_['vcannot']}
 <input name="vinherit" id="_view_permission_none" type="radio" value="3"{$s_['vinhelit'][3]}{$s_['vdisable']} /><label for="_view_permission_none"> {$this->msg['permission_none']}</label><br />
</p>
{$v_default}
<h3>{$this->msg['lower_page_inherit']}</h3>
<p>
 <input name="vinherit" id="_view_inherit_default" type="radio" value="1"{$s_['vinhelit'][1]}{$s_['vdisable']} /><label for="_view_inherit_default"> {$this->msg['inherit_default']}</label><br />
 <input name="vinherit" id="_view_inherit_forced" type="radio" value="2"{$s_['vinhelit'][2]}{$s_['vdisable']} /><label for="_view_inherit_forced"> {$this->msg['inherit_forced']}</label><br />
 <input name="vinherit" id="_view_inherit_onlythis" type="radio" value="0"{$s_['vinhelit'][0]}{$s_['vdisable']} /><label for="_view_inherit_onlythis"> {$this->msg['inherit_onlythis']}</label><br />
</p>
<h4>{$this->msg['parmission_setting']}</h4>
<table style="margin-left:2em;"><tr>
 <td>
  <input name="vgid" id="_vgid1" type="radio" value="all"{$s_['vgids']['all']}{$s_['vdisable']} /><label for="_vgid1"> {$this->msg['admit_all_group']}</label><br />
  <input name="vgid" id="_vgid2" type="radio" value="none"{$s_['vgids']['none']}{$s_['vdisable']} /><label for="_vgid2"> {$this->msg['not_admit_all_group']}</label><br />
  <input name="vgid" id="_vgid3" type="radio" value="select"{$s_['vgids']['select']}{$s_['vdisable']} /><label for="_vgid3"> {$this->msg['admit_select_group']}</label><br />
  <div style="margin-left:2em;">{$view_group_list}</div>
 </td>
 <td>
  <input name="vaid" id="_vaid1" type="radio" value="all"{$s_['vaids']['all']}{$s_['vdisable']} /><label for="_vaid1"> {$this->msg['admit_all_user']}</label><br />
  <input name="vaid" id="_vaid2" type="radio" value="none"{$s_['vaids']['none']}{$s_['vdisable']} /><label for="_vaid2"> {$this->msg['not_admit_all_user']}</label><br />
  <input name="vaid" id="_vaid3" type="radio" value="select"{$s_['vaids']['select']}{$s_['vdisable']} /><label for="_vaid3"> {$this->msg['admit_select_user']}</label><br />
  <div style="margin-left:2em;">
    <div id="xpwiki_tag_list2" class="xpwiki_tag_list">{$view_user_list}</div>
    <input type="hidden" name="vaids" id="xpwiki_tag_hidden2" value="" />
    {$this->msg['search_user']}: <input type="text" size="25" id="xpwiki_tag_input2" name="xpwiki_tag_input2" autocomplete='off' class="form_text"{$s_['vdisable']} /><br />
    {$vfor_remove}
    <div id='xpwiki_suggest_list2' class="auto_complete"></div>
  </div>
 </td>
</tr></table>

<hr />
<input type="hidden" name="cmd" value="pginfo" />
<input type="hidden" name="page" value="{$spage}" />
<input type="hidden" name="pmode" value="setparm" />
<input id="xpwiki_parmission_submit" type="submit" value="{$this->msg['submit']}" />
</form>
EOD;
		return $ret;
	}
	
	function show_admin_form () {
		//error_reporting(E_ERROR);
		
		@set_time_limit(120);
		
		if (empty($this->root->post['action']) or !$this->root->userinfo['admin'])
		{
			$this->msg['msg_usage'] = str_replace(array("%1d","%2d"),array(ini_get('max_execution_time'),ini_get('max_execution_time') - 5),$this->msg['msg_usage']);
			$body = $this->func->convert_html($this->msg['msg_usage']);
		}
		if ($this->root->userinfo['admin'])
		{
			$not = array();
			foreach(array("i","c","p","a") as $type)
			{
				if (file_exists($this->cont['CACHE_DIR']."pginfo_".$type.".dat"))
				{
					$not[$type] = '<span style="color:red;font-weight:bold;"> *</span>';
				}
				else
				{
					$not[$type] = "";
				}
			}
			$body = <<<__EOD__
<script>
<!--
var xpwiki_pginfo_doing = false;
var xpwiki_pginfo_timerID;
function xpwiki_pginfo_done()
{
	document.getElementById('xpwiki_pginfo_submit').disabled = false;
}
function xpwiki_pginfo_blink(mode)
{
	var timer;
	clearTimeout(xpwiki_pginfo_timerID);
	
	if (mode == 'stop')
	{
		xpwiki_pginfo_doing = false;
	}
	else
	{
		xpwiki_pginfo_doing = true;
	}
	
	if (!xpwiki_pginfo_doing || document.getElementById('xpwiki_pginfo_doing').style.visibility == "visible")
	{
		document.getElementById('xpwiki_pginfo_doing').style.visibility = "hidden";
		timer = 200;
	}
	else
	{
		document.getElementById('xpwiki_pginfo_doing').style.visibility = "visible";
		timer = 800;
	}
	
	if (mode == 'start') {xpwiki_pginfo_setmsg('xpwiki_pginfo_doing','{$this->msg['msg_now_doing']}');}
	
	if (mode == 'continue')
	{
		xpwiki_pginfo_setmsg('xpwiki_pginfo_doing','{$this->msg['msg_next_do']}');
		document.getElementById('xpwiki_pginfo_doing').style.visibility = "visible";
	}
	
	if (xpwiki_pginfo_doing && mode != 'continue')
	{
		xpwiki_pginfo_timerID = setTimeout("xpwiki_pginfo_blink()", timer);
	}
}
function xpwiki_pginfo_setmsg(id,msg)
{
	document.getElementById(id).innerHTML = msg;
}
-->
</script>
<form target="pukiwiki_pginfo_work" style= "margin:0px;" method="POST" action="{$this->root->script}">
 <div>
  <input type="hidden" name="plugin" value="pginfo" />
  <input type="hidden" name="pmode" value="update" />
  <input type="hidden" name="mode" value="select" />
  {$this->msg['msg_hint']}
  <div style="margin-left:20px;">
  <input type="checkbox" name="init" value="on" checked="true" />{$this->msg['msg_init']}{$not['i']}<br />
  &nbsp;&#9500;<input type="radio" name="title" value="" checked="true" />{$this->msg['msg_noretitle']}<br />
  &nbsp;&#9495;<input type="radio" name="title" value="on" />{$this->msg['msg_retitle']}<br />
  <input type="checkbox" name="count" value="on" checked="true" />{$this->msg['msg_count']}{$not['c']}<br />
  <input type="checkbox" name="plain" value="on" checked="true" />{$this->msg['msg_plain_init']}{$not['p']}<br />
  &nbsp;&#9500;<input type="radio" name="plain_all" value="" checked="true" />{$this->msg['msg_plain_init_notall']}<br />
  &nbsp;&#9495;<input type="radio" name="plain_all" value="on" />{$this->msg['msg_plain_init_all']}<br />
  <input type="checkbox" name="attach" value="on" checked="true" />{$this->msg['msg_attach_init']}{$not['a']}<br />
 </div>
  <br />
  <input id="xpwiki_pginfo_submit" type="submit" value="{$this->msg['btn_submit']}" onClick="xpwiki_pginfo_blink('start');return true;" />
 </div>
</form>
<div id="xpwiki_pginfo_doing" style="color:red;background-color:white;visibility:hidden;width:500px;text-align:center;">{$this->msg['msg_now_doing']}</div>
<div>{$this->msg['msg_progress_report']}</div>
<iframe src="" height="350" width="500" name="pukiwiki_pginfo_work"></iframe>
__EOD__;
			return array(
				'msg'=>$this->msg['title_update'],
				'body'=>$body
			);
		}
	}
	
	function do_dbupdate() {
		//error_reporting(E_ALL);
		$this->root->post['start_time'] = time();
		
		header ("Content-Type: text/html; charset="._CHARSET);
		// mod_gzip を無効にするオプション(要サーバー側設定)
		header('X-NoGzip: 1');
		
		// 出力をバッファリングしない
		ob_end_clean();
		echo str_pad('',256);//for IE
		ob_implicit_flush(true);
		
		echo <<<__EOD__
<html>
<head>
</head>
<script>
var pukiwiki_root_url ="./";
</script>
<script type="text/javascript" src="skin/default.ja.js"></script>
<body>
__EOD__;
			
		$this->root->post['init'] = (!empty($this->root->post['init']))? "on" : "";
		$this->root->post['count'] = (!empty($this->root->post['count']))? "on" : "";
		$this->root->post['title'] = (!empty($this->root->post['title']))? "on" : "";
		$this->root->post['plain'] = (!empty($this->root->post['plain']))? "on" : "";
		$this->root->post['plain_all'] = (!empty($this->root->post['plain_all']))? "on" : "";
		$this->root->post['attach'] = (!empty($this->root->post['attach']))? "on" : "";
		
		if ($this->root->post['mode'] == 'all' || $this->root->post['init']) $this->pginfo_db_init();
		if ($this->root->post['mode'] == 'all' || $this->root->post['count']) $this->count_db_init();
		//if ($post['mode'] == 'all' || $post['title']) pginfo_db_retitle();
		if ($this->root->post['mode'] == 'all' || $this->root->post['plain']) $this->plain_db_init();
		if ($this->root->post['mode'] == 'all' || $this->root->post['attach']) $this->attach_db_init();
		
		//redirect_header("$script?plugin=pginfo",3,$_links_messages['msg_done']);
		echo $this->msg['msg_done'];
		echo "<script>parent.xpwiki_pginfo_done();parent.xpwiki_pginfo_blink('stop');</script>";
		echo "</body></html>";
		exit();
	}
	
	// ページ情報データベース初期化
	function pginfo_db_init()
	{
	//	global $xoopsDB,$whatsnew,$post;
		
		if ($dir = @opendir($this->cont['DATA_DIR']))
		{
			//name テーブルの属性を BINARY にセット(検索で大文字・小文字を区別する)
			//$query = 'ALTER TABLE `'.$this->xpwiki->db->prefix($this->root->mydirname."_pginfo").'` CHANGE `name` `name` VARCHAR( 255 ) BINARY NOT NULL ';
			//$result=$this->xpwiki->db->queryF($query);
			
			// ページ閲覧権限のキャッシュをクリアー
			$this->func->get_pg_allow_viewer("",false,true);
			
			// 処理済ファイルデーター
			$work = $this->cont['CACHE_DIR']."pginfo_i.dat";
			$domix = $dones = array();
			$done = 0;
			if (file_exists($work))
			{
				$dones = unserialize(join('',file($work)));
				if (!isset($dones[1])) $dones[1] = array();
				$docnt = count($dones[1]);
				$domix = array_merge($dones[0],$dones[1]);
				$done = count($domix);
			}
			if ($done)
			{
				echo "<div style=\"font-size:14px;\"><b>DB '".$this->root->mydirname."_pginfo' Already converted {$docnt} pages.</b></div>";
			}
			
			echo "<div style=\"font-size:14px;\"><b>DB '".$this->root->mydirname."_pginfo' Now converting... </b>( * = 10 Pages)<hr>";
			
			$fcounter = $counter = 0;
			
			$files = array();
			while($file = readdir($dir))
			{
				$files[] = $file;
			}
			
			foreach(array_diff($files,$domix) as $file)
			{
				if($file == ".." || $file == "." || strstr($file,".txt")===FALSE)
				{
					$dones[0][] = $file;
					continue;
				}
				
				$name=$aids=$gids=$vaids=$vgids= "";
				$buildtime=$editedtime=$lastediter=$uid=$freeze=$unvisible = 0;
				
				$page = $this->func->decode(trim(preg_replace("/\.txt$/"," ",$file)));
	
				if ($page === $this->root->whatsnew)
				{
					$dones[0][] = $file;
					@unlink($this->cont['DATA_DIR'].$file);
					continue;
				}
				
				$name = $this->func->strip_bracket($page);
				
				// id取得
				$id = $this->func->get_pgid_by_name($page);
				
				$name = addslashes($name);
				$buildtime = filectime($this->cont['DATA_DIR'].$file);
				$editedtime = filemtime($this->cont['DATA_DIR'].$file);
				if (!$buildtime || $buildtime > $editedtime) $buildtime = $editedtime;
				
				$checkpostdata = join("",$this->func->get_source($page));
				if (!$checkpostdata)
				{
					@unlink($this->cont['DATA_DIR'].$file);
					continue;
				}
				
				// pginfo
				$pginfo = $this->func->get_pginfo($page);
				
				//echo $page."<hr />";
				// 編集権限
				$arg = array();
				if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$checkpostdata,$arg))
				{
					$freeze = 1;
					if (isset($arg[1])) $uid = $arg[1];
					if (isset($arg[2])) $aids = "&".str_replace(",","&",$arg[2])."&";
					if (isset($arg[3])) $gids = "&".str_replace(",","&",$arg[3])."&";
					$checkpostdata = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/","",$checkpostdata);
				}
				else
				{
					$aids = "&all";
					$gids = "&3&";
					$freeze = 0;
				}
				
				// 閲覧権限
				if (preg_match("/^#unvisible(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+|all))?(?:\tgid:([0-9,]+))?\n/",$checkpostdata,$arg))
				{
					$unvisible = 1;
					$checkpostdata = preg_replace("/^#unvisible(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+|all))?(?:\tgid:([0-9,]+))?\n/","",$checkpostdata);
				}
				$info = $this->func->get_pg_allow_viewer($page);
				if (!isset($uid)) $uid = $info['owner'];
				$vaids = "&".str_replace(",","&",$info['user']);
				$vgids = "&".str_replace(",","&",$info['group']);
	
				// ページ作成者 元ページに記載があればその値を取得
				$author_uid = 0;
				if (preg_match("/^\/\/ author:([0-9]+)($|\n)/",$checkpostdata,$arg))
					if (!isset($uid)) $uid = $arg[1];
				
				if (!isset($uid) || $uid ==="") $uid = 0;
				$lastediter = $uid;
				
				// タイトル情報
				$title = "";
				if (!$id || !empty($this->root->post['title']) || !$this->func->get_heading($page, true))
				{
					$title = addslashes(str_replace(array('&lt;','&gt;','&amp;','&quot;','&#039;'),array('<','>','&','"',"'"),$this->func->get_heading_init($page)));
				}
				
				if (!$id)
				{
					// 新規作成
					$query = "insert into ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." (`name`,`buildtime`,`editedtime`,`aids`,`gids`,`vaids`,`vgids`,`lastediter`,`uid`,`freeze`,`unvisible`,`update`) values('$name',$buildtime,$editedtime,'$aids','$gids','$vaids','$vgids',$lastediter,$uid,$freeze,$unvisible,1);";
				}
				else
				{
					// アップデート
					if ($title)
					{
						$title = ",`title`='$title'";
					}
					$value = "`name`='$name'"
				.",`buildtime`='$buildtime'"
				.",`editedtime`='$editedtime'"
				.",`lastediter`='$lastediter'"
				.$title
					.",`aids`='$aids'"
				.",`gids`='$gids'"
				.",`vaids`='$vaids'"
				.",`vgids`='$vgids'"
				.",`uid`='$uid'"
				.",`freeze`='$freeze'"
				.",`unvisible`='$unvisible'"
				.",`update`='1'";
					$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." SET $value WHERE id = '$id' LIMIT 1;";
				}
				$result=$this->xpwiki->db->queryF($query);
				//echo $query."<hr>";
				
				$counter++;
				$dones[1][] = $file;
				if (($counter/10) == (floor($counter/10)))
				{
					echo "*";
					
				}
				if (($counter/100) == (floor($counter/100)))
				{
					echo " ( Done ".$counter." Pages !)<br />";
					
				}
				
				if ($this->root->post['start_time'] + (ini_get('max_execution_time') - 5) < time())
				{
					// 処理済ファイルリスト保存
					if ($fp = fopen($work,"wb"))
					{
						fputs($fp,serialize($dones));
						fclose($fp);
					}
					closedir($dir);
					$this->plugin_pginfo_next_do();
				}
			}
			closedir($dir);
			
			echo " ( Done ".$counter." Pages !)<hr />";
			echo "</div>";
			
			// アップデートしなかったページ情報(テキストファイルがないページ)を削除済み(editedtime=0)にする
			$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." SET `editedtime` = '0' WHERE `update` = '0';";
			$result=$this->xpwiki->db->queryF($query);
			
			// アップデートフラグ戻し
			$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." SET `update` = '0';";
			$result=$this->xpwiki->db->queryF($query);
			
			@unlink ($work);
		}
		$this->root->post['init'] = "";
	}
	
	// ページカウンターデータベース初期化
	function count_db_init()
	{
	//	global $xoopsDB,$whatsnew,$post;
		
		// カウント情報
		if ($dir = @opendir($this->cont['COUNTER_DIR']))
		{
			// 処理済ファイルリストデーター
			$work = $this->cont['CACHE_DIR']."pginfo_c.dat";
			$domix = $dones = array();
			$done = 0;
			if (file_exists($work))
			{
				$dones = unserialize(join('',file($work)));
				if (!isset($dones[1])) $dones[1] = array();
				$docnt = count($dones[1]);
				$domix = array_merge($dones[0],$dones[1]);
				$done = count($domix);
			}
			if ($done)
			{
				echo "<div style=\"font-size:14px;\"><b>DB '".$this->root->mydirname."_counter' Already converted {$docnt} pages.</b></div>";
			}
			else
			{
				$query = "DELETE FROM ".$this->xpwiki->db->prefix($this->root->mydirname."_count");
				$result=$this->xpwiki->db->queryF($query);
			}
			
			echo "<div style=\"font-size:14px;\"><b>DB '".$this->root->mydirname."_counter' Now converting... </b>( * = 10 Pages)<hr>";
			
			
			$counter = 0;
			
			$files = array();
			while($file = readdir($dir))
			{
				$files[] = $file;
			}
			
			foreach(array_diff($files,$domix) as $file)
			{
				if($file == ".." || $file == "." || strstr($file,".count")===FALSE)
				{
					$dones[0][] = $file;
					continue;
				}
				
				$name=$today=$ip="";
				$count=$today_count=$yesterday_count=0;
				
				$page = $this->func->decode(trim(preg_replace("/\.count$/"," ",$file)));
				// 存在しないページ
				if ($page == $this->root->whatsnew || !file_exists($this->cont['DATA_DIR'].$this->func->encode($page).".txt"))
				{
					@unlink($this->cont['COUNTER_DIR'].$file);
					$dones[0][] = $file;
					continue;
				}
				
				$array = file($this->cont['COUNTER_DIR'].$file);
				$name = addslashes($this->func->strip_bracket($page));
				$count = rtrim($array[0]);
				$today = rtrim($array[1]);
				$today_count = rtrim($array[2]);
				$yesterday_count = rtrim($array[3]);
				$ip = rtrim($array[4]);
				
				$query = "insert into ".$this->xpwiki->db->prefix($this->root->mydirname."_count")." (name,count,today,today_count,yesterday_count,ip) values('$name',$count,'$today',$today_count,$yesterday_count,'$ip');";
				$result=$this->xpwiki->db->queryF($query);
				
				$counter++;
				if (($counter/10) == (floor($counter/10)))
				{
					echo "*";
					
				}
				if (($counter/100) == (floor($counter/100)))
				{
					echo " ( Done ".$counter." Pages !)<br />";
					
				}
				
				$dones[1][] = $file;
				
				if ($this->root->post['start_time'] + (ini_get('max_execution_time') - 5) < time())
				{
					// 処理済ファイルリスト保存
					if ($fp = fopen($work,"wb"))
					{
						fputs($fp,serialize($dones));
						fclose($fp);
					}
					closedir($dir);
					$this->plugin_pginfo_next_do();
				}
			}
			closedir($dir);
			echo " ( Done ".$counter." Pages !)<hr />";
			echo "</div>";
			
			@unlink ($work);
		}
		$this->root->post['count'] = "";
	}
	
	// 検索用 plain DB 再設定
	function plain_db_init()
	{
	//	global $xoopsDB,$whatsnew,$vars,$post,$get,$related,$comment_no;
		
		if ($dir = @opendir($this->cont['DATA_DIR']))
		{
			// 処理済ファイルリストデーター
			$work = $this->cont['CACHE_DIR']."pginfo_p.dat";
			$domix = $dones = array();
			$done = 0;
			if (file_exists($work))
			{
				$dones = unserialize(join('',file($work)));
				if (!isset($dones[1])) $dones[1] = array();
				$docnt = count($dones[1]);
				$domix = array_merge($dones[0],$dones[1]);
				$done = count($domix);
			}
			if ($done)
			{
				echo "<div style=\"font-size:14px;\"><b>DB '".$this->root->mydirname."_plain' Already converted {$docnt} pages.</b></div>";
			}
			
			echo "<div style=\"font-size:14px;\"><b>DB '".$this->root->mydirname."_plain' Now converting... </b>( * = 10 Pages)<hr>";
			
			
			$counter = 0;
			
			$files = array();
			while($file = readdir($dir))
			{
				$files[] = $file;
			}
			
			$this->root->vars['from_pginfo_init'] = true;
			
			foreach(array_diff($files,$domix) as $file)
			{
				if($file == ".." || $file == "." || strstr($file,".txt")===FALSE)
				{
					$dones[0][] = $file;
					continue;
				}
				
				$this->root->related = array();
				$page = $this->func->decode(trim(preg_replace("/\.txt$/"," ",$file)));
				$this->root->vars['page']=$this->root->get['page']=$this->root->post['page'] = $page;
				$this->root->comment_no = 0;
				
				if($page === $this->root->whatsnew)
				{
					$dones[0][] = $file;
					continue;
				}
				
				$id = $this->func->get_pgid_by_name($page);
				$query = "SELECT plain FROM `".$this->xpwiki->db->prefix($this->root->mydirname."_plain")."` WHERE `pgid` = ".$id.";";
				$result = $this->xpwiki->db->query($query);
				if (mysql_num_rows($result))
				{
					list($text) = mysql_fetch_row ( $result );
					if ($text && !$this->root->post['plain_all'])
					{
						$dones[0][] = $file;
						continue;
					}
					$mode = "update";
				}
				else
				{
					$mode = "insert";
				}
				
				if ($this->func->plain_db_write($page,$mode))
				{
					$dones[1][] = $file;
					$counter++;
					if (($counter/10) == (floor($counter/10)))
					{
						echo "*";
						
					}
					if (($counter/100) == (floor($counter/100)))
					{
						echo " ( Done ".$counter." Pages !)<br />";
						
					}
				}
				else
				{
					$dones[0][] = $file;
				}
				
				if ($this->root->post['start_time'] + (ini_get('max_execution_time') - 5) < time())
				{
					// 処理済ファイルリスト保存
					if ($fp = fopen($work,"wb"))
					{
						fputs($fp,serialize($dones));
						fclose($fp);
					}
					closedir($dir);
					$this->plugin_pginfo_next_do();
				}
			}
			closedir($dir);
			$this->root->vars['page']=$this->root->get['page']=$this->root->post['page'] = "";
			$this->root->post['plain'] = "";
			echo " ( Done ".$counter." Pages !)<hr />";
			echo "</div>";
			
			@unlink ($work);
		}
	}
	
	// 添付ファイル DB 再設定
	function attach_db_init()
	{
	//	global $xoopsDB,$vars,$post,$get;
		
		if ($dir = @opendir($this->cont['UPLOAD_DIR']))
		{
			// 処理済ファイルリストデーター
			$work = $this->cont['CACHE_DIR']."pginfo_a.dat";
			$domix = $dones = array();
			$done = 0;
			if (file_exists($work))
			{
				$dones = unserialize(join('',file($work)));
				if (!isset($dones[1])) $dones[1] = array();
				$docnt = count($dones[1]);
				$domix = array_merge($dones[0],$dones[1]);
				$done = count($domix);
			}
			if ($done)
			{
				echo "<div style=\"font-size:14px;\"><b>DB '".$this->root->mydirname."_attach' Already converted {$docnt} pages.</b></div>";
			}
			else
			{
				$query = "DELETE FROM ".$this->xpwiki->db->prefix($this->root->mydirname."_attach");
				$result=$this->xpwiki->db->queryF($query);
			}
			echo "<div style=\"font-size:14px;\"><b>DB '".$this->root->mydirname."_attach' Now converting... </b>( * = 10 Pages)<hr>";
			
			
			include_once($this->cont['PLUGIN_DIR']."attach.inc.php");
			
			$counter = 0;
			
			$page_pattern = '(?:[0-9A-F]{2})+';
			$age_pattern = '(?:\.([0-9]+))?';
			$pattern = "/^({$page_pattern})_((?:[0-9A-F]{2})+){$age_pattern}$/";
			
			$files = array();
			while($file = readdir($dir))
			{
				$files[] = $file;
			}
			
			foreach(array_diff($files,$domix) as $file)
			{
				$matches = array();
				if (!preg_match($pattern,$file,$matches))
				{
					$dones[0][] = $file;
					continue;
				}
				$page = $this->func->decode($matches[1]);
				$name = $this->func->decode($matches[2]);
				$age = array_key_exists(3,$matches) ? $matches[3] : 0;
				
				// サムネイルは除外
				if (preg_match("/^\d\d?%/",$name))
				{
					$dones[0][] = $file;
					continue;
				}
				
				$obj = &new XpWikiAttachFile($this->xpwiki, $page,$name,$age);
				$obj->getstatus();
				
				$data['pgid'] = $this->func->get_pgid_by_name($page);
				$data['name'] = $name;
				$data['mtime'] = $obj->time;
				$data['size'] = $obj->size;
				$data['type'] = $obj->type;
				$data['status'] = $obj->status;
	
				if ($this->func->attach_db_write($data,"insert"))
				{
					$counter++;
					$dones[1][] = $file;
					if (($counter/10) == (floor($counter/10)))
					{
						echo "*";
						
					}
					if (($counter/100) == (floor($counter/100)))
					{
						echo " ( Done ".$counter." Files !)<br />";
						
					}
				}
				else
				{
					$dones[0][] = $file;
				}
				
				if ($this->root->post['start_time'] + (ini_get('max_execution_time') - 5) < time())
				{
					// 処理済ファイルリスト保存
					if ($fp = fopen($work,"wb"))
					{
						fputs($fp,serialize($dones));
						fclose($fp);
					}
					closedir($dir);
					$this->plugin_pginfo_next_do();
				}
			}
			closedir($dir);
			echo " ( Done ".$counter." Files !)<hr />";
			echo "</div>";
			
			@unlink ($work);
		}
	}
	
	function plugin_pginfo_next_do()
	{
	//	global $script,$post,$_links_messages;
		
		$token = $this->func->get_token_html();
		
		$html = <<<__EOD__
<form method="POST" action="{$this->root->script}" onsubmit="return pukiwiki_check(this);">
 <div>
  {$token}
  <input type="hidden" name="encode_hint" value="ぷ" />
  <input type="hidden" name="plugin" value="pginfo" />
  <input type="hidden" name="action" value="update" />
  <input type="hidden" name="mode" value="select" />
  <input type="hidden" name="init" value="{$this->root->post['init']}" />
  <input type="hidden" name="title" value="{$this->root->post['title']}" />
  <input type="hidden" name="plain" value="{$this->root->post['plain']}" />
  <input type="hidden" name="plain_all" value="{$this->root->post['plain_all']}" />
  <input type="hidden" name="attach" value="{$this->root->post['attach']}" />
  <input type="submit" value="{$this->msg['btn_next_do']}" onClick="parent.xpwiki_pginfo_blink('start');return true;" />
 </div>
</form>
<script>
<!--
parent.$this->xpwiki_pginfo_blink('continue');
-->
</script>
</body></html>
__EOD__;
		echo $html;
		
		exit();
	}
}
?>