lib_require :Core, 'storable/storable'

module Observations
	class ObservablePermissions
		attr :userid, true;
		attr :filters, true;
		
		EVENT_SHOW = true
		
		def initialize(userid)
			@userid = userid
			initialize_filters
			
		end
		
		def initialize_filters
			self.filters = {}
			permissions = ObservablePermission.find(@userid)
			permissions.each {|permission|
				self.filters[permission.typeid] = permission.allow
			}
		end
		
		def filter(event_list)
			filtered_list = Array.new();
			matched_events = []
			event_list.each{|event|
				if (allow_event?(event))
					matched_events << event
				end
			}
			return matched_events
		end
		
		def allow_event?(event)
			if (self.filters[event.classtypeid].nil?)
				type_class = TypeID.get_class(event.classtypeid)
				if (type_class.class_variables.index(:@@EVENT_SHOW))
					return type_class::EVENT_SHOW
				else
					return EVENT_SHOW
				end
			else
				return self.filters[event.classtypeid]
			end
		end
		
		class << self
			#pass in a userid and a set of class typeids that should be allowed, the 
			#set of existing permissions will be updated to map to the allowed type set
			#this includes allowing new things, and denying things that are not in the allowed set
			def adjust_permissions(userid, allow_typeids)
				permissions = ObservablePermission.find(userid).to_hash
				Observable.classes.each {|observable_class|
					current_permission = permissions[[userid, observable_class.typeid]]
					if (current_permission)
						if (current_permission.allow)
							if (!allow_typeids.index(current_permission.typeid))
								current_permission.allow = false
								current_permission.store
							end
						else
							if (allow_typeids.index(current_permission.typeid))
								current_permission.allow = true
								current_permission.store
							end
						end
					else
						new_perm = ObservablePermission.new
						new_perm.typeid = observable_class.typeid
						new_perm.userid = userid
						if (allow_typeids.index(observable_class.typeid))
							new_perm.allow = true
						else
							new_perm.allow = false
						end
						new_perm.store
					end
				}	
			end
		end
	end
	
	class ObservablePermission < Storable
		init_storable(:usersdb, 'observablepermissions')
	end
end