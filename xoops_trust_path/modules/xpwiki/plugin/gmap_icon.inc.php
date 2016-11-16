<?php
/* Pukiwiki GoogleMaps plugin 2.3
 * http://reddog.s35.xrea.com
 * -------------------------------------------------------------------
 * Copyright (c) 2005, 2006, 2007 OHTSUKA, Yoshio
 * This program is free to use, modify, extend at will. The author(s)
 * provides no warrantees, guarantees or any responsibility for usage.
 * Redistributions in any form must retain this copyright notice.
 * ohtsuka dot yoshio at gmail dot com
 * -------------------------------------------------------------------
 * 2007-12-01 2.3.0 ¾ÜºÙ¤Ïgmap.inc.php
 */

class xpwiki_plugin_gmap_icon extends xpwiki_plugin {
	function plugin_gmap_icon_init () {

		$this->cont['PLUGIN_GMAP_ICON_IMAGE'] =  '//maps.google.com/mapfiles/ms/icons/red-dot.png';
		$this->cont['PLUGIN_GMAP_ICON_SHADOW'] = '//maps.google.com/mapfiles/ms/icons/msmarker.shadow.png';
		$this->cont['PLUGIN_GMAP_ICON_IW'] =  32;
		$this->cont['PLUGIN_GMAP_ICON_IH'] =  32;
		$this->cont['PLUGIN_GMAP_ICON_SW'] =  59;
		$this->cont['PLUGIN_GMAP_ICON_SH'] =  32;
		$this->cont['PLUGIN_GMAP_ICON_IANCHORX'] =  16;
		$this->cont['PLUGIN_GMAP_ICON_IANCHORY'] =  32;
		//$this->cont['PLUGIN_GMAP_ICON_SANCHORX'] =  10;
		//$this->cont['PLUGIN_GMAP_ICON_SANCHORY'] =  0;
		$this->cont['PLUGIN_GMAP_ICON_SANCHORX'] =  null;
		$this->cont['PLUGIN_GMAP_ICON_SANCHORY'] =  null;
		//$this->cont['PLUGIN_GMAP_ICON_TRANSPARENT'] =  '//www.google.com/mapfiles/markerTransparent.png';
		//$this->cont['PLUGIN_GMAP_ICON_AREA'] =  '1 7 7 0 13 0 19 7 19 12 13 20 12 23 11 34 9 34 8 23 6 19 1 13 1 70';
		$this->cont['PLUGIN_GMAP_ICON_AREA'] =  '';

		$this->cont['PLUGIN_GMAP_ICON_REGEX'] = '#^https?://[a-z]+\.google\.com#i';
	}
	
	function plugin_gmap_icon_get_default () {
		return array(
			'image'       => $this->cont['PLUGIN_GMAP_ICON_IMAGE'],
			'shadow'      => $this->cont['PLUGIN_GMAP_ICON_SHADOW'],
			'iw'          => $this->cont['PLUGIN_GMAP_ICON_IW'],
			'ih'          => $this->cont['PLUGIN_GMAP_ICON_IH'],
			'sw'          => $this->cont['PLUGIN_GMAP_ICON_SW'],
			'sh'          => $this->cont['PLUGIN_GMAP_ICON_SH'],
			'ianchorx'    => $this->cont['PLUGIN_GMAP_ICON_IANCHORX'],
			'ianchory'    => $this->cont['PLUGIN_GMAP_ICON_IANCHORY'],
			'sanchorx'    => $this->cont['PLUGIN_GMAP_ICON_SANCHORX'],
			'sanchory'    => $this->cont['PLUGIN_GMAP_ICON_SANCHORY'],
			//'transparent' => $this->cont['PLUGIN_GMAP_ICON_TRANSPARENT'],
			'area'        => $this->cont['PLUGIN_GMAP_ICON_AREA'],
			'basepage'    => $this->root->vars['page']
		);
	}
	
	function plugin_gmap_icon_convert() {
		if (func_num_args() < 1) {
			$args = array('Default', '');
		} else {
			$args = func_get_args();
		}
		return $this->plugin_gmap_icon_output($args[0], array_slice($args, 1));
	}
	
	function plugin_gmap_icon_inline() {
		if (isset($this->root->rtf['GET_HEADING_INIT'])) return 'Google Maps';
		if (func_num_args() < 1) {
			$args = array('Default', '');
		} else {
			$args = func_get_args();
			array_pop($args);
		}
		return $this->plugin_gmap_icon_output($args[0], array_slice($args, 1));
	}
	
