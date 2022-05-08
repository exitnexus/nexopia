lib_require :Core, "secure_form"

# Provides a simple class you can instanciate to represent an image in template.
class Img
	attr :link, true;
	attr :caption, true;
	def img_info()
		return [caption, link];
	end
	def initialize(caption, link)
		@caption = caption;
		@link = link;
	end
end

module Template
	# LiteralView is the processor used for HTML tags and attributes, like <img> and <div>.
	class LiteralView < Processor
		namespace ""
		show_in_html true

		class << self
			
			def translate_default(node,code,&block)
				if (node.attribute("t:id"))
					DefaultView::_normal_handle_tid(node,code,&block)
				else
					DefaultView::handle_json(node, code)
					yield node;
				end
			end
			
			def _handle_external_link(node,code)
				if (node.attributes['href'])
					if (node.attributes['href'][0...1] != "/")
						yield node;
						return true
					end
				end
				return false
			end
	
			def _handle_existing_onclick(node,code)
				if (node.attributes['onclick'])
					yield node;
					return true
				end
				return false
			end
	
			def translate_a(node, code,&block)
				DefaultView::handle_json(node, code)
				if (node.attribute('t:id'))
					if (node.attribute('t:linktype'))
						type = %Q{"#{node.attribute('t:linktype')}"};
					else
						type = '';
					end
					class_target = "#{node.attribute('t:id')}.class";
					node.attributes["href"] = "\#{__link_info[1]}";
					if (node.children.empty?)
						node.add REXML::Text.new("\#{__link_info[0]}");
					end

					code.append <<-EOT
						if (!#{node.attribute('t:id')}.nil?)
							if #{node.attribute('t:id')}.respond_to?(:uri_info)
								__link_info = #{node.attribute('t:id')}.uri_info(#{type})
							else
								raise ArgumentError, "Object Doesn't support Linking (\#{#{class_target}} - must implement :uri_info)"
							end
						end
						
						if (!#{node.attribute('t:id')}.nil? && __link_info[1])
					EOT

					yield node
					
					code.append <<-EOT
						else
							__template_output << __link_info[0];
						end
					EOT
				else
					# This is needed for simple anchor tags. Because we are outputting in HTML 4.01, not
					#  XHTML the closing / can be misinterpretted by the browser's parser and will have
					#  every following text node wrapped by the anchor. By adding a child to the node the
					#  output will be <a ...></a> rather than <a ... />, we need this behaviour.
					if(node.attributes['name'])
						node.add REXML::Text.new("");
					end
					yield node;
				end
			end
	
			def translate_script(node, code)
				if (node.attributes['src'])
					node.attributes['src'] += "?rev=#{Time.now.to_i}";
					yield node;
				else
					code.append_print("<script><!--")
					code.append_print htmldecode(node.text);
					node.cdatas.each{|cdata|
						code.append_print htmldecode(cdata.to_s);
					}
					code.append_print("//--></script>")
				end
			end
	
			def translate_link(node, code)
				if (node.attributes['href'])
					node.attributes['href'] += "?rev=#{Time.now.to_i}";
				end
				yield node;
			end
	
			def translate_img(node, code)
				DefaultView::handle_json(node, code)
				if (node.attribute('t:id'))
					if (node.attribute('t:linktype'))
						type = %Q{"#{node.attribute('t:linktype')}"};
					else
						type = '';
					end
					code.append "__img_info = #{node.attribute('t:id')}.img_info(#{type})\n"
				end
				
				if (node.attribute('t:delay'))
					code.append "__delay = (#{node.attribute('t:delay').value})\n"
					if (node.attribute('t:loading'))
						code.append "__loading = (\"#{node.attribute('t:loading').value}\")\n"
					end
					if (node.attribute('t:id'))
						node.attributes['cond:src'] = "(if (!__delay) then __img_info[1] else __loading end)\n"
						node.attributes['cond:url'] = "(if (__delay) then __img_info[1] else nil end)\n"
						node.attributes["alt"] = "\#{urlencode(__img_info[0].to_s)}";
					else
						src = node.attributes["src"];
						node.attributes.delete("src");
						code.append "__src = \"#{src}\"\n"
						node.attributes['cond:src'] = "(if (!__delay) then __src else __loading end)\n"
						node.attributes['cond:url'] = "(if (__delay) then __src else nil end)\n"
					end
				elsif (node.attribute('t:id'))
					node.attributes["src"] = "\#{__img_info[1]}";
					node.attributes["alt"] = "\#{urlencode(__img_info[0].to_s)}";
				end
				
				yield node;
			end
			
			def translate_form(node, code)
				DefaultView::handle_json(node, code)
				elt = REXML::Element.new("input");
				elt.add_attribute("type", "hidden");
				elt.add_attribute("name", "form_key[]");
				elt.add_attribute("value", "{PageRequest.current.handler.form_key}");
				node.add(elt);
				yield node;
			end
	

		
		
		end
	end
	
end