if (typeof(googlemaps_maps) == 'undefined') {
	// add vml namespace for MSIE
	var agent = navigator.userAgent.toLowerCase();
	if (agent.indexOf("msie") != -1 && agent.indexOf("opera") == -1) {
		try {
		document.namespaces.add('v', 'urn:schemas-microsoft-com:vml');
		document.createStyleSheet().addRule('v\:*', 'behavior: url(#default#VML);');
		} catch(e) {}
	}

	var googlemaps_maps = new Array();
	var googlemaps_markers = new Array();
	var googlemaps_zoomarkers = new Array();
	var googlemaps_icons = new Array();
	var googlemaps_crossctrl = new Array();
	var googlemaps_searchctrl = new Array();
	var googlemaps_infowindow = new Array();
	var googlemaps_dropmarker = new Array();
	var onloadfunc = new Array();
	var onloadfunc2 = new Array();
	var onloadfunc3 = new Array();
}

var PGMap = function(page, mapname, options) {
	// init page var
	if (typeof(googlemaps_maps[page]) == 'undefined') {
		googlemaps_maps[page] = new Array();
		googlemaps_markers[page] = new Array();
		googlemaps_zoomarkers[page] = new Array();
		googlemaps_icons[page] = new Array();
		googlemaps_crossctrl[page] = new Array();
		googlemaps_searchctrl[page] = new Array();
		googlemaps_infowindow[page] = new Array();
		googlemaps_dropmarker[page] = new Array();
	}
	
	// Cancel Event dblclick bubble up
	google.maps.event.addDomListener(document.getElementById(mapname), 'dblclick', function(e) {
		(e || window.event).stop();
	});
	
	var map = new google.maps.Map(document.getElementById(mapname),options);
	
	// init map var
	googlemaps_maps[page][mapname] = map;
	googlemaps_markers[page][mapname] = new Array();
	googlemaps_zoomarkers[page][mapname] = new Array();

	//map.pukiwikiname = mapname;
	map.wikipage = page;
	
	if (typeof googlemaps_infowindow[page][mapname] == 'undefined') {
		var infowindowopts = {};
		googlemaps_infowindow[page][mapname] = new google.maps.InfoWindow(infowindowopts);
	}
	
	google.maps.event.addListener(map, "dblclick", function(e) {
		googlemaps_infowindow[page]["$mapname"].close();
	});

	google.maps.event.addListener(map, "zoom_changed", function() {
		var markers = googlemaps_zoomarkers[page][mapname];
	
		var zoom = map.getZoom();
		for (name in markers) {
			if (!markers.hasOwnProperty(name)) continue;
			var m = markers[name];
			var minzoom = m.minzoom <  0 ?  0 : m.minzoom;
			var maxzoom = m.maxzoom > 21 ? 21 : m.maxzoom;
			if (minzoom > maxzoom) {
				maxzoom = minzoom;
			}
			if (zoom >= minzoom && zoom <= maxzoom) {
				m.marker.setMap(map);
				m.marker.setVisible(true);
			} else {
				m.marker.setMap(null);
			}
		}
	});
	
	// Register markers into map
	onloadfunc3.push( function () {
		p_googlemaps_mark_to_map(page, mapname);

		//Fire zoom_changed for attempt maxzoom & minzoom
		map.setZoom(map.getZoom());
	});

	return map;
};

