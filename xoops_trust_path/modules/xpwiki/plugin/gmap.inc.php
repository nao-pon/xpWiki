<?php
/* Pukiwiki GoogleMaps plugin 3.1
 * http://reddog.s35.xrea.com
 * -------------------------------------------------------------------
 * Copyright (c) 2005-2012 OHTSUKA, Yoshio
 * This program is free to use, modify, extend at will. The author(s)
 * provides no warrantees, guarantees or any responsibility for usage.
 * Redistributions in any form must retain this copyright notice.
 * ohtsuka dot yoshio at gmail dot com
 * -------------------------------------------------------------------
 * 2005-09-25 1.1   -Release
 * 2006-04-20 2.0   -GoogleMaps API ver2
 * 2006-07-15 2.1   -googlemaps2_insertmarker.inc.phpを追加。usetoolオプションの廃止。
 *                   ブロック型の書式を使えるようにした。
 *                  -googlemaps2にdbclickzoom, continuouszoomオプションを追加。
 *                  -googlemaps2_markのimageオプションで添付画像を使えるようにした。
 *                  -OverViewMap, マウスクリック操作の改良。
 *                  -XSS対策。googlemaps2_markのformatlist, formatinfoの廃止。
 *                  -マーカーのタイトルをツールチップで表示。
 *                  -アンカー名にpukiwikigooglemaps2_というprefixをつけるようにした。
 * 2006-07-29 2.1.1 -includeやcalender_viewerなど複数のページを一つのページにまとめて
 *                   出力するプラグインでマップが表示されないバグを修正。
 * 2006-08-24 2.1.2 -単語検索でマーカー名がハイライトされた時のバグを修正。
 * 2006-09-30 2.1.3 -携帯電話,PDAなど非対応のデバイスでスクリプトを出力しないようにした。
 * 2006-12-30 2.2   -マーカーのフキダシを開く時に画像の読み込みを待つようにした。
 *                  -GMapTypeControlを小さく表示。
 *                  -GoogleのURLをmaps.google.comからmaps.google.co.jpに。
 *                  -googlemaps2にgeoctrl, crossctrlオプションの追加。
 *                  -googlemaps2_markにmaxurl, minzoom, maxzoomオプションの追加。
 *                  -googlemaps2_insertmarkerでimage, maxurl, minzoom, maxzoomを入力可能に。
 *                  -googlemaps2_drawにfillopacity, fillcolor, inradiusオプションの追加。
 *                  -googlemaps2_drawにpolygonコマンドの追加。
 * 2007-01-10 2.2.1 -googlemaps2のoverviewctrlのパラメータで地図が挙動不審になるバグを修正。
 *                  -googlemaps2_insertmarkerがincludeなどで複数同時に表示されたときの挙動不審を修正
 * 2007-01-22 2.2.2 -googlemaps2のwidth, heightで単位が指定されていないときは暗黙にpxを補う。
 *                  -googlemaps2のoverviewtypeにautoを追加。地図のタイプにオーバービューが連動。
 * 2007-01-31 2.2.3 -googlemaps2でcrossコントロール表示時にフキダシのパンが挙動不審なのを修正。
 *                  -GoogleのロゴがPukiwikiのCSSによって背景を透過しない問題を暫定的に修正。
 * 2007-08-04 2.2.4 -IEで図形を描画できないバグを修正。
 *                  -googlemaps2にgeoxmlオプションの追加。
 * 2007-09-25 2.2.5 -geoxmlでエラーがあるとinsertmarkerが動かないバグを修正。
 * 2007-12-01 2.3.0 -googlemaps2のgeoctrl, overviewtypeオプションの廃止
 *                  -googlemaps2にgooglebar, importicon, backlinkmarkerオプションの追加
 *                  -googlemaps2_markのmaxurlオプションの廃止。（一時的にmaxcontentにマッピングした）
 *                  -googlemaps2_markにmaxcontent, maxtitle, titleispagenameオプションを追加。
 * 2008-10-21 2.3.1 -apiのバージョンを2.132dに固定した。
 * 2012-10-28 3.0.0 GoogleMaps API v3 (3.10)
 *                  -プラグインの名称をgooglemaps3に変更した
 *                  -googlemaps3
 *                      -廃止オプション
 *                          -key
 *                          -api
 *                          -mapctrl        zoomctrlとpanctrlに分離した
 *                          -overviewwidth  v3ではサイズ変更できないみたい
 *                          -overviewheight v3ではサイズ変更できないみたい
 *                          -continuouszoom
 *                          -googlebar		searchctrlになりました
 *                          -geoxml         kmlになりました
 *                      -追加オプション
 *                          -zoomctrl       拡縮コントロール
 *                          -panctrl        移動コントロール
 *                          -rotatectrl     45度回転コントロール(45度回転に地図が対応してないとダメ)
 *                          -streetviewctrl ストリートビューコントロール
 *                          -searchctrl		検索ボックス
 *                          -kml            KMLファイルへのURL,添付ファイル名      
 *                      -変更オプション
 *                          -type           (追加)roadmap, terrain
 *                          -typectrl       (追加)horizontal, dropdown
 *                          -crossctrl      (追加)normal
 *                                          (廃止)show
 *                  -googlemaps3_mark
 *                      -廃止オプション
 *                          -maxcontent    v3で無くなった
 *                          -maxtitle      v3で無くなった
 *                          -maxurl        v3で無くなった
 *                      -追加オプション
 *                          -flat           アイコンの影を無くす
 *
 *                  -googlemaps3_icon
 *                      -変更なし
 *
 * 2012-12-01 3.1.0 GoogleMaps API v3 (3.10)
 *                  -googlemaps3
 *                      -kmlオプションの追加。(旧geoxml)
 *  
 */

