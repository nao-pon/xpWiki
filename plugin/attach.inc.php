<?php
class xpwiki_plugin_attach extends xpwiki_plugin {
	function plugin_attach_init () {


	// PukiWiki - Yet another WikiWikiWeb clone
	// $Id: attach.inc.php,v 1.1 2006/10/13 13:17:49 nao-pon Exp $
	// Copyright (C)
	//   2003-2006 PukiWiki Developers Team
	//   2002-2003 PANDA <panda@arino.jp> http://home.arino.jp/
	//   2002      Y.MASUI <masui@hisec.co.jp> http://masui.net/pukiwiki/
	//   2001-2002 Originally written by yu-ji
	// License: GPL v2 or (at your option) any later version
	//
	// File attach plugin
	
	// NOTE (PHP > 4.2.3):
	//    This feature is disabled at newer version of PHP.
	//    Set this at php.ini if you want.
	// Max file size for upload on PHP (PHP default: 2MB)
		ini_set('upload_max_filesize', '2M');
	
	// Max file size for upload on script of PukiWikiX_FILESIZE
		$this->cont['PLUGIN_ATTACH_MAX_FILESIZE'] =  (1024 * 1024); // default: 1MB
	
	// 管理者だけが添付ファイルをアップロードできるようにする
		$this->cont['PLUGIN_ATTACH_UPLOAD_ADMIN_ONLY'] =  TRUE; // FALSE or TRUE
	
	// 管理者だけが添付ファイルを削除できるようにする
		$this->cont['PLUGIN_ATTACH_DELETE_ADMIN_ONLY'] =  TRUE; // FALSE or TRUE
	
	// 管理者が添付ファイルを削除するときは、バックアップを作らない
	// PLUGIN_ATTACH_DELETE_ADMIN_ONLY=TRUEのとき有効
		$this->cont['PLUGIN_ATTACH_DELETE_ADMIN_NOBACKUP'] =  TRUE; // FALSE or TRUE
	
	// アップロード/削除時にパスワードを要求する(ADMIN_ONLYが優先)
		$this->cont['PLUGIN_ATTACH_PASSWORD_REQUIRE'] =  FALSE; // FALSE or TRUE
	
	// 添付ファイル名を変更できるようにする
		$this->cont['PLUGIN_ATTACH_RENAME_ENABLE'] =  TRUE; // FALSE or TRUE
	
	// ファイルのアクセス権
		$this->cont['PLUGIN_ATTACH_FILE_MODE'] =  0644;
	//define('PLUGIN_ATTACH_FILE_MODE', 0604); // for XREA.COM
	
	// File icon image
		$this->cont['PLUGIN_ATTACH_FILE_ICON'] =  '<img src="' . $this->cont['IMAGE_DIR'] .  'file.png"' .
	' width="20" height="20" alt="file"' .
	' style="border-width:0px" />';
	
	// mime-typeを記述したページ
		$this->cont['PLUGIN_ATTACH_CONFIG_PAGE_MIME'] =  'plugin/attach/mime-type';

	}
	
	//-------- convert
	function plugin_attach_convert()
	{
	//	global $vars;
	
		$page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
	
		$nolist = $noform = FALSE;
		if (func_num_args() > 0) {
			foreach (func_get_args() as $arg) {
				$arg = strtolower($arg);
				$nolist |= ($arg == 'nolist');
				$noform |= ($arg == 'noform');
			}
		}
	
		$ret = '';
		if (! $nolist) {
			$obj  = & new XpWikiAttachPages($this->xpwiki, $page);
			$ret .= $obj->toString($page, TRUE);
		}
		if (! $noform) {
			$ret .= $this->attach_form($page);
		}
	
		return $ret;
	}
	
