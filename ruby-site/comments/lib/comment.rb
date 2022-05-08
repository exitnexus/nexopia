lib_require :Core, "user_time"
lib_want :user_dump, 'archive_type', 'dumpable'
lib_require :Archive, "archive"
lib_require :Comments, "comment_permissions"

module Comments
	class Comment < Cacheable
		init_storable(:usersdb, "usercomments");
		
		relation :singular, :user, [:userid], User
		relation :singular, :db_author, [:authorid], User
		
		user_content :nmsg
		
		if (site_module_loaded?(:UserDump))
			extend(Dumpable)
			def self.user_dump(uid, start=0, finish=Time.now.to_i)
				archive = ArchiveType.new(ArchiveType::COMMENT)
				dump = archive.dump_archive(uid, start, finish)
				return Dumpable.str_to_file("#{uid}-comments.txt", dump)
			end
		end
		
		COMMENTS_PER_PAGE = 20;
		
		# We need to wrap the author relation because the author of a comment might be a deleted user. If so
		#  the relation will return nil, at that point we will try to find the deleted user. If we can't we'll
		#  just use a placeholder.
		def author
			if (db_author.nil?)
				$log.info("Comments: We got a nil author relation", :debug)
				return DeletedUser.find(:first, self.authorid) || DeletedUser.new()
			end
			return db_author
		end
		
		postchain_method(:after_create) { |val|
			user.newcomments += 1
			user.store
		
			Archive::save(authorid, id, Archive::ARCHIVE_COMMENT, Archive::ARCHIVE_VISIBILITY_USER, user.userid, 0, "", nmsg);
			val
		};
		
		def date
			UserTime.new(time).strftime("%I:%M%p | %b %d, '%y" );
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

	end
end

class User < Cacheable
	relation :multi, :first_five_comments, [:userid], Comments::Comment, {:limit => 5, :conditions => "deleted = 'n'", :order => "time DESC", :extra_columns => [:deleted, :time]}
	relation :count, :comments_count, [:userid], Comments::Comment, {:conditions => "deleted = 'n'", :extra_columns => :deleted}
end
