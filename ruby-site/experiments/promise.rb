class Promise
	alias promise_instance_eval instance_eval;

	def initialize(&block)
	   @block = block;
	   @obj = nil;
	end

	def method_missing(symbol, *args)
		if (!@obj)
			@obj = @block.call();
		end
		promise_instance_eval(<<-END
				def self.#{symbol}(*args)
					@obj.send(:#{symbol}, *args);
				end
			END
		);

		@obj.send(symbol, *args);
	end

	# redefine most (all?) of the Object functions to push them to the enclosed object
	[:==, :===, :=~, :__send__, :send, :class, :clone, :display, :dup,
	 :enum_for, :eql?, :equal?, :extend, :freeze, :frozen?, :hash, :id, :inspect,
	 :instance_eval, :instance_of?, :instance_variables_get, :instance_variable_set,
	 :instance_variables, :is_a?, :kind_of?, :method, :methods, :nil?, :object_id,
	 :private_methods, :protected_methods, :public_methods, :remove_instance_variable,
	 :respond_to?, :send, :taint, :tainted?, :to_a, :to_enum, :type, :to_s, :untaint
	].each { |sym|
		define_method(sym) {|*args|
			method_missing(sym, *args);
		}
	}
end

myint = Promise.new { 1; }
puts("#{myint}");
puts("#{myint+1}");
puts("#{myint+2}");
