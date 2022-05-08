# Generate a class that represents a template object.  The class should be
# generated at compile-time at a one-time overhead, then instanciated quickly
# many times afterwards.

class CodeGenerator
	attr :dependencies, true;
	attr :exports, true;
	
	def initialize(string)
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
				@code << "\t\t__template_output << @@_static_strings[#{@strings.length - 1}];\n";
				@write_open = true;
			end
		end
	end
	
	def append_conditional_print(skin_property, prop, rule_value)
		if(prop.length() < 1)
			return;
		end
		
		@strings << prop;
		@code << "\t\tif(!#{skin_property}.nil?())\n";
		@code << "\t\t\t__template_output << @@_static_strings[#{@strings.length - 1}]\n";
		@code << "\t\t\t__template_output << #{rule_value}\n";
		@code << "\t\tend\n";
		@write_open = false;
	end
	
	def append_string(string)
		append_output("%q|#{string.gsub('|','\|')}|")
	end

	# Apped a print statement for the input paramenter, which is a variable or method call
	# such as "object.to_s"
	def append_output( string )
		@write_open = false;
		@code << "\t\t__template_output << #{string};\n";
	end

	# Generate the class through a mixture of metaprogramming and string evals.
	# Most of the class generation is done through metaprogramming constructs
	# because they are faster and have a more programmatic interface.
	def generate(xml_file_name)
		basename = "#{$site.config.generated_base_dir}/#{@class_name}.gen"
		filename = "#{basename}.rb"

		strings = @strings.map {|str| ("%Q|" + str.gsub("|", "\\|") + "|") }.join(",\n");
		defines = "";
		@exports.each{ |name,dump|
			File.open("#{basename}.#{name}.robj", "w"){|f|
				f.write dump;
			}
			defines << "Template::DefaultView.defines['#{name}'] = Marshal.load(File.open('#{basename}.#{name}.robj').read)\n";
		}
		string = <<-EOF
	@xml_file_name = "#{xml_file_name}";
	@@_static_strings = [
#{strings}
	];

	#{defines}

	def method_missing(meth, *args)
		self.class.module_eval {
			attr meth.to_s.chomp('=').to_sym, true;
		}

		self.send(meth, *args)
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
		__template_output = [];
#{@code}
		return __template_output.join('');
	end
			EOF
		rescue SyntaxError => err
			raise c.handle_syntax_err("Error compiling", err)
		end

		#c.class_eval(string);
				
		f = File.new(filename, "w")
		$log.info("Writing #{@class_name}", :debug, :template);
		f.write("#dependencies=#{@dependencies.join(',')}\n");
		f.write("#lib_require :core, 'template/default_view'\n");
		f.write("class #{@class_name}\n")
		f.write(string);
		f.write("end\n");
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
