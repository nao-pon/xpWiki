<?php

class xpwiki_plugin_snsref extends xpwiki_plugin {
	
	private $usage, $sns, $options, $width, $fetcherr;
	
	public function plugin_snsref_init() {
		$this->usage = 'Usage: #snsref(SNS post URL) or &#38;snsref(SNS post URL);';
		
		$this->sns = array(
				'twitter'   => '#^https?://twitter\.com/[a-z0-9_-]+/status/([0-9]+)#i',
				'google'    => '#^(https?://plus\.google\.com/(?:[^/]+/)*?([0-9]+)/posts/([a-z0-9]+))#i',
				'facebook'  => '#^(https?://www\.facebook\.com/(?:([a-z0-9._-]+)/posts/|photo.php\?fbid=|video.php\?v=)([0-9]+))#i',
				'instagram' => '#^https?://instagram\.com/p/([a-z0-9]+)#i',
				'vine'      => '#^https?://vine\.co/v/([a-z0-9]+)#i',
		);
		
		$this->options = array(
			'media'   => 1,     // for twitter
			'thread'  => 1,     // for twitter
			'lang'    => 'ja',  // for twitter
			'width'   => 466,   // for Facebook, Instagram, Twitter and Vine. Google+ is not effective.
			'caption' => 1,     // for instagram
			'audio'   => 0,     // for vine
			'related' => 1,     // for vine
			'simple'  => false, // for vine
		);
		
		$this->minwidth = 300;
		
		$this->maxwidth = 1024;
	}

	public function plugin_snsref_convert() {
		$args = func_get_args();
		if ($args) {
			$this->width = 0;
			$html = $this->getHtml($args);
			$width = ($this->width)? 'width:'.$this->width.'px' : '';
			return '<div style="'.$width.'" class="plugin_snsref">' . "\n" . $html . "\n" . '</div>';
		} else {
			return $this->usage;
		}
	}
	
	public function plugin_snsref_inline() {
		$args = func_get_args();
		$body = array_pop($args);
		if ($args) {
			$this->width = 0;
			$html = $this->getHtml($args);
			$width = ($this->width)? 'width:'.$this->width.'px' : '';
			return '<div style="display:inline-block;'.$width.'">' . $html . '</div>';
		} else {
			return $this->usage;
		}
	}
	
	/////////////////////
	// Pprivate functions

	private function getHtml($args) {
		$options = $this->options;
		$this->fetch_options($options, $args, array('url'));
		$options['width'] = max(min(intval($options['width']), $this->maxwidth), $this->minwidth);
		
		$url = $options['url'];
		foreach($this->sns as $host => $regex) {
			if (preg_match($regex, $url, $m)) {
				 return '<!--NA-->'.$this->fetch($host, $options, $m).'<!--/NA-->';
			}
		}
		
		return 'Bad URL: '.$this->func->htmlspecialchars($url);
	}
	
	private function fetch($host, $options, $m) {
		switch ($host) {
			case 'twitter':
				return $this->fetch_twitter($options, $m);
			case 'google':
				return $this->fetch_google($options, $m);
			case 'facebook':
				return $this->fetch_facebook($options, $m);
			case 'instagram':
				$this->width = $options['width'];
				return $this->fetch_instagram($options, $m);
			case 'vine':
				return $this->fetch_vine($options, $m);
			default:
				$method = 'fetch_' . $host;
				if (method_exists($this, $method)) {
					return $this->$method($options, $m);
				} else {
					return 'Bad URL: '.$this->func->htmlspecialchars($url);
				}
		}
	}

	private function apiRequest($apiurl, $target = 'html') {
		$html = null;
		$this->fetcherr = false;
		$res = $this->func->http_request($apiurl);
		if ($res['rc'] === 200) {
			if ($res['data'] && $decd = json_decode($res['data'], true)) {
				if (is_array($decd) && isset($decd[$target])) {
					$html = mb_convert_encoding($decd[$target], $this->cont['SOURCE_ENCODING'], 'UTF-8');
					$ttl = 2592000; // 30days
				}
			}
		}
		if (is_null($html)) {
			$this->fetcherr = true;
			if ($res['rc'] === 404) {
				// Not found
				$html = 'Target post was not found: ';
				$ttl = 3600;
			} else {
				// Network error
				$html = 'Network error, Try after 10 minute: ';
				$ttl = 60;
				$this->root->rtf['disable_render_cache'] = true;
			}
		}
		return array($html, $ttl);
	}
	
