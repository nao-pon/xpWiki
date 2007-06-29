<?php
class xpwiki_plugin_freeze extends xpwiki_plugin {
	function plugin_freeze_init () {



	}
	// PukiWiki - Yet another WikiWikiWeb clone.
	// $Id: freeze.inc.php,v 1.4 2007/06/29 08:51:32 nao-pon Exp $
	//
	// Freeze(Lock) plugin
	
	// Reserve 'Do nothing'. '^#freeze' is for internal use only.
	function plugin_freeze_convert() { return ''; }
	
	function plugin_freeze_action()
	{
	//	global $script, $vars, $function_freeze;
	//	global $_title_isfreezed, $_title_freezed, $_title_freeze;
	//	global $_msg_invalidpass, $_msg_freezing, $_btn_freeze;
	
		$page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
		if (! $this->root->function_freeze || ! $this->func->is_page($page))
			return array('msg' => '', 'body' => '');
	
		$pass = isset($this->root->vars['pass']) ? $this->root->vars['pass'] : NULL;
		$msg = $body = '';
		if ($this->func->is_freeze($page)) {
			// Freezed already
			$msg  = & $this->root->_title_isfreezed;
			$body = str_replace('$1', htmlspecialchars($this->func->strip_bracket($page)),
			$this->root->_title_isfreezed);
	
		} else if ($this->root->userinfo['admin'] || ($pass !== NULL && $this->func->pkwk_login($pass))) {
			// Freeze
			$postdata = $this->func->get_source($page);
			array_unshift($postdata, "#freeze\n");
			$this->root->rtf['freezefunc'] = true;
			$this->func->file_write($this->cont['DATA_DIR'], $page, join('', $postdata), TRUE);
	
			// Update
			$this->func->is_freeze($page, TRUE);
			
			// pginfo DB write
			$this->func->pginfo_freeze_db_write($page, 1);
			
			$this->root->vars['cmd'] = 'read';
			$msg  = & $this->root->_title_freezed;
			$body = '';
	
		} else {
			// Show a freeze form
			$msg    = & $this->root->_title_freeze;
			$s_page = htmlspecialchars($page);
			$body   = ($pass === NULL) ? '' : "<p><strong>{$this->root->_msg_invalidpass}</strong></p>\n";
			$body  .= <<<EOD
<p>{$this->root->_msg_freezing}</p>
<form action="{$this->root->script}" method="post">
 <div>
  <input type="hidden"   name="cmd"  value="freeze" />
  <input type="hidden"   name="page" value="$s_page" />
  <input type="password" name="pass" size="12" />
  <input type="submit"   name="ok"   value="{$this->root->_btn_freeze}" />
 </div>
</form>
EOD;
		}
	
		return array('msg'=>$msg, 'body'=>$body);
	}
}
?>