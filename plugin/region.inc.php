<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: region.inc.php,v 1.1 2007/01/12 00:43:55 nao-pon Exp $
//

class xpwiki_plugin_region extends xpwiki_plugin {
	function plugin_region_init () {

	}
	
	function plugin_region_convert()
	{
		static $builder = array();
		if (!isset($builder[$this->xpwiki->pid])) {$builder[$this->xpwiki->pid] = 0;}
		if( $builder[$this->xpwiki->pid]==0 ) $builder[$this->xpwiki->pid] = new XpWikiRegionPluginHTMLBuilder($this->xpwiki);
	
		// static で宣言してしまったので２回目呼ばれたとき、前の情報が残っていて変な動作になるので初期化。
		$builder[$this->xpwiki->pid]->setDefaultSettings();
	
		// 引数が指定されているようなので解析
		if (func_num_args() >= 1){
			$args = func_get_args();
			// end 指定?
			if ($args[0] === 'end') {
				return '</td></tr></table>' . "\n";
			} else {
				$builder[$this->xpwiki->pid]->setDescription( array_shift($args) );
				foreach( $args as $value ){
					// opened が指定されたら初期表示は開いた状態に設定
					if( preg_match("/^open/i", $value) ){
						$builder[$this->xpwiki->pid]->setOpened();
					// closed が指定されたら初期表示は閉じた状態に設定。
					}elseif( preg_match("/^close/i", $value) ){
						$builder[$this->xpwiki->pid]->setClosed();
					}
				}
			}
		}
		// ＨＴＭＬ返却
		return $builder[$this->xpwiki->pid]->build();
	}
}
	
	
	// クラスの作り方⇒http://php.s3.to/man/language.oop.object-comparison-php4.html
class XpWikiRegionPluginHTMLBuilder
{
	var $description;
	var $isopened;
	var $scriptVarName;
	//↓ buildメソッドを呼んだ回数をカウントする。
	//↓ これは、このプラグインが生成するJavaScript内でユニークな変数名（被らない変数名）を生成するために使います
	var $callcount;

	function XpWikiRegionPluginHTMLBuilder(& $xpwiki) {
		$this->xpwiki =& $xpwiki;
		$this->root   =& $xpwiki->root;
		$this->cont   =& $xpwiki->cont;
		$this->func   =& $xpwiki->func;

		$this->callcount = 0;
		$this->setDefaultSettings();
	}
	function setDefaultSettings(){
		$this->description = "...";
		$this->isopened = false;
	}
	function setClosed(){ $this->isopened = false; }
	function setOpened(){ $this->isopened = true; }
	// convert_html()を使って、概要の部分にブラケットネームを使えるように改良。
	function setDescription($description){
		//$this->description = convert_html($description);
		$this->description = $this->func->make_link($description);
		// convert_htmlを使うと <p>タグで囲まれてしまう。Mozzilaだと表示がずれるので<p>タグを消す。
		$this->description = preg_replace( "/^<p>/i", "", $this->description);
		$this->description = preg_replace( "/<\/p>$/i", "", $this->description);
	}
	function build(){
		$this->callcount++;
		$html = array();
		// 以降、ＨＴＭＬ作成処理
		array_push( $html, $this->buildButtonHtml() );
		array_push( $html, $this->buildBracketHtml() );
		array_push( $html, $this->buildSummaryHtml() );
		array_push( $html, $this->buildContentHtml() );
		return join($html);
	}

	// ■ ボタンの部分。
	function buildButtonHtml(){
		$button = ($this->isopened) ? "-" : "+";
		// JavaScriptでsummaryrgn1、contentrgn1などといった感じのユニークな変数名を使用。かぶったら一巻の終わりです。万事休す。id指定せずオブジェクト取れるような、なんかよい方法があればいいんだけど。
		return <<<EOD
<table cellpadding=1 cellspacing=2 style="width:auto;"><tr>
<td valign=top>
	<span id=rgn_button$this->callcount style="cursor:pointer;font:normal 10px ＭＳ Ｐゴシック;border:gray 1px solid;"
	onclick="
	if(document.getElementById('rgn_summary$this->callcount').style.display!='none'){
		document.getElementById('rgn_summary$this->callcount').style.display='none';
		document.getElementById('rgn_content$this->callcount').style.display='block';
		document.getElementById('rgn_bracket$this->callcount').style.borderStyle='solid none solid solid';
		document.getElementById('rgn_button$this->callcount').innerHTML='-';
	}else{
		document.getElementById('rgn_summary$this->callcount').style.display='block';
		document.getElementById('rgn_content$this->callcount').style.display='none';
		document.getElementById('rgn_bracket$this->callcount').style.borderStyle='none';
		document.getElementById('rgn_button$this->callcount').innerHTML='+';
	}
	">$button</span>
</td>
EOD;
	}

	// ■ 展開したときの左側の囲いの部分。こんなやつ ⇒ [ 。 ボーダーで上下左をsolid。右側だけnoneにして [ に見せかける。
	function buildBracketHtml(){
		$bracketstyle = ($this->isopened) ? "border-style: solid none solid solid;" : "border-style:none;";
		return <<<EOD
<td id=rgn_bracket$this->callcount style="font-size:1pt;border:gray 1px;$bracketstyle">&nbsp;</td>
EOD;
	}

	// ■ 縮小表示しているときの表示内容。
	function buildSummaryHtml(){
		$summarystyle = ($this->isopened) ? "display:none;" : "display:block;";
		return <<<EOD
<td id=rgn_summary$this->callcount style="color:gray;border:gray 1px solid;$summarystyle">$this->description</td>
EOD;
	}

	// ■ 展開表示しているときの表示内容ヘッダ部分。ここの<td>の閉じタグは endregion 側にある。
	function buildContentHtml(){
		$contentstyle = ($this->isopened) ? "display:block;" : "display:none;";
		return <<<EOD
<td valign=top id=rgn_content$this->callcount style="$contentstyle">
EOD;
	}

}// end class XpWikiRegionPluginHTMLBuilder
?>