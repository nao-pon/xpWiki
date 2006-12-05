<?php
class xpwiki_plugin_tracker extends xpwiki_plugin {
	function plugin_tracker_init () {


	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: tracker.inc.php,v 1.7 2006/12/05 23:55:54 nao-pon Exp $
	//
	// Issue tracker plugin (See Also bugtrack plugin)
	
	// tracker_listで表示しないページ名(正規表現で)
	// 'SubMenu'ページ および '/'を含むページを除外する
		$this->cont['TRACKER_LIST_EXCLUDE_PATTERN'] = '#^SubMenu$|/#';
	// 制限しない場合はこちら
	//define('TRACKER_LIST_EXCLUDE_PATTERN','#(?!)#');
	
	// 項目の取り出しに失敗したページを一覧に表示する
		$this->cont['TRACKER_LIST_SHOW_ERROR_PAGE'] = TRUE;
	/*
	function plugin_tracker_inline()
	{
		global $vars;
	
		if (PKWK_READONLY) return ''; // Show nothing
	
		$args = func_get_args();
		if (count($args) < 3)
		{
			return FALSE;
		}
		$body = array_pop($args);
		list($config_name,$field) = $args;
	
		$config = new XpWikiConfig($this->xpwiki, 'plugin/tracker/'.$config_name);
	
		if (!$config->read())
		{
			return "config file '".htmlspecialchars($config_name)."' not found.";
		}
	
		$config->config_name = $config_name;
	
		$fields = plugin_tracker_get_fields($vars['page'],$vars['page'],$config);
		$fields[$field]->default_value = $body;
		return $fields[$field]->get_tag();
	}
		*/

	}
	
