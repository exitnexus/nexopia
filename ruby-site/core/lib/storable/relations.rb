# This module defines some basic relationships between storable objects to make
# developing them easier.
module Relations
	
	def invalidate_relation(storable)
		
	end
	
	# Maps id column(s) to a single row in another table's pkey.
	# First argument is a name for the mapping (ie. :category), through
	# which it will be accessed, idcols is either a single symbol or an
	# array of symbols that identify the columns used in the other table's
	# find() call. table is a reference to the class object that the other table is in
	# and promise identifies whether or not it should do it through a promise
	# (defaults to true).
	def relation_singular(name, idcols, table, promise = true)
		idcols = [*idcols];
		idcols.each{|col|
			postchain_method(:"#{col}="){
				@storable_relations = Hash.new unless (@storable_relations)
				@storable_relations.delete(name)
			}
		}
		define_method name, lambda {
			@storable_relations = Hash.new unless (@storable_relations)
			if (@storable_relations[name])
				return @storable_relations[name]
			end
			id = idcols.collect {|col| send(col); }
			table.register_relation(self, name, id)
			if (promise)
				id.unshift(:promise)
			end
			@storable_relations[name] = table.find(:first, *id)
			return @storable_relations[name]
		}
	end

	# Maps id column(s) to any number of rows in another table's pkey.
	# See Relations#relation_singular for details on the arguments. This function
	# differs in that it uses :all instead of :first. It also takes any extra
	# arguments and passes them into find when it's called, either from the
	# initial argument or the generated one.
	def relation_multi(name, idcols, table, *opts)
		idcols = [*idcols];
		idcols.each{|col|
			postchain_method(:"#{col}="){
				@storable_relations = Hash.new unless (@storable_relations)
				@storable_relations.delete(name)
			}
		}
		define_method name, lambda {|*rest|
			@storable_relations = Hash.new unless (@storable_relations)
			if (@storable_relations[name])
				return @storable_relations[name]
			end
			ids = idcols.collect {|col| send(col); }
			table.register_relation(self, name, ids)
			id = opts + rest + ids
			@storable_relations[name] = table.find(:all, :promise, *id)
			return @storable_relations[name]
		}
	end

	# Maps id column(s) to rows in a nother table's pkey, but with added arguments
	# to make it so you can page between them.
	# See Relations#relation_multi for details on the arguments.
	# The function that's generated will have two+ arguments, page, pagecount,
	# and any other arguments are passed to find.
	def relation_paged(name, idcols, table, *opts)
		idcols = [*idcols];
		idcols.each{|col|
			postchain_method(:"#{col}="){
				@storable_relations = Hash.new unless (@storable_relations)
				@storable_relations.delete(name)
			}
		}
		define_method name, lambda {|page, pagecount, *rest|
			order = rest.pop;
			
			ids = idcols.collect {|col| send(col); }
			table.register_relation(self, name, ids)
			id = opts + order + ids

			return table.find(:all, :total_rows,
				:offset => page * pagecount, :limit => pagecount, *id);
		}
	end
	
	# Works just like Relations#relation_multi, but caches the resulting list.
	# Specifically, caches a list of the primary ids matching the relations.
	# See Relations#relation_singular for details on the arguments. 
	def relation_multi_cached(name, idcols, table, prefix, *opts)
		table.memcache_watch_key(prefix, idcols, *opts)
		idcols = [*idcols];
		idcols.each{|col|
			postchain_method(:"#{col}="){
				@storable_relations = Hash.new unless (@storable_relations)
				@storable_relations.delete(name)
			}
		}
		define_method name, lambda {|*rest|
			@storable_relations = Hash.new unless (@storable_relations)
			if (@storable_relations[name])
				return @storable_relations[name]
			end
			ids = idcols.collect {|col| send(col); };
			table.register_relation(self, name, ids)
			findargs = opts + rest
			@storable_relations[name] = table.memcache_find(prefix, ids, 86400, :all, *findargs)
			return @storable_relations[name]
		}
		define_method :"#{name}_ids", lambda {|*rest|
			@storable_relations = Hash.new unless (@storable_relations)
			if (@storable_relations[:"#{name}_ids"])
				return @storable_relations[:"#{name}_ids"]
			end
			ids = idcols.collect {|col| send(col); };
			table.register_relation(self, name, ids)
			findargs = opts + rest
			@storable_relations[:"#{name}_ids"] = table.memcache_find_ids(prefix, ids, 86400, :all, *findargs)
			return @storable_relations[:"#{name}_ids"]
		}
	end
	
	
	#object: the object the relation is on (eg. user in user.pics)
	#name: the name of the relation (eg. :pics)
	#ids: the ids used in the query for the relation (eg. [200, 1] for userid=200, picid=1)
	#index: the index the ids utilize
	#Once a relation is registered here for a particular object instance, if any part of the relation is invalidated
	#the entire relation will be invalidated.  (eg. if a pic is deleted the cached value of user.pics is also deleted)
	#TODO: make the relation_(singular|multi|cached|paged) methods aware of indexes other than primary so they can
	#utilize this properly on arbitrary indexes
	def register_relation(object, name, ids, index=:PRIMARY)
		@@registered_relations ||= []
		@@registered_relations << [object, name, self::StorableID.new(ids, index)]
	end
	
	def self.extended(klass)
		klass.send(:define_method, :invalidate_relation_cache) {
			@@registered_relations ||= []
			@@registered_relations.each {|(object, name, storable_id)|
				if (storable_id === self)
					object.invalidate_relation(name)
				end
			}
		}
		klass.send(:define_method, :invalidate_relation) { |name|
			@storable_relations ||= Hash.new
			@storable_relations[name] = nil
		}
	end
	
end
