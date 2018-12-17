<?php
// Author: nao-pon http://hypweb.net/
// Bace script is pop.php of mailbbs by Let's PHP!
// Let's PHP! Web: http://php.s3.to/

class xpwiki_plugin_moblog extends xpwiki_plugin {
	function plugin_moblog_init () {

		// ���������ɥ쥹�ˤ�äƿ���ʬ����ڡ����λ���
		// �ڡ���̾���� '' ��̵��(�����Ͽ���)
		// �Ķ�����ˤ� user_pref �Ǥ��������Ĥ��ʤ����ˤ��������ꤹ��
		$this->config['adr2page'] = array(
		//	'�᡼�륢�ɥ쥹'   => array('�ڡ���̾', UserID�ʥ�С�),
		//	'hoge@example.com' => array('����', 1),	// ������
			'other'            => array('', 0),	    // ��Ͽ�᡼�륢�ɥ쥹�ʳ�
		);

		// �����ʸ�Υƥ�ץ졼��
		$this->config['template'] = "__ATTACHES__\n__TEXT__\n\n__DATE__";

		// ʣ��ź�եե����������³��
		$this->config['attach_glue'] = "#clear\n";

		// ref�ץ饰������ɲå��ץ����
		$this->config['ref'] = ',left,around,mw:320,mh:320';

		// googlemaps ���ɲå��ץ����
		$this->config['gmap'] = ',width=90%,height=300px,zoom=15,type=normal,overviewctrl=1,autozoom=1';

		// ����10��ޤ���13��ΤߤιԤ�ISBN �Ȥ��ư��������Ѵ��� (������Ѵ�̵��)
		$this->config['isbn'] = "#isbn(__ISBN__,h)\n#isbn(__ISBN__,info)";

		// �������@amazon �ΤߤιԤ��Ѵ��� (������Ѵ�̵��)
		$this->config['amazon'] = '#aws(w5,blended,__KEYWORD__)';

		// ����ź���̡ʥХ��ȡ�1�ե�����ˤĤ��ˢ�Ķ�����Τ���¸���ʤ�
		$this->config['maxbyte'] = $this->func->return_bytes(ini_get('upload_max_filesize'));

		// ��ʸʸ�����¡�Ⱦ�Ѥ�
		$this->config['body_limit'] = 6000;

		// �Ǿ������ֳ֡�ʬ��
		$this->config['refresh_min'] = 5;

		// ��̾���ʤ��Ȥ�����̾
		$this->config['nosubject'] = "";

		// ���Ĥ��� Received-SPF: �إå�
		// Received-SPF: �إå����ղä��ʤ�MTA�ϡ��֥����å����ʤ��פˤ��롣
		$this->config['allow_spf'] = '';                     // �����å����ʤ�
		//$this->config['allow_spf'] = '/pass/i';              // pass �Τߵ��� (����)
		//$this->config['allow_spf'] = '/pass|none|neutral/i'; // pass, none, neutral �����

		// �������ĥ��ɥ쥹�ʥ��˵�Ͽ���ʤ���
		$this->config['deny'] = array('163.com','bigfoot.com','boss.com','yahoo-delivers@mail.yahoo.co.jp');

		// �������ĥ᡼�顼(perl�ߴ�����ɽ��)�ʥ��˵�Ͽ���ʤ���
		$this->config['deny_mailer'] = '';

		// �������ĥ����ȥ�(perl�ߴ�����ɽ��)�ʥ��˵�Ͽ���ʤ���
		$this->config['deny_title'] = '';

		// �������ĥ���饯�������å�(perl�ߴ�����ɽ��)�ʥ��˵�Ͽ���ʤ���
		$this->config['deny_lang'] = '';

		// �б�MIME�����ס�����ɽ����Content-Type: image/jpeg�θ�����ʬ��octet-stream�ϴ�����
		$this->config['subtype'] = "gif|jpe?g|png|bmp|octet-stream|x-pmd|x-mld|x-mid|x-smd|x-smaf|x-mpeg|3gpp2?";

		// ��¸���ʤ��ե�����(����ɽ��)
		$this->config['viri'] = ".+\.exe$|.+\.pif$|.+\.scr$";

		// 25���ʾ�β����Ϻ���ʹ�����ڤ��
		$this->config['del_ereg'] = "[_]{25,}";

		// ��ʸ����������ʸ����
		$this->config['word'][] = "http://auction.msn.co.jp/";
		$this->config['word'][] = "Do You Yahoo!?";
		$this->config['word'][] = "Yahoo! BB is Broadband by Yahoo!";
		$this->config['word'][] = "http://bb.yahoo.co.jp/";

		// ź�ե᡼��Τߵ�Ͽ���롩Yes=1 No=0����ʸ�Τߤϥ��˺ܤ��ʤ���
		$this->config['imgonly'] = 0;

		// google map ��ư������̵���ˤ���
		$this->config['nomap'] = 0;

		// ����᡼������å��ֳ� (OFF:0, ʬ)
		$this->config['check_interval'] = 5;

	}
	function plugin_moblog_action()
	{
		error_reporting(0);

		$this->debug = array();
		$this->admin = $this->root->userinfo['admin'];
		$this->chk_fp = NULL;

		$this->output_mode = (isset($this->root->vars['om']) && $this->root->vars['om'] === 'rss')? 'rss' : 'img';

		$host = $user = $pass = $port = '';
		$execution_time = intval(ini_get('max_execution_time'));

		//����ե������ɤ߹���
		if (isset($this->config['host'])) $host = (string)$this->config['host'];
		if (isset($this->config['mail'])) $mail = (string)$this->config['mail'];
		if (isset($this->config['user'])) $user = (string)$this->config['user'];
		if (isset($this->config['pass'])) $pass = (string)$this->config['pass'];
		if (isset($this->config['port'])) $port = (int)$this->config['port'];
		foreach(array('mail', 'host', 'port', 'user', 'pass') as $key) {
			$_key = 'moblog_pop_' . $key;
			if (! empty($this->root->$_key)) {
				$$key = $this->root->$_key;
			}
		}

		if (!$host || ! $user || ! $pass || ! $port) $this->plugin_moblog_output();

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


		$chk_file = $this->cont['CACHE_DIR']."moblog.chk";
		if (! is_file($chk_file)) {
			touch($chk_file);
		} else if ($refresh_min * 60 > $this->cont['UTC'] - filemtime($chk_file) && empty($this->root->vars['now'])) {
			$this->plugin_moblog_output();
		} else {
			$this->func->pkwk_touch_file($chk_file);
		}

		if ($this->config['check_interval']) {
			$interval = max($this->config['check_interval'], $this->config['refresh_min']);
			$data = array(
				'action' => 'plugin_func',
				'plugin' => 'moblog',
				'func'   => 'plugin_moblog_action'
			);
			$this->func->regist_jobstack($data, 0, $interval * 60);
		}

		$this->chk_fp = fopen($chk_file, 'wb');
		if (! flock($this->chk_fp, LOCK_EX)) {
			$this->plugin_moblog_output();
		}

		// user_pref �ɤ߹���
		$adr2page = (array)$this->config['adr2page'];
		$user_pref_all = $this->func->get_user_pref();
		if ($user_pref_all) {
			foreach($user_pref_all as $_uid => $_dat) {
				$_dat = unserialize($_dat);
				if (! empty($_dat['moblog_base_page'])) {
					if (! empty($_dat['moblog_mail_address'])) {
						$adr2page[strtolower($_dat['moblog_mail_address'])] = array($_dat['moblog_base_page'], $_uid);
					} else if (! empty($_dat['moblog_user_mail'])) {
						$adr2page[strtolower($_dat['moblog_user_mail'])] = array($_dat['moblog_base_page'], $_uid);
					}
				}
			}
		}

		// SMS(MMS) ��ͳ�Υǡ������ɤ߹���
		if ($smsdata = $this->func->cache_get_db(null, 'moblog')) {
			foreach($smsdata as $_data) {
				$_data = unserialize($_data);
				$adr2page = array_merge($adr2page, $_data);
			}
		}

		// attach �ץ饰�����ɤ߹���
		$attach = $this->func->get_plugin_instance('attach');

		// wait ����
		$wait = (empty($this->root->vars['wait']))? 0 : (int)$this->root->vars['wait'];
		sleep(min(5, $wait));

		// ��³����
		$err = "";
		$num = $size = $errno = 0;
		$this->sock = fsockopen($host, $port, $err, $errno, 10) or $this->plugin_moblog_error_output('Could not connect to ' . $host . ':' . $port);
		$buf = fgets($this->sock, 512);
		if(substr($buf, 0, 3) != '+OK') {
			$this->plugin_moblog_error_output($buf);
		}
		$buf = $this->plugin_moblog_sendcmd("USER $user");
		if(substr($buf, 0, 3) != '+OK') {
			$this->plugin_moblog_error_output($buf);
		}
		$buf = $this->plugin_moblog_sendcmd("PASS $pass");
		if(substr($buf, 0, 3) != '+OK') {
			$this->plugin_moblog_error_output($buf);
		}
		$data = $this->plugin_moblog_sendcmd("STAT");//STAT -����ȥ��������� +OK 8 1234
		sscanf($data, '+OK %d %d', $num, $size);

		if ($num == "0") {
			$buf = $this->plugin_moblog_sendcmd("QUIT"); //�Х��Х�
			fclose($this->sock);
			$this->debug[] = 'No mail.';
			$this->plugin_moblog_output();
		}

		$this->debug[] = $num . ' message(s) found.';

		$tmpfiles = array();
		// ���ʬ
		for($i=1;$i<=$num;$i++) {
			$line = $this->plugin_moblog_sendcmd("RETR $i");//RETR n -n���ܤΥ�å����������ʥإå���
			$dat = '';
			while (! preg_match("/^\.\r\n/",$line) && $line !== false) {//EOF��.�ޤ��ɤ�
				$line = fgets($this->sock,4096);
				$dat.= $line;
			}
			$data = $this->plugin_moblog_sendcmd("DELE $i");//DELE n n���ܤΥ�å��������
			$tmpfname = tempnam($this->cont['CACHE_DIR'], 'moblog');
			file_put_contents($tmpfname, $dat);
			$tmpfiles[] = $tmpfname;
		}
		$buf = $this->plugin_moblog_sendcmd("QUIT"); //�Х��Х�
		fclose($this->sock);

		foreach ($tmpfiles as $tmpfname) {

			if ($execution_time) {
				@ set_time_limit($execution_time);
			}

			$write = true;
			$subject = $from = $text = $atta = $part = $filename = $charset = '';
			$this->user_pref = array();
			$this->post_options = array();
			$this->is_newpage = 0;
			$filenames = array();
			$body_text = array();
			$rotate = 0;
			$page = '';
			$exifgeo = array();
			$attach_only = false;
			$this->root->vars['refid'] = '';

			unset($this->root->rtf['esummary'], $this->root->rtf['twitter_update']);

			$dat = file_get_contents($tmpfname);
			unlink($tmpfname);
			list($head, $body) = $this->plugin_moblog_mime_split($dat);

			// To:�إå���ǧ
			$treg = array();
			$to_ok = FALSE;
			if (preg_match("/^To:[ \t]*([^\r\n]+)/im", $head, $treg)){
				$treg[1] = $this->plugin_moblog_addr_search($treg[1]);
				$mail_reg = preg_quote($mail, '/');
				$mail_reg = '/' . str_replace('\\*', '[^@]*?', $mail_reg) . '/i';
				//if ($mail === $treg[1]) {
				if (preg_match($mail_reg, $treg[1])) {
					$to = $treg[1];
					$to_ok = TRUE;
				} else if (preg_match("/^X-Forwarded-To:[ \t]*([^\r\n]+)/im", $head, $treg)) {
					//if ($mail === $treg[1]) {
					$treg[1] = $this->plugin_moblog_addr_search($treg[1]);
					if (preg_match($mail_reg, $treg[1])) {
						$to = $treg[1];
						$to_ok = TRUE;
					}
				}
			}
			if (! $to_ok) {
				$write = false;
				$this->debug[] = 'Bad To: '. $to;
			}
			$to = strtolower($to);

			// Received-SPF: �Υ����å�
			if ($this->config['allow_spf']) {
				if (preg_match('/^Received-SPF:\s*([a-z]+)/im', $head, $match)) {
					if (! preg_match($this->config['allow_spf'], $match[1])) {
						$write = false;
						$this->debug[] = 'Bad SPF.';
					}
				}
			}

			// �᡼�顼�Υ����å�
			$mreg = array();
			if ($write && (preg_match("#^(X-Mailer|X-Mail-Agent):[ \t]*([^\r\n]+)#im", $head, $mreg))) {
				if ($deny_mailer){
					if (preg_match($deny_mailer,$mreg[2])) {
						$write = false;
						$this->debug[] = 'Bad mailer.';
					}
				}
			}
			// ����饯�������åȤΥ����å�
			if ($write && (preg_match('/charset\s*=\s*"?([^"\r\n]+)/i', $head, $mreg))) {
				$charset = $mreg[1];
				if ($deny_lang){
					if (preg_match($deny_lang,$charset)) {
						$write = false;
						$this->debug[] = 'Bad charset.';
					}
				}
			}
			// ���դ����
			$datereg = array();
			preg_match("#^Date:[ \t]*([^\r\n]+)#im", $head, $datereg);
			$now = strtotime($datereg[1]);
			if ($now == -1) $now = $this->cont['UTC'];

			// �����ԥ��ɥ쥹�����
			$freg = array();
			if (preg_match("#^From:[ \t]*([^\r\n]+)#im", $head, $freg)) {
				$from = $this->plugin_moblog_addr_search($freg[1]);
			} elseif (preg_match("#^Reply-To:[ \t]*([^\r\n]+)#im", $head, $freg)) {
				$from = $this->plugin_moblog_addr_search($freg[1]);
			} elseif (preg_match("#^Return-Path:[ \t]*([^\r\n]+)#im", $head, $freg)) {
				$from = $this->plugin_moblog_addr_search($freg[1]);
			}
			$from = strtolower($from);

			// ���֥������Ȥ����
			$subreg = array();
			if (preg_match("#^Subject:[ \t]*([^\r\n]+)#im", $head, $subreg)) {

				if (HypCommonFunc::get_version() >= '20081215') {
					if (! XC_CLASS_EXISTS('MobilePictogramConverter')) {
						HypCommonFunc::loadClass('MobilePictogramConverter');
					}
					$mpc =& MobilePictogramConverter::factory_common();
				} else {
					$mpc = null;
				}

				// ����ʸ�����
				$subject = str_replace(array("\r","\n"),"",$subreg[1]);

				$subject = $this->mime_decode($subject, $mpc, $from);

				// ^\*\d+ ǧ�ڥ������
				$_reg = '/^\*(\d+)/i';
				if (preg_match($_reg, $subject, $match)) {
					$this->post_options['auth_code'] = $match[1];
					$subject = trim(preg_replace($_reg, '', $subject, 1));
				}

				// �ڡ������ꥳ�ޥ�ɸ���
				$_reg = '/@&([^&]+)&/';
				if (preg_match($_reg, $subject, $match)) {
					$page = $match[1];
					$subject = trim(preg_replace($_reg, '', $subject, 1));
				}

				// �����쥯�ȥڡ������ꥳ�ޥ�ɸ���
				$_reg = '/@&([^\$]+)\$/';
				if (preg_match($_reg, $subject, $match)) {
					$page = $match[1];
					$subject = trim(preg_replace($_reg, '', $subject, 1));
					$this->post_options['directpage'] = 1;
				}

				// ��ž���ꥳ�ޥ�ɸ���
				$_reg = '/@(r|l)\b/i';
				if (preg_match($_reg, $subject, $match)) {
					$rotate = (strtolower($match[1]) == "r")? 1 : 3;
					$subject = trim(preg_replace($_reg, '', $subject, 1));
				}
				$_reg = '/\b(r|l)@/i'; // compat for old type
				if (preg_match($_reg, $subject, $match)) {
					$rotate = (strtolower($match[1]) == "r")? 1 : 3;
					$subject = trim(preg_replace($_reg, '', $subject, 1));
				}

				// @new �����ڡ������ꥳ�ޥ�ɸ���
				$_reg = '/@new\b/i';
				if (preg_match($_reg, $subject)) {
					$this->post_options['new'] = true;
					$subject = trim(preg_replace($_reg, '', $subject, 1));
				}

				// @p\d+ �оݥڡ�������(����x�ڡ���)���ޥ�ɸ���
				$_reg = '/@p(\d+)/i';
				if (preg_match($_reg, $subject, $match)) {
					$this->post_options['page_past'] = $match[1];
					$subject = trim(preg_replace($_reg, '', $subject));
				}

				// �ޥå׺������ޥ�ɸ���
				$_reg = '/@map\b/i';
				if (preg_match($_reg, $subject, $match)) {
					$this->post_options['makemap'] = true;
					$subject = trim(preg_replace($_reg, '', $subject));
				}

				// ���������
				$_reg = '/#([^#]*)/';
				if (preg_match($_reg, $subject, $match)) {
					$_tag = trim($match[1]);
					if ($_tag) {
						$this->post_options['tag'] = $_tag;
					}
					$subject = trim(preg_replace($_reg, '', $subject, 1));
				}

				// ̤�������𥫥å�
				if ($write && $deny_title){
					if (preg_match($deny_title,$subject)) {
						$write = false;
						$this->debug[] = 'Bad title.';
					}
				}
			}

			$today = getdate($now);
			$date = sprintf("/%04d-%02d-%02d-0",$today['year'],$today['mon'],$today['mday']);

			// ���ݥ��ɥ쥹
			if ($write){
				for ($f=0; $f<count($deny); $f++) {
					if (strpos($from, $deny[$f]) !== false) {
						$write = false;
						$this->debug[] = 'Bad from addr.';
					}
				}
			}

			// ��Ͽ�оݥڡ���������
			if ($write) {
				$uid = 0;
				if (!empty($adr2page[$to])) {
					if (! $page) $page = (is_array($adr2page[$to]))? $adr2page[$to][0] : $adr2page[$to];
					if (is_array($adr2page[$to])) {
						$uid = $adr2page[$to][1];
						if (!empty($adr2page[$to][2])) {
							$attach_only = true;
							$this->post_options['directpage'] = 1;
							if (!empty($adr2page[$to][3])) {
								$this->root->vars['refid'] = $adr2page[$to][3];
							}
						}
					}
				} else if (!empty($adr2page[$from])) {
					if (! $page) $page = (is_array($adr2page[$from]))? $adr2page[$from][0] : $adr2page[$from];
					if (is_array($adr2page[$from])) {
						$uid = $adr2page[$from][1];
					}
				} else {
					if (! $page) $page = (is_array($adr2page['other']))? $adr2page['other'][0] : $adr2page['other'];
				}
				$uid = intval($uid);

				// userinfo ������
				$this->func->set_userinfo($uid);
				$this->root->userinfo['ucd'] = '';
				$this->root->cookie['name']  = '';

				// pginfo �Υ���å���򥯥ꥢ
				$this->func->get_pginfo($page, '', TRUE);

				if ($page) $page = $this->get_pagename($page, $uid, $today);
				if ($page) {
					if (! $this->func->is_pagename($page)) {
						$write = false;
						$this->debug[] = '"' . $page . '" is not the WikiName.';
					} else {
						if (! $attach_only) {
							$this->user_pref = $this->func->get_user_pref($uid);
							if (! empty($this->user_pref['moblog_auth_code'])) {
								if ($this->user_pref['moblog_auth_code'] != $this->post_options['auth_code']) {
									$write = false;
									$this->debug[] = 'User auth key dose not mutch.';
								}
							}
						}
					}
				} else {
					$write = false;
					$this->debug[] = 'Allow page not found.' . $page;
				}

			}

			if ($write) {
				// �ޥ���ѡ��Ȥʤ�ХХ�������ʬ��
				if (preg_match("#^Content-type:.*multipart/#im",$head)) {
					$boureg = array();
					preg_match('#boundary="([^"]+)"#i', $head, $boureg);
					$body = str_replace($boureg[1], urlencode($boureg[1]), $body);
					$part = split("\r\n--".urlencode($boureg[1])."-?-?",$body);
					$boureg2 = array();
					if (preg_match('#boundary="([^"]+)"#i', $body, $boureg2)) {//multipart/altanative
						$body = str_replace($boureg2[1], urlencode($boureg2[1]), $body);
						$body = preg_replace("#\r\n--".urlencode($boureg[1])."-?-?\r\n#i","",$body);
						$part = split("\r\n--".urlencode($boureg2[1])."-?-?",$body);
					}
				} else {
					$part[0] = $dat;// ���̤Υƥ����ȥ᡼��
				}

				foreach ($part as $multi) {
					if (! $write) break;
					@ list($m_head, $m_body) = $this->plugin_moblog_mime_split($multi);
					if (!$m_body) continue;
					$filename = '';
					$m_body = preg_replace("/\r\n\.\r\n$/", "", $m_body);

					if (! preg_match("#^Content-type:(.+)$#im", $m_head, $match)) continue;

					$match = trim($match[1]);
					list($type, $charset) = array_pad(explode(';', $match), 2, '');
					if ($charset) {
						$charset = trim($charset);
						if (preg_match('/^charset=(.+)$/i', $charset)) {
							$charset = substr($charset, 8);
						} else {
							$charset = '';
						}
					}
					list($main, $sub) = explode('/', trim($type));
					$sub = strtolower($sub);

					// ��ʸ��ǥ�����
					if (strtolower($main) === 'text') {
						if (! empty($body_text['plain']) && $sub === 'html') {
							continue;
						}

						// ����饯�������åȤΥ����å�
						if ($charset) {
							if ($deny_lang){
								if (preg_match($deny_lang,$charset)) {
									$write = false;
									$this->debug[] = 'Bad charset.';
									break;
								}
							}
						} else {
							$charset = 'AUTO';
						}

						if (preg_match("#^Content-Transfer-Encoding:.*base64#im", $m_head))
							$m_body = base64_decode($m_body);
						if (preg_match("#^Content-Transfer-Encoding:.*quoted-printable#im", $m_head))
							$m_body = quoted_printable_decode($m_body);

						if (HypCommonFunc::get_version() >= '20081215') {
							if (! isset($mpc)) {
								if (! XC_CLASS_EXISTS('MobilePictogramConverter')) {
									HypCommonFunc::loadClass('MobilePictogramConverter');
								}
								$mpc =& MobilePictogramConverter::factory_common();
							}
							$m_body = $mpc->mail2ModKtai($m_body, $from, $charset);
						}

						$text = trim(mb_convert_encoding($m_body, $this->cont['SOURCE_ENCODING'], $charset));

						// ����ʸ������
						$text = str_replace(array("\r\n", "\r"), array("\n", "\n"), $text);

						if ($sub === 'html') {
							$text = str_replace("\n", '', $text);
							$text = preg_replace('#<br([^>]+)?>#i', "\n", $text);
							$text = preg_replace('#</?(?:p|tr|table|div)([^>]+)?>#i', "\n\n", $text);
							$text = strip_tags($text);
						}

						// ����3Ϣ³�ʾ�� #clear ���ִ�
						$text = preg_replace("/\n{3,}/", "\n#clear\n", $text);

						if ($write) {
							// �����ֹ���
							//$text = preg_replace("#([[:digit:]]{11})|([[:digit:]\-]{13})#", "", $text);
							// �������
							$text = preg_replace('#'.$del_ereg.'#', '', $text);
							// mac���
							$text = preg_replace("#Content-type: multipart/appledouble;[[:space:]]boundary=(.*)#","",$text);
							// ���������
							if (is_array($word)) {
								foreach ($word as $delstr) {
									$text = str_replace($delstr, "", $text);
								}
							}
							if (strlen($text) > $body_limit) $text = substr($text, 0, $body_limit)."...";
						}
						// ISBN, ASIN �Ѵ�
						if (! empty($this->config['isbn'])) {
							$isbn = $this->config['isbn'];
							$text = preg_replace_callback('/^([A-Za-z0-9]{10}|\d{13})$/m', function($m){ return str_replace('__ISBN__', $m[1], $isbn); }, $text);
						}

						// �������@amazon �Ѵ�
						if (! empty($this->config['amazon'])) {
							$amazon = $this->config['amazon'];
							$text = preg_replace_callback('/^(.+)@amazon$/mi', function($m){ return str_replace('__KEYWORD__', $m[1], $amazon); }, $text);
						}

						$body_text[$sub][] = trim($text);
					} else {
						// �ե�����̾�����
						$filereg = array();
						if (preg_match("#name=\"?([^\"\n]+)\"?#i",$m_head, $filereg)) {
							$filename = trim($filereg[1]);
							$filename = $this->mime_decode($filename);
						}
						// ź�եǡ�����ǥ����ɤ�����¸
						if (preg_match("#^Content-Transfer-Encoding:.*base64#im", $m_head) && preg_match('#'.$subtype.'#i', $sub)) {
							$tmp = base64_decode($m_body);

							//$save_file = $this->cont['CACHE_DIR'].$this->func->encode($filename).".tmp";

							if (strlen($tmp) < $maxbyte && $write && $attach)
							{

								$save_file = tempnam(rtrim($this->cont['UPLOAD_DIR'], '/'), 'moblog');
								chmod($save_file, 0606);
								if (file_put_contents($save_file, $tmp, LOCK_EX)) {
									//Exif geo
									$exifgeo = $this->getExifGeo($save_file);

									list($usec) = explode(' ', microtime());
									if (!$filename) $filename = $this->cont['UTC'].'_'.$usec.'.'.$sub;
									
									//��ž���� or ��ư��ž($rotate = 0)
									HypCommonFunc::rotateImage($save_file, $rotate);
									
									// GPS �������
									if ($_size && version_compare(HypCommonFunc::get_version(), '20150515', '>=')) {
										if (!empty($this->root->vars['rmgps'])) {
											HypCommonFunc::removeExifGps($save_file);
										}
									}
									
									// �ڡ�����̵����ж��ڡ��������
									if (!$this->func->is_page($page)) {
										$this->func->make_empty_page($page, false);
									}
									//$attach = $this->func->get_plugin_instance('attach');
									$pass = null;
									if (! $uid) {
										list($pass) = explode('@', $from);
									}
									$res = $attach->do_upload($page,$filename,$save_file,false,$pass,true);
									if ($res['result']) {
										$filenames[] =array('name' => $res['name'], 'exifgeo' => $exifgeo);
									} else {
										$this->debug[] = $res['msg'];
									}
								} else {
									$write = false;
									$this->debug[] = 'Can not make temp-file.';
								}
							} else {
								$write = false;
								$this->debug[] = 'Plugin attach was not found.';
							}
						}
					}
				}
				if ($imgonly && ! $filenames) {
					$write = false;
					$this->debug[] = 'Attach file was not found.';
				}

				$subject = trim($subject);
			}

			if (! empty($body_text['plain'])) {
				$text = join("\n\n", $body_text['plain']);
			} else if (! empty($body_text['html'])) {
				$text = join("\n\n", $body_text['html']);
			} else {
				$text = '';
			}

			// wiki�ڡ����񤭹���
			if ($write && !$attach_only) $this->plugin_moblog_page_write($page,$subject,$text,$filenames,$ref_option,$now);
		}
		// img�����ƤӽФ�
		$this->plugin_moblog_output();
	}

