lib_require :Blogs, "blog_comment";
lib_require :Blogs, "blog_navigation";
lib_require :Blogs, "blog_visibility";
lib_require :Blogs, 'blog_type', 'photo_blog', 'video_blog', 'poll_blog', 'battle_blog'

lib_require :Core, "slugly";
lib_require :Core, "storable/storable";
lib_require :Core, "storable/user_content"
lib_require :Core, "users/user";


# :all :first
# :limit => 10
# :promise
# :group => "userid"
# :order => "userid ASC"
# :conditions => "userid != 42"
module Blogs
	class BlogPost < Cacheable
		extend TypeID;

		init_storable(:usersdb, "blog");

		relation :singular, :db_user, [:userid], User;
		relation :count, :comments_count, [:userid, :id], BlogComment, {:index => :ruby_index, :conditions => "deleted = 'n'", :extra_columns => :deleted};
		relation :count, :total_comments_count, [:userid, :id], BlogComment, {:index => :ruby_index};
		relation :multi, :comments, [:userid, :id], BlogComment, {:index => :ruby_index}

		relation :singular, :photo_content, [:userid, :id], PhotoBlog
		relation :singular, :video_content, [:userid, :id], VideoBlog
		relation :singular, :poll_content, [:userid, :id], PollBlog
		relation :singular, :battle_content, [:userid, :id], BattleBlog

		register_selection(:minimal, :userid, :id, :time, :visibility);

		set_enums(
		:visibility => BlogVisibility.list
		);

		POSTS_PER_PAGE = 25;

		user_content :msg

		make_slugable :title

		attr_accessor :root_comments;

		def initialize(*args)
			super(*args);
			self.root_comments = Array.new();
		end
	
		def self.build(user, params, post=nil)
			typeid = params["typeid", Integer, 0]

			post_time = Time.now

			post_content = params["blog_post_content", String, ""]
			post_title = params["blog_post_title", String, ""]
			post_visibility = params['blog_post_visibility', Integer, 4]
			post_comments = params['blog_post_comments', Boolean, false]
			reset_timestamp = params['reset_timestamp', Boolean, false]


			#For some reason inverse_visibility_list is keyed by numbers as strings, eg. "4"
			#rather than just integers.  to_s the integer we received to maintain this convention.
			if(BlogVisibility.instance.inverse_visibility_list[post_visibility.to_s()].nil?())
				post_visibility = user.blog_profile.defaultvisibility
			end

			if (post.nil?)
				post = BlogPost.new()
				post.userid = user.userid
				post.id = BlogPost.get_seq_id(user.userid)
				reset_timestamp = true
			end

			if (reset_timestamp)
				post.time = post_time.to_i
				post.month = post_time.month
				post.year = post_time.year
			end
			
			post.title = post_title
			post.msg = post_content
			post.allowcomments = post_comments
			post.visibility = post_visibility
			post.typeid = typeid

			#find the type of blog post
			blog_class = TypeID.get_class(typeid)
			if (!blog_class.nil? && blog_class.ancestors.include?(BlogType))
				extra_content = blog_class.build(post, user, params)
			end
			
			return [post, extra_content]
		end

		def self.preview(user, params)
			post, content = self.build(user, params)
			
			post_eigen = class << post; self; end
			
			post_eigen.send(:define_method, :extra_content) {
				content
			}
			return post
		end

		#This should now be used to create blogs, the params (a typesafe hash) define the object
		#to be created and based on the typeid param it chooses the specific
		#type of blog to instantiate.
		def self.build!(user, params, post=nil)
			post, extra_content = self.build(user, params, post)
		
			post.store()
			extra_content.store() unless extra_content.nil?

			return post
		end

		def blog_type
			if ( extra_content.nil?)
				return :freeform
			else
				return extra_content.blog_type
			end
		end

		def extra_content
			case self.typeid
			when Blogs::PhotoBlog.typeid
				return self.photo_content
			when Blogs::VideoBlog.typeid
				return self.video_content
			when Blogs::PollBlog.typeid
				return self.poll_content
			when Blogs::BattleBlog.typeid
				return self.battle_content
			else
				return nil
			end
		end

		# We need to wrap the user relation because the user of the blog post might be a deleted user. If so
		#  the relation will return nil, at that point we will try to find the deleted user. If we can't we'll
		#  just use a placeholder.
		def user
			if (db_user.nil?)
				$log.info("BlogPost: We got a nil user relation", :debug)
				return DeletedUser.find(:first, self.userid) || DeletedUser.new()
			end
			return db_user;
		end

		def <=>(anOther)
			if(!anOther.kind_of?(BlogPost))
				raise(ArgumentError.new("#{anOther.class} is not compatible with BlogPost"));
			end

			if(anOther.time > self.time)
				return 1;
			elsif(anOther.time < self.time)
				return -1;
			else
				return 0;
			end
		end

		def visibility_display()
			return BlogVisibility.blog_visibility_name(self.visibility);
		end

		def uri_info
			return [self.title, url / :users / self.user.username / :blog / self.id]
		end

		def self.allUsers
			BlogPost.find(:all, :group => "userid", :order => "userid ASC")
		end

		def self.someUsers(times=0)
			BlogPost.find(:all, :limit => times, :group => "userid", :order => "userid ASC")
		end

		def self.allEntries(id)
			BlogPost.find(:all, :conditions => "userid = #{id}", :order => "userid ASC")
		end

		def self.random_blog()
			result = $site.dbs[:usersdb].query("SELECT id FROM blog ORDER BY RAND() LIMIT 2");

			result.each {| line|
				blog = BlogPost.find(:first, :promise, :conditions => ["id = #", line['id']]);
				if (!blog.nil? and !blog.user.nil?)
					return blog
				end
			}

			return nil;
		end

		def self.recent(blog_user, viewing_user)
			visibility_level = BlogVisibility.determine_visibility_level(blog_user, viewing_user)

			return self.find(:first, :conditions => ["`userid` = # && `visibility` >= ?", blog_user.userid, visibility_level], :order => ["`time` DESC"], :limit => 1);
		end

		def after_create
			$site.memcache.delete("blog-post-date-map-#{self.userid}")
		end

		def after_load
			@time_before_update = self.time
			@visibility_before_update = self.visibility
		end
		def after_update
			if ((!@time_before_update.nil? && self.time > @time_before_update) || 
				(!@visibility_before_update.nil? && self.visibility != @visibility_before_update))
				$site.memcache.delete("blog-post-date-map-#{self.userid}")
			end
			@time_before_update = nil
			@visibility_before_update = nil
		end		

		def before_delete
			self.extra_content.delete unless self.extra_content.nil?
			super
		end
		
		def after_delete
			related_navigations = BlogNavigation.find(:all, :scan, :conditions => ["bloguserid = # && postid = ?",self.userid, self.id])
			related_navigations.each { |navigation|
				navigation.delete
			}

			$site.memcache.delete("blog-post-date-map-#{self.userid}")
			super
		end

		postchain_method(:after_create) { |val|
			#Archive::save(authorid, id, Archive::ARCHIVE_COMMENT, Archive::ARCHIVE_VISIBILITY_USER, user.userid, 0, "", nmsg);
			#val
		}
	end

	class BlogComment < Cacheable
		relation :singular, :post, [:bloguserid, :blogid], BlogPost;
	end

	class BlogPostResult
		attr_accessor :success, :error_list, :title, :content, :allow_comments, :reset_timestamp, :visibility;

		def initialize()
			@error_list = [];
			@success = true;
		end

		def error_msg()
			msg_prefix = "You need to provide ";
			msg_suffix = " for your post.";

			msg = msg_prefix;
			if(error_list.length == 1)
				msg = msg + error_list.first;
			else
				i = 0
				while(i < error_list.length)
					error = error_list[i];

					msg = msg + error;

					if(error_list.length == 1 || error_list.last == error)
						#case to skip
					elsif(error_list[i+1] == error_list.last)
						msg = msg + " and ";
					else
						msg = msg + ", ";
					end
					i = i + 1;
				end
			end

			msg = msg + msg_suffix;

			return msg;
		end
	end
end


