lib_require :Observations, 'observable_event'
lib_require :Observations, 'site_event'
lib_require :Observations, 'status'
lib_require :Observations, 'observation_preferences'
lib_require :Friends, "friend"
lib_require :core, "user_time"

module Observations
=begin
	class Display < PageHandler
		declare_handlers("ObservableDisplay") {
			area :Internal
			handle :GetRequest, :display, "display"
		}

		def display()
			obj = params.to_hash["obj"];
			type = params.to_hash["type"];
			obj.display_message(type)
		end
		
	end
=end
	class UpdatePages < PageHandler
		
		declare_handlers("/") {
			area   :Public
			page   :GetRequest, :Full, :test_status_updates,   "test_status_updates"
			
			area   :Self
			page   :GetRequest, :Full, :my_status_updates,  "updates"
			page   :GetRequest, :Full, :status_menu,       "status_menu"
			page   :GetRequest, :Full, :friend_updates,   "friends_updates"
			page   :GetRequest, :Full, :post_update,   "post_an_update"
			page   :GetRequest, :Full, :events, "events"

			page   :PostRequest, :Full, :delete_update, "friends_updates", "delete"
			page   :PostRequest, :Full, :delete_update_self, "updates", "delete"

			area   :Internal
			handle :GetRequest, :my_status_updates_internal,   "updates_internal"
			handle :GetRequest, :friend_updates_internal,   "friends_updates_internal"
		}
		
		def events
			Observable.classes.each{|klass|
				klass.html_dump
			}	
		end
		
		def delete_update()
			userid = params["user", Integer];
			originatorid = params["originator", Integer];
			eventid = params["eventid", Integer];
			WorkerModule.do_task(Observations, "delete_log", [userid, originatorid, eventid, Time.now], false);
			if (params['ajax', String] != "true")
				external_redirect("/my/updates")
			end
		end

		def delete_update_self()
			originatorid = params["originator", Integer];
			eventid = params["eventid", Integer];
			WorkerModule.do_task(Observations, "delete_event", [originatorid, eventid]);
			
			if (params['ajax', String] != "true")
				external_redirect("/my/updates")
			end
		end
		
		def test_status_updates
			request.send(:instance_variable_set, :@user, User.get_by_id(203));
			t = Template.instance("observations", "friend_updates")
			count = 20;
			page = params['page', Integer, 1];
			t.entries = ObservableEvent.friend_events(203, count, page);
			t.user = User.find(:first, 203);
			request.reply.headers['X-width'] = 630;
			
			puts UpdatePages::status_page(t.display, "friends updates", User.get_by_id(203));
		end

		class << self
			@@pages = [["friends updates", "/my/friends_updates"], ["my updates", "/my/updates"]];
			def get_pages(current_page=nil)
				types = []
				@@pages.each {|name, url|
					struct_type = OpenStruct.new
					struct_type.symbol = url
					struct_type.name = name
					struct_type.css_class = "selected" if (name.to_s == current_page)
					types << struct_type
				}
				return types
			end
			
			def status_page(page_text, page, user)
	
				t = Template.instance("observations", "status_menu")
				t.user = user;
				t.types = get_pages(page).concat(Observations::StatusPage.get_types(page))
				t.page_content = page_text;
				
				return t.display();
				
			end
		end

		def friend_updates
			t = Template.instance("observations", "friend_updates")
			prefs = ObservationPreferences.new(session.user.userid);
			count = 20;
			page = params['page', Integer, 1];
			
			friend_events = FriendEvent.events_by_day(session.user.userid).map{|m|m};
			site_events = SiteEvent.events_by_day().map{|m|m};
			
			site_event_prefs = SiteEventPreference.find(:all, request.user.userid)
			site_events = site_events.select{|event|
				if (site_event_prefs[[request.user.userid, event.type]])
					site_event_prefs[[request.user.userid, event.type]].allowed
				else
					true
				end
			}
			
			all_entries = friend_events + site_events
			#t.entries = ().sort{|a,b| b.time <=> a.time }

			t.days = [];
			t.days << OpenStruct.new({ :day => 'Today', 
				:entries => all_entries.select{|e| UserTime.new(e.time).strftime("%B%d") == UserTime.new(Time.now).strftime("%B%d") } });
			t.days << OpenStruct.new({ :day => 'Yesterday',
				:entries => all_entries.select{|e| UserTime.new(Time.at(e.time)).strftime("%B%d") == UserTime.new(Time.now - 86400).strftime("%B%d") } });
			t.days << OpenStruct.new({ :day => 'All Time',
				:entries => all_entries.select{|e| true } });
			
			t.user = User.find(:first, session.user.userid);
			request.reply.headers['X-width'] = 630;
			
			puts UpdatePages::status_page(t.display, "friends updates", session.user)
		end

		def my_status_updates
			t = Template.instance("observations", "my_updates")
			t.types = Observations::StatusPage.get_types;
			prefs = ObservationPreferences.new(session.user.userid);
			count = 20;
			page = params['page', Integer, 1];
			t.entries = UserEvent.events_by_day(session.user.userid);
			t.user = User.find(:first, session.user.userid);
			request.reply.headers['X-width'] = 630;
			puts UpdatePages::status_page(t.display, "my updates", session.user)
		end
		
		def status_menu
			t = Template.instance("observations", "updates_menu")
			puts t.display
		end
		
	end
end
