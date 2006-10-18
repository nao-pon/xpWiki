<?php
//
// Created on 2006/09/29 by nao-pon http://hypweb.net/
// $Id: xpwiki.php,v 1.4 2006/10/18 13:27:36 nao-pon Exp $
//

class XpWiki {
	
	var $runmode = "xoops";
	var $module;

	var $root;
	var $const;
	var $func;
	
	var $page_name;
	var $body;
	var $html;


	function XpWiki ($mydirname) {
		
		static $pid;
		
		$pid ++;
		$this->pid = $pid;
				
		$this->root = new XpWikiRoot();
		$this->cont = & $this->root->c;
		$this->root->mydirname = $mydirname;
		
		$this->func = new XpWikiFunc($this);
		$this->func->set_moduleinfo();

		$this->root->mydirpath = $this->cont['ROOT_PATH']."modules/".$mydirname;
		$this->root->mytrustdirpath = dirname(dirname(__FILE__));

		$this->cont['DATA_HOME'] = $this->root->mydirpath."/";
		$this->cont['HOME_URL'] = $this->cont['ROOT_URL']."modules/".$mydirname."/";
		
		// アクセスユーザーの情報読み込み
		$this->func->set_userinfo();
		
		// cookie 用ユーザーコード取得 & cookie読み書き 
		$this->func->set_usercode();
	}

	function init($page = "") {

		if ($page) {$this->cont['page_show'] = $page;}
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
					$title = htmlspecialchars($func->strip_bracket($base));
					$page  = $func->make_search($base);
				}
				
				if (!empty($root->vars['cmd']) && $root->vars['cmd'] != 'read') {
					$func->ref_save($base);
					redirect_header($root->script."?".rawurlencode($base),0,$title);
					exit();
				} else {
					$vars['cmd']  = 'read';
					$vars['page'] = $base;
					$body  = $func->convert_html($func->get_source($base));
					$body .= $func->tb_get_rdf($root->vars['page']);
					$func->ref_save($root->vars['page']);
				}
			}

			// Output
			$this->title     = $title;
			$this->page_name = $page;
			$this->body      = $body;
		}
	}
	
	function catbody () {
		
		ob_start();
		$this->func->catbody($this->title, $this->page_name, $this->body);
		$this->html = ob_get_contents();
		if (!empty($this->root->runmode)) $this->runmode = $this->root->runmode;
		ob_end_clean();

		return;
	}
	
	function get_body () {

		return $this->body;

	}
	
	function get_html_for_block ($page, $width = "100%") {
		
		// 初期化
		$this->init($page);
		
		// 実行
		$this->execute();
		
		// 出力
		$class = "xpwiki_".$this->root->mydirname;
		$block = <<< EOD
<link rel="stylesheet" type="text/css" media="screen" href="{$this->cont['HOME_URL']}{$this->cont['SKIN_DIR']}pukiwiki.css.php?charset=Shift_JIS&amp;base={$this->root->mydirname}" charset="Shift_JIS" />
<script type="text/javascript">
<!--
var wikihelper_root_url = "{$this->cont['HOME_URL']}";
//-->
</script>
<script type="text/javascript" src="{$this->cont['HOME_URL']}skin/js/?src=default.{$this->cont['UI_LANG']}"></script>
<div class="{$class}" style="width:{$width};overflow:hidden;">
{$this->body}
</div>
EOD;

		return $block;
	}
	
	function setValue ($key, $val) {
		$this->$key = $val;
	}
}
?>