<?php
class xpwiki_plugin_attach extends xpwiki_plugin {
	
	var $listed = FALSE;
	
	function plugin_attach_init () {


	/////////////////////////////////////////////////
	// PukiWiki - Yet another WikiWikiWeb clone.
	//
	//  $Id: attach.inc.php,v 1.25 2007/11/20 07:08:21 nao-pon Exp $
	//  ORG: attach.inc.php,v 1.31 2003/07/27 14:15:29 arino Exp $
	//
	
	/*
	 プラグイン attach
	
	 changed by Y.MASUI <masui@hisec.co.jp> http://masui.net/pukiwiki/
	 modified by PANDA <panda@arino.jp> http://home.arino.jp/
		*/
		// アップロード可能なファイルサイズ(php.iniから取得)
		$this->cont['PLUGIN_ATTACH_MAX_FILESIZE'] = $this->func->return_bytes(ini_get('upload_max_filesize')); // getini
		
		// 管理者だけが添付ファイルをアップロードできるようにする
		$this->cont['ATTACH_UPLOAD_ADMIN_ONLY'] = FALSE; // FALSE or TRUE
	
		// 管理者だけが ShockwaveFlash ファイルをアップロードできるようにする
		$this->cont['ATTACH_UPLOAD_FLASH_ADMIN_ONLY'] = FALSE;

		// ページ編集権限がある人のみ添付ファイルをアップロードできるようにする
		$this->cont['ATTACH_UPLOAD_EDITER_ONLY'] = TRUE; // FALSE or TRUE
	
		// ページオーナー権限がない場合にアップロードできる拡張子(カンマ区切り)
		// ATTACH_UPLOAD_EDITER_ONLY = FALSE のときに使用
		$this->cont['ATTACH_UPLOAD_EXTENSION'] = 'jpg, jpeg, gif, png, txt, spch';

		// 管理者とページ作成者だけが添付ファイルを削除できるようにする
		$this->cont['ATTACH_DELETE_ADMIN_ONLY'] = FALSE; // FALSE or TRUE
	
		// 管理者とページ作成者が添付ファイルを削除するときは、バックアップを作らない
		$this->cont['ATTACH_DELETE_ADMIN_NOBACKUP'] = TRUE; // FALSE or TRUE 
	
		// ゲストユーザーのアップロード/削除時にパスワードを要求する
		// (ADMIN_ONLYが優先 TRUE を強く奨励)
		$this->cont['ATTACH_PASSWORD_REQUIRE'] = TRUE; // FALSE or TRUE

		// 添付ファイル名を変更できるようにする
		$this->cont['PLUGIN_ATTACH_RENAME_ENABLE'] =  TRUE; // FALSE or TRUE

		// ファイルのアクセス権 
		$this->cont['ATTACH_FILE_MODE'] = 0644; 
		//define('ATTACH_FILE_MODE',0604); // for XREA.COM 
	
		// イメージファイルのアクセス権
		if  (ini_get('safe_mode') == "1")
		{  
			//セーフモード時はサムネイル作成と回転のためゲストに書き込み権限が必要
			$this->cont['ATTACH_IMGFILE_MODE'] =  0606;
		}
		else
		{
			$this->cont['ATTACH_IMGFILE_MODE'] =  $this->cont['ATTACH_FILE_MODE'];
		}
	
		// open 時にリファラをチェックする
		// 0:チェックしない, 1:未定義は許可, 2:未定義も不許可
		// 未設定 = URL直打ち, ノートンなどでリファラを遮断 など。
		$this->cont['ATTACH_REFCHECK'] = 1;
	
		// file icon image
		if (!isset($this->cont['FILE_ICON']))
		{
			$this->cont['FILE_ICON'] = '<img src="./image/file.png" width="20" height="20" alt="file" style="border-width:0px" />';
		}
	
		// mime-typeを記述したページ
		$this->cont['ATTACH_CONFIG_PAGE_MIME'] = 'plugin/attach/mime-type';
	
		// 詳細情報・ファイル一覧(イメージモード)で使用する ref プラグインの追加オプション
		$this->cont['ATTACH_CONFIG_REF_OPTION'] = ',mw:160,mh:120';
		
		// ref プラグインの添付リンクから呼び出された場合のサムネイル作成サイズ規定値(px)
		$this->cont['ATTACH_CONFIG_REF_THUMB'] = 240;
	
		// tar
		$this->cont['TAR_HDR_LEN'] = 512;			// ヘッダの大きさ
		$this->cont['TAR_BLK_LEN'] = 512;			// 単位ブロック長さ
		$this->cont['TAR_HDR_NAME_OFFSET'] = 0;	// ファイル名のオフセット
		$this->cont['TAR_HDR_NAME_LEN'] = 100;		// ファイル名の最大長さ
		$this->cont['TAR_HDR_SIZE_OFFSET'] = 124;	// サイズへのオフセット
		$this->cont['TAR_HDR_SIZE_LEN'] = 12;		// サイズの長さ
		$this->cont['TAR_HDR_TYPE_OFFSET'] = 156;	// ファイルタイプへのオフセット
		$this->cont['TAR_HDR_TYPE_LEN'] = 1;		// ファイルタイプの長さ

	
		// 添付可能な拡張子を配列化
		if (!$this->cont['ATTACH_UPLOAD_ADMIN_ONLY'] && $this->cont['ATTACH_UPLOAD_EXTENSION'])
		{
			$this->root->allow_extensions = explode(",",str_replace(" ","",$this->cont['ATTACH_UPLOAD_EXTENSION']));
		}
		else
		{
			$this->root->allow_extensions = array();
		}

	}
	