var PGMarker = function(point, icon, flat, page, map, hidden, visible, title, minzoom, maxzoom) {
	var marker = null;
	if (hidden == false) {
		var opt = new Object();
		opt.position = point;
		var setIcon;
		if (icon != '') {
			setIcon = googlemaps_icons[page][icon];
		} else if (!!googlemaps_icons[page]['Default']) {
			setIcon = googlemaps_icons[page]['Default'];
		} else {
			setIcon = null;
		}
		if (setIcon) {
			opt.icon = setIcon.image;
			if (setIcon.shadow)      opt.shadow = setIcon.shadow;
			if (setIcon.anchorPoint) opt.anchorPoint = setIcon.anchorPoint;
			if (setIcon.shape)       opt.shape = setIcon.shape;
		}
		if (flat) {
			opt.flat = true;
		} else {
			opt.flat = false;
		}
		if (title != '') { opt.title = title; }
		marker = new google.maps.Marker(opt);
		google.maps.event.addListener(marker, "click", function() { this.pukiwikigooglemaps.onclick(); });
		marker.pukiwikigooglemaps = this;
	}

	this.marker = marker;
	this.icon = icon;
	this.map = map;
	this.point = point;
	this.minzoom = minzoom;
	this.maxzoom = maxzoom;

	var _visible = false;
	var _html = null;
	var _zoom = null;
	var _type = null;

	this.setHtml = function(h) {_html = h;};
	this.setZoom = function(z) {_zoom = parseInt(z);};
	this.setType = function(t) {_type = t;};
	this.getHtml = function() {return _html;};
	this.getZoom = function() {return _zoom;};
	//this.getType = function() {return _type;}

	this.onclick = function () {
		var map = googlemaps_maps[page][this.map];

		if (_type !== map.getMapTypeId()) {
			map.setMapTypeId(_type);
		}

		if (_zoom) {
			if (map.getZoom() != _zoom) {
				map.setZoom(_zoom);
			}
		}
		
		var infowindow = googlemaps_infowindow[page][this.map];
		infowindow.close();

		map.panTo(this.point);

		if ( _html && this.marker ) {
			//map.panTo(this.point);
			// Wait while load image.
			var root = document.createElement('div');
			root.innerHTML = _html;

			var checkNodes = new Array();
			var doneOpenInfoWindow = false;
			checkNodes.push(root);

			while (checkNodes.length) {
				var node = checkNodes.shift();
				if (node.hasChildNodes()) {
					for (var i=0; i<node.childNodes.length; i++) {
						checkNodes.push(node.childNodes.item(i));
					}
				} else {
					var tag = node.tagName;
					if (tag && tag.toUpperCase() == "IMG") {
						if (node.complete == false) {
							// Wait while load image.
							var openInfoWindowFunc = function (xmlhttp) {
								infowindow.setContent(_html);
								infowindow.open(map, marker);
							};
							var async = false;
							if (agent.indexOf("msie") != -1 && agent.indexOf("opera") == -1) {
								async = true;
							}
							if (PGTool.downloadURL(node.src, openInfoWindowFunc, async, null, null)) {
								doneOpenInfoWindow = true;
							}
							break;
						}
					}
				}
			}
			if (doneOpenInfoWindow == false) {
				infowindow.setContent(_html);
				infowindow.open(map, this.marker);
			}
		} else {
			//map.panTo(this.point);
		}
	};

	this.isVisible = function () {
		return _visible;
	};
	this.show = function () {
		if (_visible) return;
		if (this.marker) this.marker.setVisible(true);
		_visible = true;
	};

	this.hide = function () {
		if (!_visible) return;
		if (this.marker != null) this.marker.setVisible(false);
		_visible = false;
	};

	if (visible) {
		this.show();
	} else {
		this.hide();
	}
	return this;
};


