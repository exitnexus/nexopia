
var smileypics = new Array();
var smileycodes = new Array();

var smileypos = new Object();
var smileycols = 4;
var smileyrows = 7;
var smileyloc = "/images/smilies/";

String.prototype.trim = function()
{
	return this.replace(/^\s+|\s+$/g, '');
}

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

function doInterstitial(){
	var interstitial = document.getElementById("interstitial_background");
	if (interstitial){
		var docHeight;
		if (typeof document.height != 'undefined'){
			docHeight = document.height;
		}
		else if (document.compatMode && document.compatMode != 'BackCompat'){
			docHeight = document.documentElement.scrollHeight;
		}
		else if (document.body && typeof document.body.scrollHeight != 'undefined'){
			docHeight = document.body.scrollHeight;
		}
		interstitial.style.height = "" + docHeight + "px";
	}
}

function initFrames(){
	doInterstitial();
}

function init(){
	if(top!=self){
//		if(confirm("You may not login while in frames.\nWould you like to be removed from frames?"))
			top.location.href = self.location.href;
	}
	
	doInterstitial();
	
}

function getWindowSize(){
	w = h = 0;
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

function setSelectionRange(input, selectionStart, selectionEnd) {
  if (input.setSelectionRange) {
    input.focus();
    input.setSelectionRange(selectionStart, selectionEnd);
  }
  else if (input.createTextRange) {
    var range = input.createTextRange();
    range.collapse(true);
    range.moveEnd('character', selectionEnd);
    range.moveStart('character', selectionStart);
    range.select();
  }
}
function setCaretToEnd (input) {
  setSelectionRange(input, input.value.length, input.value.length);
}
function setCaretToBegin (input) {
  setSelectionRange(input, 0, 0);
}
function setCaretToPos (input, pos) {
  setSelectionRange(input, pos, pos);
}
function selectString (input, string) {
  var match = new RegExp(string, "i").exec(input.value);
  if (match) {
    setSelectionRange (input, match.index, match.index + match[0].length);
  }
}
function replaceSelection (input, replaceString) {

  if (input.setSelectionRange) {
    var selectionStart = input.selectionStart;
    var selectionEnd = input.selectionEnd;
    input.value = input.value.substring(0, selectionStart)
                  + replaceString
                  + input.value.substring(selectionEnd);
    if (selectionStart != selectionEnd) // has there been a selection
      setSelectionRange(input, selectionStart, selectionStart + replaceString.length);
    else // set caret
      setCaretToPos(input, selectionStart + replaceString.length);
  }
  else if (document.selection) {
    var range = document.selection.createRange();
    if (range.parentElement() == input) {
      var isCollapsed = range.text == '';
      range.text = replaceString;
      if (!isCollapsed)  { // there has been a selection
        //it appears range.select() should select the newly
        //inserted text but that fails with IE
        range.moveStart('character', -replaceString.length);
        range.select();
      }
    }else if (input.caretPos) {
      var caretPos = input.caretPos;
      caretPos.text = replaceString;
      input.focus();
	}
  } else {
  	input.value += replaceString;
  	input.focus();
  }
}
function emoticon(text, id, formid, maxlength) {
	text = ' ' + text + ' ';
	replaceSelection(document.getElementById(id), text);
	if(maxlength > 0)
		setTextAreaLength(document.getElementById(id), maxlength, "length_"+id);
}

function bbfontstyle(bbopen, bbclose, id, formid, maxlength) {
	if (document.selection)
	{
		theSelection = document.selection.createRange().text;
		if (!theSelection) {
			document.getElementById(id).value +=  bbopen + bbclose;
			document.getElementById(id).focus();

			if(maxlength > 0)
				setTextAreaLength(document.getElementById(id), maxlength, "length_"+id);
			return;
		}
		document.selection.createRange().text = bbopen + theSelection + bbclose;
		eval('document.'+formid+'.'+id+'.focus()');
		if(maxlength > 0)
			setTextAreaLength(document.getElementById(id), maxlength, "length_"+id);
		return;
	} else {
		strt = document.getElementById(id).selectionStart;
        end  = document.getElementById(id).selectionEnd;
		curtext = document.getElementById(id).value;
        text = curtext.substr(0,strt) + bbopen + curtext.substr(strt,end-strt)+  bbclose + curtext.substr(end, curtext.length-end);
        document.getElementById(id).value = text;
		document.getElementById(id).focus();
		if(maxlength > 0)
			setTextAreaLength(document.getElementById(id), maxlength, "length_"+id);
		return;
	}
	storeCaret(document.getElementById(id));
	if(maxlength > 0)
		setTextAreaLength(document.getElementById(id), maxlength, "length_"+id);
}

function bbstyle(bbnumber, id, formid, maxlength) {
	donotinsert = false;
	theSelection = false;
	bblast = 0;

	if (bbnumber == -1) { // Close all open tags & default button names
		while (bbcode[0]) {
			butnumber = arraypop(bbcode) - 1;
			document.getElementById(id).value +=  bbtags[butnumber + 1];
			buttext = document.getElementById(formid + '_addbbcode' + butnumber).value;
			document.getElementById(formid + '_addbbcode' + butnumber).value = buttext.substr(0,(buttext.length - 1));
		}
		imageTag = false; // All tags are closed including image tags :D
		document.getElementById(id).focus();
		if(maxlength > 0)
			setTextAreaLength(document.getElementById(id), maxlength, "length_"+id);
		return;
	}

	if (document.selection)
	{
        theSelection = document.selection.createRange().text; // Get text selection

        if (theSelection) {
            // Add tags around selection
            document.selection.createRange().text = bbtags[bbnumber] + theSelection + bbtags[bbnumber+1];
            document.getElementById(id).focus();
            theSelection = '';
            if(maxlength > 0 )
				setTextAreaLength(document.getElementById(id), maxlength, "length_"+id);
            return;
        }
	}
	else
	{
        strt = document.getElementById(id).selectionStart;
        end  = document.getElementById(id).selectionEnd;

		// add tags around selection
		if (strt != end) {
	        curtext = document.getElementById(id).value;
    	    text = curtext.substr(0,strt) + bbtags[bbnumber] + curtext.substr(strt,end-strt)+  bbtags[bbnumber+1] + curtext.substr(end, curtext.length-end);
	        document.getElementById(id).value =  text;
    	    theSelection = '';
	        if(maxlength)
				setTextAreaLength(document.getElementById(id), maxlength, "length_"+id);
        	return;
		}
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
				document.getElementById(id).value += bbtags[butnumber + 1];
				buttext = document.getElementById(formid + '_addbbcode' + butnumber).value;
				document.getElementById(formid + '_addbbcode' + butnumber).value = buttext.substr(0,(buttext.length - 1));
				imageTag = false;
			}
			document.getElementById(id).focus();
			if(maxlength > 0)
				setTextAreaLength(document.getElementById(id), maxlength, "length_"+id);
			return;
	} else { // Open tags
		if (imageTag && (bbnumber != 8)) {		// Close image tag before adding another
			document.getElementById(id).value +=  bbtags[9];
			lastValue = arraypop(bbcode) - 1;	// Remove the close image tag from the list
			document.getElementById(formid + '_addbbcode8').value = "Img";	// Return button back to normal state
			imageTag = false;
		}

		// Open tag
		document.getElementById(id).value +=  bbtags[bbnumber];
		if ((bbnumber == 8) && (imageTag == false)) imageTag = 1; // Check to stop additional tags after an unclosed image tag
		arraypush(bbcode,bbnumber+1);
		document.getElementById(formid + '_addbbcode' + bbnumber).value += "*";
		document.getElementById(formid + '_addbbcode' + bbnumber).down=true;
		document.getElementById(id).focus();
		if(maxlength > 0)
			setTextAreaLength(document.getElementById(id), maxlength, "length_"+id);
		return;
	}
	storeCaret(document.getElementById(id));
}


