=begin
Proper use of this script:
1) Create the column 'gallerypicid' in newusers:pics
2) Create the column 'userpicid' in newusers:gallerypics
3) Create the column 'md5' (varchar32) in newusers:gallerypics
3) Run this script
4) Delete the column 'description' in newusers:pics
5) Change the key of newusers:pics to {userid, priority}


Run at the lowest log level.

If this crashes mid-loop, you can tell exactly what galleries were created by 
looking at the logs. You can then roll back. However, using gallery.delete() is
not recommended, it WILL NUKE USERPICS. Once the pics are linked to the gallery,
the gallery takes ownership and is responsible for deleting them.

=end


lib_require :Core, "users/user"
lib_require :Userpics, "pics"
lib_require :Gallery, "gallery_pic"
lib_require :Gallery, "gallery_folder"
lib_require :Core, "accounts"
lib_require :Core, "array", "rangelist"

$current_time = Time.now.to_i #cache so we don't create in the inner loops.



concurrency = ENV['CONCURRENCY'] || 10  # run this many processes
db_list     = ENV['DB_LIST'] && ENV['DB_LIST'].range_list # using these databases


concurrency = concurrency.to_i

db_hash = $site.dbs[:usersdb].dbs
db_list ||= db_hash.keys.sort {|a,b|
	if((a % 2) == (b % 2))
		(a <=> b)
	else
		(a % 2) <=> (b % 2)
	end
}



def migrate_user(db, userid, rows)
	return if(!userid || rows.length == 0)

	gallerypicid = nil

	galleryid = Gallery::GalleryFolder.get_seq_id(userid)

	return if(!galleryid)

	firstpic = Gallery::Pic.get_seq_id(userid);
	user_firstpic = firstpic;

	db.query("INSERT INTO `gallery` ( ownerid, id, name, permission, previewpicture, description, created ) 
		VALUES (#{userid}, #{galleryid}, 'Imported Pictures', 'anyone', #{firstpic}, 
		'These are pictures Nexopia has moved into the gallery when we updated our picture system.', #{$current_time})")


	rows.each{|row|
		if (!firstpic)
			gallerypicid = Gallery::Pic.get_seq_id(userid);
		else
			gallerypicid = firstpic
			firstpic = nil
		end

		is_userpic = 3;
		is_signpic = row['signpic'] == 'y' ? 2 : 0
		db.query("INSERT INTO `gallerypics` 
			( userid, id, sourceid, galleryid, md5, priority, description, userpicid, signpic, userpic, created )
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
			userid, gallerypicid, 0, galleryid, nil, row['priority'], row['description'], row['id'], is_signpic, is_userpic, $current_time)

		db.query("UPDATE `pics` SET `gallerypicid` = ? WHERE userid = ? && id = ?", gallerypicid, row['userid'], row['id'])
	}

	db.query("UPDATE `users` SET `firstpic` = ? WHERE `userid` = ?", user_firstpic, userid)
end

begin
	db_list.each_fork(concurrency){|servid|
		db = db_hash[servid]

		starttime = Time.now.to_i

		$log.info( ("[%s] %i: Migrating users from serverid %i" % 
			[Time.now.strftime("%b %d %Y, %H:%M:%S"), Process.pid, servid]) );


		lastuser = nil;
		user_rows = nil

		time = Time.now.to_f
		num_moved = 0;

		res = db.query("SELECT pics.* FROM users, pics WHERE users.userid = pics.userid ORDER BY pics.userid, pics.priority");
		res.each{|row|

			userid = row['userid'].to_i;

			if(userid != lastuser)

				migrate_user(db, lastuser, user_rows)

				num_moved += 1;
				if(num_moved % 1000 == 0)
					$log.info( ("[%s] %i: Server %i: currently moving %.2f users/s, overall moving %.2f users/s" % 
						[Time.now.strftime("%b %d %Y, %H:%M:%S"), Process.pid, servid, 
						1000.0 / (Time.now.to_f - time), num_moved.to_f / (Time.now.to_f - starttime)]) );
					time = Time.now.to_f
				end

				user_rows = [row];

				lastuser = userid;
			else
				user_rows << row;
			end
		}

		migrate_user(db, lastuser, user_rows)

		$log.info( ("[%s] %i: Done migrating users from serverid %i after %i seconds" % 
			[Time.now.strftime("%b %d %Y, %H:%M:%S"), Process.pid, servid, Time.now.to_i - starttime]) );
	}
rescue
	$log.info $!
	$log.info $!.backtrace.join("\n")
end
