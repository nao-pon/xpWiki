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

class xpwiki_plugin_gmap_insertmarker extends xpwiki_plugin {
	function plugin_gmap_insertmarker_init () {

		// 言語ファイルの読み込み
		$this->load_language();

		$this->cont['PLUGIN_GMAP_INSERTMARKER_DIRECTION'] =  'down'; //追加していく方向(up, down)
		$this->cont['PLUGIN_GMAP_INSERTMARKER_TITLE_MAXLEN'] =  40; //タイトルの最長の長さ
		$this->cont['PLUGIN_GMAP_INSERTMARKER_CAPTION_MAXLEN'] =  400; //キャプションの最長の長さ
		$this->cont['PLUGIN_GMAP_INSERTMARKER_URL_MAXLEN'] =  1024; //URLの最長の長さ

	}

	function plugin_gmap_insertmarker_action() {

		if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits editing');

		if(is_numeric($this->root->vars['lat'])) $lat = $this->root->vars['lat']; else return;
		if(is_numeric($this->root->vars['lng'])) $lng = $this->root->vars['lng']; else return;
		if(is_numeric($this->root->vars['zoom'])) $zoom = $this->root->vars['zoom']; else return;
		if(is_string($this->root->vars['mtype'])) $mtype = $this->root->vars['mtype']; else return;
		
		if ($this->root->vars['plugin'] === 'googlemaps2_insertmarker') {
			$plugin = 'googlemaps2';
		} else {
			$plugin = 'gmap';
		}
		$gmap =& $this->func->get_plugin_instance('gmap');
		
		$maptypes = array('satellite', 'hybrid', 'physical', 'terrain');
		$mtypename = in_array($mtype, $maptypes)? $mtype : 'roadmap';

		$map    = htmlspecialchars(trim($this->root->vars['map']));
		$icon   = htmlspecialchars($this->root->vars['icon']);
		$flat   = isset($this->root->vars['flat'])? true : false;
		$title   = substr($this->root->vars['title'], 0, $this->cont['PLUGIN_GMAP_INSERTMARKER_TITLE_MAXLEN']);
		$caption = substr(trim($this->root->vars['caption']), 0, $this->cont['PLUGIN_GMAP_INSERTMARKER_CAPTION_MAXLEN']);
		$image   = substr($this->root->vars['image'], 0, $this->cont['PLUGIN_GMAP_INSERTMARKER_URL_MAXLEN']);

		$minzoom = $this->root->vars['minzoom'] == '' ? '' : (int)$this->root->vars['minzoom'];
		$maxzoom = $this->root->vars['maxzoom'] == '' ? '' : (int)$this->root->vars['maxzoom'];
		
		$save_zoom = (!empty($this->root->vars['save_zoom']));
		$save_mtype = (!empty($this->root->vars['save_mtype']));
		$save_addr = (!empty($this->root->vars['save_addr']));
		
		$caption .= ($save_addr)? ($caption? '&br;' : '') . $this->msg['cap_addr'] . ': ' . $this->root->vars['addr'] : '';

		$title   = htmlspecialchars(str_replace("\n", '', $title));
		$caption = str_replace("\n", '&br;', $caption);
		$image   = htmlspecialchars($image);
		$maxurl  = htmlspecialchars($maxurl);

		$marker = '-&'.$plugin.'_mark('.$lat.', '.$lng;
		if ($title)         $marker .= ', title='.$title;
		if ($map)           $marker .= ', map='.$map;
		//if ($caption != '') $marker .= ', caption='.$caption;
		if ($icon != '')    $marker .= ', icon='.$icon;
		if ($flat)          $marker .= ', flat=1';
		if ($image != '')   $marker .= ', image='.$image;
		if ($maxurl != '')  $marker .= ', maxurl='.$maxurl;
		if ($minzoom != '')  $marker .= ', minzoom='.$minzoom;
		if ($maxzoom != '')  $marker .= ', maxzoom='.$maxzoom;
		if ($save_zoom) $marker .= ', zoom='.$zoom;
		if ($save_mtype) $marker .= ', type='.$mtypename;
		$marker .= '){'.$caption.'};';

		$no       = 0;
		$postdata = '';
		$above    = ($this->root->vars['direction'] == 'up');

		$postdata_old = $this->func->get_source($this->root->vars['refer']);
		$this->func->escape_multiline_pre($postdata_old, TRUE);
		foreach ($postdata_old as $line) {
			if (! $above) $postdata .= $line;
			if (preg_match('/^#'.$plugin.'_insertmarker/i', $line) && $no++ == $this->root->vars['no']) {
				if ($above) {
					$postdata = rtrim($postdata) . "\n" . $marker . "\n";
				} else {
					$postdata = rtrim($postdata) . "\n" . $marker . "\n";
				}
			}
			if ($above) $postdata .= $line;
		}

		$title = $this->root->_title_updated;
		$body = '';
		if ($this->func->get_digests($this->func->get_source($this->root->vars['refer'], TRUE, TRUE)) != $this->root->vars['digest']) {
			$title = $this->root->_title_comment_collided;
			$body  = $this->root->_msg_comment_collided . $this->func->make_pagelink($this->root->vars['refer']);
		}

		$this->func->escape_multiline_pre($postdata, FALSE);
		$this->func->page_write($this->root->vars['refer'], $postdata);

		$retvars['msg']  = $title;
		$retvars['body'] = $body;
		$this->root->vars['page'] = $this->root->vars['refer'];

		//表示していたポジションを返すcookieを追加
		$cookieval = 'lat|'.$lat.'|lng|'.$lng.'|zoom|'.$zoom.'|mtype|'.$gmap->plugin_gmap_get_maptype($mtypename);
		$cookieval .= '|flat|' . ($flat? '1' : '0');
		$cookieval .= '|save_zoom|' . ($save_zoom? '1' : '0');
		$cookieval .= '|save_mtype|' . ($save_mtype? '1' : '0');
		$cookieval .= '|save_addr|' . ($save_addr? '1' : '0');
		if ($minzoom) $cookieval .= '|minzoom|'.$minzoom;
		if ($maxzoom) $cookieval .= '|maxzoom|'.$maxzoom;
		setcookie($this->root->vars['mapid'].'_i', $cookieval, 0, '/');
		return $retvars;
	}

