
var ids = new Array();
var pics = new Array();
var descs = new Array();
var preLoad = new Array();
var numpics = 0;
var selectedpic = 0;
//var picloc = "";
var userid = 0;

function addPic(id, loc, desc){
	ids[numpics]= id;
	pics[numpics]= loc;
	descs[numpics] = desc;
	numpics++;
}
function setUserid(id){
	userid = id;
}

function changepic(picnum){
	if(picnum < 0 || picnum >= numpics)
		return;

	if(preLoad.length == 0){
		for(i=0;i<numpics;i++){
			preLoad[i] = new Image();
			preLoad[i].src = pics[i];
		}
	}

	var hidediv = document.getElementById('hidediv');
	hidediv.style.height = 0;
	hidediv.style.width = 0;

	document.images.userpic.src=preLoad[picnum].src;

	selectedpic=picnum;
	desc = "";
	if(descs[picnum] != "")
		desc = "<br>" + descs[picnum];

	var piclinks = "";
	for(i=0;i<numpics;i++){
		if(i==picnum)
			piclinks += "Pic " + (i+1) + " ";
		else
			piclinks += "<a class=body href=\"javascript: changepic(" + i + ")\">Pic " + (i+1) + "</a> ";
	}

	putinnerHTML('picdesc',desc);
	putinnerHTML('piclinks',piclinks);
}

function hidepics(retrytime){
	if(retrytime === null)
	 	retrytime = 100;

	var hidediv = document.getElementById('hidediv');
	var userpic = document.images.userpic;

	if(userpic.height){
		hidediv.style.height = userpic.height;
		hidediv.style.width = userpic.width + userpic.offsetLeft; //UGLY!!!!!!!! makes it sorta cross browser though.
	}else if(retrytime){
		setTimeout(function() { hidepics(0); }, retrytime);
	}
}
