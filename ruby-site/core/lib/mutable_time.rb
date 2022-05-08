
# Convenience class that conforms to the interface of 'Time' but has some nice 
# functions for setting the day, month and year.
class MutableTime
	
	attr :year, true;
	attr :month, true;
	attr :day, true;
	
	def initialize(t)
		time = Time.at(t).gmtime;
		@day = time.day;
		@month = time.month;
		@year = time.year;
	end
	
	def MutableTime.method_missing(method_name, *args)
		ret_val = Time.send(method_name, *args);
		if (ret_val.kind_of? Time)
			return MutableTime.new(ret_val);
		end
		return ret_val;
	end

	def time
		Time.utc(@year, @month, @day);
	end
	
	def untime(t)
		@day = t.day;
		@month = t.month;
		@year = t.year;
	end
	
	def method_missing(method_name, *args)
		t = time
		ret_val = t.send(method_name, *args);
		untime(t);
		return ret_val;
	end
	
	def month=(new_month)
		@month = new_month;
	end
	
	def day=(new_day)
		@day = new_day;
	end
	
	def year=(new_year)
		@year = new_year;
	end	
	
end