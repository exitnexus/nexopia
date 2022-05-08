function BrowserCheck() {
	var b = navigator.appName;
	
	if (b == "Netscape") 
		this.b = "NS";
	else if (b == "Microsoft Internet Explorer") 
		this.b = "IE";
	else this.b = b;
	
	this.v = parseInt(navigator.appVersion);
	this.NS = (this.b == "NS" && this.v>=4);
	this.NS4 = (this.b == "NS" && this.v == 4);
	this.NS5 = (this.b == "NS" && this.v == 5);
	this.IE = (this.b == "IE" && this.v>=4);
	this.IE4 = (navigator.userAgent.indexOf('MSIE 4')>0);
	this.IE5 = (navigator.userAgent.indexOf('MSIE 5')>0);
	
	if (this.IE5 || this.NS5) 
		this.VER5 = true;
	if (this.IE4 || this.NS4) 
		this.VER4 = true;
	
	this.OLD = (! this.VER5 && ! this.VER4) ? true : false;
	this.min = (this.NS||this.IE);
}

is = new BrowserCheck();

iter = 0;
setId = 0;
down = true;
up = false;
bouncingBall = (is.VER5) ? document.getElementById("ball").style
	: (is.NS) ? document.layers["ball"]
	: document.all["ball"].style;
text = (is.VER5) ? document.getElementById("report")
	: (is.NS) ? document.layers["ball"]
	: document.all["ball"];
winH = (is.NS) ? window.innerHeight - 55 : document.body.offsetHeight - 55;
document.getElementById("ball").onmousedown = buttonDown;

window.onscroll = scroll;
//window.setScroll(0,0);

if (is.NS4)
	document.getElementById("ball").captureEvents(Event.MOUSEUP);
if (is.NS4)
	document.getElementById("ball").captureEvents(Event.MOUSEDOWN);
if (is.NS4)
	document.captureEvents(Event.MOUSEMOVE);
	
var down = false;
var bx = 0;
var by = 0;
var sx = 50.0;
var sy = 150.0;

function buttonUp(e) {
	down = false;
	bouncingBall.position = "fixed";
	bouncingBall.left = "" + sx + "px";
	bouncingBall.top = "" + sy + "px";
	document.getElementById("report").innerHTML = "up";
	document.onmouseup = "";
	document.onmousemove = "";
	return true;
}
function buttonDown(e) {
	if (!down){
		bouncingBall.position = "absolute";
		bouncingBall.left = "" + (sx + window.scrollX) + "px";
		bouncingBall.top = "" + (sy + window.scrollY) + "px";
		bx = e.clientX;
		by = e.clientY;
		document.getElementById("report").innerHTML = "down";
		document.onmouseup = buttonUp;
		document.onmousemove = buttonMove;
	}
	down = true;
	return true;
}

function scroll(e){
}

function buttonMove(e) {
	if (down){
	
		sx = sx + (e.clientX - bx);
		sy = sy + (e.clientY - by);
		if (sx < 0) sx = 0;
		if (sy < 0) sy = 0;
		bouncingBall.left = "" + (sx + window.scrollX) + "px";
		bouncingBall.top = "" + (sy + window.scrollY) + "px";
		bx = e.clientX;
		by = e.clientY;
		document.getElementById("report").innerHTML = "Move";
	}	
	window.status = bouncingBall.top + " : " + bouncingBall.left;
	return true;
}

function getAbsLeft(el){
var l=el.offsetLeft;
  while((el=el.parentNode) && el!=document)
    l+=el.offsetLeft;
  return l;
}

function getAbsTop(el){
var l=el.offsetTop;
  while((el=el.parentNode) && el!=document)
    l+=el.offsetTop;
  return l;
}