lib_require :Core, 'storable/storable'

# DefectHistory (Storable)
#
# Properties:
#		id: Automatically assigned upon DefectHistory.new.
#		defectid: The ID of the associated Defect.
#		moduserid: The userid of the User that made the modification.
#		changecolumn: The column name of the column from Defect that got changed.
#		fromvalue: The value the column from Defect was originally.
#		tovalue: The value the column from Defect has been changed to.
#		comment: A comment associated with the change.
#		modtime: A timestamp for when the change was made.
#
# Virtual properties:
#		display_changecolumn: Returns the preferred display version of the column that was changed.
#		display_fromvalue: Returns the preferred display version of the fromvalue.
#		display_tovalue: Returns the preferred display version of the column that was changed.
class DefectHistory < Storable
	init_storable(:taskdb, 'defecthistory');
	
	
	# Return: a more user-friendly version of the changecolumn field (don't want to 
	#		display ID values).
	def display_changecolumn()
		if (changecolumn == "ownuserid")
			return "ownusername";
		end
		
		return changecolumn;
	end
	
	# Return: a more user-friendly version of the fromvalue field (don't want to 
	#		display ID values).
	def display_fromvalue()
		if (changecolumn == "ownuserid")
			return get_username(fromvalue);
		end
		
		return fromvalue;
	end
	
	# Return: a more user-friendly version of the tovalue field (don't want to 
	#		display ID values).
	def display_tovalue()
		if (changecolumn == "ownuserid")
			return get_username(tovalue);
		end
		
		return tovalue;
	end
	
	
	# Helper method to grab the username for a userid.
	def get_username(userid)

		user = User.find(:first, userid);
		if (!user.nil?)
			return user.username;
		else
			return "";
		end
	end
	
end