	//-------- convert
	function plugin_attach_convert()
	{
		$this->converted = TRUE;
		
		if (!ini_get('file_uploads'))
		{
			return 'file_uploads disabled';
		}
		
		$noattach = $nolist = $noform = FALSE;
		
		if (func_num_args() > 0)
		{
			foreach (func_get_args() as $arg)
			{
				$arg = strtolower($arg);
				$nolist |= ($arg == 'nolist');
				$noform |= ($arg == 'noform');
				$noattach |= ($arg == 'noattach');
			}
		}
		$ret = '';
		if ($noattach) {
			$nolist = TRUE;
			$noform = TRUE;
			$this->listed = TRUE;
		}
		if (!$nolist)
		{
			$obj = &new XpWikiAttachPages($this->xpwiki, $this->root->vars['page']);
			$ret .= $obj->toString($this->root->vars['page'],FALSE);
			$this->listed = TRUE;
		}
		if (!$noform)
		{
			$ret .= $this->attach_form($this->root->vars['page']);
		}
		
		return $ret;
	}
	
	//-------- action
	function plugin_attach_action()
	{
		// backward compatible
		if (array_key_exists('openfile',$this->root->vars))
		{
			$this->root->vars['pcmd'] = 'open';
			$this->root->vars['file'] = $this->root->vars['openfile'];
		}
		if (array_key_exists('delfile',$this->root->vars))
		{
			$this->root->vars['pcmd'] = 'delete';
			$this->root->vars['file'] = $this->root->vars['delfile'];
		}
		if (empty($this->root->vars['refer'])) $this->root->vars['refer'] = $this->root->vars['page'];
		
		$age = array_key_exists('age',$this->root->vars) ? $this->root->vars['age'] : 0;
		$pcmd = array_key_exists('pcmd',$this->root->vars) ? $this->root->vars['pcmd'] : '';
		
		if (!empty($this->root->vars['page']) && $this->func->is_page($this->root->vars['page']) && empty($pcmd))
		{
			//ページが指定されていて pcmd がない時は 'upload' にする
			$pcmd = 'upload';
		}
		
		if (empty($this->root->vars['page']) && $pcmd === 'upload') {
			// ページ名の指定がない場合は list
			$pcmd = 'list';
		}
		
		// リファラチェック
		if ($this->cont['ATTACH_REFCHECK'])
		{
			if ($pcmd == 'open' && !$this->func->refcheck($this->cont['ATTACH_REFCHECK']-1))
			{
				//redirect_header(XOOPS_WIKI_URL,0,"Access denied!");
				//echo "Access Denied!";
				@readfile("./image/accdeny.gif");
				exit;
			}
		}
	
		// Authentication
		if (array_key_exists('refer',$this->root->vars))
		{
			if ($pcmd == 'upload')
			{
				//アップロード
				if ($this->cont['ATTACH_UPLOAD_ADMIN_ONLY'])
				{
					$check = $this->root->userinfo['admin'];
				}
				else
				{
					if ($this->cont['ATTACH_UPLOAD_EDITER_ONLY'])
					{
						$check = $this->func->check_editable($this->root->vars['refer']);
					}
					else
					{
						$check = $this->func->check_readable($this->root->vars['refer']);
					}
				}
				if (!$check) return array('result'=>FALSE,'msg'=>$this->root->_attach_messages['err_noparm']);
			}
			else
			{
				//その他
				if (!$this->func->check_readable($this->root->vars['refer'])) return array('result'=>FALSE,'msg'=>_MD_PUKIWIKI_NO_VISIBLE);
			}
			
			// Upload
			if (array_key_exists('attach_file',$_FILES))
			{
				$pass = (!empty($this->root->vars['pass'])) ? md5($this->root->vars['pass']) : NULL;
				$copyright = (isset($this->root->post['copyright']))? TRUE : FALSE;
				$ret = $this->attach_upload($_FILES['attach_file'],$this->root->vars['refer'],$pass,$copyright);
				if (!empty($this->root->post['returi'])) {
					$ret['redirect'] = $this->root->siteinfo['host'].$this->root->post['returi'];
				}
				return $ret;
			}
		}
		
		$pass = (!empty($this->root->vars['pass'])) ? $this->root->vars['pass'] : NULL;
		switch ($pcmd)
		{
			case 'info'      : return $this->attach_info();
			case 'delete'    : return $this->attach_delete($pass);
			case 'open'      : return $this->attach_open();
			case 'list'      : return $this->attach_list();
			case 'imglist'   : return $this->attach_list('imglist');
			case 'freeze'    : return $this->attach_freeze(TRUE,$pass);
			case 'unfreeze'  : return $this->attach_freeze(FALSE,$pass);
			case 'rename'    : return $this->attach_rename($pass);
			case 'upload'    : return $this->attach_showform();
			case 'copyright0': return $this->attach_copyright(FALSE, $pass);
			case 'copyright1': return $this->attach_copyright(TRUE, $pass);
			case 'rotate'    : return $this->attach_rotate($pass);
		}
		if (empty($this->root->vars['page']) || !$this->func->is_page($this->root->vars['page']))
		{
			return $this->attach_list();
		}
		
		return false;
	}
	
