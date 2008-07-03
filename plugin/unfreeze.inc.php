<?php
class xpwiki_plugin_unfreeze extends xpwiki_plugin {
	function plugin_unfreeze_init () {


	// PukiWiki - Yet another WikiWikiWeb clone.
	// $Id: unfreeze.inc.php,v 1.7 2008/07/03 00:01:42 nao-pon Exp $
	//
	// Unfreeze(Unlock) plugin
	
	// Show edit form when unfreezed
		$this->cont['PLUGIN_UNFREEZE_EDIT'] =  TRUE;

	}
	
	function plugin_unfreeze_action()
	{
	//	global $script, $vars, $function_freeze;
	//	global $_title_isunfreezed, $_title_unfreezed, $_title_unfreeze;
	//	global $_msg_invalidpass, $_msg_unfreezing, $_btn_unfreeze;
	
		$page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
		if (! $this->root->function_freeze || ! $this->func->is_page($page))
			return array('msg' => '', 'body' => '');
	
		$pass = isset($this->root->vars['pass']) ? $this->root->vars['pass'] : NULL;
		$msg = $body = '';
		if (! $this->func->is_freeze($page)) {
			// Unfreezed already
			$msg  = & $this->root->_title_isunfreezed;
			$body = str_replace('$1', $this->func->make_pagelink($page), $this->root->_title_isunfreezed);
	
		} else if ($this->func->is_owner($page) || ($pass !== NULL && $this->func->pkwk_login($pass))) {
			// Unfreeze
			$postdata = $this->func->get_source($page);
			array_shift($postdata);
			$postdata = join('', $postdata);
			$this->root->rtf['no_checkauth_on_write'] = true;
			$this->func->file_write($this->cont['DATA_DIR'], $page, $postdata, TRUE);
	
			// pginfo DB write
			$this->func->pginfo_freeze_db_write ($page, 0);

			// Update 
			$this->func->is_freeze($page, TRUE);

			if ($this->cont['PLUGIN_UNFREEZE_EDIT']) {
				$this->root->vars['cmd'] = 'read'; // To show 'Freeze' link
				$msg  = & $this->root->_title_unfreezed;
				$body = $this->func->edit_form($page, $postdata);
			} else {
				$this->root->vars['cmd'] = 'read';
				$msg  = & $this->root->_title_unfreezed;
				$body = '';
			}
	
		} else {
			// Show unfreeze form
			$msg    = & $this->root->_title_unfreeze;
			$s_page = htmlspecialchars($page);
			$body   = ($pass === NULL) ? '' : "<p><strong>{$this->root->_msg_invalidpass}</strong></p>\n";
			$body  .= <<<EOD
<p>{$this->root->_msg_unfreezing}</p>
<form action="{$this->root->script}" method="post">
 <div>
  <input type="hidden"   name="cmd"  value="unfreeze" />
  <input type="hidden"   name="page" value="$s_page" />
  <input type="password" name="pass" size="12" />
  <input type="submit"   name="ok"   value="{$this->root->_btn_unfreeze}" />
 </div>
</form>
EOD;
		}
	
		return array('msg'=>$msg, 'body'=>$body);
	}
}
?>