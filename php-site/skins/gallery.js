
var pics = new Array();
var descs = new Array();
var preLoad = new Array();
var numpics = 0;
var selectedpic = 0;
var picloc = "";
var thumbloc = "";
var numcols = 5;
var gallerytitle = "";
var userid = 0;

function addPic(id,desc){
	pics[numpics]= id;
	descs[numpics] = desc;
	numpics++;
}

function setGalleryPicLoc(str){
	picloc = str;
}

function setGalleryThumbLoc(str){
	thumbloc = str;
}

function setGalleryTitle(str){
	gallerytitle = str;
}

function setUserid(uid){
	userid = uid;
}

function changepic(picnum){
	if(picnum < 0 || picnum >= numpics)
		return;

	if(!preLoad[picnum]){
		preLoad[picnum] = new Image();
		preLoad[picnum].src = picloc + Math.floor(pics[picnum]/1000) + "/" + pics[picnum] + ".jpg";
	}

	selectedpic=picnum;
	desc = "";
	if(descs[picnum] != "")
		desc = "<br>" + descs[picnum];

	if(document.all){
		var piclinks = "<a class=body href=gallery.php?uid=" + userid + "><b>" + gallerytitle + "</b></a><br>";

		piclinks += "<center>";
		piclinks += (picnum + 1) + "/" + numpics + " | ";

		if(picnum <= 0)
			pic = numpics-1
		else
			pic = picnum-1;
		piclinks += "<a class=body href=\"javascript: changepic(" + (pic) + ")\">Prev</a> | ";
		piclinks += "<a class=body href=\"javascript: showthumbs()\">Thumbnails</a>";

		if(picnum < numpics-1)
			pic = picnum+1
		else
			pic = 0;
		piclinks += " | <a class=body href=\"javascript: changepic(" + (pic) + ")\">Next</a>";


		piclinks += " | <a class=body href=\"reportabuse.php?type=galleryabuse&id=" + pics[picnum] + "\">Report Abuse</a>";
		piclinks += "</center>";
	}else{
		var piclinks = "<table width=640>";

		piclinks += "<tr><td class=header align=center colspan=" + numcols + "><a class=header href=gallery.php?uid=" + userid + "><b>" + gallerytitle + "</b></td></tr>";

		piclink += "<tr>";

		piclinks += "<td class=body width=100>" + (picnum + 1) + "/" + numpics + "</td>";

		piclinks += "<td align=center class=body width=200>";
		if(picnum <= 0)
			pic = numpics-1
		else
			pic = picnum-1;
		piclinks += "<a class=body href=\"javascript: changepic(" + (pic) + ")\">Prev</a> | ";
		piclinks += "<a class=body href=\"javascript: showthumbs()\">Thumbnails</a>";

		if(picnum < numpics-1)
			pic = picnum+1
		else
			pic = 0;
		piclinks += " | <a class=body href=\"javascript: changepic(" + (pic) + ")\">Next</a>";

		piclinks += "</td>";
		piclinks += "<td align=right class=body width=100>";
		piclinks += "<a class=body href=\"reportabuse.php?type=galleryabuse&id=" + pics[picnum] + "\">Report Abuse</a>";
		piclinks += "</td>";
		piclinks += "</tr></table>";
	}

	putinnerHTML('picdesc',desc)
	putinnerHTML('piclinks',piclinks)
	document.images.gallerypic.src=preLoad[picnum].src;
}

function showthumbs(){

	if(document.all){
		str = "<center><a class=body href=gallery.php?uid=" + userid + "><b>" + gallerytitle + "</b><br>";

		var i = 0;
		while(1){
			if(i < numpics)
				str += "<a class=body href=\"javascript: showthumb(" + i + ")\"><img src=\"" + thumbloc + Math.floor(pics[i]/1000) + "/" + pics[i] + ".jpg\" border=0></a> &nbsp; ";

			if((i % numcols == numcols-1) || (i >= numpics-1)){
				str += "<br><br>";
				if(i >= numpics-1)
					break;
			}else{
				str += " &nbsp; ";
			}

			i++;
		}
		str += "</center>";
	}else{
		str = "<table align=center cellspacing=10>";

		str += "<tr><td class=header align=center colspan=" + numcols + "><a class=header href=gallery.php?uid=" + userid + "><b>" + gallerytitle + "</b></td></tr>";

		var numrows = Math.ceil(numpics/numcols);

		var i = 0;
		while(1){
			if(i % numcols == 0)
				str += "<tr>";
			str += "<td class=body align=center>";

			if(i < numpics)
				str += "<a class=body href=\"javascript: showthumb(" + i + ")\"><img src=\"" + thumbloc + Math.floor(pics[i]/1000) + "/" + pics[i] + ".jpg\" border=0></a>";

			if((i % numcols == numcols-1) || (numrows==1 && i == numpics-1)){
				str += "</tr>";
				if(i >= numpics-1)
					break;
			}

			i++;
		}
		str += "</table>";
	}
	putinnerHTML('outerdiv',str);
}

function showthumb(picnum){
	str = "<center><div id=piclinks name=piclinks></div><img name=gallerypic id=gallerypic><div id=picdesc name=picdesc></div></center>";

	putinnerHTML('outerdiv',str);

	changepic(picnum);
}
