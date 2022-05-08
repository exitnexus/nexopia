class AdminLog
	class << self
		def log(request, action, logstr)
			$site.dbs[:moddb].query("INSERT INTO adminlog SET userid = ?, ip = ?, time = ?, page = ?, action = ?, description = ?", request.session.user.userid, request.get_ip_as_int, Time.now().to_i, request.uri, action, logstr)
		end
	end
end