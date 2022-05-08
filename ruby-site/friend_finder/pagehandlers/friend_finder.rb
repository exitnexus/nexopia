lib_require :FriendFinder, "email_search", "email_invite", "address_book_importer", "invite_email";
lib_require :Search, "email_search";
lib_require :Friends, "friend";
require 'yaml';

module FriendFinder
	class FriendFinderHandler < PageHandler
		declare_handlers("friends/find"){
			area :Self
			access_level :Activated
			
			page	:GetRequest, :Full, :view_friend_finder_results, "results";
			page	:GetRequest, :Full, :view_friend_finder_invite_results, "results", "view", "invites";
			
			page	:GetRequest, :Full, :view_results_body, "results", "body";
			page 	:GetRequest, :Full, :view_invite_results_body, "results", "invite", "body";
			
			page	:GetRequest, :Full, :view_member_results, "results", "member";
			handle	:PostRequest, :handle_member_results, "results", "member", "submit";
			page	:GetRequest, :Full, :view_invite_results, "results", "invite";
			handle	:PostRequest, :handle_invite_results, "results", "invite", "submit";
			
			handle 	:PostRequest, :finish_email_search, "results", "finish";
			
			handle	:PostRequest, :ajax_handle_invite_results, "results", "invite", "submit", "ajax";
			handle	:PostRequest, :ajax_handle_member_results, "results", "member", "submit", "ajax";
			handle	:PostRequest, :ajax_handle_search_cleanup, "results", "cleanup";
			
		};
		
		def view_friend_finder_results()
			t = Template.instance("friend_finder", "finder_search_results");
			
			request.reply.headers['X-width'] = 0;
			
			t.redirect_location = "/friends/find/results/view/invites/";
			t.finish_location = "/users/#{request.session.user.username}/friends/";
			print t.display();
		end
		
		def view_friend_finder_invite_results()
			t = Template.instance("friend_finder", "finder_invite_search_results");
			
			request.reply.headers['X-width'] = 0;
			
			t.redirect_location = "/users/#{request.session.user.username}/friends/";
			
			print t.display();
		end
		
		def view_results_body()
			t = Template.instance("friend_finder", "search_results_body");
			
			t.redirect_location = params['redirect_location', String];
			
			print t.display();
		end
		
		def view_invite_results_body()
			t = Template.instance("friend_finder", "search_results_invite_wrapper");
			
			t.redirect_location = params['redirect_location', String];
			
			print t.display();
		end
		
		def view_member_results()
			t = Template.instance("friend_finder", "search_results_user");
			
			user_id = request.session.user.userid;
			
			email_search = EmailSearch.find(:first, user_id);
			
			if(email_search.nil?())
				#display error
				return;
			end
			
			contact_list = YAML.load(email_search.contacts);
			
			display_list = Array.new();
			user_id_list = Array.new();
			
			for contact in contact_list
				if(!contact.user_id.nil?())
					display_list << contact;
					user_id_list << contact.user_id;
				end
			end
			
			user_list = User.find(:all, *user_id_list);
			user_hash = user_list.to_hash();
			
			t.user_contact_list = display_list;
			t.user_hash = user_hash;
			t.redirect_location = params["redirect_location", String];
			
			print t.display();
		end
		
		def handle_member_results()
			redirect_location = params["redirect", String];
			
			if(!params.has_key?("member_skip"))
				process_member_results();
			end
			
			site_redirect(redirect_location);
		end
		
		def ajax_handle_member_results()
			process_member_results();
		end
		
		def process_member_results()
			
			user_id_list = Array.new();
			for key in params.keys
				if(/^user_\d+$/.match(key))
					temp_arr = key.split("_");
					user_id_list << temp_arr[1];
				end
			end
			
			search_user = request.session.user;
			
			for user_id in user_id_list
				search_user.add_friend(user_id.to_i());
			end
		end
		
		def view_invite_results()
			t = Template.instance("friend_finder", "search_results_invite");
			
			user_id = request.session.user.userid;
			
			email_search = EmailSearch.find(:first, user_id);
			
			if(email_search.nil?())
				#display error
				return;
			end
			
			contact_list = YAML.load(email_search.contacts);
			
			display_list = Array.new();
			
			for contact in contact_list
				if(contact.user_id.nil?())
					display_list << contact;
				end
			end
			
			t.invite_list = display_list;
			t.redirect_location = params["redirect_location", String];;
			
			print t.display();
		end
		
		def handle_invite_results()
			redirect_location = params["redirect", String];
			
			search_user = request.session.user;
			
			email_search = EmailSearch.find(:first, search_user.userid);
			if(!email_search.nil?())
				email_search.delete();
			end
			
			if(!params.has_key?("invite_skip"));
				process_invite_results(email_search);
			end
			
			site_redirect(redirect_location, :Public);
		end
		
		def ajax_handle_invite_results()
			search_user = request.session.user;

			email_search = EmailSearch.find(:first, search_user.userid);
			
			process_invite_results(email_search);
		end
		
		def process_invite_results(email_search)
			#user_real_name = params["email_name", String, nil];
			invite_message = params["invite_message", String, ""];
			
			invite_id_list = Array.new();
			for key in params.keys
				if(/^invite_\d+$/.match(key))
					temp_arr = key.split("_");
					invite_id_list << temp_arr[1].to_i();
				end
			end
			
			search_user = request.session.user;
			contact_list = YAML.load(email_search.contacts);
			
			email_invite_list = Array.new();
			for contact in contact_list
				if(contact.invite_id != nil && invite_id_list.include?(contact.invite_id))
					temp = InviteEmail.new();
					temp.friend_name = (contact.name.nil?())? "" : contact.name;
					temp.friend_email = contact.email;
					temp.user_name = search_user.username;
					temp.user_email = search_user.email;
					temp.user_id = search_user.userid;
					#temp.user_real_name = user_real_name;
					temp.personalized_message = invite_message;
					email_invite_list << temp;
				end
			end
			
			for invite in email_invite_list
				invite.send();
				temp = EmailInvite.new();
				temp.userid = search_user.userid;
				temp.email = invite.friend_email;
				temp.store();
			end
		end
		
		def finish_email_search()
			self.process_search_cleanup();
			
			finish_location = params["finish_location", String];
			
			site_redirect(finish_location, :Public);
		end
		
		def ajax_handle_search_cleanup()
			self.process_search_cleanup();
		end
		
		def process_search_cleanup()
			search_user = request.session.user;
			
			email_search = EmailSearch.find(:first, search_user.userid);
			
			if(!email_search.nil?())
				email_search.delete();
			end
		end
	end
end
