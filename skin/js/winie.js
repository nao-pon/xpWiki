function h_wikihelper_make_copy_button(arg)
{
	document.write ("<input class=\"copyButton\" type=\"button\" value=\"COPY\" onclick=\"h_wikihelper_doCopy('" + arg + "')\"><br />");
}

function h_wikihelper_doCopy(arg)
{
	var doc = document.body.createTextRange();
	doc.moveToElementText(document.all(arg));
	doc.execCommand("copy");
	alert(wikihelper_msg_copyed);
}

function wikihelper_pos(){
	var et = document.activeElement.type;
	if (!(et == "text" || et == "textarea"))
	{
		if (et == "submit") wikihelper_elem = null;
		return;
	}
	
	wikihelper_elem = document.activeElement;
	wikihelper_elem.caretPos = document.selection.createRange().duplicate();
}

function wikihelper_eclr(){
	wikihelper_elem = NULL;
}

function wikihelper_ins(v)
{
	if(!wikihelper_elem)
	{
		alert(wikihelper_msg_elem);
		wikihelper_elem.focus();
		return;	
	}
	
	if (v == "&(){};")
	{
		inp = prompt(wikihelper_msg_inline1, '');
		if (inp == null) {wikihelper_elem.focus();return;}
		v = "&" + inp;
		inp = prompt(wikihelper_msg_inline2, '');
		if (inp == null) {wikihelper_elem.focus();return;}
		v = v + "(" + inp + ")";
		inp = prompt(wikihelper_msg_inline3, '');
		if (inp == null) {wikihelper_elem.focus();return;}
		v = v + "{" + inp + "}";
		v = v + ";";
	}

	if (v == "&ref();") {
		var today = new Date();
		var yy = today.getYear();
		if (yy < 2000) {yy = yy+1900;}
		var mm = today.getMonth() + 1;
		if (mm < 10) {mm = "0" + mm;}
		var dd = today.getDate();
		if (dd < 10) {dd = "0" + mm;}
		var h = today.getHours();
		if (h < 10) {h = "0" + h;}
		var m = today.getMinutes();
		if (m < 10) {m = "0" + m;}
		var s = today.getSeconds();
		if (s < 10) {s = "0" + s;}
		var ms = today.getMilliseconds();
		if (ms < 10) {ms = "00" + ms;}
		else if (ms < 100) {ms = "0" + ms;}
		
		inp = prompt(wikihelper_msg_thumbsize, '');
		if (inp == null) { inp = "";}
		var size = '';
		if (inp.match(/[\d]{1,3}[^\d]+[\d]{1,3}/)) {
			size = inp.replace(/([\d]{1,3})[^\d]+([\d]{1,3})/, ",mw:$1,mh:$2");
		} else if (inp.match(/[\d]{1,3}/)) {
			size = inp.replace(/([\d]{1,3})/, ",mw:$1,mh:$1");
		}
		
		v = "&ref(UNQ_"+yy+mm+dd+h+m+s+ms+size+");";
	}
	
	wikihelper_elem.caretPos.text = v;
	wikihelper_elem.focus();
}

function wikihelper_face(v)
{
	if(!wikihelper_elem)
	{
		alert(wikihelper_msg_elem);
		wikihelper_elem.focus();
		return;	
	}
	
	if (wikihelper_elem.caretPos.offsetLeft == wikihelper_elem.createTextRange().offsetLeft)
		wikihelper_elem.caretPos.text = '&nbsp; ' + v + ' ';
	else
		wikihelper_elem.caretPos.text = ' ' + v + ' ';
	
	wikihelper_elem.focus();
}

function wikihelper_tag(v)
{
	if (!document.selection || !wikihelper_elem)
	if (!wikihelper_elem || !wikihelper_elem.caretPos)
	{
		alert(wikihelper_msg_elem);
		wikihelper_elem.focus();
		return;	
	}
	
	var str = wikihelper_elem.caretPos.text;
	if (!str)
	{
		alert(wikihelper_msg_select);
		wikihelper_elem.focus();
		return;
	}
	
	if ( v == 'size' )
	{
		var default_size = "%";
		v = prompt(wikihelper_msg_fontsize, default_size);
		if (!v) return;
		if (!v.match(/(%|pt)$/))
			v += "pt";
		if (!v.match(/\d+(%|pt)/))
			return;
	}
	if (str.match(/^&font\([^\)]*\)\{.*\};$/))
	{
		str = str.replace(/^(&font\([^\)]*)(\)\{.*\};)$/,"$1," + v.replace(/(\r\n|\r|\n)/g, "&br;") + "$2");
	}
	else
	{
		str = '&font(' + v + '){' + str.replace(/(\r\n|\r|\n)/g, "&br;") + '};';
	}
	
	wikihelper_elem.caretPos.text = str;
	wikihelper_elem.focus();
	wikihelper_pos();
}

