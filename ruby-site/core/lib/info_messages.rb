class InfoMessages
	attr_reader :messages
	
	def initialize
		@messages = []
	end

	def add_message(message)
		@messages << message.to_s
	end
	
	def html
		t = Template::instance("core", "info_messages")
		t.messages = self.messages
		return t.display
	end
	
	def text
		return self.messages
	end
	
	class << self
		#executes a block and logs all errors encountered via InfoMessage
		#returns true if an error was logged, false otherwise
		def display_errors(&block)
			begin
				yield
			rescue Object => error
				messages = InfoMessages.new
				messages.add_message(error)
				PageHandler.current.puts messages.html
				return messages
			end
			return false
		end
		
		def capture_errors(&block)
			begin
				yield
			rescue Object => error
				messages = InfoMessages.new
				messages.add_message(error)
				return messages				
			end
			return false
		end
	end # class << self
end # class InfoMessages