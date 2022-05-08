require 'site_initialization';

begin
	require 'lockfile';
rescue LoadError
	$log.info("Lockfile gem not installed, ignoring it", :warning);

	class Lockfile
		def lock(*blah)
			yield;
		end
	end
end

initialize_site(false);

runfiles = [];

$runs.each {|type|
	Dir["*/run/#{type}*.rb"].each {|file|
		if (File.ftype(file) != 'directory')
			runfiles.push(file);
		end
	}
}

$regex = /([^-\/]+)-(.+)\.rb$/;
$running = nil;
$running_info = {};
$depth = 0;
$ran = {};

def run_script(file)
	$running, file = file, $running; # swap them so we can restore them after this is done.

	res = $running.match($regex);
	info = {:type => res[1], :name => res[2]};

	$running_info, info = info, $running_info;

	begin
		if (!$ran.key?($running))
			$ran[$running] = true;
			puts("#{'|'*$depth}Running #{$running_info[:type]}-#{$running_info[:name]}...");
			require($running);
		end
	ensure
		$running, file = file, $running; # swap back
		$running_info, info = info, $running_info;
	end
end

def depends_on(name)
	new_file = $running.sub($regex, '\1-' + name + '.rb');
	$depth += 1;
	puts("#{'|'*$depth}#{$running_info[:type]}-#{$running_info[:name]} depends on #{$running_info[:type]}-#{name}");
	run_script(new_file);
	$depth -= 1;
end

if (PLATFORM =~ /win32/)
	class Lockfile
		def lock(*blah)
			yield;
		end
	end
end

lock = Lockfile.new("/tmp/runner.#{$site.config.class.config_name}.#{$runs.join('-')}.lock");
lock.lock {
	runfiles.each {|file|
		$site.cache.use_context({}) {
			run_script(file);
		}
	}
}
