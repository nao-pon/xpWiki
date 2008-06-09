<?php
/*
 * Created on 2008/03/24 by nao-pon http://hypweb.net/
 * $Id: attach.php,v 1.4 2008/06/09 01:53:16 nao-pon Exp $
 */

//-------- クラス
//ファイル
class XpWikiAttachFile
{
	var $page,$file,$age,$basename,$filename,$logname,$copyright;
	var $time = 0;
	var $size = 0;
	var $pgid = 0;
	var $time_str = '';
	var $size_str = '';
	var $owner_str = '';
	var $status = array(
			'count'    => array(0),
			'age'      => '',
			'pass'     => '',
			'freeze'   => FALSE,
			'copyright'=> FALSE,
			'owner'    => 0,
			'ucd'      => '',
			'uname'    => '',
			'md5'      => '',
			'admins'   => 0,
			'org_fname'=> '',
			'imagesize'=> NULL
		);
	var $action = 'update';
	var $dbinfo = array();
	
	function XpWikiAttachFile(& $xpwiki, $page, $file, $age=0, $pgid=0)
	{
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;

		$this->page = $page;
		$this->pgid = ($pgid)? $pgid : $this->func->get_pgid_by_name($page);
		$this->file = $this->func->basename($file);
		$this->age  = is_numeric($age) ? $age : 0;
		$this->id   = $this->get_id();
		
		$this->basename = $this->cont['UPLOAD_DIR'].$this->func->encode($page).'_'.$this->func->encode($this->file);
		$this->filename = $this->basename . ($age ? '.'.$age : '');
		$this->logname = $this->basename.'.log';
		
		if ($this->id) {
			$this->get_dbinfo();
			$this->exist = TRUE;
			$this->time = $this->dbinfo['mtime'];
		} else {
			$this->exist = file_exists($this->filename);
			$this->time = $this->exist ? filemtime($this->filename) - $this->cont['LOCALZONE'] : 0;
		}
		$this->owner_id = 0;
	}
	// ファイル情報取得
	function getstatus()
	{
		if (!$this->exist)
		{
			return FALSE;
		}
		// ログファイル取得
		if (file_exists($this->logname))
		{
			$data = file($this->logname);
			foreach ($this->status as $key=>$value)
			{
				$this->status[$key] = chop(array_shift($data));
			}
			$this->status['count'] = explode(',',$this->status['count']);
			if (empty($this->status['org_fname'])) $this->status['org_fname'] = $this->file;
			if (is_null($this->status['imagesize']) || $this->status['imagesize'] === '') {
				$this->status['imagesize'] = @ getimagesize($this->filename);
				$this->putstatus(FALSE);
			} else {
				$this->status['imagesize'] = unserialize($this->status['imagesize']);
			}
		}
		$this->time_str = $this->func->get_date('Y/m/d H:i:s',$this->time);
		$this->size = isset($this->dbinfo['size'])? $this->dbinfo['size'] : filesize($this->filename);
		$this->size_str = sprintf('%01.1f',round($this->size)/1024,1).'KB';
		$this->type = isset($this->dbinfo['type'])? $this->dbinfo['type'] : xpwiki_plugin_attach::attach_mime_content_type($this->filename, $this->status);
		$this->owner_id = intval($this->status['owner']);
		$user = $this->func->get_userinfo_by_id($this->status['owner']);
		$user = htmlspecialchars($user['uname']);
		if (!$this->status['owner']) {
			if ($this->status['uname']) {
				$user = htmlspecialchars($this->status['uname']);
			}
			$user = $user . " [".$this->status['ucd'] . "]";
		}
		$this->owner_str = $user;

		return TRUE;
	}
	//ステータス保存
	function putstatus($dbup = TRUE)
	{
		if ($dbup) $this->update_db();
		$status = $this->status;
		$status['count'] = join(',', $status['count']);
		$status['imagesize'] = serialize($status['imagesize']);
		$fp = fopen($this->logname,'wb')
			or $this->func->die_message('cannot write '.$this->logname);
		flock($fp,LOCK_EX);
		foreach ($status as $key=>$value)
		{
			fwrite($fp,$value."\n");
		}
		fclose($fp);
	}

	// DB id 取得
	function get_id() {
		return $this->func->get_attachfile_id($this->page, $this->file, $this->age);
	}
	
	// Get attachDB info
	function get_dbinfo () {
		$this->dbinfo = $this->func->get_attachdbinfo($this->id);
		/*
		$query = 'SELECT `type`, `mtime`, `size` FROM '.$this->xpwiki->db->prefix($this->root->mydirname.'_attach').' WHERE `id`=\''.$this->id.'\' LIMIT 1';
		if ($result = $this->xpwiki->db->query($query)) {
			$this->dbinfo = $this->xpwiki->db->fetchArray($result);
		}
		*/
	}
	
