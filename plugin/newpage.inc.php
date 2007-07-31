<?php
class xpwiki_plugin_newpage extends xpwiki_plugin {
	function plugin_newpage_init () {



	}
	// $Id: newpage.inc.php,v 1.4 2007/07/31 03:03:38 nao-pon Exp $
	//
	// Newpage plugin
	
	function plugin_newpage_convert()
	{
	//	global $script, $vars, $_btn_edit, $_msg_newpage, $BracketName;
	//	static $id = 0;
		static $id = array();
		if (!isset($id[$this->xpwiki->pid])) {$id[$this->xpwiki->pid] = 0;}
	
		if ($this->cont['PKWK_READONLY'] === 1) return ''; // Show nothing
	
		$newpage = '';
		if (func_num_args()) list($newpage) = func_get_args();
		if (strtolower($newpage) === 'this') {
			$newpage = $this->root->vars['page'] . '/';
		}
		
		if (! preg_match('/^' . $this->root->BracketName . '$/', $newpage)) $newpage = '';
		
	
		$s_page    = htmlspecialchars(isset($this->root->vars['refer']) ? $this->root->vars['refer'] : $this->root->vars['page']);
		$s_newpage = htmlspecialchars($newpage);
		$base = (empty($s_newpage))? '' : $s_newpage ;
		
		++$id[$this->xpwiki->pid];
	
		$ret = <<<EOD
<form action="{$this->root->script}" method="post">
 <div>
  <input type="hidden" name="plugin" value="newpage" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="base" value="$s_newpage" />
  <label for="_p_newpage_{$id[$this->xpwiki->pid]}">{$this->root->_msg_newpage}: {$base}</label>
  <input type="text"   name="page" id="_p_newpage_{$id[$this->xpwiki->pid]}" value="" size="30" />
  <input type="submit" value="{$this->root->_btn_edit}" />
 </div>
</form>
EOD;
	
		return $ret;
	}
	
	function plugin_newpage_action()
	{
	//	global $vars, $_btn_edit, $_msg_newpage;
	
		if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits editing');
	
		if ($this->root->vars['page'] == '') {
			$retvars['msg']  = $this->root->_msg_newpage;
			$retvars['body'] = $this->plugin_newpage_convert();
			return $retvars;
		} else {
			$base = (empty($this->root->vars['base']))? '' : $this->root->vars['base'] . '/';
			$page    = $base . $this->func->strip_bracket($this->root->vars['page']);
			$r_page  = rawurlencode(isset($this->root->vars['refer']) ?
				$this->func->get_fullname($page, $this->root->vars['refer']) : $page);
			$r_refer = rawurlencode($this->root->vars['refer']);
	
			$this->func->send_location('', '', $this->func->get_script_uri() .
			'?cmd=read&page=' . $r_page . '&refer=' . $r_refer);
		}
	}
}
?>