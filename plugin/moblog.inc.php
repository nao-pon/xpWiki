<?php
// $Id: moblog.inc.php,v 1.6 2008/05/08 00:10:00 nao-pon Exp $
// Author: nao-pon http://hypweb.net/
// Bace script is pop.php of mailbbs by Let's PHP!
// Let's PHP! Web: http://php.s3.to/
	
class xpwiki_plugin_moblog extends xpwiki_plugin {
	function plugin_moblog_init () {
		////// 必須設定項目 ///////
		
		// 受信用メールアドレス
		$this->config['mail'] = '';
		// POPサーバー
		$this->config['host'] = 'localhost';
		// POPサーバーアカウント
		$this->config['user'] = '';
		// POPサーバーパスワード
		$this->config['pass'] = '';
		// POPサーバーポート番号
		$this->config['port'] = 110;
		
		// 送信元アドレスによって振り分けるページの指定
		// ページ名空白 '' で無視(投稿登録中止)
		$this->config['adr2page'] = array(
		//	'メールアドレス'   => array('ページ名', UserIDナンバー),
		//	'hoge@example.com' => array('日記', 1),	// 設定例
			'other'            => array('', 0),	    // 登録メールアドレス以外
		);
		
		////// 必須設定項目終了 //////
		
		//////////////////////////////
		///// 以下はお好みで設定 /////
		
		// refプラグインの追加オプション
		$this->config['ref'] = ',left,around,mw:240,mh:240';
		
		// googlemaps の追加オプション
		$this->config['gmap'] = ',width=100%,height=300px,zoom=15,type=normal,overviewctrl=1,autozoom=1';
		
		// 最大添付量（バイト・1ファイルにつき）※超えるものは保存しない
		$this->config['maxbyte'] = "1048576"; //1MB
		
		// 本文文字制限（半角で
		$this->config['body_limit'] = 1000;
		
		// 最小自動更新間隔（分）
		$this->config['refresh_min'] = 5;
		
		// 件名がないときの題名
		$this->config['nosubject'] = "";
		
		// 許可する Received-SPF: ヘッダ
		// Received-SPF: ヘッダを付加しないMTAは、「チェックしない」にする。
		$this->config['allow_spf'] = '';                     // チェックしない
		//$this->config['allow_spf'] = '/pass/i';              // pass のみ許可 (奨励)
		//$this->config['allow_spf'] = '/pass|none|neutral/i'; // pass, none, neutral を許可
		
		// 投稿非許可アドレス（ログに記録しない）
		$this->config['deny'] = array('163.com','bigfoot.com','boss.com','yahoo-delivers@mail.yahoo.co.jp');
		
		// 投稿非許可メーラー(perl互換正規表現)（ログに記録しない）
		$this->config['deny_mailer'] = '/(Mail\s*Magic|Easy\s*DM|Friend\s*Mailer|Extra\s*Japan|The\s*Bat)/i';
		
		// 投稿非許可タイトル(perl互換正規表現)（ログに記録しない）
		$this->config['deny_title'] = '/((未|末)\s?承\s?(諾|認)\s?広\s?告)|相互リンク/i';
		
		// 投稿非許可キャラクターセット(perl互換正規表現)（ログに記録しない）
		$this->config['deny_lang'] = '/us-ascii|big5|euc-kr|gb2312|iso-2022-kr|ks_c_5601-1987/i';
		
		// 対応MIMEタイプ（正規表現）Content-Type: image/jpegの後ろの部分。octet-streamは危険かも
		$this->config['subtype'] = "gif|jpe?g|png|bmp|octet-stream|x-pmd|x-mld|x-mid|x-smd|x-smaf|x-mpeg";
		
		// 保存しないファイル(正規表現)
		$this->config['viri'] = ".+\.exe$|.+\.zip$|.+\.pif$|.+\.scr$";
		
		// 25字以上の下線は削除（広告区切り）
		$this->config['del_ereg'] = "[_]{25,}";
		
		// 本文から削除する文字列
		$this->config['word'][] = "http://auction.msn.co.jp/";
		$this->config['word'][] = "Do You Yahoo!?";
		$this->config['word'][] = "Yahoo! BB is Broadband by Yahoo!";
		$this->config['word'][] = "http://bb.yahoo.co.jp/";
		
		// 添付メールのみ記録する？Yes=1 No=0（本文のみはログに載せない）
		$this->config['imgonly'] = 0;
	}
	function plugin_moblog_action()
	{
		error_reporting(0);
		//error_reporting(E_ALL);
		$this->debug = array();
		//設定ファイル読み込み
		$host = (string)$this->config['host'];
		$mail = (string)$this->config['mail'];
		$user = (string)$this->config['user'];
		$pass = (string)$this->config['pass'];
		$port = (int)$this->config['port'];
		$adr2page = (array)$this->config['adr2page'];
		$ref_option = (string)$this->config['ref'];
		$maxbyte = (int)$this->config['maxbyte'];
		$body_limit = (int)$this->config['body_limit'];
		$refresh_min = (int)$this->config['refresh_min'];
		$nosubject = (string)$this->config['nosubject'];
		$deny = (array)$this->config['deny'];
		$deny_mailer = (string)$this->config['deny_mailer'];
		$deny_title = (string)$this->config['deny_title'];
		$deny_lang = (string)$this->config['deny_lang'];
		$subtype = (string)$this->config['subtype'];
		$viri = (string)$this->config['viri'];
		$del_ereg = (string)$this->config['del_ereg'];
		$word = (array)$this->config['word'];
		$imgonly = (int)$this->config['imgonly'];
		
		if (! $user || ! $pass) $this->plugin_moblog_output();
		
		$chk_file = $this->cont['CACHE_DIR']."moblog.chk";
		if (! file_exists($chk_file)) {
			touch($chk_file);
		} else if ($refresh_min * 60 > $this->cont['UTC'] - filemtime($chk_file) && empty($this->root->vars['now'])) {
			$this->plugin_moblog_output();
		} else {
			touch($chk_file);
		}
		
		// wait 指定
		$wait = (empty($this->root->vars['wait']))? 0 : (int)$this->root->vars['wait'];
		sleep(min(5, $wait));
		
		// 接続開始
		$err = "";
		$num = $size = $errno = 0;
		$this->sock = fsockopen($host, $port, $err, $errno, 10) or $this->plugin_moblog_error_output('Could not connect to ' . $host . ':' . $port);
		$buf = fgets($this->sock, 512);
		if(substr($buf, 0, 3) != '+OK') {
			$this->plugin_moblog_error_output($buf);
		}
		$buf = $this->plugin_moblog_sendcmd("USER $user");
		$buf = $this->plugin_moblog_sendcmd("PASS $pass");
		$data = $this->plugin_moblog_sendcmd("STAT");//STAT -件数とサイズ取得 +OK 8 1234
		sscanf($data, '+OK %d %d', $num, $size);
	
		if ($num == "0") {
			$buf = $this->plugin_moblog_sendcmd("QUIT"); //バイバイ
			fclose($this->sock);
			$this->plugin_moblog_output ();
		}
		// 件数分
		for($i=1;$i<=$num;$i++) {
			$line = $this->plugin_moblog_sendcmd("RETR $i");//RETR n -n番目のメッセージ取得（ヘッダ含
			$dat[$i] = "";
			while (!ereg("^\.\r\n",$line)) {//EOFの.まで読む
				$line = fgets($this->sock,512);
				$dat[$i].= $line;
			}
			$data = $this->plugin_moblog_sendcmd("DELE $i");//DELE n n番目のメッセージ削除
		}
		$buf = $this->plugin_moblog_sendcmd("QUIT"); //バイバイ
		fclose($this->sock);
	
		for($j=1;$j<=$num;$j++) {
			$write = true;
			$subject = $from = $text = $atta = $part = $attach = $filename = "";
			$filenames = array();
			$rotate = 0;
			list($head, $body) = $this->plugin_moblog_mime_split($dat[$j]);
			// To:ヘッダ確認
			$treg = array();
			if (preg_match("/(?:^|\n|\r)To:[ \t]*([^\r\n]+)/i", $head, $treg)){
				$toreg = "/".quotemeta($mail)."/";
				if (!preg_match($toreg,$treg[1])) $write = false; //投稿アドレス以外
				$this->debug[] = 'Bad to addr.';
			} else {
				// To: ヘッダがない
				$write = false;
				$this->debug[] = 'To: not found.';
			}
			
			// Received-SPF: のチェック
			if ($this->config['allow_spf']) {
				if (preg_match('/^Received-SPF:\s*([a-z]+)/im', $head, $match)) {
					if (! preg_match($this->config['allow_spf'], $match[1])) {
						$write = false;
						$this->debug[] = 'Bad SPF.';
					}
				}
			}
			
			// メーラーのチェック
			$mreg = array();
			if ($write && (eregi("(X-Mailer|X-Mail-Agent):[ \t]*([^\r\n]+)", $head, $mreg))) {
				if ($deny_mailer){
					if (preg_match($deny_mailer,$mreg[2])) $write = false;
					$this->debug[] = 'Bad mailer.';
				}
			}
			// キャラクターセットのチェック
			if ($write && (eregi("charset[\s]*=[\s]*([^\r\n]+)", $head, $mreg))) {
				if ($deny_lang){
					if (preg_match($deny_lang,$mreg[1])) $write = false;
					$this->debug[] = 'Bad charset.';
				}
			}
			// 日付の抽出
			$datereg = array();
			eregi("Date:[ \t]*([^\r\n]+)", $head, $datereg);
			$now = strtotime($datereg[1]);
			if ($now == -1) $now = $this->cont['UTC'];
			// サブジェクトの抽出
			$subreg = array();
			if (preg_match("/\nSubject:[ \t]*(.+?)(\n[\w-_]+:|$)/is", $head, $subreg)) {
				// 改行文字削除
				$subject = str_replace(array("\r","\n"),"",$subreg[1]);
				// エンコード文字間の空白を削除
				$subject = preg_replace("/\?=[\s]+?=\?/","?==?",$subject);
				$regs = array();
				while (eregi("(.*)=\?iso-[^\?]+\?B\?([^\?]+)\?=(.*)",$subject,$regs)) {//MIME B
					$subject = $regs[1].base64_decode($regs[2]).$regs[3];
				}
				$regs = array();
				while (eregi("(.*)=\?iso-[^\?]+\?Q\?([^\?]+)\?=(.*)",$subject,$regs)) {//MIME Q
					$subject = $regs[1].quoted_printable_decode($regs[2]).$regs[3];
				}
				$subject = trim(mb_convert_encoding($subject,"EUC-JP","AUTO"));
				
				//回転指定コマンド検出
				if (preg_match("/(.*)(?:(r|l)@)$/i",$subject,$match))
				{
					$subject = rtrim($match[1]);
					$rotate = (strtolower($match[2]) == "r")? 1 : 3;
				}
				
				// 未承諾広告カット
				if ($write && $deny_title){
					if (preg_match($deny_title,$subject)) $write = false;
					$this->debug[] = 'Bad title.';
				}
			}
			// 送信者アドレスの抽出
			$freg = array();
			if (eregi("From:[ \t]*([^\r\n]+)", $head, $freg)) {
				$from = $this->plugin_moblog_addr_search($freg[1]);
			} elseif (eregi("Reply-To:[ \t]*([^\r\n]+)", $head, $freg)) {
				$from = $this->plugin_moblog_addr_search($freg[1]);
			} elseif (eregi("Return-Path:[ \t]*([^\r\n]+)", $head, $freg)) {
				$from = $this->plugin_moblog_addr_search($freg[1]);
			}
			
			$today = getdate($now);
			$date = sprintf("/%04d-%02d-%02d-0",$today['year'],$today['mon'],$today['mday']);
			
			// 拒否アドレス
			if ($write){
				for ($f=0; $f<count($deny); $f++)
					if (eregi($deny[$f], $from)) $write = false;
					$this->debug[] = 'Bad from addr.';
			}

			// 登録対象ページを設定
			if ($write) {
				$page = "";
				$uid = 0;
				if (!empty($adr2page[$from])) {
					$_page = (is_array($adr2page[$from]))? $adr2page[$from][0] : $adr2page[$from];
					if (is_array($adr2page[$from])) $uid = $adr2page[$from][1];
				} else {
					$_page = (is_array($adr2page['other']))? $adr2page['other'][0] : $adr2page['other'];
				}
				if ($_page) $page = $_page . $date;
				if ($page) {
					// userinfo を設定
					$this->root->userinfo = $this->func->get_userinfo_by_id($uid);
					$this->root->userinfo['ucd'] = '';
					$this->root->cookie['name']  = '';
					$this->is_newpage = ! $this->func->is_page($page);
				} else {
					$write = false;
					$this->debug[] = 'Allow page not found.';
				}				
			}
			
			if ($write) {
				// マルチパートならばバウンダリに分割
				if (eregi("\nContent-type:.*multipart/",$head)) {
					$boureg = array();
					eregi('boundary="([^"]+)"', $head, $boureg);
					$body = str_replace($boureg[1], urlencode($boureg[1]), $body);
					$part = split("\r\n--".urlencode($boureg[1])."-?-?",$body);
					$boureg2 = array();
					if (eregi('boundary="([^"]+)"', $body, $boureg2)) {//multipart/altanative
						$body = str_replace($boureg2[1], urlencode($boureg2[1]), $body);
						$body = eregi_replace("\r\n--".urlencode($boureg[1])."-?-?\r\n","",$body);
						$part = split("\r\n--".urlencode($boureg2[1])."-?-?",$body);
					}
				} else {
					$part[0] = $dat[$j];// 普通のテキストメール
				}

				foreach ($part as $multi) {
					if (! $write) break;
					@ list($m_head, $m_body) = $this->plugin_moblog_mime_split($multi);
					if (!$m_body) continue;
					$filename = '';
					$m_body = ereg_replace("\r\n\.\r\n$", "", $m_body);
					// キャラクターセットのチェック
					if ($write && (eregi("charset[\s]*=[\s]*([^\r\n]+)", $m_head, $mreg))) {
						if ($deny_lang){
							if (preg_match($deny_lang,$mreg[1])) $write = false;
							$this->debug[] = 'Bad charset.';
						}
					}
					$type = array();
					if (!eregi("Content-type: *([^;\n]+)", $m_head, $type)) continue;
					list($main, $sub) = explode("/", $type[1]);
					// 本文をデコード
					if (strtolower($main) == "text") {
						if (eregi("Content-Transfer-Encoding:.*base64", $m_head)) 
							$m_body = base64_decode($m_body);
						if (eregi("Content-Transfer-Encoding:.*quoted-printable", $m_head)) 
							$m_body = quoted_printable_decode($m_body);
						$text = trim(mb_convert_encoding($m_body, $this->cont['SOURCE_ENCODING'], 'AUTO'));
						if ($sub == "html") $text = strip_tags($text);
						$text = str_replace(array("\r\n", "\r"), "\n", $text);
						$text = preg_replace("/\n{2,}/", "\n\n", $text);
						if ($write) {
							// 電話番号削除
							$text = eregi_replace("([[:digit:]]{11})|([[:digit:]\-]{13})", "", $text);
							// 下線削除
							$text = eregi_replace($del_ereg, "", $text);
							// mac削除
							$text = ereg_replace("Content-type: multipart/appledouble;[[:space:]]boundary=(.*)","",$text);
							// 広告等削除
							if (is_array($word)) {
								foreach ($word as $delstr)
									$text = str_replace($delstr, "", $text);
							}
							if (strlen($text) > $body_limit) $text = substr($text, 0, $body_limit)."...";
						}
					}
					// ファイル名を抽出
					$filereg = array();
					if (eregi("name=\"?([^\"\n]+)\"?",$m_head, $filereg)) {
						$filename = trim($filereg[1]);
						// エンコード文字間の空白を削除
						$filename = preg_replace("/\?=[\s]+?=\?/","?==?",$filename);
						while (eregi("(.*)=\?iso-[^\?]+\?B\?([^\?]+)\?=(.*)",$filename,$regs)) {//MIME B
							$filename = $regs[1].base64_decode($regs[2]).$regs[3];
						}
						$filename = mb_convert_encoding($filename,"EUC-JP","AUTO");
					}
					// 添付データをデコードして保存
					if (eregi("Content-Transfer-Encoding:.*base64", $m_head) && eregi($subtype, $sub)) {
						$tmp = base64_decode($m_body);
						if (!$filename) $filename = $this->cont['UTC'].".$sub";

						$save_file = $this->cont['CACHE_DIR'].$this->func->encode($filename).".tmp";
						
						if (strlen($tmp) < $maxbyte && $write && $this->func->exist_plugin('attach'))
						{
							$fp = fopen($save_file, "wb");
							fputs($fp, $tmp);
							fclose($fp);
							//回転指定
							if ($rotate)
							{
								HypCommonFunc::rotateImage($save_file, $rotate);
							}
							// ページが無ければ空ページを作成
							if (!$this->func->is_page($page)) {
								$this->func->page_write($page, "\t");
							}
							$attach = $this->func->get_plugin_instance('attach');
							$res = $attach->do_upload($page,$filename,$save_file);
							if ($res['result']) {
								$filenames[] = $res['name'];
							}
						} else {
							$write = false;
							$this->debug[] = 'Attach not found.';
						}
					}
				}
				if ($imgonly && $attach=="") $write = false;
				
				$subject = trim($subject);
			}
			
			// wikiページ書き込み
			if ($write) $this->plugin_moblog_page_write($page,$subject,$text,$filenames,$ref_option,$now);
		}
		// imgタグ呼び出し
		$this->plugin_moblog_output();
	}
	function plugin_moblog_convert() {
		if (! $this->config['user'] || ! $this->config['pass']) {
			return '';
		} else {
			//POPサーバーにアクセスするためのイメージタグを生成
			return '<div style="float:left;"><img src="' . $this->root->script . '?plugin=moblog" width="1" height="1" /></div>' . "\n";
		}
	}
	
