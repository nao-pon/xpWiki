// Init.
var wikihelper_WinIE=(document.all&&!window.opera&&navigator.platform=="Win32");
var wikihelper_Gecko=(navigator && navigator.userAgent && navigator.userAgent.indexOf("Gecko/") != -1);

var wikihelper_elem;
var wikihelper_mapLoad=0;
var wikihelper_initLoad=0;

var xpwiki_area_edit_var = new Object();
xpwiki_area_edit_var["id"] = '';
xpwiki_area_edit_var["html"] = '';
xpwiki_area_edit_var['mode'] = '';

// cookie
var wikihelper_adv = wikihelper_load_cookie("__whlp");
if (wikihelper_adv) wikihelper_save_cookie("__whlp",wikihelper_adv,90,"/");

function wikihelper_show_fontset_img()
{
	var str = '<small> [&nbsp;<a href="#" onClick="javascript:wikihelper_show_hint(); return false;">' + wikihelper_msg_hint + '<'+'/'+'a>&nbsp;]<'+'/'+'small>';
	
	if (wikihelper_adv == "on") {
		str = str + '<small> [&nbsp;<a href="#" title="'+wikihelper_msg_to_easy_t+'" onClick="javascript:wikihelper_adv_swich(); return false;">' + 'Easy' + '<'+'/'+'a>&nbsp;]<'+'/'+'small>';
	} else {
		str = str + '<small> [&nbsp;<a href="#" title="'+wikihelper_msg_to_adv_t+'" onClick="javascript:wikihelper_adv_swich(); return false;">' + 'Adv.' + '<'+'/'+'a>&nbsp;]<'+'/'+'small>';
	}
	
	str += ' <a href="#" title="Close" onClick="javascript:wikihelper_hide_helper(); return false;"><img src="$wikihelper_root_url/skin/loader.php?src=close.gif" border="0" alt="Close" '+'/'+'><'+'/'+'a>';

	if (!wikihelper_mapLoad)
	{
		wikihelper_mapLoad = 1;
		var map='<map name="map_button">'+
			'<area shape="rect" coords="0,0,22,16" title="URL" alt="URL" href="#" onClick="javascript:wikihelper_linkPrompt(\'url\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="24,0,40,16" title="B" alt="B" href="#" onClick="javascript:wikihelper_tag(\'b\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="43,0,59,16" title="I" alt="I" href="#" onClick="javascript:wikihelper_tag(\'i\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="62,0,79,16" title="U" alt="U" href="#" onClick="javascript:wikihelper_tag(\'u\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="81,0,103,16" title="SIZE" alt="SIZE" href="#" onClick="javascript:wikihelper_tag(\'size\'); return false;" '+'/'+'>'+
			'<'+'/'+'map>'+
			'<map name="map_color">'+
			'<area shape="rect" coords="0,0,8,8" title="Black" alt="Black" href="#" onClick="javascript:wikihelper_tag(\'Black\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="8,0,16,8" title="Maroon" alt="Maroon" href="#" onClick="javascript:wikihelper_tag(\'Maroon\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="16,0,24,8" title="Green" alt="Green" href="#" onClick="javascript:wikihelper_tag(\'Green\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="24,0,32,8" title="Olive" alt="Olive" href="#" onClick="javascript:wikihelper_tag(\'Olive\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="32,0,40,8" title="Navy" alt="Navy" href="#" onClick="javascript:wikihelper_tag(\'Navy\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="40,0,48,8" title="Purple" alt="Purple" href="#" onClick="javascript:wikihelper_tag(\'Purple\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="48,0,55,8" title="Teal" alt="Teal" href="#" onClick="javascript:wikihelper_tag(\'Teal\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="56,0,64,8" title="Gray" alt="Gray" href="#" onClick="javascript:wikihelper_tag(\'Gray\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="0,8,8,16" title="Silver" alt="Silver" href="#" onClick="javascript:wikihelper_tag(\'Silver\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="8,8,16,16" title="Red" alt="Red" href="#" onClick="javascript:wikihelper_tag(\'Red\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="16,8,24,16" title="Lime" alt="Lime" href="#" onClick="javascript:wikihelper_tag(\'Lime\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="24,8,32,16" title="Yellow" alt="Yellow" href="#" onClick="javascript:wikihelper_tag(\'Yellow\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="32,8,40,16" title="Blue" alt="Blue" href="#" onClick="javascript:wikihelper_tag(\'Blue\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="40,8,48,16" title="Fuchsia" alt="Fuchsia" href="#" onClick="javascript:wikihelper_tag(\'Fuchsia\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="48,8,56,16" title="Aqua" alt="Aqua" href="#" onClick="javascript:wikihelper_tag(\'Aqua\'); return false;" '+'/'+'>'+
			'<area shape="rect" coords="56,8,64,16" title="White" alt="White" href="#" onClick="javascript:wikihelper_tag(\'White\'); return false;" '+'/'+'>'+
			'<'+'/'+'map>'+
			'<div id="wikihelper_base" style="position:absolute;top:-1000px;left:-1000px;"><'+'/'+'div>';
		
		var src;
		
		src = document.createElement('link');
		src.href = '$wikihelper_root_url/skin/loader.php?src=wikihelper.css';
		src.rel  = 'stylesheet';
		src.type = 'text/css';
		document.body.appendChild(src);

		src = document.createElement('div');
		src.innerHTML = map;
		src.zIndex = 1000;
		document.body.appendChild(src);
	}

	// Helper image tag set
	var wikihelper_adv_tag = '';
	if (wikihelper_adv == "on")
	{
		wikihelper_adv_tag =
			'<img src="$wikihelper_root_url/image/clip.png" width="18" height="16" border="0" title="'+wikihelper_msg_attach+'" alt="&amp;ref;" onClick="javascript:wikihelper_ins(\'&ref();\'); return false;" '+'/'+'>'+
			'<img src="$wikihelper_root_url/image/ncr.gif" width="22" height="16" border="0" title="'+wikihelper_msg_to_ncr+'" alt="'+wikihelper_msg_to_ncr+'" onClick="javascript:wikihelper_charcode(); return false;" '+'/'+'>'+
			'<img src="$wikihelper_root_url/image/br.gif" width="18" height="16" border="0" title="&amp;br;" alt="&amp;br;" onClick="javascript:wikihelper_ins(\'&br;\'); return false;" '+'/'+'>'+
			'<img src="$wikihelper_root_url/image/iplugin.gif" width="18" height="16" border="0" title="Inline Plugin" alt="Inline Plugin" onClick="javascript:wikihelper_ins(\'&(){};\'); return false;" '+'/'+'>';
	}

	var wikihelper_helper_img = 
		'<img src="$wikihelper_root_url/image/buttons.gif" width="103" height="16" border="0" usemap="#map_button" tabindex="-1" '+'/'+'>'+
		' '+
		wikihelper_adv_tag +
		' '+
		'<img src="$wikihelper_root_url/image/colors.gif" width="64" height="16" border="0" usemap="#map_color" tabindex="-1" '+'/'+'> '+
		str+
		'<br '+'/'+'>';
	
	if (wikihelper_adv == "on") {
		wikihelper_helper_img += $face_tag_full '';
	} else {
		wikihelper_helper_img += $face_tag '';
	}

	document.getElementById("wikihelper_base").innerHTML = wikihelper_helper_img;
}

