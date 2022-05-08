lib_require :Core, 'users/user'
lib_require :Core, 'pagelist'
lib_require :Profile, 'profile'
lib_require :Search, 'usersearch'

# This is the user search. Going to /search/ gives the basic input boxes for both simple and advanced searches
#
# The three simple search types are username, email and real name. If a direct match of a username or email 
# exists, it'll just go to the profile. Real name searches aren't implemented yet.
# 
# The advanced search will return paged a list of results. At the moment it just uses a static list of users
# to show what it will look like.

module Search

class Search < PageHandler

	PROFILE_URL="/profile.php?uid=";
	SEARCH_PAGE_SIZE=10;

	declare_handlers("search") {
		area :Public

		page :GetRequest, :Full, :search

		page :GetRequest, :Full, :search_simple_results, "simple", "results"
		page :GetRequest, :Full, :search_advanced_results, "advanced", "results"
#		page :GetRequest, :Full, :search_contacts_results, "contacts", "results"
		
	}

	def get_defaults
		opts = {
			'agemin'    => (request.session.user.anonymous? ? 14 : request.session.user.defaultminage),
			'agemax'    => (request.session.user.anonymous? ? 30 : request.session.user.defaultmaxage),
			'sex'       => (request.session.user.anonymous? ? 2  : (request.session.user.defaultsex == "Male" ? 0 : 1)),
			'location'  => (request.session.user.anonymous? ? 0  : request.session.user.defaultloc),
			'interests' => 0,
#			'socials'   => 0,
			'active'    => 2,
			'pic'       => 1,
			'sexuality' => 0,
			'single'    => false,
		}

		return opts;
	end

	def get_params
		defaults = get_defaults;

		opts = {};
		opts['agemin']    = params['agemin',    14..60,  defaults['agemin']];
		opts['agemax']    = params['agemax',    14..80,  defaults['agemax']];
		opts['sex']       = params['sex',       0..2,    defaults['sex']];
		opts['location']  = params['location',  Integer, defaults['location']];
		opts['interests'] = params['interest',  Integer, defaults['interests']];
#		opts['socials']   = params['socials',   Integer, defaults['socials']];
		opts['active']    = params['active',    0..3,    defaults['active']];
		opts['pic']       = params['pic',       0..2,    defaults['pic']];
		opts['sexuality'] = params['sexuality', 0..3,    defaults['sexuality']];
		opts['single']    = params['single',    Boolean, defaults['single']];

		return opts;
	end

	def search(results = "")
		request.reply.headers['X-width'] = 0;

		template = Template.instance("search", "search");

		t = Template.instance("search", "simple");
		t.type = params['type', String, 'username'];
		t.value = params['value', String, ''];
		template.simple = t.display;


		opts = get_params;

		t = Template.instance("search", "advanced");
		t.agemin    = opts['agemin'];
		t.agemax    = opts['agemax'];
		t.sex       = opts['sex'];
		t.location  = opts['location'];
		t.interest  = opts['interests'];
		t.active    = opts['active'];
		t.pic       = opts['pic'];
		t.sexuality = opts['sexuality'];
		t.single    = opts['single'];
		template.advanced = t.display;
		
		template.results = results;

		puts template.display;
	end

	def search_simple_results
		request.reply.headers['X-width'] = 0;

		type = params['type', ['userid','username','realname','email'].to_set, 'unknown'];
		value = params['value', String, nil].strip;

		if(type == "unknown" && value.length >= 1)
			if(value['@']) #contains an @ sign, must be an email
				type = "email";
			elsif(value[" "]) # a space is not valid for a username, so is probably a real name
				type = "realname";
			elsif(value.match(/^[0-9]+$/)) #only digits is invalid for a username, so probably a userid
				type = "userid";
			else
				type = "username";
			end
		end

		if(type == "userid")
			user = User.get_by_id(value.to_i);
		elsif(type == "username")
			user = User.get_by_name(value);
		elsif(type == "email")
			user = User.get_by_email(value);
		elsif(type == "realname")
			user = nil; #not implemented yet
		end

		if(!user || user.nil?)
			t = Template.instance("search", "user_not_found");
			t.type = type;
			t.value = value;

			search(t.display);
		else
			site_redirect("#{PROFILE_URL}#{user.username}");
		end
	end

	def search_advanced_results
		request.reply.headers['X-width'] = 0;

		page = params['page', PageList, 0];

		opts = get_params;

		user_search = UserSearch.new;

		results = user_search.search(opts, page, SEARCH_PAGE_SIZE);

		if(results['totalrows'] == 0)
			t = Template.instance("search", "no_results_found");
			search(t.display());
			return;
		end

#		results.html_dump

		user_list = User.find(:all, *results['userids']);

		opts = UserSearch.clean_params(opts, get_defaults);

		page_list = PageList.new(UserSearch.prepare("/search/advanced/results", opts), page);
		page_list.set_num_pages(results['totalrows'], SEARCH_PAGE_SIZE);

#		user_list.html_dump

		t = Template.instance("search", "results");
		t.users = user_list;
		t.page_list = page_list;
		search(t.display());
	end
end
end
