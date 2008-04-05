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
 * 2005-09-25 1.1	-Release
 * 2006-04-20 2.0	-GoogleMaps API ver2
 * 2006-07-15 2.1	-googlemaps2_insertmarker.inc.phpを追加。usetoolオプションの廃止。
 *					 ブロック型の書式を使えるようにした。
 *					-googlemaps2にdbclickzoom, continuouszoomオプションを追加。
 *					-googlemaps2_markのimageオプションで添付画像を使えるようにした。
 *					-OverViewMap, マウスクリック操作の改良。
 *					-XSS対策。googlemaps2_markのformatlist, formatinfoの廃止。
 *					-マーカーのタイトルをツールチップで表示。
 *					-アンカー名にpukiwikigooglemaps2_というprefixをつけるようにした。
 * 2006-07-29 2.1.1 -includeやcalender_viewerなど複数のページを一つのページにまとめて
 *					 出力するプラグインでマップが表示されないバグを修正。
 * 2006-08-24 2.1.2 -単語検索でマーカー名がハイライトされた時のバグを修正。
 * 2006-09-30 2.1.3 -携帯電話,PDAなど非対応のデバイスでスクリプトを出力しないようにした。
 * 2006-12-30 2.2	-マーカーのフキダシを開く時に画像の読み込みを待つようにした。
 *					-GMapTypeControlを小さく表示。
 *					-GoogleのURLをmaps.google.comからmaps.google.co.jpに。
 *					-googlemaps2にgeoctrl, crossctrlオプションの追加。
 *					-googlemaps2_markにmaxurl, minzoom, maxzoomオプションの追加。
 *					-googlemaps2_insertmarkerでimage, maxurl, minzoom, maxzoomを入力可能に。
 *					-googlemaps2_drawにfillopacity, fillcolor, inradiusオプションの追加。
 *					-googlemaps2_drawにpolygonコマンドの追加。
 * 2007-01-10 2.2.1 -googlemaps2のoverviewctrlのパラメータで地図が挙動不審になるバグを修正。
 *					-googlemaps2_insertmarkerがincludeなどで複数同時に表示されたときの挙動不審を修正
 * 2007-01-22 2.2.2 -googlemaps2のwidth, heightで単位が指定されていないときは暗黙にpxを補う。
 *					-googlemaps2のoverviewtypeにautoを追加。地図のタイプにオーバービューが連動。
 * 2007-01-31 2.2.3 -googlemaps2でcrossコントロール表示時にフキダシのパンが挙動不審なのを修正。
 *					-GoogleのロゴがPukiwikiのCSSによって背景を透過しない問題を暫定的に修正。
 * 2007-08-04 2.2.4 -IEで図形を描画できないバグを修正。
 *					-googlemaps2にgeoxmlオプションの追加。
 * 2007-09-25 2.2.5 -geoxmlでエラーがあるとinsertmarkerが動かないバグを修正。
 * 2007-12-01 2.3.0 -googlemaps2のgeoctrl, overviewtypeオプションの廃止
 *					-googlemaps2にgooglebar, importicon, backlinkmarkerオプションの追加
 *					-googlemaps2_markのmaxurlオプションの廃止。（一時的にmaxcontentにマッピングした）
 *					-googlemaps2_markにmaxcontent, maxtitle, titleispagenameオプションを追加。
 */

class xpwiki_plugin_googlemaps2 extends xpwiki_plugin {
	
	var $map_count = array();
	var $lastmap_name;
	