var PGTool = new function () {
	this.fmtNum = function (x) {
		var n = x.toString().split(".");
		n[1] = (n[1] + "000000").substr(0, 6);
		return n.join(".");
	};
	this.getLatLng = function (x, y, api) {
		switch (api) {
			case 0:
				x = x - y * 0.000046038 - x * 0.000083043 + 0.010040;
				y = y - y * 0.00010695  + x * 0.000017464 + 0.00460170;
			case 1:
				t = x;
				x = y;
				y = t;
				break;
		}
		return new google.maps.LatLng(x, y);
	};
	this.getXYPoint = function (x, y, api) {
		if (api < 2) {
			t = x;
			x = y;
			y = t;
		}
		if (api == 0) {
			nx = 1.000083049 * x + 0.00004604674815 * y - 0.01004104571;
			ny = 1.000106961 * y - 0.00001746586797 * x - 0.004602192204;
			x = nx;
			y = ny;
		}
		return {x:x, y:y};
	};
	this.createXmlHttp = function () {
		if (typeof(XMLHttpRequest) == "function") {
			return new XMLHttpRequest();
		}
		if (typeof(ActiveXObject) == "function") {
			try {
				return new ActiveXObject("Msxml2.XMLHTTP");
			} catch(e) {};
			try {
				return new ActiveXObject("Microsoft.XMLHTTP");
			} catch(e) {};
		}
		return null;
	};
	this.downloadURL = function (url, func, async, postData, contentType) {
		var xmlhttp = this.createXmlHttp();
		if (!xmlhttp) {
			return null;
		}
		if (async && func) {
			xmlhttp.onreadystatechange = function () {
				if (xmlhttp.readyState == 4) {
					func(xmlhttp);
				}
			};
		}
		try {
			if (postData) {
				xmlhttp.open("POST", url, async);
				if (!contentType) {
					contentType = "application/x-www-form-urlencoded";
				}
				xmlhttp.setRequestHeader("Content-Type", contentType);
				xmlhttp.send(postData);
			} else {
				xmlhttp.open("GET", url, async);
				xmlhttp.send(null);
			}
		} catch(e) {
			return false;
		}
		if (!async && func) func(xmlhttp);
	};

	this.transparentGoogleLogo = function(map) {
		var container = map.getContainer();
		for (var i=0; i<container.childNodes.length; i++) {
			var node = container.childNodes.item(i);
			if (node.tagName != "A") continue;
			if (node.hasChildNodes() == false) continue;

			var img = node.firstChild;
			if (img.tagName != "IMG") continue;
			if (img.src.match(/http:.*\/poweredby\.png/) == null) continue;

			node.style.backgroundColor = "transparent";
			break;
		}
		return;
	};

	this.getMapTypeName = function(type) {
		if (type == google.maps.MapTypeId.HYBRID) {
			return 'hybrid';
		} else if (type == google.maps.MapTypeId.SATELLITE) {
			return 'satellite';
		} else if (type == google.maps.MapTypeId.TERRAIN) {
			return 'terrain';
		} else {
			return 'roadmap';
		}
	};
	
	this.setWikiTag = function(options) {
		var self = this;
		var pointLat, pointLng, centerLat, centerLng;
		centerLat = self.fmtNum(googlemaps_maps[options.page][options.mapname].getCenter().lat());
		centerLng = self.fmtNum(googlemaps_maps[options.page][options.mapname].getCenter().lng());
		if (!!googlemaps_dropmarker[options.page][options.mapname]) {
			pointLat = self.fmtNum(googlemaps_dropmarker[options.page][options.mapname].getPosition().lat());
			pointLng = self.fmtNum(googlemaps_dropmarker[options.page][options.mapname].getPosition().lng());
		} else {
			pointLat = centerLat;
			pointLng = centerLng;
		}
		var maptag = ', type=' + self.getMapTypeName(googlemaps_maps[options.page][options.mapname].getMapTypeId());
		maptag += ', zoom=' + googlemaps_maps[options.page][options.mapname].getZoom();
		
		var mapBlock  = '#gmap(lat=' + centerLat + ', lng=' + centerLng;
		mapBlock += maptag;

		for (key in options) {
			if (!options.hasOwnProperty(key)) continue;
			if (key == 'page' || key == 'mapname' || key == 'lat' || key == 'lng' || key == 'zoom' || key == 'type') continue;
			mapBlock += ', ' + key + '=' + options[key];
		}

		mapBlock += ')';

		var mapMark  = '&gmap_mark(' + pointLat + ', ' + pointLng;
		mapMark += maptag;
		mapMark += ', title=Here Title){Here Caption};';

		$(options.mapname + '_info').innerHTML = '<p>' + mapBlock + '</p><p>' + mapMark + '</p>';

	};
};

