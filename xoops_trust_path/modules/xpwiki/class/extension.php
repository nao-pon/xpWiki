<?php
class XpWikiExtension {
	public function XpWikiExtension($xpwiki) {
		return self::__construct($xpwiki);
	}

	public function __construct($xpwiki) {
		$this->xpwiki = & $xpwiki;
		$this->root   = & $xpwiki->root;
		$this->cont   = & $xpwiki->cont;
		$this->func   = & $xpwiki->func;
	}
}
