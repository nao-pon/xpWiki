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
 * 2007-12-01 2.3.0 詳細はgmap.inc.php
 */

class xpwiki_plugin_gmap_mark extends xpwiki_plugin {
	var $marker_count;

	function plugin_gmap_mark_init () {

		// 言語ファイルの読み込み
		$this->load_language();

		$this->cont['PLUGIN_GMAP_MK_DEF_TITLE'] = $this->msg['nontitle']; //マーカーの名前
		$this->cont['PLUGIN_GMAP_MK_DEF_CAPTION'] =  '';         //マーカーのキャプション
		$this->cont['PLUGIN_GMAP_MK_DEF_NOLIST'] =  false;       //マーカーのリストを出力しない
		$this->cont['PLUGIN_GMAP_MK_DEF_NOINFOWINDOW'] =  false; //マーカーのinfoWindowを表示しない
		$this->cont['PLUGIN_GMAP_MK_DEF_ZOOM'] =  null;          //マーカーの初期zoom値。nullは初期値無し。
		$this->cont['PLUGIN_GMAP_MK_DEF_MINZOOM'] =   0;         //マーカーが表示される最小ズームレベル
		$this->cont['PLUGIN_GMAP_MK_DEF_MAXZOOM'] =  21;         //マーカーが表示される最大ズームレベル
		$this->cont['PLUGIN_GMAP_MK_DEF_ICON'] =  '';        //アイコン。空の時はデフォルト
		$this->cont['PLUGIN_GMAP_MK_DEF_FLAT'] = false;         //アイコンを影なしにする。
		$this->cont['PLUGIN_GMAP_MK_DEF_NOICON'] =  false;   //アイコンを表示しない。
		$this->cont['PLUGIN_GMAP_MK_DEF_AJUMP'] = $this->msg['back2list']; //infoWindowから本文中へのリンク文字
		$this->cont['PLUGIN_GMAP_MK_DEF_TITLEISPAGENAME'] =  false; //title省略時にページ名を使う。

		//FORMATLISTはhtmlに出力されるマーカーのリストの雛型
		//FMTINFOはマップ上のマーカーをクリックして表示されるフキダシの（中の）雛型
		$this->cont['PLUGIN_GMAP_MK_DEF_FORMATLIST'] =  '<b>%title%</b><p>%caption%</p>';
		$this->cont['PLUGIN_GMAP_MK_DEF_FORMATINFO'] =  '<b>%title%</b><br/><div style=\'width:215px;\'><span style=\'float:left; padding-right: 3px; padding-bottom: 3px;\'>%image%</span>%caption%</div>';

		//リストをクリックするとマップにフォーカスさせる。(0 or 1)
		$this->cont['PLUGIN_GMAP_MK_DEF_ALINK'] =  1;

		// This Plugin's Config
		$this->conf['IMG_THUMB_MAX_WIDTH'] = '100'; // 画像サムネイルの最大幅
		$this->conf['IMG_THUMB_MAX_HEIGHT'] = '100'; // 画像サムネイルの最大高さ


	}