	function plugin_moblog_page_write($page,$subject,$text,$filenames,$ref_option,$now) {
		
		$aids = $gids = $freeze = "";
		$date = "at ".date("g:i a", $now);

		$set_data = ($subject)?  "**$subject\n" : "----\n";
		if ($filenames) {
			foreach($filenames as $filename) {
				$set_data .= "#ref(".$filename.$ref_option.")\n";
			}
		}
		$set_data .= $text."\n\n".$date."\n#clear";
		
		// 念のためページ情報を削除
		$set_data = $this->func->remove_pginfo($set_data);
		
		// 改行文字調整
		$set_data = ltrim($set_data, "\r\n");
		$set_data = rtrim($set_data)."\n\n";
		
		if ($this->is_newpage) {
			//ページ新規作成
			$template = ':template_m/' . preg_replace('/(.*)\/[^\/]+/', '$1', $page);
			if ($this->func->is_page($template)) {
				$page_data = rtrim(join('',$this->func->get_source($template)))."\n";
			} else {
				$page_data = '';
			}
		} else {
			$page_data = rtrim(join('',$this->func->get_source($page)))."\n";
		}
		$page_data = $this->func->remove_pginfo($page_data);
		
		$this->make_googlemaps($page_data, $set_data, $subject, $date);
		
		if (preg_match("/\/\/ Moblog Body\n/",$page_data)) {
			$page_data = preg_split("/\/\/ Moblog Body[ \t]*\n/",$page_data,2);
			$save_data = rtrim($page_data[0]) . "\n\n" . $set_data."// Moblog Body\n".$page_data[1];
		} else 	{
			$save_data = $page_data . "\n" . $set_data;
		}
		
		// ページ更新
		$this->func->page_write($page, $save_data);
		
		$this->debug[] = 'Page write ' . $page;
	}
	