	function plugin_googlemaps2_init () {

		// 言語ファイルの読み込み
		$this->load_language();

		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_KEY'] =  'ABQIAAAAv2QINn0BFSDyNh38h-ot6RR7mgPdW6gOZV_PvH6uKxrQxi_kMxQdnrNUwY6bBhsUf_q-K_RFktoHsg';
	
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_MAPNAME'] =  'map';	  //Map名
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_WIDTH'] =  '400px';			  //横幅
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_HEIGHT'] =  '400px';			  //縦幅
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_LAT'] =   35.036198;		  //経度
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_LNG'] =   135.732103;		  //緯度
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_ZOOM'] =   13;		  //ズームレベル
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_TYPE'] =   'normal'; //マップのタイプ(normal, satellite, hybrid)
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_MAPCTRL'] =   'normal'; //マップコントロール(none,smallzoom,small,normal,large)
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_TYPECTRL'] = 'normal'; //maptype切替コントロール(none, normal)
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_SCALECTRL'] = 'none';	 //スケールコントロール(none, normal)
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWCTRL'] = 'none';	 //オーバービューマップ(none, hide, show)
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_CROSSCTRL'] = 'none';	 //センタークロスコントロール(none, show)
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWWIDTH'] =  '150';	 //オーバービューマップの横幅
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWHEIGHT'] = '150';	 //オーバービューマップの縦幅
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_API'] =  2;				 //APIの後方互換用フラグ(1=1系, 2=2系). 廃止予定。
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_TOGGLEMARKER'] =  false;	   //マーカーの表示切替チェックの表示
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_NOICONNAME'] =  'Unnamed'; //アイコン無しマーカーのラベル
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_DBCLICKZOOM'] =  true;	   //ダブルクリックでズームする(true, false)
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_CONTINUOUSZOOM'] =  true;	   //滑らかにズームする(true, false)
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_GEOXML'] =  '';			   //読み込むKML, GeoRSSのURL
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_GOOGLEBAR'] =  false;		   //GoogleBarの表示
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_IMPORTICON'] =  '';		   //アイコンを取得するPukiwikiページ
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_BACKLINKMARKER'] =  false;	//バックリンクでマーカーを集める
		
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_WIKITAG'] = 'hide';	//このマップのWiki記法 (none, hide, show)
		$this->cont['PLUGIN_GOOGLEMAPS2_DEF_AUTOZOOM'] = false;	//自動ズームですべてのマーカーを表示
	
		//Pukiwikiは1.4.5から携帯電話などのデバイスごとにプロファイルを用意して
		//UAでスキンを切り替えて表示できるようになったが、この定数ではGoogleMapsを
		//表示可能なプロファイルを設定する。
		//対応デバイスのプロファイルをカンマ(,)区切りで記入する。
		//Pukiwiki1.4.5以降でサポートしてるデフォルトのプロファイルはdefaultとkeitaiの二つ。
		//ユーザーが追加したプロファイルがあり、それもGoogleMapsが表示可能なデバイスなら追加すること。
		//またデフォルトのプロファイルを"default"以外の名前にしている場合も変更すること。
		//注:GoogleMapsは携帯電話で表示できない。
		$this->cont['PLUGIN_GOOGLEMAPS2_PROFILE'] =  'default';

	}
	
	function plugin_googlemaps2_is_supported_profile () {
		if (defined("UA_PROFILE")) {
			return in_array($this->cont['UA_PROFILE'], preg_split('/[\s,]+/', $this->cont['PLUGIN_GOOGLEMAPS2_PROFILE']));
		} else {
			return 1;
		}
	}
	
	function plugin_googlemaps2_get_default () {
	//	global $vars;
		return array(
			'mapname'		 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_MAPNAME'],
			'key'			 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_KEY'],
			'width'			 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_WIDTH'],
			'height'		 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_HEIGHT'],
			'lat'			 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_LAT'],
			'lng'			 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_LNG'],
			'zoom'			 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_ZOOM'],
			'mapctrl'		 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_MAPCTRL'],
			'type'			 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_TYPE'],
			'typectrl'		 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_TYPECTRL'],
			'scalectrl'		 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_SCALECTRL'],
			'overviewctrl'	 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWCTRL'],
			'crossctrl'		 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_CROSSCTRL'],
			'overviewwidth'	 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWWIDTH'],
			'overviewheight' => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWHEIGHT'],
			'api'			 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_API'],
			'togglemarker'	 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_TOGGLEMARKER'],
			'noiconname'	 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_NOICONNAME'],
			'dbclickzoom'	 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_DBCLICKZOOM'],
			'continuouszoom' => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_DBCLICKZOOM'],
			'geoxml'		 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_GEOXML'],
			'googlebar'		 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_GOOGLEBAR'],
			'importicon'	 => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_IMPORTICON'],
			'backlinkmarker' => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_BACKLINKMARKER'],
			'wikitag'        => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_WIKITAG'],
			'autozoom'       => $this->cont['PLUGIN_GOOGLEMAPS2_DEF_AUTOZOOM'],
		);
	}
	
	function plugin_googlemaps2_convert() {
		static $init = true;
		$args = func_get_args();
		$ret = "<div>".$this->plugin_googlemaps2_output($init, $args)."</div>";
		$init = false;
		return $ret;
	}
	
	function plugin_googlemaps2_inline() {
		static $init = true;
		$args = func_get_args();
		array_pop($args);
		$ret = $this->plugin_googlemaps2_output($init, $args);
		$init = false;
		return $ret;
		
	}
	
