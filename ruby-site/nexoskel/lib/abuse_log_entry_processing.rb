lib_require :Core, 'abuse_log'

module AbuseLogEntryProcessing
	# params: The usual params that are passed to a page handler. It is assumed that /Nexoskel/abuse has 
	# been included in the previous page handler and that the params being passed included parameters from
	# that handler-include.
	# abuse_log_type: The actual type abuse log entry (can usually be found as one of the static variables
	# in the core/abuse_log storable class - i.e. AbuseLog::ABUSE_ACTION_EDIT_COMMENTS)
	#
	# returns: false if there's an error processing the params or true if everything went fine
	
	AbuseError = Struct.new(:error, :reason, :subject, :entry)
	def process_abuse_log_params(params, abuse_log_type)
		abuse_log_entry = params["abuse_log_entry", String, ""];
		abuse_log_subject = params["abuse_log_subject", String, ""]
		abuse_log_reason = params["abuse_log_reason", Integer, nil]
		
		if(abuse_log_reason.nil?() || abuse_log_subject.length < 1)
			$site.memcache.set("abuse_error-#{abuse_log_type}-#{request.session.userid}/#{request.user.userid}", 
				AbuseError.new("Abuse reason and subject needed.", abuse_log_reason, abuse_log_subject, abuse_log_entry), 10)
			return false
		else
			$site.memcache.delete("abuse_error-#{abuse_log_type}-#{request.session.userid}/#{request.user.userid}")
		end
		
		error_list = Hash.new();
		author_id_list = Array.new();
		
		AbuseLog.make_entry(request.session.user.userid, request.user.userid,
			abuse_log_type, abuse_log_reason, abuse_log_subject, abuse_log_entry)
		
		return true
	end
end