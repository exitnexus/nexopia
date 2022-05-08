lib_require :Core, 'storable/storable'

class LoginLog < Storable
	init_storable(:usersdb, "loginlog");
	
	def self.log(userid, ip, status)
		login_log = LoginLog.new
		login_log.userid = userid
		login_log.time = Time.now.to_i
		login_log.ip = ip
		login_log.result = status
		login_log.store
	end
end