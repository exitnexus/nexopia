function ProfileDisplayBlock()
{
	this.html_id = "";
}
ProfileDisplayBlock.prototype = {
	init: function(key)
	{
		block_info = this.get_block_info(key);
		
		this.path = block_info.path;
		this.moduleid = block_info.module_id;
		this.visibility = block_info.default_visibility();
		this.columnid = block_info.initial_column;
		this.position = block_info.initial_position;
	},

	get_block_info: function(key)
	{
		if(key == null)
		{
			key = this.moduleid + "-" + this.path;
		}
		
		if(this.block_info == null)
		{
			this.block_info = YAHOO.profile.block_info_list[key];
		}
		
		return this.block_info;
	},
	
	new_block: function()
	{
		if(this.blockid == null)
		{
			return true;
		}
		return false;
	},
	
	save_path: function()
	{
		var path;
		if(YAHOO.profile.admin_user)
		{
			path = YAHOO.profile.admin_uri_base + "/profile/edit/" + this.blockid + "/save";
			return path;
		}
		
		if(this.new_block())
		{
			path = "/my/profile/edit/create";
		}
		else if(!this.explicit_save())
		{
			path = "/my/profile/edit/" + this.blockid + "/visibility?module_name=" + this.module_name() + "&path=" + this.path;;
		}
		else
		{
			path = "/my/profile/edit/" + this.blockid + "/save";
		}
		return path;
	},
	
	refresh_path: function()
	{
		var path;
		if(YAHOO.profile.admin_user)
		{
			path = YAHOO.profile.admin_uri_base + "/profile/block/" + this.blockid + "/refresh";
			return path;
		}
		path = "/my/profile/block/" + this.blockid + "/refresh";
		return path;
	},
	
	edit_path: function()
	{
		var path;
		if(YAHOO.profile.admin_user)
		{
			path = YAHOO.profile.admin_uri_base + "/profile/edit/block/" + this.blockid + "/view";
			return path;
		}
		
		if(this.new_block())
		{
			path = "/my/profile/edit/block/new?module_name=" + this.module_name() + "&path=" + this.path;
		}
		else
		{
			path = "/my/profile/edit/block/" + this.blockid + "/view";
		}
		return path;
	},
	
	
	remove_path: function()
	{
		var path;
		if(YAHOO.profile.admin_user)
		{
			path = YAHOO.profile.admin_uri_base + "/profile/edit/block/" + this.blockid + "/remove";
		}
		else
		{
			var path = "/my/profile/edit/block/" + this.blockid + "/remove";
		}
		
		return path;
	},
	
	update: function (block_id)
	{
		this.blockid = block_id;
		this.html_id = "block" + this.columnid + "_" + this.blockid;
	}
};

ProfileDisplayBlock.prototype.moveable = function()
{
	block_info = this.get_block_info();
	
	return block_info.moveable;
};

ProfileDisplayBlock.prototype.removable = function()
{	
	block_info = this.get_block_info();
	
	return block_info.removable;
};

ProfileDisplayBlock.prototype.title = function()
{
	block_info = this.get_block_info();
	
	return block_info.title;
};

ProfileDisplayBlock.prototype.editable = function()
{
	if(this.content_error)
	{
		return false;
	}
	
	block_info = this.get_block_info();
	
	if(this.new_block())
	{
		return block_info.editable;
	}
	else
	{
		return block_info.editable && !block_info.immutable_after_create;
	}
};

ProfileDisplayBlock.prototype.in_place_editable = function()
{
	if(this.content_error)
	{
		return false;
	}
	
	block_info = this.get_block_info();
	
	return block_info.in_place_editable;
};

ProfileDisplayBlock.prototype.custom_edit_button = function()
{
	if(this.content_error)
	{
		return false;
	}
	
	block_info = this.get_block_info();
	
	return block_info.custom_edit_button;
};

ProfileDisplayBlock.prototype.plus_only = function()
{
	block_info = this.get_block_info();
	
	return block_info.plus_only;
};

ProfileDisplayBlock.prototype.multiple = function()
{
	block_info = this.get_block_info();
	
	return block_info.multiple;
};

ProfileDisplayBlock.prototype.max_number = function()
{
	block_info = this.get_block_info();
	
	return block_info.max_number;
};

ProfileDisplayBlock.prototype.form_factor = function()
{
	block_info = this.get_block_info();
	
	return block_info.form_factor;
};

ProfileDisplayBlock.prototype.module_name = function()
{
	block_info = this.get_block_info();
	
	return block_info.module_name;
};

ProfileDisplayBlock.prototype.explicit_save = function()
{
	if(YAHOO.profile.admin_user)
	{
		return true;
	}
		
	block_info = this.get_block_info();
	
	return block_info.explicit_save;
};

ProfileDisplayBlock.prototype.javascript_init_function = function()
{
	block_info = this.get_block_info();
	
	return block_info.javascript_init_function;
};

ProfileDisplayBlock.prototype.visibility_exclude = function()
{
	block_info = this.get_block_info();
	
	return block_info.visibility_exclude();
};

ProfileDisplayBlock.getBlockById = function(id)
{
	for (var i = 0; i < YAHOO.profile.display_block_list.length; i++)
	{
		if (YAHOO.profile.display_block_list[i].html_id == id)
		{
			return YAHOO.profile.display_block_list[i];
		}
	}

	return null;
};