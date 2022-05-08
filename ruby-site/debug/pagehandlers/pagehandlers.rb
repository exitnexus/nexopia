class PageHandlers < PageHandler
	declare_handlers("pagehandlers") {
		handle :GetRequest, :list, "list"
	}

	PageHandlerInfo = Struct.new('PageHandlerInfo', :area, :name, :type, :class, :method, :priv);

	def match_to_str(item)
		case item.class.name
		when "HandlerTreeNode::CaptureInput"
			return "capture(#{match_to_str item.key})";
		when "Regexp"
			return "regex#{item}";
		when "Class"
			return "{#{item.name}}";
		else
			return "#{item}";
		end
	end


	def search_node(nodelist, node, stack = [])
		if (node.this_node)
			nodearea = stack.first;
			nodename = (stack.empty? ? "{Default}" : stack.slice(1, 1000000).join(" / "));

			nodelist << PageHandlerInfo.new( 
				nodearea, 
				nodename, 
				node.this_node.type, 
				node.this_node.klass,
				node.this_node.methods,
				node.this_node.priv
				);
		end

		if (!node.child_nodes.empty?)
			node.child_nodes.each {|key, value|
				stack.push(match_to_str(key));
				search_node(nodelist, value, stack);
				stack.pop();
			}
		end
	end

	def list()
		nodelist = [];
		search_node(nodelist, pagehandler_tree);

		nodelist.sort!{ |a, b|
			if(a.area == b.area)
				(a.name <=> b.name);
			else
				(a.area <=> b.area);
			end
		}

		t = Template.instance("devutils", "pagehandlers");
		t.nodes = nodelist;
		print t.display();

	end
end
