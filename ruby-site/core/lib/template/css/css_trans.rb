lib_require :core, 'template/css/csstrans.ErrorStream'
lib_require :core, 'template/css/csstrans.Parser'
lib_require :core, 'template/css/csstrans.Scanner'
lib_require :core, 'template/code_generator'
lib_require :core, 'template/generated_cache'

module CSSTrans

	@@instantiatedClasses = Hash.new;
	def self.instantiatedClasses
		return @@instantiatedClasses;
	end

	@@instantiatedUserClasses = Hash.new;
	def self.instantiatedUserClasses
		return @@instantiatedUserClasses;
	end

	def self.get_file_path(mod, css_file)
		f = "#{$site.config.site_base_dir}/#{mod.to_s.downcase}/#{css_file}.css";
	end

	def self.get_name(mod, css_file)
		name = "CSSTemplate_" + mod.to_s.upcase + "_" + css_file.gsub(/[^a-zA-Z0-9]/, "_").upcase;
	end

	class Cache < GeneratedCodeCache
		def self.library
			Dir["core/lib/template/css/*.rb"];
		end
		def self.prefix
			"CSSTemplate_";
		end
		
		def self.parse_dependency(dependency)
			return dependency.split(":");
		end
	
		def self.parse_source_file(_module, file_base)
			"#{_module}/#{file_base}.css"
		end
		
		def self.class_name(_module, file_base)
			"CSSTemplate_#{_module.to_s.upcase}_#{file_base.gsub(/[^\w]/, '_').upcase}"
		end

		def self.output_file(_module, file_base)
			"generated/CSSTemplate_#{_module.to_s.upcase}_#{file_base.gsub(/[^\w]/, '_').upcase}.gen.rb"
		end
		
		def self.source_dirs(mod)
			["#{mod.directory_name}"]
		end
		def self.source_regexp()
			/((?:layout|control)\/[^.\/]+).css/
		end

		@@instantiatedClasses = {};
		@@instantiatedUserClasses = {};
		@@times = {}
		def self.instantiatedClasses
			return @@instantiatedClasses;
		end

		@@instantiatedUserClasses = Hash.new;
		def self.instantiatedUserClasses
			return @@instantiatedUserClasses;
		end
	end
	
	def self.from_file(mod, css_file)
		name = get_name(mod, css_file);
		f = get_file_path(mod, css_file);
		#FileChanges.register_file(f);
		file = File.basename(f)
		dir = File.dirname(f)

		css_string = File.open(f).read();
		return CSSClass.new(name, f, [mod, css_file], css_string);
	end

	public; def self.instance(css_file)
		css_file =~ /^(\w+)\/(.+).css$/
		mod,file = $1,$2
		if (!@@instantiatedClasses[[mod, file]])
			$log.info("Creating... #{css_file}", :info, :template);
			CSSTrans.from_file(mod,file);
		elsif (!$site.config.live)
			$log.info("Checking cache... #{css_file}", :debug, :template);
			fname = get_file_path(mod, file)
			if (File.exists?(fname))
				if !Cache::check_cached_file(fname, Cache::get_cached(mod,file), Time.at(0))
					CSSTrans.from_file(mod,file);
				end
			end
		end
		$log.info("Instantiating... #{css_file}", :debug, :template);
		@@instantiatedClasses[[mod, file]].new();
	end
	
	public; def self.user_instance(css_file)
		css_file =~ /^(\w+)\/(.+).css$/
		mod,file = $1,$2
		if !@@instantiatedUserClasses[[mod, file]]
			CSSTrans.from_file(mod, file);
		end
		return @@instantiatedUserClasses[[mod, file]].new();
	end

	def self.parse_css(str)
		e_str = CSS::ErrorStream.new();
		css_scan = CSS::Scanner.new();
		css_scan.InitFromStr(str, e_str);
		css_parser = CSS::Parser.new(css_scan);	
		return css_parser.Parse();	
	end
	
	class CSSClass
		# class_name is the ruby class name of the class to be generated
		# source_name is the full path to the source template file
		# symbol used to look it up in the hash
		# source a string containing css to be parsed
		private; def initialize(class_name, source_name, symbol, source)
			@name = class_name;
			@source_name = source_name;
			@css_string = source;
			
			@vars = Hash.new;
			parse();
			CSSTrans.instantiatedClasses[symbol] = CSSTrans.const_get(@name);
			CSSTrans.instantiatedUserClasses[symbol] = CSSTrans.const_get(:"#{@name}_user_skin");
		end

		# Completely parse the CSS document and generate the class associated with it.
		private; def parse
			@doc = CSSTrans::parse_css(@css_string);
			@code = CodeGenerator.new(@name);
			@user_skin_code = CodeGenerator.new(:"#{@name}_user_skin")
			
			@doc.keys.each{|selector|
				user_skin_in_selector = false
				selector_string = ""
				selector.each_with_index{|sel,i|
					if (sel.kind_of? Array)
						selector_string += sel.join("")
					else
						selector_string += sel
					end
				}
				selector_string += "{\n"
				@code.append_print selector_string
				@doc[selector].each{|rule|
					prop, v = rule;
					user_skin_rule = false
					rule_name = "	#{prop}:"
					@code.append_print rule_name
					
					rule_value = ""
					v.each_with_index{|value,index|
						if (index > 0)
							rule_value += %Q|","|
						end
						value.each{|sub_value|
							sub_value = sub_value.join(" ")
							user_skin_rule = true if (sub_value.match(/\@/))
							sub_value = sub_value.gsub(/(\$|\@)[a-zA-Z1-9_]*/){|m|
								'#{@' + m[1..-1] + '}';
							}
							sub_value.gsub!('"', '\"');
							rule_value += %Q|" #{sub_value}"|;
						}
					}
					if (user_skin_rule)
						unless (user_skin_in_selector)
							@user_skin_code.append_print selector_string
							user_skin_in_selector = true
						end
						@user_skin_code.append_print(rule_name)
						@user_skin_code.append_output(rule_value)
						@user_skin_code.append_print ";\n";
					end
					@code.append_output rule_value
					@code.append_print ";\n";
				}
				if (user_skin_in_selector)
					@user_skin_code.append_print "}\n"
				end
				@code.append_print "}\n"
			}
			@code.generate(@source_name);
			@user_skin_code.generate(@source_name)
		end
		
		def to_str(css_set)
		end

	end
	
	
end
