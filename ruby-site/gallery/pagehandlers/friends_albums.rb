lib_require :Gallery, 'gallery_folder'
lib_require :Core, 'storable/relation_manager'
lib_want :Scoop, 'story'

module Gallery #if (site_module_loaded?(:Scoop))
	class FriendsAlbum < PageHandler
		declare_handlers("gallery") {
			area :User
			page :GetRequest, :Full, :friends_albums, :friends, :updates
		}

		def friends_albums
			apply_user_skin!
			request.reply.headers['X-width'] = 0

			t = Template::instance('gallery', 'friends_albums')
			t.user = request.user
			t.stories = Scoop::Story.find(:user_type, request.user.userid, Gallery::GalleryFolder.typeid)

			if (!t.stories.empty?)
				t.stories.each {|story|
					story.reporter #prime the reporter objects
				}
				t.stories.reject! {|story| story.reporter.nil? || story.reporter.owner.nil? || !story.reporter.viewable_by_user?(request.session.user)}

				ids = t.stories.map {|story| [story.primaryid, story.secondaryid]}
				counts = {}

				#only mark stories as viewed when the owner of the story views them
				if (request.user == request.session.user)
					Scoop::Story.db.query("UPDATE scoop_story SET viewed = 'y' WHERE userid = # && typeid = ?", request.user.userid, Gallery::GalleryFolder.typeid)
					t.stories.each {|story|
						story.invalidate_cache_keys(false)
						RelationManager.invalidate_delete(story)
					}
				end

				t.stories.each {|story|
					count = story.reporter.size
					album = story.reporter
					album.meta.count = count
					album.album_cover #prime this
					if (count <= 3)
						album.meta.preview_pics = (1..count).map {|priority| album.pic_priority(priority)}
					else
						#second pic, middle pic, last pic
						album.meta.preview_pics = [album.pic_priority(2), album.pic_priority((count/2)+1), album.pic_priority(count)]
					end
				}

				t.stories = t.stories.sort_by {|story| -story.reporter.created}
			end


			puts t.display
		end

		private
		def apply_user_skin!
			user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.galleryskin]);
			if(!user_skin.nil?() && request.user.plus?())
				request.reply.headers['X-user-skin'] = user_skin.header();
			end
		end
	end
end if (site_module_loaded?(:Scoop))