	function plugin_tracker_convert()
	{
	//	global $script,$vars;
	
		if ($this->cont['PKWK_READONLY']) return ''; // Show nothing
	
		$base = $refer = $this->root->vars['page'];
	
		$config_name = 'default';
		$form = 'form';
		$options = array();
		if (func_num_args())
		{
			$args = func_get_args();
			switch (count($args))
			{
				case 3:
					$options = array_splice($args,2);
				case 2:
					$args[1] = $this->func->get_fullname($args[1],$base);
					$base = $this->func->is_pagename($args[1]) ? $args[1] : $base;
				case 1:
					$config_name = ($args[0] != '') ? $args[0] : $config_name;
					list($config_name,$form) = array_pad(explode('/',$config_name,2),2,$form);
			}
		}
	
		$config = new XpWikiConfig($this->xpwiki, 'plugin/tracker/'.$config_name);
	
		if (!$config->read())
		{
			return "<p>config file '".htmlspecialchars($config_name)."' not found.</p>";
		}
	
		$config->config_name = $config_name;
	
		$fields = $this->plugin_tracker_get_fields($base,$refer,$config);
	
		$form = $config->page.'/'.$form;
		if (!$this->func->is_page($form))
		{
			return "<p>config file '".$this->func->make_pagelink($form)."' not found.</p>";
		}
		$retval = $this->func->convert_html($this->plugin_tracker_get_source($form));
		$hiddens = '';
	
		foreach (array_keys($fields) as $name)
		{
			$replace = $fields[$name]->get_tag();
			if (is_a($fields[$name],'XpWikiTracker_field_hidden'))
			{
				$hiddens .= $replace;
				$replace = '';
			}
			$retval = str_replace("[$name]",$replace,$retval);
		}
		return <<<EOD
<form enctype="multipart/form-data" action="{$this->root->script}" method="post">
<div>
$retval
$hiddens
</div>
</form>
EOD;
	}
	function plugin_tracker_action()
	{
	//	global $post, $vars, $now;
	
		if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits editing');
	
		$config_name = array_key_exists('_config',$this->root->post) ? $this->root->post['_config'] : '';
	
		$config = new XpWikiConfig($this->xpwiki, 'plugin/tracker/'.$config_name);
		if (!$config->read())
		{
			return "<p>config file '".htmlspecialchars($config_name)."' not found.</p>";
		}
		$config->config_name = $config_name;
		$source = $config->page.'/page';
	
		$refer = array_key_exists('_refer',$this->root->post) ? $this->root->post['_refer'] : $this->root->post['_base'];
	
		if (!$this->func->is_pagename($refer))
		{
			return array(
				'msg'=>'cannot write',
			'body'=>'page name ('.htmlspecialchars($refer).') is not valid.'
		);
		}
		if (!$this->func->is_page($source))
		{
			return array(
				'msg'=>'cannot write',
			'body'=>'page template ('.htmlspecialchars($source).') is not exist.'
		);
		}
		// ページ名を決定
		$base = $this->root->post['_base'];
		$num = 0;
		$name = (array_key_exists('_name',$this->root->post)) ? $this->root->post['_name'] : '';
		if (array_key_exists('_page',$this->root->post))
		{
			$page = $real = $this->root->post['_page'];
		}
		else
		{
			$real = $this->func->is_pagename($name) ? $name : ++$num;
			$page = $this->func->get_fullname('./'.$real,$base);
		}
		if (!$this->func->is_pagename($page))
		{
			$page = $base;
		}
	
		while ($this->func->is_page($page))
		{
			$real = ++$num;
			$page = "$base/$real";
		}
		// ページデータを生成
		$postdata = $this->plugin_tracker_get_source($source);
	
		// 規定のデータ
		$_post = array_merge($this->root->post,$_FILES);
		$_post['_date'] = $this->root->now;
		$_post['_page'] = $page;
		$_post['_name'] = $name;
		$_post['_real'] = $real;
		// $_post['_refer'] = $_post['refer'];
	
		$fields = $this->plugin_tracker_get_fields($page,$refer,$config);
	
		// Creating an empty page, before attaching files
		touch($this->func->get_filename($page));
	
		foreach (array_keys($fields) as $key)
		{
			$value = array_key_exists($key,$_post) ?
				$fields[$key]->format_value($_post[$key]) : '';
	
			foreach (array_keys($postdata) as $num)
			{
				if (trim($postdata[$num]) == '')
				{
					continue;
				}
				$postdata[$num] = str_replace(
					"[$key]",
				($postdata[$num]{0} == '|' or $postdata[$num]{0} == ':') ?
						str_replace('|','&#x7c;',$value) : $value,
				$postdata[$num]
				);
			}
		}
	
		// Writing page data, without touch
		$this->func->page_write($page, join('', $postdata));
	
		$r_page = rawurlencode($page);
	
		$this->func->pkwk_headers_sent();
		header('Location: ' . $this->func->get_script_uri() . '?' . $r_page);
		exit;
	}
	// フィールドオブジェクトを構築する
	function plugin_tracker_get_fields($base,$refer,&$config)
	{
	//	global $now,$_tracker_messages;
	
		$fields = array();
		// 予約語
		foreach (array(
			'_date'=>'text',    // 投稿日時
			'_update'=>'date',  // 最終更新
			'_past'=>'past',    // 経過(passage)
			'_page'=>'page',    // ページ名
			'_name'=>'text',    // 指定されたページ名
			'_real'=>'real',    // 実際のページ名
			'_refer'=>'page',   // 参照元(フォームのあるページ)
			'_base'=>'page',    // 基準ページ
			'_submit'=>'submit' // 追加ボタン
			) as $field=>$class)
		{
			$class = 'XpWikiTracker_field_'.$class;
			$fields[$field] = &new $class($this->xpwiki, array($field,$this->root->_tracker_messages["btn$field"],'','20',''),$base,$refer,$config);
		}
	
		foreach ($config->get('fields') as $field)
		{
			// 0=>項目名 1=>見出し 2=>形式 3=>オプション 4=>デフォルト値
			//echo $field[2]."<br>";
			$class = 'XpWikiTracker_field_'.$field[2];
			if (!class_exists($class))
			{ // デフォルト
				$class = 'XpWikiTracker_field_text';
				$field[2] = 'text';
				$field[3] = '20';
			}
			$fields[$field[0]] = &new $class($this->xpwiki, $field,$base,$refer,$config);
		}
		return $fields;
	}
	///////////////////////////////////////////////////////////////////////////
	// 一覧表示
	function plugin_tracker_list_convert()
	{
	//	global $vars;
	
		$config = 'default';
		$page = $refer = $this->root->vars['page'];
		$field = '_page';
		$order = '';
		$list = 'list';
		$limit = NULL;
		if (func_num_args())
		{
			$args = func_get_args();
			switch (count($args))
			{
				case 4:
					$limit = is_numeric($args[3]) ? $args[3] : $limit;
				case 3:
					$order = $args[2];
				case 2:
					$args[1] = $this->func->get_fullname($args[1],$page);
					$page = $this->func->is_pagename($args[1]) ? $args[1] : $page;
				case 1:
					$config = ($args[0] != '') ? $args[0] : $config;
					list($config,$list) = array_pad(explode('/',$config,2),2,$list);
			}
		}
		return $this->plugin_tracker_getlist($page,$refer,$config,$list,$order,$limit);
	}
	function plugin_tracker_list_action()
	{
	//	global $script,$vars,$_tracker_messages;
	
		$page = $refer = $this->root->vars['refer'];
		$s_page = $this->func->make_pagelink($page);
		$config = $this->root->vars['config'];
		$list = array_key_exists('list',$this->root->vars) ? $this->root->vars['list'] : 'list';
		$order = array_key_exists('order',$this->root->vars) ? $this->root->vars['order'] : '_real:SORT_DESC';
	
		return array(
			'msg' => $this->root->_tracker_messages['msg_list'],
		'body'=> str_replace('$1',$s_page,$this->root->_tracker_messages['msg_back']).
			$this->plugin_tracker_getlist($page,$refer,$config,$list,$order)
		);
	}
	function plugin_tracker_getlist($page,$refer,$config_name,$list,$order='',$limit=NULL)
	{
		$config = new XpWikiConfig($this->xpwiki, 'plugin/tracker/'.$config_name);
	
		if (!$config->read())
		{
			return "<p>config file '".htmlspecialchars($config_name)."' is not exist.";
		}
	
		$config->config_name = $config_name;
	
		if (!$this->func->is_page($config->page.'/'.$list))
		{
			return "<p>config file '".$this->func->make_pagelink($config->page.'/'.$list)."' not found.</p>";
		}
	
		$list = &new XpWikiTracker_list($this->xpwiki, $page,$refer,$config,$list);
		$list->sort($order);
		return $list->toString($limit);
	}
	function plugin_tracker_get_source($page)
	{
		$source = $this->func->get_source($page);
		// 見出しの固有ID部を削除
		$source = preg_replace('/^(\*{1,6}.*)\[#[A-Za-z][\w-]+\](.*)$/m','$1$2',$source);
		// #freeze #info を削除
		return $this->func->remove_pginfo(preg_replace('/^#freeze\s*$/im', '', $source));
	}
}
	// フィールドクラス
