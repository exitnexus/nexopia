class Terms
	
	def Terms.with_breaks
		return Terms.source.gsub(/\n/,"<br/>");
	end
	
	
	def Terms.source
		# terms.html cannot be accessed via a Template even though it's in the templates directory.
		# The reason for this is that we want to be able to convert the line breaks into <br/> tags
		# and the Template code removes line breaks by default.
		file = File.open("accountcreate/templates/terms.html");
		
		return file.read;
	end

end