	// attach DB 更新
	function update_db()
	{
		if ($this->action == "insert")
		{
			$this->size = filesize($this->filename);
			$this->type = xpwiki_plugin_attach::attach_mime_content_type($this->filename, $this->status);
			$this->time = filemtime($this->filename) - $this->cont['LOCALZONE'];
		}
		$data['id']   = $this->id;
		$data['pgid'] = $this->pgid;
		$data['name'] = $this->file;
		$data['mtime'] = $this->time;
		$data['size'] = $this->size;
		$data['type'] = $this->type;
		$data['status'] = $this->status;

		$this->func->attach_db_write($data,$this->action);
		
	}
	// 日付の比較関数
	function datecomp($a,$b)
	{
		return ($a->time == $b->time) ? 0 : (($a->time > $b->time) ? -1 : 1);
	}
	function toString($showicon,$showinfo,$mode="")
	{
//		global $script,$date_format,$time_format,$weeklabels;
//		global $_attach_messages;
		
		$this->getstatus();
		$param = '&amp;refer='.rawurlencode($this->page)
		       . ($this->age ? '&amp;age='.$this->age : '')
		       . '&amp;';
		$param2 = 'file='.rawurlencode($this->file);
		$title = $this->time_str.' '.$this->size_str;
		$label = ($showicon ? $this->cont['FILE_ICON'] : '').htmlspecialchars($this->status['org_fname']);
		if ($this->age) {
			if ($mode == "imglist"){
				$label = 'backup No.'.$this->age;
			} else {
				$label .= ' (backup No.'.$this->age.')';
			}
		}
		
		$info = $count = '';
		if ($showinfo) {
			$_title = str_replace('$1',rawurlencode($this->file),$this->root->_attach_messages['msg_info']);
			if ($mode == "imglist") {
				$info = "[ [[{$this->root->_attach_messages['btn_info']}:{$this->root->script}?plugin=attach&pcmd=info".str_replace("&amp;","&", ($param . $param2))."]] ]";
			} else {
				$info = "\n<span class=\"small\">[<a href=\"{$this->root->script}?plugin=attach&amp;pcmd=info{$param}{$param2}\" title=\"$_title\">{$this->root->_attach_messages['btn_info']}</a>]</span>";
			}
			$count = ($showicon and !empty($this->status['count'][$this->age])) ?
				sprintf($this->root->_attach_messages['msg_count'],$this->status['count'][$this->age]) : '';
		}
		if ($mode == "imglist") {
			if ($this->age) {
				return "&size(12){".$label.$info."};";
			} else {
				return "&size(12){&ref(\"".$this->func->strip_bracket($this->page)."/".$this->file."\"".$this->cont['ATTACH_CONFIG_REF_OPTION'].");&br();".$info."};";
			}
		} else {
			return "<a href=\"{$this->cont['HOME_URL']}gate.php?way=attach&amp;_noumb{$param}open{$param2}\" title=\"{$title}\">{$label}</a>{$count}{$info}";
		}
	}
	// 情報表示
	function info($err) {
		
		$r_page = rawurlencode($this->page);
		$s_page = htmlspecialchars($this->page);
		$s_file = htmlspecialchars($this->file);
		$s_err = ($err == '') ? '' : '<p style="font-weight:bold">'.$this->root->_attach_messages[$err].'</p>';
		$ref = "";
		$img_info = "";
			
		$pass = '';
		$msg_require = '';
		$is_editable = $this->is_owner();
		if ($this->cont['ATTACH_PASSWORD_REQUIRE'] && !$this->cont['ATTACH_UPLOAD_ADMIN_ONLY'] && !$is_editable)
		{
			$title = $this->root->_attach_messages[$this->cont['ATTACH_UPLOAD_ADMIN_ONLY'] ? 'msg_adminpass' : 'msg_password'];
			$pass = $title.': <input type="password" name="pass" size="8" />';
			$msg_require = $this->root->_attach_messages['msg_require'];
		}

		$msg_rename = '';
		if ($this->age)
		{
			$msg_freezed = '';
			$msg_delete  = '<input type="radio" id="pcmd_d" name="pcmd" value="delete" /><label for="pcmd_d">'.$this->root->_attach_messages['msg_delete'].'</label>';
			$msg_delete .= $this->root->_attach_messages['msg_require'];
			$msg_delete .= '<br />';
			$msg_freeze  = '';
		}
		else
		{
			// イメージファイルの場合
			$isize = @getimagesize($this->filename);
			if (is_array($isize) && $isize[2] !== 4)
			{
				$img_info = "Image: {$isize[0]} x {$isize[1]} px";
				if ($is_editable && (defined('HYP_JPEGTRAN_PATH') || $isize[2] == 2))
				{
					$img_info = <<<EOD
<form action="{$this->root->script}" method="post">
 <div>
  $img_info
  <input type="hidden" name="plugin" value="attach" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="file" value="$s_file" />
  <input type="hidden" name="age" value="{$this->age}" />
  <input type="hidden" name="pcmd" value="rotate" />
  [ Rotate:
  <input type="radio" id="rotate90" name="rd" value="1" /> <label for="rotate90">90&deg;</label>
  <input type="radio" id="rotate180" name="rd" value="2" /> <label for="rotate180">180&deg;</label>
  <input type="radio" id="rotate270" name="rd" value="3" /> <label for="rotate270">270&deg;</label>
  $pass
  <input type="submit" value="{$this->root->_attach_messages['btn_submit']}" /> ]
 </div>
</form>
EOD;
				}
			}

			// refプラグインで表示
			if ($this->func->exist_plugin_inline("ref"))
			{
				$_dum = '';
				$ref .= "<dd><hr /></dd><dd>".$this->func->do_plugin_inline("ref", $this->page."/".$this->file.$this->cont['ATTACH_CONFIG_REF_OPTION'],$_dum)."</dd>\n";
			}
			
			if ($this->status['freeze'])
			{
				$msg_freezed = "<dd>{$this->root->_attach_messages['msg_isfreeze']}</dd>";
				$msg_delete = '';
				$msg_freeze  = '<input type="radio" id="pcmd_u" name="pcmd" value="unfreeze" /><label for="pcmd_u">'.$this->root->_attach_messages['msg_unfreeze'].'</label>';
				$msg_freeze .= $msg_require.'<br />';
			}
			else
			{
				$msg_freezed = '';
				$msg_delete = '<input type="radio" id="pcmd_d" name="pcmd" value="delete" /><label for="pcmd_d">'.$this->root->_attach_messages['msg_delete'].'</label>';
				$msg_delete .= $msg_require.'<br />';
				$msg_freeze  = '<input type="radio" id="pcmd_f" name="pcmd" value="freeze" /><label for="pcmd_f">'.$this->root->_attach_messages['msg_freeze'].'</label>';
				$msg_freeze .= $msg_require.'<br />';
				if ($this->cont['PLUGIN_ATTACH_RENAME_ENABLE']) {
					$msg_rename  = '<input type="radio" name="pcmd" id="_p_attach_rename" value="rename" />' .
						'<label for="_p_attach_rename">' .  $this->root->_attach_messages['msg_rename'] .
						$msg_require . '</label><br />&nbsp;&nbsp;&nbsp;&nbsp;' .
						'<label for="_p_attach_newname">' . $this->root->_attach_messages['msg_newname'] .
						':</label> ' .
						'<input type="text" name="newname" id="_p_attach_newname" size="40" value="' .
						(htmlspecialchars(empty($this->status['org_fname'])? $this->file : $this->status['org_fname'])) . '" /><br />';
				}
				if ($this->status['copyright']) {
					$msg_copyright  = '<input type="radio" id="pcmd_c" name="pcmd" value="copyright0" /><label for="pcmd_c">'.$this->root->_attach_messages['msg_copyright0'].'</label>';
				} else {
					$msg_copyright  = '<input type="radio" id="pcmd_c" name="pcmd" value="copyright1" /><label for="pcmd_c">'.$this->root->_attach_messages['msg_copyright'].'</label>';
				}
				$msg_copyright .= $msg_require.'<br />';
			}
		}
		$info = $this->toString(TRUE,FALSE);
		$copyright = ($this->status['copyright'])? ' checked=TRUE' : '';
		
		$retval = array('msg'=>sprintf($this->root->_attach_messages['msg_info'],htmlspecialchars($this->file)));
		$page_link = $this->func->make_pagelink($s_page);
		//EXIF DATA
		//$exif_data = $this->func->get_exif_data($this->filename, TRUE);
		$exif_data = $this->func->get_exif_data($this->filename);
		$exif_tags = '';
		if ($exif_data){
			$exif_tags = "<hr>".$exif_data['title'];
			foreach($exif_data as $key => $value){
				if ($key != "title") $exif_tags .= "<br />$key: $value";
			}
		}
		$v_filename = "<dd>{$this->root->_attach_messages['msg_filename']}:".$s_file;
		if ($this->root->userinfo['admin']) {
			$v_filename .=  '<br />&nbsp;&nbsp;&nbsp;'.basename($this->filename).'</dd>';
		} else {
			$v_filename .=  '</dd>';
		}
		$v_md5hash  = ($this->status['copyright'])? "" : "<dd>{$this->root->_attach_messages['msg_md5hash']}:{$this->status['md5']}</dd>";
		if ($img_info) $img_info = "<dd>{$img_info}</dd>";
		if ($exif_tags) $exif_tags = "<dd>{$exif_tags}</dd>";
		
		$retval['body'] = <<<EOD
<p class="small">
 [<a href="{$this->root->script}?plugin=attach&amp;pcmd=list&amp;refer=$r_page">{$this->root->_attach_messages['msg_list']}</a>]
 [<a href="{$this->root->script}?plugin=attach&amp;pcmd=list">{$this->root->_attach_messages['msg_listall']}</a>]
</p>
<dl style="word-break: break-all;">
 <dt>$info</dt>
 <dd>{$this->root->_attach_messages['msg_page']}:$page_link</dd>
 {$v_filename}
 {$v_md5hash}
 <dd>{$this->root->_attach_messages['msg_filesize']}:{$this->size_str} ({$this->size} bytes)</dd>
 <dd>Content-type:{$this->type}</dd>
 <dd>{$this->root->_attach_messages['msg_date']}:{$this->time_str}</dd>
 <dd>{$this->root->_attach_messages['msg_dlcount']}:{$this->status['count'][$this->age]}</dd>
 <dd>{$this->root->_attach_messages['msg_owner']}:{$this->owner_str}</dd>
 $ref
 $img_info
 $exif_tags
 $msg_freezed
</dl>
$s_err
EOD;
		if ($is_editable || (! $this->owner_id && $pass && $this->status['uname'] !== 'System'))
		{
			$retval['body'] .= <<<EOD
<hr />
<form action="{$this->root->script}" method="post">
 <div>
  <input type="hidden" name="plugin" value="attach" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="file" value="$s_file" />
  <input type="hidden" name="age" value="{$this->age}" />
  $msg_delete
  $msg_freeze
  $msg_rename
  $msg_copyright
  $pass
  <input type="submit" value="{$this->root->_attach_messages['btn_submit']}" />
 </div>
</form>
EOD;
		}
		return $retval;
	}
	function delete($pass)
	{
//		global $adminpass,$_attach_messages,$vars,$X_admin,$X_uid,$script;
				
		if ($this->status['freeze'])
		{
			return xpwiki_plugin_attach::attach_info('msg_isfreeze');
		}
		
		$uid = $this->func->get_pg_auther($this->root->vars['page']);
		$admin = FALSE;
		if (!$this->is_owner())
		// 管理者とページ作成者とファイル所有者以外
		{
			if (! $this->func->pkwk_login($pass)) {
				if (($this->cont['ATTACH_PASSWORD_REQUIRE'] and (!$pass || md5($pass) != $this->status['pass'])) || $this->status['owner'])
					return xpwiki_plugin_attach::attach_info('err_password');
				
				if ($this->cont['ATTACH_DELETE_ADMIN_ONLY'] or $this->age)
					return xpwiki_plugin_attach::attach_info('err_adminpass');
			}
		}
		else
			$admin = TRUE;

		//バックアップ
		if ($this->age or 
			($admin and $this->cont['ATTACH_DELETE_ADMIN_NOBACKUP']))
		{
			@unlink($this->filename);
			$this->del_thumb_files();
			$this->func->attach_db_write(array('pgid'=>$this->pgid,'name'=>$this->file),"delete");
		}
		else
		{
			do
			{
				$age = ++$this->status['age'];
			}
			while (file_exists($this->basename.'.'.$age));
			
			if (!rename($this->basename,$this->basename.'.'.$age))
			{
				// 削除失敗 why?
				return array('msg'=>$this->root->_attach_messages['err_delete']);
			}

			$this->del_thumb_files();
			
			$this->status['count'][$age] = $this->status['count'][0];
			$this->status['count'][0] = 0;
			$this->putstatus();
		}
		if ($this->func->is_page($this->page))
		{
			$this->func->pkwk_touch_file($this->func->get_filename($this->page));
			$this->func->touch_db($this->page);
		}
		
		return array('msg'=>$this->root->_attach_messages['msg_deleted'],'redirect'=>$this->root->script."?plugin=attach&pcmd=upload&page=".rawurlencode($this->page));
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

		$fname = xpwiki_plugin_attach::regularize_fname ($newname, $this->page);
		if ($fname !== $newname) {
			$this->status['org_fname'] = $newname;
		} else {
			$this->status['org_fname'] = '';
		}

		$newbase = $this->cont['UPLOAD_DIR'] . $this->func->encode($this->page) . '_' . $this->func->encode($fname);
		if (file_exists($newbase)) {
			return array('msg'=>$this->root->_attach_messages['err_exists']);
		}
		if (! $this->cont['PLUGIN_ATTACH_RENAME_ENABLE'] || ! rename($this->basename, $newbase)) {
			return array('msg'=>$this->root->_attach_messages['err_rename']);
		}
		
		@unlink($this->logname);
		
		$this->rename_thumb_files($fname);
		
		$this->file = $fname;
		$this->basename = $newbase;
		$this->filename = $this->basename;
		$this->logname  = $this->basename . '.log';
		
		$this->action = 'update';
		
		$this->putstatus();
				
		return array('msg'=>$this->root->_attach_messages['msg_renamed']);
	}

