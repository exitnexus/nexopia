lib_require :Core,  'storable/storable', 'privilege'

# TODO: Need to write a manual sort for resultsets (OrderedMaps) because we aren't guaranteed sort ordering due to db splits.
# Probably will want to do one for group by's too, oh and something to enforce limit clauses.

module Forum
	class Category < Storable
        attr_accessor :view_all, :forumsObj, :pageCount, :pageCountTotal, :forum_list, :page, :total_pages;

        def Category.list(category_id = nil)
            if(category_id == nil)
                site_categories = SiteCategory.find(:order => "priority ASC");
            else
                category = SiteCategory.find(:first, category_id);
                
                if(category != nil)
                    return category;
                end
            end
            
            if(PageRequest.current && PageRequest.current.session)
                if(category_id == nil)
                    user_categories = UserCategory.find(:conditions => ["userid = ?", PageRequest.current.session.userid], :order => "name ASC");
                else
                    category = UserCategory.find(:first, category_id, :conditions => ["userid = ?", PageRequest.current.session.userid]);
                    return category;
                end
            end
            
            if(site_categories != nil && user_categories != nil)
                return user_categories.merge(site_categories);
            elsif(user_categories != nil)
                return user_categories;
            elsif(site_categories != nil)
                return site_categories;
            else
                return OrderedMap.new();
            end
        end
        
#		# Lists the categories available (to the log
#		def Category.list(category_id = nil, only_user_categories = false)
#		
#			official = OrderedMap.new();
#            usercats = OrderedMap.new();
#            
#            if (! only_user_categories)
#                if (category_id == nil)
#        			official = SiteCategory.find(:order => 'priority ASC');
#        		else
#        			officialOne = SiteCategory.find(:first, category_id, :order => 'priority ASC');
#        			official[category_id] = officialOne
#        		end
#            end
#            
#			if (PageRequest.current && PageRequest.current.session)
#				userid = PageRequest.current.session.userid;
#
#                if (category_id == nil)
#                    userCategory = UserCategory.find(:conditions => ['userid = #', userid], :order => 'name ASC');
#    				usercats     = userCategory if (userCategory != nil)
#        		else
#            		userCategory = UserCategory.find(:first, :conditions => ['userid = # AND id = #', userid, category_id], :order => 'name ASC');
#    				usercats     << userCategory if (userCategory != nil)
#        		end
#			end
#
#			return usercats.merge(official)
#		end

		# returns true if the category is collapsed
		def is_collapsed
			# needs optimization, as right now it'll do one query per category.
			if (PageRequest.current && !PageRequest.current.session.anonymous?())
				return !Collapse.find(:first, PageRequest.current.session.user.userid, (!userid.nil?)? 'y':'n', id).nil?;
			else
				return false;
			end
		end

		def set_collapse(collapse = true)
			if (PageRequest.current && PageRequest.current.session)
				if (collapse)
					cobj = Collapse.new();
					cobj.userid = PageRequest.current.session.userid;
					cobj.userdefined = !userid.nil?;
					cobj.categoryid = id;
					cobj.store(:ignore);
				else
					cobj = Collapse.find(:first, PageRequest.current.session.userid, (!userid.nil?)?'y':'n', id);
					if (cobj)
						cobj.delete();
					end
				end
			end
		end
		
		def add_option_to_args!(args, option, value = nil)
            if(value == nil)
                args << option;
            else
                args.each{|arg|
                        if(arg.is_a?(Hash))
                            arg.store(option, value);
                            return;
                        end
                        };
            end 
        end
	end

	class CategoryCollapse < Storable
		init_storable(:usersdb, "forumcatcollapse");

			def full_categoryid()
				if (userdefined)
					return [userid, categoryid];
				else
					return categoryid;
				end
			end

			def category()
				if (userdefined)
					UserCategory.find(:promise, :first, userid, categoryid);
				else
					SiteCategory.find(:promise, :first, categoryid);
				end
			end
		end

	class SiteCategory < Category
		init_storable(:forummasterdb, "forumcats");

		def full_categoryid()
			return id;
		end

		def userid()
			return nil;
		end