	function make_googlemaps ($pagedata, & $set_data, $subject, $date) {
		if (preg_match('/pos=N([0-9.]+)E([0-9.]+)([^\s]+)(.*)$/mi', $set_data, $prm)) {
			$lats = explode('.', $prm[1]);
			$lngs = explode('.', $prm[2]);
			$lats = array_pad($lats, 4, 0);
			$lngs = array_pad($lngs, 4, 0);
			$title = (! empty($prm[4]))? trim($prm[4]) : '';
			$title = $title? $title : $date;
			$lat = $lats[0] + ($lats[1] / 60 + ((float)($lats[2] . '.' . $lats[3]) / 3600));
			$lng = $lngs[0] + ($lngs[1] / 60 + ((float)($lngs[2] . '.' . $lngs[3]) / 3600));
			$map = '';
			if (! preg_match('/^#googlemaps2/m', $pagedata)) {
				$map = "\n" . '#googlemaps2(lat=' . $lat . ',lng=' . $lng . $this->config['gmap'] . ')' . "\n";
			}
			$marker = "\n" . '-&googlemaps2_mark(' . $lat . ',' . $lng . ',"title=Marker: ' . $title . '"){' . ($subject? $subject . '&br;' : '') . '( ' . $date . ' )};' . "\n";
			$set_data = preg_replace('/^(.+pos=N[0-9.]+E[0-9.]+[^\s]+).*$/mi', $map . '$1' . $marker, $set_data);
		}
	}
	