function wikihelper_adv_swich()
{
	if (wikihelper_adv == "on")	{
		wikihelper_adv = "off";
	} else {
		wikihelper_adv = "on";
	}
	wikihelper_save_cookie("__whlp",wikihelper_adv,90,"/");
	wikihelper_show_fontset_img();
	wikihelper_elem.focus();
}

function wikihelper_save_cookie(arg1,arg2,arg3,arg4){ //arg1=dataname arg2=data arg3=expiration days
	if(arg1&&arg2)
	{
		if(arg3)
		{
			xDay = new Date;
			xDay.setDate(xDay.getDate() + eval(arg3));
			xDay = xDay.toGMTString();
			_exp = ";expires=" + xDay;
		}
		else
		{
			_exp ="";
		}
		if(arg4)
		{
			_path = ";path=" + arg4;
		}
		else
		{
			_path= "";
		}
		document.cookie = escape(arg1) + "=" + escape(arg2) + _exp + _path +";";
	}
}

function wikihelper_load_cookie(arg){ //arg=dataname
	if(arg) {
		cookieData = document.cookie + ";" ;
		arg = escape(arg);
		startPoint1 = cookieData.indexOf(arg);
		startPoint2 = cookieData.indexOf("=",startPoint1) +1;
		endPoint = cookieData.indexOf(";",startPoint1);
		if(startPoint2 < endPoint && startPoint1 > -1 &&startPoint2-startPoint1 == arg.length+1) {
			cookieData = cookieData.substring(startPoint2,endPoint);
			cookieData = unescape(cookieData);
			return cookieData
		}
	}
	return false
}