/**
 * xpwiki_plugin_gmap : Google Maps V3 for xpWiki
 * @author nao-pon http://xoops.hypweb.net/
 *
 * 2013-02-17 xpWiki 版 googlemaps2 と reddog さんの PukiWiki 版 googlemaps3 を合体
 *            - reddog さんの google maps プラグインとの相違点 (V2 時代も含む)
 *            -- マップ名の自動付与 mark 利用時にもマップ名を省略すると直前のマップにポイントされる
 *            -- insertmarker に住所を追加
 *            -- マップオプシションの追加
 *            --- autozoom 自動ズームで複数マーカー時にすべてのマーカーが表示される
 *            --- wikitag マップの Wiki 記法表示オプション
 *            --- dropmarker (マーカーを移動してポイント指定)を追加 (V3)
 *            --- googlebar オプションの復活 （使用している API がすでにサポート対象外なので使えなくなるかも知れない） (V3)
 *            -- マーカー用画像に ref プラグインを利用するようにした（サムネイル自動作成）
 *            -- ズーム最大値を 17 から 21 に変更
 *            -- ズーム値範囲を指定したマーカーのみズーム変更時にリライトするようにした (V3)
 *            -- icon の影指定、Infowindow位置指定、ポリゴン指定のバグ修正 (V3)
 *            -- insertmarker のフォームの値の cookie への保存する項目を増やした (V3)
 *            -- insertmarker のフォームの値の cookie 保存の path を '/' に指定した (V3)
 */

class xpwiki_plugin_gmap extends xpwiki_plugin {

	var $map_count = array();
	var $lastmap_name;
	var $google_staticmap_url = 'https://maps.googleapis.com/maps/api/staticmap?';

	function plugin_gmap_init () {

		// 言語ファイルの読み込み
		$this->load_language();

		$this->cont['PLUGIN_GMAP_DEF_MAPNAME'] =        'map';			//Map名(自動設定される)
		$this->cont['PLUGIN_GMAP_DEF_WIDTH'] =          '400px';		//横幅
		$this->cont['PLUGIN_GMAP_DEF_HEIGHT'] =         '400px';		//縦幅
		$this->cont['PLUGIN_GMAP_DEF_LAT'] =            35.036198;	 	//経度
		$this->cont['PLUGIN_GMAP_DEF_LNG'] =            135.732103;		//緯度
		$this->cont['PLUGIN_GMAP_DEF_ZOOM'] =           13;				//ズームレベル
		$this->cont['PLUGIN_GMAP_DEF_AUTOZOOM'] =       false;			//自動ズームですべてのマーカーを表示
		$this->cont['PLUGIN_GMAP_DEF_TYPE'] =           'roadmap';		//マップのタイプ(normal, roadmap, satellite, hybrid, terrain)
		//$this->cont['PLUGIN_GMAP_DEF_MAPCTRL'] =      'normal';		//マップコントロール(none,smallzoom,small,normal,large) //廃止
		$this->cont['PLUGIN_GMAP_DEF_PANCTRL'] =        'normal';		//移動コントロール(none,normal)
		$this->cont['PLUGIN_GMAP_DEF_ZOOMCTRL'] =       'normal';		//拡縮コントロール(none,normal,small,large)
		$this->cont['PLUGIN_GMAP_DEF_TYPECTRL'] =       'normal';		//maptype切替コントロール(none, normal, horizontal, dropdown)
		//$this->cont['PLUGIN_GMAP_DEF_PHYSICALCTRL'] = 'show';	 		//地形図切替(none, show) //廃止
		$this->cont['PLUGIN_GMAP_DEF_SCALECTRL'] =      'none';	 		//スケールコントロール(none, normal)
		$this->cont['PLUGIN_GMAP_DEF_ROTATECTRL'] =     'none';			//45度回転コントロール(none, normal)
		$this->cont['PLUGIN_GMAP_DEF_STREETVIEWCTRL'] = 'normal';		//ストリートビューコントロール(none, normal)
		$this->cont['PLUGIN_GMAP_DEF_OVERVIEWCTRL'] =   'none';	 		//オーバービューマップ(none, hide, show)
		$this->cont['PLUGIN_GMAP_DEF_CROSSCTRL'] =      'none';	 		//センタークロスコントロール(none, show)
		$this->cont['PLUGIN_GMAP_DEF_SEARCHCTRL'] =     'none';			//検索ボックスコントロール(none, normal)
		$this->cont['PLUGIN_GMAP_DEF_OVERVIEWWIDTH'] =  '150';			//オーバービューマップの横幅
		$this->cont['PLUGIN_GMAP_DEF_OVERVIEWHEIGHT'] = '150';			//オーバービューマップの縦幅
		//$this->cont['PLUGIN_GMAP_DEF_API'] =            2;			//APIの後方互換用フラグ(1=1系, 2=2系). 廃止予定。
		$this->cont['PLUGIN_GMAP_DEF_DROPMARKER'] =     'none';			//ドロップマーカーの表示
		$this->cont['PLUGIN_GMAP_DEF_TOGGLEMARKER'] =   false;			//マーカーの表示切替チェックの表示
		$this->cont['PLUGIN_GMAP_DEF_NOICONNAME'] = $this->msg['unnamed_icon_caption']; //アイコン無しマーカーのラベル
		$this->cont['PLUGIN_GMAP_DEF_DBCLICKZOOM'] =    true;			//ダブルクリックでズームする(true, false)
		//$this->cont['PLUGIN_GMAP_DEF_CONTINUOUSZOOM'] =  true;		//滑らかにズームする(true, false)　V3で廃止
		$this->cont['PLUGIN_GMAP_DEF_SCROLLWHEEL'] =    true;			//マウスホイールでズームする(true, false)
		$this->cont['PLUGIN_GMAP_DEF_KML'] =            '';				//読み込むKML, GeoRSSのURL
		$this->cont['PLUGIN_GMAP_DEF_GOOGLEBAR'] =      false;			//GoogleBarの表示
		$this->cont['PLUGIN_GMAP_DEF_IMPORTICON'] =     '';				//アイコンを取得するPukiwikiページ
		$this->cont['PLUGIN_GMAP_DEF_BACKLINKMARKER'] = false;			//バックリンクでマーカーを集める
		$this->cont['PLUGIN_GMAP_DEF_WIKITAG'] =        'hide';			//このマップのWiki記法 (none, hide, show)

		//Pukiwikiは1.4.5から携帯電話などのデバイスごとにプロファイルを用意して
		//UAでスキンを切り替えて表示できるようになったが、この定数ではGoogleMapsを
		//表示可能なプロファイルを設定する。
		//対応デバイスのプロファイルをカンマ(,)区切りで記入する。
		//xpWikiでサポートしてるデフォルトのプロファイルは default,mobile,keitai の3つ。
		//ユーザーが追加したプロファイルがあり、それもGoogleMapsが表示可能なデバイスなら追加すること。
		//またデフォルトのプロファイルを"default"以外の名前にしている場合も変更すること。
		//注:GoogleMapsは携帯(ガラケー)電話で表示できない。
		$this->cont['PLUGIN_GMAP_PROFILE'] =  'default,mobile';

		// This plugins config
		$this->conf['ApiVersion'] = '3';

		if ($this->cont['UA_PROFILE'] === 'mobile') {
			$this->conf['StaticMapSizeW'] = 480;
			$this->conf['StaticMapSizeH'] = 400;
			$this->conf['mapsize'] = 'width="480" height="400"';
		} else {
			$this->conf['StaticMapSizeW'] = 240;
			$this->conf['StaticMapSizeH'] = 200;
			$this->conf['mapsize'] = (isset($this->root->keitai_imageTwiceDisplayWidth) && $this->root->keitai_imageTwiceDisplayWidth && $this->root->keitai_display_width >= $this->root->keitai_imageTwiceDisplayWidth)? 'width="480" height="400"' : 'width="240" height="200"';
		}
		$this->conf['navsize'] = 'width="60" height="40"';

		$this->conf['StaticMapSize'] = $this->conf['StaticMapSizeW'] . 'x' . $this->conf['StaticMapSizeH'];
	}

