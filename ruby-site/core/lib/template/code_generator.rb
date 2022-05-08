class CodeGenerator
	attr :dependencies, true;
	attr :exports, true;
	
	def initialize(string)
		@vars = Hash.new;
		@class_name = string;
		@code = Array.new();
		@strings = Array.new();
		@write_open = false;
		@exports = {};
		@dependencies = [];
	end
	
		# Append a piece of code, such as "gork = bobble.find("frunk");"
	def append(string)
		@code << string;
		@write_open = false;
	end

	# Append a print statement for the parameter string, such as "Hello, world".
	def append_print(string)
		if (string.length < 1)
			return
		end
		#@code << "@output << '" + ("\t"*@indent) + "' + %Q|" + string.gsub("|", "\\|") + "\\n|;\n";
		if (string.index('#{'))
			match = string.match(/\#\{([^\}]+)\}/)
			append_print($`) if $`
			append_output($1) if $1
			append_print("#{$'}") if $'# Stupid eclipse parsing error
		else
			if (@write_open)
				@strings[-1] += string
			else
				@strings << string
				@code << "@output << _static_strings[#{@strings.length - 1}];\n";
				@write_open = true;
			end
		end
	end
	
	def append_string(string)
		append_output("%q|#{string}|")
	end

	# Apped a print statement for the input paramenter, which is a variable or method call
	# such as "object.to_s"
	def append_output( string )
		@write_open = false;
		@code << "@output << #{string};\n";
	end

	# Generate the class through a mixture of metaprogramming and string evals.
	# Most of the class generation is done through metaprogramming constructs
	# because they are faster and have a more programmatic interface.
	def generate(xml_file_name)
		basename = "generated/#{@class_name}.gen"
		filename = "#{basename}.rb"
		temp = @vars;
		temp_code = @code;

		vars = "";
		temp.each{|var|
			if (vars.length > 0)
				vars = vars + ",";
			end
			vars = vars + "[\"#{var[0]}\", \"#{var[1]}\"]";
		}
		string_array = "";
		@strings.each{|str|
			if (string_array.length > 0)
				string_array += ",\n";
			end
			string_array += ("%Q|" + str.gsub("|", "\\|") + "|");
			
		}
		defines = "";
		@exports.each{ |name,dump|
			File.open("#{basename}.#{name}.robj", "w"){|f|
				f.write dump;
			}
			defines << "Template::DefaultView.defines['#{name}'] = Marshal.load(File.open('#{basename}.#{name}.robj').read)\n";
		}
		string = <<-EOF
  	@xml_file_name = "#{xml_file_name}";
	@vars = [#{vars}];	
	@_static_strings = [#{string_array}];
	
	attr :this_page, true;
	attr :host, true;

	#{defines}
	
	def initialize()
    	@this_page = "";
	end
	
	def method_missing(meth, *args)
		if meth.to_s[-1..-1] == "="
			self.instance_variable_set(:"@\#{meth.to_s[0...-1]}", *args)
		else
			self.instance_variable_get(:"@\#{meth}")
		end
	end
	
		EOF
		
		@vars.each{ |var, type|
			begin
			string << <<-EOF
	attr :#{var}, false;
	def #{var}=(val)
		send :instance_variable_set, "@#{var}", val;
	end
			EOF
			rescue
				$log.object variable_positions, :error;
				raise
			end
		}
		string << <<-EOF
	class << self
		def _static_strings
			return @_static_strings
		end
		
		def get_vars
			return @vars;
		end
	end
		EOF
		# The "display" method is created as an evaluation of a string,
		# because it is a complex chain of arbitrary commands.  The same results
		# could be achieved with a list of Proc objects, but the proc objects incur
		# an overhead and benchmarks have convinced me that eval is the fastest
		# method for this.


		#$log.info "Evaling...\n#{@code}"
		begin
			string << <<-EOF
	def display()
		@output = StringIO.new();
		_static_strings = self.class._static_strings
			#{@code}
		return @output.string;
	end
			EOF
		rescue SyntaxError => err
			raise c.handle_syntax_err("Error compiling", err)
		end

		#c.class_eval(string);
				
		f = File.new(filename, "w")
		$log.info("Writing #{@class_name}", :debug, :template);
		f.write("#dependencies=#{@dependencies.join(',')}\n");
		f.write("lib_require :core, 'template/default_view'\n");
		f.write("class #{@class_name}\n")
		f.write(string);
		f.write("end");
		f.flush();
		f.close();
		begin
			$log.info("Requiring #{@class_name}", :debug, :template);
			load(f.path);
		rescue Exception => e
			$log.info "#{$!}:#{$!.backtrace}", :error;
		end

		$log.info("Done.", :debug, :template);
		#@code.html_dump;
		#puts @code;
	end

end