	//-------- call from skin
	function attach_filelist($isbn=false)
	{
	//	global $vars,$_attach_messages;
		if ($this->listed) return '';
		
		$obj = &new XpWikiAttachPages($this->xpwiki, $this->root->vars['page'],0,$isbn,20);
		if ($obj->err === 1) return '<span style="color:red;font-size:150%;font-weight:bold;">DB ERROR!: Please initialize an attach file database on an administrator screen.</span>';
	
		if (!array_key_exists($this->root->vars['page'],$obj->pages))
		{
			return '';
		}
		$_tmp = $obj->toString($this->root->vars['page'],TRUE);
		if ($_tmp) $_tmp = '<a href="'.$this->root->script.'?plugin=attach&amp;pcmd=list&amp;refer='.rawurlencode($this->root->vars['page']).'" title="'.strip_tags($this->root->_attach_messages['msg_list']).'">' . $this->root->_attach_messages['msg_file'].'</a>: '.$_tmp."\n";
		return $_tmp;
	}
	//-------- 実体
	//ファイルアップロード
	function attach_upload($file,$page,$pass=NULL,$copyright=FALSE)
	{
		// $pass=NULL : パスワードが指定されていない
		// $pass=TRUE : アップロード許可
		
		if ($file['tmp_name'] == '' or !is_uploaded_file($file['tmp_name']) or !$file['size'])
		{
			return array('result'=>FALSE);
		}
		if (!$this->root->userinfo['uid'] && $this->cont['ATTACH_PASSWORD_REQUIRE'] && !$pass) {
			return array('result'=>FALSE,'msg'=>$this->root->_attach_messages['msg_require']);
		}
		if ($file['size'] > $this->cont['PLUGIN_ATTACH_MAX_FILESIZE'])
		{
			return array('result'=>FALSE,'msg'=>$this->root->_attach_messages['err_exceed']);
		}

		if (!$this->func->is_pagename($page) or ($pass !== TRUE and $this->cont['ATTACH_UPLOAD_EDITER_ONLY'] and !$this->func->is_editable($page)))
		{
			return array('result'=>FALSE,'msg'=>$this->root->_attach_messages['err_noparm']);
		}
		if ($this->cont['ATTACH_UPLOAD_ADMIN_ONLY'] and $pass !== TRUE and !$this->root->userinfo['admin'])
		{
			return array('result'=>FALSE,'msg'=>$this->root->_attach_messages['err_adminpass']);
		}
		//$copyright = (isset($post['copyright']))? TRUE : FALSE;

		// ページが無ければ空ページを作成
		if (!$this->func->is_page($page)) {
			$this->func->page_write($page, "\n");
		}

		if ( strcasecmp(substr($file['name'],-4),".tar") == 0 && $this->root->post['untar_mode'] == "on" ) {
			// UploadされたTarアーカイブを展開添付する
	
			// Tarファイル展開
			$etars = $this->untar( $file['tmp_name'], $this->cont['UPLOAD_DIR']);
	
			// 展開されたファイルを全てアップロードファイルとして追加
			foreach ( $etars as $efile ) {
				$res = $this->do_upload( $page,
				mb_convert_encoding($efile['extname'], $this->cont['SOURCE_ENCODING'],"auto"),
				$efile['tmpname'],$copyright,$pass);
				if ( ! $res['result'] ) {
					unlink( $efile['tmpname']);
				}
			}
	
			// 最後の返り値でreturn
			return $res;
		} else {
			// 通常の単一ファイル添付処理
			return $this->do_upload($page,$file['name'],$file['tmp_name'],$copyright,$pass);
		}
	}
	