	//-------- action
	function plugin_attach_action()
	{
	//	global $vars, $_attach_messages;
	
		// Backward compatible
		if (isset($this->root->vars['openfile'])) {
			$this->root->vars['file'] = $this->root->vars['openfile'];
			$this->root->vars['pcmd'] = 'open';
		}
		if (isset($this->root->vars['delfile'])) {
			$this->root->vars['file'] = $this->root->vars['delfile'];
			$this->root->vars['pcmd'] = 'delete';
		}
	
		$pcmd  = isset($this->root->vars['pcmd'])  ? $this->root->vars['pcmd']  : '';
		$refer = isset($this->root->vars['refer']) ? $this->root->vars['refer'] : '';
		$pass  = isset($this->root->vars['pass'])  ? $this->root->vars['pass']  : NULL;
		$page  = isset($this->root->vars['page'])  ? $this->root->vars['page']  : '';
	
		if ($refer != '' && $this->func->is_pagename($refer)) {
			if(in_array($pcmd, array('info', 'open', 'list'))) {
				$this->func->check_readable($refer);
			} else {
				$this->func->check_editable($refer);
			}
		}
	
		// Dispatch
		if (isset($_FILES['attach_file'])) {
			// Upload
			return $this->attach_upload($_FILES['attach_file'], $refer, $pass);
		} else {
			switch ($pcmd) {
			case 'delete':	/*FALLTHROUGH*/
			case 'freeze':
			case 'unfreeze':
				if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits editing');
			}
			switch ($pcmd) {
			case 'info'     : return $this->attach_info();
			case 'delete'   : return $this->attach_delete();
			case 'open'     : return $this->attach_open();
			case 'list'     : return $this->attach_list();
			case 'freeze'   : return $this->attach_freeze(TRUE);
			case 'unfreeze' : return $this->attach_freeze(FALSE);
			case 'rename'   : return $this->attach_rename();
			case 'upload'   : return $this->attach_showform();
			}
			if ($page == '' || ! $this->func->is_page($page)) {
				return $this->attach_list();
			} else {
				return $this->attach_showform();
			}
		}
	}
	
	//-------- call from skin
	function attach_filelist()
	{
	//	global $vars, $_attach_messages;
	
		$page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
	
		$obj = & new XpWikiAttachPages($this->xpwiki, $page, 0);
	
		if (! isset($obj->pages[$page])) {
			return '';
		} else {
			return $this->root->_attach_messages['msg_file'] . ': ' .
		$obj->toString($page, TRUE) . "\n";
		}
	}
	
