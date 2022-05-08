lib_require :Core, 'users/user'
lib_want :Userpics, 'pics'

lib_require :Core, 'benchmark', 'rangelist'
lib_require :Core, 'pagerequest','pagehandler'

require 'uploader/pagehandlers/file_handler'

group_size  = ENV['GROUP_SIZE'] || 100 # get this many users at a time
db_list     = ENV['DB_LIST'] && ENV['DB_LIST'].range_list # using these databases

group_size = group_size.to_i

$mog = MogileFS::MogileFS.new(:hosts => $site.config.mogilefs_hosts, :domain => 'nexopia.com')

db_hash = $site.dbs[:usersdb].dbs
db_list ||= db_hash.keys.sort {|a,b|
	if((a % 2) == (b % 2))
		(a <=> b)
	else
		(a % 2) <=> (b % 2)
	end
}

def wait_children(numchildren)
	begin
		begin
			while(Process.wait(-1, Process::WNOHANG))
				$children -= 1;
			end
		end while($children >= numchildren && sleep(0.1))
	rescue SystemCallError
		$children = 0
	end	
end

puts("stat\t{type}\t{serverid}\t{mogpath}\t{OK|FAIL}\t{Reason}")

def store_pic(serverid, pic)
	# ignore this if it is already in mogile
	mog_path = "2/#{pic['userid']}/#{pic['id']}"
	print("stat\tuserpic\t#{serverid}\t#{mog_path}\t")
	mog_path = "2/#{pic['userid']}/#{pic['id']}"
	if ($mog.get_paths(mog_path))
		puts("OK\tAlready done")
		return
	end
	
	fname = "#{$site.config.user_pic_dir}/#{pic['userid'].to_i/1000}/#{pic['userid']}/#{pic['id']}.jpg"
	if (!File.file?(fname))
		puts("FAIL\tNot on disk (#{fname}) or mogile")
		return
	end
	
	begin
		$mog.store_file(mog_path, "userpics", fname)
	rescue
		puts("FAIL\tError pushing to mogile: #{$!}")
		return
	end
	puts("OK\tPushed to mogile.")						
end

db_list.each{|servid|
	user_db = db_hash[servid]

	$log.info "Starting on server #{servid}", :warning

	$children = 0;

	userid = 0
	iter = 0
	
	loop {
		iter += 1
		userids = []

		res = user_db.query("SELECT userid FROM usernames WHERE userid > ? ORDER BY userid LIMIT #", userid, group_size)
		res.each{|row| userids << row['userid'].to_i }

		break if(userids.empty?)

		userid = userids.last

		$site.close_dbs()
		benchmark("Server: #{servid}, Loop #{iter}: Userid range #{userids.first}-#{userids.last}", 0 - group_size){
			begin
				$site.cache.use_context({}) {
					res = user_db.query("SELECT * FROM pics WHERE userid IN #", userids)
					res.each {|pic|
						store_pic(servid, pic)
					}
				}
			rescue
				puts $!
				$!.backtrace.each{|line| puts line }
			end
		}
	}
}
