#This class represents a list of columns that will be selected from a particular table.
class StorableSelection
	attr_reader(:symbol, :columns, :sql);
	def initialize(symbol, *columns)
		@symbol = symbol;
		@columns = {}
		columns.flatten.each{|c|@columns[c] = true};

	#retrieve an sql SELECT string.  eg. "SELECT `id`, `name` "
		@sql = " `" + @columns.keys.join('`, `') + "` "
	end

	def valid_column?(name)
		return @columns[name.to_sym];
	end
	
end