	//-------- 実体
	// ファイルアップロード
	// $pass = NULL : パスワードが指定されていない
	// $pass = TRUE : アップロード許可
	function attach_upload($file, $page, $pass = NULL)
	{
	//	global $_attach_messages, $notify, $notify_subject;
	
		if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits editing');
	
		// Check query-string
		$query = 'plugin=attach&amp;pcmd=info&amp;refer=' . rawurlencode($page) .
		'&amp;file=' . rawurlencode($file['name']);
	
		if ($this->cont['PKWK_QUERY_STRING_MAX'] && strlen($query) > $this->cont['PKWK_QUERY_STRING_MAX']) {
			$this->func->pkwk_common_headers();
			echo('Query string (page name and/or file name) too long');
			exit;
		} else if (! $this->func->is_page($page)) {
			$this->func->die_message('No such page');
		} else if ($file['tmp_name'] == '' || ! is_uploaded_file($file['tmp_name'])) {
			return array('result'=>FALSE);
		} else if ($file['size'] > $this->cont['PLUGIN_ATTACH_MAX_FILESIZE']) {
			return array(
				'result'=>FALSE,
			'msg'=>$this->root->_attach_messages['err_exceed']);
		} else if (! $this->func->is_pagename($page) || ($pass !== TRUE && ! $this->func->is_editable($page))) {
			return array(
				'result'=>FALSE,'
			msg'=>$this->root->_attach_messages['err_noparm']);
		} else if ($this->cont['PLUGIN_ATTACH_UPLOAD_ADMIN_ONLY'] && $pass !== TRUE &&
			  ($pass === NULL || ! $this->func->pkwk_login($pass))) {
			return array(
				'result'=>FALSE,
			'msg'=>$this->root->_attach_messages['err_adminpass']);
		}
	
		$obj = & new XpWikiAttachFile($this->xpwiki, $page, $file['name']);
		if ($obj->exist)
			return array('result'=>FALSE,
			'msg'=>$this->root->_attach_messages['err_exists']);
	
		if (move_uploaded_file($file['tmp_name'], $obj->filename))
			chmod($obj->filename, $this->cont['PLUGIN_ATTACH_FILE_MODE']);
	
		if ($this->func->is_page($page))
			touch($this->func->get_filename($page));
	
		$obj->getstatus();
		$obj->status['pass'] = ($pass !== TRUE && $pass !== NULL) ? md5($pass) : '';
		$obj->putstatus();
	
		if ($this->root->notify) {
			$footer['ACTION']   = 'File attached';
			$footer['FILENAME'] = & $file['name'];
			$footer['FILESIZE'] = & $file['size'];
			$footer['PAGE']     = & $page;
	
			$footer['URI']      = $this->func->get_script_uri() .
			//'?' . rawurlencode($page);
	
				// MD5 may heavy
				'?plugin=attach' .
				'&refer=' . rawurlencode($page) .
				'&file='  . rawurlencode($file['name']) .
				'&pcmd=info';
	
			$footer['USER_AGENT']  = TRUE;
			$footer['REMOTE_ADDR'] = TRUE;
	
			$this->func->pkwk_mail_notify($this->root->notify_subject, "\n", $footer) or
				die('pkwk_mail_notify(): Failed');
		}
	
		return array(
			'result'=>TRUE,
		'msg'=>$this->root->_attach_messages['msg_uploaded']);
	}
	
	// 詳細フォームを表示
	function attach_info($err = '')
	{
	//	global $vars, $_attach_messages;
	
		foreach (array('refer', 'file', 'age') as $var)
			${$var} = isset($this->root->vars[$var]) ? $this->root->vars[$var] : '';
	
		$obj = & new XpWikiAttachFile($this->xpwiki, $refer, $file, $age);
		return $obj->getstatus() ?
			$obj->info($err) :
			array('msg'=>$this->root->_attach_messages['err_notfound']);
	}
	
	// 削除
	function attach_delete()
	{
	//	global $vars, $_attach_messages;
	
		foreach (array('refer', 'file', 'age', 'pass') as $var)
			${$var} = isset($this->root->vars[$var]) ? $this->root->vars[$var] : '';
	
		if ($this->func->is_freeze($refer) || ! $this->func->is_editable($refer))
			return array('msg'=>$this->root->_attach_messages['err_noparm']);
	
		$obj = & new XpWikiAttachFile($this->xpwiki, $refer, $file, $age);
		if (! $obj->getstatus())
			return array('msg'=>$this->root->_attach_messages['err_notfound']);
			
		return $obj->delete($pass);
	}
	
	// 凍結
	function attach_freeze($freeze)
	{
	//	global $vars, $_attach_messages;
	
		foreach (array('refer', 'file', 'age', 'pass') as $var) {
			${$var} = isset($this->root->vars[$var]) ? $this->root->vars[$var] : '';
		}
	
		if ($this->func->is_freeze($refer) || ! $this->func->is_editable($refer)) {
			return array('msg'=>$this->root->_attach_messages['err_noparm']);
		} else {
			$obj = & new XpWikiAttachFile($this->xpwiki, $refer, $file, $age);
			return $obj->getstatus() ?
				$obj->freeze($freeze, $pass) :
				array('msg'=>$this->root->_attach_messages['err_notfound']);
		}
	}
	
	// リネーム
	function attach_rename()
	{
	//	global $vars, $_attach_messages;
	
		foreach (array('refer', 'file', 'age', 'pass', 'newname') as $var) {
			${$var} = isset($this->root->vars[$var]) ? $this->root->vars[$var] : '';
		}
	
		if ($this->func->is_freeze($refer) || ! $this->func->is_editable($refer)) {
			return array('msg'=>$this->root->_attach_messages['err_noparm']);
		}
		$obj = & new XpWikiAttachFile($this->xpwiki, $refer, $file, $age);
		if (! $obj->getstatus())
			return array('msg'=>$this->root->_attach_messages['err_notfound']);
	
		return $obj->rename($pass, $newname);
	
	}
	
	// ダウンロード
	function attach_open()
	{
	//	global $vars, $_attach_messages;
	
		foreach (array('refer', 'file', 'age') as $var) {
			${$var} = isset($this->root->vars[$var]) ? $this->root->vars[$var] : '';
		}
	
		$obj = & new XpWikiAttachFile($this->xpwiki, $refer, $file, $age);
		return $obj->getstatus() ?
			$obj->open() :
			array('msg'=>$this->root->_attach_messages['err_notfound']);
	}
	
	// 一覧取得
	function attach_list()
	{
	//	global $vars, $_attach_messages;
	
		$refer = isset($this->root->vars['refer']) ? $this->root->vars['refer'] : '';
	
		$obj = & new XpWikiAttachPages($this->xpwiki, $refer);
	
		$msg = $this->root->_attach_messages[($refer == '') ? 'msg_listall' : 'msg_listpage'];
		$body = ($refer == '' || isset($obj->pages[$refer])) ?
			$obj->toString($refer, FALSE) :
			$this->root->_attach_messages['err_noexist'];
	
		return array('msg'=>$msg, 'body'=>$body);
	}
	
	// アップロードフォームを表示 (action時)
	function attach_showform()
	{
	//	global $vars, $_attach_messages;
	
		$page = isset($this->root->vars['page']) ? $this->root->vars['page'] : '';
		$this->root->vars['refer'] = $page;
		$body = $this->attach_form($page);
	
		return array('msg'=>$this->root->_attach_messages['msg_upload'], 'body'=>$body);
	}
	
	//-------- サービス
	// mime-typeの決定
	function attach_mime_content_type($filename)
	{
		$type = 'application/octet-stream'; // default
	
		if (! file_exists($filename)) return $type;
	
		$size = @getimagesize($filename);
		if (is_array($size)) {
			switch ($size[2]) {
				case 1: return 'image/gif';
				case 2: return 'image/jpeg';
				case 3: return 'image/png';
				case 4: return 'application/x-shockwave-flash';
			}
		}
	
		$matches = array();
		if (! preg_match('/_((?:[0-9A-F]{2})+)(?:\.\d+)?$/', $filename, $matches))
			return $type;
	
		$filename = $this->func->decode($matches[1]);
	
		// mime-type一覧表を取得
		$config = new XpWikiConfig($this->xpwiki, $this->cont['PLUGIN_ATTACH_CONFIG_PAGE_MIME']);
		$table = $config->read() ? $config->get('mime-type') : array();
		unset($config); // メモリ節約
	
		foreach ($table as $row) {
			$_type = trim($row[0]);
			$exts = preg_split('/\s+|,/', trim($row[1]), -1, PREG_SPLIT_NO_EMPTY);
			foreach ($exts as $ext) {
				if (preg_match("/\.$ext$/i", $filename)) return $_type;
			}
		}
	
		return $type;
	}
	
	// アップロードフォームの出力
	function attach_form($page)
	{
	//	global $script, $vars, $_attach_messages;
	
		$r_page = rawurlencode($page);
		$s_page = htmlspecialchars($page);
		$navi = <<<EOD
  <span class="small">
   [<a href="{$this->root->script}?plugin=attach&amp;pcmd=list&amp;refer=$r_page">{$this->root->_attach_messages['msg_list']}</a>]
   [<a href="{$this->root->script}?plugin=attach&amp;pcmd=list">{$this->root->_attach_messages['msg_listall']}</a>]
  </span><br />
EOD;
	
		if (! ini_get('file_uploads')) return '#attach(): file_uploads disabled<br />' . $navi;
		if (! $this->func->is_page($page))          return '#attach(): No such page<br />'          . $navi;
	
		$maxsize = $this->cont['PLUGIN_ATTACH_MAX_FILESIZE'];
		$msg_maxsize = sprintf($this->root->_attach_messages['msg_maxsize'], number_format($maxsize/1024) . 'KB');
	
		$pass = '';
		if ($this->cont['PLUGIN_ATTACH_PASSWORD_REQUIRE'] || $this->cont['PLUGIN_ATTACH_UPLOAD_ADMIN_ONLY']) {
			$title = $this->root->_attach_messages[$this->cont['PLUGIN_ATTACH_UPLOAD_ADMIN_ONLY'] ? 'msg_adminpass' : 'msg_password'];
			$pass = '<br />' . $title . ': <input type="password" name="pass" size="8" />';
		}
		return <<<EOD
<form enctype="multipart/form-data" action="{$this->root->script}" method="post">
 <div>
  <input type="hidden" name="plugin" value="attach" />
  <input type="hidden" name="pcmd"   value="post" />
  <input type="hidden" name="refer"  value="$s_page" />
  <input type="hidden" name="max_file_size" value="$maxsize" />
  $navi
  <span class="small">
   $msg_maxsize
  </span><br />
  <label for="_p_attach_file">{$this->root->_attach_messages['msg_file']}:</label> <input type="file" name="attach_file" id="_p_attach_file" />
  $pass
  <input type="submit" value="{$this->root->_attach_messages['btn_upload']}" />
 </div>
</form>
EOD;
	}
}
	
