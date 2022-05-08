#This class represents a list of columns that will be selected from a particular table.
class StorableSelection
	attr_reader(:symbol, :columns, :sql);
	def initialize(symbol, *columns)
		@symbol = symbol;
		@columns = columns.flatten;
	end
	
	#retrieve an sql SELECT string.  eg. "SELECT `id`, `name` "
	def sql
		columns = columns.map{|col| "`#{col}`"}
		return " #{columns.join(', ')} ";
	end
	
	def valid_column?(name)
		return columns.include?(name.to_sym);
	end
	
end