	function plugin_moblog_convert() {
		if (isset($this->config['host'])) $host = (string)$this->config['host'];
		if (isset($this->config['user'])) $user = (string)$this->config['user'];
		if (isset($this->config['pass'])) $pass = (string)$this->config['pass'];
		foreach(array('host', 'user', 'pass') as $key) {
			$_key = 'moblog_pop_' . $key;
			if (! empty($this->root->$_key)) {
				$$key = $this->root->$_key;
			}
		}
		if ($host && $user && $pass) {
			$data = array(
				'action' => 'plugin_func',
				'plugin' => 'moblog',
				'func'   => 'plugin_moblog_action'
			);
			$this->func->regist_jobstack($data, 0);
		}
		return '';
	}

	function plugin_moblog_page_write($page,$subject,$text,$filenames,$ref_option,$now) {

		$aids = $gids = $freeze = "";
		$date = "at ".date("g:i a", $now);

		if (! $this->is_newpage) {
			if ($subject) {
				$set_data = "** $subject\n";
			} else {
				$set_data = "----\n";
			}
		} else {
			$set_data = '';
		}

		// ź�եե������ #ref ��
		$attaches = array();
		if ($filenames) {
			foreach($filenames as $array) {

				$img = '#ref(' . $array['name'] . $ref_option . ")\n";
				if (strpos($text, '<img>') !== FALSE) {
					// ��ʸ���<img>��#ref���ִ�
					$text = preg_replace('/<img>/i', $img, $text, 1);
				} else {
					$attaches[] = $img;
				}
			}
		}
		if (strpos($text, '<img>') !== FALSE) {
			$text = preg_replace('/<img>/i', '', $text);
		}
		$attaches = join($this->config['attach_glue'], $attaches);

		// �ƥ�ץ졼��Ŭ��
		$replace_pairs = array(
			'__ATTACHES__' => $attaches,
			'__TEXT__'     => $text,
			'__DATE__'     => $date );
		$set_data .= strtr($this->config['template'], $replace_pairs) . "\n#clear";

		// ǰ�Τ���ڡ����������
		$set_data = $this->func->remove_pginfo($set_data);

		// ���Ԥ�Ĵ��
		$set_data = trim(preg_replace('/\n{3,}/', "\n\n", $set_data));

		// ����ʸ��Ĵ��
		$set_data = ltrim($set_data, "\r\n");
		$set_data = rtrim($set_data)."\n\n";

		if ($this->is_newpage) {
			//�ڡ�����������
			if ($this->root->auto_template_rules) {
				$auto_template_rules = array();
				foreach($this->root->auto_template_rules as $reg => $rules) {
					if (! $rules) continue;
					if (! is_array($rules)) {
						$rules = array($rules);
					}
					$_rules = array();
					foreach($rules as $rule) {
						$_rules[] = str_replace('template', 'template_m', $rule);
					}
					$auto_template_rules[$reg] = $_rules;
				}
			} else {
				$auto_template_rules = NULL;
			}

			$page_data = $this->func->auto_template($page, $auto_template_rules);

			if (strpos($page_data, '__TITLE__') !== false) {
				$page_data = str_replace('__TITLE__', $subject? $subject : 'notitle', $page_data);
			} else {
				if ($subject) $set_data = "* $subject\n" . $set_data;
			}
		} else {
			$page_data = rtrim(join('',$this->func->get_source($page)))."\n";
		}
		$page_data = $this->func->remove_pginfo($page_data);

		$this->make_googlemaps($page_data, $set_data, $subject, $date, $filenames);

		if (preg_match("/\/\/ Moblog Body\n/",$page_data)) {
			$page_data = preg_split("/\/\/ Moblog Body[ \t]*\n/",$page_data,2);
			$save_data = rtrim($page_data[0]) . "\n\n" . $set_data . "// Moblog Body\n" . $page_data[1];
		} else 	{
			$save_data = $page_data . "\n" . $set_data . "// Moblog Body\n";
		}

		if (! empty($this->post_options['tag'])) {
			$p_tag = $this->func->get_plugin_instance('tag');
			if (is_object($p_tag)) {
				$p_tag->set_tags($save_data, $page, $this->post_options['tag']);
			}
		}

		if ((! $this->is_newpage || ! $this->root->pagename_num2str) && $subject) {
			$this->root->rtf['esummary'] = $subject;
		}
		if ($this->user_pref['moblog_to_twitter']) {
			$this->root->rtf['twitter_update'] = '1';
		}

		// �ڡ�������
		$this->func->page_write($page, $save_data);
		$this->debug[] = $save_data;
		$this->debug[] = 'Page write ' . $page;

	}

