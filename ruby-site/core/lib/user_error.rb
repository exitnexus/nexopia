class UserError < StandardError
	# UserErrors are, by default, logged at :debug level even if not caught.
	def warn_level()
		return nil;
	end
end