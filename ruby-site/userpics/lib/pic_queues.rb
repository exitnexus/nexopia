lib_require :Gallery, "gallery_pic"
lib_require :Core, "abuse_log"
lib_want :Moderator, "modqueue"

if (site_module_loaded?(:Moderator))
	module Userpics
		class PicQueueItem < Moderator::ModItem
			relation_singular :gallery_pic, [:splitid, :itemid], Gallery::Pic
			relation_singular :user, :splitid, User
			relation_multi :votes, :id, Moderator::ModVote, :moditemid # duplicated due to storable bug.
			
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
			
			def show_username?
				return false
			end
		end
		
		class DetailedPicQueueItem < PicQueueItem
			# duplicated here to get around a bug in storable relations.
			relation_singular :gallery_pic, [:splitid, :itemid], Gallery::Pic
			relation_singular :user, :splitid, User
			relation_multi :votes, :id, Moderator::ModVote, :moditemid # duplicated due to storable bug.

			def show_username?
				return true
			end
		end
		
		class QuestionablePicsQueue < Moderator::QueueBase
			declare_queue("Questionable", 4)
			self.item_type = DetailedPicQueueItem
			
			# The default implementation will push the queue items back to PicsQueue, so don't define them here.
		end
		
		class ModVotesLog < Storable
			init_storable(:moddb, "modvoteslog")
		end
		
		class PicsQueue < Moderator::QueueBase
			declare_queue("Pics", 1)
			set_questionable(QuestionablePicsQueue)
			self.item_type = PicQueueItem

			def self.log_votes(items)
				time = Time.now.to_i()
				items.each {|item|
					item.votes.each {|vote|
						log = ModVotesLog.new
						log.modid = vote.modid
						log.userid = item.splitid
						log.picid = item.itemid
						log.vote = vote.vote
						log.time = time
						if (item.gallery_pic == nil)
							log.description = ""
						else
							log.description = item.gallery_pic.description
						end
						log.points = vote.points
						log.store
					}
				}
			end
			
			def self.handle_yes(items)
				log_votes(items)
				
				key_list = []
				items.each { |item|
						key_list.push([item.splitid, item.itemid])
				}

				pics = Pics.find(*key_list)
				pics.each { |pic|
					pic.gallery_pic.userpic = Gallery::Pic::ACCEPTED
					pic.gallery_pic.store
				}
				
			end

			def self.handle_no(items)
				log_votes(items)

				# create a list of keys that are the user id and the image id. 
				key_list = []
				items.each { |item|
					key_list.push([item.splitid, item.itemid])
				}

				# find pic objects for the profile pics we just moderated
				pics = Pics.find(*key_list)
				pics.each { |pic|
					pic.gallery_pic.userpic = Gallery::Pic::FAILED
					pic.gallery_pic.store
					
					if (!pic.gallery_pic.md5.nil?) # md5 should never be nil, but sometimes it seems to be.
						# find any bans that exist for the given pic
						ban = PicBans.find(:first, [pic.gallery_pic.userid, pic.gallery_pic.md5])
						globalban = GlobalPicBans.find(:first, pic.gallery_pic.md5)
					
						# Add or increment the ban counter
						if (ban)
							ban.times += 1
						else
							ban = PicBans.new
							ban.userid = pic.gallery_pic.userid
							ban.md5 = pic.gallery_pic.md5
							ban.times = 1
						end
					
						if (globalban)
							globalban.times += 1
						else
							globalban = GlobalPicBans.new
							globalban.md5 = pic.gallery_pic.md5
							globalban.times = ban.times
						end
					
						ban.store					
						globalban.store
					end
					
					# Baned pics get deleted as profile pics, but can stay in the gallery.
					pic.delete
					
				}

			end
		end
	
		class SignPicsQueue < Moderator::QueueBase
			declare_queue("Sign Pics", 2)
			self.item_type = DetailedPicQueueItem

			def self.handle_yes(items)
				
				key_list = []
				items.each { |item|
						key_list.push([item.splitid, item.itemid])
				}

				pics = Gallery::Pic.find(*key_list)
				pics.each { |pic|
					pic.signpic = Gallery::Pic::ACCEPTED
					pic.store
				}
				
				# raise "Yes votes on pic items #{items.join(',')}, but not implemented yet."
			end
			def self.handle_no(items)
				
				key_list = []
				items.each { |item|
						key_list.push([item.splitid, item.itemid])
				}

				pics = Gallery::Pic.find(*key_list)
				
				pics.each { |pic|
					pic.signpic = Gallery::Pic::FAILED
					pic.store
				}
				
				# raise "No votes on pic items #{items.join(',')}, but not implemented yet."
			end
		end
	end	
end

class User < Cacheable
	def pic_mod?
		return !mod_level(Userpics::PicsQueue).nil?
	end
end
