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
 * 2007-12-01 2.3.0 �ܺ٤�googlemaps2.inc.php
 */

class xpwiki_plugin_googlemaps2_mark extends xpwiki_plugin {
	var $marker_count;

	function plugin_googlemaps2_mark_init () {

		// ����ե�������ɤ߹���
		$this->load_language();

		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_TITLE'] = $this->msg['nontitle']; //�ޡ�������̾��
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_CAPTION'] =  '';         //�ޡ������Υ���ץ����
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_MAXCONTENT'] =  '';      //�᤭�Ф��������ˤ����Ȥ���ɽ������Pukiwiki�Υڡ���̾��URL
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_MAXTITLE'] =  '';        //�᤭�Ф��������ˤ����Ȥ��Υ����ȥ�
		//define ('PLUGIN_GOOGLEMAPS2_MK_DEF_MAXURL', '');          //MaxContent����̾. �ѻ�ͽ�ꡣ
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_NOLIST'] =  false;       //�ޡ������Υꥹ�Ȥ���Ϥ��ʤ�
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_NOINFOWINDOW'] =  false; //�ޡ�������infoWindow��ɽ�����ʤ�
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_ZOOM'] =  null;          //�ޡ������ν��zoom�͡�null�Ͻ����̵����
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_MINZOOM'] =   0;         //�ޡ�������ɽ�������Ǿ��������٥�
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_MAXZOOM'] =  17;         //�ޡ�������ɽ���������祺�����٥�
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_ICON'] =  '';        //�������󡣶��λ��ϥǥե����
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_NOICON'] =  false;   //���������ɽ�����ʤ���
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_AJUMP'] = $this->msg['back2list']; //infoWindow������ʸ��ؤΥ��ʸ��
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_TITLEISPAGENAME'] =  false; //title��ά���˥ڡ���̾��Ȥ���

		//FORMATLIST��html�˽��Ϥ����ޡ������Υꥹ�Ȥο���
		//FMTINFO�ϥޥå׾�Υޡ������򥯥�å�����ɽ�������ե������Ρ���Ρ˿���
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_FORMATLIST'] =  '<b>%title%</b><p>%caption% %maxcontent%</p>';
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_FORMATINFO'] =  '<b>%title%</b><br/><div style=\'width:215px;\'><span style=\'float:left; padding-right: 3px; padding-bottom: 3px;\'>%image%</span>%caption%</div>';

		//�ꥹ�Ȥ򥯥�å�����ȥޥåפ˥ե������������롣(0 or 1)
		$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_ALINK'] =  1;

		// This Plugin's Config
		$this->conf['IMG_THUMB_MAX_WIDTH'] = '100'; // ��������ͥ���κ�����
		$this->conf['IMG_THUMB_MAX_HEIGHT'] = '100'; // ��������ͥ���κ���⤵


	}

	function plugin_googlemaps2_mark_get_default () {
	//	global $vars, $script;

		return array (
			'title'        => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_TITLE'],
			'caption'      => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_CAPTION'],
			'maxcontent'   => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_MAXCONTENT'],
			'maxtitle'     => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_MAXTITLE'],
			'image'        => '',
			'icon'         => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_ICON'],
			'nolist'       => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_NOLIST'],
			'noinfowindow' => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_NOINFOWINDOW'],
			'noicon'       => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_NOICON'],
			'zoom'         => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_ZOOM'],
			'maxzoom'      => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_MAXZOOM'],
			'minzoom'      => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_MINZOOM'],
			'map'          => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_MAPNAME'],
			'formatlist'   => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_FORMATLIST'],
			'formatinfo'   => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_FORMATINFO'],
			'alink'        => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_ALINK'],
	        'titleispagename' => $this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_TITLEISPAGENAME'],
	        'type'         => '',
		);
	}

	function plugin_googlemaps2_mark_convert() {
		$args = func_get_args();
		if (sizeof($args)<2) {
			return $this->wrap_plugin_error('error: plugin googlemaps2_mark wrong args')."\n";
		}
		return $this->plugin_googlemaps2_mark_output($args[0], $args[1], array_slice($args, 2));
	}

	function plugin_googlemaps2_mark_inline() {
		if (isset($this->root->rtf['GET_HEADING_INIT'])) return 'Google Maps';
		$args = func_get_args();
		$caption = array_pop($args);
		if ($caption) {
			$args[] = 'caption2=' . $caption;
		}
		if (sizeof($args)<2) {
			return $this->wrap_plugin_error('error: plugin googlemaps2_mark wrong args')."\n";
		}
		return $this->plugin_googlemaps2_mark_output($args[0], $args[1], array_slice($args, 2));
	}

