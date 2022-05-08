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
	}
}
