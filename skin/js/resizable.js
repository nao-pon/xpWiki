//
// Created on 2007/10/03 by nao-pon http://hypweb.net/
// $Id: resizable.js,v 1.2 2007/10/04 08:41:49 nao-pon Exp $
//

var Resizable = Class.create();

Resizable.prototype = {

	initialize: function( name, mode ){
		
		this.sizeX = null;
		this.sizeY = null;
		this.curX = null;
		this.curY = null;
		this.onDrag = false;
		this.saveCursor = [];

		this.boolX = false;
		this.boolY = false;
		
		this.resizeX = null;
		this.resizeY = null;
		this.resizeXY = null;
		
		this.initHeight = '';
		this.initWidth = '';
		
		var target = $(name);
		if (!target) return false;
		
		if (target.tagName == 'TEXTAREA') {
			var parent = target.parentNode;
			this.base = document.createElement('div');
			this.base.id = name + '_resize_base';
			this.base.style.padding = '0px';
			this.base.style.paddingLeft = target.style.paddingLeft;
			this.base.style.marginLeft = target.style.marginLeft;
			this.base.style.paddingTop = target.style.paddingTop;
			this.base.style.marginTop = target.style.marginTop;

			this.elem = target;
			
			if (!!this.elem.style.height) {
				var initH = this.elem.style.height;
			} else {
				var initH = this.elem.getStyle('maxHeight');
			}
			if (!!initH && initH != 'none') {
				this.initHeight = initH;
			}
			
			if (!!this.elem.style.width) {
				var initW = this.elem.style.width;
			} else {
				var initW = this.elem.getStyle('maxWidth');
			}
			if (!!initW && initW != 'none') {
				this.initWidth = initW;
			}
			
			this.elem.style.margin = '0px';
			parent.replaceChild(this.base, target);
			this.base.appendChild(this.elem);
			
			Element.makePositioned(parent);
			
		} else if (target.tagName == 'DIV') {
			this.base = target;
			
			if (!!this.base.style.height) {
				var initH = this.base.style.height;
			} else {
				var initH = this.base.getStyle('maxHeight');
			}
			if (!!initH && initH != 'none') {
				this.initHeight = initH;
			}
			
			if (!!this.base.style.width) {
				var initW = this.base.style.width;
			} else {
				var initW = this.base.getStyle('maxWidth');
			}
			if (initW && initW != 'none') {
				this.initWidth = initW;
			}
			
			this.elem = document.createElement('div');
			this.elem.innerHTML = this.base.innerHTML;
			this.base.innerHTML = '';
			this.base.appendChild(this.elem);
			
		} else {
			return false;
		}
		with(this.base.style) {
			overflow = 'visible';
			maxHeight = 'none';
			maxWidth = 'none';
			marginBottom = '10px';
		}
		Element.makePositioned(this.base);
		this.makeResizeBox (mode);
		return this;
	},

	// Version
	VERSION: "0.01",
	
	// methods
	setEast: function (elemid) { 
		this.setEdge( elemid, true, false, "e-resize" );
	},
	setSouth: function (elemid) { 
		this.setEdge( elemid, false, true, "s-resize" );
	},
	setSouthEast: function (elemid) { 
		this.setEdge( elemid, true, true, "se-resize" );
	},
	
	// make resize box
	makeResizeBox: function (mode) {
		if (!mode) mode = 'xy';
		if (mode == 'x' ) {
			//Element.show(this.base);
			this.elem.style.maxWidth = 'none';
			if (this.initWidth) {
				this.base.style.width = this.elem.style.width = this.initWidth;
				this.sizeY = parseInt(this.initWidth);
			} else {
				this.sizeX = parseInt(this.elem.offsetWidth);
				if (this.sizeX) {
					this.base.style.width = this.elem.style.width = this.sizeX + 'px';
				}
			}
			var resize = document.createElement('div');
			resize.id = this.base.id + '_resizeX';
			resize.className = 'resizableResizeX';
			resize.style.zIndex = this.base.style.zIndex + 1;
			this.base.appendChild(resize);
		} else if (mode == 'y') {

			this.elem.style.maxHeight = 'none';
			if (this.initHeight) {
				this.base.style.height = this.elem.style.height = this.initHeight;
				this.sizeY = parseInt(this.initHeight);
			} else {
				this.sizeY = parseInt(this.elem.offsetHeight);
				if (this.sizeY) {
					this.base.style.height = this.elem.style.height = this.sizeY + 'px';
				}
			}

			var resize = document.createElement('div');
			resize.id = this.base.id + '_resizeY';
			resize.className = 'resizableResizeY';
			resize.style.zIndex = this.base.style.zIndex + 1;
			this.base.appendChild(resize);
		} else {
			mode = 'xy';
			this.makeResizeBox ('x');
			this.makeResizeBox ('y');
			var resize = document.createElement('div');
			resize.id = this.base.id + '_resizeXY';
			resize.className = 'resizableResizeXY';
			resize.style.zIndex = this.base.style.zIndex + 1;
			this.base.appendChild(resize);
		}
		this.setEdge( resize.id, mode );
	},
	
	setEdge: function (elemid, mode) {
		var edgelem;
		if ( typeof(elemid) == "object" && elemid.parentNode ) {
			edgelem = elemid;
		} else { 
			edgelem = document.getElementById( elemid );
		}
		if ( ! edgelem ) return;	// no such element
		
		this.elem.style.overflow = 'auto';
		
		if (mode == 'x' ) {
			edgelem.style.cursor = 'e-resize';
			var func = this.dragStartX;
		} else if (mode == 'y') {
			edgelem.style.cursor = 's-resize';
			var func = this.dragStartY;
		} else {
			edgelem.style.cursor = 'se-resize';
			var func = this.dragStartXY;
		}
		Event.observe(edgelem, "mousedown", func.bindAsEventListener(this));
	},
	
	dragStartX: function (event) {
		if (!this.sizeX) {
			this.sizeX = parseInt(this.elem.offsetWidth);
			this.base.style.width = this.elem.style.width = this.sizeX + 'px';
		}
		this.boolX = true;
		this.boolY = false;
		this.cursorS = 'e-resize';
		this.dragStart(event);
	},

	dragStartY: function (event) {
		if (!this.sizeY) {
			this.sizeY = parseInt(this.elem.offsetWidth);
			this.base.style.width = this.elem.style.width = this.sizeY + 'px';
		}
		this.boolX = false;
		this.boolY = true;
		this.cursorS = 's-resize';
		this.dragStart(event);
	},

	dragStartXY: function (event) {
		if (!this.sizeX) {
			this.sizeX = parseInt(this.elem.offsetWidth);
			this.base.style.width = this.elem.style.width = this.sizeX + 'px';
		}
		if (!this.sizeY) {
			this.sizeY = parseInt(this.elem.offsetWidth);
			this.base.style.width = this.elem.style.width = this.sizeY + 'px';
		}
		this.boolX = true;
		this.boolY = true;
		this.cursorS = 'se-resize';
		this.dragStart(event);
	},

	dragStart: function (event) {
		if ( this.onDrag ) return;
		this.onDrag = true;
		this.backupCursor();
	
		this.curX = event.clientX;
		this.curY = event.clientY;
		
		Event.observe(document, "mousemove", this.dragMove.bindAsEventListener(this));
		Event.observe(document, "mouseup", this.dragFinish.bindAsEventListener(this));
		
		event.stop();
	},
	
	dragMove: function (event) {
		if ( ! this.onDrag ) return;
		//alert(this.sizeX);
		var newX = this.sizeX + event.clientX - this.curX;
		if ( this.boolX && newX > 0 ) {
			this.base.style.width = this.elem.style.width = newX + "px";
			this.sizeX = newX;
			this.curX = event.clientX;
		}
	
		var newY = this.sizeY + event.clientY - this.curY;
		if ( this.boolY && newY > 0 ) {
			this.base.style.height = this.elem.style.height = newY + "px";
			this.sizeY = newY;
			this.curY = event.clientY;
		}
		
		event.stop();
	},
	
	dragFinish: function (event) {
		if ( ! this.onDrag ) return;
		this.restoreCursor();
		this.onDrag = false;
		event.stop();
	},
	
	backupCursor: function () {
		this.saveCursor['body'] = document.body.style.cursor;
		this.saveCursor['base'] = this.base.style.cursor;
		this.saveCursor['elem'] = this.elem.style.cursor;
		if (!!$(this.base.id + '_resizeX')) {
			this.saveCursor['resizeX'] = $(this.base.id + '_resizeX').style.cursor;
			$(this.base.id + '_resizeX').style.cursor = this.cursorS;
		}
		if (!!$(this.base.id + '_resizeY')) {
			this.saveCursor['resizeY'] = $(this.base.id + '_resizeY').style.cursor;
			$(this.base.id + '_resizeY').style.cursor = this.cursorS;
		}
		if (!!$(this.base.id + '_resizeXY')) {
			this.saveCursor['resizeXY'] = $(this.base.id + '_resizeXY').style.cursor;
			$(this.base.id + '_resizeXY').style.cursor = this.cursorS;
		}
		this.elem.style.cursor = this.base.style.cursor = document.body.style.cursor = this.cursorS;
	},
	
	restoreCursor: function () {
		document.body.style.cursor = this.saveCursor['body'];
		this.base.style.cursor = this.saveCursor['base'];
		this.elem.style.cursor = this.saveCursor['elem'];
		if (!!$(this.base.id + '_resizeX')) { $(this.base.id + '_resizeX').style.cursor = this.saveCursor['resizeX']; }
		if (!!$(this.base.id + '_resizeY')) { $(this.base.id + '_resizeY').style.cursor = this.saveCursor['resizeY']; }
		if (!!$(this.base.id + '_resizeXY')) { $(this.base.id + '_resizeXY').style.cursor = this.saveCursor['resizeXY']; }
	}
}