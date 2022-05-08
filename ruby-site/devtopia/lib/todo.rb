lib_require :devtopia, 'log', 'task';

module Devtopia
	class Todo < Storable
		set_enums(
			:state => {:new => 0, :in_progress => 1, :done => 2, :cancelled => 3 }
		);	

		set_db(:devtaskdb);
		set_table("todo");
		init_storable();
			
		def todos
			if (demand(@todos).nil?)
				@todos = Array.new;
			end
		
			return @todos;
		end

	
		def after_load()
			@todos  = Todo.find(:all, :scan, :promise, self.productid, self.taskid, :conditions => ["parentid=?", self.id]);
		end


		def after_create()
			@todos  = Todo.find(:all, :scan, :promise, self.productid, self.taskid, :conditions => ["parentid=?", self.id]);
		end
		
		
		def before_update()
			if (self.modified?("state"))
				@state_changed = true;
			else
				@state_changed = false;
			end
		end
		
		
		def after_update()
			super();
			
			if (@state_changed)
				parent_task = Devtopia::Task.find(:first, self.productid, self.taskid);
						
				if (parent_task.state == :new)
					if (!parent_task.todos.select { | todo | todo.state == :in_progress }.empty? ||
							parent_task.todos.select { | todo | todo.state != :done && todo.state != :cancelled }.empty?)
						parent_task.state = :in_progress;
						parent_task.store;
					end
				elsif (parent_task.state == :done || parent_task.state == :cancelled)
					if (self.state == :in_progress || self.state == :new)
						parent_task.state = self.state;
						parent_task.store;
					end
				end
			end
			
			@state_changed = false;
		end
		
		
		def assigned?
			if (projectid == 0 || sprintid == 0)
				return false;
			else
				return true;
			end
		end
		
		
		def cumulative_time
			time = 0;
			
			todos.each { | todo |
				time = time + todo.cumulative_time;
			};

			if (time < calc_ttc)
				time = calc_ttc;
			end
			
			return time;
		end
		

		def calc_ttc
			time = self.ttc;
			
			todos.each { | todo |
				time = time - todo.cumulative_time;
			};
			
			time = (time < 0) ? 0 : time;
			
			return time;
		end
		
				
		def parent_id=(value)
			id_array = value.split("-");

			self.productid = id_array[0].to_i;
			self.taskid = id_array[1].to_i;
			
			# If it is a parent TODO, the todo.parentid will be the parent todo's todo.id.
			self.parentid = id_array[2].to_i || 0;
			
			# Also set the most appropriate sprint values if they exist already.
			parent_with_sprint = get_parent_with_sprint();
			
			if (parent_with_sprint.nil?)
				$log.info("Problem with retrieving Todo item #{self.productid}/#{self.taskid}/#{self.parentid}. Not setting project and sprint for this.", :error);
			else
				self.projectid = parent_with_sprint.projectid;
				self.sprintid = parent_with_sprint.sprintid;
			end
		end
		
		
		def get_parent()
			if (self.parentid != 0)
				return Devtopia::Todo.find(:first, self.productid, self.taskid, self.parentid);
			else
				return Devtopia::Task.find(:first, self.productid, self.taskid);
			end
		end
		
		
		def get_parent_with_sprint()
			todo_parent = self.get_parent();

			if (todo_parent.nil? || todo_parent.kind_of?(Devtopia::Task))
				return todo_parent;
			end
			
			if (todo_parent.projectid != 0 && todo_parent.sprintid != 0)
				return todo_parent;
			end
			
			return todo_parent.get_parent_with_sprint();
		end
		
		
		def parent_id
			return "#{self.productid}-#{self.taskid}-#{self.parentid}";
		end

		def display_columns
			return ["name", "ttc", "cumulative_time", "state"];
		end

		def edit_link
			return "/my/projectmanager/products/#{productid}/tasks/#{taskid}/todos/#{parentid}/list/#{id}";
		end
		
		
		def complete!
			self.state = :done;
			self.ttc = 0;
			self.store;
			
			todos.each { |todo|
				todo.complete!;
			}
		end
		
				
		include Devtopia::Loggable;
		
	end
end