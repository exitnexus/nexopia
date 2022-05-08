lib_require :Forums, 'category', 'forum';

module Forum

	class Moderation < PageHandler
        
		declare_handlers("forums"){
			area :Public;
			access_level :Any;

			page    :GetRequest, :Full, :manage, "manage", remain;
		}

		def manage(remain)
			command = nil;
			if (remain.length > 0)
				command = remain[0];
			end
			if (remain.length > 1)
				category_id = remain[1].to_i;
			end
				
			if (command != nil)
				case command
				when "movedown"
					resync_categories();
					cat = SiteCategory.find(:first, :conditions => ["`id` = ?", category_id]);
					res = SiteCategory.find(:first, :conditions => ["`priority` = (SELECT MIN( `priority` ) FROM forumcats WHERE `priority` > ?)", cat.priority]);
					if (cat && res)
						res.priority, cat.priority = cat.priority, res.priority;
						cat.store;
						res.store;
					end
				when "moveup"
					resync_categories();
					cat = SiteCategory.find(:first, :conditions => ["`id` = ?", category_id]);
					res = SiteCategory.find(:first, :conditions => ["`priority` = (SELECT MAX( `priority` ) FROM forumcats WHERE `priority` < ?)", cat.priority]);
					if (cat && res)
						res.priority, cat.priority = cat.priority, res.priority;
						cat.store;
						res.store;
					end
				when "delete"
					cat = SiteCategory.find(:first, :conditions => ["`id` = ?", category_id]);
					cat.delete();
					resync_categories();
				when "create"
					resync_categories();
					res = SiteCategory.find(:first, :conditions => ["`priority` = (SELECT MAX( `priority` ) FROM forumcats)"]);

					cat = SiteCategory.new
					cat.name = params["catname", String]
					cat.official = (params["official", String] == "y");
					cat.priority = res.priority + 1;
					cat.store();
				end
			end
	
			t = Template.instance("forums", "manage");
			
			t.categories = Array.new();
			categories = Category.list().sort{|a,b|
				a.priority <=> b.priority;
			};
			categories.each(){|category|
				t.categories << [
					category, category.forums.collect{|forum|
						[forum.id, forum.name.gsub('&lt;','<')]
					}
				];
			}
	
			puts t.display();
		end
	    
		# Re-number all the category priorities in the database from 1 to
		# the number of categories.
		def resync_categories
			categories = Category.list().sort{|a,b|
				a.priority <=> b.priority;
			};
			categories.each_with_index{|cat, index|
				cat.priority = index + 1;
				cat.store();
			}
		end
		
	end

end