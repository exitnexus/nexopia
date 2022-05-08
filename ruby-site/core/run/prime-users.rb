lib_require :Core, 'users/user'
lib_want :Friends, 'friend'
lib_want :Comments, 'comment'
lib_want :Userpics, 'pics'
lib_want :Profile, 'profile_user'

lib_require :Core, 'benchmark', 'array', 'rangelist'

group_size  = ENV['GROUP_SIZE'] || 100 # get this many users at a time
concurrency = ENV['CONCURRENCY'] || 1  # run this many processes
db_list     = ENV['DB_LIST'] && ENV['DB_LIST'].range_list # using these databases


group_size = group_size.to_i
concurrency = concurrency.to_i


db_hash = $site.dbs[:usersdb].dbs
db_list ||= db_hash.keys.sort {|a,b|
	if((a % 2) == (b % 2))
		(a <=> b)
	else
		(a % 2) <=> (b % 2)
	end
}

db_list.each_fork(concurrency){|servid|
	user_db = db_hash[servid]

	puts "Starting on server #{servid}"

	userid = 0
	iter = 0
	
	loop {
		iter += 1
		userids = []

		res = user_db.query("SELECT userid FROM usernames WHERE userid > ? ORDER BY userid LIMIT #", userid, group_size)
		res.each{|row| userids << row['userid'].to_i }

		break if(userids.empty?)

		userid = userids.last

		benchmark("Server: #{servid}, Loop #{iter}: Userid range #{userids.first}-#{userids.last}", 0 - group_size){
			begin
				$site.cache.use_context({}) {
					users = User.find(*userids)

					users.compact!

					users.each {|user| user.username_obj }
					users.each {|user| user.username == 0 }
		
					users.each {|user| user.interests }
					users.each {|user| user.interests == 0 }
		
					if(site_module_loaded?(:Friends))
						users.each {|user| user.friends }
						users.each {|user| user.friends == 0 }
			
						users.each {|user| user.friends_of }
						users.each {|user| user.friends_of == 0 }
					end
		
					if(site_module_loaded?(:Comments))
						users.each {|user| user.first_five_comments }
						users.each {|user| user.first_five_comments == 0 }
			
						users.each {|user| user.comments_count }
						users.each {|user| user.comments_count == 0 }
					end
		
					if(site_module_loaded?(:UserPics))
						users.each {|user| user.pics }
						users.each {|user| user.pics == 0 }
			
						users.each {|user| user.internal_first_picture }
						users.each {|user| user.internal_first_picture == 0 }
					end
		
					#if(site_module_loaded?(:Gallery))
					#	users.each {|user| user.galleries }
					#	users.each {|user| user.galleries == 0 }
					#end
		
					if(site_module_loaded?(:Profile))
						users.each {|user| user.profile }
						users.each {|user| user.profile == 0 }
			
						users.each {|user| user.profile_blocks }
						users.each {|user| user.profile_blocks == 0 }
			
						users.each {|user| user.freeform_text_blocks }
						users.each {|user| user.freeform_text_blocks == 0 }
					end
				}
			rescue
				puts $!
				$!.backtrace.each{|line| puts line }
			end
		}
	}
}
