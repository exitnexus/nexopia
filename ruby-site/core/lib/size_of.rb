#This code is based on the information found at http://eigenclass.org/hiki.rb?ruby+space+overhead
class Object
	MALLOC_OVERHEAD = 4; #number of overhead bytes per chunk allocated by malloc
	RVALUE_SIZE = 20; #basic size of a ruby rvalue
	BYTES_PER_WORD = 4; #32-bit
	DEFAULT_BINS = 55; #number of instance variables included in the default objec

	def size_of_instance
		extra_bins = if (instance_variables.size - DEFAULT_BINS > 0)
			instance_variables.size - DEFAULT_BINS
		else
			0
		end
		return RVALUE_SIZE + 16 + 11*BYTES_PER_WORD + instance_variables.size*(4*BYTES_PER_WORD+MALLOC_OVERHEAD) + extra_bins*BYTES_PER_WORD + MALLOC_OVERHEAD*2;
	end
end

class Numeric
	def size_of_instance
		return RVALUE_SIZE;
	end
end

class NilClass
	def size_of_instance
		return RVALUE_SIZE;
	end
end

class FalseClass
	def size_of_instance
		return RVALUE_SIZE;
	end
end

class TrueClass
	def size_of_instance
		return RVALUE_SIZE;
	end
end

class Symbol
	def size_of_instance
		byte_length = self.to_s.size+1;
		if (byte_length < 16)
			byte_length = 16;
		end
		if (byte_length%8 != 0) #%8 #This comment added to make eclipse syntax highlight properly
			byte_length += 8-(byte_length%8); #%8 #This comment added to make eclipse syntax highlight properly
		end
		return byte_length + 2*(16+MALLOC_OVERHEAD);
	end
end

class Array
	def size_of_instance
		length = if (self.size < 16)
			16
		else
			self.size * 1.5
		end
		
		#For arrays larger than length 16 this is only an approximation.
		return RVALUE_SIZE + length*BYTES_PER_WORD + MALLOC_OVERHEAD;
	end
end

class Hash
	#This function uses information garnered from http://www.artima.com/forums/flat.jsp?forum=123&thread=186974
	#as well as eigenclass.
	def size_of_instance
		return RVALUE_SIZE + [self.size,4].max*(16+MALLOC_OVERHEAD) + [self.size/5, 11].max*4+MALLOC_OVERHEAD + 4*BYTES_PER_WORD; #/ #comment for eclipse syntax highlighting
	end
end

class Struct
	def size_of_instance
		return RVALUE_SIZE + self.size*(BYTES_PER_WORD + MALLOC_OVERHEAD);
	end
end