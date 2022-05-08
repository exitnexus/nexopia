require "singleton";
lib_require :Profile, "new_user_task";

module Profile
	class NewUserTaskMediator
		include Singleton
	
		attr_accessor :new_task_definitions;
	
		def initialize()
			temp = NewUserTask.find(:scan);
			
			if(temp.nil?())
				temp = [];
			end
			
			@new_task_definitions = temp;
		end
	
		def self.task_list()
			return self.instance.new_task_definitions;
		end
	end
end
