
module Observations
	class FriendEvent < Storable
		init_storable(:usersdb, "friendevents")
		
		def self.events_by_day(userid)
			o_entries = find(:total_rows, :conditions => ["userid = # && time > ?", userid, Time.now.to_i - (365*86400)], :order => "time DESC")
			ObservableEvent.preload(o_entries);
			UserEvent.check_deleted(o_entries)
			
			entries = [];
			o_entries.each{|e|
				if (e.object != nil)
					if (e.object.display_message(e.eventtype) != nil)
						entries << e;
					end
					# This is not an error because we allow some events to be
					# ignored by the classes that generate them.
				else
					#This is not an error because it probably means that the
					# backing object was deleted.
					e.delete
				end
			}
			
			collapsed = {};
			entries.each{|entry|
				collapsed[entry.object.collapsable_bucket(entry)] ||= []
				collapsed[entry.object.collapsable_bucket(entry)] << entry
			}
			
			output = []
			collapsed.each_pair{|key, list|
				if (list.length > 1)
					output << list.first.object.collapsed_event(list)
				else
					output << list.first
				end
			}
			
			return output.sort{|a,b| 
				b.time <=> a.time
			};
			
		end

		def self.events(userid, count, page)
			cull(userid);
			
			observations = find(:total_rows, userid, :order => "time DESC", :page => page, :limit => count);
			
			deleted = preload(observations);

			if (deleted)
				#redo, we didn't get enough.
				return friend_events(userid, count, page);
			end
			
			return observations;
		end
	
		def storable_new()
			@event_object = nil;	
		end
		
		def self.cull(userid)
			ids = self.db.query("SELECT t1.userid, t1.originatorid, t1.eventid FROM #{self.table} as t1, #{self.table} as t2 
WHERE t1.userid = # AND t2.userid = # AND t1.originatorid = t2.originatorid AND t1.eventid = t2.eventid AND t1.type = 'add' AND t2.type = 'delete'",
			userid,userid
			)
			
			conds = [];
			ids.each{|row|
				conds << "userid = #{row['userid']} AND originatorid = #{row['originatorid']} AND eventid = #{row['eventid']}"
			}
			
			if (conds.size > 0)
				delete_query = "DELETE #{self.table} WHERE " << conds.join(" OR ");
				self.db.query(delete_query);
			end
		end
		
		def self.create_log(receiverid, user_event)
			f = self.new();
			f.userid = receiverid;
			f.originatorid = user_event.userid;
			f.eventid = user_event.id;
			f.time = user_event.time;
			f.classtypeid = user_event.classtypeid;
			f.eventtype = user_event.eventtype;
			f.objectid = user_event.objectid;
			f.store;
		end
		
		def self.delete_log(receiverid, originatorid, eventid, time)
			user = User.get_by_id(receiverid);
			
			f = self.new();
			f.userid = receiverid;
			f.originatorid = originatorid;
			f.eventid = eventid;
			f.type = 'delete';
			f.time = time.to_i;
			f.classtypeid = -1;
			f.eventtype = "";
			f.store;
		end
		
		def display_message()
			object.display_message(self.eventtype)
		end
		
		def image
			return User.get_by_id(self.originatorid)
		end
		
		def object
			if (@event_object)
				return @event_object;
			end
			
			key = Marshal.load(@objectid)
			if (TypeID.get_class(@classtypeid).nil?)
				raise "Class id #{@classtypeid} is not a loaded class."
			end
			klass = TypeID.get_class(@classtypeid)
			obj = klass.find(:first, *key);
			if (obj == nil)
				$log.info "Nothing found for #{TypeID.get_class(@classtypeid)}, #{[*key].join(",")}", :error
				#raise "Deleted event #{klass.name} -> #{key}.";
			end
			return obj;
		end
		
	end
end