lib_require :Core, "validateinput";

# HandlerTreeNode is used to build a tree of pagehandler information. Nodes are
# added to it via add_node, and fetched via find_node. Both functions are recursive
# and add_node constructs the tree as it inserts.
class HandlerTreeNode
	attr_reader :static_nodes;
	attr_reader :dynamic_nodes;
	attr_reader :this_node;

	def initialize()
		@static_nodes = {};
		@dynamic_nodes = {};
		@this_node = nil;
	end

	# Returns all child nodes of this node as a single hash.
	def child_nodes()
		return @static_nodes.merge(@dynamic_nodes);
	end

	# path is an array of path components under which to add the item. Does a
	# recursive lookup to find the correct place to put the node and creates
	# items as it goes.
	def add_node(path, handler_info)
		if (path.length == 0)
			if (handler_info.respond_to?(:tree_node=))
				handler_info.tree_node = self
			end
			@this_node = handler_info;
			return;
		end
		# Find the node, insert if necessary, and then pass it on or set the handler to it.
		node = @static_nodes.fetch(path.first) {
			@dynamic_nodes.fetch(path.first) {
				if (path.first.kind_of? String)
					@static_nodes[path.first] = HandlerTreeNode.new();
				else
					@dynamic_nodes[path.first] = HandlerTreeNode.new();
				end
			}
		}
		node.add_node(path[1, path.length - 1], handler_info);
	end

	# recursively searches the tree from this node for an item that matches
	# the given path as completely as possible. It will use the deepest
	# match it can find. If you pass a block to it, matches will be
	# passed to it and can be rejected be the caller.
	# Returns [remain, value, inputs] where remain is any path components
	# not used, value is the node's value, and inputs is any items pulled out
	# via an input argument wrapped in a CaptureInput object.
	# The inputs argument is for internal, recursive, use.
	def find_node(path, inputs = [])
		# break out early if we're on a complete match.
		if (path.length == 0)
			return path, @this_node, inputs;
		end
		cur_path = path.first;
		remain = path[1, path.length - 1];

		match_remain = path;
		match_val = @this_node;
		match_inputs = inputs;

		# because our nodes may have regexes or whatever else, we may have to loop
		# through it rather than use the builtin lookup functions. There is an
		# optimization here, however, when a simple hash lookup is done
		# first it can speed up the tree lookup substantially.
		validate_proc = proc {|key, child|
			returnit = false;
			found = false
			if (key.kind_of? CaptureInput)
				key = key.key;
				returnit = true;
			end
			validated = key.validate_input(cur_path)
			if (validated)
				newmatch_inputs = inputs;
				if (returnit)
					newmatch_inputs = newmatch_inputs + [validated];
				end
				newmatch_remain, newmatch_val, newmatch_inputs = child.find_node(remain, newmatch_inputs);
				if (newmatch_remain.length < match_remain.length && !newmatch_val.nil?)
					if (!block_given? || yield(newmatch_val))
						match_remain, match_val, match_inputs = newmatch_remain, newmatch_val, newmatch_inputs;
						found = true
					end
				end
			end
			found
		}

		found = false
		if (match_child = @static_nodes[cur_path] || match_child = @static_nodes[CaptureInput.new(cur_path)])
			found = validate_proc.call(cur_path, match_child);
		end

		if (!found) # if the static path doesn't lead to any found items, move on to dynamic.
			@dynamic_nodes.each_pair {|key, val| validate_proc.call(key, val); }
		end

		return match_remain, match_val, match_inputs;
	end
	
	# this is a simpler function than find_node. It doesn't do longest-possible
	# submatch or any of that kind of thing. It just looks for the exact node
	# specified and returns it.
	def find_exact(path)
		if (path.length == 0)
			return self
		end
		cur_path = path.first;
		remain = path[1, path.length - 1];
		
		if (@static_nodes[cur_path])
			return @static_nodes[cur_path].find_exact(remain)
		end
		
		@dynamic_nodes.each_pair {|key, val|
			if (key.validate_input(cur_path))
				return val
			end
		}
		return nil
	end

	# This is a simple class used pretty much only to tell whether or not
	# an input to a pagehandler should be captured and passed to the handler itself
	class CaptureInput
		attr :key;

		def initialize(key)
			@key = key;
		end
	end
end
