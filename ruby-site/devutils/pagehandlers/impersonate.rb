class Impersonate < PageHandler
	declare_handlers("") {
		area :Public
		page :GetRequest, :Full, :impersonate, 'impersonate', input(String), remain
	}

	def impersonate(user_name, remain)
		user = User.get_by_name(user_name)
		$log.info user_name
		#$log.info host
		$log.object remain
		out = StringIO.new()
		new_request = request.dup_modify(
			:method => 'POST',
			:uri => "/webrequest/#{$site.config.www_url.join('/')}/#{remain.join '/'}",
			:params => params,
			:area => :Internal,
			:reply => PageReply.new(out, false),
			:user => user
		) {|req|
			session = Session.build('0.0.0.0', user.userid, false)
			req.instance_variable_set(:"@session", session)
			PageHandler.execute(req);
		}
		puts out.string
	end
end

