lib_require :Gallery, "gallery_pic"
lib_want :Moderator, "modqueue"

if (site_module_loaded?(:Moderator))
	module Gallery
		class GalleryQueueItem < Moderator::ModItem
			relation_singular :gallery_pic, [:splitid, :itemid], Gallery::Pic
			relation_singular :user, :splitid, User
			
			def user_color()
				case self.user.sex.to_sym
				when :Male
					"rgb(170, 170, 255)"
				when :Female
					"rgb(255, 170, 170)"
				else
					"rgb(255, 255, 255)"
				end
			end
			
			def validate()
				return (!gallery_pic.nil? && !user.nil?)
			end
		end

		class GalleryQueue < Moderator::QueueBase
			declare_queue("Gallery", 21)
			self.item_type = GalleryQueueItem
			
			def self.handle_yes(items)
				# Don't need to do anything on a yes.
			end
			def self.handle_no(items)
				
				# create keys to extract the pics from the database
				key_list = []
				items.each { |item|
					key_list.push([item.splitid, item.itemid])
				}

				# delete the pic
				pics = Pic.find(*key_list)
				pics.each { |pic|
					pic.delete
				}
				
			end
		end

		class GalleryAbuseQueue < Moderator::QueueBase
			declare_queue("Gallery Abuse", 22)
			self.item_type = GalleryQueueItem
			
			def self.handle_no(items)
				
				# create keys to extract the pics from the database
				key_list = []
				items.each { |item|
					key_list.push([item.splitid, item.itemid])
				}

				# delete the pic
				pics = Pic.find(*key_list)
				pics.each { |pic|
					pic.delete
				}
				
			end
		end
	end
end