	//-------- クラス
	// ファイル
class XpWikiAttachFile
{
	var $page, $file, $age, $basename, $filename, $logname;
	var $time = 0;
	var $size = 0;
	var $time_str = '';
	var $size_str = '';
	var $status = array('count'=>array(0), 'age'=>'', 'pass'=>'', 'freeze'=>FALSE);

	function XpWikiAttachFile(& $xpwiki, $page, $file, $age = 0)
	{
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;
		$this->page = $page;
		$this->file = preg_replace('#^.*/#','',$file);
		$this->age  = is_numeric($age) ? $age : 0;

		$this->basename = $this->cont['UPLOAD_DIR'] . $this->func->encode($page) . '_' . $this->func->encode($this->file);
		$this->filename = $this->basename . ($age ? '.' . $age : '');
		$this->logname  = $this->basename . '.log';
		$this->exist    = file_exists($this->filename);
		$this->time     = $this->exist ? filemtime($this->filename) - $this->cont['LOCALZONE'] : 0;
		$this->md5hash  = $this->exist ? md5_file($this->filename) : '';
	}

	// ファイル情報取得
	function getstatus()
	{
		if (! $this->exist) return FALSE;

		// ログファイル取得
		if (file_exists($this->logname)) {
			$data = file($this->logname);
			foreach ($this->status as $key=>$value) {
				$this->status[$key] = chop(array_shift($data));
			}
			$this->status['count'] = explode(',', $this->status['count']);
		}
		$this->time_str = $this->func->get_date('Y/m/d H:i:s', $this->time);
		$this->size     = filesize($this->filename);
		$this->size_str = sprintf('%01.1f', round($this->size/1024, 1)) . 'KB';
		$this->type     = xpwiki_plugin_attach::attach_mime_content_type($this->filename);

		return TRUE;
	}

