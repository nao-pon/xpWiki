<?php
/*
 * Created on 2009/11/19 by nao-pon http://xoops.hypweb.net/
 * $Id: xmlrpc.inc.php,v 1.2 2010/06/04 07:17:26 nao-pon Exp $
 */

class xpwiki_plugin_xmlrpc extends xpwiki_plugin {
	function plugin_xmlrpc_init() {

		$this->config['BlogPages'] = array(
		//	'blog',
			'$uname/blog'
		);

		$this->config['br2lf'] = 1;
		$this->config['striptags'] = 1;

		$this->config['BlogPageTemplate'] = <<<EOD
#norelated
#noattach
#nopagecomment

* $2's blog &rsslink($1);

#block(width:200px,around,left){{
#calendar2(off)
}}

** Tag cloud

#block(round){{
#tag(0)
}}

#clear

** Recent posts

#calendar_viewer(this,5,past,notoday,contents:2)
EOD;
//'

	}

	function plugin_xmlrpc_convert() {
		$rsd = $this->root->script . '?cmd=xmlrpc';
		$this->root->head_tags[$rsd] = '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="'.$rsd.'" />';
		return '';
	}

	function plugin_xmlrpc_action() {
		if ($this->root->use_xmlrpc) {
			if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
				return $this->rsd();
			}

			$GLOBALS['xpWikiXmlRpcObj'] =& $this;
			HypCommonFunc::loadClass('IXR_Server');
			$server =& new IXR_Server(array(
				'blogger.newPost'           => 'blogger_newPost',
				'blogger.editPost'          => 'blogger_editPost',
				'blogger.deletePost'        => 'blogger_deletePost',
				'blogger.getRecentPosts'    => 'blogger_getRecentPosts',
				'blogger.getUsersBlogs'     => 'blogger_getUsersBlogs',
				'blogger.getUserInfo'       => 'blogger_getUserInfo',
				'metaWeblog.newPost'        => 'metaWeblog_newPost',
				'metaWeblog.editPost'       => 'metaWeblog_editPost',
				'metaWeblog.getPost'        => 'metaWeblog_getPost',
				'metaWeblog.getRecentPosts' => 'metaWeblog_getRecentPosts',
				'metaWeblog.getCategories'  => 'return_empty',
				'mt.getRecentPostTitles'    => 'mt_getRecentPostTitles',
				'mt.getCategoryList'        => 'return_empty',
				'mt.getPostCategories'      => 'return_empty',
				'mt.setPostCategories'      => 'return_true',
				'mt.setPostCategories'      => 'return_true',
				'mt.publishPost'            => 'return_true',
			));
			header('Content-Type: text/xml;charset=UTF-8');
		}
		return array('exit' => 'xmlrpc is not effective.');
	}

	function rsd() {
		$res = <<<EOD
<?xml version="1.0" ?>
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">
    <service>
        <engineName>xpWiki</engineName>
        <engineLink>http://xoops.hypweb.net/</engineLink>
        <homePageLink>{$this->cont['ROOT_URL']}</homePageLink>
        <apis>
            <api name="blogger" preferred="false" apiLink="{$this->root->script}?cmd=xmlrpc" blogID="" />
            <api name="metaWeblog" preferred="true" apiLink="{$this->root->script}?cmd=xmlrpc" blogID="" />
        </apis>
    </service>
</rsd>
EOD;
		header('Content-type: text/xml');
		return array('exit' => $res);
	}

	function get_blog_page($uname) {
		$pages = array();

		// Read user config
		$config = new XpWikiConfig($this->xpwiki, $this->cont['PKWK_CONFIG_USER'] . '/' . $uname);
		$table = $config->read() ? $config->get('XML-RPC') : array();
		foreach ($table as $row) {
			if (isset($row[1]) && strtolower(trim($row[0])) === 'myblog') {
				$page = $this->func->strip_bracket(trim($row[1]));
				if ($this->check_blogpage($page)) {
					$pages[] = $page;
				}
			}
		}

		// Read user config (template)
		if (! $pages) {
			$config = new XpWikiConfig($this->xpwiki, $this->cont['PKWK_CONFIG_USER'] . '/template');
			$table = $config->read() ? $config->get('XML-RPC') : array();
			foreach ($table as $row) {
				if (isset($row[1]) && strtolower(trim($row[0])) === 'myblog') {
					$page = $this->func->strip_bracket(trim($row[1]));
					$page = str_replace('$3', $uname, $page);
					if ($this->check_blogpage($page)) {
						$pages[] = $page;
					}
				}
			}
		}

		$config = NULL;
		unset($config);

		if (! $pages) {
			foreach($this->config['BlogPages'] as $page) {
				$page = str_replace('$uname', $uname, $page);
				if ($this->check_blogpage($page)) {
					$pages[] = $page;
				}
			}
		}

		return $pages;
	}

	function getUsersBlogs($args) {
		list($appkey, $uname, $pass) = array_pad($args, 3, '');

		$uname = $this->toInEnc($uname);
		$userinfo = $this->func->user_auth($uname, $pass);

		$res = array();
		$error = $this->get_error();
		if ($userinfo['uid']) {

			$this->func->set_userinfo($userinfo['uid']);

			if ($pages = $this->get_blog_page($uname)) {

				$bname = $this->root->siteinfo['sitename'] . ' - ' . $this->root->module['title'];
				$bname = $this->toUTF8($bname);

				if (is_string($pages)) $pages = array($pages);
				foreach ($pages as $page) {
					$pagename = $this->toUTF8($page);
					$res[] = array(
						'url'      => $this->func->get_page_uri($page, true),
						'blogid'   => $pagename,
						'blogName' => $pagename . '@' . $bname
					);
				}
			} else {
				$error = $this->get_error(802);
			}
		} else {
			$error = $this->get_error(801);
		}

		return $res? $res : new IXR_Error($error[0], $error[1]);
	}

	function getUserInfo($args) {
		list($appkey, $uname, $pass) = array_pad($args, 3, '');
		$uname = $this->toInEnc($uname);
		$userinfo = $this->func->user_auth($uname, $pass);

		$res = array();
		$error = $this->get_error();
		if ($userinfo['uid']) {
			$res = array(
				'userid' => $userinfo['uname'],
				'firstname' => '',
				'lastname' => '',
				'nickname' => '',
				'email' => '',
				'url' => $this->root->script
			);
		}

		return $res? array($res) : new IXR_Error($error[0], $error[1]);
	}

	function Post($args, $mode = 'new') {
		list($page, $uname, $pass, $content, $publish) = array_pad($args, 5, '');

		if ($mode === 'delete') {
			$content = '';
		}

		$uname = $this->toInEnc($uname);
		$userinfo = $this->func->user_auth($uname, $pass);

		$res = '';
		$error = $this->get_error();
		if ($userinfo['uid']) {

			$this->func->set_userinfo($userinfo['uid']);

			if ($this->check_blogpage($page, (($mode === 'new')? TRUE : FALSE))) {
				if (is_string($content)) {
					$content['description'] = $content;
				}
				$title = isset($content['title'])? $this->toInEnc($content['title']) : '';
				$description = $this->toInEnc($content['description']);
				if ($description || $mode === 'delete') {
					if ($mode === 'new') {
						$dateObj = isset($content['dateCreated']) ? $content['dateCreated'] : '';
						$time = $this->get_time($dateObj);

						$date = date('Y-m-d', $time);
						$page .= '/' . $date;

						$base = $page;
						$i = 1;
						while($this->func->is_page($page)) {
							$page = $base . '-' . $i++;
						}
					} else if ($mode === 'edit') {
						$dateObj = isset($content['dateCreated']) ? $content['dateCreated'] : '';
						$time = $this->get_time($dateObj);
					}
					if ($this->func->check_editable($page, FALSE, FALSE)) {

						//$this->root->post['page'] = $this->root->vars['page'] = $page;

						if ($this->config['br2lf']) {
							$description = preg_replace('#<br[^>]*?>#', "\n", $description);
						}
						if ($this->config['striptags']) {
							$description = strip_tags($description);
							$description = $this->func->unhtmlspecialchars($description);
						}

						if (! empty($content['mt_keywords'])) $this->set_tags($description, $page, $this->toInEnc($content['mt_keywords']));

						$this->func->page_write($page, $description);
						if ($mode === 'edit') {
							$this->func->touch_page($page, $time);
						}
						if ($mode === 'new') {
							$res = $this->toUTF8($page);
						} else {
							$res = true;
						}
					} else {
						$error = $this->get_error(807);
					}
				} else {
					$error = $this->get_error(804);
				}
			} else {
				$error = $this->get_error(803);
			}
		} else {
			$error = $this->get_error(801);
		}

		return $res? $res : new IXR_Error($error[0], $error[1]);

	}

	function getPost($args) {
		list($page, $uname, $pass) = array_pad($args, 3, '');

		$_uname = $this->toInEnc($uname);
		$page = $this->toInEnc($page);
		$userinfo = $this->func->user_auth($_uname, $pass);

		$res = '';
		$error = $this->get_error();
		if ($userinfo['uid']) {
			if ($this->func->check_editable($page, FALSE, FALSE)) {
				$link = $this->func->get_page_uri($page, TRUE);
				$res = $this->get_item($page, $uname);
			} else {
				$error = $this->get_error(807);
			}
		} else {
			$error = $this->get_error(801);
		}
		return $res? array($res) : new IXR_Error($error[0], $error[1]);
	}

	function getRecentPosts($args, $type='') {
		list($page, $uname, $pass, $max) = array_pad($args, 4, '');

		$uname = $this->toInEnc($uname);
		$userinfo = $this->func->user_auth($uname, $pass);
		$max = intval($max);

		$res = '';
		$error = $this->get_error();
		if ($userinfo['uid']) {
			$pages = $this->func->get_existpages(FALSE, $page . '/', array('where' => 'uid=\''.$userinfo['uid'].'\'', 'limit' => $max, 'order' => ' ORDER BY editedtime DESC'));
			$res = array();
			foreach($pages as $_page) {
				$res[] = $this->get_item($_page, $uname, $type);
			}
		} else {
			$error = $this->get_error(801);
		}
		return $res? $res : new IXR_Error($error[0], $error[1]);
	}

	function return_empty($args) {
		return array();
	}

	function get_item($page, $uname=NULL, $type='') {
		if (is_null($uname)) {
			$pginfo = $this->get_pginfo($page);
			$uname = $this->func->unhtmlspecialchars($pginfo('uname'));
		}
		$res = array(
			'userid' => $this->toUTF8($uname),
			'dateCreated' => $this->make_iso8601(filemtime($this->func->get_filename($page))),
			'postid' => $this->toUTF8($page),
			'title' => $this->toUTF8($this->func->get_heading($page))
		);
		if ($type !== 'title') {
			$link = $this->func->get_page_uri($page, TRUE);
			$src = $this->func->remove_pginfo($this->func->get_source($page, TRUE, TRUE));
			$description = $this->toUTF8($src);
			$res['description'] = $description;
			$res['link'] = $link;
			$res['permaLink'] = $link;
			$res['mt_keywords'] = $this->toUTF8($this->get_tags($src, $page));

		}
		return $res;
	}

	function check_blogpage($page, $make=FALSE) {
		if (! $this->func->is_pagename($page) || ! $this->func->check_editable($page . '/a', FALSE, FALSE)) {
			return FALSE;
		}
		if ($make && ! $this->func->is_page($page)) {
			if (! $src = $this->func->auto_template($page)) {
				$src = $this->config['BlogPageTemplate'];
			}
			$this->func->page_write($page, $src);
		}
		return TRUE;
	}

	function get_time($obj) {
		if (is_object($obj) && is_a($obj, 'IXR_Date')) {
			$time = $obj->getTimestamp() + date('Z');
		} else {
			$time = time();
		}
		return $time;
	}

	function make_iso8601($time) {
		return substr(str_replace('-', '', gmdate(DATE_ISO8601, $time)), 0, 17) . 'Z';
	}

	function get_tags($postdata, $page) {
		$params = '';
		$p_tag = $this->func->get_plugin_instance('tag');
		if (is_object($p_tag)) {
			$params = $p_tag->get_tags($postdata, $page);
		}
		return $params;
	}

	function set_tags(& $postdata, $page, $tags) {
		$p_tag = $this->func->get_plugin_instance('tag');
		if (is_object($p_tag)) {
			$p_tag->set_tags($postdata, $page, $tags);
		}
	}

	function get_error($code = 0) {
		$code = intval($code);
		switch($code) {
			case 801:
				$msg = 'Login Error.';
				break;
			case 802:
				$msg = 'No Such Blog.';
				break;
			case 803:
				$msg = 'Page name is wrong.';
				break;
			case 804:
				$msg = 'Cannot add Empty Items.';
				break;
			case 806:
				$msg = 'No Such Item.';
				break;
			case 807:
				$msg = 'Not Allowed to Alter Item.';
				break;
			default:
				$code = 999;
				$msg = 'Unknown error.';
		}
		return array($code, $msg);
	}

	function toUTF8($str) {
		return mb_convert_encoding($str, 'UTF-8', $this->cont['SOURCE_ENCODING']);
	}

	function toInEnc($str){
		return mb_convert_encoding($str, $this->cont['SOURCE_ENCODING'], 'UTF-8');
	}
}

