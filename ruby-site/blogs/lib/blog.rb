
lib_require :Core, "storable/storable";
lib_require :Core, "storable/user_content"
lib_require :Core, "users/user";
lib_require :Friends, "friend";
lib_want :Observations, "observable";

# :all :first
# :limit => 10
# :promise
# :group => "userid"
# :order => "userid ASC"
# :conditions => "userid != 42"

class Blog < Storable
	attr_reader :user, :comments
	acts_as_uri(:description => :title, :uri_spec => ['blogs',:title])

	if (site_module_loaded? :Observations)
		include Observations::Observable
		OBSERVABLE_NAME = "Blog Entries";

		observable_event :create, proc{"#{user.link} created a blog entry entitled #{self.link}"}
		observable_event :edit, proc{"#{user.link} edited #{user.possessive_pronoun} blog entry entitled #{self.link}"}
	end
	
    Public  = 1
    LoggedIn  = 2
    Friends = 3
    Private = 4
    
	init_storable(:usersdb, "blog");

	user_content :msg
	
    def Blog.allUsers
        Blog.find(:all, :group => "userid", :order => "userid ASC")
    end

    def Blog.someUsers(times=0)
        Blog.find(:all, :limit => times, :group => "userid", :order => "userid ASC")
    end

    def Blog.allEntries(id)
        Blog.find(:all, :conditions => "userid = #{id}", :order => "userid ASC")
    end

	def after_load
        case @scope
            when Public then  @scope = "Public"
            when LoggedIn then  @scope = "Logged-in Only"
            when Friends then @scope = "Friends Only"
            when Private then @scope = "Private"
        end

		#@user     = User.find(:first, :conditions => ["userid = ?", userid])
		#@comments = Blogcomments.find(:all, :blogid, self.id, :group => "bloguserid")
	end

	def Blog.random_blog()
		result = $site.dbs[:usersdb].query("SELECT id FROM blog ORDER BY RAND() LIMIT 2");

		result.each {| line|
			blog = Blog.find(:first, :promise, :conditions => ["id = #", line['id']]);
			if (!blog.nil? and !blog.user.nil?)
				return blog
			end
		}


		return nil;

	end

	def Blog.recent(user, viewer)
		level = Public
		if (!viewer.anonymous?)
			level = LoggedIn
		end
		if (user.friend? viewer)
			level = Friends
		end
		if (user == viewer)
			level = Private
		end
		return self.find(:first, :conditions => ["userid = # && scope <= ?", user.userid, level], :order => ["`time` DESC"])
	end
end


