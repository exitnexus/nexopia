
def setup_global_logging_facility(config = $config, log_facilities = nil, redirect_stderr = false)
	#
	# Setup Global Loggin Facility (first call)
	#

	unless config.nil?
		$log ||= ErrorLog.new(config.log_minlevel, log_facilities || config.log_facilities);
		if (redirect_stderr)
			$log.redirect_stderr();
		end
	else
		raise "No Config Avalible:: Unable to start logging"
	end

	$log.setup_special_logging();
end