function wikihelper_linkPrompt(v)
{
	if (!document.selection || !wikihelper_elem)
	{
		alert(wikihelper_msg_elem);
		wikihelper_elem.focus();
		return;	
	}

	var str = document.selection.createRange().text;
	if (!str)
	{
		str = prompt(wikihelper_msg_link, '');
		if (str == null) {wikihelper_elem.focus();return;}
	}
	var default_url = "http://";
	regex = "^s?https?://[-_.!~*'()a-zA-Z0-9;/?:@&=+$,%#]+$";
	var cbText = clipboardData.getData("Text");
	if(cbText && cbText.match(regex))
		default_url = cbText;
	var my_link = prompt('URL: ', default_url);
	if (my_link != null) {
		if (!document.selection.createRange().text) {
			wikihelper_elem.caretPos.text = '[[' + str + ':' + my_link + ']]';
		} else {
			document.selection.createRange().text = '[[' + str + ':' + my_link + ']]';
		}
	}
	wikihelper_elem.focus();
}

function wikihelper_charcode()
{
	if (!document.selection || !wikihelper_elem)
	{
		alert(wikihelper_msg_elem);
		wikihelper_elem.focus();
		return;	
	}

	var str = document.selection.createRange().text;
	if (!str)
	{
		alert(wikihelper_msg_select);
		wikihelper_elem.focus();
		return;
	}
	
	var j ="";
	for(var n = 0; n < str.length; n++) j += ("&#"+(str.charCodeAt(n))+";");
	str = j;
		
	document.selection.createRange().text = str;
	wikihelper_elem.focus();
}

function wikihelper_initTexts()
{
	if (wikihelper_initLoad) return;
	wikihelper_initLoad = 1;
	wikihelper_show_fontset_img();
	var oElements = document.getElementsByTagName("form");
	for (i = 0; i < oElements.length; i++)
	{
		oElement = oElements[i];
		var onkeyup = oElement.onkeyup;
		var onmouseup = oElement.onmouseup;
		oElement.onkeyup = function()
		{
			if (onkeyup) onkeyup();
			wikihelper_pos();
		};
		oElement.onmouseup = function()
		{
			if (onmouseup) onmouseup();
			wikihelper_pos();
		};
	}
	
	oElements = document.getElementsByTagName("input");
	for (i = 0; i < oElements.length; i++)
	{
		oElement = oElements[i];
		if (oElement.type == "text" || oElement.type == "submit")
		{
			var rel = String(oElement.getAttribute('rel'));
			var onfocus = oElement.onfocus;
			if (rel == "wikihelper") {
				//alert(rel);
				oElement.onfocus = function()
				{
					if (onfocus) onfocus();
					wikihelper_setActive(this);
				};
			} else {
				oElement.onfocus = function()
				{
					if (onfocus) onfocus();
					wikihelper_hide_helper();
				};
			}
		}
	}
	oElements = document.getElementsByTagName("textarea");
	for (i = 0; i < oElements.length; i++)
	{
		oElement = oElements[i];
		var rel = String(oElement.getAttribute('rel'));
		var onfocus = oElement.onfocus;
		if (rel == "wikihelper") {
			oElement.onfocus = function()
			{
				if (onfocus) onfocus();
				wikihelper_setActive(this);
			};
		} else {
			oElement.onfocus = function()
			{
				if (onfocus) onfocus();
				wikihelper_hide_helper();
			};
		}
	}
	return;
}

function wikihelper_setActive(elem)
{
	wikihelper_elem = elem;
	var offset = wikihelper_cumulativeOffset(wikihelper_elem);
	var helper = document.getElementById("wikihelper_base");
	helper.style.left = offset[0] + "px";
	helper.style.top = ( offset[1] - helper.offsetHeight - 1 ) + "px";
	oElements = document.getElementsByTagName("select");
	for (i = 0; i < oElements.length; i++)
	{
		oElement = oElements[i];
		oElement.style.visibility = "hidden";
	}
}

function wikihelper_show_hint()
{
	alert(wikihelper_msg_winie_hint_text);
	
	if (wikihelper_elem != null) wikihelper_elem.focus();
}
