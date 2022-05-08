lib_require :Core, "validation/rule", "validation/results";

module Promotions
	class Rules
	
		class CheckEmail < Validation::Rule
			def initialize(email)
				super([email]);
				
				@email = email.value;
			end
		
		
			def validate
				email_check_regex = /^[a-z0-9]+([a-z0-9_.+&-]+)*@([a-z0-9.-]+)+\.([a-z0-9.-]+)+$/;
				
				if ( @email.downcase =~ email_check_regex )
					return Validation::Results.new(:valid, "");
				else
					return Validation::Results.new(:error, "Not a valid mail address");
				end
			end
		end
		
		
		class CheckNumeric < Validation::Rule
 
			def initialize(number)
				super([number]);
				
				@number = number.value;
			end
			
		
			def validate()
				pattern = /^\d+$/;
				
				if (pattern.match(@number) != nil)
					return Validation::Results.new(:valid, "");
				else
					return Validation::Results.new(:error, "This is not a number.");
				end
				
			end

		end # CheckNumeric < Validation::Rule
		
		
		class CheckPostal < Validation::Rule
 
			def initialize(part1, part2)
				super([part1, part2]);
				
				@part1 = part1.value;
				@part2 = part2.value;
			end
		
		
			def validate()
				pattern1 = /[a-zA-Z][0-9][a-zA-Z]/;
				pattern2 = /[0-9][a-zA-Z][0-9]/;
				
				if (pattern1.match(@part1) != nil && pattern2.match(@part2) != nil)
					return Validation::Results.new(:valid, "");
				else
					return Validation::Results.new(:error, "This is not a postal code.");
				end
				
			end
		end
		
		
		class CheckWaiverSelection < Validation::Rule
			def initialize(waiver_selection)
				super([waiver_selection]);
				@waiver_selection = waiver_selection.value;
			end
		
		
			def validate
				if (@waiver_selection.nil?)
					return Validation::Results.new(:none,"");
				end

				if (@waiver_selection == "")
					return Validation::Results.new(:error, "You must agree to the Waiver and Release.");
				else
					return Validation::Results.new(:valid, "");
				end			
			end
		end
				
	end
end