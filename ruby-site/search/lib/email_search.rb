lib_require :Core, "users/useremails";
lib_require :FriendFinder, "email_invite", "invite_optout";

module Search
	class EmailSearch
		
		#Used by the contact list searches
		#contact_list is the list of emails
		#search_user is the user performing the search
		#only active determines if only "active" users are eligible for the search results.
		def EmailSearch.search_contacts(contact_list, search_user, only_active = true)
			email_list = contact_list.map{|contact| contact.email}.compact();
			
			user_email_list = UserEmail.find(:all, :email, *email_list);
			
			user_id_list = user_email_list.map{|user_email|
				if(only_active)
					if(user_email.active)
						user_email.userid;
					else
						next;
					end
				else
					user_email.userid;
				end
			}.compact();
			
			user_list = User.find(:all, *user_id_list);
			
			filtered_contact_list = Array.new();
			
			for user_email in user_email_list
				user = EmailSearch.search_user_list(user_email.userid, user_list);#user_list[user_email.userid];
				
				if(user.nil?())
					next;
				end
				
				contact = EmailSearch.find_contacts(user_email.email, contact_list);
				if(contact.nil?())
					next;
				end
				
				contact.user_id = user_email.userid;
				
				#determining if a user is eligible to be friended. The criteria are
				#that the user is visible to the search user, the user is searchable
				#by email and that the search user has not already friended the user.
				friend_eligible = true;
				friend_eligible = friend_eligible && user.visible?(search_user);
				friend_eligible = friend_eligible && user.searchemail;
				friend_eligible = friend_eligible && !search_user.friend?(user);
				friend_eligible = friend_eligible && !user.ignored?(search_user);
				
				if(friend_eligible)
					filtered_contact_list << contact;
				end
			end
			
			for contact in filtered_contact_list
				email_list.delete(contact.email);
			end
			
			invite_id = 0;
			for email_address in email_list
				user_invite_list = FriendFinder::EmailInvite.find(:all, search_user.userid);
				
				contact = EmailSearch.find_contacts(email_address, contact_list);
				if(contact.nil?())
					next;
				end
				
				invite_eligible = true;
				invite_eligible = invite_eligible && !EmailSearch.has_email_invite?(email_address, user_invite_list);
				invite_eligible = invite_eligible && EmailSearch.has_optout?(email_address);
				
				if(invite_eligible)
					contact.invite_id = invite_id;
					filtered_contact_list << contact;
					invite_id += 1;
				end
			end
			
			return filtered_contact_list;
		end
		
		def EmailSearch.find_contacts(email_address, contact_list)
			contact_matches = Array.new();
			for contact in contact_list
				if(email_address == contact.email)
					contact_matches << contact;
				end
			end
			
			for match in contact_matches
				contact_list.delete(match);
			end
			
			if(contact_matches.empty?())
				return nil;
			elsif(contact_matches.length == 1)
				return contact_matches.first;
			else
				return EmailSearch.merge_contacts(contact_matches, contact_list);
			end
		end
		
		def EmailSearch.merge_contacts(contact_matches, contact_list)
			base_contact = contact_matches.first;
			
			if(base_contact.nil?())
				return nil;
			end
			
			base_contact.alt_names = Array.new();
			contact_matches.delete(base_contact);
			
			for contact in contact_matches
				if(!base_contact.name.nil?() && base_contact.name.length > 0)
					base_contact.alt_names << contact.name;
				else
					base_contact.name = contact.name;
				end
				
				contact_list.delete(contact);
			end
			
			return base_contact;
		end
		
		def EmailSearch.has_email_invite?(email_address, invite_list)
			for invite in invite_list
				if(email_address == invite.email)
					return true;
				end
			end
			
			return false;
		end
		
		def EmailSearch.has_optout?(email_address)
			optout = FriendFinder::InviteOptout.find(:first, email_address);
			
			return optout.nil?();
		end
		
		def EmailSearch.search_user_list(user_id, user_list)
			for user in user_list
				if(user.userid == user_id)
					return user;
				end
			end
			return nil;
		end
	end
end