	function do_upload($page,$fname,$tmpname,$copyright=FALSE,$pass=NULL,$notouch=FALSE)
	{
	//	global $_attach_messages,$X_uid,$X_admin;
		
		$_action = "insert";
		// style.css
		if ($fname == "style.css" && $this->func->is_owner($page))
		{
			if ( is_uploaded_file($tmpname) )
			{
				$_pagecss_file = $this->cont['CACHE_DIR'].$this->func->get_pgid_by_name($page).".css";
				if (file_exists($_pagecss_file)) unlink($_pagecss_file);
				if (move_uploaded_file($tmpname,$_pagecss_file))
				{
					$this->attach_chmod($_pagecss_file);
					// 空のファイルの場合はファイル削除
					if (!trim(join('',file($_pagecss_file))))
					{
						unlink($_pagecss_file);
						return array('result'=>TRUE,'msg'=>$this->root->_attach_messages['msg_unset_css']);
					}
					else
					{
						// 外部ファイルの参照を禁止するための書き換え
						$_data = join('',file($_pagecss_file));
						$_data = preg_replace("#(ht|f)tps?://#","",$_data);
						if ($fp = fopen($_pagecss_file,"wb"))
						{
							fputs($fp,$_data);
							fclose($fp);
						}
						
						return array('result'=>TRUE,'msg'=>$this->root->_attach_messages['msg_set_css']);
					}
				}
				else
					return array('result'=>FALSE,'msg'=>$this->root->_attach_messages['err_exists']);
				
			}
		}
		
		// ページオーナー権限がない場合は拡張子をチェック
		if ($this->root->allow_extensions && !$this->func->is_owner($page)
			 && !preg_match("/\.(".join("|",$this->root->allow_extensions).")$/i",$fname)) {
			return array('result'=>FALSE,'msg'=>str_replace('$1',preg_replace('/.*\.([^.]*)$/',"$1",$fname),$this->root->_attach_messages['err_extension']));
		}
		
		// Flashファイルの検査
		if ($this->cont['ATTACH_UPLOAD_FLASH_ADMIN_ONLY']) {
			$_size = @getimagesize($tmpname);
			if (!$this->root->userinfo['admin'] && ($_size[2] === 4 || $_size[2] === 13)) {
				return array('result'=>FALSE,'msg'=>$this->root->_attach_messages['err_isflash']);
			}
		}
		
		// オリジナルファイル名
		$org_fname = $fname;
		
		// ファイル名指定あり
		if (!empty($this->root->post['filename'])) {
			$fname = $this->root->post['filename'];
		}
		
		// ファイル名 文字数のチェック
		$fname = $this->regularize_fname($fname, $page);
		
		// ファイル名が存在する場合は、数字を付け加える
		$fi = 0;
		if (preg_match("/^(.+)(\.[^.]*)$/",$fname,$match)) {
			$_fname = $match[1];
			$_ext = $match[2];
		} else {
			$_fname = $fname;
			$_ext = '';
		}
		do {
			$obj = &new XpWikiAttachFile($this->xpwiki, $page, $fname);
			$fname = $_fname.'_'.($fi++).$_ext;
		} while ($obj->exist);
		
		if ( is_uploaded_file($tmpname) ) {
			if ($obj->exist)
			{
				return array('result'=>FALSE,'msg'=>$this->root->_attach_messages['err_exists']);
			}
			
			if (move_uploaded_file($tmpname,$obj->filename)) {
				$this->attach_chmod($obj->filename);
			} else {
				return array('result'=>FALSE,'msg'=>$this->root->_attach_messages['err_exists']);
			}
		} else {
			if (file_exists($obj->filename)) {
				unlink($obj->filename);
				$_action = "update";
			}
			if (rename($tmpname,$obj->filename)) {
				$this->attach_chmod($obj->filename);
			} else {
				return array('result'=>FALSE,'msg'=>$this->root->_attach_messages['err_exists']);
			}
		}
		
		if (!$notouch && $this->func->is_page($page)) {
			$this->func->pkwk_touch_file($this->func->get_filename($page));
			$this->func->touch_db($page);
			$this->func->push_page_changes($page, 'Attach file: '.htmlspecialchars($obj->file). ' by '.$this->root->userinfo['uname_s']);
		}
		
		$obj->getstatus();
		$obj->status['pass'] = ($pass !== TRUE and $pass !== NULL) ? $pass : '';
		$obj->status['copyright'] = $copyright;
		$obj->status['owner'] = $this->root->userinfo['uid'];
		$obj->status['ucd']   = $this->root->userinfo['ucd'];
		$obj->status['uname'] = $this->root->userinfo['uname'];
		$obj->status['md5'] = md5_file($obj->filename);
		$obj->status['admins'] = (int)$this->func->check_admin($this->root->userinfo['uid']);
		if ($fname !== $org_fname) {
			$obj->status['org_fname'] = $org_fname;
		} else {
			$obj->status['org_fname'] = '';
		}
		$obj->action = $_action;
		$obj->putstatus();
		
		if (!empty($this->root->vars['refid'])) {
			$this->ref_replace($page, $this->root->vars['refid'], $obj->file, $obj->filename);
		}
		
		return array('result'=>TRUE,'msg'=>$this->root->_attach_messages['msg_uploaded']);
	}
	
	// ref プラグインのソース置換
	function ref_replace($page, $refid, $name, $filename) {
		// サムネイルサイズ指定？
		$prm = '';
		if (!empty($this->root->vars['make_thumb']) && @getimagesize($filename)) {
			if (!empty($this->root->vars['thumb_r'])) {
				$prm = ','.htmlspecialchars((int)$this->root->vars['thumb_r']).'%';
			} else {
				if (!empty($this->root->vars['thumb_w'])) {
					$prm = ',mw:'.htmlspecialchars((int)$this->root->vars['thumb_w']);
				}
				if (!empty($this->root->vars['thumb_h'])) {
					$prm .= ',mh:'.htmlspecialchars((int)$this->root->vars['thumb_h']);
				}
			}
		}
		$_tmp = $postdata = $this->func->get_source($page);
		$postdata = preg_replace('/((?:&|#)ref)\(ID\$'.preg_quote($refid, '/').'((?:,[^\)]+)?\);?)/', "$1(".$name.$prm."$2", $postdata);
		if ($_tmp !== $postdata) {
			$this->func->file_write($this->cont['DATA_DIR'], $page, join('', $postdata), TRUE);
		}	
	}
	
	// ファイルアクセス権限を設定
	function attach_chmod($file)
	{
		if (isset($this->cont['ATTACH_IMGFILE_MODE']) && @getimagesize($file))
		{
			chmod($file, $this->cont['ATTACH_IMGFILE_MODE']);
		}
		else
		{
			chmod($file, $this->cont['ATTACH_FILE_MODE']);	
		}
	}
	
