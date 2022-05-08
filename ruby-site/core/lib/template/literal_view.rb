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
	class LiteralView < Processor
		namespace ""
		show_in_html true

		class << self
			
			def translate_default(node,code,&block)
				if (node.attribute("t:id"))
					DefaultView::_normal_handle_tid(node,code,&block)
				else
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
	
			def _translate_a_handle_tid(node,code)
				if (node.attribute('t:id'))
					if (node.attribute('t:linktype'))
						type = %Q{"#{node.attribute('t:linktype')}"};
					else
						type = '';
					end
					class_target = "#{node.attribute('t:id')}.class";
					code.append <<-EOT
						if #{node.attribute('t:id')}.respond_to?(:uri_info)
							__link_info = #{node.attribute('t:id')}.uri_info(#{type})
						else
							raise ArgumentError, "Object Doesn't support Linking (\#{#{class_target}} - must implement :uri_info)"
						end
					EOT
	
					node.attributes["href"] = "\#{__link_info[1]}";
					if (node.children.empty?)
						node.add REXML::Text.new("\#{__link_info[0]}");
					end
				end
			end
	
			def translate_a(node, code,&block)
=begin
				return if _handle_external_link(node,code,&block);
				return if _handle_existing_onclick(node,code,&block);
	
				_translate_a_handle_tid(node,code,&block)
	
				# Translate this into an ajax-targetted link.
				# The "ajax-target" attribute specifies the id of which
				# component the link should load into.
				node.name = "a"
				target_id = node.attribute('t:ajax-target') || "MainObj";
				node.attributes['onclick'] = "return AJAXLoadLink(this, '#{target_id}');";
				yield node;
=end
				_translate_a_handle_tid(node,code,&block)
				yield node;
			end
	
			def translate_script(node, code)
				if (node.attributes['src'])
					node.attributes['src'] += "?rev=#{Time.now.to_i}";
					yield node;
				else
					code.append_print("<script><!--")
					code.append_print CGI::unescapeHTML(node.text);
					node.cdatas.each{|cdata|
						code.append_print CGI::unescapeHTML(cdata.to_s);
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
				if (node.attribute('t:id'))
					if (node.attribute('t:linktype'))
						type = %Q{"#{node.attribute('t:linktype')}"};
					else
						type = '';
					end
					code.append "__img_info = #{node.attribute('t:id')}.img_info(#{type});"
					node.attributes["src"] = "\#{__img_info[1]}";
					node.attributes["alt"] = "\#{__img_info[0]}";
				end
				yield node;
			end
			
			def translate_form(node, code)
				elt = REXML::Element.new("input");
				elt.add_attribute("type", "hidden");
				elt.add_attribute("name", "form_key");
				elt.add_attribute("value", "{PageRequest.current.handler.form_key}");
				node.add(elt);
				yield node;
			end
	

		
		
		end
	end
	
end