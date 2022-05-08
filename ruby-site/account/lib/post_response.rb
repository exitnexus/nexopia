module AccountManagement
	class PostResponse
		attr_accessor :state

		def initialize(state, primary_text, secondary_text=nil)
			@state = state
			@primary_text = primary_text
			@secondary_text = secondary_text
		end
		
		def primary_text
			return BBCode.parse(@primary_text)
		end
		
		def secondary_text
			return @secondary_text.nil? ? nil : BBCode.parse(@secondary_text)
		end
		
		def to_s
			return primary_text
		end
	end
end