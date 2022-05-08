lib_require :Core, 'users/locs';
lib_require :Core, 'users/interests';


# Class to provide location selection functionality in forms.
#
# By default, the location selector will have the name/id of "location". This can be changed
# via the location_ref attribute (for example, if there is more than one LocationSelector on
# a form).
# 
# TODO: Right now, this class just defaults to the PHP site location selection style.
# If there is time, provide a more intuitive location selection style for Javascript
# enabled users.
class HierarchicalSelector < PageHandler
	declare_handlers("Nexoskel/selector") {
		area :Skeleton
		
		page :GetRequest, :Full, :location, "location"
		page :GetRequest, :Full, :location, "location", input(Integer)
		
		page :GetRequest, :Full, :interest, "interest"
		page :GetRequest, :Full, :interest, "interest", input(Integer)
	}

	@@location_list = nil;
	@@interest_list = nil;

	def location(id = 0)
		if(@@location_list.nil?)
			@@location_list = Locs.get_children
		end

		t = create_selector_template(id, @@location_list, params, "location");
		puts t.display;
	end
	
	def interest(id = 0)
		if(@@interest_list.nil?)
			@@interest_list = Interests.get_children
		end

		t = create_selector_template(id, @@interest_list, params, "interest");
		puts t.display;
	end

	def create_selector_template(id, nodes, params, default_ref)
		t = Template.instance("nexoskel", "hierarchical_selector");

		t.selected_node = id;
		t.nodes = nodes;

		t.onchange_handler = params['onchange_handler', String, nil];
		t.ref = params['ref', String, default_ref];
		
		return t;
	end
	private :create_selector_template;
end
