
lib_require :Core, 'errorlog', 'data_structures/string' #for each_char
lib_require :Core, 'mutable_time'
lib_require :Core, 'json'

# register the child sql classes
autoload(:SqlDBdbi, 'core/lib/sql/sqldbi');
autoload(:SqlDBmysql, 'core/lib/sql/sqlmysql');
autoload(:SqlDBMirror, 'core/lib/sql/sqlmirror');
autoload(:SqlDBStripe, 'core/lib/sql/sqlstripe');

# debug levels:
#   0 -> no debug output at all, ignore debugtabes and debugregex
#   1 -> only log slow queries, or those that match debugtables or debugregex
#   2 -> keep a list of queries
#   3 -> do explains as well
# slowtime is in seconds, and can be a float

class SqlBase
	attr_reader :all_options;

	# This class is used to construct a database object.
	class Config
		public
		attr :live,true;
		attr :type,true;
		attr :options;
		attr :children,true;
		attr :inherit,true;
		attr :blocks_run,true;
		attr :all_blocks_run,true;

		# Initialize a dbconfig object with either no argument or a hash
		# with the following possible settings:
		#  - :live, whether the config should be automatically instantiated
		#  - :type, a Class object derived from SqlBase that this will be passed
		#           to.
		#  - :options, a hash of type specific options to be used in initializing
		#              the database object.
		#  - :children, a hash of type specific child database objects to be
		#               used by this object.
		#  - :inherit, an existing dbconfig that this inherits from.
		# Note that if you're not putting these through the main config interface,
		#  children have to be manually initialized and inherit will not do anything,
		#  and live will also have no effect.
		def initialize(init = {})
			@live = init[:live] || false;
			@type = init[:type] || SqlDBmysql;
			@options = init[:options] || {}
			@children = init[:children] || {};
			@inherit = init[:inherit] || nil;

			@blocks_run = [];
			@all_blocks_run = [];
		end

		def create(name, idx)
			return @type.new(name, idx, self);
		end

		# note that this is actually a merge, not a replacement.
		def options=(hash)
			@options.merge!(hash);
		end

		# Constructs child configs and passes them out by a yield, then replaces
		# the entries with whatever the yield returns.
		def build_children(dbconfigs)
			@children.each {|key, childblocks|
				if (!childblocks.kind_of?(Array))
					childblocks = [childblocks];
				end
				new_childblocks = [];
				childblocks.each {|childblock|
					# inheritence on a child works like this:
					#  - copy in the parents' options{}
					#  - run the child's block
					#  - if there is an inherit, start over with:
					#   - run the inherit's all_blocks_run
					#   - run the parent's blocks
					#   - run the child's block

					childconfig = Config.new();
					# copy parent's options
					childconfig.options = @options;
					# now we can run the actual childconfig
					childblock.call(childconfig);

					if (childconfig.inherit)
						inherit = dbconfigs[childconfig.inherit];
						childconfig = Config.new();

						# run the inherited from's blocks in their
						# entirety
						inherit.all_blocks_run.each {|block|
							block.call(childconfig);
						}
						# add in the parent's options
						childconfig.options = @options;
						# now run the child's own blocks.
						childblock.call(childconfig);
					end

					# Recurse into the child's child builder
					childconfig.build_children(dbconfigs) {|passback|
						yield(passback);
					}

					new_childblocks.push(yield(childconfig));
				}
				@children[key] = new_childblocks;
			}
		end
	end

	attr :name
	attr :idx

	#takes options in the form:
	# options = { :debug_level => 1, :debug_tables => false, :debug_regex => false, :slow_time => 1.0,
	# priority is for compatability with the mirror implemenation, and isn't used
	def initialize(name, idx, dbconfig)
		options = dbconfig.options;
		@all_options = options;
		@debug_level = options[:debug_level]  || 1;
		@debug_tables= options[:debug_tables] || false;
		@debug_regex = options[:debug_regex]  || false;
		@slow_time   = options[:slow_time]    || 1.0;
		@max_query_count = 1000; #max queries to be stored for debug

		@connection_creation_time = 0;
		@connection_time = 0;
		@num_queries = 0;
		@time = 0;
		@last_query_time = 0;

		@name = name
		@idx = idx
	end

	# This function is intended to give a recursive view of the database
	# hierarchy. This can be used to generate structured log output.
	# The default implementation returns {to_s => self}, but aggregates
	# should use it to list their child databases.
	def get_struct()
		return {to_s => self};
	end

	attr_writer :slow_time;
	attr_reader :num_queries, :query_time;


	# return an array of the underlying db handles. Being a single single connection, this is just for compatability
	def get_split_dbs()
		return [ self ];
	end

	def num_dbs()
		return 1;
	end

	#in the single database case, squery throws away the keys, and uses the database defined query function
	def squery(keys, query, *params, &block)
		query(query, *params, &block)
	end

	#repeat a query until it stops changing stuff
	#generally useful for big updates/deletes that would block for a long time
	def repeat_query(query, limit = 1000)
		begin
			query(query + " LIMIT " + limit);
		end while(affected_rows() == limit);
	end

	# Replace placeholders in query with the parameters,
	# placeholders will be replaced with their escaped equivalent.
	# * strings and symbols will be put into quotes, while ints won't
	# * true/false becomes 'y'/'n'
	# * nil becomes NULL
	# * passing an array as a parameter will put it in as a comma separated list
	#   wrapped with (), arrays may be nested
	# The placeholder # will give the extra comment used for server balancing
	def prepare(query, *params)
		raise ParamError, "Wrong param count on query #{query}: #{params.inspect}" if (query.gsub(/[^#?]/,'').length != params.length);
		query.gsub!(/([#?])/) {
			prepare_object(params.shift, ($1 == '#' ? 3 : 0));
		}
		return query;
	end

	#extract the keys from a query. This implementation does not edit out the comments from the query
	def get_server_values(query)
		ids = [];
		
		if(!query['\\']) #simple way that works if nothing is escaped
			#take out all strings, then parse out all the server key comments
			ids = query.gsub(/'.*?'/, "").scan(/\/\*\*%: ([0-9,-]+) :%\*\*\//).map{|a| a[0].split(',').map{|b| b.to_i} }

		else # more correct, but slower
		
			quote = false;
			escape = false;

			offset = -1;
			query.each_char { |c|
				offset += 1;

				#only look for the comments if not in a string
				if(!quote)
					if(c == '"' || c == "'")
						quote = c;
						next;
					end

					#do c == / as an optimization, and the regex to find the actual comments
					if(c == '/' && matches = query[offset, (query.length - offset)].match(/^(\/\*\*%: ([0-9,-]+) :%\*\*\/)/) )
						ids.push(*(matches[2].split(',').map {|i| i.to_i})); #keep the ids list in the comment
						query.slice!(offset, matches[1].length); #remove the comment from the query
					end
				else
				#if in a string, look for its closing quote
					if(!escape && c == '\\')
						escape = true;
					else
						if(!escape && c == quote)
							quote = false;
						end
						escape = false;
					end
				end
			}
		end

		return (ids.empty? ? false : ids.uniq );
	end

	# wrap a block in a transaction.
	# if a transaction is already open, commit it and start a new one
	# if it fails, roll back
	def transaction()
		commit();
		start();

		begin
			yield self;
		rescue QueryError => e
			return rollback();
		end

		return commit();
	end

	Log = Struct.new(:db, :time, :query, :should_explain, :backtrace);
	class Log
		def to_s()
			format(%Q{%s [%.3f msec] "%s" }, db, time * 1000, query);
		end

		def explain()
			return should_explain && db.query("EXPLAIN #{query}").fetch;
		end
	end

	# called from the query command to log queries if needed
	def debug(query, query_time)
		if(@debug_level == 0 || query =~ /^EXPLAIN/)
			return;
		end

		if(@debug_level == 1 &&
		 	!(query_time < @slow_time ||
		 	  (@debug_tables && query =~ /SELECT[^;]+FROM[^;]*[,\.\s`]({#@debug_tables.join('|')})([`\s]?|[`,\s][^;]*)/) ||
			  (@debug_regex && query.match(@debug_regex)) ) )
			return;
		end

		explain = (@debug_level == 3) && (query =~ /^SELECT/);
		backtrace = (@debug_level == 3) && caller;

		$log.info(Log.new(self, query_time, query, explain, backtrace), :info, :sql);
	end


	class ParamError < ::SiteError
	end
	class QueryError < ::SiteError
		attr :errno
		attr :error
		def initialize(errno = 0, error = 'Unknown')
			@errno = errno
			@error = error
		end
		def to_s()
			super() + " #{@error} [#{@errno}]"
		end
	end
	class DeadlockError < QueryError
	end
	class CannotFindRowError < QueryError
	end
	class ConnectionError < ::SiteError
	end

	private
	#takes an object and turns it to a quoted string form
	#split is a stack type parameter which outputs the split comment if it is > 0
	# it is used to output them only for the first entry in a multi-dimensional array
	def prepare_object(obj, split)
		obj = demand(obj)
		str = case obj
				when Array
					obj = obj.dup;
					obj.each_with_index {|o, i|
						obj[i] = prepare_object(o, (split == 3 ? split - 1 : split - 1 - i));
					}
					split = 0; #never use a full array as the split string
					'(' + obj.join(',') + ')';
				when Integer #ints don't need escaping
					obj.to_s;
				when Float
					obj.to_s;
				when String
					"'" + quote(obj.convertible_to_utf8) + "'";
				when nil
					"NULL";
				when true
					"'y'";
				when false
					"'n'";
				when Symbol
					"'" + quote(obj.to_s) + "'";
				when MutableTime
					obj.to_i.to_s;
				when Lazy::Promise
					return prepare_object(demand(obj), split);
				else #try .to_s before failing?
					raise ParamError, "Trying to escape an unknown object #{obj.class}";
				end
		if(split > 0)
			str << "/**%: #{str} :%**/";
		end
		return str;
	end
end


