lib_require :core, 'template/css/csstrans.ErrorStream'
lib_require :core, 'template/css/csstrans.Parser'
lib_require :core, 'template/css/csstrans.Scanner'
lib_require :core, 'template/code_generator'
lib_require :core, 'template/generated_cache'

module CSSTrans

	def self.get_file_path(mod, css_file)
		f = "#{$site.config.site_base_dir}/#{mod.to_s.downcase}/#{css_file}.css";
	end

	def self.get_name(mod, css_file)
		name = Cache.prefix + "_" + mod.to_s.upcase + "_" + css_file.gsub(/[^a-zA-Z0-9]/, "_").upcase;
	end

	class Cache < GeneratedCodeCache
		def self.instance(*args)
			return CSSTrans::from_file(*args)
		end

		def self.library
			Dir["core/lib/template/css/*.rb"];
		end
		def self.prefix
			"CSS";
		end
		
		def self.parse_dependency(dependency)
			return dependency.split(":");
		end
	
		def self.parse_source_file(_module, file_base)
			"#{_module}/#{file_base}.css"
		end
		
		def self.class_name(_module, file_base)
			"#{prefix}_#{_module.to_s.upcase}_#{file_base.gsub(/[^\w]/, '_').upcase}"
		end

		def self.output_file(_module, file_base)
			"generated/#{prefix}_#{_module.to_s.upcase}_#{file_base.gsub(/[^\w]/, '_').upcase}.gen.rb"
		end
		
		def self.source_dirs(mod)
			["#{mod.directory_name}"]
		end
		def self.source_regexp()
			/((?:layout|control)\/[^.\/]+).css$/
		end

		@@instantiatedClasses = {};
		def self.instantiatedClasses
			return @@instantiatedClasses;
		end

		@@instantiatedUserClasses = {};
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
		if (!Cache.instantiatedClasses[[mod, file]])
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
		return Cache.instantiatedClasses[[mod, file]].new();
	end
	
	public; def self.user_instance(css_file)
		css_file =~ /^(\w+)\/(.+).css$/
		mod,file = $1,$2
		if(!Cache.instantiatedUserClasses[[mod, file]])
			CSSTrans.from_file(mod, file);
		end
		return Cache.instantiatedUserClasses[[mod, file]].new();
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
			Cache.instantiatedClasses[symbol] = CSSTrans.const_get(@name);
			Cache.instantiatedUserClasses[symbol] = CSSTrans.const_get(:"#{@name}_user_skin");
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

					#TODO: Make this more generic so it can be applied to properties other than background image. For today, this is good enough.
					if(prop.to_s() == "ext-background")
						rule_name = " background-color:"
						@code.append_conditional_print("@page_background_image", "background-image:", "@page_background_image");
					end
					if(prop.to_s() == "opacity")
						ie_val = v.to_s.to_f*100
						@code.append_print("\tfilter: alpha(opacity = #{ie_val.to_i});\n")
					end
					if(prop.to_s() == "min-height")
						min_height = v.to_s[0, -2].to_i #the min-height without px at the end
						@code.append_print("\t_height: expression( this.scrollHeight < #{min_height} ? \"#{min_height}\" : \"auto\" );\n")
					end
					if(prop.to_s() == "max-height")
						max_height = v.to_s[0, -2].to_i #the max-height without px at the end
						@code.append_print("\t_height: expression( this.scrollHeight < #{max_height} ? \"#{max_height}\" : \"auto\" );\n")
					end
					if(prop.to_s() == "min-width")
						min_width = v.to_s[0, -2].to_i #the min-width without px at the end
						@code.append_print("\t_width: expression( this.scrollWidth < #{min_width} ? \"#{min_width}\" : \"auto\" );\n")
					end
					if(prop.to_s() == "max-width")
						max_width = v.to_s[0, -2].to_i #the max-width without px at the end
						@code.append_print("\t_width: expression( this.scrollWidth < #{max_width} ? \"#{max_width}\" : \"auto\" );\n")
					end
					
					#hack to make inline-block work cross browser
					if(prop.to_s == "display" && v.to_s == "inline-block")
						@code.append_print("\tdisplay: -moz-inline-block;\n")
						@code.append_print("\tzoom: 1;\n")
						@code.append_print("\tdisplay: inline-block;\n")
						@code.append_print("\t_display: inline;\n")
						next #jump out here because we know there is nothing else to do for this rule
					end
					
					@code.append_print rule_name
					
					rule_value = ""
					v.each_with_index{|value,index|
						if (index > 0)
							rule_value += ","
						end
						value.each{|sub_value|
							sub_value = sub_value.join(" ")
							user_skin_rule = true if (sub_value.match(/\@/))
							sub_value = sub_value.gsub(/(\$|\@)[a-zA-Z1-9_]*/){|m|
								'#{@' + m[1..-1] + '}';
							}
							sub_value.gsub!('"', '\"');
							rule_value += " #{sub_value}";
						}
					}
					if (user_skin_rule)
						unless (user_skin_in_selector)
							@user_skin_code.append_print(selector_string)
							user_skin_in_selector = true
						end
						@user_skin_code.append_print(rule_name)
						@user_skin_code.append_print(rule_value)
						@user_skin_code.append_print(";\n")
					end
					@code.append_print(rule_value)
					@code.append_print(";\n")
				}
				if (user_skin_in_selector)
					@user_skin_code.append_print("}\n")
				end
				@code.append_print("}\n")
			}
			@code.generate(@source_name);
			@user_skin_code.generate(@source_name)
		end
		
		def to_str(css_set)
		end

	end
	
	
end
