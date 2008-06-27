<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: pukiwiki.ini.php,v 1.81 2008/06/27 01:25:54 nao-pon Exp $
// Copyright (C)
//   2002-2006 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// PukiWiki main setting file

/////////////////////////////////////////////////
// Variable initialize
$root->ext_autolinks = array();	// External AutoLink
$root->page_aliases = array(); // Pagename aliases

/////////////////////////////////////////////////
// Functionality settings

// PKWK_OPTIMISE - Ignore verbose but understandable checking and warning
//   If you end testing this PukiWiki, set '1'.
//   If you feel in trouble about this PukiWiki, set '0'.
$const['PKWK_OPTIMISE'] = 0;

/////////////////////////////////////////////////
// Security settings

// PKWK_READONLY - Prohibits editing and maintain via WWW
//   NOTE: Counter-related functions will work now (counter, attach count, etc)
$const['PKWK_READONLY'] = 0; // 0 or 1

// PKWK_SAFE_MODE - Prohibits some unsafe(but compatible) functions 
// 'auto': Safe mode( The administer is excluded. )
//     1 : Safe mode
//     0 : Normal mode
$const['PKWK_SAFE_MODE'] = 'auto';

// PKWK_DISABLE_INLINE_IMAGE_FROM_URI - Disallow using inline-image-tag for URIs
//   Inline-image-tag for URIs may allow leakage of Wiki readers' information
//   (in short, 'Web bug') or external malicious CGI (looks like an image's URL)
//   attack to Wiki readers, but easy way to show images.
$const['PKWK_DISABLE_INLINE_IMAGE_FROM_URI'] = 0;

// $const['PKWK_DISABLE_INLINE_IMAGE_FROM_URI'] = 0 の時、
// 外部サイトのファイルは ref プラグインを使用して表示する
$const['SHOW_EXTIMG_BY_REF'] = TRUE;

// PKWK_QUERY_STRING_MAX
//   Max length of GET method, prohibits some worm attack ASAP
//   NOTE: Keep (page-name + attach-file-name) <= PKWK_QUERY_STRING_MAX
$const['PKWK_QUERY_STRING_MAX'] = 640; // Bytes, 0 = OFF

/////////////////////////////////////////////////
// Experimental features

// Multiline plugin hack (See BugTrack2/84)
// EXAMPLE(with a known BUG):
//   #plugin(args1,args2,...,argsN){{
//   argsN+1
//   argsN+1
//   #memo(foo)
//   argsN+1
//   }}
//   #memo(This makes '#memo(foo)' to this)
$const['PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK'] = 0; // 1 = Disabled

// 整形済みマルチラインプラグイン
// Multiline PRE plugins
$this->root->multiline_pre_plugins = array('pre', 'code');

/////////////////////////////////////////////////
// UI LANG Auto Discovery

// Accept Lang
$const['ACCEPT_LANG_REGEX'] = '/(?:^|\W)([a-z]{2}(?:-[a-z]+)?)/i';

// GET QUERY's key of set lang.
$const['SETLANG'] = $this->get_setlang('setlang');

// COOKIE's key of set lang.
$const['SETLANG_C'] = $this->get_setlang_c(''); 

/////////////////////////////////////////////////
// Directory settings I (ended with '/', permission '777')

// You may hide these directories (from web browsers)
// by setting $const['DATA_HOME'] at index.php.

$const['DATA_DIR']         = $const['DATA_HOME'] . 'private/wiki/';       // Latest wiki texts
$const['DIFF_DIR']         = $const['DATA_HOME'] . 'private/diff/';       // Latest diffs
$const['BACKUP_DIR']       = $const['DATA_HOME'] . 'private/backup/';     // Backups
$const['CACHE_DIR']        = $const['DATA_HOME'] . 'private/cache/';      // Some sort of caches
$const['UPLOAD_DIR']       = $const['DATA_HOME'] . 'attach/';             // Attached files and logs
$const['COUNTER_DIR']      = $const['DATA_HOME'] . 'private/counter/';    // Counter plugin's counts
$const['TRACKBACK_DIR']    = $const['DATA_HOME'] . 'private/trackback/';  // TrackBack logs
$const['PLUGIN_DIR']       = $const['DATA_HOME'] . 'private/plugin/';     // Plugin directory
$const['RENDER_CACHE_DIR'] = $const['DATA_HOME'] . 'private/cache/';      // Rander caches

/////////////////////////////////////////////////
// Directory settings II (ended with '/')

// Skins / Stylesheets
// Default skin name
$const['SKIN_NAME'] = 'default';

// Enable Skin changer by GET REQUEST or Plugin? (0: off, 1: on)
$const['SKIN_CHANGER'] = 1;

// tDiary theme directory
$const['TDIARY_DIR'] = 'skin/tdiary_theme/';

// Static image files
$const['IMAGE_DIR'] = $const['HOME_URL'].'image/';
// Keep this directory shown via web browsers like
// ./IMAGE_DIR from index.php.

