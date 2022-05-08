lib_require :Observations, 'observable_permissions', 'observation_preferences'

module Observations
	class ManageObservations < PageHandler
		declare_handlers("updates") {
			area :Self
			page :GetRequest, :Full, :edit_permissions, 'permissions'
			page :GetRequest, :Full, :edit_preferences, 'preferences'
			page :GetRequest, :Full, :edit_site_preferences, 'site_preferences'
			handle :PostRequest, :set_permissions, 'permissions', 'set'
			handle :PostRequest, :set_preferences, 'preferences', 'set'
			handle :PostRequest, :set_site_preferences, 'site_preferences', 'set'
			handle :PostRequest, :add_preference, 'preferences', 'add'
			handle :PostRequest, :delete_preference, 'preferences', 'delete'
		}
		
		def edit_permissions
			t = Template.instance('observations', 'edit_permissions')
			t.observables = Observable.classes
			
			existing_permissions = ObservablePermission.find(request.user.userid).to_hash
			
			permissions = {}
			Observable.classes.each {|observable_class|
				if (existing_permissions[[request.user.userid, observable_class.typeid]])
					permissions[observable_class] = existing_permissions[[request.user.userid, observable_class.typeid]].allow
				else
					permissions[observable_class] = observable_class.observable_default
				end
			}
			t.permissions = permissions
			t.my_selected = "selected"
			puts t.display
		end
		
		def edit_preferences
			t = Template.instance('observations', 'edit_preferences')
			t.observables = Observable.classes
			
			existing_preferences = ObservationPreference.find(request.user.userid, 0).to_hash
			
			preferences = {}
			Observable.classes.each {|observable_class|
				if (existing_preferences[[request.user.userid, 0, observable_class.typeid]])
					preferences[observable_class] = existing_preferences[[request.user.userid, 0, observable_class.typeid]].display
				else
					preferences[observable_class] = observable_class.observable_default
				end
			}
			t.preferences = preferences
			rules = ObservationPreference.find(request.user.userid, :conditions => "eventuserid != 0")
			cp = [];
			rules.map{|rule|
				if (rule.eventuser != nil)
					cp << rule
				end
			}
			t.rules = cp;
			t.friends = request.user.friends
			t.friends_selected = "selected"
			puts t.display
		end
		
		def edit_site_preferences
			t = Template::instance('observations', 'edit_site_preferences')
			preferences = {}
			site_event_preferences = SiteEventPreference.find(request.user.userid).to_hash
			SiteEventType.loaded.each{|type|
				if (site_event_preferences[[request.user.userid, type.id]])
					preferences[type] = site_event_preferences[[request.user.userid, type.id]].allowed
				else
					preferences[type] = true;
				end
			}
			t.preferences = preferences
			t.site_selected = "selected"
			puts t.display
		end
		
		def set_permissions
			observable = params["observable", TypeSafeHash, TypeSafeHash.new({})]
			allow_typeids = []
			observable.each_pair(String) {|string_id, value|
				if (value.downcase == "on")
					id = string_id.to_i
					allow_typeids << id
				end
			}
			ObservablePermissions.adjust_permissions(request.user.userid, allow_typeids)
			site_redirect('/updates/permissions')
		end
		
		def set_preferences
			observable = params["observable", TypeSafeHash, TypeSafeHash.new({})]
			allow_typeids = []
			observable.each_pair(String) {|string_id, value|
				if (value.downcase == "on")
					id = string_id.to_i
					allow_typeids << id
				end
			}
			ObservationPreferences.adjust_preferences(request.user.userid, allow_typeids)
			site_redirect('/updates/preferences')
		end

		def set_site_preferences
			events = params["site_event", TypeSafeHash, TypeSafeHash.new({})]
			existing_prefs = SiteEventPreference.find(:all, request.user.userid).to_hash
			SiteEventType.loaded.each{|type|
			#events.each_pair(String) {|string_id, value|
				value = events[type.id.to_s, String]
				id = [request.user.userid, type.id]
				if (value && value.downcase == "on")
					if (existing_prefs[id])
						if not (existing_prefs[id].allowed)
							existing_prefs[id].allowed = true
						end
					else
						existing_prefs[id] = SiteEventPreference.new()
						existing_prefs[id].userid = request.user.userid
						existing_prefs[id].eventtype = type.id
						existing_prefs[id].allowed = true
					end
				else
					if (existing_prefs[id])
						if (existing_prefs[id].allowed)
							existing_prefs[id].allowed = false
						end
					else
						existing_prefs[id] = SiteEventPreference.new()
						existing_prefs[id].userid = request.user.userid
						existing_prefs[id].eventtype = type.id
						existing_prefs[id].allowed = false
					end
				end
			}
			existing_prefs.each_pair{|key, pref|
				pref.store
			}
			site_redirect('/updates/site_preferences')
		end

		def add_preference
			friend = params["friend", Integer, nil]
			observable = params["observable", Integer, nil]
			display = params["display", String, nil]
			#if we didn't get all the expected params don't do anything
			site_redirect('/my/updates/preferences') if (friend.nil? || observable.nil? || display.nil?)
			pref = ObservationPreference.find(:first, request.user.userid, friend, observable)
			pref ||= ObservationPreference.new
			
			pref.userid = request.user.userid
			pref.eventuserid = friend
			pref.typeid = observable
			pref.display = display == "true"
			pref.store
			site_redirect('/updates/preferences')
		end
		
		def delete_preference
			friend = params["friend", Integer, nil]
			observable = params["observable", Integer, nil]
			#if we didn't get all the expected params don't do anything
			site_redirect('/my/updates/preferences') if (friend.nil? || observable.nil?)
			pref = ObservationPreference.find(:first, request.user.userid, friend, observable)
			if (pref)
				pref.delete
			end
			site_redirect('/updates/preferences')
		end
	end
end