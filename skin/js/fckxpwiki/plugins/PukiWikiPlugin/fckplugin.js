//
//	guiedit - PukiWiki Plugin
//
//	License:
//	  GNU General Public License Version 2 or later (GPL)
//	  http://www.gnu.org/licenses/gpl.html
//
//	Copyright (C) 2006-2007 garand
//	PukiWiki : Copyright (C) 2001-2006 PukiWiki Developers Team
//	FCKeditor : Copyright (C) 2003-2007 Frederico Caldeira Knabben
//


// コマンド
FCKCommands.RegisterCommand('PukiWikiPlugin',
	new FCKDialogCommand('PukiWikiPlugin', FCKLang.PukiWikiPluginDlgTitle,
						 FCKPlugins.Items['PukiWikiPlugin'].Path + 'PukiWikiPlugin.html', 460, 280
	)
);

FCKCommands.GetCommand('PukiWikiPlugin').GetState = function() {
	// Disabled if not WYSIWYG.
	if ( FCK.EditMode != FCK_EDITMODE_WYSIWYG || ! FCK.EditorWindow )
		return FCK_TRISTATE_DISABLED ;

	var oElement = FCKSelection.GetSelectedElement() || FCKSelection.GetParentElement();
	if (oElement && oElement.tagName.Equals('DIV', 'SPAN') && oElement.className == 'plugin') {
		return FCK_TRISTATE_ON;
	}
	
	return FCK_TRISTATE_OFF;
}

FCKCommands.RegisterCommand('Attachment',
	new FCKDialogCommand('Attachment', FCKLang.AttachmentDlgTitle,
						 FCKPlugins.Items['PukiWikiPlugin'].Path + 'Attachment.html', 420, 300
	)
);

FCKCommands.GetCommand('Attachment').GetState = function() {
	// Disabled if not WYSIWYG.
	if ( FCK.EditMode != FCK_EDITMODE_WYSIWYG || ! FCK.EditorWindow )
		return FCK_TRISTATE_DISABLED ;

	var oElement = FCKSelection.GetSelectedElement() || FCKSelection.GetParentElement();
	if (oElement && oElement.tagName.Equals('DIV', 'SPAN') && oElement.className == 'ref') {
		return FCK_TRISTATE_ON;
	}
	
	return FCK_TRISTATE_OFF;
}

// プラグイン削除
var PukiWikiPluginDelete = {
	Execute : function() {
		var oElement = PukiWikiPlugin.GetSelectedElement2();
		if (!oElement) return;
		
		FCKUndo.SaveUndoStep();
		
		oElement.parentNode.removeChild(oElement);
		
		FCKUndo.SaveUndoStep();
	},

	GetState : function() { return FCK_TRISTATE_OFF; }
}
FCKCommands.RegisterCommand('PukiWikiPluginDelete', PukiWikiPluginDelete);



// ツールバー・ボタン
FCKToolbarItems.RegisterItem('PukiWikiPlugin', new FCKToolbarButton('PukiWikiPlugin', FCKLang.PukiWikiPluginBtn));

FCKToolbarItems.RegisterItem('Attachment',
	new FCKToolbarButton('Attachment', FCKLang.AttachmentBtn, FCKLang.AttachmentBtn, null, false, false, 37)
);

function _RefreshPukiWikiPluginButton() {
	FCKToolbarItems.GetItem('PukiWikiPlugin').RefreshState();
	FCKToolbarItems.GetItem('Attachment').RefreshState();
}

FCK.Events.AttachEvent('OnSelectionChange', _RefreshPukiWikiPluginButton);



//	コンテキストメニュー
FCK.ContextMenu.RegisterListener( {
	AddItems : function(menu, tag, tagName) {
		if ((FCKBrowserInfo.IsOpera || FCKBrowserInfo.IsSafari) && (e = FCKSelection.GetParentElement())) {
			tag = e;
			tagName = e.tagName;
		}
		if ((tagName == 'DIV' || tagName == 'SPAN') && tag.className.Equals('plugin', 'ref')) {
			menu.AddSeparator();
			if (tag.className == 'plugin') {
				menu.AddItem('PukiWikiPluginDelete', FCKLang.PukiWikiPluginDelete);
				menu.AddItem('PukiWikiPlugin', FCKLang.PukiWikiPluginDlgTitle,
								FCKToolbarItems.GetItem('PukiWikiPlugin').IconPath);
			}
			else {
				menu.AddItem('PukiWikiPluginDelete', FCKLang.AttachmentDelete);
				menu.AddItem('Attachment', FCKLang.AttachmentDlgTitle, 37);
			}
	 	}
	}}
);