	private function fetch_instagram($options, $m) {
	
		$url = $options['url'];
		$hidecaption = ($options['caption'] && $options['caption'] !== 'none')? '0' : '1';
	
		$id = $m[1];
		$ckey = 'i:' . $id . ':' . $hidecaption;
		$html = '';
	
		if ($html = $this->func->cache_get_db($ckey, 'snsref')) {
			return $html;
		}
	
		$html = $url;
		$ttl = 60;
		$apiurl = 'https://api.instagram.com/oembed/?beta=1&url=http://instagram.com/p/'.$id.'/&hidecaption='.$hidecaption;
	
		list($html, $ttl) = $this->apiRequest($apiurl);
		if ($this->fetcherr) {
			$html .= $this->func->htmlspecialchars($url);
		}
		
		$this->func->cache_save_db($html, 'snsref', $ttl, $ckey);
		return $html;
	}
	
	private function fetch_twitter($options, $m) {
		
		$url = $options['url'];
		$media = ($options['media'] && $options['media'] !== 'none')? '1' : '0';
		$thread = ($options['thread'] && $options['thread'] !== 'none')? '1' : '0';
		$lang = preg_replace('/[^a-zA-Z0-9._-]/', '', $options['lang']);
		
		$id = $m[1];
		$ckey = 't:' . $id . ':' . $media . $thread . $lang;
		$html = '';
		
		if ($html = $this->func->cache_get_db($ckey, 'snsref')) {
			$this->twitterSetWidth($html, $options);
			return $html;
		}
		
		$html = $url;
		$ttl = 60;
		$media = $media? 'false' : 'true';
		$thread = $thread? 'false' : 'true';
		$apiurl = 'https://api.twitter.com/1/statuses/oembed.json?hide_media='.$media.'&hide_thread='.$thread.'&lang='.$lang.'&id='.$id;
		
		list($html, $ttl) = $this->apiRequest($apiurl);
		if ($this->fetcherr) {
			$html .= $this->func->htmlspecialchars($url);
		}
				
		$this->func->cache_save_db($html, 'snsref', $ttl, $ckey);
		
		$this->twitterSetWidth($html, $options);
		return $html;
	}
	
	private function twitterSetWidth(&$html, $options) {
		$html = str_replace('<blockquote', '<blockquote width="'.$options['width'].'"', $html);
	}
	
	private function fetch_google($options, $m) {
		$this->func->add_js_head('//apis.google.com/js/plusone.js');
		return '<div class="g-post" data-href="https://plus.google.com/'.$this->func->htmlspecialchars($m[2]).'/posts/'.$this->func->htmlspecialchars($m[3]).'">Google+:<a href="'.$this->func->htmlspecialchars($m[1]).'">'.$this->func->htmlspecialchars($m[2].'-'.$m[3]).'</a></div>';
	}
	
	private function fetch_facebook($options, $m) {
		return '<div id="fb-root"></div> <script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/ja_JP/all.js#xfbml=1"; fjs.parentNode.insertBefore(js, fjs); }(document, \'script\', \'facebook-jssdk\'));</script>
<div class="fb-post" data-href="'.$this->func->htmlspecialchars($m[1]).'" data-width="'.$options['width'].'"><div class="fb-xfbml-parse-ignore">Facebook: <a href="'.$this->func->htmlspecialchars($m[1]).'">'.$this->func->htmlspecialchars($m[2].'-'.$m[3]).'</a></div></div>';
	}
	
	private function fetch_vine($options, $m) {
		$audio = ($options['audio'] && $options['audio'] !== 'none')? '1' : '0';
		$related = ($options['related'] && $options['related'] !== 'none')? '1' : '0';
		$type = ($options['simple'])? 'simple' : 'postcard';
		return '<iframe class="vine-embed" src="https://vine.co/v/'.$m[1].'/embed/'.$type.'?audio='.$audio.'&amp;related='.$related.'" width="'.$options['width'].'" height="'.$options['width'].'" frameborder="0"></iframe><script async src="//platform.vine.co/static/scripts/embed.js" charset="utf-8"></script>';
	}
}