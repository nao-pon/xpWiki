<?php
// $Id: dump.inc.php,v 1.7 2008/05/14 07:16:41 nao-pon Exp $
//
// Remote dump / restore plugin
// Originated as tarfile.inc.php by teanan / Interfair Laboratory 2004.

class xpwiki_plugin_dump extends xpwiki_plugin {
	function plugin_dump_init () {
		/////////////////////////////////////////////////
		// User defines
		
		// Allow using resture function
		$this->cont['PLUGIN_DUMP_ALLOW_RESTORE'] =  TRUE; // FALSE, TRUE
	
		// ページ名をディレクトリ構造に変換する際の文字コード (for mbstring)
		$this->cont['PLUGIN_DUMP_FILENAME_ENCORDING'] =  'SJIS';
	
		// 最大アップロードサイズ
		$this->cont['PLUGIN_DUMP_MAX_FILESIZE'] =  1024; // Kbyte
	
		/////////////////////////////////////////////////
		// Internal defines
		
		// Action
		$this->cont['PLUGIN_DUMP_DUMP'] =     'dump';    // Dump & download
		$this->cont['PLUGIN_DUMP_RESTORE'] =  'restore'; // Upload & restore
	
		//global $_STORAGE;
	
		// DATA_DIR (wiki/*.txt)
		$this->root->_STORAGE['DATA_DIR']['add_filter']     = '^[0-9A-F]+\.txt';
		$this->root->_STORAGE['DATA_DIR']['extract_filter'] = '^' . preg_quote($this->cont['DATA_DIR'], '/')   . '((?:[0-9A-F])+)(\.txt){0,1}';
	
		// UPLOAD_DIR (attach/*)
		$this->root->_STORAGE['UPLOAD_DIR']['add_filter']     = '^[0-9A-F_]+';
		$this->root->_STORAGE['UPLOAD_DIR']['extract_filter'] = '^' . preg_quote($this->cont['UPLOAD_DIR'], '/') . '((?:[0-9A-F]{2})+)_((?:[0-9A-F])+)';
	
		// COUNTER_DIR (counter/*.count)
		$this->root->_STORAGE['COUNTER_DIR']['add_filter']     = '^[0-9A-F]+\.count';
		$this->root->_STORAGE['COUNTER_DIR']['extract_filter'] = '^' . preg_quote($this->cont['COUNTER_DIR'], '/') . '((?:[0-9A-F])+)(\.count){0,1}';

		// BACKUP_DIR (backup/*.gz)
		$this->root->_STORAGE['BACKUP_DIR']['add_filter']     = '^[0-9A-F]+\.gz';
		$this->root->_STORAGE['BACKUP_DIR']['extract_filter'] =  '^' . preg_quote($this->cont['BACKUP_DIR'], '/') . '((?:[0-9A-F])+)(\.gz){0,1}';

		// DIFF_DIR (diff/*.(txt|add))
		$this->root->_STORAGE['DIFF_DIR']['add_filter']     = '^[0-9A-F]+\.(txt|add)';
		$this->root->_STORAGE['DIFF_DIR']['extract_filter'] = '^' . preg_quote($this->cont['DIFF_DIR'], '/') . '((?:[0-9A-F])+)(\.txt|add){0,1}';

	
		/////////////////////////////////////////////////
		// tarlib: a class library for tar file creation and expansion
		
		// Tar related definition
		$this->cont['TARLIB_HDR_LEN'] =            512;	// ヘッダの大きさ
		$this->cont['TARLIB_BLK_LEN'] =            512;	// 単位ブロック長さ
		$this->cont['TARLIB_HDR_NAME_OFFSET'] =      0;	// ファイル名のオフセット
		$this->cont['TARLIB_HDR_NAME_LEN'] =       100;	// ファイル名の最大長さ
		$this->cont['TARLIB_HDR_MODE_OFFSET'] =    100;	// modeへのオフセット
		$this->cont['TARLIB_HDR_UID_OFFSET'] =     108;	// uidへのオフセット
		$this->cont['TARLIB_HDR_GID_OFFSET'] =     116;	// gidへのオフセット
		$this->cont['TARLIB_HDR_SIZE_OFFSET'] =    124;	// サイズへのオフセット
		$this->cont['TARLIB_HDR_SIZE_LEN'] =        12;	// サイズの長さ
		$this->cont['TARLIB_HDR_MTIME_OFFSET'] =   136;	// 最終更新時刻のオフセット
		$this->cont['TARLIB_HDR_MTIME_LEN'] =       12;	// 最終更新時刻の長さ
		$this->cont['TARLIB_HDR_CHKSUM_OFFSET'] =  148;	// チェックサムのオフセット
		$this->cont['TARLIB_HDR_CHKSUM_LEN'] =       8;	// チェックサムの長さ
		$this->cont['TARLIB_HDR_TYPE_OFFSET'] =    156;	// ファイルタイプへのオフセット
	
		// Status
		$this->cont['TARLIB_STATUS_INIT'] =     0;		// 初期状態
		$this->cont['TARLIB_STATUS_OPEN'] =    10;		// 読み取り
		$this->cont['TARLIB_STATUS_CREATE'] =  20;		// 書き込み
	
		$this->cont['TARLIB_DATA_MODE'] =       '100666 ';	// ファイルパーミッション
		$this->cont['TARLIB_DATA_UGID'] =       '000000 ';	// uid / gid
		$this->cont['TARLIB_DATA_CHKBLANKS'] =  '        ';
	
		// GNU拡張仕様(ロングファイル名対応)
		$this->cont['TARLIB_DATA_LONGLINK'] =  '././@LongLink';
	
		// Type flag
		$this->cont['TARLIB_HDR_FILE'] =  '0';
		$this->cont['TARLIB_HDR_LINK'] =  'L';
	
		// Kind of the archive
		$this->cont['TARLIB_KIND_TGZ'] =  0;
		$this->cont['TARLIB_KIND_TAR'] =  1;

	}
	
	
	/////////////////////////////////////////////////
	// プラグイン本体
	function plugin_dump_action()
	{
		// 権限チェック
		if (!$this->root->userinfo['admin']) {
			return $this->action_msg_admin_only();
		}
		
		// 言語ファイルの読み込み
		$this->load_language();

		// 管理画面モード指定
		if ($this->root->module['platform'] == "xoops") {
			$this->root->runmode = "xoops_admin";
		}
	
		if ($this->cont['PKWK_READONLY']) $this->func->die_message('PKWK_READONLY prohibits this');
		
		$pass = isset($_POST['pass']) ? $_POST['pass'] : NULL;
		$act  = isset($this->root->vars['act'])   ? $this->root->vars['act']   : NULL;
	
		$body = '';
	
		if ($this->root->userinfo['admin'] || $pass !== NULL) {
			if (! $this->func->pkwk_login($pass)) {
				$body = "<p><strong>{$this->msg['password_ng']}</strong></p>\n";
			} else {
				switch($act){
				case $this->cont['PLUGIN_DUMP_DUMP']:
					$body = $this->plugin_dump_download();
					break;
				case $this->cont['PLUGIN_DUMP_RESTORE']:
					$retcode = $this->plugin_dump_upload();
					if ($retcode['code'] == TRUE) {
						$msg = $this->msg['upload_ok'];
					} else {
						$msg = $this->msg['upload_ng'];
					}
					$body .= $retcode['msg'];
					return array('msg' => $msg, 'body' => $body);
					break;
				}
			}
		}
	
		// 入力フォームを表示
		$body .= $this->plugin_dump_disp_form();
	
		$msg = '';
		if ($this->cont['PLUGIN_DUMP_ALLOW_RESTORE']) {
			$msg = 'dump & restore';
		} else {
			$msg = 'dump';
		}
	
		return array('msg' => $msg, 'body' => $body);
	}
	
