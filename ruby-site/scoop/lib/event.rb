lib_require :Worker, 'worker'
lib_require :Scoop, 'story'
lib_require :Friends, 'friend'

require 'set'

module Scoop
	class Event < Storable
		init_storable(:usersdb, 'scoop_event')
		
		extend TypeID
		
		set_enums(:event => {
			:default => 0,
			:create => 1,
			:update => 2,
			:delete => 3
		})
		
		# User that is the source of this event object.
		relation :singular, :user, [:userid], User

		# Store this event in the given user's feed.
		def distribute!(userid)

			# Check with the object that created the event 
			# that the given user can see the event.
			if (!self.reporter.nil? && self.reporter.can_view_event?(userid))
				story = Story.new
				story.userid = userid
				story.id = Story.get_seq_id(userid)
				story.typeid = self.typeid
				story.primaryid = self.primaryid
				story.secondaryid = self.secondaryid
				story.sourceuserid = self.userid
				story.sourceid = self.id
				story.viewed = false

				# if this event has already been inserted ignore the insert.
				story.store(:ignore, :affected_rows)
				if (story.affected_rows > 0 && self.reporter.class.after)
					self.reporter.send(self.reporter.class.after, story)
				end
			end
			
		end # def distribute!(userid)
		
		# Returns the list of users this event should be distributed to.
		# If the object defines #report_readers (usually created by #report at the top of the object)
		# then we'll fetch the list from that and from subscriptions.
		# 
		# If #report_readers isn't defined we'll just fetch the person's friends list.
		def readers
			
			# if the class defines report_readers then we'll use that.
			reader_list = []
			
			if (reporter.class.report_readers)
				reader_list = [*reporter.send(reporter.class.report_readers)]
			else
				reader_list = user.friends_of.map { |friend| friend.owner }
			end
			reader_list += subscribers()
			reader_list.uniq!
			return reader_list
			
		end
		
		def subscribers()
			
			# We want to find anyone who's subscribed to the user, the class of objects for the user (say pictures), or a given object of that class (a specific picture).
			#
			# Primaryid is exclusivly userid at this point but in the future it might not be.
			# We would have to re-architect this a little if that ever becomes the case.
			subscribe = reporter.class.subscribe
			return [] if subscribe.nil?
			
			primaryid = reporter.send(subscribe[:primaryid])
			typeid = subscribe[:typeid]
			secondaryid = reporter.send(subscribe[:secondaryid])			
			
			subscriptions = Subscription.find([primaryid, 0, 0], [primaryid, typeid, 0], [primaryid, typeid, secondaryid])
			
			# Get a unique list of userids.
			subscriber_ids = subscriptions.map { |subscription| subscription.userid }
			subscriber_ids.uniq!

			if (subscriber_ids.size > 0)
				# Return the user objects for those IDs.
				return User.find(:promise, *subscriber_ids)
			else
				return []
			end

		end
		
		# Get the class of the object that created this event.
		def reporter
			storable_class = TypeID.get_class(self.typeid);
			if (storable_class.indexes[:PRIMARY].length > 1)
				return storable_class.find(self.primaryid, self.secondaryid, :promise, :first);
			else
				return storable_class.find(self.primaryid, :promise, :first);
			end
		end
		
		#distribute_event distributes an event to everyone who should receive it, currently the event
		#owner's reverse friends list.  Eventually the logic to do this may become more complex and
		#event distribution may be best refactored out into a separate management class.
		def self.distribute_event(userid, id)
			
			# Get the event we want to distribute.
			event = Scoop::Event.find(userid, id, :first)
			return false if event.nil?
			
			# Find the user who posted the event.
			user = User.find(:first, :promise, event.userid)
			
			# make sure we have the class that created the event.
			if (!event.reporter.nil? && !user.nil?)
				
				# for each friend of the user get their user object and loop over them.
				event.readers.each { |target_user|

					# If the creator of the event hasn't been ignored distribute the event to the target user.
					if (!target_user.ignored?(userid))
						
						# add the event to the target_user's feed
						event.distribute!(target_user.userid) #friend_id is [userid, friendid] so we just want the userid

						# Trim the queue so we don't have too many stories.
						event.reporter.class.clean_up_stories(target_user.userid)
					end

				}
				return true
			end
			
			return false
		end
		register_task ScoopModule, :distribute_event

		def self.redistribute_event(userid, id)
			event = Scoop::Event.find(userid, id, :first)
			stories = Scoop::Story.find(userid, id, :source_event)
			stories.each {|story|
				story.user #priming users
			}
			if (!event.reporter.nil?)
				stories.each {|story|
					story.reevaluate_permissions!
				}
				#this will create stories for anyone who should get one but doesn't have one yet
				#since it uses insert ignore and has a unique index on sourceuserid, sourceid, userid,
				#we don't have to worry about the risk of inserting duplicates
				self.distribute_event(userid, id)
				return true
			else 
				#the object the event is referring to is deleted so just delete the event
				self.delete_event(userid, id)
				return false
			end
		end
		register_task ScoopModule, :redistribute_event

		def self.delete_event(userid, id)
			stories = Scoop::Story.find(userid, id, :source_event)
			stories.each {|story|
				story.delete if !story.nil?
			}
			event = Scoop::Event.find(userid, id, :first)
			event.delete if !event.nil?
			return true
		end
		register_task ScoopModule, :delete_event

		#adds events from friendid to userid's feed if they can view them
		def self.populate_friendship(userid, friendid)
			events = Scoop::Event.find(friendid)
			
			classes = Set.new #all of the classes we have an event for (these will need to have their feeds cleaned up)
			
			events.each {|event|
				if (!event.reporter.nil?)
					reader_uids = event.readers.map{|reader| reader.userid}
					if (reader_uids.include?(userid))
						event.distribute!(userid)
						classes.add(event.reporter.class)
					end
				else
					self.delete_event(event.userid, event.id) #if a reporter is nil get this garbage outta here!
				end
			}
			classes.each {|event_class|
				event_class.clean_up_stories(userid)
			}
			return true
		end
		register_task ScoopModule, :populate_friendship
		
		#looks at friendid's stories and verifies permissions for them all
		def self.reevaluate_friendship(userid, friendid)
			stories = Scoop::Story.find(friendid, :conditions => ["sourceuserid = ?", userid])
			stories.each {|story|
				story.reevaluate_permissions!
			}
			return true
		end
		register_task ScoopModule, :reevaluate_friendship
	end
end