	function plugin_gmap_is_supported_profile () {
		if ($this->cont['UA_PROFILE']) {
			return in_array($this->cont['UA_PROFILE'], preg_split('/[\s,]+/', $this->cont['PLUGIN_GMAP_PROFILE']));
		} else {
			return 1;
		}
	}

	function plugin_gmap_get_default () {
		return array(
			'mapname'		 => $this->cont['PLUGIN_GMAP_DEF_MAPNAME'],
			'key'			 => $this->root->google_api_key,
			'lat'			 => $this->cont['PLUGIN_GMAP_DEF_LAT'],
			'lng'			 => $this->cont['PLUGIN_GMAP_DEF_LNG'],
			'width'			 => $this->cont['PLUGIN_GMAP_DEF_WIDTH'],
			'height'		 => $this->cont['PLUGIN_GMAP_DEF_HEIGHT'],
			'zoom'			 => $this->cont['PLUGIN_GMAP_DEF_ZOOM'],
			'autozoom'       => $this->cont['PLUGIN_GMAP_DEF_AUTOZOOM'],
			//'mapctrl'		 => $this->cont['PLUGIN_GMAP_DEF_MAPCTRL'],//廃止
			'panctrl'		 => $this->cont['PLUGIN_GMAP_DEF_PANCTRL'],
			'zoomctrl'		 => $this->cont['PLUGIN_GMAP_DEF_ZOOMCTRL'],
			'type'			 => $this->cont['PLUGIN_GMAP_DEF_TYPE'],
			'typectrl'		 => $this->cont['PLUGIN_GMAP_DEF_TYPECTRL'],
			'scalectrl'		 => $this->cont['PLUGIN_GMAP_DEF_SCALECTRL'],
			'rotatectrl'     => $this->cont['PLUGIN_GMAP_DEF_ROTATECTRL'],
			'streetviewctrl' => $this->cont['PLUGIN_GMAP_DEF_STREETVIEWCTRL'],
			'crossctrl'		 => $this->cont['PLUGIN_GMAP_DEF_CROSSCTRL'],
			'searchctrl'	 => $this->cont['PLUGIN_GMAP_DEF_SEARCHCTRL'],
			'overviewctrl'	 => $this->cont['PLUGIN_GMAP_DEF_OVERVIEWCTRL'],
			//'overviewwidth'	 => $this->cont['PLUGIN_GMAP_DEF_OVERVIEWWIDTH'], //廃止
			//'overviewheight' => $this->cont['PLUGIN_GMAP_DEF_OVERVIEWHEIGHT'], //廃止
			'googlebar'		 => $this->cont['PLUGIN_GMAP_DEF_GOOGLEBAR'],
			'dbclickzoom'	 => $this->cont['PLUGIN_GMAP_DEF_DBCLICKZOOM'],
			'scrollwheel'    => $this->cont['PLUGIN_GMAP_DEF_SCROLLWHEEL'],
			'dropmarker'	 => $this->cont['PLUGIN_GMAP_DEF_DROPMARKER'],
			'togglemarker'	 => $this->cont['PLUGIN_GMAP_DEF_TOGGLEMARKER'],
			'wikitag'        => $this->cont['PLUGIN_GMAP_DEF_WIKITAG'],
			'kml'			 => $this->cont['PLUGIN_GMAP_DEF_KML'],
			'noiconname'	 => $this->cont['PLUGIN_GMAP_DEF_NOICONNAME'],
			'importicon'	 => $this->cont['PLUGIN_GMAP_DEF_IMPORTICON'],
			'backlinkmarker' => $this->cont['PLUGIN_GMAP_DEF_BACKLINKMARKER'],
			//'physicalctrl'   => $this->cont['PLUGIN_GMAP_DEF_PHYSICALCTRL'], //廃止
		);
	}

	function plugin_gmap_convert() {
		static $init = true;
		$args = func_get_args();
		$ret = $this->plugin_gmap_output($init, $args, 'block');
		$init = false;
		return $ret;
	}

