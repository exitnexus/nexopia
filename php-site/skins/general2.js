
var smileypics = new Array();
var smileycodes = new Array();

var smileypos = 0;
var smileycols = 4;
var smileyrows = 6;
var smileyloc = "/images/smilies/";

function confirmLink(link,message){
	if(confirm("Are you sure you want to " + message + "?"))
		location.href=link;
}

function donothing(){
	var x;
}

function goOpener(url) {
	if (opener.closed) {
		alert("The browser window that opened this contact window has been closed. This directory window will not function properly so I will close it. To re-enable it, you must go back go to your Start Menu.");
		window.close();
	} else {
		window.opener.location.href = url;
	}
}

function notice() { return false }
//document.oncontextmenu=notice;

function init(){
	if(top!=self){
//		if(confirm("You may not login while in frames.\nWould you like to be removed from frames?"))
			top.location.href = self.location.href;
	}
}

function getWindowSize(){
	if(self.innerHeight){
		w = self.innerWidth;
		h = self.innerHeight;
	}else if(document.documentElement && document.documentElement.offsetHeight){
		w = document.documentElement.offsetWidth;
		h = document.documentElement.offsetHeight;
	}else if(document.body){
		w = document.body.offsetWidth;
		h = document.body.offsetHeight;
	}

	return [w,h];
}

function putinnerHTML(div,str){
	if(document.all){
		document.all[div].innerHTML = str;
	}else{
		eval("document.getElementById('" + div + "').innerHTML = str;");
	}
}

var checkflag = "false";
function check(field,prefix,check) {
	if(check != null)
		checkflag=check;
	if (checkflag == "false") {
		for (i = 0; i < field.length; i++)
			if(field[i].name.substr(0,prefix.length) == prefix)
				field[i].checked = true;
		checkflag = "true";
		return "Uncheck All";
	}else {
		for (i = 0; i < field.length; i++)
			if(field[i].name.substr(0,prefix.length) == prefix)
				field[i].checked = false;
		checkflag = "false";
		return "Check All";
	}
}

var submit='true';
function checksubmit(){
	if(submit=='true')
		submit = 'false';

	return submit;
}

function storeCaret(textEl) {
	if(textEl.createTextRange)
		textEl.caretPos = document.selection.createRange().duplicate();
}

function insertAtCaret(textEl, text) {
	if(textEl.createTextRange && textEl.caretPos) {
		var caretPos = textEl.caretPos;
		caretPos.text =  caretPos.text.charAt(caretPos.text.length - 1) == ' ' ?    text + ' ' : text;
	}else
		textEl.value  = text;
}


var imageTag = false;
var theSelection = false;


var clientPC = navigator.userAgent.toLowerCase(); // Get client info
var clientVer = parseInt(navigator.appVersion); // Get browser version
var is_ie = ((clientPC.indexOf("msie") != -1) && (clientPC.indexOf("opera") == -1));
var is_nav  = ((clientPC.indexOf('mozilla')!=-1) && (clientPC.indexOf('spoofer')==-1)
                && (clientPC.indexOf('compatible') == -1) && (clientPC.indexOf('opera')==-1)
                && (clientPC.indexOf('webtv')==-1) && (clientPC.indexOf('hotjava')==-1));
var is_win   = ((clientPC.indexOf("win")!=-1) || (clientPC.indexOf("16bit") != -1));
var is_mac    = (clientPC.indexOf("mac")!=-1);


// Define the bbCode tags
bbcode = new Array();
bbtags = new Array('[b]','[/b]','[i]','[/i]','[u]','[/u]','[quote]','[/quote]','[img]','[/img]','[url]','[/url]');
imageTag = false;




function getarraysize(thearray) {
	for (i = 0; i < thearray.length; i++) {
		if ((thearray[i] == "undefined") || (thearray[i] == "") || (thearray[i] == null))
			return i;
		}
	return thearray.length;
}

function arraypush(thearray,value) {
	thearray[ getarraysize(thearray) ] = value;
}

function arraypop(thearray) {
	thearraysize = getarraysize(thearray);
	retval = thearray[thearraysize - 1];
	delete thearray[thearraysize - 1];
	return retval;
}


function checkForm() {
	formErrors = false;

	if (document.editbox.msg.value.length < 2) {
		formErrors = "You must enter a message when posting";
	}

	if (formErrors) {
		alert(formErrors);
		return false;
	} else {
		bbstyle(-1);
		return true;
	}
}

function emoticon(text) {
	text = ' ' + text + ' ';
	if (document.editbox.msg.createTextRange && document.editbox.msg.caretPos) {
		var caretPos = document.editbox.msg.caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;
		document.editbox.msg.focus();
	} else {
		document.editbox.msg.value  += text;
		document.editbox.msg.focus();
	}
}

function bbfontstyle(bbopen, bbclose) {
	if ((clientVer >= 4) && is_ie && is_win) {
		theSelection = document.selection.createRange().text;
		if (!theSelection) {
			document.editbox.msg.value += bbopen + bbclose;
			document.editbox.msg.focus();
			return;
		}
		document.selection.createRange().text = bbopen + theSelection + bbclose;
		document.editbox.msg.focus();
		return;
	} else {
		document.editbox.msg.value += bbopen + bbclose;
		document.editbox.msg.focus();
		return;
	}
	storeCaret(document.editbox.msg);
}

