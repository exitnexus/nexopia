
module Test::Unit::Assertions

	#test whether a klass implements a certain function. 
	def assert_class_respond_to(klass, function)
		assert(klass.instance_method(function));
	end
end
