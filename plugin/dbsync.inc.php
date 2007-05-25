<?php
//
// Created on 2006/11/17 by nao-pon http://hypweb.net/
// $Id: dbsync.inc.php,v 1.5 2007/05/25 03:00:10 nao-pon Exp $
//

class xpwiki_plugin_dbsync extends xpwiki_plugin {

	function plugin_dbsync_init()
	{
		// 言語ファイルの読み込み
		$this->load_language();
	}
	
	function plugin_dbsync_convert() {
		return '';
	}
	
	function plugin_dbsync_action()
	{
		$pmode = (empty($this->root->post['pmode']))? '' : $this->root->post['pmode'];
		$page = (empty($this->root->vars['page']))? '' : $this->root->vars['page'];

		if ($pmode === 'update') {
			// DB Update
			//exit('Do not work this version.'."<script>parent.xpwiki_dbsync_done();parent.xpwiki_dbsync_blink('stop');</script>");
			return $this->do_dbupdate();
		} else { 
			// 管理画面モード指定
			if ($this->root->module['platform'] == "xoops") {
				$this->root->runmode = "xoops_admin";
			}
			return $this->show_admin_form();
		}
	}
	
	function show_admin_form () {
		//error_reporting(E_ERROR);
		
		@set_time_limit(120);
		
		//if (empty($this->root->post['action']) or !$this->root->userinfo['admin'])
		//{
			$this->msg['msg_usage'] = str_replace(array("%1d","%2d"),array(ini_get('max_execution_time'),ini_get('max_execution_time') - 5),$this->msg['msg_usage']);
			$body = $this->func->convert_html($this->msg['msg_usage']);
		//}
		if ($this->root->userinfo['admin'])
		{
			$not = array();
			foreach(array("i","c","p","a") as $type)
			{
				if (file_exists($this->cont['CACHE_DIR']."dbsync_".$type.".dat"))
				{
					$not[$type] = '<span style="color:red;font-weight:bold;"> *</span>';
				}
				else
				{
					$not[$type] = "";
				}
			}
			$body .= <<<__EOD__
<script>
<!--
var xpwiki_dbsync_doing = false;
var xpwiki_dbsync_timerID;
function xpwiki_dbsync_done()
{
	document.getElementById('xpwiki_dbsync_submit').style.visibility = "visible";
	document.getElementById('xpwiki_dbsync_submit').disabled = false;
}
function xpwiki_dbsync_blink(mode)
{
	var timer;
	clearTimeout(xpwiki_dbsync_timerID);
	
	if (mode == 'start') {
		document.getElementById('xpwiki_dbsync_submit').disabled = true;
		document.getElementById('xpwiki_dbsync_submit').style.visibility = "hidden";
	}
	
	if (mode == 'stop')
	{
		xpwiki_dbsync_doing = false;
	}
	else
	{
		xpwiki_dbsync_doing = true;
	}
	
	if (!xpwiki_dbsync_doing || document.getElementById('xpwiki_dbsync_doing').style.visibility == "visible")
	{
		document.getElementById('xpwiki_dbsync_doing').style.visibility = "hidden";
		timer = 200;
	}
	else
	{
		document.getElementById('xpwiki_dbsync_doing').style.visibility = "visible";
		timer = 800;
	}
	
	if (mode == 'start') {xpwiki_dbsync_setmsg('xpwiki_dbsync_doing','{$this->msg['msg_now_doing']}');}
	
	if (mode == 'continue')
	{
		xpwiki_dbsync_setmsg('xpwiki_dbsync_doing','{$this->msg['msg_next_do']}');
		document.getElementById('xpwiki_dbsync_doing').style.visibility = "visible";
	}
	
	if (xpwiki_dbsync_doing && mode != 'continue')
	{
		xpwiki_dbsync_timerID = setTimeout("xpwiki_dbsync_blink()", timer);
	}
}
function xpwiki_dbsync_setmsg(id,msg)
{
	document.getElementById(id).innerHTML = msg;
}
-->
</script>
<form target="pukiwiki_dbsync_work" style= "margin:0px;" method="POST" action="{$this->root->script}">
 <div>
  <input type="hidden" name="plugin" value="dbsync" />
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
  <input id="xpwiki_dbsync_submit" type="submit" value="{$this->msg['btn_submit']}" onClick="xpwiki_dbsync_blink('start');return true;" />
 </div>
</form>
<div id="xpwiki_dbsync_doing" style="color:red;background-color:white;visibility:hidden;width:500px;text-align:center;">{$this->msg['msg_now_doing']}</div>
<div>{$this->msg['msg_progress_report']}</div>
<iframe src="" height="350" width="500" name="pukiwiki_dbsync_work"></iframe>
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
		while( ob_get_level() ) { ob_end_clean() ; }
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
		
		//redirect_header("$script?plugin=dbsync",3,$_links_messages['msg_done']);
		echo $this->msg['msg_done'];
		echo "<script>parent.xpwiki_dbsync_done();parent.xpwiki_dbsync_blink('stop');</script>";
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
			//$this->func->get_pg_allow_viewer("",false,true);
			
			// 処理済ファイルデーター
			$work = $this->cont['CACHE_DIR']."dbsync_i.dat";
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
				$buildtime = filectime($this->cont['DATA_DIR'].$file) - $this->cont['LOCALZONE'];
				$editedtime = filemtime($this->cont['DATA_DIR'].$file) - $this->cont['LOCALZONE'];
				if (!$buildtime || $buildtime > $editedtime) $buildtime = $editedtime;
				
				$checkpostdata = join("",$this->func->get_source($page));
				if (!$checkpostdata)
				{
					@unlink($this->cont['DATA_DIR'].$file);
					continue;
				}
				
				//echo $page."<hr />";
				// 凍結？
				$arg = array();
				if (preg_match("/^#freeze\s*\n/",$checkpostdata,$arg))
				{
					$freeze = 1;
				}
				
				// pginfo
				$pginfo = $this->func->get_pginfo($page);
				
				foreach (array('uid', 'ucd', 'uname', 'einherit', 'vinherit', 'lastuid', 'lastucd', 'lastuname') as $key) {
					$$key = addslashes($pginfo[$key]);
				}
				foreach (array('eaids', 'egids', 'vaids', 'vgids') as $key) {
					if ($pginfo[$key] === 'all' || $pginfo[$key] === 'none') {
						$$key = $pginfo[$key];
					} else {
						$$key = '&'.$pginfo[$key].'&';
					}
				}
	
				// タイトル情報
				$title = "";
				if (!$id || !empty($this->root->post['title']) || !$this->func->get_heading($page, true))
				{
					$title = addslashes($this->func->get_heading_init($page));
					//echo $title;
				}
				
				if (!$id)
				{
					// 新規作成
					$query = "INSERT INTO ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo").
						" (`name`,`title`,`buildtime`,`editedtime`,`uid`,`ucd`,`uname`,`freeze`,`einherit`,`eaids`,`egids`,`vinherit`,`vaids`,`vgids`,`lastuid`,`lastucd`,`lastuname`,`update`)" .
						" values('$name','$title','$buildtime','$editedtime','$uid','$ucd','$uname','$freeze','$einherit','$eaids','$egids','$vinherit','$vaids','$vgids','$lastuid','$lastucd','$lastuname','1')";
				}
				else
				{
					// アップデート
					if ($title)
					{
						$title = ",`title`='$title'";
					}
					//echo $title;

					$value =
						  "`name`='$name'"
						.$title
						.",`buildtime`='$buildtime'"
						.",`editedtime`='$editedtime'"
						.",`uid`='$uid'"
						.",`ucd`='$ucd'"
						.",`uname`='$uname'"
						.",`freeze`='$freeze'"
						.",`einherit`='$einherit'"
						.",`eaids`='$eaids'"
						.",`egids`='$egids'"
						.",`vinherit`='$vinherit'"
						.",`vaids`='$vaids'"
						.",`vgids`='$vgids'"
						.",`lastuid`='$lastuid'"
						.",`lastucd`='$lastucd'"
						.",`lastuname`='$lastuname'"
						.",`update`='1'";
					$query = "UPDATE ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." SET $value WHERE pgid = '$id' LIMIT 1;";
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
					$this->plugin_dbsync_next_do();
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
			$work = $this->cont['CACHE_DIR']."dbsync_c.dat";
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
				
				$array = array_pad(file($this->cont['COUNTER_DIR'].$file), 5, '');
				$pgid = $this->func->get_pgid_by_name($page);
				$count = intval(rtrim($array[0]));
				$today = intval(rtrim($array[1]));
				$today_count = intval(rtrim($array[2]));
				$yesterday_count = intval(rtrim($array[3]));
				$ip = rtrim($array[4]);
				
				$query = "insert into ".$this->xpwiki->db->prefix($this->root->mydirname."_count")." (pgid,count,today,today_count,yesterday_count,ip) values('$pgid',$count,'$today',$today_count,$yesterday_count,'$ip');";
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
					$this->plugin_dbsync_next_do();
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
			$work = $this->cont['CACHE_DIR']."dbsync_p.dat";
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
				
				if ($this->func->plain_db_write($page,$mode,TRUE))
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
					$this->plugin_dbsync_next_do();
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
		if (!$this->func->exist_plugin('attach')) return;
		$attach = $this->func->get_plugin_instance('attach');
		if (!$attach) return;
		if ($dir = @opendir($this->cont['UPLOAD_DIR']))
		{
			// 処理済ファイルリストデーター
			$work = $this->cont['CACHE_DIR']."dbsync_a.dat";
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
					$this->plugin_dbsync_next_do();
				}
			}
			closedir($dir);
			echo " ( Done ".$counter." Files !)<hr />";
			echo "</div>";
			
			@unlink ($work);
		}
	}
	
	function plugin_dbsync_next_do()
	{
	//	global $script,$post,$_links_messages;
		
		//$token = $this->func->get_token_html();
		$token = '';
		
		$html = <<<__EOD__
<form method="POST" action="{$this->root->script}" onsubmit="return pukiwiki_check(this);">
 <div>
  {$token}
  <input type="hidden" name="encode_hint" value="ぷ" />
  <input type="hidden" name="plugin" value="dbsync" />
  <input type="hidden" name="pmode" value="update" />
  <input type="hidden" name="mode" value="select" />
  <input type="hidden" name="init" value="{$this->root->post['init']}" />
  <input type="hidden" name="title" value="{$this->root->post['title']}" />
  <input type="hidden" name="plain" value="{$this->root->post['plain']}" />
  <input type="hidden" name="plain_all" value="{$this->root->post['plain_all']}" />
  <input type="hidden" name="attach" value="{$this->root->post['attach']}" />
  <input type="submit" value="{$this->msg['btn_next_do']}" onClick="parent.xpwiki_dbsync_blink('start');return true;" />
 </div>
</form>
<script>
<!--
parent.xpwiki_dbsync_blink('continue');
-->
</script>
</body></html>
__EOD__;
		echo $html;
		
		exit();
	}
}
?>