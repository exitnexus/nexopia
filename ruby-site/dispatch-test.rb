require 'site_initialization';
require "test/unit";

initialize_site();

ARGV = [];

def find_tests(path)
	files = [];
	Dir["#{path}/*.test.rb"].each {|file|
		if (File.ftype(file) == 'directory')
			files += find_tests(file);
		else
			files.push(file);
		end
	}
	return files;
end

if ($tests.length < 1)
	$tests.push('') # do everything if nothing is passed in.
end
runfiles = [];

$tests.each {|path|
	path = "../ruby-test/#{path}";
	if (File.exists?("#{path}.test.rb"))
		runfiles.push("#{path}.test.rb");
	else
		runfiles += find_tests(path);
	end
}

runfiles.each {|file|
	require(file);
}
