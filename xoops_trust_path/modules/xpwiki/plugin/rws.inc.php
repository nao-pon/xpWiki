<?php
/*
 * Created on 2012/05/26 by nao-pon http://hypweb.net/
 */

/////////////////////////////////////////////////
// #rws(Template name,shopCode,Keyword,genreId,Sort Mode,header level[,hits:(1-30)][,page:(1-100)][,minPrice:(int)][,maxPrice:(int)][,field:(0|1)][,orFlag:(0|1)])

class xpwiki_plugin_rws extends xpwiki_plugin {

	var $options_default = array();

	function plugin_rws_init() {
		//////// Config ///////
		$this->config['developerId']     = '';
		$this->config['affiliateId']     = '';
		$this->config['cache_time']      = 1440; // Cache time (min) 1440min = 24h
		$this->config['template_map']    = array(
			// Template mapping
			//'From name' => 'To name',
			'default' => 'default.rakuten',
		);

		$this->options_default = array(
				'hits'      => false,
				'page'      => false,
				'minPrice'  => false,
				'maxPrice'  => false,
				'field'     => false,
				'orFlag'    => false
		);

	}

	function plugin_rws_action() {
		if (isset($this->root->vars['pcmd']) && $this->root->vars['pcmd'] === 'gc') {
			$this->gc();
		}
	}

	function plugin_rws_convert() {

		if (HypCommonFunc::get_version() < 20120528) {
			return '#rws require "HypCommonFunc" >= Ver. 20120528';
		}

		if (! empty($this->root->vars['page']) && preg_match('/template/i', $this->root->vars['page'])) {
			return FALSE;
		}

		$this->root->rtf['disable_render_cache'] = true;

		$this->load_language();

		if (! $this->options_default) {
			$this->options_default = array(
				'hits'      => false,
				'page'      => false,
				'minPrice'  => false,
				'maxPrice'  => false,
				'field'     => false,
				'orFlag'    => false
			);
		} else {
			// for compat
			if (! isset($this->options_default['pages'])) {
				$this->options_default['pages'] = 1;
			}
			if (! isset($this->options_default['start'])) {
				$this->options_default['start'] = 1;
			}
		}
		$this->options = $this->options_default;

		$args = array_pad(func_get_args(), 6, '');
		$f = trim(array_shift($args));
		$m = trim(array_shift($args)); // shopCode
		$k = trim(array_shift($args)); // keyword
		$b = intval(array_shift($args)); // genreId
		$s = trim(array_shift($args)); // sort
		$header = trim(array_shift($args));
		if ($header === '') {
			$header = 1;
		}

		if (!$m && !$k && !$b) return FALSE;

		$this->fetch_options($this->options, $args);

		list($more_link, $ret) = $this->plugin_rws_get($f, $m, $k, $b, $s);

		$style = ' style="word-break:break-all;"';
		$more = '';
		if ($more_link) {
			$header  = intval($header);
			if ($header > 2 && $header < 6) {
				$more = '<h'.$header.'>' . $more_link . '</h'.$header.'>';
			} else {
				$more = ($header) ? '<h4>' . $more_link . '</h4>' : '';
			}
		}
		return $this->gc(true) . $more . '<div' . $style . '>' . $ret . '</div>';
	}

