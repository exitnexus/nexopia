
# a hello world page for testing
class HelloWorld < PageHandler
	declare_handlers("hello") {
		area :Public
#		access_level :NotLoggedIn
		handle :GetRequest, :hello
	}

	def hello()
		if(!$num_requests)
			$num_requests = 0;
		end
		$num_requests += 1;

		puts "Hello World!<br>";
		puts "Time stamp: " + (Time.now.to_s) + "<br>";
		puts "PID: " + Process.pid.to_s + "<br>";
		puts "Request count: " + $num_requests.to_s;
	end
end
