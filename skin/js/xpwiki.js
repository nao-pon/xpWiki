var XpWiki = {
	Version: '20071011',
	
	MyUrl: XpWikiModuleUrl,
	EncHint: XpWikiEncHint,
	
	PopupDiv: null,
	
	PopupTop:    '10%',
	PopupBottom: '',
	PopupLeft:   '10px',
	PopupRight:  '',
	PopupHeight: '80%',
	PopupWidth:  '300px',

	dir: '',
	page: '',
	title: '',
	
	isIE7: (typeof(document.documentElement.style.msInterpolationMode) != "undefined"),
	
	initPopupDiv: function (arg) {

		if (!$('XpWikiPopup')) {
			
			// base
			this.PopupDiv = document.createElement('div');
			this.PopupDiv.id = 'XpWikiPopup';
			Element.setStyle(this.PopupDiv,{
				position: 'fixed',
				overflow: 'hidden',
				marginRight: '5px',
				marginBottom: '5px',
				backgroundColor: 'white',
				zIndex: '1000'
			});
			
			// body (iframe)
			var elem = document.createElement('iframe');
			elem.id = 'XpWikiPopupBody';
			elem.src = '';
			Element.setStyle(elem,{
				position: 'absolute',
				top: '0px',
				left: '0px',
				margin: '0px',
				padding: '0px',
				overflow: 'auto',
				border: 'none',
				width: '100%',
				height: '100%'
			});
			this.PopupDiv.appendChild(elem);

			// cover for event
			var elem = document.createElement('div');
			elem.id = 'XpWikiPopupCover';
			Element.setStyle(elem,{
				position: 'absolute',
				top: '0px',
				left: '0px',
				margin: '0px',
				padding: '0px',
				overflow: 'hidden',
				border: 'none',
				width: '100%',
				height: '100%',
				zIndex: '10000'
			});
			this.PopupDiv.appendChild(elem);
			
			// header
			elem = document.createElement('div');
			elem.id = 'XpWikiPopupHeader';
			Element.setStyle(elem,{
				position: 'absolute',
				top: '0px',
				right: '22px',
				margin: '0px 0px 0px 0px',
				padding: '2px 3px 0px 5px',
				width: 'auto',
				height: '22px',
				fontSize: '16px',
				cursor: 'move'
			});
			elem.innerHTML = '<div style="float:right;cursor:pointer;padding-top:3px;" onclick="Element.hide(\'XpWikiPopup\');"><img src="' + this.MyUrl + '/' + this.dir + '/skin/loader.php?src=close.gif" alt="Close" title="Close"></div>' +
					'<div id="XpWikiPopupHeaderTitle" style="float:left;"></div>';
			this.PopupDiv.appendChild(elem);
			
			var objBody = $('xpwiki_body') || document.getElementsByTagName('body').item(0);
			objBody.appendChild(this.PopupDiv);

			if (!!arg.bottom) {
				this.PopupDiv.style.bottom = this.PopupBottom = arg.bottom;
			} else if (!!this.PopupBottom) {
				this.PopupDiv.style.bottom = this.PopupBottom;
			}

			if (!!arg.top) {
				this.PopupDiv.style.top = this.PopupTop = arg.top;
			} else if (!!this.PopupTop && !this.PopupBottom) {
				this.PopupDiv.style.top = this.PopupTop;
			}
			
			if (!!arg.right) {
				this.PopupDiv.style.right = this.PopupRight = arg.right;
			} else if (!!this.PopupRight) {
				this.PopupDiv.style.right = this.PopupRight
			}

			if (!!arg.left) {
				this.PopupDiv.style.left = this.PopupLeft = arg.left;
			} else if (!!this.PopupLeft && !this.PopupRight) {
				this.PopupDiv.style.left = this.PopupLeft;
			}
			
			if (!!arg.width) {
				this.PopupDiv.style.width = this.PopupWidth = arg.width;
			} else if (!!this.PopupWidth) {
				this.PopupDiv.style.width = this.PopupWidth;
			}
			
			if (!!arg.height) {
				this.PopupDiv.style.height = this.PopupHeight = arg.height;
			} else if (!!this.PopupHeight) {
				this.PopupDiv.style.height = this.PopupHeight;
			}
			
			if (!!this.PopupDiv.style.top) {
				this.PopupDiv.style.top = this.PopupTop = this.PopupDiv.offsetTop + 'px';
			}
			if (!!this.PopupDiv.style.left) {
				this.PopupDiv.style.left = this.PopupLeft = this.PopupDiv.offsetLeft + 'px'; 
			}
			
			$('XpWikiPopupBody').src = '';
			$('XpWikiPopupBody').observe("load", function(){
				$('XpWikiPopupHeaderTitle').innerHTML = this.title;
			}.bind(this));

			Element.hide('XpWikiPopupCover');
			
			new Draggable(this.PopupDiv.id, {handle:'XpWikiPopupHeader', starteffect:this.dragStart, endeffect:this.dragEnd });
			new Resizable(this.PopupDiv.id, {mode:'xy', element:'XpWikiPopupBody', starteffect:this.dragStart, endeffect:this.dragEnd });
		}
		Element.hide(this.PopupDiv);
	},
	
	dragStart: function () {
		Element.show('XpWikiPopupCover');
		if (Prototype.Browser.IE) { Element.hide('XpWikiPopupBody'); }
	},
	
	dragEnd: function () {
		if (Prototype.Browser.IE) { Element.show('XpWikiPopupBody'); }
		Element.hide('XpWikiPopupCover');
	},
	pagePopup: function (arg) {
		if (!arg.dir || !arg.page) return true;
		
		if (typeof(document.body.style.maxHeight) != 'undefined') {
			if (!!$('XpWikiPopup') && this.dir == arg.dir && this.page == arg.page) {
				Element.show(this.PopupDiv);
				return false;
			}
			
			this.dir = arg.dir;
			this.page = arg.page.replace(/(#[^#]+)?$/, '');
			var hash = arg.page.replace(/^[^#]+/, '');
			
			this.title = this.htmlspecialchars(this.page);
			
			this.initPopupDiv(arg);
			$('XpWikiPopupHeaderTitle').innerHTML = 'Now loading...';
	
			var url = this.MyUrl + '/' + this.dir + '/?cmd=read';
			url += '&page=' + encodeURIComponent(this.page);
			url += '&popup=1';
			url += '&encode_hint=' + encodeURIComponent(this.EncHint);
			url += hash;
			
			$('XpWikiPopupBody').src = url;
			Element.show(this.PopupDiv);
		} else {
			this.dir = arg.dir;
			this.page = arg.page.replace(/(#[^#]+)?$/, '');
			var hash = arg.page.replace(/^[^#]+/, '');
			
			this.title = this.htmlspecialchars(this.page);
			
			if (!window.self.name) {
				window.self.name = "xpwiki_opener";
			}
			this.window_name = window.self.name;
			
			var url = this.MyUrl + '/' + this.dir + '/?cmd=read';
			url += '&page=' + encodeURIComponent(this.page);
			url += '&popup=' + encodeURIComponent(this.window_name);
			url += '&encode_hint=' + encodeURIComponent(this.EncHint);
			url += hash;
			
			var width = '250';
			var height = '400';
			var top = '10';
			var left = '10';
		    var options = "width=" + width + ",height=" + height + ",top=" + top + ",left=" + left + "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no";

		    this.new_window = window.open(url, 'xpwiki_popup', options);
		    //this.new_window.document.title = this.title;
		    this.new_window.focus();
		}
		return false;
	},
	
	pagePopupAjax: function (arg) {
		if (!arg.dir || !arg.page) return;
		
		if (!!$('XpWikiPopup') && this.dir == arg.dir && this.page == arg.page) {
			Element.show(this.PopupDiv);
			return;
		}
		
		this.dir = arg.dir;
		this.page = arg.page;
		this.title = arg.page;
		
		if (!!arg.top) { this.PopupTop = arg.top; }
		if (!!arg.left) { this.PopupTop = arg.left; }
		if (!!arg.width) { this.PopupTop = arg.width; }
		if (!!arg.height) { this.PopupTop = arg.height; }

		this.initPopupDiv();
		
		var url = this.MyUrl + '/' + this.dir + '/?cmd=read';
		var pars = '';
		pars += 'page=' + encodeURIComponent(arg.page);
		pars += '&ajax=1';
		pars += '&encode_hint=' + encodeURIComponent(this.EncHint);
		
		var myAjax = new Ajax.Request(
			url, 
			{
				method: 'get',
				parameters: pars,
				onComplete: this.ShowPopup.bind(this)
			}
		);
		
	},
	
	ShowPopup: function (orgRequest) {
		var xmlRes = orgRequest.responseXML;
		if (xmlRes.getElementsByTagName('xpwiki').length) {
		
			var item = xmlRes.getElementsByTagName('xpwiki')[0];
			var str = item.getElementsByTagName('content')[0].firstChild.nodeValue;
			var mode = item.getElementsByTagName('mode')[0].firstChild.nodeValue;
			
			if (mode == 'read') {
				var objHead = document.getElementsByTagName('head').item(0);
				var ins;
				ins = document.createElement('div');
				Element.update(ins, item.getElementsByTagName('headPreTag')[0].firstChild.nodeValue);
				objHead.appendChild(ins);
	
				ins = document.createElement('div');
				Element.update(ins, item.getElementsByTagName('headTag')[0].firstChild.nodeValue);
				objHead.appendChild(ins);
				
				var body = item.getElementsByTagName('content')[0].firstChild.nodeValue;
				
				this.Popup(body, this.title);
			}
		}
	},
	
	Popup: function (body, title) {
		Element.setStyle(this.PopupDiv,{
			top: this.PopupTop,
			left: this.PopupLeft,
			height: this.PopupHeight,
			width: this.PopupWidth
		});
		
		$('XpWikiPopupHeaderTitle').innerHTML = title;
		$('XpWikiPopupBody').innerHTML = '<div style="margin:25px 10px 10px 10px;">' + body + '</div>';
		//wikihelper_initTexts(this.PopupDiv.id);
		Element.show(this.PopupDiv);
	},
	
	textaraWrap: function (id) {
	    var txtarea = $(id);
	    var wrap = txtarea.getAttribute('wrap');
	    if(wrap && wrap.toLowerCase() == 'off'){
	        txtarea.setAttribute('wrap', 'soft');
	        var ret = wikihelper_msg_nowrap;
	    }else{
	        txtarea.setAttribute('wrap', 'off');
	        var ret = wikihelper_msg_wrap;
	    }
	    // Fix display for mozilla
	    var parNod = txtarea.parentNode;
	    var nxtSib = txtarea.nextSibling;
	    parNod.removeChild(txtarea);
	    parNod.insertBefore(txtarea, nxtSib);
	    return ret;
	},
	
	addWrapButton: function (id) {
		var txtarea = $(id);
		
		if (typeof(txtarea.XpWiki_addWrap_done) != 'undefined') return false;
		txtarea.XpWiki_addWrap_done = true;

		var btn = document.createElement('div');
		Element.setStyle (btn, {
			'float' : 'right',
			'fontSize' : '80%',
			'padding' : '3px',
			'border' : '1px solid gray',
			'cursor' : 'pointer'	
		});
		btn.innerHTML = wikihelper_msg_nowrap;
		Event.observe(btn, 'mousedown', function(){
			this.innerHTML = XpWiki.textaraWrap(id);
		});
		var parNod = txtarea.parentNode;
		var nxtSib = txtarea.nextSibling;
		parNod.insertBefore(btn, nxtSib);
	},
	
	addCssInHead: function (filename) {
		var doload = true;
		var links = document.getElementsByTagName('link');
		for (var i=0; i<links.length; i++){
			var link = links[i];
			if (link.getAttribute('href')) {
				var href = String(link.getAttribute('href')).toLowerCase();
				if (href.match(wikihelper_root_url + '/skin/loader.php')
					&& href.match(filename)) {
					doload = false;
					break;
				}
			}
		}
		if (doload) {
			var css = document.createElement('link');
			css.href = wikihelper_root_url + '/skin/loader.php?src=' + filename;
			css.rel  = 'stylesheet';
			css.type = 'text/css';
			document.getElementsByTagName('head')[0].appendChild(css);
		}
	},
	
	faviconSetDone: false,
	faviconSet: function () {
		if (this.faviconSetDone || typeof(this.faviconSetClass) == 'undefined') return;
		
		var em = document.createElement('div');
		em.style.height = '1em';
		em.style.width = '1px';
		em.style.visibility = 'hidden';
		document.getElementsByTagName('body')[0].appendChild(em);
		var pxPerEm = em.clientHeight;
		document.getElementsByTagName('body')[0].removeChild(em);
		
		var ins_a = new Array();
		var ins_img = new Array();
		this.faviconSetDone = true;
		var time_limit = 3000; // (ms)
		time_limit += new Date().getTime();
		var Objs = document.getElementsByTagName('a');
		var n = 0;
		for (var i = 0; i < Objs.length; i++) {
			if (time_limit < new Date().getTime()) break;
			var obj = Objs[i];
			if (obj.className == this.faviconSetClass && obj.firstChild && obj.firstChild.nodeName != 'IMG') {
				var height = Element.getStyle(obj ,'fontSize');
				if (height.match(/%$/)) {
					height = parseFloat(height)/100 * pxPerEm;
				} else if (height.match(/em$/)) {
					height = parseFloat(height) * pxPerEm;
				} else {
					height = parseFloat(height);
				}
				height = Math.min(32, height) + 'px';
				var img = document.createElement('img');
				img.src = wikihelper_root_url + '/skin/loader.php?src=favicon&url=' + this.rawurlencode(obj.readAttribute("href").replace(/\?.*/, ''));
				img.alt = '';
				img.style.width = height;
				img.style.height = height;
				img.className = 'xpwikiFavicon';
				
				ins_a[n] = obj
				ins_img[n] = img; 

				n++;
			}
		}
		if (ins_a.length) {
			for (var i = 0; i < ins_a.length; i++) {
				if (typeof(this.faviconReplaceClass) == 'undefined') {
					ins_a[i].style.backgroundImage = 'none';
					ins_a[i].style.paddingLeft = "0px";
				} else {
					ins_a[i].className = this.faviconReplaceClass;
				}
				ins_a[i].insertBefore(ins_img[i], ins_a[i].firstChild);
			}
		}
	},
	
	checkUseHelper: function (obj) {
		if (!! this.UseWikihelperAtAll || obj.id.match(/^xpwiki/)) {
			return true;
		} else {
			var obj = document.getElementsByTagName('script');
			for (var i=0; i<obj.length; i++){
				if (!! obj[i].src && obj[i].src.match(/wikihelper_loader\.js$/)) {
					return true;
				}
			}
		}
		var parent;
		while(parent = obj.parentNode) {
			if (typeof(parent.className) != 'undefined') {
				if (parent.className.match(/^NoWikiHelper/)) {
					return false;
				}
				if (parent.className.match(/^xpwiki/)) {
					return true;
				}
			}
			obj = parent;
		}
		return false;
	},
	
	remakeTextArea: function () {
		var tareas = document.getElementsByTagName('body')[0].getElementsByTagName('textarea');
		for (var i=0; i<tareas.length; i++){
			if (tareas[i].style.display == 'none') continue;
			if (! tareas[i].getAttribute('rel') && ! tareas[i].getAttribute('readonly') && this.checkUseHelper(tareas[i])) {
				tareas[i].setAttribute("rel", "wikihelper");
			}
			if (!tareas[i].id) {
				tareas[i].id = 'textarea_autoid_' + i;
			}
			new Resizable(tareas[i].id, {mode:'xy'});
			this.addWrapButton(tareas[i].id);
		}
	},

	initDomExtension: function () {
		var elems = document.getElementsByTagName('body')[0].getElementsByTagName('div');
		var pres = new Array();
		var pNode;
		var inTable;
		var tocId = 0;
		var tocCond = XpWiki.cookieLoad('_xwtoc');
		for (var i=0; i<elems.length; i++){
			if (elems[i].className == 'pre' && elems[i].style.overflow == 'auto') {
				inTable = false;
				pNode = elems[i].parentNode;
				do {
					if (pNode.nodeName == 'TD') {
						inTable = true;
						break;
					}
					pNode = pNode.parentNode;
				} while(pNode);
				if (inTable && elems[i].offsetParent) {
					elems[i].style.width = '500px';
					pres.push(elems[i]);
				}
			}
			if (elems[i].className === "toc_header") {
				var toc_childlen = elems[i].parentNode.getElementsByTagName('div');
				var toc_body = null;
				for (var toc_i=0; toc_i<toc_childlen.length; toc_i++){
					if (toc_childlen[toc_i].className === "toc_body") {
						toc_body = toc_childlen[toc_i];
						toc_body.id = 'xpwiki_toc_body' + tocId;
						break;
					}
				}
				if (toc_body) {
					var toc_marker = document.createElement('span');
					toc_marker.id = 'xpwiki_toc_marker' + tocId;
					elems[i].insertBefore(toc_marker, elems[i].firstChild);
					eval( 'elems[i].onclick = function(){ XpWiki.tocToggle("' + tocId + '"); };');
					elems[i].style.cursor = 'pointer';
					this.tocSetMarker(toc_body, toc_marker);
					if (tocCond == '+') {
						this.tocToggle(tocId);
					}
					tocId++;
				}
			}
		}
		for (var i=0; i<pres.length; i++) {
			var width = pres[i].offsetParent.offsetWidth - pres[i].offsetLeft - 30;
			if (width > 0) {
				pres[i].style.width = width + 'px';
			}
		}
	},

	tocToggle: function (tocId) {
		body = $('xpwiki_toc_body' + tocId);
		marker = $('xpwiki_toc_marker' + tocId);
		Element.toggle(body);
		this.tocSetMarker(body, marker);
	},
	
	tocSetMarker: function (body, marker) {
		var cond;
		if (body.style.display === 'none') {
			marker.className = 'toc_open';
			cond = '+';
		} else {
			marker.className = 'toc_close';
			cond = '-';
		}
		marker.innerHTML = '<span>' + cond + '</span>';
		XpWiki.cookieSave('_xwtoc', cond, 90, '/');
	},
	
	htmlspecialchars: function (str) {
		return str.
		replace(/&/g,"&amp;").
		replace(/</g,"&lt;").
		replace(/>/g,"&gt;").
		replace(/"/g,"&quot;").
		replace(/'/g,"&#039;");
	},

	rawurlencode: function (str) {
		try {
			return encodeURIComponent(str)
				.replace(/!/g,  "%21")
				.replace(/'/g,  "%27")
				.replace(/\(/g, "%28")
				.replace(/\)/g, "%29")
				.replace(/\*/g, "%2A")
				.replace(/~/g,  "%7E");
		} catch(e) {
			return escape(str)
				.replace(/\+/g, "%2B")
				.replace(/\//g, "%2F")
				.replace(/@/g,  "%40");
		}
	},
	
	cookieSave: function (arg1, arg2, arg3, arg4) {
		// arg1=dataname, arg2=data, arg3=expiration days, arg4=path
		var xDay;
		var _exp;
		var _path;
		if(arg1 && arg2) {
			if (arg3) {
				xDay = new Date;
				xDay.setDate(xDay.getDate() + eval(arg3));
				xDay = xDay.toGMTString();
				_exp = ";expires=" + xDay;
			} else {
				_exp ="";
			}
			if(arg4) {
				_path = ";path=" + arg4;
			} else {
				_path= "";
			}
			document.cookie = escape(arg1) + "=" + escape(arg2) + _exp + _path +";";
		}
	},
	
	cookieLoad: function (arg) {
		if (arg) {
			var cookieData = document.cookie + ";" ;
			arg = escape(arg);
			var startPoint1 = cookieData.indexOf(arg);
			var startPoint2 = cookieData.indexOf("=", startPoint1) + 1;
			var endPoint = cookieData.indexOf(";", startPoint1);
			if(startPoint2 < endPoint && startPoint1 > -1 && startPoint2-startPoint1 == arg.length + 1) {
				cookieData = cookieData.substring(startPoint2,endPoint);
				cookieData = unescape(cookieData);
				return cookieData;
			}
		}
		return false;
	}

};