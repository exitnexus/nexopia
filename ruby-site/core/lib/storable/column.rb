lib_require :Core, "data_structures/enum"
#Column acts as a type bridge between SQL and Ruby and also stores column info.
class Column
	attr_reader(:name, :default, :default_value, :primary, :full_type, :nullable, :extra, :type_name, :key, :enum_symbols);
	attr_reader :sym_name, :sym_ivar_name, :sym_ivar_equ_name
	#Takes a row from SqlDB#fetch_fields(table) as a constructor.
	def initialize(column_info, enum_map = nil)
		@name = column_info['Field'];
		@sym_name = @name.to_sym
		@sym_ivar_name = "@#{@name}".to_sym
		@sym_ivar_equ_name = "@#{@name}=".to_sym

		@primary = column_info['Key'] == 'PRI';
		@nullable = column_info['Null'] != 'NO';
		@extra = column_info['Extra'];
		/^(\w+)(\(.*\))?.*$/ =~ column_info['Type'];
		@type_name = $1;
		@type_name_sym = "#{@type_name}".to_sym
		@type_name_column_sym = "#{@type_name}_column".to_sym

		@full_type = column_info['Type'];
		@default = column_info['Default'];
		@key = column_info['Key'];

		if (@type_name == "enum")
			@enum_symbols = Enum.parse_type(self.full_type);
			boolean? #preinitialize this because we just do a direct variable access later and it needs to be set
		end
		@enum_map = enum_map

		@default_value = parse_string(@default);
	end

	BOOL_Y = 'y'
	BOOL_N = 'n'

	AUTO_INCR = "auto_increment"

	def auto_increment?
		@extra == AUTO_INCR
	end

	def has_enum_map?
		return @enum_map
	end
	
	#Transforms a string into the column's corresponding ruby type.
	def parse_string(string)
		if (@enum_map.nil?)
			return self.send(@type_name_sym, string);
		else
			return enum_map(self.send(@type_name_sym, string));
		end
	end
	
	def default_ignore_enum
		return self.send(@type_name_sym, @default);
	end

	#Transforms a string into the column's corresponding pure-ruby type.
	def parse_column(string)
		if (@enum_map.nil?)
			return self.send(@type_name_column_sym, string) if self.respond_to?(@type_name_column_sym)
			return self.send(@type_name_sym, string);
		else
			return enum_map_column(self.send(@type_name_sym, string));
		end
	end

	#Is the column's ruby type boolean?
	def boolean?()
		return @boolean unless @boolean.nil?

		if (enum_symbols.length == 2 && enum_symbols.include?(BOOL_N) && enum_symbols.include?(BOOL_Y))
			@boolean = true;
		elsif (enum_symbols.length == 1 && enum_symbols.include?(BOOL_Y))
			@boolean = true;
		else
			@boolean = false;
		end
		return @boolean
	end

	private
	def varchar(string)
		return string
	end
	def tinyint(string)
		return string.to_i;
	end
	def text(string)
		return string;
	end
	def date(string)
		method_missing();
	end
	def smallint(string)
		return string.to_i;
	end
	def mediumint(string)
		return string.to_i;
	end
	def int(string)
		return string.to_i;
	end
	def bigint(string)
		return string.to_i;
	end
	def float(string)
		return string.to_f;
	end
	def double(string)
		return string.to_f;
	end
	def decimal(string)
		return string.to_f;
	end
	def datetime(string)
		method_missing();
	end
	def timestamp(string)
		return string.to_i;
	end
	def time(string)
		method_missing();
	end
	def year(string)
		return string.to_i;
	end
	def char(string)
		return string;
	end
	def tinyblob(string)
		return string;
	end
	def tinytext(string)
		return string;
	end
	def blob(string)
		return string;
	end
	def mediumblob(string)
		return string;
	end
	def mediumtext(string)
		return string;
	end
	def longblob(string)
		return string;
	end
	def longtext(string)
		return string;
	end
	def enum(string)
		if (@boolean)
			return Boolean.new((string == BOOL_Y));
		else
			return Enum.new(string, @enum_symbols);
		end
	end
	def enum_column(string)
		if (@boolean)
			return (string == BOOL_Y)
		else
			return string;
		end
	end
	def enum_map(string)
		return EnumMap.new(string, @enum_map);
	end
	def enum_map_column(string)
		return string
	end
end
