# This class encapsulates potentially multiple DefectHistory items (which will occur if the user
# changes more than one property of Defect in an update). The only way it should be accessed is:
#
# DefectHistoryGroup.find_by_defectid(defectid), which will return an Array of DefectHistoryGroup
# objects for the Defct identified by defectid. Each group will represent DefectHistory items of
# the same modtime.
class DefectHistoryGroup

	# Returns:
	#		An Array of DefectHistoryGroups (aggregates change information stored in multiple
	#		DefectHistory records into one DefectHistoryGroup - for display purposes).
	def DefectHistoryGroup.find_by_defectid(defectid)
		history_groups = Array.new;
		
		histories = DefectHistory.find(:all, :conditions => ["defectid = ?", defectid]);
		
		# Get a list of all the modtimes of the DefectHistory records associated with the Defect
		# identified by defectid. The modtime will be the same for all DefectHistory records
		# that got entered during the same update event.
		modtimes = Array.new;
		histories.each {|history|
			modtimes << history.modtime
		};
		
		# Take out duplicate modtimes.
		modtimes.uniq!;
		
		# Make a DefectHistoryGroup out of each set of DefectHistory records with the same
		# modtime. Add the DefectHistory group to the list of DefectHistoryGroups.
		modtimes.each {|modtime|
			history_set = histories.select {|history|
				history.modtime == modtime;
			};
			
			historygroup = DefectHistoryGroup.new(history_set);
			history_groups << historygroup;
		};
		
		return history_groups;
	end
	
	
	# Initialize the DefectHistoryGroup with an Array of DefectHistory objects.
	def initialize(history_set)
	
		@modtime = history_set.first.modtime;
		@moduserid = history_set.first.moduserid;
		@comment = history_set.first.comment;
		@changes = "";
		
		history_set.each { |defecthistory|
			display_changecolumn = defecthistory.display_changecolumn();
			display_fromvalue = defecthistory.display_fromvalue();
			display_tovalue = defecthistory.display_tovalue();
			
			if (!display_changecolumn.nil?)
				@changes += "#{display_changecolumn} changed from \"#{display_fromvalue}\" to \"#{display_tovalue}\"";
				if (defecthistory != history_set.last)
					@changes += ", ";
				end
			end
		}
		
	end
	
	
	# The time when the Defect was modified.
	def modtime
		return Time.at(@modtime);
	end
	
	
	# The username of the person who modified the Defect.
	def modusername
		user = User.find(:first, @moduserid);

		if (user.nil?)
			return "";
		end
		
		return user.username;
	end
	
	
	# The comment associated with this change to the Defect.
	def comment
		return @comment;
	end
	
	
	# A textual concatenated description of changes that occured to the various
	# properties of the associated Defect.
	def changes
		return @changes;
	end
	
end