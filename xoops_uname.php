<?php

error_reporting(0);

include(dirname(__FILE__).'/include/compat.php');

$q = (isset($_GET['q']))? $_GET['q'] : "";

$dats = array();
$oq = $q = str_replace("\0","",$q);

if ($q !== "")
{
	$q = addslashes(mb_convert_encoding($q,"EUC-JP","UTF-8"));

	$where1 = " WHERE `uname` LIKE '".$q."%'";
	$where2 = " WHERE `uname` LIKE '%".$q."%' AND `uname` NOT LIKE '".$q."%'";
	$order = " ORDER BY `uname` ASC";
	$limit = 100;

	mysql_connect(XOOPS_DB_HOST, XOOPS_DB_USER, XOOPS_DB_PASS) or die(mysql_error());
	mysql_select_db(XOOPS_DB_NAME); 
	
	$query = "SELECT `uid`, `uname` FROM `".XOOPS_DB_PREFIX."_users`".$where1.$order." LIMIT ".$limit;
	
	$suggests = $tags = array();
	if ($result = mysql_query($query))
	{
		while($dat = mysql_fetch_row($result))
		{
			//$uids[] = '"'.str_replace('"','\"',$dat[0]).'"';
			$unames[] = '"'.str_replace('"','\"',$dat[1]).'['.$dat[0].']"';
		}
	}

	$count = count($uids);
	if ($count < $limit)
	{
		$query = "SELECT `uid`, `uname` FROM `".XOOPS_DB_PREFIX."_users`".$where2.$order." LIMIT ".($limit - $count);
		if ($result = mysql_query($query))
		{
			while($dat = mysql_fetch_row($result))
			{
				//$uids[] = '"'.str_replace('"','\"',$dat[0]).'"';
				$unames[] = '"'.str_replace('"','\"',$dat[1]).'['.$dat[0].']"';
			}
		}		
	}

}

$oq = '"'.str_replace('"','\"',$oq).'"';
//$ret = "this.setSuggest($oq,new Array(".join(", ",$uids)."),new Array(".mb_convert_encoding(join(", ",$unames),"UTF-8","EUC-JP")."));";
$ret = "this.setSuggest($oq,new Array(".mb_convert_encoding(join(", ",$unames),"UTF-8","EUC-JP")."));";


header ("Content-Type: text/html; charset=UTF-8");
header ("Content-Length: ".strlen($ret));
echo $ret;

?>
