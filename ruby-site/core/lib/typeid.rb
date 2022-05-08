lib_require :Core, "storable/storable", "attrs/class_attr"

class TypeIDItem < Storable
	set_db(:db);
	set_table("typeid");
	init_storable();

	@@typeids = nil
	def self.load_all
		@@typeids = {}
		rows = self.find(:all, :scan)
		rows.each{|row|
			@@typeids[row.typename.downcase] = row
		}
	end

	def TypeIDItem.get_by_name(name)
		load_all() if @@typeids.nil?
		if (obj = @@typeids[name.downcase])
			return obj;
		else
			begin
				obj = TypeIDItem.new;
				obj.typename = name;
				obj.store();
			rescue SqlBase::QueryError => e
				if (e.errno == 1062)
					obj = TypeIDItem.find(:typename, :first, name)
					if (!obj)
						raise "Typeid duplicate detected, but there was no matching typeid in the database."
					end
				else
					raise # Unknown sql error, just re-raise it.
				end
			end
				
			@@typeids[name.downcase] = obj
			return obj;
		end
	end
	
end



# Importing this module into a class will allow that class to have a persistant
# numeric representation in the current installation of the site. This can be used
# to identify the class in database tables in a way that will be useful beyond
# the current execution context.
# Example:
#  class Forum
#   extend TypeID
#  end
#
#  class ForumPost
#   extend TypeID
#  end
#
#  Forum.typeid => 1
#  ForumPost.typeid => 2
module TypeID
	@@typeid_classes = {};
	@@typeid_upcase_names = {};
	@@typeid_names = {};
	attr :typeid, true;

	def TypeID.extend_object(other)
		super(other);
		other.send(:typeid=, TypeIDItem.get_by_name(other.name).send(:typeid));
		@@typeid_classes[other.typeid] = other;
		@@typeid_upcase_names[other.name.upcase] = other.typeid;
		@@typeid_names[other.name] = other.typeid;
	end

	def TypeID.get_class(typeid)
		return @@typeid_classes[typeid];
	end

	def TypeID.get_typeid(class_name, ignore_case=true)
		if (ignore_case)
			return @@typeid_upcase_names[class_name.upcase];
		else
			return @@typeid_names[class_name];
		end
	end
end