function bbstyle(bbnumber) {
	donotinsert = false;
	theSelection = false;
	bblast = 0;

	if (bbnumber == -1) { // Close all open tags & default button names
		while (bbcode[0]) {
			butnumber = arraypop(bbcode) - 1;
			document.editbox.msg.value += bbtags[butnumber + 1];
			buttext = eval('document.editbox.addbbcode' + butnumber + '.value');
			eval('document.editbox.addbbcode' + butnumber + '.value ="' + buttext.substr(0,(buttext.length - 1)) + '"');
		}
		imageTag = false; // All tags are closed including image tags :D
		document.editbox.msg.focus();
		return;
	}

	if ((clientVer >= 4) && is_ie && is_win)
		theSelection = document.selection.createRange().text; // Get text selection

	if (theSelection) {
		// Add tags around selection
		document.selection.createRange().text = bbtags[bbnumber] + theSelection + bbtags[bbnumber+1];
		document.editbox.msg.focus();
		theSelection = '';
		return;
	}

	// Find last occurance of an open tag the same as the one just clicked
	for (i = 0; i < bbcode.length; i++) {
		if (bbcode[i] == bbnumber+1) {
			bblast = i;
			donotinsert = true;
		}
	}

	if (donotinsert) {		// Close all open tags up to the one just clicked & default button names
		while (bbcode[bblast]) {
				butnumber = arraypop(bbcode) - 1;
				document.editbox.msg.value += bbtags[butnumber + 1];
				buttext = eval('document.editbox.addbbcode' + butnumber + '.value');
				eval('document.editbox.addbbcode' + butnumber + '.value ="' + buttext.substr(0,(buttext.length - 1)) + '"');
				imageTag = false;
			}
			document.editbox.msg.focus();
			return;
	} else { // Open tags

		if (imageTag && (bbnumber != 8)) {		// Close image tag before adding another
			document.editbox.msg.value += bbtags[9];
			lastValue = arraypop(bbcode) - 1;	// Remove the close image tag from the list
			document.editbox.addbbcode8.value = "Img";	// Return button back to normal state
			imageTag = false;
		}

		// Open tag
		document.editbox.msg.value += bbtags[bbnumber];
		if ((bbnumber == 8) && (imageTag == false)) imageTag = 1; // Check to stop additional tags after an unclosed image tag
		arraypush(bbcode,bbnumber+1);
		eval('document.editbox.addbbcode'+bbnumber+'.value += "*"');
		eval('document.editbox.addbbcode'+bbnumber+'.down=true');
		document.editbox.msg.focus();
		return;
	}
	storeCaret(document.editbox.msg);
}


function checkProfileLength(){
	var formErrors = "";
	var maxlength = 10000;
	var siglength = 1000;

	if(document.editbox.about.value.length > maxlength)
		formErrors += "Your About entry is " + (document.editbox.about.value.length - maxlength) + " characters too long\n";

	if(document.editbox.likes.value.length > maxlength)
		formErrors += "Your Likes entry is " + (document.editbox.likes.value.length - maxlength) + " characters too long\n";

	if(document.editbox.dislikes.value.length > maxlength)
		formErrors += "Your Dislikes entry is " + (document.editbox.dislikes.value.length - maxlength) + " characters too long\n";

	if(document.editbox.signiture.value.length > siglength)
		formErrors += "Your Signature is " + (document.editbox.signiture.value.length - siglength) + " characters too long\n";


	if(formErrors != ""){
		alert(formErrors);
		return false;
	}else{
		document.editbox.submit();
	}
}

