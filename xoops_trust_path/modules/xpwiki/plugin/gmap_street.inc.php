<?php
/**
 * gmap_street.inc.php - Google Maps API V3 StreetView Map
 * 
 * @author nao-pon  http://xoops.hypweb.net/
 * 
 * * gmap_street.inc.php
 * 
 *  #gmap_street(width=[サイズ値], height=[サイズ値], streetlayer=[0|1], heading=[数値], pitch=[数値], zoom=[数値], markerzoom=[数値])
 * 
 *  &gmap_street(width=[数値], height=[数値], streetlayer=[0|1], heading=[数値], pitch=[数値], zoom=[数値], markerzoom=[数値]);
 * 
 * gmap で表示した地図にリンクしたストリートビューを表示するプラグイン。
 * 
 * 先に、リンクする地図を gmap プラグインで記述する必要があります。(直前の gmap の地図にリンクされます。)
 * 
 * ** プラグインオプション(すべて省略可能) カッコ[]内は初期値。
 * :width|       横幅 [400px]
 * :height|      縦幅 [400px]
 * :streetlayer| リンク地図にストリートビュー対象レイヤーを表示する [0]
 * :heading|     ストリートビューの方角 (0 - 360 or -180 - 180) [0]
 * :pitch|       ストリートビューの仰角 (-90 - 90) [0]
 * :zoom|        ストリートビューのズーム値 [1]
 * :markerzoom|  マーカークリックで自動ズームアップ時の最大値(0 - 21) 0:無効 [18]
 *
 */

class xpwiki_plugin_gmap_street extends xpwiki_plugin {
	function plugin_gmap_street_init () {
		$this->cont['PLUGIN_GMAP_STREET_DEF_WIDTH'] =       '400px'; //横幅
		$this->cont['PLUGIN_GMAP_STREET_DEF_HEIGHT'] =      '400px'; //縦幅
		$this->cont['PLUGIN_GMAP_STREET_DEF_STREETLAYER'] = 'none';  //ストリートビュー対象レイヤーを表示する
		$this->cont['PLUGIN_GMAP_STREET_DEF_HEADING'] =     0;       //ストリートビューの方角 (0 - 359)
		$this->cont['PLUGIN_GMAP_STREET_DEF_PITCH'] =       0;       //ストリートビューの仰角 (-90 - 90)
		$this->cont['PLUGIN_GMAP_STREET_DEF_ZOOM'] =        1;       //ストリートビューのズーム値
		$this->cont['PLUGIN_GMAP_STREET_DEF_MARKERZOOM'] =  18;      //マーカークリックで自動ズームアップ時の最大値(0 - 21) 0:無効
		$this->cont['PLUGIN_GMAP_STREET_DEF_LINKS']        = false;  //矢印のコントローラ(true, false)
		$this->cont['PLUGIN_GMAP_STREET_DEF_IMAGEDATE']    = true;   //画像の撮影日の表示
	}

	function get_default() {
		return array(
			'width'			 => $this->cont['PLUGIN_GMAP_STREET_DEF_WIDTH'],
			'height'		 => $this->cont['PLUGIN_GMAP_STREET_DEF_HEIGHT'],
			'streetlayer'	 => $this->cont['PLUGIN_GMAP_STREET_DEF_STREETLAYER'],
			'heading'		 => $this->cont['PLUGIN_GMAP_STREET_DEF_HEADING'],
			'pitch'			 => $this->cont['PLUGIN_GMAP_STREET_DEF_PITCH'],
			'zoom'			 => $this->cont['PLUGIN_GMAP_STREET_DEF_ZOOM'],
			'markerzoom'	 => $this->cont['PLUGIN_GMAP_STREET_DEF_MARKERZOOM'],
			'links'          => $this->cont['PLUGIN_GMAP_STREET_DEF_LINKS'],
			'imageDate'      => $this->cont['PLUGIN_GMAP_STREET_DEF_IMAGEDATE']
		);
	}

	function plugin_gmap_street_inline() {
		$args = func_get_args();
		return $this->get_body($args, 'inline-block');
	}
	
	function plugin_gmap_street_convert() {
		$args = func_get_args();
		return $this->get_body($args, 'block');
	}
	
	function get_body($params, $display) {

		$p_gmap =& $this->func->get_plugin_instance('gmap');

		if (!$p_gmap->plugin_gmap_is_supported_profile()) {
			return '';
		}

		if (! $mapname = $p_gmap->lastmap_name) {
			return "gmap_insertmarker: {$p_gmap->msg['err_need_gmap']}";
		}

		//オプション
		$defoptions = $this->get_default();
		$inoptions = array();
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
		}
		
		$options = array_merge($defoptions, $inoptions);

		$width = $options['width'];
		$height = $options['height'];
		$page = $p_gmap->get_pgid($this->root->vars['page']);
		$streetlayer = $p_gmap->plugin_gmap_getbool($options['streetlayer']);
		$heading = intval($options['heading']);
		$pitch = intval($options['pitch']);
		$zoom = intval($options['zoom']);
		$markerzoom = intval($options['markerzoom']);
		$links = $p_gmap->plugin_gmap_getbool($options['links'])? 'true' : 'false';
		$imageDate = $p_gmap->plugin_gmap_getbool($options['imageDate'])? 'true' : 'false';
		
		$optObj = array();
		
		$optObj[] = "streetlayer: $streetlayer";
		$optObj[] = "heading: $heading";
		$optObj[] = "pitch: $pitch";
		$optObj[] = "zoom: $zoom";
		$optObj[] = "markerzoom: $markerzoom";
		$optObj[] = "linksControl: $links";
		$optObj[] = "imageDateControl: $imageDate";

		$optObj = '{' . join(',' , $optObj) . '}';

		$output = <<<EOD

<div id="{$mapname}_street" class="gmap_streetview" style="width: $width; height: $height;"></div>
EOD;
		
		$output .= <<<EOD

<script type="text/javascript">
//<![CDATA[
onloadfunc.push(function(){
	PGStreet('$page', '$mapname', $optObj);
	var map = googlemaps_maps['$page']['$mapname'];
	if (!googlemaps_dropmarker['$page']['$mapname']) {
		map._onloadfunc.push(function(){PGTool.setCenterNearStreetViewPoint(map, null, false);});
	}
});
</script>
EOD;

		$class = 'gmap_street';
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
}