	function freeze($freeze,$pass)
	{
//		global $adminpass,$vars,$X_admin,$X_uid,$_attach_messages,$script;
		
		$uid = $this->func->get_pg_auther($this->root->vars['page']);
		if (!$this->is_owner())
		// 管理者とページ作成者とファイル所有者以外
		{
			if (! $this->func->pkwk_login($pass)) {
				if (($this->cont['ATTACH_PASSWORD_REQUIRE'] and (!$pass || md5($pass) != $this->status['pass'])) || $this->status['owner'])
					return xpwiki_plugin_attach::attach_info('err_password');
			}
		}
		$this->getstatus();
		$this->status['freeze'] = $freeze;
		$this->putstatus();
		
		$param  = '&file='.rawurlencode($this->file).'&refer='.rawurlencode($this->page).
			($this->age ? '&age='.$this->age : '');
		$redirect = "{$this->root->script}?plugin=attach&pcmd=info$param";
		
		return array('msg'=>$this->root->_attach_messages[$freeze ? 'msg_freezed' : 'msg_unfreezed'],'redirect'=>$redirect);
	}
	function rotate($count,$pass)
	{
//		global $adminpass,$vars,$X_admin,$X_uid,$_attach_messages,$script;
		
		$uid = $this->func->get_pg_auther($this->root->vars['page']);
		if (!$this->is_owner())
		// 管理者とページ作成者とファイル所有者以外
		{
			if (! $this->func->pkwk_login($pass)) {
				if (($this->cont['ATTACH_PASSWORD_REQUIRE'] and (!$pass || md5($pass) != $this->status['pass'])) || $this->status['owner'])
					return xpwiki_plugin_attach::attach_info('err_password');
			}
		}
		
		$filemtime = filemtime($this->filename);
		$ret = HypCommonFunc::rotateImage($this->filename, $count);
		
		if ($ret) {
			$this->del_thumb_files();
			$this->func->pkwk_touch_file($this->filename, $filemtime);
			$this->getstatus();
			$this->status['imagesize'] = @ getimagesize($this->filename);
			$this->putstatus();
		}
		
		$param  = '&file='.rawurlencode($this->file).'&refer='.rawurlencode($this->page).
			($this->age ? '&age='.$this->age : '');
		$redirect = "{$this->root->script}?plugin=attach&pcmd=info$param";
		
		return array('msg'=>$this->root->_attach_messages[$ret ? 'msg_rotated_ok' : 'msg_rotated_ng'],'redirect'=>$redirect);
	}
	function copyright($copyright,$pass)
	{
//		global $adminpass,$vars,$X_admin,$X_uid,$_attach_messages,$script;
		
		$uid = $this->func->get_pg_auther($this->root->vars['page']);
		if (!$this->is_owner())
		// 管理者とページ作成者とファイル所有者以外
		{
			if (! $this->func->pkwk_login($pass)) {
				if (($this->cont['ATTACH_PASSWORD_REQUIRE'] and (!$pass || md5($pass) != $this->status['pass'])) || $this->status['owner'])
					return xpwiki_plugin_attach::attach_info('err_password');
			}
		}
		
		$this->getstatus();
		$this->status['copyright'] = $copyright;
		$this->putstatus();
		
		$param  = '&file='.rawurlencode($this->file).'&refer='.rawurlencode($this->page).
			($this->age ? '&age='.$this->age : '');
		$redirect = "{$this->root->script}?plugin=attach&pcmd=info$param";
		
		return array('msg'=>$this->root->_attach_messages[$copyright ? 'msg_copyrighted' : 'msg_uncopyrighted'],'redirect'=>$redirect);
	}
	function open()
	{
		$this->getstatus();
		if (!$this->is_owner())
		{
			if ($this->status['copyright'])
				return xpwiki_plugin_attach::attach_info('err_copyright');
		}
		$this->status['count'][$this->age]++;
		$this->putstatus();
		
		$filename = $this->status['org_fname'];

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

		// clear output buffer
		while( ob_get_level() ) {
			ob_end_clean() ;
		}

		ini_set('default_charset','');
		mb_http_output('pass');
		
		// SSL環境にIEでアクセスするとファイルが開けないバグ対策 orz...
		if ($this->cont['UA_NAME'] === 'MSIE' && strtolower($this->cont['HOME_URL']{4}) === 's') {
			header('Pragma:');
			header('Cache-Control:');
		}
		
		// 画像以外(管理者所有を除く)はダウンロード扱いにする(XSS対策)
		$_i_size = getimagesize($this->filename);
		if (! isset($_i_size[2]) || $_i_size[2] === 4) $_i_size[2] = FALSE;
		if ($this->status['admins'] || $_i_size[2])
		{
			header('Content-Disposition: inline; filename="'.$filename.'"');
		}
		else
		{
			header('Content-Disposition: attachment; filename="'.$filename.'"');
		}
		
		header('Content-Length: '.$this->size);
		header('Content-Type: '.$this->type);
		@readfile($this->filename);
		exit;
	}

