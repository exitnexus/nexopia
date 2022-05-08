lib_require :Profile, "new_user_task_mediator";

module Profile
	class NewUserTaskBlockPageHandler < PageHandler
		declare_handlers("new/tasks"){
			area :User
			access_level :IsUser
			
			handle	:GetRequest, :view_task_block;
			handle	:PostRequest, :close_task_block, "close";
		};
		
		def view_task_block()
			if(request.session.user.user_task_list.empty?())
				return;
			end
			
			t = Template.instance("profile", "new_user_header_block");
			
			display_list = [];
			task_list = NewUserTaskMediator.task_list();
			
			user_task_list = request.session.user.user_task_list;
			
			task_list.each{|task|
				if(user_task_list.include?(task))
					display_list << [task, true];
				else
					display_list << [task, false];
				end
			};
			
			t.display_user = request.user;
			t.display_list = display_list;
			print t.display();
		end
		
		def close_task_block()
			request.session.user.user_task_list.each{|task|
				task.delete();
			};
		end
	end
end