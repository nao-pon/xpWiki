<?php
$filter = array(
	'url_i18n' => 'XpwikiPluginMake_linkAmazonAssociatesLink::url_i18n'
);

if (!class_exists('XpwikiPluginMake_linkAmazonAssociatesLink', false)) {
	class XpwikiPluginMake_linkAmazonAssociatesLink
	{
		public static function url_i18n(&$args) {
			$self =& $args[0];
			$moreTag =& $args[1];
			$rel =& $args[2];
			$class =& $args[3];
			$target =& $args[4];
			$title =& $args[5];
			$img =& $args[6];
			$title = urldecode($title);
			if ($self->cont['SOURCE_ENCODING'] !== 'UTF-8') {
				$title = mb_convert_encoding($title, $self->cont['SOURCE_ENCODING'], 'UTF-8');
			}
			if ($self->root->amazon_AssociateTag && strpos($self->name, 'http://www.amazon.co.jp/') === 0) {
				if (preg_match('#/dp?/([0-9A-Za-z]{10})\b#', $self->name, $m)) {
					$self->name = 'http://www.amazon.co.jp/gp/product/'.$m[1].'/ref=as_li_ss_tl?ie=UTF8&camp=247&creative=7399&creativeASIN='.$m[1].'&linkCode=as2&tag=' . $self->root->amazon_AssociateTag;
					$moreTag .= '<img src="http://ir-jp.amazon-adsystem.com/e/ir?t='.$self->root->amazon_AssociateTag.'&l=as2&o=9&a='.$m[1].'" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />';
				}
			}
		}
	}
}
