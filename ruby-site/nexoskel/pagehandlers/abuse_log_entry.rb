lib_require :Nexoskel, 'abuse_log_entry_processing'

class AbuseLogPageHandler < PageHandler
	declare_handlers("Nexoskel/abuse") {
		area :Skeleton
		handle :GetRequest, :abuse_log_fields, input(Integer)
	}

	def abuse_log_fields(abuse_log_type)
		t = Template.instance('nexoskel', 'abuse_log_fields')
		
		abuse_error_obj = $site.memcache.get("abuse_error-#{abuse_log_type}-#{request.session.userid}/#{request.user.userid}")
		if(!abuse_error_obj.nil?)
			t.abuse_error = abuse_error_obj.error
			t.reason = abuse_error_obj.reason
			t.subject = abuse_error_obj.subject
			t.entry = abuse_error_obj.entry
		end
		
		puts t.display
	end
end