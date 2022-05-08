lib_require :core, 'validation/display'

module Validation
	
	# Holds a set of Validation::Results. This class manages the creation of proper Validation::Display objects
	# to represent the Validation::Results. As well, it will handle binding all of the Validation::Display objects
	# to a Template. Finally, an entire Form can be validated based on the Validation::Set.
	class Set
		def initialize()
			@validation_results = Hash.new;
			@validation_displays = Hash.new;
			@validation_chains = Hash.new;
		end
	
		
		# Add the given field and Validation::Results to representing it to the Validation::Set.
		#
		# field: 		The name/id of the field in the form. See Validation::Display for more information on 
		# 					naming conventions.
		# results: 	The Validation::Results to associate with the field.
		# show_icon_for_valid:
		# 					Can optionally "turn off" the default of showing a valid icon for Validation::Results in
		# 					a :valid state.
		def add(field,chain,show_icon_for_valid=true)
			@validation_results[field] = chain.validate;
			@validation_chains[field] = chain;
			
			display = Display.new(field,@validation_results[field],show_icon_for_valid);
		
			@validation_displays[field] = display;
		end
	
		def get_results(field)
			return @validation_results[field];
		end
	
		def get_display(field)
			return @validation_displays[field];
		end
	
	
		# Bind all Validation::Display objects in this Validation::Set to the given Template.
		def bind(template)
			@validation_displays.values.each { |display|
				display.bind(template);
			}
			
			javascript_string = ""
			@validation_chains.keys.each { | field_id |
				chain = @validation_chains[field_id];
				javascript_string = javascript_string + "#{chain.javascript(field_id)}\n";
			};
			
			template.client_validation = javascript_string;
		end

		# Tells us if we haven't really been given anything to validate.
		def no_data?
			@validation_results.values.each { |result|
				return false if result.state == :error
			}
			
			return true
		end
	
		# Returns true if all Validation::Results in the set have either a :valid or :warning state.
		# Returns false if any of the Validation::Results in the set have either a :none or :error state.
		def valid?
			@validation_results.values.each { |result|
				if (result.state != :valid && result.state != :warning)
					return false;
				end
			}
		
			return true;
		end
	end
end