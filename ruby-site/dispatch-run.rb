require 'site_initialization';

#define it in case it doesn't load properly or is skipped
class Lockfile
	def initialize(*blah)
	end
	def lock(*blah)
		yield;
	end
end

if(!ENV['SKIP_LOCKFILE'] && !(PLATFORM =~ /win32/))
	begin
		require 'lockfile';
	rescue LoadError
		$log.info("Lockfile gem not installed, ignoring it", :warning);
	end
end

initialize_site(false);

lib_require :Core, "run-scripts"

lock = Lockfile.new("/tmp/runner.#{$site.config.class.config_name}.#{$runs.join('-')}.lock");
lock.lock {
	Core::RunScripts::run($runs)
}