function blogger_newPost($args) {
	$p =& $GLOBALS['xpWikiXmlRpcObj'];
	array_shift($args);
	return $p->Post($args);
}

function blogger_editPost($args) {
	$p =& $GLOBALS['xpWikiXmlRpcObj'];
	array_shift($args);
	return $p->Post($args, 'edit');
}

function blogger_deletePost($args) {
	$p =& $GLOBALS['xpWikiXmlRpcObj'];
	array_shift($args);
	return $p->Post($args, 'delete');
}

function blogger_getRecentPosts($args) {
	$p =& $GLOBALS['xpWikiXmlRpcObj'];
	array_shift($args);
	return $p->getRecentPosts($args, 'blogger');
}

function blogger_getUsersBlogs($args) {
	$p =& $GLOBALS['xpWikiXmlRpcObj'];
	return $p->getUsersBlogs($args);
}

function blogger_getUserInfo($args) {
	$p =& $GLOBALS['xpWikiXmlRpcObj'];
	return $p->getUserInfo($args);
}

function metaWeblog_newPost($args) {
	$p =& $GLOBALS['xpWikiXmlRpcObj'];
	return $p->Post($args);
}

function metaWeblog_editPost($args) {
	$p =& $GLOBALS['xpWikiXmlRpcObj'];
	return $p->Post($args, 'edit');
}

function metaWeblog_getPost($args) {
	$p =& $GLOBALS['xpWikiXmlRpcObj'];
	return $p->getPost($args);
}

function metaWeblog_getRecentPosts($args) {
	$p =& $GLOBALS['xpWikiXmlRpcObj'];
	return $p->getRecentPosts($args);
}

function mt_getRecentPostTitles($args) {
	$p =& $GLOBALS['xpWikiXmlRpcObj'];
	return $p->getRecentPosts($args, 'title');
}

function return_empty($args) {
	$p =& $GLOBALS['xpWikiXmlRpcObj'];
	return $p->return_empty($args);
}

function return_true($args) {
	return TRUE;
}