	/////////////////////////////////////////////////
	// ファイルのダウンロード
	function plugin_dump_download()
	{
		// アーカイブの種類
		$arc_kind = ($this->root->vars['pcmd'] == 'tar') ? 'tar' : 'tgz';

		// ページ名に変換する
		$namedecode = isset($this->root->vars['namedecode']) ? TRUE : FALSE;
	
		// バックアップディレクトリ
		$bk_wiki   = isset($this->root->vars['bk_wiki'])   ? TRUE : FALSE;
		$bk_attach = isset($this->root->vars['bk_attach']) ? TRUE : FALSE;
		$bk_counter= isset($this->root->vars['bk_counter']) ? TRUE : FALSE;
		$bk_backup = isset($this->root->vars['bk_backup']) ? TRUE : FALSE;
		$bk_diff   = isset($this->root->vars['bk_diff']) ? TRUE : FALSE;
	
		$filecount = 0;
		$tar = new XpWikitarlib($this->xpwiki);
		$tar->create($this->cont['CACHE_DIR'], $arc_kind) or
			$this->func->die_message($this->msg['maketmp_ng']);
	
		if ($bk_wiki)   $filecount += $tar->add_dir($this->cont['DATA_DIR'],   $this->root->_STORAGE['DATA_DIR']['add_filter'],   $namedecode);
		if ($bk_attach) $filecount += $tar->add_dir($this->cont['UPLOAD_DIR'], $this->root->_STORAGE['UPLOAD_DIR']['add_filter'], $namedecode);
		if ($bk_counter)$filecount += $tar->add_dir($this->cont['COUNTER_DIR'],$this->root->_STORAGE['COUNTER_DIR']['add_filter'], $namedecode);
		if ($bk_backup) $filecount += $tar->add_dir($this->cont['BACKUP_DIR'], $this->root->_STORAGE['BACKUP_DIR']['add_filter'], $namedecode);
		if ($bk_diff)   $filecount += $tar->add_dir($this->cont['DIFF_DIR'],   $this->root->_STORAGE['DIFF_DIR']['add_filter'], $namedecode);
	
		$tar->close();
	
		if ($filecount === 0) {
			@unlink($tar->filename);
			return '<p><strong>'.$this->msg['file_notfound'].'</strong></p>';
		} else {
			// ダウンロード
			$this->download_tarfile($tar->filename, $arc_kind);
			@unlink($tar->filename);
			exit;	// 正常終了
		}
	}
	
