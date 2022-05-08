require "devutils/lib/tasklogset"

class ProjectManager < PageHandler
	declare_handlers("projectv2") {
		area :Public
		access_level :Any
		page :GetRequest, :Full, :list; #list of all available projects
		page :GetRequest, :Full, :project, input(Integer); #project details for specified project id
		page :GetRequest, :Full, :sprint, input(Integer), "sprint", input(Integer); #details on an individual sprint
        page :GetRequest, :Full, :sprint_log, input(Integer), "sprint", input(Integer), "log", remain;
        page :GetRequest, :Full, :unassigned_tasks, input(Integer), "tasks";
        page :GetRequest, :Full, :tasks, input(Integer), "sprint", input(Integer), "tasks";
        
        handle :PostRequest, :add_project, "add";
        handle :PostRequest, :add_person, input(Integer), "add", "person";
        handle :PostRequest, :add_sprint, input(Integer), "add", "sprint";
        handle :PostRequest, :add_task, input(Integer), "add", "task";
        handle :PostRequest, :add_sprint_person, input(Integer), "sprint", input(Integer), "add", "person";
        handle :PostRequest, :add_log, input(Integer), "sprint", input(Integer), "add", "log";
	}

	def list()
		t = Template.instance("devutils", "project_main");	

		t.projects = Project.list();
		t.url = url;

		print t.display		
	end

	def project(project_id)
		project = Project.find(project_id).first;

		if(project)
            t = Template.instance("devutils", "project_details");
			t.project = project;
			t.sprints = project.sprints;
			t.people = project.people;
			
			print t.display;
		else
			site_redirect(url/:project);
		end
	end

	def sprint(project_id, sprint_id)
		#$log.info("Project #{project_id}");
		#$log.info("Sprint #{sprint_id}");
		
		project = Project.find(project_id).first;
		sprint = project && project.sprint(sprint_id);
		
		if(project && sprint)
			t = Template.instance("devutils", "sprint_details");
			
			t.uri = sprint.uri;
			t.project = project;
			t.sprint = sprint;
			t.people = sprint.people;
			t.tasks = sprint.tasks;
			
			print t.display;
		end
	end
	
	def add_project()
        name = params['name', String];
        description = params['description', String];
        
        if(name && description)
            project = Project.new();
            project.name = name;
            project.description = description;
            
            project.store();
            
            site_redirect(project.uri);
        else
            site_redirect("/project/");
        end
	end
	
	def add_person(project_id)
        project = Project.find(project_id).first;
        
        name = params['user_name', String];
        commitment = params['commitment', Integer, 100];
        commitment = [commitment, 100].min;
        commitment = [commitment, 0].max;
        
        if(project && name)
            user = name && User.get_by_name(name);
            
            if(user)
                person = ProjectPeople.new();
                person.projectid = project.id;
                person.userid = user.userid;
                person.commitment = commitment;
                
                person.store();
            end
        end
        
        site_redirect(project.uri);   
	end
	
	def add_sprint(project_id)
        project = Project.find(project_id).first;
        
        name = params['name', String];
        description = params['description', String];
        
        if(project && name && description)
            sprint = Sprint.new();
            sprint.projectid = project.id;
            sprint.name = name;
            sprint.description = description;
            sprint.start_date = sprint.today;
            sprint.end_date = sprint.today + 30;
            
            sprint.store();
            
            site_redirect(sprint_uri);
        end
        site_redirect(project_uri);
	end
	
	def add_task(project_id)
        project = Project.find(project_id).first;
        sprint = nil;
        task = nil;
        
        name = params['name', String];
        description = params['description', String];
        estimate = params['estimate', Integer];
        owner_id = params['owner_id', Integer];
        sprint_id = params['sprint_id', Integer];
        
        if(project && name && description && estimate && owner_id)
            task = Task.new();
            task.projectid = project.id;
            task.name = name;
            task.description = description;
            task.estimate = estimate;
            
            if(sprint_id != 0)
                sprint = project.sprint(sprint_id);
            end
            
            if(sprint)
                task.sprintid = sprint.id;
            end
            
            owner = User.find(:first, owner_id);
            
            if(owner)
                task.ownerid = owner.userid;
                
                task.store();
            else
                task = nil;
            end
        end
        
        if(task)
            site_redirect(task.uri);
        elsif(sprint)
            site_redirect(sprint.uri);
        elsif(project)
            site_redirect(project.uri)
        else
            site_redirect("/project/");
        end
	end
	
	def add_sprint_person(project_id, sprint_id)
	   sprint = Sprint.get(project_id, sprint_id);
	   
	   user_id = params['user_id', Integer];
	   commitment = params['commitment', Integer, 100];
	   commitment = [commitment, 100].min;
	   commitment = [commitment, 0].max;
	   
	   if(sprint && user_id)
	       user = User.find(user_id).first;
	       
	       if(user)
	           person = SprintPeople.new();
	           person.projectid = project_id;
	           person.sprintid = sprint.id;
	           person.userid = user.userid;
	           person.commitment = commitment;
	           
	           person.store();
	       end
	   end
	   
	   if(sprint)
	       site_redirect(sprint.uri);
	   else
	       site_redirect("/project/");
	   end
	end
	
	def sprint_log(project_id, sprint_id, remain)
	   project = Project.find(:first, project_id);
	   sprint = project && project.sprint(sprint_id);
	   
	   if(project && sprint)
	       t = Template.instance("devutils", "sprint_log");
	       logs = sprint.tasks.collect{|task|
	                                   [task, task.subtasks.collect {|subtask|
	                                       [subtask, subtask.logs.collect {|log| log.date_of
	                                       }]
	                                   }]
	                                  };
            logs.flatten!();
            
            if logs.include?(sprint.today)
                today = sprint.today;
                $log.info("Today is: #{today}");
            else
                today = Date.today() -1;
            end
            
            if(remain.include?("all"))
                sprint_start = sprint.start_date;
            else
                sprint_start = today - 12;
            end
            
            sprint_end = sprint.end_date;
            log_end = [today, sprint_end].min;
            #$log.info("Before Dates")
            dates = (sprint_start..log_end).find_all {|date| date.workday?};
            #$log.info("The length of the dates array: #{dates.length}");
            
            #t.logs = logs;
            t.dates = dates;
            t.sprint = sprint;
            t.project = project;
            
            tasklog_list = Array.new();
            
            for task in sprint.tasks
                tls = TaskLogSet.new();
                tls.task_id = task.id;
                tls.task_name = task.name;
                tls.user_name = task.owner.username;
                for subtask in task.subtasks
                    sbls = SubTaskLogSet.new(dates);
                    sbls.subtask_id = subtask.id;
                    sbls.subtask_name = subtask.name;
                    for log in subtask.logs
                        l = sbls.logbydate(log.date_of) 
                        if l
                            #$log.info("Estimate of #{log.estimate}");
                            l.estimate = log.estimate;
                        end
                    end
                    #$log.info("The length of the log entry array is: #{sbls.log_entries.length}");
                    #if sbls.log_entries.last
                    #    $log.info("The last was not nil")
                    #end
                    $log.info("The complete is: #{sbls.incomplete}")
                    tls.subtask_list << sbls;
                end
                tasklog_list << tls;
            end
            #dates.collect! {|date| date.strftime('%d/%m/%Y')};
            dates.collect! {|date| date.strftime('%b %d, %Y')};
            t.tasklog_list = tasklog_list;
                    
            print t.display;
            
	   end
	end
	
	def tasks(project_id, sprint_id=0)
	   project = Project.find(project_id).first;
	   
	   if project && sprint_id != 0
	       sprint = project.sprint(sprint_id);
	   end
	   
	   if sprint
	       task_source = sprint;
	   else
	       task_source = project;
	       sprint = Sprint.new();
	       sprint.id = 0;
	       sprint.description = "Unassigned Tasks";
	   end
	   
	   if project
    	   t = Template.instance("devutils", "task_list");
    	   
    	   t.people = task_source.people;
    	   t.tasks = task_source.tasks;
    	   t.project = project;
    	   t.sprint = sprint;
    	   
    	   print t.display;
	   else
	       site_redirect("/project/");
	   end
	   
	end
	
	def unassigned_tasks(project_id)
	   tasks(project_id);
	end
	
	def add_log(projectid, sprintid)
		project = Project.find(projectid).first;
		sprint = project && project.sprint(sprintid);

		logitems = {};
		params.each_match(/left([0-9]+):([0-9]+)/, Integer) {|match, val|
			logitems[[match[1].to_i, match[2].to_i]] = val;
		}

		if (project && sprint && logitems.length > 0)
			logitems.each {|id, estimate|
				taskid, subtaskid = id;

				logitem = SubTaskLog.get(project.id, taskid, subtaskid, Date.today);
				logitem.estimate = estimate;

				logitem.store();
			}
		end
		if (sprint)
			site_redirect("#{sprint.uri}/burndown");
		elsif (project)
			site_redirect("#{project.uri}");
		else
			site_redirect("/project/");
		end
	end
end
