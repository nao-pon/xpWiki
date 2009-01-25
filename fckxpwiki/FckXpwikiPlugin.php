<?php
/*
 * Created on 2008/12/29 by nao-pon http://hypweb.net/
 * License: GPL v2 or (at your option) any later version
 * $Id: FckXpwikiPlugin.php,v 1.1 2009/01/25 01:00:37 nao-pon Exp $
 */

class FckXpwikiPlugin {
	function GetPriority() {
		return 0;
	}
	
	function PrepareRaw2XHTML($str) {
		
		return $str;
	}
	
	function PrepareXHTML2Raw($str) {
		
		return $str;
	}
}