	function get_sms_link($text = 'Send by MMS', $page = null, $refid = '', $uid = null) {
		if (strpos($this->root->moblog_pop_mail, '*') === false) return '';
		if (is_null($page)) {
			$page = $this->root->vars['page'];
		}
		if (is_null($uid)) {
			$uid = $this->root->userinfo['uid'];
		}
		$key = md5(date("Ymd").'&'.$page.'&'.$uid.'&'.$refid);
		if ($cache = $this->func->cache_get_db($key, 'moblog')) {
			list($mail) = array_keys(unserialize($cache));
		} else {
			$mail = str_replace('*', $key, $this->root->moblog_pop_mail);
			$data = array($mail => array($page, $uid, 1, $refid));
			$this->func->cache_save_db(serialize($data), 'moblog', 1800, $key);
		}
		return '<a href="sms:'.$mail.'"><span class="button">'.$text.'</span></a>';
	}

	function make_googlemaps ($pagedata, & $set_data, $subject, $date, $filenames) {
		if (! empty($this->config['nomap'])) return;

		$match = false;
		if (preg_match('/pos=N([0-9.]+)E([0-9.]+)[^\s]+(.*)$/mi', $set_data, $prm)) {
			$match = true;
			$repreg = '/^(.+pos=N[0-9.]+E[0-9.]+[^\s]+).*$/mi';
		} else if (preg_match('/lat=%2B([0-9.]+)&lon=%2B([0-9.]+)[^\s]+(.*)$/mi', $set_data, $prm)) {
			$match = true;
			$repreg = '/^(.+lat=%2B[0-9.]+&lon=%2B[0-9.]+[^\s]+).*$/mi';
		} else if (preg_match('/loc:([0-9.-]+),([0-9.-]+).*$/mi', $set_data, $prm)) {
			$match = true;
			$repreg = '/^(.+)$/mi';
		}
		$points = array();
		if ($match) {
			$this->post_options['makemap'] = true;
			$lats = explode('.', $prm[1]);
			if (count($lats) === 2) {
				$lat = $prm[1];
				$lng = $prm[2];
			} else {
				$lngs = explode('.', $prm[2]);
				$lats = array_pad($lats, 4, 0);
				$lngs = array_pad($lngs, 4, 0);
				$lat = $lats[0] + ($lats[1] / 60 + ((float)($lats[2] . '.' . $lats[3]) / 3600));
				$lng = $lngs[0] + ($lngs[1] / 60 + ((float)($lngs[2] . '.' . $lngs[3]) / 3600));
			}

			$title = (! empty($prm[3]))? trim($prm[3]) : '';

			$points[] = array(
				'title' => $title,
				'Lat' => $lat,
				'Lon' => $lng,
				'date' =>$date,
				'repreg' => $repreg,
				'image' => ''
			);
		}

		if ($filenames) {
			foreach($filenames as $_file) {
				if ($_file['exifgeo']) {
					$_file['name'];
					$_file['exifgeo']['image'] = $_file['name'];
					$_file['exifgeo']['date'] = "at ".date("g:i a", strtotime($_file['exifgeo']['Date']));
					$points[] = $_file['exifgeo'];
				}
			}
		}

		if ($points) {
			$mapfound = (preg_match('/^#gmap\(/m', $pagedata));
			foreach($points as $point) {
				$date = '';
				if ($point['title']) {
					$title = $point['title'];
					$date = '( '.$point['date'].' )';
				} else {
					$title = $point['date'];
				}
				$map = '';
				if (! empty($this->post_options['makemap']) && ! $mapfound) {
					$map = "\n#clear\n" . '#gmap(lat=' . $point['Lat'] . ',lng=' . $point['Lon'] . $this->config['gmap'] . ')' . "\n";
					$mapfound = true;
				}
				if ($mapfound) {
					$image = ($point['image'])? ',"image='.$point['image'].'"' : '';
					$marker = "\n" . '-&gmap_mark(' . $point['Lat'] . ',' . $point['Lon'] . $image . ',"title=Point: ' . $title . '"){' . ($subject? $subject . '&br;' : '') . $date . '};' . "\n";
					if ($point['repreg']) {
						$set_data = preg_replace($point['repreg'], $map . '$1' . $marker, $set_data);
					} else {
						$set_data .= $map . $marker;
					}
				}
			}
		}
	}

