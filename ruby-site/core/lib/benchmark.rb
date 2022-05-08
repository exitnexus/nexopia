
def benchmark(test_name = nil, num = nil)
	start = Time.now.to_f;

	if(num && num > 0)
		num.times {
			yield
		}
	else
		yield
	end

	time = Time.now.to_f - start;

	str = "%13.3f ms" % [ time*1000 ]
	str << " - %7.2f/s" % [ num.abs/time ] if num
	str << " - #{test_name}" if test_name
	
	puts str
end

