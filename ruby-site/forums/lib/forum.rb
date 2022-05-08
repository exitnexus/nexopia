lib_require :Core,  'storable/storable', 'privilege', 'accounts', 'users/user'
lib_require :Forums, 'category', 'thread'
lib_want :Observations, 'observable'

# reopen user
class User
	def subscriptions
		#HAZARD: Live DB is split, Dev is not.  This is written to work on Dev,
		# hopefully just have to change ? to #.
		res = Forum::Forum.db.query("SELECT forumthreads.id,forumthreads.title FROM forumread,forumthreads WHERE forumread.subscribe='y' && forumread.userid = ? && forumread.threadid=forumthreads.id && forumread.time < forumthreads.time", self.userid);
		res2 = [];
		res.each{|h|
			res2 << h
		}
		res2
	end
end

module Forum
	class Forum < Storable
	        # :threadsObjObj is a way to access :threadsObj data from inside the template
        attr_accessor :time_readable

		init_storable(:forumdb, "forums");
		relation_singular :site_category, :categoryid, SiteCategory;
		#relation_multi :announcements, :id, Thread, :order => 'time DESC', :conditions => "announcement = 'y'";
		#relation_paged :user_threads, :id, Thread, :order => 'time DESC', :conditions => "announcement = 'n'";

        register_selection(:forum_exist, :id);

		extend Privilege::Privileged;
		define_privileges ForumsModule,
			:view, :post, :postglobal, :postlocked, :move,
			:editownposts, :editallposts, :deleteposts,
			:deletethreads, :lock, :stick, :mute, :globalmute,
			:invite, :announce, :flag, :modlog, :editmods, :admin;

		include AccountType;
		if (site_module_loaded? :Observations)
			include Observations::Observable
			observable_event :create, proc{"#{owner.link} created a Forum entitled '#{@name}.'"}
		end

		def owner
			return User.get_by_id(@ownerid);
		end

        def after_load
			@time_readable = Time.at(time).strftime("%b %d, %y %I:%M %p") # %I:%M %p
        end

		def is_owner?
			return PageRequest.current.session.user.userid == ownerid;
		end

		def pre_has_view?
			return public;
		end
		def pre_has_post?
			return public;
		end

		#TODO: Have thread_list work like an overriden find for threads.
		#
		def thread_list(*args)
			#announcements = Thread.find();
			announcements = Thread.find(:order => "time DESC", :conditions => "announcement = 'y'");
            threads = self.announcements;
            threads.merge(self.user_threads(requested_page, result_size, options));

            #$log.info("The returned threads are: #{threads.inspect}");

            if((PageRequest.current != nil) && !PageRequest.current.session.anonymous?() && !request.session.user.anonymous?())
                user_thread_view = ForumRead.find(:conditions => ["userid = ?", request.session.user.userid]);
                #$log.info("The ForumRead.find() returned: #{user_thread_view.inspect}");
            end

            if(user_thread_view != nil)
                threads.each{ |thread|
                            thread_read = user_thread_view[[thread.id, request.session.user.userid]];
                            if(thread_read != nil)
                                thread.subscribed = thread_read.subscribe;
                                if(thread_read.posts > (thread.posts + 1))
                                    thread.read_level = :new_replies;
                                else
                                    thread.read_level = :unchanged;
                                end
                            end
                            };
            end

            return threads;
		end

		#TODO: Refactor find to break it up into some smaller function calls. Right now it is
		#way too big (Over 275 lines). Best candidates for method extraction include the subscription
		#block, limit block, and offset block.
		def Forum.find(*args)

            #TODO: Make the limit and offset defaults class constants.
            #default values for result_size (limit) and requested_page (offset)
            result_size_default = 20;
            requested_page_default = 0;
            minimum_non_subscribed_result_size = 3;

    		if(args != nil)
                $log.info("The incoming args are: #{args.inspect}");
            else
                $log.info("The args are nil");
            end

            #This function will seperate the incoming args into items for the
            #Storable find query and for the custom args we want for the forum
            #find. It is returned in a hash with the storable args associated
            #with the :storable_args symbol and the forum options associated with
            #the :forum_args symbol.
            separated_args = self.extract_forum_options_from_args(args);
            args = separated_args[:storable_args];
            options = separated_args[:forum_args];

            #Here we can determine if there were any conditions sent in seperate
            #from the forum options. Conditions should be sent in as an array.
            #According to the Storable documentation the first element will be
            #the query and following that will be the needed arguments. After we
            #have taken the conditions out of the args array, we remove it from the
            #args. A modified version will be inserted later.
            #
            #If there are no conditions we initialize with an empty string and
            #a new Array.
            conditions = self.find_option_from_args(:conditions, args);
            if(conditions != nil)
                conditions_query = conditions.delete_at(0);
                conditions_args = conditions;
                self.remove_option_from_args!(:conditions, args);
            else
                conditions_query = "";
                conditions_args = Array.new();
            end

            #Category ID is a conditional query field. Will only
            #select forums which belong to the specified category.
            if(options.has_key?(:category_id))
                category_id = options[:category_id];
                category_query = "categoryid = ?";
                conditions_args << category_id;
                if(conditions_query == "")
                    conditions_query = category_query;
                else
                    conditions_query = conditions_query + " AND " + category_query;
                end
            end

            #This is the handling group for the Storable limit argument.
            #We also support the forums specific argument of :result_size.
            #The expectation is for only one of these two options to be present.
            #If both are present we will have the storable :limit override the
            #forum specific :result_size and a warning will be printed. If neither
            #are present the default result size will be used.
            #
            #Negative values and zero are illegal values. If one of those are
            #found the default result size (specified locally at the top of the
            #function definition). This is because limit must be appropriately set
            #to define the offset (if present).
            limit = self.find_option_from_args(:limit, args);
            if(limit == nil)
                if(options.has_key?(:result_size))
                    limit = options[:result_size];
                else
                    limit = result_size_default;
                end
            else
                self.remove_option_from_args!(:limit, args);
                if(options.has_key?(:result_size))
                    $log.warning("Only one of ':limit' and ':result_size' should be specified. Using ':limit'.");
                end
            end

            if((limit <= 0))
                limit = result_size_default;
            end

             # TODO: Subscription filtering in the forum.find() must be completed. Not done at all right now.
            if(options.has_key?(:subscriptions_only) && (options[:subscriptions_only] == true))
                if((PageRequest.current) && (PageRequest.current.session) && options.has_key?(:category_id))
                    subscribed_forums = ForumSubscription.find(:conditions => ["categoryid = ? AND userid = ?", options[:category_id], PageRequest.current.session.userid]);
                    if(!subscribed_forums.empty?())
                        subscription_args = Array.new();
                        subscribed_forums.each{|forum|
                                                subscription_args << forum.forumid;
                                                };
                        args << subscription_args;
                    end
                end
            else
                if((PageRequest.current) && PageRequest.current.session) && (options.has_key?(:category_id))
                    #subscribed_forums = ForumSubscription.find(:conditions => ["categoryid = ? AND userid = ?", options[:category_id], PageRequest.current.session.userid]);
                    subscribed_forums = ForumSubscription.all();
                    $log.info("The subscribed forums are: #{subscribed_forums.inspect}")
                    if(!subscribed_forums.empty?())
                        if(options.has_key?(:exclude))
                            exclude_args = options[:exclude];
                            subscribed_forums.each{ |forum|
                                                    exclude_args << forum.forumid;
                                                    };
                        else
                            subscription_args = Array.new();
                            subscribed_forums.each{|forum|
                                                    subscription_args << forum.forumid;
                                                    };
                            options.store(:exclude, subscription_args);
                        end

                        subscription_find_args = Array.new();
                        subscribed_forums.each{ |forum|
                                                if(forum.categoryid == options[:category_id])
                                                    subscription_find_args << forum.forumid;
                                                end
                                                };
                    end
                end
            end

            if((subscription_find_args != nil) && (!subscription_find_args.empty?()))
                $log.info("The forums I am getting for subscriptions are: #{subscription_find_args.inspect}");
                subscription_results = super(subscription_find_args, :total_rows);
                if((!subscription_results.empty?()) && (subscription_results.at(0) != nil))
                    limit = limit - subscription_results.at(0).total_rows;
                    if(limit < minimum_non_subscribed_result_size)
                        limit = minimum_non_subscribed_result_size;
                    end
                end
            end

            #View Private is a conditional query field.
            #View Private can be sent in as an argument
            #with no value or as an argument with a true/false
            #value.
            #If it is present with no argument or it has a true
            #argument the query should return all forums, not just
            #public forums.
            if(!options.has_key?(:view_private))
                if(!options[:view_private])
                    private_query = "public = ?";
                    conditions_args << 'y';
                    if(conditions_query == "")
                        conditions_query = private_query;
                    else
                        conditions_query = conditions_query + " AND " + private_query;
                    end
                end
            end

            #Excude is a conditional query field.
            #Exclude is to be sent in with an array of values.
            #It is to exclude certain specified forums by id.
            if(options.has_key?(:exclude))
                exclude_query = "id NOT in (?)";
                conditions_args << options[:exclude];
                if(conditions_query == "")
                    conditions_query = exclude_query;
                else
                    conditions_query = conditions_query + " AND " + exclude_query;
                end
            end

            #Filter is a conditional query field.
            #Filter is a search string for the name
            #and description columns. The query string
            #is added to the conditions args array twice
            #because we are looking for it in two columns.
            if(options.has_key?(:filter))
                if(options[:filter] != nil)
                    filter_query = "(name LIKE ? OR description LIKE ?)";
                    conditions_args << "%#{options[:filter]}%";
                    conditions_args << "%#{options[:filter]}%";
                    if(conditions_query == "")
                        conditions_query = filter_query
                    else
                        conditions_query = conditions_query + " AND " + filter_query;
                    end
                end
            end

            #Order is the query ordering.
            #Order can be a forum option or specified as normal for a Storable find.
            #If it is a forum option it will be associated with a symbol. From these
            #symbols we can specify how we'd like the sorting to be performed.
            #If it is a normal Storable order, it will be passed in as a string and
            #we will pass it along as such.
            if(options.has_key?(:order))
                order = options[:order];
                if(order.is_a?(Symbol))
                    case order
                        when :most_active
                            order_query = "unofficial DESC, posts DESC";
                        when :most_recent
                            order_query = "unofficial DESC, time DESC";
                        when :by_name
                            order_query = "unofficial DESC, name ASC";
                    end
                else
                    order_query = order;
                end
            end

            #This is the handling group for the Storable offset argument.
            #There is also support for the forums specific argument of :requested_page.
            #The offset determined by :requested_page is calculated by multiplying
            #the limit value and the requested page.
            #
            #If neither argument is present the offset will be set to the default.
            #
            #If both arguments are present the :offset will override the forum specific
            #:requested_page. A warning will also be printed out indicating this.
            #
            #An offset of less than 0 is an illegal argument. If that is the case, the
            #default will be used.
            offset = self.find_option_from_args(:offset, args);
            if(offset == nil)
                if(options.has_key?(:requested_page))
                    offset = options[:requested_page] * limit;
                else
                    offset = requested_page_default * limit;
                end
            else
                self.remove_option_from_args!(:offset, args);
                if(options.has_key?(:requested_page))
                    $log.warning("Only one of ':offset' and ':requested_page' should be specified. Using ':offset'.");
                end
            end

            if((offset < 0))
                offset = requested_page_default * limit;
            end

            #Recreate the conditions array
            temp = Array.new();
            temp[0] = conditions_query;
            conditions_args.each{|arg|
                                temp << arg;
                                };

            #take all of the newly defined arguments and place them into a
            #temporary hash which can be merged with the other arguments.
            args_temp = Hash.new();
            args_temp.store(:conditions, temp);
            args_temp.store(:order, order_query);
            args_temp.store(:limit, limit);
            args_temp.store(:offset, offset);

            #the merge step
            query_args = self.merge_options_to_args(args, args_temp);

            #$log.info("The query_args are: #{query_args.inspect}")

            forum_results = super(*query_args);

            if((subscription_results != nil) && (!subscription_results.empty?()) && (!forum_results.empty?()))
                if((subscription_results.at(0) != nil) && (forum_results.at(0) != nil))
                    subscription_result_size = subscription_results.at(0).total_rows;
                    forum_result_size = forum_results.at(0).total_rows;
                    result_size = subscription_result_size + forum_result_size;
                end

                subscription_results.merge(forum_results);

                if(result_size != nil)
                    subscription_results.each{ |forum|
                                            forum.total_rows = result_size;
                                            };
                end

                return subscription_results;
            else
                return forum_results;
            end
		end

		#This function will reconstruct an appropriate args array to
		#be sent into a Storable find() call. It first seperates the
		#existing args into single elements and hashed elements.
		#
		#Since all of the options being sent in are hashed elements
		#we merge the existing hashed arguments with the new hashed
		#arguments from the custom forum options.
		#
		#We then attach the hashed elements onto the end of the
		#new args array and return said array.
		def self.merge_options_to_args(args, options)
            new_args = Array.new();
            new_hash = Hash.new();


            args.each{|arg|
                        if(arg.is_a?(Hash))
                            if(!arg.empty?)
                                arg.each{|key, internal_arg|
                                        new_hash.store(key, internal_arg);
                                        };
                            end
                        else
                            new_args << arg;
                        end
                        };

            #take new hashed arguments and insert them in the argument hash
            options.each{|key, arg|
                        if(arg != nil)
                            new_hash.store(key, arg);
                        end
                        };
            new_args << new_hash;

            return new_args;
		end

		#This function will try to find the specified option
		#in the array of arguments sent in. If there is an
		#embedded hash table it will search through it as well.
		#If the item is found in the hash table it will return
		#the value, if it is found in the array it will return
		#true. If it is not found in either, nil is returned.
		def self.find_option_from_args(option, args)
            args.each {|arg|
                        if(arg.is_a?(Hash))
                            arg.each{|key, internal_arg|
                                    if(key == option)
                                        return internal_arg;
                                    end
                                    };
                        else
                            if(arg == option)
                                return true;
                            end
                        end
                        };
            return nil;
		end

		#This function will attempt to remove the specified
		#option from the provided args. It will search through
		#the array and in any hashes found in the args (there
		#should generally only be one, and it only searches one
		#level into the hash, it will not perform a deep search).
		#
		#There is no return value from this function.
		def self.remove_option_from_args!(option, args)
		  args.each{|arg|
		          if(arg.is_a?(Hash))
		              arg.each{|key, internal_arg|
		                      if(key == option)
		                          arg.delete(option);
		                      end
		                      };
		          elsif(arg == option)
		              args.delete(option);
		          end
	              };
		end

		#pulls database query options from the arguments
		def self.extract_forum_options_from_args(args)
			options = Hash.new;
			args.each {|arg|
			    #$log.info("My value is: #{arg}");
				if (arg.is_a?(Symbol))
				     #$log.info("The outside argument is #{arg} ? #{:options}");
				    if(arg == :order)
				        #$log.info("The argument is #{arg}");
				        options << arg;
				        args.delete(arg);
				    elsif(arg == :view_private)
				        options.store(:view_private, true);
				        args.delete(arg);
				    elsif(arg == :subscriptions_only)
				        options.store(:subscriptions_only, true);
				        args.delete(arg);
				    end
				elsif(arg.is_a?(Hash))
				    #$log.info("I've found a hash");
				    arg.each{|key, internal_arg|
				            #$log.info("The key is: #{key} internal arg is: #{internal_arg.inspect}")
                            if(key.is_a?(Symbol))
                                #$log.info("I've found a symbol");
                                if(key == :view_private)
                                    if(internal_arg)
                                        options.store(:view_private, internal_arg);
                                    end
                                    arg.delete(:view_private);
                                elsif(key == :order)
                                    if(internal_arg.is_a?(Symbol))
                                        options.store(:order, internal_arg);
                                        arg.delete(:order);
                                    end
                                elsif(key == :result_size)
                                    options.store(:result_size, internal_arg);
                                    arg.delete(:result_size);
                                elsif(key == :requested_page)
                                    options.store(:requested_page, internal_arg);
                                    arg.delete(:requested_page);
                                elsif(key == :filter)
                                    options.store(:filter, internal_arg);
                                    arg.delete(:filter);
                                elsif(key == :exclude)
                                    options.store(:exclude, internal_arg);
                                    arg.delete(:exclude);
                                elsif(key == :category)
                                    options.store(:category_id, internal_arg);
                                    arg.delete(:category);
                                elsif(key == :subscriptions_only)
                                    options.store(:subscriptions_only, internal_arg);
                                    arg.delete(:subscriptions_only);
                                end
                            end
				            };
				end
			};

			separated_args = Hash.new();
			separated_args.store(:storable_args, args);
			separated_args.store(:forum_args, options);

			return separated_args;
		end
	end

	class ForumSubscription < Storable
		init_storable(:usersdb, "foruminvite");

		relation_singular :forum, :forumid, Forum;

        #TODO: Investigate if full_categoryid is really needed since you have access to
        #both fields on object.

		# Returns [userid, categoryid] if a userdefined category, or just categoryid
		# otherwise.
#		def full_categoryid()
#			if (userdefined)
#				return [userid, categoryid];
#			else
#				return categoryid;
#			end
#		end

        #This is functioning as a relation, but depending on whether the subscription
        #is in a user defined category or not it maps to different Storable objects.
		def category()
			if (userdefined)
				UserCategory.find(:promise, :first, :conditions => ["userid= ? AND id = ?", userid, categoryid]);
			else
				SiteCategory.find(:promise, :first, categoryid);
			end
		end

        #This function will provide every subscription which a user has. If
        #the user is not logged in we will return an empty OrderedMap.
		def ForumSubscription.all()
			if (PageRequest.current && PageRequest.current.session)
				return find(PageRequest.current.session.userid, :total_rows);
			else
				return OrderedMap.new();
			end
		end
	end
end