	function plugin_gmap_icon_output($name, $params) {
		
		$p_gmap =& $this->func->get_plugin_instance('gmap');
				
		if (! isset($this->root->rtf['PUSH_PAGE_CHANGES']) && $p_gmap->plugin_gmap_is_supported_profile() && !$p_gmap->lastmap_name) {
			return "gmap_icon: {$p_gmap->msg['err_need_gmap']}";
		}

		if (!$p_gmap->plugin_gmap_is_supported_profile()) {
			return '';
		}
	
		$defoptions = $this->plugin_gmap_icon_get_default();
		
		$inoptions = array();
		foreach ($params as $param) {
			list($index, $value) = array_pad(split('=', $param, 2), 2, '');
			$index = trim($index);
			$value = $this->func->htmlspecialchars(trim($value), ENT_QUOTES);
			$inoptions[$index] = $value;
		}
		
		if (array_key_exists('define', $inoptions)) {
			$this->root->vars['gmap_icon'][$inoptions['define']] = $inoptions;
			return "";
		}
		
		$coptions = array();
		if (array_key_exists('class', $inoptions)) {
			$class = $inoptions['class'];
			if (array_key_exists($class, $this->root->vars['gmap_icon'])) {
				$coptions = $this->root->vars['gmap_icon'][$class];
			}
		}
		$options = array_merge($defoptions, $coptions, $inoptions);
		$image       = $this->optimize_image($options['image'], $options['basepage']);
		$shadow      = $this->optimize_image($options['shadow'], $options['basepage']);
		$iw          = (integer)$options['iw'];
		$ih          = (integer)$options['ih'];
		$sw          = (integer)$options['sw'];
		$sh          = (integer)$options['sh'];
		$ianchorx    = is_numeric($options['ianchorx'])? (integer)$options['ianchorx'] : null;
		$ianchory    = is_numeric($options['ianchory'])? (integer)$options['ianchory'] : null;
		$sanchorx    = is_numeric($options['sanchorx'])? (integer)$options['sanchorx'] : null;
		$sanchory    = is_numeric($options['sanchory'])? (integer)$options['sanchory'] : null;
		//$transparent = $this->optimize_image($options['transparent'], $options['basepage']);
		$area        = $options['area'];
	
		$coords = array();
		if (isset($area)) {
			$c = substr($area, 0, 1);
			switch ($c) {
				case "'":
				case "[";
				case "{";
					$area = substr($area, 1, strlen($area)-2);
					break;
				case "&":
					if (substr($area, 0, 6) == "&quot;") {
						$area = substr($area, 6, strlen($area)-12);
					}
					break;
			}
			foreach (explode(' ', $area) as $p) {
				if (strlen($p) <= 0) continue;
				array_push($coords, $p);
			}
		}
		$coords = join($coords, ",");
		$page = $p_gmap->get_pgid($this->root->vars['page']);
	
		// Output
		//if ($image && $shadow && $transparent) {
		if ($image) {
			if ($iw > 0 && $ih > 0) {
				$iSize = "new google.maps.Size($iw, $ih)";
				$iOrigin = 'new google.maps.Point(0,0)';
			} else {
				$iSize = 
				$iOrigin = 'null';
			}
			$iAnchor = (!is_null($ianchorx) && !is_null($ianchory))? "new google.maps.Point($ianchorx, $ianchory)" : 'null';
			if ($shadow) {
				if ($sw > 0 && $sh > 0) {
					$sSize = "new google.maps.Size($sw, $sh)";
				} else {
					$sSize = 'null';
				}
				$shadow = "{url:'$shadow',size:$sSize,anchor:$iAnchor}";
			} else {
				$shadow = 'null';
			}
			$anchorPoint = (!is_null($sanchorx) && !is_null($sanchory))? "new google.maps.Point($sanchorx, $sanchory)" : 'null';
			$shape = ($area)? "{coords: [$coords], type: 'poly'}" : 'null';

			$output = <<<EOD

<script type="text/javascript">
//<![CDATA[
onloadfunc.push( function () {
	var icon = {
		image: {url:'$image',size:$iSize,anchor:$iAnchor},
		shadow: $shadow,
		anchorPoint: $anchorPoint,
		shape: $shape
	};
	icon.pukiwikiname = "$name";
	googlemaps_icons["$page"]["$name"] = icon;
});
//]]>
</script>
EOD;
			return $output;
		} else {
			return '';
		}
	}
	
	function optimize_image($image, $basepage) {
		if (strtolower(substr($image, 0, 4)) !== 'http') {
			$image = $this->func->unhtmlspecialchars($image, ENT_QUOTES);
			if (strpos($image, '/') !== FALSE) {
				$basepage = $this->func->page_dirname($image);
				$image = $this->func->page_basename($image);
			}
			$image = $this->cont['HOME_URL'].'gate.php?way=ref&_nodos&_noumb&page='.rawurlencode($basepage).'&src='.rawurlencode($image);
		} else {
			if ($this->cont['PLUGIN_GMAP_ICON_REGEX']) {
				if (strpos($image, $this->root->siteinfo['host']) !== 0 && !preg_match($this->cont['PLUGIN_GMAP_ICON_REGEX'], $image)) {
					$image = '';
				}
			}
		}
		return $image;
	}
}
?>