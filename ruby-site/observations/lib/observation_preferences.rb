lib_require :Core, 'storable/storable'
require 'set'

module Observations
	class ObservationPreferences
	
		attr :userid, true;
		attr :filters, true;
		
		def initialize(userid)
			@userid = userid
			@filters = Hash.new()
			ObservationPreference.find(userid).each{|pref|
				@filters[[pref.eventuserid, pref.typeid]] = pref.display;
			}
		end
		
		def filter(event_list)
			filtered_list = Array.new();
			event_list.each{|event|
				key = [event.originatorid, event.classtypeid];
				if (!@filters.key?(key) or (@filters[key] == true))
					filtered_list << event;
				end
			}
			return filtered_list;
		end
		
		class << self
			def quick_check(userid, eventuserid, typeid)
				klass = TypeID.get_class(typeid);
				filter = ObservationPreference.find(:first, [userid, eventuserid, typeid]);
				if ((!filter and klass.observable_default) or filter.display)
					return true
				end
				return false;
			end
			
			#pass in a userid and a set of class typeids that should be allowed for all friends, the 
			#set of existing preferences will be updated to map to the allowed type set
			#this includes allowing new things, and denying things that are not in the allowed set
			def adjust_preferences(userid, allow_typeids)
				preferences_list = ObservationPreference.find(userid, 0)
				preferences = preferences_list.to_hash
				
				Observable.classes.each {|observable_class|
					current_pref = preferences[[userid, 0, observable_class.typeid]]
					if (current_pref)
						if (current_pref.display)
							if (!allow_typeids.index(current_pref.typeid))
								current_pref.display = false
								current_pref.store
							end
						else
							if (allow_typeids.index(current_pref.typeid))
								current_pref.display = true
								current_pref.store
							end
						end
					else
						new_pref = ObservationPreference.new
						new_pref.typeid = observable_class.typeid
						new_pref.userid = userid
						new_pref.eventuserid = 0
						if (allow_typeids.index(observable_class.typeid))
							new_pref.display = true
						else
							new_pref.display = false
						end
						new_pref.store
					end
				}	
			end
		end
	end
	
	class ObservationPreference < Storable
		init_storable(:usersdb, 'observationpreferences')
		
		relation_singular :eventuser, :eventuserid, User
		relation_singular :user, :userid, User
		
		#string representation of the display/block state
		def action
			if (display)
				return "Display"
			else
				return "Block"
			end
		end
		
		def observable_class
			return TypeID.get_class(typeid)
		end
	end
end