	// 該当ファイルのサムネイルを削除
	function del_thumb_files(){
		$dir = opendir($this->cont['UPLOAD_DIR']."s/")
			or die('directory '.$this->cont['UPLOAD_DIR'].'s/ is not exist or not readable.');
		
		$root = $this->cont['UPLOAD_DIR']."s/".$this->func->encode($this->page).'_';
		$_file = preg_split('/(\.[a-zA-Z]+)?$/', $this->file, -1, PREG_SPLIT_DELIM_CAPTURE);
		$_file = $this->func->encode($_file[0]) . $_file[1];
		for ($i = 1; $i < 100; $i++)
		{
			$file = $root . $i . '_' . $_file;
			if (file_exists($file))
			{
				unlink($file);
			}
		}
	}
	
	// 該当ファイルのサムネイルをリネーム
	function rename_thumb_files($newname){
		$dir = opendir($this->cont['UPLOAD_DIR']."s/")
			or die('directory '.$this->cont['UPLOAD_DIR'].'s/ is not exist or not readable.');
		
		$root = $this->cont['UPLOAD_DIR']."s/".$this->func->encode($this->page).'_';
		for ($i = 1; $i < 100; $i++)
		{
			$base    = $root.$this->func->encode($i."%");
			$file    = $base.$this->func->encode($this->file);
			$newfile = $base.$this->func->encode($newname);
			if (file_exists($file))
			{
				rename($file, $newfile);
			}
		}
	}
	