function setTextAreaLength(textarea, maxlength, divid, height, width)
{

	if(maxlength > 0)
	{
		text = textarea.value;
		div = document.getElementById(divid);
		if (text.length+1 > maxlength)
			textarea.value = textarea.value.substring(0, maxlength);
		if(div)
			div.innerHTML = "Length: "+text.length+" / "+ maxlength;

	}
}

function editBox(text,smilies,id,formid, maxlength,height, width){
	var str = "";

	str += "\n<table cellspacing=0 align=center>";
	if(maxlength > 0)
		str += "<tr><td class=body align=center><div id=length_"+id+">Length: 0 / " + maxlength + "</div>";
	else
		maxlength = 0;

	str += "<tr><td align=center>";
			str += "<input class=body type=button class=button accesskey=b id='" + formid + "_addbbcode0' value=' B ' style='font-weight:bold; width: 30px' onClick='bbstyle(0,\""+id+"\" ,\""+formid+"\", \""+maxlength+"\" )'>";
		str += "<input class=body type=button class=button accesskey=i id='" + formid + "_addbbcode2' value=' i ' style='font-style:italic; width: 30px' onClick='bbstyle(2, \""+id+"\" ,\""+formid+"\",\""+maxlength+"\")'>";
		str += "<input class=body type=button class=button accesskey=u id='" + formid + "_addbbcode4' value=' u ' style='text-decoration: underline; width: 30px' onClick='bbstyle(4, \""+id+"\" ,\""+formid+"\",\""+maxlength+"\")'>";
		str += "<input class=body type=button class=button accesskey=q id='" + formid + "_addbbcode6' value='Quote' style='width: 50px' onClick='bbstyle(6, \""+id+"\" ,\""+formid+"\",\""+maxlength+"\")'>";
		str += "<input class=body type=button class=button accesskey=p id='" + formid + "_addbbcode8' value='Img' style='width: 40px'  onClick='bbstyle(8, \""+id+"\" ,\""+formid+"\",\""+maxlength+"\")'>";
		str += "<input class=body type=button class=button accesskey=w id='" + formid + "_addbbcode10' value='URL' style='text-decoration: underline; width: 40px' onClick='bbstyle(10, \""+id+"\" ,\""+formid+"\",\""+maxlength+"\")'>";
		str += "<select style='width: 60px' class=body onChange=\"if(this.selectedIndex!=0) bbfontstyle('[font=' + this.options[this.selectedIndex].value + ']', '[/font]', '"+id+"' ,'"+formid+"',"+maxlength+");this.selectedIndex=0\">";
			str += "<option value='0'>Font</option>";
			str += "<option value='Arial' style='font-family:Arial'>Arial</option>";
			str += "<option value='Times' style='font-family:Times'>Times</option>";
			str += "<option value='Courier' style='font-family:Courier'>Courier</option>";
			str += "<option value='Impact' style='font-family:Impact'>Impact</option>";
			str += "<option value='Geneva' style='font-family:Geneva'>Geneva</option>";
			str += "<option value='Optima' style='font-family:Optima'>Optima</option>";
		str += "</select>";
		str += "<select style='width: 60px' class=body onChange=\"if(this.selectedIndex!=0) bbfontstyle('[color=' + this.options[this.selectedIndex].value + ']', '[/color]', '"+id+"' ,'"+formid+"',"+maxlength+");this.selectedIndex=0\">";
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
		str += "<select style='width: 60px' class=body onChange=\"if(this.selectedIndex!=0) bbfontstyle('[size=' + this.options[this.selectedIndex].value + ']', '[/size]', '"+id+"' ,'"+formid+"',"+maxlength+");this.selectedIndex=0\">";
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

			str += "<td class=body><div name=smiley_"+formid + i + " id=smiley_"+formid + i + ">";
			if(i < smileycodes.length)
				str += "<a href=\"javascript:emoticon('" + smileycodes[i] + "', '"+id+"' ,'"+formid+"',"+maxlength+")\"><img src=\"" + smileyloc + smileypics[i] + ".gif\" alt=\"" + smileycodes[i] + "\" border=0></a>";
			str += "</div></td>";

			if(i % smileycols == smileycols - 1)
				str += "</tr>";

			smileypos[formid] = 0;
		}
		str += "<tr><td colspan=" + smileycols + " class=body>";

		str += "<table width=100%><tr>";
		str += "<td class=body><a class=body href=\"javascript:smiliespage(-1,'"+id+"' ,'"+formid+"',"+maxlength+");\">Prev</a></td>";
		str += "<td class=body align=right><a class=body href=\"javascript:smiliespage(1,'"+id+"' ,'"+formid+"',"+maxlength+");\">Next</a></td>";
		str += "</tr></table>";

		str += "</td></tr>";
		str += "</table>";
		str += "</td>\n";
	}

	str += "</tr>";

	str += "<tr ><td align=center ><textarea style='width: "+(width-150)+"px;' class=header rows="+Math.ceil(height/15)+"  id='"+id+"' name='"+id+"' wrap=virtual onSelect=\"storeCaret(this);\" onClick=\"storeCaret(this);\"   onKeyUp=\"storeCaret(this);setTextAreaLength(this,"+maxlength+",'length_"+id+"')\">" + text + "</textarea></td></tr>\n";
	str += "</table>\n";
	if(maxlength > 0)
	str += "<script>document.write('<script>setTextAreaLength(document.getElementById(\""+id+"\"),"+maxlength+",\"length_"+id+"\")<'+'/script>')</script>";
	return str;
}