	function plugin_gmap_inline() {
		if (isset($this->root->rtf['GET_HEADING_INIT'])) return 'Google Maps';
		static $init = true;
		$args = func_get_args();
		array_pop($args);
		$ret = $this->plugin_gmap_output($init, $args, 'inline-block');
		$init = false;
		return $ret;
	}

	function plugin_gmap_action() {
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
					$body = $this->func->htmlspecialchars($page);
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
			case 'static':
				$ret = $this->action_static();
				if ($ret) return array('msg' => 'Mobile Map', 'body' => $ret);
		}
		return array('exit' => 0);
	}

	function action_static() {
		////////////////////////////////////////////////////////////
		// This part is based on GNAVI (http://xoops.iko-ze.net/) //
		////////////////////////////////////////////////////////////

		if ($this->cont['UA_PROFILE'] === 'mobile') {
			$this->func->add_tag_head('gmap.css');
			$navi_tag = '';
		} else {
			$this->root->keitai_output_filter = 'SJIS';
			$this->root->rtf['no_accesskey'] = TRUE;
			$navi_tag = '<img src="'.$this->cont['LOADER_URL'].'?src=mnavi.gif" '.$this->conf['navsize'].' />';
		}

		$default_lat  = empty( $this->root->get['lat'] )  ? $this->cont['PLUGIN_GMAP_DEF_LAT']  : floatval( $this->root->get['lat'] ) ;
		$default_lng  = empty( $this->root->get['lng'] )  ? $this->cont['PLUGIN_GMAP_DEF_LNG']  : floatval( $this->root->get['lng'] ) ;
		$default_zoom = empty( $this->root->get['zoom'] ) ? $this->cont['PLUGIN_GMAP_DEF_ZOOM'] : intval( $this->root->get['zoom'] ) ;

		$markers = isset($this->root->get['markers'])? '&amp;markers=' . $this->func->htmlspecialchars($this->root->get['markers']) : '';
		$refer = isset($this->root->get['refer'])? $this->root->get['refer'] : '';
		$title = isset($this->root->get['t'])? $this->root->get['t'] : '';

		$back = '';
		if ($refer) {
			$refer = $this->func->htmlspecialchars(preg_replace('#^[a-zA-Z]+://[^/]+#', '', $refer));
			$back = '[ <a href="'.$this->root->siteinfo['host'].$refer.'">' . $this->root->_msg_back_word . '</a> ]';
			$refer = '&amp;refer=' . $refer;
		}

		$other = $markers . $refer;

		$mymap = $this->google_staticmap_url . "center=$default_lat,$default_lng&zoom=$default_zoom&size={$this->conf['StaticMapSize']}&maptype=mobile&key={$this->root->google_api_key}{$markers}";
		$google_link = $this->get_static_image_url($default_lat, $default_lng, $default_zoom, '', 2, $title);

		/*緯度は -90度 〜 +90度の範囲に、経度は -180度 〜 +180度の範囲に収まるように*/

		$movex=$this->conf['StaticMapSizeW']/pow(2,$default_zoom);
		$movey=$this->conf['StaticMapSizeH']/pow(2,$default_zoom);

		//Amount of movement on Mini map . set bigger than 0 and 1 or less. (0 < value <=1).
		$mobile_mapmove_raito = 0.6;

		$latup = $this->latlnground($default_lat+$movey * $mobile_mapmove_raito);
		$latdown = $this->latlnground($default_lat-$movey * $mobile_mapmove_raito);
		$lngup = $this->latlnground($default_lng+$movex * $mobile_mapmove_raito);
		$lngdown = $this->latlnground($default_lng-$movex * $mobile_mapmove_raito);

		$latup =   $latup   > 90  ? $latup  -180 : ($latup   < -90  ? $latup   + 180 : $latup   );
		$latdown = $latdown > 90  ? $latdown-180 : ($latdown < -90  ? $latdown + 180 : $latdown );
		$lngup =   $lngup   > 180 ? $lngup  -360 : ($lngup   < -180 ? $lngup   + 360 : $lngup   );
		$lngdown = $lngdown > 180 ? $lngdown-360 : ($lngdown < -180 ? $lngdown + 360 : $lngdown );

		$mapkeys=array(
				'zoom' => $default_zoom,
				'zoomdown' => ($default_zoom-1 > 1 ? $default_zoom-1 : 1 ),
				'zoomup' => ($default_zoom+1 < 18 ? $default_zoom+1 : 18) ,
				'doublezoomdown' => ($default_zoom-3 > 1 ? $default_zoom-4 : 1 ),
				'doublezoomup' => ($default_zoom+3 < 18 ? $default_zoom+4 : 18) ,
				'lat' => $default_lat,
				'lng' => $default_lng,
				'latup' =>   $latup  ,
				'latdown' => $latdown  ,
				'lngup' =>   $lngup  ,
				'lngdown' => $lngdown  ,
			);

		$maplink = $this->root->script . '?plugin=gmap&amp;action=static&amp;';
		
		if ($this->cont['UA_PROFILE'] === 'mobile') {
			
		} 
		
		if ($default_zoom < 18) {
			$zoomup = <<<EOD
<a href="{$maplink}&amp;zoom={$mapkeys['zoomup']}&amp;lng={$mapkeys['lng']}&amp;lat={$mapkeys['lat']}{$other}"  accesskey="5" ></a>
<a href="{$maplink}&amp;zoom={$mapkeys['doublezoomup']}&amp;lng={$mapkeys['lng']}&amp;lat={$mapkeys['lat']}{$other}"  accesskey="*" ></a>
EOD;
		} else {
			$zoomup = '';
		}

		if ($default_zoom > 1) {
			$zoomdown = <<<EOD
<a href="{$maplink}&amp;zoom={$mapkeys['zoomdown']}&amp;lng={$mapkeys['lng']}&amp;lat={$mapkeys['lat']}{$other}"  accesskey="0" ></a>
<a href="{$maplink}&amp;zoom={$mapkeys['doublezoomdown']}&amp;lng={$mapkeys['lng']}&amp;lat={$mapkeys['lat']}{$other}"  accesskey="#" ></a>
EOD;
		} else {
			$zoomdown = '';
		}
		
		$title = $title? '<h3>' . $this->func->htmlspecialchars($title) . '</h3>' : '';
		$ret = <<<EOD
<a href="{$maplink}&amp;zoom={$mapkeys['zoom']}&amp;lng={$mapkeys['lngdown']}&amp;lat={$mapkeys['latup']}{$other}"  accesskey="1" ></a>
<a href="{$maplink}&amp;zoom={$mapkeys['zoom']}&amp;lng={$mapkeys['lng']}&amp;lat={$mapkeys['latup']}{$other}"  accesskey="2" ></a>
<a href="{$maplink}&amp;zoom={$mapkeys['zoom']}&amp;lng={$mapkeys['lngup']}&amp;lat={$mapkeys['latup']}{$other}"  accesskey="3" ></a>
<a href="{$maplink}&amp;zoom={$mapkeys['zoom']}&amp;lng={$mapkeys['lngdown']}&amp;lat={$mapkeys['lat']}{$other}"  accesskey="4" ></a>
<a href="{$maplink}&amp;zoom={$mapkeys['zoom']}&amp;lng={$mapkeys['lngup']}&amp;lat={$mapkeys['lat']}{$other}"  accesskey="6" ></a>
<a href="{$maplink}&amp;zoom={$mapkeys['zoom']}&amp;lng={$mapkeys['lngdown']}&amp;lat={$mapkeys['latdown']}{$other}"  accesskey="7" ></a>
<a href="{$maplink}&amp;zoom={$mapkeys['zoom']}&amp;lng={$mapkeys['lng']}&amp;lat={$mapkeys['latdown']}{$other}"  accesskey="8" ></a>
<a href="{$maplink}&amp;zoom={$mapkeys['zoom']}&amp;lng={$mapkeys['lngup']}&amp;lat={$mapkeys['latdown']}{$other}"  accesskey="9" ></a>
{$zoomup}
{$zoomdown}
{$title}
<div style="text-align:center">
	<div class="gmap_smap"><a class="link2googlemap" href="{$google_link}"><img src="{$mymap}" {$this->conf['mapsize']} /></a></div>
	{$navi_tag}
	<br />
	<a href="{$google_link}">GoogleMap</a>
	<hr />
	{$back}
</div>

EOD;
		return $ret;
	}


	function latlnground($value){
		////////////////////////////////////////////////////////////
		// This part is based on GNAVI (http://xoops.iko-ze.net/) //
		////////////////////////////////////////////////////////////
		return round(floatval($value)*1000000)/1000000 ;
	}

	function plugin_gmap_getbool($val) {
		if ($val == false) return 0;
		if (!strcasecmp ($val, "false") ||
			!strcasecmp ($val, "none") ||
			!strcasecmp ($val, "no"))
			return 0;
		return 1;
	}
	
	function gmap_getpos($val) {
		if (! is_numeric($val)) {
			$val = strtoupper($val);
			if (in_array($val, array('TL','TC','TR','LT','RT','LC','RC','LB','RB','BL','BC','BR'))) {
				return $val;
			}
		}
		return $this->plugin_gmap_getbool($val);
	}
	
	// 	+----------------+
	// 	+ TL    TC    TR +
	// 	+ LT          RT +
	// 	+                +
	// 	+ LC          RC +
	// 	+                +
	// 	+ LB          RB +
	// 	+ BL    BC    BR +
	// 	+----------------+
	function gmap_get_pos_constant($val, $default = '') {
		static $pos = array(
				'T' => 'TOP',
				'B' => 'BOTTOM',
				'L' => 'LEFT',
				'C' => 'CENTER',
				'R' => 'RIGHT');
		
		if (!$default) {
			$default = 'RB';
		}
		if (is_numeric($val) || strlen($val) < 2 || !isset($pos[$val[0]]) || !isset($pos[$val[0]])) {
			$val = $default;
		}
		return 'google.maps.ControlPosition.'.$pos[$val[0]].'_'.$pos[$val[1]];
	}
	
	function plugin_gmap_addprefix($page, $name) {
		$page = $this->get_pgid($page);
		//if (!$page) $page = uniqid('r_');
		if ($name === $this->cont['PLUGIN_GMAP_DEF_MAPNAME']) {
			if (!isset($this->map_count[$page])) {
				$this->map_count[$page] = 0;
			}
			$this->map_count[$page]++;
			$name .= strval($this->map_count[$page]);
		}
		$this->lastmap_name = 'pukiwikigmap_'.$page.'_'.$name;
		return $this->lastmap_name;
	}

	function get_static_image_url($lat, $lng, $zoom, $markers = '', $useAction = 0, $title = '') {
		if ($useAction === 2) {
			if ($this->cont['UA_PROFILE'] === 'mobile') {
				if ($title) {
					$title = rawurlencode(mb_convert_encoding(' ('.$title.')', 'UTF-8', $this->cont['SOURCE_ENCODING']));
				}
				$url = 'https://maps.google.com/maps?q=loc:'.$lat.','.$lng.$title.'&z='.$zoom.'&iwloc=A';
			} else {
				$url = 'https://www.google.co.jp/m/local?site=local&ll='.$lat.','.$lng.'&z='.$zoom;
			}
		} else if ($useAction) {
			$url = $this->root->script . '?plugin=gmap&amp;action=static&amp;lat='.$lat.'&amp;lng='.$lng.'&amp;zoom='.$zoom.'&amp;refer='.rawurlencode(@ $_SERVER['REQUEST_URI']);
			if ($title) {
				$url .= '&amp;t='.rawurlencode($title);
			}
		} else {
			if ($this->cont['UA_PROFILE'] === 'keitai' && $zoom > 10) {
				$zoom = $zoom - 1;
			}
			$params = ($lng)? 'center='.$lat.','.$lng.'&amp;zoom='.$zoom.'&amp;' : $lat;
			$url = $this->google_staticmap_url.$params.'size='.$this->conf['StaticMapSize'].'&amp;type=mobile&amp;key='.$this->root->google_api_key;
		}
		if ($markers && $useAction < 2) {
			$url .= '&amp;markers=' . $this->func->htmlspecialchars($markers);
		}
		return $url;
	}

	function make_static_maps($lat, $lng, $zoom) {
		$markers = '__GOOGLE_MAPS_STATIC_MARKERS_' . $this->lastmap_name;
		$this->root->replaces_finish[$markers] = '';
		$params = '__GOOGLE_MAPS_STATIC_PARAMS_' . $this->lastmap_name;
		$_zoom = ($zoom > 10)? ($zoom - 1) : $zoom;
		$this->root->replaces_finish[$params] = 'center='.$lat.','.$lng.'&amp;zoom='.$_zoom.'&amp;';
		$imgurl = $this->get_static_image_url($params, '', 0, $markers);
		$img = '<div class="gmap_smap"><img class="img_margin" src="'.$imgurl.'" '.$this->conf['mapsize'].' /></div>';
		$map = '<br />[ <a href="'.$this->get_static_image_url($lat, $lng, $zoom, '__GOOGLE_MAPS_STATIC_MARKERS_' . $this->lastmap_name, 1).'">Map</a> | <a href="'.$this->get_static_image_url($lat, $lng, $zoom, '__GOOGLE_MAPS_STATIC_MARKERS_' . $this->lastmap_name, 2).'">Google</a> ]';
		return '<div style="text-align:center;">' . $img . $map . '</div>';
	}

	function plugin_gmap_output($doInit, $params, $display) {
		$this->root->rtf['disable_render_cache'] = true;

		$this->root->pagecache_profiles = $this->cont['PLUGIN_GMAP_PROFILE'];

		$defoptions = $this->plugin_gmap_get_default();

		$inoptions = array();
		$isSetZoom = false;
		$align = '';
		$around = false;
		foreach ($params as $param) {
			$pos = strpos($param, '=');
			if ($pos === false) {
				$param = strtolower(trim($param));
				if (in_array($param, array('left', 'right', 'center'))) {
					$align = $param;
				} else {
					if ($param === 'around') {
						$around = true;
					}
				}
				continue;
			}
			$index = trim(substr($param, 0, $pos));
			$value = $this->func->htmlspecialchars(trim(substr($param, $pos+1)), ENT_QUOTES);
			$inoptions[$index] = $value;
			if ($index == 'cx') {$cx = (float)$value;}//for old api
			if ($index == 'cy') {$cy = (float)$value;}//for old api
			if ($index == 'zoom') {$isSetZoom = true;}//for old api
		}

		if (array_key_exists('define', $inoptions)) {
			$this->root->vars['gmap'][$inoptions['define']] = $inoptions;
			return "";
		}

		$this->func->add_tag_head('gmap.css');

		$coptions = array();
		if (array_key_exists('class', $inoptions)) {
			$class = $inoptions['class'];
			if (array_key_exists($class, $this->root->vars['gmap'])) {
				$coptions = $this->root->vars['gmap'][$class];
			}
		}
		$options = array_merge($defoptions, $coptions, $inoptions);
		$mapname		= $this->plugin_gmap_addprefix($this->root->vars['page'], $options['mapname']);
		$key			= $options['key'];
		$width			= $options['width'];
		$height			= $options['height'];
		$lat			= (float)$options['lat'];
		$lng			= (float)$options['lng'];
		$zoom			= (integer)$options['zoom'];
		$type			= $options['type'];
		$mapctrl		= isset($options['mapctrl'])? $options['mapctrl'] : false; //非奨励
		$panctrl		= $options['panctrl'];
		$zoomctrl		= $options['zoomctrl'];
		$typectrl		= $options['typectrl'];
		$scalectrl		= $this->gmap_getpos($options['scalectrl']);
		$rotatectrl     = $this->plugin_gmap_getbool($options['rotatectrl']);
		$streetviewctrl = $this->plugin_gmap_getbool($options['streetviewctrl']);
		$overviewctrl	= $this->plugin_gmap_getbool($options['overviewctrl']);
		$crossctrl		= $this->plugin_gmap_getbool($options['crossctrl']);
		$searchctrl		= $this->gmap_getpos($options['searchctrl']);
		$dropmarker		= $this->plugin_gmap_getbool($options['dropmarker']);
		$togglemarker	= $this->plugin_gmap_getbool($options['togglemarker']);
		$googlebar		= 0; // $this->gmap_getpos($options['googlebar']); // サポート終了
		//$overviewwidth	= $options['overviewwidth']; // 廃止
		//$overviewheight = $options['overviewheight']; // 廃止
		$api			= isset($options['api'])? (integer)$options['api'] : 2; //非奨励
		$noiconname		= $options['noiconname'];
		$dbclickzoom	= $this->plugin_gmap_getbool($options['dbclickzoom']);
		$scrollwheel    = $this->plugin_gmap_getbool($options['scrollwheel']);
		$kml			= preg_replace("/&amp;/i", '&', ($options['kml']? $options['kml'] : (!empty($options['geoxml'])? $options['geoxml'] : '')));
		$importicon		= $options['importicon'];
		$backlinkmarker = $this->plugin_gmap_getbool($options['backlinkmarker']);
		$wikitag        = $options['wikitag'];
		$autozoom       = $this->plugin_gmap_getbool($options['autozoom']);
		$page = $this->get_pgid($this->root->vars['page']);
		//apiのチェック
		if ( ! (is_numeric($api) && $api >= 0 && $api <= 2) ) {
			 $api = 2;
		}
		$this->root->vars['gmap_info'][$mapname]['api'] = $api;
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

		if (!$this->plugin_gmap_is_supported_profile()) {
			//return "gmap:unsupported device";
			return $this->make_static_maps($lat, $lng, $zoom);
		}

		// width, heightの値チェック
		if (is_numeric($width)) { $width = (int)$width . "px"; }
		if (is_numeric($height)) { $height = (int)$height . "px"; }

		// Mapタイプの名前を正規化
		$type = $this->plugin_gmap_get_maptype($type);

		// 初期化処理の出力
		if ($doInit) {
			$output = $this->plugin_gmap_init_output($key);
		} else {
			$output = "";
		}
		$pukiwikiname = $options['mapname'];
		$output .= <<<EOD
<div id="$mapname" class="gmap_map" style="width: $width; height: $height;"></div>
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

<div class="gmap_tag_base" style="width: $width;">
<span id="{$mapname}_handle" class="gmap_handle" onclick="this.innerHTML = (this.innerHTML == '+')? '-' : '+';$('{$mapname}_info').toggle();">{$_icon}</span>
 {$this->msg['wikitag_thismap']}
<div id="{$mapname}_info" class="gmap_tag_info" style="width: $width;{$_display}">&nbsp;</div>
</div>
EOD;
		}

		// Make map options
		$mOptions = array();
		
		// Basic
		$mOptions[] = "center: {lat: $lat, lng: $lng}";
		$mOptions[] = "zoom: $zoom";
		$mOptions[] = "mapTypeId: google.maps.MapTypeId.$type";
		
		// Show Map Control/Zoom
		if ($mapctrl !== false) {
			switch($mapctrl) {
				case "small":
					$panctrl = 'normal';
					$zoomctrl = 'small';
					break;
				case "smallzoom":
					$panctrl = 'none';
					$zoomctrl = 'small';
					break;
				case "none":
					$panctrl = 'none';
					$zoomctrl = 'none';
					break;
				case "large":
					$panctrl = 'normal';
					$zoomctrl = 'large';
					break;
				default:
					break;
			}
		}
		
		// panControl
		if ($panctrl == 'none') {
			$mOptions[] = "panControl: false";
		}
		
		// zoomControl
		switch($zoom) {
			case 'small':
				$mOptions[] = "zoomControl: true";
				$mOptions[] = "zoomControlOptions: {style: google.maps.ZoomControlStyle.SMALL}";
				break;
			case 'large':
				$mOptions[] = "zoomControl: true";
				$mOptions[] = "zoomControlOptions: {style: google.maps.ZoomControlStyle.LARGE}";
				break;
			case 'none':
				$mOptions[] = "zoomControl: false";
			default:
				break;
		}
		
		// Scale
		//if ($scalectrl != "none") {
		if($scalectrl) {
			$mOptions[] = "scaleControl: true";
			$_def = '';
			if ($scalectrl === 1 && ($googlebar === 1 || $googlebar === 'BL')) {
				$_def = 'RB';
			}
			$_pos = $this->gmap_get_pos_constant($scalectrl, $_def);
			$mOptions[] = "scaleControlOptions: {position: {$_pos}}";
		}
		
		// Show Map Type Control and Center
		if ($typectrl == 'none') {
			$mOptions[] = "mapTypeControl: false";
		} else {
			$mOptions[] = "mapTypeControl: true";
			switch($typectrl[0]) {
				//horizontal
				case "h":
					$mOptions[] = "mapTypeControlOptions: { style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR }";
					break;
					//dropdown
				case "d":
					$mOptions[] = "mapTypeControlOptions: { style: google.maps.MapTypeControlStyle.DROPDOWN_MENU }";
					break;
					//normal(default)
				case "n":
				default:
					break;
			}
		}
		
		// rotateControl
		if ($rotatectrl) {
			$mOptions[] = "rotateControl: true";
		}
		
		// streetViewControl
		if (!$streetviewctrl) {
			$mOptions[] = "streetViewControl: false";
		}
		
		// OverviewMap
		if ($overviewctrl != "none") {
			// V3 ではサイズ指定ができない
			//$ovw = preg_replace("/(\d+).*"."/i", "\$1", $overviewwidth);
			//$ovh = preg_replace("/(\d+).*"."/i", "\$1", $overviewheight);
			//if ($ovw == "") $ovw = $this->cont['PLUGIN_GMAP_DEF_OVERVIEWWIDTH'];
			//if ($ovh == "") $ovh = $this->cont['PLUGIN_GMAP_DEF_OVERVIEWHEIGHT'];
				
			$mOptions[] = "overviewMapControl: true";
			$mOptions[] = "overviewMapControlOptions: {opened: ".(($overviewctrl == "hide")? 'false' : 'true')."}";
		}
		
		// Double click zoom
		if (! $dbclickzoom) {
			$mOptions[] = "disableDoubleClickZoom: true";
		}
		
		// scrollwheel zooming
		if (! $scrollwheel) {
			$mOptions[] = "scrollwheel: false";
		}
		
		// set map options
		$mOptions = "{" . join(",", $mOptions) . "}";
		
		// マップ作成
		$output .= <<<EOD