//	PukiWikiPlugin オブジェクト
var PukiWikiPlugin = new Object();

//	追加
PukiWikiPlugin.Add = function(sValue) {
	FCKUndo.SaveUndoStep();
	
	var oElement = FCK.CreateElement(sValue['type']);
	this.SetupElement(oElement, sValue);
	
	FCKUndo.SaveUndoStep();
}

//	変更
PukiWikiPlugin.Change = function(element, sValue) {
	FCKUndo.SaveUndoStep();

	if (element.tagName != sValue['type']) {
		element = FCK.CreateElement(sValue['type']);
	}
	
	this.SetupElement(element, sValue);

	FCKUndo.SaveUndoStep();
}

//	要素の設定
PukiWikiPlugin.SetupElement = function(element, sValue) {
	if (sValue['class'] == 'plugin') {
		this.SetupPlugin(element, sValue);
	}
	else {
		this.SetupAttachment(element, sValue);
	}
	
	element.className = sValue['class'];
	element.contentEditable = false;
	element.onresizestart = PukiWikiPlugin.OnResizeStart;

	if (FCKBrowserInfo.IsGecko) {
		element.style.cursor = 'default';
	}
}

PukiWikiPlugin.SetupPlugin = function(element, sValue) {
	var html;
	var option = '';
	var text = '';

	if (sValue['option1'] || sValue['option2'] || sValue['option3']) {
		option = sValue['option1'];
		option += sValue['option2'] ? (',' + sValue['option2']) : '';
		option += sValue['option3'] ? (',' + sValue['option3']) : '';
		option = FCKTools.HTMLEncode(option);
	}
	
	if (sValue['text']) {
		text = FCKTools.HTMLEncode(sValue['text']);
		text = text.replace(/\n/g, "<BR>");
	}
	
	if (sValue['type'] == 'DIV') {
		html = '#' + sValue['name'] + (option ? '(' + option + ')' : '') + (text ? "{{<BR>" + text + "<BR>}}" : '');
	}
	else {
		html = '&amp;' + sValue['name'] + (option ? '(' + option + ')' : '') + (text ? "{" + text + "}" : '') + ';';
	}
	
	element.innerHTML = html;
}

PukiWikiPlugin.SetupAttachment = function(element, sValue) {
	var options1 = '';
	var options2 = '';
	var option3 = '';
	
	if (sValue['type'] == 'DIV') {
		options1 += sValue['align'] ? (',' + sValue['align']) : '';
		options1 += sValue['wrap'] ? ',wrap' : '';
		options1 += sValue['around'] ? ',around' : '';
	}
	
	options1 += sValue['nolink'] ? ',nolink' : '';
	options1 += sValue['noicon'] ? ',noicon' : '';
	options1 += sValue['noimg'] ? ',noimg' : '';
	//options1 += sValue['zoom'] ? ',zoom' : '';
	
	if (sValue['size'] > 0) {
		options2 += ',' + sValue['size'] + '%';
		sValue['width'] = '';
		sValue['height'] = '';
		sValue['mw'] = '';
		sValue['mh'] = '';
	} else {
		if (sValue['width'] > 0) {
			if (sValue['mw'] == 'max') {
				sValue['mw'] = sValue['width'];
				options2 += ',mw:' + sValue['width'];
				sValue['width'] = '';
			} else {
				options2 += ',w:' + sValue['width'];
				sValue['mw'] = '';
			}
		}
		if (sValue['height'] > 0) {
			if (sValue['mh'] == 'max') {
				sValue['mh'] = sValue['height'];
				options2 += ',mh:' + sValue['height'];
				sValue['height'] = '';
			} else {
				options2 += ',h:' + sValue['height'];
				sValue['mh'] = '';
			}
		}
	}
	
	options2 += sValue['othor'] ? (',' + sValue['othor']) : '';
	
	if (sValue['alt']) {
		if (sValue['type'] == 'DIV') {
			options2 += ',' + sValue['alt'];
		} else {
			option3 = '{' + sValue['alt'] + '}';
		}
	}
	
	var text = (sValue['type'] == 'DIV') ? '#' : "&amp;";
	text += 'ref(' + sValue['name'];
	//text += (options1 != '' || options2 != '') ? ',' : '';
	text += options1 + options2 + ')' + option3 + ((sValue['type'] == 'DIV') ? '' : ';');
	
	element.innerHTML = text;

	element.setAttribute('_filename', sValue['name']);
	element.setAttribute('_othor', sValue['othor']);
	element.setAttribute('_alt', sValue['alt']);
	element.setAttribute('_width', sValue['width']);
	element.setAttribute('_height', sValue['height']);
	element.setAttribute('_mw', sValue['mw']);
	element.setAttribute('_mh', sValue['mh']);
	element.setAttribute('_size', sValue['size']);
	element.setAttribute('_align', sValue['align']);
	element.setAttribute('_nolink', sValue['nolink'] ? 1 : 0);
	element.setAttribute('_noicon', sValue['noicon'] ? 1 : 0);
	element.setAttribute('_noimg', sValue['noimg'] ? 1 : 0);
	element.setAttribute('_wrap', sValue['wrap'] ? 1 : 0);
	element.setAttribute('_around', sValue['around'] ? 1 : 0);
	element.setAttribute('_zoom', sValue['zoom'] ? 1 : 0);
}

