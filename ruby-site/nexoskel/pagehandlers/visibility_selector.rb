lib_require :Core, 'visibility'

class VisibilitySelector < PageHandler
	declare_handlers("Nexoskel/selector") {
		area :Skeleton
		
		page :GetRequest, :Full, :visibility, "visibility"		
		page :GetRequest, :Full, :visibility, "visibility", input(Integer)
	}
	

	def visibility(value=nil)
		options = option_array(Visibility.options);
		
		if (!params.to_hash['allow_empty'].nil?)
			empty_name = params.to_hash['allow_empty']
			options.insert(0, Option.new("", empty_name));
		end
		
		t = Template.instance("nexoskel", "selector");
		
		field = params["field", String, "visibility"];
		id = params["id", String, nil];
		if (id.nil? || id == "")
			t.ref = field;
		else
			t.ref = "#{field}[#{id}]";
		end
		
		t.options = options;
		t.value = value;

		puts t.display;
	end
	

	Option = Struct.new :value, :text;
	def option_array(list)
		options = Array.new;

		list.each { |pair| 
			options << Option.new(pair[1], pair[0]);
		};

		return options;
	end
	private :option_array;
end