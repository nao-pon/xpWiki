<?php
class xpwiki_plugin_xoopsblock extends xpwiki_plugin {
	
	function plugin_xoopsblock_init() {
	// $Id: xoopsblock.inc.php,v 1.2 2006/10/23 08:11:41 nao-pon Exp $
	
	/*
	 * countdown.inc.php
	 * License: GPL
	 * Author: nao-pon http://hypweb.net
	 * XOOPS Module Block Plugin
	 *
	 * XOOPSのブロックを表示するプラグイン
	 */
	
		include_once(XOOPS_ROOT_PATH."/class/xoopsmodule.php");
		include_once(XOOPS_ROOT_PATH."/class/xoopsblock.php");
		
	}
	
	function plugin_xoopsblock_convert() {
		
		static $css_show = FALSE;
		
		list($tgt,$option1,$option2) = array_pad(func_get_args(),3,"");
		
		$tgt_bids = array();
		
		if (!$tgt || $tgt === "?") {
			$tgt = "?";
		} else { 
			foreach(explode(",", $tgt) as $_bid) {
				if (preg_match("/^\d+$/",$_bid)) {
					$tgt_bids[] = $_bid;
				}					
			}
		}
		
		$align = "left";
		$around = false;
		$width = "";
		$arg = array();
		if (preg_match("/^(left|center|right)$/i",$option2,$arg))
			$align = $arg[1];
		if (preg_match("/^(left|center|right)$/i",$option1,$arg))
			$align = $arg[1];
		if (preg_match("/^(around|float)(:?w?([\d]+%?))?$/i",$option2,$arg))
		{
			if ($arg[1]) $around = true;
			$width = (!strstr($arg[3],"%"))? $arg[3]."px" : $arg[3];
			$width = ",width:".$arg[3].";";
		}
		if (preg_match("/^(around|float)(:?w?([\d]+%?))?$/i",$option1,$arg))
		{
			if ($arg[1]) $around = true;
			$width = (!strstr($arg[3],"%"))? $arg[3]."px" : $arg[3];
			$width = " width:".$width.";";
		}
		$style = " style='float:{$align};{$width}'";
		$clear = ($around)? "" : "<div style='clear:both;'></div>";
	
		global $xoopsUser;
		$xoopsblock = new XoopsBlock();
		$xoopsgroup = new XoopsGroup();
		$arr = array();
		$side = null;
		
		if ( $xoopsUser ) {
			$arr = $xoopsblock->getAllBlocksByGroup($xoopsUser->groups());
		} else {
			$arr = $xoopsblock->getAllBlocksByGroup($this->plugin_xoopsblock_getByType("Anonymous"));
		}
		
		$ret = "";
		
		if ($tgt == "?"){
			foreach ( $arr as $myblock ) {
				$block = array();
				$block_type = (@$myblock->getVar("type"))? $myblock->getVar("type") : $myblock->getVar("block_type");
				$name = ($block_type != "C") ? $myblock->getVar("name") : $myblock->getVar("title");
				$bid = $myblock->getVar('bid');
				$ret .= "<li>(".$bid.")".$name."</li>";
			}
		} else {
			global $xoopsTpl;
			
			require_once XOOPS_ROOT_PATH.'/class/template.php';
			$xoopsTpl = new XoopsTpl();

			foreach ($tgt_bids as $bid) {
				$myblock =& new XoopsBlock($bid);
				$bcachetime = $myblock->getVar('bcachetime');
				// Only a guest enable cache. by nao-pon
				//if (empty($bcachetime)) {
				if ($bcachetime % 10 == 1)
				{
					$bcachetime_guest = TRUE;
					$bcachetime = $bcachetime - 1;
				}
				else
				{
					$bcachetime_guest = FALSE;
				}
				if (empty($bcachetime) || (is_object($xoopsUser) && $bcachetime_guest)) {
				//if (empty($bcachetime)) {
					$xoopsTpl->xoops_setCaching(0);
				} else {
					$xoopsTpl->xoops_setCaching(2);
					$xoopsTpl->xoops_setCacheTime($bcachetime);
				}
				$btpl = $myblock->getVar('template');
				if ($btpl != '') {
					if (empty($bcachetime) || !$xoopsTpl->is_cached('db:'.$btpl, 'blk_'.$myblock->getVar('bid'))) {
						//$xoopsLogger->addBlock($myblock->getVar('name'));
						$bresult = $myblock->buildBlock();
						if (!$bresult) {
							continue;
						}
						$xoopsTpl->assign_by_ref('block', $bresult);
						$bcontent = $xoopsTpl->fetch('db:'.$btpl, 'blk_'.$myblock->getVar('bid'));
						$xoopsTpl->clear_assign('block');
					} else {
					   //$xoopsLogger->addBlock($myblock->getVar('name'), true, $bcachetime);
						$bcontent = $xoopsTpl->fetch('db:'.$btpl, 'blk_'.$myblock->getVar('bid'));
					}
				} else {
					//$bid = $myblock->getVar('bid');
					if (empty($bcachetime) || !$xoopsTpl->is_cached('db:system_dummy.html', 'blk_'.$bid)) {
						//$xoopsLogger->addBlock($myblock->getVar('name'));
						$bresult = $myblock->buildBlock();
						if (!$bresult) {
							continue;
						}
						$xoopsTpl->assign_by_ref('dummy_content', $bresult['content']);
						$bcontent = $xoopsTpl->fetch('db:system_dummy.html', 'blk_'.$bid);
						$xoopsTpl->clear_assign('block');
					} else {
						//$xoopsLogger->addBlock($myblock->getVar('name'), true, $bcachetime);
						$bcontent = $xoopsTpl->fetch('db:system_dummy.html', 'blk_'.$bid);
					}
				}


				if ($bcontent) {
					$ret .= "<h5>".$myblock->getVar('title')."</h5>\n";
					$ret .= $bcontent;
				}
			}
			unset($myblock);
		}
		
		if (!$css_show) {
			$css_show = true;
			$this->root->head_precsses[] = "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"". XOOPS_URL ."/xoops.css\" />";
			//global $xoopsConfig;
			//$this->root->head_precsses[] = "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"". xoops_getcss($xoopsConfig['theme_set']) ."\" />\n";
		}
		
		if ($tgt == "?") $ret = "<ul>$ret</ul>";
		unset($xoopsblock,$xoopsgroup);
		return "<div{$style}>{$ret}</div>{$clear}";
	}
	
	function plugin_xoopsblock_getByType($type=""){
		// For XOOPS 2
		global $xoopsDB;
		$ret = array();
		$where_query = "";
		if ( !empty($type) ) {
			$where_query = " WHERE group_type='".$type."'";
		}
		$sql = "SELECT groupid FROM ".$xoopsDB->prefix("groups")."".$where_query;
		$result = $xoopsDB->query($sql);
		while ( $myrow = $xoopsDB->fetchArray($result) ) {
			$ret[] = $myrow['groupid'];
		}
		return $ret;
	}
}
?>