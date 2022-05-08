class HtmlDoc
	attr :body
	
	def initialize()
		@body = StringIO.new;
	end
	
	def append_body(string)
		@body << string;
	end

	
	def string()
		return @body.string;
	end
	
end
