<?php
//
// Created on 2009/05/28 by nao-pon http://xoops.hypweb.net/
//

/**
 * Write HTML
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fhtml.inc.php
 * @version    $Id: html.inc.php,v 1.2 2011/07/29 07:14:25 nao-pon Exp $
 * @package    plugin
 */

class xpwiki_plugin_html extends xpwiki_plugin {
	var $config = array();
	
	function plugin_html_init () {
		switch ($this->cont['UI_LANG']) {
			case 'ja':
				$this->msg['error_admin'] = '<p>#html(): このページ($page) は、管理人以外が編集できるので HTML は表示されません。</p>';
				$this->msg['error_user']  = '<p>#html(): このページ($page) は、ゲストが編集できるので HTML は表示されません。</p>';
				break;
			default:
				$this->msg['error_admin'] = '<p>#html(): Because this page ($page) can be edited in case of no manager, HTML is not displayed.</p>';
				$this->msg['error_user']  = '<p>#html(): Because this page ($page) can be edited any guest, HTML is not displayed.</p>';
		}
		$this->config['auth_mode'] = 'admin'; // admin or user
	}
	
	function plugin_html_convert()
	{
	    $args = func_get_args();
	    $body = array_pop($args);
	    if (substr($body, -1) != "\r") {
	        return '<p>html(): no argument(s).</p>';
	    }
	    $page = $this->root->vars['page'];
	    if (isset($this->config['auth_mode']) && $this->config['auth_mode'] === 'user') {
	    	if ($this->root->render_mode === 'render' || $this->func->check_editable_page($page, false, false, 0)) {
	    		if ($this->cont['UI_LANG'] === 'ja' && $this->cont['SOURCE_ENCODING'] === 'UTF-8') {
	    			$this->msg['error_user'] = mb_convert_encoding($this->msg['error_user'], 'UTF-8', 'EUC-JP');
	    		}
	    		return str_replace('$page', $page, $this->msg['error_user']);
	    	}
	    } else {
		    if (! $this->func->is_editable_only_admin($page)) {
		        $page = $this->func->htmlspecialchars($page);
		        if ($this->cont['UI_LANG'] === 'ja' && $this->cont['SOURCE_ENCODING'] === 'UTF-8') {
		        	$this->msg['error_admin'] = mb_convert_encoding($this->msg['error_admin'], 'UTF-8', 'EUC-JP');
		        }
		        return str_replace('$page', $page, $this->msg['error_admin']);
		    }
	    }

	    $noskin = in_array("noskin", $args);
	    if ($noskin) {
			// clear output buffer
			$this->func->clear_output_buffer();
	        $this->func->pkwk_common_headers();
	        print $body;
	        exit;
	    }
	    return $body;
	}
}