var PGDraw = new function () {
	var self = this;
	this.weight = 10;
	this.opacity = 0.5;
	this.color = "#00FF00";
	this.fillopacity = 0;
	this.fillcolor = "#FFFF00";

	this.line = function (plist) {
		return new google.maps.Polyline(plist, this.color, this.weight, this.opacity);
	};
	
	this.rectangle = function (p1, p2) {
		var points = new Array (
			p1,
			new google.maps.LatLng(p1.lat(), p2.lng()),
			p2,
			new google.maps.LatLng(p2.lat(), p1.lng()),
			p1
		);
		return draw_polygon (plist);
	};
	
	this.circle  = function (point, radius) {
		return draw_ngon(point, radius, 0, 48, 0, 360);
	};
	
	this.arc = function (point, outradius, inradius, st, ed) {
		while (st > ed) { ed += 360; }
		if (st == ed) {
			return this.circle(point, outradius, inradius);
		}
		return draw_ngon(point, outradius, inradius, 48, st, ed);
	};
	
	this.ngon = function (point, radius, n, rotate) {
		if (n < 3) return null;
		return draw_ngon(point, radius, 0, n, rotate, rotate+360);
	};
	
	this.polygon = function (plist) {
		return draw_polygon (plist);
	};
	
	function draw_ngon (point, outradius, inradius, div, st, ed) {
		if (div <= 2) return null;

		var incr = (ed - st) / div;
		var lat = point.lat();
		var lng = point.lng();
		var out_plist = new Array();
		var in_plist  = new Array();
		var rad = 0.017453292519943295; /* Math.PI/180.0 */
		var en = 0.00903576399827824;   /* 1/(6341km * rad) */
		var out_clat = outradius * en; 
		var out_clng = out_clat/Math.cos(lat * rad);
		var in_clat = inradius * en; 
		var in_clng = in_clat/Math.cos(lat * rad);
		
		for (var i = st ; i <= ed; i+=incr) {
			if (i+incr > ed) {i=ed;}
			var nx = Math.sin(i * rad);
			var ny = Math.cos(i * rad);

			var ox = lat + out_clat * nx;
			var oy = lng + out_clng * ny;
			out_plist.push(new google.maps.LatLng(ox, oy));

			if (inradius > 0) {
			var ix = lat + in_clat  * nx;
			var iy = lng + in_clng  * ny;
			in_plist.push (new google.maps.LatLng(ix, iy));
			}
		}

		var plist;
		if (ed - st == 360) {
			plist = out_plist;
			plist.push(plist[0]);
		} else {
			if (inradius > 0) {
				plist = out_plist.concat( in_plist.reverse() );
				plist.push(plist[0]);
			} else {
				out_plist.unshift(point);
				out_plist.push(point);
				plist = out_plist;
			}
		}

		return draw_polygon(plist);
	}

	function draw_polygon (plist) {
		if (self.fillopacity <= 0) {
			return new google.maps.Polyline({
				path:          plist,
				strokeColor:   self.color,
				strokeWeight:  self.weight,
				strokeOpacity: self.opacity});
		}
		return new google.maps.Polygon({
			path:          plist, 
			strokeColor:   self.color,
			strokeWeight:  self.weight,
			strokeOpacity: self.opacity,
			fillColor:     self.fillcolor,
			fillOpacity:   self.fillopacity}); 
	}
};


