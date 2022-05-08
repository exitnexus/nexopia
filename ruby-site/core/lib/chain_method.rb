module Kernel
	def prechain_method(method_name, &block)
		counter = 0
		old_method_name = :"_prechain_#{method_name.to_s}_#{counter}"
		while (self.method_defined?(old_method_name))
			counter += 1
			old_method_name = :"_prechain_#{method_name.to_s}_#{counter}"
		end
		alias_method old_method_name, method_name.to_sym
		self.send(:define_method, method_name.to_sym) { |*args|
			instance_exec(*args, &block)
			self.send(old_method_name, *args)
		}
	end
	
	def postchain_method(method_name, &block)
		counter = 0
		old_method_name = :"_postchain_#{method_name.to_s}_#{counter}"
		while (self.method_defined?(old_method_name))
			counter += 1
			old_method_name = :"_postchain_#{method_name.to_s}_#{counter}"
		end
		alias_method old_method_name, method_name.to_sym
		self.send(:define_method, method_name.to_sym) { |*args|
			args << self.send(old_method_name, *args)
			instance_exec(*args, &block)
		}
	end
end