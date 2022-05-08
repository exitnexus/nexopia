require 'singleton'
lib_require :Profile, "profile_block_query_info_module";

module Profile
	class ProfileBlockQueryMediator
		include Singleton
		
		attr_accessor :block_handler_query_cache, :full_list_cached, :menu_list_cached, :initial_list_cached;
		
		def initialize()
			self.block_handler_query_cache = Hash.new();
			self.full_list_cached = false;
		end
		
		def initial_blocks()
			if(!self.initial_list_cached.nil?())
				return self.initial_list_cached;
			end
			
			if(!self.full_list_cached)
				self.list_blocks();
			end
			
			initial_block_list = Array.new();
			
			module_list = self.block_handler_query_cache.keys();
			for block_module in module_list
				for block_name in self.block_handler_query_cache[block_module].keys()
					block = block_module[block_name];
					if(block.nil?())
						block = self.query_block(block_module, block_name);
					end
					
					if(block.kind_of?(ProfileBlockQueryInfo) && block.initial_block)
						initial_block_list << block;
					end
				end
			end
			
			self.initial_list_cached = initial_block_list;
			
			return initial_block_list;
		end
		
		def menu_blocks()
			if(!self.menu_list_cached.nil?())
				return self.menu_list_cached;
			end
			
			if(!self.full_list_cached)
				self.list_blocks();
			end
			
			menu_block_list = Array.new();
			
			module_list = self.block_handler_query_cache.keys();
			for block_module in module_list
				for block_name in self.block_handler_query_cache[block_module].keys()
					block = block_module[block_name];
					if(block.nil?())
						block = self.query_block(block_module, block_name);
					end
					
					if(block.kind_of?(ProfileBlockQueryInfo) && !block.page_url.nil?())
						menu_block_list << block;
					end
				end
			end
			
			self.menu_list_cached = menu_block_list.sort{|x,y| x.page_url[2] <=> y.page_url[2]};
			
			return self.menu_list_cached;
		end
		
		def list_blocks(module_name = "")
			if(module_name.length == 0)
				module_list = PageHandler.list_uri(url/:profile_blocks, :User);
					for block_module in module_list
						if(self.block_handler_query_cache[block_module].nil?())
							list_blocks(block_module);
						end
					end
				self.full_list_cached = true;
				
				return self.build_list();
			end
			
			block_list = block_handler_query_cache[module_name];
			if(block_list.nil?())
				block_list = PageHandler.list_uri(url/:profile_blocks/module_name, :User);
				block_handler_query_cache[module_name] = Hash.new();
				for block in block_list
					(self.block_handler_query_cache[module_name])[block] = nil;
				end
			end
			
			return self.build_list(module_name);
		end
		
		def query_block(module_name, block_path)
			block_list = self.block_handler_query_cache[module_name];
			if(block_list.nil?())
				self.list_blocks(module_name);
				block_list = self.block_handler_query_cache[module_name];
			end
			
			block_query = block_list[block_path];
			if(block_query.nil?())
				block_query = PageHandler.query_uri(:GetRequest, url/:profile_blocks/module_name/block_path/"0", :User);
				
				if(block_query.kind_of?(ProfileBlockQueryInfo))
					block_query.module_name = module_name;
					block_query.path = block_path;
				end
				block_list[block_path] = block_query;
			end
			
			return block_list[block_path];
		end
		
		def build_list(module_name = "")
			temp_list = Array.new();
			if(module_name.length == 0)
				for key in self.block_handler_query_cache.keys()
					self.build_list(key).each{|item| temp_list << item};
				end
			else
				block_list = self.block_handler_query_cache[module_name];
				for block_name in block_list.keys()
					temp_list << [module_name, block_name];
				end
			end
			return temp_list;
		end
	end
end