	function plugin_googlemaps2_mark_output($lat, $lng, $params) {
		if (empty($this->conf['IMG_THUMB_MAX_WIDTH'])) {
			$this->conf['IMG_THUMB_MAX_WIDTH'] = '120';
		}
		if (empty($this->conf['IMG_THUMB_MAX_HEIGHT'])) {
			$this->conf['IMG_THUMB_MAX_HEIGHT'] = '120';
		}

		$p_googlemaps2 =& $this->func->get_plugin_instance('googlemaps2');

		if (! isset($this->root->rtf['PUSH_PAGE_CHANGES']) && $p_googlemaps2->plugin_googlemaps2_is_supported_profile() && !$p_googlemaps2->lastmap_name) {
			return $this->wrap_plugin_error("googlemaps2_mark: {$p_googlemaps2->msg['err_need_googlemap2']}");
		}

		$defoptions = $this->plugin_googlemaps2_mark_get_default();

		$inoptions = array();
		$isSetZoom = false;
		$inoptions['caption'] = '';
		foreach ($params as $param) {
			list($index, $value) = array_pad(explode('=', $param, 2), 2, '');
			$index = trim($index);
			if ($index !== 'caption2') $value = $this->func->htmlspecialchars(trim($value), ENT_QUOTES);
			$inoptions[$index] = $value;
			if ($index == 'zoom') {$isSetZoom = true;}//for old api
		}
		if (isset($inoptions['caption2'])) {
			$inoptions['caption'] .=  $inoptions['caption2'];
		}

		if (array_key_exists('define', $inoptions)) {
			$this->root->vars['googlemaps2_mark'][$inoptions['define']] = $inoptions;
			return "";
		}

		$coptions = array();
		if (array_key_exists('class', $inoptions)) {
			$class = $inoptions['class'];
			if (array_key_exists($class, $this->root->vars['googlemaps2_mark'])) {
				$coptions = $this->root->vars['googlemaps2_mark'][$class];
			}
	    }

	    // map maxurl to maxcontent if maxurl exists.
	    if (array_key_exists('maxurl', $coptions) && !array_key_exists('maxcontent', $coptions)) {
	        $coptions['maxcontent'] = $coptions['maxurl'];
	    }
	    if (array_key_exists('maxurl', $inoptions) && !array_key_exists('maxcontent', $inoptions)) {
	        $inoptions['maxcontent'] = $inoptions['maxurl'];
	    }

		$options = array_merge($defoptions, $coptions, $inoptions);
		$lat = trim($lat);
		$lng = trim($lng);
		$title        = $options['title'];
		$caption      = $options['caption'];
		$maxcontent   = $options['maxcontent'];
		$maxtitle     = $options['maxtitle'];
		$image        = $options['image'];
		$icon         = $options['icon'];
		$nolist       = $p_googlemaps2->plugin_googlemaps2_getbool($options['nolist']);
		$noinfowindow = $p_googlemaps2->plugin_googlemaps2_getbool($options['noinfowindow']);
		$noicon       = $p_googlemaps2->plugin_googlemaps2_getbool($options['noicon']);
		$zoom         = $options['zoom'];
		$maxzoom      = (int)$options['maxzoom'];
		$minzoom      = (int)$options['minzoom'];
		$type         = $options['type'];
		if ($options['map'] === $this->cont['PLUGIN_GOOGLEMAPS2_DEF_MAPNAME']) {
			$map      = $p_googlemaps2->lastmap_name;
		} else {
			$map      = $p_googlemaps2->plugin_googlemaps2_addprefix($this->root->vars['page'], $options['map']);
		}
		//XSS�к��Τ��ἡ����ĤΥ��ץ����ϥ桼�������������ԲĤˤ��롣
		$formatlist   = $defoptions['formatlist'];
		$formatinfo   = $defoptions['formatinfo'];
		$alink        = $options['alink'];
		$titleispagename = $p_googlemaps2->plugin_googlemaps2_getbool($options['titleispagename']);
		$api = isset($this->root->vars['googlemaps2_info'][$map]['api'])? $this->root->vars['googlemaps2_info'][$map]['api'] : 2;

		$page = $p_googlemaps2->get_pgid($this->root->vars['page']);

		if (isset($this->marker_count[$this->root->mydirname][$page][$map])) {
			$this->marker_count[$this->root->mydirname][$page][$map]++;
		} else {
			$this->marker_count[$this->root->mydirname][$page][$map] = 1;
		}

		if ($nolist) {
			$alink = false;
		}

		$maxcontentfull = $maxcontent;
		if ($maxcontent != '') {
			if (!preg_match('/^http:\/\/.*$/i', $maxcontent)) {
				$encurl = rawurlencode($maxcontent);
				$maxcontent = $this->func->get_script_uri();
				$maxcontentfull = $maxcontent;
				$maxcontent .= '?cmd=googlemaps2&action=showbody&page=';
				$maxcontentfull .= '?';
				$maxcontent .= $encurl;
				$maxcontentfull .= $encurl;
			}
	    }

	    if ($titleispagename) {
	        $title = $this->func->htmlspecialchars($this->root->vars['page'], ENT_QUOTES);
	    }

	    if ($maxtitle == '') {
	        $maxtitle = $title;
	    }

		//���ӥǥХ����ѥꥹ�Ƚ���
		if (!$p_googlemaps2->plugin_googlemaps2_is_supported_profile()) {
			if ($nolist == false) {
				$markers = $lat . ',' . $lng;
				if (!empty($this->root->replaces_finish['__GOOGLE_MAPS_STATIC_PARAMS_' . $map]) && $this->root->replaces_finish['__GOOGLE_MAPS_STATIC_MARKERS_' . $map]) {
					$this->root->replaces_finish['__GOOGLE_MAPS_STATIC_PARAMS_' . $map] = '';
				}
				$this->root->replaces_finish['__GOOGLE_MAPS_STATIC_MARKERS_' . $map] .= $markers . '|';
				$imgurl = $p_googlemaps2->get_static_image_url($lat, $lng, $zoom, $markers, 1);
				$title = '<a href="'.$imgurl.'">'.$title.' [Map]</a>';
				return $this->plugin_googlemaps_mark_simple_format_listhtml(
					$formatlist, $title, $caption, $maxcontentfull);
			}
			return '';
		}

		if ($api < 2 && $isSetZoom) $zoom = 19 - $zoom;
		if ($zoom == null) $zoom = 'null';

		if ($noicon == true) {
			$noinfowindow = true;
		}

		//Pukiwiki��ź�դ��줿������ɽ��
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
			$zoom, $maxcontentfull);
		}

		if ($type) {
			$type = $p_googlemaps2->plugin_googlemaps2_get_maptype($type);
		} else {
			$type = 'null';
		}

		// Create Marker
		$output = <<<EOD
