<?php
class xpwiki_plugin_if extends xpwiki_plugin {
	
	// #if([page=PAGENAME][,uid=NUMBAR[&NUMBAR]][,gid=NUMBAR[&NUMBAR]][,admin][,owner][,user][,guest][,writable]){{
	// Wiki Contents
	// }}
	
	function plugin_if_convert() {
		
		$options = array(
			'page'     => '',
			'uid'      => '',
			'gid'      => '',
			'admin'    => false,
			'owner'    => false,
			'user'     => false,
			'guest'    => false,
			//'readable' => false,
			'writable' => false
		);
		$args = func_get_args();
		$this->fetch_options($options, $args);
		
		$body = '';
		if ($options['_args']) {
			$body = array_pop($options['_args']);
		}
		
		$render = false;
		if ($body) {
			$page = ($this->root->render_mode === 'block' && isset($GLOBALS['Xpwiki_'.$this->root->mydirname]['page']))? $GLOBALS['Xpwiki_'.$this->root->mydirname]['page'] : $this->root->vars['page'];
			if ($options['page']) {
				$render = $this->func->str_match_wildcard($options['page'], $page);
			}
			
			if ($options['uid']) {
				$this->root->rtf['disable_render_cache'] = true;
				$uids = explode('&', $options['uid']);
				$uids = array_map('strval', $uids);
				$uids = array_flip($uids);
				$render = isset($uids[$this->root->userinfo['uid']]);
			}
			
			if ($options['gid']) {
				$this->root->rtf['disable_render_cache'] = true;
				$gids = explode('&', $options['gid']);
				$gids = array_map('strval', $gids);
				$render = (array_intersect($gids, $this->root->userinfo['gids']));
			}
			
			if ($options['admin']) {
				$this->root->rtf['disable_render_cache'] = true;
				$render = ($this->root->userinfo['admin']);
			}
			
			if ($options['owner']) {
				$this->root->rtf['disable_render_cache'] = true;
				$render = ($this->func->is_owner($page));
			}
			
			if ($options['user']) {
				$this->root->rtf['disable_render_cache'] = true;
				$render = ($this->root->userinfo['uid']);
			}
			
			if ($options['guest']) {
				$this->root->rtf['disable_render_cache'] = true;
				$render = (! $this->root->userinfo['uid']);
			}
			
			//if ($options['readable']) {
			//	$render = ($this->func->check_readable($page));
			//}
			
			if ($options['writable']) {
				$render = ($this->func->check_writable($page));
			}
			
		}
		
		if ($render) {
			$body = $this->func->convert_html_multiline($body);
			return $body;
		} else {
			return '';
		}
	}
}

