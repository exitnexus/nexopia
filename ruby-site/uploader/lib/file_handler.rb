
module Kernel
	@@file_handlers = {}
	
	#Register a file handler for your module.
	#
	#@match should be an array of the file class names.  It will be used as
	#the url where your files will be available.
	#@handler should be a proc that takes arguments <filename, fileclass>, both
	#strings.
	def file_handler(match, handler)
		@@file_handlers[match] = handler;
	end

end
