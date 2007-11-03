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
		var css = document.createElement('link');
		css.href = wikihelper_root_url + '/skin/loader.php?src=' + filename;
		css.rel  = 'stylesheet';
		css.type = 'text/css';
		document.getElementsByTagName('head')[0].appendChild(css);
	},
	
	htmlspecialchars: function (str) {
		return str.
		replace(/&/g,"&amp;").
		replace(/</g,"&lt;").
		replace(/>/g,"&gt;").
		replace(/"/g,"&quot;").
		replace(/'/g,"&#039;");
	}

};