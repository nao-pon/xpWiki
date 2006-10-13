<?php
class xpwiki_plugin_links extends xpwiki_plugin {
	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: links.inc.php,v 1.1 2006/10/13 13:17:49 nao-pon Exp $
	//
	// Update link cache plugin
	
	// Message setting
	function plugin_links_init()
	{


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
	管理者パスワードを入力して、[実行]ボタンをクリックしてください。
	"
		)
		);
		$this->func->set_plugin_messages($messages);
	}
	
	function plugin_links_action()
	{
	//	global $script, $post, $vars, $foot_explain;
	//	global $_links_messages;
	
		if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits this');
	
		$msg = $body = '';
		if (empty($this->root->vars['action']) || empty($this->root->post['adminpass']) || ! $this->func->pkwk_login($this->root->post['adminpass'])) {
			$msg   = & $this->root->_links_messages['title_update'];
			$body  = $this->func->convert_html($this->root->_links_messages['msg_usage']);
			$body .= <<<EOD
<form method="POST" action="{$this->root->script}">
 <div>
  <input type="hidden" name="plugin" value="links" />
  <input type="hidden" name="action" value="update" />
  <label for="_p_links_adminpass">{$this->root->_links_messages['msg_adminpass']}</label>
  <input type="password" name="adminpass" id="_p_links_adminpass" size="20" value="" />
  <input type="submit" value="{$this->root->_links_messages['btn_submit']}" />
 </div>
</form>
EOD;
	
		} else if ($this->root->vars['action'] == 'update') {
			$this->func->links_init();
			$this->root->foot_explain = array(); // Exhaust footnotes
			$msg  = & $this->root->_links_messages['title_update'];
			$body = & $this->root->_links_messages['msg_done'    ];
		} else {
			$msg  = & $this->root->_links_messages['title_update'];
			$body = & $this->root->_links_messages['err_invalid' ];
		}
		return array('msg'=>$msg, 'body'=>$body);
	}
}
?>