#        # Returns a list of all forums excluding the ones the users is subscribed to.
#		def forums(options)		
#            list_remove = Array.new();
#
#			Subscription.all.each {|sub| list_remove << sub.forum.id }
#
#    		options[:notin] = *list_remove;
#
#			return Forum.findOptions(options, self.id);
#		end
#
        def forums(*args)
            self.add_option_to_args!(args, :category, self.id);
            return Forum.find(*args);
        end
	end

	class UserCategory < Category
		init_storable(:usersdb, "forumcats");

		def full_categoryid()
			return [userid, id];
		end

		def official()
			return false;
		end

		def forums(*args) # Equivalent to Forum.findOptions. Uses ruby code instead of mySql.
            # Retrieves the subscribed forums list
            subscriptions = ForumSubscription.find(:total_rows, :conditions => ["userid = ? AND categoryid = ?", PageRequest.current.session.userid, id ]);
            
            forum_list = Array.new();
            
            subscriptions.each{|sub|
                            forum_list << sub.forumid;
                            };
            
            $log.info("From UserCategory.forums the forum_list is: #{forum_list.inspect}")
            
            self.add_option_to_args!(args, forum_list);
            
            results = Forum.find(*args);
            
            return results;
            
#            # Retrieves the forum info
#			list_forums = OrderedMap.new();
#			list_subs.each{|sub|
#                if (options[:filter] == nil || sub.forum.name.downcase.include?(options[:filter].downcase) || sub.forum.description.downcase.include?(options[:filter].downcase) )
#                    list_forums << sub.forum;
#                end
#            }
#
#            # Enforces options
#			list_forums_official     = OrderedMap.new();
#			list_forums_not_official = OrderedMap.new();
#			
#			options[:pagenum]   = options[:pagenum]   || 0;
#			options[:pagecount] = options[:pagecount] || 20;
#			options[:orderby]   = options[:orderby]   || :mostrecent;
#
#            start  =   (options[:pagenum]      * options[:pagecount])
#            finish = [((options[:pagenum] + 1) * options[:pagecount]), list_forums.length].min
#
#            # Page Views and total_rows
#            ( start ... finish ).each {|index|
#                list_forums.at(index).total_rows = list_forums.length
#                
#                if (list_forums.at(index).official)
#                    list_forums_official << list_forums.at(index);
#                else
#                    list_forums_not_official << list_forums.at(index);
#                end                
#            } if (start < list_forums.length)
#
#            # Order By
#			case options[:orderby]
#                when :mostactive:                    
#                    list_forums_official     = sort(list_forums_official,     'posts', '<');
#                    list_forums_not_official = sort(list_forums_not_official, 'posts', '<');
#
#                    # posts DESC
#		      	when :mostrecent:
#                    list_forums_official     = sort(list_forums_official,     'time', '<');
#                    list_forums_not_official = sort(list_forums_not_official, 'time', '<');
#
#                    # time DESC";
#                when :alphabetic:
#                    list_forums_official     = sort(list_forums_official,     'name', '>');
#                    list_forums_not_official = sort(list_forums_not_official, 'name', '>');
#			end
#
#            return list_forums_official.merge(list_forums_not_official);
		end
		
		def sort(list_forums, criteria, order)
            list_forums_final = OrderedMap.new();
            
            list_forums.each {|forum|
                list_forums_aux   = list_forums_final;
                list_forums_final = OrderedMap.new();
                
                found_spot = false;
                
                list_forums_aux.each {|forum_aux|
                    if (found_spot)
                        list_forums_final << forum_aux;
                    else
                        if (eval "(forum.#{criteria} #{order} forum_aux.#{criteria})" )
                            list_forums_final << forum_aux;
                        else
                            found_spot = true;
                            list_forums_final << forum;
                            list_forums_final << forum_aux;
                        end
                    end
                }
                list_forums_final << forum if (list_forums_aux.empty?)
                list_forums_final << forum if ( (! list_forums_aux.empty?) && (! found_spot) )
            }
            
            return list_forums_final;
		end
	end
end
