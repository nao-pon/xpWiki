<?php
class xpwiki_plugin_vote extends xpwiki_plugin {
	function plugin_vote_init () {



	}
	// PukiWiki - Yet another WikiWikiWeb clone.
	// $Id: vote.inc.php,v 1.2 2006/10/18 03:02:08 nao-pon Exp $
	//
	// Vote box plugin
	
	function plugin_vote_action()
	{
	//	global $vars, $script, $cols,$rows;
	//	global $_title_collided, $_msg_collided, $_title_updated;
	//	global $_vote_plugin_votes;
	
		if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits editing');
	
		$postdata_old  = $this->func->get_source($this->root->vars['refer']);
	
		$vote_no = 0;
		$title = $body = $postdata = $postdata_input = $vote_str = '';
		$matches = array();
		foreach($postdata_old as $line) {
	
			if (! preg_match('/^#vote(?:\((.*)\)(.*))?$/i', $line, $matches) ||
			    $vote_no++ != $this->root->vars['vote_no']) {
				$postdata .= $line;
				continue;
			}
			$args  = explode(',', $matches[1]);
			$lefts = isset($matches[2]) ? $matches[2] : '';
	
			foreach($args as $arg) {
				$cnt = 0;
				if (preg_match('/^(.+)\[(\d+)\]$/', $arg, $matches)) {
					$arg = $matches[1];
					$cnt = $matches[2];
				}
				$e_arg = $this->func->encode($arg);
				if (! empty($this->root->vars['vote_' . $e_arg]) && $this->root->vars['vote_' . $e_arg] == $this->root->_vote_plugin_votes)
					++$cnt;
	
				$votes[] = $arg . '[' . $cnt . ']';
			}
	
			$vote_str       = '#vote(' . @join(',', $votes) . ')' . $lefts . "\n";
			$postdata_input = $vote_str;
			$postdata      .= $vote_str;
		}
	
		if (md5(@join('', $this->func->get_source($this->root->vars['refer']))) != $this->root->vars['digest']) {
			$title = $this->root->_title_collided;
	
			$s_refer          = htmlspecialchars($this->root->vars['refer']);
			$s_digest         = htmlspecialchars($this->root->vars['digest']);
			$s_postdata_input = htmlspecialchars($postdata_input);
			$body = <<<EOD
{$this->root->_msg_collided}
<form action="{$this->root->script}?cmd=preview" method="post">
 <div>
  <input type="hidden" name="refer"  value="$s_refer" />
  <input type="hidden" name="digest" value="$s_digest" />
  <textarea name="msg" rel="wikihelper" rows="{$this->root->rows}" cols="{$this->root->cols}" id="textarea">$s_postdata_input</textarea><br />
 </div>
</form>

EOD;
		} else {
			$this->func->page_write($this->root->vars['refer'], $postdata);
			$title = $this->root->_title_updated;
		}
	
		$this->root->vars['page'] = $this->root->vars['refer'];
	
		return array('msg'=>$title, 'body'=>$body);
	}
	
	function plugin_vote_convert()
	{
	//	global $script, $vars,  $digest;
	//	global $_vote_plugin_choice, $_vote_plugin_votes;
	//	static $number = array();
		static $number = array();
		if (!isset($number[$this->xpwiki->pid])) {$number[$this->xpwiki->pid] = array();}
	
		$page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
		
		// Vote-box-id in the page
		if (! isset($number[$this->xpwiki->pid][$page])) $number[$this->xpwiki->pid][$page] = 0; // Init
		$vote_no = $number[$this->xpwiki->pid][$page]++;
	
		if (! func_num_args()) return '#vote(): No arguments<br />' . "\n";
	
		if ($this->cont['PKWK_READONLY']) {
			$_script = '';
			$_submit = 'hidden';
		} else {
			$_script = $this->root->script;
			$_submit = 'submit';
		}
	
		$args     = func_get_args();
		$s_page   = htmlspecialchars($page);
		$s_digest = htmlspecialchars($this->root->digest);
	
		$body = <<<EOD
<form action="$_script" method="post">
 <table cellspacing="0" cellpadding="2" class="style_table" summary="vote">
  <tr>
   <td align="left" class="vote_label" style="padding-left:1em;padding-right:1em"><strong>{$this->root->_vote_plugin_choice}</strong>
    <input type="hidden" name="plugin"  value="vote" />
    <input type="hidden" name="refer"   value="$s_page" />
    <input type="hidden" name="vote_no" value="$vote_no" />
    <input type="hidden" name="digest"  value="$s_digest" />
   </td>
   <td align="center" class="vote_label"><strong>{$this->root->_vote_plugin_votes}</strong></td>
  </tr>

EOD;
	
		$tdcnt = 0;
		$matches = array();
		foreach($args as $arg) {
			$cnt = 0;
	
			if (preg_match('/^(.+)\[(\d+)\]$/', $arg, $matches)) {
				$arg = $matches[1];
				$cnt = $matches[2];
			}
			$e_arg = $this->func->encode($arg);
	
			$link = $this->func->make_link($arg);
	
			$cls = ($tdcnt++ % 2)  ? 'vote_td1' : 'vote_td2';
	
			$body .= <<<EOD
  <tr>
   <td align="left"  class="$cls" style="padding-left:1em;padding-right:1em;">$link</td>
   <td align="right" class="$cls">$cnt&nbsp;&nbsp;
    <input type="$_submit" name="vote_$e_arg" value="{$this->root->_vote_plugin_votes}" class="submit" />
   </td>
  </tr>

EOD;
		}
	
		$body .= <<<EOD
 </table>
</form>

EOD;
	
		return $body;
	}
}
?>