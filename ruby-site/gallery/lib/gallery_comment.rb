lib_require :Core, "user_time"
lib_require :Archive, "archive"
lib_require :Gallery, "gallery_pic"
lib_want :Comments, "comment_permissions"
lib_want :Scoop, 'story'

module Gallery
	class GalleryComment < Cacheable
		extend TypeID;
		
		init_storable(:usersdb, "gallerycomments");
	
		relation :singular, :pic, [:userid, :picid], Gallery::Pic
		relation :singular, :owner, [:userid], User
		relation :singular, :db_author, [:authorid], User
	
		report :create, :report_readers => :owner, :subscribe => { 
			:primaryid => :userid, 
			:typeid => Gallery::Pic.typeid, 
			:secondaryid => :picid, 
			:userid => :authorid
		}, :sort => :feed_sort, :after => :mark_own_read, :restrict => :can_view?
				
		user_content :nmsg
		
		COMMENTS_PER_PAGE = 10;
		
		def feed_sort
			return -time
		end
		
		def can_view?(userid)
			return false if self.pic.nil?
			return false if self.pic.gallery.nil?
			return self.pic.gallery.scoop_permission?(userid)
		end
		
		def mark_own_read(story)
			if (story.userid == self.authorid)
				story.viewed = true
				story.store
			end
		end
		
		def author
			if (db_author.nil?)
				return DeletedUser.find(:first, self.authorid)
			end
			return db_author
		end
	
		def after_create
			#Archive::save(authorid, id, Archive::ARCHIVE_COMMENT, Archive::ARCHIVE_VISIBILITY_USER, 
			#	user.userid, 0, "", nmsg);
			super
		end
	
		def date
			UserTime.new(time).strftime("%I:%M%p | %b %d, '%y" );
		end
		
		def may_delete_without_abuse_report?(session)
			return (!self.deleted) && 
				((self.authorid == session.user.userid) || 
				self.userid == session.user.userid)
		end
	
		def may_delete?(session)
			return (!self.deleted) && 
				((self.authorid == session.user.userid  && (Time.now.to_i - self.time < 300)) || 
				self.userid == session.user.userid ||
				session.has_priv?(CoreModule, :deletecomments)) 
		end
		
		# A comment can be quick deleted (without abuse report) if the comment hasn't already been deleted and
		#  the user viewing the comments is either the author of the post (and currently has plus) or the
		#  owning user of the comments page.
		def quick_delete?(viewing_user)
			if(self.deleted)
				return false;
			elsif((self.authorid == viewing_user.userid && viewing_user.plus?()) || self.userid == viewing_user.userid)
				return true;
			else
				return false;
			end
		end
		
		# Determines if a user can post. This isn't the only function to determine if an editor appears for a gallery image. If the gallery doesn't
		#  allow comments the editor won't appear.
		def self.can_post?(gallery_owner, viewing_user)
			# If it's the owner viewing the page, he/she's all good to post
			if(gallery_owner == viewing_user)
				return true;
			end
			
			# If the viewing user is anonymous or is ignored by the gallery owner they are not allowed to post.
			if(viewing_user.anonymous?() || gallery_owner.ignored?(viewing_user))
				return false;
			end
			
			# If the viewer is a friend of the gallery owner then they can post.
			if(gallery_owner.friend?(viewing_user))
				return true;
			end
			
			# If the gallery owner has it set up to only accept comments from friends and the user isn't a friend, then it's a no go.
			if(gallery_owner.friends_only?(:comments) && !gallery_owner.friend?(viewing_user))
				return false;
			end
			
			# If the gallery owner has it set up to ignore comments from users outside their age range and the viewing user is outside that range, then too bad.
			if(gallery_owner.ignore_section_by_age?(:comments) && (viewing_user.age < gallery_owner.defaultminage || viewing_user.age > gallery_owner.defaultmaxage))
				return false;
			end
			
			# Otherwise you're good to post and giv'er
			return true;
		end

	end
	
	class Pic < Cacheable
		relation :multi, :first_five_comments, [:userid, :id], GalleryComment, {:index => :userid, :limit => 5, :conditions => "deleted = 'n'", :order => "time DESC", :extra_columns => :deleted}
		relation :count, :comments_count, [:userid, :id], GalleryComment, :index => :userid, :conditions => "deleted = 'n'", :extra_columns => :deleted
	end
end

class User
	if (site_module_loaded?(:Scoop))
		relation :count, :gallery_comments_count, :userid, Scoop::Story, :user_type, :conditions => ["typeid = ? && viewed = 'n'", Gallery::GalleryComment.typeid], :extra_columns => :viewed
	end
end