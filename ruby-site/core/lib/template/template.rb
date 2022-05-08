require "rexml/document"
lib_require :Core, "template/generated_cache"
lib_require :Core, "template/code_generator"
lib_require :Core, "template/default_view"
lib_require :Core, "template/literal_view"
lib_require :Core, "filechangemonitor"
lib_require :Core, "template/template_document"
lib_require :Core, "template/form"
lib_require :Core, "template/template_processor"
lib_require :Core, "template/call_processor"

# =Template Class User's Guide
#
# A template file is a .html file with special keywords and attributes.
#
# =How To Write Templates
#
# A template is an HTML document with control flow and other programming features. The control
# flow is done with custom tags.  When the template is run, the output is a normal HTML
# file. The custom tags let you do loops, ifs, insert data, etc.
#
# Templates must be valid XML.  This means that all tags must be closed in the correct order, 
# and single tags such as "br" must be closed as in "<br/>".  All attributes must be surrounded 
# in quotations. Any javascript with "<" and ">" operators must be encapsulated in CDATA. Also, 
# there can only be one root tag in a template document.
#
# Any custom tags are namespaced, as in <namespace:tag>. There are also custom attributes,
# like <div namespace:attribute="something">. 
#
# The template code can be extended to use any namespaces, but the one provided by default is
# 't'. There are many different tags defined in the t namespace. Note that there are many
# meaningful tags, but you can also use totally meaningless ones if you want.  The meaningless
# ones will be stripped out, but you can use them for organization.   
#
# Following is an example hello world template that follows these conventions. This is useful as
# a basis for writing new templates:
#
# <t:template>
#  Hello World!
# <t:template>
#
#
# We've introduced several special conventions into templates.
#
# Variables
#   You can insert variables into the template output with the {} characters.  The value of the
#   variable will be converted into a string and transformed into an htmlentity, so all special
#   characters in HTML will be converted.  "<" will become &lt;, for instance.
#
#   {varname} will HTML-escape your data.
#
#   #{varname} will substitute directly, without escaping any characters.
#
#   ${varname} will add slashes to special characters found in the string value of the variable.
#
#   %{varname} will urlencode the string value of the variable.
#
# Here's an example:
#
# <t:template>
#   Hello, #{PageRequest.current.username}.
# </t:template>
#
# Common template tags:
#
# * <template-include module="modname" name="templatename"/> includes another template inside this one.
# * <handler-include path="/site"/> includes another page inside this template. 
#
# Ifs, Loops, and Other Constructs
#
# * <tag t:id="varname"> can be used with any HTML tag.  The tag and output will be transformed
#   according to the default transformation for the type of tag and the class of varname. The
#   transformations attempt to be as obvious as possible.
#
#   * If the variable is of the class Array, the tag will be treated as a "for" loop on the
#     elements of the array.  The "iter" attribute can be set to the handle of the iterator
#     variable you'd like to use inside.  The "index" attribute can be set to the handle of
#	  a variable you'd like to use.
#     ie. <t:loop t:id="array" t:iter="iterator" t:index="index" class="body,body2">
#            <td>{index}: {iterator}
#		  </t:toop>
#	will print out something like:
#  1: element1
#  2: element2
#  3: element3...
#
#   * If the tag is an IMG tag, and the class of the nid variable defines a "img_info"
#     method, then the "src" attribute of the image will be set to whatever the img_info
#     method defines.
# Javascript
#
# =How To Instantiate Templates
#
# The mechanism for using a template is the static method
# Template::instance(modulename, templatename, pagehandler).  Note that templatename is the
# filename without the ".html".
#
# For example:
#
#   t = Template::instance("core", "index", Pagehandler.current);
#
# The template system will automatically take care of the creation and caching of the associated
# template class.
#
# Variables in the template are denoted by the {varname} notation. Inside the ruby code,
# template variables are accessible as instance variables.  So {username} in the template
# can be set with
#   t.username = "Timo";
# in the ruby code.
#
#


