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

def create_gallery(userid)
	gallery = Gallery::GalleryFolder.new();
	gallery.ownerid = userid;
	gallery.id = Gallery::GalleryFolder.get_seq_id(userid)
	gallery.name = "Pictures of me"
	gallery.description = "These are pictures Nexopia has moved into the gallery when we updated our picture system."
	gallery.permission = "anyone"
	gallery.store
	return gallery
end

def create_pic(userpic, gallery)
	pic = Gallery::Pic.new
	pic.userid = userpic.userid;
	pic.id = Gallery::Pic.get_seq_id(userpic.userid);

	pic.description = userpic.description
	pic.galleryid = gallery.id
	pic.priority = userpic.priority
	pic.userpicid = userpic.id;
	pic.store;
	
	userpic.gallerypicid = pic.id;
	userpic.store;
end

$chunk_size = 10 # The amount to page by when looping over accounts.

begin
	
	result = nil
	$log.info "migrate-script: userid\tgalleryid"

	User.db.dbs.each{|db|
		$log.info "migrate-script: --------------DB #{db[0].to_s}----------------"
		start = 0;
		begin #do...while loop
			# Get some user accounts from the DB. 
			result = Account.db.query("SELECT `id` FROM `accounts` WHERE `serverid` = #{db[0]} AND `type` = 6 LIMIT #{start},#{$chunk_size}")
			start += $chunk_size
			result.each{|row|
				userid = row['id'].to_i

				# Check if the user exists in the users DB, and skip if not.
				exist = db[1].query("SELECT userid FROM `users` WHERE `userid` = #{userid}")
				next if (exist.num_rows == 0)

				begin

if $find_and_delete_first || false
# Enable this variable to clean out the existing folders first.
# Warning - will set off the Observable system like crazy, DO NOT USE LIVE
					while (g = Gallery::GalleryFolder.find(:first, :conditions => ["ownerid = # AND name = 'Pictures of me'", userid]))
						$log.info "migrate-script: Exists for #{userid}, deleting."
						g.pics.each{|pic|
							pic.userpicid = 0;
						}
						g.delete;
					end
end	
					#Create a gallery to hold userpics
					gallery = create_gallery(userid)
					$log.info "migrate-script: #{userid}\t#{gallery.id}"
					
					#Load all userpics into the gallery.  After this point,
					#deleting the gallerypic or gallery will cascade and delete
					#the actual userpics.
					pics = Pics.find(userid);
					pics.each{|p|
						create_pic(p, gallery)
					}

				rescue
					$log.info "migrate-script: Error for user #{userid}"
					$log.info "migrate-script: #{$!}"
					$log.info "migrate-script: " + $!.backtrace.join("\nmigrate-script: ")
				end

			}
		end while (result and result.num_rows > 0)
	}
rescue
	$log.info $!
	$log.info $!.backtrace.join("\n")
end