function wikihelper_area_highlite(id,mode) {
	if (mode) {
		document.getElementById(id).className = "area_on";
	} else {
		document.getElementById(id).className = "area_off";
	}
	
}

function wikihelper_check(f) {
	if (wikihelper_elem && wikihelper_elem.type == "text") {
		if (!confirm(wikihelper_msg_submit)) {
			wikihelper_elem.focus();
			return false;
		}
	}
	
	for (i = 0; i < f.elements.length; i++) {
		oElement = f.elements[i];
		if (oElement.type == "submit" && (!oElement.name || oElement.name == "comment")) {
			oElement.disabled = true;
		}
	}
	
	return true;

}

function  wikihelper_cumulativeOffset(element) {
	var valueT = 0, valueL = 0;
	do {
		valueT += element.offsetTop  || 0;
		valueL += element.offsetLeft || 0;
		element = element.offsetParent;
	} while (element);
	return [valueL, valueT];
}


function wikihelper_hide_helper() {
	var helper = document.getElementById("wikihelper_base");
	helper.style.left = "-1000px";
	helper.style.top =  "-1000px";
	if (wikihelper_WinIE) {
		oElements = document.getElementsByTagName("select");
		for (i = 0; i < oElements.length; i++)
		{
			oElement = oElements[i];
			oElement.style.visibility = "";
		}
	}
}

function xpwiki_now_loading(mode) {
	if (mode) {
		if (!$("xpwiki_loading")) {
			// HTML BODYオブジェクト取得
			var objBody = document.getElementsByTagName("body").item(0);
			
			// 背景半透明オブジェクト作成
			var objBack = document.createElement("div");
			objBack.setAttribute('id', 'xpwiki_loading');
			Element.setStyle(objBack, {'display': 'none'});
			Element.setStyle(objBack, {'position': 'absolute'});
			Element.setStyle(objBack, {'z-index': 90});
			Element.setStyle(objBack, {'text-align': 'center'});
			Element.setStyle(objBack, {'background-color': 'black'});
			Element.setStyle(objBack, {'filter': 'alpha(opacity=50);'});		// IE
			Element.setStyle(objBack, {'-moz-opacity': '0.5'});		// FF
			
			objBody.appendChild(objBack);
		}
		
		var windowTop;
		var windowLeft;
		var windowWidht;
		var windowHeight;
		
		windowTop = document.documentElement.scrollTop || document.body.scrollTop || 0;
		windowLeft = document.documentElement.scrollLeft || document.body.scrollLeft || 0;
		if(wikihelper_WinIE) {
			windowWidth = document.body.scrollWidth || document.documentElement.scrollWidth || 0;
			windowHeight = document.body.scrollHeight || document.documentElement.scrollHeight || 0;
		}
		else {
			windowWidth = document.documentElement.scrollWidth || document.body.scrollWidth || 0;
			windowHeight = document.documentElement.scrollHeight || document.body.scrollHeight || 0;
		}
		
		var objBack = $('xpwiki_loading');
		objBack.style.top = 0;
		objBack.style.left = 0;
		objBack.style.width = windowWidth + "px";
		objBack.style.height = windowHeight + "px";

		Element.show('xpwiki_loading');
	} else {
		Element.hide('xpwiki_loading');
	}
}

function xpwiki_area_edit(url, id) {
	if (id) {
		if (xpwiki_area_edit_var["id"]) {
			$(xpwiki_area_edit_var["id"]).innerHTML = xpwiki_area_edit_var["html"];
		}
		wikihelper_area_highlite(id, 0);
		xpwiki_area_edit_var["id"] = id;
	} else {
		xpwiki_area_edit_var["id"] = 'xpwiki_body';
		id = '';
	}

	xpwiki_now_loading(true);
	
	// ページ情報を読込み反映する
	var pars = '';
	pars += 'cmd=edit';
	if (id) pars += '&paraid=' + encodeURIComponent(id);
	pars += '&ajax=1';
	
	var myAjax = new Ajax.Request(
		url, 
		{
			method: 'get',
			parameters: pars,
			onComplete: xpwiki_area_edit_show
		});
	return false;
}

