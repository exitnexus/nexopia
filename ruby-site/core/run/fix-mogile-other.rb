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

def store_file(serverid, klass, mog_path, fname)
	# ignore this if it is already in mogile
	print("stat\t#{klass}\t#{serverid}\t#{mog_path}\t")
	if ($mog.get_paths(mog_path))
		puts("OK\tAlready done")
		return true
	end
	
	if (!File.file?(fname))
		puts("FAIL\tNot on disk (#{fname}) or mogile")
		return false
	end
	
	begin
		$mog.store_file(mog_path, klass, fname)
	rescue
		puts("FAIL\tError pushing to mogile: #{$!}")
		return false
	end
	puts("OK\tPushed to mogile.")
	return true
end

def store_user_file(serverid, file)
	store_file(serverid, 'uploads', 
		"8/#{file['userid']}#{file['path']}", 
		"#{$site.config.uploads_dir}/#{file['userid'].to_i/1000}/#{file['userid']}#{file['path']}")
end

def store_source_pic(serverid, sourcepic)
	store_file(serverid, 'source',
		"6/#{sourcepic['userid']}/#{sourcepic['id']}",
		"#{$site.config.source_pic_dir}/#{sourcepic['userid'].to_i/1000}/#{sourcepic['userid']}/#{sourcepic['id']}.jpg")
end
def store_gallery_pic(serverid, gallerypic)
	ok_normal = store_file(serverid, 'gallery',
		"3/#{gallerypic['userid']}/#{gallerypic['id']}",
		"#{$site.config.gallery_dir}/#{gallerypic['userid'].to_i/1000}/#{gallerypic['userid']}/#{gallerypic['id']}.jpg")
	ok_full = store_file(serverid, 'galleryfull',
		"4/#{gallerypic['userid']}/#{gallerypic['id']}",
		"#{$site.config.gallery_full_dir}/#{gallerypic['userid'].to_i/1000}/#{gallerypic['userid']}/#{gallerypic['id']}.jpg")
	ok = ok_normal || ok_full
	puts("stat\tgallery_summary\t#{serverid}\t3||4/#{gallerypic['userid']}/#{gallerypic['id']}\t" + (ok ? "OK\tIn Mogile" : "FAIL\tNeither gallery or galleryfull are in mogile."))
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
					res = user_db.query("SELECT * FROM userfileslayout WHERE userid IN # AND type = 'file'", userids)
					res.each {|file|
						store_user_file(servid, file)
					}
					res = user_db.query("SELECT * FROM sourcepics WHERE userid IN #", userids)
					res.each {|source|
						store_source_pic(servid, source)
					}
					res = user_db.query("SELECT * FROM gallerypics WHERE userid IN #", userids)
					res.each {|gallery|
						store_gallery_pic(servid, gallery)
					}
				}
			rescue
				puts $!
				$!.backtrace.each{|line| puts line }
			end
		}
	}
}