	//詳細フォームを表示
	function attach_info($err='')
	{
	//	global $vars,$_attach_messages;
		
		foreach (array('refer','file','age') as $var)
		{
			$$var = array_key_exists($var,$this->root->vars) ? $this->root->vars[$var] : '';
		}
		
		$obj = &new XpWikiAttachFile($this->xpwiki, $refer,$file,$age);
		return $obj->getstatus() ? $obj->info($err) : array('msg'=>$this->root->_attach_messages['err_notfound']);
	}
	//削除
	function attach_delete($pass)
	{
	//	global $vars,$_attach_messages;
		
		foreach (array('refer','file','age','pass') as $var)
		{
			$$var = array_key_exists($var,$this->root->vars) ? $this->root->vars[$var] : '';
		}
		
		if ($this->cont['ATTACH_UPLOAD_EDITER_ONLY'] and !$this->func->is_editable($refer))
		{
			return array('msg'=>$this->root->_attach_messages['err_noparm']);
		}
		
		$obj = &new XpWikiAttachFile($this->xpwiki, $refer,$file,$age);
		return $obj->getstatus() ? $obj->delete($pass) : array('msg'=>$this->root->_attach_messages['err_notfound']);
	}
	//凍結
	function attach_freeze($freeze,$pass)
	{
	//	global $vars,$_attach_messages;
		
		foreach (array('refer','file','age','pass') as $var)
		{
			$$var = array_key_exists($var,$this->root->vars) ? $this->root->vars[$var] : '';
		}
		
		if ($this->cont['ATTACH_UPLOAD_EDITER_ONLY'] and !$this->func->is_editable($refer))
		{
			return array('msg'=>$this->root->_attach_messages['err_noparm']);
		}
		
		$obj = &new XpWikiAttachFile($this->xpwiki, $refer,$file,$age);
		return $obj->getstatus() ? $obj->freeze($freeze,$pass) : array('msg'=>$this->root->_attach_messages['err_notfound']);
	}
	//イメージ回転
	function attach_rotate($pass)
	{
	//	global $vars,$_attach_messages;
		foreach (array('refer','file','age','pass','rd') as $var)
		{
			$$var = array_key_exists($var,$this->root->vars) ? $this->root->vars[$var] : '';
		}
		
		if ($this->cont['ATTACH_UPLOAD_EDITER_ONLY'] and !$this->func->is_editable($refer))
		{
			return array('msg'=>$this->root->_attach_messages['err_noparm']);
		}
		
		$rd = intval($rd);
		$obj = &new XpWikiAttachFile($this->xpwiki, $refer,$file,$age);
		return $obj->getstatus() ? $obj->rotate($rd,$pass) : array('msg'=>$this->root->_attach_messages['err_notfound']);
	}
	//著作権設定
	function attach_copyright($copyright, $pass)
	{
	//	global $vars,$_attach_messages;
		foreach (array('refer','file','age','pass') as $var)
		{
			$$var = array_key_exists($var,$this->root->vars) ? $this->root->vars[$var] : '';
		}
		
		if ($this->cont['ATTACH_UPLOAD_EDITER_ONLY'] and !$this->func->is_editable($refer))
		{
			return array('msg'=>$this->root->_attach_messages['err_noparm']);
		}
		
		$obj = &new XpWikiAttachFile($this->xpwiki, $refer,$file,$age);
		return $obj->getstatus() ? $obj->copyright($copyright,$pass) : array('msg'=>$this->root->_attach_messages['err_notfound']);
	}
	
	// リネーム
	function attach_rename($pass)
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
	
	//ダウンロード
	function attach_open()
	{
	//	global $vars,$_attach_messages;
		
		foreach (array('refer','file','age') as $var)
		{
			$$var = array_key_exists($var,$this->root->vars) ? $this->root->vars[$var] : '';
		}
		
		$obj = &new XpWikiAttachFile($this->xpwiki, $refer,$file,$age);
		
		return $obj->getstatus() ? $obj->open() : array('msg'=>$this->root->_attach_messages['err_notfound']);
	}
	//一覧取得
	function attach_list($mode="")
	{
	//	global $vars,$noattach;
	//	global $_attach_messages;
	//	global $X_admin,$X_uid;
		
		$refer = array_key_exists('refer',$this->root->vars) ? $this->root->vars['refer'] : '';
		
		$this->root->noattach = 1;
		
		$msg = $this->root->_attach_messages[$refer === '' ? 'msg_listall' : 'msg_listpage'];
		
		$max = ($refer)? 50 : 20;
		$max = (isset($this->root->vars['max']))? (int)$this->root->vars['max'] : $max;
		$max = min(50,$max);
		$start = (isset($this->root->vars['start']))? (int)$this->root->vars['start'] : 0;
		$start = max(0,$start);
		$f_order = (isset($this->root->vars['order']))? $this->root->vars['order'] : "";
		$mode = ($mode == "imglist")? $mode : "";
	
		$obj = &new XpWikiAttachPages($this->xpwiki, $refer,NULL,TRUE,$max,$start,FALSE,$f_order,$mode);
		if ($obj->err === 1) return array('msg'=>'DB ERROR!','body'=>'Please initialize an attach file database on an administrator screen.');
		
		
		$body = ($refer === '' or array_key_exists($refer,$obj->pages)) ?
			$obj->toString($refer,FALSE) :
			"<p>".$this->func->make_pagelink($refer)."</p>\n".$this->root->_attach_messages['err_noexist'];
		return array('msg'=>$msg,'body'=>$body);
	}
	//アップロードフォームを表示
	function attach_showform()
	{
	//	global $vars;
	//	global $_attach_messages;
		
		$this->root->vars['refer'] = $this->root->vars['page'];
		$body = ini_get('file_uploads') ? $this->attach_form($this->root->vars['page']) : 'file_uploads disabled.';
		
		return array('msg'=>$this->root->_attach_messages['msg_upload'],'body'=>$body);
	}
	
