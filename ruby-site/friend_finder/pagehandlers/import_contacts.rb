lib_require :FriendFinder, 'address_book_importer'
lib_require :Search, 'email_search';

module FriendFinder
	class ImportContacts < PageHandler
		declare_handlers("friends/find") {
			area :Self
			access_level :Activated
			
			page	:GetRequest, :Full, :view_friend_finder_import;
			page	:GetRequest, :Full, :view_import_body, "import", "body";
			handle	:GetRequest, :import, "import";
		}

		def view_friend_finder_import()
			t = Template::instance('friend_finder', 'import_emails');
			request.reply.headers['X-width'] = 0;
			
			t.redirect_location = "/friends/find/results/";
			
			puts t.display();
		end
		
		def view_import_body()
			t = Template.instance("friend_finder", "import_emails_body");
			
			t.redirect_location = params["redirect_location", String];
			t.finish_location = params["finish_location", String];
			
			print t.display();
		end

		def import
			contacts = []
			email = params['email', String]
			password = params['password', String]
			file = params['file_mogile', String]
			type = params['file_type', String]
			search_emails = params['search_address', Array];
			redirect_location = params['redirect', String];
			finish_location = params["finish_redirect", String];
			
			#Short circuit for finish buttons!
			if(params.has_key?("finish") && !finish_location.nil?())
				site_redirect(finish_location);
			end
			
			if (email && password)
				contacts += AddressBookImporter.fetch_contacts(email, password)
			end
			if (file && type)
				contacts += AddressBookImporter.fetch_file_contacts(file, type)
			end
			
			if (search_emails)
				contacts += AddressBookImporter.convert_emails_to_contacts(search_emails)
			end

			#cleanup the uploaded file
			#post_process_queue("FriendFinderModule", "remove_contacts_file", array_pop(split('/',$file_name)));
			Worker::PostProcessQueue.queue(FriendFinderModule, "remove_contacts_file", [file])
			
			contacts = Search::EmailSearch.search_contacts(contacts, request.session.user);

			old_search = EmailSearch.find(:first, request.session.user.userid);
			if(!old_search.nil?())
				old_search.delete();
			end
			
			e_search = EmailSearch.new();
			e_search.userid = request.session.user.userid;
			e_search.contacts = contacts.to_yaml();
			e_search.date = Time.now.to_i();
			
			e_search.store();
			
			site_redirect(redirect_location);
		end
	end
end
