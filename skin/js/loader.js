//
// Created on 2007/10/03 by nao-pon http://hypweb.net/
// $Id: loader.js,v 1.6 2008/09/16 04:23:37 nao-pon Exp $
//

//// JavaScript optimizer by amachang.
//// http://d.hatena.ne.jp/amachang/20060924/1159084608
/*@cc_on
eval((function(props) {
	var code = [];
	for (var i=0; i<props.length; i++){
		var prop = props[i];
		window['_'+prop]=window[prop];
		code.push(prop+'=_'+prop)
	}
	return 'var '+code.join(',');
})('document self top parent alert setInterval clearInterval setTimeout clearTimeout'.split(' ')));
@*/
var _si_nativeSetInterval = window.setInterval;
var _si_nativeClearInterval = window.clearInterval;
var _si_intervalTime = 10;
var _si_counter = 1;
var _si_length = 0;
var _si_functions = {};
var _si_counters = {};
var _si_numbers = {};
var _si_intervalId = undefined;
var _si_loop = function() {
    var f = _si_functions, c = _si_counters, n = _si_numbers;
    for(var i in f) {
        if(!--c[i]) {
            f[i]();
            c[i] = n[i];
        }
    } 
};
window.setInterval = function(handler, time) {
    if(typeof handler == 'string')
        handler = new Function(handler);
    _si_functions[_si_counter] = handler;
    _si_counters[_si_counter] = _si_numbers[_si_counter] = Math.ceil(time / _si_intervalTime);
    if (++_si_length && !_si_intervalId) {
       _si_intervalId = _si_nativeSetInterval(_si_loop, _si_intervalTime);
    }
    return _si_counter++;
};
window.clearInterval = function(id) {
    if(_si_functions[id]) {
        delete _si_functions[id];
        delete _si_numbers[id];
        delete _si_counters[id];
        if (!--_si_length && _si_intervalId) {
            _si_nativeClearInterval(_si_intervalId);
            _si_intervalId = undefined;
        }
    }
};
//// By amachang end.

// Init.
var wikihelper_WinIE = (document.all&&!window.opera&&navigator.platform=="Win32");
var wikihelper_Gecko = (navigator.userAgent.indexOf('Gecko') > -1 && navigator.userAgent.indexOf('KHTML') == -1);
var wikihelper_Opera = !!window.opera;
var wikihelper_WebKit = (navigator.userAgent.indexOf('AppleWebKit/') > -1);

var xpwiki_scripts = '';

if (typeof(document.evaluate) != 'function') {
	xpwiki_scripts += 'xpath,';
}

// prototype.js
// script.aculo.us
// resizable.js
// xpwiki.js
// main.js
xpwiki_scripts += 'prototype,effects,dragdrop,resizable,xpwiki,main';

// Branch.
if (wikihelper_WinIE) {
	xpwiki_scripts += ',winie';
} else if (wikihelper_Gecko || wikihelper_Opera || wikihelper_WebKit) {
	xpwiki_scripts += ',basic';
} else {
	xpwiki_scripts += ',other';
}

document.write ('<script type="text/javascript" src="' + wikihelper_root_url + '/skin/loader.php?src=' + xpwiki_scripts + '.js"></script>');
