var FilmStripInstance = new FilmStrip();
var MIN_PIC_WIDTH = 288;

function FilmStrip(id)
{
	/*  Class Variables  */
	if(!id) {
		this.id = "film_strip";
	} else {
		this.id = id;
	}
	this.gradient = new Image();
	this.currentOffset = 0;
	this.images = new Array();
	this.scrollable = true;
	this.hasPicture = true;
	this.duration = 0.25; //time in ms for animation
	
	/*  Class Functions  */
	// Build the film strip 
	this.build = function()
	{
		this.images = YAHOO.util.Dom.getChildren(this.id + "_images");
		this.comments = YAHOO.util.Dom.getChildren(this.id + "_comments");
		
		for (var i = 0; i < this.comments.length; i++)
		{
			new Truncator(this.comments[i]);
		}
		
		if(this.images.length <= 1) {
			if(YAHOO.util.Dom.hasClass(this.images[0], "film_strip_no_picture")) {
				this.hasPicture = false;
			}
			this.scrollable = false;
		}
		
		this.gradient.src = YAHOO.util.Dom.get(this.id + "_gradient").src;
		this.images_layer = YAHOO.util.Dom.get(this.id + "_images_wrapper");
		this.maxOffset = this.images.length - 3; //3 because the first and last images are added in twice and offsets start at 0
		
		this.overlay_canvas = YAHOO.util.Dom.get(this.id + "_overlay");
		if (!this.overlay_canvas.getContext) {
			return;
		}
		this.overlay_layer = this.overlay_canvas.getContext('2d');
		
		this.width = this.overlay_canvas.width;
		this.height = this.overlay_canvas.height;
		this.size = [this.width, this.height];
		
		this.imageWidth = this.images[0].width;
		this.imageInset = (this.width - this.images[0].width) / 2;
		
		this.build_zoom_frame();
	};
	
	this.load_images = function() 
	{
		if (!this.imagesLoaded) {
			for(var i = 0; i < this.images.length; i++)
			{
				Nexopia.DelayedImage.loadImage(this.images[i]);
				Nexopia.Utilities.withImage(this.images[i], {
					load: function(image_index) {
						if(this.images[image_index].width != MIN_PIC_WIDTH)
						{
							this.images[image_index].src = YAHOO.util.Dom.get("image_not_found_image").src;
						}
					},
					scope: this,
					args: [i]
				});
			}
			this.imagesLoaded = true;
		}
	};
	
	// Build the frame for when we click on a picture.
	this.build_zoom_frame = function()
	{
		/* Build frame in the DOM then add it. */
		var zoom_frame_wrapper = document.createElement('div');
		YAHOO.util.Dom.addClass(zoom_frame_wrapper, "film_strip_zoom_frame_wrapper");
		YAHOO.util.Dom.setStyle(zoom_frame_wrapper, 'display', 'none');
		document.body.appendChild(zoom_frame_wrapper);
		
		var zoom_frame = document.createElement('div');
		YAHOO.util.Dom.addClass(zoom_frame, "zoom_frame");
		zoom_frame_wrapper.appendChild(zoom_frame);
		
		var zoom_frame_right = document.createElement('div');
		YAHOO.util.Dom.addClass(zoom_frame_right, "frame_background");
		zoom_frame.appendChild(zoom_frame_right);
		
		var image_box = document.createElement('div');
		YAHOO.util.Dom.addClass(image_box, "image_box");
		zoom_frame.appendChild(image_box);

		var zoom_frame_image = document.createElement('img');
		image_box.appendChild(zoom_frame_image);
		
		var zoom_frame_info = document.createElement('div');
		YAHOO.util.Dom.addClass(zoom_frame_info, "zoom_frame_info");
		zoom_frame.appendChild(zoom_frame_info);
		
		var zoom_frame_title = document.createElement('div');
		YAHOO.util.Dom.addClass(zoom_frame_title, "zoom_frame_title");
		zoom_frame_info.appendChild(zoom_frame_title);
		
		var zoom_frame_actions = document.createElement('div');
		YAHOO.util.Dom.addClass(zoom_frame_actions, 'zoom_frame_actions');
		zoom_frame_info.appendChild(zoom_frame_actions);
		
		var zoom_frame_link = document.createElement('a');
		YAHOO.util.Dom.addClass(zoom_frame_link, "zoom_frame_link");
		zoom_frame_actions.appendChild(zoom_frame_link);
		YAHOO.util.Event.on(zoom_frame_info, 'click', function(e)
		{
			YAHOO.util.Event.stopPropagation(e);
		});

		
		var zoom_frame_link_image = document.createElement('img');
		zoom_frame_link_image.src = YAHOO.util.Dom.get('film_strip_gallery_zoom').src;
		zoom_frame_link_image.border = 0;
		zoom_frame_link.appendChild(zoom_frame_link_image);
		
		var zoom_frame_close = document.createElement('img');
		YAHOO.util.Dom.addClass(zoom_frame_close, "zoom_frame_close");
		zoom_frame_close.src = YAHOO.util.Dom.get('film_strip_close_zoom').src;
		zoom_frame_actions.appendChild(zoom_frame_close);
		
		/* Remember wha-wha-what we got. */
		this.zoom_frame = {};
		this.zoom_frame.animation_duration = 0.3;
		this.zoom_frame.frame = zoom_frame;
		this.zoom_frame.frame_info = zoom_frame_info;
		this.zoom_frame.title = zoom_frame_title;
		this.zoom_frame.link = zoom_frame_link;
		this.zoom_frame.wrapper = zoom_frame_wrapper;
		this.zoom_frame.image = zoom_frame_image;
		this.zoom_frame.image_box = image_box;
		
		/* Align the loading spinner */
		this.zoom_frame.loading = YAHOO.util.Dom.get('film_strip_image_loading');
		YAHOO.util.Dom.setStyle(this.zoom_frame.loading, 'opacity', 0.6);
		
		/* make the background slightly transparent */
		YAHOO.util.Dom.setStyle(YAHOO.util.Dom.getElementsByClassName('frame_background', 'div', this.zoom_frame.frame), 'opacity', '0.85');
		
		this.zoom_frame.show = function(strip_image, comment)
		{
			// Actually remove the image from the "image_box", recreate it from scratch, and then append it back
			// to the parent "image_box" so that IE will properly re-render it.
			var parent = this.image.parentNode;
			parent.removeChild(this.image);
			this.image = document.createElement("img");
			this.image.src = strip_image.attributes.img_link.value;
			parent.appendChild(this.image);
			
			// Set the comment and link to the gallery for the picture 
			this.title.innerHTML = comment.innerHTML;
			this.link.href = strip_image.attributes.dest_link.value;
			
			// strip image is the image as loaded into the film strip as opposed to the actual image
			// which may be smaller or larger than the filmstrip.
			this.strip_image = strip_image;
			this.strip_image_region = YAHOO.util.Dom.getRegion(strip_image);
						
			var obj = this;			
			// Show the spinner
			YAHOO.util.Dom.setStyle(this.loading, 'display', 'block');
			Nexopia.Utilities.withImage(this.image, { load: function()
			{
				YAHOO.util.Dom.setStyle(obj.loading, 'display', 'none');
				
				obj.frame_target_top = obj.strip_image_region.top - parseInt(YAHOO.util.Dom.getStyle(obj.image_box, 'top'), 10);
				obj.frame_target_height = obj.strip_image.height + parseInt(YAHOO.util.Dom.getStyle(obj.image_box, 'top'), 10) + parseInt(YAHOO.util.Dom.getStyle(obj.image_box, 'bottom'), 10);
				obj.frame_target_left = obj.strip_image_region.left - parseInt(YAHOO.util.Dom.getStyle(obj.image_box, 'left'), 10);
				obj.frame_target_width = obj.strip_image.width + parseInt(YAHOO.util.Dom.getStyle(obj.image_box, 'left'), 10) + parseInt(YAHOO.util.Dom.getStyle(obj.image_box, 'right'), 10);

				// The wrapper is the background layer over top of the rest of the profile.
				YAHOO.util.Dom.setStyle(obj.wrapper, 'height', YAHOO.util.Dom.getDocumentHeight() + 'px');
				YAHOO.util.Dom.setStyle(obj.wrapper, 'display', 'block');

				// Set the starting attributes for the zoom frame. 
				YAHOO.util.Dom.setStyle(obj.frame, 'opacity', 0);
				YAHOO.util.Dom.setStyle(obj.frame, 'top', obj.frame_target_top + 'px');
				YAHOO.util.Dom.setStyle(obj.frame, 'height', obj.frame_target_height + 'px');
				YAHOO.util.Dom.setStyle(obj.frame, 'left', obj.frame_target_left + 'px');
				YAHOO.util.Dom.setStyle(obj.frame, 'width', obj.frame_target_width + 'px');

				obj.zoom_in();
				
				YAHOO.util.Event.removeListener(obj.image, 'load');
			}});
		};
		
		this.zoom_frame.zoom_in = function()
		{
			// Get the right size for the image in the frame
			var margin = 80;
			
			// max height/width is the size of browser window we have available to use.
			var max_width = YAHOO.util.Dom.getViewportWidth() - margin;
			var max_height = YAHOO.util.Dom.getViewportHeight() - margin;

			// Eventual size of the zoom frame is the lesser of the max size and the image size.
			var goal_width = Math.min(this.image.width, max_width);
			var goal_height = Math.min(this.image.height, max_height);

			// these ratios tell us if it's a portait or landscape picture.
			var goal_size_ratio = goal_width / goal_height;
			var image_size_ratio = this.image.width / this.image.height;
			
			// Scale the image to preserve aspect ratio.
			if (goal_size_ratio > image_size_ratio) {
				goal_width = goal_height * image_size_ratio;
			} else {
				goal_height = goal_width / image_size_ratio;
			}
			
			if (image_size_ratio > 1) {
				YAHOO.util.Dom.setStyle(this.image, 'height', goal_height + 'px'); // landscape
			} else {
				YAHOO.util.Dom.setStyle(this.image, 'width', goal_width + 'px'); // portrait
			}

			// If the image is smaller than the image in the filmstrip then we want to make the
			// frame bigger so we can get the whole comment in.
			// We do this at this point so that the zoom frame will zoom to the correct location.
			if (goal_width < MIN_PIC_WIDTH) { goal_width = MIN_PIC_WIDTH; }
			
			var goal_left = Math.round((YAHOO.util.Dom.getViewportWidth() - goal_width) / 2) + YAHOO.util.Dom.getDocumentScrollLeft();
			var goal_top = Math.round((YAHOO.util.Dom.getViewportHeight() - goal_height) / 4) + YAHOO.util.Dom.getDocumentScrollTop();
			
			// Calculate the total width of the image border that goes around the image so that this can
			// be factored into the calculation the size of the zoomed image. Note that IE 6 deals with
			// things a bit differently, so we actually don't want to do the calculation if the user is
			// viewing the page with that browser.
			var picture_border_adjustment = 0;
			var frame_info_height_adjustment = 0;
			if (!(YAHOO.env.ua.ie > 5 && YAHOO.env.ua.ie < 7))
			{
				picture_border_adjustment = this.frame.offsetWidth - this.image_box.offsetWidth;
				frame_info_height_adjustment = this.frame_info.offsetHeight;
			}

			// zoom the frame				
			// Set the destination size/location of the zoom frame.
			var attributes = {
				points: { to: [goal_left, goal_top] },
				height: { to: Math.floor(goal_height + picture_border_adjustment + frame_info_height_adjustment) },
				width: { to: Math.floor(goal_width + picture_border_adjustment) },
				opacity: { to: 1 }
			};
			var anim = new YAHOO.util.Motion(this.frame, attributes, this.animation_duration, YAHOO.util.Easing.easeBoth);
			
			var obj = this;
			anim.onComplete.subscribe(function()
			{
				YAHOO.util.Dom.setStyle(obj.image, 'opacity', '1');
				YAHOO.util.Dom.setStyle(obj.frame_info, 'opacity', '1');
			});
			anim.animate();
			
			new YAHOO.util.Scroll(this.image_box, { scroll: { from: [this.image.width/2 - this.strip_image.width/2, this.image.height/2 - this.strip_image.height/2], to: [0, 0] } }, this.animation_duration, YAHOO.util.Easing.easeBoth).animate();
		};
		
		this.zoom_frame.hide = function()
		{
			YAHOO.util.Dom.setStyle(this.image, 'height', 'auto');
			YAHOO.util.Dom.setStyle(this.image, 'width', 'auto');
		
			var obj = this;
			var attributes = {
				points: { to: [this.frame_target_left, this.frame_target_top] },
				height: { to: this.frame_target_height },
				width: { to: this.frame_target_width },
				opacity: { to: 0 }
			};
			var anim = new YAHOO.util.Motion(this.frame, attributes, this.animation_duration, YAHOO.util.Easing.easeBoth);
			anim.onComplete.subscribe(function()
			{
				YAHOO.util.Dom.setStyle(obj.wrapper, 'display', 'none');
			});
			anim.animate();

			new YAHOO.util.Scroll(this.image_box, { scroll: { to: [this.image.width/2 - this.strip_image.width/2, this.image.height/2 - this.strip_image.height/2] } }, this.animation_duration, YAHOO.util.Easing.easeBoth).animate();
		};
		
		// If they click somewhere outside the image close the zoom window.
		YAHOO.util.Event.on(this.zoom_frame.wrapper, 'click', function(e, zoom_frame) {
			zoom_frame.hide();
		}, this.zoom_frame);
		
		// If they click the 'close' icon close the zoom window
		YAHOO.util.Event.on( YAHOO.util.Dom.getElementsByClassName('zoom_frame_close', null, this.zoom_frame.frame_info), 'click', function(e, zoom_frame) {
			zoom_frame.hide();
		}, this.zoom_frame);
		
	};
	
	this.draw = function()
	{
		//alert('draw');
		this.redrawOverlay();
		//alert('overlay')
		//alert('drawImages')
		this.setPage();
	};
	
	this.scrollToImage = function()
	{
		if (!this.scrollInitialized) {
			document.getElementById("film_strip_images").style.left = "0px";
			this.images_layer.scrollLeft = this.imageWidth - this.imageInset;
			document.getElementById("film_strip_comments").style.left = "0px";
			document.getElementById("film_strip_comments_wrapper").scrollLeft = this.width;
			this.scrollInitialized = true;
		}
		
		var picturesAnim;
		var scrollTo;

		if(this.currentOffset < 0)
		{ //moving from the first image to the last image
			this.currentOffset = this.maxOffset;
			scrollTo = this.imageWidth * (-0.5) + (this.imageWidth - this.imageInset);
			picturesAnim = new YAHOO.util.Scroll(this.images_layer, { scroll: { to: [scrollTo,0] } }, this.duration/2, YAHOO.util.Easing.easeIn);
			picturesAnim.onComplete.subscribe(function() {
				scrollTo = this.imageWidth * (this.maxOffset+0.5) + (this.imageWidth - this.imageInset);
				this.images_layer.scrollLeft = scrollTo;
				scrollTo = this.imageWidth * this.maxOffset + (this.imageWidth - this.imageInset);
				picturesAnim = new YAHOO.util.Scroll(this.images_layer, { scroll: { to: [scrollTo,0] } }, this.duration/2, YAHOO.util.Easing.easeOut);
				picturesAnim.animate();
				
			}, this, true);
			picturesAnim.animate();
			var commentsAnim = new YAHOO.util.Scroll("film_strip_comments_wrapper", { scroll: { to: [0, 0] } }, this.duration, YAHOO.util.Easing.easeBoth);
			commentsAnim.onComplete.subscribe(function() {
				YAHOO.util.Dom.get("film_strip_comments_wrapper").scrollLeft = (this.maxOffset+1)*this.width;
			}, this, true);
			commentsAnim.animate();
		}
		else if(this.currentOffset > this.maxOffset)
		{ //moving from the last image to the first image
			this.currentOffset = 0;
			scrollTo = this.imageWidth * (this.maxOffset+0.5) + (this.imageWidth - this.imageInset);
			picturesAnim = new YAHOO.util.Scroll(this.images_layer, { scroll: { to: [scrollTo,0] } }, this.duration/2, YAHOO.util.Easing.easeIn);
			picturesAnim.onComplete.subscribe(function() {
				scrollTo = this.imageWidth * (-0.5) + (this.imageWidth - this.imageInset);
				this.images_layer.scrollLeft = scrollTo;
				scrollTo = this.imageWidth - this.imageInset;
				picturesAnim = new YAHOO.util.Scroll(this.images_layer, { scroll: { to: [scrollTo,0] } }, this.duration/2, YAHOO.util.Easing.easeOut);
				picturesAnim.animate();
				
			}, this, true);
			picturesAnim.animate();
			var commentsAnim = new YAHOO.util.Scroll("film_strip_comments_wrapper", { scroll: { to: [((this.maxOffset+2) * this.width), 0] } }, this.duration, YAHOO.util.Easing.easeBoth);
			commentsAnim.onComplete.subscribe(function() {
				YAHOO.util.Dom.get("film_strip_comments_wrapper").scrollLeft = this.width;
			}, this, true);
			commentsAnim.animate();
		} else {
			scrollTo = this.imageWidth * this.currentOffset + (this.imageWidth - this.imageInset);
			picturesAnim = new YAHOO.util.Scroll(this.images_layer, { scroll: { to: [scrollTo,0] } }, this.duration, YAHOO.util.Easing.easeBoth);
			picturesAnim.animate();
			var commentsAnim = new YAHOO.util.Scroll("film_strip_comments_wrapper", { scroll: { to: [((this.currentOffset+1) * this.width), 0] } }, this.duration, YAHOO.util.Easing.easeBoth);
			commentsAnim.animate();
		}
	};
	
	this.drawImages = function()
	{
		if(this.scrollable)
		{
			this.scrollToImage();
			this.setPage();
		}
	};
	
	this.setPage = function()
	{
		if(this.images.length > 1 ) {
			YAHOO.util.Dom.get("film_strip_page").innerHTML = (this.currentOffset + 1) + " of " + (this.maxOffset + 1);
		}
	};
	
	this.redrawOverlay = function(left, right)
	{
		if(!left) {
			left = 0.1;
		}
			
		if(!right && right != 0) {
			right = left;
		}
		
		this.overlay_layer.clearRect(0, 0, this.width, this.height);
		this.drawGradient();
		
		this.overlay_layer.fillStyle = "rgba(0,0,0,0.8)";
		this.overlay_layer.fillRect(0,this.height - 20, this.width, 20);
		
		if(this.scrollable) {
			this.drawSlideControls(left, right);
		}
	};
	
	this.drawGradient = function()
	{
		this.overlay_layer.drawImage(this.gradient, 0, 0, this.overlay_canvas.width, this.overlay_canvas.height);
	};
	
	// Draw the plus and minus on the right and left of the image slider.
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
	};
	
	this.getXY = function()
	{
		return YAHOO.util.Dom.getXY(this.id);
	};

	this.fixCoordinates = function(inValue)
	{
		var outValue = new Array();
		outValue[0] = inValue[0] - this.getXY()[0];
		outValue[1] = inValue[1] - this.getXY()[1];
		
		return outValue;
	};
	
	this.init = function(e)
	{
		if (!document.getElementById(this.id)) {
			return;
		}
		this.build();
		this.draw();
		
		YAHOO.util.Event.on(this.id + "_handle", 'click', function(e)
		{
			YAHOO.util.Event.preventDefault(e);
			this.load_images();
			var pos = this.fixCoordinates(YAHOO.util.Event.getXY(e));
			
			var images = null;
			var which = null;
			var comments = null;
			
			if(pos[0] >  this.width - 17 && pos[1] > this.height - 20)
			{
				images = YAHOO.util.Dom.getChildren(this.id + "_images");
				
				which = ((this.currentOffset + 1) % images.length);
				
				window.location = images[which].attributes.dest_link.value;
			}
			else if(pos[0] >  this.width - 34 && pos[1] > this.height - 20)
			{
				images = YAHOO.util.Dom.getChildren(this.id + "_images");
				comments = YAHOO.util.Dom.getChildren(this.id + "_comments");
				
				which = ((this.currentOffset + 1) % images.length);
				this.zoom_frame.show(images[which], comments[which]);
			}
			else if(pos[0] > (this.width - this.imageInset))
			{
				this.currentOffset++;
				if(this.scrollable) {
					this.drawImages();
				}
			}
			else if(pos[0] < this.imageInset)
			{
				this.currentOffset--;
				if(this.scrollable) {
					this.drawImages();
				}
			}
			else
			{
				images = YAHOO.util.Dom.getChildren(this.id + "_images");
				comments = YAHOO.util.Dom.getChildren(this.id + "_comments");
				
				which = ((this.currentOffset + 1) % images.length);
				this.zoom_frame.show(images[which], comments[which]);
			}
		}, this, true);
		
		YAHOO.util.Event.on(this.id + "_handle", 'mouseover', function(e)
		{
			var pos = this.fixCoordinates(YAHOO.util.Event.getXY(e));
		}, this, true);
		
		YAHOO.util.Event.on(this.id + "_handle", 'mouseout', function(e)
		{
			var pos = this.fixCoordinates(YAHOO.util.Event.getXY(e));
			
			this.redrawOverlay(0.1);
				YAHOO.util.Dom.get('film_strip_zoom').src = Site.coloredImgURL("aaa") + "/userpics/film_strip/images/zoom.gif";
				YAHOO.util.Dom.get('film_strip_go_to_gallery').src = Site.coloredImgURL("aaa") + "/userpics/film_strip/images/go_to_gallery.gif";
		}, this, true);
		
		YAHOO.util.Event.on(this.id + "_handle", 'mousemove', function(e)
		{
			var pos = this.fixCoordinates(YAHOO.util.Event.getXY(e));
			
			if(pos[0] >  this.width - 17 && pos[1] > this.height - 20)
			{
				this.redrawOverlay(0.1);
				YAHOO.util.Dom.get('film_strip_zoom').src = Site.coloredImgURL("aaa") + "/userpics/film_strip/images/zoom.gif";
				YAHOO.util.Dom.get('film_strip_go_to_gallery').src = Site.coloredImgURL("eee") + "/userpics/film_strip/images/go_to_gallery.gif";
			}
			else if(pos[0] >  this.width - 34 && pos[1] > this.height - 20)
			{
				this.redrawOverlay(0.1);
				YAHOO.util.Dom.get('film_strip_go_to_gallery').src = Site.coloredImgURL("aaa") + "/userpics/film_strip/images/go_to_gallery.gif";
				YAHOO.util.Dom.get('film_strip_zoom').src = Site.coloredImgURL("eee") + "/userpics/film_strip/images/zoom.gif";
			}
			else if(pos[0] > (this.width - this.imageInset)) // right side
			{
				this.redrawOverlay(0.1, 0.3);
				YAHOO.util.Dom.get('film_strip_zoom').src = Site.coloredImgURL("aaa") + "/userpics/film_strip/images/zoom.gif";
				YAHOO.util.Dom.get('film_strip_go_to_gallery').src = Site.coloredImgURL("aaa") + "/userpics/film_strip/images/go_to_gallery.gif";
			}
			else if(pos[0] < this.imageInset) // left side
			{
				this.redrawOverlay(0.3, 0.1);
				YAHOO.util.Dom.get('film_strip_zoom').src = Site.coloredImgURL("aaa") + "/userpics/film_strip/images/zoom.gif";
				YAHOO.util.Dom.get('film_strip_go_to_gallery').src = Site.coloredImgURL("aaa") + "/userpics/film_strip/images/go_to_gallery.gif";
			}
			else // middle
			{
				this.redrawOverlay(0.1);
				YAHOO.util.Dom.get('film_strip_zoom').src = Site.coloredImgURL("eee") + "/userpics/film_strip/images/zoom.gif";
				YAHOO.util.Dom.get('film_strip_go_to_gallery').src = Site.coloredImgURL("aaa") + "/userpics/film_strip/images/go_to_gallery.gif";
			}
		}, this, true);
	};
}
Overlord.assign({
	minion: "userpics:film_strip",
	load: FilmStripInstance.init,
	scope: FilmStripInstance
	
});