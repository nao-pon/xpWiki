// Init.
var wikihelper_WinIE=(document.all&&!window.opera&&navigator.platform=="Win32");
var wikihelper_Gecko=(navigator && navigator.userAgent && navigator.userAgent.indexOf("Gecko/") != -1);
var wikihelper_Is_pukiwikimod = (document.URL.indexOf(wikihelper_root_url,0) == 0);

var wikihelper_elem;
var wikihelper_mapLoad=0;
var wikihelper_initLoad=0;

// cookie
var wikihelper_adv = wikihelper_load_cookie("__whlp");
if (wikihelper_adv) wikihelper_save_cookie("__whlp",wikihelper_adv,90,"/");

// Common function.
function open_mini(URL,width,height){
	aWindow = window.open(URL, "mini", "toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=yes,resizable=no,width="+width+",height="+height);
}

function wikihelper_show_fontset_img()
{
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
		'<div id="wikihelper_base"><'+'/'+'div>';
		//document.write(map);
		var src = document.createElement('div');
		src.innerHTML = map;
		document.body.insertBefore(src,document.body.firstChild);
	}

	// Helper image tag set
	var wikihelper_adv_tag = '';
	if (wikihelper_adv == "on")
	{
		wikihelper_adv_tag += '<span style="cursor:pointer;">';
		
		if (wikihelper_Is_pukiwikimod) wikihelper_adv_tag +=
	'<img src="'+wikihelper_root_url+'image/clip.png" width="18" height="16" border="0" title="'+wikihelper_msg_attach+'" alt="&amp;attachref;" onClick="javascript:wikihelper_ins(\'&attachref();\'); return false;" '+'/'+'>';
		
		 wikihelper_adv_tag +=
	'<img src="'+wikihelper_root_url+'image/ncr.gif" width="22" height="16" border="0" title="'+wikihelper_msg_to_ncr+'" alt="'+wikihelper_msg_to_ncr+'" onClick="javascript:wikihelper_charcode(); return false;" '+'/'+'>'+
	'<img src="'+wikihelper_root_url+'image/br.gif" width="18" height="16" border="0" title="&amp;br;" alt="&amp;br;" onClick="javascript:wikihelper_ins(\'&br;\'); return false;" '+'/'+'>'+
	'<img src="'+wikihelper_root_url+'image/iplugin.gif" width="18" height="16" border="0" title="Inline Plugin" alt="Inline Plugin" onClick="javascript:wikihelper_ins(\'&(){};\'); return false;" '+'/'+'>'+
	'<'+'/'+'span><br>';
	}

	var wikihelper_helper_img = 
	'<img src="'+wikihelper_root_url+'image/buttons.gif" width="103" height="16" border="0" usemap="#map_button" tabindex="-1" '+'/'+'>'+
	' '+
	wikihelper_adv_tag +
	'<img src="'+wikihelper_root_url+'image/colors.gif" width="64" height="16" border="0" usemap="#map_color" tabindex="-1" '+'/'+'> '+
	'<span style="cursor:pointer;">'+
	'<img src="'+wikihelper_root_url+'image/face/smile.png" width="15" height="15" border="0" title=":)" alt=":)" onClick="javascript:wikihelper_face(\':)\'); return false;" '+'/'+'>'+
	'<img src="'+wikihelper_root_url+'image/face/bigsmile.png" width="15" height="15" border="0" title=":D" alt=":D" onClick="javascript:wikihelper_face(\':D\'); return false;" '+'/'+'>'+
	'<img src="'+wikihelper_root_url+'image/face/huh.png" width="15" height="15" border="0" title=":p" alt=":p" onClick="javascript:wikihelper_face(\':p\'); return false;" '+'/'+'>'+
	'<img src="'+wikihelper_root_url+'image/face/oh.png" width="15" height="15" border="0" title="XD" alt="XD" onClick="javascript:wikihelper_face(\'XD\'); return false;" '+'/'+'>'+
	'<img src="'+wikihelper_root_url+'image/face/wink.png" width="15" height="15" border="0" title=";)" alt=";)" onClick="javascript:wikihelper_face(\';)\'); return false;" '+'/'+'>'+
	'<img src="'+wikihelper_root_url+'image/face/sad.png" width="15" height="15" border="0" title=";(" alt=";(" onClick="javascript:wikihelper_face(\';(\'); return false;" '+'/'+'>'+
	'<img src="'+wikihelper_root_url+'image/face/heart.png" width="15" height="15" border="0" title="&amp;heart;" alt="&amp;heart;" onClick="javascript:wikihelper_face(\'&amp;heart;\'); return false;" '+'/'+'>'+
	'<'+'/'+'span>';

	var str =  wikihelper_helper_img + '<small> [ <a href="#" onClick="javascript:wikihelper_show_hint(); return false;">' + wikihelper_msg_hint + '<'+'/'+'a> ]<'+'/'+'small>';
	
	if (wikihelper_adv == "on")
	{
		str = str + '<small> [ <a href="#" title="'+wikihelper_msg_to_easy_t+'" onClick="javascript:wikihelper_adv_swich(); return false;">' + 'Easy' + '<'+'/'+'a> ]<'+'/'+'small>';
	}
	else
	{
		str = str + '<small> [ <a href="#" title="'+wikihelper_msg_to_adv_t+'" onClick="javascript:wikihelper_adv_swich(); return false;">' + 'Adv.' + '<'+'/'+'a> ]<'+'/'+'small>';
	}
	
	str += '<small> [<a href="#" title="Close" onClick="javascript:wikihelper_hide_helper(); return false;">x</a>]<'+'/'+'small>';
	document.getElementById("wikihelper_base").innerHTML = str;
	// document.write(str);
	
}

