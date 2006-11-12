<?php
//
// Created on 2006/11/09 by nao-pon http://hypweb.net/
// $Id: pginfo.en.php,v 1.1 2006/11/12 08:43:57 nao-pon Exp $
//

$msg = array(
	'title_update'  => 'ページ情報DB更新',
	'msg_adminpass' => '管理者パスワード',
	'msg_all' => 'DBをすべて初期化&再設定',
	'msg_select' => '以下から選択して初期化&再設定',
	'msg_hint' => '初期導入時はすべてにチェックをつけて実行してください。',
	'msg_init' => 'ページ基本情報DB',
	'msg_count' => 'ページカウンター情報DB',
	'msg_noretitle' => '既存のページはタイトル情報を保持する。',
	'msg_retitle' => '既存のページもタイトル情報を再取得する。',
	'msg_plain_init' => '検索用テキストDB と ページ間リンク情報DB',
	'msg_plain_init_notall' => '検索用テキストDBが空のページのみ処理する。',
	'msg_plain_init_all' => 'すべてのページを処理する。(時間が掛かります。)',
	'msg_attach_init' => '添付ファイル情報DB',
	'msg_progress_report' => '進捗状況:',
	'msg_now_doing' => '只今、サーバー側で処理中です。<br />下の進捗画面に「すべての処理が完了しました。」と表示されるまで<br />このページを開いたままにして置いてください。',
	'msg_next_do' => '<span style="color:blue;">サーバーの実行時間制限により処理を中断しました。<br />下の進捗画面最下部の「続きの処理を実行」をクリックして<br />引き続き処理を行ってください。</span>',
	'btn_submit'    => '実行',
	'btn_next_do'    => '続きの処理を実行',
	'msg_done'      => 'すべての処理が完了しました。',
	'msg_usage'     => "
* Description

:Update Page Information DB|
Scan all page files and rebuild page information DB.

* Notice

Please wait a while, after clicking 'Run' button.

Max PHP execution time on this server is set to &font(red,b){%1d}; seconds.
So, this process will be paused at every &font(red,b){%2d}; seconds and will show 'Continue' button.
If you see 'Continue' button, you should click this to complete this procedure.

* Run

Please click 'Run' button.
If you cannot see 'Run' button, you should login as a Administrator user.

Options marked * mean, they have not beed processed yet.",
	// for page permission
	'title_permission' => 'Permission setting of $1',
	'edit_permission' => 'Editable Permission',
	'view_parmission' => 'Readable Permission',
	'parmission_setting' => 'Detailed setting of permission(An administrator & administer group are always admitted.)',
	'lower_page_inherit' => 'Inherit setting to a lower page.',
	'inherit_forced' => 'Inherited forcibly. (cannot set it in a lower page)',
	'inherit_default' => 'Inherited as the default value. (can set it in a lower page)',
	'inherit_onlythis' => 'Not Inherited. (Setting only for this page)',
	'permission_none' => 'Not set permission. (Reset value)',
	'default_inherit' => 'Indication contents of the following "Detailed setting of permission" are applied now.<br />When you change detailed setting of permission, please choose either of "Inherit setting to a lower page".',
	'can_not_set' => 'Setting of this page is not possible so that the forced inherit is set in a higher page.',
	'admit_all_group' => 'Admit in all groups.',
	'not_admit_all_group' => 'Not admit in all groups.',
	'admit_select_group' => 'Admit only in select groups.',
	'admit_all_user' => 'Admit in all users.',
	'not_admit_all_user' => 'Not admit in all users.',
	'admit_select_user' => 'Admit only in select users.',
	'submit' => 'Regist edit / read permission setting',
	'no_parmission_title' => 'Not have enough permission to make permission setting of $1',
	'no_parmission' => 'You don\'t have enough permission to make permission setting. It is only an administrator and a page creator that can do it by authority setting.',
	'done_ok' => 'Saved editing / reading permission.',
);
?>