	// コマンド送信
	function plugin_moblog_sendcmd($cmd) {
		fputs($this->sock, $cmd."\r\n");
		$buf = fgets($this->sock, 512);
		if(substr($buf, 0, 3) == '+OK') {
			return $buf;
		} else {
			$this->plugin_moblog_error_output($buf);
		}
		return false;
	}
	
	// ヘッダと本文を分割する
	function plugin_moblog_mime_split($data) {
		$part = split("\r\n\r\n", $data, 2);
		$part[0] = ereg_replace("\r\n[\t ]+", " ", $part[0]);
		return $part;
	}
	
	// メールアドレスを抽出する
	function plugin_moblog_addr_search($addr) {
		$fromreg = array();
		if (eregi("[-!#$%&\'*+\\./0-9A-Z^_`a-z{|}~]+@[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+", $addr, $fromreg)) {
			return $fromreg[0];
		} else {
			return false;
		}
	}
	
	// エラー出力
	function plugin_moblog_error_output($str) {
		echo 'error: ' . $str;
		exit();
		//header("Content-Type: image/gif");
		//readfile('poperror.gif');
	}
	
	// イメージ出力
	function plugin_moblog_output () {
		// clear output buffer
		while( ob_get_level() ) {
			ob_end_clean() ;
		}
		// imgタグ呼び出し用
		header("Content-Type: image/gif");
		readfile($this->root->mytrustdirpath . '/skin/image/gif/spacer.gif');
		//echo 'Debug:<br />' . join('<br />', $this->debug);
		exit();
	}
}
?>