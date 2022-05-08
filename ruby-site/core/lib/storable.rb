require "var_dump"
require "class_attr"
require "dbi"

class Storable
	@@subclasses = {};
	
	class_attr_reader(:db, :table, :columns)
	class_attr_writer(:db, :table, :columns)
	
	self.db = "not_a_db";
	self.table = "not_a_table"
	
	class << self
		def inherited(child)
			@@subclasses[self] ||= []
			@@subclasses[self] << child
      		super
		end
		
		def subclasses
			return @@subclasses[self];
		end
		
		def find(*args)
			options = extract_options_from_args!(args);
			case args.first
			when :all
				puts "All!";
			when :first
				puts "First!";
			when :by_id
				puts "ID Set!";
			else
				puts "Default to something!";
			end
		end
		
		
		protected ############BEGIN PROTECTED METHODS#############
		def extract_options_from_args!(args) #:nodoc:
			return (args.last.is_a?(Hash) ? args.pop() : {})
		end
		
		def initialize_columns
			query= "DESCRIBE #{table} 'enumtest'"
			result = db.execute(query)
			result.each { |r|
				r.var_dump();
			}
			class_attr(:columns, true);
			self.columns = Hash.new;
			db.columns(table).each_with_index { |column, i|
				self.columns[column['name']] = i;
				#column.var_dump();
				attr(:"#{column['name']}", true);
			}
		end
		
		def set_db(new_db)
			class_attr(:db, true);
			self.db = new_db;
		end
		
		def set_table(new_table)
			class_attr(:table, true);
			self.table = new_table;
		end
	end
end

class TestStorable < Storable
	set_db(DBI.connect('DBI:Mysql:rubytest:192.168.0.50', "root", "Hawaii"));
	set_table("test1");
	initialize_columns
	#columns.var_dump();
end

class TestStorable2 < Storable
	#self.db.var_dump();
end


#Storable.find(:by_id, 1, :conditions => "administrator = 1", :order => "created_on DESC")
#TestStorable.subclasses.var_dump();
#puts TestStorable.bob
#puts TestStorable2.bob