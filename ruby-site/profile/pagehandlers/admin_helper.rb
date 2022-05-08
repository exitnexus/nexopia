lib_want :Core, "abuse_log";
lib_want :Nexoskel, "selector_options";

module Profile
	class AdminHelper < PageHandler
		declare_handlers("profile/selector") {
			area :Admin

			handle :GetRequest, :action_selector, "mod_action"
			handle :GetRequest, :action_selector, "mod_action", input(Integer)
			handle :GetRequest, :reason_selector, "mod_reason"
			handle :GetRequest, :reason_selector, "mod_reason", input(Integer)
		}
	
	
		def reason_selector(value=nil)
			options = SelectorOptions.option_array_from_assoc_array(AbuseLog::REASONS);
		
			t = Template.instance("nexoskel", "selector");
			t.options = options;
			t.ref = params["reason_ref", String, "reason"];
			t.value = value;
		
			puts t.display;		
		end
	end
end