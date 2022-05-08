class MissingProfileBlockHandler < PageHandler
	
	# This is needed for an error case with profile blocks. It prevents infinite recursion through
	#  the pagehandler system. If the block is in the database but there isn't a handler matching it's
	#  constructed path we need this so it can fall back and properly throw a 404.
	declare_handlers("profile_blocks") {
		area :User
		access_level :Any
		
		handle :GetReqeust, :missing_block;
	}
	
	# Only throw a 404 to indicate to the profile that an error occurred in whatever block it was
	#  trying to render.
	def missing_block()
		raise PageError.new(404), "Handler not found for #{request.area}/#{path.join '/'}";
	end
end