	function plugin_gmap_mark_get_default () {
	//	global $vars, $script;

		return array (
			'title'        => $this->cont['PLUGIN_GMAP_MK_DEF_TITLE'],
			'caption'      => $this->cont['PLUGIN_GMAP_MK_DEF_CAPTION'],
			'image'        => '',
			'icon'         => $this->cont['PLUGIN_GMAP_MK_DEF_ICON'],
			'nolist'       => $this->cont['PLUGIN_GMAP_MK_DEF_NOLIST'],
			'noinfowindow' => $this->cont['PLUGIN_GMAP_MK_DEF_NOINFOWINDOW'],
			'noicon'       => $this->cont['PLUGIN_GMAP_MK_DEF_NOICON'],
			'flat'    	   => $this->cont['PLUGIN_GMAP_MK_DEF_FLAT'],
			'zoom'         => $this->cont['PLUGIN_GMAP_MK_DEF_ZOOM'],
			'maxzoom'      => $this->cont['PLUGIN_GMAP_MK_DEF_MAXZOOM'],
			'minzoom'      => $this->cont['PLUGIN_GMAP_MK_DEF_MINZOOM'],
			'map'          => $this->cont['PLUGIN_GMAP_DEF_MAPNAME'],
			'formatlist'   => $this->cont['PLUGIN_GMAP_MK_DEF_FORMATLIST'],
			'formatinfo'   => $this->cont['PLUGIN_GMAP_MK_DEF_FORMATINFO'],
			'alink'        => $this->cont['PLUGIN_GMAP_MK_DEF_ALINK'],
	        'titleispagename' => $this->cont['PLUGIN_GMAP_MK_DEF_TITLEISPAGENAME'],
	        'type'         => '',
	        'street'       => '',
		);
	}

	function plugin_gmap_mark_convert() {
		$args = func_get_args();
		if (sizeof($args)<2) {
			return $this->wrap_plugin_error('error: plugin gmap_mark wrong args')."\n";
		}
		return $this->plugin_gmap_mark_output($args[0], $args[1], array_slice($args, 2));
	}

	function plugin_gmap_mark_inline() {
		if (isset($this->root->rtf['GET_HEADING_INIT'])) return 'Google Maps';
		$args = func_get_args();
		$caption = array_pop($args);
		if ($caption) {
			$args[] = 'caption2=' . $caption;
		}
		if (sizeof($args)<2) {
			return $this->wrap_plugin_error('error: plugin gmap_mark wrong args')."\n";
		}
		return $this->plugin_gmap_mark_output($args[0], $args[1], array_slice($args, 2));
	}

