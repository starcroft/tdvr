function ignoreShow(showid) {
	if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	} else {
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}

	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			if (xmlhttp.responseText == "OK") {
				idname="ignore_icon_" + showid;
			   document.getElementById(idname).src="ignore.png";
			}
		}
	}	
	xmlhttp.open("GET","?action=ignoreshow&showid="+showid,true);
	xmlhttp.send();	
}

function downloadRelease(releaseid) {
	if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	} else {
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}

	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
				idname="download_icon_" + releaseid;
			   document.getElementById(idname).src="download_done.png";
		}
	}	
	xmlhttp.open("GET","?action=downloadrelease&releaseid="+releaseid,true);
	xmlhttp.send();	
}

function addFavourite(showid, quality) {
	if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	} else {
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}

	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			if (xmlhttp.responseText == "OK") {
				idname="favourite_icon_" + showid;
			document.getElementById(idname).src="favourite.png";
			}
		}
	}	
	xmlhttp.open("GET","?action=addfavourite&showid="+showid+"&quality="+quality,true);
	xmlhttp.send();	
}