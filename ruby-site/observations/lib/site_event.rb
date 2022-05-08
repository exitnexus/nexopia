lib_require :Core, "storable/storable"
lib_require :Observations, "observable"

class SiteEvent < Storable
	init_storable(:db, "siteevents")
	
	def self.events_by_day()
		return self.find(:all, :conditions => ["time > ?", Time.now.to_i - (86400*2)])
	end
	
	def display_message
		return text
	end
	
	def time
		return @time
	end
	
	def userid
		return 0
	end
	
	def originatorid
		return 0
	end
	
	def image
		return self
	end
	
	def img_info
		return ["Nexopia", ""]
	end
	
end

class SiteEventType < Storable
	init_storable(:db, "siteeventtypes")
	
	attr :description, true;
	
	@@loaded = [];
	def self.loaded
		return @@loaded
	end
	
	def self.get_type(mod, symbol)
		if not (obj = find(:first, :name, [symbol, mod]))
			obj = SiteEventType.new;
			obj.name = symbol;
			obj.module = mod;
			obj.store();
		end
		@@loaded << obj
		obj
	end
end

class SiteEventPreference < Storable
	init_storable(:usersdb, "siteeventpreferences")
end

module Kernel
	def site_event(symbol, description)
		if (!@@current_module)
			raise "Not in a site module."
		end
		mod = Object.const_get("#{@@current_module.to_s}Module".to_sym).typeid
		event_type = SiteEventType.get_type(mod, symbol)
		event_type.description = description
	end
	
end
