require 'date'

class UserTime
	attr_reader :time_zone, :time_zone_name

	def initialize(time = Time.now, time_zone = self.class.default_time_zone)
		self.time_zone = demand(time_zone)
		if (time.kind_of? Time)
			@time = time.utc
		else
			@time = Time.at(time).utc
		end
	end

	def time_zone=(time_zone)
		@time_zone_name = time_zone
		@time_zone = self.class.get_time_zone(time_zone)
	end

	def time
		return Time.at(@time.to_f + self.time_zone.offset_seconds(@time)).utc
	end
	private :time

	def to_i
		return @time.to_i
	end
	alias tv_sec to_i

	def +(numeric)
		return self.class.new(self.to_i + numeric, @time_zone_name)
	end

	def -(other_time_or_numeric)
		if (other_time_or_numeric.kind_of?(Time) || other_time_or_numeric.kind_of?(UserTime))
			return self.to_f - other_time_or_numeric.to_f
		else
			return self.class.new(self.to_f - other_time_or_numeric, @time_zone_name)
		end
	end

	def <=>(other_time)
		return self.to_f <=> other_time.to_f
	end

	def asctime
		return time.asctime
	end

	def day
		return time.day
	end
	alias mday day

	def dst?
		return time_zone.dst?(@time)
	end
	alias isdst dst?

	def eql?(other_time)
		return (self.time.eql?(other_time.time) && self.time_zone == other_time.time_zone)
	end

	def getutc
		return self.class.new(@time.to_i, "UTC")
	end
	alias getgm getutc

	def getlocal
		return self.class.new(@time.to_i, @time_zone_name)
	end

	def utc?
		return time_zone == self.class.get_time_zone("UTC")
	end
	alias gmt? utc?

	def utc_offset
		return time_zone.offset_seconds(@time)
	end
	alias gmtoff utc_offset
	alias gmt_offset utc_offset

	def hour
		return time.hour
	end


	def utc
		self.time_zone = "UTC"
		return self
	end
	alias gmtime utc

	def localtime
		self.time_zone = ENV['TZ']
		return self
	end

	def min
		return time.min
	end

	def month
		return time.month
	end
	alias mon month

	def sec
		return time.sec
	end

	def succ
		return self + 1
	end

	def to_a
		return [ sec, min, hour, day, month, year, wday, yday, isdst, zone ]
	end

	def to_f
		return time.to_f
	end

	def usec
		return time.usec
	end
	alias tv_usec usec

	def wday
		return time.wday
	end

	def yday
		return time.yday
	end

	def year
		return time.year
	end

	def zone
		return @time_zone_name
	end

	def to_s
		output = time.to_s
		output.gsub!(/ UTC /, " #{@time_zone_name} ")
		return output
	end
	alias inspect to_s

	def format(format_string)
		format_string.gsub!(/(([^%]|^)(%%)*)%Z/, '\1' + time_zone_name)
		return time.strftime(format_string)
	end
	alias strftime format

	class << self
		@@default_time_zone = "UTC"

		def utc(*args)
			return self.new(Time.utc(*args), "UTC")
		end
		alias gm utc

		def local(*args)
			time = self.utc(*args)
			time.time_zone = self.default_time_zone
			return time
		end
		alias mktime local

		def get_time_zone(time_zone_string)
			return TIME_ZONES[time_zone_string] if (TIME_ZONES.key?(time_zone_string))
			return TIME_ZONES[TIME_ZONE_ALIASES[time_zone_string]]
		end

		def zone_name_by_index(num)
			return TIME_ZONE_INDEX[num] || "UTC"
		end

		def use_time_zone(time_zone, &block)
			old_time_zone, self.default_time_zone = default_time_zone, time_zone
			begin
				yield
			ensure
				self.default_time_zone = old_time_zone
			end
		end


		def default_time_zone
			return @@default_time_zone
		end

		def default_time_zone=(zone)
			@@default_time_zone = zone
		end
	end

	class TimeZone
		attr_reader :base_offset, :std_start, :std_offset, :dst_start, :dst_offset

		#offsets are in minutes, start times are in the format [seconds, minutes, hour, day, month, year, day of week, day of year]
		#only the first 6 elements are used
		def initialize(base_offset, std_start, std_offset, dst_start, dst_offset)
			@base_offset = base_offset
			begin
				@std_start = DateTime.civil(*(std_start[0,6].reverse!))
				@std_offset = std_offset
			rescue
				@std_start = nil
				@std_offset = nil
			end
			begin
				@dst_start = DateTime.civil(*(dst_start[0,6].reverse!))
				@dst_offset = dst_offset
			rescue
				@dst_start = nil
				@dst_offset = nil
			end
		end

		def offset(time)
			if not (@dst_start)
				return base_offset;
			end
			if (after_dst?(time) && before_std?(time))
				return base_offset - dst_offset
			else
				return base_offset - std_offset
			end
		end

		def dst?(time)
			return (after_dst?(time) && before_std?(time))
		end

		def offset_seconds(time)
			return offset(time)*60
		end

		def after_dst?(time)
			return date_time_before_time?(@dst_start, time)
		end

		def before_std?(time)
			return !date_time_before_time?(@std_start, time)
		end

		private
		#ignores year, just compares month/day/hour/minute/second
		def date_time_before_time?(date_time, time)
			yseconds_date_time = date_time.yday*86400 + date_time.hour*3600 + date_time.min*60 + date_time.sec
			yseconds_time = time.yday*86400 + time.hour*3600 + time.min*60 + time.sec
			return yseconds_date_time < yseconds_time
		end
	end

	#0: base offset
	#1: STD start
	#	0 - seconds
  	#	1 - minutes
	#	2 - hour
	#	3 - day of the month
	#	4 - month of the year, starting with 0 for January
	#	5 - Years since 1900
	#	6 - Day of the week
	#	7 - Day of the year
	#2: STD offset
	#3: DST start
	#	0 - seconds
  	#	1 - minutes
	#	2 - hour
	#	3 - day of the month
	#	4 - month of the year, starting with 0 for January
	#	5 - Years since 1900
	#	6 - Day of the week
	#	7 - Day of the year
	#4: DST offset
	TIME_ZONES = {
		'Africa/Abidjan' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Accra' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Addis_Ababa' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Algiers' => TimeZone.new(60, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Asmara' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Bamako' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Bangui' => TimeZone.new(60, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Banjul' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Bissau' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Blantyre' => TimeZone.new(120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Brazzaville' => TimeZone.new(60, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Bujumbura' => TimeZone.new(120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Cairo' => TimeZone.new(120, [0,0,23,5,8,0,4,0], 0, [0,0,0,5,3,0,5,0], 60),
		'Africa/Casablanca' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Ceuta' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Africa/Conakry' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Dakar' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Dar_es_Salaam' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Djibouti' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Douala' => TimeZone.new(60, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/El_Aaiun' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Freetown' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Gaborone' => TimeZone.new(120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Harare' => TimeZone.new(120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Johannesburg' => TimeZone.new(120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Kampala' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Khartoum' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Kigali' => TimeZone.new(120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Kinshasa' => TimeZone.new(60, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Lagos' => TimeZone.new(60, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Libreville' => TimeZone.new(60, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Lome' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Luanda' => TimeZone.new(60, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Lubumbashi' => TimeZone.new(120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Lusaka' => TimeZone.new(120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Malabo' => TimeZone.new(60, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Maputo' => TimeZone.new(120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Maseru' => TimeZone.new(120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Mbabane' => TimeZone.new(120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Mogadishu' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Monrovia' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Nairobi' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Ndjamena' => TimeZone.new(60, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Niamey' => TimeZone.new(60, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Nouakchott' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Ouagadougou' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Porto-Novo' => TimeZone.new(60, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Sao_Tome' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Tripoli' => TimeZone.new(120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Africa/Tunis' => TimeZone.new(60, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Africa/Windhoek' => TimeZone.new(60, [0,0,2,1,8,0,0,0], 60, [0,0,2,1,3,0,0,0], 0),
		'America/Adak' => TimeZone.new(-600, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Anchorage' => TimeZone.new(-540, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Anguilla' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Antigua' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Araguaina' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Argentina/Buenos_Aires' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Argentina/Catamarca' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Argentina/Cordoba' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Argentina/Jujuy' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Argentina/La_Rioja' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Argentina/Mendoza' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Argentina/Rio_Gallegos' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Argentina/San_Juan' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Argentina/Tucuman' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Argentina/Ushuaia' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Aruba' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Asuncion' => TimeZone.new(-240, [0,0,0,2,2,0,0,0], 0, [0,0,0,3,9,0,0,0], 60),
		'America/Atikokan' => TimeZone.new(-300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Bahia' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Barbados' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Belem' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Belize' => TimeZone.new(-360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Blanc-Sablon' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Boa_Vista' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Bogota' => TimeZone.new(-300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Boise' => TimeZone.new(-420, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Cambridge_Bay' => TimeZone.new(-420, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Campo_Grande' => TimeZone.new(-240, [0,0,0,1,10,0,0,0], 60, [0,0,0,5,1,0,0,0], 0),
		'America/Cancun' => TimeZone.new(-360, [0,0,2,5,9,0,0,0], 0, [0,0,2,1,3,0,0,0], 60),
		'America/Caracas' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Cayenne' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Cayman' => TimeZone.new(-300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Chicago' => TimeZone.new(-360, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Chihuahua' => TimeZone.new(-420, [0,0,2,5,9,0,0,0], 0, [0,0,2,1,3,0,0,0], 60),
		'America/Costa_Rica' => TimeZone.new(-360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Cuiaba' => TimeZone.new(-240, [0,0,0,1,10,0,0,0], 60, [0,0,0,5,1,0,0,0], 0),
		'America/Curacao' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Danmarkshavn' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Dawson' => TimeZone.new(-480, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Dawson_Creek' => TimeZone.new(-420, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Denver' => TimeZone.new(-420, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Detroit' => TimeZone.new(-300, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Dominica' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Edmonton' => TimeZone.new(-420, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Eirunepe' => TimeZone.new(-300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/El_Salvador' => TimeZone.new(-360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Fortaleza' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Glace_Bay' => TimeZone.new(-240, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Godthab' => TimeZone.new(-180, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'America/Goose_Bay' => TimeZone.new(-240, [0,1,0,2,2,0,0,0], 60, [0,1,0,1,10,0,0,0], 0),
		'America/Grand_Turk' => TimeZone.new(-300, [0,0,0,5,9,0,0,0], 0, [0,0,0,1,3,0,0,0], 60),
		'America/Grenada' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Guadeloupe' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Guatemala' => TimeZone.new(-360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Guayaquil' => TimeZone.new(-300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Guyana' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Halifax' => TimeZone.new(-240, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Havana' => TimeZone.new(-300, [0,0,0,5,9,0,0,0], 0, [0,0,0,1,3,0,0,0], 60),
		'America/Hermosillo' => TimeZone.new(-420, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Indiana/Indianapolis' => TimeZone.new(-300, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Indiana/Knox' => TimeZone.new(-360, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Indiana/Marengo' => TimeZone.new(-300, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Indiana/Petersburg' => TimeZone.new(-360, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Indiana/Vevay' => TimeZone.new(-300, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Indiana/Vincennes' => TimeZone.new(-360, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Inuvik' => TimeZone.new(-420, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Iqaluit' => TimeZone.new(-300, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Jamaica' => TimeZone.new(-300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Juneau' => TimeZone.new(-540, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Kentucky/Louisville' => TimeZone.new(-300, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Kentucky/Monticello' => TimeZone.new(-300, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/La_Paz' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Lima' => TimeZone.new(-300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Los_Angeles' => TimeZone.new(-480, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Maceio' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Managua' => TimeZone.new(-360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Manaus' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Martinique' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Mazatlan' => TimeZone.new(-420, [0,0,2,5,9,0,0,0], 0, [0,0,2,1,3,0,0,0], 60),
		'America/Menominee' => TimeZone.new(-360, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Merida' => TimeZone.new(-360, [0,0,2,5,9,0,0,0], 0, [0,0,2,1,3,0,0,0], 60),
		'America/Mexico_City' => TimeZone.new(-360, [0,0,2,5,9,0,0,0], 0, [0,0,2,1,3,0,0,0], 60),
		'America/Miquelon' => TimeZone.new(-180, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Moncton' => TimeZone.new(-240, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Monterrey' => TimeZone.new(-360, [0,0,2,5,9,0,0,0], 0, [0,0,2,1,3,0,0,0], 60),
		'America/Montevideo' => TimeZone.new(-180, [0,0,2,1,9,0,0,0], 60, [0,0,2,2,2,0,0,0], 0),
		'America/Montreal' => TimeZone.new(-300, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Montserrat' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Nassau' => TimeZone.new(-300, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/New_York' => TimeZone.new(-300, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Nipigon' => TimeZone.new(-300, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Nome' => TimeZone.new(-540, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Noronha' => TimeZone.new(-120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/North_Dakota/Center' => TimeZone.new(-360, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/North_Dakota/New_Salem' => TimeZone.new(-360, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Panama' => TimeZone.new(-300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Pangnirtung' => TimeZone.new(-300, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Paramaribo' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Phoenix' => TimeZone.new(-420, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Port-au-Prince' => TimeZone.new(-300, [0,0,0,5,9,0,0,0], 0, [0,0,0,1,3,0,0,0], 60),
		'America/Port_of_Spain' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Porto_Velho' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Puerto_Rico' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Rainy_River' => TimeZone.new(-360, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Rankin_Inlet' => TimeZone.new(-360, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Recife' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Regina' => TimeZone.new(-360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Rio_Branco' => TimeZone.new(-300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Santiago' => TimeZone.new(-240, [0,0,3,2,2,0,0,0], 0, [0,0,4,2,9,0,0,0], 60),
		'America/Santo_Domingo' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Sao_Paulo' => TimeZone.new(-180, [0,0,0,1,10,0,0,0], 60, [0,0,0,5,1,0,0,0], 0),
		'America/Scoresbysund' => TimeZone.new(-60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'America/St_Johns' => TimeZone.new(-210, [0,1,0,2,2,0,0,0], 60, [0,1,0,1,10,0,0,0], 0),
		'America/St_Kitts' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/St_Lucia' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/St_Thomas' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/St_Vincent' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Swift_Current' => TimeZone.new(-360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Tegucigalpa' => TimeZone.new(-360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Thule' => TimeZone.new(-240, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Thunder_Bay' => TimeZone.new(-300, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Tijuana' => TimeZone.new(-480, [0,0,2,5,9,0,0,0], 0, [0,0,2,1,3,0,0,0], 60),
		'America/Toronto' => TimeZone.new(-300, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Tortola' => TimeZone.new(-240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'America/Vancouver' => TimeZone.new(-480, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Whitehorse' => TimeZone.new(-480, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Winnipeg' => TimeZone.new(-360, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'America/Yakutat' => TimeZone.new(-540, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'America/Yellowknife' => TimeZone.new(-420, [0,0,2,1,10,0,0,0], 0, [0,0,2,2,2,0,0,0], 60),
		'Antarctica/Casey' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Antarctica/Davis' => TimeZone.new(420, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Antarctica/DumontDUrville' => TimeZone.new(600, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Antarctica/Mawson' => TimeZone.new(360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Antarctica/McMurdo' => TimeZone.new(720, [0,0,2,1,9,0,0,0], 60, [0,0,2,3,2,0,0,0], 0),
		'Antarctica/Palmer' => TimeZone.new(-240, [0,0,4,2,9,0,0,0], 60, [0,0,3,2,2,0,0,0], 0),
		'Antarctica/Rothera' => TimeZone.new(-180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Antarctica/Syowa' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Antarctica/Vostok' => TimeZone.new(360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Aden' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Almaty' => TimeZone.new(360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Amman' => TimeZone.new(120, [0,0,0,5,2,0,4,0], 60, [0,0,0,5,9,0,5,0], 0),
		'Asia/Anadyr' => TimeZone.new(720, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Asia/Aqtau' => TimeZone.new(300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Aqtobe' => TimeZone.new(300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Ashgabat' => TimeZone.new(300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Baghdad' => TimeZone.new(180, [0,0,3,1,3,1,7,0], 60, [0,0,3,1,9,1,7,0], 0),
		'Asia/Bahrain' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Baku' => TimeZone.new(240, [0,0,5,5,9,0,0,0], 0, [0,0,4,5,2,0,0,0], 60),
		'Asia/Bangkok' => TimeZone.new(420, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Beirut' => TimeZone.new(120, [0,0,0,5,2,0,0,0], 60, [0,0,0,5,9,0,0,0], 0),
		'Asia/Bishkek' => TimeZone.new(360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Brunei' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Calcutta' => TimeZone.new(330, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Choibalsan' => TimeZone.new(540, [0,0,2,5,2,0,6,0], 60, [0,0,2,5,8,0,6,0], 0),
		'Asia/Chongqing' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Colombo' => TimeZone.new(330, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Damascus' => TimeZone.new(120, [0,0,0,1,9,1,7,0], 0, [0,0,0,1,3,1,7,0], 60),
		'Asia/Dhaka' => TimeZone.new(360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Dili' => TimeZone.new(540, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Dubai' => TimeZone.new(240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Dushanbe' => TimeZone.new(300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Gaza' => TimeZone.new(120, [0,0,0,1,3,1,7,0], 60, [0,0,0,3,9,0,5,0], 0),
		'Asia/Harbin' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Hong_Kong' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Hovd' => TimeZone.new(420, [0,0,2,5,2,0,6,0], 60, [0,0,2,5,8,0,6,0], 0),
		'Asia/Irkutsk' => TimeZone.new(480, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Asia/Jakarta' => TimeZone.new(420, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Jayapura' => TimeZone.new(540, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Jerusalem' => TimeZone.new(120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Kabul' => TimeZone.new(270, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Kamchatka' => TimeZone.new(720, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Asia/Karachi' => TimeZone.new(300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Kashgar' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Katmandu' => TimeZone.new(345, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Krasnoyarsk' => TimeZone.new(420, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Asia/Kuala_Lumpur' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Kuching' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Kuwait' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Macau' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Magadan' => TimeZone.new(660, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Asia/Makassar' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Manila' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Muscat' => TimeZone.new(240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Nicosia' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Asia/Novosibirsk' => TimeZone.new(360, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Asia/Omsk' => TimeZone.new(360, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Asia/Oral' => TimeZone.new(300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Phnom_Penh' => TimeZone.new(420, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Pontianak' => TimeZone.new(420, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Pyongyang' => TimeZone.new(540, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Qatar' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Qyzylorda' => TimeZone.new(360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Rangoon' => TimeZone.new(390, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Riyadh' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Saigon' => TimeZone.new(420, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Sakhalin' => TimeZone.new(600, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Asia/Samarkand' => TimeZone.new(300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Seoul' => TimeZone.new(540, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Shanghai' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Singapore' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Taipei' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Tashkent' => TimeZone.new(300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Tbilisi' => TimeZone.new(240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Tehran' => TimeZone.new(210, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Thimphu' => TimeZone.new(360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Tokyo' => TimeZone.new(540, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Ulaanbaatar' => TimeZone.new(480, [0,0,2,5,2,0,6,0], 60, [0,0,2,5,8,0,6,0], 0),
		'Asia/Urumqi' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Vientiane' => TimeZone.new(420, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Asia/Vladivostok' => TimeZone.new(600, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Asia/Yakutsk' => TimeZone.new(540, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Asia/Yekaterinburg' => TimeZone.new(300, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Asia/Yerevan' => TimeZone.new(240, [0,0,2,5,2,0,0,0], 60, [0,0,2,5,9,0,0,0], 0),
		'Atlantic/Azores' => TimeZone.new(-60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Atlantic/Bermuda' => TimeZone.new(-240, [0,0,2,2,2,0,0,0], 60, [0,0,2,1,10,0,0,0], 0),
		'Atlantic/Canary' => TimeZone.new(0, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Atlantic/Cape_Verde' => TimeZone.new(-60, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Atlantic/Faroe' => TimeZone.new(0, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Atlantic/Madeira' => TimeZone.new(0, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Atlantic/Reykjavik' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Atlantic/South_Georgia' => TimeZone.new(-120, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Atlantic/St_Helena' => TimeZone.new(0, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Atlantic/Stanley' => TimeZone.new(-240, [0,0,2,1,8,0,0,0], 60, [0,0,2,3,3,0,0,0], 0),
		'Australia/Adelaide' => TimeZone.new(570, [0,0,2,5,2,0,0,0], 0, [0,0,2,5,9,0,0,0], 60),
		'Australia/Brisbane' => TimeZone.new(600, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Australia/Broken_Hill' => TimeZone.new(570, [0,0,2,5,2,0,0,0], 0, [0,0,2,5,9,0,0,0], 60),
		'Australia/Currie' => TimeZone.new(600, [0,0,2,5,2,0,0,0], 0, [0,0,2,1,9,0,0,0], 60),
		'Australia/Darwin' => TimeZone.new(570, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Australia/Eucla' => TimeZone.new(525, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Australia/Hobart' => TimeZone.new(600, [0,0,2,5,2,0,0,0], 0, [0,0,2,1,9,0,0,0], 60),
		'Australia/Lindeman' => TimeZone.new(600, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Australia/Lord_Howe' => TimeZone.new(630, [0,0,2,5,9,0,0,0], 30, [0,0,2,5,2,0,0,0], 0),
		'Australia/Melbourne' => TimeZone.new(600, [0,0,2,5,2,0,0,0], 0, [0,0,2,5,9,0,0,0], 60),
		'Australia/Perth' => TimeZone.new(480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Australia/Sydney' => TimeZone.new(600, [0,0,2,5,2,0,0,0], 0, [0,0,2,5,9,0,0,0], 60),
		'Europe/Amsterdam' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Andorra' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Athens' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Belgrade' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Berlin' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Brussels' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Bucharest' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Budapest' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Chisinau' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Copenhagen' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Dublin' => TimeZone.new(0, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Gibraltar' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Helsinki' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Istanbul' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Kaliningrad' => TimeZone.new(120, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Europe/Kiev' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Lisbon' => TimeZone.new(0, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/London' => TimeZone.new(0, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Luxembourg' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Madrid' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Malta' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Minsk' => TimeZone.new(120, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Europe/Monaco' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Moscow' => TimeZone.new(180, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Europe/Oslo' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Paris' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Prague' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Riga' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Rome' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Samara' => TimeZone.new(240, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Europe/Simferopol' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Sofia' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Stockholm' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Tallinn' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Tirane' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Uzhgorod' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Vaduz' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Vienna' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Vilnius' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Volgograd' => TimeZone.new(180, [0,0,2,5,9,0,0,0], 0, [0,0,2,5,2,0,0,0], 60),
		'Europe/Warsaw' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Zaporozhye' => TimeZone.new(120, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Europe/Zurich' => TimeZone.new(60, [0,0,1,5,2,0,0,0], 60, [0,0,1,5,9,0,0,0], 0),
		'Indian/Antananarivo' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Indian/Chagos' => TimeZone.new(360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Indian/Christmas' => TimeZone.new(420, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Indian/Cocos' => TimeZone.new(390, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Indian/Comoro' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Indian/Kerguelen' => TimeZone.new(300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Indian/Mahe' => TimeZone.new(240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Indian/Maldives' => TimeZone.new(300, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Indian/Mauritius' => TimeZone.new(240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Indian/Mayotte' => TimeZone.new(180, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Indian/Reunion' => TimeZone.new(240, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Apia' => TimeZone.new(-660, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Auckland' => TimeZone.new(720, [0,0,2,1,9,0,0,0], 60, [0,0,2,3,2,0,0,0], 0),
		'Pacific/Chatham' => TimeZone.new(765, [0,45,2,3,2,0,0,0], 0, [0,45,2,1,9,0,0,0], 60),
		'Pacific/Easter' => TimeZone.new(-360, [0,0,3,2,2,0,0,0], 0, [0,0,4,2,9,0,0,0], 60),
		'Pacific/Efate' => TimeZone.new(660, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Enderbury' => TimeZone.new(780, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Fakaofo' => TimeZone.new(-600, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Fiji' => TimeZone.new(720, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Funafuti' => TimeZone.new(720, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Galapagos' => TimeZone.new(-360, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Gambier' => TimeZone.new(-540, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Guadalcanal' => TimeZone.new(660, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Guam' => TimeZone.new(600, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Honolulu' => TimeZone.new(-600, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Johnston' => TimeZone.new(-600, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Kiritimati' => TimeZone.new(840, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Kosrae' => TimeZone.new(660, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Kwajalein' => TimeZone.new(720, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Majuro' => TimeZone.new(720, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Marquesas' => TimeZone.new(-570, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Midway' => TimeZone.new(-660, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Nauru' => TimeZone.new(720, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Niue' => TimeZone.new(-660, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Norfolk' => TimeZone.new(690, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Noumea' => TimeZone.new(660, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Pago_Pago' => TimeZone.new(-660, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Palau' => TimeZone.new(540, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Pitcairn' => TimeZone.new(-480, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Ponape' => TimeZone.new(660, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Port_Moresby' => TimeZone.new(600, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Rarotonga' => TimeZone.new(-600, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Saipan' => TimeZone.new(600, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Tahiti' => TimeZone.new(-600, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Tarawa' => TimeZone.new(720, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Tongatapu' => TimeZone.new(780, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Truk' => TimeZone.new(600, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Wake' => TimeZone.new(720, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
		'Pacific/Wallis' => TimeZone.new(720, [0,0,0,0,0,0,0,0], 0, [0,0,0,0,0,0,0,0], 0),
	}

	TIME_ZONE_ALIASES = {
		'Canada/Atlantic' => 'America/Halifax',
		'Canada/Central' => 'America/Winnipeg',
		'Canada/Newfoundland' => 'America/St_Johns',
		'Canada/Eastern' => 'America/New_York',
		'Canada/East-Saskatchewan' => 'America/Regina',
		'Canada/Mountain' => 'America/Edmonton',
		'Canada/Pacific' => 'America/Vancouver',
		'Canada/Saskatchewan' => 'America/Regina',
		'Canada/Yukon' => 'America/Whitehorse',

		'US/Alaska' => 'America/Anchorage',
		'US/Aleutian' => 'America/Anchorage',
		'US/Arizona' => 'America/Phoenix',
		'US/Central' => 'America/Winnipeg',
		'US/Eastern' => 'America/New_York',
		'US/East-Indiana' => 'America/Indiana/Indianapolis',
		'US/Hawaii' => 'Pacific/Honolulu',
		'US/Michigan' => 'America/Detroit',
		'US/Mountain' => 'America/Edmonton',
		'US/Pacific' => 'America/Vancouver',
		'US/Samoa' => 'Pacific/Apia',
		'Pacific/Samoa' => 'Pacific/Apia',

		'PST' => 'America/Vancouver',
		'PST8PDT' => 'America/Vancouver',
		'MST' => 'America/Edmonton',
		'MST7MDT' => 'America/Edmonton',
		'CST' => 'America/Regina',
		'CST6CDT' => 'America/Winnipeg',
		'EST' => 'America/New_York',
		'EST5EDT' => 'America/New_York',
		'NST' => 'America/St_Johns',
		'GMT' => 'Europe/London',
		'GMT0' => 'Europe/London',
		'Greenwich' => 'Europe/London',
		'UTC' => 'Europe/London',

		'Cuba' => 'America/Havana',
		'Egypt' => 'Africa/Cairo',
		'Eire' => 'Europe/Dublin',
		'Hongkong' => 'Asia/Hong_Kong',
		'Iceland' => 'Atlantic/Reykjavik',
		'Iran' => 'Asia/Tehran',
		'Israel' => 'Asia/Jerusalem',
		'Jamaica' => 'America/Jamaica',
		'Japan' => 'Asia/Tokyo',
		'Kwajalein' => 'Pacific/Kwajalein',
		'Libya' => 'Africa/Tripoli',
		'Poland' => 'Europe/Warsaw',
		'Portugal' => 'Europe/Lisbon',
		'Singapore' => 'Asia/Singapore',
		'Turkey' => 'Europe/Istanbul'
	}

	TIME_ZONE_INDEX = {
		2	=> "Africa/Abidjan",
		3	=> "Africa/Accra",
		4	=> "Africa/Addis_Ababa",
		5	=> "Africa/Algiers",
		7	=> "Africa/Bamako",
		8	=> "Africa/Bangui",
		9	=> "Africa/Banjul",
		10	=> "Africa/Bissau",
		11	=> "Africa/Blantyre",
		12	=> "Africa/Brazzaville",
		13	=> "Africa/Bujumbura",
		14	=> "Africa/Cairo",
		15	=> "Africa/Casablanca",
		16	=> "Africa/Ceuta",
		17	=> "Africa/Conakry",
		18	=> "Africa/Dakar",
		19	=> "Africa/Dar_es_Salaam",
		20	=> "Africa/Djibouti",
		21	=> "Africa/Douala",
		22	=> "Africa/El_Aaiun",
		23	=> "Africa/Freetown",
		24	=> "Africa/Gaborone",
		25	=> "Africa/Harare",
		26	=> "Africa/Johannesburg",
		27	=> "Africa/Kampala",
		28	=> "Africa/Khartoum",
		29	=> "Africa/Kigali",
		30	=> "Africa/Kinshasa",
		31	=> "Africa/Lagos",
		32	=> "Africa/Libreville",
		33	=> "Africa/Lome",
		34	=> "Africa/Luanda",
		35	=> "Africa/Lubumbashi",
		36	=> "Africa/Lusaka",
		37	=> "Africa/Malabo",
		38	=> "Africa/Maputo",
		39	=> "Africa/Maseru",
		40	=> "Africa/Mbabane",
		41	=> "Africa/Mogadishu",
		42	=> "Africa/Monrovia",
		43	=> "Africa/Nairobi",
		44	=> "Africa/Ndjamena",
		45	=> "Africa/Niamey",
		46	=> "Africa/Nouakchott",
		47	=> "Africa/Ouagadougou",
		48	=> "Africa/Porto-Novo",
		49	=> "Africa/Sao_Tome",
		51	=> "Africa/Tripoli",
		52	=> "Africa/Tunis",
		53	=> "Africa/Windhoek",
		55	=> "America/Adak",
		56	=> "America/Anchorage",
		57	=> "America/Anguilla",
		58	=> "America/Antigua",
		59	=> "America/Araguaina",
		60	=> "America/Aruba",
		61	=> "America/Asuncion",
		63	=> "America/Barbados",
		64	=> "America/Belem",
		65	=> "America/Belize",
		66	=> "America/Boa_Vista",
		67	=> "America/Bogota",
		68	=> "America/Boise",
		69	=> "America/Argentina/Buenos_Aires",
		70	=> "America/Cambridge_Bay",
		71	=> "America/Cancun",
		72	=> "America/Caracas",
		73	=> "America/Argentina/Catamarca",
		74	=> "America/Cayenne",
		75	=> "America/Cayman",
		76	=> "America/Chicago",
		77	=> "America/Chihuahua",
		78	=> "America/Argentina/Cordoba",
		79	=> "America/Costa_Rica",
		80	=> "America/Cuiaba",
		81	=> "America/Curacao",
		82	=> "America/Danmarkshavn",
		83	=> "America/Dawson_Creek",
		84	=> "America/Dawson",
		85	=> "America/Denver",
		86	=> "America/Detroit",
		87	=> "America/Dominica",
		88	=> "America/Edmonton",
		89	=> "America/Eirunepe",
		90	=> "America/El_Salvador",
		93	=> "America/Fortaleza",
		94	=> "America/Glace_Bay",
		95	=> "America/Godthab",
		96	=> "America/Goose_Bay",
		97	=> "America/Grand_Turk",
		98	=> "America/Grenada",
		99	=> "America/Guadeloupe",
		100	=> "America/Guatemala",
		101	=> "America/Guayaquil",
		102	=> "America/Guyana",
		103	=> "America/Halifax",
		104	=> "America/Havana",
		105	=> "America/Hermosillo",
		106	=> "America/Indiana/Indianapolis",
		107	=> "America/Indiana/Knox",
		108	=> "America/Indiana/Marengo",
		109	=> "America/Indiana/Vevay",
		111	=> "America/Inuvik",
		112	=> "America/Iqaluit",
		113	=> "America/Jamaica",
		114	=> "America/Argentina/Jujuy",
		115	=> "America/Juneau",
		116	=> "America/Kentucky/Louisville",
		117	=> "America/Kentucky/Monticello",
		119	=> "America/La_Paz",
		120	=> "America/Lima",
		121	=> "America/Los_Angeles",
		123	=> "America/Maceio",
		124	=> "America/Managua",
		125	=> "America/Manaus",
		126	=> "America/Martinique",
		127	=> "America/Mazatlan",
		128	=> "America/Argentina/Mendoza",
		129	=> "America/Menominee",
		130	=> "America/Merida",
		131	=> "America/Mexico_City",
		132	=> "America/Miquelon",
		133	=> "America/Monterrey",
		134	=> "America/Montevideo",
		135	=> "America/Montreal",
		136	=> "America/Montserrat",
		137	=> "America/Nassau",
		138	=> "America/New_York",
		139	=> "America/Nipigon",
		140	=> "America/Nome",
		141	=> "America/Noronha",
		142	=> "America/North_Dakota/Center",
		143	=> "America/Panama",
		144	=> "America/Pangnirtung",
		145	=> "America/Paramaribo",
		146	=> "America/Phoenix",
		147	=> "America/Port_of_Spain",
		148	=> "America/Port-au-Prince",
		150	=> "America/Porto_Velho",
		151	=> "America/Puerto_Rico",
		152	=> "America/Rainy_River",
		153	=> "America/Rankin_Inlet",
		154	=> "America/Recife",
		155	=> "America/Regina",
		156	=> "America/Rio_Branco",
		158	=> "America/Santiago",
		159	=> "America/Santo_Domingo",
		160	=> "America/Sao_Paulo",
		161	=> "America/Scoresbysund",
		163	=> "America/St_Johns",
		164	=> "America/St_Kitts",
		165	=> "America/St_Lucia",
		166	=> "America/St_Thomas",
		167	=> "America/St_Vincent",
		168	=> "America/Swift_Current",
		169	=> "America/Tegucigalpa",
		170	=> "America/Thule",
		171	=> "America/Thunder_Bay",
		172	=> "America/Tijuana",
		173	=> "America/Tortola",
		174	=> "America/Vancouver",
		176	=> "America/Whitehorse",
		177	=> "America/Winnipeg",
		178	=> "America/Yakutat",
		179	=> "America/Yellowknife",
		180	=> "Antarctica/Casey",
		181	=> "Antarctica/Davis",
		182	=> "Antarctica/DumontDUrville",
		183	=> "Antarctica/Mawson",
		184	=> "Antarctica/McMurdo",
		185	=> "Antarctica/Palmer",
		187	=> "Antarctica/Syowa",
		188	=> "Antarctica/Vostok",
		191	=> "Asia/Aden",
		192	=> "Asia/Almaty",
		193	=> "Asia/Amman",
		194	=> "Asia/Anadyr",
		195	=> "Asia/Aqtau",
		196	=> "Asia/Aqtobe",
		197	=> "Asia/Ashgabat",
		199	=> "Asia/Baghdad",
		200	=> "Asia/Bahrain",
		201	=> "Asia/Baku",
		202	=> "Asia/Bangkok",
		203	=> "Asia/Beirut",
		204	=> "Asia/Bishkek",
		205	=> "Asia/Brunei",
		206	=> "Asia/Calcutta",
		207	=> "Asia/Choibalsan",
		208	=> "Asia/Chongqing",
		210	=> "Asia/Colombo",
		212	=> "Asia/Damascus",
		213	=> "Asia/Dhaka",
		214	=> "Asia/Dili",
		215	=> "Asia/Dubai",
		216	=> "Asia/Dushanbe",
		217	=> "Asia/Gaza",
		218	=> "Asia/Harbin",
		219	=> "Asia/Hong_Kong",
		220	=> "Asia/Hovd",
		221	=> "Asia/Irkutsk",
		223	=> "Asia/Jakarta",
		224	=> "Asia/Jayapura",
		225	=> "Asia/Jerusalem",
		226	=> "Asia/Kabul",
		227	=> "Asia/Kamchatka",
		228	=> "Asia/Karachi",
		229	=> "Asia/Kashgar",
		230	=> "Asia/Katmandu",
		231	=> "Asia/Krasnoyarsk",
		232	=> "Asia/Kuala_Lumpur",
		233	=> "Asia/Kuching",
		234	=> "Asia/Kuwait",
		236	=> "Asia/Magadan",
		237	=> "Asia/Manila",
		238	=> "Asia/Muscat",
		239	=> "Asia/Nicosia",
		240	=> "Asia/Novosibirsk",
		241	=> "Asia/Omsk",
		242	=> "Asia/Phnom_Penh",
		243	=> "Asia/Pontianak",
		244	=> "Asia/Pyongyang",
		245	=> "Asia/Qatar",
		246	=> "Asia/Rangoon",
		247	=> "Asia/Riyadh",
		251	=> "Asia/Saigon",
		252	=> "Asia/Sakhalin",
		253	=> "Asia/Samarkand",
		254	=> "Asia/Seoul",
		255	=> "Asia/Shanghai",
		256	=> "Asia/Singapore",
		257	=> "Asia/Taipei",
		258	=> "Asia/Tashkent",
		259	=> "Asia/Tbilisi",
		260	=> "Asia/Tehran",
		263	=> "Asia/Thimphu",
		264	=> "Asia/Tokyo",
		266	=> "Asia/Ulaanbaatar",
		268	=> "Asia/Urumqi",
		269	=> "Asia/Vientiane",
		270	=> "Asia/Vladivostok",
		271	=> "Asia/Yakutsk",
		272	=> "Asia/Yekaterinburg",
		273	=> "Asia/Yerevan",
		275	=> "Atlantic/Azores",
		276	=> "Atlantic/Bermuda",
		277	=> "Atlantic/Canary",
		278	=> "Atlantic/Cape_Verde",
		281	=> "Atlantic/Madeira",
		282	=> "Atlantic/Reykjavik",
		283	=> "Atlantic/South_Georgia",
		284	=> "Atlantic/St_Helena",
		285	=> "Atlantic/Stanley",
		287	=> "Australia/Adelaide",
		288	=> "Australia/Brisbane",
		289	=> "Australia/Broken_Hill",
		291	=> "Australia/Darwin",
		549 => 'Australia/Eucla',
		292	=> "Australia/Hobart",
		294	=> "Australia/Lindeman",
		295	=> "Australia/Lord_Howe",
		296	=> "Australia/Melbourne",
		299	=> "Australia/Perth",
		302	=> "Australia/Sydney",
		313	=> "Canada/Atlantic",
		314	=> "Canada/Central",
		315	=> "Canada/Eastern",
		316	=> "Canada/East-Saskatchewan",
		317	=> "Canada/Mountain",
		318	=> "Canada/Newfoundland",
		319	=> "Canada/Pacific",
		320	=> "Canada/Saskatchewan",
		321	=> "Canada/Yukon",
		327	=> "CST",
		328	=> "CST6CDT",
		330	=> "Cuba",
		334	=> "Egypt",
		335	=> "Eire",
		336	=> "EST",
		337	=> "EST5EDT",
		373	=> "Europe/Amsterdam",
		374	=> "Europe/Andorra",
		375	=> "Europe/Athens",
		377	=> "Europe/Belgrade",
		378	=> "Europe/Berlin",
		380	=> "Europe/Brussels",
		381	=> "Europe/Bucharest",
		382	=> "Europe/Budapest",
		383	=> "Europe/Chisinau",
		384	=> "Europe/Copenhagen",
		385	=> "Europe/Dublin",
		386	=> "Europe/Gibraltar",
		387	=> "Europe/Helsinki",
		388	=> "Europe/Istanbul",
		389	=> "Europe/Kaliningrad",
		390	=> "Europe/Kiev",
		391	=> "Europe/Lisbon",
		393	=> "Europe/London",
		394	=> "Europe/Luxembourg",
		395	=> "Europe/Madrid",
		396	=> "Europe/Malta",
		397	=> "Europe/Minsk",
		398	=> "Europe/Monaco",
		399	=> "Europe/Moscow",
		401	=> "Europe/Oslo",
		402	=> "Europe/Paris",
		403	=> "Europe/Prague",
		404	=> "Europe/Riga",
		405	=> "Europe/Rome",
		406	=> "Europe/Samara",
		409	=> "Europe/Simferopol",
		411	=> "Europe/Sofia",
		412	=> "Europe/Stockholm",
		413	=> "Europe/Tallinn",
		414	=> "Europe/Tirane",
		416	=> "Europe/Uzhgorod",
		417	=> "Europe/Vaduz",
		419	=> "Europe/Vienna",
		420	=> "Europe/Vilnius",
		421	=> "Europe/Warsaw",
		423	=> "Europe/Zaporozhye",
		424	=> "Europe/Zurich",
		427	=> "GMT",
		428	=> "GMT0",
		429	=> "Greenwich",
		430	=> "Hongkong",
		432	=> "Iceland",
		434	=> "Indian/Antananarivo",
		435	=> "Indian/Chagos",
		436	=> "Indian/Christmas",
		437	=> "Indian/Cocos",
		438	=> "Indian/Comoro",
		439	=> "Indian/Kerguelen",
		440	=> "Indian/Mahe",
		441	=> "Indian/Maldives",
		442	=> "Indian/Mauritius",
		443	=> "Indian/Mayotte",
		444	=> "Indian/Reunion",
		445	=> "Iran",
		446	=> "Israel",
		448	=> "Jamaica",
		449	=> "Japan",
		451	=> "Kwajalein",
		452	=> "Libya",
		461	=> "MST",
		462	=> "MST7MDT",
		465	=> "NST",
		468	=> "Pacific/Apia",
		469	=> "Pacific/Auckland",
		470	=> "Pacific/Chatham",
		471	=> "Pacific/Easter",
		472	=> "Pacific/Efate",
		473	=> "Pacific/Enderbury",
		474	=> "Pacific/Fakaofo",
		475	=> "Pacific/Fiji",
		476	=> "Pacific/Funafuti",
		477	=> "Pacific/Galapagos",
		478	=> "Pacific/Gambier",
		479	=> "Pacific/Guadalcanal",
		480	=> "Pacific/Guam",
		481	=> "Pacific/Honolulu",
		482	=> "Pacific/Johnston",
		483	=> "Pacific/Kiritimati",
		484	=> "Pacific/Kosrae",
		485	=> "Pacific/Kwajalein",
		486	=> "Pacific/Majuro",
		487	=> "Pacific/Marquesas",
		488	=> "Pacific/Midway",
		489	=> "Pacific/Nauru",
		490	=> "Pacific/Niue",
		491	=> "Pacific/Norfolk",
		492	=> "Pacific/Noumea",
		493	=> "Pacific/Pago_Pago",
		494	=> "Pacific/Palau",
		495	=> "Pacific/Pitcairn",
		496	=> "Pacific/Ponape",
		497	=> "Pacific/Port_Moresby",
		498	=> "Pacific/Rarotonga",
		499	=> "Pacific/Saipan",
		500	=> "Pacific/Samoa",
		501	=> "Pacific/Tahiti",
		502	=> "Pacific/Tarawa",
		503	=> "Pacific/Tongatapu",
		504	=> "Pacific/Truk",
		505	=> "Pacific/Wake",
		506	=> "Pacific/Wallis",
		510	=> "Poland",
		511	=> "Portugal",
		514	=> "PST",
		515	=> "PST8PDT",
		517	=> "Singapore",
		532	=> "Turkey",
		535	=> "US/Alaska",
		536	=> "US/Aleutian",
		537	=> "US/Arizona",
		538	=> "US/Central",
		539	=> "US/Eastern",
		540	=> "US/East-Indiana",
		541	=> "US/Hawaii",
		543	=> "US/Michigan",
		544	=> "US/Mountain",
		545	=> "US/Pacific",
		547	=> "US/Samoa",
		548	=> "UTC",
		550	=> "Africa/Asmara",
		551	=> "America/Argentina/La_Rioja",
		552	=> "America/Argentina/Rio_Gallegos",
		553	=> "America/Argentina/San_Juan",
		554	=> "America/Argentina/Tucuman",
		555	=> "America/Argentina/Ushuaia",
		556	=> "America/Atikokan",
		557	=> "America/Bahia",
		558	=> "America/Blanc-Sablon",
		559	=> "America/Campo_Grande",
		560	=> "America/Indiana/Petersburg",
		561	=> "America/Indiana/Vincennes",
		562	=> "America/Moncton",
		563	=> "America/North_Dakota/New_Salem",
		564	=> "America/Toronto",
		565	=> "Antarctica/Rothera",
		566	=> "Asia/Macau",
		567	=> "Asia/Makassar",
		568	=> "Asia/Oral",
		569	=> "Asia/Qyzylorda",
		570	=> "Atlantic/Faroe",
		571	=> "Australia/Currie",
		572	=> "Europe/Volgograd",
	};
end