	function plugin_googlemaps2_action() {
	//	global $vars;
		$action = isset($this->root->vars['action']) ? $this->root->vars['action'] : '';
		$page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
	
		switch($action) {
			case '':
				break;
			// maxContent用のレイアウトスタイルでページのbodyを出力
			case 'showbody':
				if ($this->func->is_page($page)) {
					$body = $this->func->convert_html($this->func->get_source($page));
					$this->func->convert_finisher($body);
				} else {
					if ($page == '') {
						$page = '(Empty Page Name)';
					}
					$body = htmlspecialchars($page);
					$body .= '<br>Unknown page name';
				}
				$this->func->pkwk_common_headers();
				header('Cache-control: no-cache');
				header('Pragma: no-cache');
				header('Content-Type: text/html; charset='.$this->cont['CONTENT_CHARSET']);
				print <<<EOD
<div>
$body
</div>
EOD;
				break;
		}
		exit;
	}
	
	function plugin_googlemaps2_getbool($val) {
		if ($val == false) return 0;
		if (!strcasecmp ($val, "false") || 
			!strcasecmp ($val, "no"))
			return 0;
		return 1;
	}
	
	function plugin_googlemaps2_addprefix($page, $name) {
		$page = $this->get_pgid($page);
		if ($name === $this->cont['PLUGIN_GOOGLEMAPS2_DEF_MAPNAME']) {
			if (!isset($this->map_count[$page])) {
				$this->map_count[$page] = 0;
			}
			$this->map_count[$page]++;
			$name .= strval($this->map_count[$page]);
		}
		$this->lastmap_name = 'pukiwikigooglemaps2_'.$page.'_'.$name;
		return $this->lastmap_name;
	}
	