module Template

	def self.generate_id
		int_wrapper = Struct.new(:int)
		id = $site.cache.get(:generated_template_id, :page) { int_wrapper.new(0) }
		id.int += 1
		return id.int
	end

	# Processor for xmlns: tags/attributes.
	class XMLNSView < Processor
		namespace :xmlns
		show_in_html false;

		class << self
			def attribute_translate_default(ns,attr,code)
				#ignore xmlns attributes.
			end
		end
	
	end
	
	# Processor for cond: attributes.  The attribute will only appear if its value is
	# non-nil/false.
	class ConditionalView < Processor

		namespace :cond
		show_in_html false;

		class << self
			def method_missing(name, *args, &block)
				name = name.to_s;
				if (match = /^attribute_translate_(.*)$/.match(name))
					return attribute_translate_default(*args,&block) #{|*yargs| yield(*yargs); };
				else
					raise "WTF? #{name}"
					#super(name, *args);
				end
			end

			def attribute_translate_default(ns,attr,code)
				obj = attr.value;

				code.append "if (#{obj})\n";
				code.append_print " #{attr.name}= \\\"\#{#{obj}}\\\""
				code.append "end\n";
			end
		end
	end

	class Cache < GeneratedCodeCache
		def self.instance(*args)
			return Template::from_file(*args)
		end
		
		def self.library
			Dir["core/lib/template/*.rb"];
		end
		def self.prefix
			"Template";
		end
		
		def self.parse_dependency(dependency)
			return dependency.split(":");
		end
	
		def self.parse_source_file(_module, file_base)
			html_file = "#{_module}/templates/#{file_base}.html";
		end
		
		def self.class_name(_module, file_base)
			"#{prefix}_#{_module.to_s.upcase}_#{file_base.upcase}"
		end

		def self.output_file(_module, file_base)
			"#{$site.config.generated_base_dir}/#{prefix}_#{_module.to_s.upcase}_#{file_base.upcase}.gen.rb"
		end
		
		def self.source_dirs(mod)
			["#{mod.directory_name}/templates"]
		end
		def self.source_regexp()
			/\/([^.\/]+)\.html$/
		end

		@@instantiatedClasses = {};
		def self.instantiatedClasses
			return @@instantiatedClasses;
		end
		
	end

	# Create a new Template class from the template file 'tem_file' in the module 'mod_file'.
	# The result of this call is to parse and generate the class, and add it to the local
	# cache of templates.
	private; def Template.from_file(mod_file, tem_file)
		f = get_file_path(mod_file, tem_file)
		name = get_name(mod_file, tem_file)
		#FileChanges.register_file(f);
		file = File.basename(f)
		dir = File.dirname(f)

		xml_string = File.open(f).read();
		return TemplateClass.new(name, f, [mod_file,tem_file], xml_string);
	end

	public; def Template.inline(module_sym, name, code)
		module_sym = module_sym.to_s
		return TemplateClass.new("TemplateInline#{name}", "Inline Template #{name}", [module_sym,name], code);
	end

	def Template.get_name(mod_file, tem_file)
		name = Cache.prefix + "_" + mod_file.to_s.upcase + "_" + tem_file.gsub(/[^a-zA-Z_0-9]/, "_").upcase;
	end
	
	def Template.get_file_path(mod_file, tem_file)
		mod = site_module_get(mod_file)
		if (!mod)
			raise "Unknown module #{mod_file}"
		end
		f = "#{mod.template_path}/#{tem_file}.html"
	end
	
	# Get an instance of the template class defined by the given template file.
	# If the class does not exist, create it first.
	public; def Template.instance(mod_file, template_name)
		mod_file = mod_file.to_s;
		if !Cache.instantiatedClasses[[mod_file,template_name]]
			$log.info("Creating... #{template_name}", :info, :template);
			Template.from_file(mod_file, template_name);
		elsif (!$site.config.live) #all templates are pre-loaded live, so don't try to create it
			#get_file_path(mod_file, tem_file)
			$log.info("Checking cache... #{mod_file}:#{template_name}", :debug, :template);
			
			fname = get_file_path(mod_file, template_name)
			if (File.exists?(fname))
				if !Cache::check_cached_file(fname, Cache::get_cached(mod_file, template_name), Time.at(0))
					Template.from_file(mod_file, template_name);
				end
			end
		end

		$log.info("Instantiating... #{template_name}", :debug, :template);
		Cache.instantiatedClasses[[mod_file,template_name]].new();
	end

	# Get the class defined by the given template
	public; def Template.get_class(mod_file, tem_file)
		if !Cache.instantiatedClasses[[mod_file,tem_file]]
			$log.info("Creating... #{tem_file}", :debug, :template);
			Template.from_file(mod_file, tem_file);
		end

		return Cache.instantiatedClasses[[mod_file,tem_file]];
	end


	class TemplateClass
		# Abandon hope all ye who enter here!
		#           _.--"""--._
		#        .'             '.
		#       /                 \
		#      ;                   ;
		#      |                   |
		#      ;                   ;
		#       \ (`'--,   ,--'`) /
		#       ))(  ')/ _ \('  )((
		#       (_ `""` / \ `""` _)
		#       |`"-,  /   \  ,-"`|
		#       \  /   `"`"`   \  /
		#         | _. ; ; ; ._ |
		#   _|"";  '-'_'_'_'_'-'    ;""|_
		#   \__ '-,               ,-' __/
		#      '-, '-,         ,-' ,-'
		#         '-, '-,   ,-' ,-'
		#            '-, '-' ,-'
		#             ,-'-, '-,
		#          ,-' ,-' '-, '-,
		#     __,-' ,-'       '-, '-,__
		#   /_  ,-'              '-,  _\
		#    |,,;                  ;,,|
		# The class responsible for generating the templates.  Each template will generate
		# its own class, called Template_<MODULENAME>_<FILENAME>.  Instances of the generated
		# classes are availabled by calling Template::instance(module, file).
		#
		#
		#
		# The characters that are allowed in a template variable
		#
		VARIABLE_CHARS = '_a-zA-Z0-9';
		VARIABLE_REGEXP = /([_a-zA-Z][#{VARIABLE_CHARS}]+)/;
		#
		#
		# The xml namespace our special stuff is in.
		#
		TEMPLATE_NAMESPACE = 'http://www.nexopia.com/dev/template';
		#
		# Prefixes that can be part of the variable declaration that
		# imply some sort of pre-processing on the variable before it
		# is included
		#
		HTML_ESCAPE_FLAG = ''
		URL_ENCODE_FLAG = '%'
		NO_ESCAPE_FLAG = '#'
		SLASHIFY_FLAG = '$'
		FORMAT_FLAG = '@'
		#
		# A list of elements that are considered 'empty' (that is, don't need end tags)
		# in HTML. These will be translated to the <blah /> format, while others will
		# translate to <blah></blah> if they have no inner text. Note, this includes
		# some elements that are optionally empty (ie. p) since they will still use
		# the full format if they have a body.
		#
		EMPTY_ELEMENTS = ['br', 'img', 'hr', 'link', 'meta', 'col', 'base', 'param', # EMPTY
						  'area', 'frame', 'input', 'basefont', 'isindex', # EMPTY
						  'p', 'li', 'a'] # OPTIONALLY EMPTY
		#
		# Elements that need to have special escaping rules (namely, need to be in a
		# comment in HTML and a <![CDATA[ in XHTML). Currently script and style are
		# done this way if they have text.
		SCRIPT_ELEMENTS = ['script', 'style'];
		#
		# The general form of the variable Regexp
		#
		OPERATORS = '\+\-\/\*\,\>\<\%\='
		PARSE_VAR_REGEXP = /([#{FORMAT_FLAG}#{URL_ENCODE_FLAG}#{NO_ESCAPE_FLAG}#{SLASHIFY_FLAG}]?)\{([\ \!\(\)\[\]\?\.\:\'\"\$#{OPERATORS}#{VARIABLE_CHARS}]+)\}/
		#
		#
		#

		private; def initialize(class_name, source_name, symbol, source)
			@name = class_name;
			@f = source_name;
			@xml_string = source;

			@vars = Hash.new;
			parse();
			Template::Cache.instantiatedClasses[symbol] = Template.const_get(@name);
		end

		attr :doc, true;

		# Completely parse the XML document and generate the Template class associated with it.
		private; def parse
			@doc = REXML::TemplateDocument.new("<t:outer " +
				"xmlns:t=\"#{TemplateClass::TEMPLATE_NAMESPACE}\" " + 
				"xmlns:cond=\"#{TemplateClass::TEMPLATE_NAMESPACE}\" " +
				"xmlns:call=\"#{TemplateClass::TEMPLATE_NAMESPACE}\" " +
				">#{@xml_string}</t:outer>");

			@code = CodeGenerator.new(@name);

			@doc.root.add_namespace("t", TemplateClass::TEMPLATE_NAMESPACE);
			@doc.root.add_namespace("cond", TemplateClass::TEMPLATE_NAMESPACE);
			@doc.root.add_namespace("call", TemplateClass::TEMPLATE_NAMESPACE);

			# Add docid handling to document.
			if (@doc.root.attribute('t:docid'))
				docid = @doc.root.attribute('t:docid');
				@code.append_print("<script> GlobalRegistry.register_docid('#{docid}') </script>");
			end

			recurse(@doc.root, @code);
			@code.generate(@f);
		end

		def self.add_slashes(string)
			search_replace = {"\x00" => '\0', "\x0a" => '\n', "\x0d" => '\r', "\x1a" => '\Z'};
			search_replace.each{ |search, replace|
				string.gsub(search, replace);
			}
		end

		#
		# For a particular string, parse out all of the {var} blocks and replace
		# them with their values.
		public; def self.parseVar(string, code)
			output = string.to_s.strip;	#
			output.gsub!(PARSE_VAR_REGEXP) {|match|
				pre_process_flag = $1
				variable = $2
				_pre_process_variable(code,variable,pre_process_flag)
			}
			return output;
		end

		private; def self._pre_process_variable(code,variable,flag = nil)
			case flag.to_s
			when HTML_ESCAPE_FLAG
				'#{htmlencode((' + variable + ").to_s)}";
			when URL_ENCODE_FLAG
				'#{urlencode((' + variable + ").to_s)}";
			when FORMAT_FLAG
				'#{' + variable + ".parsed}";
			when NO_ESCAPE_FLAG,SLASHIFY_FLAG
				'#{' + variable + '}';
			else
				raise(" Unexpected Variable Replacement Flag #{flag}!");
			end
		end

		# For each child that is an XML element, yield. However there is
		# weirdness; for comments or text, we actually handle them here.
		# This could be changed later.
		public; def self.each_child(parent, code)
			parent.each{ | element |
				if (element.kind_of? REXML::Text)
					val = element.to_s;
					code.append_print parseVar(val, code);
				elsif element.kind_of?(REXML::Comment)
					if element.to_s =~ /^\[if[0-9A-Za-z ]+\]>.*<!\[endif\]$/ then
						code.append_print "<!--" + parseVar(element.to_s, code) + "-->";
					else
						code.append_print "<!--" + element.to_s + "-->" unless $site.config.live
					end
				else
					yield element;
				end
			}
		end

		# Get the first variable inside a {} block in the string, and returns it.
		public; def self.get_literal_var(string)
			output = string.gsub(/\{([\[\]\.\'#{VARIABLE_REGEXP}]+)\}/){ |match|
				return $1;
			}
			return nil;
		end

		# Check a node for all of the features we'd like to process, then forward to
		# the correct handler.  If there is no specialized handler, simply print the
		# node and recurse on its children.
		public; def self.handle_node(element, code, &block)

			if element.kind_of?(REXML::Comment)
				if (!$site.config.live) 
					yield element;
				end
				return;
			end;
			element.attributes.each_attribute{ |attr|
				attrname = (attr.prefix=="" ? "" : attr.prefix+":") + attr.name;
				element.attributes[attrname] = TemplateClass.parseVar(attr.value, code).to_s;
				element.attributes[attrname.downcase] = element.attributes[attrname];
			}

			ns = "#{element.prefix}";
			Processor.for_namespace(ns).send(:"translate_#{element.name.gsub('-', '_').downcase}", element, code) { |node|
				@depth = (@depth || 0) + 1;
				#$log.info(' ' * @depth + "Entering node of type #{element.name}.", :debug, :template);
				#code.push_position doc.positions[element];
				yield node;
				#code.pop_position
				#$log.info(' ' * @depth + "Leaving node of type #{element.name}.", :debug, :template);
				@depth = @depth - 1;
			};

		end

		def output_attributes(node, code)
			node.attributes.each_attribute{ |attr|

				if (attr.prefix != "")
					ns = "#{attr.prefix}";
					Processor.for_namespace(ns).send(:"attribute_translate_#{attr.name.gsub('-', '_').downcase}", ns,attr,code)
				else
					code.append_print(" #{attr.name}=\"#{attr.value}\"");
				end
			}
		end

		def view_for_namespace(ns)
			# This is hackish, and could easily be fixed.  Processors could register
			# themselves in a hash.
			if (ns == "t")
				return DefaultView;
			end
			if (ns == "cond")
				return ConditionalView;
			end
			return LiteralView;
		end

		# Output the html for a node in the default way. 
		def output_html(node, code)
			show = (Processor.for_namespace(node.prefix).show_in_html?);

			prefix = (node.prefix.length > 0 ? "#{node.prefix}:" : "")

			if (node.has_elements? || node.has_text? || !TemplateClass::EMPTY_ELEMENTS.include?(node.name))
				if(show)
					code.append_print("<#{prefix}#{node.name}")
					output_attributes(node,code)
					code.append_print(">");

					if (node.has_text? && TemplateClass::SCRIPT_ELEMENTS.include?(node.name))
						code.append("if (PageHandler.current.reply.headers['Content-Type']['xml'])\n");
						code.append_print("<![CDATA[");
						code.append("end\n");
					end
				end

				TemplateClass.each_child(node, code){ |child|
					yield child;
				};

				if(show)
					if (node.has_text? && TemplateClass::SCRIPT_ELEMENTS.include?(node.name))
						code.append("if (PageHandler.current.reply.headers['Content-Type']['xml'])\n");
						code.append_print("]]>");
						code.append("end\n");
					end

					code.append_print("</#{prefix}#{node.name}>");
				end
			elsif (show)
				code.append_print "<#{prefix}#{node.name}";
				output_attributes(node,code)
				code.append_print(" />");
			end
		end

		# Parse an XML subtree recursively. Evaluates attributes, prints the
		# opening tag, recurses on each child, then prints the closing tag.
		private; def recurse(original_node, code)
			return TemplateClass.handle_node(original_node, code){ |node|
				if (node == nil)
					node = original_node;
				end
				output_html(node, code){|child|
					recurse(child, code);
				}

			};
		end


	end
end
