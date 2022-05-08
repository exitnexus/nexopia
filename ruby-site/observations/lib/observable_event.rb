lib_require :Core, "storable/cacheable"
lib_require :Observations, "observable"
lib_require :Observations, "site_event"
lib_require :Observations, "user_event"
lib_require :Observations, "friend_event"
lib_require :Spotlight, "birthday"

lib_want :Gallery, "gallery_folder"
lib_want :Gallery, "gallery_pic"
lib_want :Comments, "comments"

module Observations

	class FakeEvent
		
		def initialize(msg)
			@message = msg;
		end
		
		def time
			return 0
		end
	
		def display_message(*any)
			return @message
		end
		
		def originatorid
			return 0
		end
		
		def userid
			return 0
		end
		
		def image
			return self
		end
		
		def img_info()
			return ["Error",""]
		end
	end
	
	
	# Mainly a static accessor class.
	class ObservableEvent
		extend TypeID;

		def self.preload(observations)
			
			#For each FriendEvent, get the UserEvent key/object
			event_keys = [];
			event_objs = {};
			observations.each{|obs|
				event_keys << [obs.originatorid, obs.eventid];
				if (obs.classtypeid < 1)
					$log.object obs, :error;
					$log.object observations, :error;
					raise "A bad typeid was found in the observable database."
				end
				event_objs[obs.classtypeid] ||= [];
				event_objs[obs.classtypeid] << obs.objectid;
			}
			
			preload_events_and_objs(event_keys, event_objs)

		end

		def self.preload_events_and_objs(event_keys, event_objs)
			events = UserEvent.find(:all, *event_keys);
			
			event_objs.keys.each{|klassid|
				keys = [];
				event_objs[klassid].each{|oid|
					begin
						keys << Marshal.load(oid)
					rescue
						$log.info "Bad object? #{oid}", :error
						$log.info $!, :error
						$log.info $!.backtrace.join("\n"), :error
					end
				}
				klass = TypeID.get_class(klassid);
				if (!klass)
					raise "Observable module required but not loaded: #{klassid}";
				end
				klass.find(*(keys.uniq));
			}
		end

		def ObservableEvent.create_log(user, event)
			return if (event.object.nil?) 	#This usually means something was deleted before
											#the event was processed.
			permissions = ObservablePermissions.new(user.userid);
			# Trying event
			if permissions.allow_event?(event)
				s = event.object.all_receivers.uniq
				s.each{|other|
					next if (other.kind_of? AnonymousUser)
					if (ObservationPreferences.quick_check(other.userid, user.userid, event.classtypeid))
						FriendEvent.create_log(other.userid, event);
					end
				}
			end
		end
		
		def ObservableEvent.create_event(klass, event_type, uid, time, params)
			begin
				object = TypeID.get_class(klass).find(:first, *params);
				if (object.nil?)
					$log.info "Deleting event on #{klass} with params:", :debug
					$log.object params, :debug;
					return
				end
			rescue
				$log.info "Error while loading typeid: #{klass}", :error
				raise $!
			end
			create(object, event_type, uid, Time.at(time))
		end
		worker_task :create_event
		
		def ObservableEvent.create(object, event_type, uid, time)
			#user = User.get_by_id(uid);
			user = User.find(:first, :nomemcache, :refresh, uid)
			
			klass = object.class;
			key = object.get_primary_key;
			event = UserEvent.create(klass, event_type, key, uid, time);
			log = ObservableEvent.create_log(user, event);
		end
		worker_task :create
	
		def ObservableEvent.delete_log(receiverid, originatorid, eventid, time)
			FriendEvent.delete_log(receiverid, originatorid, eventid, time)
		end
		worker_task :delete_log
	
		def ObservableEvent.delete_logs(receiverids, originatorid, eventid, time)
			[*receiverids].each{|friendid|
				ObservableEvent.delete_log(friendid, originatorid, eventid, time)
			}
		end
		
		def ObservableEvent.delete_all(typeid, key)
			logs = FriendEvent.find(:conditions => ["classtypeid = ? AND objectid = ?", typeid, Marshal.dump(key)])
			logs.each{|log|
				FriendEvent.delete_log(log.userid, log.originatorid, log.id, Time.now)
			}
		end
	
		def ObservableEvent.delete_event(originator, id)
			event = UserEvent.find(:first, originator, id)
			event.delete();
		end
		worker_task :delete_event

	end
end
