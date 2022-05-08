lib_require :Devutils, "tasks";
lib_require :Core, "collect_hash", "url";

class TaskManager < PageHandler
	declare_handlers("project") {
		page :GetRequest, :Full, :list;
		page :GetRequest, :Full, :project, input(Integer);
		handle :GetRequest, :unassigned_tasks, input(Integer), "tasks";
		page :GetRequest, :Full, :sprint, input(Integer), "sprint", input(Integer);
		handle :GetRequest, :sprint_tasks, input(Integer), "sprint", input(Integer), "tasks";
		page :GetRequest, :Full, :task, input(Integer), "task", input(Integer);

		page :GetRequest, :Full, :sprint_log, input(Integer), "sprint", input(Integer), "log", remain;
		page :GetRequest, :Full, :change_log, input(Integer), "sprint", input(Integer), "changelog", remain;
		page :GetRequest, :Full, :sprint_burndown, input(Integer), "sprint", input(Integer), "burndown";
		handle :GetRequest, :sprint_burndown_chart, input(Integer), "sprint", input(Integer), "burndown.png";
		page :GetRequest, :Full, :summary, input(Integer), "summary"

		handle :PostRequest, :add, "add";
		handle :PostRequest, :add_person, input(Integer), "add", "person";
		handle :PostRequest, :add_sprint, input(Integer), "add", "sprint";
		handle :PostRequest, :add_task, input(Integer), "add", "task";

		handle :PostRequest, :add_sprint_person, input(Integer), "sprint", input(Integer), "add", "person";
		handle :PostRequest, :add_log, input(Integer), "sprint", input(Integer), "add", "log";
		handle :PostRequest, :add_subtask, input(Integer), 'task', input(Integer), 'add', 'subtask';
	}

	def nav_bar(mab, *navitems)
		navitems = [{"Projects" => "/project/"}] + navitems;
		navitems.collect! {|item|
			x = '';
			item.each {|title, url|
				x = mab.capture { a(title, :href=>url) };
			}
			x;
		}
		mab.div { navitems.join(" > "); }
	end

	def list()
		require "markaby";
		mab = Markaby::Builder.new;
		taskman = self;
		puts mab.div.bgwhite {
			taskman.nav_bar(mab);
			h1 "Projects";

			ul {
				Project.list().each {|project|
					li { a(project.name, :href=>project.uri) };
				}
			}

			h2 "New Project";
			form(:method=>:post, :action=>url/:project/:add) {
				table {
					tr {
						td "Name:";
						td { input(:type=>:text, :name=>:name); }
					}
					tr {
						td "Description:";
						td { textarea(" ", :name=>:description) };
					}
				}
				input(:type=>:submit);
			}
		}
	end

	def add()
		name = params['name', String];
		description = params['description', String];

		if (name && description)
			project = Project.new();
			project.name = name;
			project.description = description;

			project.store();

			site_redirect(project.uri);
		else
			site_redirect("/project/");
		end
	end

	def summary(projectid)
		project = Project.find(projectid).first;
		if (project)
			require "markaby";
			mab = Markaby::Builder.new;
			taskman = self;
			puts mab.div.bgwhite {
				h1 project.name;
				p project.description;

				h2 "People";
				ul {
					people = project.people;
					people.each {|person|
						if (!person.user.nil?)
							li "#{person.user.username}"
						end
					}
				}

				sprints = project.sprints
				tasks = project.all_tasks

				# will contain a mapping of: {sprint=>{task=>[subtask,...],...},...}
				# which identifies the hierarchy of subtasks that had log
				# activity in that sprint (even if it was moved to another sprint)
				sprint_task_map = {}
				tasks.each {|task|
					task.subtasks.each {|subtask|
						subtask.logs.each {|log|
							sprints.each {|sprint|
								if (log.date_of >= sprint.start_date && log.date_of <= sprint.end_date)
									sprint_task_map[sprint] ||= {}
									sprint_task_map[sprint][task] ||= []
									sprint_task_map[sprint][task].push(subtask)
									sprint_task_map[sprint][task].uniq! # clear duplicates
								end
							}
						}
					}
				}

				h2 "Sprints";
				sprints.each {|sprint|
					h3 { a(sprint.name, :href=>sprint.uri); };
					p "(#{sprint.start_date} to #{sprint.end_date})";
					if (sprint_task_map[sprint])
						sprint_task_map[sprint].each {|task, subtasks|
							h4 task.name
							ul {
								li {
									strong "Owner: "
									if (task.owner.nil?)
										span "Deleted..."
									else
										span task.owner.username
									end
								}
								li {
									strong "Description: "
									span task.description
								}
								ul {
									subtasks.each {|subtask|
										li {
											div { strong subtask.name }
											div subtask.description
										}
									}
								}
							}
						}
					end
				}
			}

		end
	end

	def project(projectid)
		project = Project.find(projectid).first;
		if (project)
			require "markaby";
			mab = Markaby::Builder.new;
			taskman = self;
			puts mab.table.bgwhite(:width=>"100%") { tr { td {
				taskman.nav_bar(mab, project.uri_info);
				h1 project.name;
				p project.description;

				h2 "People";
				ul {
					people = project.people;
					people.each {|person|
						if (!person.user.nil?)
							li "#{person.user.username}: #{person.commitment}% commitment";
						end
					}
				}

				h3 "Add Person";
				form(:method=>:post, :action=>project.uri/:add/:person) {
					table {
						tr {
							td "User Name:";
							td { input(:type=>:text, :name=>:username); }
						}
						tr {
							td "Commitment:";
							td { input(:type=>:text, :name=>:commitment, :value=>100, :size=>3) + "%"; }
						}
					}
					input(:type=>:submit);
				}

				h2 "Sprints";
				ul {
					project.sprints.each {|sprint|
						li {
							b { a(sprint.name, :href=>sprint.uri); };
							span "(#{sprint.start_date} to #{sprint.end_date})";
						}
					}
				}

				h3 "Add Sprint";
				form(:method=>:post, :action=>project.uri/:add/:sprint) {
					table {
						tr {
							td "Name:";
							td { input(:type=>:text, :name=>:name); }
						}
						tr {
							td "Description:";
							td { textarea(" ", :name=>:description) }
						}
					}
					input(:type=>:submit);
				}

				req = taskman.subrequest(StringIO.new, :GetRequest, project.uri/:tasks);
				if (req.reply.ok?)
					div { req.reply.out.string };
				end

			}}}
		else
			site_redirect(url/:project);
		end
	end

	def tasks(projectid, sprintid = 0)
		project = Project.find(projectid).first;

		if (sprintid != 0)
			sprint = project && project.sprint(sprintid);
		end

		tasksource = sprint || project;

		if (tasksource)
			require "markaby";
			mab = Markaby::Builder.new;
			nb = proc {|*args| nav_bar(*args) };

			puts mab.div.bgwhite {
				h2 "Tasks";

				tasks = tasksource.tasks;

				ul {
					tasks.each {|task|
						li { a(task.name, :href=>task.uri); }
					}
				}

				peopleoptions = capture {
					option "Select User";
					tasksource.people.each {|person|
						option("#{person.user.username}: #{person.commitment}%", :value=>person.userid);
					}
				}

				h3 "New Task";
				form(:method=>:post, :action=>project.uri/:add/:task) {
					input(:type=>:hidden, :name=>:sprintid, :value=>sprintid);
					table {
						tr {
							td "Name:";
							td { input(:type=>:text, :name=>:name); }
						}
						tr {
							td "Owner:";
							td { select(:name=>:ownerid) { peopleoptions }; }
						}
						tr {
							td "Estimate:";
							td { input(:type=>:text, :name=>:estimate); }
						}
						tr {
							td "Description:";
							td { textarea(" ", :name=>:description) };
						}
					}
					input(:type=>:submit);
				}
			}
		end
	end

	def unassigned_tasks(projectid)
		tasks(projectid);
	end

	def sprint_tasks(projectid, sprintid)
		tasks(projectid, sprintid);
	end

	def add_person(projectid)
		project = Project.find(projectid).first;

		name = params['username', String];
		commitment = params['commitment', Integer, 100];
		commitment = [commitment, 100].min;
		commitment = [commitment, 0].max;

		if (project && name)
			user = name && User.get_by_name(name);

			if (user)
				person = ProjectPeople.new();
				person.projectid = project.id;
				person.userid = user.userid;
				person.commitment = commitment;

				person.store();
			end
		end
		site_redirect(project.uri);
	end

	def add_sprint_person(projectid, sprintid)
		sprint = Sprint.get(projectid, sprintid);

		userid = params['userid', Integer];
		commitment = params['commitment', Integer, 100];
		commitment = [commitment, 100].min;
		commitment = [commitment, 0].max;

		if (sprint && userid)
			user = User.find(userid).first;

			if (user)
				person = SprintPeople.new();
				person.projectid = projectid;
				person.sprintid = sprint.id;
				person.userid = user.userid;
				person.commitment = commitment;

				person.store();
			end
		end
		if (sprint)
			site_redirect(sprint.uri);
		else
			site_redirect("/project/");
		end
	end

	def add_sprint(projectid)
		project = Project.find(projectid).first;

		name = params['name', String];
		description = params['description', String];

		if (project && name && description)
			sprint = Sprint.new();
			sprint.projectid = project.id;
			sprint.name = name;
			sprint.description = description;
			sprint.start_date = sprint.today;
			sprint.end_date = sprint.today + 30;

			sprint.store();

			site_redirect(sprint.uri);
		end
		site_redirect(project.uri);
	end

	def add_task(projectid)
		project = Project.find(projectid).first;
		sprint = nil;
		task = nil;

		name = params['name', String];
		description = params['description', String];
		estimate = params['estimate', Integer];
		ownerid = params['ownerid', Integer];
		sprintid = params['sprintid', Integer];

		if (project && name && description && estimate && ownerid)
			task = Task.new;
			task.projectid = project.id;
			task.name = name;
			task.description = description;
			task.estimate = estimate;

			if (sprintid != 0)
				sprint = project.sprint(sprintid);
			end
			if (sprint)
				task.sprintid = sprint.id;
			end

			owner = User.find(:first, ownerid);
			if (owner)
				task.ownerid = owner.userid;

				task.store();
			else
				task = nil;
			end
		end

		if (task)
			site_redirect(task.uri);
		elsif (sprint)
			site_redirect(sprint.uri);
		elsif (project)
			site_redirect(project.uri);
		else
			site_redirect("/project/");
		end
	end

	def sprint(projectid, sprintid)
		project = Project.find(projectid).first;
		sprint = project && project.sprint(sprintid);

		if (project && sprint)
			require "markaby";
			mab = Markaby::Builder.new;
			nb = proc {|*args| nav_bar(*args) };

			puts(%Q{<div class="bgwhite">});
			nav_bar(mab, project.uri_info, sprint.uri_info);
			puts mab;
			puts("<h1>#{project.name}: #{sprint.name}</h1>");
			puts("<p>#{sprint.description}</p>");

			puts(%Q{<a href="#{sprint.uri}/log">View Log</a>});

			req = subrequest(StringIO.new, :GetRequest, "#{sprint.uri}/tasks");
			if (req.reply.ok?)
				puts req.reply.out.string;
			end

			puts("<h2>People</h2><ul>");
			sprintpeople = sprint.people;
			projectpeople = project.people;
			sprintpeople.each {|person|
				puts("<li>#{person.user.username}: #{person.commitment}% commitment</li>");
			}
			puts("</ul>");

			peopleoptions = []
			projectpeople.each {|person|
				peopleoptions << %Q{<option value="#{person.userid}">#{person.user.username}: #{person.commitment}%</option>};
			}

			puts("<h3>Add Person</h3>");
			puts(%Q{
				<form method="post" action="#{sprint.uri}/add/person">
					<table>
						<tr><td>User:</td><td><select name="userid"><option>Select User</option>#{peopleoptions.join}</select></td></tr>
						<tr><td>Commitment:</td><td><input type="text" name="commitment" value="100" size="3" />%</td></tr>
					</table>
					<input type="submit" />
				</form></div>
			});

		else
			if (project)
				site_redirect(project.uri);
			else
				site_redirect("/project/");
			end
		end
	end

	def change_log(projectid, sprintid, remain)
		project = Project.find(:first, projectid);
		sprint = project && project.sprint(sprintid);

		if (project && sprint)
			require "markaby";
			mab = Markaby::Builder.new;
			nb = proc {|*args| nav_bar(*args) };

			puts(%Q{<div class="bgwhite">});
			all = remain.include?('all');
			nav_bar(mab, project.uri_info, sprint.uri_info);
			puts(mab);

			puts("<table class=\"bgwhite\" width=\"100%\" align=\"left\"> <tr> <td>");
			puts("<h1>#{project.name}: #{sprint.name}</h1>");
			puts("<p>#{sprint.description}</p>");

			puts(%Q{<a href="#{sprint.uri}/burndown">View Burndown</a><br/>});
			puts(%Q{<a href="#{sprint.uri}/log">Show only last 5 days</a>});
			puts(%Q{<a href="#{sprint.uri}/log/all">Show all log entries</a>});


			logs = sprint.tasks.collect {|task|
				[task, task.subtasks.collect {|subtask|
					[subtask, subtask.logs.collect_hash {|log|
						[log.date_of.to_s, log]
					}]
				}]
			};

			today = sprint.today;
			sprintstart = sprint.start_date;
			sprintend = sprint.end_date;
			logend = [today, sprintend].min;
			week_ago = logend - 7;
			if (week_ago < sprintstart)
				week_ago = sprintstart
			end

			puts(%Q{<form method="post" action="#{sprint.uri}/add/log">});

			puts(%Q{<table border="1"><thead><tr><th>Task</th><th>#{week_ago}</th><th>Last Record</th>});

			puts(%Q{</tr></thead><tbody>});
			logs.each {|task, subtasks|
				puts(%Q{<tr><th><a href="#{task.uri}">#{task.name}</a> (#{task.owner.username})</th><td style="margin-left: 2em;"></td><td style="margin-left: 2em;"></td></tr>});
				subtasks.each {|subtask, logitems|
					show = false;

					logend = [today, sprintend].min;
					if (!logitems.key?(logend.to_s))
						last_record = today - 1;
						while (last_record > week_ago)
							logend = last_record;
							last_record -= 1;
						end
					end

					if (logitems.key?(logend.to_s) != logitems.key?(week_ago.to_s))
						show = true;
					end

					if logitems.key?(logend.to_s) and logitems.key?(week_ago.to_s) and (logitems[logend.to_s].estimate != logitems[week_ago.to_s].estimate)
						show = true;
					end

					if (show)
						row = "";
						row << %Q{<tr><td style="margin-left: 2em;">#{subtask.name}</td>};
						left = "";

						if (logitems[(logend).to_s])
							current = logitems[(logend).to_s].estimate;
						else
							current = "~"
						end

						if (logitems[week_ago.to_s])
							previous = logitems[week_ago.to_s].estimate;
						else
							previous = "~"
						end

						row << (%Q{<td style="width: 24px;" align="center">#{previous}</td>});
						row << (%Q{<td style="width: 24px;" align="center">#{current}</td>});
						row << (%Q{</tr>});
						puts(row);
					end
				}
			}

			puts(%Q{</tbody></table>});
			puts(%Q{<input type="submit" /></form>});

			puts("</td> </tr> </table></div>");
		end
	end

	def sprint_log(projectid, sprintid, remain)
		project = Project.find(:first, projectid);
		sprint = project && project.sprint(sprintid);

		if (project && sprint)
			require "markaby";
			mab = Markaby::Builder.new;
			nb = proc {|*args| nav_bar(*args) };

			puts(%Q{<div class="bgwhite">});
			all = remain.include?('all');
			nav_bar(mab, project.uri_info, sprint.uri_info);
			puts(mab);

			puts("<table class=\"bgwhite\" width=\"100%\" align=\"left\"> <tr> <td>");
			puts("<h1>#{project.name}: #{sprint.name}</h1>");
			puts("<p>#{sprint.description}</p>");

			puts(%Q{<a href="#{sprint.uri}/burndown">View Burndown</a><br/>});
			if (all)
				puts(%Q{<a href="#{sprint.uri}/log">Show only last 5 days</a>});
			else
				puts(%Q{<a href="#{sprint.uri}/log/all">Show all log entries</a>});
			end
			puts(%Q{<a href="#{sprint.uri}/changelog">Show only changes in the last week</a>});


			logs = sprint.tasks.collect {|task|
				[task, task.subtasks.collect {|subtask|
					[subtask, subtask.logs.collect_hash {|log|
						[log.date_of.to_s, log]
					}]
				}]
			};

			today = sprint.today;
			sprintstart = sprint.start_date;
			sprintend = sprint.end_date;
			logend = [today, sprintend].min;

			puts(%Q{<form method="post" action="#{sprint.uri}/add/log">});

			puts(%Q{<table border="1"><thead><tr><th>Task</th>});
			(sprintstart..logend).each {|date|
				if (all || date >= today - 6)
					if (date == today)
						puts("<th>Today</th>");
					elsif (date.workday?)
						puts(%Q{<th>#{date.strftime('%d/%m/%Y')}</th>});
					end
				end
			}
			puts(%Q{</tr></thead><tbody>});
			logs.each {|task, subtasks|
				puts(%Q{<tr><th><a href="#{task.uri}">#{task.name}</a> (#{task.owner.username}, #{task.estimate} days)</th></tr>});
				subtasks.each {|subtask, logitems|
					row = "";
					row << %Q{<tr><td style="margin-left: 2em;">#{subtask.name}</td>};
					left = "";
					(sprintstart..logend).each {|date|
						if (logitems.key?(date.to_s))
							left = logitems[date.to_s].estimate;
						end
						if (all || date >= today - 6)
							if (date == today)
								row << (%Q{<td align="center"><input type="text" name="left#{task.id}:#{subtask.id}" value="#{left}" size="2"/></td>});
							elsif (date.workday?)
								row << (%Q{<td align="center">#{left}</td>});
							end
						end
					}
					row << (%Q{</tr>});
					if (all || left != 0)
						puts(row);
					end
				}
			}

			puts(%Q{</tbody></table>});
			puts(%Q{<input type="submit" /></form>});

			puts("</td> </tr> </table></div>");
		end
	end

	def sprint_burndown(projectid, sprintid)
		project = Project.find(projectid).first;
		sprint = project && project.sprint(sprintid);

		if (project && sprint)
			puts(%Q{<div class="bgwhite">});
			puts(%Q{<img src="#{sprint.uri}/burndown.png" />});
			puts(%Q{</div>});
		end
	end

	def sprint_burndown_chart(projectid, sprintid)
		require "gruff";

		project = Project.find(projectid).first;
		sprint = project && project.sprint(sprintid);

		if (project && sprint)
			workdays = {};
			num_workdays = sprint.work_days {|day, i|
				if (i % 5 == 0)
					workdays[i] = day.to_s;
				end
			}

			ideal = [];
			peoplecount = 0;
			sprint.people.each {|person|
				peoplecount += (person.commitment * 0.01);
			}

			(0..num_workdays).each {|i|
				ideal.unshift(peoplecount * i);
			}

			total_days = {};
			sprint.tasks.each {|task|
				task.subtasks.each {|subtask|
					subtask.logs.each {|log|
						if (total_days[log.date_of.to_s].nil?)
							total_days[log.date_of.to_s] = {};
						end
						total_days[log.date_of.to_s][[log.projectid,log.taskid,log.subtaskid]] = log.estimate;
					}
				}
			}

			today = sprint.today;
			total_days_array = [];
			last = nil;
			lastday = Hash.new(0);
			sprint.work_days(today) {|day, i|
				last = 0;
				if (total_days[day.to_s])
					total_days[day.to_s].each {|id, day_total|
						lastday[id] = day_total;
					}
				end

				lastday.each {|id, day_total|
					last += day_total;
				}

				total_days_array[i] = last;
			}

			# inject the initial estimates for the first entries of total_days_array that are 0.
			found_non_zero = false;
			total_days_array = total_days_array.collect {|item|
				if (item == 0 && !found_non_zero)
					peoplecount * num_workdays;
				else
					item;
				end
			}

			projection = [];
			sprint.work_days {|day, i|
				projection[i] = nil;
				if (day >= today)
					if (last >= 0)
						projection[i] = last;
					else
						break;
					end
					last -= peoplecount;
				end
			}

			g = Gruff::Line.new
			g.theme = {
				:background_image => "#{$site.config.static_root}/site_data/images/scrum_burndown.jpg",
				:colors => ['lightskyblue', 'navyblue', 'deepskyblue'],
				:marker_color => 'white'
			}
			g.title = "Sprint Burndown";
			g.data("Ideal", ideal);
			g.data("Reality", total_days_array);
			g.data("Projection", projection);
			g.labels = workdays;
			g.minimum_value = 0;
			g.font = "/usr/share/fonts/TTF/Vera.ttf"; # hard code bad

			reply.headers['Content-Type'] = PageRequest::MimeType::PNG;
			print(g.to_blob());
		end


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


	def task(projectid, taskid)
		project = Project.find(projectid).first;
		task = project && project.task(taskid);

		if (project && task)
			require "markaby";
			mab = Markaby::Builder.new;
			taskman = self;

			puts mab.div.bgwhite {
				sprint = nil;
				if (task.sprintid != 0)
					sprint = project.sprint(task.sprintid);
				end

				if (sprint)
					taskman.nav_bar(mab, project.uri_info, sprint.uri_info, task.uri_info);
				else
					taskman.nav_bar(mab, project.uri_info, task.uri_info);
				end

				h1 "#{project.name}: #{task.name}";
				p(task.description, :style=>"white-space: -moz-pre-wrap;");

				h2 "Sub Tasks";
				ul {
					task.subtasks.each {|subtask|
						li {
							b subtask.name;
							div subtask.description;
						}
					}
				}

				h3 "New Sub Task";
				form(:method=>:post, :action=>task.uri/:add/:subtask) {
					input(:type=>:hidden, :name=>:projectid, :value=>projectid);
					input(:type=>:hidden, :name=>:taskid, :value=>taskid);
					table {
						tr {
							td "Name:";
							td { input(:type=>:text, :name=>:name) }
						}
						tr {
							td "Initial Estimate:";
							td { input(:type=>:text, :name=>:estimate) }
						}
						tr {
							td "Description:";
							td { textarea(" ", :name=>:description) };
						}
					}
					input(:type=>:submit);
				}
			}
		else
			if (project)
				site_redirect(project.uri);
			else
				site_redirect("/project/");
			end
		end
	end

	def add_subtask(projectid, taskid)
		project = Project.find(projectid).first;
		task = project && project.task(taskid);

		name = params['name', String];
		description = params['description', String];
		estimate = params['estimate', Integer];

		if (project && task && name && description)
			subtask = SubTask.new;
			subtask.projectid = project.id;
			subtask.taskid = task.id;
			subtask.name = name;
			subtask.description = description;

			subtask.store();

			if (estimate)
				subtasklog = SubTaskLog.new;
				subtasklog.projectid = project.id;
				subtasklog.taskid = task.id;
				subtasklog.subtaskid = subtask.id;
				subtasklog.date_of = Date.today;
				subtasklog.estimate = estimate;

				subtasklog.store();
			end
		end

		if (task)
			site_redirect(task.uri);
		elsif (project)
			site_redirect(project.uri);
		else
			site_redirect("/project/");
		end
	end
end
