lib_want :Gallery, 'gallery_comment'
lib_require :Core, 'storable/relation_manager'
lib_want :Scoop, 'story'

module Comments #if (site_module_loaded?(:Scoop))
	class GalleryCommentsFeed < PageHandler
		MAX_COMMENTS_PER_PICTURE = 3
		MAX_COMMENT_LENGTH = 500

		declare_handlers("comments") {
			area :User
			page :GetRequest, :Full, :comment_feed, :gallery
		} 
		
		def comment_feed
			apply_user_skin!
			request.reply.headers['X-width'] = 0
			
			t = Template::instance('comments', 'comment_feed_gallery')
			
			t.user = request.user
			t.viewer = request.session.user
			
			stories = Scoop::Story.find(:user_type, request.user.userid, Gallery::GalleryComment.typeid)
			
			stories.each { |story| story.reporter } #prime reporter
			stories.reject! { |story| story.reporter.nil? || story.reporter.deleted } #delete nil objects
			stories.each { |story| story.reporter.pic } #prime pics
			stories.reject! { |story| story.reporter.pic.nil? } #delete nil objects
			stories.each { |story| story.reporter.pic.gallery } #prime galleries
			
			#delete nil galleries and ones the viewer isn't allowed to see
			stories.reject! { |story| story.reporter.pic.gallery.nil? || !story.reporter.pic.gallery.viewable_by_user?(request.session.user) }
			stories.each { |story| story.reporter.db_author } #prime authors
			
			stories = stories.sort_by { |story| -story.reporter.time }
			pics = {}
			story_maps = []
			
			#We want an array of pics => the comments on them but we want to order it so we can't use a hash.
			#We use the pics hash to deal with merging comments for a picture and then story the index of the array where those
			#comments should be added.  It's a little convoluted but it works.
			stories.each { |story|
				pics[[story.reporter.userid, story.reporter.picid]] ||= pics.length
				story_maps[pics[[story.reporter.userid, story.reporter.picid]]] ||= []
				story_maps[pics[[story.reporter.userid, story.reporter.picid]]] << story
			}

			# Calculate the number of new gallery comments for a given pic
			story_maps.each { |story_map|
				
				new_stories = 0
				story_map.each { |story|
					if( story.viewed == false )
						new_stories += 1
					end
				}
				story_map.meta.new_stories = new_stories
				
			}

			# Wacky code to figure out which comments we want to show for each comment
			# Essentially we want to show the viewing user's latest comment if one exists and a couple of other comments
			story_maps.each { |story_map|
				if (story_map.first.reporter.userid != request.user.userid) #if the person who owns the picture is not the person who owns the feed
					
					story_map.each_with_index { |story, i|
						if (story.reporter.authorid == request.user.userid) #if the author is the person who owns the feed
						
							if (i >= MAX_COMMENTS_PER_PICTURE) #this means there was NOT a comment of the feed owners in the MAX_COMMENTS_PER_PICTURE most recent comments
								story_map.meta.owner_comment = true
								story_map[MAX_COMMENTS_PER_PICTURE-1] = story #We put the most recent feed owners comment at the top of the feed comments for this picture
							end						
							break
							
						end
					}
				end
				
				# Trim the comment list to be within MAX_COMMENTS_PER_PICTURE
				story_map.slice!([MAX_COMMENTS_PER_PICTURE, story_map.length].min, [0, (story_map.length - MAX_COMMENTS_PER_PICTURE)].max) #we want story_map to become its first MAX_COMMENTS_PER_PICTURE stories
				story_map.meta.link_to_all = true if (story_map.length < story_map.first.reporter.pic.comments_count)
				
				#limit the length of each comment to MAX_COMMENT_LENGTH characters
				story_map.each {|story|
					if (story.reporter.nmsg.length > MAX_COMMENT_LENGTH)
						story.reporter.nmsg = story.reporter.nmsg[0,MAX_COMMENT_LENGTH] + "..."
					end
				}
				
				story_map.reverse! #should be sorted oldest to newest within a picture
				
			}
			
			#remove any pics that don't contain a comment other than the feed owners
			story_maps.reject! { |stories|
				someone_elses_comment = false
				stories.each {|story|
					someone_elses_comment = true if story.reporter.authorid != request.user.userid
				}
				!someone_elses_comment
			}
			
			t.story_maps = story_maps
			
			# if we're the owner of the comment feed, then set all the comments as viewed.
			if (request.user == request.session.user)
				Scoop::Story.db.query("UPDATE scoop_story SET viewed = 'y' WHERE userid = # && typeid = ?", request.user.userid, Gallery::GalleryComment.typeid)
				stories.each {|story|
					story.invalidate_cache_keys(false)
					RelationManager.invalidate_delete(story)
				}
			end
			
			puts t.display
		end
		
		private
		def apply_user_skin!
			user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.commentsskin]);
			if(!user_skin.nil?() && request.user.plus?())
				request.reply.headers['X-user-skin'] = user_skin.header();
			end
		end
	end
end if (site_module_loaded?(:Scoop) && site_module_loaded?(:Gallery))
