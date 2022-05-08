
var pics = new Array();
var descs = new Array();
var votes = new Array();
var preLoad = new Array();
var numpics = 0;
var skindir = "";
var selectedpic = 0;
var votelink = "";
var picloc = "";
var userid = 0;

function addPic(id,desc,vote){
	pics[numpics]= id;
	descs[numpics] = desc;
	votes[numpics] = vote;
	numpics++;
}
function setVoteLink(str){
	votelink = str;
}
function setPicLoc(str){
	picloc = str;
}
function setUserid(id){
	userid = id;
}
function setSkinDir(str){
	skindir = str;
}

function changepic(picnum){
	if(picnum < 0 || picnum >= numpics)
		return;

	if(!preLoad[picnum]){
		preLoad[picnum] = new Image();
		preLoad[picnum].src = picloc + Math.floor(pics[picnum]/1000) + "/" + pics[picnum] + ".jpg";
	}

	document.images.userpic.src=preLoad[picnum].src;

	selectedpic=picnum;
	desc = "";
	if(descs[picnum] != "")
		desc = "<br>" + descs[picnum];

	var votestr = "";

	if(votes[picnum]=='1'){
		votestr = votelinks();
	}

	var piclinks = "";
	for(i=0;i<numpics;i++){
		if(i==picnum)
			piclinks += "Pic " + (i+1) + " ";
		else
			piclinks += "<a class=body href=\"javascript: changepic(" + i + ")\">Pic " + (i+1) + "</a> ";
	}

	putinnerHTML('picdesc',desc);
	putinnerHTML('votediv',votestr);
	putinnerHTML('piclinks',piclinks);
}

function votelinks(){
	votestr = "<table cellpadding=0 cellspacing=0 align=center><tr><td align=center class=body>";
	for(i=1;i<=10;i++)
		votestr += "<a class=vote href=\"javascript: votepic(" + i + ")\">&nbsp;" + i + "&nbsp;</a>";
	votestr += "<a class=vote href=\"javascript: votepic(0)\">&nbsp;Skip&nbsp;</a>";
	votestr += "</td></tr></table>";

	return votestr;
}

function votepic(score){
	if(votelink=="")
		return;
	if(score)
		location.href = votelink + "&voteid=" + pics[selectedpic] + "&rating=" + score;
	else
		location.href = votelink + "&voteid=" + pics[selectedpic];
}

function reportabuse(){
	if(numpics == 0)
		location.href="reportabuse.php?type=userabuse&id=" + userid;
	else
		location.href="reportabuse.php?type=picabuse&id=" + pics[selectedpic];
}
