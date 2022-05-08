lib_require :Core, 'storable/storable'
lib_require :Devutils, "defecthistory"

# Defect (Storable)
#
# Properties:
#		id: Automatically assigned upon Defect.new.
#		description: The description of the Defect.
#		workaround: The workaround for the Defect.
#		repuserid: The userid of the user that reported the Defect.
#		ownuserid: The userid of the user to which the Defect is currently assigned.
#		type: The type of Defect (:bug, :suggestion, :cosmetic).
#		priority: The priority of fixing the Defect (:low, :medium, :high).
#		status: The status of the Defect (:open, :closed, :fixed, :inprogress, :deferred).
#
# Virtual properties:
#		repusername: Returns the name of the User identified by repuserid.
#		ownusername: Returns the name of the User identified by ownuserid.
class Defect < Storable

	set_enums(
		:status => {:open => 0, :closed => 1, :fixed => 2, :inprogress => 3, :deferred => 4 },
		:type => {:bug => 0, :suggestion =>1, :cosmetic => 2 },
		:priority => {:low => 0, :medium =>1, :high => 2}
	);	
	
	init_storable(:taskdb, 'defect');

	relation_singular :repuser, :repuserid, User;
	relation_singular :ownuser, :ownuserid, User;
	
	def initialize(*args)
		super(*args);
		
		@initial_values = Hash.new;
	end
	
	
	# Overridden from superclass to reset the copy of original values after a
	# Defect is created.
	def after_create();
		reset_initial_values();
	end
	
	# Overridden from superclass to reset the copy of original values after a
	# Defect is loaded.
	def after_load()
		reset_initial_values();
	end
	
	# Resets the copy of original values of the Defect.
	def reset_initial_values
		db.list_fields(table).each { |column|
				column = Column.new(column);

				# Store the current value of the column in the old_values hash.
				@initial_values["#{column.name}"] = self.send("#{column.name}".to_sym);
		}		
	end
	
	
	# Records the in-memory set of values to DefectHistory objects.
	def store_defecthistory(moduserid, comment)
	
		modtime = Time.now.to_i();
		
		changed = false;
		
		@initial_values.each { |key, value|
			oldvalue = value;
			newvalue = self.send("#{key}".to_sym);
			if (oldvalue != newvalue)
				defecthistory = DefectHistory.new;
				defecthistory.defectid = id;
				defecthistory.moduserid = moduserid;
				defecthistory.changecolumn = key;
				defecthistory.fromvalue = oldvalue;
				defecthistory.tovalue = newvalue
				defecthistory.comment = comment;
				defecthistory.modtime = modtime;
				defecthistory.store();
				
				changed = true;
				
			end
		};
		
		# If nothing actually changed, just store the comment.
		if (!changed)
				defecthistory = DefectHistory.new;
				defecthistory.defectid = id;
				defecthistory.moduserid = moduserid;
				defecthistory.comment = comment;
				defecthistory.modtime = modtime;
				defecthistory.store();			
		end
		
		reset_initial_values();
	end
	
	
	# Maps to the <a t:id="defect"></a> tag in list_defects.html.
	# Returns:
	#		[The text to display in the hyperlink, The text to use as the url path]
	def uri_info(mode = 'self')
		maxlength = 80;
	
		# TODO: I had originally assumed that "mode" represented a sort of access level. Thus, I
		# was hoping to set things up so that when the user isn't logged in, he/she will be taken
		# to a "view" page upon clicking a defect instead of an "edit" page. Unfortunately, this
		# is not the case, so the link is still broken when the user tries to click on a Defect when
		# not logged in. Is there any good way to do this sort of access control?
		
		if (mode == 'self')
			summary = description;
			if (summary.size() > maxlength)
				summary = summary[0,maxlength];
			end
			return [summary, url/:my/:defects/id];
		else
			return [summary, url/:defects/id];
		end
	end

	
	def summary
		maxlength = 80;

		if (description.size() > maxlength)
			return description[0,maxlength];
		end
		
		return description;
	end


	# Returns:
	#		The name of the user identified by defect.repuserid
	def repusername()
		user = repuser
		
		# This should never happen, as the column is defined as non-nullable
		if (user.nil?)
			return "";
		end
		
		return user.username
	end
	
	# Returns:
	#		The name of the user identified by defect.ownuserid
	def ownusername()
		user = ownuser
		
		if (user.nil?)
			return "";
		end
		
		return user.username
	end
	
end