// loader.php URL
$const['LOADER_URL'] = $const['HOME_URL'].'skin/loader.php';

/////////////////////////////////////////////////
// Local time setting

//$const['ZONETIME'] = 9 * 3600; // JST = GMT + 9
$const['ZONETIME'] = $this->get_zonetime();
//$const['ZONE'] = 'JST';
$const['ZONE'] = $this->get_zone_by_time($const['ZONETIME'] / 3600);

/////////////////////////////////////////////////
// Title of your Wikisite (Name this)
// Also used as RSS feed's channel name etc
$root->module_title = $root->module['title'] ;

// HTML HEAD Title
$root->html_head_title = '$page_title$content_title - $module_title';

// Specifies title formatting rule. (Regex)
// The first pattern match part is used.
$root->title_setting_string = 'TITLE:';
$root->title_setting_regex = '/^TITLE:(.*)(\r\n|\r|\n)?$/m';

// Specify PukiWiki URL (default: auto)
//$root->script = 'http://example.com/pukiwiki/';

// Shorten $root->script: Cut its file name (default: not cut)
//$root->script_directory_index = 'index.php';

// Site admin's name (CHANGE THIS)
$root->modifier = 'anonymous';

// Site admin's Web page (CHANGE THIS)
$root->modifierlink = 'http://pukiwiki.example.com/';

// Page name case insensitive
$root->page_case_insensitive = 0;

// Default page name
$root->defaultpage  = 'FrontPage';     // Top / Default page
$root->whatsnew     = 'RecentChanges'; // Modified page list
$root->whatsdeleted = 'RecentDeleted'; // Removeed page list
$root->interwiki    = 'InterWikiName'; // Set InterWiki definition here
$root->aliaspage    = 'AutoAliasName'; // Set AutoAlias definition here
$root->menubar      = 'MenuBar';       // Menu
$root->render_attach= ':RenderAttaches';

$const['PLUGIN_RENAME_LOGPAGE'] = ':RenameLog'; // Rename Log page

// Guest user's name (It will be overwrite by xoops setting.)
$root->anonymous = 'anonymous';

/////////////////////////////////////////////////
// Always output "nofollow,noindex" attribute
$root->nofollow = 0; // 1 = Try hiding from search engines

/////////////////////////////////////////////////
// PKWK_ALLOW_JAVASCRIPT - Allow / Prohibit using JavaScript
$const['PKWK_ALLOW_JAVASCRIPT'] = 1;

/////////////////////////////////////////////////
// TrackBack feature

// Enable Trackback
$root->trackback = 0;

// Show trackbacks with an another window (using JavaScript)
$root->trackback_javascript = 0;

/////////////////////////////////////////////////
// Referer list feature
$root->referer = 0;

/////////////////////////////////////////////////
// Page comment feature
$root->allow_pagecomment = 1;

/////////////////////////////////////////////////
// _Disable_ WikiName auto-linking
$root->nowikiname = 0;

/////////////////////////////////////////////////
// Disable slashes comment out
$root->no_slashes_commentout = 0;

/////////////////////////////////////////////////
// 2階層以上で basename が 数字と- のみの場合
// リンク時の表示をタイトルに置換する 0 or 1
$root->pagename_num2str = 1;

/////////////////////////////////////////////////
// [ 1 ] ページリンクを [pgid].html の形式にする
// modules/[DirName]/.htaccess に次の設定が必要です
/* .htaccess 
RewriteEngine on
RewriteRule ^([0-9]+)\.html$ index.php?pgid=$1 [qsappend,L]
 */
// [ 2 ] ページリンクを index/ページ名 の形式にする
// modules/[DirName]/.htaccess に次の設定が必要です
/* .htaccess 
Options +MultiViews
<FilesMatch "^index$">
ForceType application/x-httpd-php
</FilesMatch>
 */
// [ 3 ] ページリンクを index.php/ページ名 の形式にする
// modules/[DirName]/.htaccess の設定は不要です 

$root->static_url = 0; // 0 or 1, 2, 3

// PATH_INFO 使用時 (static_url = 2 or 3) のファイル名
// "index" 以外にする場合は、.htaccess の書き換えと次の内容のファイルを置く
/* 「スクリプト名」で保存する
<?php
include 'index.php';
 */
$root->path_info_script = 'index';

/////////////////////////////////////////////////
// ページリンクをUTF-8エンコードする
// 
$root->url_encode_utf8 = 0;

/////////////////////////////////////////////////
// URLエンコードされていないGETクエリを受け入れる
// URL encoding is not GET queries to accept
$root->accept_not_encoded_query = 0;

/////////////////////////////////////////////////
// 外部リンクの追加属性
// Attributes for external link tag <a>.
// target
// ※ HTML4.01, XHTML1.0で非推奨、HTML4.01Strict, XHTML1.1では使えません
$root->link_target = '';

// class
$root->class_extlink = 'ext';

// favicon auto set class name
$root->favicon_set_classname = 'ext';

