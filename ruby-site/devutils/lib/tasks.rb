lib_require :Core, "storable/storable", "url";

class Date
	def weekday?
		return wday != 0 && wday != 6;
	end
	alias workday? weekday?
end

module SprintDates
	def convert_from_stringtime(str)
		matches = /([0-9]{4})([0-9]{2})([0-9]{2})/.match(str);
		if (!matches)
			return nil;
		end
		return Date.civil(matches[1].to_i, matches[2].to_i, matches[3].to_i);
	end

	def convert_to_stringtime(time)
		return time.to_s.gsub(/-/, '');
	end
end

class Sprint < Storable
	set_db(:taskdb);
	set_table("sprint");
	init_storable();

	include SprintDates;

	def tasks()
		return Task.find(:conditions => ["projectid = ? AND sprintid = ?", projectid, id]);
	end

	def Sprint.get(projectid, sprintid)
		return find(:first, projectid, sprintid);
	end

	def people()
		return SprintPeople.find(:all, :conditions => ["projectid = ? AND sprintid = ?", projectid, id]);
	end

	def uri()
		return url/:project/projectid/:sprint/id;
	end

	def uri_info()
		return {name => uri};
	end

	def today()
		return Date.today();
	end

	# overloads timestamp behaviour on start and end
	def start_date
		return convert_from_stringtime(startdate);
	end
	def end_date
		return convert_from_stringtime(enddate);
	end

	def start_date=(time)
		self.startdate = convert_to_stringtime(time);
	end
	def end_date=(time)
		self.enddate = convert_to_stringtime(time);
	end

	# returns either the number of work days in the sprint or yields
	# them as MutableDate objects if a block is given.
	def work_days(before = end_date)
		count = 0;

		sprintend = end_date;
		logend = [before, sprintend].min;


		(start_date..logend).each {|date|
			if (date.weekday?)
				if (block_given?)
					yield(date, count);
				end
				count += 1;
			end
		}
		return count;
	end
end

class SprintPeople < Storable
	set_db(:taskdb);
	set_table("sprintpeople");
	init_storable();

	relation_singular :user, :userid, User;
end

class Task < Storable
	set_db(:taskdb);
	set_table("task");
	init_storable();

	relation_singular :owner, :ownerid, User;

	def Task.get(projectid, taskid)
		return find(:first, projectid, taskid);
	end

	def subtasks()
		return SubTask.find(:all, :conditions => ["projectid = ? AND taskid = ?", projectid, id]);
	end

	def uri()
		return url/:project/projectid/:task/id;
	end

	def uri_info()
		return {name => uri};
	end
end

class Project < Storable
	set_db(:taskdb);
	set_table("project");
	init_storable();

	def tasks()
		return Task.find(:all, id, 0);
	end
	def all_tasks()
		return Task.find(:all, id);
	end

	def task(taskid)
		return Task.get(id, taskid);
	end

	def people()
		return ProjectPeople.find(:all, :conditions => ["projectid = ?", id]);
	end

	def sprints()
		return Sprint.find(:all, :conditions => ["projectid = ?", id]);
	end

	def sprint(sprintid)
		return Sprint.get(id, sprintid);
	end

	def uri()
		return url/:project/id;
	end

	def uri_info()
		return {name => uri};
	end

	def Project.list()
		return find(:all);
	end
end

class ProjectPeople < Storable
	set_db(:taskdb);
	set_table("projectpeople");
	init_storable();

	relation_singular :user, :userid, User;
end

class SubTask < Storable
	set_db(:taskdb);
	set_table("subtask");
	init_storable();

	def logs()
		return SubTaskLog.find(:all, :conditions => ["projectid = ? AND taskid = ? AND subtaskid = ?", projectid, taskid, id]);
	end

	def task()
		return Task.get(projectid, taskid)
	end
end

class SubTaskLog < Storable
	set_db(:taskdb);
	set_table("subtasklog");
	init_storable();

	include SprintDates;
	extend SprintDates;

	def SubTaskLog.get(projectid, taskid, subtaskid, date)
		logitem = SubTaskLog.find(:first, projectid, taskid, subtaskid, convert_to_stringtime(date));
		if (!logitem)
			logitem = SubTaskLog.new;
			logitem.projectid = projectid;
			logitem.taskid = taskid;
			logitem.subtaskid = subtaskid;
			logitem.date_of = date;
		end
		return logitem;
	end

	def date_of()
		return convert_from_stringtime(date);
	end
	def date_of=(time)
		self.date = convert_to_stringtime(time);
	end
end
