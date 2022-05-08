module Devtopia

	class Log < Storable
		set_db(:devtaskdb);
		set_table("log");
		init_storable();
	end
	

	# This module can be mixed into any Storable object in the Devtopia module where logging of changes
	# is needed. Currently, it will log all field changes and all new record creations for the Storable
	# it is included in. Loggable should be included after any overridden Storable methods, as it does
	# post-chaining with them to add the logging functionality. These methods currently include:
	# on_field_change, after_update, after_create, and after_load.
	module Loggable
		
		def self.included(base)
			@track_changes = false;
			
			base.postchain_method(:on_field_change, &lambda { |field_name,return_value|
				if (@track_changes)
					log_change(field_name);
				end
			});
			
			base.postchain_method(:after_update, &lambda { 
				store_logs("UPDATE");
			});

			base.postchain_method(:after_create, &lambda { 
				self.columns.values.each { |column|
					log_change(column.name);
				};
				store_logs("CREATE");
			});
			
			base.postchain_method(:after_load, &lambda {
				@track_changes = true;
			});			
		end
		

		def logs
			@logs ||= [];
		end
		
		
		def log_change(field)
			log = Devtopia::Log.new;
			log.programmer = PageRequest.current.session.user.userid;
			log.recordkey = self.get_primary_key.to_a * ",";
			log.field = "#{self.class.table}.#{field}";
			log.value = self.send("#{field}");

			logs << log;			
		end
		
		
		def store_logs(action_type)
			logs.each { | log | 
				log.action = action_type;
				log.time = Time.now.to_i;
				log.store();
			};
			
			logs.clear();			
		end

	end
end