lib_require :Core, "var_dump", "attrs/class_attr", 'storable/relations', 'storable/column', 'storable/storable_selection', "attrs/enum_attr", "attrs/enum_map_attr", "attrs/bool_attr", "lazy", 'data_structures/ordered_map', 'data_structures/paged_result', 'storable/user_content'
lib_require :Core, 'storable/monitored_content'

require 'set'
require 'mocha'

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

	extend Relations;
	extend UserContent;
	extend MonitoredContent;

	DEFAULT_PAGE_LENGTH = 25
	
	#valid entries here are :insert, and :update
	attr_accessor(:update_method, :insert_id, :affected_rows, :total_rows, :cache_points)

	@@subclasses = {};
	@@__cache__ = {};
	@@memcached = {};

	#Create a new object and initialize all of its column attributes to the default column values.
	def initialize()
		self.update_method = :insert;
		self.cache_points = {}
		self.class.columns.keys.each {|column| self.cache_points[column] = Set.new}
		self.cache_points[:all_cache_points] = Set.new
		
		@__modified__ = Hash.new
		self.class.columns.each_value {|column|
			self.send(:"#{column.name}=", column.parse_string(column.default));
			@__modified__[column.name.to_sym] = false;
		}
	end

	#Checks to see if column has been modified since it was retrieved from the database.
	def modified?(column=nil)
		if (column)
			return @__modified__[column.to_sym];
		else
			@__modified__.each_value{|val|
				return true if (val);
			}
			return false;
		end
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
		@__modified__.each_key {|key|
			@__modified__[key] = false;
		}
	end

	def storable_id
		self.class::StorableID.new([*self.get_primary_key])
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
	def store(*args)
		options = self.class.extract_options_from_args!(args);

		columns = "";
		variables = Array.new;

		case update_method
		when :insert
			before_create();
			increment_column = nil;
			self.class.columns.each_value {|column|
				if (self.modified?(:"#{column.name}") || update_method == :insert)
					columns += "`#{column.name}`";
					columns += " = ";
					columns += (self.class.split_column?(column.name) ? "#" : "?");
					columns += ", ";
					if (self.respond_to?(:"#{column.name}!"))
						variables << self.send(:"#{column.name}!").value;
					else
						variables << self.send(:"#{column.name}");
					end
				end
				if (column.extra == "auto_increment")
					increment_column = column.name;
					options[:insert_id] = true;
				end
			}
			columns.chomp!(", ");

			if (options[:ignore])
				ignore = " IGNORE";
			else
				ignore = "";
			end

			sql = "INSERT #{ignore} INTO #{self.table} SET #{columns} ";
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
			if (modified?)
				self.class.columns.each_value {|column|
					columns += "`#{column.name}`";
					columns += " = ";
					columns += (self.class.split_column?(column.name) ? "#" : "?");
					columns += ", ";
					if (self.respond_to?(:"#{column.name}!"))
						variables << self.send(:"#{column.name}!").value;
					else
						variables << self.send(:"#{column.name}");
					end
				}
				columns.chomp!(", ");
				sql = "UPDATE #{self.table} SET #{columns} WHERE "
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
				sql_result = self.db.query(sql, *variables);
				if (options[:affected_rows])
					@affected_rows = sql_result.affected_rows();
				end
			end
			after_update();
		else
			raise "Unsupported update method: #{update_method}";
		end
	ensure
		memcache_invalidate();
	end

	def _dump(depth)
		values = {}
		self.class.columns.each_pair {|name, column|
			values[name.to_sym] = self.instance_variable_get(:"@#{name.to_sym}")
			if (values[name.to_sym].kind_of? UserContent::UserContentString)
				values[name.to_sym] = String.new(values[name.to_sym])
			end
		}
		values[:__update_method__] = self.update_method
		return Marshal.dump(values)
	end
	
	#Delete the object from local cache and the database.
	def delete(*args)
		memcache_invalidate();

		options = self.class.extract_options_from_args!(args);

		case update_method
		when :insert, :update
			before_delete();
			self.invalidate_cache_keys
			if (primary_key.length > 0)
				sql = "DELETE FROM #{self.table} WHERE "
				first = true;
				primary_key.each { |key|
					if (first)
						sql << " `#{key}` = # && ";
					else
						sql << " `#{key}` = ? && ";
					end
				}
				sql.chomp!("&& ");
				sql_result = self.db.query(sql, *get_primary_key);
			else
				#If there is no primary key, delete by matching all columns
				columns = "";
				variables = Array.new;
				self.class.columns.each_value {|column|
					columns += "`#{column.name}`";
					columns += " = ";
					columns += "?";
					columns += " AND ";
					variables << self.send(:"#{column.name}");
				}
				columns.chomp!(" AND ");
				sql_result = self.db.query("DELETE FROM #{self.table} WHERE #{columns}", *variables);
			end
			if (options[:affected_rows])
				@affected_rows = sql_result.affected_rows();
			end
			after_delete();
		else
			raise "Unsupported update method: #{update_method}";
		end
	end

	def invalidate_cache_keys
		#if there is a relation that has this in its cached result, delete the cached version of said relation
		self.invalidate_relation_cache
		#delete any key in the local cache that points to this object
		self.cache_points[:all_cache_points].each { |cache_key|
			self.class.internal_cache.delete(cache_key)
		}
	end

	#Called before a new row is inserted into the database.
	def before_create();
		return;
	end
	#Called after a new row is inserted into the database.
	def after_create();
		return;
	end

	#Called before a row is updated in the database.
	def before_update();
		return;
	end
	#Called after a row is updated in the database
	def after_update();
		return;
	end

	#Called before a row is deleted from the database.
	def before_delete();
		return;
	end
	#Called after a row is deleted from the database.
	def after_delete();
		return;
	end

	#Called after a row is loaded from the database.
	def after_load()
		return;
	end

	# Invalidate any memcache key whose descriptor matches this instance.
	# This could be optimized to only invalidate when the relevant data is
	# actually changed.  Right now, it invalidates on any store/delete where 
	# the key matches, even if only irrelevant fields have been changed.
	def memcache_invalidate()
		return if !@@memcached[self.class];
		
		@@memcached[self.class].each_pair{ | prefix,(mindex, key_len) |
			new_memcache_key = [];
			old_memcache_key = [];
			
			self.class.indexes[mindex].each{ |col|
				if (modified?(col))
					old_memcache_key << @column_history[col];
				else
					old_memcache_key << send(col);
				end
				
				new_memcache_key << send(col)
			}
			
			newid = new_memcache_key[0...key_len].join("/");
			oldid = old_memcache_key[0...key_len].join("/");
			$log.info "deleting key '#{prefix}-#{newid}'", :debug;
			$site.memcache.delete("#{prefix}-#{newid}");
			$log.info "deleting key '#{prefix}-#{oldid}'", :debug;
			$site.memcache.delete("#{prefix}-#{oldid}");
		}
		
	end
	
	#Called everytime any column is changed, before the change.
	def before_field_change(column_name)
		return if modified?(:"#{column_name}"); 
		return if !@@memcached[self.class];
		
		@@memcached[self.class].each_pair{ | prefix,(mindex, key_len) |
			memcache_key = [];
			
			if self.class.indexes[mindex].index(column_name)
				@column_history ||= {};
				@column_history[column_name] = self.send(column_name);
			end
		}
	end

	#Called everytime any column is changed, after the change.
	def on_field_change(column_name)
		self.cache_points[column_name].each { |cache_key|
			self.class.internal_cache.delete(cache_key)
		}
		self.cache_points[column_name].clear
		return;
	end

	def register_cache_point(storable_id)
		cache_key = storable_id.cache_key
		storable_id.key_columns.each { |column|
			self.cache_points[column].add(cache_key)
		}
		self.cache_points[:all_cache_points].add(cache_key)
	end

	def primary_key
		return self.class.primary_key;
	end

	#returns the primary key if it is individual or the array or primary keys if there are multiple.
	def get_primary_key()
		if (primary_key.length == 1)
			return self.send(primary_key.first)
		else
			keys = Array.new;
			primary_key.each { |key|
				keys << self.send(key);
			}
			return keys;
		end
	end

	#compares the equality of all columns
	def ==(obj)
		return false unless obj.kind_of?(Storable);
		self.class.columns.each_key{|column|
			if (obj.respond_to?(column))
				return false if (!(obj.send(column) == self.send(column)))
			else
				return false;
			end
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
		
		def listen_create(&block) 
			prechain_method(:after_create, &block)
		end
		
		def listen_delete(&block)
			postchain_method(:before_delete, &block)
		end
		
		include Mocha::Standalone
		include Mocha::SetupAndTeardown
		
		def mock_instance()
			
			mock_obj = new();
			self.columns.each{|name, column|
				default = column.default
				if (default == nil)
					default = Time.now.to_i
				end
				mock_obj.send(:"#{column.name}=", column.parse_string(default));
			}
			[*primary_key()].each{|column|
				mock_obj.send(:"#{column}=", 1);
			}
			existing_obj = find(:first, mock_obj.get_primary_key())
			if (existing_obj)
				return existing_obj;
			end
			mock_obj.store;
			return mock_obj;
		end
		
		def fake_get_seq_id(*args)
			@last_id ||= 0
			@last_id += 1
			return @last_id
		end
		
		
		def _use_test_db()
			@real_db = self.db;
			@real_table = self.table;
			
			alias real_get_seq_id get_seq_id
			alias get_seq_id fake_get_seq_id
			
			self.db = $site.dbs[:generatedtestdb];
			self.table = "#{$site.dbs.invert[@real_db]}_#{self.table}";
		end
		
		def _unuse_test_db()
			self.db = @real_db;
			self.table = @real_table;
			alias get_seq_id real_get_seq_id
		end
		
		def recurse_use_test_db(pklass)
			if (@@subclasses[pklass])
				@@subclasses[pklass].each{|klass|
					recurse_use_test_db(klass);
					if (klass.db.kind_of? SqlBase)
						klass._use_test_db
					end
				}
			end
		end
		
		def recurse_unuse_test_db(pklass)
			if (@@subclasses[pklass])
				@@subclasses[pklass].each{|klass|
					recurse_unuse_test_db(klass);
					if (klass.db.kind_of? SqlBase)
						klass._unuse_test_db()
					end
				}
			end
		end
		
		def use_test_db(&block)
			begin
				recurse_use_test_db(Storable);
				yield block;	
			ensure
				recurse_unuse_test_db(Storable);
			end
		end

		def _load(str)
			values = Marshal.load(str)
			storable = self.new
			values.each_pair {|name, value|
				storable.instance_variable_set(:"@#{name}", value) unless (name =~ /__.+__/)
			}
			storable.update_method = values[:__update_method__]
			return storable
		end
	

		
		#Wraps the call to new, this allows children classes to override create and return an object of a different
		#class if they have need.
		def storable_new
			return self.new
		end

		#returns the column names of the primary key
		def primary_key
			return indexes["PRIMARY".to_sym];
		end

		#registers the inherited class in the subclasses hash tree
		def inherited(child)
			@@subclasses[self] ||= []
			@@subclasses[self] << child
			child.const_set("StorableID", Class.new(StorableID))
			child::StorableID.const_set("StorableClass", child)
      		super
		end
		
		def create_id(id, index)
			return self::StorableID.new(id, index)
		end

		#a nested hash of subclasses of the current class
		def subclasses
			return @@subclasses[self];
		end

		#Register either a symbol and list of columns or a custom created StorableSelection object.
		def register_selection(symbol, *columns)
			if (symbol.kind_of?(StorableSelection))
				selection = symbol;
			else
				selection = StorableSelection.new(symbol, *columns);
			end
			self.storable_selections[selection.symbol] = selection;
		end

		#look in current class and parent classes to see if the symbol has been registered anywhere as a selection
		def get_selection(symbol)
			selection = self.storable_selections[symbol];
			begin
				selection = super(symbol) unless (selection);
			rescue NoMethodError
				selection = nil;
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
	    # 	Person.find([7, 17]) # returns an array for objects with IDs in (7, 17)
	    # 	Person.find(1,2 :promise) # returns an array of promises for the objects with ID 1 and 2, these promises are aggregated when similar promise is executed.
	    # 	Person.find(:first, :conditions => ["name = ?", "bob"]) # returns the first Person whose name is bob
	    def find(*args)
			if ($site.config.storable_force_nomemcache)
				args << :nomemcache;
			end
			
			#make this a generic recursive deep copy
			args = args.clone
			options = extract_options_from_args!(args);
			if (args.length > 0)
				ids = group_ids(args, options[:index]);
			end
			
			cacheable = cacheable_options?(options)

			options[:limit] = 1 if (options[:first]);

			#we're not querying for anything, stop now.
			if (ids.nil? && !options[:limit] && !options[:conditions] && !options[:scan])
				$log.info "Attempted to perform unconstrained query without supplying :scan.", :warning
				$log.object caller, :debug
				return StorableResult.new
			end

			if (options[:promise])
				#If adding new options make sure you don't allow those that need to be passed to SQL
				if	(cacheable)
					promised_ids.merge(ids);
					if (options[:first])
						return	promise {
							result = fetch_ids(ids.first).first
							if (options[:promise].respond_to?(:call))
								options[:promise].call(result)
							else
								result
							end
						}
					else
						return promise {
							result = fetch_ids(*ids)
							if (options[:promise].respond_to?(:call))
								options[:promise].call(result)
							else
								result
							end
						}
					end
				else
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
			end

			if (args.length > 0)
				result = find_by_id(ids, options)
			else
				result = find_all(options);
			end
			
			if options[:first]
				result =  result.first 
			elsif cacheable
				result.uniq!
			end

			return result
		end

		# Inform storable that the particular given queries should result in
		# invalidating the matching memcache entries.  
		# - prefix: the memcache prefix
		# - hypothetical key: the length of this determines how many of the 
		#   index columns will be considered.  For instance, if the index was
		#   (id1, id2, id3) and hypothetical_key had 2 elements, it would try to 
		#   invalidate the memcache key called "#{prefix}-#{id1}/#{id2}"
		# - findargs: should specific the index used if not the primary.
		def memcache_watch_key(prefix, hypothetical_key, *findargs)
			options = extract_options_from_args!(findargs);
			@@memcached[self] ||= {};
			@@memcached[self][prefix] = [options[:index], [*hypothetical_key].length];
		end
		
		# The only findarg currently considered is ':first'.
		def memcache_find(prefix, relation_ids, exptime, *findargs)
			id = relation_ids.join("/");
			$log.info "Getting #{prefix}-#{id}", :debug;
			ids = $site.memcache.get("#{prefix}-#{id}");
			if (ids.nil?)
				real_findargs = relation_ids.concat(findargs);
				val = [*(self.find( *real_findargs ))];
				ids = val.map{|storable|
					storable.get_primary_key;
				}
				$site.memcache.set("#{prefix}-#{id}", ids, exptime);
			elsif (ids == [])
				val = StorableResult.new
			else
				val = self.find(:promise, *ids);
			end

			return val.first if (findargs.index(:first))
			return val;
		end
		
		def memcache_find_ids(prefix, relation_ids, exptime, *findargs)
			id = relation_ids.join("/");
			$log.info "Getting #{prefix}-#{id}", :debug;
			ids = $site.memcache.get("#{prefix}-#{id}");
			if (ids.nil?)
				real_findargs = relation_ids.concat(findargs);
				ids = [*(self.find(*real_findargs))].map{|storable|
					storable.get_primary_key
				}
				$site.memcache.set("#{prefix}-#{id}", ids, exptime);
			end
			return ids
		end

		#clear all elements out of the internal cache.
		def reset_internal_cache()
			internal_cache.each_key { |key|
				if (key.to_s =~ Regexp.new("^#{self.name}-"))
					internal_cache.delete(key);
				end
			}
			if (subclasses)
				subclasses.each { |s|
					s.reset_internal_cache()
				}
			end
		end

		#pulls database query options from the arguments
		def extract_options_from_args!(args) #:nodoc:
			options = Hash.new;
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


		#protected ############BEGIN PROTECTED METHODS#############

		#group any key elements not in an array into key length arrays
		def group_ids(ids, index)
			grouped_ids = []
			temp_id = []
			ids.each {|id|
				if (id.kind_of?(Array))
					grouped_ids << self::StorableID.new(id, index)
				elsif (id.kind_of?(StorableID))
					grouped_ids << id
				else
					temp_id << id
					if (temp_id.length == indexes[index].length)
						grouped_ids << self::StorableID.new(temp_id, index)
						temp_id = []
					end
				end
			}
			if (temp_id.length > 0)
				grouped_ids << self::StorableID.new(temp_id, index)
			end
			return grouped_ids
		end
		protected :group_ids

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

		#This function needs to be called once in each subclass after inherited databases, tables, etc have been overwritten.
		#It rechecks column information, sets up attrs, gets primary keys, etc.
		def init_storable(new_db = nil, new_table = nil, new_enums = nil)
			class_attr_accessor(:columns, :promised_ids, :fetched_promises, :storable_selections, :indexes);
			#protected_class_method :promised_ids, :promised_ids=, :fetched_promises, :fetched_promises=;
			self.storable_selections = Hash.new;
			self.promised_ids = Set.new;
			self.fetched_promises = Hash.new;
			self.indexes = Hash.new;
			set_db(new_db) if (new_db)
			set_table(new_table) if (new_table)
			set_enums(new_enums) if (new_enums)
			self.columns = Hash.new;
			result = db.list_fields(table);
			db.list_indexes(table).each { |index|
				self.indexes[index['Key_name'].to_sym] ||= []; #initialize to a new array if this is the first column of the key
				self.indexes[index['Key_name'].to_sym] << index['Column_name'];
			}
			primary_key_exists = self.indexes["PRIMARY".to_sym];
			db.list_fields(table).each { |column|
				column = Column.new(column)
				self.columns[column.name] = column;
				if (self.enums.key?(column.name.to_sym))
					enum_map_attr(column.name.to_sym, self.enums[column.name.to_sym]);
				elsif (column.type_name == "enum" && column.boolean?)
					bool_attr(column.name.to_sym)
				elsif (column.type_name == "enum" && !column.boolean?)
					enum_attr(column.name.to_sym, column.enum_symbols);
				else
					attr(column.name.to_sym, true);
				end
				if (column.type_name == "enum")
					if (column.default != nil)
						Enum.new(column.default, column.enum_symbols)
					end
				end
				#self.send(:"#{column.name}=", column.parse_string(column.default));
				#$log.info "#{column.name}:#{column.parse_string(column.default)}";
				
				self.send(:alias_method, :"_storable_#{column.name}=", "#{column.name}=".to_sym);
				self.send(:define_method, "#{column.name}=".to_sym, lambda { |x|
					self.before_field_change(column.name);
					@__modified__[column.name.to_sym] = true;
					self.send("_storable_#{column.name}=".to_sym, x);
					#$log.info("Setting #{column.name}.")
					self.on_field_change(column.name);
				})
				if (!primary_key_exists)
					self.indexes["PRIMARY".to_sym] ||= []; #initialize to a new array if this is the first column of the key
					self.indexes["PRIMARY".to_sym] << column.name;
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
			self.enums = new_enums;
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
		def fetch_ids(*ids)
			query_db = false;
			results = StorableResult.new()
			found_ids = []
			ids.each {|id|
				result = cache_load(id);
				if (result != :not_found)
					found_ids << id
					results = results.concat(result)
				else
					promised_ids.add(id)
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
		#retrieved storable objects are returned in an OrderedMap, they are also
		#cached.  Negative hits are cached as nils.
		def fetch_promised_ids()
			ids = []
			promised_ids.each {|id|
				ids << id
			}
			#we don't return a result set here, everything we find is cached locally which is where we will look for it
			self.find(*ids) if (ids.length > 0)
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
				key_conditions = id_sets.map {|id| id.condition}
				key_conditions = self.merge_conditions(" || ", *key_conditions)
				options[:conditions] = self.merge_conditions(" && ", key_conditions, options[:conditions])
				result = find_all(options).concat(cached_vals)
				if (cache_results)
					id_sets.each {|id|
						match = result.match(id)
						cache_result(id, match)
					}
				end
			else
				result = cached_vals;
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
			if self.internal_cache().key?(key.cache_key)
				return internal_cache[key.cache_key];
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
			storable_result.each { |storable| storable.register_cache_point(id)	}
			internal_cache[id.cache_key] = storable_result
			return storable_result
		end
		#protected :cache_result
		
		#caches nil internally for a set of keys, useful for preventing repeated lookups of a missing row
		def cache_nil(id)
			key = id.cache_key
			internal_cache[key] = nil
		end
		protected :cache_nil

		#returns an SQL fragment encompassing SELECT something FROM somewhere
		def get_select_string(symbol)
			selection = get_selection(symbol)
			return " * FROM `#{self.table}` " if (!selection);
			return selection.sql << " FROM `#{self.table}`";
		end
		protected :get_select_string

		#get all objects which satisfy the options
		#this is the lowest level function that all finds go through if they hit the database
		def find_all(options)
			sql = "SELECT ";
			sql << " SQL_CALC_FOUND_ROWS " if options[:total_rows];
			sql << get_select_string(options[:selection]);
			if (options[:conditions])
				if options[:conditions].class == String
					sql << db.prepare(" WHERE #{options[:conditions]} ");
				else
					options[:conditions][0] =  " WHERE " + options[:conditions][0];
					sql << db.prepare(*options[:conditions]);
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
			storables = PagedResult.new;
			storables.page = options[:page]
			storables.page ||= 1
			storables.total_rows = result.total_rows
			storables.page_length = options[:limit]
			storables.page_length ||= result.total_rows
			storables.calculated_total = options[:total_rows]
			
			result.each { |row|
				keys = indexes[:PRIMARY].map { |key|
					columns[key].parse_string(row[key]);
				}

				cached_val = cache_load(self::StorableID.new(keys));

				found_in_cache = false;

				if (cached_val != :not_found && !options[:refresh])
					found_in_cache = true;
					storables.concat cached_val;
					next;
				elsif (cached_val != :not_found && options[:refresh])
					found_in_cache = true;
					storable = cached_val.first;
				else
					storable = self.storable_new;
				end

				selection = get_selection(options[:selection])

				storable.total_rows = result.total_rows;

				if (!selection)
					self.columns.each_value { |column|
						storable.send(:"#{column.name}=", column.parse_string(row[column.name]));
					}
				else
					self.columns.each_value { |column|
						if (selection.valid_column?(column.name))
							storable.send(:"#{column.name}=", column.parse_string(row[column.name]));
						else
							if (!found_in_cache) #if we pulled this out of the internal cache then don't invalidate the columns we already knew.
								storable_class = class << storable; self; end
								storable_class.send(:define_method, :"#{column.name}=") { |value| raise SiteError, "Attempting to set a column (#{column.name}) that was not fetched from the database."}
								storable_class.send(:define_method, :"#{column.name}") { raise SiteError, "Attempting to access a column (#{column.name}) that was not fetched from the database."}
							end
						end
					}
				end
				storable.clear_modified!;
				storable.update_method = :update;
				storable.after_load();

				if (!selection)
					cache_result(storable.storable_id, storable);
				end
				storables.push storable;
			}
			return storables;
		end
		protected :find_all


		def internal_cache
			return $site.cache.get(:storable_cache, :page) { Hash.new };
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
				!options[:selection] &&
				!options[:page] &&
				!options[:offset] &&
				(!options[:limit] || options[:first])
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
	
	#StorableID is used internally to organize ids that are being queried for
	#this includes the ids (possibly partially id) of an object and the index the
	#ids should be used against
	class StorableID
		StorableClass = Storable
		
		attr_accessor :id, :index
		attr_reader :index_name
		
		def initialize(id, index_sym=:PRIMARY)
			raise SiteError if id.first.kind_of? StorableID
			self.id = id
			self.index = index_sym
		end
		
		def index=(symbol)
			@index_name = symbol
			@index = self.class::StorableClass.indexes[symbol]
		end
		
		def primary_key?
			return self.index_name == :PRIMARY
		end
		
		def memcacheable?
			return self.primary_key? && !self.partial_key?
		end
		
		def partial_key?
			return self.id.length < self.index.length
		end
		
		def condition
			key_strings = []
			self.id.each_with_index {|id, i|
				if (self.class::StorableClass.split_column?(self.index[i]))
					key_strings << "(#{self.index[i]} = #)"
				else
					key_strings << "(#{self.index[i]} = ?)"
				end
			}
			return ["(#{key_strings.join(' && ')})", *id]
		end
		
		#key format is roughly: Module::Class_index-id1/id2
		def cache_key
			if self.primary_key?
				return "#{self.class::StorableClass}-#{self.id.join('/')}"
			else
				return "#{self.class::StorableClass}_#{self.index_name}-#{self.id.join('/')}"
			end
		end
		
		def ===(storable_element)
			return false unless storable_element.kind_of? self.class::StorableClass
			self.id.each_with_index {|id, i|
				id = id.to_s if (id.kind_of? Symbol)
				begin
					return false unless (id == storable_element.send(self.index[i]))
				rescue SiteError #Can happen if storable_element is a selection that doesn't contain the column this index is on
					return false
				end
			}
		end
		
		def key_columns
			return self.index[0, self.id.length]
		end
	end
end