	//-------- サービス
	//mime-typeの決定
	function attach_mime_content_type($filename, $org_fname)
	{
		$type = 'application/octet-stream'; //default
		
		if (!file_exists($filename))
		{
			return $type;
		}
		$size = @getimagesize($filename);
		if (is_array($size))
		{
			switch ($size[2])
			{
				case 1:
					return 'image/gif';
				case 2:
					return 'image/jpeg';
				case 3:
					return 'image/png';
				case 4:
					return 'application/x-shockwave-flash';
			}
		}
		
		$matches = array();
		if (!preg_match('/_((?:[0-9A-F]{2})+)(?:\.\d+)?$/',$filename,$matches))
		{
			return $type;
		}
		//$filename = $this->func->decode($matches[1]);
		$filename = $org_fname;
		
		// mime-type一覧表を取得
		$config = new XpWikiConfig($this->xpwiki, $this->cont['ATTACH_CONFIG_PAGE_MIME']);
		$table = $config->read() ? $config->get('mime-type') : array();
		unset($config); // メモリ節約
		
		foreach ($table as $row)
		{
			$_type = trim($row[0]);
			$exts = preg_split('/\s+|,/',trim($row[1]),-1,PREG_SPLIT_NO_EMPTY);
			
			foreach ($exts as $ext)
			{
				if (preg_match("/\.$ext$/i",$filename))
				{
					return $_type;
				}
			}
		}
		
		return $type;
	}
	//アップロードフォーム
	function attach_form($page) {
		static $load = array();
		if (!isset($load[$this->xpwiki->pid])) {$load[$this->xpwiki->pid] = array();}
		
		$this->func->exist_plugin('attach');
		
		if (isset($load[$this->xpwiki->pid][$page]))
			$load[$this->xpwiki->pid][$page]++;
		else
			$load[$this->xpwiki->pid][$page] = 0;
		
		$pgid = $this->func->get_pgid_by_name($page);
		
		// refid 指定
		$refid = (!empty($this->root->vars['refid']))? '<input type="hidden" name="refid" value="'.htmlspecialchars($this->root->vars['refid']).'" />' : '';
		$thumb_px = $this->cont['ATTACH_CONFIG_REF_THUMB'];
		$thumb = (!empty($this->root->vars['refid']) && !empty($this->root->vars['thumb']))?
			'<p><input type="checkbox" name="make_thumb" value="1" checked="checked" />' .
			$this->root->_attach_messages['msg_make_thumb'].'<br />' .
			'&nbsp;&nbsp;<input type="text" name="thumb_r" size="3">% or ' .
			'W:<input type="text" name="thumb_w" size="3" value="'.$thumb_px.'" /> x ' .
			'H:<input type="text" name="thumb_h" size="3" value="'.$thumb_px.'" />(Max)</p>' : '';
		$filename = (!empty($this->root->vars['filename']))? '<input type="hidden" name="filename" value="'.htmlspecialchars($this->root->vars['filename']).'" />' : '';
		$returi = (!empty($this->root->vars['returi']))? '<input type="hidden" name="returi" value="'.htmlspecialchars($this->root->vars['returi']).'" />' : '';	
		
		$r_page = rawurlencode($page);
		$s_page = htmlspecialchars($page);
		$header = "<h3>".str_replace('$1',$this->func->make_pagelink($page),$this->root->_attach_messages['msg_upload'])."</h3>";
		$navi = <<<EOD
  $header
  <span class="small">
   [<a href="{$this->root->script}?plugin=attach&amp;pcmd=list&amp;refer=$r_page">{$this->root->_attach_messages['msg_list']}</a>]
   [<a href="{$this->root->script}?plugin=attach&amp;pcmd=list">{$this->root->_attach_messages['msg_listall']}</a>]
  </span><br />
EOD;
	
		if (!(bool)ini_get('file_uploads'))
		{
			return $navi;
		}
		
		$painter = '';
		if ($this->func->exist_plugin('painter'))
		{
			$picw = WIKI_PAINTER_DEF_WIDTH;
			$pich = WIKI_PAINTER_DEF_HEIGHT;
			//$picw = min($picw,WIKI_PAINTER_MAX_WIDTH_UPLOAD);
			//$pich = min($pich,WIKI_PAINTER_MAX_HEIGHT_UPLOAD);
			
			$painter='
<hr />
	<a href="'.$this->root->script.'?plugin=painter&amp;pmode=upload&amp;refer='.encode($page).'">'.$_attach_messages['msg_search_updata'].'</a><br />
	<form action="'.$this->root->script.'" method=POST>
	<label for="_p_attach_tools_'.$pgid.'_'.$load[$this->xpwiki->pid][$page].'">'.$_attach_messages['msg_paint_tool'].'</label>:<select id="_p_attach_tools_'.$pgid.'_'.$load[$this->xpwiki->pid][$page].'" name="tools">
	<option value="normal">'.$_attach_messages['msg_shi'].'</option>
	<option value="pro">'.$_attach_messages['msg_shipro'].'</option>
	</select>
	'.$_attach_messages['msg_width'].'<input type=text name=picw value='.$picw.' size=3> x '.$_attach_messages['msg_height'].'<input type=text name=pich value='.$pich.' size=3>
	'.$_attach_messages['msg_max'].'('.WIKI_PAINTER_MAX_WIDTH_UPLOAD.' x '.WIKI_PAINTER_MAX_HEIGHT_UPLOAD.')
	<input type=submit value="'.$_attach_messages['msg_do_paint'].'" />
	<input type=checkbox id="_p_attach_anime_'.$pgid.'_'.$load[$this->xpwiki->pid][$page].'" value="true" name="anime" />
	<label for="_p_attach_anime_'.$pgid.'_'.$load[$this->xpwiki->pid][$page].'">'.$_attach_messages['msg_save_movie'].'</label><br />
	<br />'.$_attach_messages['msg_adv_setting'].'<br />
	<label for="_p_attach_image_canvas_'.$pgid.'_'.$load[$this->xpwiki->pid][$page].'">'.$_attach_messages['msg_init_image'].'</label>: <input type="text" size="20" id="_p_attach_image_canvas_'.$pgid.'_'.$load[$this->xpwiki->pid][$page].'" name="image_canvas" />
	<input type="checkbox" id="_p_attach_fitimage_'.$pgid.'_'.$load[$this->xpwiki->pid][$page].'" name="fitimage" value="1" checked="true" />
	<label for="_p_attach_fitimage_'.$pgid.'_'.$load[$this->xpwiki->pid][$page].'">'.$_attach_messages['msg_fit_size'].'</label>
	<input type=hidden name="pmode" value="paint" />
	<input type=hidden name="plugin" value="painter" />
	<input type=hidden name="refer" value="'.$page.'" />
	<input type=hidden name="retmode" value="upload" />
	</form>';
		}
		$maxsize = $this->cont['PLUGIN_ATTACH_MAX_FILESIZE'];
		$msg_maxsize = sprintf($this->root->_attach_messages['msg_maxsize'],number_format($maxsize/1024)."KB");
	
		//$uid = get_pg_auther($this->page);
		$pass = '';
		//if (ATTACH_PASSWORD_REQUIRE && !ATTACH_UPLOAD_ADMIN_ONLY && ((!$X_admin && $X_uid !== $uid) || $X_uid == 0))
		if ($this->cont['ATTACH_PASSWORD_REQUIRE'] && !$this->cont['ATTACH_UPLOAD_ADMIN_ONLY'] && !$this->root->userinfo['uid'])
		{
			$title = $this->root->_attach_messages[$this->cont['ATTACH_UPLOAD_ADMIN_ONLY'] ? 'msg_adminpass' : 'msg_password'];
			$pass = '<br />'.$title.': <input type="password" name="pass" size="8" />';
		}
		
		$allow_extensions = '';
		$antar_tag = "(<label for=\"_p_attach_untar_mode_{$pgid}_{$load[$this->xpwiki->pid][$page]}\">{$this->root->_attach_messages['msg_untar']}</label>:<input type=\"checkbox\" id=\"_p_attach_untar_mode_{$pgid}_{$load[$this->xpwiki->pid][$page]}\" name=\"untar_mode\">)";
		if ($this->root->allow_extensions && !$this->func->is_owner($page))
		{
			$allow_extensions = str_replace('$1',join(", ",$this->root->allow_extensions),$this->root->_attach_messages['msg_extensions'])."<br />";
			$antar_tag = "";
		}
		
		//$filelist = "<hr />".$this->attach_filelist();
		$filelist = '';
		
		return <<<EOD
<form enctype="multipart/form-data" action="{$this->root->script}" method="post">
 <div>
  <input type="hidden" name="plugin" value="attach" />
  <input type="hidden" name="pcmd" value="post" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="max_file_size" value="$maxsize" />
  $refid
  $filename
  $returi
  $navi
  <span class="small">
   $msg_maxsize
  </span><br />
  $allow_extensions
  $thumb
  <label for="_p_attach_attach_fil_{$pgid}_{$load[$this->xpwiki->pid][$page]}">{$this->root->_attach_messages['msg_file']}</label>: <input type="file" id="_p_attach_attach_fil_{$pgid}_{$load[$this->xpwiki->pid][$page]}" name="attach_file" />
  $pass
  <input type="submit" class="upload_btn" value="{$this->root->_attach_messages['btn_upload']}" />
  $antar_tag<br />
  <input type="checkbox" id="_p_attach_copyright_{$pgid}_{$load[$this->xpwiki->pid][$page]}" name="copyright" value="1" /> <label for="_p_attach_copyright_{$pgid}_{$load[$this->xpwiki->pid][$page]}">{$this->root->_attach_messages['msg_copyright']}</label><br />
 </div>
</form>
$painter
$filelist
EOD;
	}
	