class XpWikiTracker_field
{
	var $name;
	var $title;
	var $values;
	var $default_value;
	var $page;
	var $refer;
	var $config;
	var $data;
	var $sort_type = SORT_REGULAR;
	var $id = 0;

	function XpWikiTracker_field(& $xpwiki, $field,$page,$refer,&$config)
	{
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;
//		global $post;
//		static $id = 0;
			static $id = array();
			if (!isset($id[$this->xpwiki->pid])) {$id[$this->xpwiki->pid] = 0;}

		$this->id = ++$id[$this->xpwiki->pid];
		$this->name = $field[0];
		$this->title = $field[1];
		$this->values = explode(',',$field[3]);
		$this->default_value = $field[4];
		$this->page = $page;
		$this->refer = $refer;
		$this->config = &$config;
		$this->data = array_key_exists($this->name,$this->root->post) ? $this->root->post[$this->name] : '';
	}
	function get_tag()
	{
	}
	function get_style($str)
	{
		return '%s';
	}
	function format_value($value)
	{
		return $value;
	}
	function format_cell($str)
	{
		return $str;
	}
	function get_value($value)
	{
		return $value;
	}
}
class XpWikiTracker_field_text extends XpWikiTracker_field
{
	var $sort_type = SORT_STRING;

	function get_tag()
	{
		$s_name = htmlspecialchars($this->name);
		$s_size = htmlspecialchars($this->values[0]);
		if ($this->default_value == '$uname' || $this->default_value == '$X_uname' ) {
			$this->default_value = $this->cont['USER_NAME_REPLACE'];
		}
		$s_value = htmlspecialchars($this->default_value);
		$helper = ($this->name == "_name" || is_a($this, "XpWikiTracker_field_page"))? "" : " rel=\"wikihelper\"";
		return "<input type=\"text\" name=\"$s_name\"{$helper} size=\"$s_size\" value=\"$s_value\" />";
	}
}
class XpWikiTracker_field_page extends XpWikiTracker_field_text
{
	var $sort_type = SORT_STRING;