// favicon auto replace class name
$root->favicon_replace_classname = 'extWithFavicon';

// Add rel="nofollow"? (0 or 1)
$root->nofollow_extlink = 0;

/////////////////////////////////////////////////
// AutoLink feature
// Automatic link to existing pages (especially helpful for non-wikiword pages, but heavy)

// Minimum length of page name
$root->autolink = 0; // Bytes, 0 = OFF (try 8)

// Is upper directory hierarchy omissible?
// 上層階層名は省略可能 ?
$root->autolink_omissible_upper = 0; // Bytes(need $root->autolink = ON), 0 = OFF
$root->autolink_omissible_upper_priority = 60; // 優先度(通常のAutolink=50)

/////////////////////////////////////////////////
// External AutoLink
// AutoLink to external site's page.

//// Auto link for hypweb's xpwiki/keyword/[ANY]
//$root->ext_autolinks[] = array(
//	'target'  => '' , 				// Target pages split with '&' (prefix search)
//	'priority'=> 40 ,				// Priority (Intenal AutoLink = 50)
//	'url'     => 'http://xoops.hypweb.net/modules/xpwiki/' , // '' means own wiki, 'DirctoryName' for other xpWiki in this site.
//	'urldat'  => 0 ,				// url is autolink's data.(0:No, 1:Yes)
//	'case_i'  => 1 ,				// Case insensitive
//	'base'    => 'keyword' ,		// base directory ('' means all pages)
//	'len'     => 3 ,				// minimum length of link text
//	'enc'     => 'EUC-JP' ,			// character encoding
//	'cache'   => 180 ,				// cache minutes (minimum: 10min)
//	'title'   => 'hypweb:[KEY]' ,	// title attr ([KEY] replaced a target word)
//	'pat'     => '' ,				// Link pattern. (can use [URL_ENCODE], [WIKI_ENCODE], [EWORDS_ENCODE])
//	'a_target'=> '' ,				// <A> attribute 'target'.
//	'a_class' => '' ,				// <A> attribute 'class'.
//);

//// Auto link for kaunet.biz
//$root->ext_autolinks[] = array(
//	'target'  => '' , 				// Target pages split with '&' (prefix search)
//	'priority'=> 40 ,				// Priority (Intenal AutoLink = 50)
//	'url'     => 'http://www.kaunet.biz/dat/autolink.dat' , // '' means own wiki, 'DirctoryName' for other xpWiki in this site.
//	'urldat'  => 1 ,				// url is autolink's data.(0:No, 1:Yes)
//	'case_i'  => 1 ,				// Case insensitive
//	'base'    => '' ,				// base directory ('' means all pages)
//	'len'     => 3 ,				// minimum length of link text
//	'enc'     => 'UTF-8' ,			// character encoding
//	'cache'   => 180 ,				// cache minutes (minimum: 10min)
//	'title'   => 'Kaunet:[KEY]' ,	// title attr ([KEY] replaced a target word)
//	'pat'     => 'http://www.kaunet.biz/[WIKI_ENCODE].html' ,// Link pattern. (can use [URL_ENCODE], [WIKI_ENCODE], [EWORDS_ENCODE])
//	'a_target'=> '' ,				// <A> attribute 'target'.
//	'a_class' => '' ,				// <A> attribute 'class'.
//);

//// Auto link for e-words.jp
//$root->ext_autolinks[] = array(
//	'target'  => '' , 				// Target pages split with '&' (prefix search)
//	'priority'=> 40 ,				// Priority (Intenal AutoLink = 50)
//	'url'     => 'http://xoops.hypweb.net/download/e-words.autolink.dat', // '' means own wiki, 'DirctoryName' for other xpWiki in this site.
//	'urldat'  => 1 ,				// url is autolink's data.
//	'case_i'  => 1 ,				// Case insensitive
//	'base'    => '' ,				// base directory ('' means all pages)
//	'len'     => 3 ,				// minimum length of page name
//	'enc'     => 'UTF-8',			// character encoding
//	'cache'   => 10 ,				// cache minutes (minimum: 10min)
//	'title'   => 'e-Words:[KEY]' ,	// title attr ([KEY] replaced a target word)
//	'pat'     => 'http://e-words.jp/w/[EWORDS_ENCODE].html' ,	// Link pattern. (can use [URL_ENCODE], [WIKI_ENCODE], [EWORDS_ENCODE])
//	'a_target'=> '' ,				// <A> attribute 'target'.
//	'a_class' => '' ,				// <A> attribute 'class'.
//);

/////////////////////////////////////////////////
// AutoAlias feature
// Automatic link from specified word, to specifiled URI, page or InterWiki

// Minimum length of alias "from" word
$root->autoalias = 0; // Bytes, 0 = OFF (try 8)

// Limit loading valid alias pairs
$root->autoalias_max_words = 50; // pairs

/////////////////////////////////////////////////
// Enable Freeze / Unfreeze feature
$root->function_freeze = 1;

