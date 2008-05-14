<?php
/*
 * Created on 2008/02/28 by nao-pon http://hypweb.net/
 * $Id: aws.inc.php,v 1.4 2008/05/14 04:28:41 nao-pon Exp $
 */

/////////////////////////////////////////////////
// #aws([Template name],[Search Index],[Keyword],[Node Number],[Sort Mode])

class xpwiki_plugin_aws extends xpwiki_plugin {

	function plugin_aws_init() {
		//////// Config ///////
		$this->config['amazon_t']     = '';   // Associates ID
		$this->config['cache_time']   = 1440; // Cache time (min) 1440min = 24h
		$this->config['template_map'] = array(
			// Template mapping
			'From name' => 'To name',
		);
	}

	function plugin_aws_convert() {

		if (HypCommonFunc::get_version() < 20080224) {
			return '#aws require "HypCommonFunc" >= Ver. 20080224';
		}

		$this->load_language();

		list($f, $m, $k, $b, $s, $noheader) = array_pad(func_get_args(), 6, '');
	
		list($more_link, $ret) = $this->plugin_aws_get($f, $m, $k, $b, $s);
		
		$style = ' style="word-break:break-all;"';
		$more = '';
		if ($k) {
			$more = (!$noheader) ? '<h4>' . $more_link . '</h4>' : '';
		}
		return $more . '<div' . $style . '>' . $ret . '</div>';
	}
	
	function plugin_aws_get($f, $m, $k, $b, $s) {

		$ret = '';
		
		if (!empty($this->config['template_map'])) {
			if (array_key_exists($f, $this->config['template_map'])) {
				$f = $this->config['template_map'][$f];
			}
		}
		
		$cache_file = $this->cont['CACHE_DIR'] . 'plugin/' . md5($f.$m.$k.$b.$s).".aws";
		
		if (is_readable($cache_file) && filemtime($cache_file) + $this->config['cache_time'] * 60 > $this->cont['UTC']) {
			$ret = file_get_contents($cache_file);
		} else {
			include_once $this->cont['TRUST_PATH'] . 'class/hyp_common/hsamazon/hyp_simple_amazon.php';
			$ama = new HypSimpleAmazon($this->config['amazon_t']);
			$ama->encoding = $this->cont['SOURCE_ENCODING'];
	
			$options = array();
			if ($s && preg_match("/(\+(titlerank|daterank))/", $s, $s_val))
			{
				$options['Sort'] = $s_val[1];
			}
	
			if ($k) {
				if ($m) $ama->setSearchIndex($m);
				$ama->itemSearch($k, $options);
			} else if ($b) {
				if ($m) $ama->setSearchIndex($m);
				$ama->browseNodeSearch($b, $options);
			}
			
			if ($k) $ret = $ama->makeSearchLink($k, sprintf($this->msg['more_search'], htmlspecialchars($k)), TRUE);
			$ret .= "\x08" . $ama->getHTML($f);
			$ama = NULL;
			
			if ($fp = @fopen($cache_file,"wb")) {
				fputs($fp,$ret);
				fclose($fp);
			}

			// Update plainDB
			$this->func->need_update_plaindb();
		}
		return explode("\x08", $ret, 2);
	}
}
?>