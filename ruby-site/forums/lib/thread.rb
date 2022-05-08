lib_require :Core,  'storable/storable', 'privilege'
lib_require :Forums, 'post'
lib_want :Observations, 'observable'

module Forum
	class ForumRead < Storable
    	init_storable(:usersdb, "forumread");


    	# Holds subscribed info

    	# Columns 'time' and 'readtime'
    	#   Listing the forums inside a category
    	#     The title of the forums changes color based on 'time' (and 'time' from forumupdated)
    	#   Listing the topics inside a Forum
    	#     The title of the topic changes color based on 'readtime' (and 'readalltime' from forumupdated)
    	#
    	#
    	#                      View Topic | Create Topic | Reply Topic
    	#   Updating     time      x              x             x
    	#   Updating readtime      x              x

    	def set_subscribe
            subscribe = true
            self.store
    	end

    	def set_unsubscribe
            subscribe = false
            self.store
    	end
	end

	class ForumUpdated < Storable
    	init_storable(:usersdb, "forumupdated");

    	# Columns 'time' and 'readalltime'
    	#   Listing the forums inside a category
    	#     The title of the forums changes color based on 'time' (and 'time' from forumread)
    	#   Listing the topics inside a Forum
    	#     The title of the topic changes color based on 'readalltime' (and 'readtime' from forumread)
    	#
    	#                         View Forum | Clicking 'Mark All as Read'
    	#   Updating        time      x
    	#   Updating readalltime                             x
	end

	class Thread < Storable
        attr_accessor :author_name, :last_author_name, :time_readable, :read_level, :subscribed;

		init_storable(:forumdb, "forumthreads");

		relation_singular :forum, :forumid, Forum;
		relation_paged :posts_list, [:forumid, :id], Post, :order => 'id ASC';

	    register_selection(:thread_exist, :forumid, :id);

		acts_as_uri(:description => :title, :uri_spec => ['forums',:forumid, :id])

		def owner
			return User.get_by_id(@authorid);
		end

		if (site_module_loaded? :Observations)
			include Observations::Observable
			observable_event :create, proc{"#{owner.link} created a Thread entitled '#{@title}.'"}
		end
		
		def after_load()
            author = User.get_by_id(authorid);
            lastauthor = User.get_by_id(lastauthorid);

            @author_name = (author != nil)? author.username : nil;
            @last_author_name = (lastauthor != nil) ? lastauthor.username : nil;
            @time_readable = Time.at(time).strftime("%b %d, %y"); # %I:%M %p
            @read_level = :new_thread; # See get_read_level
    	end

        # Sets the real @read_level only when it's going to be used
        def get_read_level(user)
            was_read = (user == nil)? nil : ForumRead.find(:first, :conditions => ["userid = ? AND threadid = ?", user.userid, id]);

            if (was_read == nil)
                @read_level = :new_thread;
            elsif (was_read.posts == (posts+1)) # posts counts only replyes, does not count the first post
                @read_level = :unchanged;
            else
                @read_level = :new_replies;
            end
        end

        def posts
        	return Post.find(:all, :conditions => ["threadid = ?", self.id]);
        end

		def Thread.get_updated()
			results = $site.dbs[:forumdb].query("SELECT forumid, id FROM forumthreads ORDER BY RAND() LIMIT 1");

			results.each{ | line |
				return Thread.find(:first, line['forumid'], line['id']);
			}
			return nil;
		end
	end
end
