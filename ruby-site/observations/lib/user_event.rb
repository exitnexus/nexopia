module Observations
	class UserEvent < Cacheable
		init_storable(:usersdb, "userevents")
		
		def self.events(userid, count = nil, page = nil)
			page = page || 0;
			length = count || 20;
			offset = page * length
	
			return UserEvent.find(:total_rows, :conditions => ["userid = #", userid], :order => "time DESC", :page => page, :limit => count)
		end
		
		def self.events_by_day(userid)
			o_entries = UserEvent.find(:total_rows, :conditions => ["userid = # && time > ?", userid, Time.now.to_i - (2*86400)], :order => "time DESC")
			ObservableEvent.preload(o_entries);
			UserEvent.check_deleted(o_entries)

			entries = [];
			o_entries.each{|e|
				if (e.object != nil && e.object.display_message(e.eventtype) != nil)
					entries << e;
				end
			}
			collapsed = [];
			while (entries.length > 0)
				entry = entries.slice!(0)
				(entry, entries) = entry.collapse(entries)
				collapsed << entry
			end		
			return collapsed;
			
		end
		
		def self.check_deleted(observations)
			deleted = false;

			#using preloaded events, check if any updates need to be deleted.
			observations.each{|obs|
				event = UserEvent.find(:first, obs.originatorid, obs.eventid);
				if (event == nil || obs.object == nil)
					obs.delete
					deleted = true;
				end
			}
			return deleted;
		end


		def storable_new()
			@event_object = nil;	
		end
		
		# Mirrors the field in FriendEvents, so that they both
		# have the same interface.
		def originatorid
			return userid
		end

		def eventid
			return id
		end
		
		def display_message()
			object.display_message(eventtype)
		end

		def image
			return User.get_by_id(originatorid)
		end

		def collapse(event_list)
			output_list = [];
			collapsed_list = [self];
			event_list.each{|event|
				if (object.collapse?(event))
					collapsed_list << event
				else
					output_list << event
				end
			}
			if (collapsed_list.length == 1)
				return [self, output_list]; 
			else
				return [object.collapsed_event(collapsed_list), output_list]
			end
		end
		
		
		def object
			if (@event_object)
				return @event_object;
			end
			
			begin
				key = Marshal.load(@objectid)
			rescue
				$log.info "Bad key.";
				self.delete
				raise "error."
				return FakeEvent.new("Broken object.  This will be removed when you refresh.");
			end
			
			if (TypeID.get_class(@classtypeid).nil?)
=begin
				klass = TypeIDItem.find(:first, @classtypeid).typename
				(mod, klass) = klass.split("::") if klass.index("::")
				lib_require(mod, klass);
=end
				
				if (TypeID.get_class(@classtypeid).nil?)
					raise "Error: #{@classtypeid} is not a loaded class."
				end
			end
			klass = TypeID.get_class(@classtypeid)
			obj = klass.find(:first, *key);
			if (obj == nil)
				$log.info "observable-system: Nothing found for #{klass}, #{[*key].join(",")} - deleting", :debug
				#obj = FakeEvent.new("#{klass.name}: #{[*key].join(',')} broken. This will be removed when you refresh.");
				obj = nil;
				self.delete
			end
			return obj;			
		end
		
		def UserEvent.create(klass, event_type, key, uid, time)
			u = new();
			u.id = UserEvent.get_seq_id(uid);
			u.userid = uid;
			u.time = time.to_i;
			u.classtypeid = klass.typeid;
			u.eventtype = event_type;
			u.objectid = Marshal.dump(key);
			u.store;
			return u;
		end
	end
	
end