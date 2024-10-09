function ignoreShow(showid, quality) {
	if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	} else {
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}

	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			if (xmlhttp.responseText.match("ignore")) {
				$('.ignore_icon_' + showid).each(function() {
					$(this).attr('src', "ignore.png") ;
				});
				delFavourite(showid, quality)
			}
			if (xmlhttp.responseText.match("show")) {
				$('.ignore_icon_' + showid).each(function() {
					$(this).attr('src', "ignore_grey.png") ;
				});
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
			console.log(xmlhttp.responseText)
				idname="download_icon_" + releaseid;
			   document.getElementById(idname).src="download_done.png";
		}
	}
	xmlhttp.open("GET","?action=downloadrelease&releaseid="+releaseid,true);
	xmlhttp.send();
}

function delFavourite(showid, quality) {
	if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	} else {
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}

	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			if (xmlhttp.responseText == "OK") {
				$('.favourite_icon_' + showid).each(function() {
					$(this).attr('src', "favourite_grey.png");
					$(this).attr("onclick","addFavourite('" + showid + "','" + quality +"');");
				});
			}
		}
	}
	xmlhttp.open("GET","?action=delfavourite&showid="+showid,true);
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
				$('.favourite_icon_' + showid).each(function() {
					$(this).attr('src', "favourite.png");
					$(this).attr("onclick","delFavourite('" + showid + "','" + quality +"');");
				});
			}
		}
	}
	xmlhttp.open("GET","?action=addfavourite&showid="+showid+"&quality="+quality,true);
	xmlhttp.send();
}
function toggleFavourite(favid, quality) {
  if (document.getElementById(favid + "-" + quality).checked) {
      set=1
  } else {
      set=0
  }
  if (window.XMLHttpRequest) {
	xmlhttp=new XMLHttpRequest();
  } else {
        xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlhttp.open("GET","?action=setfavourite&favouriteid="+favid+"&quality="+quality+"&set="+set,true);
  xmlhttp.send();
}
