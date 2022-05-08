lib_require :Core, "template/html_doc"
lib_require :Core, "template/code_generator"
# Generate a class that represents a template object.  The class should be
# generated at compile-time at a one-time overhead, then instanciated quickly
# many times afterwards.

=begin rdoc
  TemplateHelpers are methods common to all Templates instances.

  This module is mixed in when the class is constructed

=end
module TemplateHelpers
=begin rdoc
	# Thomas's comment: I don't think this actually helps anything.
  	 Multiple calls of display are going to end up duplicating
		 all the output unless there is a way to use a cached result

		 In the event that the developer needs to re-run display and
		 have it re-execute the code the method 'reset_output' can
		 be used.
=end
		def reset_display
		    @output = StringIO.new();
		    @cached_output = nil
		end
end

class TemplateClassGenerator < CodeGenerator
	attr :vars, true;
	attr :position_stack, true;

	def initialize(string)
		super(string)
		@con = 0;
		@indent = 0;
		@position_stack = Array.new();
		@code << "@stack_trace_pos = 0;\n";
	end

	class FakeAnything
		keepers = ["method_missing", "undef_method", "to_a", "respond_to?", 
			"vars", "vars=", "instance_eval", ]
		instance_methods.each{|method|
			if ( !keepers.index(method.to_s) and !method.to_s.index("__"))
				if (respond_to? method)
					self.send(:undef_method, method);
				end
			end
		}
		
		def method_missing(meth, *args)
			return self;
		end
	end
	
	class VariableFinder
		attr :vars, true;

		keepers = ["method_missing", "undef_method", "to_a", "respond_to?", 
			"vars", "vars=", "instance_eval"]
		instance_methods.each{|method|
			if ( !keepers.index(method.to_s) and !method.to_s.index("__"))
				if (respond_to? method)
					self.send(:undef_method, method);
				end
			end
		}
		
		def initialize
			@vars = Array.new();
			@fake = FakeAnything.new();
		end
		
		def method_missing(meth, *args)
			if (args.length == 0)
				@vars << meth;
			end
			return @fake;
		end
	end
	
	def add_var_to_table(variable)
		vf = VariableFinder.new();
		begin
			vf.instance_eval(variable);
		rescue Exception => e
			#raise Exception.new("Invalid variable '#{variable}' caused the following exception:\n#{$!}");
		end
		#NilClass.send(:undef_method, :method_missing);
		vf.vars.each{|var|
			add_var(var.to_s, "Object");
		}
	end

	# Simply for HTML output.  This can easily be disabled in the
	# deployed version.
	def push_indent
		@indent += 1;
	end

	def pop_indent
		@indent -= 1;
	end

	def pop_position
		pos = @position_stack.pop()
		@con +=1;
		if (pos != nil)
			@code << ("\n@stack_trace_pos = #{pos}\n");
		end
		@write_open = false;
#		@code << "$log.info #{@con}\n";
	end
	
	def push_position(pos)
		@position_stack.push pos;
		@con +=1;
		if (pos != nil)
			@code << ("\n@stack_trace_pos = #{pos}\n");
		end
		@write_open = false;
#		@code << "$log.info #{@con}\n";
		
		
	end
	

	# Assert that the object val is of the specified class type.
	def TemplateClassGenerator.assert_type(val, type)
		if (!val.kind_of?(type))
			#raise "#{val} (#{val.class}) is not a(n) #{type}.";
		end
	end

	# Return the class type registered in the symbol table for the symbol name 'var'.
	def get_var_type(var)
		return @vars[var];
	end

	# Add a variable 'var' with class 'type' to the symbol table.
	def add_var(var, type)
		if (var == "")
			raise "Total error here."
		end
		if (!Module.const_get(type))
			raise "Invalid type #{type}.";
		end
		@vars[var] = Kernel.const_get(type);
		#$stderr.puts("Making #{var} of type #{type} : #{@vars[var]}.\n");
	end

	# Assert that the variable has been registered with the symbol table already.
	def assert_var_exists(var)
		if (!@vars[var])
			#$log.info("Variable '#{var}' used in template, but never declared.", :debug, :template);
		end
	end

end

