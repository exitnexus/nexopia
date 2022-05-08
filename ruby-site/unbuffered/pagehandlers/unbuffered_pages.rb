lib_require :Unbuffered, "unbuffered"

class UnbufferedBench < PageHandler

	declare_handlers("unbuffered") {
	
		area :Self
		
		page :GetRequest, :Full, :populate, "populate"
		page :GetRequest, :Full, :buffered, "buffered"
		page :GetRequest, :Full, :unbuffered, "unbuffered"
		page :GetRequest, :Full, :compare_queries, "compare"
	}

	def populate()
		t = Template.instance("unbuffered", "populate")
		u = Unbuffered.new
		
		# Populate unbuffered table
		start = Time.now
		u.populate()
		t.populate_time = Time.now - start
		
		puts t.display()
	end
	
	def buffered
		t = Template.instance("unbuffered", "query")
		t.query_type = "Buffered"
		u = Unbuffered.new
		
		# Perform buffered query
		start = Time.now
		t.row_count = u.retrieve_buffered
		t.query_time = Time.now - start
		
		puts t.display()
	end
	
	def unbuffered
		t = Template.instance("unbuffered", "query")
		t.query_type = "Unbuffered"
		u = Unbuffered.new
		
		# Perform unbuffered query
		start = Time.now
		t.row_count = u.retrieve_unbuffered
		t.query_time = Time.now - start
		
		puts t.display()
	end
	
	def compare_queries
		t = Template.instance("unbuffered", "compare")
		u = Unbuffered.new
		
		if u.compare_queries()
			t.result = "Success"
		else
			t.result = "Fail"
		end
		
		puts t.display()
	end
end