	// 管理者、ページ作成者またはファイル所有者か？
	function is_owner() {
		if ($this->func->is_owner($this->page)) return TRUE;
		if ($this->owner_id) {
			if ($this->root->userinfo['uid'] === $this->owner_id) return TRUE;
		} else {
			if ($this->root->userinfo['ucd'] === $this->status['ucd']) return TRUE;
		}
		return FALSE;
	}
}
	
// ファイルコンテナ
class XpWikiAttachFiles
{
	var $page;
	var $pgid;
	var $files = array();
	var $count = 0;
	var $max = 50;
	var $start = 0;
	var $order = "";
	
	function XpWikiAttachFiles(& $xpwiki, $page)
	{
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;

		$this->page = $page;
	}
	function add($file,$age)
	{
		$this->files[$file][$age] = &new XpWikiAttachFile($this->xpwiki, $this->page,$file,$age,$this->pgid);
	}
	// ファイル一覧を取得
	function toString($flat,$fromall=FALSE,$mode="")
	{
		if (!$this->func->check_readable($this->page,FALSE,FALSE))
		{
			return str_replace('$1',$this->func->make_pagelink($this->page),$this->root->_title_cannotread);
		}
		if ($flat)
		{
			return $this->to_flat();
		}
		
		$this->func->add_tag_head('attach.css');
		
		$ret = '';
		$files = array_keys($this->files);
		$navi = "";
		$pcmd = ($mode == "imglist")? "imglist" : "list";
		$pcmd2 = ($mode == "imglist")? "list" : "imglist";
		if (!$fromall)
		{
			$url = $this->root->script."?plugin=attach&amp;pcmd={$pcmd}&amp;refer=".rawurlencode($this->page)."&amp;order=".$this->order."&amp;start=";
			$url2 = $this->root->script."?plugin=attach&amp;pcmd={$pcmd}&amp;refer=".rawurlencode($this->page)."&amp;start=";
			$url3 = $this->root->script."?plugin=attach&amp;pcmd={$pcmd2}&amp;refer=".rawurlencode($this->page)."&amp;order=".$this->order."&amp;start=".$this->start;
			$sort_time = ($this->order == "name")? " [ <a href=\"{$url2}0&amp;order=time\">Sort by time</a> |" : " [ <b>Sort by time</b> |";
			$sort_name = ($this->order == "name")? " <b>Sort by name</b> ] " : " <a href=\"{$url2}0&amp;order=name\">Sort by name</a> ] ";
			$mode_tag = ($mode == "imglist")? "[ <a href=\"$url3\">List view<a> ]":"[ <a href=\"$url3\">Image view</a> ]";
			
			if ($this->max < $this->count)
			{
				$_start = $this->start + 1;
				$_end = $this->start + $this->max;
				$_end = min($_end,$this->count);
				$now = $this->start / $this->max + 1;
				$total = ceil($this->count / $this->max);
				$navi = array();
				for ($i=1;$i <= $total;$i++)
				{
					if ($now == $i)
						$navi[] = "<b>$i</b>";
					else
						$navi[] = "<a href=\"".$url.($i - 1) * $this->max."\">$i</a>";
				}
				$navi = join(' | ',$navi);
				
				$prev = max(0,$now - 1);
				$next = $now;
				$prev = ($prev)? "<a href=\"".$url.($prev - 1) * $this->max."\" title=\"Prev\"> <img src=\"./image/prev.png\" width=\"6\" height=\"12\" alt=\"Prev\"> </a>|" : "";
				$next = ($next < $total)? "|<a href=\"".$url.$next * $this->max."\" title=\"Next\"> <img src=\"./image/next.png\" width=\"6\" height=\"12\" alt=\"Next\"> </a>" : "";
				
				$navi = "<div class=\"page_navi\">| $navi |<br />[{$prev} $_start - $_end / ".$this->count." files {$next}]<br />{$sort_time}{$sort_name}{$mode_tag}</div>";
			}
			else
			{
				$navi = "<div class=\"page_navi\">{$sort_time}{$sort_name}{$mode_tag}</div>";
			}
		}
		$col = 1;
		foreach ($files as $file)
		{
			$_files = array();
			foreach (array_keys($this->files[$file]) as $age)
			{
				$_files[$age] = $this->files[$file][$age]->toString(FALSE,TRUE,$mode);
			}
			if (!array_key_exists(0,$_files))
			{
				$_files[0] = htmlspecialchars($file);
			}
			ksort($_files);
			$_file = $_files[0];
			unset($_files[0]);
			if ($mode == "imglist")
			{
				$ret .= "|$_file";
				if (count($_files))
				{
					$ret .= "~\n".join("~\n-",$_files);
				}
				$mod = $col % 4;
				if ($mod === 0)
				{
					$ret .= "|\n";
					$col = 0;
				}
				$col++;
			}
			else
			{
				$ret .= " <li>$_file\n";
				if (count($_files))
				{
					$ret .= "<ul>\n<li>".join("</li>\n<li>",$_files)."</li>\n</ul>\n";
				}
				$ret .= " </li>\n";
			}
		}
		
		if ($mode == "imglist")
		{
			if ($mod) $ret .= str_repeat("|>",4-$mod)."|\n";
			//if ($mod) $ret .= "|\n";
			$ret = "|CENTER:|CENTER:|CENTER:|CENTER:|c\n".$ret;
		 	$ret = $this->func->convert_html($ret);
		}
		
		$showall = ($fromall && $this->max < $this->count)? " [ <a href=\"{$this->root->script}?plugin=attach&amp;pcmd={$pcmd}&amp;refer=".rawurlencode($this->page)."\">Show All</a> ]" : "";
		$allpages = ($fromall)? "" : " [ <a href=\"{$this->root->script}?plugin=attach&amp;pcmd={$pcmd}\" />All Pages</a> ]";
		return $navi.($navi? "<hr />":"")."<div class=\"filelist_page\">".$this->func->make_pagelink($this->page)."<small> (".$this->count." file".(($this->count===1)?"":"s").")".$showall.$allpages."</small></div>\n<ul>\n$ret</ul>".($navi? "<hr />":"")."$navi\n";
	}
	// ファイル一覧を取得(inline)
	function to_flat()
	{
//		global $script;
		$ret = '';
		$files = array();
		foreach (array_keys($this->files) as $file)
		{
			if (array_key_exists(0,$this->files[$file]))
			{
				$files[$file] = &$this->files[$file][0];
			}
		}
		uasort($files,array('XpWikiAttachFile','datecomp'));
		//if ($max) $files = array_slice($files,$start,$max);
		
		foreach (array_keys($files) as $file)
		{
			$ret .= $files[$file]->toString(TRUE,TRUE).' ';
		}
		$more = $this->count - $this->max;
		$more = ($this->count > $this->max)? "... more ".$more." files. [ <a href=\"{$this->root->script}?plugin=attach&amp;pcmd=list&amp;refer=".rawurlencode($this->page)."\">Show All</a> ]" : "";
		return $ret.$more;
	}
}
	// ページコンテナ
