var filmStrip = new FilmStrip();

function FilmStrip(id)
{
	/*  Class Variables  */
	if(!id)
		this.id = "film_strip";
	else
		this.id = id;
	this.gradient = new Image();
	this.currentOffset = 0;
	this.images = new Array();
	this.scrollable = true;
	this.hasPicture = true;
	
	/*  Class Functions  */
	this.build = function()
	{
		this.images = YAHOO.util.Dom.getChildren(this.id + "_images");
		var comments = YAHOO.util.Dom.getChildren(this.id + "_comments");
		
		for (var i = 0; i < comments.length; i++)
		{
			new Truncator(comments[i], { fudgeFactor: 50 });
		}
		
		if(this.images.length > 1)
		{
			var tmpImg;
			var tmpP;
			for(i = 0; i < 3; i++)
			{
				if(i >= this.images.length)
				{
					tmpImage = this.images[0].cloneNode(true);
					tmpComment = comments[0].cloneNode(true);
				}
				else
				{
					tmpImage = this.images[i].cloneNode(true);
					tmpComment = comments[i].cloneNode(true);
				}
				
				YAHOO.util.Dom.get(this.id + "_images").appendChild(tmpImage);
				YAHOO.util.Dom.get(this.id + "_comments").appendChild(tmpComment);
			}
		}
		else
		{
			if(YAHOO.util.Dom.hasClass(this.images[0], "film_strip_no_picture"))
				this.hasPicture = false;
			this.scrollable = false;
		}
		
		this.gradient.src = YAHOO.util.Dom.get(this.id + "_gradient").src;
		this.images_layer = YAHOO.util.Dom.get(this.id + "_images_wrapper");
		
		this.overlay_canvas = YAHOO.util.Dom.get(this.id + "_overlay");
		if (!this.overlay_canvas.getContext) return;
		this.overlay_layer = this.overlay_canvas.getContext('2d');
		
		this.width = this.overlay_canvas.width;
		this.height = this.overlay_canvas.height;
		this.size = [this.width, this.height];
		
		this.imageWidth = this.images[0].width;
		this.imageInset = (this.width - this.images[0].width) / 2;
		
		if(!this.scrollable)
			YAHOO.util.Dom.setStyle(this.images[0], "padding-left", this.imageInset + 'px')
	}
	
	this.draw = function()
	{
		this.redrawOverlay();
		this.drawImages();
	}
	
	this.scrollToImage = function(which, time)
	{
		if(!time && time != 0)
			time = 0.25;
		
		if(time == 0)
		{
			/*  Pictures  */
			this.images_layer.scrollLeft = which * this.imageWidth + (this.imageWidth - this.imageInset);
			
			/*  Comments  */
			YAHOO.util.Dom.get("film_strip_comments_wrapper").scrollLeft = (which+1) * this.width;		
		}
		else
		{
			/*  Pictures  */
			var picturesAnim = new YAHOO.util.Scroll(this.images_layer, { scroll: { to: [(which * this.imageWidth + (this.imageWidth - this.imageInset)), 0] } }, time, YAHOO.util.Easing.easeBoth);
			picturesAnim.animate();
			
			/*  Comments  */
			var commentsAnim = new YAHOO.util.Scroll("film_strip_comments_wrapper", { scroll: { to: [((which+1) * this.width), 0] } }, time, YAHOO.util.Easing.easeBoth);
			commentsAnim.animate();
		}
	}
	
	this.drawImages = function()
	{
		if(this.scrollable)
		{
			var maxOffset = this.images.length;
		
			if(this.currentOffset < 0)
			{
				this.currentOffset = maxOffset - 1;
				this.scrollToImage(this.currentOffset + 1, 0);
			}
			else if(this.currentOffset > maxOffset)
			{
				this.currentOffset = 0 + 1;
				this.scrollToImage(this.currentOffset - 1, 0);
			}
			
			this.scrollToImage(this.currentOffset);
			
			if(this.currentOffset+1 > maxOffset)
				YAHOO.util.Dom.get("film_strip_page").innerHTML = 1 + " of " + maxOffset;
			else
				YAHOO.util.Dom.get("film_strip_page").innerHTML = this.currentOffset+1 + " of " + maxOffset;
		}
	}
	
	this.redrawOverlay = function(left, right)
	{
		if(!left)
			left = 0.1;
			
		if(!right && right != 0)
			right = left;
		
		this.overlay_layer.clearRect(0, 0, this.width, this.height);
		this.drawGradient();
		
		this.overlay_layer.fillStyle = "rgba(0,0,0,0.8)";
		this.overlay_layer.fillRect(0,this.height - 20, this.width, 20);
		
		if(this.scrollable)
			this.drawSlideControls(left, right);
	}
	
	this.drawGradient = function()
	{
		this.overlay_layer.drawImage(this.gradient, 0, 0, this.overlay_canvas.width, this.overlay_canvas.height);
	}
	
	this.drawSlideControls = function(left, right)
	{
		var inset = 6;
		var size = 60;
		
		this.overlay_layer.lineWidth = 4;
		
		this.overlay_layer.strokeStyle = "rgba(255,255,255,"+left+")";
		this.overlay_layer.beginPath();
		this.overlay_layer.moveTo(inset, this.overlay_canvas.height / 2);
		this.overlay_layer.lineTo((inset + size), this.overlay_canvas.height / 2);
		this.overlay_layer.closePath();
		this.overlay_layer.stroke();
		
		this.overlay_layer.save();
			this.overlay_layer.scale(-1, 1);
			this.overlay_layer.translate(-this.overlay_canvas.width, 0);
			
			this.overlay_layer.strokeStyle = "rgba(255,255,255,"+right+")";
			this.overlay_layer.beginPath();
			this.overlay_layer.moveTo(inset, this.overlay_canvas.height / 2);
			this.overlay_layer.lineTo((inset + size), this.overlay_canvas.height / 2);
			this.overlay_layer.closePath();
			this.overlay_layer.stroke();
			
			this.overlay_layer.beginPath();
			this.overlay_layer.moveTo(inset + size/2, this.overlay_canvas.height / 2 - size / 2);
			this.overlay_layer.lineTo(inset + size/2, this.overlay_canvas.height / 2 - this.overlay_layer.lineWidth/2);
			this.overlay_layer.closePath();
			this.overlay_layer.stroke();
			
			this.overlay_layer.beginPath();
			this.overlay_layer.moveTo(inset + size/2, this.overlay_canvas.height / 2 + size / 2);
			this.overlay_layer.lineTo(inset + size/2, this.overlay_canvas.height / 2 + this.overlay_layer.lineWidth/2);
			this.overlay_layer.closePath();
			this.overlay_layer.stroke();
		this.overlay_layer.restore();
	}
	
	this.getXY = function()
	{
		return YAHOO.util.Dom.getXY(this.id);
	}
	
	YAHOO.util.Event.on(window, 'load', function(e)
	{
		this.build();
		this.draw();
	}, this, true);

	this.fixCoordinates = function(inValue)
	{
		var outValue = new Array();
		outValue[0] = inValue[0] - this.getXY()[0];
		outValue[1] = inValue[1] - this.getXY()[1];
		
		return outValue
	}
	
	YAHOO.util.Event.on(this.id + "_handle", 'click', function(e)
	{
		var pos = this.fixCoordinates(YAHOO.util.Event.getXY(e));
		
		if(pos[0] > (this.width - this.imageInset))
		{
			this.currentOffset++;
			if(this.scrollable)
				this.drawImages();
		}
		else if(pos[0] < this.imageInset)
		{
			this.currentOffset--;
			if(this.scrollable)
				this.drawImages();
		}
		
/* 		YAHOO.util.Dom.get("click_info").innerHTML = "Click: " + pos; */
	}, this, true);
	
	YAHOO.util.Event.on(this.id + "_handle", 'mouseover', function(e)
	{
		var pos = this.fixCoordinates(YAHOO.util.Event.getXY(e));
		
/* 		YAHOO.util.Dom.get("in_info").innerHTML = "In: " + pos; */
	}, this, true);
	
	YAHOO.util.Event.on(this.id + "_handle", 'mouseout', function(e)
	{
		var pos = this.fixCoordinates(YAHOO.util.Event.getXY(e));
		
		this.redrawOverlay(0.1);
/* 		YAHOO.util.Dom.setStyle(this.id + "_handle", "cursor", "default"); */
		
/* 		YAHOO.util.Dom.get("out_info").innerHTML = "Out: " + pos; */
	}, this, true);
	
	YAHOO.util.Event.on(this.id + "_handle", 'mousemove', function(e)
	{
		var pos = this.fixCoordinates(YAHOO.util.Event.getXY(e));
		
		if(pos[0] > (this.width - this.imageInset))
		{
			this.redrawOverlay(0.1, 0.3);
/* 			YAHOO.util.Dom.setStyle(this.id + "_handle", "cursor", "default"); */
		}
		else if(pos[0] < this.imageInset)
		{
			this.redrawOverlay(0.3, 0.1);
/* 			YAHOO.util.Dom.setStyle(this.id + "_handle", "cursor", "default"); */
		}
		else
		{
			this.redrawOverlay(0.1);
/*
			if(this.hasPicture)
				YAHOO.util.Dom.setStyle(this.id + "_handle", "cursor", "pointer");
*/
		}
		
/* 		YAHOO.util.Dom.get("move_info").innerHTML = "Position: " + pos; */
	}, this, true);
}
