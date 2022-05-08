lib_require :Core, "template/template_processor"
lib_require :Core, "template/rexml_util"

module Template
	# This namespace provides "yield"-like functionality in templates.
	class CallProcessor < Processor
		namespace :call
		show_in_html false
		
		class << self
			def translate_default(element, code, &block)
				self.send(:"call_#{element.name}", element, code, &block)
			end
			
			def call_default(element, code, &block)
				return element
			end
			
			def get_next_id
				@@next_id ||= 0
				@@next_id += 1
				return @@next_id
			end
			
			def method_missing(name, *args, &block)
				if (/^call_(.*)$/ =~ name.to_s)
					return call_default(*args,&block)
				end
				super(name, *args, &block)
			end
			
			def call_test(element, code, &block)
				t = Template.instance('devutils', 'test')
				t.var = "foo"
				return t.display
			end
			
			# <call:page id="page_result" url="/page">
			#   <td>lksg</td>
			# </call:page>
			
			def load_xml_file(mod_file, tem_file)
				name = "Template" + mod_file.upcase + "_" + tem_file.upcase;
				f = "#{$site.config.site_base_dir}/#{mod_file}/templates/#{tem_file}.html";
				#FileChanges.register_file(f);
				file = File.basename(f)
				dir = File.dirname(f)
		
				return File.open(f).read();
			end
			
			def call_yield(node, code, &block)
				outputs = [];
				inputs = [];
				node.attribute('call:passed').to_s.split(",").each{|decl|
					input, output = *decl.split("|");
					code.append %Q| #{output} = #{input}; \n|;
				}
				yield node;
			end
			
			def call_page(node, code, &block)
				code.append(%Q| @__url = #{node.attribute('url')}; \n|.gsub('&apos;', "'"));
				code.append(%Q| @__entries = #{node.attribute('id')}; \n|.gsub('&apos;', "'"));

				doc = REXML::TemplateDocument.new("<t:outer " +
					"xmlns:t=\"#{TemplateClass::TEMPLATE_NAMESPACE}\" " + 
					"xmlns:cond=\"#{TemplateClass::TEMPLATE_NAMESPACE}\" " +
					"xmlns:call=\"#{TemplateClass::TEMPLATE_NAMESPACE}\" " +
					">" + load_xml_file("core", "page") + "</t:outer>");

				prepare_yield(doc.root, node)
				
				TemplateClass.handle_node(doc.root, code, &block)
			end
			
			def call_dropdown(node, code, &block)
				doc = REXML::TemplateDocument.new("<t:outer " +
					"xmlns:t=\"#{TemplateClass::TEMPLATE_NAMESPACE}\" " + 
					"xmlns:cond=\"#{TemplateClass::TEMPLATE_NAMESPACE}\" " +
					"xmlns:call=\"#{TemplateClass::TEMPLATE_NAMESPACE}\" " +
					">" + load_xml_file("core", "dropdown") + "</t:outer>");


				code.append(%Q|@bodyid = Template::CallProcessor.get_next_id\n|);
				
				prepare_yield(doc.root, REXML::XPath.first(node, '/descendant::call:block[@id=\'header\']'), 'header')
				prepare_yield(doc.root, REXML::XPath.first(node, '/descendant::call:block[@id=\'body\']'), 'body')
				
				TemplateClass.handle_node(doc.root, code, &block)
			end

			def prepare_yield(new_element, node, id=nil)
				id_string = "[@id=\'#{id}\']" if id
				REXML::XPath.each(new_element, "descendant::call:yield#{id_string}"){|child|
					passed = [];
					child.attribute('call:params').to_s.split(",").each{|decl|
						input, output = *decl.split("|");
						passed << "#{input.strip}|#{node.attribute(output.strip)}"
					}
					child.attributes['call:passed'] = passed.join(",");
					
					#$log.object child.attributes;
					node.each{ |elt|
						child.add(elt.deep_clone);
					}
				}

			end

		end
	end
end