	function plugin_gmap_mark_output($lat, $lng, $params) {
		if (empty($this->conf['IMG_THUMB_MAX_WIDTH'])) {
			$this->conf['IMG_THUMB_MAX_WIDTH'] = '120';
		}
		if (empty($this->conf['IMG_THUMB_MAX_HEIGHT'])) {
			$this->conf['IMG_THUMB_MAX_HEIGHT'] = '120';
		}

		$p_gmap =& $this->func->get_plugin_instance('gmap');

		if (! isset($this->root->rtf['PUSH_PAGE_CHANGES']) && $p_gmap->plugin_gmap_is_supported_profile() && !$p_gmap->lastmap_name) {
			return $this->wrap_plugin_error("gmap_mark: {$p_gmap->msg['err_need_gmap']}");
		}

		$defoptions = $this->plugin_gmap_mark_get_default();

		$inoptions = array();
		$isSetZoom = false;
		$inoptions['caption'] = '';
		foreach ($params as $param) {
			list($index, $value) = array_pad(split('=', $param, 2), 2, '');
			$index = trim($index);
			if ($index !== 'caption2') $value = htmlspecialchars(trim($value), ENT_QUOTES);
			$inoptions[$index] = $value;
			if ($index == 'zoom') {$isSetZoom = true;}//for old api
		}
		if (isset($inoptions['caption2'])) {
			$inoptions['caption'] .=  $inoptions['caption2'];
		}

		if (array_key_exists('define', $inoptions)) {
			$this->root->vars['gmap_mark'][$inoptions['define']] = $inoptions;
			return "";
		}

		$coptions = array();
		if (array_key_exists('class', $inoptions)) {
			$class = $inoptions['class'];
			if (array_key_exists($class, $this->root->vars['gmap_mark'])) {
				$coptions = $this->root->vars['gmap_mark'][$class];
			}
	    }

		$options = array_merge($defoptions, $coptions, $inoptions);
		$lat = trim($lat);
		$lng = trim($lng);
		$title        = $options['title'];
		$caption      = $options['caption'];
		$image        = $options['image'];
		$icon         = $options['icon'];
		$nolist       = $p_gmap->plugin_gmap_getbool($options['nolist']);
		$noinfowindow = $p_gmap->plugin_gmap_getbool($options['noinfowindow']);
		$noicon       = $p_gmap->plugin_gmap_getbool($options['noicon']);
		$flat         = $p_gmap->plugin_gmap_getbool($options['flat']);
		$zoom         = $options['zoom'];
		$maxzoom      = (int)$options['maxzoom'];
		$minzoom      = (int)$options['minzoom'];
		$type         = $options['type'];
		$street       = $options['street'];
		if ($options['map'] === $this->cont['PLUGIN_GMAP_DEF_MAPNAME']) {
			$map      = $p_gmap->lastmap_name;
		} else {
			$map      = $p_gmap->plugin_gmap_addprefix($this->root->vars['page'], $options['map']);
		}
		//XSS対策のため次の二つのオプションはユーザーから設定不可にする。
		$formatlist   = $defoptions['formatlist'];
		$formatinfo   = $defoptions['formatinfo'];
		$alink        = $options['alink'];
		$titleispagename = $p_gmap->plugin_gmap_getbool($options['titleispagename']);
		$api = isset($this->root->vars['gmap_info'][$map]['api'])? $this->root->vars['gmap_info'][$map]['api'] : 2;

		$page = $p_gmap->get_pgid($this->root->vars['page']);

		if (isset($this->marker_count[$this->root->mydirname][$page][$map])) {
			$this->marker_count[$this->root->mydirname][$page][$map]++;
		} else {
			$this->marker_count[$this->root->mydirname][$page][$map] = 1;
		}

		if ($nolist) {
			$alink = false;
		}

	    if ($titleispagename) {
	        $title = htmlspecialchars($this->root->vars['page'], ENT_QUOTES);
	    }

		//携帯デバイス用リスト出力
		if (!$p_gmap->plugin_gmap_is_supported_profile()) {
			if ($nolist == false) {
				$markers = $lat . ',' . $lng;
				if (!empty($this->root->replaces_finish['__GOOGLE_MAPS_STATIC_PARAMS_' . $map]) && $this->root->replaces_finish['__GOOGLE_MAPS_STATIC_MARKERS_' . $map]) {
					$this->root->replaces_finish['__GOOGLE_MAPS_STATIC_PARAMS_' . $map] = '';
				}
				$this->root->replaces_finish['__GOOGLE_MAPS_STATIC_MARKERS_' . $map] .= $markers . '|';
				$imgurl = $p_gmap->get_static_image_url($lat, $lng, $zoom, $markers, 1, $title);
				$title = '<a href="'.$imgurl.'">'.$title.' [Map]</a>';
				return $this->plugin_googlemaps_mark_simple_format_listhtml(
					$formatlist, $title, $caption);
			}
			return '';
		}

		if ($api < 2 && $isSetZoom) $zoom = 19 - $zoom;
		if ($zoom == null) $zoom = 'null';

		if ($noicon == true) {
			$noinfowindow = true;
		}

		//Pukiwikiの添付された画像の表示
		$q = '/^http:\/\/.*(\.jpg|\.gif|\.png)$/i';
		if ($image != '' && !preg_match($q, $image)) {
			$ref =& $this->func->get_plugin_instance('ref');
			$params = $ref->get_body(array($image, 'mw:' . $this->conf['IMG_THUMB_MAX_WIDTH'], 'mh:' . $this->conf['IMG_THUMB_MAX_HEIGHT']), true);
			$image = $params['_body'];
			if ($this->root->ref_use_lightbox) {
				$image = str_replace('<a', '<a rel="lightbox[stack]" onclick="myLightbox.start(this);return false;"', $image);
			}
		}
		if ($noinfowindow == false) {
			$infohtml = $this->plugin_googlemaps_mark_format_infohtml(
				$page, $map, $formatinfo, $alink,
			$title, $caption, $image);
		} else {
			$infohtml = null;
		}

		$key = "$map,$lat,$lng";

		if ($nolist == false) {
			$listhtml = $this->plugin_googlemaps_mark_format_listhtml(
				$page, $map, $formatlist, $alink,
			$key, $infohtml,
			$title, $caption, $image,
			$zoom);
		}
		if ($noicon == true) {
			$noiconstrval = "true";
		} else {
			$noiconstrval = "false";
		}
		
		if ($icon != "" && $flat == true) {
			$flat = "true";
		} else {
			$flat = "false";
		}
		
		if ($type) {
			$type = 'google.maps.MapTypeId.' . $p_gmap->plugin_gmap_get_maptype($type);
		} else {
			$type = 'null';
		}

		$street_opt = '';
		if ($street) {
			$_keys = array('lat', 'lng', 'heading', 'pitch', 'zoom');
			$_parts = explode(':', $street);

			$_vals = array();
					for($_i = 0; isset($_parts[$_i]); $_i++) {
				if (in_array($_parts[$_i], $_keys)) {
					$_val = $_parts[$_i + 1];
					if (is_numeric($_val)) {
						$_vals[] = $_parts[$_i++].':'.$_val;
					}
				}
			}
			if ($_vals) {
				$street = '{' . join(',', $_vals) . '}';
				$street_opt = <<<EOD

if (!!googlemaps_maps['$page']['$map']._streetView) {
	googlemaps_markers['$page']['$map']['$key'].setStreet($street);
}
EOD;
			}
		}
		
		// Create Marker
		$output = <<<EOD
<script type="text/javascript">
//<![CDATA[
onloadfunc2.push(function() {
	p_gmap_regist_marker ('$page', '$map', PGTool.getLatLng($lat , $lng), '$key',
	{noicon: $noiconstrval, icon:'$icon', flat: $flat, zoom:$zoom, maxzoom:$maxzoom, minzoom:$minzoom, title:'$title', infohtml:'$infohtml', type:$type});{$street_opt}
});
//]]>
</script>\n
EOD;

		//Show Markers List
		if ($nolist == false) {
			$output .= $listhtml;
		}

		return $output;
	}