function editBox(text,smilies){
	var str = "";

	str += "\n<table cellspacing=0 align=center>";
	str += "<tr><td align=center>";
		str += "<input class=body type=button class=button accesskey=b name=addbbcode0 value=' B ' style='font-weight:bold; width: 30px' onClick='bbstyle(0)'>";
		str += "<input class=body type=button class=button accesskey=i name=addbbcode2 value=' i ' style='font-style:italic; width: 30px' onClick='bbstyle(2)'>";
		str += "<input class=body type=button class=button accesskey=u name=addbbcode4 value=' u ' style='text-decoration: underline; width: 30px' onClick='bbstyle(4)'>";
		str += "<input class=body type=button class=button accesskey=q name=addbbcode6 value='Quote' style='width: 50px' onClick='bbstyle(6)'>";
		str += "<input class=body type=button class=button accesskey=p name=addbbcode8 value='Img' style='width: 40px'  onClick='bbstyle(8)'>";
		str += "<input class=body type=button class=button accesskey=w name=addbbcode10 value='URL' style='text-decoration: underline; width: 40px' onClick='bbstyle(10)'>";
		str += "<select style='width: 60px' class=body onChange=\"if(this.selectedIndex!=0) bbfontstyle('[font=' + this.options[this.selectedIndex].value + ']', '[/font]');this.selectedIndex=0\">";
			str += "<option value='0'>Font</option>";
			str += "<option value='Arial' style='font-family:Arial'>Arial</option>";
			str += "<option value='Times' style='font-family:Times'>Times</option>";
			str += "<option value='Courier' style='font-family:Courier'>Courier</option>";
			str += "<option value='Impact' style='font-family:Impact'>Impact</option>";
			str += "<option value='Geneva' style='font-family:Geneva'>Geneva</option>";
			str += "<option value='Optima' style='font-family:Optima'>Optima</option>";
		str += "</select>";
		str += "<select style='width: 60px' class=body onChange=\"if(this.selectedIndex!=0) bbfontstyle('[color=' + this.options[this.selectedIndex].value + ']', '[/color]');this.selectedIndex=0\">";
			str += "<option style='color:black; background-color: #FFFFFF' value='0'>Color</option>";
			str += "<option style='color:darkred; background-color: #DEE3E7' value='darkred'>Dark Red</option>";
			str += "<option style='color:red; background-color: #DEE3E7' value='red'>Red</option>";
			str += "<option style='color:orange; background-color: #DEE3E7' value='orange'>Orange</option>";
			str += "<option style='color:brown; background-color: #DEE3E7' value='brown'>Brown</option>";
			str += "<option style='color:yellow; background-color: #DEE3E7' value='yellow'>Yellow</option>";
			str += "<option style='color:green; background-color: #DEE3E7' value='green'>Green</option>";
			str += "<option style='color:olive; background-color: #DEE3E7' value='olive'>Olive</option>";
			str += "<option style='color:cyan; background-color: #DEE3E7' value='cyan'>Cyan</option>";
			str += "<option style='color:blue; background-color: #DEE3E7' value='blue'>Blue</option>";
			str += "<option style='color:darkblue; background-color: #DEE3E7' value='darkblue'>Dark Blue</option>";
			str += "<option style='color:indigo; background-color: #DEE3E7' value='indigo'>Indigo</option>";
			str += "<option style='color:violet; background-color: #DEE3E7' value='violet'>Violet</option>";
			str += "<option style='color:white; background-color: #DEE3E7' value='white'>White</option>";
			str += "<option style='color:black; background-color: #DEE3E7' value='black'>Black</option>";
		str += "</select>";
		str += "<select style='width: 60px' class=body onChange=\"if(this.selectedIndex!=0) bbfontstyle('[size=' + this.options[this.selectedIndex].value + ']', '[/size]');this.selectedIndex=0\">";
			str += "<option value=0>Size</option>";
			str += "<option value=1>Tiny</option>";
			str += "<option value=2>Small</option>";
			str += "<option value=3>Normal</option>";
			str += "<option value=4>Large</option>";
			str += "<option value=5>Huge</option>";
		str += "</select>";
	str += "</td>\n";

	if(smilies){
		str += "<td rowspan=2>";
		str += "<table cellspacing=0 cellpadding=3 border=1 style=\"border-collapse: collapse\">";
		var num = Math.min(smileycols * smileyrows, Math.ceil(smileycodes.length/smileycols)*smileycols);
		for(i=0; i < num ;i++){
			if(i % smileycols == 0)
				str += "<tr>";

			str += "<td class=body><div name=smiley" + i + " id=smiley" + i + ">";
			if(i < smileycodes.length)
				str += "<a href=\"javascript:emoticon('" + smileycodes[i] + "')\"><img src=\"" + smileyloc + smileypics[i] + ".gif\" alt=\"" + smileycodes[i] + "\" border=0></a>";
			str += "</div></td>";

			if(i % smileycols == smileycols - 1)
				str += "</tr>";
		}
		str += "<tr><td colspan=" + smileycols + " class=body>";

		str += "<table width=100%><tr>";
		str += "<td class=body><a class=body href=\"javascript:smiliespage(-1);\">Prev</a></td>";
		str += "<td class=body align=right><a class=body href=\"javascript:smiliespage(1);\">Next</a></td>";
		str += "</tr></table>";

		str += "</td></tr>";
		str += "</table>";
		str += "</td>\n";
	}

	str += "</tr>";
	str += "<tr><td align=center><textarea style='width: 400px' class=header cols=70 rows=10 name=msg wrap=virtual onSelect=\"storeCaret(this);\" onClick=\"storeCaret(this);\" onKeyUp=\"storeCaret(this);\">" + text + "</textarea></td></tr>\n";
	str += "</table>\n";

	return str;
}


function smiliespage(dir){
	var max = Math.ceil(smileycodes.length / (smileycols * smileyrows) );

	smileypos = (dir+smileypos+max)%max;

	var num = Math.min(smileycols * smileyrows, smileycodes.length);
	for(i= smileypos*num; i < (smileypos+1)*num;i++){
		if(i < smileycodes.length)
			putinnerHTML("smiley" + (i%num), "<a href=\"javascript:emoticon('" + smileycodes[i] + "')\"><img src=\"" + smileyloc + smileypics[i] + ".gif\" alt=\"" + smileycodes[i] + "\" border=0></a>");
		else
			putinnerHTML("smiley" + (i%num), "");
	}
}


