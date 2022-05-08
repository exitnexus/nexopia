Nexopia.enhanced_text_editor_list = [];


Overlord.assign({
	minion: "enhanced_text_input",
		load: function(element)
		{
			var tmp = new EnhancedTextInput(element);
			if(element.id != null && element.id != "")
			{
				Nexopia.enhanced_text_editor_list[element.id] = tmp;
			}
			// initialize_auto_shading(element.parentNode); //removed due to performance problems particularly in IE
		}
});

function EnhancedTextInput(element)
{
	this.initalize(element);
};

EnhancedTextInput.prototype =
{
	initalize: function(element)
	{
		this.text_box = element;
		this.text_box_width = YAHOO.util.Dom.getRegion(this.text_box).right - YAHOO.util.Dom.getRegion(this.text_box).left;
		if (this.text_box_width == 0){
			this.text_box_width = parseInt(element.style.width, 10);
		}
		
		this.text_box_height = YAHOO.util.Dom.getRegion(this.text_box).bottom - YAHOO.util.Dom.getRegion(this.text_box).top;
		if (this.text_box_height == 0){
			this.text_box_height = parseInt(element.style.height, 10);
		}
		
		this.wrap_text_box();
		this.populate(this.population());
		this.activate_tab(0);		
	},
	
	population: function()
	{
		var that = this;
		
		return [
			{ name: "Format", content: [
				{type: "button", label: "B", pre: "[b]", post: "[/b]" },
				{type: "button", label: "I", pre: "[i]", post: "[/i]" },
				{type: "button", label: "U", pre: "[u]", post: "[/u]" },
				{type: "button", label: "Strike", pre: "[strike]", post: "[/strike]" },
				{type: "button", label: "Super", pre: "[sup]", post: "[/sup]" },
				{type: "button", label: "Sub", pre: "[sub]", post: "[/sub]" },
				{type: "menu", label: "Size", options: [
					{label: "Tiny", pre: "[size=1]", post: "[/size]" },
					{label: "Small", pre: "[size=2]", post: "[/size]" },
					{label: "Normal", pre: "[size=3]", post: "[/size]" },
					{label: "Large", pre: "[size=4]", post: "[/size]" },
					{label: "Huge", pre: "[size=5]", post: "[/size]" },
					{label: "Enormous", pre: "[size=6]", post: "[/size]" },
					{label: "Gargantuan", pre: "[size=7]", post: "[/size]" }
				]},
				{type: "menu", label: "Color", options: [
					{label: "Dark Red", color: "darkred", pre: "[color=darkred]", post: "[/color]" },
					{label: "Red", color: "red", pre: "[color=red]", post: "[/color]" },
					{label: "Orange", color: "orange", pre: "[color=orange]", post: "[/color]" },
					{label: "Brown", color: "brown", pre: "[color=brown]", post: "[/color]" },
					{label: "Yellow", color: "yellow", pre: "[color=yellow]", post: "[/color]" },
					{label: "Green", color: "green", pre: "[color=green]", post: "[/color]" },
					{label: "Olive", color: "olive", pre: "[color=olive]", post: "[/color]" },
					{label: "Cyan", color: "cyan", pre: "[color=cyan]", post: "[/color]" },
					{label: "Blue", color: "blue", pre: "[color=blue]", post: "[/color]" },
					{label: "Dark Blue", color: "darkblue", pre: "[color=darkblue]", post: "[/color]" },
					{label: "Indigo", color: "indigo", pre: "[color=indigo]", post: "[/color]" },
					{label: "Violet", color: "violet", pre: "[color=violet]", post: "[/color]" },
					{label: "White", color: "white", pre: "[color=white]", post: "[/color]" },
					{label: "Black", color: "black", pre: "[color=black]", post: "[/color]" }
				]},
				{type: "menu", label: "Font", options: [
					{label: "Arial", pre: "[font=arial]", post: "[/font]" },
					{label: "Times", pre: "[font=times]", post: "[/font]" },
					{label: "Courier", pre: "[font=courier]", post: "[/font]" },
					{label: "Impact", pre: "[font=impact]", post: "[/font]" },
					{label: "Geneva", pre: "[font=geneva]", post: "[/font]" },
					{label: "Optima", pre: "[font=optima]", post: "[/font]" },
					{label: "Verdana", pre: "[font=verdana]", post: "[/font]" }
				]}
			]},
			{ name: "Align", content: [
				{type: "button", label: "Left", pre: "[left]", post: "[/left]" },
				{type: "button", label: "Center", pre: "[center]", post: "[/center]" },
				{type: "button", label: "Right", pre: "[right]", post: "[/right]" },
				{type: "button", label: "Justify", pre: "[justify]", post: "[/justify]" }
			]},
			{ name: "Insert", content: [
				{type: "button", label: "Quote", pre: "[quote]", post: "[/quote]" },
				{type: "button", label: "Image", pre: "[img]", post: "[/img]" },
				{type: "button", label: "Link", pre: "[url]", post: "[/url]" },
				{type: "button", label: "User", pre: "[user]", post: "[/user]" },
				{type: "button", label: "Line", pre: "\n[hr]\n", post: "" },
				{type: "menu", label: "List", options: [
					{label: "Bullet", pre: "[list]\n[*]", post: "\n[/list]\n" },
					{label: "Numbered", pre: "[list=1]\n[*]", post: "\n[/list]\n" },
					{label: "Alphabetic", pre: "[list=a]\n[*]", post: "\n[/list]\n" },
					{label: "Roman", pre: "[list=i]\n[*]", post: "\n[/list]\n" }
				]}
			]},
 			{ name: "Smilies", content: [{type: "smilies", smilies: Site.smilies}], action: function() {
				Nexopia.DelayedImage.loadImages(that.enhanced_text_box_wrapper);
			}},
 			{ name: "Preview", content: [{type: "preview"}], action: function(){that.get_preview();} }
		 ];
	},
	
	populate: function(population)
	{
		for(var i = 0; i < population.length; i++)
		{
			// Create Handle
			var handle = document.createElement('div');
			YAHOO.util.Dom.addClass(handle, "tab_handle");
			handle.innerHTML = population[i].name;
			this.tab_handles.appendChild(handle);
			YAHOO.util.Event.on(handle, 'mousedown', function(e, args){
				args[0].save_selection();
			}, [this, i]);
			
			YAHOO.util.Event.on(handle, 'click', function(e, args){
				args[0].activate_tab(args[1]);
				if(population[args[1]].action) {
					population[args[1]].action();
				}
			}, [this, i]);
			
			YAHOO.util.Event.on(handle, 'mouseup', function(e, args){
				args[0].load_selection();
			}, [this, i]);
			
			// Create Body
			var body_div = document.createElement('div');
			YAHOO.util.Dom.addClass(body_div, "tab_body");
			if (population[i].name != "Preview")
			{
				YAHOO.util.Dom.addClass(body_div, "sub_menu");
			}	
			for(var j = 0; j < population[i].content.length; j++)
			{
				var config = population[i].content[j];
				var el = this["build_"+config.type](config, body_div);
				if (el) {
					body_div.appendChild(el);
				}
			}
			YAHOO.util.Dom.setStyle(body_div, 'display', 'none');
			this.tab_bodies.appendChild(body_div);
		}
	},
	
	activate_tab: function(which)
	{
		var handles = YAHOO.util.Dom.getChildren(this.tab_handles);
		for(var i = 0; i < handles.length; i++)
		{
			YAHOO.util.Dom.setStyle(handles[i], 'font-weight', 'normal');
		}
		var bodies = YAHOO.util.Dom.getChildren(this.tab_bodies);
		for(var i = 0; i < bodies.length; i++)
		{
			YAHOO.util.Dom.setStyle(bodies[i], 'display', 'none');
		}
		
		YAHOO.util.Dom.setStyle(YAHOO.util.Dom.getChildren(this.tab_handles)[which], 'font-weight', 'bold');
		YAHOO.util.Dom.setStyle(YAHOO.util.Dom.getChildren(this.tab_bodies)[which], 'display', 'block');
		// if (this.stored_selection) {
		// 	this.setCaretPosition(this.stored_selection)
		// }
	},
	
	wrap_text_box: function()
	{
		this.enhanced_text_box_wrapper = document.createElement('div');
		YAHOO.util.Dom.setStyle(this.enhanced_text_box_wrapper, 'width', this.text_box_width + 'px');
		YAHOO.util.Dom.addClass(this.enhanced_text_box_wrapper, "enhanced_text_box_wrapper");
		
		this.tab_bar = document.createElement('div');
		YAHOO.util.Dom.addClass(this.tab_bar, "tab_bar");
		this.enhanced_text_box_wrapper.appendChild(this.tab_bar);
		
		this.tab_handles = document.createElement('div');
		YAHOO.util.Dom.addClass(this.tab_handles, "tab_handles");
		this.tab_bar.appendChild(this.tab_handles);
		
		this.tab_bodies = document.createElement('div');
		YAHOO.util.Dom.addClass(this.tab_bodies, "tab_bodies");
		this.tab_bar.appendChild(this.tab_bodies);
		
		this.text_box.parentNode.replaceChild(this.enhanced_text_box_wrapper, this.text_box);
		this.enhanced_text_box_wrapper.appendChild(this.text_box);
	},
	
	build_button: function(config, parent)
	{
		var button = document.createElement('input');
		button.type = "button";
		button.value = config.label;
		parent.appendChild(button);
		
		new YAHOO.widget.Button(button, {
			onclick: {
				fn: function(e, args) {
					this.insert(config.pre, config.post);
				},
				scope: this
			}
		});
		
		return false;
	},

	build_preview: function(config, parent)
	{
		this.preview_tab_bar = parent;
	},

	get_preview: function()
	{
		var saved_color = YAHOO.util.Dom.getStyle(this.preview_tab_bar, 'background-color');
		
		YAHOO.util.Dom.setStyle(this.preview_tab_bar, 'position', 'absolute');
		YAHOO.util.Dom.setStyle(this.preview_tab_bar, 'height', this.text_box_height-1-4+'px');
		YAHOO.util.Dom.setStyle(this.preview_tab_bar, 'width', this.text_box_width-2-12+'px');
		YAHOO.util.Dom.setStyle(this.preview_tab_bar, 'overflow', 'auto');
		YAHOO.util.Dom.setStyle(this.preview_tab_bar, 'padding', '6px');
		YAHOO.util.Dom.setStyle(this.preview_tab_bar, 'padding-top', '2px');
		YAHOO.util.Dom.setStyle(this.preview_tab_bar, 'padding-bottom', '2px');
		
		YAHOO.util.Dom.setStyle(this.preview_tab_bar, 'background-color', saved_color);
		
		this.text_box.blur();
		
		this.preview_tab_bar.innerHTML = "Loading Preview...";
		
		YAHOO.util.Connect.asyncRequest('POST', Site.wwwURL + '/enhanced_text_input/preview:Body', new ResponseHandler(
		{
			success: function(o)
			{
				this.preview_tab_bar.innerHTML = o.responseText;
				Nexopia.DelayedImage.loadImages(this.preview_tab_bar);
			},
			failure: function(o)
			{
				this.preview_tab_bar.innerHTML = 'Error Generating Preview.';
			},
			scope: this
		}), 'source_text='+encodeURIComponent(this.text_box.value));
	},

	build_smilies: function(config)
	{
		
		this.smilies = document.createElement("div");

		this.left_arrow = document.createElement("img");
		this.left_arrow.className = 'color_icon';
		this.left_arrow.src = Site.coloredImgURL(this.deduceArrowColor()) + "/core/images/arrow_left.gif";

		this.right_arrow = document.createElement("img");
		this.right_arrow.className = 'color_icon';
		this.right_arrow.src = Site.coloredImgURL(this.deduceArrowColor()) + "/core/images/arrow_right.gif";

		var internal_wrapper = document.createElement("div");
		var wrapper = document.createElement("div");

		YAHOO.util.Dom.setStyle(wrapper, "width", (parseInt(this.text_box_width, 10) - 22) + "px");
		
		var break_clear = document.createElement("br");
		YAHOO.util.Dom.addClass(break_clear, 'clear');
		
		this.smilies.appendChild(this.left_arrow);
		wrapper.appendChild(internal_wrapper);
		this.smilies.appendChild(wrapper);
		this.smilies.appendChild(this.right_arrow);
		this.smilies.appendChild(break_clear);

		YAHOO.util.Event.on(this.left_arrow, 'click', function(event) {
			var anim = new YAHOO.util.Scroll(wrapper, { scroll: { by: [-parseInt(wrapper.style.width, 10)+50, 0] } }, 0.5, YAHOO.util.Easing.easeOutStrong);
			anim.animate();
		});
		YAHOO.util.Event.on(this.right_arrow, 'click', function(event) {
			var anim = new YAHOO.util.Scroll(wrapper, { scroll: { by: [parseInt(wrapper.style.width, 10)-50, 0] } }, 0.5, YAHOO.util.Easing.easeOutStrong);
			anim.animate();
		});
		
		YAHOO.util.Dom.addClass(this.smilies, 'smilies');
		for (var symbol in config.smilies) {
			if(symbol != ":P")
			{
				var emoticon = this.build_emoticon({symbol: symbol, name: config.smilies[symbol]});
				internal_wrapper.appendChild(emoticon);
			}
		}

		return this.smilies;
	},
	
	build_emoticon: function(config)
	{
		var emoticon = document.createElement("img");
		Nexopia.DelayedImage.setDelayedSrc(emoticon, Site.staticFilesURL + "/Legacy/smilies/" + config.name + ".gif");
		emoticon.alt = config.symbol;
		YAHOO.util.Event.on(emoticon, 'click', function(e)
		{
			this.insert(" " + config.symbol + " ", "");
		}, this, true);
		return emoticon;
	},
	
	build_menu: function(config, parent)
	{
		var button = document.createElement("input");
		button.type = "button";
		button.value = config.label;

		var menu = document.createElement("ul");
		for (var i=0; i<config.options.length; i++) {
			var option = this.build_option(config.options[i]);
			menu.appendChild(option);
		}
		
		parent.appendChild(button);

		yuiMenu = new YAHOO.widget.Button(button, {
			type: "menu",
			menu: menu,
			lazyloadmenu: false
		});
		
		yuiMenu.getMenu().cfg.setProperty("iframe", true);
		yuiMenu.getMenu().cfg.setProperty("zindex", "500");

		return false;
	},
	
	build_option: function(config)
	{
		var option = document.createElement("li");
		if (config.color)
		{
			YAHOO.util.Dom.addClass(option, "no_shading_color");
			option.style.color = config.color;
		}
		option.innerHTML = config.label;
		YAHOO.util.Event.on(option, 'mousedown', function(e, args)
		{
			args[0].insert(args[1], args[2]);
		}, [this, config.pre, config.post]);
		return option;
	},
	
	// Selection and Caret Position Finding
	getSelectionRange: function()
	{
		var caret_position = [0,0];
		
		// IE Support
		if (document.selection)
		{
			this.text_box.focus();
			var range = document.selection.createRange();
			var stored_range = range.duplicate();
			stored_range.moveToElementText(this.text_box);
			stored_range.setEndPoint( 'EndToEnd', range );
			
			caret_position[0] = stored_range.text.length - range.text.length;
			caret_position[1] = caret_position[0] + range.text.length;
	
			// alert(YAHOO.lang.dump(caret_position));
			
			this.text_box.focus();
		}
		else if (this.text_box.selectionStart || this.text_box.selectionStart == '0')
		{
			caret_position[0] = this.text_box.selectionStart;
			caret_position[1] = this.text_box.selectionEnd;
		}
		
		return caret_position;
	},
	
	setCaretPosition: function(position)
	{
		if(this.text_box.setSelectionRange)
		{
			this.text_box.focus();
			this.text_box.setSelectionRange(position,position);
		}
		else if(this.text_box.createTextRange)
		{
			var range = this.text_box.createTextRange();
			range.collapse(true);
			range.moveEnd('character', position);
			range.moveStart('character', position);
			range.select();
		}
	},
	
	// Insert Markup
	insert: function(open, close)
	{
		var current_scroll_position = this.text_box.scrollTop;
		this.text_box.focus();
			
		var current = this.text_box.value;
		var sel = this.getSelectionRange(this.text_box);
		
 		// alert("insert " + YAHOO.lang.dump(sel));
		
		var beginning = current.slice(0, sel[0]);
		var middle = current.slice(sel[0], sel[1]);
		var end = current.slice(sel[1]);
		
		this.text_box.value = beginning + open + middle + close + end;
		this.text_box.scrollTop = current_scroll_position;
		
		if(middle.length > 0)
			this.setCaretPosition((beginning + open + middle + close).length);
		else
			this.setCaretPosition((beginning + open).length);
	},
	
	// Selection saving and restoring
	save_selection: function()
	{
		// IE (the third condition keeps IE from jumping to the top of the page when there's no selection)
		if (document.selection && document.selection.createRange && document.selection.type != "None") 	
			this.stored_selection = document.selection.createRange();
		else
			this.stored_selection = this.getSelectionRange();
		// alert("stored selection " + this.stored_selection);
	},
	
	load_selection: function()
	{
		// IE
		if (document.selection && document.selection.createRange && document.selection.type != "None") {
				try
				{
					this.text_box.focus();
					this.setCaretPosition(this.stored_selection[0]);
					this.stored_selection.select();
				}
				catch(err)
				{
				//Handle errors here
				}
		} else {
			this.text_box.selectionStart = this.stored_selection[0];
			this.text_box.selectionEnd = this.stored_selection[1];
		}
	},
	deduceArrowColor: function()
	{
		var iconColor = '000000';
		
		var profileDiv = document.getElementById('profile');
		if (profileDiv)
		{
			var primaryDiv = document.createElement('div');
			primaryDiv.className = 'primary_block';
			profileDiv.appendChild(primaryDiv);
			iconColor = Nexopia.Utilities.deduceImgColor(primaryDiv);
			profileDiv.removeChild(primaryDiv);
		}
		return iconColor;
	}
};
