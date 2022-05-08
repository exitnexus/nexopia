class Relation < Lazy::Promise
	AUTO_PRIME_DEFAULT = true
	def initialize(origin_instance, prototype, options)
		@origin_instance = origin_instance #the instance the relation is attached to
		@prototype = prototype #The RelationPrototype that describes this relation
		@options = options || {}
		
		#calculate the actual StorableID objects we'll be using for registering promises
		#some subclasses will recalculate this, relation_multi does to utilize memcached id lists
		@ids = @prototype.target.group_ids(query_ids, @prototype.extracted_options[:index], @prototype.extracted_options[:selection])
		
		register_storable_promise
		super(&self.__method__(:load_relation)) #call Lazy::Promise constructor with the appropriate function for execution
	end

	def auto_prime?
		return @prototype.auto_prime?
	end

	def execute
		raise "Attempted to execute from class Relation. Only subclasses of Relation should be executed."
	end

	#tells storable what ids we are promising to fetch (if they are cacheable)
	#this can be overriden if any calculations need to be done to determine the correct
	#ids.  If nothing is registered everything will continue to work fine but we will not
	#be able to aggregate loading between multiple relations
	def register_storable_promise
		@ids.each {|id|
			@prototype.target.promised_ids[@prototype.extracted_options[:selection]] ||= {}
			@prototype.target.promised_ids[@prototype.extracted_options[:selection]][id] = true
		}
	end
	
	#puts the relation back into an uncalculated state forcing it to reinitialize the next time it is accessed
	def invalidate
		@computation = self.__method__(:load_relation).to_proc #this relies on how Lazy::Promise checks internally to determine if it needs to execute
	end
	
	def load_relation
		result = execute
		if (result.kind_of?(Enumerable))
			result.each {|r|
				if (r.respond_to?(:relations_to=))
					r.relations_to ||= []
					r.relations_to << self
				end
			}
		else
			if (result.respond_to?(:relations_to=))
				result.relations_to ||= []
				result.relations_to << self
			end
		end
		return result
	end
	
	def prime(method)
		$log.info "Auto priming #{method} for #{@prototype}", :info, :sql
		if (@result.kind_of?(Enumerable))
			@result.each {|r|
				r.send(method)
			}
		end
	end

	#if we're going to store the relation in memcache, this is the key that will be used
	def cache_key
		return @prototype.cache_key(@origin_instance)
	end

	def query_ids
		return @prototype.origin_columns.map {|col| @origin_instance.send(col)}
	end
	
	class << self
		#this can be overriden in relations that don't use memcache or that use it selectively to avoid
		#unnecessary invalidations against the memcache server
		def memcache?
			return true
		end
	end
end

class Storable
	attr_accessor :relations_to
	
	#invalidate all relations dependent on columns, passing in nil invalidates all columns
	def invalidate_relations
		RelationManager.invalidate(self)
	end

	class << self
		#if you try and use relation_type instead of just calling relation this will remap it for you
		def method_missing(method, *args)
			if (method.to_s =~ /^relation_(.+)/)
				type = $1.to_s
				self.send(:relation, type, *args)
			else
				super
			end
		end

		#defines a relation of type with the accessor name, using idcols from the original object as ids for querying against table
		#any extra args are passed through to the find call on table
		def relation(type, name, idcols, table, *args)
			# Code to check that this relation is being correctly setup to avoid really strange errors down the line.
			if (self.columns.nil?)
				raise "Bad Developer! You're probably trying to set up a relation before doing init_storable. Make sure init_storable comes first."
			end
			[*idcols].each {|idcol|
				if(!self.columns.include?(:"#{idcol.to_s}"))
					raise "#{idcol.to_s} is not a column on #{self.name}! Bad Developer!"
				end
			}
			# Done self-checking code
			
			RelationManager.create_prototype(name, type, self, idcols, table, *args)
			define_method name, lambda { |options|
				@relations_from ||= {}
				if (!@relations_from[name])
					@relations_from[name] = RelationManager.create_relation(self.class, name, self, options)
					if (self.relations_to)
						self.relations_to.each {|relation|
							if (relation.auto_prime?)
								relation.prime(name)
							end
						}
					end
				end
				return @relations_from[name]
			}
		end
	end #storable class methods
end