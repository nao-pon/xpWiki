<?php
//
// Created on 2006/09/29 by nao-pon http://hypweb.net/
// $Id: root.php,v 1.4 2006/10/23 04:21:57 nao-pon Exp $
//
class XpWikiRoot {

	var $c = array(); //constant
	
	// xpwiki
	var $mydirpath;
	var $pgid;
	var $runmode;
	
	// Global Vars
	var $BracketName;
	var $InterWikiName;
	var $NotePattern;
	var $WikiName;
	var $_IMAGE;
	var $_LANG;
	var $_LINK;
	var $_btn_addtop;
	var $_btn_cancel;
	var $_btn_load;
	var $_btn_notchangetimestamp;
	var $_btn_preview;
	var $_btn_repreview;
	var $_btn_template;
	var $_btn_update;
	var $_list_pad_str;
	var $_msg_andresult;
	var $_msg_auth;
	var $_msg_help;
	var $_msg_invalidiwn;
	var $_msg_notfoundresult;
	var $_msg_orresult;
	var $_msg_other;
	var $_msg_symbol;
	var $_msg_unfreeze;
	var $_msg_word;
	var $_symbol_noexists;
	var $_title_cannotedit;
	var $_title_cannotread;
	var $_ul_left_margin;
	var $_ul_margin;
	var $adminpass;
	var $aliaspage;
	var $arg;
	var $attach_link;
	var $auth_method_type;
	var $auth_users;
	var $auto_template_func;
	var $auto_template_rules;
	var $autoalias;
	var $autoalias_max_words;
	var $autolink;
	var $cantedit;
	var $cols;
	var $cycle;
	var $date_format;
	var $defaultpage;
	var $del_backup;
	var $do_backup;
	var $do_update_diff_table;
	var $edit_auth;
	var $edit_auth_pages;
	var $fixed_heading_anchor;
	var $foot_explain;
	var $function_freeze;
	var $head_tags;
	var $help_page;
	var $hr;
	var $javascript;
	var $lastmod;
	var $line_rules;
	var $list_index;
	var $load_template_func;
	var $maxage;
	var $maxshow;
	var $maxshow_deleted;
	var $modifier;
	var $modifierlink;
	var $no_proxy;
	var $nofollow;
	var $non_list;
	var $note_hr;
	var $notify;
	var $notify_diff_only;
	var $notify_from;
	var $notify_header;
	var $notify_subject;
	var $notify_to;
	var $notimeupdate;
	var $nowikiname;
	var $page_title;
	var $pagereading_chasen_path;
	var $pagereading_config_dict;
	var $pagereading_config_page;
	var $pagereading_enable;
	var $pagereading_kakasi_path;
	var $pagereading_kanji2kana_converter;
	var $pagereading_kanji2kana_encoding;
	var $pkwk_dtd;
	var $read_auth;
	var $read_auth_pages;
	var $referer;
	var $related;
	var $related_link;
	var $related_str;
	var $rows;
	var $rule_page;
	var $rule_related_str;
	var $script;
	var $script_directory_index;
	var $search_auth;
	var $search_non_list;
	var $search_word_color;
	var $show_passage;
	var $smtp_auth;
	var $smtp_server;
	var $str_rules;
	var $time_format;
	var $trackback;
	var $trackback_javascript;
	var $use_proxy;
	var $vars;
	var $weeklabels;
	var $whatsdeleted;
	var $whatsnew;
	
	function xpwiki_root() {

	}
}
?>