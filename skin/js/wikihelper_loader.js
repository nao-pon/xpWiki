/*
 * easy wikihelper loader for any form.
 */
(function () {
	if (typeof(XpWiki) == 'undefined') {
		var lang;
		if(document.all) {
			lang = navigator.browserLanguage;
		} else {
			lang = navigator.language;
		}
		lang = lang.substr(0,2);
		if (! lang.match(/(en|ja)/)) {
			lang = 'en';
		}
		
		// load default.*.js
		document.write ('<script type="text/javascript" src="$wikihelper_root_url/skin/loader.php?src=default.'+lang+'.js"></script>');
	}
})();
