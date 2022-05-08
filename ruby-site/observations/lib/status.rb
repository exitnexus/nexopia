lib_require :Core, 'storable/storable', 'typeid'
lib_require :Observations, 'observable'

module Observations
	class Status < Storable
		extend TypeID;
		include Observable
		OBSERVABLE_NAME = "Status Changes"
		
		set_enums(
			:type => {
				:general => 0, 
				:tonight => 1, 
				:weekend => 2,
				:listening => 3
			}
		)
		NAMES = {
			:general => "Currently", 
			:tonight => "Tonight", 
			:weekend => "Weekend",
			:listening => "Listening"
		}
		
		init_storable(:usersdb, 'status')
		
		relation_singular :user, :userid, User
		user_content :message, :bbcode => false;
		
		EVENT_SHOW = true #include status updates by default in events
		
		def collapsable_bucket(event)
			return [self.userid, self.type];
		end
		
		def collapsed_event(list)
			return list.sort{|a,b| b.object.creation <=> a.object.creation }.first
		end

		def before_create
			self.creation = Time.now.to_i
			self.expiry = self.class.calculate_expiry(self.type)
			self.id = self.class.get_seq_id(self.userid)
		end
		
		def after_create
			super
		end
	
		def after_update
			#Do nothing. This overrides Observable's after_update.
			#Worker::PostProcessQueue.queue(ObservableEvent, "create", [self, event_enums[:edit], PageRequest.current.session.user.userid, Time.now], false);
		end

		observable_event :create, proc{self.display_prefix + CGI::escapeHTML(self.message.parsed)}

		def display_prefix
			case self.type
			when :general
				return "#{self.user.link} "
			when :tonight
				return "Tonight #{self.user.link} is "
			when :weekend
				return "This weekend #{self.user.link} is "
			when :listening
				return "#{self.user.link} is listening to "
			end
		end
		
		def status_message
			return self.prefix + self.message
		end
		
		#See Time#strftime for details on format options
		def formatted_creation(format="%b %d, %I:%M%p")
			return Time.at(creation).strftime(format)
		end
		
		def since_creation
			since = Time.now.to_i - creation
			message = ""
			case since
			when (0...60)
				message = "moments"
			when (60...3600)
				message = "#{(since/60).to_i} minute"
				message += 's' if ((since/60).to_i > 1)
			when (3600...86400)
				message = "#{(since/3600).to_i} hour"
				message += 's' if ((since/3600).to_i > 1)
			else
				message = "#{(since/86400).to_i} day"
				message += 's' if ((since/86400).to_i > 1)
			end
			return message
		end
		
		def prefix
			case self.type
			when :general
				return "#{self.user.username} "
			when :tonight
				return "Tonight #{self.user.username} is "
			when :weekend
				return "This weekend #{self.user.username} is "
			when :listening
				return "#{self.user.username} is listening to "
			end
		end
		
		def expire!
			self.expiry = Time.now.to_i
			self.store if (self.created?)
		end
		
		def refresh
			self.creation = Time.now.to_i
			self.expiry = self.class.calculate_expiry(self.type)
			self.store if (self.created?)
		end
	
		class << self
			def types
				return self.enums[:type].keys.sort_by {|key| self.enums[:type][key]}
			end
			
			#returns the latest active status update from the database, if no active update exists
			#a new status update of the type specified is created, type general if no type is given
			#user may be a user object or userid, type should be the symbol for the type.
			def latest(user, type=nil)
				if (user.kind_of?(User))
					uid = user.userid
				else
					uid = user
				end
				latest = nil
				if (type == nil)
					latest = self.find(:first, uid, :conditions => ["expiry > ?", Time.now.to_i], :order => "creation DESC")
				else
					latest = self.find(:first, uid, self.enums[:type][type], :index => :usertype, :conditions => ["expiry > ?", Time.now.to_i], :order => "creation DESC")
				end
				if (latest.nil?)
					type ||= :general
					latest = self.new
					latest.type = type
					latest.userid = uid
				end
				return latest
			end
			
			def all_latest(user)
				user = user.userid if (user.kind_of?(User))
				ids = self.types.map {|type|
					[user, self.enums[:type][type]]
				}
				results = self.find(:limit => ids.length, :index => :usertype, :conditions => ["expiry > ?", Time.now.to_i], :order => "creation DESC", *ids)
				latest = {}
				results.each {|status|
					latest[status.type] = status
				}
				Status.types.each {|type|
					unless latest[type]
						latest[type] = self.new
						latest[type].type = type
						latest[type].userid = user
					end
				}
				return latest
			end
			
			def active(users, type, limit=nil)
				ids = users.map {|user|
					if (user.kind_of?(User))
						[user.userid, Status.enums[:type][type]]
					else
						[user, Status.enums[:type][type]]
					end
				}
				if (limit)
					results = self.find(:limit => limit, :index => :usertype, :conditions => ["expiry > ?", Time.now.to_i], :order => "creation DESC", *ids)
				else
					results = self.find(:index => :usertype, :conditions => ["expiry > ?", Time.now.to_i], :order => "creation DESC", *ids)
				end
				return results
			end
			
			def load_event(user_id, id)
				return find(:first, user_id, id);
			end
			
			def calculate_expiry(type)
				expiry = 0
				case type
				when :general
					expiry = Time.now.to_i + 60*60*24*14 #two weeks
				when :tonight
					now = Time.now
					seconds_left_in_day = 86400 - now.hour*3600 - now.min*60 - now.sec
					expiry = now.to_i + seconds_left_in_day + 60*60*6 #6am tomorrow
				when :weekend
					now = Time.now
					seconds_left_in_day = 86400 - now.hour*3600 - now.min*60 - now.sec
					days_left_in_week = 7-now.wday
					expiry = now.to_i + seconds_left_in_day + 86400*days_left_in_week + 60*60*6 #6am monday
				when :listening
					expiry = Time.now.to_i + 60*60*2 #two hours
				end
				return expiry
			end
		end
	end
end
