<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
//  $Id: attach.inc.php,v 1.41 2008/05/30 08:37:16 nao-pon Exp $
//  ORG: attach.inc.php,v 1.31 2003/07/27 14:15:29 arino Exp $
//
/*
 プラグイン attach

 changed by Y.MASUI <masui@hisec.co.jp> http://masui.net/pukiwiki/
 modified by PANDA <panda@arino.jp> http://home.arino.jp/
*/

class xpwiki_plugin_attach extends xpwiki_plugin {
	
	var $listed = FALSE;
	
	function plugin_attach_init () {
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
			$this->cont['FILE_ICON'] = '<img src="' . $this->cont['IMAGE_DIR'] . 'file.png" width="20" height="20" alt="file" style="border-width:0px" />';
		}
	
		// mime-typeを記述したページ
		$this->cont['ATTACH_CONFIG_PAGE_MIME'] = 'plugin/attach/mime-type';
	
		// 詳細情報・ファイル一覧(イメージモード)で使用する ref プラグインの追加オプション
		$this->cont['ATTACH_CONFIG_REF_OPTION'] = ',mw:160,mh:120';
		
		// ref プラグインの添付リンクから呼び出された場合のサムネイル作成サイズ規定値(px)
		$this->cont['ATTACH_CONFIG_REF_THUMB'] = 240;
	
		// リスト表示する件数
		$this->cont['ATTACH_LIST_MAX'] = 40;
		$this->cont['ATTACH_LIST_MAX_SKIN'] = 20;
		
		// tar
		$this->cont['TAR_HDR_LEN'] = 512;			// ヘッダの大きさ
		$this->cont['TAR_BLK_LEN'] = 512;			// 単位ブロック長さ
		$this->cont['TAR_HDR_NAME_OFFSET'] = 0;	    // ファイル名のオフセット
		$this->cont['TAR_HDR_NAME_LEN'] = 100;		// ファイル名の最大長さ
		$this->cont['TAR_HDR_SIZE_OFFSET'] = 124;	// サイズへのオフセット
		$this->cont['TAR_HDR_SIZE_LEN'] = 12;		// サイズの長さ
		$this->cont['TAR_HDR_TYPE_OFFSET'] = 156;	// ファイルタイプへのオフセット
		$this->cont['TAR_HDR_TYPE_LEN'] = 1;		// ファイルタイプの長さ

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
			$this->func->page_write($page, "\t");
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
	
