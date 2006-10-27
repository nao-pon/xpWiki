function code_outline(id,path)
{
    if(navigator.appVersion.match(/MSIE\s*(6\.|5\.5)/)){
        if(document.getElementById(id+"_img")) {
			document.getElementById(id+"_img").style.height="1.2em";
			document.getElementById(id+"_img").style.verticalAlign="bottom";
		}
    }
	var dotimage = "<img src=\""+path+"code_dot.png\" alt=\"\" title=\"...\" />";
    var vis = document.getElementById(id).style.display;
    if (vis=="none") {
		disp = '';
		ch = '-';
		//ch = '<img src="'+path+'treemenu_triangle_open.png"  width="9" height="9" alt="-" title="close"  class="code" />';
    } else {
		disp = 'none';
		ch = '+';
		//ch = '<img src="'+path+'treemenu_triangle_close.png" width="9" height="9" alt="+" title="open" class="code" />';
	}
	if (document.getElementById(id)) document.getElementById(id).style.display = disp;
	if (document.getElementById(id+"n")) document.getElementById(id+"n").style.display = disp;
	if (document.getElementById(id+"o")) document.getElementById(id+"o").style.display = disp;
	if (document.getElementById(id+"a")) document.getElementById(id+"a").innerHTML = ch;
    if (vis=="none") {
		if (document.getElementById(id+"_img")) document.getElementById(id+"_img").innerHTML = '';
	} else {
		if (document.getElementById(id+"_img")) document.getElementById(id+"_img").innerHTML=dotimage;
	}

}

function code_classname(id,num,disp,cname,path)
{
    var ch = '';
	var dotimage = '';

    if (disp=="") {
        ch = '-';
		//ch = '<img src="'+path+'treemenu_triangle_open.png"  width="9" height="9" alt="-" title="close"  class="code" />';
		dotimage = '';
    } else {
		ch = '+';
		//ch = '<img src="'+path+'treemenu_triangle_close.png" width="9" height="9" alt="+" title="open" class="code" />';
		dotimage = "<img src=\""+path+"code_dot.png\" alt=\"\" title=\"...\" />";
	}

    for (var i=num; i>0; i--) {
		if (document.getElementById(id+"_"+i)) {
			if (document.getElementById(id+"_"+i).className == cname) {
				if (document.getElementById(id+"_"+i).className == 'code_block' && navigator.appVersion.match(/MSIE\s*(6\.|5\.5)/)) {
					document.getElementById(id+"_"+i+"_img").style.height = "1.2em";
					document.getElementById(id+"_"+i+"_img").style.verticalAlign = "bottom";
				}
				if (document.getElementById(id+"_"+i+"o")) {
					if (document.getElementById(id+"_"+i)) {
						document.getElementById(id+"_"+i).style.display = disp;
						if (document.getElementById(id+"_"+i).className == 'code_block' && document.getElementById(id+"_"+i+"_img"))
							document.getElementById(id+"_"+i+"_img").innerHTML=dotimage;
					}
				}
				if (document.getElementById(id+"_"+i+"n")) document.getElementById(id+"_"+i+"n").style.display = disp;
				if (document.getElementById(id+"_"+i+"o")) document.getElementById(id+"_"+i+"o").style.display = disp;
				if (document.getElementById(id+"_"+i+"a")) document.getElementById(id+"_"+i+"a").innerHTML = ch;
			}	
		}
	}
}
