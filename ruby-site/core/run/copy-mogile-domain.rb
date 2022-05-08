lib_require :Core, 'users/user'

lib_require :Core, 'benchmark', 'rangelist'
lib_require :Core, 'pagerequest','pagehandler'

#lib_require :Gallery, "gallery_pic"
#lib_require :UserFiles, 'user_file_type'

lib_require :FileServing, "fast_mogilefs"

group_size  = ENV['GROUP_SIZE'] || 100 # get this many users at a time
db_list     = ENV['DB_LIST'] && ENV['DB_LIST'].range_list # using these databases

from_domain = ENV['FROM_DOMAIN'] || $site.config.mogilefs_domain
from_hosts = ENV['FROM_HOSTS'] || $site.config.mogilefs_hosts
if (from_hosts.kind_of? String)
	from_hosts = from_hosts.split(',')
end

group_size = group_size.to_i

if (from_domain == $site.config.mogilefs_domain && from_hosts == $site.config.mogilefs_hosts)
	raise "Can't copy from (#{from_hosts.join(',')}:#{from_domain}) a domain to (#{$site.config.mogilefs_hosts.join(',')}:#{$site.config.mogilefs_domain}) itself."
end

$mog_to = MogileFS::MogileFS.new(:hosts => $site.config.mogilefs_hosts, :domain => $site.config.mogilefs_domain)
$mog_from = MogileFS::MogileFS.new(:hosts => from_hosts, :domain => from_domain)

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

puts("stat\t{type}\t{serverid}\t{target_mogpath}\t{source_mogpath}\t{OK|FAIL}\t{Reason}")

class FileToProcess
	attr :serverid
	attr :klass
	attr :target_mog_path
	attr :source_mog_paths
	
	def initialize(serverid, klass, target_mog_path, source_mog_paths)
		@serverid = serverid
		@klass = klass
		@target_mog_path = target_mog_path
		@source_mog_paths = source_mog_paths
	end
	
	def source_mog_path
		@source_mog_paths.each {|source|
			data = $mog_from.get_file_data(source)
			if (data)
				return [source, StringIO.new(data)]
			end
		}
		return nil
	end
	
	# Steps to process a file:
	# - Figure out the target mog filename
	#  - Early exit if that file already exists in mog
	# - Figure out the possible source mog filenames and see if they exist
	#  - if they don't, output error
	#  - if they do, save to a tmpfile and write back to target mog 
	def process()
		print("stat\t#{klass}\t#{serverid}\t#{target_mog_path}\t")
		if ($mog_to.get_paths(target_mog_path))
			puts("(unknown)\tOK\tAlready copied.")
			return true
		end
		
		begin
			source = source_mog_path()
		rescue
			puts("(unknown)\tFAIL\tError while reading file: #{$!}")
			return false
		end
		if (!source)
			puts("(unknown)\tFAIL\tNo source available.")
			return false
		end
		source_path, source_file = source
		begin
			$mog_to.store_file(target_mog_path, 'source', source_file)
		rescue
			puts("#{source_path}\tFAIL\tError while writing file: #{$!}, #{$@}")
			return false
		end
		puts("#{source_path}\tOK\tCopied file.")
		return true
	end
end

module UserFiles
	class FileType
		extend TypeID
	end
end

class UserFile < FileToProcess
	TYPEID = UserFiles::FileType.typeid
	
	def initialize(serverid, userid, path)
		source_mog_paths = ["8/#{userid}#{path}"]
		target_mog_path = "#{TYPEID}/#{userid}#{path}"
		super(serverid, 'uploads', target_mog_path, source_mog_paths)
	end
end

module Gallery
	class SourceFileType
		extend TypeID
	end
end

class GalleryFile < FileToProcess
	TYPEID = Gallery::SourceFileType.typeid
	def initialize(serverid, userid, gallerypicid, sourceid)#, userpicid)
		source_mog_paths = []
		if (sourceid)
			source_mog_paths.push "6/#{userid}/#{sourceid}" # pull from sourcepics if there's an id for it
		end
#		if (userpicid)
#			source_mog_paths.push "2/#{userid}/#{userpicid}" # pull from userpics if there's an id for that (migrated userpic)
#		end
		source_mog_paths.push "4/#{userid}/#{gallerypicid}" # pull from galleryfull as a fallback
		source_mog_paths.push "3/#{userid}/#{gallerypicid}" # and as last resort, pull from the plain gallery picture.
		target_mog_path = "#{TYPEID}/#{userid}/#{gallerypicid}"
		super(serverid, 'source', target_mog_path, source_mog_paths)
	end
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
						f = UserFile.new(servid, file['userid'], file['path'])
						f.process
					}
					res = user_db.query("SELECT * FROM gallerypics WHERE userid IN #", userids)
					res.each {|gallery|
						f = GalleryFile.new(servid, gallery['userid'], gallery['id'], gallery['sourceid'])#, gallery['userpicid'])
						f.process
					}
				}
			rescue
				puts $!
				$!.backtrace.each{|line| puts line }
			end
		}
	}
}