	// $tname: tarファイルネーム
	// $odir : 展開先ディレクトリ
	// 返り値: 特に無し。大したチェックはせず、やるだけやって後は野となれ山となれ
	function untar( $tname, $odir)
	{
		if (!( $fp = fopen( $tname, "rb") ) ) {
			return;
		}
	
		$files = array();
		$cnt = 0;
		while ( strlen($buff=fread( $fp,$this->cont['TAR_HDR_LEN'])) == $this->cont['TAR_HDR_LEN'] ) {
			for ( $i=$this->cont['TAR_HDR_NAME_OFFSET'],$name="";
				$buff[$i] != "\0" && $i<$this->cont['TAR_HDR_NAME_OFFSET']+$this->cont['TAR_HDR_NAME_LEN'];
				$i++) {
				$name .= $buff[$i];
			}
			$name = basename(trim($name)); //ディレクトリお構い無し
	
			for ( $i=$this->cont['TAR_HDR_SIZE_OFFSET'],$size="";
					$i<$this->cont['TAR_HDR_SIZE_OFFSET']+$this->cont['TAR_HDR_SIZE_LEN']; $i++ ) {
				$size .= $buff[$i];
			}
			list($size) = sscanf("0".trim($size),"%i"); // サイズは8進数
	
			// データブロックは512byteでパディングされている
			$pdsz =  ((int)(($size+($this->cont['TAR_BLK_LEN']-1))/$this->cont['TAR_BLK_LEN']))*$this->cont['TAR_BLK_LEN'];
	
			// 通常のファイルしか相手にしない
			$type = $buff[$this->cont['TAR_HDR_TYPE_OFFSET']];
	
			if ( $name && $type == 0 ) {
				$buff = fread( $fp, $pdsz);
				$tname = tempnam( $odir, "tar" );
				$fpw = fopen( $tname , "wb");
				fwrite( $fpw, $buff, $size );
				fclose( $fpw);
				$files[$cnt  ]['tmpname'] = $tname;
				$files[$cnt++]['extname'] = $name;
			}
		}
		fclose( $fp);
	
		return $files;	
	}
	