/////////////////////////////////////////////////
// Allow to use 'Do not change timestamp' checkbox
// (0:Disable, 1:For everyone,  2:Only for the administrator)
$root->notimeupdate = 1;

/////////////////////////////////////////////////
// Admin password for this Wikisite

// Default: always fail
$root->adminpass = '{x-php-md5}!';

// Sample:
//$root->adminpass = 'pass'; // Cleartext
//$root->adminpass = '{x-php-md5}1a1dc91c907325c69271ddf0c944bc72'; // PHP md5()  'pass'
//$root->adminpass = '{CRYPT}$1$AR.Gk94x$uCe8fUUGMfxAPH83psCZG/';   // LDAP CRYPT 'pass'
//$root->adminpass = '{MD5}Gh3JHJBzJcaScd3wyUS8cg==';               // LDAP MD5   'pass'
//$root->adminpass = '{SMD5}o7lTdtHFJDqxFOVX09C8QnlmYmZnd2Qx';      // LDAP SMD5  'pass'

/////////////////////////////////////////////////
// Page-reading feature settings
// (Automatically creating pronounce datas, for Kanji-included page names,
//  to show sorted page-list correctly)

// Enable page-reading feature by calling ChaSen or KAKASHI command
// (1:Enable, 0:Disable)
$root->pagereading_enable = 0;

// Specify converter as ChaSen('chasen') or KAKASI('kakasi') or None('none')
$root->pagereading_kanji2kana_converter = 'none';

// Specify Kanji encoding to pass data between PukiWiki and the converter
$root->pagereading_kanji2kana_encoding = 'EUC'; // Default for Unix
//$root->pagereading_kanji2kana_encoding = 'SJIS'; // Default for Windows

// Absolute path of the converter (ChaSen)
$root->pagereading_chasen_path = '/usr/local/bin/chasen';
//$root->pagereading_chasen_path = 'c:\progra~1\chasen21\chasen.exe';

// Absolute path of the converter (KAKASI)
$root->pagereading_kakasi_path = '/usr/local/bin/kakasi';
//$root->pagereading_kakasi_path = 'c:\kakasi\bin\kakasi.exe';

// Page name contains pronounce data (written by the converter)
$root->pagereading_config_page = ':config/PageReading';

// Page name of default pronouncing dictionary, used when converter = 'none'
$root->pagereading_config_dict = ':config/PageReading/dict';

/////////////////////////////////////////////////
// User definition
$root->auth_users = array(
	// Username => password
	'foo'	=> 'foo_passwd', // Cleartext
	'bar'	=> '{x-php-md5}f53ae779077e987718cc285b14dfbe86', // PHP md5() 'bar_passwd'
	'hoge'	=> '{SMD5}OzJo/boHwM4q5R+g7LCOx2xGMkFKRVEx',      // LDAP SMD5 'hoge_passwd'
);

/////////////////////////////////////////////////
// Authentication method

$root->auth_method_type	= 'pagename';	// By Page name
//$root->auth_method_type	= 'contents';	// By Page contents

/////////////////////////////////////////////////
// Read auth (0:Disable, 1:Enable)
$root->read_auth = 0;

$root->read_auth_pages = array(
	// Regex		   Username
	'#HogeHoge#'		=> 'hoge',
	'#(NETABARE|NetaBare)#'	=> 'foo,bar,hoge',
);

/////////////////////////////////////////////////
// Edit auth (0:Disable, 1:Enable)
$root->edit_auth = 0;

$root->edit_auth_pages = array(
	// Regex		   Username
	'#BarDiary#'		=> 'bar',
	'#HogeHoge#'		=> 'hoge',
	'#(NETABARE|NetaBare)#'	=> 'foo,bar,hoge',
);

// Q & A 認証 (使用しない = 0, ゲストのみ = 1, 管理者以外 = 2)
$root->riddle_auth = 1;

// 編集権限をプラグインでの書き込みにも適用する
$root->plugin_follow_editauth = 0;

// 凍結をプラグインでの書き込みにも適用する
$root->plugin_follow_freeze = 1;

/////////////////////////////////////////////////
// ページ情報のサイト規定値
// inherit = 0:継承指定なし, 1:規定値継承指定, 2:強制継承指定, 3:規定値継承した値, 4:強制継承した値
$root->pginfo = array(
	'uid'       => 0,     // UserID
	'ucd'       => '',    // UserCode(by cookie)
	'uname'     => '',    // UserName(by cookie)
	'einherit'  => 3,     // Edit Inherit
	'eaids'     => 'all', // Editable users
	'egids'     => 'all', // Editable groups
	'vinherit'  => 3,     // View Inherit
	'vaids'     => 'all', // Viewable users
	'vgids'     => 'all', // Viewable groups
	'lastuid'   => 0,     // Last editer's uid
	'lastucd'   => '',    // Last editer's ucd(by cookie)
	'lastuname' => '',    // Last editer's name(by cookie)
);