	function do_upload($page,$fname,$tmpname,$copyright=FALSE,$pass=NULL,$notouch=FALSE,$options=NULL)
	{
		$overwrite = (!empty($options['overwrite']));
		$changelog = (isset($options['changelog']))? $options['changelog'] : '';
		
		// ファイル名の正規化
		$fname = preg_replace('/[[:cntrl:]]+/', '', $fname);
		$fname = $this->func->basename(str_replace("\\","/",$fname));
		
		$_action = 'insert';
		
		// style.css
		if ($fname === 'style.css' && $this->func->is_owner($page))
		{
			if ( is_uploaded_file($tmpname) )
			{
				$_pagecss_file = $this->cont['CACHE_DIR'].$this->func->get_pgid_by_name($page).".css";
				if (file_exists($_pagecss_file)) unlink($_pagecss_file);
				if (move_uploaded_file($tmpname,$_pagecss_file))
				{
					$this->attach_chmod($_pagecss_file);
					// 空のファイルの場合はファイル削除
					if (!trim(file_get_contents($_pagecss_file)))
					{
						unlink($_pagecss_file);
						return array('result'=>TRUE,'msg'=>$this->root->_attach_messages['msg_unset_css']);
					}
					else
					{
						// 外部ファイルの参照を禁止するための書き換え
						$_data = file_get_contents($_pagecss_file);
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
		$allow_extensions = $this->get_allow_extensions();
		if (empty($options['asSystem']) && !$overwrite && $allow_extensions && !$this->func->is_owner($page)
			 && !preg_match("/\.(".join("|",$allow_extensions).")$/i",$fname)) {
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

		// 格納ファイル名指定あり
		if (!empty($this->root->post['filename'])) {
			$fname = $this->root->post['filename'];
		}

		// 格納ファイル名文字数チェック(SQL varchar(255) - strlen('_\d\d\d'))
		$fname = (function_exists('mb_strcut'))? mb_strcut($fname, 0, 251) : substr($fname, 0, 251);
		
		// ファイル名 文字数のチェック
		$fname = $this->regularize_fname($fname, $page);
		
		if (!$overwrite) {
			// ファイル名が存在する場合は、数字を付け加える
			if (preg_match("/^(.+)(\.[^.]*)$/",$fname,$match)) {
				$_fname = $match[1];
				$_ext = $match[2];
			} else {
				$_fname = $fname;
				$_ext = '';
			}
	
			$fi = 0;
			do {
				$obj = & new XpWikiAttachFile($this->xpwiki, $page, $fname);
				$fname = $_fname.'_'.($fi++).$_ext;
			} while ($obj->exist);
		} else {
			$obj = & new XpWikiAttachFile($this->xpwiki, $page, $fname);
		}
		
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
		
		if ($this->func->is_page($page)) {
			if (!$notouch) {
				$this->func->pkwk_touch_file($this->func->get_filename($page));
				$this->func->touch_db($page);
				if (!$changelog) $changelog = 'Attach file: '.htmlspecialchars($obj->file). ' by '.$this->root->userinfo['uname_s'];
				$this->func->push_page_changes($page, $changelog);
			}
			$this->func->clear_page_cache($page);
		}

		if (! empty($options['asSystem'])) {
			$_uid = 0;
			$_ucd = 'SYSTEM';
			$_uname = 'System';
			$_admins = 0;
		} else {
			$_uid = $this->root->userinfo['uid'];
			$_ucd = $this->root->userinfo['ucd'];
			$_uname= $this->root->userinfo['uname'];
			$_admins = (int)$this->func->check_admin($this->root->userinfo['uid']);
		}
		
		$obj->getstatus();
		$obj->status['pass'] = ($pass !== TRUE and $pass !== NULL) ? $pass : '';
		$obj->status['copyright'] = $copyright;
		$obj->status['owner'] = $_uid;
		$obj->status['ucd']   = $_ucd;
		$obj->status['uname'] = $_uname;
		$obj->status['md5'] = md5_file($obj->filename);
		$obj->status['admins'] = $_admins;
		$obj->status['org_fname'] = $org_fname;
		$obj->status['imagesize'] = @ getimagesize($obj->filename);
		$obj->action = $_action;
		$obj->putstatus();
		
		if (!empty($this->root->vars['refid'])) {
			$this->ref_replace($page, $this->root->vars['refid'], $obj->file, $obj->status['imagesize']);
		}
		
		return array(
			'result'   => TRUE,
			'msg'      => $this->root->_attach_messages['msg_uploaded'],
			'name'     => $obj->file
		);
	}
	
	// ref プラグインのソース置換
	function ref_replace($page, $refid, $name, $imagesize) {
		// サムネイルサイズ指定？
		$prm = '';
		if (!empty($this->root->vars['make_thumb']) && $imagesize) {
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
		if ($this->root->easy_ref_syntax) {
			$postdata = preg_replace('/\{\{ID\$'.preg_quote($refid, '/').'((?:,|\|).*?)?\}\}/', "{{".$name.$prm."$1}}", $postdata);
		}
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
		$refer = isset($this->root->vars['refer'])? $this->root->vars['refer'] : '';
		
		$this->root->noattach = 1;
		
		$msg = $this->root->_attach_messages[$refer === '' ? 'msg_listall' : 'msg_listpage'];
		
		$max = ($refer)? $this->cont['ATTACH_LIST_MAX'] : $this->cont['ATTACH_LIST_MAX_SKIN'];
		$max = (isset($this->root->vars['max']))? (int)$this->root->vars['max'] : $max;
		$max = min($this->cont['ATTACH_LIST_MAX'], $max);
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
	function attach_mime_content_type($filename, $status)
	{
		$org_fname = $status['org_fname'];
		$imagesize = $status['imagesize'];
		$type = 'application/octet-stream'; //default
		
		if (!file_exists($filename))
		{
			return $type;
		}
		if (is_array($imagesize))
		{
			if (isset($imagesize['mime'])) return $imagesize['mime']; // PHP >= 4.3.0
			switch ($imagesize[2])
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
		
		$allow_extensions = $this->get_allow_extensions();
		$antar_tag = "(<label for=\"_p_attach_untar_mode_{$pgid}_{$load[$this->xpwiki->pid][$page]}\">{$this->root->_attach_messages['msg_untar']}</label>:<input type=\"checkbox\" id=\"_p_attach_untar_mode_{$pgid}_{$load[$this->xpwiki->pid][$page]}\" name=\"untar_mode\">)";
		if ($allow_extensions && !$this->func->is_owner($page)) {
			$allow_extensions = str_replace('$1',join(", ",$allow_extensions),$this->root->_attach_messages['msg_extensions'])."<br />";
			$antar_tag = "";
		} else {
			$allow_extensions = '';
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
			$name = $this->func->basename(trim($name)); //ディレクトリお構い無し
	
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
	
	function get_allow_extensions () {
		// 添付可能な拡張子を配列化
		if (!$this->cont['ATTACH_UPLOAD_ADMIN_ONLY'] && $this->cont['ATTACH_UPLOAD_EXTENSION']) {
			return explode(",",str_replace(" ","",$this->cont['ATTACH_UPLOAD_EXTENSION']));
		} else {
			return array();
		}
	}
}
?>