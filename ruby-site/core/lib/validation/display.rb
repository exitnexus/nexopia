module Validation

	# Handles the HTML display of a Validation::Results object. This class will also leave hidden
	# HTML that can be accessed via JavaScript for client-side validation that mimics the server-
	# side validation.
	#
	# field_id: the id/name of the form element that is being validated. This needs to correspond
	# 					to the form element. It can also refer to a field that is part of an array (for
	# 					example, "field_id[0]" is a valid parameter in addition to the more common "field_id".
	# results: 	the Validation::Results that will determine the type of icon displayed as well as
	# 					the message that is displayed with it.
	# show_icon_for_valid:
	# 					if set to false, the icon for a :valid Validation::Results will not be displayed.
	# 					Defaults to true.
	#
	# HTML divs will be written to the page for each of the possible displayable states. The divs
	# can be accessed via the id values:
	# 
	# "#{field_id}_error_icon": 	for the error icon
	# "#{field_id}_warning_icon": for the warning icon
	# "#{field_id}_valid_icon": 	for the valid icon
	# "#{field_id}_vm": 					for the validation message
	#
	# Validation icon placeholders in the HTML are: 		"#{field_id}_vi"
	# Validation message placeholders in the HTML are: 	"#{field_id}_vm"
	#
	# These will be replaced with code to either hold the displayed validation or to provide a
	# framework for Javascript to display validation.
	# 
	# Javascript can "turn on" any of the icons by changing their "display" value from "none" to
	# "block". It can "turn off" any of the icons by doing the inverse operation.
	# 
	# CSS styles can be applied to the types of dispayable validation messages. The classes of
	# the message divs are as follows:
	#
	# validation_warning_text: 	text written for a :warning state message
	# validation_valid_text: 		text written for a :valid state message  
	# validation_error_text: 		text written for an :error state message
	# validation_text: 					text that does not fall into any of the above states.
	class Display
	
		def initialize(field_id, results, show_icon_for_valid=true)
			@results = results;
			
			# If "field_id" is a regular field, just grab the field name. If it's a field with an
			# array index (i.e. field[0]), parse out the field and index values.
			array_regexp = /(.*)\[(.*)\]/;
			@field_id, @index = field_id.scan(array_regexp).flatten;
			@field_id = @field_id || field_id;

			@index_text = @index ? "[#{@index}]" : "";

			icon_style = "margin-right: 4px; float: left;"
		
			error_style = "display: none;";
			valid_style = "display: none;";
			warning_style = "display: none;";

			if (@results.state == :warning)
				warning_style = "display: block;";
			elsif (@results.state == :error)
				error_style = "display: block;";
			elsif (@results.state == :valid && show_icon_for_valid)
				valid_style = "display: block;";
			end
		
			@icon = "<div id=\"#{@field_id}_error_icon#{@index_text}\" style=\"#{error_style} #{icon_style}\">" +
								"<img class=\"validate_error_image\" src=\"#{$site.static_files_url}/core/images/validate_error.gif\" />" +
							"</div>" +
							"<div id=\"#{@field_id}_warning_icon#{@index_text}\" style=\"#{warning_style} #{icon_style}\">" +
								"<img class=\"validate_warning_image\" src=\"#{$site.static_files_url}/core/images/validate_warning.gif\" />" +
							"</div>" + 
							"<div id=\"#{@field_id}_valid_icon#{@index_text}\" style=\"#{valid_style} #{icon_style}\">" +
								"<img class=\"validate_valid_image\" src=\"#{$site.static_files_url}/core/images/validate_valid.gif\" />" +
							"</div>";
		end
	
	
		# Binds the Validation::Display object to an actual template, replacing any placeholders (as defined
		# above) with the appropriate validation code.
		#
		# template: A Template instance. Any instances of #{field_id}_vi and #{field_id}_vm will be replaced
		# 					by corresponding validation code.
		# icon_ref: May be used to specify a different placeholder than the default for a validation icon.
		# 					As a warning, however, the validation framework currently assumes the default naming scheme
		# 					in other areas, so unless it is reworked, this convention should be followed to avoid future
		# 					problems.
		# message_ref: 
		# 	 				May be used to specify a different placeholder than the default for a validation message.
		# 					As a warning, however, the validation framework currently assumes the default naming scheme
		# 					in other areas, so unless it is reworked, this convention should be followed to avoid future
		# 					problems.
		#
		def bind(template, icon_ref="#{@field_id}_vi", message_ref="#{@field_id}_vm")
			@template = template;
		
			icon_set_method = "#{icon_ref}=";
			message_set_method = "#{message_ref}=";

			icon_get_method = "#{icon_ref}";
			message_get_method = "#{message_ref}";
			
			if (!@index.nil? && @index != "")
				if (@template.respond_to?(icon_set_method)) 
					icon_array = @template.send(icon_get_method) || Array.new;
					icon_array[@index.to_i] = display_icon;
					display_icon_text = icon_array;
				end
				if (@template.respond_to?(message_set_method)) 
					message_array = @template.send(message_get_method) || Array.new;
					message_array[@index.to_i] = display_message;
					display_message_text = message_array;
				end		
			else
				display_icon_text = display_icon;
				display_message_text = display_message;
			end
			
		
			if (@template.respond_to?(icon_set_method)) 
				@template.send(icon_set_method, display_icon_text);
			else
				$log.info("#{icon_set_method} does not exist! Not binding a validation icon.");
			end
		
			if (@template.respond_to?(message_set_method)) 
				@template.send(message_set_method, display_message_text);
			else
				$log.info("#{message_set_method} does not exist! Not binding a validation message.");
			end
		end
	
	
		def display_icon
			return @icon;
		end
	
	
		def display_message
			if (@results.state == :warning)
				div_class = "validation_warning_text";
			elsif (@results.state == :error)
				div_class = "validation_error_text";
			elsif (@results.state == :valid)
				div_class = "validation_valid_text";
			else
				div_class = "validation_text";
			end
		
			return "<div id='#{@field_id}_vm#{@index_text}' class='#{div_class}' style='padding: 2px'>#{@results.message}</div>";
		end
	end
end