/////////////////////////////////////////////////
// Search auth
// 0: Disabled (Search read-prohibited page contents)
// 1: Enabled  (Search only permitted pages for the user)
$root->search_auth = 0;

/////////////////////////////////////////////////
// $root->whatsnew: Max number of RecentChanges
$root->maxshow = 60;

// $root->whatsdeleted: Max number of RecentDeleted
// (0 = Disabled)
$root->maxshow_deleted = 60;

/////////////////////////////////////////////////
// Page names can't be edit via PukiWiki
$root->cantedit = array( $root->whatsnew );

/////////////////////////////////////////////////
// HTTP: Output Last-Modified header
$root->lastmod = 0;

/////////////////////////////////////////////////
// Date format
$root->date_format = 'Y-m-d';

// Time format
$root->time_format = 'H:i:s';

/////////////////////////////////////////////////
// Max number of RSS feed
$root->rss_max = 15;

/////////////////////////////////////////////////
// Backup related settings

// Enable backup
$root->do_backup = 1;

// When a page had been removed, remove its backup too?
$root->del_backup = 0;

// Bacukp interval and generation
$root->cycle  =   3; // Wait N hours between backup (0 = no wait)
$root->maxage = 120; // Stock latest N backups

// NOTE: $cycle x $root->maxage / 24 = Minimum days to lost your data
//          3   x   120   / 24 = 15

// Make backup every time if different user at last time.
$root->backup_everytime_others = 1;

// Splitter of backup data (NOTE: Too dangerous to change)
$const['PKWK_SPLITTER'] = '>>>>>>>>>>';

// Use lightdox function(with JavaScript) for open a image. 
$root->ref_use_lightbox = 1;

// Enable easy ref syntax {{...}}
$root->easy_ref_syntax = 1;

/////////////////////////////////////////////////
// Command execution per update

$const['PKWK_UPDATE_EXEC'] = '';

// Sample: Namazu (Search engine)
//$root->target     = '/var/www/wiki/';
//$root->mknmz      = '/usr/bin/mknmz';
//$root->output_dir = '/var/lib/namazu/index/';
//define('PKWK_UPDATE_EXEC',
//	$root->mknmz . ' --media-type=text/pukiwiki' .
//	' -O ' . $root->output_dir . ' -L ja -c -K ' . $root->target);


/////////////////////////////////////////////////
// If this web server can't connect to WWW then set 1;
$root->can_not_connect_www = 0;

/////////////////////////////////////////////////
// HTTP proxy setting (for TrackBack etc)

// Use HTTP proxy server to get remote data
$root->use_proxy = 0;

$root->proxy_host = 'proxy.example.com';
$root->proxy_port = 8080;

// Do Basic authentication
$root->need_proxy_auth = 0;
$root->proxy_auth_user = 'username';
$root->proxy_auth_pass = 'password';

// Hosts that proxy server will not be needed
$root->no_proxy = array(
	'localhost',	// localhost
	'127.0.0.0/8',	// loopback
//	'10.0.0.0/8'	// private class A
//	'172.16.0.0/12'	// private class B
//	'192.168.0.0/16'	// private class C
//	'no-proxy.com',
);

////////////////////////////////////////////////
// Show system notification in SKIN
$root->show_system_notification_skin = 0;

////////////////////////////////////////////////
// Mail related settings

// Send mail per update of pages
$root->notify = 0;

// Send diff only
$root->notify_diff_only = 1;

//// These settings are not used on XOOPS.
// SMTP server (Windows only. Usually specified at php.ini)
$root->smtp_server = 'localhost';

// Mail recipient (To:) and sender (From:)
$root->notify_to   = 'to@example.com';	// To:
$root->notify_from = 'from@example.com';	// From:
//// The above-mentioned setting is not used on XOOPS.

// Subject: ($root->page = Page name wll be replaced)
$root->notify_subject = '['.$this->root->module['name'].'] $page';

// Mail header
// NOTE: Multiple items must be divided by "\r\n", not "\n".
$root->notify_header = '';

/////////////////////////////////////////////////
// Mail: POP / APOP Before SMTP
// These settings are not used on XOOPS.

// Do POP/APOP authentication before send mail
$root->smtp_auth = 0;

$root->pop_server = 'localhost';
$root->pop_port   = 110;
$root->pop_userid = '';
$root->pop_passwd = '';

// Use APOP instead of POP (If server uses)
//   Default = Auto (Use APOP if possible)
//   1       = Always use APOP
//   0       = Always use POP
// $root->pop_auth_use_apop = 1;

/////////////////////////////////////////////////
// Ignore list

// Regex of ignore pages
$root->non_list = '^\:';

// Search ignored pages
$root->search_non_list = 1;

// Show page's filelist only admin.
$root->filelist_only_admin = 0;

/////////////////////////////////////////////////
// Template setting

$root->auto_template_func = 1;
$root->auto_template_rules = array(
	'((.+)\/([^\/]+))' => array('\2/template', ':template/\2', 'template', ':template/default') ,
	'(()(.+))'         => array('template', ':template/default') ,
);