	function plugin_rws_get($f, $m, $k, $b, $s) {

		$ret = '';

		if (!$f) $f = 'default';
		if (!empty($this->config['template_map'])) {
			if (array_key_exists($f, $this->config['template_map'])) {
				$f = $this->config['template_map'][$f];
			}
		}

		$cache_file = $this->cont['CACHE_DIR'] . 'plugin/' . md5($f.$m.$k.$b.$s.serialize($this->options)).".rws";

		if (! empty($this->root->rtf['preview'])) {
			@ unlink($cache_file);
		}

		if (is_readable($cache_file) && filemtime($cache_file) + $this->config['cache_time'] * 60 > $this->cont['UTC']) {
			$ret = file_get_contents($cache_file);
		} else {
			include_once XOOPS_TRUST_PATH . '/class/hyp_common/hsservice/hyp_simple_rakuten.php';
			$srv = new HypSimpleRakuten($this->config['affiliateId'], $this->config['developerId']);
			$srv->encoding = ($this->cont['SOURCE_ENCODING'] === 'EUC-JP')? 'EUCJP-win' : $this->cont['SOURCE_ENCODING'];

			$options = array();
			//$options['genreInformationFlag'] = 1;
			if ($m && preg_match("/^[0-9a-z_-]+$/i", $m)) {
				$options['shopCode'] = $m;
			}
			if ($b) {
				$options['genreId'] = $b;
			}
			if ($s && preg_match("/^[+-]?(?:affiliateRate|reviewCount|reviewAverage|itemPrice|updateTimestamp)$/i", $s)) {
				if (! in_array($s[0], array('+', '-'))) {
					$s = '+' . $s;
				}
				$options['sort'] = $s;
			}
			if ($this->options['hits'] !== false) {
				$options['hits'] = max(1, min(30, intval($this->options['hits'])));
			}
			if ($this->options['page'] !== false) {
				$options['page'] = max(1, min(100, intval($this->options['page'])));
			}
			if ($this->options['minPrice'] !== false) {
				$options['minPrice'] = intval($this->options['minPrice']);
			}
			if ($this->options['maxPrice'] !== false) {
				$options['maxPrice'] = intval($this->options['maxPrice']);
			}
			if ($this->options['field'] !== false) {
				$options['field'] = $this->options['field']? 1 : 0;
			}
			if ($this->options['orFlag'] !== false) {
				$options['orFlag'] = $this->options['orFlag']? 1 : 0;
			}
			
			$srv->itemSearch($k, $options);

			$html = $srv->getHTML($f);

			$header = ($k && ! is_null($srv->compactArray['totalresults']))? $srv->makeSearchLink($k, sprintf($this->msg['more_search'], $this->func->htmlspecialchars($k)), TRUE) : '';
			$ret = $header . "\x08" . $html;

			// remove wrong characters
			$ret = mb_convert_encoding($ret, $this->cont['SOURCE_ENCODING'], $this->cont['SOURCE_ENCODING']);

			if (! is_null($srv->compactArray['totalresults']) && empty($this->root->rtf['preview']) && $fp = @fopen($cache_file,"wb")) {
				fputs($fp,$ret);
				fclose($fp);
			} else {
				//$ret .= $srv->url;
			}

			$srv = NULL;

			if (empty($this->root->rtf['preview'])) {
				// Update plainDB
				$this->func->need_update_plaindb();
				// After a day
				$this->func->need_update_plaindb($this->root->vars['page'], 'update', TRUE, TRUE, $this->config['cache_time'] * 60);
			}
		}
		return explode("\x08", $ret, 2);
	}

	function gc($get_tag = FALSE) {
		$dir = $this->cont['CACHE_DIR'] . 'plugin';
		$gc = $this->cont['CACHE_DIR'] . 'plugin/rws.gc';
		$interval = $this->config['cache_time'] * 60;
		if (! is_file($gc) || filemtime($gc) < $this->cont['UTC'] - $interval) {
			if ($get_tag) {
				return '<div style="float:left;"><img src="' . $this->root->script . '?plugin=rws&amp;pcmd=gc" width="1" height="1" alt="" /></div>' . "\n";
			}
			touch($gc);
			$attr = '.rws';
			$attr_len = strlen($attr) * -1;
		    $ttl = $this->config['cache_time'] * 60;
		    $check = $this->cont['UTC'] - $ttl;
		    if ($dh = opendir($dir)) {
		        while (($file = readdir($dh)) !== false) {
		            if (substr($file, $attr_len) === $attr ) {
		            	$target = $dir . '/' . $file;
		            	if (filemtime($target) < $check) {
		            		unlink($target);
		            	}
		            }
		        }
		        closedir($dh);
		    }
		}
		if ($get_tag) {
			return '';
		}
		// clear output buffer
		$this->func->clear_output_buffer();
		// imgタグ呼び出し用
		header("Content-Type: image/gif");
		HypCommonFunc::readfile($this->root->mytrustdirpath . '/skin/image/gif/spacer.gif');
	}
}
?>