<script type="text/javascript">
//<![CDATA[
onloadfunc.push(function() {
	p_googlemaps_regist_marker ('$page', '$map', PGTool.getLatLng($lat , $lng, '$api'), '$key',
	{noicon: '$noicon', icon:'$icon', zoom:$zoom, maxzoom:$maxzoom, minzoom:$minzoom, title:'$title', infohtml:'$infohtml', maxtitle:'$maxtitle', maxcontent:'$maxcontent', type:$type});
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

	function plugin_googlemaps_mark_simple_format_listhtml($format, $title, $caption, $maxcontentfull) {
		if ($maxcontentfull) {
			$maxcontentfull = '<a href=\''.$maxcontentfull.'\'>[PAGE]</a>';
		}
		$html = $format;
		$html = str_replace('%title%', $title, $html);
		$html = str_replace('%caption%', $caption, $html);
		$html = str_replace('%image%', '', $html);
		$html = str_replace('%maxcontent%', $maxcontentfull, $html);
		return $html;
	}

	function plugin_googlemaps_mark_format_listhtml($page, $map, $format, $alink,
	$key, $infohtml, $title, $caption, $image, $zoomstr, $maxcontentfull) {

		if ($alink == true) {
			$atag = "<a id=\"".$map."_mark".$this->marker_count[$this->root->mydirname][$page][$map]."\"></a>";
			$atag .= "<a href=\"#$map\"";
		}

		$atag .= " onclick=\"googlemaps_markers['$page']['$map']['$key'].onclick();\">%title%</a>";

		if ($maxcontentfull) {
			$maxcontentfull = '<a href=\''.$maxcontentfull.'\'>[PAGE]</a>';
		}

		$html = $format;
		if ($alink == true) {
			$html = str_replace('%title%', $atag , $html);
		}
		$html = str_replace('%title%', $title, $html);
		$html = str_replace('%caption%', $caption, $html);
		$html = str_replace('%image%', $image, $html);
		$html = str_replace('%maxcontent%', $maxcontentfull, $html);
		return $html;
	}

	function plugin_googlemaps_mark_format_infohtml($page, $map, $format, $alink, $title, $caption, $image) {

		$html = str_replace('\'', '\\\'', $format);
		if ($alink == true) {
			$atag = "%title% <a href=\\'#".$map."_mark".$this->marker_count[$this->root->mydirname][$page][$map]."\\'>"
			.$this->cont['PLUGIN_GOOGLEMAPS2_MK_DEF_AJUMP'].'</a>';
			$html = str_replace('%title%', $atag , $html);
		}
		$html = str_replace('%title%',$title , $html);
		$html = str_replace('%caption%', $caption, $html);

		if ($image != '') {
			$image = str_replace("'", "\\\'", $image);
		}
		$html = str_replace('%image%', $image, $html);

		return $html;
	}
}
?>