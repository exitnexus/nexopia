config_require "live";

class StageConfig < LiveConfig
	name_config "stage"

	def initialize
		super()
		# Stuff that belongs to stage goes here.
		@site_base_dir = "/home/nexopia/ruby-stage"
		@base_domain = "stage.nexopia.com"
		@queue_identifier = "stage"
		@gearman_protocol_id = "stage:" 
		#@memcache_options.push(:delete_only => true)

		@log_minlevel = :info;
		if ENV['DEBUG']
			@log_minlevel = :debug;
		end

		# Stuff that will eventually go into the live config goes here.
		@modules_include.push(:Video)
	end
end
