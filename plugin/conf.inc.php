<?php
/*
 * Created on 2008/01/24 by nao-pon http://hypweb.net/
 * $Id: conf.inc.php,v 1.2 2008/01/30 07:29:41 nao-pon Exp $
 */

class xpwiki_plugin_conf extends xpwiki_plugin {
	function plugin_conf_init() {
		$this->load_language();
		
		$this->conf = array(
			'PKWK_READONLY' => array(
				'kind' => 'const',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'function_freeze' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'adminpass' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'text,size="50"',
			),
			'html_head_title' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'text,size="50"',
			),
			'modifier' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'text',
			),
			'modifierlink' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'text,size="50"',
			),
			'notify' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'notify_diff_only' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'defaultpage' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'text',
			),
			'page_case_insensitive' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'SKIN_NAME' => array(
				'kind' => 'const',
				'type' => 'string',
				'form' => 'text',
			),
			'SKIN_CHANGER' => array(
				'kind' => 'const',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'referer' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'allow_pagecomment' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'nowikiname' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'pagename_num2str' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'static_url' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'link_target' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'text,size="7"',
			),
			'class_extlink' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'text,size="7"',
			),
			'nofollow_extlink' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'autolink' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'text,size="3"',
			),
			'autolink_omissible_upper' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'text,size="3"',
			),
			'autoalias' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'text,size="3"',
			),
			'autoalias_max_words' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'text,size="3"',
			),
			'plugin_follow_editauth' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'plugin_follow_freeze' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'fixed_heading_anchor_edit' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'paraedit_partarea' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'radio',
				'list' => array(
					$this->msg['paraedit_partarea']['compat'] => 'compat',
					$this->msg['paraedit_partarea']['level']  => 'level',
				),
			),
			'pagecache_min' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'text,size="3"',
			),
			'pre_width' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'text,size="6"',
			),
			'pre_width_ie' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'text,size="6"',
			),
			'pagereading_enable' => array(
				'kind' => 'root',
				'type' => 'integer',
				'form' => 'yesno',
			),
			'pagereading_kanji2kana_converter' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'radio',
				'list' => array(
					'ChaSen' => 'chasen',
					'KAKASI' => 'kakasi',
					'None'   => 'none',
				),
			),
			'pagereading_kanji2kana_encoding' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'radio',
				'list' => array(
					'EUC-JP'    => 'EUC',
					'Shift-JIS' => 'SJIS',
				),
			),
			'pagereading_chasen_path' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'text,size="40"',
			),
			'pagereading_kakasi_path' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'text,size="40"',
			),
			'pagereading_config_dict' => array(
				'kind' => 'root',
				'type' => 'string',
				'form' => 'text,size="40"',
			),

		);
	}
	
	function plugin_conf_action() {
		$mode = empty($this->root->post['pmode'])? '' : $this->root->post['pmode'];

		// 権限チェック
		if (!$this->root->userinfo['admin']) {
			$ret['msg'] = $this->msg['title_form'];
			$ret['body'] = $this->root->_msg_not_readable;
			return $ret;
		}

		// 管理画面モード指定
		if ($this->root->module['platform'] == "xoops") {
			$this->root->runmode = "xoops_admin";
		}
		
		switch($mode) {
			case 'post' :
				return $this->post_save();
				break;
			default :
				return $this->show_form();
		}
		return false;
	}
	
	function show_form() {
		$script = $this->func->get_script_uri();
		$msg_description = str_replace(array('$trust_ini_file', '$html_ini_file'), array($this->root->mytrustdirpath.'/ini/pukiwiki.ini.php',$this->root->mydirpath.'/private/ini/pukiwiki.ini.php'), $this->msg['msg_description']);
		$body =<<<EOD
<div>
<h2>{$this->msg['title_description']}</h2>
{$msg_description}
</div>
<hr />
<div>
<form action="{$script}" method="post">
<table>
EOD;
		foreach ($this->conf as $key => $conf) {
			$caption = ! empty($conf['caption'])? $conf['caption'] : (! empty($this->msg[$key]['caption'])? $this->msg[$key]['caption'] : $key);
			$description = ! empty($conf['description'])? $conf['description'] : (! empty($this->msg[$key]['description'])? $this->msg[$key]['description'] : '');
			$value = ($conf['kind'] === 'root')? $this->root->$key : $this->cont[$key];
			$value4disp = htmlspecialchars($value); 
			$name4disp = htmlspecialchars((($conf['kind'] === 'root')? 'root_' : 'const_') . $key);
			$real = htmlspecialchars(($conf['kind'] === 'root')? '$root->'.$key : '$const[\''.$key.'\']');
			$extention = ! empty($this->msg[$key]['extention'])? $this->msg[$key]['extention'] : '';
			list($form, $attr) = array_pad(explode(',', $conf['form'], 2), 2, '');
			switch ($form) {
				case 'yesno':
					$conf['list'] = array(
						$this->msg['Yes'] => 1,
						$this->msg['No'] => 0,
					);
				case 'radio':
					$forms = array();
					$i = 0;
					foreach($conf['list'] as $list_cap => $list_val) {
						if ($value == $list_val) {
							$checked = ' checked="checked"';
						} else {
							$checked = '';
						}
						$forms[] = '<input id="'.$name4disp.'_'.$i.'" type="radio" name="'.$name4disp.'" value="'.$list_val.'"'.$checked.' /><label for="'.$name4disp.'_'.$i.'">'.$list_cap.'</label>';
						$i++;
					}
					$form = join(' | ', $forms);
					break;
				case 'text':
				default:
					$style = '';
					if ($conf['type'] === 'integer') {
						$style = ' style="text-align:right;"';
					}
					$form = '<input type="text" name="'.$name4disp.'" value="'.$value4disp.'" '.$attr.$style.' />';
			}
			$body .= <<<EOD
<tr>
 <td style="font-weight:bold;padding-top:0.5em">$caption</td>
 <td style="padding-top:0.5em">{$form}{$extention}</td>
 <td style="padding-top:0.5em"><small>$real</small></td>
</tr>
<tr style="border-bottom:1px dotted gray;">
 <td colspan="3" style="padding-bottom:0.5em"><p>{$description}</p></td>
</tr>
EOD;
		}
		$body .= <<<EOD
<tr>
 <td>&nbsp;</td>
 <td><input type="submit" name="submit" value="{$this->msg['btn_submit']}" /></td>
</tr>
</table>
<input type="hidden" name="plugin" value="conf" />
<input type="hidden" name="pmode"  value="post" />
</form>
</div>
EOD;

		
		return array('msg'=>$this->msg['title_form'], 'body'=>$body);		
	}
	
	function post_save() {
		$lines = array();
		foreach ($this->root->post as $_key => $val) {
			list($kind, $key) = array_pad(explode('_', $_key, 2), 2, '');
			$line = $this->data_format($key, $val, $kind);
			if ($line) $lines[] = $line;
		}
		$data = join("\n", $lines);
		
		$this->func->save_config('pukiwiki.ini.php', 'conf', $data);
		
		$msg_done = str_replace('$cache_file', $this->cont['CACHE_DIR'].'pukiwiki.ini.php', $this->msg['msg_done']);
		$body = <<<EOD
<p>{$msg_done}</p>
<hr />
EOD;
		$body .= '<pre>'.htmlspecialchars($data).'</pre>';
		return array('msg'=>$this->msg['title_done'], 'body'=>$body);
	}
	
	function data_format($key, $val, $kind) {
		$is_int = false;
		$line = '';
		if (!isset($this->conf[$key])) return $line;
		
		switch($this->conf[$key]['type']){
			case 'integer' :
				$val = intval($val);
				break;
			case 'string' :
			default :
				$val = '\'' . str_replace('\'', '\\\'', strval($val)) . '\'';
		}
		if ($kind === 'const') {
			if (isset($this->cont[$key])) {
				$line = '$const[\''.$key.'\'] = '.$val.';';
			}
		} else if (isset($this->root->$key)) {
			$line = '$root->'.$key.' = '.$val.';';
		}
		return $line;
	}
}
?>