function xpwiki_area_edit_show(orgRequest) {
	xpwiki_area_edit_var["html"] = $(xpwiki_area_edit_var["id"]).innerHTML;
	var xmlRes = orgRequest.responseXML;
	if(xmlRes.getElementsByTagName("editform")[0].firstChild) {
		$(xpwiki_area_edit_var['id']).innerHTML = xmlRes.getElementsByTagName("editform")[0].firstChild.nodeValue;
		wikihelper_initTexts($(xpwiki_area_edit_var["id"]));
		location.hash = xpwiki_area_edit_var["id"];
	}
	xpwiki_now_loading(false);
}

function xpwiki_area_edit_submit(url) {
	xpwiki_now_loading(true);
	var frm = $('xpwiki_edit_form');
	var re = /input|textarea|select/i;
	var tag = '';
	var postdata = '';
	
	for (var i = 0; i < frm.length; i++ ) {
		var child = frm[i];
		tag = String(child.tagName);
		if (tag.match(re)) {
			if (child.type == 'checkbox') {
				if (child.checked) {
					if (postdata!='') postdata += '&';
					postdata += encodeURIComponent(child.name) +
						'=' + encodeURIComponent(child.value);
				}
			} else {
				if (postdata!='') postdata += '&';
				postdata += encodeURIComponent(child.name) +
					'=' + encodeURIComponent(child.value);
			}
		}
	}
	if (xpwiki_area_edit_var['mode'] == 'preview') {
		postdata = postdata.replace(/&write=[^&]+/,'');
	} else {
		postdata = postdata.replace(/&preview=[^&]+/,'');
	}
	postdata += '&ajax=1';
	
	var myAjax = new Ajax.Request(
		url, 
		{
			method: 'post',
			parameters: postdata,
			onComplete: xpwiki_area_edit_post
		});
	
	return false;
}

function xpwiki_area_edit_post(orgRequest) {
	xpwiki_now_loading(false);
	var xmlRes = orgRequest.responseXML;
	if(xmlRes.getElementsByTagName("xpwiki")[0].firstChild) {
		var item = xmlRes.getElementsByTagName("xpwiki")[0];
		var str = item.getElementsByTagName("content")[0].firstChild.nodeValue;
		xpwiki_area_edit_var['mode'] = item.getElementsByTagName("mode")[0].firstChild.nodeValue;
		if (xpwiki_area_edit_var['mode'] == 'write') {
			$('xpwiki_body').innerHTML = str;
		} else if (xpwiki_area_edit_var['mode'] == 'delete') {
			$('xpwiki_body').innerHTML = str;
			location.href = item.getElementsByTagName("url")[0].firstChild.nodeValue;
		}else {
			$(xpwiki_area_edit_var['id']).innerHTML = str;
		}
		wikihelper_initTexts($(xpwiki_area_edit_var["id"]));
		location.hash = xpwiki_area_edit_var["id"];
	}
	if (xpwiki_area_edit_var['mode'] == 'write') {
		xpwiki_area_edit_var["id"] = '';
		xpwiki_area_edit_var['mode'] = '';
	}
}

function xpwiki_area_edit_cancel() {
	if (xpwiki_area_edit_var["id"]) {
		$(xpwiki_area_edit_var["id"]).innerHTML = xpwiki_area_edit_var["html"];
		location.hash = xpwiki_area_edit_var["id"];
	}
	xpwiki_area_edit_var["id"] = "";
	xpwiki_area_edit_var['mode'] = '';
	return false;
}

// Branch.
if (wikihelper_WinIE) {
	document.write ('<scr'+'ipt type="text/javascr'+'ipt" src="$wikihelper_root_url/skin/loader.php?src=winie.js"></scr'+'ipt>');
} else if (wikihelper_Gecko) {
	document.write ('<scr'+'ipt type="text/javascr'+'ipt" src="$wikihelper_root_url/skin/loader.php?src=gecko.js"></scr'+'ipt>');
} else {
	document.write ('<scr'+'ipt type="text/javascr'+'ipt" src="$wikihelper_root_url/skin/loader.php?src=other.js"></scr'+'ipt>');
}

// Add function in 'window.onload' event.
void function() {
	var onload = window.onload;
	window.onload = function() {
		if (onload) onload();
		wikihelper_initTexts();
	}
} ();
