<?php
class xpwiki_plugin_update_entities extends xpwiki_plugin {
	
	// メッセージ設定
	function plugin_update_entities_init()
	{

	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: update_entities.inc.php,v 1.2 2006/10/16 02:18:09 nao-pon Exp $
	//
	// Update entities plugin - Update XHTML entities from DTD
	// (for admin)
	
	// DTDの場所
		$this->cont['W3C_XHTML_DTD_LOCATION'] =  'http://www.w3.org/TR/xhtml1/DTD/';

		$messages = array(
			'_entities_messages'=>array(
				'title_update'  => 'キャッシュ更新',
			'msg_adminpass' => '管理者パスワード',
			'btn_submit'    => '実行',
			'msg_done'      => 'キャッシュの更新が完了しました。',
			'msg_usage'     => '
* 処理内容
	
	:文字実体参照にマッチする正規表現パターンのキャッシュを更新|
	PHPの持つテーブルおよびW3CのDTDをスキャンして、キャッシュに記録します。
	
	* 処理対象
	$this->func->「COLOR(red){not found.}」と表示されたファイルは処理されません。
	-%s
	
	* 実行
	管理者パスワードを入力して、[実行]ボタンをクリックしてください。
	'
		));
		$this->func->set_plugin_messages($messages);
	}
	
	function plugin_update_entities_action()
	{
	//	global $script, $vars;
	//	global $_entities_messages;
	
		if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits this');
	
		$msg = $body = '';
		if (empty($this->root->vars['action']) || empty($this->root->vars['adminpass']) || ! $this->func->pkwk_login($this->root->vars['adminpass'])) {
			$msg   = & $this->root->_entities_messages['title_update'];
			$items = $this->plugin_update_entities_create();
			$body  = $this->func->convert_html(sprintf($this->root->_entities_messages['msg_usage'], join("\n" . '-', $items)));
			$body .= <<<EOD
<form method="POST" action="{$this->root->script}">
 <div>
  <input type="hidden" name="plugin" value="update_entities" />
  <input type="hidden" name="action" value="update" />
  <label for="_p_update_entities_adminpass">{$this->root->_entities_messages['msg_adminpass']}</label>
  <input type="password" name="adminpass" id="_p_update_entities_adminpass" size="20" value="" />
  <input type="submit" value="{$this->root->_entities_messages['btn_submit']}" />
 </div>
</form>
EOD;
		} else if ($this->root->vars['action'] == 'update') {
			$this->plugin_update_entities_create(TRUE);
			$msg  = & $this->root->_entities_messages['title_update'];
			$body = & $this->root->_entities_messages['msg_done'    ];
		} else {
			$msg  = & $this->root->_entities_messages['title_update'];
			$body = & $this->root->_entities_messages['err_invalid' ];
		}
		return array('msg'=>$msg, 'body'=>$body);
	}
	
	// Remove &amp; => amp
	function plugin_update_entities_strtr($entity){
		return strtr($entity, array('&'=>'', ';'=>''));
	}
	
	function plugin_update_entities_create($do = FALSE)
	{
		$files = array('xhtml-lat1.ent', 'xhtml-special.ent', 'xhtml-symbol.ent');
		
		$entities = array_map('plugin_update_entities_strtr',
		array_values(get_html_translation_table(HTML_ENTITIES)));
		$items   = array('php:html_translation_table');
		$matches = array();
		foreach ($files as $file) {
			$source = file($this->cont['W3C_XHTML_DTD_LOCATION'] . $file);
	//			or die_message('cannot receive ' . W3C_XHTML_DTD_LOCATION . $file . '.');
			if (! is_array($source)) {
				$items[] = 'w3c:' . $file . ' COLOR(red):not found.';
				continue;
			}
			$items[] = 'w3c:' . $file;
			if (preg_match_all('/<!ENTITY\s+([A-Za-z0-9]+)/',
			join('', $source), $matches, PREG_PATTERN_ORDER))
			{
				$entities = array_merge($entities, $matches[1]);
			}
		}
		if (! $do) return $items;
	
		$entities = array_unique($entities);
		sort($entities, SORT_STRING);
		$min = 999;
		$max = 0;
		foreach ($entities as $entity) {
			$len = strlen($entity);
			$max = max($max, $len);
			$min = min($min, $len);
		}
	
		$pattern = "(?=[a-zA-Z0-9]\{$min,$max})" . $this->func->get_matcher_regex($entities);
		$fp = fopen($this->cont['CACHE_DIR']  . $this->cont['PKWK_ENTITIES_REGEX_CACHE'], 'w')
			or $this->func->die_message('cannot write file PKWK_ENTITIES_REGEX_CACHE<br />' . "\n" .
			'maybe permission is not writable or filename is too long');
		fwrite($fp, $pattern);
		fclose($fp);
	
		return $items;
	}
}
?>