//
// Created on 2007/10/03 by nao-pon http://hypweb.net/
// $Id: loader.js,v 1.2 2007/10/15 05:31:17 nao-pon Exp $
//

// Init.
var wikihelper_WinIE = (document.all&&!window.opera&&navigator.platform=="Win32");
var wikihelper_Gecko = navigator.userAgent.indexOf('Gecko') > -1 && navigator.userAgent.indexOf('KHTML') == -1;
var wikihelper_Opera = !!window.opera;

// prototype.js
document.write ('<script type="text/javascript" src="' + wikihelper_root_url + '/skin/loader.php?src=prototype.js"></script>');

// script.aculo.us
document.write ('<script type="text/javascript" src="' + wikihelper_root_url + '/skin/loader.php?src=effects.js"></script>');
document.write ('<script type="text/javascript" src="' + wikihelper_root_url + '/skin/loader.php?src=dragdrop.js"></script>');

// resizable.js
document.write ('<script type="text/javascript" src="' + wikihelper_root_url + '/skin/loader.php?src=resizable.js"></script>');

// Include main script.
document.write ('<script type="text/javascript" src="' + wikihelper_root_url + '/skin/loader.php?src=main.js"></script>');

// Branch.
if (wikihelper_WinIE) {
	document.write ('<script type="text/javascript" src="' + wikihelper_root_url + '/skin/loader.php?src=winie.js"></script>');
} else if (wikihelper_Gecko) {
	document.write ('<script type="text/javascript" src="' + wikihelper_root_url + '/skin/loader.php?src=gecko.js"></script>');
} else {
	document.write ('<script type="text/javascript" src="' + wikihelper_root_url + '/skin/loader.php?src=other.js"></script>');
}

// xpwiki.js
document.write ('<script type="text/javascript" src="' + wikihelper_root_url + '/skin/loader.php?src=xpwiki.js"></script>');
