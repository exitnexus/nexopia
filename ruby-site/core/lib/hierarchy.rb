
# The Hierarchy Module is a mixin to hierarchical tables like locations and interests. It simplifies
# getting the parents and children, and speeds up access times dramatically by pre-computing and
# caching the tree. To use as a mixin, include it and call the init_hierarchy function.
module Hierarchy

#added as instance variables
	attr :parent_node, true;
	attr :children, true;
	attr :depth, true;

#get the list of parents
	def get_parents
		return self.get_parents(id);
	end

#get only the parent ids
	def get_parent_ids
		return self.get_parent_ids(id);
	end

#get the full subtree of this child, flattened
	def get_children
		return self.get_children(id);
	end

#get the full list of ids that are part of this subtree.
	def get_children_ids
		return self.get_children_ids(id);
	end

	def to_s
		return name
	end

	#add all the class functions
	def self.included(other)
		class << other
			public

			# init function that builds the tree. The label is the name of the root node that isn't stored in the db.
			def init_hierarchy(label)
				top_node = self.new;
				top_node.id = 0;
				top_node.parent = 0;
				top_node.depth = 0;
				top_node.name = label;
				top_node.children = [];

				@nodes = { 0 => top_node };

				records = self.find(:scan, :order => 'parent, name');
				build_node_tree(top_node, records, @nodes);
			end

			def build_node_tree(parent_node, records, nodes, depth=0)
				children = records.select { |x| x.parent == parent_node.id };
				children.each { |child|
					child.parent_node = parent_node;
					child.depth = depth+1;
					child.children = [];

					parent_node.children << child;
					nodes[child.id] = child;

					build_node_tree(child, records, nodes, depth+1);
				};
			end
			protected :build_node_tree;


			#get a node by id
			def get_by_id(node_id)
				return @nodes[node_id];
			end

			def get_parents(node_id)
				ret = [];
		
				while(node_id > 0)
					ret << @nodes[node_id];
					node_id = @nodes[node_id].parent;
				end

				return ret;
			end

			def get_parent_ids(node_id)
				ret = [];

				get_parents(node_id).each {|node|
					ret << node.id;
				}

				return ret;
			end
			
			def get_parent_properties(node_id, property_name)
				ret = [];

				get_parents(node_id).each {|node|
					ret << node.send(property_name);
				}

				return ret;
			end

			def get_children(node_id = 0, level = -1)
				ret = [];
				flatten(get_by_id(node_id), ret, level);
				return ret;
			end

			def get_children_ids(node_id = 0)
				ret = [];
				get_children(node_id).each {|node|
					ret << node.id;
				}
				return ret;
			end

			def flatten(node, list, level = -1)
				return if (level == 0)
				level -= 1;
				
				list << node;

				node.children.each { | child |
					flatten(child, list, level);
				};
			end
			
			protected :flatten;
		end
	end
end
