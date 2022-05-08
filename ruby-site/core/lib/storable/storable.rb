lib_require :Core, "var_dump", "attrs/class_attr", 'storable/column', 'storable/storable_selection', "attrs/enum_attr", "attrs/enum_map_attr", "attrs/bool_attr", "lazy", 'data_structures/ordered_map', 'data_structures/paged_result', 'storable/user_content'
lib_require :Core, 'storable/monitored_content', 'storable/relation_manager', 'storable/storable_proxy', 'storable/storable_id'

require 'set'
require 'mocha'
require 'rexml/document' #needed for dclone

class Hash
	def dclone
		klone = self.clone
		klone.clear
		self.each_key{|k| klone[k.dclone] = self[k].dclone}
		klone
	end
end
class Bignum; def dclone; self; end; end
class NilClass; def dclone; self; end; end
class TrueClass; def dclone; self; end; end;
class FalseClass; def dclone; self; end; end;
class Lazy::Promise; def dclone; self; end; end;

#Storable wraps a single row of a table.  It is intended to be subclassed for each database table in use.
#A basic implementation should be similar to:
#  class SomeTable < Storable
#      init_storable(<database handle>, <table name>);
#
#      def created()
#          ...do something when an object is inserted into the database...
#      end
#
#      def updated()
#          ...do something when an object changes in the database...
#      end
#
#      def deleted()
#         ...do something when an object is deleted from the database...
#      end
#
#      ...the rest of your class here...
#  end
#
#Rows can be retrieved using Storable.find(), limited selections that retrieve only a subset of columns can be defined
#using register_selection().
class Storable
	public

	extend UserContent
	extend MonitoredContent
	
	def self.report(*args)
		#intentionally left blank, just captures and ignores calls to setup reporting
		#when reporter.rb is loaded it extends Scoop::Reporter which overrides this
	end
	
	DEFAULT_PAGE_LENGTH = 25
	
	#valid entries here are :insert, and :update
	attr_accessor(:update_method, :insert_id, :affected_rows, :total_rows, :cache_points, :selection, :add_to_cache)

	@@subclasses = {};
	@@__cache__ = {};
	#Create a new object. If +set_default+ is true, then initialize all of its 
	#column attributes to the default column values.
	def initialize(set_default = true)

		self.update_method = :insert;
		
		if (set_default)
			self.class.columns.each_value {|column|
				# Need to create a brand new Enum for Enum columns because if we use the default_value Enum, only the 
				# pointer to that Enum will be stored in the new object (meaning a change to the new object's value 
				# would affect future default settings).
				if(column.default_value.kind_of?(Enum))
					# Need to check whether we're setting an Enum or EnumMap
					if (self.respond_to?(:"#{column.name}!"))
						instance_variable_set(column.sym_ivar_name, EnumMap.new(column.default_value.symbol, column.default_value.hash));
					else
						instance_variable_set(column.sym_ivar_name, Enum.new(column.default_value.symbol, column.default_value.symbols));
					end
				else
					instance_variable_set(column.sym_ivar_name, column.default_value);					
				end
			}
		end
	end

	#Default prefix is the name of the class.
	def prefix()
		return self.class.prefix;
	end

	def to_s
		return "#{self.class}[#{[*self.get_primary_key].join(',')}]"
	end

	#call the getter for an extra column to be added to the cache
	def get_extra_cache_column(column)
		return extra_cache_columns[column][:get].bind(self).call
	end
	#call the setter for an extra column to be added to the cache
	def set_extra_cache_column(column, value)
		return extra_cache_columns[column][:set].bind(self).call(value)
	end
	
	#a hash of column name to original value, it will potentially
	#have missing keys for columns which haven't changed.
	def __modified__
		@__modified__ = {} unless @__modified__
		return @__modified__
	end
	
	#an array of columns that have been modified
	def modified_columns
		#If this is a new object then all columns are modified
		if (self.update_method == :insert)
			return self.columns.keys
		else
			return self.__modified__.keys.select {|key| self.modified?(key)}
		end
	end

	#Checks to see if column has been modified since it was retrieved from the database.
	#
	# This was changed on Mar 3, 2009 to return true/false values only (with the use of a column)
	#  depending on the existence of the key in the hash. It used to return the value stored in the
	#  hash by that column. This was a column when the stored value was nilClass or false. With modified?
	#  being used entirely in conditionals this caused weird problems. The contents haven't changed, just 
	#  the return value. Code review revealed nothing directly modifying this hash outside of the Storable
	#  subsystem and nothing using the return value of this function for anything more than conditional statements.
	#
	# It is strongly advised that you do not directly modify the __modified__ hash as it could have
	#  weird side effects. If you are experiencing problems with UPDATEs check to make sure the correct
	#  value is returned for the requested column from this function.
	def modified?(column=nil)
		if (column)
			return __modified__.has_key?(column.to_sym);
		else
			return !__modified__.values.compact.empty?
		end
	end
	
	#returns a new storable instance in the form that this object had when it was first loaded
	def original_version
		original_version = self.class.new
		self.columns.keys.each {|column|
			if (modified?(column))
				original_version.instance_variable_set(:"@#{column}", __modified__[column])
			else
				original_version.instance_variable_set(:"@#{column}", instance_variable_get(:"@#{column}"))
			end
		}
		return original_version
	end
	
	def hash
		return self.get_primary_key.hash
	end

	def eql?(obj)
		return self.class == obj.class && self == obj
	end
	
	def created?
		return self.update_method != :insert
	end
	
	#Sets every column to report as unmodified.
	def clear_modified!
		@__modified__.clear if @__modified__
	end

	def storable_id
		self.class::StorableID.new([*self.get_primary_key], :PRIMARY, self.selection)
	end

	#This function should be overriden in any subclass that can be associated with a ticket
	#It should provide a text representation of that object, particularly any field which may
	#later be changed.
	def ticket_notes
		return "";
	end


	class_attr_accessor(:db, :table, :columns, :seq_initial_value, :enums)

	self.db = "not_a_db";
	self.table = "not_a_table"
	self.seq_initial_value = 1;
	self.enums = {};

	#Save the object to the database.  The attribute update_method is checked to determine how to do this.
	#
	# When using the :duplicate option the operation is considered an update. As such, the before_update and
	#  after_update functions will be called on the object. Since we don't know before hand what operation
	#  we need to assume one of the two operations and update is it. 
	def store(*args)
		options = self.class.extract_options_from_args!(args);
		
		cols = "";
		incr_cols = "";
		variables = Array.new;
		incr_variables = Array.new();
		
		if(options[:duplicate])
			dup_update = true;
			self.update_method = :update;
		else
			dup_update = false;
		end
				
		if(options[:increment])
			incr_update = true;
		else
			incr_update = false;
		end
		
		case update_method
		when :insert
			before_create();
			self.invalidate_cache_keys(false)
			RelationManager.invalidate_store(self)
			increment_column = nil;
			self.class.columns.each_value {|column|
				cols += "`#{column.name}`";
				cols += " = ";
				cols += (self.class.split_column?(column.name) ? "#" : "?");
				cols += ", ";
				if (self.respond_to?(:"#{column.name}!"))
					variables << self.send(:"#{column.name}!").value;
				else
					variables << self.send(column.sym_name);
				end
				if (column.auto_increment?)
					increment_column = column.name;
					options[:insert_id] = true;
				end
			}
			cols.chomp!(", ");

			if (options[:ignore])
				ignore = " IGNORE";
			else
				ignore = "";
			end
			
			sql = "INSERT #{ignore} INTO `#{self.table}` SET #{cols} ";
			sql_result = self.db.query(sql, *variables);
	
			self.update_method = :update;
			if (options[:insert_id])
				@insert_id = sql_result.insert_id();
				if (increment_column)
					self.send(:"#{increment_column}=", self.insert_id);
				end
			end
			if (options[:affected_rows])
				@affected_rows = sql_result.affected_rows();
			end
			self.class.cache_result(self.storable_id, self)
			after_create();
		when :update
			before_update();
			self.invalidate_cache_keys(true)
			RelationManager.invalidate_store(self)
			if (modified?)
				self.class.columns.each_value {|column|
					if (self.modified?(column.sym_name))
						cols += "`#{column.name}`";
						cols += " = ";
						cols += (self.class.split_column?(column.name) ? "#" : "?");
						cols += ", ";
						if (self.respond_to?(:"#{column.name}!"))
							variables << self.send(:"#{column.name}!").value;
						else
							variables << self.send(column.sym_name);
						end
					end
				}
				
				cols.chomp!(", ");
				
				if(dup_update)
					if(incr_update)
						self.class.columns.each_value {|column|
							if(options[:increment][0] == column.sym_name)
								incr_cols += "`#{column.name}`";
								incr_cols += " = `#{column.name}` + #{options[:increment][1]}";
								incr_cols += ", ";
							elsif (self.modified?(column.sym_name) && !column.primary)
								incr_cols += "`#{column.name}`";
								incr_cols += " = ";
								incr_cols += (self.class.split_column?(column.name) ? "#" : "?");
								incr_cols += ", ";
								if (self.respond_to?(:"#{column.name}!"))
									incr_variables << self.send(:"#{column.name}!").value;
								else
									incr_variables << self.send(column.sym_name);
								end
							end
						}
						incr_cols.chomp!(", ");
					else
						incr_cols = cols;
					end
					
					sql = "INSERT INTO `#{self.table}` SET #{cols} ON DUPLICATE KEY UPDATE #{incr_cols}";
				else
					sql = "UPDATE #{self.table} SET #{cols} WHERE "
				
					primary_key.each { |key|
						variables << self.send(:"#{key}");
						sql << " #{key} = "
						sql << (self.class.split_column?(key) ? "#" : "?");
						sql << " && ";
					}
					if (options[:conditions])
						if (options[:conditions].kind_of?(Array))
							sql << " #{options[:conditions].shift}";
							variables += options[:conditions];
						else
							sql << " #{options[:conditions]}";
						end
					end
					sql.chomp!("&& ");
				end
				
				if(dup_update)
					if(incr_update)
						variables = variables + incr_variables;
					else
						variables = variables + variables;
					end
				end
				
				sql_result = self.db.query(sql, *variables);
				if (options[:affected_rows])
					@affected_rows = sql_result.affected_rows();
				end
			end
			after_update();
		else
			raise "Unsupported update method: #{update_method}";
		end
	end

	def prime_extra_columns
		self.class.extra_cache_columns.each_key {|name|
			self.get_extra_cache_column(name.to_sym)
		}
	end
	
	def _dump(depth)
		values = {}

		(self.selection || self.class).columns.each_key {|name|
			values[name.to_sym] = self.instance_variable_get(:"@#{name.to_sym}")
		}

		values.each{|k,v|
			values[k] = String.new(v) if (v.kind_of?(UserContent::UserContentString) && v.kind_of?(String))
		}
		self.class.extra_cache_columns.each_key {|name|
			values[name.to_sym] = self.get_extra_cache_column(name.to_sym)
		}
		values[:__update_method__] = self.update_method if(self.update_method != :update)
		values[:__selection__] = self.selection.symbol if(self.selection)

		dump_str = Marshal.dump(values)
		return dump_str
	end
	
	#Delete the object from local cache and the database.
	def delete(*args)
		
		options = self.class.extract_options_from_args!(args);

		case update_method
		when :insert, :update
			before_delete();
			self.invalidate_cache_keys(false)
			RelationManager.invalidate_delete(self)
			if (primary_key.length > 0)
				sql = "DELETE FROM `#{self.table}` WHERE "
				primary_key.each { |key|
					if (self.class.split_column?(key))
						sql << " `#{key}` = # && ";
					else
						sql << " `#{key}` = ? && ";
					end
				}
				sql.chomp!("&& ");
				sql_result = self.db.query(sql, *get_primary_key);
			else
				#If there is no primary key, delete by matching all columns
				cols = "";
				variables = Array.new;
				self.class.columns.each_value {|column|
					cols += "`#{column.name}`";
					cols += " = ";
					cols += "?";
					cols += " AND ";
					variables << self.send(column.sym_name);
				}
				cols.chomp!(" AND ");
				
				sql_result = self.db.query("DELETE FROM `#{self.table}` WHERE #{cols}", *variables);
			end
			if (options[:affected_rows])
				@affected_rows = sql_result.affected_rows();
			end
			after_delete();
		else
			raise "Unsupported update method: #{update_method}";
		end
	end

	def trigger_event_hook(event)
		if (self.class.event_hooks[event])
			self.class.event_hooks[event].each {|hook|
				self.instance_exec(&hook)
			}
		end
	end

	#Called before a new row is inserted into the database.
	def before_create()
		trigger_event_hook(:before_create)
		trigger_event_hook(:before_store)
	end

	#Called after a new row is inserted into the database.
	def after_create()
		trigger_event_hook(:after_create)
		trigger_event_hook(:after_store)
	end

	#Called before a row is updated in the database.
	def before_update();
		trigger_event_hook(:before_store)
		trigger_event_hook(:before_update)
	end
	#Called after a row is updated in the database
	def after_update();
		trigger_event_hook(:after_update)
		trigger_event_hook(:after_store)
	end

	#Called before a row is deleted from the database.
	def before_delete();
		trigger_event_hook(:before_delete)
	end
	#Called after a row is deleted from the database.
	def after_delete();
		trigger_event_hook(:after_delete)
	end

	#Called after a row is loaded from the database.
	def after_load()
		trigger_event_hook(:after_load)
	end

	#Called everytime any column is changed, after the change.
	def on_field_change(column_name)
	end

	def invalidate_cache_keys(use_modification_state)
		if (use_modification_state) #take only modified columns into consideration for invalidation
			self.class.internal_cache.delete_if {|key,val|
				key.match_modified?(self)
			}
		else #ignore a columns modification state and just match
			self.class.internal_cache.delete_if {|key,val|
				key === self
			}
		end
	end

	def primary_key
		return self.class.primary_key;
	end

	#returns the primary key if it is individual or the array or primary keys if there are multiple.
	def get_primary_key()
		if (primary_key.length == 1)
			return self.send(primary_key.first)
		else
			return primary_key.map { |key| self.send(key) }
		end
	end

	#compares the equality of all columns
	def ==(obj)
		return false unless obj.kind_of?(Storable);
		self.class.columns.each_key{|column|
			return false unless (obj.respond_to?(column))
			return false unless (obj.send(column) == self.send(column))
		}
		return true;
	end

	#compares the equality of primary keys
	def ===(obj)
		return false unless (obj.respond_to?(:get_primary_key));
		return self.get_primary_key() == obj.get_primary_key();
	end

	class << self
		public
		
		def test_reset
			self.db.query("TRUNCATE `#{self.table}`")
			self.internal_cache.delete_if {|key, val| true}
			RelationManager.test_reset(self)
		end
		
		#Default prefix is the name of the class.
		def prefix()
			return self.name;
		end

		def _load(str)
			values = Marshal.load(str)

			storable = self.new(false)
			storable.update_method = values[:__update_method__] || :update
			storable.selection = self.get_selection(values[:__selection__]) if values[:__selection__]

			(storable.selection || self).columns.each_key { |name|
				storable.instance_variable_set(columns[name].sym_ivar_name, values[name])
			}
			extra_cache_columns.each_key{|name|
				storable.set_extra_cache_column(name, values[name])
			}

			return storable
		end
		
		def register_event_hook(event, &block)
			self.event_hooks[event] ||= []
			self.event_hooks[event].push(block)
		end
		
		#Wraps the call to new, this allows children classes to override create and return an object of a different
		#class if they have need.
		def storable_new(*args)
			return self.new(*args)
		end

		#returns the column names of the primary key
		def primary_key
			return indexes[:PRIMARY];
		end

		#registers the inherited class in the subclasses hash tree
		def inherited(child)
			@@subclasses[self] ||= []
			@@subclasses[self] << child
			#This is magic that sets up SomeDescendantOfStorable::StorableID::StorableClass to be SomeDescendantOfStorable,
			#and the same for StorableProxy
			child.const_set("StorableID", Class.new(StorableID))
			child::StorableID.const_set("StorableClass", child)
			child.const_set("StorableProxy", Class.new(StorableProxy))
			child::StorableProxy.const_set("StorableClass", child)
			super
		end
		
		def create_id(id, index, selection)
			return self::StorableID.new(id, index, selection)
		end

		#a nested hash of subclasses of the current class
		def subclasses
			return @@subclasses[self];
		end

		#Register either a symbol and list of columns or a custom created StorableSelection object.
		def register_selection(symbol, *cols)
			if (symbol.kind_of?(StorableSelection))
				selection = symbol;
			else
				selection = StorableSelection.new(symbol, *cols);
			end
			self.storable_selections[selection.symbol] = selection;
		end

		#look in current class and parent classes to see if the symbol has been registered anywhere as a selection
		def get_selection(symbol)
			selection = self.storable_selections[symbol];

			if(!selection && self.class.superclass.method_defined?(:get_selection))
				selection = super(symbol)
			end

			return selection;
		end

		#does a selection contain all of the primary key columns for this table?
		def primary_key_selection?(symbol)
			if (symbol.kind_of?(StorableSelection))
				selection = symbol;
			else
				selection = self.get_selection(symbol);
			end
			if (selection.nil?)
				raise SiteError, "Attempted to check primary_key_selection? for non-existant selection: #{symbol}";
			end
			primary_key.each { |key|
				return false unless (selection.valid_column?(key))
			}
			return true;
		end

		# Find is used to retrieve a set of Storable objects.
		# Find takes a list of ids, a list of symbols (options that are set true), and a hashtable of options.
		# If the options passed to find do not require special SQL to be included in the query then numerous
		# methods are employed to speed up processing.  Complex options will result in each find call performing a query.
		#
		# The options that are available include:
		# * <b>:conditions</b> - An SQL fragment that would follow <b>WHERE</b>.  This can also be an array with a fragment
		#   that includes placeholders and the variables that should be substituted for them.
		# * <b>:group</b> - A fragment of SQL code that would follow <b>GROUP BY</b>.
		# * <b>:limit</b> - An integer specifying the maximum number of results to return.
		# * <b>:order</b> - A fragment of SQL code that would follow <b>ORDER BY</b>.
		# * <b>:promise</b> - The query will not be performed until the result object is used, or until we can include it in another query.
		# * <b>:refresh</b> - The object will be loaded from the database whether it was cached or not.  If the object was cached
		#   the cached version is updated to reflech the current state of the database.
		# * <b>:selection</b> - This requires a symbol for a registered selection object, or a custom built selection object.  It will
		#   limit the results to those columns allowed.  If a full object has already been cached it will be returned untouched.
		# * <b>:first</b> - Only the first result is returned and it is not wrapped in an array.
		#
		# Some example calls on a theoretical subclass Person:
		# 	Person.find(1) # returns an array with one element, the object for ID = 1
		# 	Person.find(1, 2, 6, :refresh) # returns an array for objects with IDs in (1, 2, 6), refreshes them all from the database even if they are in memory.
		# 	Person.find(*[[7], [17]]) # returns an array for objects with IDs in (7, 17)		These are needed to properly query multipart key table.
		#   Person.find([7], [17]) # returns an array for objects with IDs in (7, 17)
		# 	Person.find(1,2 :promise) # returns an array of promises for the objects with ID 1 and 2, these promises are aggregated when similar promise is executed.
		# 	Person.find(:first, :conditions => ["name = ?", "bob"]) # returns the first Person whose name is bob
		def find(*args)
			$site.cache.use_context(nil) {
				if ($site.config.storable_force_nomemcache)
					args << :nomemcache;
				end
			
				#make this a generic recursive deep copy
				original_args = args
				args = args.dclone
				options = extract_options_from_args!(args);
				if (args.length > 0)
					ids = group_ids(args, options[:index], options[:selection]);
				end
			
				cacheable = cacheable_options?(options) && !ids.nil?

				#we're not querying for anything, stop now.
				if (ids.nil? && !options[:limit] && !options[:conditions] && !options[:scan])
					$log.info "Attempted to perform unconstrained query on table #{self.table} without supplying :scan.", :warning
					$log.object caller, :debug
					return StorableResult.new
				end

				#If adding new options make sure you don't allow those that need to be passed to SQL
				if(cacheable)
					promised_ids[options[:selection]] ||= {}
					ids.each{|id|
						promised_ids[options[:selection]][id] = true;
					}
					if (options[:first])
						execute_load = lambda {
							result = fetch_ids(options[:selection], ids.first).first
							promise_callback(result, options[:promise])
						}
						if (options[:promise])
							if (options[:force_proxy])
								return self::StorableProxy.new(ids.first.properties_hash, &execute_load)
							else
								return promise(&execute_load)
							end
						else
							return fetch_ids(options[:selection], ids.first).first
						end
					else
						if options[:promise]
							# relation_multi can with an acceptable degree of assuredness pull a list of ids from memcache that
							# it knows will be there and which are always complete keys, it uses :force_proxy to generate objects
							# that can be utilized without ever performing the queries when all that is needed are the ids (very useful)
							#######################################################################################################
							# XXX: there is the danger that nil results show up in the result set here if a requested id isn't found,
							# XXX: or that only the first element of a partial key query will be returned even if there are more
							# XXX: :force_proxy should ONLY be used if the ids passed in are COMPLETE and KNOWN TO EXIST
							if (options[:force_proxy])
								results = ids.map {|id|
									self::StorableProxy.new(id.properties_hash) {
										result = fetch_ids(options[:selection], id).first
										promise_callback(result, options[:promise])
									}
								}
								return StorableResult.new(results)
							else #if we aren't forcing proxy objects then just return a promise to the complete result
								return promise {
									result = fetch_ids(options[:selection], *ids)
									promise_callback(result, options[:promise])
								}
							end
						else
							return fetch_ids(options[:selection], *ids)
						end
					end
				elsif options[:promise]
					return promise {
						options.delete(:promise);
						args << options;
						result = self.find(*args);
						if (options[:promise].respond_to?(:call))
							options[:promise].call(result)
						else
							result
						end
					}
				end
			
				options.delete(:skip_fetch_ids)
				
				if (args.length > 0)
					result = find_by_id(ids, options)
				else
					result = find_all(options);
				end
			
				if (options[:total_rows] && !options[:count])
					new_args = original_args.dclone
					new_args << :count
					result.total_rows = find(*new_args)
				end
			
				return result if options[:count]
				
				if options[:first]
					result =  result.first 
				elsif cacheable
					result.uniq!
				end

				return result
			}
		end

		def promise_callback(result, promise)
			if (promise.respond_to?(:call))
				promise.call(result)
			else
				result
			end	
		end

		#pulls database query options from the arguments
		def extract_options_from_args!(args) #:nodoc:
			options = {};
			delete_elements = [];
			args.each {|arg|
				if (arg.is_a?(Symbol))
					if (indexes[arg] && !options[:index])
						options[:index] = arg;
					else
						options[arg] = true;
					end
					delete_elements << arg;
				elsif (arg.is_a?(Hash))
					options.merge!(arg);
					delete_elements << arg;
				end
			}
			delete_elements.each {|element|
				args.delete(element);
			}
			if (!options[:index] || !indexes[options[:index]])
				options[:index] = :PRIMARY;
			end
			if (options[:page])
				options[:limit] ||= DEFAULT_PAGE_LENGTH
				options[:offset] ||= (options[:page]-1)*options[:limit]
			end
			return options;
		end

		#retrieve a new secondary id for a given primary id (eg. a message id for a user id).
		def get_seq_id(primary_id)
			self.extend TypeID unless (self.respond_to?(:typeid));
			seq_id = self.db.get_seq_id(primary_id, self.typeid, self.seq_initial_value);
			return seq_id;
		end

		#group any key elements not in an array into key length arrays
		def group_ids(ids, index, selection)
			grouped_ids = []
			temp_id = []
			ids.each {|id|
				if (id.kind_of?(Array))
					grouped_ids << self::StorableID.new(id, index, selection)
				elsif (id.kind_of?(StorableID))
					grouped_ids << id
				else
					temp_id << id
					if (temp_id.length == indexes[index].length)
						grouped_ids << self::StorableID.new(temp_id, index, selection)
						temp_id = []
					end
				end
			}
			if (temp_id.length > 0)
				grouped_ids << self::StorableID.new(temp_id, index)
			end
			return grouped_ids
		end
		
		#interprets an array of ids as a partial specification of an index and creates the SQL condition for it.
		def get_key_conditions(ids, index)
			if (ids.length <= indexes[index].length)
				first = true;
				conditions = indexes[index][0, ids.length].map {|key|
					s = "`#{key}` = " + (split_column?(key) ? '#' : '?');
					s;
				}
				sql = conditions.join(' && ');
				return [sql].concat(ids);
			else
				raise ArgumentError, "Number of ids is greater than the number of primary keys."
			end
		end
		protected :get_key_conditions

		#Returns a hash of selection -> id set.
		#For "select *" queries, the key is nil.  
		def promised_ids
			key = :"#{self}_promised_ids"
			storable_cache = $site.cache.get(:storable_cache, :page) { Hash.new };
			storable_cache[key] ||= {}
			return storable_cache[key]
		end
		
		#setup an extra column to cache in memcache
		#by default uses self.column and self.column= as a getter and setter
		#you can optionally pass in proc/method objects to be used for either/both
		def cache_extra_column(column, getter=nil, setter=nil)
			if (!getter)
				getter = lambda {return self.send(column)}
			end
			if (!setter)
				setter = lambda {|value| return self.send("#{column}=".to_sym), value}
			end
			extra_cache_columns[column] = { :get => getter, :set => setter }
		end
		
		#This function needs to be called once in each subclass after inherited databases, tables, etc have been overwritten.
		#It rechecks column information, sets up attrs, gets primary keys, etc.
		def init_storable(new_db = nil, new_table = nil, new_enums = nil)
			class_attr_accessor(:columns, :fetched_promises, :storable_selections, :indexes, :extra_cache_columns, :event_hooks);
		
			self.storable_selections = Hash.new;
			self.fetched_promises = Hash.new;
			self.indexes = Hash.new;
			self.event_hooks = Hash.new
			set_db(new_db) if (new_db)
			set_table(new_table) if (new_table)
			set_enums(new_enums) if (new_enums) #enum_maps
			self.columns = {};
			self.extra_cache_columns = {};
			db.list_indexes(table).each { |index|
				self.indexes[index['Key_name'].to_sym] ||= []; #initialize to a new array if this is the first column of the key
				self.indexes[index['Key_name'].to_sym] << index['Column_name'];
			}
			primary_key_exists = self.indexes[:PRIMARY];
			db.list_fields(table).each { |column|
				column = Column.new(column, self.enums[column['Field'].to_sym])

				self.columns[column.sym_name] = column;

				if (self.enums.key?(column.sym_name))
					enum_map_attr(column.sym_name, self.enums[column.sym_name], column.default_ignore_enum);
				elsif (column.type_name == "enum")
					if(column.boolean?)
						bool_attr(column.sym_name)
					else
						enum_attr(column.sym_name, column.enum_symbols);
					end
				else
					attr(column.sym_name, true);
				end

				var_name = :"#{column.name}="
				storable_var_name = :"_storable_#{column.name}="
				column_instance_variable = :"@#{column.name.to_sym}"
				
				self.send(:alias_method, storable_var_name, var_name);
				self.send(:define_method, var_name, lambda { |x|
					__modified__[column.name.to_sym] = instance_variable_get(column_instance_variable);
					self.send(storable_var_name, x);
					self.on_field_change(column.name);
				})
				if (!primary_key_exists)
					self.indexes[:PRIMARY] ||= []; #initialize to a new array if this is the first column of the key
					self.indexes[:PRIMARY] << column.name;
				end
			}
		end
		protected :init_storable

		#Sets a class specific db for a subclass, if this isn't called the parents db will be used.
		def set_db(new_db)
			class_attr(:db, true);
			if ($site.dbs.class == Hash && $site.dbs[new_db])
				self.db = $site.dbs[new_db];
			else
				self.db = new_db;
			end
		end
		protected :set_db

		def set_enums(new_enums={})
			class_attr(:enums, true);
			if(!self.enums.nil?() && self.enums.kind_of?(Hash))
				self.enums.merge!(new_enums);
			else
				self.enums = new_enums;
			end
		end
		protected :set_enums

		#Sets a class specific seqtable and area for a subclass, if this isn't called the parents will be used.
		def set_seq_initial_value(new_initial_value)
			class_attr_accessor(:seq_initial_value);
			self.seq_initial_value = new_initial_value;
		end
		protected :set_seq_initial_value

		#Sets a class specific table for a subclass, if this isn't called the parents table will be used.
		def set_table(new_table)
			class_attr(:table, true);
			self.table = new_table;
		end
		protected :set_table

		#returns the storables for the listed ids, if it has to access the database
		#to do so it pulls all promised ids from the database as well.
		def fetch_ids(selection, *ids)
			query_db = false;
			results = StorableResult.new()
			found_ids = []
			ids.each {|id|
				result = cache_load(id);
				if (result != :not_found)
					found_ids << id
					results = results.concat(result)
				else
					promised_ids[selection] ||= {}
					promised_ids[selection][id] = true
					query_db = true
				end
			}
			ids -= found_ids
			if (query_db)
				fetch_promised_ids()
				ids.each {|id|
					result = cache_load(id)
					results = results.concat(result) if (result != :not_found)
				}
			end
			return results;
		end
		protected :fetch_ids

		#all currently queued promised ids are loaded from the database.  All
		#retrieved storable objects are cached.  Negative hits are cached as nils.
		def fetch_promised_ids()
			#If there are multiple selections queued, get them all
			promised_ids.each {|selection, val|
				ids = val.keys
				#we don't return a result set here, everything we find is cached locally which is where we will look for it
				self.find(:skip_fetch_ids, :selection => selection, *ids) if (ids.length > 0)
			}
			promised_ids.clear()
		end
		protected :fetch_promised_ids

		#get all objects in the ids array that satisfy options
		def find_by_id(id_sets, options)
			cached_vals = []
			cache_results = false
			if (cacheable_options?(options))
				cache_results = true
				cached_vals = find_in_cache(id_sets, options);
				id_sets = id_sets - cached_vals.meta.keys
			end
			if (!id_sets.empty?)
				force_no_split = false;
				
				id_sets.each{|id|
					if(!id.split?())
						force_no_split = true;
					end
				};
				key_conditions = id_sets.map {|id| id.condition(force_no_split)}
				key_conditions = self.merge_conditions(" || ", *key_conditions)
				options[:conditions] = self.merge_conditions(" && ", key_conditions, options[:conditions])
				if (!options[:count])
					result = find_all(options).concat(cached_vals)
					if (cache_results)
						id_sets.each {|id|
							match = result.match(id).uniq
							cache_result(id, match)
						}
					end
				else
					return find_all(options) + cached_vals.length
				end
			else
				if (!options[:count])
					result = cached_vals;
				else
					return cached_vals.length;
				end
			end

			if (options[:limit] && options[:limit] < result.length)
				result.slice!(0,result.length-options[:limit]);
			end
			
			return result;
		end
		protected :find_by_id

		def find_in_cache(id_sets, options={})
			cached_vals = StorableResult.new
			cached_vals.meta.keys = []
			id_sets.each { |id_set|
				cached_val = cache_load(id_set);
				if (cached_val != :not_found)
					cached_vals = cached_vals.concat(cached_val)
					cached_vals.meta.keys << id_set
				end
			}
			return cached_vals;
		end
		protected :find_in_cache

		#returns an object from the internal cache based on a set of keys.
		def cache_load(key)
			int_cache = internal_cache
			if int_cache.key?(key)
				return int_cache[key];
			else
				return :not_found;
			end
		end
		protected :cache_load

		def cache_result(id, storable_result)
			unless (storable_result.kind_of? StorableResult)
				if (storable_result)
					storable_result = StorableResult.new([storable_result])
				else
					storable_result = StorableResult.new
				end
			end
			internal_cache[id] = storable_result
			return storable_result
		end
		#protected :cache_result
		
		#caches nil internally for a set of keys, useful for preventing repeated lookups of a missing row
		def cache_nil(id)
			internal_cache[id] = nil
		end
		protected :cache_nil

		#returns an SQL fragment encompassing the 'something' in SELECT something FROM somewhere
		def get_select_string(symbol)
			selection = get_selection(symbol)
			return (selection ? selection.sql : " * " )
		end
		protected :get_select_string

		#get all objects which satisfy the options
		#this is the lowest level function that all finds go through if they hit the database
		def find_all(options)
			sql = "SELECT ";
			if (options[:count])
				sql << " COUNT(*) as `rowcount` "
			else
				sql << " SQL_CALC_FOUND_ROWS " if options[:calc_rows];
				sql << get_select_string(options[:selection]);
			end
			sql << " FROM `#{self.table}`"
			if (options[:conditions])
				if options[:conditions].class == String
					sql << " WHERE #{options[:conditions]} ";
				else
					prep_args = options[:conditions].dclone
					sql << " WHERE " + db.prepare(*prep_args);
				end
			end
			sql << " GROUP BY #{options[:group]} " if options[:group];
			sql << " ORDER BY #{options[:order]} " if options[:order];
			if (options[:offset])
				sql << " LIMIT #{options[:offset]},#{options[:limit] || 0} ";
			elsif (options[:limit])
				sql << " LIMIT #{options[:limit]} ";
			end

			result = self.db.query(sql);

			if (options[:count])
				count = 0
				result.each{|row|
					count += row['rowcount'].to_i
				}
				return count
			end

			total_rows = result.total_rows
			
			storables = PagedResult.new;
			storables.page = options[:page]
			storables.page ||= 1
			storables.total_rows = total_rows
			storables.page_length = options[:limit]
			storables.page_length ||= total_rows
			storables.calculated_total = options[:total_rows]
			
			selection = get_selection(options[:selection])

			result.each { |row|
				#Check if the object was already created. This is needed so the previous one can be invalidated if needed.
				keys = indexes[:PRIMARY].map { |key|
					columns[key.to_sym].parse_column(row[key]);
				}

				cached_val = cache_load(self::StorableID.new(keys, :PRIMARY, selection));

				if (cached_val != :not_found)
					if(options[:refresh])
						storable = cached_val.first;
						storable.clear_modified!;
					else
						storables << cached_val.first;
						next;
					end
				else
					storable = self.storable_new(false);
					storable.selection = selection
				end

				storable.total_rows = total_rows;

				if (!selection)
					columns.each_value { |column|
						storable.instance_variable_set(column.sym_ivar_name, column.parse_string(row[column.name]));
					}
				else
					selection.columns.each_key { |name|
						column = columns[name]
						storable.instance_variable_set(column.sym_ivar_name, column.parse_string(row[column.name]));
					}

					if(!$site.config.live)
						storable_class = class << storable; self; end
						columns.each_value{ |column|
							if(!selection.valid_column?(column.sym_name))
								storable_class.send(:define_method, column.sym_ivar_equ_name) { |value| raise SiteError, "Attempting to set a column (#{column.name}) that was not fetched from the database."}
								storable_class.send(:define_method, column.sym_ivar_name) { raise SiteError, "Attempting to access a column (#{column.name}) that was not fetched from the database."}
							end
						}
					end
				end
				storable.update_method = :update;
				storable.after_load();
				
				storable.add_to_cache = true;
				storables << storable;
			}

			storables.each {|storable|
				storable.prime_extra_columns if storable.add_to_cache
			}

			storables.each {|storable|
				if storable.add_to_cache
					cache_result(storable.storable_id, storable)
					storable.add_to_cache = false
				end
			}

			return storables;
		end
		protected :find_all


		def internal_cache
			storable_cache = $site.cache.get(:storable_cache, :page) { Hash.new };
			storable_cache[self] ||= {}
			return storable_cache[self]
		end

		def split_column?(column_name)
			splittable = (indexes[:PRIMARY].first.to_sym == column_name.to_sym && self.db.class == SqlDBStripe)
			return splittable
		end
		
		def cacheable_options?(options)
			return (
				!options[:group] &&
				!options[:conditions] &&
				!options[:order] &&
				!options[:refresh] &&
				!options[:page] &&
				!options[:offset] &&
				!options[:count] &&
				!options[:limit] &&
				!options[:skip_fetch_ids]
			)
		end
		
		def merge_conditions(join_with, *conditions)
			key_strings = []
			values = []
			conditions.each {|condition|
				next unless condition
				condition = [*condition]
				key_strings << condition.shift
				values = values.concat(condition)
			}
			return ["(#{key_strings.join(join_with)})", *values]
		end
	end

	class ArgumentError < SiteError
	end
end