	function plugin_gmap_insertmarker_get_default() {
	//	global $vars;
		return array(
			'map'       => $this->cont['PLUGIN_GMAP_DEF_MAPNAME'],
			'direction' => $this->cont['PLUGIN_GMAP_INSERTMARKER_DIRECTION']
		);
	}
	//inline型はテキストのパースがめんどくさそうなのでとりあえず放置。
	//function plugin_gmap_insertmarker_inline() {
	//	return $this->msg['err_noinline'] . "\n";
	//}
	function plugin_gmap_insertmarker_convert() {
		static $numbers = array();

		if (!isset($numbers[$this->xpwiki->pid])) {$numbers[$this->xpwiki->pid] = array();}

		$p_gmap =& $this->func->get_plugin_instance('gmap');

		if ($p_gmap->plugin_gmap_is_supported_profile() && !$p_gmap->lastmap_name) {
			return "gmap_insertmarker: {$p_gmap->msg['err_need_gmap']}";
		}

		if (!$p_gmap->plugin_gmap_is_supported_profile()) {
			return '';
		}

		if ($this->cont['PKWK_READONLY']) {
			return "read only<br>";
		}

		$this->msg['default_icon_caption'] = $p_gmap->msg['default_icon_caption'];

		//オプション

		$defoptions = $this->plugin_gmap_insertmarker_get_default();
		$inoptions = array();
		foreach (func_get_args() as $param) {
			$pos = strpos($param, '=');
			if ($pos == false) continue;
			$index = trim(substr($param, 0, $pos));
			$value = htmlspecialchars(trim(substr($param, $pos+1)), ENT_QUOTES);
			$inoptions[$index] = $value;
		}

		if (array_key_exists('define', $inoptions)) {
			$this->root->vars['gmap_insertmarker'][$inoptions['define']] = $inoptions;
			return '';
		}

		$this->func->add_tag_head('gmap.css');

		$coptions = array();
		if (array_key_exists('class', $inoptions)) {
			$class = $inoptions['class'];
			if (array_key_exists($class, $this->root->vars['gmap_insertmarker'])) {
				$coptions = $this->root->vars['gmap_icon'][$class];
			}
		}
		$options = array_merge($defoptions, $coptions, $inoptions);
		if ($options['map'] === $this->cont['PLUGIN_GMAP_DEF_MAPNAME']) {
			$map      = $p_gmap->lastmap_name;
			$mapname  = '';
		} else {
			$map      = $p_gmap->plugin_gmap_addprefix($this->root->vars['page'], $options['map']);
			$mapname  = $options['map'];//ユーザーに表示させるだけのマップ名（prefix除いた名前）
		}
		$direction = $options['direction'];
		$this->root->script    = $this->func->get_script_uri();
		$s_page    = htmlspecialchars($this->root->vars['page']);
		$page = $p_gmap->get_pgid($this->root->vars['page']);
		$plugin = end($this->root->plugin_stack);

		if (! isset($numbers[$this->xpwiki->pid][$page]))
			$numbers[$this->xpwiki->pid][$page] = 0;
		$no = $numbers[$this->xpwiki->pid][$page]++;

		$imprefix = "_p_gmap_insertmarker_".$page."_".$no;
		$script = $this->func->get_script_uri();
		$output = <<<EOD
<form action="{$script}" id="${imprefix}_form" method="post">
<div style="padding:2px;">
  <input type="hidden" name="plugin"    value="$plugin" />
  <input type="hidden" name="refer"     value="$s_page" />
  <input type="hidden" name="direction" value="$direction" />
  <input type="hidden" name="no"        value="$no" />
  <input type="hidden" name="digest"    value="{$this->root->digest}" />
  <input type="hidden" name="map"       value="$mapname" />
  <input type="hidden" name="mapid"     value="$map" />
  <input type="hidden" name="zoom"      value="10" id="${imprefix}_zoom"/>
  <input type="hidden" name="mtype"     value="0"  id="${imprefix}_mtype"/>

  {$this->msg['cap_lat']}: <input type="text" name="lat" id="${imprefix}_lat" size="10" />
  {$this->msg['cap_lng']}: <input type="text" name="lng" id="${imprefix}_lng" size="10" />
  {$this->msg['cap_title']}:
  <input type="text" name="title"    id="${imprefix}_title" size="20" />
  {$this->msg['cap_icon']}:
  <select name="icon" id ="${imprefix}_icon">
  <option value="Default">{$this->msg['default_icon_caption']}</option>
  </select>
  <input type="checkbox" name="flat" id="${imprefix}_flat" value="1" /> {$this->msg['cap_flat']}
  <div class="gmap_optional">
  {$this->msg['cap_image']}:
  <input type="text" name="image"    id="${imprefix}_image" size="20" />
  {$this->msg['cap_state']}:[
  <input type="checkbox" name="save_zoom" id="${imprefix}_save_zoom" value="1" checked="checked" /> {$this->msg['cap_zoom']}
  |
  <input type="checkbox" name="save_mtype" id="${imprefix}_save_mtype" value="1" checked="checked" /> {$this->msg['cap_type']}
  ]
  <br />
  {$this->msg['cap_marker']}:[ {$this->msg['cap_zoommin']}:
  <select name="minzoom" id ="${imprefix}_minzoom">
  <option value="">--</option>
  <option value="0"> 0</option> <option value="1"> 1</option>
  <option value="2"> 2</option> <option value="3"> 3</option>
  <option value="4"> 4</option> <option value="5"> 5</option>
  <option value="6"> 6</option> <option value="7"> 7</option>
  <option value="8"> 8</option> <option value="9"> 9</option>
  <option value="10">10</option> <option value="11">11</option>
  <option value="12">12</option> <option value="13">13</option>
  <option value="14">14</option> <option value="15">15</option>
  <option value="16">16</option> <option value="17">17</option>
  <option value="18">18</option> <option value="19">19</option>
  <option value="20">20</option> <option value="21">21</option>
  </select>
  |
  {$this->msg['cap_zoommax']}:
  <select name="maxzoom" id ="${imprefix}_maxzoom">
  <option value="">--</option>
  <option value="0"> 0</option> <option value="1"> 1</option>
  <option value="2"> 2</option> <option value="3"> 3</option>
  <option value="4"> 4</option> <option value="5"> 5</option>
  <option value="6"> 6</option> <option value="7"> 7</option>
  <option value="8"> 8</option> <option value="9"> 9</option>
  <option value="10">10</option> <option value="11">11</option>
  <option value="12">12</option> <option value="13">13</option>
  <option value="14">14</option> <option value="15">15</option>
  <option value="16">16</option> <option value="17">17</option>
  <option value="18">18</option> <option value="19">19</option>
  <option value="20">20</option> <option value="21">21</option>
  </select>
  ]
  <br />
  <input type="checkbox" name="save_addr" id="${imprefix}_save_addr" value="1" checked="checked" />{$this->msg['cap_addr']}: <input type="text" name="addr" id="${imprefix}_addr" size="50" />
  </div>
  {$this->msg['cap_note']}:
  <textarea id="{$imprefix}_textarea" name="caption" id="${imprefix}_caption" class="norich" rows="2" cols="55"></textarea>
  <input type="submit" name="Mark" value="{$this->msg['btn_mark']}" />
</div>
</form>

<script type="text/javascript">
//<![CDATA[
onloadfunc2.push(function() {
	new PGMAP_INSERTMARKER_FORM('$page', '$map', '$imprefix', '{$this->msg['err_map_notfind']}', '{$this->msg['err_irreg_dat']}');
});
//]]>
</script>
EOD;

		return $output;
	}
}
?>