class XpWikiAttachPages
{
	var $pages = array();
	var $start = 0;
	var $max = 50;
	var $mode = "";
	var $err = 0;
	
	function XpWikiAttachPages(& $xpwiki, $page='',$age=NULL,$isbn=true,$max=50,$start=0,$fromall=FALSE,$f_order="time",$mode="")
	{
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;

//		global $xoopsDB,$X_admin,$X_uid;
		$this->mode = $mode;
		if ($page)
		{
			// 閲覧権限チェック
			if (!$fromall && !$this->func->check_readable($page,false,false)) return;
			
			$this->pages[$page] = &new XpWikiAttachFiles($this->xpwiki, $page);
			
			$pgid = $this->func->get_pgid_by_name($page);
			$this->pages[$page]->pgid = $pgid;
			
			// WHERE句
			$where = array();
			$where[] = "`pgid` = {$pgid}";
			if (!$isbn) $where[] = "`mode` != '1'";
			if (!is_null($age)) $where[] = "`age` = $age";
			//if ($mode == "imglist") $where[] = "`type` LIKE 'image%' AND `age` = 0";
			//if ($mode == "imglist") $where[] = "`age` = 0";
			$where = " WHERE ".join(' AND ',$where);
			
			// このページの添付ファイル数取得
			$query = "SELECT count(*) as count FROM `".$this->xpwiki->db->prefix($this->root->mydirname."_attach")."`{$where};";
			if (!$result = $this->xpwiki->db->query($query))
				{
					$this->err = 1;
					return;
				}
			list($_count) = $this->xpwiki->db->fetchRow($result);
			if (!$_count) return;
			
			$this->pages[$page]->count = $_count;
			$this->pages[$page]->max = $max;
			$this->pages[$page]->start = $start;
			$this->pages[$page]->order = $f_order;
			
			// ファイル情報取得
			$order = ($f_order == "name")? " ORDER BY name ASC" : " ORDER BY mtime DESC";
			$limit = " LIMIT {$start},{$max}";
			$query = "SELECT name,age FROM `".$this->xpwiki->db->prefix($this->root->mydirname."_attach")."`{$where}{$order}{$limit};";
			$result = $this->xpwiki->db->query($query);
			while($_row = $this->xpwiki->db->fetchRow($result))
			{
				$_file = $_row[0];
				$_age = $_row[1];
				$this->pages[$page]->add($_file,$_age);
			}
		}
		else
		{
			// WHERE句
			$where = $this->func->get_readable_where('p.');
			
			if ($where) $where = ' WHERE '.$where;
			
			// 添付ファイルのあるページ数カウント
			$query = "SELECT DISTINCT p.pgid FROM ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." p INNER JOIN ".$this->xpwiki->db->prefix($this->root->mydirname."_attach")." a ON p.pgid=a.pgid{$where}";
			$result = $this->xpwiki->db->query($query);
			
			$this->count = $result ? mysql_num_rows($result) : 0;
			
			$this->max = $max;
			$this->start = $start;
			$this->order = $f_order;
			
			// ページ情報取得
			$order = ($f_order == "name")? " ORDER BY p.name ASC" : " ORDER BY p.editedtime DESC";
			$limit = " LIMIT $start,$max";
			
			$query = "SELECT DISTINCT p.name FROM ".$this->xpwiki->db->prefix($this->root->mydirname."_pginfo")." p INNER JOIN ".$this->xpwiki->db->prefix($this->root->mydirname."_attach")." a ON p.pgid=a.pgid{$where}{$order}{$limit};";
			if (!$result = $this->xpwiki->db->query($query)) echo "QUERY ERROR : ".$query;
			
			//if ($this->root->userinfo['admin']) echo $query;
			
			while($_row = $this->xpwiki->db->fetchRow($result))
			{
				$this->XpWikiAttachPages($this->xpwiki,$_row[0],$age,$isbn,20,0,TRUE,$f_order,$mode);
			}
		}
	}
	function toString($page='',$flat=FALSE)
	{
//		global $script;
		if ($page !== '')
		{
			if (!array_key_exists($page,$this->pages))
			{
				return '';
			}
			return $this->pages[$page]->toString($flat,FALSE,$this->mode);
		}
		$pcmd = ($this->mode == "imglist")? "imglist" : "list";
		$pcmd2 = ($this->mode == "imglist")? "list" : "imglist";
		$url = $this->root->script."?plugin=attach&amp;pcmd={$pcmd}&amp;order=".$this->order."&amp;start=";
		$url2 = $this->root->script."?plugin=attach&amp;pcmd={$pcmd}&amp;start=";
		$url3 = $this->root->script."?plugin=attach&amp;pcmd={$pcmd2}&amp;order=".$this->order."&amp;start=".$this->start;
		$sort_time = ($this->order == "name")? " [ <a href=\"{$url2}0&amp;order=time\">Sort by time</a> |" : " [ <b>Sort by time</b> |";
		$sort_name = ($this->order == "name")? " <b>Sort by name</b> ] " : " <a href=\"{$url2}0&amp;order=name\">Sort by name</a> ] ";
		$mode_tag = ($this->mode == "imglist")? "[ <a href=\"$url3\">List view<a> ]":"[ <a href=\"$url3\">Image view</a> ]";
		
		$_start = $this->start + 1;
		$_end = $this->start + $this->max;
		$_end = min($_end,$this->count);
		$now = $this->start / $this->max + 1;
		$total = ceil($this->count / $this->max);
		$navi = array();
		
		for ($i=1;$i <= $total;$i++)
		{
			if ($now == $i)
				$navi[] = "<b>$i</b>";
			else
				$navi[] = "<a href=\"".$url.($i - 1) * $this->max."\">$i</a>";
		}
		$navi = join(' | ',$navi);
		$prev = max(0,$now - 1);
		$next = $now;
		$prev = ($prev)? "<a href=\"".$url.($prev - 1) * $this->max."\" title=\"Prev\"> <img src=\"./image/prev.png\" width=\"6\" height=\"12\" alt=\"Prev\"> </a>|" : "";
		$next = ($next < $total)? "|<a href=\"".$url.$next * $this->max."\" title=\"Next\"> <img src=\"./image/next.png\" width=\"6\" height=\"12\" alt=\"Next\"> </a>" : "";
		$navi = "<div class=\"page_navi\">| $navi |<br />[{$prev} $_start - $_end / ".$this->count." pages {$next}]<br />{$sort_time}{$sort_name}{$mode_tag}</div>";
		
		$ret = "";
		$pages = array_keys($this->pages);
		//sort($pages);
		foreach ($pages as $page)
		{
			//$ret .= '<li>'.$this->pages[$page]->toString($flat)."</li>\n";
			$ret .= $this->pages[$page]->toString($flat,TRUE,$this->mode)."\n";
		}
		//return "\n<ul>\n".$ret."</ul>\n";
		return "\n$navi".($navi? "<hr />":"")."\n$ret\n".($navi? "<hr />":"")."$navi\n";
		
	}
}

?>