	// ステータス保存
	function putstatus()
	{
		$this->status['count'] = join(',', $this->status['count']);
		$fp = fopen($this->logname, 'wb') or
			$this->func->die_message('cannot write ' . $this->logname);
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		rewind($fp);
		foreach ($this->status as $key=>$value) {
			fwrite($fp, $value . "\n");
		}
		flock($fp, LOCK_UN);
		fclose($fp);
	}

	// 日付の比較関数
	function datecomp($a, $b) {
		return ($a->time == $b->time) ? 0 : (($a->time > $b->time) ? -1 : 1);
	}

	function toString($showicon, $showinfo)
	{
//		global $script, $_attach_messages;

		$this->getstatus();
		$param  = '&amp;file=' . rawurlencode($this->file) . '&amp;refer=' . rawurlencode($this->page) .
			($this->age ? '&amp;age=' . $this->age : '');
		$title = $this->time_str . ' ' . $this->size_str;
		$label = ($showicon ? $this->cont['PLUGIN_ATTACH_FILE_ICON'] : '') . htmlspecialchars($this->file);
		if ($this->age) {
			$label .= ' (backup No.' . $this->age . ')';
		}
		$info = $count = '';
		if ($showinfo) {
			$_title = str_replace('$1', rawurlencode($this->file), $this->root->_attach_messages['msg_info']);
			$info = "\n<span class=\"small\">[<a href=\"{$this->root->script}?plugin=attach&amp;pcmd=info$param\" title=\"$_title\">{$this->root->_attach_messages['btn_info']}</a>]</span>\n";
			$count = ($showicon && ! empty($this->status['count'][$this->age])) ?
				sprintf($this->root->_attach_messages['msg_count'], $this->status['count'][$this->age]) : '';
		}
		return "<a href=\"{$this->root->script}?plugin=attach&amp;pcmd=open$param\" title=\"$title\">$label</a>$count$info";
	}

