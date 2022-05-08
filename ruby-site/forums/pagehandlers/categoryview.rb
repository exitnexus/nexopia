lib_require :Core, 'pagehandler'
lib_require :Forums, 'category', 'forum', 'thread'

module Forum
	class ForumCategoryView < PageHandler
		declare_handlers("forums") {
			area :Public
			access_level :Any

			handle :GetRequest, :categories, 'categories'
		}

		def categories()
			puts("<h1>Categories</h1><ul>");
			subs = Subscription.all;
			Category.list(nil, true).each {|cat|
				opts = {:pagecount => 5, :subscriptions => subs, :orderby => :mostrecent, :total => 0};

				forums = cat.forums(opts);

				puts "<li>#{cat.full_categoryid},#{CGI::escapeHTML(cat.name)} (Total: #{opts[:total]})</li>";
				puts "<ul>";
				forums.each {|forum|
					puts "<li>#{forum.id},#{CGI::escapeHTML(forum.name)}, #{forum.threads}</li>";
					puts "<ul>";
					forum.announcements.merge(forum.threadsObj(0, 5, [])).each {|thread|
						puts "<li>#{thread.id},#{CGI::escapeHTML(thread.title)} (announcement: #{thread.announcement})</li>";
						puts "<ul>";
						thread.posts_list(0, 5, []).each {|post|
							puts "<li>#{post.id},#{CGI::escapeHTML(post.msg)}</li>";
						}
						puts "</ul>";
					}
					puts "</ul>";
				}
				puts("</ul>");
			}
			puts("</ul>");
			puts("<h1>Subscriptions</h1>");
			puts("<ul>");
			subs.each {|sub|
				puts "<li>#{CGI::escapeHTML(sub.forum.name)}</li>";
				puts "<li>#{sub.forum.categoryid}</li>";
				puts "<li>#{sub.categoryid}</li>";
			}
			puts("</ul>");
		end
	end
end