	function format_value($value)
	{
//		global $WikiName;
		
		$value = $this->func->strip_bracket($value);

		if ($this->default_value == '$uname' || $this->default_value == '$X_uname' ) {
			// save name to cookie
			if ($value) { $this->func->save_name2cookie($value); }
		}

		if ($this->func->is_pagename($value))
		{
			$value = "[[$value]]";
		}
		return parent::format_value($value);
	}
}
class XpWikiTracker_field_real extends XpWikiTracker_field_text
{
	var $sort_type = SORT_REGULAR;
}
class XpWikiTracker_field_title extends XpWikiTracker_field_text
{
	var $sort_type = SORT_STRING;

	function format_cell($str)
	{
		$this->func->make_heading($str);
		return $str;
	}
}
class XpWikiTracker_field_textarea extends XpWikiTracker_field
{
	var $sort_type = SORT_STRING;

	function get_tag()
	{
		$s_name = htmlspecialchars($this->name);
		$s_cols = htmlspecialchars($this->values[0]);
		$s_rows = htmlspecialchars($this->values[1]);
		$s_value = htmlspecialchars($this->default_value);
		return "<textarea name=\"$s_name\" rel=\"wikihelper\" cols=\"$s_cols\" rows=\"$s_rows\">$s_value</textarea>";
	}
	function format_cell($str)
	{
		$str = preg_replace('/[\r\n]+/','',$str);
		if (!empty($this->values[2]) and strlen($str) > ($this->values[2] + 3))
		{
			$str = mb_substr($str,0,$this->values[2]).'...';
		}
		return $str;
	}
}
class XpWikiTracker_field_format extends XpWikiTracker_field
{
	var $sort_type = SORT_STRING;

	var $styles = array();
	var $formats = array();