	function get_pagename($base, $uid, $today) {
		$page = '';

		if (empty($this->post_options['directpage'])) {
			$date = sprintf('/%04d-%02d-%02d',$today['year'],$today['mon'],$today['mday']);
			$_page = $base . $date;
		} else {
			$_page = $base;
		}

		if (! $this->func->is_pagename($_page)) {
			$this->debug[] = '"' . $_page . '" is not the WikiName.';
			return '';
		}

		if (empty($this->post_options['directpage'])) {
			$list = array();
			if (empty($this->post_options['new'])) {
				// uid �����פ���ڡ��������
				$options = array(
					'where' => '`uid`=\'' . $uid . '\'',
					'nochild' => true
				);
				$list = $this->func->get_existpages(FALSE, $_page, $options);
				if ($list) {
					// ��������˥�����
					natsort($list);
					$list = array_reverse($list);
				}
			}
			if ($list) {
				$count = 0;
				$check_tmp = '';
				$page_past = (! empty($this->post_options['page_past']))? $this->post_options['page_past'] : 0;
				foreach($list as $check) {
					$source = $this->func->get_source($check, true, true);
					if (preg_match('#^// Moblog Body#m', $source)) {
						$check_tmp = $check;
						if ($page_past == $count++) {
							$page = $check;
							break;
						}
					}
				}
				if (! $page && $check_tmp) {
					$page = $check_tmp;
				}
			}
		} else {
			// �ڡ���̾�����쥯�Ȼ���⡼��
			if ($this->func->check_editable_page($_page, false, false, $uid)) {
				return $_page;
			} else {
				return '';
			}
		}

		if (! $page) {
			$page = $this->check_page($_page, $uid);
			if (! $page) {
				$i = 1;
				while(! $page) {
					$_page = $base . $date . '-' . $i++;
					$page = $this->check_page($_page, $uid);
				}
			}
		}
		if ($page === true) {
			// �ڡ����Խ����¤��ʤ�
			$page = '';
		}
		return $page;
	}

