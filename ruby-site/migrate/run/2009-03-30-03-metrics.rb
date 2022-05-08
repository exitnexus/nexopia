# Add new blog metrics

# Grab typeid
blog_typeid = nil
rows = $site.dbs[:db].query("SELECT typeid FROM typeid
 	WHERE typename = 'Metrics::CategoryBlogs'")
rows.each { |row|
	blog_typeid = row['typeid']
}
raise "Unable to determine typeid" if blog_typeid.nil?

# Have to copy descriptions here, we can't include the metrics module for
# migrations
=begin
BLOG_POSTS_BY_TYPE           = 22
VOTES_ON_BATTLES             = 23
VOTES_ON_POLLS               = 24
YOUTUBE_SEARCHES             = 25
UPLOADING_THROUGH_UPLOADER   = 26
COMMENTS_BY_TYPE             = 27
BATTLES_BY_TYPE              = 28
BLOG_POLLS                   = 29
ABUSE_REPORTS                = 30
=end
metrics = [
	 { :metricid => 22,
	   :usertype => true,
	   :description => '# of Posts (by type)' },
	 { :metricid => 23,
	   :usertype => true,
	   :description => '# of Votes on Battles' },
	 { :metricid => 24,
	   :usertype => true,
	   :description => '# of Votes on Polls' },
	 { :metricid => 25,
	   :usertype => true,
	   :description => '# using YouTube Search' },
	 { :metricid => 26,
	   :usertype => true,
	   :description => '# Uploading Through Blog Uploader' },
	 { :metricid => 27,
	   :usertype => true,
	   :description => '# of Comments (by type)' },
	 { :metricid => 28,
	   :usertype => true,
	   :description => '# of Battle Blogs' },
	 { :metricid => 29,
	   :usertype => true,
	   :description => '# of Poll Blogs' },
	 { :metricid => 30,
	   :usertype => false,
	   :description => 'Abuse Reports' }
]

metrics.each { |metric|
	if (metric[:usertype])
		[ 'all', 'plus', 'active' ].each { |usertype|
			$site.dbs[:masterdb].query("INSERT INTO metriclookup
				(categoryid, metric, usertype, description)
				VALUES(?, ?, ?, ?)",
				blog_typeid, metric[:metricid], usertype, metric[:description])
		}
	else
		$site.dbs[:masterdb].query("INSERT INTO metriclookup
			(categoryid, metric, usertype, description)
			VALUES(?, ?, ?, ?)",
			blog_typeid, metric[:metricid], 'na', metric[:description])
	end
}