<script type="text/javascript">
//<![CDATA[
onloadfunc.push( function () {
	var map = new PGMap('$page', '$mapname', $mOptions);
EOD;

		// Auto Zoom
		if ($autozoom) {
			$output .= <<<EOD

	onloadfunc3.push( function () {
		p_gmap_auto_zoom("$page", "$mapname");
	});
EOD;
		}

		// Drop Marker
		if ($dropmarker) {
			$output .= "googlemaps_dropmarker['$page']['$mapname'] = new PGDropMarker(map, {title:'{$this->msg['drop_marker']}'})\n";
		}
		
		// センタークロスコントロール
		if ($crossctrl) {
			$output .= "googlemaps_crossctrl['$page']['$mapname'] = new PGCross(map);\n";
		}
		
		// 検索ボックスコントロール
		if ($searchctrl) {
			$_pos = $this->gmap_get_pos_constant($searchctrl, 'TC');
			$_opt = "{position: $_pos}";
			$output .= "var searchctrl = new PGSearch();\n";
			$output .= "searchctrl.initialize(map, $_opt);\n";
			$output .= "googlemaps_searchctrl['$page']['$mapname'] = searchctrl;\n";
		}
		
		// KML (Geo XML)
		if ($kml != "") {
			$ismatch = preg_match("/^https?:\/\/.*/", $kml, $matches);
			if (!$ismatch) {
				$ref =& $this->func->get_plugin_instance('ref');
				$pagename = $this->func->get_name_by_pgid($page);
				if (strpos($kml, '/') !== false) {
					$_page = preg_replace('#/[^/]+$#', '', $kml);
					$kml = basename($kml);
					$pagename = $this->func->get_fullname($_page, $pagename);
				}
				$kml = $ref->get_ref_url($pagename, $kml, false, true);
			} else {
				$kml = $this->func->htmlspecialchars($kml, ENT_QUOTES, $this->cont['SOURCE_ENCODING']);
			}
			$kml = str_replace('&amp;', '&', $kml);
			$output .= "var kmllayer = new google.maps.KmlLayer(\"$kml\");\n";
			$output .= "kmllayer.setMap(map);\n";
		}

		// GoogleBar
		if ($googlebar) {
			$this->func->add_js_head('https://www.google.com/uds/api?file=uds.js&amp;v=1');
			$this->func->add_tag_head('jGoogleBarV3.css');
			$this->func->add_tag_head('jGoogleBarV3.js');
			$output .= "var gbarOptions={searchFormOptions:{hintString:'{$this->msg['do_local_search']}',buttonText:'{$this->root->_LANG['skin']['search_s']}'}};\n";
			$output .= "var gbar=new window.jeremy.jGoogleBar(map,gbarOptions);\n";
			$_pos = $this->gmap_get_pos_constant($googlebar, 'BL');
			$output .= "map.controls[$_pos].push(gbar.container);\n";
		}

		// マーカーの表示非表示チェックボックス
		if ($togglemarker) {
			$output .= "onloadfunc2.push( function(){p_gmap_togglemarker_checkbox('$page', '$mapname', '$noiconname', '{$this->msg['default_icon_caption']}');} );";
		}

		// Map tag
		if ($wikitag !== 'none') {
			$keys = $defoptions;
			unset($keys['api'], $keys['key']);
			$keys['page'] = '';
			foreach(array_keys($keys) as $key) {
				$_options[] = $key . ':"' . str_replace('"', '\\"', (string)$$key) . '"';
			}
			$_options = join(',', $_options);
			$output .= <<<EOD
	var wikiOptions = {{$_options}};
	google.maps.event.addListener(googlemaps_maps['$page']['$mapname'], 'bounds_changed', function(){PGTool.setWikiTag(wikiOptions);});
	google.maps.event.addListener(googlemaps_maps['$page']['$mapname'], 'maptypeid_changed', function(){PGTool.setWikiTag(wikiOptions);});
	onloadfunc2.push(function () {
		if (googlemaps_dropmarker['$page']['$mapname']) {
			google.maps.event.addListener(googlemaps_dropmarker['$page']['$mapname'], 'position_changed', function(){PGTool.setWikiTag(wikiOptions);});
		}
	});
EOD;
		}

		// close script
		$output .= <<<EOD

}); // close onloadfunc
//]]>
</script>
EOD;

		// 指定されたPukiwikiページからアイコンを収集する
		if ($importicon != "") {
			$lines = $this->func->get_source($this->func->get_fullname($importicon, $this->root->vars['page']));
			foreach ($lines as $line) {
				$ismatch = preg_match('/gmap_icon\(([^()]+?)\)/i', $line, $matches);
				if ($ismatch) {
					//$output .= $this->func->convert_html("#" . $matches[0]) . "\n";
					$output .= $this->func->do_plugin_convert('gmap_icon', $matches[1] . ',basepage=' . $importicon);
				}
			}
		}

		// このページのバックリンクからマーカーを収集する。
		if ($backlinkmarker) {
			$links = $this->func->links_get_related_db($this->root->vars['page']);
			if (! empty($links)) {
				$output .= "<ul>\n";
				foreach(array_keys($links) as $page) {
					$ismatch = preg_match('/#gmap_mark\(([^, \)]+), *([^, \)]+)(.*?)\)/i',
					$this->func->get_source($page, TRUE, TRUE), $matches);
					if ($ismatch) {
						$markersource = "&gmap_mark(" .
						$matches[1] . "," . $matches[2] .
						", title=" . $page;
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

		// マーカーデフォルトアイコンを設定
		$output .= $this->func->do_plugin_convert('gmap_icon');
		
		$class = 'gmap';
		if ($align && $display === 'block') {
			if ($around && $align !== 'center') {
				$class .= ' float_' . $align;
			} else {
				$class .= ' block_' . $align;
			}
		}
		$class = ' class="'.$class.' margin_'.(($display === 'block')? '10' : '0').'"';
		$output = '<div style="display: '.$display.'; width: '.$width.';"'.$class.'>'. $output . '</div>';

		return $output;
	}

	function plugin_gmap_get_maptype($type) {
		switch (strtolower($type[0])) {
			case "s": $type = 'SATELLITE'; break;
			case "h": $type = 'HYBRID'   ; break;
			case "t":
			case "p": $type = 'TERRAIN'   ; break;
			case "r":
			case "n":
			default:  $type = 'ROADMAP'   ; break;
		}
		return $type;
	}

	function plugin_gmap_init_output($key) {
		if (floatval($this->conf['ApiVersion']) < 3) {
			$this->conf['ApiVersion'] = '3';
		}
		$this->func->add_js_head('https://maps.googleapis.com/maps/api/js?v='.$this->conf['ApiVersion'].'&amp;libraries=places&amp;key='.$key, true, 'UTF-8');
		$this->func->add_tag_head('gmap.js');
		return;
	}

	function get_pgid ($page) {
		$pgid = $this->func->get_pgid_by_name($page);
		if (!$pgid) $pgid = '0';
		return strval($pgid);
	}
}
?>