	/////////////////////////////////////////////////
	// ファイルのアップロード
	function plugin_dump_upload()
	{
	//	global $vars, $_STORAGE;
	
		if (! $this->cont['PLUGIN_DUMP_ALLOW_RESTORE'])
			return array('code' => FALSE , 'msg' => 'Restoring function is not allowed');
	
		$filename = $_FILES['upload_file']['name'];
		$matches  = array();
		$arc_kind = FALSE;
		if(! preg_match('/(\.tar|\.tar.gz|\.tgz)$/', $filename, $matches)){
			$this->func->die_message('Invalid file suffix');
		} else { 
			$matches[1] = strtolower($matches[1]);
			switch ($matches[1]) {
			case '.tar':    $arc_kind = 'tar'; break;
			case '.tgz':    $arc_kind = 'tar'; break;
			case '.tar.gz': $arc_kind = 'tgz'; break;
			default: $this->func->die_message('Invalid file suffix: ' . $matches[1]); }
		}
	
		if ($_FILES['upload_file']['size'] >  $this->cont['PLUGIN_DUMP_MAX_FILESIZE'] * 1024)
			$this->func->die_message('Max file size exceeded: ' . $this->cont['PLUGIN_DUMP_MAX_FILESIZE'] . 'KB');
	
		// Create a temporary tar file
		$uploadfile = tempnam(realpath($this->cont['CACHE_DIR']), 'tarlib_uploaded_');
		$tar = new XpWikitarlib($this->xpwiki);
		if(! move_uploaded_file($_FILES['upload_file']['tmp_name'], $uploadfile) ||
		   ! $tar->open($uploadfile, $arc_kind)) {
			@unlink($uploadfile);
			$this->func->die_message($this->msg['file_notfound']);
		}
	
		$pattern = "(({$this->root->_STORAGE['DATA_DIR']}['extract_filter']})|" .
		    "({$this->root->_STORAGE['UPLOAD_DIR']}['extract_filter']})|" .
		    "({$this->root->_STORAGE['BACKUP_DIR']}['extract_filter']}))";
		$files = $tar->extract($pattern);
		if (empty($files)) {
			@unlink($uploadfile);
			return array('code' => FALSE, 'msg' => '<p>'.$this->msg['tarfile_notfound'].'</p>');
		}
	
		$msg  = '<p><strong>'.$this->msg['filelist'].'</strong><ul>';
		foreach($files as $name) {
			$msg .= "<li>$name</li>\n";
		}
		$msg .= '</ul></p>';
	
		$tar->close();
		@unlink($uploadfile);
	
		return array('code' => TRUE, 'msg' => $msg);
	}
	
