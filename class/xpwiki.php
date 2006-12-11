<?php
//
// Created on 2006/09/29 by nao-pon http://hypweb.net/
// $Id: xpwiki.php,v 1.22 2006/12/11 04:23:35 nao-pon Exp $
//

class XpWiki {
	
	var $runmode = "xoops";
	var $module;

	var $root;  // Like a Global variable
	var $const; // Like a Const
	var $func;  // All functions
	var $db;    // Database Connection
	
	var $skin_title;
	var $page;
	var $body;
	var $html;


	function XpWiki ($mydirname) {
		
		static $pid;
		
		$pid ++;
		$this->pid = $pid;
				
		$this->root = new XpWikiRoot();
		$this->cont =& $this->root->c;
		$this->root->mydirname = $mydirname;
		
		$this->func = new XpWikiFunc($this);
		$this->func->set_moduleinfo();

		$this->root->mydirpath = $this->cont['ROOT_PATH']."modules/".$mydirname;
		$this->root->mytrustdirpath = dirname(dirname(__FILE__));

		$this->cont['DATA_HOME'] = $this->root->mydirpath."/";
		$this->cont['HOME_URL'] = $this->cont['ROOT_URL']."modules/".$mydirname."/";
		
		$this->db =& $this->func->get_db_connection(); 
		
	}

	function init($page = "") {

		if ($page) {$this->cont['page_show'] = $page;}
		
		// サイト情報読み込み
		$this->func->set_siteinfo();
		
		// ini ファイル読み込み
		$this->func->load_ini();

		// アクセスユーザーの情報読み込み
		$this->func->set_userinfo();
		
		// cookie 用ユーザーコード取得 & cookie読み書き 
		$this->func->load_usercookie();

		// 各パラメーターを初期化
		$this->func->init();
	}
	
	function execute() {
		
		$root = & $this->root;
		$func = & $this->func;
				
		$base    = $root->defaultpage;
		$retvars = array();

		if (isset($root->vars['plugin'])) {
			// Plug-in action
			if (! $func->exist_plugin_action($root->vars['plugin'])) {
				$s_plugin = htmlspecialchars($root->vars['plugin']);
				$msg      = "plugin=$s_plugin is not implemented.";
				$retvars  = array('msg'=>$msg,'body'=>$msg);
			} else {
				$retvars  = $func->do_plugin_action($root->vars['plugin']);
				if ($retvars !== FALSE) {
					$base = isset($root->vars['refer']) ? $root->vars['refer'] : '';
					$root->vars['cmd'] = $root->vars['plugin'];
				}
			}

		} else if (isset($root->vars['cmd'])) {
			// Command action
			if (! $func->exist_plugin_action($root->vars['cmd'])) {
				$s_cmd   = htmlspecialchars($root->vars['cmd']);
				$msg     = "cmd=$s_cmd is not implemented.";
				$retvars = array('msg'=>$msg,'body'=>$msg);
			} else {
				$retvars = $func->do_plugin_action($root->vars['cmd']);
				$base    = $root->vars['page'];
			}
		}

		if ($retvars !== FALSE) {
			$title = htmlspecialchars($func->strip_bracket($base));
			$page  = $func->make_search($base);

			if (isset($retvars['msg']) && $retvars['msg'] != '') {
				$title = str_replace('$1', $title, $retvars['msg']);
				$page  = str_replace('$1', $page,  $retvars['msg']);
			}

			if (isset($retvars['body']) && $retvars['body'] != '') {
				$body = $retvars['body'];
			} else {
				if ($base == '' || ! $func->is_page($base)) {
					$base  = $root->defaultpage;
					$page  = $func->make_search($base);
				}
				
				if (!empty($root->vars['cmd']) && $root->vars['cmd'] != 'read') {
					$func->ref_save($base);
					$func->redirect_header($root->script."?".rawurlencode($base),0,$title);
					exit();
				} else {
					$root->vars['cmd']  = 'read';
					$root->vars['page'] = $base;
					$body  = $func->get_body($base);
					
					if ($root->trackback) {
						$body .= $func->tb_get_rdf($root->vars['page']);
					}
					$func->ref_save($root->vars['page']);
				}
			}
			// cont['USER_NAME_REPLACE'] を 置換
			$body  = str_replace($this->cont['USER_NAME_REPLACE'], $this->root->userinfo['uname_s'], $body);


			// Output
			$page_title = strip_tags($title);
			$content_title = (!empty($this->root->content_title) && $title !== $this->root->content_title)?
				' ['.$this->func->unhtmlspecialchars($this->root->content_title, ENT_QUOTES).']' : '';
			
			$root->pagetitle = str_replace(
									array('$page_title', '$content_title', '$module_title'),
									array($page_title, $content_title, $this->root->module_title),
									$root->html_head_title);

			$this->title         = $title;
			$this->page          = $base;
			$this->skin_title    = $page;
			$this->body          = $body;
			$this->foot_explain  = $root->foot_explain;
			$this->head_pre_tags = $root->head_pre_tags;
			$this->head_tags     = $root->head_tags;
			$this->related       = $root->related;
			$this->notyets       = $root->notyets;
		}
	}
	