	function XpWikiTracker_field_format(& $xpwiki, $field,$page,$refer,&$config)
	{
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;
		parent::XpWikiTracker_field($xpwiki,$field,$page,$refer,$config);

		foreach ($this->config->get($this->name) as $option)
		{
			list($key,$style,$format) = array_pad(array_map(create_function('$a','return trim($a);'),$option),3,'');
			if ($style != '')
			{
				$this->styles[$key] = $style;
			}
			if ($format != '')
			{
				$this->formats[$key] = $format;
			}
		}
	}
	function get_tag()
	{
		$s_name = htmlspecialchars($this->name);
		$s_size = htmlspecialchars($this->values[0]);
		return "<input type=\"text\" name=\"$s_name\" size=\"$s_size\" />";
	}
	function get_key($str)
	{
		return ($str == '') ? 'IS NULL' : 'IS NOT NULL';
	}
	function format_value($str)
	{
		if (is_array($str))
		{
			return join(', ',array_map(array($this,'format_value'),$str));
		}
		$key = $this->get_key($str);
		return array_key_exists($key,$this->formats) ? str_replace('%s',$str,$this->formats[$key]) : $str;
	}
	function get_style($str)
	{
		$key = $this->get_key($str);
		return array_key_exists($key,$this->styles) ? $this->styles[$key] : '%s';
	}
}
class XpWikiTracker_field_file extends XpWikiTracker_field_format
{
	var $sort_type = SORT_STRING;

	function get_tag()
	{
		$s_name = htmlspecialchars($this->name);
		$s_size = htmlspecialchars($this->values[0]);
		return "<input type=\"file\" name=\"$s_name\" size=\"$s_size\" />";
	}
	function format_value($str)
	{
		if (array_key_exists($this->name,$_FILES))
		{
			require_once($this->cont['PLUGIN_DIR'].'attach.inc.php');
			$result = $this->func->attach_upload($_FILES[$this->name],$this->page);
			if ($result['result']) // アップロード成功
			{
				return parent::format_value($this->page.'/'.$_FILES[$this->name]['name']);
			}
		}
		// ファイルが指定されていないか、アップロードに失敗
		return parent::format_value('');
	}
}
class XpWikiTracker_field_radio extends XpWikiTracker_field_format
{
	var $sort_type = SORT_NUMERIC;

	function get_tag()
	{
		$s_name = htmlspecialchars($this->name);
		$retval = '';
		$id = 0;
		foreach ($this->config->get($this->name) as $option)
		{
			$s_option = htmlspecialchars($option[0]);
			$checked = trim($option[0]) == trim($this->default_value) ? ' checked="checked"' : '';
			++$id;
			$s_id = '_p_tracker_' . $s_name . '_' . $this->id . '_' . $id;
			$retval .= '<input type="radio" name="' .  $s_name . '" id="' . $s_id .
				'" value="' . $s_option . '"' . $checked . ' />' .
				'<label for="' . $s_id . '">' . $s_option . '</label>' . "\n";
		}

		return $retval;
	}
	function get_key($str)
	{
		return $str;
	}
	function get_value($value)
	{
//		static $options = array();
			static $options = array();
			if (!isset($options[$this->xpwiki->pid])) {$options[$this->xpwiki->pid] = array();}
		if (!array_key_exists($this->name,$options[$this->xpwiki->pid]))
		{
			$options[$this->xpwiki->pid][$this->name] = array_flip(array_map(create_function('$arr','return $arr[0];'),$this->config->get($this->name)));
		}
		return array_key_exists($value,$options[$this->xpwiki->pid][$this->name]) ? $options[$this->xpwiki->pid][$this->name][$value] : $value;
	}
}
class XpWikiTracker_field_select extends XpWikiTracker_field_radio
{
	var $sort_type = SORT_NUMERIC;

	function get_tag($empty=FALSE)
	{
		$s_name = htmlspecialchars($this->name);
		$s_size = (array_key_exists(0,$this->values) and is_numeric($this->values[0])) ?
			' size="'.htmlspecialchars($this->values[0]).'"' : '';
		$s_multiple = (array_key_exists(1,$this->values) and strtolower($this->values[1]) == 'multiple') ?
			' multiple="multiple"' : '';
		$retval = "<select name=\"{$s_name}[]\"$s_size$s_multiple>\n";
		if ($empty)
		{
			$retval .= " <option value=\"\"></option>\n";
		}
		$defaults = array_flip(preg_split('/\s*,\s*/',$this->default_value,-1,PREG_SPLIT_NO_EMPTY));
		foreach ($this->config->get($this->name) as $option)
		{
			$s_option = htmlspecialchars($option[0]);
			$selected = array_key_exists(trim($option[0]),$defaults) ? ' selected="selected"' : '';
			$retval .= " <option value=\"$s_option\"$selected>$s_option</option>\n";
		}
		$retval .= "</select>";

		return $retval;
	}
}
class XpWikiTracker_field_checkbox extends XpWikiTracker_field_radio
{
	var $sort_type = SORT_NUMERIC;