//	クリック イベント
PukiWikiPlugin._SetupClickListener = function() {
	PukiWikiPlugin._ClickListener = function(e) {
		if (e.target.tagName.Equals('DIV', 'SPAN') && e.target.className.Equals('plugin', 'ref')) {
			FCKSelection.SelectNode(e.target);
		}
	}
	FCK.EditorDocument.addEventListener('click', PukiWikiPlugin._ClickListener, true);
}

//	onresizestart イベントの設定
PukiWikiPlugin._SetupResizeListener = function() {
	var aTags = FCK.EditorDocument.getElementsByTagName('DIV');
	for (var i = 0; i < aTags.length; i++) {
		if (aTags[i].className.Equals('plugin', 'ref')) {
			FCKTools.AddEventListener(aTags[i], 'resizestart', PukiWikiPlugin.OnResizeStart);
		}
	}
	
	aTags = FCK.EditorDocument.getElementsByTagName('SPAN');
	for (var i = 0; i < aTags.length; i++) {
		if (aTags[i].className.Equals('plugin', 'ref')) {
			FCKTools.AddEventListener(aTags[i], 'resizestart', PukiWikiPlugin.OnResizeStart);
		}
	}
}

//	onresizestart イベント
PukiWikiPlugin.OnResizeStart = function() {
	FCK.EditorWindow.event.returnValue = false;
	return false;
}

//	OnAfterSetHTML イベント
PukiWikiPlugin.Redraw = function() {
	if ( FCK.EditMode != FCK_EDITMODE_WYSIWYG )
		return ;
		
	if (FCKBrowserInfo.IsGecko) {
		PukiWikiPlugin._SetupClickListener();
	}
	PukiWikiPlugin._SetupResizeListener();
}

FCK.Events.AttachEvent('OnAfterSetHTML', PukiWikiPlugin.Redraw);

//	ダブルクリック イベント
PukiWikiPlugin.OnDoubleClick = function(element) {
	if (element.className == 'plugin') {
		FCKCommands.GetCommand('PukiWikiPlugin').Execute();
	}
	else if (element.className == 'ref') {
		FCKCommands.GetCommand('Attachment').Execute();
	}
}

FCK.RegisterDoubleClickHandler(PukiWikiPlugin.OnDoubleClick, 'DIV');
FCK.RegisterDoubleClickHandler(PukiWikiPlugin.OnDoubleClick, 'SPAN');

//	FCKSelection
PukiWikiPlugin.GetSelectedHtml = function() {
	var html;
	var oSelection = PukiWikiPlugin.GetSelection2();
	
	if (!oSelection) {
		return null;
	}
	
	if (FCKBrowserInfo.IsIE) {
		html = oSelection.createRange().htmlText;
		html = html.replace(/\r\n/g, '');
	}
	else {
		var oRange = oSelection.getRangeAt(0);
		var oElement = document.createElement('BODY');
		oElement.appendChild(oRange.cloneContents());
		html = oElement.innerHTML;
	}
	
	return html;
}

PukiWikiPlugin.GetSelectedElement2 = function() {
	if (FCKBrowserInfo.IsOpera || FCKBrowserInfo.IsSafari) {
		return FCKSelection.GetParentElement();
	}
	
	return FCKSelection.GetSelectedElement();
}

PukiWikiPlugin.GetSelection2 = function() {
	if (FCKBrowserInfo.IsIE) {
		return FCK.EditorDocument.selection;
	}
	
	return FCK.EditorWindow.getSelection();
}