	function catbody () {
		
		// SKIN select from Cookie or Plugin.
		if ($this->cont['SKIN_CHANGER']) {
			if ($this->root->cookie['skin']) {$this->cont['SKIN_NAME'] = $this->root->cookie['skin']; }
			if (isset($this->cont['SKIN_NAME']) && preg_match('/^[\w-]+$/', $this->cont['SKIN_NAME'])) {
				if (substr($this->cont['SKIN_NAME'],0,3) === "tD-") {
					//tDiary's theme
					$theme_name = substr($this->cont['SKIN_NAME'],3);
					$theme_css = $this->cont['DATA_HOME'] . $this->cont['TDIARY_DIR'] . $theme_name . '/' . $theme_name . '.css';
					if (file_exists($theme_css)) {
						$this->cont['SKIN_FILE'] = $this->cont['DATA_HOME'] . $this->cont['TDIARY_DIR'] . 'tdiary.skin.php';
						$this->cont['TDIARY_THEME'] =  $theme_name;
					}
				} else {
					//PukiWiki's skin
					$skindir = "skin/" . $this->cont['SKIN_NAME'] . "/";
					$skin = $this->cont['DATA_HOME'] . $skindir . 'pukiwiki.skin.php';
					if (file_exists($skin)) {
						$this->cont['SKIN_DIR'] = $skindir;
						$this->cont['SKIN_FILE'] = $skin;
					}
				}
			}
		}
		
		// catbody
		ob_start();
		$this->func->catbody($this->title, $this->skin_title, $this->body);
		$this->html = ob_get_contents();
		if (!empty($this->root->runmode)) $this->runmode = $this->root->runmode;
		while( ob_get_level() ) { ob_end_clean() ; }

		return;
	}
	
	function get_body () {

		return $this->body;

	}
	
	function get_page_count () {
		if (!$this->page || !$this->func->is_page($this->page)) return 0;
		$pgid = $this->func->get_pgid_by_name($this->page);
		
		$count = 0;
		
		$sql = 'SELECT `count` FROM `'.$this->db->prefix($this->root->mydirname."_count").'` WHERE pgid = '.$pgid.' LIMIT 1';
		$res = $this->db->query($sql);
		if ($res) {
			list($count) = mysql_fetch_row($res);	
		}
		return $count;
	}
	
	function get_comment_count () {
		return $this->func->count_page_comments($this->page);
	}
	
	function get_pginfo () {
		return $this->func->get_pginfo($this->page);
	}
	
	function get_html_for_block ($page, $width = "100%") {
		
		// 初期化
		$this->init($page);
		
		// for menu plugin etc..
		$this->root->runmode = "xoops";
		
		// ブロック取得モード
		$this->root->is_block = TRUE;
		
		// 実行
		$this->execute();

		// SKIN select from Cookie or Plugin.
		if ($this->cont['SKIN_CHANGER']) {
			if ($this->root->cookie['skin']) {$this->cont['SKIN_NAME'] = $this->root->cookie['skin']; }
			if (isset($this->cont['SKIN_NAME']) && preg_match('/^[\w-]+$/', $this->cont['SKIN_NAME'])) {
				if (substr($this->cont['SKIN_NAME'],0,3) === "tD-") {
					//tDiary's theme
					
				} else {
					//PukiWiki's skin
					$skindir = "skin/" . $this->cont['SKIN_NAME'] . "/";
					$skin = $this->cont['DATA_HOME'] . $skindir . 'pukiwiki.skin.php';
					if (file_exists($skin)) {
						$this->cont['SKIN_DIR'] = $skindir;
					}
				}
			}
		}
		// Head Tags
		list($head_pre_tag, $head_tag) = $this->func->get_additional_headtags($this);
		
		// 出力
		$base = "b_".$this->root->mydirname;
		$block = <<< EOD
$head_pre_tag
<link rel="stylesheet" type="text/css" media="screen" href="{$this->cont['HOME_URL']}{$this->cont['SKIN_DIR']}block.css.php?charset=Shift_JIS&amp;base={$base}" charset="Shift_JIS" />
$head_tag
<div class="xpwiki_{$base}" style="width:{$width};overflow:hidden;">
{$this->body}
</div>
EOD;

		return $block;
	}
/*
	// すべてのExtensionを読み込む
	function load_extensions_all () {
		$base = $this->root->mytrustdirpath."/class/extension";
		if ($handle = opendir($base)) {
			while (false !== ($file = readdir($handle))) {
				if (preg_match("/^([\w-]+\).php$/",$file,$match)) {
					include_once($base."/".$file);
					$name = $match[1];
					$class = "XPWikiExtension_".$name;
					if (class_exists($class)) {
						$this->extension->$name = new $class($this);
					}
				}
			}
			closedir($handle);
		}	
	}
*/
	// 指定のExtensionを読み込む
	function load_extensions ($exts) {
		$base = $this->root->mytrustdirpath."/class/extension";
		if (!is_array($exts)) {
			$exts = array($exts);
		}
		foreach($exts as $name) {
			if (preg_match("/^[\w-]+$/",$name)) {
				include_once($base."/".$name.".php");
				$class = "XPWikiExtension_".$name;
				if (class_exists($class)) {
					$this->extension->$name = new $class($this);
				}
			}
		}	
	}
}
?>