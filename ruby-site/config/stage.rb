config_require "live";


class StageConfig < LiveConfig
	name_config "stage"

	def initialize
		super()

		@max_requests = nil;

		@site_name = "Nexopia.com Staging Area"
		@base_domain = "stage.nexopia.com"

		@worker_queue_name = "stage"
		# @memcache_options.push(:delete_only => true)

		@log_minlevel = case ENV['NEXOPIA_LOG_LEVEL']
			when "spam"
				:spam
			when "debug"
				:debug
			when "info"
				:info
			else
				@log_minlevel
				ENV.delete('NEXOPIA_LOG_LEVEL')
		end

		@log_facilities = {
			:general     => [:syslog, :request_buffer_timeline],
			:sql         => [:syslog, :request_buffer_timeline],
			:pagehandler => [:request_buffer_timeline],
			:memcache    => [:syslog, :request_buffer_timeline],
			:template    => [],
			:site_module => [],
			:admin       => [:request_buffer_admin],
			:worker      => [:syslog]
		};

		@rap_php_config = "stage"
		@override_email = "orwell-test@nexopia.com"

		# [Timo, Timo, Graham, Nathan, Graham, Remi, Greg, Chris T]
		@debug_info_users = [1,21,997372,1522402,1745917,2309088,3116184,3233577,3495055]
	end

end