	function get_tag($empty=FALSE)
	{
		$s_name = htmlspecialchars($this->name);
		$defaults = array_flip(preg_split('/\s*,\s*/',$this->default_value,-1,PREG_SPLIT_NO_EMPTY));
		$retval = '';
		$id = 0;
		foreach ($this->config->get($this->name) as $option)
		{
			$s_option = htmlspecialchars($option[0]);
			$checked = array_key_exists(trim($option[0]),$defaults) ?
				' checked="checked"' : '';
			++$id;
			$s_id = '_p_tracker_' . $s_name . '_' . $this->id . '_' . $id;
			$retval .= '<input type="checkbox" name="' . $s_name .
				'[]" id="' . $s_id . '" value="' . $s_option . '"' . $checked . ' />' .
				'<label for="' . $s_id . '">' . $s_option . '</label>' . "\n";
		}

		return $retval;
	}
}
class XpWikiTracker_field_hidden extends XpWikiTracker_field_radio
{
	var $sort_type = SORT_NUMERIC;

	function get_tag($empty=FALSE)
	{
		$s_name = htmlspecialchars($this->name);
		$s_default = htmlspecialchars($this->default_value);
		$retval = "<input type=\"hidden\" name=\"$s_name\" value=\"$s_default\" />\n";

		return $retval;
	}
}
class XpWikiTracker_field_submit extends XpWikiTracker_field
{
	function get_tag()
	{
		$s_title = htmlspecialchars($this->title);
		$s_page = htmlspecialchars($this->page);
		$s_refer = htmlspecialchars($this->refer);
		$s_config = htmlspecialchars($this->config->config_name);

		return <<<EOD
<input type="submit" value="$s_title" />
<input type="hidden" name="plugin" value="tracker" />
<input type="hidden" name="_refer" value="$s_refer" />
<input type="hidden" name="_base" value="$s_page" />
<input type="hidden" name="_config" value="$s_config" />
EOD;
	}
}
class XpWikiTracker_field_date extends XpWikiTracker_field
{
	var $sort_type = SORT_NUMERIC;

	function format_cell($timestamp)
	{
		return $this->func->format_date($timestamp);
	}
}
class XpWikiTracker_field_past extends XpWikiTracker_field
{
	var $sort_type = SORT_NUMERIC;

	function format_cell($timestamp)
	{
		return $this->func->get_passage($timestamp,FALSE);
	}
	function get_value($value)
	{
		return $this->cont['UTIME'] - $value;
	}
}
	
	// 一覧クラス
class XpWikiTracker_list
{
	var $page;
	var $config;
	var $list;
	var $fields;
	var $pattern;
	var $pattern_fields;
	var $rows;
	var $order;