	/////////////////////////////////////////////////
	// tarファイルのダウンロード
	function download_tarfile($tempnam, $arc_kind)
	{
		$size = filesize($tempnam);
	
		$filename = strftime('tar%Y%m%d', $this->cont['UTC']);
		if ($arc_kind == 'tgz') {
			$filename .= '.tar.gz';
		} else {
			$filename .= '.tar';
		}
		
		// clear output buffer
		while( ob_get_level() ) {
			ob_end_clean() ;
		}

		ini_set('default_charset','');
		mb_http_output('pass');
		
		$this->func->pkwk_common_headers();
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Length: ' . $size);
		header('Content-Type: application/octet-stream');
		header('Pragma: no-cache');
		@readfile($tempnam);
	}
	
	/////////////////////////////////////////////////
	// 入力フォームを表示
	function plugin_dump_disp_form()
	{
	//	global $script, $defaultpage;
		
		$act_down = $this->cont['PLUGIN_DUMP_DUMP'];
		$act_up   = $this->cont['PLUGIN_DUMP_RESTORE'];
		$maxsize  = $this->cont['PLUGIN_DUMP_MAX_FILESIZE'];
		
		$this->msg['max_filesize'] = str_replace('$maxsize', $maxsize, $this->msg['max_filesize']);

		$passform = ($this->root->userinfo['admin'])? '' :
			'<label for="_p_dump_adminpass_dump"><strong>'.$this->msg['admin_pass'].'</strong></label>
  <input type="password" name="pass" id="_p_dump_adminpass_dump" size="12" />';

	
		$data = <<<EOD
<span class="small">
</span>
<h3>{$this->msg['data_download']}</h3>
<form action="{$this->root->script}" method="post">
 <div>
  <input type="hidden" name="cmd"  value="dump" />
  <input type="hidden" name="page" value="{$this->root->defaultpage}" />
  <input type="hidden" name="act"  value="$act_down" />

<p><strong>{$this->msg['tar_type']}</strong>
<br />
  <input type="radio" name="pcmd" id="_p_dump_tgz" value="tgz" checked="checked" />
  <label for="_p_dump_tgz">{$this->msg['tar.gz']}</label><br />
  <input type="radio" name="pcmd" id="_p_dump_tar" value="tar" />
  <label for="_p_dump_tar">{$this->msg['tar']}</label>
</p>
<p><strong>{$this->msg['backup_dir']}</strong>
<br />
  <input type="checkbox" name="bk_wiki" id="_p_dump_d_wiki" checked="checked" />
  <label for="_p_dump_d_wiki">wiki</label><br />
  <input type="checkbox" name="bk_attach" id="_p_dump_d_attach" />
  <label for="_p_dump_d_attach">attach</label><br />
  <input type="checkbox" name="bk_counter" id="_p_dump_d_counter" />
  <label for="_p_dump_d_counter">counter</label><br />
  <input type="checkbox" name="bk_backup" id="_p_dump_d_backup" />
  <label for="_p_dump_d_backup">backup</label><br />
  <input type="checkbox" name="bk_diff" id="_p_dump_d_diff" />
  <label for="_p_dump_d_diff">diff</label><br />
</p>
<p><strong>{$this->msg['option']}</strong>
<br />
  <input type="checkbox" name="namedecode" id="_p_dump_namedecode" />
  <label for="_p_dump_namedecode">{$this->msg['decode_pagename']}</label><br />
</p>
<p>$passform
  <input type="submit"   name="ok"   value="OK" />
</p>
 </div>
</form>
EOD;
	
		if($this->cont['PLUGIN_DUMP_ALLOW_RESTORE']) {
			$passform = ($this->root->userinfo['admin'])? '' :
				'<label for="_p_dump_adminpass_restore"><strong>'.$this->msg['admin_pass'].'</strong></label>
  <input type="password" name="pass" id="_p_dump_adminpass_restore" size="12" />';
			$data .= <<<EOD
<h3>{$this->msg['data_restore']}</h3>
<form enctype="multipart/form-data" action="{$this->root->script}" method="post">
 <div>
  <input type="hidden" name="cmd"  value="dump" />
  <input type="hidden" name="page" value="{$this->root->defaultpage}" />
  <input type="hidden" name="act"  value="$act_up" />
<p><strong>{$this->msg['data_overwrite']}</strong></p>
<p><span class="small">
{$this->msg['max_filesize']}<br />
</span>
  <label for="_p_dump_upload_file">{$this->msg['file']}</label>
  <input type="file" name="upload_file" id="_p_dump_upload_file" size="40" />
</p>
<p>$passform
  <input type="submit"   name="ok"   value="OK" />
</p>
 </div>
</form>
EOD;
		}
	
		return $data;
	}
}
	
