/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2008 Frederico Caldeira Knabben
 *
 * == BEGIN LICENSE ==
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 *
 * This is a sample implementation for a custom Data Processor for basic BBCode.
 */

// Load Ajax LIB
document.write( '<scr' + 'ipt type="text/javascript" src="' + FCKConfig.BasePath + '../../fckxpwiki/jslb_ajax.js"><\/scr' + 'ipt>' );

FCK.DataProcessor =
{
	/*
	 * Returns a string representing the HTML format of "data". The returned
	 * value will be loaded in the editor.
	 * The HTML must be from <html> to </html>, eventually including
	 * the DOCTYPE.
	 *     @param {String} data The data to be converted in the
	 *            DataProcessor specific format.
	 */
	ConvertToHtml : function( data )
	{
		window.parent.xpwiki_now_loading(true, FCK.LinkedField.parentNode);
		
		var body;
		
		sendRequest(
			{
				onload : function (oj){
					var xmlData = oj.responseXML;
					var res = xmlData.getElementsByTagName("res");
					var lb = xmlData.getElementsByTagName("lb");
					body = res[0].firstChild.nodeValue;
					FCKConfig.xpWiki_LineBreak = lb[0].firstChild.nodeValue;
				}
			},
			{ s: data, lb: FCKConfig.xpWiki_LineBreak, _hypmode: 1 },
			'POST',
			FCKConfig.BasePath + "../../../../gate.php?way=w2x",
			false,
			true
		);
		
		//alert(body);
		
		window.parent.xpwiki_now_loading(false, FCK.LinkedField.parentNode);
		
		return '<html><head><title></title></head><body>' + body + '</body></html>' ;
	},

	/*
	 * Converts a DOM (sub-)tree to a string in the data format.
	 *     @param {Object} rootNode The node that contains the DOM tree to be
	 *            converted to the data format.
	 *     @param {Boolean} excludeRoot Indicates that the root node must not
	 *            be included in the conversion, only its children.
	 *     @param {Boolean} format Indicates that the data must be formatted
	 *            for human reading. Not all Data Processors may provide it.
	 */
	ConvertToDataFormat : function( rootNode, excludeRoot, ignoreIfEmptyParagraph, format )
	{
		window.parent.xpwiki_now_loading(true, FCK.LinkedField.parentNode);
		
		var data = FCKXHtml.GetXHTML( rootNode, false, false ) ;

		//alert(data);
		
		sendRequest(
			{
				onload : function (oj){
					var doc = oj.responseXML;
					data = doc.documentElement.firstChild.nodeValue;
				}
			},
			{ s: data, lb: FCKConfig.xpWiki_LineBreak, _xmode: 2 },
			'POST',
			FCKConfig.BasePath + "../../../../gate.php?way=x2w",
			false,
			true
		);

		window.parent.xpwiki_now_loading(false, FCK.LinkedField.parentNode);

		return data ;
	},

	/*
	 * Makes any necessary changes to a piece of HTML for insertion in the
	 * editor selection position.
	 *     @param {String} html The HTML to be fixed.
	 */
	FixHtml : function( html )
	{
		return html ;
	}
} ;

// Rename the "Source" buttom to "WikiSource".
FCKToolbarItems.RegisterItem( 'Source', new FCKToolbarButton( 'Source', FCKLang.WikiSource, null, FCK_TOOLBARITEM_ICONTEXT, true, true, 1 ) ) ;

