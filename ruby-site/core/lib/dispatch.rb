
def dispatch(cgi)
	begin
		PageHandler.execute($cgi);
	rescue
		puts $!.to_s+"<br>";
		$stderr.puts $!;
		$@.each { |line|
			puts line+"<br>";
		}
		$stderr.puts $@;
	ensure
		$stderr.flush();
	end
end