//
// Center Cross
//
PGCross = function(map) {

	var createWidget = function(nsize, lwidth, lcolor) {
		var hsize = (nsize - lwidth) / 2;
		var nsize = hsize * 2 + lwidth;
		var border = document.createElement("div");
		border.width = nsize;
		border.height = nsize;
		var table = '\
<table width="'+nsize+'" border="0" cellspacing="0" cellpadding="0">\
  <tr>\
  <td style="width:'+ hsize+'px; height:'+hsize+'px; background-color:transparent; border:0px;"></td>\
  <td style="width:'+lwidth+'px; height:'+hsize+'px; background-color:'+lcolor+';  border:0px;"></td>\
  <td style="width:'+ hsize+'px; height:'+hsize+'px; background-color:transparent; border:0px;"></td>\
  </tr>\
  <tr>\
  <td style="width:'+ hsize+'px; height:'+lwidth+'px; background-color:'+lcolor+'; border:0px;"></td>\
  <td style="width:'+lwidth+'px; height:'+lwidth+'px; background-color:'+lcolor+'; border:0px;"></td>\
  <td style="width:'+ hsize+'px; height:'+lwidth+'px; background-color:'+lcolor+'; border:0px;"></td>\
  </tr>\
  <tr>\
  <td style="width:'+ hsize+'px; height:'+hsize+'px; background-color:transparent; border:0px;"></td>\
  <td style="width:'+lwidth+'px; height:'+hsize+'px; background-color:'+lcolor+';  border:0px;"></td>\
  <td style="width:'+ hsize+'px; height:'+hsize+'px; background-color:transparent; border:0px;"></td>\
  </tr>\
</table>';
		border.innerHTML = table;
		border.firstChild.style.opacity = 0.5;
		border.firstChild.style.MozOpacity = 0.5;
		border.firstChild.style.filter = 'alpha(opacity=50)';
		return border;
	};

	var container = document.createElement("div");
	container.style.position = "absolute";
	container.style.zIndex = "99999";
	
	var crossDiv = createWidget(16, 2, "#000000");
	container.appendChild(crossDiv);
	container.width = crossDiv.width;
	container.height = crossDiv.height;

	var getCrossPosition = function() {
		var mapdiv = map.getDiv();
		var x = (mapdiv.clientWidth  - container.clientWidth)/2.0;
		var y = (mapdiv.clientHeight - container.clientHeight)/2.0;
		return new google.maps.Point(Math.ceil(x), Math.ceil(y));
	};

	var moveCenter = function() {
		var pos = getCrossPosition();
		container.style.top  = pos.y.toString() + "px";
		container.style.left = pos.x.toString() + "px";
	};

	var changeStyle = function(color, opacity) {
		var table = container.firstChild.firstChild;
		var children = table.getElementsByTagName("td");
		for (var i = 0; i < children.length; i++) {
			var node = children[i];
			if (node.style.backgroundColor != "transparent") {
				node.style.backgroundColor = color;
			}
		}
		table.style.opacity = opacity;
		table.style.MozOpacity = opacity;
		table.style.filter = 'alpha(opacity=' + (opacity * 100) + ')';
	};

	var hidefunc = function() { try {map.getDiv().removeChild(container);} catch (e) {} };
	var showfunc = function() { try {map.getDiv().appendChild(container);} catch (e) {} };

	var crossChangeStyleFunc = function () {
		switch (map.getMapTypeId()) {
			case google.maps.MapTypeId.ROADMAP:   changeStyle('#000000', 0.5); break;
			case google.maps.MapTypeId.SATELLITE: changeStyle('#FFFFFF', 0.9); break;
			case google.maps.MapTypeId.HYBRID:    changeStyle('#FFFFFF', 0.9); break;
			case google.maps.MapTypeId.TERRAIN:   changeStyle('#000000', 0.5); break;
			default: changeStyle('#000000', 0.5); break;
		}
	};

	google.maps.event.addDomListener(map.getDiv(), "resize", function(e) {
		moveCenter();
	});
	
	map.getDiv().appendChild(container);
	moveCenter();

	infowindow = googlemaps_infowindow[map.wikipage][map.getDiv().id];
	google.maps.event.addListener(infowindow, "closeclick", function(){ showfunc(); });
	google.maps.event.addListener(infowindow, "domready", function(){ hidefunc(); });
	google.maps.event.addListener(map.getStreetView(), "visible_changed", function() {
		if (map.getStreetView().getVisible() == true) {
			hidefunc();
		} else {
			showfunc();
		}
	});

	google.maps.event.addListener(map, 'maptypeid_changed', crossChangeStyleFunc);
	crossChangeStyleFunc();

	return container;
};



//
// Search Box
//
function PGSearch() {
	this.map = null;
	this.container = null;

	this.initialize = function(map, options) {
		this.map = map;
		this.container = document.createElement("div");
		this.container.style.backgroundColor = "#ffffff";
		this.container.style.padding = "5px";
		this.container.style.border = "1px solid #999999";
		var txtbox = document.createElement("input");
		txtbox.type = "text";
		txtbox.style.width = "300px";
		this.container.appendChild(txtbox);
		
		var searchbox = new google.maps.places.SearchBox(txtbox);
		var markers = new Array();

		google.maps.event.addListener(searchbox, 'places_changed', function () {
			var places = searchbox.getPlaces();
			
			for (var i = 0, marker; marker = markers[i]; i++) {
				marker.setMap(null);
			}
			markers = new Array();

			var bounds = new google.maps.LatLngBounds();
			for (var i = 0, place; place = places[i]; i++) {
				var image = new google.maps.MarkerImage(
					place.icon, new google.maps.Size(71, 71),
					new google.maps.Point(0, 0), new google.maps.Point(17, 34),
					new google.maps.Size(25, 25));

				var marker = new google.maps.Marker({
					map: map,
					icon: image,
					title: place.name,
					position: place.geometry.location
				});

				markers.push(marker);

				bounds.extend(place.geometry.location);
			}

			google.maps.event.addListener(map, 'bounds_changed', function() {
				searchbox.setBounds(map.getBounds());
			});

			map.fitBounds(bounds);
		});

		searchbox.setBounds(map.getBounds());
		map.controls[options.position].push(this.container);
		
	};

}

