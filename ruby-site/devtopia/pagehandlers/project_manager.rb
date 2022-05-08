lib_require :core, "storable/storable"
lib_require :devtopia, "project", "sprint", "product", "task", "todo", "programmer"

require "rexml/document";

class ProjectManager < PageHandler

	declare_handlers("projectmanager") {
		area :Self
		
		# Default
		page :GetRequest, :Full, :list_projects
		
		page :GetRequest, :Full, :list_projects, "projects", "list"
		page :GetRequest, :Full, :list_projects, "projects", "list", input(Integer)
		page :GetRequest, :Full, :edit_project, "projects", input(Integer);
		
		page :GetRequest, :Full, :list_products, "products", "list"
		page :GetRequest, :Full, :list_products, "products", "list", input(Integer)
		page :GetRequest, :Full, :list_sprints, "sprints", input(Integer), "list"
		page :GetRequest, :Full, :list_sprints, "sprints", input(Integer), "list", input(Integer)
		page :GetRequest, :Full, :product_backlog, "products", "backlog"
		page :PostRequest, :Full, :product_backlog_update, "products", "backlog", "update"
		page :GetRequest, :Full, :sprint_backlog, "sprints", input(Integer), input(Integer), "backlog"
		page :GetRequest, :Full, :list_product_tasks, "products", input(Integer), "tasks", "list"
		page :GetRequest, :Full, :list_product_tasks, "products", input(Integer), "tasks", "list", input(Integer)
		page :GetRequest, :Full, :list_sprint_tasks, "sprints", input(Integer), input(Integer), "tasks", "list"
		
		page :GetRequest, :Full, :list_task_todos, "products", input(Integer), "tasks", input(Integer), "todos", input(Integer), "list"
		page :GetRequest, :Full, :list_task_todos, "products", input(Integer), "tasks", input(Integer), "todos", input(Integer), "list", input(Integer)
		
		handle :PostRequest, :record_details, "record_details", input(String), input(String)
		handle :PostRequest, :record_save, "record_save", input(String), input(String)
		
		page :GetRequest, :Full, :related_object_editor, "related_object_editor"
		
		page :GetRequest, :Full, :sprint_selector, "sprint_selector"
		page :GetRequest, :Full, :sprint_selector, "sprint_selector", input(Integer), input(Integer)
		
		page :GetRequest, :Full, :programmer_filter, "programmer_filter"
		page :PostRequest, :Full, :programmer_filter, "programmer_filter", input(Integer)
		
		page :PostRequest, :Full, :summary_panel_refresh, "summary", "refresh"

		page :GetRequest, :Full, :todo_summary, "todos", "summary"
		page :GetRequest, :Full, :todo_summary, "todos", "summary", input(Integer)
		page :PostRequest, :Full, :todo_summary_update, "todos", "summary", "update"
		
		page :PostRequest, :Full, :mark_todo_done, "todos", "done", input(Integer), input(Integer), input(Integer)
		page :PostRequest, :Full, :mark_task_done, "tasks", "done", input(Integer), input(Integer)
	}
	
	def list_task_todos(product_id, task_id, parent_id, todo_id=nil)
		t = Template.instance("devtopia", "list_todos");
		
		t.records = Devtopia::Todo.find(:all, :scan, product_id, task_id, :conditions => ["parentid=?", parent_id]);
		t.record = Devtopia::Todo.find(:first, product_id, task_id, todo_id, :conditions => ["parentid=?", parent_id]) || Devtopia::Todo.new;
		t.record_type = Devtopia::Todo;
		
		if (parent_id == 0)
			t.parent_record = Devtopia::Task.find(:first, product_id, task_id);
		else
			t.parent_record = Devtopia::Todo.find(:first, product_id, task_id, parent_id);
		end
		
		project_id = params["project_id", Integer, nil];
		sprint_id = params["sprint_id", Integer, nil];
		
		t.project_id = project_id;
		t.sprint_id = sprint_id;
		
		t.programmer = Devtopia::Programmer.find(:first, session.user.userid);
		
		t.directing_record = t.parent_record;
		
		puts t.display;
	end
	
	
	def list_projects(project_id=nil)
		t = Template.instance("devtopia", "list_projects");
		
		t.records = Devtopia::Project.find(:all, :scan);
		t.record = Devtopia::Project.find(:first, project_id) || (t.records.nil? ? Devtopia::Project.new : t.records[0]);
		t.record_type = Devtopia::Project;
		t.parent_record = nil;
		
		puts t.display;
	end
	
	
	def edit_project(project_id)
		t = Template.instance("devtaskman", "edit_project");
		
		puts t.display;
	end
	
	
	def list_products(product_id=nil)
		t = Template.instance("devtopia", "list_products");

		t.records = Devtopia::Product.find(:all, :scan);
		t.record = Devtopia::Product.find(:first, product_id) || (t.records.nil? ? Devtopia::Product.new : t.records[0]);
		t.record_type = Devtopia::Product;
		t.parent_record = nil;

		puts t.display;
	end
	
	
	def list_sprints(project_id, selected_sprint_id=nil)
		t = Template.instance("devtopia", "list_sprints");

		t.records = Devtopia::Sprint.find(:all, :scan, project_id);
		t.record = Devtopia::Sprint.find(:first, project_id, selected_sprint_id) || Devtopia::Sprint.new;
		t.record_type = Devtopia::Sprint;
		t.parent_record = Devtopia::Project.find(:first, project_id);
		t.directing_record = t.parent_record;
		t.programmer = Devtopia::Programmer.find(:first, session.user.userid);
		
		puts t.display;	
	end
	
	
	def product_backlog
		t = Template.instance("devtopia", "product_backlog");
		
		t.products = Devtopia::Product.find(:all, :scan);
		t.sprints = Devtopia::Sprint.find(:all, :scan);

		puts t.display;	
	end
	

	def product_backlog_update
		sprint_id_string = params["sprint", String];
		sprint_id = sprint_id_string.split("_");
		
		if (sprint_id.length > 0)
			task_hash = params["task", TypeSafeHash, Hash.new];
			task_hash.each { | key | 
				primary_key = key.split("_");
				task = Devtopia::Task.find(:first, primary_key);
				if (!task.nil?)
					task.projectid = sprint_id[0];
					task.sprintid = sprint_id[1];
					task.store;
				end
			};
		
		
			todo_hash = params["todo", TypeSafeHash, Hash.new];
			todo_hash.each { | key | 
				primary_key = key.split("_");
				todo = Devtopia::Todo.find(:first, primary_key);
				if (!todo.nil?)
					todo.projectid = sprint_id[0];
					todo.sprintid = sprint_id[1];
					todo.store;
				end
			};	
		end
		
		product_backlog;
	end

	
	def show_sprint_backlog(project_id, sprint_id)
		t = Template.instance("devtopia", "sprint_backlog");
		
		puts t.display;
	end
	
	
	def list_product_tasks(product_id, selected_task_id=nil)
		t = Template.instance("devtopia", "list_product_tasks");

		project_id = params["project_id", Integer, nil];
		sprint_id = params["sprint_id", Integer, nil];	
	
		programmer_filter = params["programmer", Integer, nil];
		conditions_array = ["projectid = ? AND sprintid = ?", project_id, sprint_id];
		if (programmer_filter.nil?)
			if (!project_id.nil? && !sprint_id.nil?)
				tasks = Devtopia::Task.find(:all, :scan, product_id, :conditions => conditions_array);
			else
				tasks = Devtopia::Task.find(:all, :scan, product_id);
			end
			
			t.records = tasks;
		else
			if (!project_id.nil? && !sprint_id.nil?)
				tasks = Devtopia::Task.find(:all, :scan, :conditions => conditions_array);
			else
				tasks = Devtopia::Task.find(:all, :scan);
			end
			
			programmer = Devtopia::Programmer.find(:first, programmer_filter);
			t.records = tasks.select { |task| task.assigned_to?(programmer.filter_programmer) };
		end
		
		t.record = Devtopia::Task.find(:first, product_id, selected_task_id) || Devtopia::Task.new;
		t.record_type = Devtopia::Task;
		t.parent_record = Devtopia::Product.find(:first, product_id);
		t.programmers = Devtopia::Programmer.find(:all, :scan);
		t.programmer = Devtopia::Programmer.find(:first, session.user.userid);
		
		t.project_id = project_id;
		t.sprint_id = sprint_id;
		
		if (!project_id.nil? && !sprint_id.nil?)
			t.directing_record = Devtopia::Sprint.find(:first, project_id, sprint_id);
		else
			t.directing_record = t.parent_record;
		end
		
		puts t.display;	
	end


	def todo_summary(programmer_id=nil)
		programmer_id = programmer_id || session.user.userid;
		
		t = Template.instance("devtopia", "todo_summary");
		t.programmer = Devtopia::Programmer.find(:first, programmer_id);
		t.products = Devtopia::Product.get_assigned(t.programmer);
		t.handler = self;
		
		puts t.display;
	end
	
	
	def todo_summary_update()
		todo_states = params["todo_state", TypeSafeHash, nil];
		todo_ttcs = params["todo_ttc", TypeSafeHash, nil];
		programmer_id = params["programmer_id", Integer, nil];
		
		todo_states.each { |key,value|
			id = key.split("_");
			state = todo_states[key, Integer];
			ttc = todo_ttcs[key, Fixnum];

			todo = Devtopia::Todo.find(:first, id);
			if (!todo.nil?)
				todo.state = state if (todo.state!.value != state);
				todo.ttc = ttc if (todo.ttc != ttc);
				$log.info "-----modified-----" if todo.modified?;
				$log.info "ID: #{id * %q(,)} / State: #{state} / TTC: #{ttc}" if todo.modified?;
				todo.store if todo.modified?;
			end	
		}
		
		new_todo_names = params["new_todo_name", TypeSafeHash, Array.new];
		new_todo_states = params["new_todo_state", TypeSafeHash, Array.new];
		new_todo_ttcs = params["new_todo_ttc", TypeSafeHash, Array.new];
		
		new_todo_names.each { |key,value| 
			parent_id = key.split("_");
			
			# All new key values will have an extra arbitrary number appended to them in order 
			# to avoid conflicts.
			parent_id.pop;

			name = new_todo_names[key, String];
			state = new_todo_states[key, Integer];
			ttc = new_todo_ttcs[key, Fixnum];
			
			todo = Devtopia::Todo.new;
			todo.parent_id = (parent_id * "-").to_s;
			todo.name = name;
			todo.state = state;
			todo.ttc = ttc;
			todo.description = "";
			todo.store;
		};
		
		url = "/projectmanager/todos/summary"
		if (!programmer_id.nil? && programmer_id != 0)
			url = url + "/#{programmer_id}";
		end
		
		site_redirect(url, :Self);
	end
	
	
	def html_todo_tree(todo_or_task)
		if (todo_or_task.nil?)
			return "";
		end
		
		tree_string = "<ul id='ul_#{todo_or_task.get_primary_key * %q(_)}'>";
		
		todo_or_task.todos.each { | todo |
			todo_edit_string = <<-EOS
				#{html_todo(todo)}
			EOS
			
			tree_string = tree_string + "<li>" + todo_edit_string + "</li>";
		}
		
		tree_string = tree_string + "</ul>";
		
		return tree_string;
	end
	
	
	def html_task(task)
		task_edit_string = <<-EOS
			<li id="li_#{task.productid}_#{task.id}">
				<span id="span_#{task.productid}_#{task.id}" 
					#{"style='text-decoration: line-through'" if task.state == :done || task.state == :cancelled}>#{task.name}&#160;</span>
				
				<a href="javascript:;" onclick="TodoSummary.addChild('#{task.get_primary_key * %q(_)}')">
					<span style="font-size: 14px; font-weight: 700">+</span>
				</a>&#160;&#160;
				<a href="javascript:;" onclick="TodoSummary.markTaskDone('#{task.get_primary_key * %q(_)}')">
					<span style="font-size: 11px; font-weight: 700">DONE</span>
				</a>
				#{html_todo_tree(task)}
			</li>
		EOS
		
		return task_edit_string;
	end
	
	
	def html_todo(todo)
		todo_edit_string = <<-EOS
			<span id="span_#{todo.productid}_#{todo.taskid}_#{todo.id}" 
				#{"style='text-decoration: line-through'" if todo.state == :done || todo.state == :cancelled}><b>#{todo.name}</b></span>&#160;
			<select id="todo_state[#{todo.productid}_#{todo.taskid}_#{todo.id}]" name="todo_state[#{todo.productid}_#{todo.taskid}_#{todo.id}]">
				<option value="0" #{"selected" if todo.state == :new}>New</option>
				<option value="1" #{"selected" if todo.state == :in_progress}>In Progress</option>
				<option value="2" #{"selected" if todo.state == :done}>Done</option>
				<option value="3" #{"selected" if todo.state == :cancelled}>Cancelled</option>
			</select><b>&#160;&#160;|&#160;&#160;</b>
			TTC:&#160;
			<input type="text" style="width:30px" 
				id="todo_ttc[#{todo.productid}_#{todo.taskid}_#{todo.id}]"
				name="todo_ttc[#{todo.productid}_#{todo.taskid}_#{todo.id}]"
				value="#{todo.ttc}" />
			<a href="javascript:;" onclick="TodoSummary.addChild('#{todo.get_primary_key * %q(_)}')">
				<span style="font-size: 14px; font-weight: 700">+</span>
			</a>&#160;
			<a href="javascript:;" onclick="TodoSummary.markTodoDone('#{todo.get_primary_key * %q(_)}')">
				<span style="font-size: 11px; font-weight: 700">DONE</span>
			</a>
			#{html_todo_tree(todo)}
		EOS
		
		return todo_edit_string;		
	end
	
	
	def list_sprint_tasks(project_id, sprint_id)
		t = Template.instance("devtaskman", "list_sprint_tasks");
		
		puts t.display;	
	end
	
	
	def summary_panel_refresh()
		request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
		
		uri = params["uri", String, nil];

		req = PageHandler.current.subrequest(StringIO.new(), :GetRequest, uri, params, :Self, nil);
		if (req.reply.ok?)
			doc = REXML::Document.new("<div>" + req.reply.out.string + "</div>");
			puts REXML::XPath.first(doc, "//div[@class='summary_panel_table_div']").to_s;
		end
	end
	

	def sprint_selector(project_id=nil, sprint_id=nil)
		t = Template.instance("devtopia", "sprint_selector");

		t.sprints = Devtopia::Sprint.find(:all, :scan);
		t.selected_sprint = Devtopia::Sprint.find(:first, project_id, sprint_id);
		
		puts t.display;
	end


	def programmer_filter(programmer_id=nil)
		t = Template.instance("devtopia", "programmer_filter");

		current_programmer = Devtopia::Programmer.find(:first, session.user.userid);
		if (!programmer_id.nil?)
			current_programmer.filterid = programmer_id;
			current_programmer.store;
		end

		t.programmers = Devtopia::Programmer.find(:all, :scan);
		t.programmer = Devtopia::Programmer.find(:first, programmer_id || current_programmer.filterid);
		
		puts t.display;
	end


	def record_details(record_id, record_type)
		request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
		
		record_class = eval "#{record_type}";
		record_id_array = record_id.split("-").map { | id | id.to_i };
		if (record_id_array.last == 0)
			record = record_class.new;
			record_id_array.pop;
			record.parent_id = record_id_array * "-";
		else
			record = record_class.find(:first, record_id_array) || record_class.new;
		end

		template_name = "edit_#{record.table}";
		template = Template.instance("devtopia", template_name);
		template.record = record;
		template.programmer = Devtopia::Programmer.find(:first, session.user.userid);

		div_id = params["div_id", String, nil];
		if (div_id.nil?)
			puts template.display;
		else
			doc = REXML::Document.new("<div>" + template.display + "</div>");
			puts REXML::XPath.first(doc, "//div[@class='#{div_id}']").to_s;
		end
	end
	
	
	def record_save(record_id, record_type)
		$log.info("Saving ID: #{record_id}, TYPE: #{record_type}");
		record_id_array = record_id.split("-").map { | id | id.to_i };

		record_class = eval "#{record_type}";

		if (record_id_array.last == 0)
			record = record_class.new;
			record_id_array.pop;
			record.parent_id = record_id_array * "-";
		else
			record = record_class.find(:first, record_id_array) || record_class.new;
		end

		parent_id = params["parent_id", String, nil];
		if (!parent_id.nil?)
			record.parent_id = parent_id;
		end

		params.keys.each { | key |			
			string_value = params[key,String];
			column_name = key.gsub(/.*-/,"")
			
			if (!record.columns[column_name].nil?)
				actual_value = record.columns[column_name].parse_string(string_value);
				initial_value = record.send("#{column_name}");
				if (initial_value != actual_value)
					record.send("#{column_name}=", actual_value);
				end
			end
		};
		record.store();
		
		prefix = record_type.gsub(/.*::(.*)/, '\1').downcase;
		if (respond_to? "#{prefix}_save")
			send("#{prefix}_save", record, params);
		end
		
		record_details(record_id, record_type);
	end
	
	
	def task_save(record, params)
		programmers = params["programmer", TypeSafeHash, Hash.new];
		programmer_ids = programmers.map { | key | key.to_i };
		
		record.assign_programmers(programmer_ids);
		
		sprint_aggregate_id = params["sprint_aggregate_id", String, nil];
		if (!sprint_aggregate_id.nil?)
			sprint_id_array = sprint_aggregate_id.split("_");
		
			record.projectid = sprint_id_array[0];
			record.sprintid = sprint_id_array[1];
			record.store;
		end
	end
	

	def todo_save(record, params)
		sprint_aggregate_id = params["sprint_aggregate_id", String, nil];
		if (!sprint_aggregate_id.nil?)
			sprint_id_array = sprint_aggregate_id.split("_");
		
			record.projectid = sprint_id_array[0];
			record.sprintid = sprint_id_array[1];
			record.store;
		end
	end
	
	
	def sprint_save(record, params)
		start_day = params["start_day", Integer, 0];
		start_month = params["start_month", Integer, 0];
		start_year = params["start_year", Integer, 0];
		
		end_day = params["end_day", Integer, 0];
		end_month = params["end_month", Integer, 0];
		end_year = params["end_year", Integer, 0];
		
		if (start_day != 0 && start_month != 0 && start_year != 0)
			record.startdate = Time.local(start_year, start_month, start_day).to_i;
		end
		
		if (end_day != 0 && end_month != 0 && end_year != 0)
			record.enddate = Time.local(end_year, end_month, end_day).to_i;
		end
		
		record.store;
	end
	
		
	def related_object_editor
		t = Template.instance("devtopia", "related_object_editor");
		
		t.records = params.to_hash["records"];
		t.parent_record = params.to_hash["parent_record"];
		t.record_type = params.to_hash["record_type"];
		t.programmer = params.to_hash["programmer"];
		t.related_add_disable = params.to_hash["related_add_disable"];
		t.filter_by_programmer = params.to_hash["filter_by_programmer"];
		t.project_id = params.to_hash["project_id"];
		t.sprint_id = params.to_hash["sprint_id"];
		
		puts t.display;
	end
	
	
	def mark_todo_done(product_id, task_id, todo_id)
		$log.info "Prod: #{product_id} / Task: #{task_id} / Todo: #{todo_id}"
		request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
		
	 	todo = Devtopia::Todo.find(:first, product_id, task_id, todo_id);
		todo.complete!;
		
		puts html_todo(todo);
	end
	
	
	def mark_task_done(product_id, task_id)
		$log.info "Prod: #{product_id} / Task: #{task_id}"
		request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
		task = Devtopia::Task.find(:first, product_id, task_id);
		task.complete!;
		
		puts html_task(task);
	end
end