class XpWikitarlib
{
	var $filename;
	var $fp;
	var $status;
	var $arc_kind;
	var $dummydata;

	// コンストラクタ
	function XpWikitarlib(& $xpwiki) {
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;
		$this->filename = '';
		$this->fp       = FALSE;
		$this->status   = $this->cont['TARLIB_STATUS_INIT'];
		$this->arc_kind = $this->cont['TARLIB_KIND_TGZ'];
	}
	
	////////////////////////////////////////////////////////////
	// 関数  : tarファイルを作成する
	// 引数  : tarファイルを作成するパス
	// 返り値: TRUE .. 成功 , FALSE .. 失敗
	////////////////////////////////////////////////////////////
	function create($tempdir, $kind = 'tgz')
	{
		$tempnam = tempnam(realpath($tempdir), 'tarlib_create_');
		if ($tempnam === FALSE) return FALSE;
		
		if ($kind == 'tgz') {
			$this->arc_kind = $this->cont['TARLIB_KIND_TGZ'];
			$this->fp       = gzopen($tempnam, 'wb');
		} else {
			$this->arc_kind = $this->cont['TARLIB_KIND_TAR'];
			$this->fp       = @fopen($tempnam, 'wb');
		}

		if ($this->fp === FALSE) {
			@unlink($tempnam);
			return FALSE;
		} else {
			$this->filename  = $tempnam;
			$this->dummydata = join('', array_fill(0, $this->cont['TARLIB_BLK_LEN'], "\0"));
			$this->status    = $this->cont['TARLIB_STATUS_CREATE'];
			rewind($this->fp);
			return TRUE;
		}
	}