var PGDropMarker = function(map, options) {
	var title = (! options.title)? 'Drop Marker' : options.title;
	var dropmarker = new google.maps.Marker({
		position: new google.maps.LatLng(map.getCenter()),
		icon: new google.maps.MarkerImage(
			'http://www.google.com/mapfiles/gadget/arrowSmall80.png',
			new google.maps.Size(31, 27),
			new google.maps.Size(0, 0),
			new google.maps.Point(8, 27)),
		map: map,
		draggable: true,
		title: title
	});
	var dropmarkerUp = function(p) {
		dropmarker.setPosition(p);
		google.maps.event.trigger(dropmarker, 'dragend');
		dropmarker.setAnimation(google.maps.Animation.DROP);
	};
	var dropmarker_timer = null;
	google.maps.event.addListener(map, "click", function(event){ 
		dropmarker_timer = setTimeout(function(){dropmarkerUp(event.latLng);}, 300);
	});
	google.maps.event.addListener(map, "dblclick", function(event){ 
		clearTimeout(dropmarker_timer);
	});
	var dropmarkerIni = false;
	google.maps.event.addListener(map, "idle", function(){
		if (!dropmarkerIni) {
			dropmarkerIni = true;
			dropmarkerUp(map.getCenter());
		}
	});
	return dropmarker;
};

