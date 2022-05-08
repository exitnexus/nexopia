class TaskLogSet
	attr_accessor(:user_name, :task_id, :task_name);
	
	def initialize()
	   @_subtask_list = Array.new();
	end
	
	def subtask_list
	   return @_subtask_list;
	end
end

class SubTaskLogSet
    attr_accessor(:subtask_id, :subtask_name, :log_entries);
    
    def initialize(in_date_range)
        @log_entries = in_date_range.collect {|date| MySubTaskLog.new(date)};
    end
    
    def current_estimate
        if @log_entries.last
            return @log_entries.last.estimate;
        else
            return 0;
        end
    end
    
    def incomplete
        if @log_entries.last
            $log.info("Incomplete is: #{@log_entries.last.date}");
        end
        
        if @log_entries.last && 
            @log_entries.last.estimate && 
            @log_entries.last.estimate > 0
            return true;
        end
        return nil;
    end
    
    def logbydate(date)
        for log in @log_entries
            #$log.info("The arg date is #{date} our stored date is #{log.date}")
            if log.date === date
                #$log.info("Returning Date");
                return log;
            end
        end
        return nil;
    end
end

class MySubTaskLog
    attr_accessor(:date, :estimate);
    
    def initialize(in_date)
        @date = in_date;
    end
    
    def format_date
        return @date.strftime("%b %d, %Y");
    end
end