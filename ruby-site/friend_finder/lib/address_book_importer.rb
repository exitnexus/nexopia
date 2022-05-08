lib_require :Core, "validation/rules";

module FriendFinder
	class AddressBookImporter
		Contact = Struct.new(:name, :email, :user_id, :invite_id, :alt_names, :user);
		
		class << self
			def import(email, password)
				val_rule = Validation::Rules::CheckEmailSyntax.new(Validation::ValueAccessor.new(nil, email));
				result = val_rule.validate();
				
				if(result.state != :valid)
					raise EmailNotSupportedError, "The email provided is not a valid email address";
				end
				
				email_parts = email.split('@');
				if(email_parts.length != 2)
					raise EmailNotSupportedError, "A processing error occurred";
				end
				
				email_domain = email_parts[1];
				
				contacts = [];
				if(email_domain.match(/^yahoo\.(com|ca){1}$/))
					contact_obj = Contacts::Yahoo.new(email, password);
				elsif(email_domain.match(/^(gmail|googlemail)\.com$/))
					contact_obj = Contacts::Gmail.new(email, password);
				else
					raise EmailNotSupportedError, "The email provided is not a supported import type";
				end
				
				if(!contact_obj.nil?())
					contacts = contact_obj.contacts;
				else
					$log.info "Friend Finder: Contacts object is nil in AddressBookImporter", :warning;
				end
				
				contact_list = [];
				contacts.each{|contact|
					temp = Contact.new();
					temp.email = contact[1];
					temp.name = contact[0];
					contact_list << temp;
				};
				
				return contact_list;
			end		
		end
	end
	
	class EmailNotSupportedError < StandardError
	end
end
