
def dispatch(file, cgi)
	begin
		t = Time.now.to_s();
		file.puts("Received request at time #{t}.");
		PageHandler.execute($cgi);
	rescue
		puts $!.to_s+"<br>";
		file.puts $!;
		$@.each { |line|
			puts line+"<br>";
		}
		file.puts $@;
	ensure
		file.flush();
	end
end
