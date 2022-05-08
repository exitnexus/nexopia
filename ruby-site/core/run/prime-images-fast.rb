lib_require :Core, 'users/user'
lib_want :Userpics, 'pics'

lib_require :Core, 'benchmark', 'array', 'rangelist'
lib_require :Core, 'pagerequest','pagehandler'

require 'RMagick'

group_size  = ENV['GROUP_SIZE'] || 100 # get this many users at a time
concurrency = ENV['CONCURRENCY'] || 10  # run this many processes
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

$to_mog = $from_mog = MogileFS::MogileFS.new(:hosts => $site.config.mogilefs_hosts, :domain => 'nexopia.com')


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


db_list.each {|servid|
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

		fork{
			$site.close_dbs()
			benchmark("Server: #{servid}, Loop #{iter}: Userid range #{userids.first}-#{userids.last}", 0 - group_size){
				begin
					$site.cache.use_context({}) {
						users = User.find(*userids)
						users.compact!

						urls = []

						users.each{|user| user.pic_slots }

						users.each{|user|
							user.pic_slots.each {|pic|
								if(!pic.kind_of?(EmptyPicSlot))
									#don't redo this image if it was already done
									landscape = Gallery::GalleryLandscapeFileType.new(pic.userid, pic.id)
									
									next if landscape.stored?

									#grab the source file
									source = Gallery::SourceFileType.new(pic.userid, pic.id)
									
									next unless source.stored?

									landscape.get_contents(StringIO.new)
									
									if(user.firstpic == pic.id)
										Gallery::GalleryLandscapeThumbFileType.new(pic.userid, pic.id).get_contents(StringIO.new)
										Gallery::GalleryLandscapeMiniFileType.new(pic.userid, pic.id).get_contents(StringIO.new)
									end
								end
							}
						}
					}
				rescue
					puts $!
					$!.backtrace.each{|line| puts line }
				end
			}
			exit
		}
		$children += 1
		wait_children(concurrency)
	}
	wait_children(0)
}
