
#define("ARCHIVE_OLDEST_TIME", gmmktime( 0, 0, 0, 6, 0, 2005 ));

class Archive
	ARCHIVE_MESSAGE = 1
	ARCHIVE_COMMENT = 11
	ARCHIVE_PROFILE = 21
	ARCHIVE_GALLERYCOMMENT = 31
	ARCHIVE_BLOGPOST = 41
	ARCHIVE_BLOGCOMMENT = 42
	
	ARCHIVE_FORUMPOST = 101
	ARCHIVE_ARTICLE = 111
	ARCHIVE_ARTICLECOMMENT = 112
	ARCHIVE_POLLCOMMENT = 121
	ARCHIVE_VIDEOCOMMENT = 131
	
	ARCHIVE_VISIBILITY_ANON = 1
	ARCHIVE_VISIBILITY_USER = 2
	ARCHIVE_VISIBILITY_FRIEND = 3
	ARCHIVE_VISIBILITY_ADMIN = 4
	ARCHIVE_VISIBILITY_PRIVATE = 5

	@@db = $site.dbs[:usersdb]
	@@anondb = $site.dbs[:anondb]

	class << self
		def save(userid, id, type, visibility, touserid, itemid, subject, msg)
			ip = PageRequest.current.get_ip_as_int
			insert(userid, id, type, visibility, Time.now.gmtime, (userid ? ip : 0), 
				touserid, itemid, subject, msg);
		end
	
		def insert(userid, id, type, visibility, time, ip, touserid, itemid, subject, msg)
			tablename = tablename(time);
			rv = nil
	
			retries = 1
			begin
				rv = @@db.query("INSERT INTO #{tablename} SET userid = #, id = ?, type = ?, 
					visibility = ?, time = ?, ip = ?, touserid = ?, itemid = ?, subject = ?, msg = ?", 
					userid, id, type, visibility, time.to_i, ip, touserid, itemid, subject, msg);
			rescue SqlBase::QueryError
				createTable(@@db, time);
				retries -= 1
				retry if (retries >= 0)
			end
	
			#get a way of finding when the above insert failed, create the table, then try again.
			#probably need exceptions, or extensive sql lib changes to allow and report certain errors
			retries = 1
			if (rv.affected_rows() < 1) 
				#Insert failure, attempt to insert into newusersanon
				begin
					@@anondb.query("INSERT INTO `#{tablename}` SET userid = #, id = ?, type = ?, 
					visibility = ?, time = ?, ip = ?, touserid = ?, itemid = #, subject = ?, msg = ?", 
					userid, id, type, visibility, time.to_i, ip, touserid, itemid, subject, msg);
				rescue SqlBase::QueryError
					createTable(@@anondb, time);
					retries -= 1
					retry if (retries >= 0)
				end
			end
		end
	
		def tablename(time = false)
			if (!time)
				time = Time.now.gmtime;
			end
	
			return "archive" + time.strftime("%Y%m");
		end
	
		def createTable(db, time)
	
			schematablename = 'archive';
			newtablename = tablename(time);
	
			#get the base schema
			res = db.query("SHOW CREATE TABLE `#{schematablename}`");
			row = res.fetch();
			oldcreatetable = row['Create Table'];
	
			#update it with the new date
			newcreatetable = oldcreatetable.gsub("CREATE TABLE `#{schematablename}`", "CREATE TABLE IF NOT EXISTS `#{newtablename}`");
	
			#create!
			db.query(newcreatetable);
	
		end
	end
end