	function check_page($_page, $uid) {
		$page = '';
		if (! $this->func->is_page($_page, TRUE)) {
			if ($this->func->check_editable_page($_page, false, false, $uid)) {
				$page = $_page;
				$this->is_newpage = 1;
			} else {
				$page = true;
			}
		}
		return $page;
	}

	// ���ޥ������
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

	// �إå�����ʸ��ʬ�䤹��
	function plugin_moblog_mime_split($data) {
		// ���ԥ�����������
		$data = preg_replace("/(\x0D\x0A|\x0D|\x0A)/","\r\n",$data);
		$part = explode("\r\n\r\n", $data, 2);
		$part[0] = preg_replace("/\r\n[\t ]+/", " ", $part[0]);
		return $part;
	}

	// �᡼�륢�ɥ쥹����Ф���
	function plugin_moblog_addr_search($addr) {
		if (preg_match('/<(.+?)>/', $addr, $match)) {
			return $match[1];
		} else {
			return $addr;
		}
	}

	function mime_decode($str, $mpc = null, $from_addr = null) {
		// ���󥳡���ʸ���֤ζ������
		$str = preg_replace('/\?=[\s]+?=\?/', '?==?', $str);

		$regs = array();
		$_charset = 'AUTO';
		while (preg_match('#(.*?)=\?([^\?]+?)\?([BQ])\?([^\?]+?)\?=(.*?)#',$str,$regs)) {//MIME B, Q
			$_charset = $regs[2];
			if ($regs[3] === 'B') {
				$p_subject = base64_decode($regs[4]);
			} else {
				$p_subject = quoted_printable_decode($regs[4]);
			}
			if ($from_addr && is_object($mpc)) {
				$p_subject = $mpc->mail2ModKtai($p_subject, $from_addr, $_charset);
			}
			$str = $regs[1].$p_subject.$regs[5];
		}
		$str = trim(mb_convert_encoding($str, $this->cont['SOURCE_ENCODING'], $_charset));
		return $str;
	}