/////////////////////////////////////////////////
// Number of heading that inserts "#contents" automatically
// 0: Disabled
$root->contents_auto_insertion = 4;

/////////////////////////////////////////////////
// Automatically add fixed heading anchor
$root->fixed_heading_anchor = 1;

/////////////////////////////////////////////////
// enable paraedit 
$root->fixed_heading_anchor_edit = 1;
// part-edit area - 'compat':PukiWiki 1.4.4 compat, 'level':level
$root->paraedit_partarea = 'compat';

/////////////////////////////////////////////////
// Remove the first spaces from Preformatted text
$root->preformat_ltrim = 1;

/////////////////////////////////////////////////
// Convert linebreaks into <br />
$root->line_break = 0;

/////////////////////////////////////////////////
// Use extended table format like a PukiWikiMod
$root->extended_table_format = 1;

// Enable text-align of cell by spaces.
$root->space_cell_align = 1;

// Enable text-align of cell by symbols("<", "=" & ">").
$root->symbol_cell_align = 1;

// Enable join cell with empty cell.
$root->empty_cell_join = 1;

/////////////////////////////////////////////////
// Use date-time rules (See rules.ini.php)
$root->usedatetime = 1;

/////////////////////////////////////////////////
// ページキャッシュの設定 (ゲストアクセス時のみ)
// ページキャッシュを最長何分間するか？
$root->pagecache_min = 0;

// ページ更新時常にページキャッシュを破棄するページ
$root->always_clear_cache_pages = array (
	//$root->defaultpage,
	$root->menubar,
);

// 上位層のページもキャッシュをクリアする
$root->clear_cache_parent = TRUE; // (TRUE or FASLE)

/////////////////////////////////////////////////
// About CSS...

// Main CSS name
$root->main_css = 'main.css';

// <pre> の幅指定 (600px, auto など)
$root->pre_width = 'auto';

// IE に指定する <pre> の幅 (600px, auto など)
// xpWiki が Table 内に表示される場合(Table theme) px 指定したほうがよい
$root->pre_width_ie = '700px';

// CSS ID prefix ( ex. #xo-canvas )
$root->css_prefix = '';

/////////////////////////////////////////////////
//// XML-RPC ping setting (weblogUpdates.ping)
// Send update ping?
$root->update_ping = 0;

// ping servers URL + ' E'
// ' E' means Extended ping server. (weblogUpdates.extendedPing)
$root->update_ping_servers = '
http://api.my.yahoo.co.jp/RPC2
http://blog.goo.ne.jp/XMLRPC
http://blogsearch.google.co.jp/ping/RPC2 E
http://feeds.feedburner.com/ArakiNotes E
http://ping.bloggers.jp/rpc/
http://r.hatena.ne.jp/rpc
http://rpc.technorati.com/rpc/ping E
http://rpc.weblogs.com/RPC2 E
http://www.blogpeople.net/servlet/weblogUpdates E
';

/////////////////////////////////////////////////
// レンダラーモード用設定
// For renderer mode.

// レンダリングキャッシュを有効にする
// Enable render cache.
$root->render_use_cache = 0;

// キャッシュの有効時間(分) 0: Wikiページが新規作成・削除されるまで
// Render cache minutes. 0: Until make or delete a page.
$root->render_cache_min = 0;

// ページリンクをポップアップにする
// All page link uses popup. (1=All, 2=AutoLink only)
$root->render_popuplink = 0;

$root->render_popuplink_position = array(
	// Array values are value of the CSS.
	'top'    => '',
	'bottom' => '',
	'left'   => '',
	'right'  => '',
	'width'  => '',
	'height' => ''
);

// Show the Wiki Helper on the site wide.
$root->render_UseWikihelperAtAll = 0;

/////////////////////////////////////////////////
// For XOOPS System

// Update post count when page updating or page deleting
$root->xoops_post_count_up = 1;
$root->xoops_post_count_down = 1;

/////////////////////////////////////////////////
// User-Agent settings
//
// If you want to ignore embedded browsers for rich-content-wikisite,
// remove (or comment-out) all 'keitai' settings.
//
// If you want to to ignore desktop-PC browsers for simple wikisite,
// copy keitai.ini.php to default.ini.php and customize it.

