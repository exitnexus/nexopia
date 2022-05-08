lib_require :Profile, "profile_block_query_mediator", "profile_block_visibility";

class NexoSkinPage < PageHandler
	declare_handlers("Nexoskel") {
		area :Skeleton
		handle :GetRequest, :full_page, "skin", "Full", input(String), remain
		handle :GetRequest, :full_page, "skin", "Php", input(String), remain
		handle :GetRequest, :blank, "skin", "blank"

		handle :GetRequest, :php_index, "index"
		
		handle :GetRequest, :user_header, "user", "header", "users", input(String), remain;

		access_level :LoggedIn;
		handle :GetRequest, :mynex_menu, "skin", "menu", "Self"

	}

	# this handler should be removed when RAP can take over.
	declare_handlers("/") {
		area :Public
# 		handle :PostRequest, :rubytemplate_php, "rubytemplate.php"
	}

	UserMenuItem = Struct.new(:UserMenuItem, :title, :link, :order, :active, :styles);

	def blank
	puts %q|
		<html>
		   <script language="JavaScript">
		            function pageLoaded() {
		               window.parent.dhtmlHistory.iframeLoaded(window.location);
		            }
		   </script>
		   <body onload="pageLoaded()">
		      <h1>blank.html - Needed for Internet Explorer's hidden IFrame</h1>
		   </body>
		</html>
	|;
		puts "";
	end

    def NexoSkinPage.styles
	   return { "Blue" => "newsite",
	            "Gray" => "newsite_gray" }
    end

	
	def full_page(area, real_path)
		out = StringIO.new();
		req = subrequest(out, request.method, (url/real_path).to_s + ":Body", nil, area.to_sym);
		
		x_headers = {};
		req.reply.headers.each{|key,val|
			if (key =~ /^X-/)
				x_headers[key] ||= [];
				x_headers[key] << val;
			else
				reply.headers[key] = val;
			end
		}
		$log.object x_headers, :critical

		#template_args["X-modules"] = PageHandler.modules.join("/")
		#template_args["X-skeleton"] = PageHandler.pagehandler_module(self.class)

		#template_out = StringIO.new();
		#template_req = subrequest(template_out, :PostRequest, "/rubytemplate.php", template_args, :Public);

		#templ = template_out.string;
		#templ.sub!("<!--RubyReplaceThis-->", out.string);
		#puts templ
		
		puts RAPminiHandler::RAP_page(out.string, x_headers)
	end

	def mynex_menu()
		t = Template::instance("core", "mynex");
		puts t.display();
	end
	
	def php_index
		rewrite(:GetRequest, "/index.php:Page", nil, :Public)
	end
	
	def user_header(user_name, remain)
		menu_block_list = Profile::ProfileBlockQueryMediator.instance.menu_blocks();
		
		user_obj = User.get_by_name(user_name);
		
		owner_view = false;
		if(user_obj == request.session.user)
			owner_view = true;
		end
		
		current_section = remain[0];
		if(current_section.nil?())
			current_section = "";
		end
		
		include_header = false;
		for block in menu_block_list
			if(block.pagehandler_section_list.include?(current_section))
				include_header = true;
				break;
			end
		end
		
		if(include_header)
			block_list = Array.new();
		
			for block in menu_block_list
				temp = UserMenuItem.new();
				temp.title = block.page_url[0];
				temp.link = url/:users/user_name + block.page_url[1];
				temp.order = block.page_url[2];
				temp.styles = Array.new();
				
				if(owner_view)
					temp.active = true;
				elsif(!owner_view && block.page_per_block)
					col_name = block.module_name.downcase + "menuaccess";
					
					if(Profile::ProfileBlockVisibility.visible?(user_obj.send(col_name.to_sym()), user_obj, request.session.user))
						temp.active = true;
					else
						temp.active = false;
					end
				else
					temp.active = true;
				end
	
				if(block.pagehandler_section_list.include?(current_section))
					temp.styles << "selected";
					selected_block = block;
					mod_name = SiteModuleBase.directory_name(block.module_name);
				end
				
				if(block == menu_block_list.last)
					temp.styles << "last_column";
				end
				
				block_list << temp;
			end
			
			t = Template.instance("nexoskel", "user_area_header");
			
			t.menu_items = block_list.sort{|x,y| x.order <=> y.order};
			t.mod_name = mod_name;
			t.owner_view = owner_view;
	
			if(owner_view && !selected_block.nil?())
				if(selected_block.header_edit_control.nil?())
					selected_block.header_edit_control = File.exist?("#{$site.config.site_base_dir}/#{mod_name}/templates/user_area_header_edit_control.html");
				end
	
				if(selected_block.header_edit_control)
					edit_control = Template.instance(mod_name, "user_area_header_edit_control");
					
					if(!edit_control.nil?())
						edit_control.view_user = request.session.user;
						t.edit_control = edit_control.display();
					end
				end
			end
			
			print "<div class='user_area_wrapper'>";
			
			if(owner_view && !request.session.user.user_task_list.empty?())
				out = StringIO.new();
				temp_req = subrequest(out, :GetRequest, "/new/tasks", {}, [:User, request.session.user]);
				
				print out.string;
			end
			
			print t.display();
		end
		
		#when blog gets rewritten remove the blog filter.
		if(!remain.nil?() && !remain.empty?())
			rewrite(request.method, (url/remain).to_s + ":Body", nil, [:User, user_obj]);
		end
		
		if (include_header)
			print '</div>'
		end
	end
end