	function getExifGeo($file){

		if (! extension_loaded('exif')) return false;

		$exif = @ exif_read_data($file, 'GPS');
		if (!$exif) return false;

		$Lat = @ $exif['GPSLatitude'];
		$Lon = @ $exif['GPSLongitude'];
		$LatRef = @ $exif['GPSLatitudeRef'];
		$LonRef = @ $exif['GPSLongitudeRef'];
		if (!$Lat || !$Lon || !$LatRef || !$LonRef) return false;

		// replace N,E,W,S to '' or '-'
		$prefix = array( 'N' => '', 'S' => '-', 'E' => '', 'W' => '-' );

		$result = array();
		if (is_array($Lat)){
			foreach($Lat as $v){
				if (strstr($v, '/')){
					$x = explode('/', $v);
					$result['Lat'][] = $x[0] / $x[1];
				}
			}
			$result['Lat'] = $result['Lat'][0] + ($result['Lat'][1]/60) + ($result['Lat'][2]/(60*60));
		} else {
			$result['Lat'] = $Lat;
		}
		if (is_array($Lon)){
			foreach($Lon as $v){
				if (strstr($v, '/')){
					$x = explode('/', $v);
					$result['Lon'][] = $x[0] / $x[1];
				}
			}
			$result['Lon'] = $result['Lon'][0] + ($result['Lon'][1]/60) + ($result['Lon'][2]/(60*60));
		} else {
			$result['Lon'] = $Lon;
		}

		if (!$result['Lat'] && !$result['Lon']) return false;

		// TOKYO to WGS84
		if (stristr($exif['GPSMapDatum'], 'tokyo')){
			$result['Lat'] = $result['Lon'] - $result['Lon'] * 0.00010695  + $result['Lat'] * 0.000017464 + 0.0046017;
			$result['Lon'] = $result['Lat'] - $result['Lon'] * 0.000046038 - $result['Lat'] * 0.000083043 + 0.010040;
		}

		$result['Lat'] = (float)($prefix[$LatRef] . $result['Lat']);
		$result['Lon'] = (float)($prefix[$LonRef] . $result['Lon']);
		$result['Date'] = @ $exif['DateTimeOriginal'];

		return $result;
	}