$root->agents = array(
// pattern: A regular-expression that matches device(browser)'s name and version
// profile: A group of browsers

    // Embedded browsers (Rich-clients for PukiWiki)

	// Windows CE (Microsoft(R) Internet Explorer 5.5 for Windows(R) CE)
	// Sample: "Mozilla/4.0 (compatible; MSIE 5.5; Windows CE; sigmarion3)" (sigmarion, Hand-held PC)
	array('pattern'=>'#\b(?:MSIE [5-9]).*\b(Windows CE)\b#', 'profile'=>'default'),

	// ACCESS "NetFront" / "Compact NetFront" and thier OEM, expects to be "Mozilla/4.0"
	// Sample: "Mozilla/4.0 (PS2; PlayStation BB Navigator 1.0) NetFront/3.0" (PlayStation BB Navigator, for SONY PlayStation 2)
	// Sample: "Mozilla/4.0 (PDA; PalmOS/sony/model crdb/Revision:1.1.19) NetFront/3.0" (SONY Clie series)
	// Sample: "Mozilla/4.0 (PDA; SL-A300/1.0,Embedix/Qtopia/1.1.0) NetFront/3.0" (SHARP Zaurus)
	array('pattern'=>'#^(?:Mozilla/4).*\b(NetFront)/([0-9\.]+)#',	'profile'=>'default'),

    // Embedded browsers (Non-rich)

	array('pattern'=>'#^(Vodafone)/([0-9\.]+)#',	'profile'=>'keitai'),
	array('pattern'=>'#^(SoftBank)/([0-9\.]+)#',	'profile'=>'keitai'),


	// Windows CE (the others)
	// Sample: "Mozilla/2.0 (compatible; MSIE 3.02; Windows CE; 240x320 )" (GFORT, NTT DoCoMo)
	array('pattern'=>'#\b(Windows CE)\b#', 'profile'=>'keitai'),

	// ACCESS "NetFront" / "Compact NetFront" and thier OEM
	// Sample: "Mozilla/3.0 (AveFront/2.6)" ("SUNTAC OnlineStation", USB-Modem for PlayStation 2)
	// Sample: "Mozilla/3.0(DDIPOCKET;JRC/AH-J3001V,AH-J3002V/1.0/0100/c50)CNF/2.0" (DDI Pocket: AirH" Phone by JRC)
	array('pattern'=>'#\b(NetFront)/([0-9\.]+)#',	'profile'=>'keitai'),
	array('pattern'=>'#\b(CNF)/([0-9\.]+)#',	'profile'=>'keitai'),
	array('pattern'=>'#\b(AveFront)/([0-9\.]+)#',	'profile'=>'keitai'),
	array('pattern'=>'#\b(AVE-Front)/([0-9\.]+)#',	'profile'=>'keitai'), // The same?

	// NTT-DoCoMo, i-mode (embeded Compact NetFront) and FOMA (embedded NetFront) phones
	// Sample: "DoCoMo/1.0/F501i", "DoCoMo/1.0/N504i/c10/TB/serXXXX" // c以降は可変
	// Sample: "DoCoMo/2.0 MST_v_SH2101V(c100;TB;W22H12;serXXXX;iccxxxx)" // ()の中は可変
	array('pattern'=>'#^(DoCoMo)/([0-9\.]+)#',	'profile'=>'keitai'),

	// Vodafone's embedded browser
	// Sample: "J-PHONE/2.0/J-T03"	// 2.0は"ブラウザの"バージョン
	// Sample: "J-PHONE/4.0/J-SH51/SNxxxx SH/0001a Profile/MIDP-1.0 Configuration/CLDC-1.0 Ext-Profile/JSCL-1.1.0"
	array('pattern'=>'#^(J-PHONE)/([0-9\.]+)#',	'profile'=>'keitai'),

	// Openwave(R) Mobile Browser (EZweb, WAP phone, etc)
	// Sample: "OPWV-SDK/62K UP.Browser/6.2.0.5.136 (GUI) MMP/2.0"
	array('pattern'=>'#\b(UP\.Browser)/([0-9\.]+)#',	'profile'=>'keitai'),

	// Opera, dressing up as other embedded browsers
	// Sample: "Mozilla/3.0(DDIPOCKET;KYOCERA/AH-K3001V/1.4.1.67.000000/0.1/C100) Opera 7.0" (Like CNF at 'keitai'-mode)
	array('pattern'=>'#\b(?:DDIPOCKET|WILLCOM)\b.+\b(Opera) ([0-9\.]+)\b#',	'profile'=>'keitai'),

	// Planetweb http://www.planetweb.com/
	// Sample: "Mozilla/3.0 (Planetweb/v1.07 Build 141; SPS JP)" ("EGBROWSER", Web browser for PlayStation 2)
	array('pattern'=>'#\b(Planetweb)/v([0-9\.]+)#', 'profile'=>'keitai'),

	// DreamPassport, Web browser for SEGA DreamCast
	// Sample: "Mozilla/3.0 (DreamPassport/3.0)"
	array('pattern'=>'#\b(DreamPassport)/([0-9\.]+)#',	'profile'=>'keitai'),

	// Palm "Web Pro" http://www.palmone.com/us/support/accessories/webpro/
	// Sample: "Mozilla/4.76 [en] (PalmOS; U; WebPro)"
	array('pattern'=>'#\b(WebPro)\b#',	'profile'=>'keitai'),

	// ilinx "Palmscape" / "Xiino" http://www.ilinx.co.jp/
	// Sample: "Xiino/2.1SJ [ja] (v. 4.1; 153x130; c16/d)"
	array('pattern'=>'#^(Palmscape)/([0-9\.]+)#',	'profile'=>'keitai'),
	array('pattern'=>'#^(Xiino)/([0-9\.]+)#',	'profile'=>'keitai'),

	// SHARP PDA Browser (SHARP Zaurus)
	// Sample: "sharp pda browser/6.1[ja](MI-E1/1.0) "
	array('pattern'=>'#^(sharp [a-z]+ browser)/([0-9\.]+)#',	'profile'=>'keitai'),

	// WebTV
	array('pattern'=>'#^(WebTV)/([0-9\.]+)#',	'profile'=>'keitai'),

    // Desktop-PC browsers

	// Opera (for desktop PC, not embedded) -- See BugTrack/743 for detail
	// NOTE: Keep this pattern above MSIE and Mozilla
	// Sample: "Opera/7.0 (OS; U)" (not disguise)
	// Sample: "Mozilla/4.0 (compatible; MSIE 5.0; OS) Opera 6.0" (disguise)
	array('pattern'=>'#\b(Opera)[/ ]([0-9\.]+)\b#',	'profile'=>'default'),

	// MSIE: Microsoft Internet Explorer (or something disguised as MSIE)
	// Sample: "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)"
	array('pattern'=>'#\b(MSIE) ([0-9\.]+)\b#',	'profile'=>'default'),

	// Mozilla Firefox
	// NOTE: Keep this pattern above Mozilla
	// Sample: "Mozilla/5.0 (Windows; U; Windows NT 5.0; ja-JP; rv:1.7) Gecko/20040803 Firefox/0.9.3"
	array('pattern'=>'#\b(Firefox)/([0-9\.]+)\b#',	'profile'=>'default'),
	
	// Mac Safari
	// Sample: "Mozilla/5.0 (Macintosh; U; PPC Mac OS X; ja-jp) AppleWebKit/416.11 (KHTML, like Gecko) Safari/416.12"
    array('pattern'=>'#\b(Safari)(?:/([0-9\.]+))?\b#',	'profile'=>'default'),
    
    // Loose default: Including something Mozilla
	array('pattern'=>'#^([a-zA-z0-9 ]+)/([0-9\.]+)\b#',	'profile'=>'default'),

	array('pattern'=>'#^#',	'profile'=>'default'),	// Sentinel
);

