module Enumerable
	# Collects the items in the collection into a hash. Each yield should return
	# an array of two items.
	def collect_hash()
		hash = Hash[*collect {|item| yield(item)}.flatten];
	end
end

class Hash
	# Returns any item from the collection.
	def any()
		a = nil;
		each {|key, val| a = val; break; }
		return a;
	end
end
