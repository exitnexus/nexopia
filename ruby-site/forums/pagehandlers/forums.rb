lib_require :Forums, 'category', 'thread', 'forum', 'post', 'forumhelper';

module Forum

    NavigationLink = Struct.new("NavigationLink", :name, :uri, :show_seperator);

    class Forums < PageHandler
    
        include ForumHelper
        
    	declare_handlers("forums") {
    		area :Public
    		access_level :Any
    		
    		page  :GetRequest, :Full, :forum_front;
    		page  :GetRequest, :Full, :forum_front_custom, "custom_display";
    		
    		page  :GetRequest, :Full, :category_list, "category", "new", input(Integer);
    		page  :GetRequest, :Full, :category_custom, "category", "new", input(Integer), "custom_display";
    		
    		page  :GetRequest, :Full, :forum_thread_list, "forum", "new", input(Integer);
    		page  :GetRequest, :Full, :forum_thread_list_custom, "forum", "new", input(Integer), "custom_display";
    		
    		page   :GetRequest, :Full, :index, "old",               remain; # /forums
                                                                      # /forums/order_by
                                                                      # /forums/order_by/filter_phrase
                                                                      # /forums/order_by/subscribed
                                                                      # /forums/order_by/subscribed/filter_phrase
    		page   :GetRequest, :Full, :category, "category", remain; # /forums/category/category_id
                                                                      # /forums/category/category_id/order_by
                                                                      # /forums/category/category_id/order_by/page_number
                                                                      # /forums/category/category_id/order_by/page_number/filter_phrase
    		page   :GetRequest, :Full, :forum,    "forum",    remain; # /forums/forum/forum_id
                                                                      # /forums/forum/forum_id/last
                                                                      # /forums/forum/forum_id/last#end
                                                                      # /forums/forum/forum_id/page_number
                                                                      # /forums/forum/forum_id/page_number/topics_from_last_x_days


            page   :GetRequest, :Full, :subscribe,        "subscribe", remain
            handle :GetRequest,        :subscribe_folder, "subscribe_folder"

            handle :GetRequest, :unsubscribe, "unsubscribe", remain
            handle :GetRequest, :collapse,    "collapse",    remain
            handle :GetRequest, :delete,      "delete",      remain
            handle :GetRequest, :save,        "save",        remain
                        
            page   :GetRequest, :Full, :move, "move", remain

                        
    		
    		page   :GetRequest, :Full, :new_forum,       "forum", "new";

    		handle :PostRequest, :create_forum,  "forum", "new", "submit";
    	}


		        
        #The associated values with the various orderings:
        #0  most active
        #1  most recent
        #2  by name
        def forum_front(order_by = 0, show_only_subscriptions = false, filter = nil)
            t = Template.instance("forums", "forums_front");
            
            #nil is being sent in for the category id. In case you were wondering what the nil
            #represented (without wanting to go find the function signature. :)
            categories = Category.list();
            
            case order_by
                when 0
                    order = :most_active;
                when 1
                    order = :most_recent;
                when 2
                    order = :by_name;
            end
            
            resultset_limit = 5;
            
            #$log.info("The categories we have are: #{categories.inspect}");
            
            category_list = Array.new();
            
            categories.each{ |category|
                            #$log.info("We are looking at #{category.name} (#{category.id})")
                            category.forum_list = category.forums(:total_rows, :order => order, :result_size => resultset_limit, :filter => filter); 
                            if((!category.forum_list.empty?) && (category.forum_list.at(0) != nil))
                                category.view_all = (category.forum_list.at(0).total_rows > resultset_limit)? true : false;
                                category_list << category;
                                #$log.info("The length of the forum_list for  #{category.name} is #{category.forum_list.at(0).total_rows}");
                            else
                                #$log.info("There were no forums in #{category.name}")
                                #categories.delete(category);
                            end
                            #$log.info("\n\n");
                            };
            
            t.base_custom_uri = "/forums";
            
            if(filter != nil)
                t.filter_text_escaped = CGI.escape(filter);                           
            else
                t.filter_text_escaped = nil;
            end
            
            t.filter_text = filter;
            
            if(show_only_subscriptions)
                t.subscription_text = "Show all forums";
                t.new_subscription_filter_value = 0;
                t.current_subscription_filter_value = 1;
            else
                t.subscription_text = "Show only subscriptions";
                t.new_subscription_filter_value = 1;
                t.current_subscription_filter_value = 0;
            end
            
            t.order_by = order_by;
            #t.categories = categories;
            t.categories = category_list;
            
            print t.display;
        end
        
        def forum_front_custom
        
            if(params.has_key?("order_by"))
                order_by = params["order_by", Integer];
            else
                order_by = 0;
            end
            
            if(params.has_key?("show_subscriptions"))
                show_only_subscriptions = (params["show_subscriptions", Integer] == 1)? true : false; 
            else
                show_only_subscriptions = false;
            end
            
            #If the filter text box was empty it will still submit an empty string or nil.
            #This is an undesirable argument, thus we convert it to nil if the filter
            #is an empty string.
            if((params["filter_text", String] == nil) || (params["filter_text", String] == ""))
                filter = nil;
            else
                filter = CGI::unescape(params["filter_text", String]);
            end
            #ask graham about double escaping 
            
            unescaped = CGI::unescape(filter);
            $log.info("Raw value coming in: #{params['filter_text', String]}");
            $log.info("One pass with unescape: #{filter}");
            $log.info("Two passes with unescape: #{unescaped}");
            
            forum_front(order_by, show_only_subscriptions, filter);
        end
        
        #For display purposes all page counts are 1 based, not 0 based. As such we will need to adjust page
        #requests to fit with our 0 based data structures.
        def category_list(category_id, order_by = 0, filter = nil, page = 1);
            
            t = Template.instance("forums", "forums_category_front");
            
            
            
            category = Category.list(category_id);
            #category = Category.find(category_id).first;
            
            case order_by
                when 0
                    order = :most_active;
                when 1
                    order = :most_recent;
                when 2
                    order = :by_name;
            end
            
            page_query = page - 1;
            
            resultset_limit = 20;
            
            category.forum_list = category.forums(:total_rows, :order => order, :requested_page => page_query, :result_size => resultset_limit, :filter => filter);
            
            if((category.forum_list.empty?) && (category.forum_list.at(0) != nil))
                resultset_total_size = category.forum_list.at(0).total_rows;
            else
                resultset_total_size = 0;
            end
            
            create_page_views(t, page, resultset_total_size, resultset_limit);
            
            t.category = category;
            t.base_custom_uri = "/forums/category/new/#{category_id}";
            t.order_by = order_by;
            
            if(filter != nil)
                t.filter_text_escaped = CGI.escape(filter);                           
            else
                t.filter_text_escaped = nil;
            end
            
            t.filter_text = filter;
            
            t.current_page = page;
            if(page > 1)
                t.page_argument = "&page=#{page}";
            end
            
            print t.display;
        end
        
        def category_custom(category_id)
        
            if(params.has_key?("order_by"))
                order_by = params["order_by", Integer];
            else
                order_by = 0;
            end
            
            if((params["filter_text", String] == nil) || (params["filter_text", String] == ""))
                filter = nil;
            else
                filter = CGI::unescape(params["filter_text", String]);
            end
            
            if(params.has_key?("page"))
                page = params["page", Integer];
            else
                page = 1;            
            end
            
            category_list(category_id, order_by, filter, page);
        end
        
        #def forum_thread_list(forum_id, order_by = 0, filter = nil, page = 1, date_range = 31)
        def forum_thread_list(forum_id, page = 1, date_range = 31)   
            t = Template.instance("forums", "forums_forum");
            
            forum = Forum.find(:first, forum_id);
            
            if(PageRequest.current != nil && !request.session.anonymous?() && !request.session.user.anonymous?())
                category_id = ForumSubscription.find(:first, :conditions => ["userid = ? AND forumid = ?", request.session.user.userid, forum_id]);
                category = Category.list(category_id);
            end
            
            if(category == nil)
                category = Category.list(forum.categoryid);
            end
            
            nav_links = Array.new();
            nav_links << NavigationLink.new("Nexopia.com", "/", true);
            nav_links << NavigationLink.new("Forums", "/forums/", true);
            nav_links << NavigationLink.new("#{category.name}", "/forums/category/{category.id}", true);
            nav_links << NavigationLink.new("#{forum.name}", "/forums/forum/{forum.id}/", false);
            
            query_page = page - 1;
            date_range_seconds = date_range * 86400;
            query_date_range = Time.new.to_i - date_range_seconds;
            
            $log.info("The query_date_range is: #{query_date_range} which equals #{Time.new.to_i} + #{date_range_seconds}");
            options = Array.new();
            options.insert(:conditions => ["time >= ? ", query_date_range]);
            
            t.thread_list = forum.thread_list(query_page, 20, *options);
            
            t.forum_id = forum.id;
            t.forum_name = forum.name;
            
            t.nav_links = nav_links;

            print t.display();
        end
        
		#TODO: Create class constants for the default page and date_range.
        def forum_thread_list_custom(forum_id)
            if(params.has_key?("page"))
                page = params["page", Integer];
            else
                page = 1;
            end
            
            if(params.has_key?("date_range"))
                date_range = params["date_range", Integer];
            else
                date_range = 31;
            end
            
            forum_thread_list(forum_id, page, date_range);
        end
        
        def index(remain, category_id = nil, page = nil)
    		t = Template.instance("forums", "category");
            t.logged = request.session;
    		orderby = remain[0];

    		  show_only_subscribed = (remain[1] == "subscribed")? true : false
    		t.show_only_subscribed = ! show_only_subscribed;

    		categories = Category::list(category_id, show_only_subscribed);
    		
            case orderby
                when "mostactive"
                    orderby      = :mostactive;
                    t.orderby    = "mostactive";
                    t.mostactive = true;
                when "alphabetic"
                    orderby      = :alphabetic;
                    t.orderby    = "alphabetic";
                    t.alphabetic = true;
                else
                    orderby      = :mostrecent;
                    t.orderby    = "mostrecent";
                    t.mostrecent = true;
            end
            t.bold     = "<b>";
            t.end_bold = "</b>";

              page  = (page.to_i > 0) ? page.to_i : 1;
            t.page  = page
              page -= 1 # convert to array style index

           	page_count_group      = 7;
           	page_count_individual = 5;

            page_count = (category_id == nil)? page_count_group : page_count_individual

           	if (category_id != nil)
               	t.categoryOrder = true;
               	t.category_id   = category_id;
        	else
               	t.forumOrder = true;
        	end

            opts = {:orderby   => orderby,
                    :total     => true,
                    :pagenum   => page,
                    :pagecount => page_count };

            filter_original = "filter by name"; # params['filter', String];

            if (remain[1] == nil || remain[1] == filter_original)
                filter = ""
            elsif (remain[1] == "subscribed")
                if (remain[2] == nil || remain[2] == filter_original)
                    filter = ""
                else
                    filter = remain[2];
                end                
            else
                filter = remain[1];
            end

            filter = CGI::unescape(filter);

            t.filter_original = filter_original;
            t.filter = filter;
            
            opts.update( {:filter => filter} ) if (filter != "")

            total_rows = 0;

    		categories.each { |cat|
                cat.forumsObj = cat.forums(opts);
                cat.view_all  = false;

                if ( (! cat.forumsObj.empty?) && (cat.forumsObj.at(0) != nil) )
                    total_rows   = cat.forumsObj.at(0).total_rows;
                    cat.view_all = (total_rows > page_count) if (category_id == nil)
                end
    		}

            createPageViews(t, page, total_rows, page_count) if (category_id != nil)

            t.categories = categories;
    		puts t.display();
    	end

        def category(remain)
            index([remain[1], remain[3]], remain[0], remain[2]);
        end

    	def forum(remain)
    		t = Template.instance("forums", "forum");
            t.logged = request.session;

            forum_id    = remain[0].to_i;
            forum       = Forum.find(:first, forum_id);
            category_id = forum.categoryid;

            forum_is_subscribed = false;

