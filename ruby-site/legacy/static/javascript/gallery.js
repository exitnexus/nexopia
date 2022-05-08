var ids = new Array();
var pics = new Array();
var thumbs = new Array();
var fulls = new Array();
var descs = new Array();
var preLoad = new Array();
var numpics = 0;
var selectedpic = 0;
var numcols = 5;
var gallerytitle = "";
var userid = 0;

gallerylinkbase = '';

// general functions here.
function setlinkbase(linkbase)
{
	gallerylinkbase = linkbase;
}

function addPic(id, pic, thumb, full, desc){
	ids[numpics] = id;
	pics[numpics]= pic;
	thumbs[numpics]= thumb;
	fulls[numpics] = full;
	descs[numpics] = desc;
	numpics++;
}

function setGalleryTitle(str){
	gallerytitle = str;
}

function setUserid(uid){
	userid = uid;
}

// allthumbs stuff here
function showthumbs(){

	if(document.all){
		str = "<center><a class=body href=/gallery.php?uid=" + userid + "><b>" + gallerytitle + "</b><br>";

		var i = 0;
		while(1){
			if(i < numpics)
				str += '<a class=body href="' + gallerylinkbase + '/' + ids[i] + '"><img border=0 src="' + thumbs[i] + '" /></a> &nbsp;';

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

		str += "<tr><td class=header align=center colspan=" + numcols + "><a class=header href=/gallery.php?uid=" + userid + "><b>" + gallerytitle + "</b></td></tr>";

		var numrows = Math.ceil(numpics/numcols);

		var i = 0;
		while(1){
			if(i % numcols == 0)
				str += "<tr>";
			str += "<td class=body align=center>";

			if(i < numpics)
				str += '<a class=body href="' + gallerylinkbase + '/' + ids[i] + '"><img border=0 src="' + thumbs[i] + '" /></a>';;

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

// filmstrip mode stuff starts here.
curpicture = 0;

function picloaded(picid, childdoc, container)
{
	var picframe = document.getElementById('picframe');
	var sizeby = childdoc.getElementById(container);
	var indextext = document.getElementById('indextext');
	var nexthref = document.getElementById('nextpic');
	var prevhref = document.getElementById('prevpic');
	var reporthref = document.getElementById('reportpic');
	var picdesc = document.getElementById('picdesc');

	if (sizeby)
	{
		picframe.width = sizeby.width;
		picframe.height = sizeby.height;
	} else {
		picframe.width = 0;
		picframe.height = 0;
	}

	curpicture = picid;

	setupthumbs(picid, 'thumb', 5);

	picidx = findidx(ids, picid);
	indextext.innerHTML = (picidx+1) + "/" + pics.length;

	var prevnextidx = new Array(picidx - 1, picidx + 1);
	var prevnextids = wrapslice(ids, prevnextidx);
	var prevnexturls = wrapslice(pics, prevnextidx);
	var prevnextfull = wrapslice(fulls, prevnextidx);

	prevhref.href = "/imgframe.php?picid=" + prevnextids[0] + '&imgurl=' + prevnexturls[0] + '&fullurl=' + prevnextfull[0];
	nexthref.href = "/imgframe.php?picid=" + prevnextids[1] + '&imgurl=' + prevnexturls[1] + '&fullurl=' + prevnextfull[1];

	reporthref.href = "/reportabuse.php?type=22&uid=" + userid + "&id=" + picid;
	picdesc.innerHTML = descs[picidx];

	setuplinks(picid, picidx);
}

function setuplinks(picid, picidx)
{
	var piclink = document.getElementById('piclink');
	var urlfield = document.getElementById('imageurl');
	var links = piclink.getElementsByTagName('a');

	piclink.style.display = "";
	urlfield.style.display = "none";

	var href = 'http://www.nexopia.com' + gallerylinkbase + "/" + picid;
	var src = 'http://images.nexopia.com' + '/gallery/' + Math.floor(userid / 1000) + "/" + userid + '/' + picid + '.jpg';
	links[0].onclick = function() {
		urlfield.style.display = "";
		urlfield.value = href;
		return false;
	}
	links[1].onclick = function() {
		urlfield.style.display = "";
		urlfield.value = '[img]' + src + '[/img]';
		return false;
	}
	links[2].onclick = function() {
		urlfield.style.display = "";
		urlfield.value = '[url=' + href + ']' + descs[picidx] + '[/url]';
		return false;
	}

	return false;
}

function range(low, high, step)
{
	var ret = new Array();
	for (; low < high; low += step)
	{
		ret.push(low);
	}
	return ret;
}

function findidx(arr, val)
{
	for (var i = 0; i < arr.length; i++)
	{
		if (arr[i] == val)
			return i;
	}
	return -1;
}


function getindexes(thumbcount, focusonid)
{
	if (numpics == 0)
		return new Array();

	if (numpics <= thumbcount)
		return range(0, ids.length, 1);

	var focusonidx = 0;
	if (focusonid != 0)
	{
		focusonidx = findidx(ids, focusonid);
		if (focusonidx == -1)
			focusonidx = 0;
	}

	return range( focusonidx - Math.floor(thumbcount/2),
							  focusonidx + Math.ceil(thumbcount/2), 1);
}

// wraps the indexes both before 0 and after length()-1 to the
// end and beginning of the array respectively.
function wrapslice(arr, indexes)
{
	var out = new Array();
	for (var i = 0; i < indexes.length; i++)
	{
		var idx = indexes[i];
		if (idx < 0)
			out.push(arr[arr.length + idx]);
		else if (idx < arr.length)
			out.push(arr[idx]);
		else
			out.push(arr[idx - arr.length]);
	}
	return out;
}

function setupthumbs(focusonid, cellidprefix, thumbcount)
{
	var indexes = getindexes(thumbcount, focusonid);
	var imgids = wrapslice(ids, indexes);
	var subthumbs = wrapslice(thumbs, indexes);
	var subpics = wrapslice(pics, indexes);
	var subfull = wrapslice(fulls, indexes);

	var leftimgid = 0;
	var rightimgid = 0;

	for (var i = 0; i < thumbcount; i++)
	{
		var cell = document.getElementById(cellidprefix + i);
		if (subthumbs[i])
		{
			border = '0';
			if (imgids[i] == curpicture)
				border = '3';
			cell.innerHTML = '<a class=body target="picframe" href="/imgframe.php?picid=' + imgids[i] + '&imgurl=' + subpics[i] + '&fullurl=' + subfull[i] + '"><img border=' + border + ' src="' + subthumbs[i] + '" /></a>';
			if (i == 0) leftimgid = imgids[i];
			rightimgid = imgids[i]; // overwrites on every iteration
			cell.style.display = '';
		} else {
			cell.innerHTML = "";
			cell.style.display = 'none';
		}
	}

	var leftscroll = document.getElementById('leftscroll');
	var leftscroller = document.getElementById('leftscroller');
	var rightscroll = document.getElementById('rightscroll');
	var rightscroller = document.getElementById('rightscroller');
	if (ids.length > thumbcount)
	{
		leftscroller.onclick = function() { setupthumbs(leftimgid, cellidprefix, thumbcount); return false; };
		rightscroller.onclick = function() { setupthumbs(rightimgid, cellidprefix, thumbcount); return false; };
		leftscroll.style.display = '';
		rightscroll.style.display = '';
	} else {
		leftscroll.style.display = 'none';
		rightscroll.style.display = 'none';
	}
}

// hackish function for IE weirdness about setting ids on dynamic objects.
document.createPropElement = function(tagname, props)
{
	var obj;
	try {
		var propstr = "";
		for (name in props)
		{
			propstr += ' ' + name + '="' + props[name] + '"';
		}
		obj = document.createElement('<' + tagname + propstr + '>');
	} catch (e) {
		obj = document.createElement(tagname);
		for (name in props)
		{
			obj[name] = props[name];
		}
	}
	return obj;
}

// management interface code
var uploadCount = 0;
var uploadTotal = 0;
function submitfile(legacy, autosubmit)
{

	var uploadrow = document.getElementById('uploadrow');
	var formobj = document.getElementById('sendfile');
	var progressrow = document.getElementById('progressrow');
	var uploadframes = document.getElementById('uploadframes');

	if (legacy)
	{
		formobj.submit();
		return;
	}

	progressrow.style.display = '';
	var curUploadId = uploadTotal;
	uploadCount++;
	uploadTotal++;

	try {
		// add the in progress text to the current message area.
		var msgitem = document.getElementById('messages' + curUploadId);
		msgitem.innerHTML = "Upload In Progress...";

		var nextframe = document.createPropElement('iframe', {'name': 'uploadframe' + uploadTotal} );
		nextframe.width = nextframe.height = 0;
		nextframe.style.border = 0;
		uploadframes.appendChild(nextframe);

		// create a div for the next id.
		var messages = document.getElementById('messages');
		var msgitem = document.createPropElement('div', {'name': 'messages' + uploadTotal, 'id': 'messages' + uploadTotal } );
		messages.appendChild(msgitem);

		formobj.submit();
		var fileobj = document.createPropElement('input', {
			'name': 'userfile',
			'id': 'userfile',
			'class': 'body',
			'className': 'body',
			'type': 'file'
		});
		if (autosubmit)
		{
			fileobj.onchange = function() { submitfile(legacy, autosubmit); };
		}
		formobj.replaceChild(fileobj, formobj.userfile);

		formobj.target = nextframe.name;
		formobj.uploadid.value = uploadTotal;

	} catch (e) {
		uploadDone(curUploadId, 0, '', '', 'Error occured uploading image');
	}
}
function uploadDone(uploadid, uploadedpicid, uploadeddesc, uploadpicurl, messages)
{
	if (uploadCount)
	{
		if (uploadedpicid)
			addPending(uploadedpicid, uploadeddesc, uploadpicurl);

		if (!--uploadCount)
		{
			var progressrow = document.getElementById('progressrow');
			progressrow.style.display = 'none';
		}
	}
	if (messages != '')
	{
		var msgitem = 'messages' + uploadid;
		var msgobj = document.getElementById(msgitem);
		msgobj.innerHTML = messages;
	}
}
function maxUploads()
{
	var uploadok = document.getElementById('uploadok');
	var uploadmax = document.getElementById('uploadmax');
	uploadok.style.display = 'none';
	uploadmax.style.display = '';
}
function getCurrentUploadFrame()
{
	var uploadRows = document.getElementsByName('uploadrow');
	var currentUploadRow = uploadRows[uploadRows.length-1];
	return currentUploadRow.getElementsByTagName('iframe')[0];
}
function addPending(uploadedpicid, uploadeddesc, uploadpicurl)
{
	var newrow = copyInputRow('pendingimgtable','endpics');

	var updatenewrow = function() {
		newrow.style.display = "";

		newimgs = newrow.getElementsByTagName('img');
		newinputs = newrow.getElementsByTagName('input');

		var newimgs = newrow.getElementsByTagName('img');
		var newinputs = newrow.getElementsByTagName('input');
		var newlabels = newrow.getElementsByTagName('label');
		var newtextarea = newrow.getElementsByTagName('textarea');
		newimgs[0].src = uploadpicurl;
		newinputs[0].name = 'commit[' + uploadedpicid + ']';
		newinputs[0].checked = true;
		newinputs[0].value = 't';
		if (newinputs.length > 1)
		{
			newinputs[1].name = 'addtags[' + uploadedpicid + ']';
			newinputs[1].id = newinputs[1].name;
			newinputs[1].checked = true;
			newinputs[1].value = 't';
		}
		if (newlabels.length)
			newlabels[0].setAttribute('for', newinputs[1].name);
		var descinput = (newtextarea.length? newtextarea[0] : newinputs[1]);
		descinput.name = 'description[' + uploadedpicid + ']';
		descinput.value = uploadeddesc;

		document.getElementById('endpics').style.display = '';
		document.getElementById('submitline').style.display = '';
		var pendingheader = document.getElementById('pendingheader');
		if (pendingheader)
			pendingheader.style.display = '';
	};
	// IE5.5 crashes if the updates are done immediately, so they go in a timer.
	// All other browsers are fine with the updates being done immediately
	if (navigator.appVersion.indexOf("MSIE 5.5") != -1)
		setTimeout(updatenewrow, 100);
	else
		updatenewrow();
}

function resizeFrame(basedOn, iframe)
{
	if (iframe == null)
		iframe = getCurrentUploadFrame();

	iframe.width = basedOn.clientWidth;
	iframe.height = basedOn.clientHeight;
}

function enableButtonWhenSelected(buttonid, selectid)
{
	var button = document.getElementById(buttonid);
	var select = document.getElementById(selectid);

	if (button && select)
		button.disabled = select.value == 0;
}
