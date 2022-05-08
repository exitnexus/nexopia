=begin May or may not be useful?

lib_require :Core, 'storable/storable'

class ModLog < Storable
	set_enums(:action => {:none => 0});
	init_storable(:db, "modlog");

	alias _original_moduleid= moduleid=
	def moduleid=(value)
		mod = TypeID.get_class(value);
		if (!mod.nil?)
			current_action = self.action; #save the current action passed to the hash
			self.action!.hash = mod.log_actions; #reassign the enum map hash based on the current module
			self.action = current_action; #pass back in the original action to get a new back end value
			self._original_moduleid = value; #take care of setting the module id
		end
	end

	relation_singular(:user, :userid, User);
	relation_singular(:admin, :adminid, User);

	def storable()
		storable_class = TypeID.get_class(self.typeid);
		object = nil;
		if (storable_class.indexes[:PRIMARY].length > 1)
			object = storable_class.find(self.primaryid, self.secondaryid, :first);
		else
			object = storable_class.find(self.primaryid, :first);
		end
		return object;
	end

	class << self
		def log(admin, user, mod, storable, action, reason, extra1=0, extra2=0)
			entry = ModLog.new();
			entry.moduleid = mod.typeid;
			entry.typeid = storable.class.typeid;
			entry.primaryid = storable.send(storable.class.indexes[:PRIMARY][0]);
			if (storable.class.indexes[:PRIMARY].length > 1)
				entry.secondaryid = storable.send(storable.class.indexes[:PRIMARY][1]);
			end
			entry.adminid = admin.userid;
			entry.userid = user.userid unless user.nil?;
			entry.action = action;
			entry.time = Time.now.to_i;
			entry.reason = reason;
			entry.extra1 = extra1;
			entry.extra2 = extra2;
			entry.store();
		end
	end
end
=end