	////////////////////////////////////////////////////////////
	// 関数  : tarファイルにディレクトリを追加する
	// 引数  : $dir    .. ディレクトリ名
	//         $mask   .. 追加するファイル(正規表現)
	//         $decode .. ページ名の変換をするか
	// 返り値: 作成したファイル数
	////////////////////////////////////////////////////////////
	function add_dir($dir, $mask, $decode = FALSE)
	{
		$retvalue = 0;
		
		if ($this->status != $this->cont['TARLIB_STATUS_CREATE'])
			return ''; // File is not created

		unset($files);

		//  指定されたパスのファイルのリストを取得する
		$dp = @opendir($dir);
		if($dp === FALSE) {
			@unlink($this->filename);
			$this->func->die_message($dir . ' is not found or not readable.');
		}

		while ($filename = readdir($dp)) {
			if (preg_match("/$mask/", $filename))
				$files[] = $dir . $filename;
		}
		closedir($dp);

		sort($files);

		$matches = array();
		foreach($files as $name)
		{
			// Tarに格納するファイル名をdecode
			if ($decode === FALSE) {
				$filename = $name;
			} else {
				$dirname  = dirname(trim($name)) . '/';
				$filename = basename(trim($name));
				if (preg_match("/^((?:[0-9A-F]{2})+)_((?:[0-9A-F]{2})+)/", $filename, $matches)) {
					// attachファイル名
					$filename = $this->func->decode($matches[1]) . '/' . $this->func->decode($matches[2]);
				} else {
					$pattern = '^((?:[0-9A-F]{2})+)((\.txt|\.gz)*)$';
					if (preg_match("/$pattern/", $filename, $matches)) {
						$filename = $this->func->decode($matches[1]) . $matches[2];

						// 危ないコードは置換しておく
						$filename = str_replace(':',  '_', $filename);
						$filename = str_replace('\\', '_', $filename);
					}
				}
				$filename = $dirname . $filename;
				// ファイル名の文字コードを変換
				if (function_exists('mb_convert_encoding'))
					$filename = mb_convert_encoding($filename, $this->cont['PLUGIN_DUMP_FILENAME_ENCORDING']);
			}

			// 最終更新時刻
			$mtime = filemtime($name);

			// ファイル名長のチェック
			if (strlen($filename) > $this->cont['TARLIB_HDR_NAME_LEN']) {
				// LongLink対応
				$size = strlen($filename);
				// LonkLinkヘッダ生成
				$tar_data = $this->_make_header($this->cont['TARLIB_DATA_LONGLINK'], $size, $mtime, $this->cont['TARLIB_HDR_LINK']);
				// ファイル出力
	 			$this->_write_data(join('', $tar_data), $filename, $size);
			}

			// ファイルサイズを取得
			$size = filesize($name);
			if ($size === FALSE) {
				@unlink($this->filename);
				$this->func->die_message($name . ' is not found or not readable.');
			}

			// ヘッダ生成
			$tar_data = $this->_make_header($filename, $size, $mtime, $this->cont['TARLIB_HDR_FILE']);

			// ファイルデータの取得
			$fpr = @fopen($name , 'rb');
			flock($fpr, LOCK_SH);
			$data = fread($fpr, $size);
			fclose( $fpr );

			// ファイル出力
			$this->_write_data(join('', $tar_data), $data, $size);
			++$retvalue;
		}
		return $retvalue;
	}
	
	////////////////////////////////////////////////////////////
	// 関数  : tarのヘッダ情報を生成する (add)
	// 引数  : $filename .. ファイル名
	//         $size     .. データサイズ
	//         $mtime    .. 最終更新日
	//         $typeflag .. TypeFlag (file/link)
	// 戻り値: tarヘッダ情報
	////////////////////////////////////////////////////////////
	function _make_header($filename, $size, $mtime, $typeflag)
	{
		$tar_data = array_fill(0, $this->cont['TARLIB_HDR_LEN'], "\0");
		
		// ファイル名を保存
		for($i = 0; $i < strlen($filename); $i++ ) {
			if ($i < $this->cont['TARLIB_HDR_NAME_LEN']) {
				$tar_data[$i + $this->cont['TARLIB_HDR_NAME_OFFSET']] = $filename{$i};
			} else {
				break;	// ファイル名が長すぎ
			}
		}

		// mode
		$modeid = $this->cont['TARLIB_DATA_MODE'];
		for($i = 0; $i < strlen($modeid); $i++ ) {
			$tar_data[$i + $this->cont['TARLIB_HDR_MODE_OFFSET']] = $modeid{$i};
		}

		// uid / gid
		$ugid = $this->cont['TARLIB_DATA_UGID'];
		for($i = 0; $i < strlen($ugid); $i++ ) {
			$tar_data[$i + $this->cont['TARLIB_HDR_UID_OFFSET']] = $ugid{$i};
			$tar_data[$i + $this->cont['TARLIB_HDR_GID_OFFSET']] = $ugid{$i};
		}

		// サイズ
		$strsize = sprintf('%11o', $size);
		for($i = 0; $i < strlen($strsize); $i++ ) {
			$tar_data[$i + $this->cont['TARLIB_HDR_SIZE_OFFSET']] = $strsize{$i};
		}

		// 最終更新時刻
		$strmtime = sprintf('%o', $mtime);
		for($i = 0; $i < strlen($strmtime); $i++ ) {
			$tar_data[$i + $this->cont['TARLIB_HDR_MTIME_OFFSET']] = $strmtime{$i};
		}

		// チェックサム計算用のブランクを設定
		$chkblanks = $this->cont['TARLIB_DATA_CHKBLANKS'];
		for($i = 0; $i < strlen($chkblanks); $i++ ) {
			$tar_data[$i + $this->cont['TARLIB_HDR_CHKSUM_OFFSET']] = $chkblanks{$i};
		}

		// タイプフラグ
		$tar_data[$this->cont['TARLIB_HDR_TYPE_OFFSET']] = $typeflag;

		// チェックサムの計算
		$sum = 0;
		for($i = 0; $i < $this->cont['TARLIB_BLK_LEN']; $i++ ) {
			$sum += 0xff & ord($tar_data[$i]);
		}
		$strchksum = sprintf('%7o',$sum);
		for($i = 0; $i < strlen($strchksum); $i++ ) {
			$tar_data[$i + $this->cont['TARLIB_HDR_CHKSUM_OFFSET']] = $strchksum{$i};
		}

		return $tar_data;
	}
	
