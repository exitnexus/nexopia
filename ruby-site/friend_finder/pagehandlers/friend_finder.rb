lib_require :FriendFinder, 'address_book_importer';
lib_require :FriendFinder, "invite_optout";
lib_require	:FriendFinder, "email_invite";
lib_require :FriendFinder, "email_search";
lib_require :FriendFinder, "friend_finder_user";
lib_require :Search, 'email_search';
lib_require :FriendFinder, 'contacts/base', 'contacts/gmail', 'contacts/yahoo', 'contacts/plaxo'
lib_require :Core, 'validation/rules';
lib_require :FriendFinder, "windows_live_login";
lib_require :FriendFinder, "hotmail_importer";

lib_want :Metrics, 'incremental_metric_data', 'category_friends';
lib_want :Orwell, 'send_email';

require 'rexml/document';

module FriendFinder
	class FriendFinderHandler < PageHandler
		declare_handlers("friends/find"){
			area :Public
			access_level :Any
			page		:GetRequest, :Full, :find_contacts;	
			
			handle		:OpenPostRequest, :import_hotmail_contacts, "hotmail", "return";
			
			access_level :NotLoggedIn
			page		:GetRequest, :Full, :anonymous_view_user_results, "results", "user", input(Integer);
			handle	:PostRequest, :import_contacts, "import";
			
			area :Self
			access_level :Activated
			
			page		:GetRequest, :Full, :find_contacts;
			page		:GetRequest, :Full, :find_contacts, input(/^fresh$/);
			handle	:GetRequest, :find_contacts_body, "body";
			
			page		:GetRequest, :Full, :view_user_results, "results", "user";
			page		:GetRequest, :Full, :view_invite_results, "results", "invite";
			
			page		:GetRequest, :Full, :view_manual_invite, "invite", "manual";
			page		:GetRequest, :Full, :view_manual_invite, "invite", "manual", input(/^full$/);
			
			page		:GetRequest, :Full, :complete_import_process, "complete";
			
			handle	:PostRequest, :import_contacts, "import";
			handle	:PostRequest, :process_user_results, "results", "user", "submit";
			handle	:PostRequest, :process_invite_results, "results", "invite", "submit";
		};
		
		def import_hotmail_contacts()
			wll = WindowsLiveLogin.new();
			
			consent_token = params['ConsentToken', String, ""];
			
			consent = wll.processConsentToken(consent_token);
			importer = HotmailImporter.new();
			contacts = [];
			
			if(!request.session.user.anonymous?())
				search_type = $site.memcache.get("friend_finder_hotmail_search_type-#{request.session.user.userid}");
				$site.memcache.delete("friend_finder_hotmail_search_type-#{request.session.user.userid}");
				
				search_type = search_type.to_i();
				if(search_type.nil?())
					search_type = 1;
				end
			end
			
			if(consent.nil?() || !consent.isValid?())
				if(request.session.user.anonymous?())
					error_key = rand(2^31-1);
				else
					error_key = request.session.user.userid;
				end
				
				$site.memcache.set("friend_finder_error-#{error_key}", "The consent token wasn't valid", 15);
				if(request.session.user.anonymous?())
					site_redirect(url / :friends / :find / error_key);
				else
					if(search_type == 1)
						site_redirect(url / :friends / :find, :Self);
					else
						site_redirect(url / :friends / :find / :fresh, :Self);
					end
				end
			end
			
			uri = URI.parse("https://livecontacts.services.live.com/@L@#{consent.locationid}/rest/LiveContacts/Contacts/");
			req = Net::HTTP.new(uri.host, uri.port);
			req.use_ssl = true;
			req.start{
				req.request_get(uri.path, {"Authorization" => "DelegatedToken dt=\"#{consent.delegationtoken}\""}){|response|
					contacts = importer.import_contacts(response.body);
				}
			};

			process_import_contacts(contacts, request.session.user, search_type);
		end
		
		def find_contacts(new_user = nil)
			request.reply.headers['X-width'] = 0;
			
			if(!new_user.nil?() && new_user.length > 0)
				t = Template.instance("friend_finder", "friend_finder_front_new_user");
				t.search_type = 0;
			else
				t = Template.instance("friend_finder", "friend_finder_front");
				t.search_type = 1;
				t.self_area_form_key = SecureForm.encrypt(request.session.user, "/Self/friends/find");
			end
			
			if(request.session.user.anonymous?())
				t.submit_url = url / :friends / :find / :import;
			else
				t.submit_url = url / :my / :friends / :find / :import;
			end
			
			anon_error_key = params["error_id", Integer, nil];
			if(request.session.user.anonymous?() && !anon_error_key.nil?())
				error_key = anon_error_key;
			elsif(!request.session.user.anonymous?())
				error_key = request.session.user.userid;
			end
			
			if(!error_key.nil?())
				t.error_msg = $site.memcache.get("friend_finder_error-#{error_key}");
				$site.memcache.delete("friend_finder_error-#{error_key}");
			end
			
			t.viewing_user = request.session.user;
			print t.display();
		end
		
		def import_contacts()
			contacts = [];
			
			#Delete the previous search, if it exists.
			if(!request.session.user.anonymous?())
				temp = EmailSearch.new();
				temp.userid = request.session.user.userid;
				temp.delete();
			end
			
			email = params['import_email', String, nil];
			password = params['import_password', String, nil];
			search_type = params["search_type", Integer, 1];
			email_error = false;
			
			val_rule = Validation::Rules::CheckEmailSyntax.new(Validation::ValueAccessor.new(nil, email));
			result = val_rule.validate();
			
			if(result.state != :valid)
				email_error = true;
				message = "The email provided is not a valid email address";
			end
			
			if(request.session.user.anonymous?())
				error_key = rand(2^31-1);
			else
				error_key = request.session.user.userid;
			end
			
			email_parts = email.split("@");
			if(email_parts.length != 2 && !email_error)
				email_error = true;
				message = "A processing error has occurred";
			end
				
			if(!email_error)
				email_domain = email_parts[1];
				if(email_domain.match(/^hotmail\.(com|fr|co\.uk){1}$/) || email_domain.match(/^live\.(com|ca|co\.uk|fr|com\.au|nl|jp){1}$/))
					search_type = params["search_type", Integer, 1];
					if(!request.session.user.anonymous?())
						$site.memcache.set("friend_finder_hotmail_search_type-#{request.session.user.userid}", search_type, 600);
					end
					wll = WindowsLiveLogin.new();
					external_redirect(wll.getConsentUrl());
				end
			
				if (!email.nil?() && !password.nil?() && !(email == "" || password == "") )
					begin
						contacts = AddressBookImporter.import(email, password);
					rescue
						if($!.kind_of?(Contacts::AuthenticationError) || $!.kind_of?(EmailNotSupportedError))
							message = $!.message;
						else
							message = "An error occurred while processing."
							$log.info $!.to_s, :error;
							$log.info $!.backtrace, :error;
						end
					end
				else
					if(email.nil?() || email == "")
						message = "Email address must not be blank.";
					elsif(password.nil?() || password == "")
						message = "Password must not be blank.";
					end
				end
			end
			
			if(!error_key.nil?() && !message.nil?())
				$site.memcache.set("friend_finder_error-#{error_key}", message, 15);
				if(request.session.user.anonymous?())
					site_redirect(url / :friends / :find / error_key);
				else
					if(search_type == 1)
						site_redirect(url / :friends / :find);
					else
						site_redirect(url / :friends / :find / :fresh);
					end
				end
			end
			
			process_import_contacts(contacts, request.session.user, search_type);
		end
		
		def process_import_contacts(contacts, search_user, search_type)
			search_result = Search::EmailSearch.search_contacts(contacts, search_user);
						
			if(search_user.anonymous?())
				rand_num = rand(2^31-1);
				
				$site.memcache.set("anonymous_friend_finder_search-#{rand_num}", search_result[:contacts], 20);
				
				if(site_module_loaded?(:Metrics))
					m = Metrics::IncrementalMetricData.new()
					m.categoryid = Metrics::CategoryFriends.typeid;
					m.metric = 3;
					m.usertype = 'na';
					m.col = 0;
					m.date = Metrics::CategoryFriends.get_start_of_day(Time.now);
					m.value = 1;
					m.store(:duplicate, :increment => [:value, 1]);
				end
				
				site_redirect(url / :friends / :find / :results / :user / rand_num);
			end
			
			if(search_result.nil?() || search_result[:contacts].empty?())
				site_redirect(url / :friends / :find / :invite / :manual, :Self);
			end
			
			e_search = EmailSearch.new();
			e_search.userid = search_user.userid;
			e_search.contacts = search_result[:contacts].to_yaml();
			e_search.date = Time.now.to_i();
			e_search.store(:duplicate);
			
			if(site_module_loaded?(:Metrics))
				m = Metrics::IncrementalMetricData.new()
				m.categoryid = Metrics::CategoryFriends.typeid;
				m.metric = 5;
				m.usertype = 'na';
				m.col = search_type;
				m.date = Metrics::CategoryFriends.get_start_of_day(Time.now);
				m.value = 1;
				m.store(:duplicate, :increment => [:value, 1]);
			end
			
			if(!search_user.anonymous?() && !search_user.user_task_list.empty?())
				search_user.user_task_list.each{|task|
					if(task.taskid == 1)
						task.delete();
					end
				};
			end
			
			if(search_result[:users])
				site_redirect(url / :friends / :find / :results / :user & {:search_type => search_type}, :Self);
			elsif(search_result[:invites])
				site_redirect(url / :friends / :find / :results / :invite & {:added => -1, :search_type => search_type}, :Self);
			else
				site_redirect(url / :friends / :find / :invite / :manual, :Self);
				#Dunno
			end
		end
		
		def anonymous_view_user_results(import_id)
			request.reply.headers['X-width'] = 0;
			
			t = Template.instance("friend_finder", "anonymous_user_results_finder");
			
			contact_list = $site.memcache.get("anonymous_friend_finder_search-#{import_id}");
			
			if(contact_list.nil?())
				contact_list = [];
			end
			
			$site.memcache.delete("anonymous_friend_finder_search-#{import_id}");
			
			if(site_module_loaded?(:Metrics))
				m = Metrics::IncrementalMetricData.new()
				m.categoryid = Metrics::CategoryFriends.typeid;
				m.metric = 4;
				m.usertype = 'na';
				m.col = 0;
				m.date = Metrics::CategoryFriends.get_start_of_day(Time.now);
				m.value = contact_list.length;
				m.store(:duplicate, :increment => [:value, contact_list.length]);
			end
			
			if(contact_list.empty?())
				#do something else
			end
			
			user_results = [];
			
			contact_list.each{|contact|
				if(!contact.user_id.nil?() && contact.user_id > 0)
					user_results << contact;
				end
			};
			
			user_id_list = user_results.map{|contact| contact.user_id};
			
			if(!user_id_list.empty?())
				user_list = User.find(*user_id_list).to_hash;
			end
			
			user_results.each{|contact|
				contact.user = user_list[[contact.user_id.to_i()]];
			};
			
			t.user_results = user_results;
			
			print t.display();
		end
		
		def view_user_results()
			request.reply.headers['X-width'] = 0;
			
			t = Template.instance("friend_finder", "user_results_finder");
			
			email_search = EmailSearch.find(:first, request.session.user.userid);
			contact_list = YAML.load(email_search.contacts);
			user_results = [];

			contact_list.each{|contact|
				if(!contact.user_id.nil?() && contact.user_id > 0)
					user_results << contact;
				end
			};
			
			user_id_list = user_results.map{|contact| contact.user_id};
			
			user_list = User.find(*user_id_list).to_hash;
			
			user_results.each{|contact|
				contact.user = user_list[[contact.user_id.to_i()]];
			};
			
			t.user_results = user_results;
			t.search_type = params["search_type", Integer, 1];
			t.submit_url = url / :my / :friends / :find / :results / :user / :submit;

			
			print t.display();
		end
		
		def process_user_results()
			add_friend_list = [];
			params.keys().each{|key|
				if(key.match(/^friend_\d*$/))
					key_parts = key.split("_");
					add_friend_list << key_parts[1].to_i();
				end
			};
			
			add_friend_list.each{|user_id|
				request.session.user.add_friend(user_id);
			};
			
			search_type = params["search_type", Integer, 1];
			if(site_module_loaded?(:Metrics))
				m = Metrics::IncrementalMetricData.new()
				m.categoryid = Metrics::CategoryFriends.typeid;
				m.metric = 2;
				m.usertype = 'na';
				m.col = search_type;
				m.date = Metrics::CategoryFriends.get_start_of_day(Time.now);
				m.value = add_friend_list.length;
				m.store(:duplicate, :increment => [:value, add_friend_list.length]);
			end
			
			if(add_friend_list.empty?())			
				site_redirect(url / :friends / :find / :results / :invite & {:search_type => search_type});
			else
				site_redirect(url / :friends / :find / :results / :invite & {:added => add_friend_list.length, :search_type => search_type});
			end
		end
		
		def view_invite_results()
			request.reply.headers['X-width'] = 0;
			
			t = Template.instance("friend_finder", "invite_results_finder");

			email_search = EmailSearch.find(:first, request.session.user.userid);
			contact_list = YAML.load(email_search.contacts);
			invite_results = [];
			
			contact_list.each{|contact|
				if(!contact.invite_id.nil?())
					invite_results << contact;
				end
			};
			
			friends_added = params["added", Integer, 0];
			
			if(invite_results.empty?())
				site_redirect(url / :friends / :find / :invite / :manual & {:invites_found => -1});
			end
			
			t.friends_added = friends_added;
			t.invite_results = invite_results;
			t.search_type = params["search_type", Integer, 1];
			t.submit_url = url / :my / :friends / :find / :results / :invite / :submit;
			
			print t.display();
		end
		
		def process_invite_results()
			manual_invite_list = [];
			invite_list = [];
			contact_invites = [];
			manual_search = params["manual_search", Integer, 0];
			
			email_search = EmailSearch.find(:first, request.session.user.userid);
			if(!email_search.nil?())
				contact_list = YAML.load(email_search.contacts);
			else
				contact_list = [];
			end
			
			email_invite_list = EmailInvite.find(request.session.user.userid).to_hash();
			
			params.keys().each{|key|
				if(key.match(/^manual_invite_\d{1,2}$/))
					temp = params[key, String].strip();
					val_rule = Validation::Rules::CheckEmailSyntax.new(Validation::ValueAccessor.new(nil, temp));
					result = val_rule.validate();
					
					if(result.state == :valid && email_invite_list[[request.session.user.userid, temp]].nil?())
						manual_invite_list << [temp];
					end
				elsif(key.match(/^invite_\d*$/))
					key_parts = key.split("_");
					contact_invites << key_parts[1].to_i();
				end
			};
			
			if(!manual_invite_list.empty?())
				user_email_results = UserEmail.find(:email, *manual_invite_list);
				user_email_list = user_email_results.map{|user_email| user_email.email};
				email_opt_out_list = InviteOptout.find(*manual_invite_list).to_hash();
			end
			
			manual_invite_list.each{|invite|
				if(!user_email_list.include?(invite[0]) && !email_opt_out_list.keys.include?(invite))
					invite_list << invite[0];
				end
			};
			
			contact_list.each{|contact|
				if(contact_invites.include?(contact.invite_id))
					invite_list << contact.email;
				end
			};
			
			invite_list.uniq!();
			
			invite_list.each{|invite|
				msg = Orwell::SendEmail.new();
				msg.subject = "You've got a friend on #{$site.config.site_name}";
				
				begin
				msg.send(nil, 'user_invite_email_plain',
				 	:html_template => 'user_invite_email', 
					:template_module => 'friend_finder',
					:to => invite,
					:invite_user => request.session.user,
					:invite_email_address => invite
				);
				rescue
					$log.info "Problem sending invite email";
					$log.info $!.to_s(), :error;
					$log.info $!.backtrace, :error;
					next;
				end 
				invite_obj = EmailInvite.new();
				invite_obj.userid = request.session.user.userid;
				invite_obj.email = invite;
				invite_obj.date = Time.now.to_i();
				invite_obj.store(:ignore);
			};
			
			search_type = params["search_type", Integer, 1];
			if(site_module_loaded?(:Metrics))
				m = Metrics::IncrementalMetricData.new()
				m.categoryid = Metrics::CategoryFriends.typeid;
				m.metric = 1;
				m.usertype = 'na';
				m.col = search_type;
				m.date = Metrics::CategoryFriends.get_start_of_day(Time.now);
				m.value = invite_list.length;
				m.store(:duplicate, :increment => [:value, invite_list.length]);
			end
			
			if(!email_search.nil?())
				email_search.delete();
			end
			
			if(manual_search == 1)
				site_redirect(url / :friends / :find / :invite / :manual / :full & {:invites_sent => invite_list.length});
			else
				site_redirect(url / :profile);
			end
		end
		
		def view_manual_invite(tabbed_page = nil)
			request.reply.headers['X-width'] = 0;
			
			invites_found = params["invites_found", Integer, 0];
			invites_sent = params["invites_sent", Integer, 0];
			
			if(!tabbed_page.nil?() && tabbed_page.length > 0)
				t = Template.instance("friend_finder", "manual_invite_front");
				t.manual_search = 1;
				t.invites_sent = invites_sent;
			else
				t = Template.instance("friend_finder", "manual_invite_new_user");
			end
			
			t.invites_found = invites_found;
			print t.display();
		end
		
		def complete_import_process()
			email_search = EmailSearch.find(:first, request.session.user.userid);
			if(!email_search.nil?())
				email_search.delete();
			end
			
			site_redirect(url / :profile);
		end
	end
end
