lib_require :devtopia, 'log';

module Devtopia
	class Product < Storable
		set_db(:devtaskdb);
		set_table("product");
		init_storable();
		

		def Product.get_assigned(programmer)
			products = Devtopia::Product.find(:all, :scan);

			assigned_products = products.select { |product|
				!product.tasks(programmer.userid).empty?
			};
						
			return assigned_products;
		end
		
		
		def tasks(programmer=nil)
			if (programmer.nil?)
				return @tasks;
			else
				return @tasks.select { |task| task.assigned_to?(programmer) };
			end
		end
		
		
		def after_load()
			@tasks  = Task.find(:all, :scan, :promise, id, :order => "state");
			@unassigned_tasks = nil;
		end


		def after_create()
			@tasks  = Task.find(:all, :scan, :promise, id, :order => "state");
			@unassigned_tasks = nil;
		end
		
		
		def unassigned_tasks
			if (@unassigned_tasks.nil?)
				@unassigned_tasks = Array.new;
				
				@tasks.each { | task |
					if (!task.assigned?)
						@unassigned_tasks << task;
					end
				};
			end
			
			return @unassigned_tasks;
		end
		
		
		def add_link(record_type)
			return "/my/projectmanager/add/#{self.type}/#{self.get_primary_key}/#{record_type}";
		end
		
		
		def parent_id
			return 0;
		end
		
		
		def parent_id=(id)
			# Do nothing
		end
		
		def display_columns
			return ["name"];
		end
		
		def edit_link
			return "/my/projectmanager/products/list/#{id}";
		end

		include Devtopia::Loggable;

	end
end