lib_require :Devtopia, "programmer", "taskprogrammer", "log"

module Devtopia
	class Task < Storable
		set_enums(
			:state => {:new => 0, :in_progress => 1, :done => 2, :cancelled => 3 }
		);	

		set_db(:devtaskdb);
		set_table("task");
		init_storable();

		def todos
			if (demand(@todos).nil?)
				@todos = Array.new;
			end
		
			return @todos;
		end

	
		def after_load()
			@todos  = Devtopia::Todo.find(:all, :scan, :promise, productid, id, :conditions => ["parentid=0"], :order => "state");
			@unassigned_todos = nil;
			@sprint = Devtopia::Sprint.find(:first, :promise, projectid, sprintid);
		end


		def after_create()
			@todos  = Devtopia::Todo.find(:all, :scan, :promise, productid, id, :conditions => ["parentid=0"], :order => "state");
			@unassigned_todos = nil;
			@sprint = Devtopia::Sprint.find(:first, :promise, projectid, sprintid);
		end

		def unassigned_todos
			if (@unassigned_todos.nil?)
				@unassigned_todos = Array.new;
				
				todos.each { | todo |
					if (!todo.assigned?)
						@unassigned_todos << todo;
					end
				};
			end
			
			return @unassigned_todos;
		end
		

		def assigned?
			return (!todos.empty? && unassigned_todos.empty?) || (todos.empty? && self.projectid != 0 && self.sprintid != 0);
		end
		
	
		ProgrammerStatus = Struct.new :userid, :username, :checked;
		@@programmers = nil;
	
		def programmers
			if (@@programmers.nil?)
			 	@@programmers = Devtopia::Programmer.find(:all, :scan);
			end
			
			programmer_status = Array.new;
			task_programmers = Devtopia::TaskProgrammer.find(:all, :scan, self.productid, self.id).map { |task_programmer| task_programmer.userid };
			@@programmers.each { | programmer |
				checked = task_programmers.include?(programmer.userid);
				programmer_status << ProgrammerStatus.new(programmer.userid, programmer.username, checked);
			};
			
			return programmer_status;
		end
		
		
		def assign_programmers(programmer_ids)
			ids = Array.new(programmer_ids);
			task_programmers = Devtopia::TaskProgrammer.find(:all, :scan, self.productid, self.id);
			task_programmers.each { | task_programmer |
				if (!ids.include?(task_programmer.userid))
					task_programmer.delete;
				else
					ids.delete(task_programmer.userid);
				end
			};
			
			ids.each { | remaining_id | 
				new_task_programmer = Devtopia::TaskProgrammer.new;
				new_task_programmer.productid = self.productid;
				new_task_programmer.taskid = self.id;
				new_task_programmer.userid = remaining_id;
				new_task_programmer.store;
			};
		end
		
		
		def sprint_name()
			if (@sprint.nil?)
				return "";
			else
				return @sprint.name;
			end
		end
		
		
		def valid_state?(check_state)
			if (enums[:state][check_state].to_i >= enums[:state][self.lowest_todo_state].to_i)
				return true;
			else
				return false;
			end	
		end
		
		
		def lowest_todo_state
			if (todos.empty?)
				return :new;
			end
			
			lowest_state = :cancelled;
			
			todos.each{ | todo |
				if (todo.state!.value.to_i < enums[:state][lowest_state].to_i)
					lowest_state = todo.state;
				end
			}

			return lowest_state;
		end
		
		
		def cumulative_time
			time = 0;
			
			todos.each { | todo |
				time = time + todo.cumulative_time;
			};
			
			return time;
		end
		
		
		def calc_expectedtime
			time = self.expectedtime;
			
			todos.each { | todo |
				time = time - todo.cumulative_time;
			};
			
			time = (time < 0) ? 0 : time;
			
			return time;
		end
		
		
		def assigned_to?(programmer)
			if (programmer.nil?)
				return true;
			end
			
			if (programmer.kind_of? Devtopia::Programmer)
				programmer = programmer.userid;
			end
			
			return ! Devtopia::TaskProgrammer.find(:first, self.productid, self.id, programmer).nil?;
		end
		
		
		def display_columns
			return ["name", "expectedtime", "state"];
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
				if (self.state == :done || self.state == :cancelled)
					self.todos.each { | todo | 
						if (todo.state != :done && todo.state != :cancelled)
							todo.state = self.state;
							todo.store;
						end
					};
				end
			end
			
			@state_changed = false;
		end
		
		
		def edit_link
			return "/my/projectmanager/products/#{productid}/tasks/list/#{id}";
		end
		
		
		def parent_id=(value)
			self.productid = value;
		end
		
		
		def parent_id
			return self.productid;
		end
		
		
		def complete!
			self.state = :done;
			self.store;
			
			todos.each { |todo|
				todo.complete!;
			}
		end
		
		
		include Devtopia::Loggable;		
				
	end
end