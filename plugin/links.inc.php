<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: links.inc.php,v 1.2 2006/11/07 00:50:16 nao-pon Exp $
//
// Update link cache plugin
	
class xpwiki_plugin_links extends xpwiki_plugin {

	function plugin_links_init()
	{

		// 管理画面モード指定
		if ($this->root->module['platform'] == "xoops") {
			$this->root->runmode = "xoops_admin";
		}
		
		// 言語ファイルの読み込み
		$this->load_language();
		
		// Message setting
		$messages = array(
			'_links_messages'=>array(
			'title_update'  => 'キャッシュ更新',
			'msg_adminpass' => '管理者パスワード',
			'btn_submit'    => '実行',
			'msg_done'      => 'キャッシュの更新が完了しました。',
			'msg_usage'     => "
* 処理内容

:キャッシュを更新|
全てのページをスキャンし、あるページがどのページからリンクされているかを調査して、キャッシュに記録します。

* 注意
実行には数分かかる場合もあります。実行ボタンを押したあと、しばらくお待ちください。

* 実行
[実行]ボタンをクリックしてください。
"
			)
		);
		//$this->func->set_plugin_messages($messages);
	}
	
	function plugin_links_action()
	{
	//	global $script, $post, $vars, $foot_explain;
	//	global $_links_messages;
	
		if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits this');
	
		$msg = $body = '';
		if (empty($this->root->vars['action']) || ! $this->func->pkwk_login()) {
			$msg   = & $this->msg['title_update'];
			$body  = $this->func->convert_html($this->msg['msg_usage']);
			$body .= <<<EOD
<form method="POST" action="{$this->root->script}">
 <div>
  <input type="hidden" name="plugin" value="links" />
  <input type="hidden" name="action" value="update" />
  <input type="submit" value="{$this->msg['btn_submit']}" />
 </div>
</form>
EOD;
	
		} else if ($this->root->vars['action'] == 'update') {
			$this->func->links_init();
			$this->root->foot_explain = array(); // Exhaust footnotes
			$msg  = & $this->msg['title_update'];
			$body = & $this->msg['msg_done'    ];
		} else {
			$msg  = & $this->msg['title_update'];
			$body = & $this->msg['err_invalid' ];
		}
		return array('msg'=>$msg, 'body'=>$body);
	}
}
?>