	function regularize_fname ($fname, $page) {
		// ファイル名 文字数のチェック
		$page_enc = $this->func->encode($page) . '_';
		$fnlen = strlen($page_enc . $this->func->encode($fname));
		$maxlen = 255 - 14; // 14 = xxx_yyy + .log (strlen(encode(A string as x . '_' . age as yy) . '.log'))
		if (DIRECTORY_SEPARATOR == '\\') {
			$maxlen -= strlen($this->cont['UPLOAD_DIR']);
		}
		while (mb_strlen($fname) > 1 && $fnlen > $maxlen) {
			$fname = mb_substr($fname, 0, mb_strlen($fname) - 1);
			$fnlen = strlen($page_enc . $this->func->encode($fname));
		}
		return $fname;
	}
}
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
	var $status = array('count'=>array(0),'age'=>'','pass'=>'','freeze'=>FALSE,'copyright'=>FALSE,'owner'=>0,'ucd'=>'','uname'=>'','md5'=>'','admins'=>0,'org_fname'=>'');
	var $action = 'update';
	
	function XpWikiAttachFile(& $xpwiki, $page, $file, $age=0, $pgid=0)
	{
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;

		$this->page = $page;
		$this->pgid = ($pgid)? $pgid : $this->func->get_pgid_by_name($page);
		$this->file = basename(str_replace("\\","/",$file));
		$this->age  = is_numeric($age) ? $age : 0;
		$this->id   = $this->get_id();
		
		$this->basename = $this->cont['UPLOAD_DIR'].$this->func->encode($page).'_'.$this->func->encode($this->file);
		$this->filename = $this->basename . ($age ? '.'.$age : '');
		$this->logname = $this->basename.'.log';
		$this->exist = file_exists($this->filename);
		$this->time = $this->exist ? filemtime($this->filename) - $this->cont['LOCALZONE'] : 0;
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
		}
		$this->time_str = $this->func->get_date('Y/m/d H:i:s',$this->time);
		$this->size = filesize($this->filename);
		$this->size_str = sprintf('%01.1f',round($this->size)/1024,1).'KB';
		$this->type = xpwiki_plugin_attach::attach_mime_content_type($this->filename, $this->status['org_fname']);
		$this->owner_id = $this->status['owner'];
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
	function putstatus()
	{
		$this->update_db();
		$this->status['count'] = join(',',$this->status['count']);
		$fp = fopen($this->logname,'wb')
			or $this->func->die_message('cannot write '.$this->logname);
		flock($fp,LOCK_EX);
		foreach ($this->status as $key=>$value)
		{
			fwrite($fp,$value."\n");
		}
		flock($fp,LOCK_UN);
		fclose($fp);
	}

	// DB id 取得
	function get_id() {
		return $this->func->get_attachfile_id($this->page, $this->file, $this->age);
	}

	// attach DB 更新
	function update_db()
	{
		if ($this->action == "insert")
		{
			$this->size = filesize($this->filename);
			$this->type = xpwiki_plugin_attach::attach_mime_content_type($this->filename, $this->status['org_fname']);
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
		$param  = '&amp;file='.rawurlencode($this->file).'&amp;refer='.rawurlencode($this->page).
			($this->age ? '&amp;age='.$this->age : '');
		$title = $this->time_str.' '.$this->size_str;
		//$label = ($showicon ? $this->cont['FILE_ICON'] : '').htmlspecialchars($this->file);
		$label = ($showicon ? $this->cont['FILE_ICON'] : '').htmlspecialchars($this->status['org_fname']);
		if ($this->age)
		{
			if ($mode == "imglist")
				$label = 'backup No.'.$this->age;
			else
				$label .= ' (backup No.'.$this->age.')';
		}
		
		$info = $count = '';
		if ($showinfo)
		{
			$_title = str_replace('$1',rawurlencode($this->file),$this->root->_attach_messages['msg_info']);
			if ($mode == "imglist")
				$info = "[ [[{$this->root->_attach_messages['btn_info']}:{$this->root->script}?plugin=attach&pcmd=info".str_replace("&amp;","&",$param)."]] ]";
			else
				$info = "\n<span class=\"small\">[<a href=\"{$this->root->script}?plugin=attach&amp;pcmd=info$param\" title=\"$_title\">{$this->root->_attach_messages['btn_info']}</a>]</span>";
			$count = ($showicon and !empty($this->status['count'][$this->age])) ?
				sprintf($this->root->_attach_messages['msg_count'],$this->status['count'][$this->age]) : '';
		}
		if ($mode == "imglist")
		{
			if ($this->age)
				return "&size(12){".$label.$info."};";
			else
				return "&size(12){&ref(\"".$this->func->strip_bracket($this->page)."/".$this->file."\"".$this->cont['ATTACH_CONFIG_REF_OPTION'].");&br();".$info."};";
		}
		else
			return "<a href=\"{$this->root->script}?plugin=attach&amp;pcmd=open$param\" title=\"$title\">$label</a>$count$info";
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
		if ($is_editable)
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
		for ($i = 1; $i < 100; $i++)
		{
			$file = $root.$this->func->encode($i."%").$this->func->encode($this->file);
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
		if ($this->status['owner']) {
			if ($this->root->userinfo['uid'] === $owner) return TRUE;
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