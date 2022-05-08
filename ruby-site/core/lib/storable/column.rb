lib_require :Core, "data_structures/enum"
#Column acts as a type bridge between SQL and Ruby and also stores column info.
class Column
	attr_reader(:name, :default, :primary, :full_type, :nullable, :extra, :type_name, :key, :enum_symbols);
	#Takes a row from SqlDB#fetch_fields(table) as a constructor.
	def initialize(column_info)
		@name = column_info['Field'];
		@primary = column_info['Key'] == 'PRI';
		@nullable = column_info['Null'] != 'NO';
		@extra = column_info['Extra'];
		/^(\w+)(\(.*\))?.*$/ =~ column_info['Type'];
		@type_name = $1;
		@full_type = column_info['Type'];
		@default = column_info['Default'];
		@key = column_info['Key'];
		if (@type_name == "enum")
			@enum_symbols = Enum.parse_type(self.full_type);
		end
	end

	#Transforms a string into the column's corresponding ruby type.
	def parse_string(string)
		return self.send(:"#{self.type_name}", string);
	end

	#Is the column's ruby type boolean?
	def boolean?()
		if (enum_symbols.length == 2 && enum_symbols.include?('n') && enum_symbols.include?('y'))
			return true;
		elsif (enum_symbols.length == 1 && enum_symbols.include?('y'))
			return true;
		else
			return false;
		end
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
		if (boolean?())
			if (string == "y")
				return true;
			else
				return false;
			end
		else
			return string;
		end
	end
end
