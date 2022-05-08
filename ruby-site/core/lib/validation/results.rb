module Validation

	# Encapsulates the results of validating a rule. The results can have one of five states:
	# 	:none, :error, :warning, :valid, :unknown
	#
	# The :none state should be reserved for fields that have not yet been validated. This basically
	# allows validation html to be written to a template (to possibly be turned on later via Javascript)
	# without actually displaying any of it to the user.
	#
	# The :warning and :valid states contribute to an overall "valid" result. They will not break a 
	# Validation::Chain.
	#
	# The :error state will always break a Validation::Chain.
	#
	# The :unknown state can be used in a Validation::Chain "override" situation where, if the override
	# condition is not met, you don't want to display a "valid" indication to the user, nor do you want
	# to make the overall Validation::Chain "invalid" (as you would if you returned Validation::Results
	# with a state of :none).
	#
	# Validation::Results are initialized with a state and a message.
	#
	# state: 		One of the five states listed above
	# message: 	A message to display to the user regarding the validation that was done.
	class Results
	
		attr_reader :state, :message;
		attr_writer :state, :message;
	
		def initialize(init_state=:none, init_message="")
			@state = init_state;
			@message = init_message;
		end
	
	
		def to_xml()
			xml_string = 
				"<?xml version = \"1.0\" encoding=\"UTF-8\" standalone=\"yes\" ?>" + 
				"<validation-results>" + 
					"<state>#{htmlencode(state.to_s)}</state>" + 
					"<message>#{htmlencode(message.to_s)}</message>" + 
				"</validation-results>";
			
			return xml_string;
		end
	end
end