var PGMAP_INSERTMARKER_FORM = function(page, mapname, imprefix, err_map_notfind, err_irreg_dat){
	var map = googlemaps_maps[page][mapname];
	var dMarker = (googlemaps_dropmarker[page][mapname])? googlemaps_dropmarker[page][mapname] : false;
	var geocoder = new google.maps.Geocoder();
	if (!map) {
		var form = document.getElementById(imprefix + "_form");
		form.innerHTML = '<div>' + err_map_notfind.replace('\$mapname', map) + '</div>';
	} else {
		var lat   = document.getElementById(imprefix + "_lat");
		var lng   = document.getElementById(imprefix + "_lng");
		var zoom  = document.getElementById(imprefix + "_zoom");
		var mtype = document.getElementById(imprefix + "_mtype");
		var form  = document.getElementById(imprefix + "_form");
		var icon  = document.getElementById(imprefix + "_icon");
		var flat  = document.getElementById(imprefix + "_flat");
		var save_zoom  = document.getElementById(imprefix + "_save_zoom");
		var save_mtype  = document.getElementById(imprefix + "_save_mtype");
		var addr  = document.getElementById(imprefix + "_addr");
		
		var update_func = function() {
			var centerLatlng = (! dMarker)? map.getCenter() : dMarker.getPosition();
			lat.value = PGTool.fmtNum(centerLatlng.lat());
			lng.value = PGTool.fmtNum(centerLatlng.lng());
			zoom.value = parseInt(map.getZoom());
			mtype.value = PGTool.getMapTypeName(map.getMapTypeId());
		};

		var update_addr = function() {
			geocoder.geocode({latLng:(! dMarker)? map.getCenter() : dMarker.getPosition()}, set_addr);
		};

		var set_addr = function(response, status) {
			if (!response || status != google.maps.GeocoderStatus.OK) {
				addr.value = '';
			} else {
				addr.value = response[0]? response[0].formatted_address : '';
				if (addr.value && response[0].address_components) {
					for (var c = response[0].address_components.length-1; c >= 0; c--) {
						if (response[0].address_components[c].short_name == 'JP') {
							addr.value = addr.value.replace(/^[^, ]+, /, '');
							break;
						}
					}
				}
			}
		};

		//Whenever the map is dragged, the parameter is dynamically substituted.
		google.maps.event.addListener(map, 'bounds_changed', update_func);
		google.maps.event.addListener(map, 'maptypeid_changed', update_func);

		if (! dMarker) {
			google.maps.event.addListener(map, 'idle', function(){update_addr(map.getCenter());});
		} else {
			google.maps.event.addListener(dMarker, 'dragend', function(){
				update_addr(dMarker.getPosition());
				update_func();
			});
			update_addr(dMarker.getPosition());
		}
		update_func();

		//The position of the map is initialized if there is a cookie. Contents of the cookie are cleared when finishing using it.
		(function () {
			var kv = XpWiki.cookieLoad(mapname+'_i');
			if (kv.length > 0) {
				var mparam = {lat:0, lng:0, zoom:10, mtype:0};
				var oparam = {flat:false,save_zoom:true,save_mtype:true,save_addr:true,maxzoom:"", minzoom:""};
				var params = decodeURIComponent(kv).split("|");
				for (var j = 0; j < params.length; j++) {
					//dump(params[j] + "=" + params[j+1] + "\\n");
					switch (params[j]) {
						case "lat": mparam.lat = parseFloat(params[++j]); break;
						case "lng": mparam.lng = parseFloat(params[++j]); break;
						case "zoom": mparam.zoom = parseInt(params[++j]); break;
						case "mtype": mparam.mtype = eval('google.maps.MapTypeId.'+params[++j]); break;
						case "flat": oparam.flat = (params[++j] == '1'); break;
						case "save_zoom": oparam.save_zoom = (params[++j] == '1'); break;
						case "save_mtype": oparam.save_mtype = (params[++j] == '1'); break;
						case "save_addr": oparam.save_addr = (params[++j] == '1'); break;
						case "maxzoom": oparam.maxzoom = parseInt(params[++j]); break;
						case "minzoom": oparam.minzoom = parseInt(params[++j]); break;
						default: j++; break;
					}
				}
				onloadfunc2.push(function() {
					map.setCenter(new google.maps.LatLng(mparam.lat, mparam.lng));
					map.setZoom(mparam.zoom);
					map.setMapTypeId(mparam.mtype);
					document.cookie = mapname+'_i=;path=/';
				});
				
				document.getElementById(imprefix + "_flat").checked = oparam.flat;
				document.getElementById(imprefix + "_save_zoom").checked = oparam.save_zoom;
				document.getElementById(imprefix + "_save_mtype").checked = oparam.save_mtype;
				document.getElementById(imprefix + "_save_addr").checked = oparam.save_addr;
				
				var smz;
				var options;
				smz = document.getElementById(imprefix + "_minzoom");
				options = smz.childNodes;
				for (var j=0; j<options.length; j++) {
					var option = options.item(j);
					if (option.value == oparam.minzoom) {
						option.selected = true;
						break;
					}
				}

				smz = document.getElementById(imprefix + "_maxzoom");
				options = smz.childNodes;
				for (var j=0; j<options.length; j++) {
					var option = options.item(j);
					if (option.value == oparam.maxzoom) {
						option.selected = true;
						break;
					}
				}
			}
		})();

		//Input check
		form.onsubmit = function () {
			if (isNaN(parseFloat(lat.value)) || isNaN(lat.value) ||
				isNaN(parseFloat(lng.value)) || isNaN(lng.value)) {
				alert(err_irreg_dat + " LAT : " + lat.value + "  LNG : " + lng.value);
				return false;
			}
			return true;
		};

		//The selection is updated reading all the icon definitions that exist on this page.
		onloadfunc2.push(function() {
			for(iconname in googlemaps_icons[page]) {
				if (!googlemaps_icons[page].hasOwnProperty(iconname) || iconname == 'Default') continue;
				var opt = document.createElement("option");
				opt.value = iconname;
				opt.appendChild(document.createTextNode(iconname));
				icon.appendChild(opt);
			}
		});
	}
};


//
// Marker ON/OFF
//

function p_gmap_marker_toggle (page, mapname, check, name) {
	var markers = googlemaps_markers[page][mapname];
	for (key in markers) {
		if (!markers.hasOwnProperty(key)) continue;
		var m = markers[key];
		if (m.icon == name) {
			if (check.checked) {
				m.show();
			} else {
				m.hide();
			}
		}
	}
}

