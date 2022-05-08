#--
# *** This code is copyright 2004 by Gavin Kistner
# *** It is covered under the license viewable at http://phrogz.net/JS/_ReuseLicense.txt
# *** Reuse or modification is free provided you abide by the terms of that license.
# *** (Including the first two lines above in your source code usually satisfies the conditions.)
#++
#
# This file describes the MutableTime class. See its documentation for more info.
# Author::     Gavin Kistner  (mailto:gavin@refinery.com)
# Copyright::  Copyright (c)2004 Gavin Kistner
# License::    See http://Phrogz.net/JS/_ReuseLicense.txt for details
# Version::    1.0.5
# Full Code::  link:../MutableTime.rb

require 'parsedate'

# The MutableTime class behaves much like the builtin Time class, except:
# * almost any property can be changed, e.g.
#    mmTime = MutableTime.new
#    mmTime.year = 1973
#    mmTime.month += 13
# * by default, month numbers start at 0 instead of 1 -- see #monthsStartAtZero?
# * you can choose if the first day of the week is Sunday or Monday -- see #weekStartsOnMonday?
# * it has convenience methods named similar to JavaScript's Date object -- fullYear, month, date, hours, minutes, seconds
# * #customFormat is slightly more powerful than Time#strftime
# * you can easily internationalize the month and day names (without editing the source code for the class)
#
# In addition to the methods explicitly listed here, MutableTime instances support all the methods of the Time class
class MutableTime
	include Comparable
	include ParseDate

	MONTH_NAMES = %w| January February March April May June July August September October November December |
	DAY_NAMES   = %w| Sunday Monday Tuesday Wednesday Thursday Friday Saturday |

	@@zMonday=false; @@zMonth=false;

	# Similar to Time.utc, but when #monthsStartAtZero? is +true+, the default
	# month number is 0, and 1 will be added to the month before calling Time.local
	#
	#    mmTime = MutableTime.utc(2004,1,8,15,45)
	#    mmTime.to_s  =>  'Sun Feb 08 15:45:00 UTC 2004'
	#    MutableTime.monthStartsAtZero=false
	#    mmTime = MutableTime.utc(2004,1,8,15,45)
	#    mmTime.to_s  =>  'Thu Jan 08 15:45:00 UTC 2004'
	def self.utc(year, month=(@@zMonth ? 0 : 1), day=1, hours=0, minutes=0, seconds=0, microseconds=0 )
		self.new(Time.utc(year,month+(@@zMonth ? 1 : 0),day,hours,minutes,seconds,microseconds))
	end

	# Similar to Time.local, but when #monthStartsAtZero? is true, the default
	# month number is 0, and 1 will be added to the month before calling Time.local
	#
	#    mmTime = MutableTime.local(2004,1,8,15,45)
	#    mmTime.to_s  =>  'Sun Feb 08 15:45:00 MST 2004'
	#    MutableTime.monthStartsAtZero=false
	#    mmTime = MutableTime.local(2004,1,8,15,45)
	#    mmTime.to_s  =>  'Thu Jan 08 15:45:00 MST 2004'
	def self.local(year, month=(@@zMonth ? 0 : 1), day=1, hours=0, minutes=0, seconds=0, microseconds=0 )
		self.new(Time.local(year,month+(@@zMonth ? 1 : 0),day,hours,minutes,seconds,microseconds))
	end

	# Attempts to create a new MutableTime instance, using ParseDate.parsedate on the supplied string
	#     mmTime = MutableTime.parse("2/8/1973 3:45pm")
	#     mmTime.to_s  =>  'Thu Feb 08 15:45:00 MST 1973'
	def self.parse( dateTimeAsString )
		datePieces = ParseDate.parsedate(dateTimeAsString)
		datePieces.nitems==0 ? (0.0/0.0) : self.new(Time.local(*datePieces))
	end

	# Like Time.now, returns a MutableTime instance representing the current date/time
	#    mmTime = MutableTime.now
	#    mmTime.to_s  =>  'Fri Feb 13 14:51:05 MST 2004'
	def self.now
		self.new
	end

	# Like Time.at, but does not accept a Time or MutableTime instance as the parameter.
	#    mmTime = MutableTime.at(98059507)
	#    mmTime.to_s  =>  'Thu Feb 08 15:45:07 MST 1973'
	def self.at( seconds , microseconds=0 )
		raise "MutableTime.at() requires the first parameter to be Numeric" unless seconds.kind_of?(Numeric)
		self.new(seconds+microseconds)
	end

	# Can be called in one of five ways:
	# * <tt>MutableTime.new( )</tt> -- same as #now
	# * <tt>MutableTime.new( someString )</tt> -- same as #parse
	# * <tt>MutableTime.new( someSeconds )</tt> -- same as #at
	# * <tt>MutableTime.new( year, month, ... )</tt> -- same as #local
	# * <tt>MutableTime.new( aTimeOrMutableTime )</tt> -- creates a new MutableTime based off of the supplied Time or MutableTime object
	def initialize( dateString_Seconds_Time_orYear = nil , *dateTimePieces )
		if dateTimePieces.length==0 then
			if dateString_Seconds_Time_orYear.kind_of?(Numeric) then
				@t=Time.at(dateString_Seconds_Time_orYear)
			elsif dateString_Seconds_Time_orYear.kind_of?(String) then
				@t=Time.local(*ParseDate.parsedate(dateString_Seconds_Time_orYear))
			elsif dateString_Seconds_Time_orYear.kind_of?(Time) then
				@t=dateString_Seconds_Time_orYear
			elsif dateString_Seconds_Time_orYear.kind_of?(MutableTime) then
				@t=dateString_Seconds_Time_orYear.t
			else
				@t=Time.now
			end
		else
			dateTimePieces.unshift(dateString_Seconds_Time_orYear)
			@t = self.class.local(*dateTimePieces).t
		end
	end


	# Returns the current state of the class -- see #weekStartsOnMonday=
	def self.weekStartsOnMonday?
		@@zMonday
	end

	# By default, #weekDay numbers are the same as in Time: 0..6, with 0 being Sunday.
	#
	# The MutableTime class can also use 0==Monday; if you prefer this, pass in a truth value to this method to achieve this goal.
	#
	# Note that day names produced by #strftime or #customFormat will not be affected.
	#    mmTime = MutableTime.now
	#    mmTime.customFormat('#M#/#D# #DDDD#')  =>  '2/13 Friday'
	#    mmTime.weekDay                         =>  5
	#    MutableTime.weekStartsOnMonday = true
	#    mmTime.weekDay                         =>  4
	#    mmTime.customFormat('#M#/#D# #DDDD#')  =>  '2/13 Friday'
	def self.weekStartsOnMonday=(mondayIsZero=false)
		mondayIsZero=!!mondayIsZero
		DAY_NAMES.push(DAY_NAMES.shift) if mondayIsZero && !@@zMonday
		DAY_NAMES.unshift(DAY_NAMES.pop) if !mondayIsZero && @@zMonday
		@@zMonday=mondayIsZero
	end

	# Returns the current state of the class -- see #monthsStartAtZero=
	def self.monthsStartAtZero?
		@@zMonth
	end

	# *Unlike* Time, month numbers returned by #mon or #month start with January==0 (by default)
	#
	# If you prefer January==1, call this method and pass in a non-truth value.
	#
	# Note that month names and numbers produced by #strftime or #customFormat will not be affected.
	#    mmTime = MutableTime.now
	#    mmTime.customFormat('#MMMM# #D##th# -- #M#/#D#')  =>  'February 13th -- 2/13'
	#    mmTime.month                                      =>  1
	#    MutableTime.monthsStartAtZero = false
	#    mmTime.month                                      =>  2
	#    mmTime.customFormat('#MMMM# #D##th# -- #M#/#D#')  =>  'February 13th -- 2/13'
	def self.monthsStartAtZero=(januaryIsZero=true)
		@@zMonth=!!januaryIsZero
	end

	# Returns the array of month names used for customFormat.
	#
	# See #monthNames= for the impact of changing this array.
	def self.monthNames()
		MONTH_NAMES
	end

	# Allows you to set the localization-friendly month names used by #customFormat
	#    mmTime = MutableTime.new
	#    mmTime.customFormat('#DDDD#, #MMMM# #D##th#')  =>  'Friday, February 13th'
	#    MutableTime.monthNames =  %w| Janvier Fevrier Mars Avril Mai Juin Juillet
	#                                  Aout Septembre Octobre Novembre Decembre |
	#    MutableTime.dayNames   =  %w| Dimanche Lundi Mardi Mercredi Jeudi
	#                                  Vendredi Samedi |
	#    mmTime.customFormat('#DDDD#, #MMMM#-#D#')      =>  'Samedi, Fevrier-13'
	def self.monthNames=(newMonthNames)
		raise "MutableTime.monthNames expects an array of 12 month names" unless newMonthNames.class==Array && newMonthNames.length==12
		newMonthNames.each_index{ |i| MONTH_NAMES[i]=newMonthNames[i] }
	end

	# Returns the array of day names used for customFormat.
	#
	# See #dayNames= for the impact of changing this array.
	def self.dayNames()
		DAY_NAMES
	end

	# Allows you to set the localization-friendly day names used by #customFormat
	#    mmTime = MutableTime.new
	#    mmTime.customFormat('#DDDD#, #MMMM# #D##th#')  =>  'Friday, February 13th'
	#    MutableTime.monthNames =  %w| Janvier Fevrier Mars Avril Mai Juin Juillet
	#                                  Aout Septembre Octobre Novembre Decembre |
	#    MutableTime.dayNames   =  %w| Dimanche Lundi Mardi Mercredi Jeudi
	#                                  Vendredi Samedi |
	#    mmTime.customFormat('#DDDD#, #MMMM#-#D#')      =>  'Samedi, Fevrier-13'
	# The array you supply <b>must match</b> with #weekStartsOnMonday?; if #weekStartsOnMonday? is +true+, then your array must start with the name of Monday and end with Sunday.
	def self.dayNames=(newDayNames)
		newDayNames.each_index{ |i| DAY_NAMES[i]=newDayNames[i] }
	end


	def method_missing(meth, *args, &block) # :nodoc:
		@t.send(meth, *args, &block)
	end

	alias self_respond_to? respond_to?
	def respond_to?(meth)
		return self_respond_to?(meth) || @t.respond_to?(meth);
	end



	# Returns a new MutableTime instance which is n *days* in the future (and with the time unaffected).
	#    mmTime = MutableTime.new
	#    oneWeekFromToday = mmTime+7
	#    mmTime             =>  'Fri Feb 13 15:39:30 MST 2004'
	#    oneWeekFromToday   =>  'Fri Feb 20 15:39:30 MST 2004'
	#
	# Days may wrap beyond month and year boundaries without problem, and may be fractional.
	#    mmTime+21          =>  'Fri Mar 05 15:39:30 MST 2004'
	#    mmTime+1.5         =>  'Sun Feb 15 03:39:30 MST 2004'
	def +(n)
		(d=self.dup).date+=n
		d;
	end

	# If n is an integer, returns a new MutableTime instance which is n *days* in the past (and with the time unaffected).
	#    mmTime = MutableTime.new
	#    oneWeekAgo = mmTime-7
	#    mmTime        =>  'Fri Feb 13 15:39:30 MST 2004'
	#    oneWeekAgo    =>  'Fri Feb 06 15:39:30 MST 2004'
	#
	# Days may wrap beyond month and year boundaries without problem, and may be fractional.
	#    mmTime-87     =>  'Tue Nov 18 15:39:30 MST 2003'
	#    mmTime-1.5    =>  'Thu Feb 12 03:39:30 MST 2004'
	#
	# If n is a MutableTime instance, returns the number of seconds (as a Float) between the two
	def -(n)
		if n.kind_of?(Integer)
			self+(-n)
		elsif n.class==self.class
			self.t-n.t
		else
			raise "Parameter passed to MutableTime#-(n) must be an Integer or MutableTime instance"
		end
	end

	# Same as Time#<=>
	def <=>(aMutableTime)
		@t<=>aMutableTime.t
	end

	# Returns a new MutableTime instance that is one *day* in the future
	def succ()
		self+1
	end


	# Accesses the Time class instance associated with the instance. <b>Setting this to an object other than a Time instance will break the class</b>.
	attr_accessor(:t)

	# Returns the year number (with leading century).
	def fullYear()
		@t.year
	end

	# Returns the month number. (0..11 or 1..12 -- see #monthsStartAtZero?)
	def month()
		@t.mon-(@@zMonth ? 1 : 0)
	end

	# Synonym for #month. (0..11 or 1..12 -- see #monthsStartAtZero?)
	def mon()
		month
	end

	# Returns the day number (1..31) for this date.
	def date()
		@t.day
	end

	# Returns the hour number (0..23) for the time.
	def hours()
		@t.hour
	end

	# Returns the minute number (0..59) for the time.
	def minutes()
		@t.min
	end

	# Returns the second number (0..60) for the time.
	def seconds()
		@t.sec
	end

	# Returns just the milliseconds for the time.
	def milliseconds()
		@t.usec/1000
	end

	# Returns just the microseconds for the time.
	def microseconds()
		@t.usec
	end

	# Returns true if the hours are in 0..11 (12am is hours==0)
	def am?
		hours<12
	end

	# Returns true if the hours are in 12..23.
	def pm?
		hours>=12
	end

	# Return the weekday number (0..6) of the date. See #weekStartsOnMonday= for more information
	def weekDay()
		@@zMonday ? (@t.wday+6) % 7 : @t.wday
	end

	# Synonym for #weekDay
	def wday()
		weekDay
	end

	# Returns the day number in the year, 1..366 (Synonym for Time#yday)
	def yearDay()
		@t.yday
	end

	# Returns a 7-element array containing all the days which occur within the same week as this day
	#
	# If showDaysInOtherMonths is false, nil elements will be used in place of MutableTime instances for those days which are in a month other than the receiver of this method
	#    mmTime = MutableTime.new('3/2/2000')
	#    puts mmTime.weekDays
	#    => Sun Feb 27 00:00:00 MST 2000
	#    => Mon Feb 28 00:00:00 MST 2000
	#    => Tue Feb 29 00:00:00 MST 2000
	#    => Wed Mar 01 00:00:00 MST 2000
	#    => Thu Mar 02 00:00:00 MST 2000
	#    => Fri Mar 03 00:00:00 MST 2000
	#    => Sat Mar 04 00:00:00 MST 2000
	#
	#    puts mmTime.weekDays(false)
	#    => nil
	#    => nil
	#    => nil
	#    => Wed Mar 01 00:00:00 MST 2000
	#    => Thu Mar 02 00:00:00 MST 2000
	#    => Fri Mar 03 00:00:00 MST 2000
	#    => Sat Mar 04 00:00:00 MST 2000
	def weekDays(showDaysInOtherMonths=true)
		week = []
		n = self-weekDay
		range = (n..(n+6))
		if showDaysInOtherMonths
			[*range]
		else
			range.each{ |d| week << ((showDaysInOtherMonths || d.month==self.month) ? d : nil) }
			week
		end
	end

	# returns true if this object represents a weekday
	def weekday?()
		return (wday > 0 && wday < 6)
	end

	# returns true if this object represents a workday (currently just weekday is taken into account)
	def workday?()
		return weekday?();
	end

	# Replaces every token in formatString with its corresponding value. See the table below.
	#
	#    token:     description:             example:
	#    #YYYY#     4-digit year             2004
	#    #YY#       2-digit year             04
	#    #MMMM#     full month name          February
	#    #MMM#      3-letter month name      Feb
	#    #MM#       2-digit month number     02
	#    #M#        month number             2
	#    #DDDD#     full weekday name        Wednesday
	#    #DDD#      3-letter weekday name    Wed
	#    #DD#       2-digit day number       09
	#    #D#        day number               9
	#    #th#       day ordinal suffix       nd
	#    #hhh#      military/24-based hour   17
	#    #hh#       2-digit hour             05
	#    #h#        hour                     5
	#    #mm#       2-digit minute           07
	#    #m#        minute                   7
	#    #ss#       2-digit second           09
	#    #s#        second                   9
	#    #ampm#     "am" or "pm"             pm
	#    #AMPM#     "AM" or "PM"             PM
	# Non-tokens are left untouched in the output string
	#    mmTime = MutableTime.new('2/3/2004 15:37')
	#    mmTime.customFormat('#DDDD#, #MMMM# #D##th# @ #h#:#mm##ampm#')
	#      =>  'Tuesday, February 3rd @ 3:37pm'
	#    mmTime.customFormat('#YYYY#-#MMM#-#D#')
	#      =>  '2004-Feb-3'
	#    mmTime.customFormat('#MM#/#DD#/#YY#')
	#      =>  '02/03/04'
	# See #monthNames= and #dayNames= to localize the month and day strings. Note that #monthsStartAtZero= and #weekStartsOnMonday= have no affect on the output of #customFormat=
	def customFormat(formatString)
		zYY   = (zYYYY=@t.year.to_s)[-2..-1]
		zMM   = (zM=@t.mon)<10 ? ('0'+zM.to_s) : zM.to_s
		zMMM  = (zMMMM=MONTH_NAMES[zM-1])[0...3]
		zDD   = (zD=@t.day)<10 ? ('0'+zD.to_s) : zD.to_s
		zDDD  = (zDDDD=DAY_NAMES[weekDay])[0...3]
		zth   = (zD>=10&&zD<=20) ? 'th' : ((dMod=zD%10)==1) ? 'st' : (dMod==2) ? 'nd' : (dMod==3) ? 'rd' : 'th'

		zh    = (zhhh=@t.hour)==0 ? 24 : zhhh; zh-=12 if (zh>12)
		zhh   = zh<10 ? ('0'+zh.to_s) : zh.to_s
		zmm   = (zm=@t.min)<10 ? ('0'+zm.to_s) : zm.to_s
		zss   = (zs=@t.sec)<10 ? ('0'+zs.to_s) : zs.to_s
		zAMPM = (zampm=zhhh<12 ? 'am' : 'pm').upcase

		f=formatString
		f.gsub!(/#YYYY#/,zYYYY);f.gsub!(/#YY#/,zYY);f.gsub!(/#MMMM#/,zMMMM);f.gsub!(/#MMM#/,zMMM);f.gsub!(/#MM#/,zMM.to_s);f.gsub!(/#M#/,zM.to_s);f.gsub!(/#DDDD#/,zDDDD);f.gsub!(/#DDD#/,zDDD);f.gsub!(/#DD#/,zDD);f.gsub!(/#D#/,zD.to_s);f.gsub!(/#th#/,zth);f.gsub!(/#hhh#/,zhhh.to_s);f.gsub!(/#hh#/,zhh);f.gsub!(/#h#/,zh.to_s);f.gsub!(/#mm#/,zmm);f.gsub!(/#m#/,zm.to_s);f.gsub!(/#ss#/,zss);f.gsub!(/#s#/,zs.to_s);f.gsub!(/#ampm#/,zampm);f.gsub!(/#AMPM#/,zAMPM);f
	end




	# Sets the year number to the supplied value. Also works with += and -= expansion to allow incrementing/decrementing.
	#    mmTime = MutableTime.new    => 'Fri Feb 13 16:24:58 MST 2004'
	#    mmTime.year=2003
	#    puts mmTime                 => 'Thu Feb 13 16:24:58 MST 2003'
	#    mmTime.year+=10
	#    puts mmTime                 => 'Wed Feb 13 16:24:58 MST 2013'
	def year=(y)
		(locals = [*@t][0..5].reverse!)[0]=y
		@t=Time.local(*locals)
	end

	# Sets the month number to the supplied value. Works with += and -= expansion to allow incrementing/decrementing. Supports months outside the 'valid' range, automatically wrapping to the next/previous year as needed.
	#
	# This method is sensitive to the setting in #monthsStartAtZero=; if true (the default), mmTime.month=1 represents February; if false, it represents January
	#    mmTime = MutableTime.new    => 'Fri Feb 13 16:24:58 MST 2004'
	#    mmTime.month=7
	#    puts mmTime                 => 'Fri Aug 13 16:24:58 MST 2004'
	#    mmTime.month+=13
	#    puts mmTime                 => 'Tue Sep 13 16:24:58 MST 2005'
	#
	# Since setting an invalid month/day combination wraps to the valid equivalent date, repeatedly incrementing the month <b>may have unintended results</b> for date numbers greater than 28.
	#    def six_months_of_days(d)
	#    	6.times{
	#    		print d.customFormat('#MMM#-#D#-#YYYY#'),' : '
	#    		d.month+=1
	#    	}
	#    	puts
	#    end
	#
	#    six_months_of_days( MutableTime.new('1/1/2004') )
	#    => 'Jan-1 : Feb-1 : Mar-1 : Apr-1 : May-1 : Jun-1 : '
	#
	#    six_months_of_days( MutableTime.new('1/31/2004') )
	#    => 'Jan-31 : Mar-2 : Apr-2 : May-2 : Jun-2 : Jul-2 : '

	def month=(m)
		m-=1 unless @@zMonth
		y=m/12
		m%=12
		m+=1 unless @@zMonth
		locals = [*@t][0..5].reverse!
		locals[0]+= y;
		locals[1] = m;
		@t=self.class.local(*locals).t
	end


	# Sets the day number to the supplied value. Works with += and -= expansion to allow incrementing/decrementing. Supports day numbers outside the 'valid' range, automatically wrapping to the next/previous month as needed.
	#    mmTime = MutableTime.new   =>  'Fri Feb 13 17:24:52 MST 2004'
	#    mmTime.date=22
	#    puts mmTime                =>  'Sun Feb 22 17:24:52 MST 2004'
	#    mmTime.date=30
	#    puts mmTime                =>  'Mon Mar 01 17:24:52 MST 2004'
	#    mmTime.date-=15
	#    puts mmTime                =>  'Sun Feb 15 17:24:52 MST 2004'
	def date=(n)
		@t+=(n-date)*86400;
	end

	# Synonym for #date=
	def day=(n)
		date(n)
	end

	# Sets the hour number to the supplied value, using 24-hour values (0..23). Works with += and -= expansion to allow incrementing/decrementing. Supports hour numbers outside the 'valid' range, automatically wrapping to the next/previous day as needed.
	def hours=(n)
		@t+=(n-hours)*3600;
	end

	# Sets the minute number to the supplied value. Works with += and -= expansion to allow incrementing/decrementing. Supports minute numbers outside the 'valid' range, automatically wrapping to the next/previous hour as needed.
	def minutes=(n)
		@t+=(n-minutes)*60;
	end

	# Sets the second number to the supplied value. Works with += and -= expansion to allow incrementing/decrementing. Supports second numbers outside the 'valid' range, automatically wrapping to the next/previous minute as needed.
	def seconds=(n)
		@t+=(n-seconds);
	end

	# Sets the millisecond number to the supplied value. Works with += and -= expansion to allow incrementing/decrementing. Supports millisecond numbers outside the 'valid' range, automatically wrapping to the next/previous second as needed.
	def milliseconds=(n)
		@t+=(n-milliseconds)/1000;
	end

	# Sets the microsecond number to the supplied value. Works with += and -= expansion to allow incrementing/decrementing. Supports millisecond numbers outside the 'valid' range, automatically wrapping to the next/previous second as needed.
	def microseconds=(n)
		@t+=(n-microseconds)/1000000;
	end

	# Synonym for #gmtime! (since Time supports such a method).
	def gmtime
		@t.gmtime
		self
	end

	# Modifies the instance to reflect the equivalent time as specied in UTC/GMT.
	#    mmTime = MutableTime.new
	#    puts mmTime        =>  'Fri Feb 13 17:32:51 MST 2004'
	#    mmTime.gmtime!
	#    puts mmTime        =>  'Sat Feb 14 00:32:51 UTC 2004'
	def gmtime!
		gmtime
	end

	# Synonym for #localtime! (since Time supports such a method).
	def localtime
		@t.localtime
		self
	end

	# Modifies the instance to reflect the equivalent time as specied in the local timezone.
	#    mmTime = MutableTime.new
	#    mmTime.gmtime!
	#    puts mmTime        =>  'Fri Feb 13 17:32:51 MST 2004'
	#    mmTime.localtime!
	#    puts mmTime        =>  'Sat Feb 14 00:32:51 UTC 2004'
	def localtime!
		localtime
	end


	def to_s()      #:nodoc:
		@t.to_s
	end

	def inspect()   #:nodoc:
		to_s
	end

	def to_a()      #:nodoc:
		[*@t]
	end
end
