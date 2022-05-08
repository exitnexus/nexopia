MSG_TITLE = %Q{A Message to the Nexopia Community}
MSG_BODY  = %Q{You've probably noticed that Nexopia has been going through some pretty big changes lately and we know you're wondering why. Well, the site's come a long way since it began in Timo's basement all those years ago and, as our community grows larger, we need to develop a whole new programming framework so that the site can support the amount of stuff people are doing on it every day. Along with that come slight changes to how the site needs to operate which is why we're making tweaks to the profiles and galleries.

We know that change can be a pain at first and that learning to use new things can seem like a chore. Many Nexopia members were upset when we had to move platforms from EnterNexus to Nexopia a few years ago but, in the end, it gave us more freedom to improve Nexopia's performance and make it what it is today. This time, we’ve used a lot of the suggestions you’ve made over the years to make these improvements and we're confident that, with your continued help, our newest upgrade will be just as successful.

We know that the features we released last week created slow load times and made your experience on Nexopia frustrating. We just wanted to let you know that we've been listening to your concerns and are using your feedback to help get the site back on track. We had over 50,000 members tell us in the polls that they were unhappy with our new "profile picture slider," so we've provided you the option to show your pictures in a "classic view" that's more in line with what you're used to on the old Nexopia. We're hoping to continue down this path and tweak the site based on your continued suggestions and support. Our first priority is getting the speed back up (YAY!) but we're also working on shaping other features, like the friends block, galleries, and profile skinning system into something more enjoyable for you.

Another thing people have been asking is why can't we just go back to the old Nexopia or at least offer you the choice between using the new and old site. We know that that seems like a good solution, but it's just not technically possible. To offer everyone both versions would be like running two websites at once and our team is just way too small to be able to handle that amount of work. Going back to the old one means that we won't be able to keep growing or provide you with any more cool features in the future. What we can to do though, is keep using your suggestions to find ways to make the site something we're all happy with. So please, keep giving us your thoughts in the [url=http://www.nexopia.com/forumthreads.php?fid=4]Suggestions[/url] and [url=http://www.nexopia.com/forumthreads.php?fid=35]Support[/url] forums. We're always listening.

Thanks again! 
PS. Don’t forget, if you need help using the new features, check out the [url=http://www.nexopia.com/wiki/UserGuides/Profiles]Profile Userguide[/url] and [url=http://www.nexopia.com/wiki/UserGuides/Gallery]Gallery Userguide[/url].

-- The Nex Team}

lib_require :Core, 'users/user', 'pagerequest'
lib_require :Messages, "message"

lib_require :Core, 'benchmark', 'array', 'rangelist'

group_size  = ENV['GROUP_SIZE'] || 100 # get this many users at a time
db_list     = ENV['DB_LIST'] && ENV['DB_LIST'].range_list # using these databases

group_size = group_size.to_i

db_hash = $site.dbs[:usersdb].dbs
db_list ||= db_hash.keys.sort {|a,b|
	if((a % 2) == (b % 2))
		(a <=> b)
	else
		(a % 2) <=> (b % 2)
	end
}

db_list.each {|servid|
	user_db = db_hash[servid]

	puts "Starting on server #{servid}"

	userid = 0
	iter = 0

	loop {
		iter += 1
		userids = []

		res = user_db.query("SELECT userid FROM users WHERE userid > ? ORDER BY userid LIMIT #", userid, group_size)
		res.each{|row| userids << row['userid'].to_i }

		break if(userids.empty?)

		userid = userids.last

		benchmark("Server: #{servid}, Loop #{iter}: Userid range #{userids.first}-#{userids.last}", 0 - group_size){
			$site.cache.use_context({}) {
				users = User.find(*userids).compact
				
				users.each {|user|
					begin
						print("#{user.userid}:")
						message = Message.new;
						message.sender_name = "Nexopia";
						message.receiver = user;
						message.subject = MSG_TITLE;
						message.text = MSG_BODY;
						message.send();
						puts("OK::")
					rescue
						puts("FAIL:#{$!}:#{$!.backtrace}")
					end
				}
			}
		}
	}
}