function p_gmap_togglemarker_checkbox (page, mapname, undefname, defname) {
	var icons = {};
	var markers = googlemaps_markers[page][mapname];
	for (key in markers) {
		if (!markers.hasOwnProperty(key)) continue;
		var map = markers[key].map;
		var icon = markers[key].icon;
		icons[icon] = 1;
	}
	var iconlist = new Array();
	for (n in icons) {
		if (!icons.hasOwnProperty(n)) continue;
		iconlist.push(n);
	}
	iconlist.sort();

	var r = document.createElement("div");
	var map = document.getElementById(mapname);
	map.parentNode.insertBefore(r, map.nextSibling);

	for (i in iconlist) {
		if (!iconlist.hasOwnProperty(i)) continue;
		var name = iconlist[i];
		var id = "ti_" + mapname + "_" + name;
		var input = document.createElement("input");
		var label = document.createElement("label");
		input.setAttribute("type", "checkbox");
		input.id = id;
		label.htmlFor = id;
		if (name == "") {
		label.appendChild(document.createTextNode(undefname));
		} else if (name == "Default") {
		label.appendChild(document.createTextNode(defname));
		} else {
		label.appendChild(document.createTextNode(name));
		}
		eval("input.onclick = function(){p_gmap_marker_toggle('" + page + "','" + mapname + "', this, '" + name + "');}");

		r.appendChild(input);
		r.appendChild(label);
		input.setAttribute("checked", "checked");
	}
}

function p_gmap_regist_marker (page, mapname, center, key, option) {
	if (document.getElementById(mapname) == null) {
		mapname = mapname.replace(/^pukiwikigmap_/, "");
		page = mapname.match(/(^.*?)_/)[1];
		mapname = mapname.replace(/^.*?_/, "");
		alert("googlemaps2: '" + option.title + "' It failed in the marker's registration." +
		"PageName: " + page + ", Not found map name '" + mapname + "'.");
		return;
	}
	option.title = option.title.replace(/&lt;/g, '<');
	option.title = option.title.replace(/&gt;/g, '>');
	option.title = option.title.replace(/&quot;/g, '"');
	option.title = option.title.replace(/&#039;/g, '\'');
	option.title = option.title.replace(/&amp;/g, '&');
	var m = new PGMarker(center, option.icon, option.flat, page, mapname, option.noicon, true, option.title, option.minzoom, option.maxzoom);
	m.setHtml(option.infohtml);
	if (!option.zoom) {
		option.zoom = googlemaps_maps[page][mapname].getZoom();
	}
	m.setZoom(option.zoom);
	if (!option.type) {
		option.type = googlemaps_maps[page][mapname].getMapTypeId();
	}
	m.setType(option.type);
	googlemaps_markers[page][mapname][key] = m;
	if (option.minzoom != 0 || option.maxzoom != 21) {
		googlemaps_zoomarkers[page][mapname][key] = m;
	}
}

function p_googlemaps_mark_to_map (page, mapname) {
	var markers = googlemaps_markers[page][mapname];
	
	for (key in markers) {
		if (!markers.hasOwnProperty(key)) continue;
		var m = markers[key];

		if (m.marker) {
			m.marker.setMap(googlemaps_maps[page][mapname]);
		}
	}
}

function p_gmap_auto_zoom (page, mapname) {

		if (XpWiki.cookieLoad(mapname+'_i')) return; // for insertmarker
		
		var count = 0;
		var map = googlemaps_maps[page][mapname];
		var markers = googlemaps_markers[page][mapname];

		var minLat = 999;
		var minLng = 999;
		var maxLat = 0;
		var maxLng = 0;
		
		var marker, pos;
		for( var key in markers ){
			if (!markers.hasOwnProperty(key)) continue;
			marker = markers[key].marker;
			pos = marker.getPosition();
			minLat =  Math.min(minLat, pos.lat());
			minLng =  Math.min(minLng, pos.lng());
			maxLat =  Math.max(maxLat, pos.lat());
			maxLng =  Math.max(maxLng, pos.lng());
			count++;
		}
		
		if (count > 1) {
			var bounds = new google.maps.LatLngBounds(
					new google.maps.LatLng(maxLat, minLng),
					new google.maps.LatLng(minLat, maxLng));
			map.fitBounds(bounds);
		}
}

XpWiki.domInitFunctionsFirst.push(function() {
//XpWiki.domInitFunctionsFinal.push(function() {
//google.maps.event.addDomListener(window, 'load', function() {
	//if (GBrowserIsCompatible()) {
		while (onloadfunc.length > 0) {
			onloadfunc.shift()();
		}
		while (onloadfunc2.length > 0) {
			onloadfunc2.shift()();
		}
		while (onloadfunc3.length > 0) {
			onloadfunc3.shift()();
		}
	//}
});
