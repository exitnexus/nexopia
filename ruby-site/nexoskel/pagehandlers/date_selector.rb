# Class to provide date selection functionality in forms.
# 
# The default form values for day, month, or year can be accessed via the element name 
# "day", "month", or "year". If different names for these attributes are desired (for
# example, if the form has more than one DateSelector) these can be changed via the
# day_ref, month_ref, or year_ref attributes.
#
# TODO: Add a pre-made Javascript date selector, such as the one at:
# 	
# 	http://yellow5.us/projects/datechooser/
#
# to this class so that browsers with Javascript enabled get a slightly nicer way to
# select a date. The current functionality is to support non-Javascript browsers.
class DateSelector < PageHandler
	
	declare_handlers("Nexoskel/date") {
		area :Skeleton
		
		page :GetRequest, :Full, :date, "date"
		page :GetRequest, :Full, :dob, "dob"
		page :GetRequest, :Full, :date, "date", input(Integer), input(Integer), input(Integer)
		page :GetRequest, :Full, :dob, "dob", input(Integer), input(Integer), input(Integer)
	}

	MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
	DAYS = (1..31).to_a;
	YEARS = (1900..Time.now.year).to_a;

	MAX_AGE = 60;
	MIN_AGE = 14;

	def date(month=nil,day=nil,year=nil)
		t = Template.instance("nexoskel","date_selector");
		
		t.day_ref = params["day_ref", String, "day"];
		t.month_ref = params["month_ref", String, "month"];
		t.year_ref = params["year_ref", String, "year"];
		
		t.days = DAYS;
		t.months = MONTHS;
		
		min = params["min_year", Integer, Time.now.year-10];
		max = params["max_year", Integer, Time.now.year+10];
		t.years = constrain_years(min, max, true);
		
		t.display_day_name = "Day";
		t.display_month_name = "Month";
		t.display_year_name = "Year";
		
		t.selected_day = day;
		t.selected_month = month;
		t.selected_year = year;

		t.year_onchange_handler = params["year_onchange_handler", String, nil];
		t.month_onchange_handler = params["month_onchange_handler", String, nil];
		t.day_onchange_handler = params["day_onchange_handler", String, nil];
		
		t.hide_years = params["hide_years", Boolean, nil];
		t.hide_months = params["hide_months", Boolean, nil];
		t.hide_days = params["hide_days", Boolean, nil];
		
		puts t.display;
	end

	
	def dob(month=nil,day=nil,year=nil)
		t = Template.instance("nexoskel","date_selector");
		
		t.day_ref = params["day_ref", String, "day"];
		t.month_ref = params["month_ref", String, "month"];
		t.year_ref = params["year_ref", String, "year"];
		
		t.days = DAYS;
		t.months = MONTHS;
		t.years = constrain_years(Time.now.year - MAX_AGE, Time.now.year - MIN_AGE, true);
		
		t.display_day_name = "Day";
		t.display_month_name = "Month";
		t.display_year_name = "Year";

		t.selected_day = day;
		t.selected_month = month;
		t.selected_year = year;

		t.year_onchange_handler = params["year_onchange_handler", String, nil];
		t.month_onchange_handler = params["month_onchange_handler", String, nil];
		t.day_onchange_handler = params["day_onchange_handler", String, nil];

		t.hide_years = params["hide_years", Boolean, nil];
		t.hide_months = params["hide_months", Boolean, nil];
		t.hide_days = params["hide_days", Boolean, nil];
		
		puts t.display;
	end
	
	
	def constrain_years(min=1900, max=Time.now.year, reverse=false)
		years = (min..max).to_a;
		if (reverse)
			years.reverse!;
		end
		
		return years;
	end
	
	
	def constrain_months(min="January", max="December", reverse=false)
		months = MONTHS[MONTHS.index(min)..MONTHS.index(max)];
		if (reverse)
			months.reverse!;
		end
		
		return months;
	end
	
	
	def constrain_days(min=1, max=31, reverse=false)
		days = DAYS[DAYS.index(min)..DAYS.index(max)];
		if (reverse)
			days.reverse!;
		end
		
		return days;
	end
end