	////////////////////////////////////////////////////////////
	// 関数  : tarデータのファイル出力 (add)
	// 引数  : $header .. tarヘッダ情報
	//         $body   .. tarデータ
	//         $size   .. データサイズ
	// 戻り値: なし
	////////////////////////////////////////////////////////////
	function _write_data($header, $body, $size)
	{
		$fixsize  = ceil($size / $this->cont['TARLIB_BLK_LEN']) * $this->cont['TARLIB_BLK_LEN'] - $size;

		if ($this->arc_kind == $this->cont['TARLIB_KIND_TGZ']) {
			gzwrite($this->fp, $header, $this->cont['TARLIB_HDR_LEN']);    // Header
			gzwrite($this->fp, $body, $size);               // Body
			gzwrite($this->fp, $this->dummydata, $fixsize); // Padding
		} else {
			 fwrite($this->fp, $header, $this->cont['TARLIB_HDR_LEN']);    // Header
			 fwrite($this->fp, $body, $size);               // Body
			 fwrite($this->fp, $this->dummydata, $fixsize); // Padding
		}
	}

	////////////////////////////////////////////////////////////
	// 関数  : tarファイルを開く
	// 引数  : tarファイル名
	// 返り値: TRUE .. 成功 , FALSE .. 失敗
	////////////////////////////////////////////////////////////
	function open($name = '', $kind = 'tgz')
	{
		if (! $this->cont['PLUGIN_DUMP_ALLOW_RESTORE']) return FALSE; // Not allowed

		if ($name != '') $this->filename = $name;

		if ($kind == 'tgz') {
			$this->arc_kind = $this->cont['TARLIB_KIND_TGZ'];
			$this->fp = gzopen($this->filename, 'rb');
		} else {
			$this->arc_kind = $this->cont['TARLIB_KIND_TAR'];
			$this->fp =  fopen($this->filename, 'rb');
		}

		if ($this->fp === FALSE) {
			return FALSE;	// No such file
		} else {
			$this->status = $this->cont['TARLIB_STATUS_OPEN'];
			rewind($this->fp);
			return TRUE;
		}
	}

