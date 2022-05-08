var zoomImg = new Image();
var zoomImgSize = 0;
var show = false;
var animating = false;
function drawPreviewZoom()
{
	var canvas = YAHOO.util.Dom.get("preview_zoom_canvas");
	if (!canvas || !canvas.getContext) return;
	var ctx = canvas.getContext('2d');
	
	zoomImg.src = imagesPath + '/zoom.png';
}

function animateZoomIcon()
{
	var canvas = YAHOO.util.Dom.get("preview_zoom_canvas");
	if (!canvas.getContext) return;
	var ctx = canvas.getContext('2d');
	
	animating = true;
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  try{
	ctx.drawImage(zoomImg, (canvas.width - zoomImgSize), (canvas.height - zoomImgSize), zoomImgSize, zoomImgSize);
	} catch (e) {}
	
	if(show)
		zoomImgSize = zoomImgSize + 2;
	else
		zoomImgSize = zoomImgSize - 2;
	
	if(zoomImgSize < 40 && zoomImgSize >= 0)
		setTimeout("animateZoomIcon()", 5);
	else
		animating = false;
}

function hideZoomIcon()
{
	show = false;
	if(!animating)
		animateZoomIcon();
}

function showZoomIcon()
{
	show = true;
	if(!animating)
		animateZoomIcon();
}

function drawRoundedRectangle(ctx,x,y,width,height,radius)
{
	ctx.beginPath();
	ctx.moveTo(x,y+radius);
	ctx.lineTo(x,y+height-radius);
	ctx.quadraticCurveTo(x,y+height,x+radius,y+height);
	ctx.lineTo(x+width-radius,y+height);
	ctx.quadraticCurveTo(x+width,y+height,x+width,y+height-radius);
	ctx.lineTo(x+width,y+radius);
	ctx.quadraticCurveTo(x+width,y,x+width-radius,y);
	ctx.lineTo(x+radius,y);
	ctx.quadraticCurveTo(x,y,x,y+radius);
	ctx.closePath();
	ctx.fill();
	ctx.stroke();
}