function smiliespage(dir, id, formid, maxlength){
	var max = Math.ceil(smileycodes.length / (smileycols * smileyrows) );

	smileypos[formid] = (dir+smileypos[formid]+max)%max;

	var num = Math.min(smileycols * smileyrows, smileycodes.length);
	for(i= smileypos[formid]*num; i < (smileypos[formid]+1)*num;i++){
		if(i < smileycodes.length)
			putinnerHTML("smiley_"+formid + (i%num), "<a href=\"javascript:emoticon('" + smileycodes[i] + "', '"+id+"' ,'"+formid+"', "+maxlength+")\"><img src=\"" + smileyloc + smileypics[i] + ".gif\" alt=\"" + smileycodes[i] + "\" border=0></a>");
		else
			putinnerHTML("smiley_"+formid + (i%num), "");
	}
}

function getForm(formid)
{
	if (document.getElementById)
	{
		return document.getElementById(formid);
	}
	return false;
}

function getHTTPObject() {
	var xmlhttp;
	/*@cc_on
	@if (@_jscript_version >= 5)
	try {
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
	try {
		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
			xmlhttp = false;
		}
	}
	@else
	xmlhttp = false;
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
		try {
			xmlhttp = new XMLHttpRequest();
		} catch (e) {
			xmlhttp = false;
		}
	}
	return xmlhttp;
}

var changes = new Array();
var timeout;

function collapseTableRows(catid, tableid, textexpanded, textcollapsed, ajaxurl)
{
	if (document.getElementById)
	{
		clearTimeout(timeout);
		window.onbeforeunload = function() {};
		window.onunload = function() {};

		var linkid = 'expand' + catid;
		var rowheaderid = 'category' + catid;
		var textid = 'count' + catid;

		var tableobj = document.getElementById(tableid);
		var found = 0;
		var collapsed = 0;
		for (var rowidx = 0; rowidx < tableobj.rows.length; rowidx++)
		{
			var rowobj = tableobj.rows[rowidx];
			if (found)
			{
				if (rowobj.id == '')
				{
					if (rowobj.style.display == 'none')
					{
						rowobj.style.display = "";
						collapsed = 0;
					} else {
						rowobj.style.display = "none";
						collapsed = 1;
					}
				} else {
					break; // found another header object, exit
				}
			}
			if (rowobj.id == rowheaderid)
			{
				found = 1;
			}
		}
		linkobj = document.getElementById(linkid);
		if (linkobj)
		{
			if (collapsed)
				linkobj.innerHTML = "[+]";
			else
				linkobj.innerHTML = "[-]";
		}

		textobj = document.getElementById(textid);
		if (textobj)
		{
			if (collapsed)
				textobj.innerHTML = textcollapsed;
			else
				textobj.innerHTML = textexpanded;
		}

		if(changes[catid] == undefined)
			changes[catid] = collapsed;
		else
			changes[catid] = undefined;

		var send = 0;
		for(var i in changes){
			if(changes[i] != undefined){
				ajaxurl += '&collapse[' + i + ']=' + changes[i];
				send = 1;
			}
		}

		if(send)
		{
			timeout = setTimeout("AjaxSendURL('" + ajaxurl + "&timeout', true)", 2000);
			if (window.onbeforeunload)
				window.onbeforeunload = function() { window.onunload = function() {}; AjaxSendURL(ajaxurl + "&onbeforeunload", false) };
			if (window.onunload)
				window.onunload = function() { clearTimeout(timeout); AjaxSendURL(ajaxurl + "&onunload", false) };
		}
	}
}

function AjaxSendURL(ajaxurl, async){
	var http = getHTTPObject();
	if (http){
		http.open("GET", ajaxurl, async);
		http.send(null);
	}
	changes = new Array();
}

function subscribeThread(){
	if(!http){
		var http = getHTTPObject();
		if(!http)
			return false;
	}

	var obj = document.getElementById("subscribe");
	var obj2 = document.getElementById("subscribe2");
	var url = obj.href;
	var title = obj.innerHTML.trim();

	http.open("GET", url + "&noreload=1", true);
	http.send(null);

	surl = new String(url);

	if(title == 'Subscribe'){
		title = 'Unsubscribe';
		surl = surl.replace('subscribe', 'unsubscribe');
	}else{
		title = 'Subscribe';
		surl = surl.replace('unsubscribe', 'subscribe');
	}

	obj.innerHTML = title;
	obj.href = surl;

	obj2.innerHTML = title;
	obj2.href = surl;

	return true;
}

function markMsg(id){
	if(!http){
		var http = getHTTPObject();
		if(!http)
			return false;
	}

	var obj = document.getElementById("mark" + id);
	var url = obj.href;
	var title = obj.innerHTML;

	http.open("GET", url + "&noreload=1", true);
	http.send(null);

	surl = new String(url);

	if(title == '&nbsp; - &nbsp;'){
		title = '&nbsp;(+)&nbsp;';
		surl = surl.replace('mark', 'unmark');
	}else{
		title = '&nbsp; - &nbsp;';
		surl = surl.replace('unmark', 'mark');
	}

	obj.innerHTML = title;
	obj.href = surl;

	return true;
}

function copyInputRow(tablename, nextrow){
	var tableobj = document.getElementById(tablename);
	var numrows=tableobj.rows.length;

	var nextrow=document.getElementById(nextrow);
	var newrow= nextrow.previousSibling.cloneNode(true); //first row

	var inputs = newrow.getElementsByTagName('input');

	for(i = 0; i < inputs.length; i++)
		inputs[i].value = '';

	tableobj.tBodies[0].insertBefore(newrow, nextrow);

	return newrow;
}

function setLength(field, maxlimit, output) {
	if(field.value.length > maxlimit)
		field.value = field.value.substring(0, maxlimit);
	putinnerHTML(output, "Length: " + field.value.length + " / " + maxlimit );
}

function moveRow(tablename, rowtomove, insertafter)
{
	var tableobj = document.getElementById(tablename);
	var rowobj = document.getElementById(rowtomove);
	var insertobj = document.getElementById(insertafter);

	if (tableobj && rowobj && insertobj && tableobj.tBodies[0].insertBefore)
	{
		if (tableobj.moveRow)
		{
			tableobj.moveRow(rowobj.rowIndex, insertobj.rowIndex + 1);
		} else {
			tableobj.tBodies[0].insertBefore(rowobj, insertobj.nextSibling);
		}
		// reget it
		rowobj = document.getElementById(rowtomove);

		rowobj.style.display = "";

    }

}


function loadEditor(tableName, insertAfter) {
	var table = document.getElementById(tableName);
	if (editorLoaded == true) {
		var clearRow = table.rows[lastEditorRow];
		clearRow.cells[0].innerHTML = "";
		table.deleteRow(lastEditorRow);
		editorLoaded = false;
		lastEditorRow = "";
		__FCKeditorNS = false;
		FCK_EDITMODE_SOURCE = false;
		FCK_EDITMODE_WYSIWYG = false;
		FCK_STATUS_ACTIVE = false;
		FCK_STATUS_COMPLETE = false;
		FCK_STATUS_NOTLOADED = false;
		FCK_TOOLBARITEM_ICONTEXT = false;
		FCK_TOOLBARITEM_ONLYICON = false;
		FCK_TOOLBARITEM_ONLYTEXT = false;
		FCK_TRISTATE_DISABLED = false;
		FCK_TRISTATE_OFF = false;
		FCK_TRISTATE_ON = false;
		FCK_UNKNOWN = false;
		FCKeditorAPI = false;
	}


	var insertAfterRowObj = document.getElementById(insertAfter);
	var insertIndex = insertAfterRowObj.rowIndex + 1;
	var newRow = table.insertRow(insertIndex);

	lastEditorRow = insertIndex;
	editorLoaded = true;

	newRow.id = 'commententry';
	var cell = newRow.insertCell(-1);
	cell.colSpan = 2;
	cell.innerHTML = returnEditorTrContent();
	var editorTd = document.getElementById('editor_td');

	editorTd.innerHTML = returnEditor();

}

function switchElement(div_id, text_area_name)
{
	var our_div = document.getElementById(div_id);
	var textarea = document.createElement('textarea');
	textarea.setAttribute("id", text_area_name);
	textarea.setAttribute("name", text_area_name);
	our_div.appendChild(textarea);
	var oFCKeditor = new FCKeditor(text_area_name);
	oFCKeditor.BasePath	= '/include/' ;
	oFCKeditor.ReplaceTextarea();
}


function setValue(fieldname, value)
{
	var fieldobj = document.getElementById(fieldname);
	if (fieldobj)
	{
		fieldobj.value = value;
	}
}

function sendForm(formname)
{
	var formobj = document.getElementById(formname);
	if (formobj)
		formobj.submit();
}

function showAll(parent, tagname, showPrefix, hidePrefix)
{
	var parentobj = document.getElementById(parent);
	var children = parentobj.getElementsByTagName(tagname);
	for (var i = 0; i < children.length; i++)
	{
		if (showPrefix != null && children[i].id.indexOf(showPrefix) == 0)
			children[i].style.display = '';
		else if (hidePrefix != null && children[i].id.indexOf(hidePrefix) == 0)
			children[i].style.display = 'none';
	}
}

function toggleDisplay(prefix1, prefix2, id)
{
	var object1 = document.getElementById(prefix1 + id);
	var object2 = document.getElementById(prefix2 + id);

	if (object1.style.display == 'none')
	{
		object1.style.display = object2.style.display;
		object2.style.display = 'none';
	} else {
		object2.style.display = object1.style.display;
		object1.style.display = 'none';
	}
}

function singleDisplay(parent, tagname, nameprefix, displayid)
{
	var parentobj = document.getElementById(parent);
	var children = parentobj.getElementsByTagName(tagname);

	for (var i = 0; i < children.length; i++)
	{
		if (children[i].id.indexOf(nameprefix) == 0)
		{
			if (children[i].id == nameprefix + displayid)
				children[i].style.display = '';
			else
				children[i].style.display = 'none';
		}
	}
}
