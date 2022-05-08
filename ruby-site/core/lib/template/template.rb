require "rexml/document"
require 'cgi'
lib_require :Core, "template/generated_cache"
lib_require :Core, "template/template_class_generator"
lib_require :Core, "template/default_view"
lib_require :Core, "template/literal_view"
lib_require :Core, "filechangemonitor"
lib_require :Core, "template/template_document"
lib_require :Core, "template/form"
lib_require :Core, "template/template_processor"
lib_require :Core, "template/call_processor"

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
# its own class, called Template<MODULENAME>_<FILENAME>.  Instances of the generated
# classes are availabled by calling Template::instance(module, file).
#
# =Template Class User's Guide
#
# A template file is a .html file with special keywords and attributes.
#
# =How To Write Templates
#
# Most of the template file is basic HTML. Templates must be valid XML.  This means that
# all tags must be closed in the correct order, and single tags such as "br" must be closed
# as in "</br>".  All attributes must be surrounded in quotations. Any javascript with "<" and
# ">" operators must be encapsulated in CDATA.
#
# In order to seperate our own tags and attributes from those of html, we use our own namespace.
# This namespace is identified by the url in TEMPLATE_NAMESPACE, and usually by the prefix t: on
# those tags. It is EXTREMELY IMPORTANT that all template documents have a root tag, and that that
# tag identify the two main namespaces in use, a default namespace of http://www.w3.org/1999/xhtml
# and our own template namespace of TEMPLATE_NAMESPACE.
#
# Following is an example hello world template that follows these conventions. This is useful as
# a basis for writing new templates:
#
# <t:my-template xmlns="http://www.w3.org/1999/xhtml" xmlns:t="http://www.nexopia.com/dev/template">
#  Hello World!
# </t:my-template>
#
# Note that any tags or attributes in the template namespace will be stripped from the output
# xml, so when this template is included into another it will not include the outer
# t:my-template tag. If your template is completely surrounded in a real xhtml tag,
# do not put it in the t: namespace.
#
# Although xml namespaces should be linked to the URL more than the prefix, current limitations
# require that the namespace in question always be on the prefix 't'.
#
# We've introduced several special conventions into templates.
#
# Variables
# * {varname} notation
#   {varname} will be replaced with the value of a variable set at runtime.  The value of the
#   variable will be converted into a string and transformed into an htmlentity, so all special
#   characters in HTML will be converted.  "<" will become &lt;, for instance.
#
#   To escape HTML conversion, you can use #{varname} for a direct substitution, for instance
#   if you are inserting actual HTML code into the text.
#
#   ${varname} will add slashes to special characters found in the string value of the variable.
#
#   %{varname} will urlencode the string value of the variable.
#
#
# Includes
#
# * <template-include module="modname" name="templatename"/> is a special tag, that will be
#   transformed in the template output into the text of the included template.  This makes
#   the most sense when treated as a simple text include.
#
# * <handler-include path="/site"/> will include the contents of another path, dynamically at
#   the time of instantiation.  This makes the most sense if the path you are including a
#   page with many of its own variables, like the profile page.
#
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
# Tag Transforms
#
# The template system transforms certain tags BY DEFAULT.  For instance, <a> tags are
# automatically re-written to perform ajax requests.  Here is a guide to the re-written
# tags.
#
# <a>                Automatically converted to ajax requests that are displayed in the main ajax frame.
#                    You should be able to use them just like normal <a href="..."> tags, unless you
#                    are trying to do something wacky.
#                    Attributes:
#                      href        : Automatically re-written as "#" + value + ":Body"
#                      ajax-target : The ID of the HTML element into which the text of the AJAX request
#                                    will be placed.  If this element isn't specified, then the default
#                                    ID is "MainObj", which is the main ajax frame in the middle of the
#                                    page.
#
# <dropshadow>       A text style that creates a drop shadow on text.
#                    Attributes:
#                      none
#
# <scroll-list>      A list that scrolls and loads new elements from a target url.  An example
#                    is the "users" list on the index page.  The scroll list element
#                    itself only defines where the scroll button will go. You must create
#                    an html element for each element of the list.  For instance,
#                    <span id="thing0"></span> would be an HTML element for the 0th element.
#                    Attributes:
#                      t:target    : The url
#                      t:num       : The number of list elements to display
#                      t:element   : The ID of the element to use as a list.
#
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
#
# =AJAX Library
#
# This is a seperate section designed to describe the implementation of AJAX using templates
# on nexopia.  It may be moved to a more appropriate place, when I think of one.
#
# Our template system is meant to work within an AJAX environment, where the user loads
# framework at the page load, and then loads site data using asynchronous requests. The
# template library has a lot of built in functionality for that purpose.
#
# The AJAX framework works like this:
# 1) The user comes to the site (or closes all existing browsers) and opens the a page in
#    her browser.
# 2) The Pagehandler system responds with the Skin template.  The user's specific request is
#    passed as a variable into the Skin template instance.
# 3) The skin template is loaded in her browser.  The Skin page then makes an asynchronous
#    request to the server.
# 4) The server serves back any pages the user requests after this point as AJAX frames in
#    the Skin page.
#
# Consequences Of This:
# - After the skin page is loaded, the browser runs its onload event.  After this, any further pages
#   will not receive an onload event.
# - However, our ajax framework automatically parses any javascript out of asynchronously loaded
#   HTML and runs it.  This happens after the whole request is received, so it is basically
#   equivalent to onload, except for the following.
# - Javascript source includes can't be relied on to be there by the time the script is executed.
#   So you have to use the include_dom(uri, func_object) function.  Func_object will be executed
#   when the script is loaded.
#
# There are some caveats to using Javascript in templates.  First of all, the window.onload
# method is taken over by AJAX initialization, so if you try to set it in a subtemplate,
# you'll break AJAX.
#

