require 'ostruct'
lib_require :Observations, 'status'

module Observations
	class StatusPage < PageHandler
		PAGE_LENGTH = 20
		
		declare_handlers("status") {
			area :User
			page :GetRequest, :Full, :status_history, 'history'
			page :GetRequest, :Full, :status_history, 'history', input(Integer)
			area :Self
			page :GetRequest, :Full, :status_history, 'history'
			page :GetRequest, :Full, :status_history, 'history', input(Integer)
			page :GetRequest, :Full, :current_status
			page :GetRequest, :Full, :status_event, input(/tonight|weekend/)
			page :GetRequest, :Full, :status_summary, input(/general|listening/)
			handle :GetRequest, :current_status_xml, 'xml'
			handle :PostRequest, :update_status, 'update'
			handle :PostRequest, :update_status_async, 'update', 'async'
			handle :PostRequest, :copy_status, 'join'
			handle :PostRequest, :delete_status, 'delete'
		}
		
		def status_event_test(*args)
			request.instance_variable_set(:"@user", User.get_by_id(200))
			status_event(*args)
		end
		def status_summary_test(*args)
			request.instance_variable_set(:"@user", User.get_by_id(200))
			status_summary(*args)
		end
		
		def status_history(page=0)
			t = Template.instance('updates', 'status_history')
			t.updates = Status.find(request.user.userid, :limit => "#{PAGE_LENGTH*page},#{PAGE_LENGTH*(page+1)}", :order => "creation DESC")
			puts t.display
		end
		
		def current_status
			t = Template.instance('observations', 'edit_status')
			t.current_status = []
			types = Status.types;
			latest = Status.all_latest(request.user)
			types.each {|type|
				stat = latest[type]
				unless (stat.created?)
					stat.meta.style = "display:none"
				end
				t.current_status << stat
			}
			puts t.display
		end
		
		def status_summary(current_type = "general")
			current_type = current_type.to_s
			current_type = "general" unless (Status.types.include?(current_type.to_sym))
			t = Template.instance('observations', 'status_summary')
			#t.types = self.class.get_types(current_type)
			friends = request.user.friends.map {|friend| friend.user}.select{|friend| !(friend.kind_of? Friend::Fake)}
			t.statuses = Status.active(friends, current_type.to_sym)
			request.reply.headers['X-width'] = 630;
			puts UpdatePages::status_page(t.display, current_type, request.user)
		end

		Event = Struct.new(:message, :users, :status, :joinable)
		def status_event(current_type = "tonight")
			current_type = current_type.to_s
			t = Template.instance('observations', 'status_event')
			#t.types = self.class.get_types(current_type)
			friends = request.user.friends.map {|friend| friend.user}.select{|friend| !(friend.kind_of? Friend::Fake)}
			statuses = Status.active(friends, current_type.to_sym)
			events = {}
			statuses.each {|status|
				if (events[status.message])
					events[status.message] << status
				else
					events[status.message] = [status]
				end
			}
			events.each_pair {|message, event| event.sort_by {|status| -status.creation} }
			events = events.sort_by {|event| -event.last.first.creation}
			
			prefix = ""
			case current_type
			when "tonight"
				prefix = "Tonight "
			when "weekend"
				prefix = "This weekend "
			end
			events = events.map {|event|
				status_message = prefix.dup
				if (event.last.length > 2)
					event.last.each_with_index {|status, i|
						unless (i == event.last.length-1)
							status_message << status.user.link << ', '
						else
							status_message << 'and ' << status.user.link << ' are ' << status.message
						end
					}
				elsif (event.last.length == 1)
					status_message << event.last.first.user.link << ' is ' << event.last.first.message
				else
					status_message << event.last.first.user.link << ' and ' << event.last.last.user.link << ' are ' << event.last.first.message
				end
				Event.new(status_message, event.last.map {|status| status.user}, event.last.first, !event.last.map {|status| status.user}.include?(request.user))
			}
			t.events = events
			request.reply.headers['X-width'] = 630;
			puts UpdatePages::status_page(t.display, current_type, request.user);
		end
		
		def current_status_xml
			reply.headers['Content-Type'] = PageRequest::MimeType::XML;
			puts "<?xml version = \"1.0\"?>";
			puts "<status>"
			Status.enums[:type].each_key {|type|
				latest = Status.latest(request.user, type)
				if (latest.created?)
					puts "<#{type} display=\"true\">#{latest.prefix} #{latest.message}</#{type}>"
				else
					puts "<#{type} display=\"false\"/>"
				end
			}
			puts "</status>"
		end
		
		def update_status_async
			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
			update_status(true)
		end
		
		def update_status(async=false)
			Status.enums[:type].each_key {|type|
				body = params[type.to_s, String, nil]
				set_status(type, body) if body
			}
			if (async)
				current_status
			else
				site_redirect('/updates')
			end
		end
		
		def delete_status
			type = params["type", Symbol]
			status = Status.latest(request.user, type)
			status.expire!;
		end
		
		def copy_status
			userid = params["user", Integer, 0]
			id = params["id", Integer, 0]
			type = params["type", Symbol]
			to_be_copied = Status.find(:first, userid, id)
			if (to_be_copied && type == to_be_copied.type)
				old_status = Status.latest(request.user, type)
				if (to_be_copied.message != old_status.message)
					old_status.expire!
					new_status = Status.new
					new_status.message = to_be_copied.message
					new_status.type = type
					new_status.userid = request.user.userid
					new_status.store
				end
			end
			site_redirect("/status/#{type}")
		end
			
		def set_status(type, body)
			old = Status.latest(request.user, type)
			body.strip!
			if (old.message != body)
				old.expiry = Time.now.to_i
				old.store if old.created? #expire the old status message if it exists
				if (body != "")
					new_status = Status.new
					new_status.message = body
					new_status.type = type
					new_status.userid = request.user.userid
					new_status.store
				end
			end
		end

		class << self
			def get_types(current_type=nil)
				types = []
				Status.types.each {|type|
					struct_type = OpenStruct.new
					struct_type.symbol = "/my/status/#{type}"
					struct_type.name = Status::NAMES[type]
					struct_type.css_class = "selected" if (type.to_s == current_type)
					types << struct_type
				}
				return types
			end
		end
	end
end