	// 情報表示
	function info($err)
	{
//		global $script, $_attach_messages;

		$r_page = rawurlencode($this->page);
		$s_page = htmlspecialchars($this->page);
		$s_file = htmlspecialchars($this->file);
		$s_err = ($err == '') ? '' : '<p style="font-weight:bold">' . $this->root->_attach_messages[$err] . '</p>';

		$msg_rename  = '';
		if ($this->age) {
			$msg_freezed = '';
			$msg_delete  = '<input type="radio" name="pcmd" id="_p_attach_delete" value="delete" />' .
				'<label for="_p_attach_delete">' .  $this->root->_attach_messages['msg_delete'] .
				$this->root->_attach_messages['msg_require'] . '</label><br />';
			$msg_freeze  = '';
		} else {
			if ($this->status['freeze']) {
				$msg_freezed = "<dd>{$this->root->_attach_messages['msg_isfreeze']}</dd>";
				$msg_delete  = '';
				$msg_freeze  = '<input type="radio" name="pcmd" id="_p_attach_unfreeze" value="unfreeze" />' .
					'<label for="_p_attach_unfreeze">' .  $this->root->_attach_messages['msg_unfreeze'] .
					$this->root->_attach_messages['msg_require'] . '</label><br />';
			} else {
				$msg_freezed = '';
				$msg_delete = '<input type="radio" name="pcmd" id="_p_attach_delete" value="delete" />' .
					'<label for="_p_attach_delete">' . $this->root->_attach_messages['msg_delete'];
				if ($this->cont['PLUGIN_ATTACH_DELETE_ADMIN_ONLY'] || $this->age)
					$msg_delete .= $this->root->_attach_messages['msg_require'];
				$msg_delete .= '</label><br />';
				$msg_freeze  = '<input type="radio" name="pcmd" id="_p_attach_freeze" value="freeze" />' .
					'<label for="_p_attach_freeze">' .  $this->root->_attach_messages['msg_freeze'] .
					$this->root->_attach_messages['msg_require'] . '</label><br />';

				if ($this->cont['PLUGIN_ATTACH_RENAME_ENABLE']) {
					$msg_rename  = '<input type="radio" name="pcmd" id="_p_attach_rename" value="rename" />' .
						'<label for="_p_attach_rename">' .  $this->root->_attach_messages['msg_rename'] .
						$this->root->_attach_messages['msg_require'] . '</label><br />&nbsp;&nbsp;&nbsp;&nbsp;' .
						'<label for="_p_attach_newname">' . $this->root->_attach_messages['msg_newname'] .
						':</label> ' .
						'<input type="text" name="newname" id="_p_attach_newname" size="40" value="' .
						$this->file . '" /><br />';
				}
			}
		}
		$info = $this->toString(TRUE, FALSE);

		$retval = array('msg'=>sprintf($this->root->_attach_messages['msg_info'], htmlspecialchars($this->file)));
		$retval['body'] = <<< EOD
<p class="small">
 [<a href="{$this->root->script}?plugin=attach&amp;pcmd=list&amp;refer=$r_page">{$this->root->_attach_messages['msg_list']}</a>]
 [<a href="{$this->root->script}?plugin=attach&amp;pcmd=list">{$this->root->_attach_messages['msg_listall']}</a>]
</p>
<dl>
 <dt>$info</dt>
 <dd>{$this->root->_attach_messages['msg_page']}:$s_page</dd>
 <dd>{$this->root->_attach_messages['msg_filename']}:{$this->filename}</dd>
 <dd>{$this->root->_attach_messages['msg_md5hash']}:{$this->md5hash}</dd>
 <dd>{$this->root->_attach_messages['msg_filesize']}:{$this->size_str} ({$this->size} bytes)</dd>
 <dd>Content-type:{$this->type}</dd>
 <dd>{$this->root->_attach_messages['msg_date']}:{$this->time_str}</dd>
 <dd>{$this->root->_attach_messages['msg_dlcount']}:{$this->status['count'][$this->age]}</dd>
 $msg_freezed
</dl>
<hr />
$s_err
<form action="{$this->root->script}" method="post">
 <div>
  <input type="hidden" name="plugin" value="attach" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="file" value="$s_file" />
  <input type="hidden" name="age" value="{$this->age}" />
  $msg_delete
  $msg_freeze
  $msg_rename
  <br />
  <label for="_p_attach_password">{$this->root->_attach_messages['msg_password']}:</label>
  <input type="password" name="pass" id="_p_attach_password" size="8" />
  <input type="submit" value="{$this->root->_attach_messages['btn_submit']}" />
 </div>
</form>
EOD;
		return $retval;
	}