#   Fix bellow. Commented so I could get the threads list to work on posts
#
#			Subscription.all.each {|sub|
#    			if (forum_id == sub.forumid)
#        			forum_is_subscribed = true;
#
#                    t.category_id   = sub.category.id;
#                    t.category_name = sub.category.name;
#                end
#            }

            if (! forum_is_subscribed)
        		category = Category::list(category_id);

                t.category_id   = category.id;
                t.category_name = category.name;
            end

            t.forum_id       = forum.id;
            t.forum_name     = forum.name;
            t.forum_official = forum.official;

            if (!request.session.anonymous())
                t.subscribe   = true if (! forum_is_subscribed)
                t.unsubscribe = true if (  forum_is_subscribed)
            end

              page = (remain[1] == nil) ? 0 : remain[1].to_i - 1;
            t.page = page + 1;

              time_range = (remain[2] == nil) ? 1 : remain[2].to_i;
            t.time_range = time_range;

           	page_count = 20;
            total_rows = 0;

            lala = Array.new();

            opts = (time_range == 0) ? [] : [:conditions => ["time >= ?", Time.new.to_i - time_range*86400 ]]

    		forum.announcements.merge(forum.thread_list(page, page_count, opts)).each { |thread|
                thread.get_read_level(request.session.user) if (!request.session.anonymous?() && !request.session.user.anonymous?())

                lala << thread;

                total_rows = thread.total_rows;
            }

            createPageViews(t, page, total_rows, page_count) if (total_rows > 0)

            t.threadsObjObj = lala;

    		puts t.display();
    	end
    	


    	def delete(remain)
            case remain[0]
                when "category"
                    category_id = remain[1];

                    category = Category.list(category_id);
                    category.at(0).delete() if (! category.empty?)
        
                    subscription = Subscription.find(:all, :conditions => ['categoryid = #', category_id]);
                    subscription.each { |subs| subs.delete(); }
    
                    site_redirect("/forums");
    	   end
        end

    	def save(remain)
            case remain[0]
                when "category"
                    category_id = remain[1];

                    category = Category.list(category_id);
                    if (! category.empty?)
                        category.at(0).name = remain[2];
                        category.at(0).store();
                    end
    	   end
        end

    	def move(remain)
    	   remain << true;
    	   subscribe(remain);
        end
        
    	def subscribe(remain)
    		t = Template.instance("forums", "subscribe");

            forum = Forum.find(:first, remain[0]);

            t.forum_name  = forum.name;
            t.forum_id    = forum.id;
            t.page_number = remain[1];
            t.time_range  = remain[2];

            t.label_subscribe = (remain[3] == true)? false : true
            
    		t.officialCategories = SiteCategory.find(:order => 'priority ASC');
       		t.userCategories     = UserCategory.find(:conditions => ['userid = #', request.session.user.userid], :order => 'name ASC') if (!request.session.anonymous?() && !request.session.user.anonymous?());

    		puts t.display();
    	end

    	

    	def unsubscribe(remain)
            forum_id    = remain[0];
            page_number = remain[1];
            time_range  = remain[2];

            return if (request.session.anonymous?() && forum_id == nil);
            
			subs = Subscription.find(:first, PageRequest.current.session.userid, forum_id);
			subs.delete() if (subs)
            
            site_redirect("/forums/forum/#{forum_id}/#{page_number}/#{time_range}");
    	end

    	def collapse(remain)
            remain[1] = (remain[1] == "true")? true : false

            if (!request.session.anonymous?())
        		categories = Category::list(remain[0]);
                categories.at(0).set_collapse(remain[1]);
            end
    	end

        def forum_subscribe()
        
        end

        def subscribe_folder()
            return if (request.session.anonymous?());

            forum_id             = params["forum_id",    Integer];
            category_id          = params["category_id", Integer];
            page_number          = params["page_number", Integer];
            time_range           = params["time_range",  Integer];
            new_category         = params["new_category", String];
            new_category_default = params["new_category_default", String];

            # Insert new category
            if ( new_category != "" && new_category != new_category_default )
        		category_id = UserCategory.get_seq_id(request.session.user.userid);

        		userCategory         = UserCategory.new();
        		userCategory.userid  = request.session.user.userid;
        		userCategory.name    = new_category;
        		userCategory.id      = category_id;
        		userCategory.store();
            end

            # Save subscription info
            subscription = Subscription.find(:first, request.session.user.userid, forum_id);
       		subscription = Subscription.new() if (subscription == nil)

    		subscription.userid      = request.session.user.userid;
    		subscription.forumid     = forum_id;
    		subscription.categoryid  = category_id;
    		subscription.userdefined = true;
    		subscription.store();

