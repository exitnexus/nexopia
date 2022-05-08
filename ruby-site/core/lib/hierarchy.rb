
# The Hierarchy Module is a mixin to hierarchical tables like locations and interests. It simplifies
# getting the parents and children, and speeds up access times dramatically by pre-computing and
# caching the tree. To use as a mixin, include it and call the init_hierarchy function.
module Hierarchy

#added as instance variables
	attr :parent_node, true;
	attr :children, true;

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

	def children?
		return self.children?(id)
	end

	def to_s
		return name
	end

	def depth
		if(!@depth)
			@depth = 0
			current = self
			while !current.parent_node.nil?
				@depth = @depth + 1
				current = current.parent_node
			end
		end
		
		return @depth
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
				top_node.name = label;
				top_node.children = [];
				top_node.parent_node = nil
				
				@nodes = { 0 => top_node }
				
				records = self.find(:scan, :order => 'parent, name');
				build_node_tree(records, @nodes);
			end


			def build_node_tree(records, nodes)
				orphans = {}
				records.each { |record|
					nodes[record.id] = record
					nodes[record.id].children = orphans[record.id] || []
					
					nodes[record.id].children.each { |child| child.parent_node = record }
					
					if(nodes[record.parent])
						record.parent_node = nodes[record.parent]
						nodes[record.parent].children << record
					else
						orphans[record.parent] = [] if orphans[record.parent].nil?
						orphans[record.parent] << record
					end
				}
			end
			protected :build_node_tree;
			

			#get a node by id
			def get_by_id(node_id)
				return @nodes[node_id];
			end

			def get_id_by_name(name)
				@nodes.each{|id, node| 
					return id if node.name == name
				}
				return nil
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
						
			def children?(node_id)
				node = get_by_id(node_id)
				return !node.children.nil? && !node.children.empty?
			end
		end
	end
end