lib_require :Core, "template/template_processor"
lib_require :Core, "template/template"
lib_require :Core, "template/rexml_util"

module Template
	
	class TemplateDefine
		attr :blocks, true;
		attr :attrs, true;
		attr :element, true;
		def initialize(p_blocks, p_attrs, p_element)
			@blocks = p_blocks;
			@attrs = p_attrs;
			@element = p_element;
		end
	end
	
	class DefaultView < Processor
		namespace :t
		show_in_html false
	
		class << self
	
			@@defines = Hash.new;
			def defines
				return @@defines;
			end
	
			def _normal_handle_tid(node,code)
				obj = node.attribute("t:id").value;
				code.add_var_to_table(obj);
				if node.attribute("t:index")
					index = node.attribute("t:index").value;
				else
					index = "nil_index"
				end
	
				if node.attribute("t:iter")
					iter = node.attribute("t:iter").value;
				else
					iter = "nil_iter"
				end
	
				code.append "[*(#{obj})].each_with_index { |#{iter}, #{index}|\n";
				code.append "	if (#{index} > 0) \n"
				code.append_print node.attribute('join').to_s;
				code.append "	end;\n";
				if node.attribute('alternating')
					arr = node.attribute('alternating').to_s.split(",");
					arr.each_with_index{ |klass,row_num|
						code.append "		if (#{index}%#{arr.length}==#{row_num}) then "
						code.append "			class_var = '#{klass}';"
						code.append "		end;\n";
					}
					node.attributes['class'] = '#{class_var}';
				end
				#
				#
				yield node
				code.append "} if (#{obj})\n";
			end
	
			def _normal_translation(node,code,&block)
				if (node.attribute("t:id"))
					_normal_handle_tid(node,code,&block)
				else
					yield node;
				end
	
			end
	
=begin		def _attribute_translation(node,code)
				handled = false;
				@@attributes.each{ | key,fn_handle |
					attribute, type = key;
					#If the node has this attribute, insert its associated code
					arg = node.attribute(attribute);
					if (arg)
						if (code.get_var_type(Template::get_literal_var(arg.value)) == type)
							send(fn_handle, code, Template::get_literal_var(arg.value), node ) {
								yield node
							};
						else
							var_r = code.get_var_type(arg.value);
							$log.info("#{type} doesn't match #{arg.value} - #{var_r}.", :debug, :template);
						end
						handled = true;
					end
				}
				handled
			end
