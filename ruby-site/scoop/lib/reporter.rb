lib_require :Core, 'chain_method'
lib_require :Scoop, 'event', 'story', 'subscription'

#Scoop::Reporter is responsible for the initial creation of events
module Scoop
	module Reporter
		attr_writer(:userid_column, :max_stories, :delay, :subscribe)
		attr_accessor(:report_readers, :sort, :after)

		DEFAULT_MAX_STORIES = 25
		DEFAULT_DELAY = 0
		
		def self.extend_object(obj)
			#define an instance level method that will actually create and store an event
			obj.send(:define_method, :generate_event) {|type|
				
				event = Event.new
				event.userid = self.send(self.class.userid_column)
				event.id = Event.get_seq_id(self.send(self.class.userid_column))
				event.typeid = self.class.typeid
				event.primaryid, event.secondaryid = [*self.get_primary_key]
				event.event = type
				event.store
				Scoop::Event.distribute_event_defer_delay(self.class.delay, event.userid, event.id)
				
				if(self.class.subscribe)
					subscribe = self.class.subscribe
					subscription = Subscription.new
					subscription.primaryid = self.send(subscribe[:primaryid])
					subscription.typeid = subscribe[:typeid]
					subscription.secondaryid = self.send(subscribe[:secondaryid])
					subscription.userid = self.send(subscribe[:userid])
					
					subscription.store(:ignore)
				end
			}
			
			obj.send(:define_method, :redistribute_events) {|type|
				primaryid, secondaryid = [*self.get_primary_key]
				events = Event.find(:all, self.class.typeid, primaryid, secondaryid, :reporter)
				events.each {|event|
					Scoop::Event.redistribute_event_defer(event.userid, event.id)
				}
			}
			
			obj.send(:define_method, :delete_events) {|type|
				primaryid, secondaryid = [*self.get_primary_key]
				events = Event.find(:all, self.class.typeid, primaryid, secondaryid, :reporter)
				events.each {|event|
					Scoop::Event.delete_event_defer(event.userid, event.id)
				}
			}
			
			obj.send(:define_method, :can_view_event?) { |userid|
				if (self.class.instance_variable_defined?(:@restrict_func))
					method = self.class.instance_variable_get(:@restrict_func)
					return self.send(method, userid)
				else
					return true
				end
			}
			super
		end

		def userid_column
			if (instance_variable_defined?(:"@userid_column"))
				return @userid_column
			else
				return :userid
			end
		end

		def max_stories
			if (instance_variable_defined?(:"@max_stories"))
				return @max_stories
			else
				return DEFAULT_MAX_STORIES
			end
		end

		def delay
			if (instance_variable_defined?(:"@delay"))
				return @delay
			else
				return DEFAULT_DELAY
			end
		end

		def subscribe
			if (instance_variable_defined?(:"@subscribe"))
				return @subscribe
			else
				return nil
			end
		end

		def report(*events)
			events.each {|event|
				if (event.kind_of?(Hash))
					update_reporter_config(event)
				else
					method = :"report_#{event}"
					if (self.respond_to?(method))
						self.send(method)
					else
						raise Exception.new("Attempted to report undefined event: #{event} in #{self}.")
					end
				end
			}
		end
		
		def update_reporter_config(config)
			config.each_pair {|key,val|
				self.send(:"#{key}=", val)
			}
		end
		
		def restrict=(func)
			@restrict_func = func
		end
		
		def report_create
			self.register_event_hook(:after_create) {
				self.generate_event(:create)
			}
			
			self.register_event_hook(:after_update) {
				self.redistribute_events(:create)
			}
			
			self.register_event_hook(:before_delete) {
				self.delete_events(:create)
			}
		end
		
		def report_update
			self.register_event_hook(:after_update) {
				self.generate_event(:update)
			}
		end
		
		def report_delete
			self.register_event_hook(:before_delete) {
				self.generate_event(:delete)
			}
		end
		
		
		def clean_up_stories(userid)
			stories = Scoop::Story.find(:user_type, userid, self.typeid)
			#automatically delete anything that doesn't have a reporter first
			stories.reject! {|story|
				if (story.reporter.nil?)
					story.delete 
					true
				else
					false
				end
			}
			
			if (sort)
				#the weird array stuff makes it so that nil reporters always drop to the end.
				stories = stories.sort_by {|story| story.reporter.send(sort)}
			else
				stories = stories.sort_by {|story| -story.id}
			end
			stories_to_delete = stories[max_stories, stories.length-max_stories] || []
			stories_to_delete.each {|story|
				story.delete
			}
		end
	end
end

class Storable
	class << self
		remove_method(:report)
	end
	extend Scoop::Reporter
end