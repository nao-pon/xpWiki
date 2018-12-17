<?php
// plugin converter for xpwiki

$funcname_reg = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(?=[ \t]*\()/';

$func_reg = '/(?:(?:(?:\/\/|#|<\?php).*?(?:\r\n|\r|\n)|\r\n|\r|\n))*(?:function[ \t&]*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\s*\(.*?\)\s*)*\{((?:(?>[^{}]+)|(?R))*)\}\s+(?:(?:(?:\/\/|#|\?>).*?(?:\r\n|\r|\n)|\r\n|\r|\n))*/is';

$keys_reg = '/^(and|or|xor|exception|php_user_filter|array|as|break|case|class|const|continue|declare|default|die|do|echo|else|elseif|empty|enddeclare|endfor|endforeach|endif|endswitch|endwhile|eval|exit|extends|for|foreach|function|global|if|include|include_once|isset|list|new|print|require|require_once|return|static|switch|unset|use|var|while|final|php_user_filter|interface|implements|extends|public|private|protected|abstract|clone|try|catch|throw|cfunction|old_function|this)$/i';

$over_funcs = array('htmlspecialchars');
$over_funcs = array_flip($over_funcs);

$defines = $func_all = $global_all = array();
$other_all = $output_all = "";

$const_init = <<<EOD

		\$this->xpwiki =& \$xpwiki;
		\$this->root   =& \$xpwiki->root;
		\$this->cont   =& \$xpwiki->cont;
		\$this->func   =& \$xpwiki->func;

EOD;

/*
$func_init = <<<EOD

		//\$root  =& \$this->root;
		//\$const =& \$this->cont;
		//\$func  =& \$this->func;

EOD;
*/
$func_init = "";

$files = array();
$indir = "$mydirpath/private/cache/in/";
$outdir = "$mydirpath/private/cache/out/";

$cachedir = "$mydirpath/private/cache/";
$isupload = 0;

$plugin = preg_replace('/[^a-zA-Z0-9_-]/', '', @$_POST['plugin']);
$initonly = (! empty($_POST['initonly']));

if (!empty($_FILES['userfile']['name'])) {
	$files[] = basename($_FILES['userfile']['name']);
	$isupload = 1;
} else {
	if ($handle = @ opendir($indir)) {
		while (false !== ($file = readdir($handle))) {
			if (!is_dir($indir.$file)) {
				$files[] = $file;
			}
		}
		closedir($handle);
	}
}

if (!$files && !$plugin) {

	$plugins = array();
	if ($dh = opendir(XOOPS_TRUST_PATH . '/modules/xpwiki/plugin/')) {
		while (($file = readdir($dh)) !== false) {
			if (preg_match('/^([a-zA-Z0-9_-]+)\.inc\.php$/', $file, $match)) {
				$plugins[] = $match[1];
			}
		}
		closedir($dh);
	}

	sort($plugins);

	$select = '<select name="plugin"><option value="">Select plugin</option>';
	foreach ($plugins as $plugin) {
		$select .= '<option value="'.$plugin.'">'.$plugin.'</option>';
	}
	$select .= '</select>';

	echo <<<EOD
<h1>Convert the xpWiki Plugin from "trust" to "html"</h1>
<form action="index.php?page=plugin_conv&amp;mode=s2u" method="POST">
    Select xpWiki plugin: {$select}
    <p>
    <input type="checkbox" name="initonly" value="on" checked="checked" /> plugin_<i>xxx</i>_init() Only.<br />
    &nbsp; &nbsp;<input type="checkbox" name="withparent" value="on" /> With "parent::plugin_<i>xxx</i>_init();".
    </p>
    <input type="submit" value="Do convert & Download!" />
	Click &amp; Wait...
</form>
<p>
<hr />
</p>
<h1>Convert a plugin from PukiWiki 1.4 to xpWiki</h1>
<form enctype="multipart/form-data" action="index.php?page=plugin_conv" method="POST">
    PukiWiki 1.4 plugin file:<br /><input name="userfile" type="file" size="60" /><br />
    <input type="submit" value="Do convert & Download!" onClick="this.style.visibility='hidden';return true;" />
	Click &amp; Wait...
</form>
<hr />
EOD;
	return;
}

error_reporting(E_ALL ^ E_NOTICE);

$mode = (empty($_GET['mode']))? "" : $_GET['mode'];
if ($mode == "s2u" && $plugin) {
	convert_s2u ($plugin, $mydirname, $initonly);
	exit;
}

foreach($files as $input) {
	@ set_time_limit(60);
	$output = $outdir . $input;
	//echo $output;
	$output_other = $outdir ."other_{$input}";

	if (file_exists($indir . $input)) {
		$org_file = $indir . $input;
	} else {
		$org_file = $_FILES['userfile']['tmp_name'];
	}

	$dat = file($org_file);

	// �ץ饰����̾
	$plugin_name = str_replace(".inc.php","",$input);

	// ���饹���ʸ
	$class_start_code = "class xpwiki_plugin_{$plugin_name} extends xpwiki_plugin {\n";

	// ���ʴؿ�̾�μ���
	preg_match_all("/^\s*function\s+([_0-9a-zA-Z]+)/im",join('',$dat),$match);
	$my_funcs = $match[1];
	//echo join("<br>",$my_funcs);
	//echo "<hr>";

	// init�ؿ��Υ����å�
	$has_init = ((array_search("plugin_{$plugin_name}_init",$my_funcs)) === FALSE)? FALSE : TRUE;
	$init_code = "";

	//echo ($has_init? "found 'plugin_{$plugin_name}_init'" : "not found 'plugin_{$plugin_name}_init'")."<hr>";

	$i = 0;
	$out = '';
	$out_other = '';
	$st_class = $st_func = 0;
	$nest = 0;
	$statics = $globals = array();
	$count = 0;
	$block_comment = 0;
	$here = "";
	$cache = "";
	$line_cache = "";
	$class_cnt = 0;
	$class_out = array();
	$now_class_name = $now_func_name = "";
	$rename_classes = array();

	$need_xpwiki_classes = array();
	$noprc = 0;

	foreach($dat as $line) {
		$line = str_replace(array("\r\n","\r"),"\n",$line);
		$count++;
		if (preg_match("#^[ \t]*/\*#",$line)) {$block_comment = 1;}
		if (preg_match("#\*/[ \t]*$#",$line)) {$block_comment = 0;}

		if ($line_cache || $here || (!$block_comment && !preg_match("/(^([ \t]*(\/\/|#|\n|\r))|<\?php|\?>)/",$line))) {

			// Ϣ³��Ƚ��
			if (preg_match("/[,\.\"']\s*$/",$line)) {
				$line_cache .= $line;
				continue;
			}

			$line = $line_cache . $line;
			$line_cache = "";
			//echo nl2br(htmlspecialchars($line))."<hr>";

			$noprc = 0;

			// ���饹����Ƚ��
			if (preg_match("/^\s*class\s+([_0-9a-zA-Z]+)/i",$line,$match)) {
				$st_class = 1;
				$nest = -1;
				$class_out[++$class_cnt] = "";
				$now_class_name = $match[1];
				//echo $now_class_name."<br>";
				if (!preg_match("/^XpWiki/i",$now_class_name)) {
					$rename_classes[] = $now_class_name;
				}
			}

			// �ؿ�����Ƚ��
			if ($nest === 0) {
				if (!$st_func && preg_match("/^\s*function\s+([_0-9a-zA-Z]+)/i",$line,$match)) {
					$st_func = 1;
					$noprc = 1;
					$now_func_name = $match[1];
					$statics = $globals = array();
					//���饹��ǥ��󥹥ȥ饯����
					if ($st_class && $now_class_name == $now_func_name) {
						//���饹̾�ѹ����б������ơ�xpwiki���֥������Ȱ������ɲ�
						if (preg_match("/(^\s*function\s+)([_0-9a-zA-Z]+)(\s*\(\s*\))/i",$line)) {
							$line = preg_replace("/(^\s*function\s+)([_0-9a-zA-Z]+)(\s*\()/i","$1XpWiki$2$3& \$xpwiki",$line);
						} else {
							$line = preg_replace("/(^\s*function\s+)([_0-9a-zA-Z]+)(\s*\()/i","$1XpWiki$2$3& \$xpwiki, ",$line);
						}
						$need_xpwiki_classes[] = $now_class_name;
					}
				}
			}

			//echo htmlspecialchars($line)."<br>";
			$_line = preg_replace("/(\".*?\"|'.*?'|(\/\/|#).*$)/s","",trim($line));
			//echo htmlspecialchars($_line)."<hr>";

			$_nest = count(explode("{",$_line))-1;
			if ($_nest) {
				$nest += $_nest;
				// �ؿ������ʸ������
				if ($st_func && $nest === 1) {
					$_ins = ($now_class_name == $now_func_name)? $const_init : $func_init;
					$line = preg_replace("/\{/","{".$_ins,$line,1);
					if ($now_func_name == "plugin_{$plugin_name}_init") {
						$line .= "/*****_OTHER_INSERT_*****/";
					}
				}
			}

			//echo htmlspecialchars($_line)."<br>";
			//echo "$st_class:$st_func:$nest:$noprc<hr>";

			// define �񤭴���
			$line = preg_replace("/defined\('([_0-9a-zA-Z]+)'\)/i","isset(\$this->cont['$1'])",$line);
			if (preg_match("/define\s*\(\s*(?:[\"'])(.+?)(?:[\"'])\s*,\s*(.+?)\s*\)\s*;/is",$line,$match)) {
				$defines[$match[1]] = $match[2];
				$line = preg_replace("/define\s*\(\s*(?:[\"'])(.+?)(?:[\"'])\s*,(\s*.+?\s*)\)(\s*;)/is","\$this->cont['$1'] = $2$3",$line);
			}
			// $GLOBALS ��񤭴���
			$line = preg_replace("/\\\$GLOBALS\[(\"|')?([^\]\\1]+?)\\1?\]/i","\$this->root->$2",$line);

			if ($nest > 0 && $st_func && !$noprc) {

				// global�ѿ��񤭴���
				if (preg_match("/(?:^|\s*)global(.+);/s",$line,$match)) {
					$_globals = array_unique(explode(",",preg_replace("/\s/","",$match[1])));
					$globals = array_merge($globals,$_globals);
					$line = "//".$line;
				} else {
					// '' ��򥨥�������
					$line = preg_replace_callback("/'.*?'/s",function($m){ return _for_quote_replace($m[0],'\$','in'); },$line);
					$line = preg_replace_callback("/'.*?'/s",function($m){ return _for_quote_replace2($m[0],'\"','in'); },$line);
					foreach ($globals as $global) {
						//echo "[{$global}]<br>";
						// "" ��
						//$line = preg_replace('/("[^\']*?)\{?'.preg_quote($global,"/").'((?:\[[^\]]+\])*)(?![a-zA-Z0-9_\x7f-\xff])\}?([^\']*?")/i',"$1{\$this->root->".substr($global,1)."$2}$3",$line);
						$line = preg_replace_callback('/(?<!\\\\)(".*?(?<!\\\\)")/i',function($m) use($global) { return _global_replace($global,$m[0]); },$line);

						// ����¾
						//�ҥ��ɥ��������
						if ($here) {
							$line = preg_replace("/\{?".preg_quote($global,"/")."((?:\[[^\]]+\])*)(?![a-zA-Z0-9_\x7f-\xff])\}?/",'{$this->root->'.substr($global,1)."$1}",$line);
						} else {
							$line = preg_replace("/".preg_quote($global,"/")."(?![a-zA-Z0-9_\x7f-\xff])/",'$this->root->'.substr($global,1),$line);
						}
					}
					// '' ��򥨥������ײ��
					$line = preg_replace_callback("/'.*?'/s",function($m){ return _for_quote_replace2($m[0],'\"','out'); },$line);
					$line = preg_replace_callback("/'.*?'/s",function($m){ return _for_quote_replace($m[0],'\$','out'); },$line);
				}

				// static �ѿ��񤭴���
				if (preg_match("/(?:^|\s*)static(.+);/s",$line,$match)) {
					//echo $match[1]."<hr>";
					//���������Υ���ޤ򥨥�������
					$match[1] = preg_replace_callback("/('|\").*?\\1/",function($m){ return _for_quote_replace($m[0],',','in,$m[1]'); },$match[1]);
					$match[1] = preg_replace_callback("/array\((.+?)\)/i", function($m){ return 'array('.str_replace(',','\x08',$m[1]).')'; },$match[1]);

					$_tmp = array_unique(explode(",",preg_replace("/\s+/","",$match[1])));
					$pears = array();
					foreach ($_tmp as $_pear) {
						// ��������򥨥������ײ��
						//$_pear = preg_replace("/('|\").*?\\1/e","_for_quote_replace('$0',',','out','$1')",$_pear);
						$_pear = str_replace("\x08",",",$_pear);
						list($_key,$val) = array_pad(explode("=",$_pear),2,"array()");
						$pears[trim($_key)] = "$val";
					}
					$statics  = array_merge($statics ,array_keys($pears));
					$_pre = "\t".preg_replace("/static.*$/s","",$line);
					$line = "//".$line;
					foreach ($pears as $_key => $_val) {
						$line .= "{$_pre}static {$_key} = array();\n";
						$line .= "{$_pre}if (!isset({$_key}[\$this->xpwiki->pid])) {{$_key}[\$this->xpwiki->pid] = {$_val};}\n";
					}
				} else {
					foreach ($statics as $static) {
						//echo "[{$global}]<br>";
						// "" ��
						$_tmp = $line;
						$line = preg_replace('/((?:"[^"]*(?!'.$static.')[^"]*"[^"]*)?"[^\']*?)\{?'.preg_quote($static,"/").'((?:\[[^\]]+\])*)(?![_0-9a-zA-Z])\}?([^\']*?")/i',"$1{".$static."[\$this->xpwiki->pid]$2}$3",$line);
						// ����¾
						//�ҥ��ɥ��������
						if ($here) {
							$line = preg_replace("/\{?".preg_quote($static,"/")."(?!".preg_quote("[\$this->xpwiki->pid]").")((?:\[[^\]]+\])*)(?![_0-9a-zA-Z])\}?/",'{$'.$static."[\$this->xpwiki->pid]$1}",$line);
						} else {
							$line = preg_replace("/".preg_quote($static,"/")."(?!".preg_quote("[\$this->xpwiki->pid]").")(?![_0-9a-zA-Z])/",$static."[\$this->xpwiki->pid]",$line);
						}
					}
				}

				//�ؿ�̾�񤭴���
				//echo htmlspecialchars($_line)."<br>";
				if (!$here) {
					preg_match_all($funcname_reg,$_line,$match,PREG_PATTERN_ORDER);
					$funcs = array_unique($match[0]);
					foreach ($funcs as $func_name) {
						if (isset($over_funcs[strtolower($func_name)]) || (!function_exists($func_name) && !preg_match($keys_reg,$func_name))) {
							$line_old = $line;
							// ���ʴؿ���
							if (array_search($func_name,$my_funcs) !== FALSE) {
								$prefix = ($st_class)? "xpwiki_plugin_{$plugin_name}::" : '$this->';
							} else {
								$prefix = '$this->func->';
							}
							$line = preg_replace("/(?<!\->|new |::|\\\$)(".preg_quote($func_name,"/").")([ \t]*\()/i", "$prefix$1$2", $line);
							if ($line_old != $line) {
								$func_all[] = $func_name;
							}
						}
					}
				}

				//call_user_func �ν񤭴���
				if (preg_match("/(call_user_func(?:_array)?)\s*\(\s*[\",'](plugin_([_0-9a-zA-Z]+)_[a-z0-9]+)[\"|']/i",$line,$match)) {
					if ($plugin_name == $match[3]) {
						$line = preg_replace("/(call_user_func(?:_array)?)\s*\(\s*[\",'](plugin_([_0-9a-zA-Z]+)_[a-z0-9]+)[\"|']/i"
							,"$1 (array(& \$this, \"$2\")", $line);
					} else {
						$line = preg_replace("/(call_user_func(?:_array)?)\s*\(\s*[\",'](plugin_([_0-9a-zA-Z]+)_[a-z0-9]+)[\"|']/i"
							,"$1 (array(& \$_plugin, \"$2\")", $line);
						$line = "\t\$_plugin =& \$this->func->get_plugin_instance(\"{$match[3]}\");\n\t".$line;
					}
				}

			} else {
				$global_all = array_merge($global_all,$globals);
				$globals = array();
			}

			if ($st_class) {
				$class_out[$class_cnt] .= $cache.$line;
			} else {
				if ($st_func) {
					$out .= $cache.($here? "" : "\t").$line;
				} else {
					$out_other .= $cache.($here? "" : "\t\t").$line;
				}
			}
			$cache = "";

			// class function ��λȽ��
			$_nest = count(explode("}",$_line))-1;
			$nest -= $_nest;
			if ($_nest && $nest === 0) {
				$st_func = 0;
			}
			if ($_nest && $nest === -1) {
				$st_class = 0;
				$nest = 0;
				$now_class_name = "";
			}

			// �ҥ��ɥ�����ȳ���Ƚ��
			if (!$here && preg_match("/^.+<<<\s*([_0-9a-zA-Z]+)/",$line,$match)) {
				$here = $match[1];
			}
			// �ҥ��ɥ�����Ƚ�λȽ��
			if ($here && preg_match("/^$here\s*;/",$line)) {
				$here = "";
			}
		} else {
			if (trim($line) == "<?php") {
				$line .= $class_start_code;
				$out .= $line;
			} else if ( trim($line) == "?>") {
				$line = "}\n/*****_CLASS_INSERT_*****/?>\n";
				$out .= $line;
			} else {
				$cache .= ($st_class? "":"\t").$line;
			}
		}
	}

	// init �ؿ��ؽ񤭹���
	if ($has_init) {
		$out = str_replace("/*****_OTHER_INSERT_*****/", $out_other, $out);
	} else {
		$init_code = <<<EOD
	function plugin_{$plugin_name}_init () {
$func_init

$out_other
	}

EOD;
		$out = str_replace($class_start_code, $class_start_code.$init_code, $out);
	}

	// class ��Ǹ�˽񤭲ä���
	$out = str_replace("/*****_CLASS_INSERT_*****/",join('',$class_out),$out);

	// ����ִ��ν���
	$consts = file($cachedir."consts.dat");
	$consts = array_map("trim", $consts);

	$consts = array_merge($consts, array_keys($defines));
	$consts = array_unique($consts);
	rsort($consts);

	$outs = preg_split("/(\r\n|\r|\n)/", $out);
	$out = "";
	foreach($outs as $line) {
		if (preg_match("#^\s*/\*#",$line)) {$block_comment = 1;}
		if (preg_match("#\*/\s*$#",$line)) {$block_comment = 0;}
		if (!$block_comment && !preg_match("/(^([ \t]*(\/\/|#|\n|\r))|<\?php|\?>)/",$line)) {
			foreach ($consts as $const) {
				// '' ��򥨥�������
				$key = $const[0];
				$line = preg_replace_callback("/'.*?'/s",function($m) use($key){ return _for_quote_replace($m[0],$key,'in'); },$line);

				$line = preg_replace("/(?<![_0-9a-zA-Z'\"])".$const."(?![_0-9a-zA-Z'\"])/","\$this->cont['$0']",$line);

				// '' ��򥨥������ײ��
				$line = preg_replace_callback("/'.*?'/s",function($m) use($key){ return _for_quote_replace($m[0],$key,'out'); },$line);
			}
		}
		$out .= $line."\n";
	}

	// ɬ�פ� new CLASS() CLASS::CLASS �ΰ����� xpwiki ���֥������Ȥ��ɲä������
	$need_classes = file($cachedir."need_classes.dat");
	$need_classes = array_map("trim", $need_classes);

	$need_classes = array_merge($need_classes, $need_xpwiki_classes);
	$need_classes = array_unique($need_classes);
	rsort($need_classes);

	$outs = preg_split("/(\r\n|\r|\n)/", $out);
	$out = "";
	foreach($outs as $line) {
		if (preg_match("#^\s*/\*#",$line)) {$block_comment = 1;}
		if (preg_match("#\*/\s*$#",$line)) {$block_comment = 0;}
		if (!$block_comment && !preg_match("/(^([ \t]*(\/\/|#|\n|\r))|<\?php|\?>)/",$line)) {
			foreach ($need_classes as $need_class) {
				if (preg_match("/(?:new\s+|(?:$need_class|parent)::)$need_class\s*\(\s*\)/i",$line)) {
					$line = preg_replace("/(?:new\s+|::)$need_class\s*\(/i","$0\$this->xpwiki",$line);
				} else {
					$line = preg_replace("/(?:new\s+|::)$need_class\s*\(/i","$0\$this->xpwiki, ",$line);
				}
			}
		}
		$out .= $line."\n";
	}

	// ���饹̾�ν񤭴���
	$_classes = file($cachedir."rename_classes.dat");
	$_classes = array_map("trim", $_classes);

	$rename_classes = array_merge($_classes, $rename_classes);
	$rename_classes = array_unique($rename_classes);
	rsort($rename_classes);

	if ($rename_classes) {
		$outs = preg_split("/(\r\n|\r|\n)/", $out);
		$out = "";
		foreach($outs as $line) {
			if (preg_match("#^\s*/\*#",$line)) {$block_comment = 1;}
			if (preg_match("#\*/\s*$#",$line)) {$block_comment = 0;}
			if (!$block_comment && !preg_match("/(^([ \t]*(\/\/|#|\n|\r))|<\?php|\?>)/",$line)) {
				foreach ($rename_classes as $_class) {
					//echo $_class."<hr>";
					$line = preg_replace("/(?<![_0-9a-zA-Z])((?:class|new|extends)\s+)(".$_class.")(?![_0-9a-zA-Z])/i","$1XpWiki$2",$line);
					$line = preg_replace("/(?<![_0-9a-zA-Z])($_class)::($_class)(?![_0-9a-zA-Z])/i","XpWiki$1::XpWiki$2",$line);
					$line = preg_replace("/(?<![_0-9a-zA-Z])parent::($_class)(?![_0-9a-zA-Z])/i","parent::XpWiki$1",$line);
					// ��������� �ѿ����������ƻȤäƤ�������б������Ѵ������뤫�⡩
					$line = preg_replace("/((\"|')[^\\2]*)(?<![_0-9a-zA-Z])(".$_class."(?![_0-9a-zA-Z])[^\\2]*\\2)/","$1XpWiki$3",$line);
				}
			}
			$out .= $line."\n";
		}
	}

	// �ե�������¸
	if ($fp = fopen($cachedir."consts.dat","wb")) {
		fwrite($fp, join("\n",$consts));
		fclose($fp);
	}

	if ($fp = fopen($cachedir."need_classes.dat","wb")) {
		fwrite($fp, join("\n",$need_classes));
		fclose($fp);
	}

	if ($fp = fopen($cachedir."rename_classes.dat","wb")) {
		fwrite($fp, join("\n",$rename_classes));
		fclose($fp);
	}

	$out = trim($out);

	// ���ե�������
	unlink($org_file);

	if (!$isupload) {
		if ($out && $fp = fopen($output,"wb")) {
			fwrite($fp, rtrim($out));
			fclose($fp);
		}
		/*
		if ($out_other && $fp = fopen($output_other,"wb")) {
			fwrite($fp, rtrim($out_other));
			fclose($fp);
		}
		*/
		$enc = mb_detect_encoding($out, 'auto');
		echo "<pre>";
		echo @htmlspecialchars($out, ENT_COMPAT, $enc);
		echo "</pre>";

	} else {
		while( ob_get_level() ) {
			if (! ob_end_clean()) {
				break;
			}
		}
		header('Content-Disposition: attachment; filename="' . $input . '"');
		header('Content-Length: ' . strlen($out));
		header('Content-Type: plain/text');

		echo $out;
		exit;
	}



	//echo "<pre>";
	//foreach ($defines as $key=>$val) {
	//	echo htmlspecialchars($key)."<br>";
	//}
	//echo "</pre>";
	//echo "<hr>";
}

function _for_quote_replace($str,$tgt,$mode,$ext='"') {
	//echo $str;
	$str = str_replace('\\'.$ext,$ext,$str);
	if ($mode == "in")
		return str_replace($tgt,"\x08",$str);
	else {
		return str_replace("\x08",$tgt,$str);
	}
}

function _for_quote_replace2($str,$tgt,$mode,$ext='"') {
	//echo $str;
	$str = str_replace('\\'.$ext,$ext,$str);
	if ($mode == "in")
		return str_replace($tgt,"\x07",$str);
	else {
		return str_replace("\x07",$tgt,$str);
	}
}

function _global_replace($global,$str) {
	//echo $str;
	$str = str_replace('\\"','"',$str);
	$str = preg_replace("/\{?".preg_quote($global,"/")."((?:\[[^\]]+\])*)(?![a-zA-Z0-9_\x7f-\xff])\}?/i","{\$this->root->".substr($global,1)."$1}",$str);
	return $str;
}

function convert_s2u ($plugin, $mydirname, $initonly) {
	$input = $plugin . '.inc.php';
	$org_file = XOOPS_TRUST_PATH . '/modules/xpwiki/plugin/' . $input;
	$dat = file_get_contents($org_file);

	if ($initonly) {
		$withparent = (empty($_POST['withparent']))? '' : "\n\t\t//Call trust side init()\n\t\tparent::plugin_".$plugin."_init();\n";
		$init = '<'.'?php' . "\n" . 'class xpwiki_' . $mydirname  . '_plugin_' . $plugin . ' extends xpwiki_plugin_' . $plugin . ' {' ."\n";
		if (preg_match('/(\s*function\s+plugin_'.$plugin.'_init\s*[^{]+{)(.+})[^}]+function\b/isSU', $dat, $match)) {
			$init_func = trim($match[1].$withparent.$match[2], "\r\n");
		} else {
			$init_func = <<<EOD
	function plugin_{$plugin}_init () {{$withparent}
		// There is no default
	}
EOD;
		}
		$init .= "\n" . $init_func . "\n";
		$init .= '}';
		$dat = $init;
	} else {
		$dat = preg_replace("/((?:^|\n|\r)\s*class\s+xpwiki_)(plugin(_[_0-9a-zA-Z]+)\s+extends\s+xpwiki_plugin)/","$1".$mydirname."_$2$3",$dat);
	}

	while( ob_get_level() ) {
		if (! ob_end_clean()) {
			break;
		}
	}

	header('Content-Disposition: attachment; filename="' . $input . '"');
	header('Content-Length: ' . strlen($dat));
	header('Content-Type: plain/text');

	echo $dat;
	exit;

}

// file_get_contents -- Reads entire file into a string
// (PHP 4 >= 4.3.0, PHP 5)
if (! function_exists('file_get_contents')) {
	function file_get_contents($filename, $incpath = false, $resource_context = null)
	{
		if (false === $fh = fopen($filename, 'rb', $incpath)) {
			trigger_error('file_get_contents() failed to open stream: No such file or directory', E_USER_WARNING);
			return false;
		}

		clearstatcache();
		if ($fsize = @filesize($filename)) {
			$data = fread($fh, $fsize);
		} else {
			$data = '';
			while (!feof($fh)) {
				$data .= fread($fh, 8192);
			}
		}

		fclose($fh);
		return $data;
	}
}
?>