lib_want :Core, "template/default_view"

class TimeFormat
	class << self
		def time(timestamp_or_time)
			return Template::DefaultView::pp_daytime(TimeFormat.time_value(timestamp_or_time));
		end
		
		
		def date(timestamp_or_time)
			return TimeFormat.time_value(timestamp_or_time).strftime("%B %d, %Y");
		end
		
		
		def month_and_day(timestamp_or_time)
			return TimeFormat.time_value(timestamp_or_time).strftime("%B %d");
		end
		
		
		def month_and_year(timestamp_or_time)
			return TimeFormat.time_value(timestamp_or_time).strftime("%B %Y");
		end
		
		
		def short_date(timestamp_or_time)
			return TimeFormat.time_value(timestamp_or_time).strftime("%b %d, '%y");
		end
		
		
		def date_and_time(timestamp_or_time)
			time_obj = TimeFormat.time_value(timestamp_or_time);
			return Template::DefaultView::pp_daytime(time_obj) + " | " +  time_obj.strftime("%b %d, '%y");
		end
		
		
		def pretty(timestamp_or_time)
			return Template::DefaultView::pp_time(TimeFormat.time_value(timestamp_or_time));
		end
		
		
		def time_value(timestamp_or_time)
			if (timestamp_or_time.kind_of?(Time))
				return timestamp_or_time;
			else
				return Time.at(timestamp_or_time.to_i);
			end
		end
	end
end