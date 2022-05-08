module Mods
	class PicModPageHandler < PageHandler
		declare_handlers("mods/quiz/pics"){
			area :Public
			access_level :LoggedIn
			
			page	:GetRequest, :Full, :view_pic_mod_quiz;
			page	:GetRequest, :Full, :view_pic_mod_quiz, input(Integer);
			
			page	:GetRequest, :Full, :view_pic_mod_quiz_finish, "finish";
		}
		
		def view_pic_mod_quiz(page_num = 0)
			request.reply.headers['X-width'] = 0;
			queue = Moderator::QueueBase.by_number(1);
			prefs = request.session.user.mod_prefs(queue);
			if (!queue || !prefs)
				raise PageError.new(403), "Access denied.";
			end
			
			if(page_num < 0)
				print "Bad page info";
				return;
			elsif(page_num > 20)
				print "Bad page info";
				return;
			elsif(page_num > 0 && page_num < 10)
				page_string = "0#{page_num}";
			else
				page_string = "#{page_num}";
			end
			
			if(page_num > 0)
				t = Template.instance("mods", "pic_mod_quiz_#{page_string}");
			else
				t = Template.instance("mods", "pic_mod_quiz");
			end
			
			if(t.nil?())
				print "Quiz page not found";
				return;
			end
			
			print t.display();
		end
		
		def view_pic_mod_quiz_finish()
			request.reply.headers['X-width'] = 0;
			queue = Moderator::QueueBase.by_number(1);
			prefs = request.session.user.mod_prefs(queue);
			if (!queue || !prefs)
				raise PageError.new(403), "Access denied.";
			end
			
			t = Template.instance("mods", "pic_mod_quiz_finish");
			
			print t.display();
		end
	end
end