	////////////////////////////////////////////////////////////
	// 関数  : 指定したディレクトリにtarファイルを展開する
	// 引数  : 展開するファイルパターン(正規表現)
	// 返り値: 展開したファイル名の一覧
	// 補足  : ARAIさんのattachプラグインパッチを参考にしました
	////////////////////////////////////////////////////////////
	function extract($pattern)
	{
		if ($this->status != $this->cont['TARLIB_STATUS_OPEN']) return ''; // Not opened
		
		$files = array();
		$longname = '';

		while(1) {
			$buff = fread($this->fp, $this->cont['TARLIB_HDR_LEN']);
			if (strlen($buff) != $this->cont['TARLIB_HDR_LEN']) break;

			// ファイル名
			$name = '';
			if ($longname != '') {
				$name     = $longname;	// LongLink対応
				$longname = '';
			} else {
				for ($i = 0; $i < $this->cont['TARLIB_HDR_NAME_LEN']; $i++ ) {
					if ($buff{$i + $this->cont['TARLIB_HDR_NAME_OFFSET']} != "\0") {
						$name .= $buff{$i + $this->cont['TARLIB_HDR_NAME_OFFSET']};
					} else {
						break;
					}
				}
			}
			$name = trim($name);

			if ($name == '') break;	// 展開終了

			// チェックサムを取得しつつ、ブランクに置換していく
			$checksum = '';
			$chkblanks = $this->cont['TARLIB_DATA_CHKBLANKS'];
			for ($i = 0; $i < $this->cont['TARLIB_HDR_CHKSUM_LEN']; $i++ ) {
				$checksum .= $buff{$i + $this->cont['TARLIB_HDR_CHKSUM_OFFSET']};
				$buff{$i + $this->cont['TARLIB_HDR_CHKSUM_OFFSET']} = $chkblanks{$i};
			}
			list($checksum) = sscanf('0' . trim($checksum), '%i');

			// Compute checksum
			$sum = 0;
			for($i = 0; $i < $this->cont['TARLIB_BLK_LEN']; $i++ ) {
				$sum += 0xff & ord($buff{$i});
			}
			if ($sum != $checksum) break; // Error
				
			// Size
			$size = '';
			for ($i = 0; $i < $this->cont['TARLIB_HDR_SIZE_LEN']; $i++ ) {
				$size .= $buff{$i + $this->cont['TARLIB_HDR_SIZE_OFFSET']};
			}
			list($size) = sscanf('0' . trim($size), '%i');

			// ceil
			// データブロックは512byteでパディングされている
			$pdsz = ceil($size / $this->cont['TARLIB_BLK_LEN']) * $this->cont['TARLIB_BLK_LEN'];

			// 最終更新時刻
			$strmtime = '';
			for ($i = 0; $i < $this->cont['TARLIB_HDR_MTIME_LEN']; $i++ ) {
				$strmtime .= $buff{$i + $this->cont['TARLIB_HDR_MTIME_OFFSET']};
			}
			list($mtime) = sscanf('0' . trim($strmtime), '%i');

			// タイプフラグ
//			 $type = $buff{TARLIB_HDR_TYPE_OFFSET};

			if ($name == $this->cont['TARLIB_DATA_LONGLINK']) {
				// LongLink
				$buff     = fread($this->fp, $pdsz);
				$longname = substr($buff, 0, $size);
			} else if (preg_match("/$pattern/", $name) ) {
//			} else if ($type == 0 && preg_match("/$pattern/", $name) ) {
				$buff = fread($this->fp, $pdsz);

				// 既に同じファイルがある場合は上書きされる
				$fpw = @fopen($name, 'wb');
				if ($fpw !== FALSE) {
					flock($fpw, LOCK_EX);
					fwrite($fpw, $buff, $size);
					@chmod($name, 0666);
					@touch($name, $mtime);

					fclose($fpw);
					$files[] = $name;
				}
			} else {
				// ファイルポインタを進める
				@fseek($this->fp, $pdsz, SEEK_CUR);
			}
		}
		return $files;
	}

	////////////////////////////////////////////////////////////
	// 関数  : tarファイルを閉じる
	// 引数  : なし
	// 返り値: なし
	////////////////////////////////////////////////////////////
	function close()
	{
		if ($this->status == $this->cont['TARLIB_STATUS_CREATE']) {
			// ファイルを閉じる
			if ($this->arc_kind == $this->cont['TARLIB_KIND_TGZ']) {
				// バイナリーゼロを1024バイト出力
				gzwrite($this->fp, $this->dummydata, $this->cont['TARLIB_HDR_LEN']);
				gzwrite($this->fp, $this->dummydata, $this->cont['TARLIB_HDR_LEN']);
				gzclose($this->fp);
			} else {
				// バイナリーゼロを1024バイト出力
				fwrite($this->fp, $this->dummydata, $this->cont['TARLIB_HDR_LEN']);
				fwrite($this->fp, $this->dummydata, $this->cont['TARLIB_HDR_LEN']);
				fclose($this->fp);
			}
		} else if ($this->status == $this->cont['TARLIB_STATUS_OPEN']) {
			if ($this->arc_kind == $this->cont['TARLIB_KIND_TGZ']) {
				gzclose($this->fp);
			} else {
				 fclose($this->fp);
			}
		}

		$this->status = $this->cont['TARLIB_STATUS_INIT'];
	}

}
?>