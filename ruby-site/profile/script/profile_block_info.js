PROFILE = {};

function ProfileBlockInfo(module_id, path){
	this.module_id = module_id;
	this.path = path;
}

ProfileBlockInfo.prototype = {
	generate_key: function(){
		if(this.key == null || this.key == ""){
			var s;
			s = this.module_id + "-" + this.path;
			this.key = s;
		}
		
		return this.key;
	},
	
	create_display_block: function(){
		var temp;

		temp = new ProfileDisplayBlock();
		temp.init(this.generate_key);
		
		return temp;
	},
	
	visibility_exclude: function(){
		if(this.visibility_exclude_list == null)
		{
			var temp = this.javascript_visibility_exclude.split(",");
			this.visibility_exclude_list = new Array();
			for(var i=0; i<temp.length; i++)
			{
				this.visibility_exclude_list.push(parseInt(temp[i], 10));
			}
		}
		return this.visibility_exclude_list;
	},
	
	default_visibility: function()
	{
		return this.javascript_default_visibility;
	},
	
	can_make_more: function()
	{
		// Count the existing blocks
		var count = 0;
		for (var i=0; i < YAHOO.profile.display_block_list.length; i++)
		{
			var existingBlock = YAHOO.profile.display_block_list[i];
			if (existingBlock.moduleid === this.module_id &&
				existingBlock.path === this.path)
			{
				count++;
			}

		}
		
		return count < this.max_number;
	}
};