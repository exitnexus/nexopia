class ProfileInfoSelectors < PageHandler
	
	declare_handlers("Nexoskel/selector") {
		area :Skeleton
		
		page :GetRequest, :Full, :height, "height"
		page :GetRequest, :Full, :weight, "weight"
		page :GetRequest, :Full, :orientation, "orientation"
		page :GetRequest, :Full, :living, "living"
		page :GetRequest, :Full, :dating, "dating"
		
		page :GetRequest, :Full, :height, "height", input(String)
		page :GetRequest, :Full, :weight, "weight", input(String)
		page :GetRequest, :Full, :orientation, "orientation", input(String)
		page :GetRequest, :Full, :living, "living", input(String)
		page :GetRequest, :Full, :dating, "dating", input(String)
	}
	
	
	def weight(value=nil)
		options = SelectorOptions.option_array_from_assoc_array(Profile::Profile::WEIGHT);
		
		t = Template.instance("nexoskel", "selector");
		t.options = options;
		t.ref = "weight";
		t.value = value;
		
		puts t.display;
	end
	
	
	def height(value=nil)
		options = SelectorOptions.option_array_from_assoc_array(Profile::Profile::HEIGHT);
		
		t = Template.instance("nexoskel", "selector");
		t.options = options;
		t.ref = "height";
		t.value = value;
		
		puts t.display;
	end
	
	
	def orientation(value=nil)
		options = SelectorOptions.option_array_from_assoc_array(Profile::Profile::SEXUAL_ORIENTATION);
		
		t = Template.instance("nexoskel", "selector");
		t.options = options;
		t.ref = "orientation";
		t.value = value;
		
		puts t.display;
	end


	def living(value=nil)
		options = SelectorOptions.option_array_from_assoc_array(Profile::Profile::LIVING_SITUATION);
		
		t = Template.instance("nexoskel", "selector");
		t.options = options;
		t.ref = "living";
		t.value = value;
		
		puts t.display;
	end


	def dating(value=nil)
		options = SelectorOptions.option_array_from_assoc_array(Profile::Profile::DATING_SITUATION);
		
		t = Template.instance("nexoskel", "selector");
		t.options = options;
		t.ref = "dating";
		t.value = value;

		puts t.display;
	end
end