#           forumRead = ForumRead.find(:first, :conditions => ["userid = # && threadid = ?", request.session.user.userid, remain[1]]);
#           forumRead.subscribe();

            site_redirect("/forums/forum/#{forum_id}/#{page_number}/#{time_range}");
    	end

        def new_forum()
            t = Template.instance("forums", "forums_new_forum");

            t.forum_categories = Category::list(nil);

            print t.display;
        end

        def create_forum()
            forum_name = params['forum_name', String];
            forum_desc = params['forum_desc', String];
            category_id = params['forum_category', Integer];
            forum_auto_lock = params['forum_thread_lock_time', Integer];
            forum_post_edit = params['forum_post_edit', String];
            forum_official = params['forum_official', String];
            forum_public = params['forum_public', String];
            forum_mute = params['forum_mute', String];

            forum = Forum.new();
            forum.id = Forum.create_account;
            forum.name = forum_name;
            forum.description = forum_desc;
            forum.categoryid = category_id;
            forum.time = Time.now.to_i();
            forum.edit = forum_post_edit;
            forum.public = (forum_public == "y")? true : false;
            forum.mute = (forum_mute == "y")? true : false;
            forum.official = (forum_official == "y")? true : false;
            forum.autolock = (forum_auto_lock > 0)? forum_auto_lock*86400 : 0;
            forum.ownerid = request.session.user.userid;
            forum.edit = forum_post_edit;

            forum.store();

            if(session.user.forumjumplastpost)
                site_redirect(url / :forums / :forum / forum.id);
            else
                site_redirect(url / :forums);
            end
        end
    end
end
