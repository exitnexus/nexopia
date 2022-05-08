module Mockups
	class MockupPageHandler < PageHandler
		declare_handlers("mockups"){
			area :Public
			access_level :Any
			
			page	:GetRequest, :Full, :list_mockups;
			page	:GetRequest, :Full, :view_mockup, input(String)
		}
		
		def list_mockups()
			temp_dir = Dir.open("#{$site.config.site_base_dir}/mockups/templates/");
			temp_list = Array.new();
			temp_dir.each{|temp| temp_list << temp;}
			temp_dir.close();
			
			t = Template.instance("mockups", "list_mockups");
			t.list = temp_list - [".", "..", ".svn", "list_mockups.html"];
			t.list.each{|temp|
				temp.gsub!(".html", "");
			};
			print t.display();
		end
		
		def view_mockup(template_ref)
			request.reply.headers['X-width'] = 0;
			begin
				t = Template.instance("mockups", template_ref)
			rescue Exception => e
				print "<center><h1>Don't Panic!</h1>Something went dreadfully wrong!<br />It is probably because '#{template_ref}.html' doesn't exist. If not talk to somebody about this.</center>"
				e.html_dump()
				$log.info e.inspect
				return
			end
			
			print t.display
		end
	end
end