	// ���顼����
	function plugin_moblog_error_output($str) {
		$this->func->clear_output_buffer();
		if ($this->admin) {
			echo 'error: ' . $str;
		} else {
			header("Content-Type: image/gif");
			HypCommonFunc::readfile($this->root->mytrustdirpath . '/skin/image/gif/poperror.gif');
		}
		exit();
	}

	// ���᡼������
	function plugin_moblog_output () {
		if ($this->chk_fp) {
			flock($this->chk_fp, LOCK_UN);
			fclose($this->chk_fp);
		}
		$this->debug_write();
		if ($this->output_mode === 'rss') {
			$rss = $this->func->get_plugin_instance('rss');
			$rss->plugin_rss_action();
			exit();
		}
		// clear output buffer
		$this->func->clear_output_buffer();
		if (isset($this->root->get['debug']) && $this->admin) {
			echo 'Debug:<br />' . join('<br />', $this->debug);
		} else {
			// img�����ƤӽФ���
			header("Content-Type: image/gif");
			HypCommonFunc::readfile($this->root->mytrustdirpath . '/skin/image/gif/spacer.gif');
		}
		exit();
	}

	function debug_write($str, $line = '') {
		if ($this->debug) {
			file_put_contents($this->cont['CACHE_DIR'].'moblog_debug.txt', join("\n", $this->debug));
		}
	}
}
?>