$const['PKWK_DTD_XHTML_1_1'] = 17;
$const['PKWK_DTD_XHTML_1_0'] = 16;
$const['PKWK_DTD_XHTML_1_0_STRICT'] = 16;
$const['PKWK_DTD_XHTML_1_0_TRANSITIONAL'] = 15;
$const['PKWK_DTD_XHTML_1_0_FRAMESET'] = 14;
$const['PKWK_DTD_HTML_4_01'] = 3;
$const['PKWK_DTD_HTML_4_01_STRICT'] = 3;
$const['PKWK_DTD_HTML_4_01_TRANSITIONAL'] = 2;
$const['PKWK_DTD_HTML_4_01_FRAMESET'] = 1;
$const['PKWK_DTD_TYPE_XHTML'] = 1;
$const['PKWK_DTD_TYPE_HTML'] = 0;
$const['PKWK_PLUGIN_CALL_TIME_LIMIT'] = 768;
$const['PKWK_HTTP_REQUEST_URL_REDIRECT_MAX'] = 2;
$const['PKWK_CIDR_NETWORK_REGEX'] = '/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}';
$const['PLUGIN_TRACKBACK_VERSION'] = 'PukiWiki/TrackBack 0.3';
$const['PKWK_PASSPHRASE_LIMIT_LENGTH'] = 512;
$const['PKWK_CONFIG_PREFIX'] = ':config/';
$const['PKWK_DIFF_SHOW_CONFLICT_DETAIL'] = 1;
$const['PKWK_MAXSHOW_ALLOWANCE'] = 10;
$const['PKWK_MAXSHOW_CACHE'] = 'recent.dat';
$const['PKWK_ENTITIES_REGEX_CACHE'] = 'entities.dat';
$const['PKWK_AUTOLINK_REGEX_CACHE'] = 'autolink.dat';
$const['PKWK_AUTOALIAS_REGEX_CACHE'] = 'autoalias.dat';
$const['BACKUP_EXT'] = (extension_loaded('zlib'))? '.gz' : '.txt';
$const['PKWK_DIFF_SHOW_CONFLICT_DETAIL'] = 1;

// Fixed prefix of configuration-page's name
$const['PKWK_CONFIG_PREFIX'] = ':config/';

// 名前欄の仮文字列(コンバート後にユーザー名に置換) 
$const['USER_NAME_REPLACE'] = '__uSER_nAME_rEPLACE__';
$const['USER_CODE_REPLACE'] = '__uSER_cODE_rEPLACE__';

// #pginfo の正規表現 (#pginfo削除などに利用)
$const['PKWK_PGINFO_REGEX'] = '/^(?:#pginfo\(.*\)[\r\n]*)+/m';
?>