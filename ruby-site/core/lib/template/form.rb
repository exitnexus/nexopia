# This class is a reference to an instance variable. Calls to instance methods
# will be forwarded to the referenced object.
class Ref

	include Comparable
	def <=>(other)
		return ref <=> other;
	end
	
	def initialize(object, symbol)
		@object = object;
		@symbol = symbol;
	end
	
	def ref=(val)
		@object.send("#{@symbol}=", val);
		#@object.instance_variable_set("@#{@symbol}", val)
	end

	def ref
		@object.send("#{@symbol}");
		#@object.instance_variable_get("@#{@symbol}")
	end
	
	def method_missing(method_name, *args)
		ref.send(method_name, *args);
	end
	
	def to_s
		return ref.to_s;
	end
	
end

=begin
# This class lets you get an object with refs to all of the instance variables.
# 
class RefHolder
	def initialize(obj)
		obj.instance_variables.each{|var|
			ref = Ref.new(obj.instance_variable_get(var));
			obj.instance_variable_set(var, ref);
			self.instance_variable_set(var, ref);
		}
	end
	
	def method_missing(method, *args)
		if (method.to_s[-1..-1] == "=")
			self.instance_variable_set(:"@#{method.to_s[0..-2]}", *args);
		else
			self.instance_variable_get(:"@#{method}")
		end
		
	end
	
end
=end

# Allows you to get a reference to one of this object's instance variables. The
# return value is a Ref object.
class Object
	def get_ref(sym)
		ref = Ref.new(self, sym);
		#self.instance_variable_set(:"@#{sym}", ref);
		return ref;
	end
end
  

class Form
	attr :bindings;
	def initialize
		@bindings = Array.new();
	end
	
	def Form.declare_form(&block)
		form = Form.new()
		yield block
		return form;
	end
	
	# Binds an instance var on 'object' to a template var.
	def bind( object_symbol, template_symbol )
		@bindings << [object_symbol, template_symbol];
	end
	
	def load_to_form(template)
		@bindings.each{|(object_ref, template_symbol)|
			template.instance_variable_set("@#{template_symbol}", object_ref);
		}
	end
	def unload_from_form(params)
		@bindings.each{|(object_ref, template_symbol)|
			val = params[template_symbol, String];
			object_ref.ref = val;
		}
	end
	
end