	function XpWikiTracker_list(& $xpwiki, $page,$refer,&$config,$list)
	{
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;
		$this->page = $page;
		$this->config = &$config;
		$this->list = $list;
		$this->fields = xpwiki_plugin_tracker::plugin_tracker_get_fields($page,$refer,$config);

		$pattern = join('',xpwiki_plugin_tracker::plugin_tracker_get_source($config->page.'/page'));
		// ブロックプラグインをフィールドに置換
		// #commentなどで前後に文字列の増減があった場合に、[_block_xxx]に吸い込ませるようにする
		$pattern = preg_replace('/^\#([^\(\s]+)(?:\((.*)\))?\s*$/m','[_block_$1]',$pattern);

		// パターンを生成
		$this->pattern = '';
		$this->pattern_fields = array();
		$pattern = preg_split('/\\\\\[(\w+)\\\\\]/',preg_quote($pattern,'/'),-1,PREG_SPLIT_DELIM_CAPTURE);
		while (count($pattern))
		{
			$this->pattern .= preg_replace('/\s+/','\\s*','(?>\\s*'.trim(array_shift($pattern)).'\\s*)');
			if (count($pattern))
			{
				$field = array_shift($pattern);
				$this->pattern_fields[] = $field;
				$this->pattern .= '(.*)';
			}
		}
		// ページの列挙と取り込み
		$this->rows = array();
		$pattern = "$page/";
		$pattern_len = strlen($pattern);
		foreach ($this->func->get_existpages(FALSE, $pattern) as $_page)
		{
			//if (strpos($_page,$pattern) === 0)
			//{
				$name = substr($_page,$pattern_len);
				if (preg_match($this->cont['TRACKER_LIST_EXCLUDE_PATTERN'],$name))
				{
					continue;
				}
				$this->add($_page,$name);
			//}
		}
	}
	function add($page,$name)
	{
//		static $moved = array();
			static $moved = array();
			if (!isset($moved[$this->xpwiki->pid])) {$moved[$this->xpwiki->pid] = array();}

		// 無限ループ防止
		if (array_key_exists($name,$this->rows))
		{
			return;
		}

		$source = xpwiki_plugin_tracker::plugin_tracker_get_source($page);
		if (preg_match('/move\sto\s(.+)/',$source[0],$matches))
		{
			$page = $this->func->strip_bracket(trim($matches[1]));
			if (array_key_exists($page,$moved[$this->xpwiki->pid]) or !$this->func->is_page($page))
			{
				return;
			}
			$moved[$this->xpwiki->pid][$page] = TRUE;
			return $this->add($page,$name);
		}
		$source = join('',preg_replace('/^(\*{1,6}.*)\[#[A-Za-z][\w-]+\](.*)$/','$1$2',$source));

		// デフォルト値
		$this->rows[$name] = array(
			'_page'  => "[[$page]]",
			'_refer' => $this->page,
			'_real'  => $name,
			'_update'=> $this->func->get_filetime($page),
			'_past'  => $this->func->get_filetime($page)
		);
		if ($this->rows[$name]['_match'] = preg_match("/{$this->pattern}/s",$source,$matches))
		{
			array_shift($matches);
			foreach ($this->pattern_fields as $key=>$field)
			{
				$this->rows[$name][$field] = trim($matches[$key]);
			}
		}
	}
	function sort($order)
	{
		if ($order == '')
		{
			return;
		}
		$names = array_flip(array_keys($this->fields));
		$this->order = array();
		foreach (explode(';',$order) as $item)
		{
			list($key,$dir) = array_pad(explode(':',$item),1,'ASC');
			if (!array_key_exists($key,$names))
			{
				continue;
			}
			switch (strtoupper($dir))
			{
				case 'SORT_ASC':
				case 'ASC':
				case SORT_ASC:
					$dir = SORT_ASC;
					break;
				case 'SORT_DESC':
				case 'DESC':
				case SORT_DESC:
					$dir = SORT_DESC;
					break;
				default:
					continue;
			}
			$this->order[$key] = $dir;
		}
		$keys = array();
		$params = array();
		foreach ($this->order as $field=>$order)
		{
			if (!array_key_exists($field,$names))
			{
				continue;
			}
			foreach ($this->rows as $row)
			{
				$keys[$field][] = isset($row[$field])? $this->fields[$field]->get_value($row[$field]) : '';
			}
			$params[] = $keys[$field];
			$params[] = $this->fields[$field]->sort_type;
			$params[] = $order;

		}
		$params[] = &$this->rows;

		call_user_func_array('array_multisort',$params);
	}
	function replace_item($arr)
	{
		$params = explode(',',$arr[1]);
		$name = array_shift($params);
		if ($name == '')
		{
			$str = '';
		}
		else if (array_key_exists($name,$this->items))
		{
			$str = $this->items[$name];
			if (array_key_exists($name,$this->fields))
			{
				$str = $this->fields[$name]->format_cell($str);
			}
		}
		else
		{
			return $this->pipe ? str_replace('|','&#x7c;',$arr[0]) : $arr[0];
		}
		$style = count($params) ? $params[0] : $name;
		if (array_key_exists($style,$this->items)
			and array_key_exists($style,$this->fields))
		{
			$str = sprintf($this->fields[$style]->get_style($this->items[$style]),$str);
		}
		return $this->pipe ? str_replace('|','&#x7c;',$str) : $str;
	}
	function replace_title($arr)
	{
//		global $script;

		$field = $sort = $arr[1];
		if ($sort == '_name' or $sort == '_page')
		{
			$sort = '_real';
		}
		if (!array_key_exists($field,$this->fields))
		{
			return $arr[0];
		}
		$dir = SORT_ASC;
		$arrow = '';
		$order = $this->order;

		if (is_array($order) && isset($order[$sort]))
		{
			// BugTrack2/106: Only variables can be passed by reference from PHP 5.0.5
			$order_keys = array_keys($order); // with array_shift();

			$index = array_flip($order_keys);
			$pos = 1 + $index[$sort];
			$b_end = ($sort == array_shift($order_keys));
			$b_order = ($order[$sort] == SORT_ASC);
			$dir = ($b_end xor $b_order) ? SORT_ASC : SORT_DESC;
			$arrow = '&br;'.($b_order ? '&uarr;' : '&darr;')."($pos)";

			unset($order[$sort], $order_keys);
		}
		$title = $this->fields[$field]->title;
		$r_page = rawurlencode($this->page);
		$r_config = rawurlencode($this->config->config_name);
		$r_list = rawurlencode($this->list);
		$_order = array("$sort:$dir");
		if (is_array($order))
			foreach ($order as $key=>$value)
				$_order[] = "$key:$value";
		$r_order = rawurlencode(join(';',$_order));

		return "[[$title$arrow>{$this->root->script}?plugin=tracker_list&refer=$r_page&config=$r_config&list=$r_list&order=$r_order]]";
	}
	function toString($limit=NULL)
	{
//		global $_tracker_messages;

		$source = '';
		$body = array();

		if ($limit !== NULL and count($this->rows) > $limit)
		{
			$source = str_replace(
				array('$1','$2'),
				array(count($this->rows),$limit),
				$this->root->_tracker_messages['msg_limit'])."\n";
			$this->rows = array_splice($this->rows,0,$limit);
		}
		if (count($this->rows) == 0)
		{
			return '';
		}
		foreach (xpwiki_plugin_tracker::plugin_tracker_get_source($this->config->page.'/'.$this->list) as $line)
		{
			if (preg_match('/^\|(.+)\|[hHfFcC]$/',$line))
			{
				$source .= preg_replace_callback('/\[([^\[\]]+)\]/',array(&$this,'replace_title'),$line);
			}
			else
			{
				$body[] = $line;
			}
		}
		foreach ($this->rows as $key=>$row)
		{
			if (!$this->cont['TRACKER_LIST_SHOW_ERROR_PAGE'] and !$row['_match'])
			{
				continue;
			}
			$this->items = $row;
			foreach ($body as $line)
			{
				if (trim($line) == '')
				{
					$source .= $line;
					continue;
				}
				$this->pipe = ($line{0} == '|' or $line{0} == ':');
				$source .= preg_replace_callback('/\[([^\[\]]+)\]/',array(&$this,'replace_item'),$line);
			}
		}
		return $this->func->convert_html($source);
	}
}
?>