	function plugin_googlemaps2_output($doInit, $params) {
	//	global $vars;
		$this->root->rtf['disable_render_cache'] = true;
		
		if (!$this->plugin_googlemaps2_is_supported_profile()) {
			return "googlemaps2:unsupported device";
		}
		
		$defoptions = $this->plugin_googlemaps2_get_default();
		
		$inoptions = array();
		$isSetZoom = false;
		foreach ($params as $param) {
			$pos = strpos($param, '=');
			if ($pos === false) continue;
			$index = trim(substr($param, 0, $pos));
			$value = htmlspecialchars(trim(substr($param, $pos+1)), ENT_QUOTES);
			$inoptions[$index] = $value;
			if ($index == 'cx') {$cx = (float)$value;}//for old api
			if ($index == 'cy') {$cy = (float)$value;}//for old api
			if ($index == 'zoom') {$isSetZoom = true;}//for old api
		}
	
		if (array_key_exists('define', $inoptions)) {
			$this->root->vars['googlemaps2'][$inoptions['define']] = $inoptions;
			return "";
		}

		$this->func->add_tag_head('googlemaps2.css');
		
		$coptions = array();
		if (array_key_exists('class', $inoptions)) {
			$class = $inoptions['class'];
			if (array_key_exists($class, $this->root->vars['googlemaps2'])) {
				$coptions = $this->root->vars['googlemaps2'][$class];
			}
		}
		$options = array_merge($defoptions, $coptions, $inoptions);
		$mapname		= $this->plugin_googlemaps2_addprefix($this->root->vars['page'], $options['mapname']);
		$key			= $options['key'];
		$width			= $options['width'];
		$height			= $options['height'];
		$lat			= (float)$options['lat'];
		$lng			= (float)$options['lng'];
		$zoom			= (integer)$options['zoom'];
		$type			= $options['type'];
		$mapctrl		= $options['mapctrl'];
		$typectrl		= $options['typectrl'];
		$scalectrl		= $options['scalectrl'];
		$overviewctrl	= $options['overviewctrl'];
		$crossctrl		= $options['crossctrl'];
		$togglemarker	= $this->plugin_googlemaps2_getbool($options['togglemarker']);
		$googlebar		= $this->plugin_googlemaps2_getbool($options['googlebar']);
		$overviewwidth	= $options['overviewwidth'];
		$overviewheight = $options['overviewheight'];
		$api			= (integer)$options['api'];
		$noiconname		= $options['noiconname'];
		$dbclickzoom	= $this->plugin_googlemaps2_getbool($options['dbclickzoom']);
		$continuouszoom = $this->plugin_googlemaps2_getbool($options['continuouszoom']);
		$geoxml			= preg_replace("/&amp;/i", '&', $options['geoxml']);
		$importicon		= $options['importicon'];
		$backlinkmarker = $this->plugin_googlemaps2_getbool($options['backlinkmarker']);
		$wikitag        = $options['wikitag'];
		$autozoom       = $options['autozoom'];
		
		$page = $this->get_pgid($this->root->vars['page']);
		//apiのチェック
		if ( ! (is_numeric($api) && $api >= 0 && $api <= 2) ) {
			 $api = 2;
		}
		$this->root->vars['googlemaps2_info'][$mapname]['api'] = $api;
		//古い1系APIとの互換性のためcx, cyが渡された場合lng, latに代入する。
		if ($api < 2) {
			if (isset($cx) && isset($cy)) {
				$lat = $cx;
				$lng = $cy;
			} else {
				$tmp = $lng;
				$lng = $lat;
				$lat = $tmp;
			}
		} else {
			if (isset($cx)) $lng = $cx;
			if (isset($cy)) $lat = $cy;
		}
		
		// zoomレベル
		if ($api < 2 && $isSetZoom) {
			$zoom = 17 - $zoom;
		}
		// width, heightの値チェック
		if (is_numeric($width)) { $width = (int)$width . "px"; }
		if (is_numeric($height)) { $height = (int)$height . "px"; }
	
		// Mapタイプの名前を正規化
		$type = $this->plugin_googlemaps2_get_maptype($type);
	
		// 初期化処理の出力
		if ($doInit) {
			$output = $this->plugin_googlemaps2_init_output($key, $noiconname);
		} else {
			$output = "";
		}
		$pukiwikiname = $options['mapname'];
		$output .= <<<EOD
<div id="$mapname" style="width: $width; height: $height;"></div>
EOD;
		if ($wikitag !== 'none') {
			if ($wikitag === 'show') {
				$_display = '';
				$_icon = '-';
			} else {
				$_display = 'display:none;';
				$_icon = '+';
			}
			$output .= <<<EOD
<div class="googlemaps2_tag_base" style="width: $width;">
<span id="{$mapname}_handle" class="googlemaps2_handle" onclick="this.innerHTML = (this.innerHTML == '+')? '-' : '+';$('{$mapname}_info').toggle();">{$_icon}</span>
 {$this->msg['wikitag_thismap']}
<div id="{$mapname}_info" class="googlemaps2_tag_info" style="width: $width;{$_display}">&nbsp;</div>
</div>
EOD;
		}
		$output .= <<<EOD
<script type="text/javascript">
//<![CDATA[
onloadfunc.push( function () {

if (typeof(googlemaps_maps['$page']) == 'undefined') {
	googlemaps_maps['$page'] = new Array();
	googlemaps_markers['$page'] = new Array();
	googlemaps_marker_mgrs['$page'] = new Array();
	googlemaps_icons['$page'] = new Array();
	googlemaps_crossctrl['$page'] = new Array();
}

var map = new GMap2(document.getElementById("$mapname"));
map.pukiwikiname = "$pukiwikiname";
GEvent.addListener(map, "dblclick", function() {
		this.closeInfoWindow();
});
onloadfunc2.push( function () {
	p_googlemaps_regist_to_markermanager("$page", "$mapname", true);
});

map.setCenter(PGTool.getLatLng($lat, $lng, "$api"), $zoom, $type);

var marker_mgr = new GMarkerManager(map);

// 現在(2.70)のMarker Managerではマーカーをhideしていても、描画更新時に
// マーカーを表示してしまうため、リフレッシュ後にフラグを確認して再び隠す。
// 一度表示されて消えるみたいな挙動になるが、他に手段が無いので仕方が無い。
GEvent.addListener(marker_mgr, "changed", function(bounds, markerCount) {
	var markers = googlemaps_markers["$page"]["$mapname"];
	for (var key in markers) {
		if (!markers.hasOwnProperty(key)) continue;
		var m = markers[key];
		if (m.isVisible() == false) {
			m.marker.hide();
		}
	}
});
EOD;
		// Auto Zoom
		if ($autozoom) {
			$output .= <<<EOD
onloadfunc2.push( function () {
	p_googlemaps_auto_zoom("$page", "$mapname");
});
EOD;
		}
		// Show Map Control/Zoom 
		switch($mapctrl) {
			case "small":
				$output .= "map.addControl(new GSmallMapControl());\n";
				break;
			case "smallzoom":
				$output .= "map.addControl(new GSmallZoomControl());\n";
				break;
			case "none":
				break;
			case "large":
			default:
				$output .= "map.addControl(new GLargeMapControl());\n";
				break;
		}
		
		// Scale
		if ($scalectrl != "none") {
			$_pos = ($googlebar)? ', new GControlPosition(G_ANCHOR_BOTTOM_LEFT, new GSize(90,4))' : '';
			$output .= "map.addControl(new GScaleControl(){$_pos});\n";
		}
		
		// Show Map Type Control and Center
		if ($typectrl != "none") {
			$output .= "map.addControl(new GMapTypeControl(true));\n";
		}
		
		// Double click zoom
		if ($dbclickzoom) {
			$output .= "map.enableDoubleClickZoom();\n";
		} else {
			$output .= "map.disableDoubleClickZoom();\n";
		}
	
		// Continuous zoom
		if ($continuouszoom) {
			$output .= "map.enableContinuousZoom();\n";
		} else {
			$output .= "map.disableContinuousZoom();\n";
		}
		
		// OverviewMap
		if ($overviewctrl != "none") {
			$ovw = preg_replace("/(\d+).*/i", "\$1", $overviewwidth);
			$ovh = preg_replace("/(\d+).*/i", "\$1", $overviewheight);
			if ($ovw == "") $ovw = $this->cont['PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWWIDTH'];
			if ($ovh == "") $ovh = $this->cont['PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWHEIGHT'];
			$output .= "var ovctrl = new GOverviewMapControl(new GSize($ovw, $ovh));\n";
			$output .= "map.addControl(ovctrl);\n";
	
			if ($overviewctrl == "hide") {
			$output .= "ovctrl.hide(true);\n";
			}
		}
	
		// Geo XML
		if ($geoxml != "") {
			$output .= "try {\n";
			$output .= "var geoxml = new GGeoXml(\"$geoxml\");\n";
			$output .= "map.addControl(geoxml);\n";
			$output .= "} catch (e) {}\n";
		}
		
		// GoogleBar
		if ($googlebar) {
			$output .= "map.enableGoogleBar();\n";
		}
	
		// Center Cross Custom Control
		if ($crossctrl != "none") {
			$output .= "var crossctrl = new PGCross();\n";
			$output .= "crossctrl.initialize(map);\n";
			$output .= "var pos = crossctrl.getDefaultPosition();\n";
			$output .= "pos.apply(crossctrl.container);\n";
			$output .= "var crossChangeStyleFunc = function () {\n";
			$output .= "	switch (map.getCurrentMapType()) {\n";
			$output .= "		case G_NORMAL_MAP:	  crossctrl.changeStyle('#000000', 0.5); break;\n";
			$output .= "		case G_SATELLITE_MAP: crossctrl.changeStyle('#FFFFFF', 0.9); break;\n";
			$output .= "		case G_HYBRID_MAP:	  crossctrl.changeStyle('#FFFFFF', 0.9); break;\n";
			$output .= "		default: crossctrl.changeStyle('#000000', 0.5); break;\n";
			$output .= "	}\n";
			$output .= "}\n";
			$output .= "GEvent.addListener(map, 'maptypechanged', crossChangeStyleFunc);\n";
			$output .= "crossChangeStyleFunc();\n";
			$output .= "googlemaps_crossctrl['$page']['$mapname'] = crossctrl;\n";
		}
	
		// マーカーの表示非表示チェックボックス
		if ($togglemarker) {
			$output .= "onloadfunc.push( function(){p_googlemaps_togglemarker_checkbox('$page', '$mapname', '$noiconname');} );";
		}
	
		$output .= "PGTool.transparentGoogleLogo(map);\n";
		$output .= "googlemaps_maps['$page']['$mapname'] = map;\n";
		$output .= "googlemaps_markers['$page']['$mapname'] = new Array();\n";
		$output .= "googlemaps_marker_mgrs['$page']['$mapname'] = marker_mgr;\n";

		// Map tag
		if ($wikitag !== 'none') {
			$maptag  = " + ', zoom=' + googlemaps_maps['$page']['$mapname'].getZoom()";
			$maptag .= " + ', type=' + ((googlemaps_maps['$page']['$mapname'].getCurrentMapType() == G_SATELLITE_MAP)? 'satellite' : ((googlemaps_maps['$page']['$mapname'].getCurrentMapType() == G_HYBRID_MAP)? 'hybrid' : 'normal'))";
	
			$mapBlock  = "'#googlemaps2(lat=' + PGTool.fmtNum(googlemaps_maps['$page']['$mapname'].getCenter().lat()) + ', lng=' + PGTool.fmtNum(googlemaps_maps['$page']['$mapname'].getCenter().lng())";
			$mapBlock .= " + ', width=$width'";
			$mapBlock .= " + ', height=$height'";
			$mapBlock .= $maptag;
	
			$mapBlock .= " + ', mapctrl=$mapctrl'";
			$mapBlock .= " + ', typectrl=$typectrl'";
			$mapBlock .= " + ', scalectrl=$scalectrl'";
			$mapBlock .= " + ', overviewctrl=$overviewctrl'";
			$mapBlock .= " + ', crossctrl=$crossctrl'";
			$mapBlock .= " + ', togglemarker=$togglemarker'";
			$mapBlock .= " + ', googlebar=$googlebar'";
			$mapBlock .= " + ', wikitag=$wikitag'";
	
			$mapBlock .= " + ')'";
			
			$mapMark  = "'&googlemaps2_mark(' + PGTool.fmtNum(googlemaps_maps['$page']['$mapname'].getCenter().lat()) + ', ' + PGTool.fmtNum(googlemaps_maps['$page']['$mapname'].getCenter().lng())";
			$mapMark .= $maptag;
			$mapMark .= " + ', title=Here Title){Here Caption};'";
			
			$output .= "GEvent.addListener(googlemaps_maps['$page']['$mapname'], 'moveend', function(){\$('$mapname' + '_info').innerHTML = '<p>' + $mapBlock + '</p><p>' + $mapMark;});\n";
			$output .= "\$('$mapname' + '_info').innerHTML = '<p>' + $mapBlock + '</p><p>' + $mapMark;\n";
		}

		$output .= "});\n";
		$output .= "//]]>\n";
		$output .= "</script>\n";
		
		// 指定されたPukiwikiページからアイコンを収集する
		if ($importicon != "") {
			$lines = $this->func->get_source($importicon);
			foreach ($lines as $line) {
				$ismatch = preg_match('/googlemaps2_icon\(.*?\)/i', $line, $matches);
				if ($ismatch) {
					$output .= $this->func->convert_html("#" . $matches[0]) . "\n";
				}
			}
		}
	
		// このページのバックリンクからマーカーを収集する。
		if ($backlinkmarker) {
			$links = $this->func->links_get_related_db($this->root->vars['page']);
			if (! empty($links)) {
				$output .= "<ul>\n";
				foreach(array_keys($links) as $page) {
					$ismatch = preg_match('/#googlemaps2_mark\(([^, \)]+), *([^, \)]+)(.*?)\)/i', 
					$this->func->get_source($page, TRUE, TRUE), $matches);
					if ($ismatch) {
						$markersource = "&googlemaps2_mark(" . 
						$matches[1] . "," . $matches[2] . 
						", title=" . $page . ", maxcontent=" . $page;
						if ($matches[3] != "") {
							preg_match('/caption=[^,]+/', $matches[3], $m_caption);
							if ($m_caption) $markersource .= "," . $m_caption[0];
							preg_match('/icon=[^,]+/', $matches[3], $m_icon);
							if ($m_icon) $markersource .= "," . $m_icon[0];
						}
						$markersource .= ");";
						$output .= "<li>" . $this->func->make_link($markersource) . "</li>\n";
					}
				}
				$output .= "</ul>\n";
			}
		}
	
		return $output;
	}
	
	function plugin_googlemaps2_get_maptype($type) {
		switch (strtolower(substr($type, 0, 1))) {
			case "n": $type = 'G_NORMAL_MAP'   ; break;
			case "s": $type = 'G_SATELLITE_MAP'; break;
			case "h": $type = 'G_HYBRID_MAP'   ; break;
			default:  $type = 'G_NORMAL_MAP'   ; break;
		}
		return $type;
	}
	
	function plugin_googlemaps2_init_output($key, $noiconname) {
		$this->func->add_js_head('http://maps.google.co.jp/maps?file=api&amp;v=2.x&amp;key='.$key, false, 'UTF-8');
		$this->func->add_tag_head('googlemaps2.js');
		return;
	}
	
	function get_pgid ($page) {
		$pgid = $this->func->get_pgid_by_name($page);
		if (!$pgid) $pgid = '0';
		return strval($pgid);
	}
}
?>