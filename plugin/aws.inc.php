<?php
/*
 * Created on 2008/02/28 by nao-pon http://hypweb.net/
 * $Id: aws.inc.php,v 1.8 2009/06/26 00:24:20 nao-pon Exp $
 */

/////////////////////////////////////////////////
// #aws([Template name],[Search Index],[Keyword],[Node Number],[Sort Mode])

class xpwiki_plugin_aws extends xpwiki_plugin {

	var $options_default = array();
	
	function plugin_aws_init() {
		//////// Config ///////
		$this->config['AccessKeyId']     = '';
		$this->config['SecretAccessKey'] = '';
		$this->config['amazon_t']        = '';   // Associates ID
		$this->config['cache_time']      = 1440; // Cache time (min) 1440min = 24h
		$this->config['template_map']    = array(
			// Template mapping
			'From name' => 'To name',
		);

		$this->options_default = array(
			'search'    => 'keywords',
			'timestamp' => FALSE,
			'makepage'  => FALSE,
			'maxdepth'  => 3,
		);

	}

	function plugin_aws_convert() {

		if (HypCommonFunc::get_version() < 20080224) {
			return '#aws require "HypCommonFunc" >= Ver. 20080224';
		}
		
		if (! empty($this->root->vars['page']) && preg_match('/template/i', $this->root->vars['page'])) {
			return FALSE;
		}
		
		$this->load_language();

		if (! $this->options_default) {
			$this->options_default = array(
				'search'    => 'keywords',
				'timestamp' => FALSE,
				'makepage'  => FALSE,
				'maxdepth'  => 3,
			);
		}
		$this->options = $this->options_default;

		$args = array_pad(func_get_args(), 6, '');
		$f = array_shift($args);
		$m = array_shift($args);
		$k = array_shift($args);
		$b = intval(array_shift($args));
		$s = array_shift($args);
		$noheader = array_shift($args);
		
		$this->fetch_options($this->options, $args);
	
		list($more_link, $ret) = $this->plugin_aws_get($f, $m, $k, $b, $s);
		
		$style = ' style="word-break:break-all;"';
		$more = '';
		if ($more_link) {
			$noheader  = intval($noheader);
			if ($noheader > 2 && $noheader < 6) {
				$more = '<h'.$noheader.'>' . $more_link . '</h'.$noheader.'>';
			} else {
				$more = ($noheader) ? '<h4>' . $more_link . '</h4>' : '';
			}
		}
		
		return $more . '<div' . $style . '>' . $ret . '</div>';
	}
	
	function plugin_aws_get($f, $m, $k, $b, $s) {

		$ret = '';
		
		if (!$f) $f = 'default';
		if (!empty($this->config['template_map'])) {
			if (array_key_exists($f, $this->config['template_map'])) {
				$f = $this->config['template_map'][$f];
			}
		}
		
		if ($this->options['timestamp'] && ! empty($this->root->vars['page'])) {
			$this->options['page'] = $this->root->vars['page'];
		}
		$cache_file = $this->cont['CACHE_DIR'] . 'plugin/' . md5($f.$m.$k.$b.$s.serialize($this->options)).".aws";
		
		if (! empty($this->root->rtf['preview'])) {
			@ unlink($cache_file);
		}
		
		if (is_readable($cache_file) && filemtime($cache_file) + $this->config['cache_time'] * 60 > $this->cont['UTC']) {
			$ret = file_get_contents($cache_file);
		} else {
			include_once $this->cont['TRUST_PATH'] . 'class/hyp_common/hsamazon/hyp_simple_amazon.php';
			$ama = new HypSimpleAmazon($this->config['amazon_t']);
			if ($this->config['AccessKeyId']) $ama->AccessKeyId = $this->config['AccessKeyId'];
			if ($this->config['SecretAccessKey']) $ama->SecretAccessKey = $this->config['SecretAccessKey'];
			$ama->encoding = ($this->cont['SOURCE_ENCODING'] === 'EUC-JP')? 'EUCJP-win' : $this->cont['SOURCE_ENCODING'];
			
			$options = array();
			if ($s && preg_match("/\+?([a-z-]+)/", $s, $s_val))
			{
				$options['Sort'] = $s_val[1];
			}
	
			if ($k) {
				if ($m) $ama->setSearchIndex($m, $this->options['search']);
				if ($b) $options['BrowseNode'] = $b;
				$ama->itemSearch($k, $options);
			} else if ($b) {
				if ($m) $ama->setSearchIndex($m);
				$ama->browseNodeSearch($b, $options);
			}
			
			$html = $ama->getHTML($f);
			$header = ($k && ! $ama->error && $ama->compactArray['totalresults'] > 1)? $ama->makeSearchLink($k, sprintf($this->msg['more_search'], htmlspecialchars($k)), TRUE) : '';
			$ret = $header . "\x08" . $html;
			
			// remove wrong characters
			$ret = mb_convert_encoding($ret, $this->cont['SOURCE_ENCODING'], $this->cont['SOURCE_ENCODING']);
			
			if (! $ama->error && empty($this->root->rtf['preview']) && $fp = @fopen($cache_file,"wb")) {
				fputs($fp,$ret);
				fclose($fp);
			} else {
				$ret .= $ama->url;
			}
			if ($this->options['timestamp'] && empty($this->root->rtf['preview']) && $ama->newestTime && ! empty($this->root->vars['page'])) {
				$this->func->touch_page($this->root->vars['page'], $ama->newestTime);
			}
			
			if (! $ama->error && empty($this->root->rtf['preview']) && $this->options['makepage'] && ! empty($this->root->vars['page']) && substr_count($this->root->vars['page'], '/') + 1 < $this->options['maxdepth']) {
				$wait = 0;
				$checkUTIME = $this->cont['UTC'] - 86400;
				foreach($ama->compactArray['Items'] as $item) {
					if ($checkUTIME <= $item['RELEASEUTIME'] && $this->func->basename($this->root->vars['page']) !== $item['TITLE']) {
						$newpage = $this->root->vars['page'] . '/' . $item['TITLE'];
						if (! $this->func->is_page($newpage)) {
							$data = array(
								'action' => 'plugin_func',
								'plugin' => 'makepage',
								'func' => 'auto_make',
								'args' => array(
									'new_page' => $newpage
								),
							);
							$this->func->regist_jobstack($data, 864000, $wait);
							$wait = $wait + 10;
						}
					}
				}
			}
			
			$ama = NULL;

			// Update plainDB
			$this->func->need_update_plaindb();
		}
		return explode("\x08", $ret, 2);
	}
}
?>