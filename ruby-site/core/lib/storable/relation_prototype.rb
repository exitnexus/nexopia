class RelationPrototype
	attr_reader :type, :name, :origin, :target, :origin_columns, :target_columns, :extra_columns,
	 	:relation_options, :find_options, :extracted_options
	
	def initialize(name, type, origin, columns, target, *options)
		@type = Kernel.const_get("Relation" + type.to_s.gsub(/(^|_)(\w)/) {|match| $2.capitalize})
		@name = name #name of the relation, used for caching, method definitions, etc

		@origin = origin #the originating table of the relation
		@target = target #the target table of the relation

		@relation_options = self.class.extract_options_from_args!(options) #extract any options that are specific to relations and stores them in @relation_options

		#use the storable extract options so that we can find an index if there is one specified
		#then rebuild the options passed in so it can be sent to find when needed
		@extracted_options = self.target.extract_options_from_args!(options)
		@extracted_options[:index] ||= :PRIMARY #use primary unless an index is set
		
		options << @extracted_options
		@find_options = options #the options that will be passed through in find calls

		@origin_columns = [*columns] #the columns used to find the relation from the origin
		@target_columns = self.target.indexes[@extracted_options[:index]].map {|column| column.to_sym} #the columns used to find the relation from the target

		if ((@extracted_options[:conditions] || @extracted_options[:order]) && !@relation_options[:extra_columns])
			raise Exception.new("Specified conditions to a relation without specifying the extra columns for invalidation.  Use :extra_columns => [:col1, :col2, ...].")
		end
	end
	
	def to_s
		return "#{origin}>#{name}"
	end
	
	def auto_prime?
		if (@relation_options[:auto_prime].nil?)
			return @type::AUTO_PRIME_DEFAULT
		else
			return @relation_options[:auto_prime]
		end
	end
	
	def extra_columns
		return [*@relation_options[:extra_columns]] || Array.new
	end
	
	#builds a relation object based on this prototype from origin_instance
	def create_relation(origin_instance, options)
		self.type.new(origin_instance, self, options)
	end

	#takes a storable instance of class origin or target and generates the corresponding relation cache key
	def cache_key(instance)
		if (instance.class == origin)
			columns = origin_columns
		elsif (instance.class == target)
			columns = target_columns[0,origin_columns.length]
		else
			raise "Attempt to generate cache key for relation #{origin}-#{name} using an instance of class #{instance.class}."
		end
		ids = columns.map {|col| instance.send(col)}
		return "#{origin.prefix}_#{name}_relation-#{ids.join('/')}"
	end
	
	#true if this relation could be invalidated by the change of the specified columns on table
	def match?(instance, columns=nil)
		table = instance.class
		#if no columns are passed in then we only need to check the class
		if (columns.nil?)
			result = (table == origin || table == target)
		elsif (table == origin)
			#intersect the two columns arrays and if it's not empty then we care about changes
			result = !(columns&origin_columns).empty?
		elsif (table == @target)
			#intersect the two columns arrays and if it's not empty then we care about changes
			result = !(columns&(target_columns+extra_columns)).empty?
		else
			result = false
		end
		return result
	end

	class << self
		#removes relation specific options from the options array and returns them
		def extract_options_from_args!(options)
			matched_options = {}
			options.each {|option|
				if (option.kind_of? Hash)
					option.each {|key, val|
						if (relation_option?(key))
							matched_options[key] = val
						end
					}
				elsif (relation_option?(option))
					matched_options[option] = true
				end
			}
			return matched_options
		end
		
		#this takes a symbol and determines if it controls a relation specific option
		#the internal implementation should be updated if we get many or complex options here
		def relation_option?(key)
			return (key == :auto_prime) || (key == :extra_columns)
		end
	end #class << self
end