function wikihelper_adv_swich()
{
	if (wikihelper_adv == "on")
	{
		wikihelper_adv = "off";
		//wikihelper_ans = confirm(wikihelper_msg_to_easy);
	}
	else
	{
		wikihelper_adv = "on";
		//wikihelper_ans = confirm(wikihelper_msg_to_adv);
	}
	wikihelper_save_cookie("__whlp",wikihelper_adv,90,"/");
	wikihelper_show_fontset_img();
	wikihelper_elem.focus();
	//wikihelper_setActive(wikihelper_elem);
	//if (wikihelper_ans) window.location.reload();
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
	if(arg)
	{
		cookieData = document.cookie + ";" ;
		arg = escape(arg);
		startPoint1 = cookieData.indexOf(arg);
		startPoint2 = cookieData.indexOf("=",startPoint1) +1;
		endPoint = cookieData.indexOf(";",startPoint1);
		if(startPoint2 < endPoint && startPoint1 > -1 &&startPoint2-startPoint1 == arg.length+1)
		{
			cookieData = cookieData.substring(startPoint2,endPoint);
			cookieData = unescape(cookieData);
			return cookieData
		}
	}
	return false
}

function wikihelper_area_highlite(id,mode)
{
	if (mode)
	{
		document.getElementById(id).className = "area_on";
	}
	else
	{
		document.getElementById(id).className = "area_off";
	}
	
}

function wikihelper_check(f)
{
	if (wikihelper_elem && wikihelper_elem.type == "text")
	{
		if (!confirm(wikihelper_msg_submit))
		{
			wikihelper_elem.focus();
			return false;
		}
	}
	
	for (i = 0; i < f.elements.length; i++)
	{
		oElement = f.elements[i];
		if (oElement.type == "submit" && (!oElement.name || oElement.name == "comment"))
		{
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

// Branch.
if (wikihelper_WinIE)
{
	document.write ('<scr'+'ipt type="text/javascr'+'ipt" src="' + wikihelper_root_url + 'skin/loader.php?type=js&amp;src=winie"></scr'+'ipt>');
}
else if (wikihelper_Gecko)
{
	document.write ('<scr'+'ipt type="text/javascr'+'ipt" src="' + wikihelper_root_url + 'skin/loader.php?type=js&amp;src=gecko"></scr'+'ipt>');
}
else
{
	document.write ('<scr'+'ipt type="text/javascr'+'ipt" src="' + wikihelper_root_url + 'skin/loader.php?type=js&amp;src=other"></scr'+'ipt>');
}

// Add function in 'window.onload' event.
void function()
{
	var onload = window.onload;
	window.onload = function()
	{
		if (onload) onload();
		wikihelper_initTexts();
	}
} ();