	function delete($pass)
	{
//		global $_attach_messages, $notify, $notify_subject;

		if ($this->status['freeze']) return xpwiki_plugin_attach::attach_info('msg_isfreeze');

		if (! $this->func->pkwk_login($pass)) {
			if ($this->cont['PLUGIN_ATTACH_DELETE_ADMIN_ONLY'] || $this->age) {
				return xpwiki_plugin_attach::attach_info('err_adminpass');
			} else if ($this->cont['PLUGIN_ATTACH_PASSWORD_REQUIRE'] &&
				md5($pass) != $this->status['pass']) {
				return xpwiki_plugin_attach::attach_info('err_password');
			}
		}

		// バックアップ
		if ($this->age ||
			($this->cont['PLUGIN_ATTACH_DELETE_ADMIN_ONLY'] && $this->cont['PLUGIN_ATTACH_DELETE_ADMIN_NOBACKUP'])) {
			@unlink($this->filename);
		} else {
			do {
				$age = ++$this->status['age'];
			} while (file_exists($this->basename . '.' . $age));

			if (! rename($this->basename,$this->basename . '.' . $age)) {
				// 削除失敗 why?
				return array('msg'=>$this->root->_attach_messages['err_delete']);
			}

			$this->status['count'][$age] = $this->status['count'][0];
			$this->status['count'][0] = 0;
			$this->putstatus();
		}

		if ($this->func->is_page($this->page))
			touch($this->func->get_filename($this->page));

		if ($this->root->notify) {
			$footer['ACTION']   = 'File deleted';
			$footer['FILENAME'] = & $this->file;
			$footer['PAGE']     = & $this->page;
			$footer['URI']      = $this->func->get_script_uri() .
				'?' . rawurlencode($this->page);
			$footer['USER_AGENT']  = TRUE;
			$footer['REMOTE_ADDR'] = TRUE;
			$this->func->pkwk_mail_notify($this->root->notify_subject, "\n", $footer) or
				die('pkwk_mail_notify(): Failed');
		}

		return array('msg'=>$this->root->_attach_messages['msg_deleted']);
	}

	function rename($pass, $newname)
	{
//		global $_attach_messages, $notify, $notify_subject;

		if ($this->status['freeze']) return xpwiki_plugin_attach::attach_info('msg_isfreeze');

		if (! $this->func->pkwk_login($pass)) {
			if ($this->cont['PLUGIN_ATTACH_DELETE_ADMIN_ONLY'] || $this->age) {
				return xpwiki_plugin_attach::attach_info('err_adminpass');
			} else if ($this->cont['PLUGIN_ATTACH_PASSWORD_REQUIRE'] &&
				md5($pass) != $this->status['pass']) {
				return xpwiki_plugin_attach::attach_info('err_password');
			}
		}
		$newbase = $this->cont['UPLOAD_DIR'] . $this->func->encode($this->page) . '_' . $this->func->encode($newname);
		if (file_exists($newbase)) {
			return array('msg'=>$this->root->_attach_messages['err_exists']);
		}
		if (! $this->cont['PLUGIN_ATTACH_RENAME_ENABLE'] || ! rename($this->basename, $newbase)) {
			return array('msg'=>$this->root->_attach_messages['err_rename']);
		}

		return array('msg'=>$this->root->_attach_messages['msg_renamed']);
	}

	function freeze($freeze, $pass)
	{
//		global $_attach_messages;

		if (! $this->func->pkwk_login($pass)) return xpwiki_plugin_attach::attach_info('err_adminpass');

		$this->getstatus();
		$this->status['freeze'] = $freeze;
		$this->putstatus();

		return array('msg'=>$this->root->_attach_messages[$freeze ? 'msg_freezed' : 'msg_unfreezed']);
	}

	function open()
	{
		$this->getstatus();
		$this->status['count'][$this->age]++;
		$this->putstatus();
		$filename = $this->file;

		// Care for Japanese-character-included file name
		if ($this->cont['LANG'] == 'ja') {
			switch($this->cont['UA_NAME'] . '/' . $this->cont['UA_PROFILE']){
			case 'Opera/default':
				// Care for using _auto-encode-detecting_ function
				$filename = mb_convert_encoding($filename, 'UTF-8', 'auto');
				break;
			case 'MSIE/default':
				$filename = mb_convert_encoding($filename, 'SJIS', 'auto');
				break;
			}
		}
		$filename = htmlspecialchars($filename);

		ini_set('default_charset', '');
		mb_http_output('pass');

		$this->func->pkwk_common_headers();
		header('Content-Disposition: inline; filename="' . $filename . '"');
		header('Content-Length: ' . $this->size);
		header('Content-Type: '   . $this->type);

		@readfile($this->filename);
		exit;
	}
}
	