	function plugin_googlemaps_mark_simple_format_listhtml($format, $title, $caption) {
		$html = $format;
		$html = str_replace('%title%', $title, $html);
		$html = str_replace('%caption%', $caption, $html);
		$html = str_replace('%image%', '', $html);
		return $html;
	}

	function plugin_googlemaps_mark_format_listhtml($page, $map, $format, $alink,
	$key, $infohtml, $title, $caption, $image, $zoomstr) {

		if ($alink == true) {
			$atag = "<a id=\"".$map."_mark".$this->marker_count[$this->root->mydirname][$page][$map]."\"></a>";
			$atag .= "<a href=\"#$map\"";
		}

		$atag .= " onclick=\"googlemaps_markers['$page']['$map']['$key'].onclick();\">%title%</a>";

		$html = $format;
		if ($alink == true) {
			$html = str_replace('%title%', $atag , $html);
		}
		$html = str_replace('%title%', $title, $html);
		$html = str_replace('%caption%', $caption, $html);
		$html = str_replace('%image%', $image, $html);
		return $html;
	}

	function plugin_googlemaps_mark_format_infohtml($page, $map, $format, $alink, $title, $caption, $image) {

		$html = str_replace('\'', '\\\'', $format);
		if ($alink == true) {
			$atag = "%title% <a href=\\'#".$map."_mark".$this->marker_count[$this->root->mydirname][$page][$map]."\\'>"
			.$this->cont['PLUGIN_GMAP_MK_DEF_AJUMP'].'</a>';
			$html = str_replace('%title%', $atag , $html);
		}
		$html = str_replace('%title%',$title , $html);
		$html = str_replace('%caption%', $caption, $html);

		if ($image != '') {
			$image = str_replace("'", "\\'", $image);
		}
		$html = str_replace('%image%', $image, $html);

		return $html;
	}
}
?>