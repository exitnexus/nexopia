lib_require :Core, 'template/template';
lib_require :Video, 'mock_profile';
lib_require :Video, 'embed_handler';

class MockProfiles < PageHandler

	def initialize(*args)
		super(*args);
		@dump = StringIO.new;
	end


	declare_handlers("videos/profile") {

		# Public Level Handlers
		area :Public
		
		page :GetRequest, :Full, :edit_profile, "edit"
		page :GetRequest, :Full, :edit_profile, "edit", input(Integer)
		page :PostRequest, :Full, :update_profile, "update" 		

	}


  def edit_profile(userid=nil)
    p = MockProfile.find(:first, userid) || MockProfile.new;

    t = Template::instance('video', 'edit_mock_profile');
		t.mockprofile = p;

		t.handler_root = "/videos/profile";
		puts t.display();
  end
  

  def update_profile
    content = params['content', String];
    username = params['username', String];
    
    user = User.get_by_name(username);
    
    p = MockProfile.find(:first, user.userid) || MockProfile.new;
    p.userid = user.userid;
    p.content = content;
    p.store();
    
		if (site_module_loaded?(:Worker))
			Worker::PostProcessQueue.queue(VideoModule, "handle_content_from_profile", [content], false);
		end

    site_redirect("/videos/profile/edit/#{user.userid}");
  end
  
end