module Template

	class XMLNSView < Processor
		namespace :xmlns
		show_in_html false;

		class << self
			def attribute_translate_default(ns,attr,code)
				#ignore xmlns attributes.
			end
		end
	
	end
	
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

				# This should be extracted as common functionality for all
				# namespaced attributes, when I have some time.
				[*obj.match(TemplateClass::VARIABLE_REGEXP)].each{|match|
					variable = $1
					code.add_var_to_table(variable);
				}

				code.append "if (#{obj})\n";
				code.append_print " #{attr.name}= \\\"\#{#{obj}}\\\""
				code.append "end\n";
			end
		end
	end

	class Cache < GeneratedCodeCache
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
			"Template#{_module.to_s.upcase}_#{file_base.upcase}"
		end

		def self.output_file(_module, file_base)
			"generated/Template#{_module.to_s.upcase}_#{file_base.upcase}.gen.rb"
		end
		
		def self.source_dirs(mod)
			["#{mod.directory_name}/templates"]
		end
		def self.source_regexp()
			/\/([^.\/]+).html/
		end

		@@instantiatedClasses = {};
		
		@@times = {}
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
		name = "Template" + mod_file.to_s.upcase + "_" + tem_file.upcase;
	end
	
	def Template.get_file_path(mod_file, tem_file)
		f = "#{$site.config.site_base_dir}/#{mod_file}/templates/#{tem_file}.html";
	end
	
	# Get an instance of the template class defined by the given template file.
	# If the class does not exist, create it first.
	public; def Template.instance(mod_file, template_name)
		mod_file = mod_file.to_s;
		if !Cache.instantiatedClasses[[mod_file,template_name]]
			$log.info("Creating... #{template_name}", :info, :template);
			Template.from_file(mod_file, template_name);
		elsif (!$site.config.live)
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
		OPERATORS = '\+\-\/\*\,\>\<\%'
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
			@doc = REXML::TemplateDocument.new(@xml_string);

			@code = TemplateClassGenerator.new(@name);

			@doc.root.add_namespace("t", TemplateClass::TEMPLATE_NAMESPACE);

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
			code.add_var_to_table(variable);

			case flag.to_s
			when HTML_ESCAPE_FLAG
	        	'#{CGI::escapeHTML((' + variable + ").to_s)}";
			when URL_ENCODE_FLAG
	        	'#{CGI::escape((' + variable + ").to_s)}";
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
			if (element.attribute('t:id'))
				var = element.attributes.get_attribute('t:id').value;
				[*var.match(VARIABLE_REGEXP)].each{|match|
					variable = $1
					code.add_var_to_table(variable);
				}
			end
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
			if (ns == "t")
				return DefaultView;
			end
			if (ns == "cond")
				return ConditionalView;
			end
			return LiteralView;
		end

		def output_html(node, code)
			show = (Processor.for_namespace(node.prefix).show_in_html?);
			if (node.has_elements? || node.has_text? || !TemplateClass::EMPTY_ELEMENTS.include?(node.name))
				if (node.prefix.length > 0)
					show && code.append_print("<#{node.prefix}:#{node.name}")
					show && output_attributes(node,code)
					show && code.append_print(">");
				else
					show && code.append_print("<#{node.name}")
					show && output_attributes(node,code)
					show && code.append_print(">");
				end
				show && code.push_indent;
				if (node.has_text? && TemplateClass::SCRIPT_ELEMENTS.include?(node.name) && show)
					code.append(%Q{if (PageHandler.current.reply.headers['Content-Type']['xml'])\n});
					code.append_print("<![CDATA[");
					code.append(%Q{end\n});
				end

				TemplateClass.each_child(node, code){ |child|
					yield child;
				};

				if (node.has_text? && TemplateClass::SCRIPT_ELEMENTS.include?(node.name) && show)
					code.append(%Q{if (PageHandler.current.reply.headers['Content-Type']['xml'])\n});
					code.append_print("]]>");
					code.append(%Q{end\n});
				end
				show && code.pop_indent;
					if (node.prefix.length > 0)
						show && code.append_print("</#{node.prefix}:#{node.name}>");
					else
						show && code.append_print("</#{node.name}>");
					end
			elsif (show)
				if (node.prefix.length > 0)
					code.append_print "<#{node.prefix}:#{node.name}";
					output_attributes(node,code)
					code.append_print(" />");
				else
					code.append_print "<#{node.name}";
					output_attributes(node,code)
					code.append_print(" />");
				end
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
