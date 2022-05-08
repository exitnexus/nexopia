lib_want :Worker, 'post_process_queue'

module Observations


	module Observable
		OBSERVABLE_DEFAULT = true
		
#		def display_message(event_type)
#			return "Class '#{self.class}' does not implement display message. #{self.inspect}"
#		end

		# Should be reimplimented in subclasses to collapse similar events in
		# the list. Should collapse all events similar to @first_event.
		#
		# For collapsed events, remove all the collapsed elements from the list, 
		# and return [the collapsed event, the remaining list]
		#
		# If none are collapsed, then the original event_list and first_event 
		# are returned.
		def collapse?(event)
			return false;
		end
		
		def collapsable_bucket(event)
			return self;
		end
		
		def collapsed_event(list)
			return list.first
		end
	
		
		def all_receivers()
			User.get_by_id(self.userid).friends.map{|o|o.user};
		end

		def after_create
			if (site_module_loaded?(:Worker))
				Worker::PostProcessQueue.queue(ObservationsModule, "create", [self, :create, self.userid, Time.now], false);
			end
		end
		
		def after_update
			#Bah this is too much trouble.
			#if (site_module_loaded?(:Worker))
			#	Worker::PostProcessQueue.queue(ObservationsModule, "create", [self, :edit, self.userid, Time.now], false);
			#end
		end
		
		# othermod MUST be a Storable.
		def Observable.included(othermod)
			@@observable_classes ||= []
			@@observable_classes << othermod
			@@observable_classes.uniq!
			othermod.extend TypeID unless othermod.respond_to?(:typeid)
			othermod.extend ObservableClassFunctions
			
			othermod.module_eval{
				class_variable_set(:@@events, {});
				def self.observable_event(type, *args)
					class_variable_get(:@@events)[type] = args.first
				end
			}
			othermod.module_eval{
				def display_message(type)
					pr = self.class.module_eval{ 
						class_variable_get(:@@events)[type.to_sym] 
					}
					
					if (pr)
						instance_eval &pr
					else 
						#raise "'#{type}' not handled by #{self.class.name}."
						# This is not an error because we're allowing events to 
						# be generated but ignored for some non-interesting things.
						nil
					end
				#rescue
				#	return "Error loading object. #{$!} in #{self.class.name}"
				end
			}
				
				
		
		end
		
		def Observable.classes
			return @@observable_classes
		end
		
		module ObservableClassFunctions
			

			def observable_name
				if (self.const_defined?('OBSERVABLE_NAME'))
					return self.const_get('OBSERVABLE_NAME')
				else
					return self.name
				end
			end
			
			def observable_default
				if (self.const_defined?('OBSERVABLE_DEFAULT'))
					return self.const_get('OBSERVABLE_DEFAULT')
				else
					return OBSERVABLE_DEFAULT
				end
			end
			
		end
	end
end