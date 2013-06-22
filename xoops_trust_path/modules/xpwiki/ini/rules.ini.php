<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: rules.ini.php,v 1.2 2007/11/30 05:04:22 nao-pon Exp $
// Copyright (C)
//   2003-2005 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// PukiWiki setting file

/////////////////////////////////////////////////
// 日時置換ルール (閲覧時に置換)
// $usedatetime = 1なら日時置換ルールが適用されます
// 必要のない方は $usedatetimeを0にしてください。
$root->datetime_rules = array(
	'&amp;_now;'	=> $this->format_date($root->c['UTIME']),
	'&amp;_date;'	=> $this->get_date($root->date_format),
	'&amp;_time;'	=> $this->get_date($root->time_format),
);

/////////////////////////////////////////////////
// ユーザ定義ルール(保存時に置換)
//  正規表現で記述してください。?(){}-*./+\$^|など
//  は \? のようにクォートしてください。
//  デリミタは "/" が使用されます。
//  行単位ではなく、投稿文全体で処理します。
//  行頭または行末を指定する場合は、
//  '任意の判別子' => array('/^hoge$/m', 'hogeのみの行だよ'),
//  のように、
//  任意の判別子をキーとし値を配列で第一項目にデリミタを含む正規表現パターン、
//  第二項目に置換文字列を指定してください。
//

// BugTrack2/106: Only variables can be passed by reference from PHP 5.0.5
$page_array = explode('/', $root->vars['page']); // with array_pop()

$root->str_rules = array(
	'now\?' 	=> $this->format_date($root->c['UTIME']),
	'date\?'	=> $this->get_date($root->date_format),
	'time\?'	=> $this->get_date($root->time_format),
	'&now;' 	=> $this->format_date($root->c['UTIME']),
	'&date;'	=> $this->get_date($root->date_format),
	'&time;'	=> $this->get_date($root->time_format),
	'&page;'	=> array_pop($page_array),
	'&fpage;'	=> $root->vars['page'],
	'&t;'   	=> "\t",
	'&ua;'      => htmlspecialchars($root->ua),
);

//  preg_replace_callback を使用する置換
//  'デリミタを含む正規表現パターン' => 置換後の文字列を返す関数表現
$root->str_rules_callback = array();

unset($page_array);

?>
