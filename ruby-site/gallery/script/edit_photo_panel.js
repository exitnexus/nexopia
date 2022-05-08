var Dom = YAHOO.util.Dom;
var Event = YAHOO.util.Event;
var DDM = YAHOO.util.DragDropMgr;


YAHOO.namespace ("profile");

YAHOO.profile.EditPhotoDialog = function(el, pictureid, imgSrc, user) {
	
    YAHOO.profile.EditPhotoDialog.superclass.constructor.call(this, el, 
	{
		width:"540px",
		fixedcenter:true,
		close:false,
		draggable:false,
		zindex:4,
		modal:true,
		visible:false,
		pictureid: pictureid,
		imgSrc: imgSrc,
		user: user
	});
};




YAHOO.extend(YAHOO.profile.EditPhotoDialog, YAHOO.widget.Panel, {
	moveTo: function(e, event, gallery){
		alert("moving");
		this.pic.moveTo(gallery); 
		this.hide();
		this.destroy();
	},
	buildEditPanel: function(){
	
		this.dialogButtonBar = document.createElement("div");
		this.dialogButtonBar.className = "button_bar";
				
		this.mainContainer.appendChild(this.dialogButtonBar);
		
		this.infoBlock = document.createElement("div");
		this.infoBlock.className = "info_block"
		
		var editLabel = document.createElement("h2");
		editLabel.innerHTML = "Edit";
		this.infoBlock.appendChild(editLabel);
		
		var descLabel = document.createElement("div");
		
		var description = document.createElement("span");
		description.innerHTML = this.pic.description + "&nbsp;";
		descLabel.appendChild(description);
		
		var instructions = document.createElement("span");
		instructions.className = "instructions";
		instructions.innerHTML = "(Click to edit)";
		descLabel.appendChild(instructions);

		this.infoBlock.appendChild(descLabel);
		
		var descEdit = document.createElement("textarea");
		descEdit.innerHTML = this.pic.description;
		descEdit.style.display = "none";
		this.infoBlock.appendChild(descEdit);

		function descLabelOnClick(e){
			descLabel.style.display = "none";
			descEdit.style.display = "block";
			descEdit.select();
			descEdit.focus();
		}
		YAHOO.util.Event.addListener(descLabel, "click", descLabelOnClick); 
		
		var thatpic = this.pic;
		function descEditOnBlur(e){
			descLabel.style.display = "block";
			descEdit.style.display = "none";
			
			descEdit.value = descEdit.value.replace(/^\s+|\s+$/g, '');
			description.innerHTML = descEdit.value;
			thatpic.changeDescription(descEdit.value);
		}
		YAHOO.util.Event.addListener(descEdit, "blur", descEditOnBlur); 

		
		this.mainContainer.appendChild(this.infoBlock);

		this.commandButtonBar = document.createElement("div");
		this.commandButtonBar.className = "command_bar yui-skin-sam";
		this.mainContainer.appendChild(this.commandButtonBar);

		this.img = document.createElement("img");
		this.img.src = this.imgSrc;
		this.mainContainer.appendChild(this.img);
		
		this.oMenu = new YAHOO.widget.Menu("basicmenu", 
		{ position: "dynamic" }
		);
		
		items = [];
		for(index in this.user.galleries){
			items.push({ 
				text: this.user.galleries[index].name, 
				onclick: { 
					fn: this.moveTo,
					obj: this.user.galleries[index], 
					scope: this
				} 
			});
    	}
		this.oMenu.addItems(items);
    	
		this.menuButton = new YAHOO.widget.Button({
			id: "menu_button_" + this.id,
			type: "menu",
			label: "Move to:",
			menu: this.oMenu, 
			container: this.commandButtonBar
		});
        this.oMenu.render(this.menuButton);
    
		this.makeButton("Crop Image", "crop", this.commandButtonBar, function(e, editableBlock){
			editableBlock.clear();
			editableBlock.buildCropPanel();
		});
		this.makeButton("Set as Album Cover", "album_cover", this.commandButtonBar, function(e, editableBlock){
			editableBlock.pic.setAsAlbumCover();
		});
		this.makeButton("Delete", "delete", this.commandButtonBar, function(e, editableBlock){
			editableBlock.pic.deletePic();
		});

		this.makeButton("Done", "done", this.dialogButtonBar, function(e, editableBlock){
			editableBlock.hide();
			editableBlock.destroy();
		});

	},
	buildCropPanel: function(){

		this.commandButtonBar = document.createElement("div");
		this.commandButtonBar.className = "crop_command_bar yui-skin-sam";
		this.mainContainer.appendChild(this.commandButtonBar);

		this.scroller = document.createElement("div");
		this.scroller.className = "scroller";
		
		this.mainContainer.appendChild(this.scroller);

		
		this.img = document.createElement("img");
		this.img.src = this.imgSrc;
		this.img.style.width = "100%";
		this.scroller.appendChild(this.img);
		this.cropper = new YAHOO.widget.ImageCropper(this.img, {xyratio:1.0/1.2,minW:1,zIndex:24});

		this.makeButton("cancel", "cancel", this.commandButtonBar, function(e, editableBlock){
			editableBlock.clear();
			editableBlock.buildEditPanel();
		});
		
		this.makeButton("save", "save", this.commandButtonBar, function(e, editableBlock){
			var region = editableBlock.cropper.getCropRegionNormalized();
			editableBlock.pic.crop(region.x, region.y, region.w, region.h)
			editableBlock.clear();
			editableBlock.buildEditPanel();
		});

	},
	clear: function(){
		while (this.mainContainer.firstChild) {
			this.mainContainer.removeChild(this.mainContainer.firstChild);
		}
	},
	init: function(el, config)
	{
		YAHOO.profile.EditPhotoDialog.superclass.init.call(this, el, config);

		this.pictureid = config['pictureid'];
		this.imgSrc = config['imgSrc'];
		this.user = config['user'];

		this.galleryid = parseInt(document.getElementById("galleryid").value);
		
		for(index in this.user.galleries){
			var gallery = this.user.galleries[index];
			if (gallery.id == this.galleryid){
				this.gallery = gallery;
			}
		}
		
		this.pic = galleryPics[this.pictureid];

		this.mainContainer = document.createElement("div");
		this.mainContainer.className = "mainContainer";
		this.innerElement.appendChild(this.mainContainer);
		
		
		this.buildEditPanel();
	},
	makeButton: function(name, id, container, fn){
		button = new YAHOO.widget.Button({
			id: id + "_button_" + this.id,
			type: "button",
			label: name,
			container: container
		});
	
		button.addListener("click", fn, this);
		return button
	}
	
});

