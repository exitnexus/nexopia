class Storable
	#StorableID is used internally to organize ids that are being queried for
	#this includes the ids (possibly partially id) of an object and the index the
	#ids should be used against
	class StorableID
#		StorableClass = Storable
		
		attr_accessor :id, :index, :selection
		attr_reader :index_name
		
		def initialize(id, index_sym=:PRIMARY, selection=nil)
			raise SiteError if id.first.kind_of? StorableID
			self.id = id
			self.index = index_sym
			
			if (selection.kind_of? StorableSelection)
				self.selection = selection.symbol if selection
			else
				self.selection = selection
			end
			@cache_key = nil
		end
		
		def to_s
			"#{self.class::StorableClass}_#{self.index_name}-#{self.id.join('/')}"
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
		
		def split?()
			key_split = false;
			self.id.each_with_index{|contents, i|
				if(self.class::StorableClass.split_column?(self.index[i]))
					key_split = true;
				end
			};
			
			return key_split;	
		end
		
		def condition(force_no_split = false)
			key_strings = []
			
			self.id.each_with_index {|contents, i|
				if (self.class::StorableClass.split_column?(self.index[i]) && !force_no_split)
					key_strings << "(#{self.index[i]} = #)"
				else
					key_strings << "(#{self.index[i]} = ?)"
				end
			}
			return ["(#{key_strings.join(' && ')})", *self.id]
		end
		
		#key format is roughly: Module::Class_index-id1/id2
		# If the primary key changes after this is first called, it may break
		def cache_key
			return @cache_key if @cache_key
			@cache_key = if self.primary_key?
				"#{self.class::StorableClass}-#{self.id.join('/')}"
			else
				"#{self.class::StorableClass}_#{self.index_name}-#{self.id.join('/')}"
			end
			@cache_key << "-#{self.selection}" if self.selection
			return @cache_key
		end
		
		def ==(other)
			return other.class.const_defined?("StorableClass") && 
				self.class::StorableClass == other.class::StorableClass &&
				self.id == other.id && self.index == other.index && 
				self.selection == other.selection
		end
		
		def eql?(other)
			return self.==(other)
		end
		
		def properties_hash
			properties = {}
			index.each_with_index { |key, i|
				properties[key.to_sym] = id[i] if id[i]
			}
			return properties
		end
		
		def hash
			return [self.id, self.index, self.selection, self.class::StorableClass].hash
		end
		
		#returns true if the storable element has modified entries that would affect its positioning
		#within the result set of the key ie. either it moved into or out of the set matched by the key.
		def match_modified?(storable_element)
			return false unless storable_element.kind_of? self.class::StorableClass
			modified = storable_element.modified_columns.map {|col| col.to_s}
			#If the intersection of the key columns and the modified columns is non empty then
			#we proceed to see if the ids match
			if (!(self.index[0,self.id.length] & modified).empty?)
				return (self === storable_element || self.match_original?(storable_element))
			else #we didn't modify any of the key columns
				return false
			end
		end
		
		#works like === but uses original values from __modified__ when they exist
		def match_original?(storable_element)
			return false unless storable_element.kind_of? self.class::StorableClass
			self.id.each_with_index {|id, i|
				id = id.to_s if (id.kind_of? Symbol)
				begin
					#checks against the original version from __modified__ if it exists, otherwise check against the current version
					if (storable_element.modified?(self.index[i]))
						return false unless (id == storable_element.__modified__[self.index[i]])
					else
						return false unless (id == storable_element.send(self.index[i]))
					end
				rescue SiteError #Can happen if storable_element is a selection that doesn't contain the column this index is on
					return false
				end
			}
		end
		
		#returns true if the storable element would be part of the result set described by this id
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