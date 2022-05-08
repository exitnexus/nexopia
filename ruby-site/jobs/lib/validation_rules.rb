lib_require :Core, "validation/rule", "validation/results";
lib_require :Jobs, "applicant";

module Jobs	
	class Rules
		class CheckEmailAvailable < Validation::Rule
			def initialize(email)
				super([email]);
				
				@email = email.value;
			end
		
			def validate()
				applicant = Applicant.find(:first, :conditions => ["email = ?", self.email]);
				if (applicant.nil?)
					return Results.new(:valid, "");
				else
					return Results.new(:error, "E-mail already in use");
				end
			end
		end
		
		class CheckPhoneNumberSyntax < Validation::Rule
			def initialize(phone)
				super([phone]);
				
				@phone = phone.value;
			end
			
			def validate()
				if(/(((d{3}) ?)|(d{3}[- .]))?d{3}[- .]d{4}(s(((Ext|x)(.)?)(s)?d+)?){0,1}$/.match(phone))
					return Results.new(:valid, "");
				else
					return Results.new(:error, "The phone number provided is not formatted correctly");
				end
			end
		end
	end
end
