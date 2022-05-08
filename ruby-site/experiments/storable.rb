require "var_dump";
#This module uses reflection to store and load objects from a database
#To include this module you must define YourClass.storable_db <DBI::Handler> and
#YourClass.storable_table <String>
module Storable
	require "dbi"
	require "mixin_class_methods";
	
	@@storable_class_variables = Hash.new;
	
	#Checks to ensure that klass.storable_db and klass.storable_table are public methods
	mixin_class_methods { |klass|
	};
	
	#Define class methods in the including class
	define_class_methods {
		#Handles methods of the format load_all_by_<column1>_<column2>_...(val1, val2, ...)
		def method_missing(method, *args)
			if (method.to_s =~ /^load_all_by_(.+)/)
				match_columns = $1.split("_");
				load_set(match_columns, args);
			else
				super(method);
			end
		end
		
		#Creates a new object for every matching database row and returns them in an array
		def load_set(match_columns=Array.new, values=Array.new)
			if (match_columns.length != values.length)
				raise "Number of columns to be matched is not equal to number of values to match.";
			end
			
			where_string = nil;
			match_columns.each_index { |i|
				if (!storable_var('columns').has_key?(match_columns[i]))
					raise "Column '#{match_columns[i]}' does not exist in '#{self.class.storable_table}'";
				end
				if (!where_string)
					where_string = "#{match_columns[i]} = '#{values[i]}'"
				else
					where_string += "&& #{match_columns[i]} = '#{values[i]}'";
				end
			}
			
			objects = Array.new;
			storable_db.select_all("SELECT * FROM #{storable_table} WHERE #{where_string}") { |result|
				obj = self.new;
				columns = storable_var('columns');
				columns.each_key { |key|
					obj.instance_variable_set("@"+key, result[columns[key]]);
				}
				objects << obj;
			}
			return objects;
		end
		
		def load_all_where(where_clause)
			objects = Array.new;
			storable_db.select_all("SELECT * FROM #{storable_table} WHERE #{where_clause}") { |result|
				obj = self.new;
				columns = storable_var('columns');
				columns.each_key { |key|
					obj.instance_variable_set("@"+key, result[columns[key]]);
				}
				objects << obj;
			}
			return objects;
		end
		
		def storable_initialize(db, table)
			if (!self.kind_of?(Class))
				raise "storable_initialize can only be called by a class object.";
			end
			@@storable_class_variables[self] = { 'db' => db, 'table' => table, 'columns' => Hash.new }
			
			db.columns(table).each_with_index { |column, i|
				@@storable_class_variables[self]['columns'][column['name']] = i;
				module_eval("attr :#{column['name']}, true");
			}
		end
		
		

		def storable_db
			return storable_var('db');
		end
		def storable_table
			return storable_var('table');
		end
		def storable_var(name)
			self.ancestors.each { |klass|
				if (@@storable_class_variables.has_key?(klass))
					return @@storable_class_variables[klass][name];
				end
			}
			return nil;
		end
	}
	
	
	#Saves all instance variables not prefixed with @ignore_ to the database.
	def store()
		replace_string = nil;
		instance_variables.each { |var|
			name = var[1,var.length]; #remove the @
			if (self.class.storable_var('columns').has_key?(name))
				if (!replace_string)
					replace_string = "#{name} = '#{instance_variable_get(var)}'"
				else
					replace_string += ", #{name} = '#{instance_variable_get(var)}'";
				end
			end
		}
		#puts("REPLACE INTO #{self.class.storable_table} SET #{replace_string}");
		self.class.storable_db.do("REPLACE INTO #{self.class.storable_table} SET #{replace_string}");
	end
	
	#Map any load_by_ method call to the load function.
	def method_missing(method, *args)
		if (method.to_s =~ /^load_by_(.+)!/)
			match_columns = $1.split("_");
			load!(match_columns, args);
		else
			super(method);
		end
	end
	
	#Load an objects stored instance variables from the database.
	def load!(match_columns=Array.new, values=Array.new)
		if (match_columns.length != values.length)
			raise "Number of columns to be matched is not equal to number of values to match.";
		end
		
		where_string = nil;
		match_columns.each_index { |i|
			if (!self.class.storable_var('columns').has_key?(match_columns[i]))
				raise "Column '#{match_columns[i]}' does not exist in '#{self.class.storable_table}'";
			end
			if (!where_string)
				where_string = "#{match_columns[i]} = '#{values[i]}'"
			else
				where_string += "&& #{match_columns[i]} = '#{values[i]}'";
			end
		}
		
		#puts("SELECT * FROM #{self.class.storable_table} WHERE #{where_string}");
		result = self.class.storable_db.select_one("SELECT * FROM #{self.class.storable_table} WHERE #{where_string}");
		if (result)
			columns = self.class.storable_var('columns');
			columns.each_key { |key|
				instance_variable_set("@"+key, result[columns[key]]);
			}
			return self;
		else
			columns = self.class.storable_var('columns');
			columns.each_key { |key|
				instance_variable_set("@"+key, nil);
			}
			return false;
		end
	end	
end


class TestStorable
	include(Storable);
	storable_initialize(DBI.connect('DBI:Mysql:rubytest:192.168.0.50', "root", "Hawaii"), "test1");
	
	def initialize
		@var1 = 5;
		@var3 = 5;
		@ignore_bob = "bob";
		@var2 = 5;
		@var9 = 9;
	end
end