	// ファイルコンテナ
class XpWikiAttachFiles
{
	var $page;
	var $files = array();

	function XpWikiAttachFiles(& $xpwiki, $page)
	{
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;
		$this->page = $page;
	}

	function add($file, $age)
	{
		$this->files[$file][$age] = & new XpWikiAttachFile($this->xpwiki, $this->page, $file, $age);
	}

	// ファイル一覧を取得
	function toString($flat)
	{
//		global $_title_cannotread;

		if (! $this->func->check_readable($this->page, FALSE, FALSE)) {
			return str_replace('$1', $this->func->make_pagelink($this->page), $this->root->_title_cannotread);
		} else if ($flat) {
			return $this->to_flat();
		}

		$ret = '';
		$files = array_keys($this->files);
		sort($files);

		foreach ($files as $file) {
			$_files = array();
			foreach (array_keys($this->files[$file]) as $age) {
				$_files[$age] = $this->files[$file][$age]->toString(FALSE, TRUE);
			}
			if (! isset($_files[0])) {
				$_files[0] = htmlspecialchars($file);
			}
			ksort($_files);
			$_file = $_files[0];
			unset($_files[0]);
			$ret .= " <li>$_file\n";
			if (count($_files)) {
				$ret .= "<ul>\n<li>" . join("</li>\n<li>", $_files) . "</li>\n</ul>\n";
			}
			$ret .= " </li>\n";
		}
		return $this->func->make_pagelink($this->page) . "\n<ul>\n$ret</ul>\n";
	}

	// ファイル一覧を取得(inline)
	function to_flat()
	{
		$ret = '';
		$files = array();
		foreach (array_keys($this->files) as $file) {
			if (isset($this->files[$file][0])) {
				$files[$file] = & $this->files[$file][0];
			}
		}
		uasort($files, array('XpWikiAttachFile', 'datecomp'));
		foreach (array_keys($files) as $file) {
			$ret .= $files[$file]->toString(TRUE, TRUE) . ' ';
		}

		return $ret;
	}
}
	
	// ページコンテナ
class XpWikiAttachPages
{
	var $pages = array();

	function XpWikiAttachPages(& $xpwiki, $page = '', $age = NULL)
	{
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;

		$dir = opendir($this->cont['UPLOAD_DIR']) or
			die('directory ' . $this->cont['UPLOAD_DIR'] . ' is not exist or not readable.');

		$page_pattern = ($page == '') ? '(?:[0-9A-F]{2})+' : preg_quote($this->func->encode($page), '/');
		$age_pattern = ($age === NULL) ?
			'(?:\.([0-9]+))?' : ($age ?  "\.($age)" : '');
		$pattern = "/^({$page_pattern})_((?:[0-9A-F]{2})+){$age_pattern}$/";

		$matches = array();
		while ($file = readdir($dir)) {
			if (! preg_match($pattern, $file, $matches))
				continue;

			$_page = $this->func->decode($matches[1]);
			$_file = $this->func->decode($matches[2]);
			$_age  = isset($matches[3]) ? $matches[3] : 0;
			if (! isset($this->pages[$_page])) {
				$this->pages[$_page] = & new XpWikiAttachFiles($this->xpwiki, $_page);
			}
			$this->pages[$_page]->add($_file, $_age);
		}
		closedir($dir);
	}

	function toString($page = '', $flat = FALSE)
	{
		if ($page != '') {
			if (! isset($this->pages[$page])) {
				return '';
			} else {
				return $this->pages[$page]->toString($flat);
			}
		}
		$ret = '';

		$pages = array_keys($this->pages);
		sort($pages);

		foreach ($pages as $page) {
			if ($this->func->check_non_list($page)) continue;
			$ret .= '<li>' . $this->pages[$page]->toString($flat) . '</li>' . "\n";
		}
		return "\n" . '<ul>' . "\n" . $ret . '</ul>' . "\n";
	}
}
?>