=end
			
			def translate_radio_group(node, code, &block)
				if (@radio_group_stack == nil)
					@radio_group_stack = Array.new();
				end
	
				@radio_group_stack.push(node.attributes["selected"]);
				yield node;
				@radio_group_stack.pop;
	
			end
	
			def translate_radio(node, code, &block)
				newnode = REXML::Element.new("input");
				if (@radio_group_stack != nil and node.attributes["id"] == @radio_group_stack[-1])
					newnode.attributes["checked"] = "checked";
				end
				node.attributes.each_attribute{|attr|
					newnode.attributes[attr.name] = attr.value;
				}
	
				newnode.attributes["type"] = "radio";
				node.add_element newnode;
	
				label = REXML::Element.new("label");
				label.attributes["for"] = node.attributes["id"];
				label.text = node.text;
				node.add_element label;
	
				node.text = nil
	
	
				yield node;
			end
			
			# Translate any tag not handled by a specific method.
			def translate_default(node, code, &yblock)
				if (@@defines)
					if @@defines[node.name]
						new_elt = @@defines[node.name].element.deep_clone;
						node.attributes.each{ |attr,val|
							new_elt.attributes[attr] = val;
						}
						if @@defines[node.name].blocks.length == 1
							# From the define, find the place where that block is used
							new_elt.search_children(@@defines[node.name].blocks.first){|parent, child|
								node.each{ | elt|
									parent.add(elt.deep_clone);
								}
								parent.delete(child);
							}
						else
							@@defines[node.name].blocks.each{|block|
								
								passed_thru_subtree = nil;
	
								# From the calling node, find the passed in blocks
								REXML::XPath.each(node, "descendant::#{block}"){|child|
									passed_thru_subtree = child;
								}
								
								# From the define, find the place where that block is used
								new_elt.search_children(block){|parent, child|
									passed_thru_subtree.each{ | elt|
										parent.add(elt.deep_clone);
									}
									parent.delete(child);
								}
							}
						end
						
						if @@defines[node.name].attrs
							[*@@defines[node.name].attrs.split(",")].each{ |param|
								code.append %Q| #{param} = '#{node.attributes[param]}';|;
							}
						end
						#yield new_elt;
						TemplateClass.handle_node(new_elt, code, &yblock)
						return;
					end
				end
	
				# If the node wasn't handled in a special way by the attrib handlers or the
				# extension handlers, then just handle it normally.
				_normal_translation(node,code,&yblock)
			end
			
			# Here you can define a macro of sorts.
			def translate_define(node, code)
	
				name = node.attributes['t:name'];
				blocks = [];
				if (node.attributes['t:inner'])
					blocks << node.attributes['t:inner'];
				end
				if (node.attributes['t:blocks'])
					blocks = blocks.concat(node.attributes['t:blocks'].split(","));
				end
				attrs = node.attributes['t:attrs'];
	
				@@defines[name] = TemplateDefine.new(blocks, attrs, [*node.elements].first());
				code.exports[name] = Marshal.dump(@@defines[name]);
			end
	
			def translate_ajax(node, code)
				node.name = "a";
				node.attributes['href'] = "javascript:void(0);";
				node.attributes['onclick'] = "AJAXLoadLink(this);";
				yield node
			end
	
	
			# Include from an existing template
			def translate_template_include(element, code)
				mod = element.attribute("module");
				templ = element.attribute("name");
				code.dependencies << "#{mod}:#{templ}";
				#code.append %Q|\ninclude_start = Time.now.to_f; \n|;
				code.append %Q|templ = Template::instance("#{mod}", "#{templ}");\n|;
				Template.get_class(mod.to_s, templ.to_s)::get_vars.each{|var,type|
					code.add_var(var, type.to_s);
					code.append %Q|if (local_variables.include?("#{var}"))\n|;
					code.append %Q|	templ.#{var} = #{var};\n|;
					code.append %Q|else\n|;
					code.append %Q|	templ.#{var} = @#{var};\n|;
					code.append %Q|end\n|;
				}
				code.append_output %Q|templ.display();\n|;
				#code.append %Q|child_time += (Time.now.to_f - include_start); \n|;
			end
	
			def _handler_include_path(element,code,path,area = nil,user = nil,params=[])
				code.add_var_to_table(user.to_s) if user
				
				area = ":#{area.to_s.capitalize}" if not area.nil?  and area.to_s[0] != ':'
				
				area = 'nil' unless area
				user = 'nil' unless user
				
				# If there is no selector, default to :Body
				if (!path.to_s[':'])
					path = path.to_s + ":Body";
				end
				
				#
				# Generate code to create and execute a subrequest, placing the output
				# of the subrequest into the "out" variable if it exists, or
				# appending it to the HTML otherwise.
				out = element.attribute("out");
				#code.append %Q|\nsubrequest_start = Time.now.to_f; \n|;
				code.append %Q|__req = PageHandler.current.subrequest(StringIO.new(), :GetRequest, "#{path}", {#{params.join(',')}}, #{area}, #{user} )\n|;
				code.append %Q|if (__req.reply.ok?)\n|;
				if (!out)
					code.append_output %Q|__req.reply.out.string;\n|;
				else
					code.append %Q|#{out} = __req.reply.out.string;\n|;
					yield element
				end
				code.append %Q|end; \n|;
				#code.append %Q|PageHandler.current['timing'] += (Time.now.to_f - subrequest_start); \n|;
			end
	
			# Include the html returned by a pagehandler for a given path.
			def translate_handler_include(element, code,&block)
				vars = [];
				REXML::XPath.each(element, 'descendant::t:var'){|tag|
					vars << "#{CGI::unescape(tag.attribute('t:name').value)} => #{CGI::unescape(tag.attribute('t:val').value)}";
				}
				path = element.attribute("path");
				area = element.attribute("area");
				user = element.attribute("user");
				_handler_include_path(element,code,path,area,user,vars,&block) if path
			end
	
			def translate_url(__url)
				if (__url.index("?"))
					return "#{__url}&"
				else 
					return "#{__url}?";
				end
			end
			
			def DefaultView.page_header(__entries, __url)
				params = "";
				__url = translate_url(__url);
				
				output = "";			
				output << %Q|
					<div class="page_header">
					Displaying #{(__entries.page-1)*__entries.page_length + 1}-#{(__entries.page-1)*__entries.page_length+__entries.length} of #{__entries.first.total_rows}.
					Page: 
				|;
			
				if (__entries.total_pages <= 8)
				 	(1..__entries.total_pages).each{|_page|
						if (_page == __entries.page)
							output << %Q| &#160;#{_page} |;
						else
							output << %Q| &#160;<a href="#{__url}page=#{_page}">#{_page}</a> |;
						end
					}
				else
					output << %Q[ &#160;<a href="#{__url}page=1">|&lt;</a> ];
					begin_page = [__entries.page - 3, 1].max;
					end_page = [begin_page+6, __entries.total_pages].min;
					begin_page = end_page-6;
					
					((begin_page)..(end_page)).each{|_page|
						if (_page == __entries.page)
							output << %Q| &#160;#{_page} |;
						else
							output << %Q| &#160;<a href="#{__url}page=#{_page}">#{_page}</a> |;
						end
					}
					output << %Q[ &#160;<a href="#{__url}page=#{__entries.total_pages}">&gt;|</a> ];
				end
				
				output << %Q|</div>|;
				
				return output;
			end
			
			def translate_page(element, code)
				code.append %Q|
			__entries = #{element.attributes['id']}; \n
			__url = #{element.attributes['url']}; \n
			if (__entries.length > 0) \n
				|;
				code.append_output %Q| Template::DefaultView.page_header(__entries, __url) |;
				code.append %Q[	__entries.each{|#{element.attributes['iter']}|\n ];
	
				yield element
	
				code.append %Q|}\n|;
				code.append %Q| 
			end \n
				|;
			end
	
			# Possibly the weirdest function on the planet...
			# Escape arbitrary HTML for insertion into a javascript string using single
			# quotes, inside of an html attribute using double quotes.
			# ie.: <a onclick="string = ' %return val of this function% '">
			def DefaultView.escape(string)
				return (string.gsub("\\", "\\\\\\\\").gsub("\n", "\\n").gsub("'"){"\\" + "'"}.gsub("\r", "")).gsub("<", "&lt;").gsub("&", "&amp;");
			end
	
			def translate_form(node, code)
=begin
				if (node.attributes['t:ignore'])
					yield node;
					return;
				end
				target_id = node.attribute('t:target') || "MainObj";
				node.attributes['onsubmit'] = "return AJAXSubmit(this, '#{func}');";
				node.attributes['method'] = 'post';
				yield node;
=end
				yield node;
			end
			
			def translate_varsub(node, code)
				__old = node.attribute('t:old')
				__new = node.attribute('t:new')
				code.append("#{__new} = #{__old}\n");
			end
			
			def translate_statement(node, code)
				code.append(node.text + "\n");
			end
			
			def translate_json(node, code)
				code.append_print("<script>")
				code.append_print("Nexopia.JSONData.#{node.attribute('t:handle')} = ")
				code.append_output node.attribute('t:data').to_s + ".to_json";
				code.append_print(";");
				code.append_print("</script>")				
			end
			
			def pp_daytime(time)
				str = "";
				str << (time.hour > 12 ? time.hour - 12 : time.hour).to_s
				str << ":"
				str << time.min.to_s
				str << time.strftime("%p").downcase
			end
				
			def pp_time(time)
				if (time.to_f > Time.now.to_f - 7*86000)
					since = Time.now.to_i - time.to_i
					message = ""
					case since
					when (0...60)
						message = "moments"
					when (60...3600)
						message = "#{(since/60).to_i} minute"
						message += 's' if ((since/60).to_i > 1)
					when (3600...86400)
						message = "#{(since/3600).to_i} hour"
						message += 's' if ((since/3600).to_i > 1)
					else
						message = "#{(since/86400).to_i} day"
						message += 's' if ((since/86400).to_i > 1)
					end
					message = "#{message} ago"
					return message
				else
					return time.strftime("%m/%d/%Y");
				end
			end
			
			def translate_nice_time(node, code)
				__time = node.attribute('time')
				code.append_output("Template::DefaultView::pp_time(#{__time})");
			end

			def translate_nice_daytime(node, code)
				__time = node.attribute('time')
				code.append_output("Template::DefaultView::pp_daytime(#{__time})");
			end

			def translate_display(node, code, &block)
				obj = node.attribute("t:id")
				code.append_output "#{obj}.display_message; "
			end
	
			def method_missing(name, *args, &block)
				name = name.to_s;
				if (match = /^translate_(.*)$/.match(name))
					return translate_default(*args,&block) #{|*yargs| yield(*yargs); };
				elsif (match = /^attribute_translate_(.*)$/.match(name))
					return attribute_translate_default(*args,&block) #{|*yargs| yield(*yargs); };
				else
					raise "WTF? #{name}"
					#super(name, *args);
				end
			end
	
			def attribute_translate_default(ns,attr,code)
				val = TemplateClass.parseVar(attr.value, code);
			end
	
		end
	end
end
