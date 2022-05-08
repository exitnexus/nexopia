module FriendFinder
	class HotmailImporter
		def import_contacts(xml_string)
			contact_list = [];
			doc = REXML::Document.new(xml_string);
			if(doc.nil?() || doc.root.nil?())
				$log.info "XML string did not contain root element", :warning;
				$log.info xml_string, :warning;
				raise Exception, "Malformed XML";	
			end
			
			doc.root.each{|element| 
				if(element.name.downcase == "contact")
					process_contact(element, contact_list);
				end
			}
			
			return contact_list;
		end
		
		def process_contact(contact_element, contact_list)
			temp_contact_list = [];
			contact_first_name = "";
			contact_last_name = "";
			contact_nick_name = "";
			contact_name = "";
			
			# Extract the needed information from the contact node. XPath isn't used here because
			#  it actually complicates matters. It's a pretty simple structure and we only care about
			#  a small amount of data.
			contact_element.each_element{|child_element|
				if(child_element.name.downcase == "profiles")
					child_element.each{|profile_child|
						if(profile_child.name.downcase == "personal")
							profile_child.each{|name_child|
								if(name_child.name.downcase == "firstname")
									contact_first_name = name_child.text;
								elsif(name_child.name.downcase == "lastname")
									contact_last_name = name_child.text;
								elsif(name_child.name.downcase == "nickname")
									contact_nick_name = name_child.text;
								end
							};
						end
					};
				elsif(child_element.name.downcase == "emails")
					child_element.each{|email_node|
						if(email_node.name.downcase == "email")
							email_node.each{|detail_node|
								if(detail_node.name.downcase == "address")
									temp = AddressBookImporter::Contact.new();
									temp.email = detail_node.text;
									temp_contact_list << temp;
								end
							};
						end
					};
				end
			};
			
			# Figure out the proper name to use for the contact.
			if(contact_first_name != "")
				contact_name = contact_first_name;
			end
			
			if(contact_name != "" && contact_last_name != "")
				contact_name = contact_name + " " + contact_last_name;
			elsif(contact_name == "" && contact_last_name != "")
				contact_name = contact_last_name;
			end
			
			if(contact_name == "" && contact_nick_name != "")
				contact_name = contact_nick_name;
			end
			
			# Assign the name to the contact and stick the contact is
			#  the proper array.
			temp_contact_list.each{|contact|
				contact.name = contact_name;
				contact_list << contact;
			};
		end
	end
end